<?php
/**
 * Public shared report page — no auth required. Renders the full branded
 * report plus Chart.js charts for extra visual polish on the web view.
 *
 * Variables in scope:
 *   $report     — row from generated_reports
 *   $reportData — decoded JSON snapshot
 *   $shareToken — the 32-hex token
 */

$meta    = $reportData['meta']    ?? [];
$metrics = $reportData['metrics'] ?? [];
$savings = $reportData['savings'] ?? [];
$posts   = $reportData['posts']   ?? [];
$aiSummary = trim((string) ($reportData['ai_summary'] ?? ''));
$aiTips    = $reportData['ai_tips'] ?? [];
$aiHighlight = trim((string) ($reportData['ai_highlight'] ?? ''));

$primary     = htmlspecialchars($meta['primary_color'] ?? '#6366f1');
$logo        = htmlspecialchars($meta['logo_url'] ?? '');
$darkLogo    = htmlspecialchars($meta['dark_logo_url'] ?? '');
$company     = htmlspecialchars($meta['company_name'] ?? 'Your Company');
$rangeLabel  = htmlspecialchars($meta['date_range']['display'] ?? '');
$title       = htmlspecialchars($meta['title'] ?? 'Social Media Report');

// RGB decomposition for gradient + color-mix fallback
$hex = ltrim($meta['primary_color'] ?? '#6366f1', '#');
if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
$pR = hexdec(substr($hex, 0, 2));
$pG = hexdec(substr($hex, 2, 2));
$pB = hexdec(substr($hex, 4, 2));
$primaryRgb = "{$pR}, {$pG}, {$pB}";

// OG share preview metadata
$ogTitle = $title . ' — ' . $company;
$ogDesc  = $aiHighlight !== ''
    ? $aiHighlight
    : "Social Media Performance Report for {$company} covering {$rangeLabel}. {$metrics['total_posts']} posts, {$savings['display_dollars']} in estimated savings.";
$ogDesc = htmlspecialchars(mb_substr($ogDesc, 0, 280));

// Prepare chart data — JSON-encoded for Chart.js
$platformLabels = array_map('ucfirst', array_keys($metrics['platforms'] ?? []));
$platformCounts = array_values($metrics['platforms'] ?? []);
$topicLabels    = array_slice(array_keys($metrics['topics'] ?? []), 0, 8);
$topicCounts    = array_slice(array_values($metrics['topics'] ?? []), 0, 8);

// Build a per-day timeline of posts within the range for the line chart
$timelineLabels = [];
$timelineCounts = [];
$rangeStart = strtotime($meta['date_range']['start'] ?? 'today');
$rangeEnd   = strtotime($meta['date_range']['end']   ?? 'today');
$bucket = [];
for ($t = $rangeStart; $t <= $rangeEnd; $t += 86400) {
    $bucket[date('Y-m-d', $t)] = 0;
}
foreach ($posts as $p) {
    $d = $p['scheduled_at'] ?? $p['created_at'] ?? null;
    if (!$d) continue;
    $key = date('Y-m-d', strtotime($d));
    if (isset($bucket[$key])) $bucket[$key]++;
}
foreach ($bucket as $date => $count) {
    $timelineLabels[] = date('M j', strtotime($date));
    $timelineCounts[] = $count;
}

// Status breakdown for the doughnut chart
$statusBreakdown = [
    'Published' => (int) ($metrics['published'] ?? 0),
    'Scheduled' => (int) ($metrics['scheduled'] ?? 0),
    'Draft'     => (int) ($metrics['draft'] ?? 0),
    'Failed'    => (int) ($metrics['failed'] ?? 0),
];
$statusBreakdown = array_filter($statusBreakdown); // drop zeros
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $ogTitle ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $ogDesc ?>">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph / Facebook / LinkedIn -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta property="og:description" content="<?= $ogDesc ?>">
    <meta property="og:url" content="<?= BASE_URL ?>/shared/<?= htmlspecialchars($shareToken) ?>">
    <?php if ($logo): ?><meta property="og:image" content="<?= $logo ?>"><?php endif; ?>
    <meta property="og:site_name" content="<?= $company ?>">

    <!-- Twitter card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($ogTitle) ?>">
    <meta name="twitter:description" content="<?= $ogDesc ?>">
    <?php if ($logo): ?><meta name="twitter:image" content="<?= $logo ?>"><?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            line-height: 1.55;
        }
        a { color: var(--primary); text-decoration: none; }
        .report-wrapper { max-width: 1040px; margin: 0 auto; padding: 32px 24px 64px; }
        .report-section {
            background: var(--bg-card);
            border-radius: 20px;
            box-shadow: 0 4px 28px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
            overflow: hidden;
            margin-bottom: 28px;
        }

        /* Public banner — slim info bar at the top */
        .public-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: rgba(var(--primary-rgb), 0.06);
            border: 1px solid rgba(var(--primary-rgb), 0.16);
            border-radius: 12px;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        .public-banner i { color: var(--primary); }
        .public-banner strong { color: var(--text); }
        .public-banner .spacer { flex: 1; }
        .public-banner-btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            background: var(--primary);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .public-banner-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(var(--primary-rgb), 0.3);
        }

        /* Cover */
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
            background: radial-gradient(circle at 15% 10%, rgba(255,255,255,0.22) 0%, transparent 42%),
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
            opacity: 0.5;
            animation: coverRaysSpin 120s linear infinite;
        }
        @keyframes coverRaysSpin {
            from { transform: translateX(-50%) rotate(0deg); }
            to   { transform: translateX(-50%) rotate(360deg); }
        }
        .cover > * { position: relative; z-index: 2; }

        /* Floating white particles */
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

        /* ============== Staggered reveal system ============== */
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
        @keyframes revealFadeUp    { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes revealFadeIn    { from { opacity: 0; } to { opacity: 1; } }
        @keyframes revealScaleIn   { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        @keyframes revealSlideLeft { from { opacity: 0; transform: translateX(-36px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes revealSlideRight{ from { opacity: 0; transform: translateX(36px); } to { opacity: 1; transform: translateX(0); } }

        .cover-logo, .cover-badge, .cover-title, .cover-subtitle, .cover-meta {
            opacity: 0;
            animation: revealFadeUp 1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .cover-logo     { animation-delay: 0.08s; }
        .cover-badge    { animation-delay: 0.25s; }
        .cover-title    { animation-delay: 0.42s; }
        .cover-subtitle { animation-delay: 0.62s; }
        .cover-meta     { animation-delay: 0.82s; }

        .metric-value.count-up-done { animation: metricPulse 0.55s ease-out; }
        @keyframes metricPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.12); }
            100% { transform: scale(1); }
        }
        .savings-value.count-up-done { animation: savingsGlow 1.2s ease-out; }
        @keyframes savingsGlow {
            0%   { text-shadow: 0 0 0 rgba(var(--primary-rgb), 0); }
            40%  { text-shadow: 0 0 22px rgba(var(--primary-rgb), 0.6); }
            100% { text-shadow: 0 0 0 rgba(var(--primary-rgb), 0); }
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
        .cover-logo { display: inline-block; margin-bottom: 28px; }
        .cover-logo img {
            max-height: 84px;
            max-width: 240px;
            object-fit: contain;
            filter: drop-shadow(0 4px 14px rgba(0,0,0,0.25));
        }
        .cover-logo-text { font-size: 28px; font-weight: 800; }
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
        .cover-subtitle { font-size: 16px; font-weight: 500; color: rgba(255,255,255,0.78); margin-bottom: 36px; }
        .cover-meta {
            display: inline-flex;
            gap: 32px;
            padding: 16px 28px;
            background: rgba(0,0,0,0.22);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            backdrop-filter: blur(4px);
            flex-wrap: wrap;
            justify-content: center;
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
        .cover-meta-value { font-size: 14px; font-weight: 600; color: #fff; }

        .section-pad { padding: 40px 44px; }
        .section-title {
            font-size: 22px;
            font-weight: 800;
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
        .section-subtitle { font-size: 13px; color: var(--text-muted); margin-left: 48px; margin-bottom: 24px; }

        /* Metrics grid */
        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .metric-card {
            padding: 20px 16px;
            border-radius: 14px;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: #fff;
            text-align: center;
            box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.22);
        }
        .metric-value { font-size: 32px; font-weight: 900; letter-spacing: -0.02em; line-height: 1; margin-bottom: 4px; }
        .metric-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; }
        .metric-card.metric-alt {
            background: var(--bg);
            color: var(--text);
            box-shadow: none;
            border: 1px solid var(--border);
        }
        .metric-card.metric-alt .metric-label { color: var(--text-muted); }

        /* Savings callout */
        .savings-callout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 28px 32px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08) 0%, rgba(var(--primary-rgb), 0.02) 100%);
            border: 1px solid rgba(var(--primary-rgb), 0.18);
            margin-bottom: 32px;
        }
        .savings-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }
        .savings-value { font-size: 42px; font-weight: 900; letter-spacing: -0.03em; line-height: 1; margin-bottom: 6px; }
        .savings-desc { font-size: 13px; color: var(--text-muted); line-height: 1.55; }
        .savings-math {
            padding: 16px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 13px;
        }
        .savings-math-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed var(--border);
            color: var(--text-muted);
        }
        .savings-math-row:last-child {
            border-bottom: none;
            padding-top: 10px;
            font-weight: 700;
            color: var(--text);
        }

        /* Charts grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .chart-box {
            padding: 22px 24px;
            border-radius: 14px;
            background: var(--bg);
            border: 1px solid var(--border);
        }
        .chart-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }
        .chart-canvas-wrap {
            position: relative;
            height: 240px;
        }
        .charts-grid.full-width {
            grid-template-columns: 1fr;
        }

        /* Post cards */
        .posts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .post-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
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
        .post-card-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .post-card-image-placeholder { color: var(--text-light); font-size: 32px; }
        .post-card-body { padding: 14px 16px 16px; }
        .post-card-pills { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
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
        .post-pill-status-published { background: rgba(34,197,94,0.12); color: #15803d; border-color: rgba(34,197,94,0.3); }
        .post-pill-status-scheduled { background: rgba(59,130,246,0.12); color: #1e40af; border-color: rgba(59,130,246,0.3); }
        .post-pill-status-failed    { background: rgba(239,68,68,0.12); color: #991b1b; border-color: rgba(239,68,68,0.3); }
        .post-pill-status-draft     { background: rgba(148,163,184,0.15); color: #475569; border-color: rgba(148,163,184,0.3); }
        .post-card-title {
            font-size: 14px;
            font-weight: 700;
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

        /* AI summary + tips */
        .ai-summary {
            padding: 28px 32px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.04) 0%, rgba(var(--primary-rgb), 0.01) 100%);
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
        .ai-summary-body { font-size: 14px; line-height: 1.7; white-space: pre-wrap; }
        .ai-summary-body p { margin-bottom: 12px; }

        .tips-section {
            padding: 28px 32px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
        }
        .tip-list { list-style: none; }
        .tip-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            line-height: 1.55;
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

        /* Footer */
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
        .footer-logo { max-height: 36px; max-width: 180px; filter: brightness(0) invert(1); opacity: 0.95; }
        .footer-logo.has-dark { filter: none; opacity: 1; }
        .footer-text { font-size: 11px; color: rgba(255,255,255,0.7); text-align: right; line-height: 1.6; }
        .footer-text strong { color: #fff; }
        @media print { .report-footer { animation: none !important; } }

        /* Mobile */
        @media (max-width: 860px) {
            .section-pad { padding: 28px 22px; }
            .cover { padding: 44px 24px 36px; }
            .cover-title { font-size: 30px; }
            .cover-meta { flex-direction: column; gap: 14px; padding: 14px 20px; }
            .metrics-grid { grid-template-columns: 1fr 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
            .posts-grid { grid-template-columns: 1fr; }
            .savings-callout { grid-template-columns: 1fr; padding: 22px 20px; }
            .savings-value { font-size: 36px; }
            .public-banner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="report-wrapper">

    <!-- Public share banner -->
    <div class="public-banner">
        <i class="fas fa-share-nodes"></i>
        <span>You're viewing a <strong>shared report</strong> from <strong><?= $company ?></strong>. No account required.</span>
        <span class="spacer"></span>
        <button type="button" class="public-banner-btn" onclick="window.print()">
            <i class="fas fa-file-pdf"></i> Save as PDF
        </button>
    </div>

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
                    <div class="cover-meta-label">Total Posts</div>
                    <div class="cover-meta-value"><?= (int) ($metrics['total_posts'] ?? 0) ?></div>
                </div>
                <div class="cover-meta-item">
                    <div class="cover-meta-label">Estimated Savings</div>
                    <div class="cover-meta-value"><?= htmlspecialchars($savings['display_dollars'] ?? '$0.00') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics + Charts -->
    <div class="report-section">
        <div class="section-pad">
            <h2 class="section-title reveal reveal-up">
                <span class="section-title-icon"><i class="fas fa-chart-column"></i></span>
                Performance Overview
            </h2>
            <p class="section-subtitle reveal reveal-fade" style="animation-delay:0.12s">Key numbers and visual breakdowns for this reporting period.</p>

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

            <div class="savings-callout reveal reveal-up" style="animation-delay:0.1s">
                <div>
                    <div class="savings-title"><i class="fas fa-piggy-bank"></i> Estimated Savings</div>
                    <div class="savings-value count-up" data-target="<?= (float) ($savings['dollars_saved'] ?? 0) ?>" data-prefix="<?= htmlspecialchars($savings['currency_symbol'] ?? '$') ?>" data-decimals="2" data-duration="1800"><?= htmlspecialchars($savings['currency_symbol'] ?? '$') ?>0.00</div>
                    <div class="savings-desc">
                        <strong style="color:var(--text)"><?= (float) ($savings['hours_saved'] ?? 0) ?> hours saved</strong> on research, copy writing, image sourcing, branding, and platform uploads.
                    </div>
                </div>
                <div class="savings-math">
                    <div class="savings-math-row"><span>Posts counted</span><span><?= (int) ($savings['post_count'] ?? 0) ?></span></div>
                    <div class="savings-math-row"><span>Minutes per post</span><span><?= (int) ($savings['minutes_per_post'] ?? 30) ?> min</span></div>
                    <div class="savings-math-row"><span>Hourly rate</span><span><?= htmlspecialchars($savings['currency_symbol'] ?? '$') . number_format((float) ($savings['hourly_rate'] ?? 29), 2) ?></span></div>
                    <div class="savings-math-row"><span>Total value</span><span><?= htmlspecialchars($savings['display_dollars'] ?? '$0.00') ?></span></div>
                </div>
            </div>

            <!-- Charts grid — 4 Chart.js charts -->
            <div class="charts-grid">
                <div class="chart-box reveal reveal-left" data-chart="platform" style="animation-delay:0.05s">
                    <div class="chart-title"><i class="fas fa-layer-group"></i> Posts by Platform</div>
                    <div class="chart-canvas-wrap"><canvas id="chartPlatform"></canvas></div>
                </div>
                <div class="chart-box reveal reveal-right" data-chart="topic" style="animation-delay:0.15s">
                    <div class="chart-title"><i class="fas fa-tags"></i> Top Topics</div>
                    <div class="chart-canvas-wrap"><canvas id="chartTopic"></canvas></div>
                </div>
            </div>
            <div class="charts-grid">
                <div class="chart-box reveal reveal-left" data-chart="timeline" style="animation-delay:0.05s">
                    <div class="chart-title"><i class="fas fa-chart-line"></i> Posting Timeline</div>
                    <div class="chart-canvas-wrap"><canvas id="chartTimeline"></canvas></div>
                </div>
                <div class="chart-box reveal reveal-right" data-chart="status" style="animation-delay:0.15s">
                    <div class="chart-title"><i class="fas fa-chart-pie"></i> Status Breakdown</div>
                    <div class="chart-canvas-wrap"><canvas id="chartStatus"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Summary -->
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
                        $para = trim($para); if ($para === '') continue;
                        $paraDelay = 0.15 + ($paraIdx * 0.12);
                    ?>
                        <p class="reveal reveal-fade" style="animation-delay:<?= $paraDelay ?>s"><?= htmlspecialchars($para) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Post cards -->
    <?php if (!empty($posts)): ?>
    <div class="report-section">
        <div class="section-pad">
            <h2 class="section-title reveal reveal-up">
                <span class="section-title-icon"><i class="fas fa-grip"></i></span>
                Posts in This Period
            </h2>
            <p class="section-subtitle reveal reveal-fade" style="animation-delay:0.12s"><?= count($posts) ?> total.</p>
            <div class="posts-grid">
                <?php foreach ($posts as $pcIdx => $post):
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
            <div class="tips-section reveal reveal-up" style="animation-delay:0.05s">
                <ul class="tip-list">
                    <?php foreach ($aiTips as $tipIdx => $tip):
                        $tip = trim((string) $tip); if ($tip === '') continue;
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
                <?= $rangeLabel ?> &middot; <?= (int) ($report['view_count'] ?? 0) ?> view<?= (int) ($report['view_count'] ?? 0) === 1 ? '' : 's' ?>
            </div>
        </div>
    </div>

</div>

<script>
// Chart.js configuration — 4 charts using the brand color palette.
// BRAND/BRAND_RGB go through json_encode with hex-safe flags so even a
// malformed primary_color can't break out of this <script> block.
var BRAND = <?= json_encode($meta['primary_color'] ?? '#6366f1', JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
var BRAND_RGB = <?= json_encode($primaryRgb, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
function brand(alpha) { return 'rgba(' + BRAND_RGB + ', ' + alpha + ')'; }

// Brand palette — rotates through shades of the primary for multi-slice charts
var palette = [
    brand(0.95), brand(0.72), brand(0.52), brand(0.36), brand(0.22), brand(0.14), brand(0.08),
];

Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = 'rgba(0,0,0,0.05)';

<?php
// JSON_HEX_* flags escape <, >, &, ', " as unicode so user-controlled
// strings (topic names, etc.) can't break out of this <script> block.
$chartJsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
var platformData = <?= json_encode(['labels' => $platformLabels, 'counts' => $platformCounts], $chartJsonFlags) ?>;
var topicData    = <?= json_encode(['labels' => $topicLabels, 'counts' => $topicCounts], $chartJsonFlags) ?>;
var timelineData = <?= json_encode(['labels' => $timelineLabels, 'counts' => $timelineCounts], $chartJsonFlags) ?>;
var statusData   = <?= json_encode(['labels' => array_keys($statusBreakdown), 'counts' => array_values($statusBreakdown)], $chartJsonFlags) ?>;

// ============================================================
// Chart builders — called lazily when each chart-box scrolls
// into view, so Chart.js's native entrance animation triggers
// at the right moment instead of all four firing on page load.
// ============================================================
var chartBuilders = {};

chartBuilders.platform = function() {
    if (!platformData.labels.length) return;
    new Chart(document.getElementById('chartPlatform'), {
        type: 'doughnut',
        data: {
            labels: platformData.labels,
            datasets: [{
                data: platformData.counts,
                backgroundColor: palette,
                borderColor: '#fff',
                borderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } },
            }
        }
    });
};

chartBuilders.topic = function() {
    if (!topicData.labels.length) return;
    new Chart(document.getElementById('chartTopic'), {
        type: 'bar',
        data: {
            labels: topicData.labels,
            datasets: [{
                label: 'Posts',
                data: topicData.counts,
                backgroundColor: brand(0.6),
                borderColor: brand(0.95),
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.04)' } },
                y: { grid: { display: false } }
            }
        }
    });
};

chartBuilders.timeline = function() {
    if (!timelineData.labels.length) return;
    new Chart(document.getElementById('chartTimeline'), {
        type: 'line',
        data: {
            labels: timelineData.labels,
            datasets: [{
                label: 'Posts per day',
                data: timelineData.counts,
                borderColor: BRAND,
                backgroundColor: brand(0.12),
                fill: true,
                tension: 0.35,
                pointBackgroundColor: BRAND,
                pointRadius: 3,
                pointHoverRadius: 6,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 16 } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.04)' } }
            }
        }
    });
};

chartBuilders.status = function() {
    if (!statusData.labels.length) return;
    // Map statuses to sensible colors — use brand shades + neutrals so it still feels branded
    var statusColors = {
        'Published': '#16a34a',
        'Scheduled': '#3b82f6',
        'Draft':     '#94a3b8',
        'Failed':    '#ef4444',
    };
    var statusBg = statusData.labels.map(function(l) { return statusColors[l] || brand(0.5); });
    new Chart(document.getElementById('chartStatus'), {
        type: 'pie',
        data: {
            labels: statusData.labels,
            datasets: [{
                data: statusData.counts,
                backgroundColor: statusBg,
                borderColor: '#fff',
                borderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } },
            }
        }
    });
};

// ============================================================
// Staggered reveal + count-up + lazy chart init
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
            var p = Math.min(1, (ts - start) / duration);
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
            if (p < 1) requestAnimationFrame(step);
            else el.classList.add('count-up-done');
        }
        requestAnimationFrame(step);
    }

    function buildChartIfPending(el) {
        var key = el.getAttribute('data-chart');
        if (!key || el.dataset.chartBuilt === '1') return;
        el.dataset.chartBuilt = '1';
        if (typeof chartBuilders[key] === 'function') {
            try { chartBuilders[key](); } catch (e) { console.error(e); }
        }
    }

    if (prefersReduced) {
        document.querySelectorAll('.reveal').forEach(function(el) {
            el.classList.add('revealed');
            el.style.opacity = '1';
        });
        document.querySelectorAll('.count-up').forEach(animateCountUp);
        document.querySelectorAll('[data-chart]').forEach(buildChartIfPending);
        return;
    }

    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            var el = entry.target;
            el.classList.add('revealed');
            if (el.classList.contains('count-up')) animateCountUp(el);
            el.querySelectorAll('.count-up').forEach(animateCountUp);
            if (el.hasAttribute('data-chart')) buildChartIfPending(el);
            io.unobserve(el);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    document.querySelectorAll('.reveal').forEach(function(el) { io.observe(el); });

    // Force-reveal anything already above the fold after first paint
    setTimeout(function() {
        document.querySelectorAll('.reveal:not(.revealed)').forEach(function(el) {
            var rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                el.classList.add('revealed');
                if (el.classList.contains('count-up')) animateCountUp(el);
                el.querySelectorAll('.count-up').forEach(animateCountUp);
                if (el.hasAttribute('data-chart')) buildChartIfPending(el);
                io.unobserve(el);
            }
        });
    }, 200);
})();
</script>

</body>
</html>
