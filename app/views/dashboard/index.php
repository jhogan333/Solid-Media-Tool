<?php
$firstName = $_SESSION['first_name'] ?? '';
$justLoggedIn = !empty($_SESSION['just_logged_in']);
if ($justLoggedIn) { unset($_SESSION['just_logged_in']); }
$brandingService = new BrandingService();
$brand = $brandingService->get($GLOBALS['client_id']);
$companyName = $brand['company_name'] ?? 'your company';
$_primaryColor = $brand['primary_color'] ?? '#6366f1';

// Fetch weekly trend data for stat card tooltips
$_weekTrends = [];
try {
    $db = Database::connect();
    $trendStmt = $db->prepare(
        "SELECT status, COUNT(*) AS cnt FROM posts
         WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY status"
    );
    $trendStmt->execute([':cid' => $GLOBALS['client_id']]);
    while ($row = $trendStmt->fetch()) {
        $_weekTrends[$row['status']] = (int) $row['cnt'];
    }
    // Total new posts this week (all statuses)
    $totalStmt = $db->prepare(
        "SELECT COUNT(*) AS cnt FROM posts
         WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    $totalStmt->execute([':cid' => $GLOBALS['client_id']]);
    $_weekTrends['_total'] = (int) $totalStmt->fetch()['cnt'];
} catch (Exception $e) {
    $_weekTrends = [];
}

/**
 * Helper: returns tooltip text for a given stat key.
 */
if (!function_exists('_dashTrendText')):
function _dashTrendText(string $key, array $trends): string {
    if ($key === 'total') {
        $n = $trends['_total'] ?? 0;
    } else {
        $n = $trends[$key] ?? 0;
    }
    if ($n > 0) {
        return "&#8593; {$n} new this week";
    }
    return 'No change this week';
}
endif;

// Time-based greeting
$hour = (int) date('G');
if ($hour < 12) {
    $timeGreeting = 'Good morning';
} elseif ($hour < 17) {
    $timeGreeting = 'Good afternoon';
} else {
    $timeGreeting = 'Good evening';
}
$greeting = $firstName ? "{$timeGreeting}, " . htmlspecialchars($firstName) : $timeGreeting;
$companyHtml = htmlspecialchars($companyName);
?>

<?php if ($justLoggedIn): ?>
<!-- Login entrance overlay — gradient dissolve into dashboard -->
<style>
.dash-entrance-overlay {
    position: fixed;
    inset: 0;
    z-index: 9998;
    background: linear-gradient(165deg, <?= htmlspecialchars($_primaryColor) ?> 0%, color-mix(in srgb, <?= htmlspecialchars($_primaryColor) ?> 40%, #0a0a0a) 60%, #0a0a0a 100%);
    animation: dashEntranceFade 0.6s ease 0.15s forwards;
    pointer-events: none;
}
@keyframes dashEntranceFade {
    0% { opacity: 1; }
    100% { opacity: 0; }
}

/* Enhanced cascading entrance for dashboard elements */
.login-entrance .main-content > * {
    opacity: 0;
    transform: translateY(18px);
    animation: dashElReveal 0.55s cubic-bezier(0.23, 1, 0.32, 1) forwards;
}
.login-entrance .main-content > *:nth-child(1) { animation-delay: 0.25s; } /* overlay itself (hidden) */
.login-entrance .main-content > *:nth-child(2) { animation-delay: 0.3s; }  /* greeting */
.login-entrance .main-content > *:nth-child(3) { animation-delay: 0.4s; }  /* stat style */
.login-entrance .main-content > *:nth-child(4) { animation-delay: 0.45s; } /* stats grid */
.login-entrance .main-content > *:nth-child(5) { animation-delay: 0.55s; }
.login-entrance .main-content > *:nth-child(6) { animation-delay: 0.65s; }
.login-entrance .main-content > *:nth-child(7) { animation-delay: 0.75s; }
.login-entrance .main-content > *:nth-child(8) { animation-delay: 0.85s; }
.login-entrance .main-content > *:nth-child(9) { animation-delay: 0.95s; }
.login-entrance .main-content > *:nth-child(10) { animation-delay: 1.05s; }

@keyframes dashElReveal {
    to { opacity: 1; transform: translateY(0); }
}

/* Make stat cards pop with extra flair on login entrance */
.login-entrance .stats-grid .stat-card {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
    animation: dashStatPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
.login-entrance .stats-grid .stat-card:nth-child(1) { animation-delay: 0.45s; }
.login-entrance .stats-grid .stat-card:nth-child(2) { animation-delay: 0.55s; }
.login-entrance .stats-grid .stat-card:nth-child(3) { animation-delay: 0.65s; }
.login-entrance .stats-grid .stat-card:nth-child(4) { animation-delay: 0.75s; }
.login-entrance .stats-grid .stat-card:nth-child(5) { animation-delay: 0.85s; }

@keyframes dashStatPop {
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Quick action cards entrance */
.login-entrance .quick-action-card {
    opacity: 0;
    transform: translateX(-12px);
    animation: dashActionSlide 0.5s cubic-bezier(0.23, 1, 0.32, 1) forwards;
}
.login-entrance .quick-action-card:nth-child(1) { animation-delay: 0.8s; }
.login-entrance .quick-action-card:nth-child(2) { animation-delay: 0.9s; }
.login-entrance .quick-action-card:nth-child(3) { animation-delay: 1.0s; }

@keyframes dashActionSlide {
    to { opacity: 1; transform: translateX(0); }
}
</style>

<div class="dash-entrance-overlay" id="dashEntranceOverlay"></div>

<script>
// Add entrance class to body so CSS selectors work, remove after animations complete
document.body.classList.add('login-entrance');
setTimeout(function() {
    var overlay = document.getElementById('dashEntranceOverlay');
    if (overlay) overlay.remove();
    document.body.classList.remove('login-entrance');
}, 2000);
</script>
<?php endif; ?>

<!-- Greeting -->
<div style="margin-bottom:28px">
    <h2 style="font-size:22px;font-weight:700;color:var(--text);margin-bottom:4px"><?= $greeting ?>.</h2>
    <p style="font-size:14px;color:var(--text-muted);margin:0">Here's an overview of your content for <?= $companyHtml ?>.</p>
</div>

<!-- Stats Grid -->
<style>
.stat-card { position: relative; }
.stat-card .stat-tooltip {
    position: absolute;
    left: 50%;
    top: 100%;
    transform: translateX(-50%) translateY(6px) scale(0.9);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
    background: rgba(15,23,42,0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 10px;
    padding: 8px 14px;
    white-space: nowrap;
    font-size: 12px;
    font-weight: 500;
    color: #e2e8f0;
    z-index: 20;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
}
.stat-card .stat-tooltip::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-bottom-color: rgba(15,23,42,0.95);
}
.stat-card:hover .stat-tooltip {
    opacity: 1;
    transform: translateX(-50%) translateY(6px) scale(1);
    pointer-events: auto;
}
</style>
<div class="stats-grid">
    <div class="stat-card stat-primary stat-openable" data-stat-bucket="total" role="button" tabindex="0" aria-label="View all posts">
        <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
        <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
        <div class="stat-label">Total Posts</div>
        <div class="stat-tooltip"><?= _dashTrendText('total', $_weekTrends) ?></div>
    </div>
    <div class="stat-card stat-info stat-openable" data-stat-bucket="scheduled" role="button" tabindex="0" aria-label="View scheduled posts">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $stats['scheduled'] ?? 0 ?></div>
        <div class="stat-label">Scheduled</div>
        <div class="stat-tooltip"><?= _dashTrendText('scheduled', $_weekTrends) ?></div>
    </div>
    <div class="stat-card stat-success stat-openable" data-stat-bucket="published" role="button" tabindex="0" aria-label="View published posts">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $stats['published'] ?? 0 ?></div>
        <div class="stat-label">Published</div>
        <div class="stat-tooltip"><?= _dashTrendText('published', $_weekTrends) ?></div>
    </div>
    <div class="stat-card stat-warning stat-openable" data-stat-bucket="draft" role="button" tabindex="0" aria-label="View draft posts">
        <div class="stat-icon"><i class="fas fa-pen-fancy"></i></div>
        <div class="stat-value"><?= $stats['draft'] ?? 0 ?></div>
        <div class="stat-label">Drafts</div>
        <div class="stat-tooltip"><?= _dashTrendText('draft', $_weekTrends) ?></div>
    </div>
    <?php if (($stats['failed'] ?? 0) > 0): ?>
    <div class="stat-card stat-danger stat-openable" data-stat-bucket="failed" role="button" tabindex="0" aria-label="View failed posts">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-value"><?= $stats['failed'] ?></div>
        <div class="stat-label">Failed</div>
        <div class="stat-tooltip"><?= _dashTrendText('failed', $_weekTrends) ?></div>
    </div>
    <?php endif; ?>
</div>

<!-- Stat bucket lightbox — zoom-in/out modal showing the posts in the clicked bucket -->
<div id="statBucketModal" class="stat-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="statModalTitle">
    <div class="stat-modal-backdrop"></div>
    <div class="stat-modal-dialog" role="document">
        <div class="stat-modal-header">
            <div class="stat-modal-header-icon" id="statModalIcon"><i class="fas fa-layer-group"></i></div>
            <div>
                <div class="stat-modal-eyebrow">SolidTech</div>
                <h2 class="stat-modal-title" id="statModalTitle">Loading…</h2>
            </div>
            <button type="button" class="stat-modal-close" onclick="closeStatModal()" aria-label="Close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="stat-modal-body" id="statModalBody">
            <div class="stat-modal-loading">
                <i class="fas fa-spinner fa-spin"></i> Loading posts…
            </div>
        </div>
        <div class="stat-modal-footer" id="statModalFooter"></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="section-header">
    <h3 class="section-title">Quick Actions</h3>
</div>
<div class="quick-actions-grid">
    <a href="<?= BASE_URL ?>/generator" class="quick-action-card">
        <div class="quick-action-icon"><i class="fas fa-magic"></i></div>
        <div class="quick-action-text">
            <div class="quick-action-title">Generate Content</div>
            <div class="quick-action-desc">Create posts for <?= $companyHtml ?></div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/calendar" class="quick-action-card">
        <div class="quick-action-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="quick-action-text">
            <div class="quick-action-title">View Calendar</div>
            <div class="quick-action-desc">See your upcoming schedule</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/reporting" class="quick-action-card">
        <div class="quick-action-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="quick-action-text">
            <div class="quick-action-title">View Reports</div>
            <div class="quick-action-desc">Track <?= $companyHtml ?>'s performance</div>
        </div>
    </a>
</div>

<!-- Recent Posts -->
<div class="section-header">
    <h3 class="section-title">Recent Posts</h3>
    <a href="<?= BASE_URL ?>/posts" class="btn btn-ghost btn-sm">View All</a>
</div>

<?php if (empty($recentPosts)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-feather-alt"></i>
            <p><?= $firstName ? "Hey {$firstName}, no" : 'No' ?> posts yet — let's get <?= $companyHtml ?>'s content rolling. Fire up the AI Generator and create your first post in seconds.</p>
            <a href="<?= BASE_URL ?>/generator" class="btn btn-primary cinematic-link cta-shine" data-cin-label="Preparing AI Engine..." style="position:relative;overflow:hidden">
                <i class="fas fa-magic"></i> Generate Your First Post
            </a>
            <style>
            .cta-shine { animation: ctaPulseGlow 2s ease-in-out infinite; }
            .cta-shine::before {
                content: '';
                position: absolute;
                top: 0; left: -100%;
                width: 100%; height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                animation: ctaShineSweep 3s ease 0.5s infinite;
            }
            @keyframes ctaShineSweep {
                0% { left: -100%; }
                30% { left: 100%; }
                100% { left: 100%; }
            }
            @keyframes ctaPulseGlow {
                0%,100% { box-shadow: 0 0 12px rgba(<?php
                    $cr = hexdec(substr($_primaryColor,1,2));
                    $cg = hexdec(substr($_primaryColor,3,2));
                    $cb = hexdec(substr($_primaryColor,5,2));
                    echo "$cr,$cg,$cb";
                ?>, 0.3); }
                50% { box-shadow: 0 0 24px rgba(<?= "$cr,$cg,$cb" ?>, 0.5), 0 4px 16px rgba(<?= "$cr,$cg,$cb" ?>, 0.25); }
            }
            </style>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
            <table data-row-thumb="true">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Platform</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPosts as $post): ?>
                    <tr data-image-url="<?= htmlspecialchars($post['image_url'] ?? '') ?>" data-title="<?= htmlspecialchars($post['title'] ?? '') ?>">
                        <td>
                            <div style="font-weight:600;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= htmlspecialchars($post['title']) ?>
                            </div>
                            <?php if ($post['topic']): ?>
                                <div class="text-muted text-small"><?= htmlspecialchars($post['topic']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= $post['platform'] ?>"><?= ucfirst($post['platform']) ?></span></td>
                        <td><span class="text-small" style="text-transform:capitalize"><?= str_replace('_', ' ', $post['post_type']) ?></span></td>
                        <td><span class="badge badge-<?= $post['status'] ?>"><?= ucfirst($post['status']) ?></span></td>
                        <td>
                            <?php if ($post['scheduled_at']): ?>
                                <span class="text-small"><?= date('M j, g:ia', strtotime($post['scheduled_at'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted text-small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/posts/edit/<?= $post['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($topicStats)): ?>
<!-- Topic Distribution -->
<div class="section-header mt-3">
    <h3 class="section-title">Topics Used</h3>
</div>
<div class="card">
    <div class="flex gap-2" style="flex-wrap:wrap">
        <?php foreach ($topicStats as $topic): ?>
            <div style="background:var(--bg-input);border-radius:100px;padding:6px 14px;font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px">
                <?= htmlspecialchars($topic['topic']) ?>
                <span style="background:rgba(var(--primary-rgb),0.15);color:var(--primary);border-radius:100px;padding:1px 8px;font-size:11px;font-weight:700"><?= $topic['count'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Row image thumbnail tooltip — same slick card used on Posts and Reports views.
(function attachRowThumbTooltip() {
    if (window.__rowThumbTooltipAttached) return;
    window.__rowThumbTooltipAttached = true;

    var tip = null;
    var imgEl = null;
    var labelEl = null;
    var placeholderEl = null;
    var imgWrap = null;
    var currentUrl = null;
    var showTimer = null;
    var hideTimer = null;

    function ensureTip() {
        if (tip) return tip;
        tip = document.createElement('div');
        tip.className = 'row-thumb-tip';
        tip.innerHTML =
            '<div class="row-thumb-tip-img-wrap">' +
                '<div class="row-thumb-tip-placeholder"><i class="fas fa-image"></i><span>No image yet</span></div>' +
                '<img alt="" draggable="false">' +
            '</div>' +
            '<div class="row-thumb-tip-label"></div>';
        document.body.appendChild(tip);
        imgWrap = tip.querySelector('.row-thumb-tip-img-wrap');
        imgEl = tip.querySelector('img');
        labelEl = tip.querySelector('.row-thumb-tip-label');
        placeholderEl = tip.querySelector('.row-thumb-tip-placeholder');
        return tip;
    }

    function positionTip(row) {
        var rect = row.getBoundingClientRect();
        var tipRect = tip.getBoundingClientRect();
        var pad = 14;
        var rightSpace = window.innerWidth - rect.right;
        var preferRight = rightSpace >= tipRect.width + pad;
        var left, top;
        if (preferRight) {
            left = rect.right + pad;
            tip.classList.remove('right-anchor');
        } else {
            left = rect.left - tipRect.width - pad;
            tip.classList.add('right-anchor');
        }
        top = rect.top + rect.height / 2 - tipRect.height / 2;
        if (top < 10) top = 10;
        if (top + tipRect.height > window.innerHeight - 10) {
            top = window.innerHeight - tipRect.height - 10;
        }
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
    }

    function showForRow(row) {
        ensureTip();
        var url = row.getAttribute('data-image-url') || '';
        var title = row.getAttribute('data-title') || '';
        labelEl.textContent = title;

        if (url && url !== currentUrl) {
            imgEl.classList.remove('loaded');
            imgEl.removeAttribute('src');
            imgEl.onload = function() { imgEl.classList.add('loaded'); };
            imgEl.src = url;
            placeholderEl.style.display = 'none';
            currentUrl = url;
        } else if (url) {
            if (imgEl.complete) imgEl.classList.add('loaded');
            placeholderEl.style.display = 'none';
        } else {
            imgEl.classList.remove('loaded');
            imgEl.removeAttribute('src');
            placeholderEl.style.display = '';
            currentUrl = null;
        }

        tip.style.left = '-9999px';
        tip.style.top = '-9999px';
        tip.classList.add('visible');
        requestAnimationFrame(function() { positionTip(row); });
    }

    function hideTip() {
        if (!tip) return;
        tip.classList.remove('visible');
    }

    function bindTable(table) {
        table.addEventListener('mouseover', function(ev) {
            var row = ev.target.closest('tr[data-image-url]');
            if (!row || !table.contains(row)) return;
            clearTimeout(hideTimer);
            clearTimeout(showTimer);
            showTimer = setTimeout(function() { showForRow(row); }, 90);
        });
        table.addEventListener('mouseout', function(ev) {
            var row = ev.target.closest('tr[data-image-url]');
            if (!row) return;
            var to = ev.relatedTarget;
            if (to && row.contains(to)) return;
            clearTimeout(showTimer);
            hideTimer = setTimeout(hideTip, 60);
        });
        table.addEventListener('mousemove', function(ev) {
            if (!tip || !tip.classList.contains('visible')) return;
            var row = ev.target.closest('tr[data-image-url]');
            if (row) positionTip(row);
        });
    }

    document.querySelectorAll('table[data-row-thumb="true"]').forEach(bindTable);
    window.addEventListener('scroll', hideTip, true);
})();
</script>
