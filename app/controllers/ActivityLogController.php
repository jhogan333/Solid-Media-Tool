<?php

class ActivityLogController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $clientId = $GLOBALS['client_id'];
        $model = new ActivityLog();

        // Parse filters from query string
        $filters = [
            'user_id'     => $_GET['user_id'] ?? '',
            'action'      => trim((string) ($_GET['action'] ?? '')),
            'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
            'from'        => trim((string) ($_GET['from'] ?? '')),
            'to'          => trim((string) ($_GET['to'] ?? '')),
            'q'           => trim((string) ($_GET['q'] ?? '')),
        ];
        // Empty string → unset so the model doesn't apply a no-op filter
        foreach ($filters as $k => $v) {
            if ($v === '' || $v === null) {
                unset($filters[$k]);
            }
        }

        // Pagination
        $perPage = 50;
        $totalCount = $model->countFiltered($clientId, $filters);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        // Clamp requested page to [1, totalPages] so deep-linked stale pages don't show empty tables.
        $page = max(1, min($totalPages, (int) ($_GET['page'] ?? 1)));
        $offset = ($page - 1) * $perPage;

        $events = $model->getFiltered($clientId, $filters, $perPage, $offset);

        // Filter dropdown data
        $actionsUsed = $model->getActionsUsed($clientId);
        $usersList = $model->getUsersWithActivity($clientId);

        // Session summary (last 30 days by default)
        $summaryStart = date('Y-m-d', strtotime('-30 days'));
        $summaryEnd = date('Y-m-d');
        $sessions = $model->getSessionDurations($clientId, $summaryStart, $summaryEnd);

        $this->view('activity-log/index', [
            'pageTitle'    => 'Activity Log',
            'events'       => $events,
            'totalCount'   => $totalCount,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'perPage'      => $perPage,
            'filters'      => $filters,
            'actionsUsed'  => $actionsUsed,
            'usersList'    => $usersList,
            'sessions'     => $sessions,
            'summaryStart' => $summaryStart,
            'summaryEnd'   => $summaryEnd,
        ]);
    }
}
