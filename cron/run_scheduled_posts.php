<?php
/**
 * Cron Job: Process Scheduled Posts
 *
 * Runs every minute to check for posts that are due to be published.
 *
 * CRON ENTRY (add to server crontab):
 * * * * * php /path/to/social-media/cron/run_scheduled_posts.php
 *
 * On Windows (XAMPP), use Task Scheduler or run manually for testing.
 */

// Bootstrap the application
define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/env.php';
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Controller.php';
require_once APP_ROOT . '/core/Model.php';
require_once APP_ROOT . '/app/models/Post.php';
require_once APP_ROOT . '/app/models/BrandingSetting.php';
require_once APP_ROOT . '/app/models/ActivityLog.php';
require_once APP_ROOT . '/app/services/BrandingService.php';
require_once APP_ROOT . '/app/services/ZernioService.php';
require_once APP_ROOT . '/app/services/ActivityLogService.php';

// Prevent web access
if (php_sapi_name() !== 'cli' && !defined('CRON_RUNNING')) {
    die('This script can only be run from the command line.');
}

// Set client_id for BrandingService/ZernioService (used outside of a session context)
$GLOBALS['client_id'] = defined('CLIENT_ID') ? CLIENT_ID : 1;

$logFile = APP_ROOT . '/storage/cron.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function cronLog(string $message, string $logFile): void
{
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

cronLog('--- Cron started ---', $logFile);

try {
    $db = Database::connect();

    // Find all posts that are scheduled and due
    $stmt = $db->prepare(
        "SELECT * FROM posts WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()"
    );
    $stmt->execute();
    $duePosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = count($duePosts);
    cronLog("Found {$count} due post(s)", $logFile);

    if ($count === 0) {
        cronLog('Nothing to process. Exiting.', $logFile);
        exit(0);
    }

    $zernio = new ZernioService();
    $activityLog = new ActivityLogService();

    foreach ($duePosts as $post) {
        $postId = (int) $post['id'];
        cronLog("Processing post #{$postId}: {$post['title']}", $logFile);

        // Determine platforms
        $platforms = [];
        if (!empty($post['platforms'])) {
            $decoded = json_decode($post['platforms'], true);
            if (is_array($decoded)) {
                $platforms = $decoded;
            }
        }
        if (empty($platforms) && !empty($post['platform'])) {
            $platforms = [$post['platform']];
        }

        if (empty($platforms)) {
            cronLog("  No platforms set for post #{$postId}. Skipping.", $logFile);
            continue;
        }

        $allSuccess = true;
        $anySuccess = false;

        foreach ($platforms as $platform) {
            $platform = strtolower(trim($platform));
            cronLog("  Posting to {$platform}...", $logFile);

            // Make the image URL absolute if it's relative
            $imageUrl = $post['image_url'] ?? null;
            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = BASE_URL . '/' . ltrim($imageUrl, '/');
            }

            $firstComment = !empty($post['first_comment']) ? $post['first_comment'] : null;
            $result = $zernio->postNow($platform, $post['content'], $imageUrl, $firstComment);

            if ($result['success']) {
                $zernioPostId = $result['zernio_post_id'] ?? null;
                try {
                    $zernio->logPostAttempt(
                        $postId, $platform, 'success',
                        $zernio->getAccountId($platform),
                        $zernioPostId,
                        json_encode($result)
                    );
                } catch (Throwable $logErr) {
                    cronLog("  WARNING: logPostAttempt failed: {$logErr->getMessage()}", $logFile);
                }
                $anySuccess = true;
                cronLog("  {$platform}: SUCCESS (zernio_id: {$zernioPostId})", $logFile);
            } else {
                $error = $result['error'] ?? 'Unknown error';
                try {
                    $zernio->logPostAttempt(
                        $postId, $platform, 'failed',
                        $zernio->getAccountId($platform),
                        null,
                        json_encode($result),
                        $error
                    );
                } catch (Throwable $logErr) {
                    cronLog("  WARNING: logPostAttempt failed: {$logErr->getMessage()}", $logFile);
                }
                $allSuccess = false;
                cronLog("  {$platform}: FAILED ({$error})", $logFile);
            }
        }

        // Update post status
        $newStatus = $allSuccess ? 'published' : ($anySuccess ? 'published' : 'failed');
        $updateStmt = $db->prepare("UPDATE posts SET status = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $postId]);

        cronLog("  Post #{$postId} status -> {$newStatus}", $logFile);

        // Record activity log event for this publish attempt (try/catch protected by the service itself)
        try {
            $activityLog->log([
                'client_id'   => (int) ($post['client_id'] ?? $GLOBALS['client_id']),
                'user_id'     => null,
                'user_name'   => 'System (cron)',
                'user_role'   => 'system',
                'action'      => $newStatus === 'published' ? 'post_published' : 'post_failed',
                'entity_type' => 'post',
                'entity_id'   => $postId,
                'description' => $newStatus === 'published'
                    ? "Auto-published \"{$post['title']}\""
                    : "Failed to auto-publish \"{$post['title']}\"",
                'metadata'    => [
                    'platforms'   => $platforms,
                    'all_success' => $allSuccess,
                    'any_success' => $anySuccess,
                ],
            ]);
        } catch (Throwable $logErr) {
            cronLog("  WARNING: activity log insert failed: {$logErr->getMessage()}", $logFile);
        }
    }

    cronLog('--- Cron finished ---', $logFile);

} catch (Throwable $e) {
    cronLog("FATAL ERROR: {$e->getMessage()}", $logFile);
    cronLog($e->getTraceAsString(), $logFile);
    exit(1);
}
