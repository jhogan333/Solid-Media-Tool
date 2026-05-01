<?php
$csrfToken = $_SESSION['csrf_token'] ?? '';
if (empty($csrfToken)) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); $csrfToken = $_SESSION['csrf_token']; }
?>
<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">
<style>
.filter-bar { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; margin-bottom:24px; }
.filter-bar .form-group { margin-bottom:0; min-width:140px; }
.filter-bar .form-label { margin-bottom:4px; }
.platform-bars { display:flex; flex-direction:column; gap:12px; }
.platform-bar-row { display:flex; align-items:center; gap:12px; }
.platform-bar-label { min-width:90px; font-size:13px; font-weight:600; color:var(--text); text-transform:capitalize; }
.platform-bar-track { flex:1; height:28px; background:var(--bg-input); border-radius:100px; overflow:hidden; position:relative; }
.platform-bar-fill { height:100%; border-radius:100px; display:flex; align-items:center; padding:0 10px; font-size:11px; font-weight:700; color:#fff; min-width:32px; transition:width 0.4s ease; }
/* Platform bars — brand-color variants so the chart blends into the rest
   of the interface. Facebook/Twitter use a lighter fill; LinkedIn/Instagram
   use the full brand color so the two platforms remain distinguishable. */
.platform-bar-fill.bar-facebook  { background:linear-gradient(90deg, rgba(var(--primary-rgb),0.55), rgba(var(--primary-rgb),0.75)); color:#fff; }
.platform-bar-fill.bar-twitter   { background:linear-gradient(90deg, rgba(var(--primary-rgb),0.55), rgba(var(--primary-rgb),0.75)); color:#fff; }
.platform-bar-fill.bar-linkedin  { background:linear-gradient(90deg, var(--primary), color-mix(in srgb, var(--primary) 70%, #000)); color:#fff; }
.platform-bar-fill.bar-instagram { background:linear-gradient(90deg, var(--primary), color-mix(in srgb, var(--primary) 70%, #000)); color:#fff; }
.platform-bar-fill.bar-all       { background:var(--primary); color:#fff; }
.platform-bar-count { min-width:36px; text-align:right; font-size:13px; font-weight:700; color:var(--text); }
.stat-clickable { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.stat-clickable:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
.stat-clickable.stat-active { ring: 2px; box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.35), 0 6px 20px rgba(0,0,0,0.1); transform: translateY(-2px); }
</style>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card stat-primary stat-clickable" data-filter-status="" role="button" tabindex="0" aria-label="Filter: All posts" style="cursor:pointer">
        <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
        <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
        <div class="stat-label">Total Posts</div>
    </div>
    <div class="stat-card stat-info stat-clickable" data-filter-status="scheduled" role="button" tabindex="0" aria-label="Filter: Scheduled posts" style="cursor:pointer">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $stats['scheduled'] ?? 0 ?></div>
        <div class="stat-label">Scheduled</div>
    </div>
    <div class="stat-card stat-success stat-clickable" data-filter-status="published" role="button" tabindex="0" aria-label="Filter: Published posts" style="cursor:pointer">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $stats['published'] ?? 0 ?></div>
        <div class="stat-label">Published</div>
    </div>
    <div class="stat-card stat-warning stat-clickable" data-filter-status="draft" role="button" tabindex="0" aria-label="Filter: Draft posts" style="cursor:pointer">
        <div class="stat-icon"><i class="fas fa-pen-fancy"></i></div>
        <div class="stat-value"><?= $stats['draft'] ?? 0 ?></div>
        <div class="stat-label">Drafts</div>
    </div>
    <?php if (($stats['failed'] ?? 0) > 0): ?>
    <div class="stat-card stat-danger stat-clickable" data-filter-status="failed" role="button" tabindex="0" aria-label="Filter: Failed posts" style="cursor:pointer">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-value"><?= $stats['failed'] ?></div>
        <div class="stat-label">Failed</div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================
     Cost Savings Callout + Generate Report CTA
     ============================================ -->
<?php
// Compute savings based on published + scheduled count
$savingsBase = (int) (($stats['published'] ?? 0) + ($stats['scheduled'] ?? 0));
$savings = (new ReportSettingsService())->calculate($savingsBase, $reportSettings ?? null);
?>
<div class="card mb-3" id="savings-card" style="padding:28px 32px;background:linear-gradient(135deg,rgba(var(--primary-rgb),0.08) 0%,rgba(var(--primary-rgb),0.02) 100%);border:1px solid rgba(var(--primary-rgb),0.18);display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center">
    <div>
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">
            <i class="fas fa-piggy-bank" style="margin-right:6px"></i> Estimated Savings
        </div>
        <div style="display:flex;align-items:baseline;gap:14px;margin-bottom:6px;flex-wrap:wrap">
            <div style="font-size:38px;font-weight:900;letter-spacing:-0.02em;color:var(--text);line-height:1"><?= htmlspecialchars($savings['display_dollars']) ?></div>
            <div style="font-size:13px;color:var(--text-muted)"><strong style="color:var(--text)"><?= $savings['hours_saved'] ?> hours</strong> saved</div>
            <button type="button" onclick="openSavingsInfo()" title="How is this calculated?" style="background:none;border:none;color:var(--primary);cursor:pointer;font-size:14px;padding:0"><i class="fas fa-info-circle"></i></button>
        </div>
        <div style="font-size:13px;color:var(--text-muted);line-height:1.55">
            Based on <?= $savingsBase ?> published or scheduled post<?= $savingsBase === 1 ? '' : 's' ?> &times; <?= $savings['minutes_per_post'] ?> min each &times; <?= $savings['currency_symbol'] . number_format($savings['hourly_rate'], 2) ?>/hr social media manager rate.
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                &middot; <a href="#" onclick="openReportSettings();return false" style="color:var(--primary);font-weight:600;text-decoration:none">Edit math</a>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;min-width:200px">
        <button type="button" class="btn btn-primary btn-shine" onclick="openGenerateWizard()" style="padding:14px 26px;font-weight:700;white-space:nowrap">
            <i class="fas fa-file-lines"></i> Generate Report
        </button>
        <?php if (!empty($savedReports) && count($savedReports) > 0): ?>
        <button type="button" class="btn btn-ghost btn-sm" onclick="openSavedReportsModal()" style="padding:10px 18px;font-weight:600;white-space:nowrap;border:1px solid rgba(var(--primary-rgb),0.25);color:var(--primary)">
            <i class="fas fa-folder-open"></i> Saved Reports
            <span style="display:inline-block;min-width:22px;margin-left:4px;padding:1px 8px;border-radius:100px;background:rgba(var(--primary-rgb),0.15);font-size:11px;font-weight:800"><?= count($savedReports) ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($savedReports) && count($savedReports) > 0): ?>
<!-- Saved Reports lightbox — scrolling list, click a row to view -->
<div id="savedReportsModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="savedReportsModalTitle" aria-hidden="true" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.62);backdrop-filter:blur(8px);z-index:9998;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeSavedReportsModal()">
    <div class="modal-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;max-width:640px;width:100%;padding:0;overflow:hidden;box-shadow:0 28px 72px rgba(0,0,0,0.4);display:flex;flex-direction:column;max-height:80vh">
        <div style="padding:24px 32px;background:linear-gradient(165deg,var(--primary),color-mix(in srgb,var(--primary) 55%,#000));color:#fff;flex-shrink:0">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:14px">
                <div>
                    <h2 id="savedReportsModalTitle" style="margin:0 0 4px;font-size:20px;font-weight:800;letter-spacing:-0.01em">
                        <i class="fas fa-folder-open" style="margin-right:8px"></i>Saved Reports
                    </h2>
                    <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.78)"><?= count($savedReports) ?> report<?= count($savedReports) === 1 ? '' : 's' ?> available. Click any row to view.</p>
                </div>
                <button type="button" onclick="closeSavedReportsModal()" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:#fff;width:36px;height:36px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:all 0.2s ease" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <div class="saved-reports-scroll" style="overflow-y:auto;flex:1;padding:8px 0">
            <?php foreach ($savedReports as $r):
                $isShared = !empty($r['share_token']);
            ?>
            <a href="<?= BASE_URL ?>/reports/view/<?= (int)$r['id'] ?>" class="saved-report-row">
                <div class="saved-report-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="saved-report-info">
                    <div class="saved-report-title"><?= htmlspecialchars($r['title']) ?></div>
                    <div class="saved-report-meta">
                        <span><i class="fas fa-calendar" style="margin-right:4px;opacity:0.6"></i><?= date('M j', strtotime($r['date_range_start'])) ?> – <?= date('M j, Y', strtotime($r['date_range_end'])) ?></span>
                        <span><i class="fas fa-clock" style="margin-right:4px;opacity:0.6"></i><?= date('M j, g:ia', strtotime($r['created_at'])) ?></span>
                        <span><i class="fas fa-eye" style="margin-right:4px;opacity:0.6"></i><?= (int) ($r['view_count'] ?? 0) ?> view<?= (int) ($r['view_count'] ?? 0) === 1 ? '' : 's' ?></span>
                    </div>
                </div>
                <div class="saved-report-badge">
                    <?php if ($isShared): ?>
                        <span class="badge badge-published" style="font-size:10px;padding:3px 10px"><i class="fas fa-globe" style="margin-right:4px"></i>Public</span>
                    <?php else: ?>
                        <span class="badge badge-draft" style="font-size:10px;padding:3px 10px"><i class="fas fa-lock" style="margin-right:4px"></i>Private</span>
                    <?php endif; ?>
                </div>
                <div class="saved-report-chevron">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.saved-report-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    text-decoration: none;
    border-bottom: 1px solid var(--border-light);
    transition: all 0.2s ease;
    cursor: pointer;
}
.saved-report-row:last-child { border-bottom: none; }
.saved-report-row:hover {
    background: rgba(var(--primary-rgb), 0.06);
    padding-left: 28px;
}
.saved-report-row:hover .saved-report-icon {
    background: var(--primary);
    color: #fff;
    transform: scale(1.08);
}
.saved-report-row:hover .saved-report-chevron {
    color: var(--primary);
    transform: translateX(2px);
}
.saved-report-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    transition: all 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.12);
}
.saved-report-info {
    flex: 1;
    min-width: 0;
}
.saved-report-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.saved-report-meta {
    display: flex;
    gap: 14px;
    font-size: 11px;
    color: var(--text-muted);
    flex-wrap: wrap;
}
.saved-report-badge { flex-shrink: 0; }
.saved-report-chevron {
    color: var(--text-muted);
    font-size: 12px;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.saved-reports-scroll::-webkit-scrollbar { width: 8px; }
.saved-reports-scroll::-webkit-scrollbar-track { background: var(--bg); }
.saved-reports-scroll::-webkit-scrollbar-thumb { background: rgba(var(--primary-rgb), 0.25); border-radius: 4px; }
.saved-reports-scroll::-webkit-scrollbar-thumb:hover { background: rgba(var(--primary-rgb), 0.4); }

@media (max-width: 520px) {
    .saved-report-meta { flex-direction: column; gap: 2px; }
    .saved-report-badge { display: none; }
}
</style>

<script>
function openSavedReportsModal() {
    document.getElementById('savedReportsModal').style.display = 'flex';
}
function closeSavedReportsModal() {
    document.getElementById('savedReportsModal').style.display = 'none';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSavedReportsModal();
});
</script>
<?php endif; ?>

<!-- Savings info lightbox -->
<div id="savingsInfoModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="savingsInfoModalTitle" aria-hidden="true" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:9998;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeSavingsInfo()">
    <div class="modal-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:18px;max-width:480px;width:100%;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.3)">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(180deg,var(--primary),color-mix(in srgb,var(--primary) 65%,#000));display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;box-shadow:0 6px 18px rgba(var(--primary-rgb),0.35)"><i class="fas fa-calculator"></i></div>
            <h2 id="savingsInfoModalTitle" style="margin:0;font-size:20px;font-weight:800;color:var(--text)">How we calculate savings</h2>
        </div>
        <p style="font-size:14px;color:var(--text-muted);line-height:1.65;margin-bottom:18px">
            A typical social media manager spends around <strong style="color:var(--text)"><?= $savings['minutes_per_post'] ?> minutes</strong> producing a single polished branded post &mdash; research, copy writing, sourcing and branding an image, and uploading to each platform individually. At <strong style="color:var(--text)"><?= $savings['currency_symbol'] . number_format($savings['hourly_rate'], 2) ?>/hour</strong> (average US social media manager rate), each post you automate is worth roughly <strong style="color:var(--primary)"><?= $savings['display_per_post'] ?></strong> in labor.
        </p>
        <div style="background:var(--bg-input);border:1px solid var(--border);border-radius:12px;padding:18px 20px;font-size:13px;color:var(--text-muted);margin-bottom:18px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace">
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border)"><span>Posts counted</span><span style="color:var(--text);font-weight:700"><?= $savings['post_count'] ?></span></div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border)"><span>Minutes per post</span><span style="color:var(--text);font-weight:700"><?= $savings['minutes_per_post'] ?> min</span></div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border)"><span>Hourly rate</span><span style="color:var(--text);font-weight:700"><?= $savings['currency_symbol'] . number_format($savings['hourly_rate'], 2) ?></span></div>
            <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-weight:700;color:var(--text)"><span>Total value</span><span style="color:var(--primary);font-size:16px"><?= htmlspecialchars($savings['display_dollars']) ?></span></div>
        </div>
        <p style="font-size:12px;color:var(--text-muted);line-height:1.55;margin-bottom:22px;font-style:italic">
            The numbers are estimates. Actual labor savings depend on your manager's workflow, seniority, and market rate. <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>You can tune the minutes-per-post and hourly rate in Report Settings below.<?php endif; ?>
        </p>
        <div style="display:flex;justify-content:flex-end">
            <button type="button" onclick="closeSavingsInfo()" class="btn btn-primary">Got it</button>
        </div>
    </div>
</div>

<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
<!-- Report Settings lightbox (admin only) -->
<div id="reportSettingsModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="reportSettingsModalTitle" aria-hidden="true" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);z-index:9998;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeReportSettings()">
    <div class="modal-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:18px;max-width:460px;width:100%;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,0.3)">
        <h2 id="reportSettingsModalTitle" style="margin:0 0 6px;font-size:20px;font-weight:800;color:var(--text)">Report Settings</h2>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:22px;line-height:1.55">Tune the cost savings math to match your local market and workflow. Changes apply immediately to the Reports page and all future generated reports.</p>
        <div class="form-group">
            <label class="form-label">Minutes per post</label>
            <input type="number" id="rs-minutes" class="form-input" min="1" max="600" value="<?= (int) ($reportSettings['minutes_per_post'] ?? 30) ?>">
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Time a manager would typically spend on one polished branded post (research, copy, image, upload).</div>
        </div>
        <div class="form-group" style="margin-top:14px">
            <label class="form-label">Hourly rate (USD)</label>
            <input type="number" id="rs-rate" class="form-input" min="0" max="9999.99" step="0.01" value="<?= number_format((float) ($reportSettings['hourly_rate'] ?? 29), 2, '.', '') ?>">
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Average social media manager wage in your market.</div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
            <button type="button" onclick="closeReportSettings()" class="btn btn-ghost">Cancel</button>
            <button type="button" onclick="saveReportSettings()" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Generate Report Wizard lightbox -->
<div id="generateReportModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="generateReportModalTitle" aria-hidden="true" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.62);backdrop-filter:blur(8px);z-index:9998;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeGenerateWizard()">
    <div class="modal-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;max-width:540px;width:100%;padding:0;overflow:hidden;box-shadow:0 28px 72px rgba(0,0,0,0.4)">
        <div style="padding:24px 32px;background:linear-gradient(165deg,var(--primary),color-mix(in srgb,var(--primary) 55%,#000));color:#fff">
            <h2 id="generateReportModalTitle" style="margin:0 0 4px;font-size:20px;font-weight:800;letter-spacing:-0.01em">Generate Report</h2>
            <p style="margin:0;font-size:13px;color:rgba(255,255,255,0.8)">Build a branded performance report for any date range.</p>
        </div>
        <div id="generateReportBody" style="padding:28px 32px">
            <div class="form-group">
                <label class="form-label">Date range</label>
                <select id="gr-range" class="form-select">
                    <option value="last_7">Last 7 days</option>
                    <option value="last_30" selected>Last 30 days</option>
                    <option value="this_month">This month</option>
                    <option value="last_month">Last month</option>
                    <option value="custom">Custom…</option>
                </select>
            </div>
            <div id="gr-custom-dates" style="display:none;margin-top:12px">
                <div style="display:flex;gap:10px">
                    <div class="form-group" style="flex:1;margin-bottom:0">
                        <label class="form-label">From</label>
                        <input type="date" id="gr-start" class="form-input">
                    </div>
                    <div class="form-group" style="flex:1;margin-bottom:0">
                        <label class="form-label">To</label>
                        <input type="date" id="gr-end" class="form-input">
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label class="form-label">Report title</label>
                <input type="text" id="gr-title" class="form-input" placeholder="Leave blank for auto-generated title" maxlength="240">
            </div>
            <div class="form-group" style="margin-top:14px">
                <label class="form-label">Delivery</label>
                <select id="gr-delivery" class="form-select">
                    <option value="view" selected>View &amp; save as PDF</option>
                    <option value="email">Email to me or others</option>
                </select>
            </div>
            <div id="gr-email-wrap" class="form-group" style="display:none;margin-top:14px">
                <label class="form-label">Email to (comma-separated)</label>
                <input type="text" id="gr-email" class="form-input" placeholder="you@company.com, client@company.com">
            </div>

            <div id="gr-error" style="display:none;margin-top:16px;padding:12px 14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:10px;font-size:13px;color:#b91c1c"></div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
                <button type="button" onclick="closeGenerateWizard()" class="btn btn-ghost">Cancel</button>
                <button type="button" id="gr-submit-btn" onclick="submitGenerateReport()" class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Generate</button>
            </div>
        </div>
        <div id="generateReportLoading" style="display:none;padding:48px 32px;text-align:center">
            <div style="width:56px;height:56px;margin:0 auto 18px;border:4px solid rgba(var(--primary-rgb),0.18);border-top-color:var(--primary);border-radius:50%;animation:grSpin 0.9s linear infinite"></div>
            <div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">Building your report…</div>
            <div style="font-size:12px;color:var(--text-muted)">Fetching data, calling AI, assembling the summary. This usually takes 5–15 seconds.</div>
        </div>
    </div>
</div>
<style>@keyframes grSpin { to { transform: rotate(360deg); } }</style>

<script>
function openSavingsInfo()  { document.getElementById('savingsInfoModal').style.display = 'flex'; }
function closeSavingsInfo() { document.getElementById('savingsInfoModal').style.display = 'none'; }

<?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
function openReportSettings()  { document.getElementById('reportSettingsModal').style.display = 'flex'; }
function closeReportSettings() { document.getElementById('reportSettingsModal').style.display = 'none'; }
function saveReportSettings() {
    var minutes = parseInt(document.getElementById('rs-minutes').value, 10);
    var rate = parseFloat(document.getElementById('rs-rate').value);
    if (!minutes || minutes < 1 || !rate || rate < 0) {
        alert('Please enter valid numbers.');
        return;
    }
    fetch('<?= BASE_URL ?>/reports/settings/save', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            csrf_token: document.getElementById('csrf-token').value,
            minutes_per_post: minutes,
            hourly_rate: rate
        })
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.success) { location.reload(); }
        else { alert(d.error || 'Save failed.'); }
    });
}
<?php endif; ?>

function openGenerateWizard() {
    document.getElementById('generateReportModal').style.display = 'flex';
    document.getElementById('generateReportBody').style.display = '';
    document.getElementById('generateReportLoading').style.display = 'none';
    document.getElementById('gr-error').style.display = 'none';
}
function closeGenerateWizard() {
    document.getElementById('generateReportModal').style.display = 'none';
}

document.getElementById('gr-range').addEventListener('change', function() {
    document.getElementById('gr-custom-dates').style.display = this.value === 'custom' ? '' : 'none';
});
document.getElementById('gr-delivery').addEventListener('change', function() {
    document.getElementById('gr-email-wrap').style.display = this.value === 'email' ? '' : 'none';
});

function submitGenerateReport() {
    var range    = document.getElementById('gr-range').value;
    var title    = document.getElementById('gr-title').value.trim();
    var delivery = document.getElementById('gr-delivery').value;
    var emailTo  = document.getElementById('gr-email').value.trim();
    var start    = document.getElementById('gr-start').value;
    var end      = document.getElementById('gr-end').value;
    var errEl    = document.getElementById('gr-error');
    errEl.style.display = 'none';

    if (range === 'custom') {
        if (!start || !end) {
            errEl.textContent = 'Please select both a start and end date.';
            errEl.style.display = '';
            return;
        }
        if (new Date(start) > new Date(end)) {
            errEl.textContent = 'Start date must be before end date.';
            errEl.style.display = '';
            return;
        }
    }
    if (delivery === 'email' && !emailTo) {
        errEl.textContent = 'Please enter at least one email address.';
        errEl.style.display = '';
        return;
    }

    document.getElementById('generateReportBody').style.display = 'none';
    document.getElementById('generateReportLoading').style.display = '';

    fetch('<?= BASE_URL ?>/reports/generate', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            csrf_token: document.getElementById('csrf-token').value,
            range:    range,
            title:    title,
            delivery: delivery,
            email_to: emailTo,
            start_date: start,
            end_date:   end
        })
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.error) {
            document.getElementById('generateReportBody').style.display = '';
            document.getElementById('generateReportLoading').style.display = 'none';
            errEl.textContent = d.error;
            errEl.style.display = '';
            return;
        }
        if (d.delivery === 'email') {
            // Show a success state then close
            document.getElementById('generateReportLoading').innerHTML =
                '<div style="font-size:42px;color:var(--success);margin-bottom:12px"><i class="fas fa-check-circle"></i></div>' +
                '<div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px">Report emailed!</div>' +
                '<div style="font-size:12px;color:var(--text-muted);margin-bottom:20px">Delivered to ' + (d.email && d.email.sent_to ? d.email.sent_to.join(', ') : 'recipients') + '</div>' +
                '<button type="button" onclick="closeGenerateWizard();location.reload()" class="btn btn-primary">Done</button>';
            return;
        }
        // View mode — navigate to the report page
        window.location.href = d.view_url;
    }).catch(function(err) {
        document.getElementById('generateReportBody').style.display = '';
        document.getElementById('generateReportLoading').style.display = 'none';
        errEl.textContent = 'Network error: ' + err.message;
        errEl.style.display = '';
    });
}

// Close any modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSavingsInfo();
        closeGenerateWizard();
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>closeReportSettings();<?php endif; ?>
    }
});
</script>

<!-- Failed Posts -->
<?php if (!empty($failedPosts)): ?>
<div class="section-header" id="failed-posts">
    <h3 class="section-title" style="color:var(--danger)"><i class="fas fa-exclamation-triangle" style="margin-right:8px"></i>Failed Posts</h3>
</div>
<div class="card" style="padding:0;overflow:hidden;border:1px solid rgba(239,68,68,0.2);margin-bottom:24px">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Platform</th>
                    <th>Error</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($failedPosts as $fp): ?>
                <tr style="cursor:pointer" onclick="if(!event.target.closest('button'))window.location.href='<?= BASE_URL ?>/posts/edit/<?= (int)$fp['id'] ?>'" id="failed-row-<?= (int)$fp['id'] ?>">
                    <td>
                        <div style="font-weight:600;color:var(--text);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($fp['title']) ?>">
                            <?= htmlspecialchars($fp['title']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= htmlspecialchars($fp['failed_platform'] ?? $fp['platform'] ?? 'draft') ?>">
                            <?= ucfirst(htmlspecialchars($fp['failed_platform'] ?? $fp['platform'] ?? 'Unknown')) ?>
                        </span>
                    </td>
                    <td>
                        <span class="text-small" style="color:var(--danger);max-width:300px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($fp['error_message'] ?? 'Unknown error') ?>">
                            <?= htmlspecialchars($fp['error_message'] ?? 'Unknown error') ?>
                        </span>
                    </td>
                    <td>
                        <span class="text-small text-muted">
                            <?= !empty($fp['failed_at']) ? date('M j, g:ia', strtotime($fp['failed_at'])) : (!empty($fp['created_at']) ? date('M j, g:ia', strtotime($fp['created_at'])) : '—') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center">
                            <a href="<?= BASE_URL ?>/posts/edit/<?= (int)$fp['id'] ?>" class="btn btn-ghost btn-sm" style="white-space:nowrap" onclick="event.stopPropagation()">
                                <i class="fas fa-redo"></i> Retry
                            </a>
                            <button class="btn btn-ghost btn-sm btn-icon" title="Delete" onclick="event.stopPropagation();deleteFailedPost(<?= (int)$fp['id'] ?>, this)" style="color:var(--danger)">
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
<?php endif; ?>

<!-- Filter Bar -->
<div class="section-header" id="posts-section">
    <h3 class="section-title">Posts</h3>
</div>
<div class="card mb-3">
    <div class="filter-bar" id="filter-bar">
        <div class="form-group">
            <label class="form-label">From</label>
            <input type="date" class="form-input" id="filter-date-from">
        </div>
        <div class="form-group">
            <label class="form-label">To</label>
            <input type="date" class="form-input" id="filter-date-to">
        </div>
        <div class="form-group">
            <label class="form-label">Platform</label>
            <select class="form-select" id="filter-platform">
                <option value="">All Platforms</option>
                <option value="facebook">Facebook</option>
                <option value="linkedin">LinkedIn</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-select" id="filter-status">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="scheduled">Scheduled</option>
                <option value="published">Published</option>
                <option value="failed">Failed</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Type</label>
            <select class="form-select" id="filter-type">
                <option value="">All Types</option>
                <option value="image">Image</option>
                <option value="video">Video</option>
                <option value="carousel">Carousel</option>
                <option value="story">Story</option>
                <option value="reel">Reel</option>
                <option value="text">Text</option>
            </select>
        </div>
        <div class="form-group">
            <button class="btn btn-primary btn-sm" id="apply-filters"><i class="fas fa-filter"></i> Apply</button>
        </div>
        <div class="form-group" style="margin-left:auto">
            <a href="<?= BASE_URL ?>/reporting/export-csv" class="btn btn-ghost btn-sm"><i class="fas fa-download"></i> Download CSV</a>
        </div>
    </div>
</div>

<!-- Posts Table -->
<?php if (empty($posts)): ?>
    <div class="card">
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <p>No posts to report on yet. Start creating content to see your reports.</p>
            <a href="<?= BASE_URL ?>/generator" class="btn btn-primary"><i class="fas fa-magic"></i> Generate Content</a>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div class="table-wrapper">
            <table id="report-table" data-row-thumb="true">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Platform</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Topic</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post):
                        $rptPlatforms = [];
                        if (!empty($post['platforms'])) {
                            $decoded = json_decode($post['platforms'], true);
                            if (is_array($decoded)) $rptPlatforms = $decoded;
                        }
                        if (empty($rptPlatforms)) $rptPlatforms = [$post['platform'] ?? 'facebook'];
                    ?>
                    <tr class="clickable-row"
                        data-post-id="<?= (int)$post['id'] ?>"
                        data-platform="<?= htmlspecialchars(implode(',', $rptPlatforms)) ?>"
                        data-status="<?= htmlspecialchars($post['status'] ?? '') ?>"
                        data-type="<?= htmlspecialchars($post['post_type'] ?? '') ?>"
                        data-scheduled="<?= htmlspecialchars($post['scheduled_at'] ?? '') ?>"
                        data-topic="<?= htmlspecialchars($post['topic'] ?? '') ?>"
                        data-image-url="<?= htmlspecialchars($post['image_url'] ?? '') ?>"
                        data-title="<?= htmlspecialchars($post['title'] ?? '') ?>">
                        <td>
                            <div style="font-weight:600;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= htmlspecialchars($post['title']) ?>
                            </div>
                        </td>
                        <td>
                            <?php foreach ($rptPlatforms as $rp): ?>
                                <span class="badge badge-<?= htmlspecialchars($rp) ?>"><?= ucfirst(htmlspecialchars($rp)) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><span class="text-small" style="text-transform:capitalize"><?= str_replace('_', ' ', $post['post_type'] ?? '') ?></span></td>
                        <td><span class="badge badge-<?= $post['status'] ?>"><?= ucfirst($post['status']) ?></span></td>
                        <td>
                            <?php if (!empty($post['scheduled_at'])): ?>
                                <span class="text-small"><?= date('M j, g:ia', strtotime($post['scheduled_at'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted text-small">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($post['topic'])): ?>
                                <span class="text-small"><?= htmlspecialchars($post['topic']) ?></span>
                            <?php else: ?>
                                <span class="text-muted text-small">&mdash;</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Topic Distribution -->
<?php if (!empty($topicDist)): ?>
<div class="section-header mt-3">
    <h3 class="section-title">Topics</h3>
</div>
<div class="card">
    <div class="flex gap-2" style="flex-wrap:wrap">
        <?php foreach ($topicDist as $topic): ?>
            <div style="background:var(--bg-input);border-radius:100px;padding:6px 14px;font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px">
                <?= htmlspecialchars($topic['topic']) ?>
                <span style="background:rgba(var(--primary-rgb),0.15);color:var(--primary);border-radius:100px;padding:1px 8px;font-size:11px;font-weight:700"><?= $topic['count'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Platform Breakdown -->
<?php if (!empty($platformDist)): ?>
<div class="section-header mt-3">
    <h3 class="section-title">Platform Breakdown</h3>
</div>
<div class="card">
    <div class="platform-bars">
        <?php
            $maxCount = max(array_column($platformDist, 'count'));
            foreach ($platformDist as $plat):
                $pct = $maxCount > 0 ? round(($plat['count'] / $maxCount) * 100) : 0;
        ?>
        <div class="platform-bar-row">
            <div class="platform-bar-label"><?= ucfirst(htmlspecialchars($plat['platform'])) ?></div>
            <div class="platform-bar-track">
                <div class="platform-bar-fill bar-<?= htmlspecialchars($plat['platform']) ?>" style="width:<?= max($pct, 5) ?>%">
                    <?= $plat['count'] ?>
                </div>
            </div>
            <div class="platform-bar-count"><?= $plat['count'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    var applyBtn = document.getElementById('apply-filters');
    if (!applyBtn) return;

    applyBtn.addEventListener('click', function() {
        var platform = document.getElementById('filter-platform').value;
        var status = document.getElementById('filter-status').value;
        var postType = document.getElementById('filter-type').value;
        var dateFrom = document.getElementById('filter-date-from').value;
        var dateTo = document.getElementById('filter-date-to').value;

        var table = document.getElementById('report-table');
        if (!table) return;
        var rows = table.querySelectorAll('tbody tr');
        var visible = 0;

        rows.forEach(function(row) {
            var show = true;

            if (platform && !row.getAttribute('data-platform').split(',').includes(platform)) show = false;
            if (status && row.getAttribute('data-status') !== status) show = false;
            if (postType && row.getAttribute('data-type') !== postType) show = false;

            if (dateFrom || dateTo) {
                var scheduled = row.getAttribute('data-scheduled');
                if (!scheduled) {
                    show = false;
                } else {
                    var d = scheduled.substring(0, 10);
                    if (dateFrom && d < dateFrom) show = false;
                    if (dateTo && d > dateTo) show = false;
                }
            }

            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (visible === 0) {
            showToast('No posts match the selected filters.', 'info');
        } else {
            showToast(visible + ' post' + (visible !== 1 ? 's' : '') + ' found.', 'success');
        }
    });
})();

// Handle hash-based navigation (e.g. #failed-posts)
(function() {
    var hash = window.location.hash;
    if (hash === '#failed-posts') {
        var failedCard = document.querySelector('.stat-clickable[data-filter-status="failed"]');
        if (failedCard) {
            setTimeout(function() { failedCard.click(); }, 100);
        }
    }
})();

// Stat card click → filter + smooth scroll
document.querySelectorAll('.stat-clickable').forEach(function(card) {
    card.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
    });
    card.addEventListener('click', function() {
        var status = this.getAttribute('data-filter-status');
        var statusSelect = document.getElementById('filter-status');
        if (statusSelect) statusSelect.value = status;

        // Clear other filters for a clean view
        var platformSelect = document.getElementById('filter-platform');
        var typeSelect = document.getElementById('filter-type');
        var dateFrom = document.getElementById('filter-date-from');
        var dateTo = document.getElementById('filter-date-to');
        if (platformSelect) platformSelect.value = '';
        if (typeSelect) typeSelect.value = '';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        // Apply filter
        var applyBtn = document.getElementById('apply-filters');
        if (applyBtn) applyBtn.click();

        // Highlight active card
        document.querySelectorAll('.stat-clickable').forEach(function(c) { c.classList.remove('stat-active'); });
        this.classList.add('stat-active');

        // Smooth scroll to the "Posts" heading with a small offset
        var target = document.getElementById('posts-section');
        if (target) {
            var y = target.getBoundingClientRect().top + window.pageYOffset - 20;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    });
});

function deleteFailedPost(id, btnEl) {
    confirmModal('Delete Failed Post', 'Are you sure you want to delete this post? This cannot be undone.', async function() {
        try {
            var formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);

            var res = await fetch('<?= BASE_URL ?>/posts/delete/' + id, {
                method: 'POST',
                body: formData
            });
            var data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Delete failed');

            var row = document.getElementById('failed-row-' + id);
            if (row) {
                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(function() { row.remove(); }, 300);
            }
            showToast('Post deleted.', 'success');
        } catch (err) {
            showToast(err.message, 'error');
        }
    });
}

// Row click → open post editor (ignore clicks on links, buttons, badges with their own handler)
(function() {
    var table = document.getElementById('report-table');
    if (!table) return;
    table.addEventListener('click', function(ev) {
        var target = ev.target;
        if (target.closest('a, button, input, select, textarea, label')) return;
        var row = target.closest('tr.clickable-row');
        if (!row) return;
        var id = row.getAttribute('data-post-id');
        if (id) window.location.href = '<?= rtrim(BASE_URL, '/') ?>/posts/edit/' + id;
    });
})();

// Row image thumbnail tooltip — slick corporate card that follows row hover.
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

<!-- ============================================
     Saved Reports library
     ============================================ -->
<?php if (!empty($savedReports)): ?>
<div class="section-header mt-3" id="saved-reports">
    <h3 class="section-title"><i class="fas fa-folder-open" style="margin-right:10px;color:var(--primary)"></i>Saved Reports</h3>
</div>
<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Period</th>
                    <th>Created</th>
                    <th>Views</th>
                    <th>Share</th>
                    <th style="width:180px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($savedReports as $r):
                    $isShared = !empty($r['share_token']);
                ?>
                <tr data-report-id="<?= (int)$r['id'] ?>">
                    <td>
                        <a href="<?= BASE_URL ?>/reports/view/<?= (int)$r['id'] ?>" style="font-weight:600;color:var(--text);text-decoration:none">
                            <?= htmlspecialchars($r['title']) ?>
                        </a>
                    </td>
                    <td class="text-small text-muted">
                        <?= date('M j', strtotime($r['date_range_start'])) ?> – <?= date('M j, Y', strtotime($r['date_range_end'])) ?>
                    </td>
                    <td class="text-small text-muted">
                        <?= date('M j, Y g:ia', strtotime($r['created_at'])) ?>
                    </td>
                    <td class="text-small">
                        <?= (int) ($r['view_count'] ?? 0) ?>
                    </td>
                    <td>
                        <span class="share-status" data-shared="<?= $isShared ? '1' : '0' ?>">
                            <?php if ($isShared): ?>
                                <span class="badge badge-published"><i class="fas fa-link" style="margin-right:4px"></i>Public</span>
                            <?php else: ?>
                                <span class="badge badge-draft"><i class="fas fa-lock" style="margin-right:4px"></i>Private</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>/reports/view/<?= (int)$r['id'] ?>" class="btn btn-ghost btn-sm" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleShareReport(<?= (int)$r['id'] ?>, this)" title="<?= $isShared ? 'Manage share link' : 'Create share link' ?>">
                            <i class="fas fa-share-nodes"></i>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="deleteSavedReport(<?= (int)$r['id'] ?>, this)" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Share link modal -->
<div id="shareReportModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="shareReportModalTitle" aria-hidden="true" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.62);backdrop-filter:blur(8px);z-index:9998;align-items:center;justify-content:center;padding:20px" onclick="if(event.target===this)closeShareReport()">
    <div class="modal-card" style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;max-width:540px;width:100%;padding:32px;box-shadow:0 28px 72px rgba(0,0,0,0.4)">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
            <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(180deg,var(--primary),color-mix(in srgb,var(--primary) 65%,#000));display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;box-shadow:0 6px 18px rgba(var(--primary-rgb),0.35)"><i class="fas fa-share-nodes"></i></div>
            <div>
                <h2 id="shareReportModalTitle" style="margin:0;font-size:20px;font-weight:800;color:var(--text)">Share report</h2>
                <p style="margin:2px 0 0;font-size:13px;color:var(--text-muted)">Anyone with this link can view the report.</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:20px;margin-bottom:16px">
            <input type="text" id="shareUrlInput" class="form-input" readonly style="flex:1;font-family:ui-monospace,SFMono-Regular,monospace;font-size:13px">
            <button type="button" id="copyShareBtn" class="btn btn-primary" onclick="copyShareUrl()"><i class="fas fa-copy"></i> Copy</button>
        </div>
        <div id="shareCopiedHint" style="display:none;font-size:12px;color:var(--success);margin-bottom:14px"><i class="fas fa-check"></i> Copied to clipboard</div>
        <div style="padding:14px 18px;background:rgba(var(--primary-rgb),0.05);border:1px solid rgba(var(--primary-rgb),0.15);border-radius:10px;font-size:12px;color:var(--text-muted);line-height:1.6;margin-bottom:20px">
            <i class="fas fa-info-circle" style="color:var(--primary);margin-right:6px"></i>
            This is a public URL. Anyone you send it to can see the full report without signing in. You can revoke access anytime.
        </div>
        <div style="display:flex;gap:10px;justify-content:space-between">
            <button type="button" onclick="unshareReport()" class="btn btn-ghost" style="color:var(--danger)"><i class="fas fa-link-slash"></i> Revoke link</button>
            <button type="button" onclick="closeShareReport()" class="btn btn-primary">Done</button>
        </div>
    </div>
</div>

<script>
var currentShareReportId = null;

function toggleShareReport(id, btn) {
    currentShareReportId = id;
    var row = btn.closest('tr');
    var statusEl = row.querySelector('.share-status');
    var isShared = statusEl.getAttribute('data-shared') === '1';

    if (isShared) {
        // Already shared — fetch the token and show the modal directly
        // We don't have the token client-side, so we call /share which
        // returns the existing one (or mints a new one if missing).
        callShareEndpoint(id);
    } else {
        callShareEndpoint(id);
    }
}

function callShareEndpoint(id) {
    fetch('<?= BASE_URL ?>/reports/share/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: document.getElementById('csrf-token').value})
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.error) { alert(d.error); return; }
        document.getElementById('shareUrlInput').value = d.public_url;
        document.getElementById('shareReportModal').style.display = 'flex';
        document.getElementById('shareCopiedHint').style.display = 'none';
        // Update the row badge
        updateShareBadge(id, true);
    }).catch(function(err) { alert('Failed: ' + err.message); });
}

function closeShareReport() {
    document.getElementById('shareReportModal').style.display = 'none';
    currentShareReportId = null;
}

function copyShareUrl() {
    var input = document.getElementById('shareUrlInput');
    input.select();
    input.setSelectionRange(0, 99999);
    try {
        navigator.clipboard.writeText(input.value).then(function() {
            document.getElementById('shareCopiedHint').style.display = '';
        });
    } catch (e) {
        document.execCommand('copy');
        document.getElementById('shareCopiedHint').style.display = '';
    }
}

function unshareReport() {
    if (!currentShareReportId) return;
    if (!confirm('Revoke this share link? Anyone with the current link will no longer be able to view the report.')) return;
    fetch('<?= BASE_URL ?>/reports/unshare/' + currentShareReportId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: document.getElementById('csrf-token').value})
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.success) {
            updateShareBadge(currentShareReportId, false);
            closeShareReport();
        } else {
            alert(d.error || 'Revoke failed.');
        }
    });
}

function updateShareBadge(id, shared) {
    var row = document.querySelector('tr[data-report-id="' + id + '"]');
    if (!row) return;
    var statusEl = row.querySelector('.share-status');
    statusEl.setAttribute('data-shared', shared ? '1' : '0');
    statusEl.innerHTML = shared
        ? '<span class="badge badge-published"><i class="fas fa-link" style="margin-right:4px"></i>Public</span>'
        : '<span class="badge badge-draft"><i class="fas fa-lock" style="margin-right:4px"></i>Private</span>';
}

function deleteSavedReport(id, btn) {
    if (!confirm('Delete this saved report? This cannot be undone.')) return;
    fetch('<?= BASE_URL ?>/reports/delete/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: document.getElementById('csrf-token').value})
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.success) {
            var row = btn.closest('tr');
            row.style.transition = 'opacity 0.2s ease';
            row.style.opacity = '0';
            setTimeout(function() { row.remove(); }, 200);
        } else {
            alert(d.error || 'Delete failed.');
        }
    });
}
</script>
<?php endif; ?>
