<?php

/**
 * ReportGeneratorService — assembles all the data a generated report needs.
 *
 * The output is a self-contained array snapshot that gets JSON-encoded and
 * stored in generated_reports.report_data. This means:
 *
 *   1. The report view renders from the snapshot, not live queries, so
 *      historical reports keep their numbers even if posts are later
 *      edited or deleted.
 *   2. The public shareable web view (Phase 3) can render a report
 *      without authenticating to the main tool.
 *   3. AI costs are paid once at generation time, not every time someone
 *      views the report.
 */
class ReportGeneratorService
{
    /**
     * Build the full report data for a given date range. Safe to call
     * multiple times — this is a pure read+assemble method with one
     * blocking AI call.
     *
     * @param int    $clientId
     * @param string $startDate Y-m-d
     * @param string $endDate   Y-m-d
     * @param string $title     Report title (user-provided or defaulted)
     */
    public function build(int $clientId, string $startDate, string $endDate, string $title): array
    {
        $postModel      = new Post();
        $brandingSvc    = new BrandingService();
        $reportSettings = new ReportSettingsService();

        $branding = $brandingSvc->get($clientId);
        $settings = $reportSettings->get($clientId);

        // Fetch posts by created_at within the range — captures everything
        // that was authored in the window, not just scheduled posts.
        $posts = Database::fetchAll(
            "SELECT * FROM posts
             WHERE client_id = :cid
               AND DATE(created_at) BETWEEN :start AND :end
             ORDER BY COALESCE(scheduled_at, created_at) ASC",
            ['cid' => $clientId, 'start' => $startDate, 'end' => $endDate]
        );

        $postCount = count($posts);

        // Metrics breakdown
        $metrics = [
            'total_posts'     => $postCount,
            'published'       => 0,
            'scheduled'       => 0,
            'draft'           => 0,
            'failed'          => 0,
            'pending_review'  => 0,
            'platforms'       => [],
            'topics'          => [],
            'post_types'      => [],
        ];

        foreach ($posts as $p) {
            $status = $p['status'] ?? 'draft';
            if (isset($metrics[$status])) {
                $metrics[$status]++;
            }

            // Platform counts (handle JSON multi-platform)
            $platforms = [];
            if (!empty($p['platforms'])) {
                $decoded = json_decode($p['platforms'], true);
                if (is_array($decoded)) $platforms = $decoded;
            }
            if (empty($platforms) && !empty($p['platform'])) {
                $platforms = [$p['platform']];
            }
            foreach ($platforms as $plat) {
                $plat = strtolower(trim($plat));
                if ($plat === '') continue;
                if (!isset($metrics['platforms'][$plat])) $metrics['platforms'][$plat] = 0;
                $metrics['platforms'][$plat]++;
            }

            // Topic counts
            $topic = trim($p['topic'] ?? '');
            if ($topic !== '') {
                if (!isset($metrics['topics'][$topic])) $metrics['topics'][$topic] = 0;
                $metrics['topics'][$topic]++;
            }

            // Post type counts
            $ptype = $p['post_type'] ?? '';
            if ($ptype !== '') {
                if (!isset($metrics['post_types'][$ptype])) $metrics['post_types'][$ptype] = 0;
                $metrics['post_types'][$ptype]++;
            }
        }

        // Sort topic + platform buckets by count desc
        arsort($metrics['platforms']);
        arsort($metrics['topics']);
        arsort($metrics['post_types']);

        // Failure rate
        $metrics['failure_rate'] = $postCount > 0
            ? round(($metrics['failed'] / $postCount) * 100, 1)
            : 0;

        // Cost savings — only counts posts that actually went somewhere
        // (published or scheduled). Drafts don't save time.
        $billablePostCount = $metrics['published'] + $metrics['scheduled'];
        $savings = $reportSettings->calculate($billablePostCount, $settings);

        // Post cards data (trimmed for storage — we keep the content but
        // cap it at a reasonable length for display)
        $postCards = array_map(function($p) {
            $platforms = [];
            if (!empty($p['platforms'])) {
                $decoded = json_decode($p['platforms'], true);
                if (is_array($decoded)) $platforms = $decoded;
            }
            if (empty($platforms) && !empty($p['platform'])) {
                $platforms = [$p['platform']];
            }
            $content = $p['content'] ?? '';
            if (function_exists('mb_strlen') && mb_strlen($content) > 800) {
                $content = mb_substr($content, 0, 800) . '…';
            }
            return [
                'id'           => (int) $p['id'],
                'title'        => (string) ($p['title'] ?? ''),
                'content'      => $content,
                'image_url'    => (string) ($p['image_url'] ?? ''),
                'platforms'    => $platforms,
                'post_type'    => (string) ($p['post_type'] ?? ''),
                'topic'        => (string) ($p['topic'] ?? ''),
                'status'       => (string) ($p['status'] ?? ''),
                'scheduled_at' => $p['scheduled_at'] ?? null,
                'created_at'   => $p['created_at'] ?? null,
            ];
        }, $posts);

        // AI-generated executive summary + helpful tips
        $ai = $this->generateAiSummary($branding, $metrics, $savings, $postCards, $title, $startDate, $endDate);

        // Meta block — everything the view needs to render branded
        $meta = [
            'title'          => $title,
            'company_name'   => $branding['company_name'] ?? 'Your Company',
            'logo_url'       => $branding['logo_url'] ?? '',
            'dark_logo_url'  => $branding['dark_logo_url'] ?? '',
            'primary_color'  => $branding['primary_color'] ?? '#6366f1',
            'website'        => $branding['website'] ?? '',
            'phone'          => $branding['phone'] ?? '',
            'date_range'     => [
                'start'        => $startDate,
                'end'          => $endDate,
                'display'      => date('M j, Y', strtotime($startDate)) . ' – ' . date('M j, Y', strtotime($endDate)),
                'day_count'    => max(1, (int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1),
            ],
            'generated_at'   => date('Y-m-d H:i:s'),
            'generated_by'   => $_SESSION['first_name'] ?? $_SESSION['username'] ?? 'Admin',
        ];

        return [
            'meta'        => $meta,
            'metrics'     => $metrics,
            'savings'     => $savings,
            'posts'       => $postCards,
            'ai_summary'  => $ai['summary'],
            'ai_tips'     => $ai['tips'],
            'ai_highlight'=> $ai['highlight'],
        ];
    }

    /**
     * Call OpenRouter to produce an executive summary, helpful tips, and
     * a short one-line highlight for the email intro. Falls back to
     * template text on any failure so the report always renders.
     */
    private function generateAiSummary(
        array  $branding,
        array  $metrics,
        array  $savings,
        array  $postCards,
        string $title,
        string $startDate,
        string $endDate
    ): array {
        $company = $branding['company_name'] ?? 'the company';
        $topTopics = array_slice(array_keys($metrics['topics']), 0, 5);
        $topPlatforms = array_keys($metrics['platforms']);

        $system = 'You are an executive reporting analyst who writes for busy social-media managers. '
                . 'You produce concise, professional summaries and practical tips. '
                . 'Write in plain English. Never use marketing fluff. '
                . 'ALWAYS respond with valid JSON matching the requested shape exactly.';

        $userPrompt = "Generate an executive report summary for {$company}'s social media activity "
                    . "between {$startDate} and {$endDate}.\n\n"
                    . "Report title: {$title}\n\n"
                    . "Stats:\n"
                    . "- Total posts: {$metrics['total_posts']}\n"
                    . "- Published: {$metrics['published']}\n"
                    . "- Scheduled: {$metrics['scheduled']}\n"
                    . "- Failed: {$metrics['failed']}\n"
                    . "- Failure rate: {$metrics['failure_rate']}%\n"
                    . "- Platforms used: " . (empty($topPlatforms) ? 'none' : implode(', ', $topPlatforms)) . "\n"
                    . "- Top topics: " . (empty($topTopics) ? 'none' : implode(', ', $topTopics)) . "\n"
                    . "- Estimated hours saved by automation: {$savings['hours_saved']}\n"
                    . "- Estimated money saved: {$savings['display_dollars']}\n\n"
                    . "Respond with JSON in this exact shape:\n"
                    . "{\n"
                    . '  "summary": "2-3 paragraph executive summary. First paragraph covers what happened (volume, themes, platforms). Second paragraph covers performance (success/failure, trends). Third paragraph covers the cost/time savings and ROI angle.",' . "\n"
                    . '  "highlight": "One single sentence (max 25 words) that would work as an email-preview highlight — the most interesting or impressive takeaway.",' . "\n"
                    . '  "tips": ["5-8 practical short tips (one sentence each) tailored to the data above. Cover posting times, frequency, using the tool effectively, content strategy, and any improvement areas based on failure rate or imbalance between platforms."]' . "\n"
                    . "}\n\n"
                    . "Output ONLY the JSON, no code fences, no commentary.";

        try {
            $ai = new AIService();
            $raw = $ai->chat($system, $userPrompt);
            if ($raw) {
                // Strip possible code fences if the model ignored instructions
                $raw = trim($raw);
                $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
                $raw = preg_replace('/\s*```$/', '', $raw);
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['summary'])) {
                    return [
                        'summary'   => (string) $decoded['summary'],
                        'tips'      => is_array($decoded['tips'] ?? null) ? array_values($decoded['tips']) : [],
                        'highlight' => (string) ($decoded['highlight'] ?? ''),
                    ];
                }
            }
        } catch (\Throwable $e) {
            error_log('ReportGeneratorService AI call failed: ' . $e->getMessage());
        }

        // Fallback — deterministic summary built from the numbers so the
        // report is still useful even if OpenRouter is down.
        return [
            'summary' => "During the period {$startDate} to {$endDate}, {$company} produced "
                       . "{$metrics['total_posts']} posts, with {$metrics['published']} successfully "
                       . "published and {$metrics['scheduled']} scheduled for future release. "
                       . "The estimated time saved by using automated content generation was "
                       . "{$savings['hours_saved']} hours, translating to approximately "
                       . "{$savings['display_dollars']} in social-media-manager labor costs.",
            'tips' => [
                'Post at consistent times — use the schedule feature to queue up content for the week ahead.',
                'Regenerate images that don\'t match your brand from inside the editor.',
                'Use the theme library to keep content variety high and avoid repetition.',
                'Review failed posts in the Reports page and retry them individually.',
                'Keep your phone and website current in Branding — they appear on every generated post.',
            ],
            'highlight' => "{$metrics['published']} posts published, saving roughly {$savings['display_dollars']} in manager time.",
        ];
    }

    /**
     * Generate a short AI-personalized email intro paragraph for the
     * "report ready" notification email. Falls back to a template on
     * any failure.
     */
    public function generateEmailIntro(array $reportData, string $recipientName = ''): string
    {
        $meta     = $reportData['meta'] ?? [];
        $metrics  = $reportData['metrics'] ?? [];
        $savings  = $reportData['savings'] ?? [];
        $highlight = (string) ($reportData['ai_highlight'] ?? '');
        $company  = $meta['company_name'] ?? 'your company';
        $range    = $meta['date_range']['display'] ?? '';

        $system = 'You are a concise professional copywriter for a SaaS tool. '
                . 'Write warm but brief emails. Never use marketing fluff. '
                . 'Output plain text only, no markdown, no code fences.';
        $userPrompt = "Write a single short paragraph (2-3 sentences) opening an email "
                    . "that delivers a social media performance report. The reader is {$recipientName}, "
                    . "and the report is for {$company} covering {$range}. "
                    . "Key stats: {$metrics['total_posts']} posts total, "
                    . "{$metrics['published']} published, "
                    . "{$savings['display_dollars']} in estimated time-savings. "
                    . "Notable highlight: {$highlight}\n\n"
                    . "Tone: warm, confident, not salesy. Output only the paragraph, no subject, no greeting line.";

        try {
            $ai = new AIService();
            $intro = $ai->chat($system, $userPrompt);
            if ($intro) {
                return trim($intro);
            }
        } catch (\Throwable $e) {
            error_log('generateEmailIntro failed: ' . $e->getMessage());
        }

        // Fallback intro
        return "Your {$company} social media report for {$range} is ready. "
             . "{$metrics['published']} posts were published in the period, and your automated "
             . "workflow saved approximately {$savings['display_dollars']} in manager labor. "
             . "Click through to see the full breakdown and helpful tips inside.";
    }
}
