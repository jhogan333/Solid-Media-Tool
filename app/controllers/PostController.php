<?php

class PostController extends Controller
{
    /**
     * Parse platforms from POST data.
     */
    private function parsePlatforms(): array
    {
        $raw = $_POST['platforms'] ?? null;

        if (is_array($raw)) {
            return array_values(array_filter(array_map('trim', $raw)));
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded)));
            }
            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        $single = $_POST['platform'] ?? 'facebook';
        return [$single];
    }

    /**
     * Get platforms for an existing post record.
     */
    private function getPostPlatforms(array $post): array
    {
        if (!empty($post['platforms'])) {
            $decoded = json_decode($post['platforms'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }
        return [$post['platform'] ?? 'facebook'];
    }

    public function index(): void
    {
        $this->requireAuth();

        $postModel = new Post();
        $clientId = $GLOBALS['client_id'];
        $posts = $postModel->getByClient($clientId);

        $this->view('editor/index', [
            'posts' => $posts,
            'pageTitle' => 'Posts',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');

        $postModel = new Post();
        $post = $postModel->find((int) $id);

        if (!$post) {
            $this->redirect('/posts');
            return;
        }

        $this->view('editor/edit', [
            'post' => $post,
            'pageTitle' => 'Edit Post',
        ]);
    }

    public function save(): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $postModel = new Post();
        $memoryService = new ContentMemoryService();
        $clientId = $GLOBALS['client_id'];

        $platforms = $this->parsePlatforms();
        $scheduledAt = $_POST['scheduled_at'] ?? null;
        if (empty($scheduledAt)) $scheduledAt = null;

        $data = [
            'client_id' => $clientId,
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'post_type' => $_POST['post_type'] ?? 'image',
            'platform' => $platforms[0] ?? 'facebook',
            'platforms' => json_encode(array_values($platforms)),
            'status' => $_POST['status'] ?? 'draft',
            'scheduled_at' => $scheduledAt,
            'topic' => trim($_POST['topic'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'angle' => trim($_POST['angle'] ?? ''),
            'first_comment' => trim($_POST['first_comment'] ?? ''),
            'content_hash' => $memoryService->generateHash(
                trim($_POST['topic'] ?? ''),
                trim($_POST['keywords'] ?? ''),
                trim($_POST['angle'] ?? '')
            ),
        ];

        $id = $postModel->create($data);

        if ($data['topic']) {
            $memoryService->remember($id, $data['topic'], $data['keywords'], $data['angle'], $clientId);
        }

        (new ActivityLogService())->logPostAction('post_created', (int)$id, $data['title'], [
            'platforms' => $platforms,
            'status'    => $data['status'],
            'topic'     => $data['topic'] ?: null,
        ]);

        $this->json(['success' => true, 'id' => $id]);
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $postModel = new Post();

        $platforms = $this->parsePlatforms();
        $scheduledAt = $_POST['scheduled_at'] ?? null;
        if (empty($scheduledAt)) $scheduledAt = null;

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'post_type' => $_POST['post_type'] ?? 'image',
            'platform' => $platforms[0] ?? 'facebook',
            'platforms' => json_encode(array_values($platforms)),
            'status' => $_POST['status'] ?? 'draft',
            'scheduled_at' => $scheduledAt,
            'topic' => trim($_POST['topic'] ?? ''),
            'keywords' => trim($_POST['keywords'] ?? ''),
            'angle' => trim($_POST['angle'] ?? ''),
            'first_comment' => trim($_POST['first_comment'] ?? ''),
        ];

        $postModel->update((int) $id, $data);

        (new ActivityLogService())->logPostAction('post_updated', (int)$id, $data['title'], [
            'status' => $data['status'],
        ]);

        $this->json(['success' => true]);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $postModel = new Post();

        // Snapshot title before delete so the log entry is readable
        $existing = $postModel->find((int) $id);
        $title = $existing['title'] ?? '';

        $postModel->delete((int) $id);

        (new ActivityLogService())->logPostAction('post_deleted', (int)$id, $title);

        $this->json(['success' => true]);
    }

    /**
     * Schedule a post for future publishing.
     *
     * This ONLY saves the post with status=scheduled and the scheduled_at time.
     * The actual API call to Zernio happens via the cron job (cron/run_scheduled_posts.php)
     * when the scheduled time arrives. This prevents premature posting.
     */
    public function schedule(string $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $postModel = new Post();
        $post = $postModel->find((int) $id);

        if (!$post) {
            $this->json(['error' => 'Post not found'], 404);
            return;
        }

        $scheduledAt = $post['scheduled_at'] ?? null;

        // Validate scheduled time is in the future
        if ($scheduledAt && strtotime($scheduledAt) <= time()) {
            $this->json(['error' => 'Scheduled time must be in the future'], 400);
            return;
        }

        // Just mark as scheduled — the cron job handles the rest
        $postModel->update((int) $id, ['status' => 'scheduled']);

        (new ActivityLogService())->logPostAction('post_scheduled', (int)$id, $post['title'] ?? '', [
            'scheduled_at' => $scheduledAt,
        ]);

        $this->json([
            'success' => true,
            'message' => 'Post scheduled. It will be published automatically at the scheduled time.',
        ]);
    }

    public function postNow(string $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $postModel = new Post();
        $post = $postModel->find((int) $id);

        if (!$post) {
            $this->json(['error' => 'Post not found'], 404);
            return;
        }

        $platforms = $this->getPostPlatforms($post);
        $content = $post['content'] ?? '';
        $imageUrl = $post['image_url'] ?? null;
        $firstComment = !empty($post['first_comment']) ? $post['first_comment'] : null;

        $zernioService = new ZernioService();
        $results = [];
        $allSuccess = true;
        $anySuccess = false;

        foreach ($platforms as $platform) {
            try {
                $result = $zernioService->postNow($platform, $content, $imageUrl, $firstComment);
                $success = !empty($result['success']);

                if (!$success) $allSuccess = false;
                if ($success) $anySuccess = true;

                $results[$platform] = $result;

                $zernioService->logPostAttempt(
                    (int) $id,
                    $platform,
                    $success ? 'success' : 'failed',
                    $zernioService->getAccountId($platform),
                    $result['zernio_post_id'] ?? null,
                    json_encode($result),
                    $success ? null : ($result['error'] ?? 'Unknown error')
                );
            } catch (\Throwable $e) {
                $allSuccess = false;
                $results[$platform] = ['success' => false, 'error' => $e->getMessage()];

                $zernioService->logPostAttempt(
                    (int) $id,
                    $platform,
                    'failed',
                    $zernioService->getAccountId($platform),
                    null,
                    json_encode(['error' => $e->getMessage()]),
                    $e->getMessage()
                );
            }
        }

        $newStatus = $allSuccess ? 'published' : ($anySuccess ? 'published' : 'failed');
        $postModel->update((int) $id, ['status' => $newStatus]);

        (new ActivityLogService())->logPostAction(
            $newStatus === 'published' ? 'post_posted_now' : 'post_failed',
            (int)$id,
            $post['title'] ?? '',
            ['platforms' => $platforms, 'new_status' => $newStatus]
        );

        $this->json([
            'success' => true,
            'new_status' => $newStatus,
            'results' => $results,
        ]);
    }

    public function retry(string $id): void
    {
        $this->requireAuth();
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $postModel = new Post();
        $post = $postModel->find((int) $id);

        if (!$post) {
            $this->json(['error' => 'Post not found'], 404);
            return;
        }

        $zernioService = new ZernioService();

        // Get logs to find failed platforms
        $logs = $zernioService->getPostLogs((int) $id);

        // Build latest status per platform
        $latestStatus = [];
        foreach ($logs as $log) {
            if (!isset($latestStatus[$log['platform']])) {
                $latestStatus[$log['platform']] = $log['status'];
            }
        }

        $platforms = $this->getPostPlatforms($post);
        $failedPlatforms = [];

        foreach ($platforms as $platform) {
            if (isset($latestStatus[$platform]) && $latestStatus[$platform] === 'failed') {
                $failedPlatforms[] = $platform;
            }
        }

        if (empty($failedPlatforms)) {
            $this->json(['success' => true, 'message' => 'No failed platforms to retry', 'results' => []]);
            return;
        }

        $content = $post['content'] ?? '';
        $imageUrl = $post['image_url'] ?? null;
        $firstComment = !empty($post['first_comment']) ? $post['first_comment'] : null;

        // If the post has a future scheduled time, just reset to 'scheduled'
        // and let the cron job handle it when the time arrives.
        if (!empty($post['scheduled_at']) && strtotime($post['scheduled_at']) > time()) {
            $postModel->update((int) $id, ['status' => 'scheduled']);
            $this->json([
                'success' => true,
                'message' => 'Post re-scheduled. It will be published at the scheduled time.',
                'retried_platforms' => $failedPlatforms,
                'results' => [],
            ]);
            return;
        }

        // Otherwise, the scheduled time has passed (or there is none) — post now.
        $results = [];
        $allSuccess = true;
        $anySuccess = false;

        foreach ($failedPlatforms as $platform) {
            try {
                $result = $zernioService->postNow($platform, $content, $imageUrl, $firstComment);

                $success = !empty($result['success']);
                if (!$success) $allSuccess = false;
                if ($success) $anySuccess = true;

                $results[$platform] = $result;

                $zernioService->logPostAttempt(
                    (int) $id,
                    $platform,
                    $success ? 'success' : 'failed',
                    $zernioService->getAccountId($platform),
                    $result['zernio_post_id'] ?? null,
                    json_encode($result),
                    $success ? null : ($result['error'] ?? 'Unknown error')
                );
            } catch (\Throwable $e) {
                $allSuccess = false;
                $results[$platform] = ['success' => false, 'error' => $e->getMessage()];

                $zernioService->logPostAttempt(
                    (int) $id,
                    $platform,
                    'failed',
                    $zernioService->getAccountId($platform),
                    null,
                    json_encode(['error' => $e->getMessage()]),
                    $e->getMessage()
                );
            }
        }

        if ($allSuccess || $anySuccess) {
            $postModel->update((int) $id, ['status' => 'published']);
        }

        (new ActivityLogService())->logPostAction('post_retried', (int)$id, $post['title'] ?? '', [
            'retried_platforms' => $failedPlatforms,
            'all_success' => $allSuccess,
        ]);

        $this->json([
            'success' => $allSuccess,
            'retried_platforms' => $failedPlatforms,
            'results' => $results,
        ]);
    }

    public function logs(string $id): void
    {
        $this->requireAuth();
        @ob_clean();

        $zernioService = new ZernioService();
        $logs = $zernioService->getPostLogs((int) $id);

        $this->json(['success' => true, 'logs' => $logs]);
    }

    public function apiList(): void
    {
        $this->requireAuth();
        @ob_clean();

        $postModel = new Post();
        $clientId = $GLOBALS['client_id'];
        $posts = $postModel->getByClient($clientId);

        $this->json(['posts' => $posts]);
    }
}
