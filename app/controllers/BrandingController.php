<?php

class BrandingController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $brandingService = new BrandingService();
        $branding = $brandingService->get($GLOBALS['client_id']);

        $this->view('branding/index', [
            'pageTitle' => 'Branding Settings',
            'branding' => $branding,
        ]);
    }

    public function save(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        if (!$this->verifyCsrf()) {
            $this->json(['error' => 'Invalid request.'], 403);
        }

        $brandingService = new BrandingService();
        $clientId = $GLOBALS['client_id'];

        $data = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'primary_color' => trim($_POST['primary_color'] ?? '#6366f1'),
            'secondary_color' => trim($_POST['secondary_color'] ?? '#8b5cf6'),
            'tagline' => trim($_POST['tagline'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'industry' => trim($_POST['industry'] ?? ''),
            'about_company' => trim($_POST['about_company'] ?? ''),
            'key_services' => trim($_POST['key_services'] ?? ''),
            'industry_keywords' => trim($_POST['industry_keywords'] ?? ''),
            'first_comment' => trim($_POST['first_comment'] ?? ''),
            'particles_enabled' => isset($_POST['particles_enabled']) ? 1 : 0,
        ];

        // Handle file uploads — or removal if flagged
        if (!empty($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
            $data['logo_url'] = '';
        } elseif (!empty($_FILES['logo']['tmp_name'])) {
            $logoPath = $this->handleUpload('logo');
            if ($logoPath) {
                $data['logo_url'] = $logoPath;
            }
        }

        // Dark logo (used on light-background reports/PDFs)
        if (!empty($_POST['remove_dark_logo']) && $_POST['remove_dark_logo'] === '1') {
            $data['dark_logo_url'] = '';
        } elseif (!empty($_FILES['dark_logo']['tmp_name'])) {
            $darkLogoPath = $this->handleUpload('dark_logo');
            if ($darkLogoPath) {
                $data['dark_logo_url'] = $darkLogoPath;
            }
        }

        if (!empty($_POST['remove_login_bg']) && $_POST['remove_login_bg'] === '1') {
            $data['login_bg_url'] = '';
        } elseif (!empty($_FILES['login_bg']['tmp_name'])) {
            $bgPath = $this->handleUpload('login_bg');
            if ($bgPath) {
                $data['login_bg_url'] = $bgPath;
            }
        }

        if (!empty($_POST['remove_favicon']) && $_POST['remove_favicon'] === '1') {
            $data['favicon_url'] = '';
        } elseif (!empty($_FILES['favicon']['tmp_name'])) {
            $faviconPath = $this->handleUpload('favicon');
            if ($faviconPath) {
                $data['favicon_url'] = $faviconPath;
                $this->generateFaviconSizes($faviconPath);
            }
        }

        $brandingService->save($clientId, $data);

        (new ActivityLogService())->logSettingsChange('branding', [
            'fields' => array_keys($data),
        ]);

        // Update user first name if provided
        $firstName = trim($_POST['first_name'] ?? '');
        if ($firstName !== '' && isset($_SESSION['user_id'])) {
            $db = Database::connect();
            $stmt = $db->prepare('UPDATE users SET first_name = ? WHERE id = ?');
            $stmt->execute([$firstName, $_SESSION['user_id']]);
            $_SESSION['first_name'] = $firstName;
        }

        $this->redirect('/branding?saved=1');
    }

    public function saveApi(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'error' => 'Invalid request'], 403);
            return;
        }

        $envFile = APP_ROOT . '/config/env.php';
        $envContent = file_get_contents($envFile);

        // Update OpenRouter key
        $orKey = trim($input['openrouter_key'] ?? '');
        $envContent = preg_replace(
            "/define\('OPENROUTER_API_KEY',\s*'[^']*'\)/",
            "define('OPENROUTER_API_KEY', '" . addslashes($orKey) . "')",
            $envContent
        );

        // Update OpenRouter model
        $orModel = trim($input['openrouter_model'] ?? 'openai/gpt-4o-mini');
        $envContent = preg_replace(
            "/define\('OPENROUTER_MODEL',\s*'[^']*'\)/",
            "define('OPENROUTER_MODEL', '" . addslashes($orModel) . "')",
            $envContent
        );

        // Update Kie key
        $kieKey = trim($input['kie_key'] ?? '');
        $envContent = preg_replace(
            "/define\('KIE_API_KEY',\s*'[^']*'\)/",
            "define('KIE_API_KEY', '" . addslashes($kieKey) . "')",
            $envContent
        );

        if (file_put_contents($envFile, $envContent)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['success' => false, 'error' => 'Could not write config file']);
        }
    }

    public function testApi(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['ok' => false, 'error' => 'Invalid request'], 403);
            return;
        }

        $service = $input['service'] ?? '';
        $key = trim($input['key'] ?? '');

        if ($service === 'openrouter') {
            if (empty($key)) {
                $this->json(['ok' => false, 'error' => 'No key provided']);
                return;
            }
            // Quick test call
            $payload = json_encode([
                'model' => OPENROUTER_MODEL,
                'messages' => [['role' => 'user', 'content' => 'Respond with exactly: OK']],
                'max_tokens' => 10,
            ]);
            $ch = curl_init(OPENROUTER_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $key,
                ],
                CURLOPT_TIMEOUT => 15,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $this->json(['ok' => true]);
            } else {
                $data = json_decode($response, true);
                $err = $data['error']['message'] ?? "HTTP {$httpCode}";
                $this->json(['ok' => false, 'error' => $err]);
            }
        } elseif ($service === 'kie') {
            if (empty($key)) {
                $this->json(['ok' => false, 'error' => 'No key provided']);
                return;
            }
            // Test by creating a minimal task
            $payload = json_encode([
                'model' => KIE_MODEL,
                'input' => ['prompt' => 'A simple blue circle on white background, test', 'resolution' => '1K'],
            ]);
            $ch = curl_init(KIE_CREATE_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $key,
                ],
                CURLOPT_TIMEOUT => 15,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);
            if ($httpCode === 200 && ($data['code'] ?? 0) === 200) {
                $this->json(['ok' => true]);
            } else {
                $err = $data['msg'] ?? "HTTP {$httpCode}";
                $this->json(['ok' => false, 'error' => $err]);
            }
        } else {
            $this->json(['ok' => false, 'error' => 'Unknown service']);
        }
    }

    private function generateFaviconSizes(string $faviconWebUrl): void
    {
        $filename = basename(parse_url($faviconWebUrl, PHP_URL_PATH));
        $localFile = UPLOAD_DIR . $filename;
        self::generateFaviconSizesFromFile($localFile);
    }

    public static function generateFaviconSizesFromFile(string $localFile): void
    {
        if (!file_exists($localFile)) return;

        $info = getimagesize($localFile);
        if (!$info) return;

        $src = match ($info['mime']) {
            'image/png' => imagecreatefrompng($localFile),
            'image/jpeg' => imagecreatefromjpeg($localFile),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($localFile) : null,
            'image/gif' => imagecreatefromgif($localFile),
            default => null,
        };
        if (!$src) return;

        $publicDir = APP_ROOT . '/public/';
        $sizes = [16, 32, 48, 180];
        $names = [16 => 'favicon-16.png', 32 => 'favicon-32.png', 48 => 'favicon-48.png', 180 => 'apple-touch-icon.png'];

        foreach ($sizes as $s) {
            $dst = imagecreatetruecolor($s, $s);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $trans);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $s, $s, imagesx($src), imagesy($src));
            imagepng($dst, $publicDir . $names[$s]);
            imagedestroy($dst);
        }

        // Also create favicon.ico (32x32 PNG)
        copy($publicDir . 'favicon-32.png', $publicDir . 'favicon.ico');
        imagedestroy($src);
    }

    private function handleUpload(string $field): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $field . '_' . time() . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return BASE_URL . '/uploads/' . $filename;
        }

        return null;
    }
}
