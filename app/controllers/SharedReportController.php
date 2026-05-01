<?php

/**
 * SharedReportController — renders a public, unauthenticated view of a
 * shared report. Intentionally does NOT call requireAuth(). Security comes
 * from the share_token being a 32-hex random value (128 bits of entropy),
 * which is unguessable. Revoking the share token removes access.
 */
class SharedReportController extends Controller
{
    public function show(string $token): void
    {
        // Strict format validation: must be exactly 32 lowercase hex chars.
        // bin2hex(random_bytes(16)) always returns lowercase, so reject anything
        // that doesn't match precisely — no point being permissive.
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            $this->notFound();
            return;
        }

        $model  = new GeneratedReport();
        $report = $model->findByShareToken($token);

        if (!$report || empty($report['share_token'])) {
            $this->notFound();
            return;
        }

        // Rate-limit view counter: only count one view per browser session
        // per report within a 5-minute window. This stops bots and refreshes
        // from inflating the count without needing full user tracking.
        try {
            $bucketKey = 'shared_view_' . (int) $report['id'];
            $now       = time();
            $lastSeen  = $_SESSION[$bucketKey] ?? 0;
            if (($now - $lastSeen) > 300) {
                // Pass client_id from the already-fetched row so the UPDATE
                // is tenant-scoped at the SQL layer, not just by id.
                $model->incrementViewCount((int) $report['id'], (int) $report['client_id']);
                $_SESSION[$bucketKey] = $now;
            }
        } catch (\Throwable $e) { /* swallow */ }

        $reportData = json_decode($report['report_data'], true) ?: [];

        $this->viewOnly('shared/report', [
            'report'     => $report,
            'reportData' => $reportData,
            'shareToken' => $token,
        ]);
    }

    private function notFound(): void
    {
        http_response_code(404);
        $this->viewOnly('shared/not_found', []);
    }
}
