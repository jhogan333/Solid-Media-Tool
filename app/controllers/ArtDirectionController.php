<?php

class ArtDirectionController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $clientId = $GLOBALS['client_id'];
        $artService = new ArtDirectionService();
        $brandingService = new BrandingService();

        $settings = $artService->get($clientId);
        $branding = $brandingService->get($clientId);
        $promptPreview = $artService->buildPromptPreview($settings);

        $this->view('art-direction/index', [
            'pageTitle' => 'Art Direction',
            'settings' => $settings,
            'branding' => $branding,
            'promptPreview' => $promptPreview,
        ]);
    }

    public function save(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');
        if (!$this->verifyCsrf()) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $artService = new ArtDirectionService();
        $artService->save($GLOBALS['client_id'], $input);

        (new ActivityLogService())->logSettingsChange('art_direction');

        @ob_clean();
        $this->json(['success' => true]);
    }

    public function preview(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->json(['preview' => '']);
            return;
        }

        $artService = new ArtDirectionService();
        $preview = $artService->buildPromptPreview($input);

        @ob_clean();
        $this->json(['preview' => $preview]);
    }

    public function applyPreset(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        $presetName = $input['preset'] ?? '';

        $artService = new ArtDirectionService();
        $preset = $artService->applyPreset($presetName);

        if (!$preset) {
            @ob_clean();
            $this->json(['error' => 'Unknown preset'], 400);
            return;
        }

        @ob_clean();
        $this->json(['success' => true, 'settings' => $preset]);
    }
}
