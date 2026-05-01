<style>
.activity-filter-bar { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; margin-bottom:0; }
.activity-filter-bar .form-group { margin-bottom:0; min-width:140px; }
.activity-filter-bar .form-label { margin-bottom:4px; }
.activity-action-badge {
    display:inline-block;
    padding:3px 10px;
    border-radius:100px;
    font-size:11px;
    font-weight:600;
    text-transform:capitalize;
    letter-spacing:0.02em;
    white-space:nowrap;
}
.activity-action-login_success { background:rgba(34,197,94,0.15); color:#22c55e; }
.activity-action-login_failed { background:rgba(239,68,68,0.15); color:#ef4444; }
.activity-action-logout { background:rgba(148,163,184,0.18); color:#94a3b8; }
.activity-action-post_created, .activity-action-post_updated, .activity-action-post_scheduled,
.activity-action-post_published, .activity-action-post_posted_now, .activity-action-post_retried {
    background:rgba(var(--primary-rgb),0.15); color:var(--primary);
}
.activity-action-post_deleted, .activity-action-user_deleted, .activity-action-user_permanently_deleted,
.activity-action-post_failed, .activity-action-login_failed {
    background:rgba(239,68,68,0.12); color:#ef4444;
}
.activity-action-post_approved { background:rgba(34,197,94,0.15); color:#22c55e; }
.activity-action-post_changes_requested { background:rgba(250,204,21,0.18); color:#facc15; }
.activity-action-role_changed, .activity-action-user_updated, .activity-action-user_created,
.activity-action-user_activated, .activity-action-user_deactivated, .activity-action-user_restored,
.activity-action-password_reset_by_admin, .activity-action-password_self_reset, .activity-action-password_changed,
.activity-action-invite_sent { background:rgba(139,92,246,0.15); color:#a78bfa; }
.activity-action-settings_updated { background:rgba(59,130,246,0.15); color:#60a5fa; }

.activity-role-pill {
    display:inline-block;
    padding:2px 8px;
    border-radius:100px;
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:0.05em;
    background:rgba(148,163,184,0.15);
    color:var(--text-muted);
    margin-left:6px;
    vertical-align:middle;
}
.activity-role-admin { background:rgba(var(--primary-rgb),0.18); color:var(--primary); }
.activity-role-editor { background:rgba(139,92,246,0.15); color:#a78bfa; }
.activity-role-reviewer { background:rgba(250,204,21,0.18); color:#facc15; }
.activity-role-system { background:rgba(59,130,246,0.15); color:#60a5fa; }

.activity-details-btn {
    background:transparent;
    border:none;
    color:var(--text-muted);
    cursor:pointer;
    padding:4px 8px;
    border-radius:6px;
    font-size:12px;
    transition:all 0.15s;
}
.activity-details-btn:hover { background:rgba(var(--primary-rgb),0.1); color:var(--primary); }

.session-summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
    gap:16px;
    margin-bottom:24px;
}
.session-card {
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:var(--radius-md);
    padding:18px 20px;
    display:flex;
    align-items:center;
    gap:16px;
    transition:all 0.2s;
}
.session-card:hover { border-color:rgba(var(--primary-rgb),0.35); }
.session-card-avatar {
    width:46px;
    height:46px;
    border-radius:50%;
    background:linear-gradient(135deg, var(--primary), rgba(var(--primary-rgb),0.55));
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    font-size:17px;
    flex-shrink:0;
}
.session-card-body { flex:1; min-width:0; }
.session-card-name {
    font-weight:600;
    font-size:14px;
    color:var(--text);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}
.session-card-stats {
    display:flex;
    gap:14px;
    margin-top:4px;
    font-size:12px;
    color:var(--text-muted);
}
.session-card-stats span strong { color:var(--text); }

.activity-metadata-modal {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.7);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:10000;
    padding:20px;
}
.activity-metadata-modal.active { display:flex; }
.activity-metadata-modal .modal-card {
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:var(--radius-md);
    max-width:600px;
    width:100%;
    max-height:80vh;
    overflow:auto;
    padding:24px;
}
.activity-metadata-modal pre {
    background:var(--bg-input);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    padding:14px;
    font-size:12px;
    color:var(--text);
    overflow:auto;
    white-space:pre-wrap;
    word-break:break-word;
    max-height:400px;
}

.pagination-bar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-top:16px;
    padding:8px 4px;
}
.pagination-bar .page-info { color:var(--text-muted); font-size:13px; }
.pagination-bar .page-nav { display:flex; gap:8px; }
.pagination-bar .page-nav a,
.pagination-bar .page-nav span {
    padding:6px 12px;
    border-radius:6px;
    font-size:13px;
    text-decoration:none;
    color:var(--text-muted);
    background:var(--bg-card);
    border:1px solid var(--border);
}
.pagination-bar .page-nav a:hover { border-color:var(--primary); color:var(--primary); }
.pagination-bar .page-nav .disabled { opacity:0.4; pointer-events:none; }
</style>

<!-- Section Header -->
<div class="section-header">
    <h3 class="section-title" style="display:flex;align-items:center">
        <i class="fas fa-history" style="margin-right:10px;color:var(--primary)"></i>
        Activity Log
        <span class="badge badge-scheduled" style="margin-left:8px;font-size:12px;vertical-align:middle"><?= number_format($totalCount) ?></span>
    </h3>
</div>

<!-- Session Summary -->
<?php if (!empty($sessions)): ?>
<div class="card mb-3">
    <div style="margin-bottom:12px">
        <div style="font-size:13px;font-weight:600;color:var(--text)">User Activity Summary</div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
            Approximate active time by user since <?= date('M j', strtotime($summaryStart)) ?>.
            Session durations clamped at 4 hours to avoid inflation from idle browsers.
        </div>
    </div>
    <div class="session-summary-grid">
        <?php foreach ($sessions as $s):
            $hours = floor($s['active_seconds'] / 3600);
            $mins = floor(($s['active_seconds'] % 3600) / 60);
            $timeLabel = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
            $initial = strtoupper(substr($s['user_name'] ?? 'U', 0, 1));
        ?>
        <div class="session-card">
            <div class="session-card-avatar"><?= htmlspecialchars($initial) ?></div>
            <div class="session-card-body">
                <div class="session-card-name">
                    <?= htmlspecialchars($s['user_name'] ?? 'Unknown') ?>
                    <?php if (!empty($s['user_role'])): ?>
                        <span class="activity-role-pill activity-role-<?= htmlspecialchars($s['user_role']) ?>"><?= htmlspecialchars($s['user_role']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="session-card-stats">
                    <span><i class="fas fa-clock" style="margin-right:3px"></i><strong><?= $timeLabel ?></strong></span>
                    <span><i class="fas fa-sign-in-alt" style="margin-right:3px"></i><strong><?= (int)$s['login_count'] ?></strong> logins</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Filter Form -->
<div class="card mb-3">
    <form method="get" action="<?= BASE_URL ?>/settings/activity-log">
        <div class="activity-filter-bar">
            <div class="form-group">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All users</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?= (int)$u['user_id'] ?>" <?= (string)($filters['user_id'] ?? '') === (string)$u['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['user_name'] ?? 'User #' . $u['user_id']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All actions</option>
                    <?php foreach ($actionsUsed as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>>
                            <?= htmlspecialchars(str_replace('_', ' ', $a)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-input" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-input" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
            </div>
            <div class="form-group" style="flex:1;min-width:200px">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-input" placeholder="Search description or user..." value="<?= htmlspecialchars($filters['q'] ?? '') ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
            </div>
            <?php if (!empty($filters)): ?>
            <div class="form-group">
                <a href="<?= BASE_URL ?>/settings/activity-log" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Clear</a>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Events Table -->
<?php if (empty($events)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 24px">
            <i class="fas fa-history" style="font-size:44px;color:var(--text-muted);opacity:0.35"></i>
            <p style="font-size:15px;font-weight:600;margin-top:14px;color:var(--text)">No activity found</p>
            <p style="color:var(--text-muted);font-size:12px;max-width:320px;margin:4px auto 0">
                <?= !empty($filters) ? 'No events match the current filters. Try clearing them.' : 'User activity will appear here once people start using the system.' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:160px">When</th>
                        <th style="width:180px">User</th>
                        <th style="width:160px">Action</th>
                        <th>Description</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $ev): ?>
                    <tr>
                        <td>
                            <div class="text-small" style="color:var(--text)"><?= date('M j, g:ia', strtotime($ev['created_at'])) ?></div>
                            <div class="text-muted text-small" style="font-size:11px"><?= date('Y', strtotime($ev['created_at'])) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:500;font-size:13px">
                                <?= htmlspecialchars($ev['user_name'] ?? '—') ?>
                                <?php if (!empty($ev['user_role'])): ?>
                                    <span class="activity-role-pill activity-role-<?= htmlspecialchars($ev['user_role']) ?>"><?= htmlspecialchars($ev['user_role']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($ev['ip_address'])): ?>
                                <div class="text-muted" style="font-size:10px;font-family:monospace"><?= htmlspecialchars($ev['ip_address']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="activity-action-badge activity-action-<?= htmlspecialchars($ev['action']) ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', $ev['action'])) ?>
                            </span>
                        </td>
                        <td style="font-size:13px;color:var(--text)">
                            <?= htmlspecialchars($ev['description'] ?? '') ?>
                        </td>
                        <td>
                            <?php if (!empty($ev['metadata']) && $ev['metadata'] !== 'null'): ?>
                                <button class="activity-details-btn" onclick='showActivityMeta(<?= htmlspecialchars(json_encode($ev['metadata']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ev['ip_address'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($ev['user_agent'] ?? ''), ENT_QUOTES) ?>)'>
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1):
        $queryParams = $_GET;
        unset($queryParams['page']);
        $baseQuery = http_build_query($queryParams);
        $prefix = $baseQuery ? '?' . $baseQuery . '&' : '?';
    ?>
    <div class="pagination-bar">
        <div class="page-info">
            Showing <?= (($page - 1) * $perPage) + 1 ?>–<?= min($page * $perPage, $totalCount) ?> of <?= number_format($totalCount) ?>
        </div>
        <div class="page-nav">
            <?php if ($page > 1): ?>
                <a href="<?= BASE_URL ?>/settings/activity-log<?= $prefix ?>page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i> Prev</span>
            <?php endif; ?>
            <span style="padding:6px 12px;color:var(--text)">Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="<?= BASE_URL ?>/settings/activity-log<?= $prefix ?>page=<?= $page + 1 ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
                <span class="disabled">Next <i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Metadata Details Modal -->
<div class="activity-metadata-modal" id="activityMetaModal" onclick="if(event.target===this)closeActivityMeta()">
    <div class="modal-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="margin:0;font-size:16px;color:var(--text)">Event Details</h3>
            <button onclick="closeActivityMeta()" style="background:none;border:none;color:var(--text-muted);font-size:20px;cursor:pointer">&times;</button>
        </div>
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.05em">Metadata</div>
        <pre id="activityMetaBody">{}</pre>
        <div id="activityMetaTech" style="margin-top:14px;font-size:11px;color:var(--text-muted)"></div>
    </div>
</div>

<script>
function showActivityMeta(metadata, ip, ua) {
    var pre = document.getElementById('activityMetaBody');
    var tech = document.getElementById('activityMetaTech');
    try {
        var parsed = typeof metadata === 'string' ? JSON.parse(metadata) : metadata;
        pre.textContent = JSON.stringify(parsed, null, 2);
    } catch (e) {
        pre.textContent = String(metadata);
    }
    var techHtml = '';
    if (ip) techHtml += '<div><strong>IP:</strong> ' + escapeHtml(ip) + '</div>';
    if (ua) techHtml += '<div style="margin-top:4px;word-break:break-all"><strong>User Agent:</strong> ' + escapeHtml(ua) + '</div>';
    tech.innerHTML = techHtml;
    document.getElementById('activityMetaModal').classList.add('active');
}
function closeActivityMeta() {
    document.getElementById('activityMetaModal').classList.remove('active');
}
function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeActivityMeta();
});
</script>
