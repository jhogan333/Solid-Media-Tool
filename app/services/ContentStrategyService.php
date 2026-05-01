<?php

class ContentStrategyService
{
    private ContentTheme $themeModel;
    private ThemeSample $sampleModel;
    private ThemeSchedule $scheduleModel;

    public function __construct()
    {
        $this->themeModel = new ContentTheme();
        $this->sampleModel = new ThemeSample();
        $this->scheduleModel = new ThemeSchedule();
    }

    // ------ Themes ------

    public function getThemes(int $clientId): array
    {
        $themes = $this->themeModel->getByClient($clientId);
        foreach ($themes as &$theme) {
            $theme['samples'] = $this->sampleModel->getByTheme((int)$theme['id']);
            if (is_string($theme['required_elements'])) {
                $theme['required_elements'] = json_decode($theme['required_elements'], true) ?: [];
            }
        }
        return $themes;
    }

    public function getActiveThemes(int $clientId): array
    {
        $themes = $this->themeModel->getActiveByClient($clientId);
        foreach ($themes as &$theme) {
            $theme['samples'] = $this->sampleModel->getByTheme((int)$theme['id']);
            if (is_string($theme['required_elements'])) {
                $theme['required_elements'] = json_decode($theme['required_elements'], true) ?: [];
            }
        }
        return $themes;
    }

    public function getTheme(int $themeId, int $clientId): ?array
    {
        $theme = $this->themeModel->getByIdAndClient($themeId, $clientId);
        if (!$theme) return null;
        $theme['samples'] = $this->sampleModel->getByTheme($themeId);
        if (is_string($theme['required_elements'])) {
            $theme['required_elements'] = json_decode($theme['required_elements'], true) ?: [];
        }
        return $theme;
    }

    public function createTheme(int $clientId, array $data): int
    {
        // Prevent duplicate theme names (case-insensitive)
        $name = trim($data['name'] ?? '');
        if (!empty($name)) {
            $existing = Database::fetch(
                "SELECT id FROM content_themes WHERE client_id = :cid AND LOWER(TRIM(name)) = LOWER(:name) LIMIT 1",
                ['cid' => $clientId, 'name' => $name]
            );
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        // Default every new theme to have all the required elements checked.
        // The user can uncheck any they don't want, but out-of-the-box we always
        // include phone, website, hashtags, CTA and (sparingly) emojis on every post.
        $defaultElements = [
            'phone' => true,
            'website' => true,
            'hashtags' => true,
            'cta' => true,
            'emojis' => true,
        ];
        $required = is_array($data['required_elements'] ?? null) ? $data['required_elements'] : [];
        $required = array_merge($defaultElements, $required);

        $themeData = [
            'client_id' => $clientId,
            'name' => $name,
            'description' => trim($data['description'] ?? ''),
            'copy_instructions' => trim($data['copy_instructions'] ?? ''),
            'required_elements' => json_encode($required),
            'default_hashtags' => trim($data['default_hashtags'] ?? ''),
            'image_style_override' => $data['image_style_override'] ?? 'global',
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ];
        $themeId = $this->themeModel->create($themeData);

        $this->saveSamples($themeId, $data['samples'] ?? []);

        return $themeId;
    }

    public function updateTheme(int $themeId, int $clientId, array $data): bool
    {
        $theme = $this->themeModel->getByIdAndClient($themeId, $clientId);
        if (!$theme) return false;

        $themeData = [];
        if (isset($data['name'])) $themeData['name'] = trim($data['name']);
        if (isset($data['description'])) $themeData['description'] = trim($data['description']);
        if (isset($data['copy_instructions'])) $themeData['copy_instructions'] = trim($data['copy_instructions']);
        if (isset($data['required_elements'])) $themeData['required_elements'] = json_encode($data['required_elements']);
        if (isset($data['default_hashtags'])) $themeData['default_hashtags'] = trim($data['default_hashtags']);
        if (isset($data['image_style_override'])) $themeData['image_style_override'] = $data['image_style_override'];
        if (isset($data['sort_order'])) $themeData['sort_order'] = (int)$data['sort_order'];
        if (isset($data['is_active'])) $themeData['is_active'] = (int)$data['is_active'];

        if (!empty($themeData)) {
            $this->themeModel->update($themeId, $themeData);
        }

        if (isset($data['samples'])) {
            $this->sampleModel->deleteByTheme($themeId);
            $this->saveSamples($themeId, $data['samples']);
        }

        return true;
    }

    public function deleteTheme(int $themeId, int $clientId): bool
    {
        $deleted = $this->themeModel->deleteByClient($themeId, $clientId);
        if ($deleted) {
            // Null out schedule references
            Database::query(
                "UPDATE theme_schedule SET theme_id = NULL WHERE theme_id = :tid AND client_id = :cid",
                ['tid' => $themeId, 'cid' => $clientId]
            );
        }
        return $deleted;
    }

    private function saveSamples(int $themeId, array $samples): void
    {
        $order = 0;
        foreach ($samples as $content) {
            $content = trim($content);
            if ($content === '') continue;
            $this->sampleModel->create([
                'theme_id' => $themeId,
                'sample_content' => $content,
                'sort_order' => $order++,
            ]);
        }
    }

    // ------ Schedule ------

    public function getSchedule(int $clientId): array
    {
        $rows = $this->scheduleModel->getByClient($clientId);
        $schedule = [];
        foreach ($rows as $row) {
            $schedule[(int)$row['day_of_week']] = $row;
        }
        return $schedule;
    }

    public function saveSchedule(int $clientId, array $dayThemeMap): void
    {
        for ($day = 0; $day <= 6; $day++) {
            $themeId = isset($dayThemeMap[$day]) && $dayThemeMap[$day] !== '' && $dayThemeMap[$day] !== null
                ? (int)$dayThemeMap[$day]
                : null;
            $this->scheduleModel->setDay($clientId, $day, $themeId);
        }
    }

    // ------ AI Critique ------

    public function critiquePost(string $content, ?array $themeContext = null): array
    {
        $systemPrompt = "You are a senior social media copy editor and marketing strategist. "
            . "Analyze the provided social media post and give constructive feedback. "
            . "Return valid JSON only — no markdown fences, no extra text.";

        $userPrompt = "Analyze this social media post and provide feedback:\n\n"
            . "POST:\n\"{$content}\"\n\n";

        if ($themeContext) {
            $userPrompt .= "THEME CONTEXT:\n"
                . "Theme: " . ($themeContext['name'] ?? '') . "\n"
                . "Copy Instructions: " . ($themeContext['copy_instructions'] ?? '') . "\n\n";
        }

        $userPrompt .= "Return a JSON object with these keys:\n"
            . "- \"strengths\": array of 2-3 things the post does well\n"
            . "- \"suggestions\": array of 2-4 specific improvement suggestions (mention if phone number, website, CTA, hashtags, or emojis should be added)\n"
            . "- \"revised\": a beautifully formatted rewritten version of the post that is READY TO COPY AND PASTE to social media. Follow these formatting rules for the revised post:\n"
            . "  * Start with a strong hook line using a relevant emoji\n"
            . "  * Use line breaks between paragraphs for readability\n"
            . "  * Use relevant emojis at the start of key points or bullet items\n"
            . "  * Include a clear call-to-action on its own line\n"
            . "  * Add two blank lines before hashtags\n"
            . "  * Put all hashtags on the last line, space-separated\n"
            . "  * Replace [Your Phone Number] or [Your Website] with actual placeholders like 📞 555-123-4567 and 🌐 yourwebsite.com\n"
            . "  * The post should look polished and professional when pasted directly into LinkedIn or Facebook\n\n"
            . "Return valid JSON only.";

        $payload = json_encode([
            'model' => OPENROUTER_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 1200,
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
            return [
                'strengths' => ['Could not analyze — API error'],
                'suggestions' => [],
                'revised' => $content,
            ];
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $result = json_decode($text, true);
        if (!$result || !isset($result['strengths'])) {
            return [
                'strengths' => ['Post has content worth building on'],
                'suggestions' => ['Could not parse AI suggestions — try again'],
                'revised' => $content,
            ];
        }

        return $result;
    }
}
