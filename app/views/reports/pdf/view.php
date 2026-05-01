<?php
/**
 * Branded report view — standalone page (no sidebar/topbar) so the print
 * output is clean. Designed with @page rules and careful page-break
 * controls so "Print → Save as PDF" from any modern browser produces a
 * polished PDF deliverable.
 *
 * Data in scope:
 *   $report     — row from generated_reports (id, title, dates, etc.)
 *   $reportData — decoded JSON snapshot (meta, metrics, savings, posts, ai_*)
 *   $viewMode   — 'web' or 'print' (reserved for future)
 */

$meta    = $reportData['meta']    ?? [];
$metrics = $reportData['metrics'] ?? [];
$savings = $reportData['savings'] ?? [];
$posts   = $reportData['posts']   ?? [];
$aiSummary = trim((string) ($reportData['ai_summary'] ?? ''));
$aiTips    = $reportData['ai_tips'] ?? [];

$primary     = htmlspecialchars($meta['primary_color'] ?? '#6366f1');
$logo        = htmlspecialchars($meta['logo_url'] ?? '');
$darkLogo    = htmlspecialchars($meta['dark_logo_url'] ?? '');
$company     = htmlspecialchars($meta['company_name'] ?? 'Your Company');
$rangeLabel  = htmlspecialchars($meta['date_range']['display'] ?? '');
$generatedAt = htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($meta['generated_at'] ?? 'now')));
$generatedBy = htmlspecialchars($meta['generated_by'] ?? '');
$title       = htmlspecialchars($meta['title'] ?? 'Social Media Report');

// RGB decomposition for the gradient
$hex = ltrim($meta['primary_color'] ?? '#6366f1', '#');
if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
$pR = hexdec(substr($hex, 0, 2));
$pG = hexdec(substr($hex, 2, 2));
$pB = hexdec(substr($hex, 4, 2));
$primaryRgb = "{$pR}, {$pG}, {$pB}";

// Pagination for post cards — 4 cards per page (2 rows of 2)
$postsPerPage = 4;
$postPages = array_chunk($posts, $postsPerPage);

// Build QuickChart.io URLs for 2 charts — these render as static PNGs so
// they print perfectly in browser Save-as-PDF. QuickChart is a free service
// that takes a Chart.js config as a URL parameter and returns an image.
$quickChartBase = 'https://quickchart.io/chart';
$chartW = 400; $chartH = 260;
$platformChartUrl = '';
$topicChartUrl    = '';

if (!empty($metrics['platforms'])) {
    $platLabels = array_map(function($k) { return ucfirst($k); }, array_keys($metrics['platforms']));
    $platCounts = array_values($metrics['platforms']);
    $platConfig = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $platLabels,
            'datasets' => [[
                'data' => $platCounts,
                'backgroundColor' => [$primary, 'rgba('.$primaryRgb.', 0.65)', 'rgba('.$primaryRgb.', 0.4)', 'rgba('.$primaryRgb.', 0.22)'],
                'borderColor' => '#ffffff',
                'borderWidth' => 3,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['position' => 'bottom', 'labels' => ['font' => ['size' => 13], 'padding' => 12]],
                'title'  => ['display' => true, 'text' => 'Posts by Platform', 'font' => ['size' => 15, 'weight' => 'bold'], 'color' => '#0f172a'],
            ],
        ],
    ];
    $platformChartUrl = $quickChartBase . '?w=' . $chartW . '&h=' . $chartH
        . '&bkg=white&c=' . rawurlencode(json_encode($platConfig));
}

if (!empty($metrics['topics'])) {
    $topTopicsForChart = array_slice($metrics['topics'], 0, 6, true);
    $topicConfig = [
        'type' => 'horizontalBar',
        'data' => [
            'labels' => array_keys($topTopicsForChart),
            'datasets' => [[
                'label' => 'Posts',
                'data' => array_values($topTopicsForChart),
                'backgroundColor' => 'rgba('.$primaryRgb.', 0.72)',
                'borderColor' => $primary,
                'borderWidth' => 1,
            ]],
        ],
        'options' => [
            'plugins' => [
                'legend' => ['display' => false],
                'title'  => ['display' => true, 'text' => 'Top Topics', 'font' => ['size' => 15, 'weight' => 'bold'], 'color' => '#0f172a'],
            ],
            'scales' => [
                'xAxes' => [['ticks' => ['beginAtZero' => true, 'precision' => 0]]],
            ],
        ],
    ];
    $topicChartUrl = $quickChartBase . '?w=' . $chartW . '&h=' . $chartH
        . '&bkg=white&c=' . rawurlencode(json_encode($topicConfig));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> — <?= $company ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: <?= $primary ?>;
            --primary-rgb: <?= $primaryRgb ?>;
            --primary-dark: color-mix(in srgb, <?= $primary ?> 55%, #000000);
            --text: #0f172a;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --bg: #f8fafc;
            --bg-card: #ffffff;
            --border: #e2e8f0;
        }

        /* ============== Base ============== */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            line-height: 1.55;
        }

        /* ============== Screen layout ============== */
        .report-wrapper {
            max-width: 880px;
            margin: 0 auto;
            padding: 32px 24px 64px;
        }
        .report-section {
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: 0 4px 28px rgba(0,0,0,0.06),
                        0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 28px;
        }

        /* ============== Action bar (screen only) ============== */
        .report-actions {
            position: sticky;
            top: 16px;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            padding: 14px 20px;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }
        .report-actions-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            flex: 1;
            min-width: 200px;
        }
        .report-btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
            text-decoration: none;
        }
        .report-btn-primary {
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 4px 14px rgba(var(--primary-rgb), 0.4);
        }
        .report-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.5);
        }
        .report-btn-ghost {
            background: rgba(var(--primary-rgb), 0.08);
            color: var(--primary);
            border-color: rgba(var(--primary-rgb), 0.2);
        }
        .report-btn-ghost:hover {
            background: rgba(var(--primary-rgb), 0.14);
        }

        /* ============== Cover page ============== */
        .cover {
            position: relative;
            padding: 64px 48px 52px;
            background: linear-gradient(165deg,
                var(--primary) 0%,
                color-mix(in srgb, var(--primary) 70%, #000000) 45%,
                color-mix(in srgb, var(--primary) 45%, #000000) 100%);
            background-size: 200% 200%;
            animation: coverGradientShift 18s ease-in-out infinite;
            color: #fff;
            overflow: hidden;
            text-align: center;
        }
        @keyframes coverGradientShift {
            0%   { background-position: 0% 0%; }
            50%  { background-position: 100% 100%; }
            100% { background-position: 0% 0%; }
        }
        .cover::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 15% 10%, rgba(255,255,255,0.22) 0%, transparent 42%),
                radial-gradient(circle at 85% 110%, rgba(0,0,0,0.35) 0%, transparent 55%);
            pointer-events: none;
            animation: coverGlowDrift 14s ease-in-out infinite alternate;
        }
        @keyframes coverGlowDrift {
            0%   { transform: translate(0, 0) scale(1); opacity: 0.9; }
            50%  { transform: translate(2%, -1%) scale(1.04); opacity: 1; }
            100% { transform: translate(-2%, 1%) scale(1); opacity: 0.85; }
        }
        .cover::after {
            /* Subtle rays pattern — slowly rotating */
            content: '';
            position: absolute;
            top: -20%;
            left: 50%;
            width: 140%;
            height: 140%;
            transform: translateX(-50%);
            transform-origin: 50% 50%;
            background: repeating-conic-gradient(
                from 0deg,
                rgba(255,255,255,0.05) 0deg 8deg,
                transparent 8deg 22deg
            );
            pointer-events: none;
            opacity: 0.55;
            animation: coverRaysSpin 120s linear infinite;
        }
        @keyframes coverRaysSpin {
            from { transform: translateX(-50%) rotate(0deg); }
            to   { transform: translateX(-50%) rotate(360deg); }
        }
        .cover > * { position: relative; z-index: 2; }

        /* ===== Floating white particles ===== */
        .cover-particles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }
        .cover-particle {
            position: absolute;
            bottom: -12px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 0 6px 1px rgba(255, 255, 255, 0.55);
            opacity: 0;
            animation-name: particleFloat;
            animation-timing-function: linear;
            animation-iteration-count: infinite;
        }
        @keyframes particleFloat {
            0%   { transform: translate3d(0, 0, 0) scale(0.6); opacity: 0; }
            10%  { opacity: var(--p-opacity, 0.7); }
            50%  { transform: translate3d(var(--p-drift, 20px), -55%, 0) scale(1); }
            90%  { opacity: var(--p-opacity, 0.7); }
            100% { transform: translate3d(calc(var(--p-drift, 20px) * -0.4), -120%, 0) scale(0.4); opacity: 0; }
        }
        /* Per-particle positioning/sizing/timing — hand-tuned for a natural scatter */
        .cover-particle:nth-child(1)  { left:  5%; width: 4px; height: 4px; animation-duration: 14s; animation-delay:  0s; --p-drift:  18px; --p-opacity: 0.55; }
        .cover-particle:nth-child(2)  { left: 12%; width: 3px; height: 3px; animation-duration: 18s; animation-delay: -4s; --p-drift: -22px; --p-opacity: 0.45; }
        .cover-particle:nth-child(3)  { left: 19%; width: 6px; height: 6px; animation-duration: 12s; animation-delay: -7s; --p-drift:  14px; --p-opacity: 0.75; }
        .cover-particle:nth-child(4)  { left: 26%; width: 2px; height: 2px; animation-duration: 22s; animation-delay: -2s; --p-drift: -12px; --p-opacity: 0.35; }
        .cover-particle:nth-child(5)  { left: 33%; width: 5px; height: 5px; animation-duration: 16s; animation-delay: -9s; --p-drift:  24px; --p-opacity: 0.65; }
        .cover-particle:nth-child(6)  { left: 40%; width: 3px; height: 3px; animation-duration: 20s; animation-delay: -5s; --p-drift: -18px; --p-opacity: 0.45; }
        .cover-particle:nth-child(7)  { left: 47%; width: 7px; height: 7px; animation-duration: 15s; animation-delay:-11s; --p-drift:  10px; --p-opacity: 0.8;  }
        .cover-particle:nth-child(8)  { left: 54%; width: 4px; height: 4px; animation-duration: 19s; animation-delay: -1s; --p-drift: -26px; --p-opacity: 0.55; }
        .cover-particle:nth-child(9)  { left: 61%; width: 2px; height: 2px; animation-duration: 24s; animation-delay: -6s; --p-drift:  16px; --p-opacity: 0.4;  }
        .cover-particle:nth-child(10) { left: 68%; width: 5px; height: 5px; animation-duration: 13s; animation-delay: -3s; --p-drift: -14px; --p-opacity: 0.7;  }
        .cover-particle:nth-child(11) { left: 75%; width: 3px; height: 3px; animation-duration: 17s; animation-delay: -8s; --p-drift:  22px; --p-opacity: 0.5;  }
        .cover-particle:nth-child(12) { left: 82%; width: 6px; height: 6px; animation-duration: 14s; animation-delay:-10s; --p-drift: -10px; --p-opacity: 0.75; }
        .cover-particle:nth-child(13) { left: 89%; width: 4px; height: 4px; animation-duration: 21s; animation-delay: -4s; --p-drift:  18px; --p-opacity: 0.55; }
        .cover-particle:nth-child(14) { left: 94%; width: 3px; height: 3px; animation-duration: 16s; animation-delay: -7s; --p-drift: -20px; --p-opacity: 0.45; }
        .cover-particle:nth-child(15) { left:  9%; width: 2px; height: 2px; animation-duration: 23s; animation-delay:-12s; --p-drift:  14px; --p-opacity: 0.4;  }
        .cover-particle:nth-child(16) { left: 22%; width: 5px; height: 5px; animation-duration: 15s; animation-delay: -2s; --p-drift: -16px; --p-opacity: 0.65; }
        .cover-particle:nth-child(17) { left: 37%; width: 3px; height: 3px; animation-duration: 19s; animation-delay: -8s; --p-drift:  20px; --p-opacity: 0.5;  }
        .cover-particle:nth-child(18) { left: 52%; width: 6px; height: 6px; animation-duration: 12s; animation-delay: -5s; --p-drift: -12px; --p-opacity: 0.75; }
        .cover-particle:nth-child(19) { left: 72%; width: 2px; height: 2px; animation-duration: 25s; animation-delay: -1s; --p-drift:  28px; --p-opacity: 0.35; }
        .cover-particle:nth-child(20) { left: 86%; width: 4px; height: 4px; animation-duration: 18s; animation-delay: -9s; --p-drift: -22px; --p-opacity: 0.55; }

        .cover-logo {
            display: inline-block;
            margin-bottom: 28px;
            max-height: 84px;
        }
        .cover-logo img {
            max-height: 84px;
            max-width: 240px;
            object-fit: contain;
            filter: drop-shadow(0 4px 14px rgba(0,0,0,0.25));
        }
        .cover-logo-text {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .cover-badge {
            display: inline-block;
            padding: 6px 18px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.28);
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 22px;
            backdrop-filter: blur(6px);
        }
        .cover-title {
            font-size: 42px;
            font-weight: 900;
            letter-spacing: -0.02em;
            line-height: 1.1;
            margin-bottom: 14px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .cover-subtitle {
            font-size: 16px;
            font-weight: 500;
            color: rgba(255,255,255,0.75);
            margin-bottom: 36px;
        }
        .cover-meta {
            display: inline-flex;
            gap: 32px;
            padding: 16px 28px;
            background: rgba(0,0,0,0.22);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            backdrop-filter: blur(4px);
        }
        .cover-meta-item { text-align: left; }
        .cover-meta-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.55);
            margin-bottom: 2px;
        }
        .cover-meta-value {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        /* ============== Section headers ============== */
        .section-pad { padding: 40px 44px; }
        .section-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.01em;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .section-title-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.35);
        }
        .section-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-left: 48px;
            margin-bottom: 24px;
        }

        /* ============== Metrics grid ============== */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .metric-card {
            padding: 20px 16px;
            border-radius: 14px;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: #fff;
            text-align: center;
            box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.22);
        }
        .metric-value {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.02em;
            line-height: 1;
            margin-bottom: 4px;
        }
        .metric-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.85;
        }
        .metric-card.metric-alt {
            background: var(--bg);
            color: var(--text);
            box-shadow: none;
            border: 1px solid var(--border);
        }
        .metric-card.metric-alt .metric-label { color: var(--text-muted); }

        /* ============== Savings callout ============== */
        .savings-callout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 28px 32px;
            border-radius: 16px;
            background: linear-gradient(135deg,
                rgba(var(--primary-rgb), 0.08) 0%,
                rgba(var(--primary-rgb), 0.02) 100%);
            border: 1px solid rgba(var(--primary-rgb), 0.18);
            margin-bottom: 28px;
        }
        .savings-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }
        .savings-value {
            font-size: 42px;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: var(--text);
            line-height: 1;
            margin-bottom: 6px;
        }
        .savings-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.55;
        }
        .savings-math {
            padding: 16px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .savings-math-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed var(--border);
        }
        .savings-math-row:last-child {
            border-bottom: none;
            padding-top: 10px;
            font-weight: 700;
            color: var(--text);
        }

        /* ============== Distribution lists ============== */
        .dist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        .dist-box {
            padding: 22px 24px;
            border-radius: 14px;
            background: var(--bg);
            border: 1px solid var(--border);
        }
        .dist-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }
        .dist-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            color: var(--text);
            text-transform: capitalize;
        }
        .dist-bar-wrap {
            flex: 1;
            margin: 0 14px;
            height: 8px;
            background: var(--border);
            border-radius: 100px;
            overflow: hidden;
        }
        .dist-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            border-radius: 100px;
        }
        .dist-count {
            font-weight: 700;
            color: var(--primary);
            min-width: 32px;
            text-align: right;
            font-size: 14px;
        }

        /* ============== Post cards ============== */
        .posts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .post-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .post-card-image {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .post-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .post-card-image-placeholder {
            color: var(--text-light);
            font-size: 32px;
        }
        .post-card-body {
            padding: 14px 16px 16px;
        }
        .post-card-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .post-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary);
            border: 1px solid rgba(var(--primary-rgb), 0.2);
        }
        .post-pill-status-published {
            background: rgba(34,197,94,0.12);
            color: #15803d;
            border-color: rgba(34,197,94,0.3);
        }
        .post-pill-status-scheduled {
            background: rgba(59,130,246,0.12);
            color: #1e40af;
            border-color: rgba(59,130,246,0.3);
        }
        .post-pill-status-failed {
            background: rgba(239,68,68,0.12);
            color: #991b1b;
            border-color: rgba(239,68,68,0.3);
        }
        .post-pill-status-draft {
            background: rgba(148,163,184,0.15);
            color: #475569;
            border-color: rgba(148,163,184,0.3);
        }
        .post-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .post-card-content {
            font-size: 11px;
            line-height: 1.55;
            color: var(--text-muted);
            white-space: pre-line;
            max-height: 9em;
            overflow: hidden;
            position: relative;
        }
        .post-card-content::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2.5em;
            background: linear-gradient(180deg, transparent, var(--bg-card));
            pointer-events: none;
        }
        .post-card-meta {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed var(--border);
            font-size: 10px;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
        }

        /* ============== AI Summary ============== */
        .ai-summary {
            padding: 28px 32px;
            background: linear-gradient(135deg,
                rgba(var(--primary-rgb), 0.04) 0%,
                rgba(var(--primary-rgb), 0.01) 100%);
            border: 1px solid rgba(var(--primary-rgb), 0.14);
            border-radius: 16px;
            margin-bottom: 24px;
        }
        .ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(var(--primary-rgb), 0.12);
            color: var(--primary);
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }
        .ai-summary-body {
            font-size: 14px;
            line-height: 1.7;
            color: var(--text);
            white-space: pre-wrap;
        }
        .ai-summary-body p { margin-bottom: 12px; }
        .ai-summary-body p:last-child { margin-bottom: 0; }

        /* ============== Helpful tips ============== */
        .tips-section {
            padding: 28px 32px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
        }
        .tip-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .tip-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            line-height: 1.55;
            color: var(--text);
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .tip-item:last-child { border-bottom: none; }
        .tip-bulb {
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        /* ============== Footer ============== */
        .report-footer {
            position: relative;
            padding: 32px 40px;
            background: linear-gradient(165deg,
                var(--primary) 0%,
                color-mix(in srgb, var(--primary) 70%, #000000) 45%,
                color-mix(in srgb, var(--primary) 45%, #000000) 100%);
            background-size: 200% 200%;
            animation: coverGradientShift 18s ease-in-out infinite;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            overflow: hidden;
        }
        .report-footer::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 15% 10%, rgba(255,255,255,0.18) 0%, transparent 42%),
                radial-gradient(circle at 85% 110%, rgba(0,0,0,0.35) 0%, transparent 55%);
            pointer-events: none;
        }
        .report-footer > * { position: relative; z-index: 1; }
        .footer-logo {
            max-height: 36px;
            max-width: 180px;
            /* White logo rendering when no dark variant is provided */
            filter: brightness(0) invert(1);
            opacity: 0.95;
        }
        .footer-logo.has-dark { filter: none; opacity: 1; }
        .footer-text {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.7);
            text-align: right;
            line-height: 1.6;
        }
        .footer-text strong { color: #fff; }
        @media print {
            .report-footer { animation: none !important; }
        }

        /* ============== Charts (PNG via QuickChart.io) ============== */
        .chart-pngs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }
        .chart-png-box {
            padding: 16px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            text-align: center;
        }
        .chart-png-box img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        @media (max-width: 720px) {
            .chart-pngs { grid-template-columns: 1fr; }
        }

        /* ============== Print / PDF ============== */
        @page {
            size: letter;
            margin: 14mm 12mm;
        }
        @media print {
            html, body {
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .report-actions, .no-print {
                display: none !important;
            }
            .report-wrapper {
                max-width: none;
                padding: 0;
                margin: 0;
            }
            .report-section {
                border-radius: 0;
                box-shadow: none;
                margin-bottom: 0;
                page-break-after: always;
                break-after: page;
            }
            .report-section:last-child {
                page-break-after: auto;
            }
            .cover {
                min-height: 85vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .cover-title { font-size: 36px; }
            .metric-card { box-shadow: none; }
            .posts-grid { page-break-inside: auto; }
            .post-card {
                page-break-inside: avoid;
                break-inside: avoid;
                box-shadow: none;
            }
            .tip-item { page-break-inside: avoid; break-inside: avoid; }
        }

        /* ============== Mobile ============== */
        @media (max-width: 720px) {
            .section-pad { padding: 28px 22px; }
            .cover { padding: 44px 24px 36px; }
            .cover-title { font-size: 30px; }
            .cover-meta { flex-direction: column; gap: 14px; padding: 14px 20px; }
            .metrics-grid { grid-template-columns: 1fr 1fr; }
            .dist-grid { grid-template-columns: 1fr; }
            .posts-grid { grid-template-columns: 1fr; }
            .savings-callout { grid-template-columns: 1fr; padding: 22px 20px; }
            .savings-value { font-size: 36px; }
            .report-actions { flex-direction: column; align-items: stretch; }
            .report-actions-title { text-align: center; margin-bottom: 8px; }
        }

        /* ============== Staggered reveal system ============== */
        /* Elements with .reveal start hidden and animate in when they
           scroll into view (via IntersectionObserver). Cover elements
           animate on load since they're above the fold. Count-up numbers
           tick from 0 to their target value with an ease-out curve. */
        .reveal { opacity: 0; will-change: opacity, transform; }
        .reveal.revealed {
            animation-duration: 0.85s;
            animation-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
            animation-fill-mode: forwards;
        }
        .reveal-up.revealed    { animation-name: revealFadeUp; }
        .reveal-fade.revealed  { animation-name: revealFadeIn; }
        .reveal-scale.revealed { animation-name: revealScaleIn; }
        .reveal-left.revealed  { animation-name: revealSlideLeft; }
        .reveal-right.revealed { animation-name: revealSlideRight; }

        @keyframes revealFadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes revealFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes revealScaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes revealSlideLeft {
            from { opacity: 0; transform: translateX(-36px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes revealSlideRight {
            from { opacity: 0; transform: translateX(36px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* Cover page — on-load animations, fire immediately */
        .cover-logo,
        .cover-badge,
        .cover-title,
        .cover-subtitle,
        .cover-meta {
            opacity: 0;
            animation: revealFadeUp 1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .cover-logo     { animation-delay: 0.08s; }
        .cover-badge    { animation-delay: 0.25s; }
        .cover-title    { animation-delay: 0.42s; }
        .cover-subtitle { animation-delay: 0.62s; }
        .cover-meta     { animation-delay: 0.82s; }

        /* Subtle pulse on metric values when the count-up finishes */
        .metric-value.count-up-done {
            animation: metricPulse 0.55s ease-out;
        }
        @keyframes metricPulse {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.12); }
            100% { transform: scale(1); }
        }

        /* Glow sweep on the savings value when it finishes counting */
        .savings-value.count-up-done {
            animation: savingsGlow 1.2s ease-out;
        }
        @keyframes savingsGlow {
            0%   { text-shadow: 0 0 0 rgba(var(--primary-rgb), 0); }
            40%  { text-shadow: 0 0 22px rgba(var(--primary-rgb), 0.6); }
            100% { text-shadow: 0 0 0 rgba(var(--primary-rgb), 0); }
        }

        @media print {
            /* Disable all animations for print — everything should be static in PDF */
            .reveal,
            .cover, .cover::before, .cover::after,
            .cover-logo, .cover-badge, .cover-title, .cover-subtitle, .cover-meta {
                opacity: 1 !important;
                animation: none !important;
                transform: none !important;
            }
            .cover-particles { display: none !important; }
        }
        @media (prefers-reduced-motion: reduce) {
            .reveal,
            .cover, .cover::before, .cover::after,
            .cover-logo, .cover-badge, .cover-title, .cover-subtitle, .cover-meta {
                opacity: 1 !important;
                animation: none !important;
                transform: none !important;
            }
            .cover-particles { display: none !important; }
        }
    </style>
</head>
<body>
<div class="report-wrapper">

    <?php
        $isPublicReport = !empty($report['share_token'] ?? '');
        $publicUrlEsc   = $isPublicReport ? htmlspecialchars(BASE_URL . '/shared/' . $report['share_token']) : '';
        $reportIdForJs  = (int) ($report['id'] ?? 0);
        $csrfForToggle  = htmlspecialchars($_SESSION['csrf_token'] ?? '');
    ?>
    <!-- Screen-only action bar -->
    <div class="report-actions no-print">
        <div class="report-actions-title">
            <i class="fas fa-file-lines" style="margin-right:6px;color:var(--primary)"></i>
            <?= $title ?>
        </div>
        <button type="button" class="report-btn report-lock-btn <?= $isPublicReport ? 'is-public' : 'is-private' ?>" id="reportLockBtn" onclick="toggleReportLock()" title="Toggle report visibility">
            <i class="fas <?= $isPublicReport ? 'fa-lock-open' : 'fa-lock' ?>" id="reportLockIcon"></i>
            <span id="reportLockLabel"><?= $isPublicReport ? 'Public' : 'Private' ?></span>
        </button>
        <button type="button" class="report-btn report-btn-primary" onclick="window.print()">
            <i class="fas fa-file-pdf"></i> Save as PDF
        </button>
        <a href="<?= BASE_URL ?>/reporting" class="report-btn report-btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
    </div>

    <!-- Visibility toggle confirmation lightbox —
         zoom-in/zoom-out animation, different icon + copy per state -->
    <div id="reportVisibilityModal" class="report-vis-backdrop no-print" aria-hidden="true">
        <div class="report-vis-card" role="dialog" aria-modal="true">
            <div class="report-vis-icon-wrap" id="reportVisIconWrap">
                <div class="report-vis-icon-halo"></div>
                <i class="fas fa-lock report-vis-icon" id="reportVisIcon"></i>
            </div>
            <h2 class="report-vis-title" id="reportVisTitle">This report is private</h2>
            <p class="report-vis-body" id="reportVisBody">Only people signed into this account can view it. Click Make public to generate a shareable link anyone can open without logging in.</p>

            <!-- Public URL row — only shown when public -->
            <div id="reportVisUrlRow" class="report-vis-url-row" style="display:none">
                <input type="text" id="reportVisUrlInput" readonly>
                <button type="button" class="report-btn report-btn-ghost-sm" onclick="copyVisibilityUrl()"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <div id="reportVisCopied" class="report-vis-copied"><i class="fas fa-check"></i> Link copied</div>

            <div class="report-vis-actions">
                <button type="button" class="report-btn report-btn-ghost-sm" onclick="closeReportVisibility()">Close</button>
                <button type="button" class="report-btn report-btn-primary" id="reportVisActionBtn" onclick="commitVisibilityToggle()">Make public</button>
            </div>
        </div>
    </div>

    <style>
    .report-lock-btn {
        background: rgba(var(--primary-rgb), 0.08);
        color: var(--primary);
        border: 1px solid rgba(var(--primary-rgb), 0.25);
    }
    .report-lock-btn:hover {
        background: rgba(var(--primary-rgb), 0.15);
        border-color: rgba(var(--primary-rgb), 0.4);
        transform: translateY(-1px);
    }
    .report-lock-btn.is-public {
        background: rgba(34, 197, 94, 0.12);
        color: #15803d;
        border-color: rgba(34, 197, 94, 0.32);
    }
    .report-lock-btn.is-public:hover {
        background: rgba(34, 197, 94, 0.2);
    }
    .report-btn-ghost-sm {
        padding: 8px 14px;
        background: rgba(var(--primary-rgb), 0.08);
        color: var(--primary);
        border: 1px solid rgba(var(--primary-rgb), 0.22);
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: inherit;
    }
    .report-btn-ghost-sm:hover { background: rgba(var(--primary-rgb), 0.14); }

    .report-vis-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(5, 5, 15, 0.78);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10001;
        padding: 20px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 1s cubic-bezier(0.22,1,0.36,1),
                    visibility 0s linear 1s;
    }
    .report-vis-backdrop.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transition: opacity 1s cubic-bezier(0.22,1,0.36,1),
                    visibility 0s linear 0s;
    }
    .report-vis-card {
        width: 100%;
        max-width: 460px;
        background: linear-gradient(160deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid var(--border);
        border-radius: 22px;
        padding: 42px 36px 32px;
        text-align: center;
        box-shadow: 0 28px 72px rgba(0,0,0,0.4),
                    0 0 0 1px rgba(255,255,255,0.04);
        transform: scale(0);
        opacity: 0;
        transition: transform 1s cubic-bezier(0.22,1,0.36,1),
                    opacity 1s cubic-bezier(0.22,1,0.36,1);
    }
    .report-vis-backdrop.active .report-vis-card {
        transform: scale(1);
        opacity: 1;
    }

    .report-vis-icon-wrap {
        position: relative;
        width: 108px;
        height: 108px;
        margin: 0 auto 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.08);
        border: 1px solid rgba(var(--primary-rgb), 0.16);
        transition: background 0.45s ease, border-color 0.45s ease;
    }
    .report-vis-icon-wrap.is-public {
        background: rgba(34, 197, 94, 0.1);
        border-color: rgba(34, 197, 94, 0.3);
    }
    .report-vis-icon-halo {
        position: absolute;
        inset: -10px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(var(--primary-rgb), 0.22) 0%, transparent 60%);
        animation: reportVisHalo 2.6s ease-in-out infinite;
    }
    .report-vis-icon-wrap.is-public .report-vis-icon-halo {
        background: radial-gradient(circle, rgba(34, 197, 94, 0.25) 0%, transparent 60%);
    }
    @keyframes reportVisHalo {
        0%, 100% { transform: scale(0.92); opacity: 0.6; }
        50%      { transform: scale(1.05); opacity: 1; }
    }
    .report-vis-icon {
        position: relative;
        z-index: 2;
        font-size: 42px;
        color: var(--primary);
        filter: drop-shadow(0 4px 14px rgba(var(--primary-rgb), 0.35));
        transition: color 0.45s ease;
    }
    .report-vis-icon-wrap.is-public .report-vis-icon {
        color: #15803d;
        filter: drop-shadow(0 4px 14px rgba(34, 197, 94, 0.35));
    }
    .report-vis-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--text);
        margin: 0 0 10px;
        letter-spacing: -0.01em;
    }
    .report-vis-body {
        font-size: 14px;
        color: var(--text-muted);
        line-height: 1.65;
        max-width: 360px;
        margin: 0 auto 22px;
    }
    .report-vis-url-row {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
    }
    .report-vis-url-row input {
        flex: 1;
        padding: 10px 14px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 12px;
        color: var(--text);
        outline: none;
    }
    .report-vis-url-row input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15); }
    .report-vis-copied {
        display: none;
        font-size: 12px;
        color: #16a34a;
        margin-bottom: 14px;
        font-weight: 600;
    }
    .report-vis-copied.show { display: block; }
    .report-vis-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    </style>

    <!-- Cover -->
    <div class="report-section">
        <div class="cover">
            <div class="cover-particles" aria-hidden="true">
                <?php for ($i = 0; $i < 20; $i++): ?><span class="cover-particle"></span><?php endfor; ?>
            </div>
            <?php if ($logo): ?>
                <div class="cover-logo"><img src="<?= $logo ?>" alt="<?= $company ?>"></div>
            <?php else: ?>
                <div class="cover-logo"><div class="cover-logo-text"><?= $company ?></div></div>
            <?php endif; ?>
            <div class="cover-badge">Social Media Performance Report</div>
            <h1 class="cover-title"><?= $title ?></h1>
            <div class="cover-subtitle"><?= $company ?></div>
            <div class="cover-meta">
                <div class="cover-meta-item">
                    <div class="cover-meta-label">Reporting Period</div>
                    <div class="cover-meta-value"><?= $rangeLabel ?></div>
                </div>
                <div class="cover-meta-item">
                    <div class="cover-meta-label">Generated</div>
                    <div class="cover-meta-value"><?= $generatedAt ?></div>
                </div>
                <?php if ($generatedBy): ?>
                <div class="cover-meta-item">
                    <div class="cover-meta-label">Prepared by</div>
                    <div class="cover-meta-value"><?= $generatedBy ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- High-level metrics -->
    <div class="report-section">
        <div class="section-pad">
            <h2 class="section-title reveal reveal-up">
                <span class="section-title-icon"><i class="fas fa-chart-column"></i></span>
                Key Metrics
            </h2>
            <p class="section-subtitle reveal reveal-fade" style="animation-delay:0.12s">A snapshot of everything that moved through the content pipeline during this period.</p>

            <div class="metrics-grid">
                <div class="metric-card reveal reveal-up" style="animation-delay:0.05s">
                    <div class="metric-value count-up" data-target="<?= (int) ($metrics['total_posts'] ?? 0) ?>" data-decimals="0">0</div>
                    <div class="metric-label">Total Posts</div>
                </div>
                <div class="metric-card reveal reveal-up" style="animation-delay:0.13s">
                    <div class="metric-value count-up" data-target="<?= (int) ($metrics['published'] ?? 0) ?>" data-decimals="0">0</div>
                    <div class="metric-label">Published</div>
                </div>
                <div class="metric-card reveal reveal-up" style="animation-delay:0.21s">
                    <div class="metric-value count-up" data-target="<?= (int) ($metrics['scheduled'] ?? 0) ?>" data-decimals="0">0</div>
                    <div class="metric-label">Scheduled</div>
                </div>
                <div class="metric-card metric-alt reveal reveal-up" style="animation-delay:0.29s">
                    <div class="metric-value"><span class="count-up" data-target="<?= (float) ($metrics['failure_rate'] ?? 0) ?>" data-decimals="1" data-suffix="%">0%</span></div>
                    <div class="metric-label">Failure Rate</div>
                </div>
            </div>

            <!-- Cost savings callout -->
            <div class="savings-callout reveal reveal-up" style="animation-delay:0.1s">
                <div>
                    <div class="savings-title"><i class="fas fa-piggy-bank"></i> Estimated Savings</div>
                    <div class="savings-value count-up" data-target="<?= (float) ($savings['dollars_saved'] ?? 0) ?>" data-prefix="<?= htmlspecialchars($savings['currency_symbol'] ?? '$') ?>" data-decimals="2" data-duration="1800"><?= htmlspecialchars($savings['currency_symbol'] ?? '$') ?>0.00</div>
                    <div class="savings-desc">
                        Based on <?= (int) ($savings['post_count'] ?? 0) ?> published or scheduled post<?= (int) ($savings['post_count'] ?? 0) === 1 ? '' : 's' ?> &times; <?= (int) ($savings['minutes_per_post'] ?? 30) ?> min each &times; <?= htmlspecialchars($savings['currency_symbol'] ?? '$') . number_format((float) ($savings['hourly_rate'] ?? 29), 2) ?>/hr manager rate.
                        <br><strong style="color:var(--text)"><?= (float) ($savings['hours_saved'] ?? 0) ?> hours saved</strong> on research, copy writing, image sourcing, branding, and platform uploads.
                    </div>
                </div>
                <div class="savings-math">
                    <div class="savings-math-row">
                        <span>Posts counted</span>
                        <span><?= (int) ($savings['post_count'] ?? 0) ?></span>
                    </div>
                    <div class="savings-math-row">
                        <span>Minutes per post</span>
                        <span><?= (int) ($savings['minutes_per_post'] ?? 30) ?> min</span>
                    </div>
                    <div class="savings-math-row">
                        <span>Hourly rate</span>
                        <span><?= htmlspecialchars($savings['currency_symbol'] ?? '$') . number_format((float) ($savings['hourly_rate'] ?? 29), 2) ?></span>
                    </div>
                    <div class="savings-math-row">
                        <span>Total value</span>
                        <span><?= htmlspecialchars($savings['display_dollars'] ?? '$0.00') ?></span>
                    </div>
                </div>
            </div>

            <!-- QuickChart.io PNG charts for Save-as-PDF fidelity -->
            <?php if ($platformChartUrl || $topicChartUrl): ?>
            <div class="chart-pngs">
                <?php if ($platformChartUrl): ?>
                    <div class="chart-png-box reveal reveal-left" style="animation-delay:0.05s">
                        <img src="<?= $platformChartUrl ?>" alt="Posts by Platform" loading="lazy">
                    </div>
                <?php endif; ?>
                <?php if ($topicChartUrl): ?>
                    <div class="chart-png-box reveal reveal-right" style="animation-delay:0.15s">
                        <img src="<?= $topicChartUrl ?>" alt="Top Topics" loading="lazy">
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Platform + Topic breakdowns (also keep the CSS bars for redundancy) -->
            <?php if (!empty($metrics['platforms']) || !empty($metrics['topics'])): ?>
            <div class="dist-grid" style="margin-top:24px">
                <?php if (!empty($metrics['platforms'])):
                    $maxPlat = max($metrics['platforms']);
                ?>
                <div class="dist-box">
                    <div class="dist-title"><i class="fas fa-layer-group"></i> Platforms</div>
                    <?php foreach ($metrics['platforms'] as $plat => $count):
                        $pct = $maxPlat > 0 ? round(($count / $maxPlat) * 100) : 0;
                    ?>
                    <div class="dist-row">
                        <span style="min-width:72px"><?= htmlspecialchars($plat) ?></span>
                        <div class="dist-bar-wrap"><div class="dist-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        <span class="dist-count"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($metrics['topics'])):
                    $topTopics = array_slice($metrics['topics'], 0, 6, true);
                    $maxTopic = max($topTopics);
                ?>
                <div class="dist-box">
                    <div class="dist-title"><i class="fas fa-tags"></i> Top Topics</div>
                    <?php foreach ($topTopics as $topic => $count):
                        $pct = $maxTopic > 0 ? round(($count / $maxTopic) * 100) : 0;
                    ?>
                    <div class="dist-row">
                        <span style="min-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($topic) ?></span>
                        <div class="dist-bar-wrap"><div class="dist-bar-fill" style="width:<?= $pct ?>%"></div></div>
                        <span class="dist-count"><?= $count ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Post cards -->
    <?php if (!empty($posts)): ?>
    <div class="report-section">
        <div class="section-pad">
            <h2 class="section-title reveal reveal-up">
                <span class="section-title-icon"><i class="fas fa-grip"></i></span>
                Posts in This Period
            </h2>
            <p class="section-subtitle reveal reveal-fade" style="animation-delay:0.12s"><?= count($posts) ?> total. Click any post in the main app to edit or re-run it.</p>

            <?php foreach ($postPages as $pageIdx => $pageOfPosts): ?>
                <div class="posts-grid" <?= $pageIdx > 0 ? 'style="margin-top:20px"' : '' ?>>
                    <?php foreach ($pageOfPosts as $pcIdx => $post):
                        $statusClass = 'post-pill-status-' . htmlspecialchars($post['status'] ?? 'draft');
                        $dateLabel = '';
                        if (!empty($post['scheduled_at'])) {
                            $dateLabel = date('M j, Y g:ia', strtotime($post['scheduled_at']));
                        } elseif (!empty($post['created_at'])) {
                            $dateLabel = date('M j, Y', strtotime($post['created_at']));
                        }
                        $pcDelay = ($pcIdx % 4) * 0.08;
                    ?>
                    <div class="post-card reveal reveal-up" style="animation-delay:<?= $pcDelay ?>s">
                        <div class="post-card-image">
                            <?php if (!empty($post['image_url'])): ?>
                                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="">
                            <?php else: ?>
                                <i class="fas fa-image post-card-image-placeholder"></i>
                            <?php endif; ?>
                        </div>
                        <div class="post-card-body">
                            <div class="post-card-pills">
                                <?php foreach (($post['platforms'] ?? []) as $plat): ?>
                                    <span class="post-pill"><?= htmlspecialchars(ucfirst($plat)) ?></span>
                                <?php endforeach; ?>
                                <?php if (!empty($post['post_type'])): ?>
                                    <span class="post-pill"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $post['post_type']))) ?></span>
                                <?php endif; ?>
                                <span class="post-pill <?= $statusClass ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $post['status'] ?? 'draft'))) ?></span>
                            </div>
                            <div class="post-card-title"><?= htmlspecialchars($post['title'] ?? 'Untitled') ?></div>
                            <div class="post-card-content"><?= htmlspecialchars($post['content'] ?? '') ?></div>
                            <div class="post-card-meta">
                                <span><?= htmlspecialchars($post['topic'] ?? '') ?></span>
                                <span><?= htmlspecialchars($dateLabel) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- AI Executive Summary -->
    <?php if ($aiSummary !== ''): ?>
    <div class="report-section">
        <div class="section-pad">
            <h2 class="section-title reveal reveal-up">
                <span class="section-title-icon"><i class="fas fa-wand-magic-sparkles"></i></span>
                Executive Summary
            </h2>
            <p class="section-subtitle reveal reveal-fade" style="animation-delay:0.12s">An AI-generated overview of this reporting period.</p>

            <div class="ai-summary reveal reveal-scale" style="animation-delay:0.05s">
                <div class="ai-badge"><i class="fas fa-sparkles"></i> AI Generated</div>
                <div class="ai-summary-body">
                    <?php foreach (preg_split('/\n\s*\n/', $aiSummary) as $paraIdx => $para):
                        $para = trim($para);
                        if ($para === '') continue;
                        $paraDelay = 0.15 + ($paraIdx * 0.12);
                    ?>
                        <p class="reveal reveal-fade" style="animation-delay:<?= $paraDelay ?>s"><?= htmlspecialchars($para) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Helpful tips -->
    <?php if (!empty($aiTips) && is_array($aiTips)): ?>
    <div class="report-section">
        <div class="section-pad">
            <h2 class="section-title reveal reveal-up">
                <span class="section-title-icon"><i class="fas fa-lightbulb"></i></span>
                Helpful Tips
            </h2>
            <p class="section-subtitle reveal reveal-fade" style="animation-delay:0.12s">Practical guidance for getting the most out of your content workflow.</p>

            <div class="tips-section reveal reveal-up" style="animation-delay:0.05s">
                <ul class="tip-list">
                    <?php foreach ($aiTips as $tipIdx => $tip):
                        $tip = trim((string) $tip);
                        if ($tip === '') continue;
                        $tipDelay = 0.15 + ($tipIdx * 0.09);
                    ?>
                    <li class="tip-item reveal reveal-left" style="animation-delay:<?= $tipDelay ?>s">
                        <span class="tip-bulb"><i class="fas fa-lightbulb"></i></span>
                        <span><?= htmlspecialchars($tip) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="report-section">
        <div class="report-footer">
            <?php if ($darkLogo || $logo): ?>
                <img src="<?= $darkLogo ?: $logo ?>" alt="<?= $company ?>" class="footer-logo <?= $darkLogo ? 'has-dark' : '' ?>">
            <?php else: ?>
                <strong><?= $company ?></strong>
            <?php endif; ?>
            <div class="footer-text">
                <strong>Social Media Performance Report</strong><br>
                <?= $rangeLabel ?> &middot; Generated <?= $generatedAt ?>
            </div>
        </div>
    </div>

</div>

<script>
// ---- Report visibility toggle + confirmation lightbox ----
var REPORT_ID = <?= $reportIdForJs ?>;
var REPORT_CSRF = <?= json_encode($_SESSION['csrf_token'] ?? '', JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
var BASE_URL = <?= json_encode(BASE_URL, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
var isReportPublic = <?= $isPublicReport ? 'true' : 'false' ?>;
var currentPublicUrl = <?= $isPublicReport ? json_encode(BASE_URL . '/shared/' . ($report['share_token'] ?? ''), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) : "''" ?>;

function openReportVisibility() {
    var modal = document.getElementById('reportVisibilityModal');
    if (!modal) return;
    refreshVisibilityModalContent();
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}
function closeReportVisibility() {
    var modal = document.getElementById('reportVisibilityModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    // Hide the "copied" hint on close so it's fresh next open
    document.getElementById('reportVisCopied').classList.remove('show');
}

// Clicked lock icon in the toolbar — just open the modal so the user sees
// the current state and can choose to toggle.
function toggleReportLock() {
    openReportVisibility();
}

// Render the modal body based on the current isReportPublic state.
function refreshVisibilityModalContent() {
    var wrap   = document.getElementById('reportVisIconWrap');
    var icon   = document.getElementById('reportVisIcon');
    var title  = document.getElementById('reportVisTitle');
    var body   = document.getElementById('reportVisBody');
    var urlRow = document.getElementById('reportVisUrlRow');
    var urlInput = document.getElementById('reportVisUrlInput');
    var actionBtn = document.getElementById('reportVisActionBtn');

    if (isReportPublic) {
        wrap.classList.add('is-public');
        icon.className = 'fas fa-lock-open report-vis-icon';
        title.textContent = 'This report is public';
        body.textContent = 'Anyone with the link below can view the full report without signing in. The link stops working the moment you make this report private again.';
        urlRow.style.display = 'flex';
        urlInput.value = currentPublicUrl;
        actionBtn.innerHTML = '<i class="fas fa-lock"></i> Make private';
        actionBtn.className = 'report-btn report-btn-ghost-sm';
        actionBtn.style.background = 'rgba(239,68,68,0.1)';
        actionBtn.style.color = '#b91c1c';
        actionBtn.style.borderColor = 'rgba(239,68,68,0.25)';
    } else {
        wrap.classList.remove('is-public');
        icon.className = 'fas fa-lock report-vis-icon';
        title.textContent = 'This report is private';
        body.textContent = 'Only people signed into this account can view it. Click Make public to generate a shareable link anyone can open without logging in.';
        urlRow.style.display = 'none';
        actionBtn.innerHTML = '<i class="fas fa-globe"></i> Make public';
        actionBtn.className = 'report-btn report-btn-primary';
        actionBtn.style.background = '';
        actionBtn.style.color = '';
        actionBtn.style.borderColor = '';
    }
}

// Called when user clicks "Make public" or "Make private" in the modal.
// Hits the existing /reports/share or /reports/unshare endpoint, updates
// local state, and re-renders the modal content so the user sees the
// confirmation before manually closing.
function commitVisibilityToggle() {
    var endpoint = isReportPublic ? '/reports/unshare/' : '/reports/share/';
    var btn = document.getElementById('reportVisActionBtn');
    var origHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Working...';
    btn.disabled = true;

    fetch(BASE_URL + endpoint + REPORT_ID, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: REPORT_CSRF})
    }).then(function(r) { return r.json(); }).then(function(d) {
        btn.disabled = false;
        if (d.error) {
            btn.innerHTML = origHtml;
            alert(d.error);
            return;
        }
        // Flip the state
        isReportPublic = !isReportPublic;
        if (isReportPublic) {
            currentPublicUrl = d.public_url || (BASE_URL + '/shared/' + (d.token || ''));
        } else {
            currentPublicUrl = '';
        }
        refreshVisibilityModalContent();
        // Update the toolbar lock button too
        var lockBtn = document.getElementById('reportLockBtn');
        var lockIcon = document.getElementById('reportLockIcon');
        var lockLabel = document.getElementById('reportLockLabel');
        if (isReportPublic) {
            lockBtn.classList.remove('is-private');
            lockBtn.classList.add('is-public');
            lockIcon.className = 'fas fa-lock-open';
            lockLabel.textContent = 'Public';
        } else {
            lockBtn.classList.remove('is-public');
            lockBtn.classList.add('is-private');
            lockIcon.className = 'fas fa-lock';
            lockLabel.textContent = 'Private';
        }
    }).catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        alert('Network error: ' + err.message);
    });
}

function copyVisibilityUrl() {
    var input = document.getElementById('reportVisUrlInput');
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(input.value).then(function() {
            document.getElementById('reportVisCopied').classList.add('show');
        });
    } catch (e) {
        try { document.execCommand('copy'); } catch (e2) {}
        document.getElementById('reportVisCopied').classList.add('show');
    }
}

// Close modal on backdrop click or Escape
document.getElementById('reportVisibilityModal').addEventListener('click', function(e) {
    if (e.target === this) closeReportVisibility();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var m = document.getElementById('reportVisibilityModal');
        if (m && m.classList.contains('active')) closeReportVisibility();
    }
});

// ============================================================
// Staggered reveal system: IntersectionObserver + count-up
// ============================================================
(function() {
    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

    function animateCountUp(el) {
        if (el.dataset.countUpDone === '1') return;
        el.dataset.countUpDone = '1';
        var target   = parseFloat(el.dataset.target || '0');
        var decimals = parseInt(el.dataset.decimals || '0', 10);
        var prefix   = el.dataset.prefix || '';
        var suffix   = el.dataset.suffix || '';
        var duration = parseInt(el.dataset.duration || '1400', 10);

        if (prefersReduced) {
            el.textContent = prefix + target.toFixed(decimals) + suffix;
            el.classList.add('count-up-done');
            return;
        }

        var start = null;
        function step(ts) {
            if (start === null) start = ts;
            var elapsed = ts - start;
            var p = Math.min(1, elapsed / duration);
            var val = target * easeOutCubic(p);
            var formatted = val.toFixed(decimals);
            if (decimals === 0 && Math.abs(target) >= 1000) {
                formatted = parseInt(formatted, 10).toLocaleString();
            } else if (decimals > 0 && Math.abs(target) >= 1000) {
                var parts = formatted.split('.');
                parts[0] = parseInt(parts[0], 10).toLocaleString();
                formatted = parts.join('.');
            }
            el.textContent = prefix + formatted + suffix;
            if (p < 1) {
                requestAnimationFrame(step);
            } else {
                el.classList.add('count-up-done');
            }
        }
        requestAnimationFrame(step);
    }

    if (prefersReduced) {
        document.querySelectorAll('.reveal').forEach(function(el) {
            el.classList.add('revealed');
            el.style.opacity = '1';
        });
        document.querySelectorAll('.count-up').forEach(animateCountUp);
        return;
    }

    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            var el = entry.target;
            el.classList.add('revealed');
            if (el.classList.contains('count-up')) animateCountUp(el);
            el.querySelectorAll('.count-up').forEach(animateCountUp);
            io.unobserve(el);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    document.querySelectorAll('.reveal').forEach(function(el) { io.observe(el); });

    // Fallback: force-reveal anything already above the fold after first paint
    setTimeout(function() {
        document.querySelectorAll('.reveal:not(.revealed)').forEach(function(el) {
            var rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                el.classList.add('revealed');
                if (el.classList.contains('count-up')) animateCountUp(el);
                el.querySelectorAll('.count-up').forEach(animateCountUp);
                io.unobserve(el);
            }
        });
    }, 200);
})();
</script>

</body>
</html>
