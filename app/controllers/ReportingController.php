<?php

class ReportingController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');

        $postModel = new Post();
        $clientId = $GLOBALS['client_id'];

        $stats = $postModel->getStats($clientId);
        $posts = $postModel->getByClient($clientId);
        $topicDist = $postModel->getTopicDistribution($clientId);
        $platformDist = $postModel->getPlatformDistribution($clientId);

        // Get failed posts with their error messages from posting logs
        $failedPosts = [];
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT p.id, p.title, p.platform, p.platforms, p.created_at,
                    l.error_message, l.platform AS failed_platform, l.created_at AS failed_at
             FROM posts p
             LEFT JOIN social_post_logs l ON p.id = l.post_id AND l.status = 'failed'
             WHERE p.client_id = :cid AND p.status = 'failed'
             ORDER BY COALESCE(l.created_at, p.created_at) DESC"
        );
        $stmt->execute(['cid' => $clientId]);
        $failedPosts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Phase 2: report settings (for cost savings card) + saved reports library
        $reportSettings = (new ReportSettingsService())->get($clientId);
        $savedReports   = (new GeneratedReport())->getByClient($clientId, 20);

        $this->view('reporting/index', [
            'pageTitle' => 'Reports',
            'stats' => $stats,
            'posts' => $posts,
            'topicDist' => $topicDist,
            'platformDist' => $platformDist,
            'failedPosts' => $failedPosts,
            'reportSettings' => $reportSettings,
            'savedReports'   => $savedReports,
        ]);
    }

    public function exportCsv(): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');

        $postModel = new Post();
        $clientId = $GLOBALS['client_id'];
        $posts = $postModel->getByClient($clientId);

        $filename = 'posts-export-' . date('Y-m-d') . '.csv';

        // Release session lock before streaming output
        session_write_close();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM so Excel opens the file as UTF-8 instead of Windows-1252
        // (without this, any remaining non-ASCII chars render as mojibake like "ðŸ")
        fwrite($output, "\xEF\xBB\xBF");

        // CSV header row
        fputcsv($output, ['Title', 'Content', 'Platform', 'Post Type', 'Status', 'Scheduled At', 'Created At']);

        foreach ($posts as $post) {
            // Determine platform(s)
            $platforms = '';
            if (!empty($post['platforms'])) {
                $decoded = json_decode($post['platforms'], true);
                if (is_array($decoded)) {
                    $platforms = implode(', ', array_map('ucfirst', $decoded));
                }
            }
            if (empty($platforms)) {
                $platforms = ucfirst($post['platform'] ?? 'facebook');
            }

            // Clean title and content: strip emojis + symbol chars, collapse whitespace
            $title = $this->stripEmojis($post['title'] ?? '');
            $content = $this->stripEmojis($post['content'] ?? '');
            if (mb_strlen($content) > 100) {
                $content = mb_substr($content, 0, 100) . '...';
            }

            fputcsv($output, [
                $title,
                $content,
                $platforms,
                ucfirst(str_replace('_', ' ', $post['post_type'] ?? '')),
                ucfirst($post['status'] ?? ''),
                $post['scheduled_at'] ?? '',
                $post['created_at'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Remove emoji and related symbol characters, normalise whitespace.
     */
    private function stripEmojis(string $text): string
    {
        if ($text === '') return '';

        // Drop all 4-byte UTF-8 sequences (emoji, pictographs, flags)
        $text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
        // Drop common BMP emoji/symbol ranges + variation selectors + ZWJ
        $text = preg_replace(
            '/[\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{3000}-\x{303F}\x{FE00}-\x{FE0F}\x{200D}]/u',
            '',
            $text
        );
        // Collapse runs of whitespace (including newlines) into single spaces
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
