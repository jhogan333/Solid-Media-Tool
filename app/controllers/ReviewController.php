<?php

class ReviewController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin', 'reviewer');

        $clientId = $GLOBALS['client_id'];
        $approvalService = new ApprovalService();
        $pendingPosts = $approvalService->getPendingReviews($clientId);

        // Attach review status to each post
        foreach ($pendingPosts as &$post) {
            $post['review_status'] = $approvalService->getReviewStatus((int)$post['id'], $clientId);
        }

        $this->view('reviews/index', [
            'pageTitle' => 'Review Queue',
            'posts' => $pendingPosts,
        ]);
    }

    public function approve(string $id): void
    {
        $this->requireRole('admin', 'reviewer');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $approvalService = new ApprovalService();
        $result = $approvalService->approve(
            (int)$id,
            (int)$_SESSION['user_id'],
            $input['comment'] ?? null
        );

        // Resolve title for the log
        $postTitle = '';
        try {
            $post = (new Post())->find((int)$id);
            $postTitle = $post['title'] ?? '';
        } catch (\Throwable $e) { /* ignore */ }

        (new ActivityLogService())->logPostAction('post_approved', (int)$id, $postTitle, [
            'comment' => $input['comment'] ?? null,
        ]);

        @ob_clean();
        $this->json(['success' => true, 'result' => $result]);
    }

    public function requestChanges(string $id): void
    {
        $this->requireRole('admin', 'reviewer');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $comment = trim($input['comment'] ?? '');
        if (empty($comment)) {
            @ob_clean();
            $this->json(['error' => 'Please provide feedback on what needs to change.'], 400);
            return;
        }

        $approvalService = new ApprovalService();
        $approvalService->requestChanges((int)$id, (int)$_SESSION['user_id'], $comment);

        $postTitle = '';
        try {
            $post = (new Post())->find((int)$id);
            $postTitle = $post['title'] ?? '';
        } catch (\Throwable $e) { /* ignore */ }

        (new ActivityLogService())->logPostAction('post_changes_requested', (int)$id, $postTitle, [
            'comment' => $comment,
        ]);

        @ob_clean();
        $this->json(['success' => true]);
    }
}
