<?php

class WizardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $clientId = $GLOBALS['client_id'];
        $brandingService = new BrandingService();
        $branding = $brandingService->get($clientId);

        $isRerun = isset($_GET['rerun']);
        $strategyService = new ContentStrategyService();
        $existingThemes = $strategyService->getThemes($clientId);

        $wizardService = new WizardService();
        $setupComplete = $wizardService->isSetupComplete($clientId);
        $isNewAdmin = !empty($_SESSION['needs_wizard']);

        // First admin ever: mandatory, can't cancel
        // Subsequent admins: wizard shown but can cancel
        $isMandatory = $isNewAdmin && !$setupComplete;
        $isSubsequentAdmin = $isNewAdmin && $setupComplete;

        $this->view('wizard/index', [
            'pageTitle' => 'Setup Wizard',
            'branding' => $branding,
            'isRerun' => $isRerun || $isSubsequentAdmin,
            'isMandatory' => $isMandatory,
            'isSubsequentAdmin' => $isSubsequentAdmin,
            'existingThemes' => $existingThemes,
        ]);
    }

    public function scanWebsite(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $url = trim($input['url'] ?? '');
        if (empty($url)) {
            @ob_clean();
            $this->json(['error' => 'No URL provided']);
            return;
        }

        $service = new WizardService();
        $result = $service->scanWebsite($url);

        @ob_clean();
        $this->json($result);
    }

    public function suggestThemes(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        // Fetch existing themes so the wizard never re-suggests ones the client
        // already has (and avoids close-reword variants).
        $clientId = $GLOBALS['client_id'];
        $existingThemes = (new ContentStrategyService())->getThemes($clientId);
        $existingNames = array_map(
            function ($t) { return (string) ($t['name'] ?? ''); },
            $existingThemes
        );

        $service = new WizardService();
        $themes = $service->suggestThemes($input, $existingNames);

        @ob_clean();
        $this->json(['themes' => $themes]);
    }

    public function save(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $clientId = $GLOBALS['client_id'];

        // Handle logo upload from base64 if provided
        if (!empty($input['logo_base64'])) {
            $logoData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $input['logo_base64']));
            if ($logoData) {
                $ext = 'png';
                if (preg_match('#^data:image/(\w+);#i', $input['logo_base64'], $m)) {
                    $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                }
                $filename = 'logo_' . time() . '.' . $ext;
                $path = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                file_put_contents($path, $logoData);
                $input['logo_url'] = BASE_URL . '/uploads/' . $filename;
            }
        }

        // Handle favicon upload from base64 if provided
        if (!empty($input['favicon_base64'])) {
            $favData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $input['favicon_base64']));
            if ($favData) {
                $ext = 'png';
                if (preg_match('#^data:image/(\w+);#i', $input['favicon_base64'], $m)) {
                    $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                }
                $filename = 'favicon_' . time() . '.' . $ext;
                $path = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                file_put_contents($path, $favData);
                $input['favicon_url'] = BASE_URL . '/uploads/' . $filename;

                // Generate favicon sizes
                $brandingController = new BrandingController();
                $brandingController->generateFaviconSizesFromFile($path);
            }
        }

        $service = new WizardService();
        $service->saveWizardData($clientId, $input);

        // Mark wizard as complete, clear the mandatory flag
        $_SESSION['wizard_complete'] = true;
        unset($_SESSION['needs_wizard']);

        // If admin hasn't completed tour yet, queue it up next
        $user = (new User())->find($_SESSION['user_id']);
        if ($user && empty($user['has_completed_tour'])) {
            $_SESSION['needs_tour'] = true;
        }

        @ob_clean();
        $this->json(['success' => true]);
    }
}
