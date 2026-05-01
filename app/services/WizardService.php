<?php

class WizardService
{
    public function isSetupComplete(int $clientId): bool
    {
        $branding = (new BrandingService())->get($clientId);
        // Setup is complete if website and phone are set (wizard requires these)
        $website = trim($branding['website'] ?? '');
        $phone = trim($branding['phone'] ?? '');
        $logo = trim($branding['logo_url'] ?? '');
        // At least website OR phone must be set (wizard fills these in)
        return !empty($website) || !empty($phone) || !empty($logo);
    }

    public function scanWebsite(string $url): array
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        // Fetch HTML
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SolidSocialBot/1.0)',
            CURLOPT_MAXREDIRS => 3,
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 400 || empty($html)) {
            return ['error' => 'Could not access website. You can enter your information manually.'];
        }

        // Strip scripts/styles, truncate
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = strip_tags($html, '<title><meta><h1><h2><h3><p><li><a>');
        $html = preg_replace('/\s+/', ' ', $html);
        $html = mb_substr($html, 0, 5000);

        // Send to AI for extraction
        $systemPrompt = "You are a business analyst. Extract key information from this website content. "
            . "Return valid JSON only — no markdown fences.";

        $userPrompt = "Analyze this website content and extract:\n\n"
            . $html . "\n\n"
            . "Return a JSON object with these keys:\n"
            . "- \"company_name\": the company name\n"
            . "- \"services\": array of 3-8 key services offered\n"
            . "- \"about\": 2-3 sentence summary of what the company does\n"
            . "- \"phone\": phone number if found (or empty string)\n"
            . "- \"email\": email address if found (or empty string)\n"
            . "- \"industry\": the primary industry (e.g. IT, Healthcare, Finance)\n"
            . "- \"keywords\": array of 5-10 industry keywords for social media\n"
            . "- \"tagline\": a suggested tagline based on what you read\n"
            . "Return valid JSON only.";

        $payload = json_encode([
            'model' => OPENROUTER_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 800,
            'temperature' => 0.3,
        ]);

        $ch = curl_init(OPENROUTER_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENROUTER_API_KEY,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['error' => 'AI analysis failed. You can enter your information manually.'];
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);
        $result = json_decode($text, true);

        return $result ?: ['error' => 'Could not parse website data. Enter your information manually.'];
    }

    public function suggestThemes(array $businessInfo, array $existingThemeNames = []): array
    {
        $systemPrompt = "You are a social media strategist. Suggest content themes for this business. "
            . "Return valid JSON only — no markdown fences.";

        $context = "Business: " . ($businessInfo['company_name'] ?? 'Unknown') . "\n"
            . "Industry: " . ($businessInfo['industry'] ?? 'General') . "\n"
            . "Services: " . implode(', ', $businessInfo['services'] ?? []) . "\n"
            . "About: " . ($businessInfo['about'] ?? '') . "\n"
            . "Keywords: " . implode(', ', $businessInfo['keywords'] ?? []) . "\n";

        // Tell the LLM which themes already exist so it proactively avoids them.
        $avoidBlock = '';
        if (!empty($existingThemeNames)) {
            $avoidBlock = "\nThe business ALREADY has these themes in their system — do NOT suggest any of these, "
                . "and do NOT suggest anything that is a close rewording, synonym, or narrower/broader variant of them "
                . "(e.g. \"IT Tips\" vs \"Tech Tips\", or \"Industry Trends\" vs \"Tech Trends\"):\n"
                . "- " . implode("\n- ", $existingThemeNames) . "\n"
                . "Only suggest themes that are clearly and meaningfully different from ALL of the above.\n";
        }

        $userPrompt = $context . $avoidBlock . "\n"
            . "Suggest 5-7 social media content themes for this business.\n"
            . "Each theme must be distinct and non-overlapping with the others.\n"
            . "Return a JSON array of objects, each with:\n"
            . "- \"name\": short theme name (2-4 words)\n"
            . "- \"description\": 1-2 sentence description of what content falls under this theme\n"
            . "- \"copy_instructions\": specific guidance for writing posts in this theme\n"
            . "- \"suggested_hashtags\": 4-5 relevant hashtags as a string\n"
            . "Return valid JSON only.";

        $payload = json_encode([
            'model' => OPENROUTER_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 1500,
            'temperature' => 0.7,
        ]);

        $ch = curl_init(OPENROUTER_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENROUTER_API_KEY,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return [];
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);
        $result = json_decode($text, true);

        if (!is_array($result)) return [];

        // Safety net: drop anything too close to an existing theme (or to another
        // suggestion in the same batch). The LLM was told to avoid duplicates above,
        // but we still filter programmatically so a bad response can't slip through.
        return $this->filterDuplicateThemes($result, $existingThemeNames);
    }

    /**
     * Remove suggestions that are too similar to existing theme names or to each
     * other. Uses normalised-token Jaccard overlap plus a similar_text percentage.
     * A suggestion is rejected if it's ≥60% similar to any kept/existing theme.
     */
    private function filterDuplicateThemes(array $suggestions, array $existingNames): array
    {
        $kept = [];
        $keptNorms = array_map([$this, 'normaliseThemeName'], $existingNames);

        foreach ($suggestions as $theme) {
            $name = trim($theme['name'] ?? '');
            if ($name === '') continue;
            $norm = $this->normaliseThemeName($name);
            if ($norm === '') continue;

            $isDup = false;
            foreach ($keptNorms as $other) {
                if ($this->themesAreSimilar($norm, $other)) {
                    $isDup = true;
                    break;
                }
            }
            if ($isDup) continue;

            $kept[] = $theme;
            $keptNorms[] = $norm;
        }
        return $kept;
    }

    /**
     * Lowercase, strip punctuation, drop filler/stopwords, sort remaining words.
     * "Tech Tips & Tricks" → "tech tips tricks"
     * "IT Tips & Tricks"   → "it tips tricks"
     */
    private function normaliseThemeName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $name); // drop punctuation
        $name = preg_replace('/\s+/', ' ', trim($name));
        if ($name === '') return '';
        $stop = ['and','or','the','a','an','of','for','to','in','on','with','our','your','we'];
        $words = array_values(array_filter(
            explode(' ', $name),
            function ($w) use ($stop) { return $w !== '' && !in_array($w, $stop, true); }
        ));
        sort($words);
        return implode(' ', $words);
    }

    /**
     * Two normalised names are "similar" if they share enough tokens OR if
     * similar_text reports ≥60% match. Catches synonyms ("IT"/"Tech") as long as
     * the other words overlap.
     */
    private function themesAreSimilar(string $a, string $b): bool
    {
        if ($a === '' || $b === '') return false;
        if ($a === $b) return true;

        // Token Jaccard — e.g. "it tips tricks" vs "tech tips tricks" → 2/4 = 50%
        $aw = array_unique(explode(' ', $a));
        $bw = array_unique(explode(' ', $b));
        $intersect = count(array_intersect($aw, $bw));
        $union = count(array_unique(array_merge($aw, $bw)));
        $jaccard = $union > 0 ? $intersect / $union : 0;

        // If the two names share ≥50% of their distinct tokens, they're dupes.
        if ($jaccard >= 0.5) return true;

        // Fallback: edit-distance similarity on the raw normalised strings.
        similar_text($a, $b, $pct);
        return $pct >= 60.0;
    }

    public function saveWizardData(int $clientId, array $data): void
    {
        // 1. Save branding
        $brandingService = new BrandingService();
        $brandingData = [];
        if (!empty($data['company_name'])) $brandingData['company_name'] = trim($data['company_name']);
        if (!empty($data['website'])) $brandingData['website'] = trim($data['website']);
        if (!empty($data['phone'])) $brandingData['phone'] = trim($data['phone']);
        if (!empty($data['tagline'])) $brandingData['tagline'] = trim($data['tagline']);
        if (!empty($data['primary_color'])) $brandingData['primary_color'] = trim($data['primary_color']);
        if (!empty($data['secondary_color'])) $brandingData['secondary_color'] = trim($data['secondary_color']);
        if (!empty($data['favicon_url'])) $brandingData['favicon_url'] = trim($data['favicon_url']);
        // Business context fields
        if (!empty($data['industry'])) $brandingData['industry'] = trim($data['industry']);
        if (!empty($data['about'])) $brandingData['about_company'] = trim($data['about']);
        if (!empty($data['services'])) {
            $brandingData['key_services'] = is_array($data['services']) ? implode(', ', $data['services']) : trim($data['services']);
        }
        if (!empty($data['keywords'])) {
            $brandingData['industry_keywords'] = is_array($data['keywords']) ? implode(', ', $data['keywords']) : trim($data['keywords']);
        }
        if (!empty($brandingData)) {
            $brandingService->save($clientId, $brandingData);
        }

        // 2. Create selected themes
        if (!empty($data['themes']) && is_array($data['themes'])) {
            $strategyService = new ContentStrategyService();
            $order = 0;
            foreach ($data['themes'] as $theme) {
                if (empty($theme['name'])) continue;
                $strategyService->createTheme($clientId, [
                    'name' => $theme['name'],
                    'description' => $theme['description'] ?? '',
                    'copy_instructions' => $theme['copy_instructions'] ?? '',
                    'default_hashtags' => $theme['suggested_hashtags'] ?? $theme['default_hashtags'] ?? '',
                    // createTheme() applies defaults for any missing flags, so omitting this
                    // will default phone/website/hashtags/cta/emojis to all-on.
                    'sort_order' => $order++,
                    'samples' => [],
                ]);
            }
        }

        // 3. Set default art direction
        $artService = new ArtDirectionService();
        $existing = $artService->get($clientId);
        if (empty($existing['id'])) {
            $artService->save($clientId, [
                'image_style' => 'photorealistic',
                'realism_level' => 8,
                'color_temperature' => 'cold',
                'contrast_level' => 'punchy',
                'mood' => 'professional',
                'brand_color_bleed' => 25,
                'illustration_limit' => 'max_1_per_week',
            ]);
        }
    }
}
