<?php

class AIService
{
    private string $openRouterKey;
    private string $openRouterModel;
    private string $kieKey;

    public function __construct()
    {
        $this->openRouterKey  = OPENROUTER_API_KEY;
        $this->openRouterModel = OPENROUTER_MODEL;
        $this->kieKey = KIE_API_KEY;
    }

    // ──────────────────────────────────────────
    //  TEXT GENERATION  (OpenRouter)
    // ──────────────────────────────────────────

    /**
     * Generate weekly content. Supports theme-aware generation when themeData is provided.
     *
     * @param array $themeData  Optional array of [{day, theme_name, description, copy_instructions, required_elements, samples, default_hashtags, image_style_override}, ...]
     */
    public function generateWeekContent(array $brandContext, array $memoryContext, array $themeData = []): array
    {
        $company  = $brandContext['company_name'] ?? 'the company';

        // Build memory exclusion list — flatten DB rows to simple strings
        $rawTopics = $memoryContext['topics'] ?? [];
        $rawAngles = $memoryContext['recent_angles'] ?? [];
        $usedTopics = array_column($rawTopics, 'topic');
        $usedAngles = array_column($rawAngles, 'angle');
        $exclusion  = '';
        if ($usedTopics) {
            $exclusion .= "\nAVOID these recently-used topics: " . implode(', ', array_slice($usedTopics, 0, 15));
        }
        if ($usedAngles) {
            $exclusion .= "\nAVOID these recently-used angles: " . implode(', ', array_slice($usedAngles, 0, 10));
        }

        // Determine days/types from theme data or use defaults
        if (!empty($themeData)) {
            $days = array_column($themeData, 'day');
            $postCount = count($themeData);
        } else {
            $days = ['Monday', 'Wednesday', 'Friday'];
            $postCount = 3;
        }
        $types = ['educational', 'promotional', 'engagement', 'storytelling', 'behind_the_scenes'];

        // Build company contact info for injection
        $phone = $brandContext['phone'] ?? '';
        $website = $brandContext['website'] ?? '';
        $contactBlock = '';
        if ($phone || $website) {
            $contactParts = [];
            if ($phone) $contactParts[] = "Phone: {$phone}";
            if ($website) $contactParts[] = "Website: {$website}";
            $contactBlock = "\nCompany contact info to include in posts: " . implode(', ', $contactParts);
        }

        $systemPrompt = "You are a senior social media strategist for {$company}. "
            . "Create engaging, on-brand social media posts formatted for LinkedIn and Facebook. "
            . "Return valid JSON only — no markdown fences, no extra text.";

        // Build per-post instructions when themes are provided
        $themeInstructions = '';
        if (!empty($themeData)) {
            foreach ($themeData as $i => $td) {
                $num = $i + 1;
                $themeInstructions .= "\nPost {$num} ({$td['day']}):\n";

                if (!empty($td['theme_name'])) {
                    $themeInstructions .= "Theme: {$td['theme_name']}";
                    if (!empty($td['description'])) {
                        $themeInstructions .= " — {$td['description']}";
                    }
                    $themeInstructions .= "\n";
                }
                if (!empty($td['copy_instructions'])) {
                    $themeInstructions .= "Writing style: {$td['copy_instructions']}\n";
                }
                // Required elements
                $req = $td['required_elements'] ?? [];
                $reqList = [];
                if (!empty($req['phone'])) $reqList[] = 'include phone number';
                if (!empty($req['website'])) $reqList[] = 'include website URL';
                if (!empty($req['cta'])) $reqList[] = 'include a call to action';
                if (!empty($req['hashtags'])) $reqList[] = 'include hashtags';
                if (!empty($req['emojis'])) $reqList[] = 'use emojis';
                if ($reqList) {
                    $themeInstructions .= "Required: " . implode(', ', $reqList) . "\n";
                }
                if (!empty($td['default_hashtags'])) {
                    $themeInstructions .= "Include these hashtags: {$td['default_hashtags']}\n";
                }
                // Sample posts for AI to mimic
                $samples = $td['samples'] ?? [];
                if (!empty($samples)) {
                    $themeInstructions .= "Mimic the tone and structure of these examples:\n";
                    foreach (array_slice($samples, 0, 2) as $si => $sample) {
                        $sampleText = is_array($sample) ? ($sample['sample_content'] ?? '') : $sample;
                        $sampleText = mb_substr(trim($sampleText), 0, 300);
                        $themeInstructions .= "Example " . ($si + 1) . ": \"{$sampleText}\"\n";
                    }
                }
            }
        }

        $userPrompt = "Generate {$postCount} social media posts for {$company} to publish on " . implode(', ', $days) . ".\n"
            . "Each post must have a DIFFERENT topic and angle.\n";

        if (empty($themeData)) {
            $userPrompt .= "Post types in order: " . implode(', ', array_slice($types, 0, $postCount)) . ".\n";
        }

        $userPrompt .= $exclusion . "\n"
            . $contactBlock . "\n";

        if ($themeInstructions) {
            $userPrompt .= "\nPer-post theme instructions:\n" . $themeInstructions . "\n";
        }

        $userPrompt .= "\nFORMATTING RULES for the 'content' field (CRITICAL — follow exactly):\n"
            . "1. Start with a strong opening line with a relevant emoji\n"
            . "2. Use \\n (newline) for line breaks — this will display on social media\n"
            . "3. Add a blank line (\\n\\n) between paragraphs for readability\n"
            . "4. Use emojis at the start of key points or bullet items (e.g. ✅, 🔒, 💡, 🚀, 📊)\n"
            . "5. Include a clear call-to-action on its own line\n"
            . "6. ALWAYS end with the company contact info on its own line:\n";
        if ($phone) {
            $userPrompt .= "   📞 {$phone}\n";
        }
        if ($website) {
            $userPrompt .= "   🌐 {$website}\n";
        }
        $userPrompt .= "7. After the contact info, add TWO blank lines, then put all hashtags on the last line, space-separated\n"
            . "8. The post should be 150-350 characters and look polished when pasted directly into LinkedIn or Facebook\n"
            . "9. Mix up the style — vary the CTA wording, emoji choices, and hook styles across posts\n\n"
            . "Return a JSON array of {$postCount} objects, each with these exact keys:\n"
            . "day, title, content, post_type, topic, keywords, angle, image_prompt\n"
            . "- title: catchy headline with an emoji (max 80 chars)\n"
            . "- content: the FULL formatted post caption following the formatting rules above. Use \\n for line breaks.\n"
            . "- topic: one-word or short phrase topic\n"
            . "- keywords: comma-separated relevant keywords\n"
            . "- angle: the creative approach used\n"
            . "- image_prompt: a detailed prompt to generate a matching image\n";

        $response = $this->callOpenRouter($systemPrompt, $userPrompt);

        if ($response !== null) {
            $decoded = $this->parseJson($response);
            if (is_array($decoded) && count($decoded) >= 1) {
                return $decoded;
            }
        }

        // Fallback if API fails or key missing
        return $this->fallbackWeekContent($company, $days, array_slice($types, 0, count($days)));
    }

    public function generateSinglePost(string $topic, string $postType, array $brandContext, array $memoryContext): array
    {
        $company = $brandContext['company_name'] ?? 'the company';
        $phone = $brandContext['phone'] ?? '';
        $website = $brandContext['website'] ?? '';

        $rawAngles = $memoryContext['recent_angles'] ?? [];
        $usedAngles = array_column($rawAngles, 'angle');
        $exclusion  = $usedAngles ? "\nAVOID these angles: " . implode(', ', array_slice($usedAngles, 0, 10)) : '';

        $contactInfo = '';
        if ($phone) $contactInfo .= "Phone: {$phone}. ";
        if ($website) $contactInfo .= "Website: {$website}. ";

        $systemPrompt = "You are a senior social media strategist for {$company}. "
            . "Create posts formatted for LinkedIn and Facebook. "
            . "Return valid JSON only — no markdown fences, no extra text.";

        $userPrompt = "Create one {$postType} social media post about \"{$topic}\" for {$company}.\n"
            . ($contactInfo ? "Company contact: {$contactInfo}\n" : '')
            . $exclusion . "\n\n"
            . "FORMATTING RULES for the 'content' field:\n"
            . "1. Start with a hook line using a relevant emoji\n"
            . "2. Use \\n for line breaks, \\n\\n for paragraph breaks\n"
            . "3. Use emojis at key points (✅ 🔒 💡 🚀 📊 etc.)\n"
            . "4. Include a clear CTA on its own line\n"
            . "5. End with contact info on its own line"
            . ($phone ? " (📞 {$phone})" : '') . ($website ? " (🌐 {$website})" : '') . "\n"
            . "6. Two blank lines, then hashtags on the last line\n"
            . "7. 150-350 chars, polished and ready to paste\n\n"
            . "Return a single JSON object with these keys:\n"
            . "title, content, post_type, topic, keywords, angle, image_prompt\n"
            . "- title: catchy headline with emoji (max 80 chars)\n"
            . "- content: FULL formatted post following the rules above, using \\n for line breaks\n"
            . "- topic: \"{$topic}\"\n"
            . "- post_type: \"{$postType}\"\n"
            . "- keywords: comma-separated relevant keywords\n"
            . "- angle: the creative approach used\n"
            . "- image_prompt: detailed prompt to generate a matching image\n";

        $response = $this->callOpenRouter($systemPrompt, $userPrompt);

        if ($response !== null) {
            $decoded = $this->parseJson($response);
            if (is_array($decoded) && isset($decoded['title'])) {
                return $decoded;
            }
            // Might have returned wrapped in an array
            if (is_array($decoded) && isset($decoded[0]['title'])) {
                return $decoded[0];
            }
        }

        // Fallback
        return [
            'title' => "Post about {$topic}",
            'content' => "AI generation unavailable. Please check your OpenRouter API key in Branding > API Settings. Topic: {$topic}",
            'post_type' => $postType,
            'topic' => $topic,
            'keywords' => $topic,
            'angle' => "{$postType} perspective",
            'image_prompt' => "Professional social media image about {$topic} for {$company}",
        ];
    }

    public function regenerateText(string $originalContent, string $instruction): string
    {
        $systemPrompt = "You are a social media copywriter. Rewrite the given post with a fresh angle. "
            . "Return ONLY the new post text, nothing else.";

        $userPrompt = "Original post:\n\"{$originalContent}\"\n\n"
            . "Instruction: {$instruction}\n\n"
            . "Rewrite this post with a fresh perspective. Keep it the same length. Include hashtags.";

        $response = $this->callOpenRouter($systemPrompt, $userPrompt);

        return $response ?? "Could not regenerate. Check your OpenRouter API key. Original: {$originalContent}";
    }

    /**
     * Public wrapper for the OpenRouter call — used by services that need
     * a generic "send a prompt, get a text response" without the post-
     * generation conventions baked into the other public methods. Returns
     * null on any failure so callers can degrade gracefully.
     */
    public function chat(string $system, string $user): ?string
    {
        return $this->callOpenRouter($system, $user);
    }

    private function callOpenRouter(string $system, string $user): ?string
    {
        if (empty($this->openRouterKey)) {
            return null;
        }

        $payload = json_encode([
            'model' => $this->openRouterModel,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.8,
            'max_tokens' => 2000,
        ]);

        $ch = curl_init(OPENROUTER_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->openRouterKey,
                'HTTP-Referer: ' . BASE_URL,
                'X-OpenRouter-Title: ' . APP_NAME,
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("OpenRouter error [{$httpCode}]: {$error} | Response: " . substr($response, 0, 500));
            return null;
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    // ──────────────────────────────────────────
    //  IMAGE GENERATION  (Kie.ai — NanoBanana2)
    // ──────────────────────────────────────────

    public function generateImage(string $prompt): string
    {
        // Image generation can take a while (polling Kie.ai) — extend time limit
        set_time_limit(180);

        if (empty($this->kieKey)) {
            return 'https://placehold.co/1080x1080/6366f1/ffffff?text=Add+Kie+API+Key';
        }

        // Clean the prompt for image generation
        $cleanPrompt = $prompt;
        // Strip hashtags
        $cleanPrompt = preg_replace('/#\w+\s*/u', '', $cleanPrompt);
        // Strip emojis (unicode emoji ranges)
        $cleanPrompt = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{200D}\x{20E3}\x{2702}-\x{27B0}\x{2300}-\x{23FF}]/u', '', $cleanPrompt);
        // Strip phone numbers and URLs
        $cleanPrompt = preg_replace('/\d{3}[-.]?\d{3}[-.]?\d{4}/', '', $cleanPrompt);
        $cleanPrompt = preg_replace('#https?://\S+#i', '', $cleanPrompt);
        // Strip common CTA phrases that shouldn't be in image prompts
        $cleanPrompt = preg_replace('/\b(call us|contact us|visit|schedule|sign up|learn more|click here|check out)\b.*/i', '', $cleanPrompt);
        // Clean up whitespace
        $cleanPrompt = preg_replace('/\s+/', ' ', trim($cleanPrompt));

        // Truncate to keep prompt reasonable for image gen (max 300 chars before art direction)
        if (mb_strlen($cleanPrompt) > 300) {
            $cleanPrompt = mb_substr($cleanPrompt, 0, 300);
        }

        if (empty($cleanPrompt)) {
            $cleanPrompt = $prompt; // fallback to original if nothing left
        }

        // Inject art direction modifiers
        $artService = new ArtDirectionService();
        $artModifiers = $artService->buildImagePromptModifiers($GLOBALS['client_id']);
        if (!empty($artModifiers)) {
            // Keep art modifiers concise — truncate to 200 chars
            $artModifiers = mb_substr($artModifiers, 0, 200);
            $cleanPrompt = $cleanPrompt . '. ' . $artModifiers;
        }

        // Step 1: Create task
        $createResult = $this->kieCreateTask($cleanPrompt);
        if (is_array($createResult) && isset($createResult['error'])) {
            // Return structured error as a JSON-encoded string that starts with ERROR:
            return 'ERROR:' . json_encode($createResult);
        }
        $taskId = $createResult;
        if (!$taskId) {
            return 'ERROR:' . json_encode(['error' => 'Failed to create image task', 'code' => 'CREATE_FAILED']);
        }

        // Step 2: Poll for result (max ~120 seconds)
        $pollResult = $this->kiePollResult($taskId);
        if (is_array($pollResult) && isset($pollResult['error'])) {
            return 'ERROR:' . json_encode($pollResult);
        }
        $imageUrl = $pollResult;
        if (!$imageUrl) {
            return 'ERROR:' . json_encode(['error' => 'Image generation timed out after 2 minutes', 'code' => 'TIMEOUT']);
        }

        // Step 3: Download and save locally
        $localPath = $this->downloadImage($imageUrl);
        if (!$localPath) {
            return $imageUrl;
        }

        // Step 4: Watermark with logo + website
        $watermarked = $this->watermarkImage($localPath);
        return $watermarked ?: $localPath;
    }

    /**
     * Stamp the brand logo (bottom-left) and website (bottom-right)
     * onto the image with a subtle dark gradient fade at the bottom.
     */
    public function watermarkImage(string $imageWebUrl): ?string
    {
        // Check art direction watermark settings
        $artService = new ArtDirectionService();
        $artSettings = $artService->get($GLOBALS['client_id']);

        // If watermark is disabled, skip entirely
        if (empty($artSettings['watermark_enabled'])) {
            return $imageWebUrl;
        }

        // Get branding data
        $brandingService = new BrandingService();
        $brand = $brandingService->get($GLOBALS['client_id']);
        $logoUrl  = $brand['logo_url'] ?? '';
        // Watermark website: art direction override takes precedence, then branding
        $website = !empty($artSettings['watermark_website']) ? $artSettings['watermark_website'] : ($brand['website'] ?? '');
        $logoPosition = $artSettings['watermark_logo_position'] ?? 'bottom-left';
        $gradientOpacity = (int)($artSettings['watermark_gradient_opacity'] ?? 85);

        // Nothing to stamp if no logo and no website
        if (empty($logoUrl) && empty($website)) {
            return $imageWebUrl;
        }

        // Convert web URL to local file path
        $filename = basename(parse_url($imageWebUrl, PHP_URL_PATH));
        $localFile = UPLOAD_DIR . $filename;

        if (!file_exists($localFile)) {
            return $imageWebUrl;
        }

        // Load image
        $info = getimagesize($localFile);
        if (!$info) return $imageWebUrl;

        $mime = $info['mime'];
        $img = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($localFile),
            'image/png'  => imagecreatefrompng($localFile),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($localFile) : null,
            default      => null,
        };
        if (!$img) return $imageWebUrl;

        $imgW = imagesx($img);
        $imgH = imagesy($img);

        // ── Dark gradient at the bottom (bottom 20% of image) ──
        $gradientHeight = (int)($imgH * 0.20);
        $gradientStart  = $imgH - $gradientHeight;

        for ($y = 0; $y < $gradientHeight; $y++) {
            $alpha = (int)($gradientOpacity * ($y / $gradientHeight));
            $color = imagecolorallocatealpha($img, 0, 0, 0, 127 - (int)(($alpha / 100) * 127));
            imageline($img, 0, $gradientStart + $y, $imgW, $gradientStart + $y, $color);
        }

        $padding = (int)($imgW * 0.03); // 3% padding from edges
        $bottomY = $imgH - $padding;

        // ── Logo ──
        $logoOnRight = ($logoPosition === 'bottom-right');
        if (!empty($logoUrl)) {
            $logoFilename = basename(parse_url($logoUrl, PHP_URL_PATH));
            $logoLocal = UPLOAD_DIR . $logoFilename;

            if (file_exists($logoLocal)) {
                $logoInfo = getimagesize($logoLocal);
                if ($logoInfo) {
                    $logoMime = $logoInfo['mime'];
                    $logoImg = match ($logoMime) {
                        'image/jpeg' => imagecreatefromjpeg($logoLocal),
                        'image/png'  => imagecreatefrompng($logoLocal),
                        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($logoLocal) : null,
                        'image/gif'  => imagecreatefromgif($logoLocal),
                        default      => null,
                    };

                    if ($logoImg) {
                        $logoOrigW = imagesx($logoImg);
                        $logoOrigH = imagesy($logoImg);

                        // Scale logo to ~24% of image width, max 100px tall
                        $targetW = (int)($imgW * 0.24);
                        $scale   = $targetW / $logoOrigW;
                        $targetH = (int)($logoOrigH * $scale);
                        if ($targetH > 100) {
                            $targetH = 100;
                            $targetW = (int)($logoOrigW * (100 / $logoOrigH));
                        }

                        $logoX = $logoOnRight ? ($imgW - $padding - $targetW) : $padding;
                        $logoY = $bottomY - $targetH;

                        // Use imagecopyresampled for quality
                        imagecopyresampled(
                            $img, $logoImg,
                            $logoX, $logoY,
                            0, 0,
                            $targetW, $targetH,
                            $logoOrigW, $logoOrigH
                        );
                        imagedestroy($logoImg);
                    }
                }
            }
        }

        // ── Website text (opposite side of logo) ──
        if (!empty($website)) {
            $white = imagecolorallocate($img, 255, 255, 255);

            // Try to use a nice font if available, fallback to built-in
            $fontSize = max(14, (int)($imgW * 0.018));
            $fontFile = APP_ROOT . '/public/fonts/Inter-Variable.ttf';

            if (file_exists($fontFile)) {
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $website);
                $textW = abs($bbox[2] - $bbox[0]);
                $textH = abs($bbox[7] - $bbox[1]);
                // Text goes on opposite side of logo
                $textX = $logoOnRight ? $padding : ($imgW - $padding - $textW);
                $textY = $bottomY - (int)($textH * 0.3);
                imagettftext($img, $fontSize, 0, $textX, $textY, $white, $fontFile, $website);
            } else {
                // Built-in font fallback (font 5 is the largest built-in)
                $font = 5;
                $charW = imagefontwidth($font);
                $charH = imagefontheight($font);
                $textW = strlen($website) * $charW;
                $textX = $logoOnRight ? $padding : ($imgW - $padding - $textW);
                $textY = $bottomY - $charH;
                imagestring($img, $font, $textX, $textY, $website, $white);
            }
        }

        // Save the watermarked image (overwrite original)
        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($img, $localFile, 92),
            'image/png'  => imagepng($img, $localFile, 2),
            'image/webp' => function_exists('imagewebp') ? imagewebp($img, $localFile, 90) : false,
            default      => false,
        };

        imagedestroy($img);

        return $saved ? $imageWebUrl : $imageWebUrl;
    }

    /**
     * @return string|array Task ID string on success, or error array on failure
     */
    public function kieCreateTask(string $prompt): string|array
    {
        $payload = json_encode([
            'model' => KIE_MODEL,
            'input' => [
                'prompt'       => $prompt,
                'aspect_ratio' => '1:1',
                'resolution'   => '1K',
                'output_format' => 'jpg',
            ],
        ]);

        $ch = curl_init(KIE_CREATE_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->kieKey,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Kie.ai create error [{$httpCode}]: {$error}");
            return ['error' => "Connection error: {$error}", 'code' => 'CONNECTION_ERROR'];
        }

        $data = json_decode($response, true);

        if ($httpCode === 401 || $httpCode === 403) {
            error_log("Kie.ai auth error [{$httpCode}]: " . substr($response, 0, 500));
            return ['error' => 'API authentication failed. Check your Kie.ai API key in Branding settings.', 'code' => 'AUTH_FAILED'];
        }

        if ($httpCode === 402 || $httpCode === 429 ||
            (isset($data['msg']) && (stripos($data['msg'], 'credit') !== false || stripos($data['msg'], 'quota') !== false || stripos($data['msg'], 'limit') !== false))) {
            error_log("Kie.ai credits/quota error [{$httpCode}]: " . ($data['msg'] ?? ''));
            return ['error' => 'Image generation credits are depleted. Contact your administrator to add more credits.', 'code' => 'OUT_OF_CREDITS'];
        }

        if ($httpCode !== 200) {
            $msg = $data['msg'] ?? "HTTP {$httpCode}";
            error_log("Kie.ai create error [{$httpCode}]: {$msg}");
            return ['error' => "Image service error: {$msg}", 'code' => 'API_ERROR', 'http_code' => $httpCode];
        }

        $taskId = $data['data']['taskId'] ?? null;
        if (!$taskId) {
            return ['error' => 'No task ID returned from image service', 'code' => 'NO_TASK_ID'];
        }

        return $taskId;
    }

    /**
     * @return string|array Image URL on success, or error array on failure, or null on timeout
     */
    public function kiePollResult(string $taskId, int $maxWait = 120): string|array|null
    {
        $start = time();

        while (time() - $start < $maxWait) {
            $url = KIE_STATUS_URL . '?taskId=' . urlencode($taskId);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->kieKey,
                ],
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
            $state = $data['data']['state'] ?? 'unknown';

            if ($state === 'success') {
                $resultJson = $data['data']['resultJson'] ?? '';
                if (is_string($resultJson)) {
                    $result = json_decode($resultJson, true);
                } else {
                    $result = $resultJson;
                }
                $urls = $result['resultUrls'] ?? [];
                return $urls[0] ?? null;
            }

            if ($state === 'fail') {
                $failMsg = $data['data']['failMsg'] ?? 'Unknown error';
                error_log("Kie.ai task {$taskId} failed: {$failMsg}");

                // Detect specific failure reasons
                $failLower = strtolower($failMsg);
                if (strpos($failLower, 'credit') !== false || strpos($failLower, 'quota') !== false || strpos($failLower, 'balance') !== false) {
                    return ['error' => 'Image generation credits are depleted. Contact your administrator to add more credits.', 'code' => 'OUT_OF_CREDITS'];
                }
                if (strpos($failLower, 'nsfw') !== false || strpos($failLower, 'content') !== false || strpos($failLower, 'policy') !== false || strpos($failLower, 'safety') !== false) {
                    return ['error' => 'The image prompt was flagged by the AI safety filter. This is usually automatic — click Try Again and it should work with a slightly different approach.', 'code' => 'CONTENT_FILTER', 'auto_retry' => true];
                }

                return ['error' => "Image generation failed: {$failMsg}", 'code' => 'GENERATION_FAILED'];
            }

            sleep(3);
        }

        error_log("Kie.ai task {$taskId} timed out after {$maxWait}s");
        return null;
    }

    public function downloadImage(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $imageData = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$imageData) {
            return null;
        }

        $ext = 'jpg';
        if (str_contains($contentType, 'png')) $ext = 'png';
        if (str_contains($contentType, 'webp')) $ext = 'webp';

        $filename = 'ai_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = UPLOAD_DIR . $filename;

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        if (file_put_contents($destination, $imageData)) {
            return BASE_URL . '/uploads/' . $filename;
        }

        return null;
    }

    // ──────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────

    private function parseJson(string $raw): ?array
    {
        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Try to find JSON in the response
        if (preg_match('/\[[\s\S]*\]/', $cleaned, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }
        if (preg_match('/\{[\s\S]*\}/', $cleaned, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }

        error_log("AIService JSON parse failed: " . substr($raw, 0, 300));
        return null;
    }

    private function fallbackWeekContent(string $company, array $days, array $types): array
    {
        $results = [];
        foreach ($days as $i => $day) {
            $results[] = [
                'day'          => $day,
                'title'        => "Sample {$types[$i]} post for {$day}",
                'content'      => "AI generation unavailable. Add your OpenRouter API key in Branding > API Settings to generate real content for {$company}.",
                'post_type'    => $types[$i],
                'topic'        => 'general',
                'keywords'     => strtolower($company) . ', technology',
                'angle'        => $types[$i] . ' angle',
                'image_prompt' => "Professional {$types[$i]} social media image for {$company}",
            ];
        }
        return $results;
    }

    /**
     * Check API connectivity — used by the settings page.
     */
    public function testOpenRouter(): array
    {
        if (empty($this->openRouterKey)) {
            return ['ok' => false, 'error' => 'No API key configured'];
        }
        $response = $this->callOpenRouter('Respond with exactly: OK', 'Test');
        return $response !== null
            ? ['ok' => true, 'message' => 'Connected']
            : ['ok' => false, 'error' => 'API call failed — check key'];
    }

    public function testKie(): array
    {
        if (empty($this->kieKey)) {
            return ['ok' => false, 'error' => 'No API key configured'];
        }
        // Light test — just try creating a task (won't wait for result)
        $taskId = $this->kieCreateTask('A simple blue square on a white background, minimal, test image');
        return $taskId
            ? ['ok' => true, 'message' => 'Connected (task: ' . $taskId . ')']
            : ['ok' => false, 'error' => 'API call failed — check key'];
    }
}
