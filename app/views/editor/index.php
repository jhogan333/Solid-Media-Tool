<?php
$csrfToken = $_SESSION['csrf_token'] ?? '';
$posts = $posts ?? [];
$firstName = $_SESSION['first_name'] ?? '';
$brand = (new BrandingService())->get($GLOBALS['client_id']);
$companyName = htmlspecialchars($brand['company_name'] ?? 'your company');
$tPrimary = htmlspecialchars($brand['primary_color'] ?? '#6366f1');
$tLogo = $brand['logo_url'] ?? '';

/**
 * Strip emoji characters from a title so the Posts UI stays clean. The raw
 * title is preserved in the database; this is display-only.
 */
function postTitleClean(?string $title): string {
    if ($title === null) return '';
    $cleaned = preg_replace(
        '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F900}-\x{1F9FF}\x{200D}\x{20E3}\x{2702}-\x{27B0}\x{2300}-\x{23FF}\x{1FA70}-\x{1FAFF}]/u',
        '',
        $title
    );
    $cleaned = preg_replace('/\s+/u', ' ', (string)$cleaned);
    return trim((string)$cleaned);
}
?>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">

<!-- Page Transition Portal -->
<div id="pageTransition" style="position:fixed;inset:0;z-index:99995;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0);backdrop-filter:blur(0px);opacity:0;visibility:hidden;transition:opacity 0.4s ease,background 0.5s ease,backdrop-filter 0.5s ease">
    <div id="pageTransContent" style="position:relative;width:280px;height:280px;display:flex;flex-direction:column;align-items:center;justify-content:center;transform:scale(0.6);opacity:0;transition:transform 0.6s cubic-bezier(0.34,1.56,0.64,1),opacity 0.4s ease">
        <!-- Rings -->
        <div style="position:absolute;width:240px;height:240px;border:1px solid rgba(255,255,255,0.06);border-radius:50%;animation:ptSpin 8s linear infinite"></div>
        <div style="position:absolute;width:180px;height:180px;border:1px dashed rgba(255,255,255,0.08);border-radius:50%;animation:ptSpin 5s linear infinite reverse"></div>
        <div style="position:absolute;width:120px;height:120px;border:2px solid rgba(255,255,255,0.06);border-top-color:rgba(255,255,255,0.5);border-radius:50%;animation:ptSpin 2.5s linear infinite;box-shadow:0 0 24px rgba(255,255,255,0.06)"></div>
        <!-- Orbiting dot -->
        <div style="position:absolute;width:160px;height:160px;animation:ptSpin 4s linear infinite">
            <div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 10px rgba(255,255,255,0.7)"></div>
        </div>
        <!-- Logo -->
        <div style="position:relative;z-index:5;width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.1);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center">
            <?php if ($tLogo): ?>
                <img src="<?= htmlspecialchars($tLogo) ?>" style="max-width:42px;max-height:42px;object-fit:contain;filter:brightness(0) invert(1)" alt="">
            <?php else: ?>
                <div style="font-size:24px;font-weight:800;color:#fff"><?= strtoupper(substr($brand['company_name'] ?? 'S', 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div style="position:relative;z-index:5;margin-top:16px;font-size:14px;font-weight:600;color:#fff" id="pageTransStatus">Loading...</div>
        <div style="position:relative;z-index:5;margin-top:4px;font-size:11px;color:rgba(255,255,255,0.4)">Please wait a moment</div>
        <!-- Particles -->
        <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none">
            <?php for ($pi = 0; $pi < 10; $pi++): ?>
            <div style="position:absolute;left:<?= 5 + $pi * 9.5 ?>%;width:<?= 2 + ($pi % 3) ?>px;height:<?= 2 + ($pi % 3) ?>px;border-radius:50%;background:rgba(255,255,255,0.5);animation:ptFloat <?= 3 + ($pi % 3) ?>s ease-in-out infinite;animation-delay:-<?= round($pi * 0.3, 1) ?>s;opacity:0"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>
<style>
@keyframes ptSpin{to{transform:rotate(360deg)}}
@keyframes ptFloat{0%{bottom:-10px;opacity:0;transform:scale(.4)}15%{opacity:.5}85%{opacity:.2}100%{bottom:110%;opacity:0;transform:scale(1)}}

/* Kanban filter bar — painted with the same brand gradient used on the
   topbar and stat cards so the whole Posts page reads as one cohesive
   branded stack. The dropdowns inside keep their light look (the
   custom dropdown enhancer handles hover/selected in brand color). */
.kanban-filter-bar.card {
    background: linear-gradient(180deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 70%, #000000) 100%);
    border: none;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15),
                inset 0 1px 0 rgba(255,255,255,0.12);
}
.kanban-filter-bar .form-select,
.kanban-filter-bar .cdd-btn {
    background: rgba(255,255,255,0.92);
    border-color: rgba(255,255,255,0.6);
}

/* Kanban column card — header uses the brand gradient, body stays on
   the neutral card background. White text and a translucent icon chip
   replace the old blue/linkedin-blue colored chips. */
.kanban-col {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    animation: contentReveal 0.5s ease both;
}
.kanban-col-head {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(180deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 70%, #000000) 100%);
    border-bottom: 1px solid rgba(0,0,0,0.15);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.15);
}
.kanban-col-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,0.18);
    border: 1px solid rgba(255,255,255,0.28);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 15px;
    flex-shrink: 0;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.25),
                0 2px 6px rgba(0,0,0,0.18);
}
.kanban-col-title {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.01em;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
.kanban-col-count {
    font-size: 11px;
    color: rgba(255,255,255,0.7);
    margin-top: 2px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>

<!-- Section Header -->
<div class="section-header">
    <h3 class="section-title">All Posts</h3>
    <div style="display:flex;gap:8px;align-items:center">
        <!-- View Toggle -->
        <div style="display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
            <button class="btn btn-sm" id="viewTableBtn" onclick="switchView('table')" style="border:none;border-radius:0;background:var(--bg-input);color:var(--text-muted);padding:8px 14px">
                <i class="fas fa-list"></i>
            </button>
            <button class="btn btn-sm" id="viewKanbanBtn" onclick="switchView('kanban')" style="border:none;border-radius:0;background:var(--primary);color:#fff;padding:8px 14px">
                <i class="fas fa-columns"></i>
            </button>
        </div>
        <a href="#" class="btn btn-primary btn-shine" onclick="navigateWithTransition(event, '<?= BASE_URL ?>/generator', 'Loading Generator...')">
            <i class="fas fa-magic"></i> New Post
        </a>
    </div>
</div>

<!-- Table View Filters -->
<div id="tableFilters" class="card mb-3" style="padding:16px 20px;display:none">
    <div class="flex-center gap-2" style="flex-wrap:wrap">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
            <select id="filter-status" class="form-select" onchange="filterPosts()">
                <option value="all">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="pending_review">Pending Review</option>
                <option value="scheduled">Scheduled</option>
                <option value="published">Published</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
            <select id="filter-platform" class="form-select" onchange="filterPosts()">
                <option value="all">All Platforms</option>
                <option value="facebook">Facebook</option>
                <option value="linkedin">LinkedIn</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;flex:2;min-width:200px">
            <input type="text" id="filter-search" class="form-input" placeholder="Search posts..." oninput="filterPosts()">
        </div>
    </div>
</div>

<!-- Kanban View Filters — brand-gradient card to match the topbar -->
<div id="kanbanFilters" class="card mb-3 kanban-filter-bar" style="padding:16px 20px">
    <div class="flex-center gap-2" style="flex-wrap:wrap">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
            <select id="kanban-month" class="form-select" onchange="renderKanban()">
                <?php
                $now = new DateTime();
                for ($m = -1; $m <= 2; $m++) {
                    $d = (clone $now)->modify("{$m} months");
                    $val = $d->format('Y-m');
                    $label = $d->format('F Y');
                    $sel = $m === 0 ? ' selected' : '';
                    echo "<option value=\"{$val}\"{$sel}>{$label}</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:160px">
            <select id="kanban-status" class="form-select" onchange="renderKanban()">
                <option value="all">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="published">Published</option>
                <option value="failed">Failed</option>
            </select>
        </div>
    </div>
</div>

<!-- Kanban Board -->
<div id="kanbanView">
    <div id="kanbanBoard" style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Rendered by JS -->
    </div>
</div>

<!-- Posts Table View -->
<div id="tableView" style="display:none">
<?php if (empty($posts)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-feather-alt"></i>
            <p><?= $firstName ? "Hey {$firstName}, there" : 'There' ?> are no posts for <?= $companyName ?> yet. Head over to the AI Generator to create your first one.</p>
            <a href="<?= BASE_URL ?>/generator" class="btn btn-primary">
                <i class="fas fa-magic"></i> Generate Your First Post
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
            <table id="posts-table" data-row-thumb="true">
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
                    <?php foreach ($posts as $post): ?>
                    <?php
                        $postPlatforms = [];
                        if (!empty($post['platforms'])) {
                            $decoded = json_decode($post['platforms'], true);
                            if (is_array($decoded)) $postPlatforms = $decoded;
                        }
                        if (empty($postPlatforms)) $postPlatforms = [$post['platform'] ?? 'facebook'];
                    ?>
                    <tr class="clickable-row"
                        data-post-id="<?= (int)$post['id'] ?>"
                        data-status="<?= htmlspecialchars($post['status']) ?>"
                        data-platform="<?= htmlspecialchars(implode(',', $postPlatforms)) ?>"
<?php $displayTitle = postTitleClean($post['title'] ?? '') ?: 'Untitled'; ?>
                        data-search="<?= htmlspecialchars(strtolower($displayTitle . ' ' . ($post['topic'] ?? ''))) ?>"
                        data-image-url="<?= htmlspecialchars($post['image_url'] ?? '') ?>"
                        data-title="<?= htmlspecialchars($displayTitle) ?>">
                        <td>
                            <div style="font-weight:600;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= htmlspecialchars($displayTitle) ?>
                            </div>
                            <?php if (!empty($post['topic'])): ?>
                                <div class="text-muted text-small"><?= htmlspecialchars($post['topic']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php foreach ($postPlatforms as $p): ?>
                                <span class="badge badge-<?= htmlspecialchars($p) ?>"><?= ucfirst(htmlspecialchars($p)) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><span class="text-small" style="text-transform:capitalize"><?= str_replace('_', ' ', htmlspecialchars($post['post_type'])) ?></span></td>
                        <td><span class="badge badge-<?= htmlspecialchars($post['status']) ?>"><?= ucfirst(htmlspecialchars($post['status'])) ?></span></td>
                        <td>
                            <?php if (!empty($post['scheduled_at'])): ?>
                                <span class="text-small"><?= date('M j, g:ia', strtotime($post['scheduled_at'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted text-small">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex-center gap-1">
                                <a href="<?= BASE_URL ?>/posts/edit/<?= (int)$post['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button class="btn btn-ghost btn-sm btn-icon" title="Delete" onclick="deletePost(<?= (int)$post['id'] ?>, this)" style="color:var(--danger)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-muted text-small mt-2" id="filter-count">
        Showing <?= count($posts) ?> post<?= count($posts) !== 1 ? 's' : '' ?>
    </div>
<?php endif; ?>
</div><!-- /tableView -->

<script>
const BASE = '<?= rtrim(BASE_URL, '/') ?>';
const csrfToken = () => document.getElementById('csrf-token').value;

function filterPosts() {
    const status = document.getElementById('filter-status').value;
    const platform = document.getElementById('filter-platform').value;
    const search = document.getElementById('filter-search').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#posts-table tbody tr');
    let visible = 0;

    rows.forEach(row => {
        const matchStatus = status === 'all' || row.dataset.status === status;
        const matchPlatform = platform === 'all' || row.dataset.platform.split(',').includes(platform);
        const matchSearch = !search || row.dataset.search.includes(search);

        if (matchStatus && matchPlatform && matchSearch) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    const countEl = document.getElementById('filter-count');
    if (countEl) {
        countEl.textContent = 'Showing ' + visible + ' post' + (visible !== 1 ? 's' : '');
    }
}

// Row click → open post editor (ignore clicks on buttons, links, inputs)
(function() {
    var table = document.getElementById('posts-table');
    if (!table) return;
    table.addEventListener('click', function(ev) {
        var target = ev.target;
        if (target.closest('a, button, input, select, textarea, label')) return;
        var row = target.closest('tr.clickable-row');
        if (!row) return;
        var id = row.getAttribute('data-post-id');
        if (id) window.location.href = BASE + '/posts/edit/' + id;
    });
})();

// Row image thumbnail tooltip — slick corporate card that follows row hover.
// Shared logic: attaches to any <table data-row-thumb="true">. Reads
// data-image-url + data-title from the <tr>, lazy-loads the image once,
// positions the tip next to the row, and fades it in/out.
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
        // Keep in viewport vertically
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
            // Same image — just ensure it's marked loaded if already cached
            if (imgEl.complete) imgEl.classList.add('loaded');
            placeholderEl.style.display = 'none';
        } else {
            imgEl.classList.remove('loaded');
            imgEl.removeAttribute('src');
            placeholderEl.style.display = '';
            currentUrl = null;
        }

        // Position off-screen first so we can measure, then show
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
            var row = ev.target.closest('tr[data-image-url], tr.clickable-row');
            if (!row || !table.contains(row)) return;
            if (!row.hasAttribute('data-image-url')) return;
            clearTimeout(hideTimer);
            clearTimeout(showTimer);
            // Small delay so flicking past rows doesn't spam tooltips
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
        // Re-position while moving down rows (helps on long tables)
        table.addEventListener('mousemove', function(ev) {
            if (!tip || !tip.classList.contains('visible')) return;
            var row = ev.target.closest('tr[data-image-url]');
            if (row) positionTip(row);
        });
    }

    document.querySelectorAll('table[data-row-thumb="true"]').forEach(bindTable);
    window.addEventListener('scroll', hideTip, true);
})();

function deletePost(id, btnEl) {
    confirmModal('Delete Post', 'Are you sure you want to delete this post? This action cannot be undone.', async () => {
        try {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken());

            const res = await fetch(BASE + '/posts/delete/' + id, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Delete failed');

            const row = btnEl.closest('tr');
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    filterPosts();
                }, 300);
            }
            showToast('Post deleted.', 'success');
        } catch (err) {
            showToast(err.message, 'error');
        }
    });
}

// --- Row Highlight on redirect ---
(function() {
    var params = new URLSearchParams(window.location.search);
    var highlightId = params.get('highlight');
    if (!highlightId) return;

    // Clean URL without reload
    var cleanUrl = window.location.pathname;
    window.history.replaceState({}, '', cleanUrl);

    // Find the row
    var row = document.querySelector('tr[data-post-id="' + highlightId + '"]');
    if (!row) return;

    // Clear any active filters so the row is visible
    document.querySelectorAll('.filter-select').forEach(function(s) { s.value = 'all'; });
    var searchInput = document.querySelector('.filter-search');
    if (searchInput) searchInput.value = '';
    document.querySelectorAll('tbody tr').forEach(function(r) { r.style.display = ''; });

    // Scroll to row
    setTimeout(function() {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Apply glow effect
        row.style.transition = 'box-shadow 0.6s ease, background 0.6s ease';
        row.style.position = 'relative';
        row.style.zIndex = '5';
        row.style.boxShadow = '0 0 0 2px var(--primary), 0 0 24px rgba(var(--primary-rgb), 0.35)';
        row.style.background = 'rgba(var(--primary-rgb), 0.08)';

        // Pulse the glow
        setTimeout(function() {
            row.style.boxShadow = '0 0 0 3px var(--primary), 0 0 40px rgba(var(--primary-rgb), 0.5)';
            row.style.background = 'rgba(var(--primary-rgb), 0.12)';
        }, 600);

        // Fade back to normal
        setTimeout(function() {
            row.style.transition = 'box-shadow 1.2s ease, background 1.2s ease';
            row.style.boxShadow = 'none';
            row.style.background = '';
        }, 2200);

        // Clean up
        setTimeout(function() {
            row.style.position = '';
            row.style.zIndex = '';
            row.style.transition = '';
        }, 3500);
    }, 300);
})();

// ---- View Toggle ----
// Kanban is the default — nicer looking than the plain table for most users.
var currentView = 'kanban';
var ALL_POSTS = <?= json_encode(array_map(function($p) {
    $plats = json_decode($p['platforms'] ?? '[]', true) ?: [$p['platform'] ?? 'facebook'];
    // Filter to active platforms only
    $plats = array_values(array_filter($plats, function($pl) { return in_array($pl, ['facebook','linkedin']); }));
    return [
        'id' => (int)$p['id'],
        'title' => postTitleClean($p['title'] ?? ''), // strip emojis server-side
        'topic' => $p['topic'] ?? '',
        'post_type' => $p['post_type'] ?? '',
        'status' => $p['status'],
        'platforms' => $plats,
        'scheduled_at' => $p['scheduled_at'] ?? null,
        'created_at' => $p['created_at'] ?? null,
        'image_url' => $p['image_url'] ?? '',
    ];
}, $posts)) ?>;

function switchView(view) {
    currentView = view;
    var tableBtn = document.getElementById('viewTableBtn');
    var kanbanBtn = document.getElementById('viewKanbanBtn');
    var tableView = document.getElementById('tableView');
    var kanbanView = document.getElementById('kanbanView');
    var tableFilters = document.getElementById('tableFilters');
    var kanbanFilters = document.getElementById('kanbanFilters');

    if (view === 'table') {
        tableBtn.style.background = 'var(--primary)'; tableBtn.style.color = '#fff';
        kanbanBtn.style.background = 'var(--bg-input)'; kanbanBtn.style.color = 'var(--text-muted)';
        tableView.style.display = '';
        kanbanView.style.display = 'none';
        tableFilters.style.display = '';
        kanbanFilters.style.display = 'none';
    } else {
        kanbanBtn.style.background = 'var(--primary)'; kanbanBtn.style.color = '#fff';
        tableBtn.style.background = 'var(--bg-input)'; tableBtn.style.color = 'var(--text-muted)';
        tableView.style.display = 'none';
        kanbanView.style.display = '';
        tableFilters.style.display = 'none';
        kanbanFilters.style.display = '';
        renderKanban();
    }
}

// ---- Kanban Board ----
function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
}
function escAttr(str) {
    return String(str == null ? '' : str)
        .replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')
        .replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

var PLATFORM_CONFIG = {
    facebook: { label: 'Facebook', color: '#1877F2', icon: 'fab fa-facebook-f' },
    linkedin: { label: 'LinkedIn', color: '#0A66C2', icon: 'fab fa-linkedin-in' },
};

var STATUS_COLORS = {
    draft: 'var(--text-muted)',
    pending_review: 'var(--warning)',
    scheduled: 'var(--info)',
    published: 'var(--success)',
    failed: 'var(--danger)',
};

// ---- Title cleanup + date helpers ----
// Strip leading/inline emojis from titles for display purposes. The raw
// titles stay unchanged in the database; we only clean them for the board.
function stripEmojis(text) {
    if (text == null) return '';
    try {
        return String(text)
            .replace(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{2600}-\u{27BF}\u{FE00}-\u{FE0F}\u{1F900}-\u{1F9FF}\u{200D}\u{20E3}\u{2702}-\u{27B0}\u{2300}-\u{23FF}\u{1FA70}-\u{1FAFF}]/gu, '')
            .replace(/\s+/g, ' ')
            .trim();
    } catch (e) {
        // Fallback for older browsers without the u flag
        return String(text).replace(/[^\x20-\x7E\u00A0-\uFFFF]/g, '').trim();
    }
}
function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' · '
         + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}
function relativeDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '';
    var now = new Date();
    var diffMs = d - now;
    var abs = Math.abs(diffMs);
    var future = diffMs > 0;

    if (abs < 60000) return 'Now';
    var mins = Math.round(abs / 60000);
    if (abs < 3600000) return future ? ('In ' + mins + 'm') : (mins + 'm ago');
    var hrs = Math.round(abs / 3600000);
    if (abs < 86400000) return future ? ('In ' + hrs + 'h') : (hrs + 'h ago');
    var days = Math.round(abs / 86400000);
    if (days === 1) return future ? 'Tomorrow' : 'Yesterday';
    if (days < 7) return future ? ('In ' + days + ' days') : (days + ' days ago');
    if (days < 30) { var w = Math.round(days / 7); return future ? ('In ' + w + 'w') : (w + 'w ago'); }
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ---- Kanban renderer (status-bucketed) ----
// Each platform column now contains two sub-sections:
//   1. Scheduled — upcoming posts + in-progress drafts + failures in the pipeline
//   2. Published — what's already gone out
// Drafts appear in the Scheduled bucket with a dashed border + "DRAFT" badge
// so they read as "not ready yet" at a glance. Failures appear in Scheduled
// with a red accent so they get attention.
function renderKanban() {
    var board = document.getElementById('kanbanBoard');
    if (!board) { console.error('[kanban] board element missing'); return; }

    try {
        var monthEl = document.getElementById('kanban-month');
        var statusEl = document.getElementById('kanban-status');
        var monthFilter = monthEl ? monthEl.value : '';
        var statusFilter = statusEl ? statusEl.value : 'all';
        var monthParts = monthFilter.split('-');
        var filterYear = parseInt(monthParts[0], 10);
        var filterMonth = parseInt(monthParts[1], 10);

        board.innerHTML = '';

        Object.keys(PLATFORM_CONFIG).forEach(function(platform) {
            var conf = PLATFORM_CONFIG[platform];

            // Pre-filter by platform + status dropdown
            var candidates = (Array.isArray(ALL_POSTS) ? ALL_POSTS : []).filter(function(p) {
                var pls = Array.isArray(p.platforms) ? p.platforms : [];
                if (pls.indexOf(platform) === -1) return false;
                if (statusFilter !== 'all' && p.status !== statusFilter) return false;
                return true;
            });

            // Split into buckets:
            //   scheduled bucket = scheduled + draft + pending_review + failed
            //   published bucket = published
            // Dated items respect the month filter; draft/pending without a
            // publish date always show (they're "pipeline" work).
            var scheduledBucket = [];
            var publishedBucket = [];

            candidates.forEach(function(p) {
                var dateStr = p.scheduled_at || p.created_at;
                var d = dateStr ? new Date(String(dateStr).replace(' ', 'T')) : null;
                var dateValid = d && !isNaN(d.getTime());
                var inMonth = dateValid
                    ? (d.getFullYear() === filterYear && (d.getMonth() + 1) === filterMonth)
                    : true;

                if (p.status === 'published') {
                    // Published respects the month filter (historical view)
                    if (inMonth) publishedBucket.push(p);
                } else if (p.status === 'scheduled') {
                    // Only show scheduled items that fall inside the month filter
                    if (inMonth) scheduledBucket.push(p);
                } else {
                    // draft, pending_review, failed — always visible in the pipeline
                    scheduledBucket.push(p);
                }
            });

            // Sort scheduled bucket: dated items first (ascending, soonest first),
            // then dateless drafts (by created_at descending, newest first).
            scheduledBucket.sort(function(a, b) {
                var aDate = a.scheduled_at ? new Date(String(a.scheduled_at).replace(' ', 'T')) : null;
                var bDate = b.scheduled_at ? new Date(String(b.scheduled_at).replace(' ', 'T')) : null;
                if (aDate && bDate) return aDate - bDate;
                if (aDate && !bDate) return -1;
                if (!aDate && bDate) return 1;
                // Both dateless — newest-created first
                var aC = new Date(String(a.created_at || 0).replace(' ', 'T'));
                var bC = new Date(String(b.created_at || 0).replace(' ', 'T'));
                return bC - aC;
            });

            // Published sorted newest first
            publishedBucket.sort(function(a, b) {
                var aD = new Date(String((a.scheduled_at || a.created_at || 0)).replace(' ', 'T'));
                var bD = new Date(String((b.scheduled_at || b.created_at || 0)).replace(' ', 'T'));
                return bD - aD;
            });

            // Build column shell
            var col = document.createElement('div');
            col.className = 'kanban-col';
            col.style.animationDelay = (platform === 'facebook' ? '0.1' : '0.2') + 's';

            var totalCount = scheduledBucket.length + publishedBucket.length;
            col.innerHTML = '<div class="kanban-col-head">'
                + '<div class="kanban-col-icon"><i class="' + conf.icon + '"></i></div>'
                + '<div>'
                + '<div class="kanban-col-title">' + conf.label + '</div>'
                + '<div class="kanban-col-count">' + totalCount + ' total</div>'
                + '</div></div>';

            var bodyHtml = '<div class="kanban-col-body">';
            bodyHtml += renderBucket('scheduled', 'Scheduled & Drafts', 'fa-calendar-plus', scheduledBucket);
            bodyHtml += renderBucket('published', 'Published', 'fa-check-double', publishedBucket);
            bodyHtml += '</div>';
            col.innerHTML += bodyHtml;

            board.appendChild(col);
        });
    } catch (err) {
        console.error('[kanban] renderKanban failed:', err);
        board.innerHTML = '<div style="grid-column:1/-1;padding:32px;text-align:center;color:var(--text-muted);font-size:13px">'
            + '<i class="fas fa-exclamation-triangle" style="color:var(--warning);font-size:24px;margin-bottom:8px;display:block"></i>'
            + 'Could not render the board: ' + (err && err.message ? err.message : 'unknown error')
            + '</div>';
    }
}

function renderBucket(bucketType, label, iconClass, posts) {
    // Restore collapsed state from localStorage so it persists across reloads
    var storageKey = 'kanban.bucket.' + bucketType + '.collapsed';
    var isCollapsed = false;
    try { isCollapsed = localStorage.getItem(storageKey) === '1'; } catch (e) {}

    var html = '<div class="kanban-bucket kanban-bucket-' + bucketType + (isCollapsed ? ' collapsed' : '') + '" data-bucket="' + bucketType + '">';
    html += '<div class="kanban-bucket-head" onclick="toggleKanbanBucket(this)" role="button" tabindex="0" aria-expanded="' + (isCollapsed ? 'false' : 'true') + '">'
        +   '<span class="kanban-bucket-head-left">'
        +     '<span class="kanban-bucket-label"><i class="fas ' + iconClass + '"></i> ' + label + '</span>'
        +   '</span>'
        +   '<span class="kanban-bucket-head-right">'
        +     '<span class="kanban-bucket-count">' + posts.length + '</span>'
        +     '<span class="kanban-bucket-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>'
        +   '</span>'
        + '</div>';
    html += '<div class="kanban-bucket-body"><div class="kanban-bucket-body-inner">';

    if (posts.length === 0) {
        var emptyMsg;
        if (bucketType === 'scheduled') {
            emptyMsg = 'Nothing in the pipeline for this month.';
        } else {
            emptyMsg = 'No posts published yet.';
        }
        html += '<div class="kanban-empty-hint"><i class="fas fa-inbox" style="margin-right:6px;opacity:0.55"></i>' + emptyMsg + '</div>';
    } else {
        posts.forEach(function(p, idx) {
            html += renderCard(p, idx);
        });
    }

    html += '</div></div></div>';
    return html;
}

// Accordion toggle — click the bucket header (or press Enter/Space when it has focus)
function toggleKanbanBucket(headEl) {
    var bucket = headEl.closest('.kanban-bucket');
    if (!bucket) return;
    var key = 'kanban.bucket.' + bucket.getAttribute('data-bucket') + '.collapsed';
    var willCollapse = !bucket.classList.contains('collapsed');
    bucket.classList.toggle('collapsed', willCollapse);
    headEl.setAttribute('aria-expanded', willCollapse ? 'false' : 'true');
    try { localStorage.setItem(key, willCollapse ? '1' : '0'); } catch (e) {}
}
// Keyboard support for the header button
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    var t = e.target;
    if (t && t.classList && t.classList.contains('kanban-bucket-head')) {
        e.preventDefault();
        toggleKanbanBucket(t);
    }
});

function renderCard(p, idx) {
    var cleanTitle = stripEmojis(p.title || 'Untitled') || 'Untitled';
    var typeLabel = (p.post_type || '').replace(/_/g, ' ');

    var variant = 'card-' + (p.status || 'draft');
    // Map pending_review → card-review so CSS matches without hyphenation issues
    if (p.status === 'pending_review') variant = 'card-review';

    var badgeHtml = '';
    var dateMain = '';
    var relLabel = '';

    if (p.status === 'draft') {
        badgeHtml = '<span class="kanban-card-badge badge-draft"><i class="fas fa-pen-ruler"></i> Incomplete</span>';
        dateMain = p.created_at ? 'Created ' + formatDate(p.created_at) : '';
        relLabel = 'Needs scheduling';
    } else if (p.status === 'pending_review') {
        badgeHtml = '<span class="kanban-card-badge badge-review"><i class="fas fa-hourglass-half"></i> In review</span>';
        dateMain = p.scheduled_at ? formatDate(p.scheduled_at) : (p.created_at ? 'Submitted ' + formatDate(p.created_at) : '');
        relLabel = p.scheduled_at ? relativeDate(p.scheduled_at) : 'Awaiting approval';
    } else if (p.status === 'failed') {
        badgeHtml = '<span class="kanban-card-badge badge-failed"><i class="fas fa-triangle-exclamation"></i> Failed</span>';
        dateMain = p.scheduled_at ? formatDate(p.scheduled_at) : (p.created_at ? formatDate(p.created_at) : '');
        relLabel = 'Tap to retry';
    } else if (p.status === 'scheduled') {
        dateMain = p.scheduled_at ? formatDate(p.scheduled_at) : '';
        relLabel = p.scheduled_at ? relativeDate(p.scheduled_at) : '';
    } else if (p.status === 'published') {
        dateMain = p.scheduled_at ? formatDate(p.scheduled_at) : (p.created_at ? formatDate(p.created_at) : '');
        relLabel = p.scheduled_at ? relativeDate(p.scheduled_at) : relativeDate(p.created_at);
    }

    return '<a href="' + BASE + '/posts/edit/' + p.id + '" class="kanban-card ' + variant + '" style="animation-delay:' + (0.08 + idx * 0.04) + 's" '
        + 'onmouseenter="showKanbanTip(this,event)" onmouseleave="hideKanbanTip()">'
        + (badgeHtml ? '<div class="kanban-card-badge-row">' + badgeHtml + '</div>' : '')
        + '<div class="kanban-card-title">' + escHtml(cleanTitle) + '</div>'
        + (dateMain || relLabel ? '<div class="kanban-card-meta">'
            + '<span class="kanban-card-date">' + escHtml(dateMain) + '</span>'
            + (relLabel ? '<span class="kanban-card-rel">' + escHtml(relLabel) + '</span>' : '')
            + '</div>' : '')
        + '<div data-tip-type="' + escAttr(typeLabel) + '" data-tip-status="' + escAttr(p.status) + '" data-tip-topic="' + escAttr(p.topic || '') + '" data-tip-image="' + escAttr(p.image_url || '') + '" data-tip-title="' + escAttr(cleanTitle) + '" style="display:none"></div>'
        + '</a>';
}

// Kanban is the default view — render immediately on page load so the
// initial state isn't an empty board.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { renderKanban(); });
} else {
    renderKanban();
}

// Animated tooltip for kanban cards — now includes the post thumbnail
var kanbanTipEl = null;
function showKanbanTip(card, e) {
    hideKanbanTip();
    var meta = card.querySelector('[data-tip-type]');
    if (!meta) return;

    var tip = document.createElement('div');
    tip.id = 'kanbanTip';
    tip.style.cssText = 'position:fixed;z-index:9999;width:220px;background:rgba(15,23,42,0.95);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:10px 10px 12px;pointer-events:none;animation:tipIn 0.25s cubic-bezier(0.34,1.56,0.64,1);box-shadow:0 18px 44px rgba(0,0,0,0.45),0 4px 14px rgba(0,0,0,0.25)';

    var parts = [];

    // Thumbnail (or placeholder if no image)
    var imgUrl = meta.dataset.tipImage || '';
    if (imgUrl) {
        parts.push('<div style="width:100%;aspect-ratio:1/1;border-radius:8px;overflow:hidden;background:rgba(255,255,255,0.05);margin-bottom:10px">'
            + '<img src="' + imgUrl + '" alt="" style="width:100%;height:100%;object-fit:cover;display:block">'
            + '</div>');
    } else {
        parts.push('<div style="width:100%;aspect-ratio:1/1;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;color:rgba(255,255,255,0.4);margin-bottom:10px">'
            + '<i class="fas fa-image" style="font-size:28px;opacity:0.45"></i>'
            + '<span style="font-size:10px">No image yet</span>'
            + '</div>');
    }

    // Text block
    parts.push('<div style="padding:0 4px">');
    if (meta.dataset.tipType) parts.push('<div style="font-size:10px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">' + meta.dataset.tipType + '</div>');
    if (meta.dataset.tipTitle) parts.push('<div style="font-size:12px;color:#fff;font-weight:700;line-height:1.35;margin-bottom:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">' + meta.dataset.tipTitle + '</div>');
    if (meta.dataset.tipTopic) parts.push('<div style="font-size:11px;color:rgba(255,255,255,0.65)">' + meta.dataset.tipTopic + '</div>');
    if (meta.dataset.tipStatus) parts.push('<div style="font-size:10px;color:rgba(255,255,255,0.4);margin-top:4px;text-transform:capitalize">' + meta.dataset.tipStatus.replace(/_/g, ' ') + '</div>');
    parts.push('</div>');

    tip.innerHTML = parts.join('');

    document.body.appendChild(tip);
    kanbanTipEl = tip;

    var rect = card.getBoundingClientRect();
    var tipRect = tip.getBoundingClientRect();
    var pad = 12;

    // Prefer right, fall back to left if it overflows
    var left = rect.right + pad;
    if (left + tipRect.width > window.innerWidth - 10) {
        left = rect.left - tipRect.width - pad;
    }
    // Clamp vertically so the top stays in view
    var top = rect.top;
    if (top + tipRect.height > window.innerHeight - 10) {
        top = Math.max(10, window.innerHeight - tipRect.height - 10);
    }
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
}

function hideKanbanTip() {
    var t = document.getElementById('kanbanTip');
    if (t) t.remove();
    kanbanTipEl = null;
}

// Page transition for navigation
function navigateWithTransition(e, url, statusText) {
    e.preventDefault();
    var portal = document.getElementById('pageTransition');
    var content = document.getElementById('pageTransContent');
    var status = document.getElementById('pageTransStatus');
    var primary = '<?= $tPrimary ?>';

    status.textContent = statusText || 'Loading...';
    portal.style.background = 'linear-gradient(165deg,' + primary + ' 0%,#0a0a0a 60%,#000 100%)';
    portal.style.opacity = '1';
    portal.style.visibility = 'visible';
    portal.style.backdropFilter = 'blur(10px)';
    content.style.transform = 'scale(1)';
    content.style.opacity = '1';

    setTimeout(function() {
        window.location.href = url;
    }, 1500);
}
</script>
<style>
.kanban-card {
    display: block;
    padding: 11px 14px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    animation: kanbanCardIn 0.3s ease both;
    position: relative;
}
.kanban-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 14px rgba(var(--primary-rgb), 0.14);
    transform: translateX(2px);
}

/* Status-variant card styling */
.kanban-card.card-scheduled {
    border-left: 3px solid #3b82f6;
}
.kanban-card.card-published {
    border-left: 3px solid #16a34a;
}
.kanban-card.card-draft {
    border-style: dashed;
    border-color: rgba(245, 158, 11, 0.45);
    background: rgba(245, 158, 11, 0.04);
    border-left: 3px dashed #f59e0b;
}
.kanban-card.card-draft:hover {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, 0.08);
}
.kanban-card.card-failed {
    border-color: rgba(239, 68, 68, 0.45);
    background: rgba(239, 68, 68, 0.04);
    border-left: 3px solid #ef4444;
}
.kanban-card.card-failed:hover {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
}
.kanban-card.card-review {
    border-color: rgba(168, 85, 247, 0.4);
    background: rgba(168, 85, 247, 0.035);
    border-left: 3px solid #a855f7;
}

.kanban-card-badge-row {
    margin-bottom: 6px;
}
.kanban-card-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 9px;
    border-radius: 100px;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
}
.kanban-card-badge.badge-draft {
    background: rgba(245, 158, 11, 0.15);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.35);
}
.kanban-card-badge.badge-review {
    background: rgba(168, 85, 247, 0.12);
    color: #7c3aed;
    border: 1px solid rgba(168, 85, 247, 0.3);
}
.kanban-card-badge.badge-failed {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.kanban-card-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    line-height: 1.35;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.kanban-card.card-draft .kanban-card-title {
    color: rgba(15, 23, 42, 0.72);
    font-style: italic;
}
.kanban-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 10px;
    color: var(--text-muted);
    font-weight: 500;
}
.kanban-card-rel {
    font-weight: 700;
    color: #3b82f6;
    padding: 1px 8px;
    background: rgba(59,130,246,0.1);
    border-radius: 100px;
    white-space: nowrap;
}
.kanban-card.card-published .kanban-card-rel { color: #16a34a; background: rgba(22,163,74,0.1); }
.kanban-card.card-draft .kanban-card-rel { color: #b45309; background: rgba(245,158,11,0.12); font-style: italic; }
.kanban-card.card-failed .kanban-card-rel { color: #b91c1c; background: rgba(239,68,68,0.12); }

/* Buckets — sub-sections within each platform column */
.kanban-col-body { padding: 0; }
.kanban-bucket { }
.kanban-bucket + .kanban-bucket { border-top: 1px solid var(--border); }
.kanban-bucket-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 18px 10px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
}
.kanban-bucket-head:hover {
    filter: brightness(1.05);
}
.kanban-bucket-head-left {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}
.kanban-bucket-head-right {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}
.kanban-bucket-chevron {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 6px;
    background: rgba(255,255,255,0.65);
    color: currentColor;
    font-size: 10px;
    transition: transform 0.3s cubic-bezier(0.22,1,0.36,1),
                background 0.2s ease;
}
.kanban-bucket-scheduled .kanban-bucket-chevron { background: rgba(59,130,246,0.18); }
.kanban-bucket-published .kanban-bucket-chevron { background: rgba(34,197,94,0.18); }
.kanban-bucket.collapsed .kanban-bucket-chevron {
    transform: rotate(-90deg);
}
.kanban-bucket-body {
    overflow: hidden;
    max-height: 4000px; /* large enough to hold expanded content */
    transition: max-height 0.42s cubic-bezier(0.22,1,0.36,1),
                padding 0.32s ease,
                opacity 0.25s ease;
    opacity: 1;
}
.kanban-bucket.collapsed .kanban-bucket-body {
    max-height: 0;
    padding-top: 0;
    padding-bottom: 0;
    opacity: 0;
}
.kanban-bucket-scheduled .kanban-bucket-head {
    color: #3b82f6;
    background: linear-gradient(180deg, rgba(59,130,246,0.09) 0%, rgba(59,130,246,0.02) 100%);
    border-bottom: 1px solid rgba(59,130,246,0.14);
}
.kanban-bucket-published .kanban-bucket-head {
    color: #16a34a;
    background: linear-gradient(180deg, rgba(34,197,94,0.09) 0%, rgba(34,197,94,0.02) 100%);
    border-bottom: 1px solid rgba(34,197,94,0.14);
}
.kanban-bucket-label {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.kanban-bucket-count {
    padding: 2px 9px;
    border-radius: 100px;
    font-size: 10px;
    font-weight: 800;
    min-width: 22px;
    text-align: center;
}
.kanban-bucket-scheduled .kanban-bucket-count {
    background: rgba(59,130,246,0.16);
    color: #1d4ed8;
}
.kanban-bucket-published .kanban-bucket-count {
    background: rgba(34,197,94,0.18);
    color: #15803d;
}
.kanban-bucket-body-inner {
    padding: 10px 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 54px;
}
.kanban-empty-hint {
    text-align: center;
    padding: 14px 12px;
    color: var(--text-muted);
    font-size: 11px;
    font-style: italic;
    opacity: 0.75;
}
@keyframes kanbanCardIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes tipIn {
    from { opacity: 0; transform: scale(0.9) translateY(4px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.btn-shine{position:relative;overflow:hidden}
.btn-shine::after{content:'';position:absolute;top:-50%;left:-60%;width:40%;height:200%;background:linear-gradient(105deg,transparent 40%,rgba(255,255,255,0.35) 45%,rgba(255,255,255,0.1) 50%,transparent 55%);opacity:0;pointer-events:none;transition:opacity .2s}
.btn-shine:hover::after{opacity:1;animation:btnShine .7s ease forwards}
@keyframes btnShine{0%{left:-60%}100%{left:120%}}
</style>
