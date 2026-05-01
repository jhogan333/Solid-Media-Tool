<?php
/**
 * report_ready.php — branded HTML email for report delivery.
 *
 * Variables in scope (provided by ReportsController::deliverEmail):
 *   $reportData — full report snapshot array
 *   $intro      — AI-personalized intro paragraph
 *   $viewUrl    — absolute URL to the report view
 */

$meta     = $reportData['meta']    ?? [];
$metrics  = $reportData['metrics'] ?? [];
$savings  = $reportData['savings'] ?? [];
$highlight = trim((string) ($reportData['ai_highlight'] ?? ''));

$primary  = htmlspecialchars($meta['primary_color'] ?? '#6366f1');
$company  = htmlspecialchars($meta['company_name']  ?? 'Your Company');
$title    = htmlspecialchars($meta['title'] ?? 'Social Media Report');
$logoUrl  = htmlspecialchars($meta['logo_url'] ?? '');
$range    = htmlspecialchars($meta['date_range']['display'] ?? '');
$viewUrlEsc = htmlspecialchars($viewUrl);

// Key numbers
$total      = (int) ($metrics['total_posts'] ?? 0);
$published  = (int) ($metrics['published'] ?? 0);
$scheduled  = (int) ($metrics['scheduled'] ?? 0);
$saved      = htmlspecialchars($savings['display_dollars'] ?? '$0.00');
$hours      = (float) ($savings['hours_saved'] ?? 0);

// Primary color darker mix for gradient (can't use color-mix in email)
$hex = ltrim($meta['primary_color'] ?? '#6366f1', '#');
if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
$darkHex = sprintf('#%02x%02x%02x',
    max(0, (int)(hexdec(substr($hex, 0, 2)) * 0.55)),
    max(0, (int)(hexdec(substr($hex, 2, 2)) * 0.55)),
    max(0, (int)(hexdec(substr($hex, 4, 2)) * 0.55))
);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $title ?></title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:40px 0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

    <!-- Gradient header -->
    <tr><td style="background:linear-gradient(165deg,<?= $primary ?> 0%,<?= $darkHex ?> 100%);border-radius:16px 16px 0 0;padding:44px 40px 36px;text-align:center">
        <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" alt="<?= $company ?>" style="max-height:56px;max-width:200px;margin-bottom:22px;filter:drop-shadow(0 4px 10px rgba(0,0,0,0.2))">
        <?php endif; ?>
        <div style="display:inline-block;padding:5px 16px;background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.18);border-radius:100px;font-size:10px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:rgba(255,255,255,0.85);margin-bottom:14px">
            📊 Your Report is Ready
        </div>
        <div style="font-size:28px;font-weight:800;color:#fff;letter-spacing:-0.01em;line-height:1.2;margin-bottom:6px"><?= $title ?></div>
        <div style="font-size:13px;color:rgba(255,255,255,0.65);letter-spacing:0.02em"><?= $range ?></div>
    </td></tr>

    <!-- Accent bar -->
    <tr><td style="height:3px;background:linear-gradient(90deg,<?= $primary ?>,rgba(255,255,255,0.8),transparent)"></td></tr>

    <!-- Body -->
    <tr><td style="background:#ffffff;padding:36px 40px 28px">

        <!-- AI-personalized intro -->
        <div style="font-size:15px;color:#334155;line-height:1.75;margin-bottom:28px">
            <?= nl2br(htmlspecialchars($intro)) ?>
        </div>

        <?php if ($highlight): ?>
        <!-- Highlight callout -->
        <div style="background:#f8fafc;border-left:4px solid <?= $primary ?>;border-radius:0 10px 10px 0;padding:16px 22px;margin-bottom:28px">
            <div style="font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:<?= $primary ?>;margin-bottom:6px">✨ Highlight</div>
            <div style="font-size:14px;color:#1e293b;line-height:1.55;font-weight:500"><?= htmlspecialchars($highlight) ?></div>
        </div>
        <?php endif; ?>

        <!-- Quick stats grid -->
        <div style="font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#94a3b8;margin-bottom:10px">At a glance</div>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px">
            <tr>
                <td width="50%" style="padding:4px">
                    <div style="background:linear-gradient(180deg,<?= $primary ?>,<?= $darkHex ?>);border-radius:12px;padding:18px 16px;text-align:center;color:#fff">
                        <div style="font-size:30px;font-weight:900;letter-spacing:-0.02em;line-height:1"><?= $total ?></div>
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;opacity:0.85;margin-top:4px">Total Posts</div>
                    </div>
                </td>
                <td width="50%" style="padding:4px">
                    <div style="background:linear-gradient(180deg,<?= $primary ?>,<?= $darkHex ?>);border-radius:12px;padding:18px 16px;text-align:center;color:#fff">
                        <div style="font-size:30px;font-weight:900;letter-spacing:-0.02em;line-height:1"><?= $published ?></div>
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;opacity:0.85;margin-top:4px">Published</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="50%" style="padding:4px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px 16px;text-align:center;color:#1e293b">
                        <div style="font-size:30px;font-weight:900;letter-spacing:-0.02em;line-height:1"><?= $scheduled ?></div>
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-top:4px">Scheduled</div>
                    </div>
                </td>
                <td width="50%" style="padding:4px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px 16px;text-align:center;color:#1e293b">
                        <div style="font-size:30px;font-weight:900;letter-spacing:-0.02em;line-height:1;color:<?= $primary ?>"><?= $saved ?></div>
                        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin-top:4px">Est. Saved</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Savings note -->
        <div style="font-size:12px;color:#64748b;line-height:1.6;margin-bottom:30px;text-align:center;font-style:italic">
            Your content automation saved approximately <strong style="color:#1e293b"><?= $hours ?> hours</strong> of social media manager time this period.
        </div>

        <!-- CTA button -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:6px 0 10px">
                <a href="<?= $viewUrlEsc ?>" style="display:inline-block;padding:15px 36px;background:<?= $primary ?>;color:#fff;font-size:14px;font-weight:700;text-decoration:none;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.15);letter-spacing:0.02em">
                    📈 View Full Report
                </a>
            </td></tr>
        </table>

        <div style="margin-top:20px;text-align:center;font-size:11px;color:#94a3b8">
            The full report includes platform breakdowns, post previews, AI executive summary, and helpful tips.<br>
            Save it as a PDF from inside your browser with one click.
        </div>

    </td></tr>

    <!-- Footer -->
    <tr><td style="background:#f8fafc;border-radius:0 0 16px 16px;padding:22px 40px;text-align:center;border-top:1px solid #e2e8f0">
        <div style="font-size:11px;color:#64748b;line-height:1.7">
            Generated by <strong style="color:#1e293b"><?= $company ?></strong> · Social Media Platform
        </div>
        <div style="font-size:10px;color:#cbd5e1;margin-top:6px">
            This is an automated report delivery email. To stop receiving these, update your settings in the app.
        </div>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
