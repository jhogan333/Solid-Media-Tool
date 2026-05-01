<?php

/**
 * ReportSettingsService — central place for cost savings math.
 *
 * Defaults assume a social media manager earning $29/hour (roughly $60k/yr
 * in the US) spending 30 minutes per polished branded post (research +
 * copy writing + image sourcing/branding + upload to each platform).
 * Both values are editable per-client in the Report Settings form so any
 * agency can tune the math for their specific market and workflow.
 */
class ReportSettingsService
{
    private ReportSettings $model;

    public function __construct()
    {
        $this->model = new ReportSettings();
    }

    public function get(int $clientId): array
    {
        return $this->model->getByClient($clientId);
    }

    public function save(int $clientId, array $data): array
    {
        $this->model->upsertByClient($clientId, $data);
        return $this->model->getByClient($clientId);
    }

    /**
     * Calculate savings for a given post count. Pure function — accepts
     * either a clientId (loads settings) or an explicit settings array
     * (for the Reports page where the full row is already in scope).
     *
     * Returns an array with:
     *   - minutes_per_post
     *   - hourly_rate
     *   - currency_symbol
     *   - post_count
     *   - total_minutes
     *   - hours_saved (float, rounded to 1 decimal)
     *   - dollars_saved (float, rounded to 2 decimals)
     *   - per_post_value (float)
     *   - display_dollars (string, formatted with commas and symbol)
     */
    public function calculate(int $postCount, $clientIdOrSettings = null): array
    {
        // Defensive: no negative post counts (callers should never pass them,
        // but aggregates from surprising DB states shouldn't produce negative $).
        $postCount = max(0, $postCount);

        if (is_array($clientIdOrSettings)) {
            $settings = $clientIdOrSettings;
        } else {
            $cid = $clientIdOrSettings ?? ($GLOBALS['client_id'] ?? 1);
            $settings = $this->model->getByClient((int) $cid);
        }

        $minutes = (int) ($settings['minutes_per_post'] ?? 30);
        $rate    = (float) ($settings['hourly_rate'] ?? 29.00);
        $symbol  = (string) ($settings['currency_symbol'] ?? '$');

        $totalMinutes = $postCount * $minutes;
        $hoursSaved   = $totalMinutes / 60;
        $dollarsSaved = $hoursSaved * $rate;
        $perPostValue = ($minutes / 60) * $rate;

        return [
            'minutes_per_post' => $minutes,
            'hourly_rate'      => $rate,
            'currency_symbol'  => $symbol,
            'post_count'       => $postCount,
            'total_minutes'    => $totalMinutes,
            'hours_saved'      => round($hoursSaved, 1),
            'dollars_saved'    => round($dollarsSaved, 2),
            'per_post_value'   => round($perPostValue, 2),
            'display_dollars'  => $symbol . number_format($dollarsSaved, 2),
            'display_per_post' => $symbol . number_format($perPostValue, 2),
        ];
    }
}
