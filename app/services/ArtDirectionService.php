<?php

class ArtDirectionService
{
    private ArtDirectionSetting $model;

    private const DEFAULTS = [
        'image_style' => 'photorealistic',
        'realism_level' => 8,
        'color_temperature' => 'cold',
        'contrast_level' => 'punchy',
        'mood' => 'professional',
        'brand_color_bleed' => 25,
        'illustration_limit' => 'max_1_per_week',
        // The avoid list is now a subject-matter exclusion (not a style list).
        // Style is already handled by image_style + realism_level. Leave this empty
        // by default so each client can list the specific things they don't want
        // appearing in generated images (e.g. children, pets, outdoor scenes).
        'avoid_list' => '',
        'watermark_enabled' => 1,
        'watermark_website' => '',
        'watermark_logo_position' => 'bottom-left',
        'watermark_gradient_opacity' => 85,
    ];

    private const PRESETS = [
        'corporate_it' => [
            'image_style' => 'photorealistic',
            'realism_level' => 8,
            'color_temperature' => 'cold',
            'contrast_level' => 'punchy',
            'mood' => 'professional',
            'brand_color_bleed' => 25,
            'illustration_limit' => 'max_1_per_week',
        ],
        'tech_magazine' => [
            'image_style' => 'photorealistic',
            'realism_level' => 9,
            'color_temperature' => 'cold',
            'contrast_level' => 'maximum',
            'mood' => 'dramatic',
            'brand_color_bleed' => 15,
            'illustration_limit' => 'max_1_per_week',
        ],
        'dark_dramatic' => [
            'image_style' => 'photorealistic',
            'realism_level' => 9,
            'color_temperature' => 'cold',
            'contrast_level' => 'maximum',
            'mood' => 'moody_dark',
            'brand_color_bleed' => 35,
            'illustration_limit' => 'never',
        ],
        'clean_professional' => [
            'image_style' => 'photorealistic',
            'realism_level' => 7,
            'color_temperature' => 'neutral',
            'contrast_level' => 'balanced',
            'mood' => 'clean_bright',
            'brand_color_bleed' => 10,
            'illustration_limit' => 'max_2_per_week',
        ],
    ];

    public function __construct()
    {
        $this->model = new ArtDirectionSetting();
    }

    public function get(int $clientId): array
    {
        $settings = $this->model->getByClient($clientId);
        if (!$settings) {
            return self::DEFAULTS;
        }
        return array_merge(self::DEFAULTS, $settings);
    }

    public function save(int $clientId, array $data): void
    {
        $allowed = [
            'image_style', 'realism_level', 'color_temperature', 'contrast_level',
            'mood', 'brand_color_bleed', 'illustration_limit', 'avoid_list',
            'watermark_enabled', 'watermark_website', 'watermark_logo_position',
            'watermark_gradient_opacity',
        ];
        $filtered = array_intersect_key($data, array_flip($allowed));

        if (isset($filtered['realism_level'])) {
            $filtered['realism_level'] = max(1, min(10, (int)$filtered['realism_level']));
        }
        if (isset($filtered['brand_color_bleed'])) {
            $filtered['brand_color_bleed'] = max(0, min(100, (int)$filtered['brand_color_bleed']));
        }
        if (isset($filtered['watermark_gradient_opacity'])) {
            $filtered['watermark_gradient_opacity'] = max(0, min(100, (int)$filtered['watermark_gradient_opacity']));
        }
        if (isset($filtered['watermark_enabled'])) {
            $filtered['watermark_enabled'] = $filtered['watermark_enabled'] ? 1 : 0;
        }

        $this->model->upsertByClient($clientId, $filtered);
    }

    public function applyPreset(string $name): ?array
    {
        return self::PRESETS[$name] ?? null;
    }

    public function getPresets(): array
    {
        return array_keys(self::PRESETS);
    }

    public function buildImagePromptModifiers(int $clientId): string
    {
        $s = $this->get($clientId);
        return $this->buildPromptFromSettings($s);
    }

    public function buildPromptPreview(array $settings): string
    {
        $s = array_merge(self::DEFAULTS, $settings);
        return $this->buildPromptFromSettings($s);
    }

    private function buildPromptFromSettings(array $s): string
    {
        $parts = [];

        // Image style
        $styleMap = [
            'photorealistic' => 'Photorealistic photograph, shot on professional DSLR camera, natural lighting',
            'mixed' => 'Blend of professional photography with clean graphic design overlays and text elements',
            'technical_diagram' => 'Clean technical diagram or infographic with realistic textures and dark branded background',
        ];
        $parts[] = $styleMap[$s['image_style']] ?? $styleMap['photorealistic'];

        // Realism level
        $level = (int)$s['realism_level'];
        if ($level >= 9) {
            $parts[] = 'Hyper-realistic, indistinguishable from a real photograph, extreme detail';
        } elseif ($level >= 7) {
            $parts[] = 'Highly realistic and detailed';
        } elseif ($level >= 4) {
            $parts[] = 'Semi-realistic with clean, polished aesthetics';
        } else {
            $parts[] = 'Stylized, artistic interpretation';
        }

        // Color temperature
        $tempMap = [
            'cold' => 'Cool blue-tinted undertones, deep blacks, desaturated colors',
            'neutral' => 'Neutral, balanced color palette',
            'warm' => 'Warm golden tones, amber highlights',
        ];
        $parts[] = $tempMap[$s['color_temperature']] ?? $tempMap['cold'];

        // Contrast
        $contrastMap = [
            'subtle' => 'Low contrast, soft tones',
            'balanced' => 'Natural, balanced contrast',
            'punchy' => 'High contrast, deep rich blacks and bright highlights, bold and punchy like a tech magazine',
            'maximum' => 'Extreme contrast, dramatic deep shadows, cinematic lighting',
        ];
        $parts[] = $contrastMap[$s['contrast_level']] ?? $contrastMap['punchy'];

        // Mood
        $moodMap = [
            'professional' => 'Clean, corporate, trustworthy, modern office or IT environment',
            'dramatic' => 'Cinematic composition, bold dramatic lighting, strong visual impact',
            'moody_dark' => 'Dark and atmospheric, moody lighting, noir-inspired',
            'clean_bright' => 'Bright, airy, minimal, well-lit clean spaces',
        ];
        $parts[] = $moodMap[$s['mood']] ?? $moodMap['professional'];

        // Brand color bleed
        $bleed = (int)$s['brand_color_bleed'];
        if ($bleed > 0) {
            $intensity = $bleed <= 15 ? 'subtle' : ($bleed <= 40 ? 'noticeable' : 'strong');
            $parts[] = "A {$intensity} accent of the brand color tinting the lighting and atmosphere";
        }

        $positive = implode('. ', $parts) . '.';

        // Avoid list — treated as a negative prompt of subject matter that must
        // not appear in the image. Normalised: split on commas, trim, dedupe,
        // re-join with semicolons so the model sees a clear list of items.
        $avoidRaw = trim((string)($s['avoid_list'] ?? ''));
        if ($avoidRaw === '') {
            return $positive;
        }

        $terms = array_values(array_unique(array_filter(array_map(
            'trim',
            preg_split('/[,\n]/', $avoidRaw)
        ))));
        if (empty($terms)) {
            return $positive;
        }

        $negative = ' STRICT NEGATIVE PROMPT — the image MUST NOT contain any of the following: '
            . implode('; ', $terms)
            . '. Do not include, reference, or imply these subjects anywhere in the composition.';

        return $positive . $negative;
    }
}
