<?php

class ReportsController extends Controller
{
    /**
     * POST /reports/generate — build + save a report, then return the id.
     * Request body (JSON):
     *   csrf_token, title, range (last_7|last_30|this_month|last_month|custom),
     *   start_date (Y-m-d, only if range=custom), end_date, delivery (view|email),
     *   email_to (comma-separated, only if delivery=email)
     */
    public function generate(): void
    {
        $this->requireRole('admin', 'editor');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $clientId = (int) $GLOBALS['client_id'];
        $title    = trim((string) ($input['title'] ?? ''));
        $range    = (string) ($input['range'] ?? 'last_30');
        $delivery = (string) ($input['delivery'] ?? 'view');
        $emailTo  = trim((string) ($input['email_to'] ?? ''));

        // Resolve date range
        [$startDate, $endDate] = $this->resolveDateRange($range, $input);
        if ($startDate === null) {
            $this->json(['error' => 'Invalid date range.'], 400);
            return;
        }

        // Default title
        if ($title === '') {
            $brandingSvc = new BrandingService();
            $company     = $brandingSvc->get($clientId)['company_name'] ?? 'Company';
            $monthLabel  = date('M Y', strtotime($startDate));
            $title       = "{$company} Social Report — {$monthLabel}";
        }
        if (mb_strlen($title) > 240) $title = mb_substr($title, 0, 240);

        // Release session lock so browser isn't blocked during the AI call
        session_write_close();

        // Build the report
        try {
            $generator  = new ReportGeneratorService();
            $reportData = $generator->build($clientId, $startDate, $endDate, $title);
        } catch (\Throwable $e) {
            // Log the full trace internally; return a generic message to the
            // client so stack-trace fragments or PDO errors can't leak.
            error_log('ReportsController::generate build failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->json(['error' => 'Failed to build report. Please try again or contact support.'], 500);
            return;
        }

        // Persist
        $model    = new GeneratedReport();
        $reportId = $model->create([
            'client_id'          => $clientId,
            'created_by_user_id' => (int) ($_SESSION['user_id'] ?? 0),
            'title'              => $title,
            'date_range_start'   => $startDate,
            'date_range_end'     => $endDate,
            'report_data'        => json_encode($reportData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        // Activity log
        try {
            (new ActivityLogService())->log([
                'client_id'   => $clientId,
                'user_id'     => (int) ($_SESSION['user_id'] ?? 0),
                'user_name'   => $_SESSION['first_name'] ?? $_SESSION['username'] ?? null,
                'user_role'   => $_SESSION['role'] ?? null,
                'action'      => 'report_generated',
                'entity_type' => 'report',
                'entity_id'   => (int) $reportId,
                'description' => "Generated report \"{$title}\"",
                'metadata'    => [
                    'range'         => [$startDate, $endDate],
                    'total_posts'   => $reportData['metrics']['total_posts'] ?? 0,
                    'delivery'      => $delivery,
                    'dollars_saved' => $reportData['savings']['dollars_saved'] ?? 0,
                ],
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        $viewUrl = BASE_URL . '/reports/view/' . (int) $reportId;

        // Email delivery
        $emailResult = null;
        if ($delivery === 'email' && $emailTo !== '') {
            $emailResult = $this->deliverEmail($reportData, $viewUrl, $emailTo);
        }

        $this->json([
            'success'   => true,
            'report_id' => (int) $reportId,
            'view_url'  => $viewUrl,
            'delivery'  => $delivery,
            'email'     => $emailResult,
        ]);
    }

    /**
     * GET /reports/view/{id} — render the full branded report page.
     * Method name is `show` (not `view`) to avoid collision with the
     * base Controller::view() template-rendering helper.
     */
    public function show(string $id): void
    {
        $this->requireAuth();

        $clientId = (int) $GLOBALS['client_id'];
        $model    = new GeneratedReport();
        $report   = $model->findForClient((int) $id, $clientId);

        if (!$report) {
            http_response_code(404);
            echo '<h1>Report not found</h1>';
            return;
        }

        // Increment view count once per page load
        try { $model->incrementViewCount((int) $id, $clientId); } catch (\Throwable $e) { /* swallow */ }

        $reportData = json_decode($report['report_data'], true) ?: [];

        // Render standalone (no sidebar/topbar) so Print Preview looks clean
        $this->viewOnly('reports/pdf/view', [
            'report'     => $report,
            'reportData' => $reportData,
            'viewMode'   => 'web',
        ]);
    }

    /**
     * GET /reports/library — list of previously generated reports.
     * Not a standalone route — called from the Reports page, returns JSON.
     */
    public function libraryList(): void
    {
        $this->requireRole('admin', 'editor');
        @ob_clean();

        $model  = new GeneratedReport();
        $rows   = $model->getByClient((int) $GLOBALS['client_id'], 50);

        $this->json(['reports' => $rows]);
    }

    /**
     * POST /reports/settings/save — admin-only editable cost savings math.
     */
    public function saveSettings(): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $svc = new ReportSettingsService();
        $saved = $svc->save((int) $GLOBALS['client_id'], $input);

        try {
            (new ActivityLogService())->logSettingsChange('report_settings', [
                'minutes_per_post' => $saved['minutes_per_post'],
                'hourly_rate'      => $saved['hourly_rate'],
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        @ob_clean();
        $this->json(['success' => true, 'settings' => $saved]);
    }

    /**
     * POST /reports/share/{id} — generate a random share token for a report.
     * Returns the public URL so the caller can copy it immediately.
     */
    public function share(string $id): void
    {
        $this->requireRole('admin', 'editor');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $model  = new GeneratedReport();
        $report = $model->findForClient((int) $id, (int) $GLOBALS['client_id']);
        if (!$report) {
            $this->json(['error' => 'Report not found.'], 404);
            return;
        }

        // Reuse existing token if one is already set; otherwise mint a fresh 32-hex one
        $token    = $report['share_token'] ?: bin2hex(random_bytes(16));
        $clientId = (int) $GLOBALS['client_id'];
        $model->setShareToken((int) $id, $clientId, $token);

        try {
            (new ActivityLogService())->log([
                'client_id'   => (int) $GLOBALS['client_id'],
                'user_id'     => (int) ($_SESSION['user_id'] ?? 0),
                'user_name'   => $_SESSION['first_name'] ?? $_SESSION['username'] ?? null,
                'user_role'   => $_SESSION['role'] ?? null,
                'action'      => 'report_shared',
                'entity_type' => 'report',
                'entity_id'   => (int) $id,
                'description' => "Shared report \"{$report['title']}\"",
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        @ob_clean();
        $this->json([
            'success'    => true,
            'token'      => $token,
            'public_url' => BASE_URL . '/shared/' . $token,
        ]);
    }

    /**
     * POST /reports/unshare/{id} — revoke the share token.
     */
    public function unshare(string $id): void
    {
        $this->requireRole('admin', 'editor');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $model  = new GeneratedReport();
        $report = $model->findForClient((int) $id, (int) $GLOBALS['client_id']);
        if (!$report) {
            $this->json(['error' => 'Report not found.'], 404);
            return;
        }

        $model->setShareToken((int) $id, (int) $GLOBALS['client_id'], null);

        try {
            (new ActivityLogService())->log([
                'client_id'   => (int) $GLOBALS['client_id'],
                'user_id'     => (int) ($_SESSION['user_id'] ?? 0),
                'user_name'   => $_SESSION['first_name'] ?? $_SESSION['username'] ?? null,
                'user_role'   => $_SESSION['role'] ?? null,
                'action'      => 'report_unshared',
                'entity_type' => 'report',
                'entity_id'   => (int) $id,
                'description' => "Revoked share link for \"{$report['title']}\"",
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        @ob_clean();
        $this->json(['success' => true]);
    }

    /**
     * POST /reports/delete/{id} — soft-hardcoded delete of a saved report.
     */
    public function delete(string $id): void
    {
        $this->requireRole('admin', 'editor');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $model  = new GeneratedReport();
        $report = $model->findForClient((int) $id, (int) $GLOBALS['client_id']);
        if (!$report) {
            $this->json(['error' => 'Report not found.'], 404);
            return;
        }

        $model->deleteForClient((int) $id, (int) $GLOBALS['client_id']);

        try {
            (new ActivityLogService())->log([
                'client_id'   => (int) $GLOBALS['client_id'],
                'user_id'     => (int) ($_SESSION['user_id'] ?? 0),
                'user_name'   => $_SESSION['first_name'] ?? $_SESSION['username'] ?? null,
                'user_role'   => $_SESSION['role'] ?? null,
                'action'      => 'report_deleted',
                'entity_type' => 'report',
                'entity_id'   => (int) $id,
                'description' => "Deleted report \"{$report['title']}\"",
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        @ob_clean();
        $this->json(['success' => true]);
    }

    // ────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────

    /**
     * Resolve a range preset into concrete start/end dates.
     * Returns [startDate, endDate] or [null, null] on invalid input.
     */
    private function resolveDateRange(string $range, array $input): array
    {
        $today = date('Y-m-d');
        switch ($range) {
            case 'last_7':
                return [date('Y-m-d', strtotime('-6 days')), $today];
            case 'last_30':
                return [date('Y-m-d', strtotime('-29 days')), $today];
            case 'this_month':
                return [date('Y-m-01'), $today];
            case 'last_month':
                $first = date('Y-m-01', strtotime('first day of last month'));
                $last  = date('Y-m-t', strtotime('last day of last month'));
                return [$first, $last];
            case 'custom':
                $start = trim((string) ($input['start_date'] ?? ''));
                $end   = trim((string) ($input['end_date'] ?? ''));
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
                    return [null, null];
                }
                if (strtotime($start) > strtotime($end)) {
                    return [null, null];
                }
                return [$start, $end];
            default:
                return [null, null];
        }
    }

    /**
     * Send the "report ready" email with the AI-personalized intro and
     * a View Report button. Returns a status array.
     */
    private function deliverEmail(array $reportData, string $viewUrl, string $emailTo): array
    {
        $emailSvc = new EmailService();
        if (!$emailSvc->isConfigured()) {
            return ['success' => false, 'error' => 'Email is not configured. Configure SMTP in Settings → Email first.'];
        }

        $recipients = array_filter(array_map('trim', explode(',', $emailTo)));
        $recipients = array_filter($recipients, function($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); });
        if (empty($recipients)) {
            return ['success' => false, 'error' => 'No valid email addresses provided.'];
        }

        // AI-personalized intro (per-recipient would be too slow; use one)
        $generator = new ReportGeneratorService();
        $intro     = $generator->generateEmailIntro($reportData, '');

        // Build HTML body from the shared email template
        ob_start();
        extract([
            'reportData' => $reportData,
            'intro'      => $intro,
            'viewUrl'    => $viewUrl,
        ]);
        include APP_ROOT . '/app/views/emails/report_ready.php';
        $html = ob_get_clean();

        $meta    = $reportData['meta'] ?? [];
        $company = $meta['company_name'] ?? 'your company';
        $subject = "{$company} Social Media Report — " . ($meta['date_range']['display'] ?? 'ready to view');

        $results = [];
        $anyFail = false;
        foreach ($recipients as $recipient) {
            $r = $emailSvc->send($recipient, $subject, $html);
            $results[$recipient] = $r;
            if (empty($r['success'])) $anyFail = true;
        }

        return [
            'success' => !$anyFail,
            'sent_to' => array_keys($results),
            'details' => $results,
        ];
    }
}
