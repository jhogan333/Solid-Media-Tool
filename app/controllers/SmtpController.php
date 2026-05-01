<?php

class SmtpController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $model = new SmtpSetting();
        $settings = $model->getByClient($GLOBALS['client_id']);

        $this->view('smtp/index', [
            'pageTitle' => 'Email Settings',
            'settings' => $settings ?: [],
        ]);
    }

    public function save(): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $provider = $input['provider'] ?? 'smtp';
        $data = [
            'provider' => $provider,
            'from_name' => trim($input['from_name'] ?? ''),
            'from_email' => trim($input['from_email'] ?? ''),
            'is_configured' => 0,
        ];

        if ($provider === 'smtp') {
            $data['smtp_host'] = trim($input['smtp_host'] ?? '');
            $data['smtp_port'] = (int)($input['smtp_port'] ?? 587);
            $data['smtp_user'] = trim($input['smtp_user'] ?? '');
            $data['smtp_pass'] = trim($input['smtp_pass'] ?? '');
            $data['smtp_encryption'] = $input['smtp_encryption'] ?? 'tls';
            $data['is_configured'] = !empty($data['smtp_host']) && !empty($data['from_email']) ? 1 : 0;
        } elseif ($provider === 'sendgrid') {
            $data['sendgrid_api_key'] = trim($input['sendgrid_api_key'] ?? '');
            $data['is_configured'] = !empty($data['sendgrid_api_key']) && !empty($data['from_email']) ? 1 : 0;
        } elseif ($provider === 'mailgun') {
            $data['mailgun_api_key'] = trim($input['mailgun_api_key'] ?? '');
            $data['mailgun_domain'] = trim($input['mailgun_domain'] ?? '');
            $data['is_configured'] = !empty($data['mailgun_api_key']) && !empty($data['mailgun_domain']) && !empty($data['from_email']) ? 1 : 0;
        } elseif ($provider === 'emailit') {
            $data['emailit_api_key'] = trim($input['emailit_api_key'] ?? '');
            $data['is_configured'] = !empty($data['emailit_api_key']) && !empty($data['from_email']) ? 1 : 0;
        }

        $model = new SmtpSetting();
        $model->upsertByClient($GLOBALS['client_id'], $data);

        (new ActivityLogService())->logSettingsChange('smtp', [
            'provider'      => $provider,
            'is_configured' => (bool) $data['is_configured'],
        ]);

        @ob_clean();
        $this->json(['success' => true, 'is_configured' => $data['is_configured']]);
    }

    public function test(): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $emailService = new EmailService();
        $result = $emailService->testConnection();

        @ob_clean();
        $this->json($result);
    }
}
