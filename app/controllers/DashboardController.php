<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $postModel = new Post();
        $clientId = $GLOBALS['client_id'];

        $stats = $postModel->getStats($clientId);
        $recentPosts = $postModel->getRecent($clientId, 8);
        $scheduledPosts = $postModel->getScheduled($clientId);
        $topicStats = $postModel->getTopicDistribution($clientId);

        $this->view('dashboard/index', [
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'scheduledPosts' => $scheduledPosts,
            'topicStats' => $topicStats,
            'pageTitle' => 'Dashboard',
        ]);
    }

    public function apiStats(): void
    {
        $this->requireAuth();

        $postModel = new Post();
        $clientId = $GLOBALS['client_id'];
        $stats = $postModel->getStats($clientId);

        $this->json(['stats' => $stats]);
    }

    /**
     * GET /api/posts-by-status?status=<total|scheduled|published|draft|failed>&page=N
     *
     * Feeds the dashboard stat-card lightbox. Returns a paginated list of
     * posts matching the requested bucket along with the label shown in the
     * modal header.
     */
    public function apiPostsByStatus(): void
    {
        $this->requireAuth();

        $clientId = (int) $GLOBALS['client_id'];
        $status   = (string) ($_GET['status'] ?? 'total');
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $perPage  = 10;

        $allowed = ['total', 'scheduled', 'published', 'draft', 'failed'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['error' => 'Invalid status'], 400);
            return;
        }

        $labels = [
            'total'     => 'All Posts',
            'scheduled' => 'Scheduled Posts',
            'published' => 'Published Posts',
            'draft'     => 'Draft Posts',
            'failed'    => 'Failed Posts',
        ];

        if ($status === 'total') {
            $where  = 'client_id = :cid';
            $params = ['cid' => $clientId];
            $order  = 'created_at DESC';
        } elseif ($status === 'scheduled') {
            $where  = "client_id = :cid AND status = 'scheduled'";
            $params = ['cid' => $clientId];
            $order  = 'scheduled_at ASC';
        } else {
            $where  = 'client_id = :cid AND status = :status';
            $params = ['cid' => $clientId, 'status' => $status];
            $order  = 'created_at DESC';
        }

        $total  = Database::fetch("SELECT COUNT(*) AS c FROM posts WHERE {$where}", $params)['c'] ?? 0;
        $total  = (int) $total;
        $pages  = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $rows = Database::fetchAll(
            "SELECT id, title, status, topic, platform, scheduled_at, created_at, image_url
             FROM posts WHERE {$where} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $items = array_map(function($r) {
            return [
                'id'           => (int) $r['id'],
                'title'        => $r['title'] ?? 'Untitled',
                'status'       => $r['status'] ?? 'draft',
                'topic'        => $r['topic'] ?? '',
                'platform'     => $r['platform'] ?? '',
                'scheduled_at' => $r['scheduled_at'] ?? null,
                'created_at'   => $r['created_at'] ?? null,
                'image_url'    => $r['image_url'] ?? null,
                'edit_url'     => BASE_URL . '/posts/edit/' . (int) $r['id'],
            ];
        }, $rows);

        $this->json([
            'label'    => $labels[$status],
            'status'   => $status,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
            'per_page' => $perPage,
            'items'    => $items,
        ]);
    }
}
