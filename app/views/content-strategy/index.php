<?php
$themes = $themes ?? [];
$schedule = $schedule ?? [];
$b = $branding ?? (new BrandingService())->get($GLOBALS['client_id']);
$criPrimary = $b['primary_color'] ?? '#6366f1';
$criLogo = $b['logo_url'] ?? '';
$criCompany = $b['company_name'] ?? APP_NAME;
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<style>
.theme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.theme-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-left: 4px solid var(--primary);
    border-radius: var(--radius-lg);
    padding: 22px 22px 20px 24px;
    transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.theme-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.03) 0%, transparent 60%);
    pointer-events: none;
}
.theme-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), rgba(var(--primary-rgb), 0.2), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.theme-card:hover {
    box-shadow: 0 12px 32px rgba(var(--primary-rgb), 0.14), 0 2px 8px rgba(0,0,0,0.06);
    border-left-color: var(--primary);
    transform: translateY(-3px);
}
.theme-card:hover::after { opacity: 1; }
.theme-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12px;
    position: relative;
}
.theme-card-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}
.theme-card-name::before {
    content: '\f5fd';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    font-size: 12px;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary);
    flex-shrink: 0;
}
.theme-card-desc {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 14px;
    position: relative;
}
.theme-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
    position: relative;
}
.theme-badge {
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary);
    border: 1px solid rgba(var(--primary-rgb), 0.15);
}
.theme-badge-muted {
    background: var(--bg-input);
    color: var(--text-muted);
    border-color: transparent;
}
.theme-card-actions {
    display: flex;
    gap: 8px;
    position: relative;
}
.theme-card-samples {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    position: relative;
}

.schedule-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 12px;
}
@media (max-width: 900px) {
    .schedule-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 500px) {
    .schedule-grid { grid-template-columns: 1fr 1fr; }
}
.schedule-day {
    text-align: center;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-top: 3px solid rgba(var(--primary-rgb), 0.2);
    border-radius: var(--radius-md);
    padding: 16px 12px;
    transition: all 0.2s ease;
}
.schedule-day:hover {
    border-top-color: var(--primary);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.08);
}
.schedule-day-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--primary);
    margin-bottom: 10px;
}
.schedule-day select {
    width: 100%;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-input);
    color: var(--text);
    font-size: 12px;
    font-family: inherit;
}

/* Modal Form */
.theme-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
}
.theme-modal-overlay.visible {
    opacity: 1;
    visibility: visible;
}
.theme-modal {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    max-width: 640px;
    width: 92%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: var(--shadow-xl);
    transform: translateY(20px) scale(0.97);
    transition: transform 0.25s ease;
    padding: 28px;
}
.theme-modal-overlay.visible .theme-modal {
    transform: translateY(0) scale(1);
}
.theme-modal-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.theme-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: var(--bg-input);
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.theme-modal-close:hover { background: var(--danger); color: #fff; }

.sample-group {
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 14px;
    margin-bottom: 10px;
}
.sample-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.sample-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
}

.critique-panel {
    margin-top: 12px;
    padding: 16px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    display: none;
}
.critique-section {
    margin-bottom: 12px;
}
.critique-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
}
.critique-strengths { color: var(--success); }
.critique-suggestions { color: var(--warning); }
.critique-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.critique-list li {
    font-size: 13px;
    color: var(--text);
    padding: 4px 0 4px 16px;
    position: relative;
}
.critique-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 10px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
}
.critique-strengths .critique-list li::before { background: var(--success); }
.critique-suggestions .critique-list li::before { background: var(--warning); }
.critique-revised {
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 16px;
    font-size: 13px;
    line-height: 1.8;
    color: var(--text);
    margin-top: 8px;
    white-space: pre-line;
    font-family: inherit;
}

/* Analysis Lightbox */
.analysis-lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.65);
    backdrop-filter: blur(6px);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
.analysis-lightbox.visible {
    opacity: 1;
    visibility: visible;
}
.analysis-lightbox-content {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 48px 40px;
    border-radius: 24px;
    background: linear-gradient(165deg, <?= $criPrimary ?> 0%, color-mix(in srgb, <?= $criPrimary ?> 55%, #000000) 100%);
    box-shadow: 0 0 0 1px rgba(0,0,0,0.1), 0 24px 80px rgba(0,0,0,0.3), 0 0 120px -20px <?= $criPrimary ?>88;
    max-width: 400px;
    width: 85%;
    overflow: hidden;
    transform: scale(0.8);
    transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.analysis-lightbox.visible .analysis-lightbox-content {
    transform: scale(1);
}
.analysis-lightbox-content::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.5), rgba(255,255,255,0.1));
    background-size: 200% 100%;
    animation: anStripe 2.4s ease infinite;
}
@keyframes anStripe { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

.analysis-logo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    z-index: 2;
}
.analysis-logo img { max-width: 54px; max-height: 54px; object-fit: contain; filter: brightness(0) invert(1); }
.analysis-logo-fallback { font-size: 32px; font-weight: 800; color: #fff; }

.analysis-orbit {
    position: absolute;
    top: 48px;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.12);
    animation: anSpin 3.6s linear infinite;
    z-index: 1;
}
@keyframes anSpin { to { transform: rotate(360deg); } }
.analysis-orbit-dot {
    position: absolute;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 0 10px rgba(255,255,255,0.6);
    animation: anGlow 1.2s ease-in-out infinite alternate;
}
.analysis-orbit-dot:nth-child(1) { top: -4px; left: calc(50% - 4px); }
.analysis-orbit-dot:nth-child(2) { bottom: -4px; left: calc(50% - 4px); animation-delay: -0.6s; }
.analysis-orbit-dot:nth-child(3) { top: calc(50% - 4px); left: -4px; animation-delay: -1.2s; }
@keyframes anGlow { 0% { opacity: 0.4; transform: scale(0.7); } 100% { opacity: 1; transform: scale(1.2); } }

.analysis-pulse {
    position: absolute;
    top: 48px;
    width: 80px; height: 80px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.4);
    animation: anPulse 2.4s ease-out infinite;
    pointer-events: none;
}
@keyframes anPulse { 0% { transform: scale(1); opacity: 0.5; } 100% { transform: scale(2.2); opacity: 0; } }

.analysis-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}
.analysis-particles span {
    position: absolute;
    width: 3px; height: 3px;
    border-radius: 50%;
    background: rgba(255,255,255,0.6);
    opacity: 0;
    animation: anFloat 3.5s ease-in-out infinite;
}
.analysis-particles span:nth-child(1) { left:12%; animation-delay:0s; }
.analysis-particles span:nth-child(2) { left:25%; animation-delay:0.4s; }
.analysis-particles span:nth-child(3) { left:40%; animation-delay:0.8s; }
.analysis-particles span:nth-child(4) { left:55%; animation-delay:1.2s; }
.analysis-particles span:nth-child(5) { left:70%; animation-delay:0.2s; }
.analysis-particles span:nth-child(6) { left:85%; animation-delay:0.6s; }
.analysis-particles span:nth-child(7) { left:48%; animation-delay:1.5s; }
.analysis-particles span:nth-child(8) { left:92%; animation-delay:1.0s; }
@keyframes anFloat {
    0% { bottom: -8px; opacity: 0; transform: scale(0.5); }
    20% { opacity: 0.6; }
    80% { opacity: 0.2; }
    100% { bottom: 110%; opacity: 0; transform: scale(1.1); }
}

.analysis-title {
    font-size: 18px; font-weight: 700; color: #fff;
    margin-bottom: 6px; z-index: 2;
}
.analysis-subtitle {
    font-size: 13px; color: rgba(255,255,255,0.7);
    z-index: 2;
}

.req-checks {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 6px;
}
.req-check {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--text-secondary);
}
.req-check input { accent-color: var(--primary); }
</style>

<!-- Analysis Lightbox -->
<div class="analysis-lightbox" id="analysisLightbox">
    <div class="analysis-lightbox-content">
        <div class="analysis-orbit">
            <div class="analysis-orbit-dot"></div>
            <div class="analysis-orbit-dot"></div>
            <div class="analysis-orbit-dot"></div>
        </div>
        <div class="analysis-pulse"></div>
        <div class="analysis-pulse" style="animation-delay:0.6s"></div>
        <div class="analysis-logo">
            <?php if ($criLogo): ?>
                <img src="<?= htmlspecialchars($criLogo) ?>" alt="">
            <?php else: ?>
                <div class="analysis-logo-fallback"><?= strtoupper(substr($criCompany, 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div class="analysis-title" id="analysisTitle">Analyzing your post</div>
        <div class="analysis-subtitle" id="analysisSubtitle">Running AI-powered content analysis...</div>
        <div class="analysis-particles">
            <span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span>
        </div>
    </div>
</div>

<!-- Themes Section -->
<div class="card" style="margin-bottom:28px">
    <div class="card-header">
        <div>
            <div class="card-title"><i class="fas fa-chess" style="margin-right:8px;color:var(--primary)"></i> Content Themes</div>
            <div class="card-subtitle">Define content categories with copy instructions and sample posts for AI to follow</div>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openThemeModal()">
            <i class="fas fa-plus"></i> New Theme
        </button>
    </div>

    <div class="theme-grid" id="themeGrid">
        <?php if (empty($themes)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--text-muted)">
            <i class="fas fa-layer-group" style="font-size:32px;opacity:0.3;margin-bottom:12px;display:block"></i>
            No themes yet. Create your first theme to guide AI content generation.
        </div>
        <?php else: ?>
        <?php foreach ($themes as $theme): ?>
        <div class="theme-card" id="theme-card-<?= $theme['id'] ?>">
            <div class="theme-card-header">
                <div class="theme-card-name"><?= htmlspecialchars($theme['name']) ?></div>
                <div class="theme-card-actions">
                    <button class="btn btn-ghost btn-sm btn-icon" onclick="editTheme(<?= $theme['id'] ?>)" title="Edit">
                        <i class="fas fa-pen" style="font-size:12px"></i>
                    </button>
                    <button class="btn btn-ghost btn-sm btn-icon" onclick="deleteTheme(<?= $theme['id'] ?>, '<?= htmlspecialchars(addslashes($theme['name'])) ?>')" title="Delete" style="color:var(--danger)">
                        <i class="fas fa-trash" style="font-size:12px"></i>
                    </button>
                </div>
            </div>
            <div class="theme-card-desc"><?= htmlspecialchars($theme['description'] ?? 'No description') ?></div>
            <div class="theme-card-badges">
                <?php
                $elems = $theme['required_elements'] ?? [];
                if (!empty($elems['phone'])): ?><span class="theme-badge">Phone</span><?php endif;
                if (!empty($elems['website'])): ?><span class="theme-badge">Website</span><?php endif;
                if (!empty($elems['cta'])): ?><span class="theme-badge">CTA</span><?php endif;
                if (!empty($elems['hashtags'])): ?><span class="theme-badge">Hashtags</span><?php endif;
                if (!empty($elems['emojis'])): ?><span class="theme-badge">Emojis</span><?php endif;
                if (($theme['image_style_override'] ?? 'global') !== 'global'): ?>
                    <span class="theme-badge-muted theme-badge"><i class="fas fa-camera" style="margin-right:3px"></i> <?= htmlspecialchars($theme['image_style_override']) ?></span>
                <?php endif; ?>
            </div>
            <div class="theme-card-samples">
                <i class="fas fa-file-alt" style="margin-right:4px"></i>
                <?= count($theme['samples'] ?? []) ?> sample post<?= count($theme['samples'] ?? []) !== 1 ? 's' : '' ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Weekly Schedule -->
<div class="card" style="margin-bottom:28px">
    <div class="card-header">
        <div>
            <div class="card-title"><i class="fas fa-calendar-week" style="margin-right:8px;color:var(--primary)"></i> Weekly Schedule</div>
            <div class="card-subtitle">Assign themes to days — the generator will use these when creating weekly content</div>
        </div>
    </div>
    <div class="schedule-grid">
        <?php for ($d = 1; $d <= 6; $d++): // Mon-Sat ?>
        <div class="schedule-day">
            <div class="schedule-day-label"><?= substr($dayNames[$d], 0, 3) ?></div>
            <select id="schedule_day_<?= $d ?>" class="schedule-select">
                <option value="">— No Post —</option>
                <?php foreach ($themes as $t): ?>
                <option value="<?= $t['id'] ?>" <?= (isset($schedule[$d]) && ($schedule[$d]['theme_id'] ?? null) == $t['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endfor; ?>
        <!-- Sunday at end -->
        <div class="schedule-day">
            <div class="schedule-day-label">Sun</div>
            <select id="schedule_day_0" class="schedule-select">
                <option value="">— No Post —</option>
                <?php foreach ($themes as $t): ?>
                <option value="<?= $t['id'] ?>" <?= (isset($schedule[0]) && ($schedule[0]['theme_id'] ?? null) == $t['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div style="margin-top:16px;display:flex;justify-content:flex-end">
        <button class="btn btn-primary" onclick="saveSchedule()" id="saveScheduleBtn">
            <i class="fas fa-save"></i> Save Schedule
        </button>
    </div>
</div>

<!-- Theme Modal -->
<div class="theme-modal-overlay" id="themeModal">
    <div class="theme-modal">
        <div class="theme-modal-title">
            <span id="themeModalTitle">New Theme</span>
            <button class="theme-modal-close" onclick="closeThemeModal()"><i class="fas fa-times"></i></button>
        </div>

        <input type="hidden" id="modal_theme_id" value="">

        <div class="form-group">
            <label class="form-label">Theme Name *</label>
            <input type="text" id="modal_name" class="form-input" placeholder="e.g. Cybersecurity Tips">
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea id="modal_description" class="form-textarea" rows="2" placeholder="What kind of content falls under this theme?"></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Copy Instructions</label>
            <textarea id="modal_copy_instructions" class="form-textarea" rows="3" placeholder="Guidance for AI when writing posts in this theme. e.g. Keep tone authoritative but accessible. End with a CTA."></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Required Elements</label>
            <div class="req-checks">
                <label class="req-check"><input type="checkbox" id="req_phone"> Phone Number</label>
                <label class="req-check"><input type="checkbox" id="req_website"> Website</label>
                <label class="req-check"><input type="checkbox" id="req_cta"> Call to Action</label>
                <label class="req-check"><input type="checkbox" id="req_hashtags"> Hashtags</label>
                <label class="req-check"><input type="checkbox" id="req_emojis"> Emojis</label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Default Hashtags</label>
            <input type="text" id="modal_hashtags" class="form-input" placeholder="#YourTag #Industry #Brand">
        </div>

        <div class="form-group">
            <label class="form-label">Image Style Override</label>
            <select id="modal_image_style" class="form-input">
                <option value="global">Use Global Art Direction</option>
                <option value="photorealistic">Photorealistic</option>
                <option value="mixed">Mixed (Photo + Graphics)</option>
                <option value="technical_diagram">Technical Diagram</option>
            </select>
        </div>

        <!-- Sample Posts -->
        <div class="form-group">
            <label class="form-label">Sample Posts <span style="font-weight:400;color:var(--text-muted)">(AI will mimic this style)</span></label>
            <div id="samplesContainer">
                <!-- Dynamically added -->
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="addSample()" id="addSampleBtn" style="margin-top:8px">
                <i class="fas fa-plus"></i> Add Sample Post
            </button>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
            <button type="button" class="btn btn-ghost" onclick="closeThemeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveTheme()" id="saveThemeBtn">
                <i class="fas fa-save"></i> Save Theme
            </button>
        </div>
    </div>
</div>

<script>
var THEMES = <?= json_encode($themes) ?>;
var CSRF = '<?= $csrfToken ?>';
var sampleCount = 0;

// ----- Theme Modal -----

function openThemeModal(themeId) {
    document.getElementById('modal_theme_id').value = '';
    document.getElementById('modal_name').value = '';
    document.getElementById('modal_description').value = '';
    document.getElementById('modal_copy_instructions').value = '';
    document.getElementById('modal_hashtags').value = '';
    document.getElementById('modal_image_style').value = 'global';
    // New theme: default all required elements to ON. The user can uncheck any
    // they don't want, but phone/website/CTA/hashtags/emojis are on by default.
    document.getElementById('req_phone').checked = true;
    document.getElementById('req_website').checked = true;
    document.getElementById('req_cta').checked = true;
    document.getElementById('req_hashtags').checked = true;
    document.getElementById('req_emojis').checked = true;
    document.getElementById('samplesContainer').innerHTML = '';
    sampleCount = 0;
    document.getElementById('themeModalTitle').textContent = 'New Theme';

    if (themeId) {
        loadThemeIntoModal(themeId);
    } else {
        addSample(); // Start with one empty sample
    }

    document.getElementById('themeModal').classList.add('visible');
}

function closeThemeModal() {
    document.getElementById('themeModal').classList.remove('visible');
}

function editTheme(id) {
    openThemeModal(id);
}

function loadThemeIntoModal(id) {
    var theme = THEMES.find(function(t) { return t.id == id; });
    if (!theme) return;

    document.getElementById('themeModalTitle').textContent = 'Edit Theme';
    document.getElementById('modal_theme_id').value = theme.id;
    document.getElementById('modal_name').value = theme.name || '';
    document.getElementById('modal_description').value = theme.description || '';
    document.getElementById('modal_copy_instructions').value = theme.copy_instructions || '';
    document.getElementById('modal_hashtags').value = theme.default_hashtags || '';
    document.getElementById('modal_image_style').value = theme.image_style_override || 'global';

    var re = theme.required_elements || {};
    document.getElementById('req_phone').checked = !!re.phone;
    document.getElementById('req_website').checked = !!re.website;
    document.getElementById('req_cta').checked = !!re.cta;
    document.getElementById('req_hashtags').checked = !!re.hashtags;
    document.getElementById('req_emojis').checked = !!re.emojis;

    // Load samples
    var samples = theme.samples || [];
    if (samples.length === 0) {
        addSample();
    } else {
        samples.forEach(function(s) {
            addSample(s.sample_content || '');
        });
    }
}

function addSample(content) {
    if (sampleCount >= 3) {
        showToast('Maximum 3 sample posts', 'warning');
        return;
    }
    sampleCount++;
    var idx = sampleCount;
    var div = document.createElement('div');
    div.className = 'sample-group';
    div.id = 'sample_group_' + idx;
    div.innerHTML = '<div class="sample-header">'
        + '<span class="sample-label">Sample Post ' + idx + '</span>'
        + '<div style="display:flex;gap:6px">'
        + '<button type="button" class="btn btn-ghost btn-sm" onclick="critiqueSample(' + idx + ')" title="AI Critique"><i class="fas fa-robot"></i> Analyze</button>'
        + '<button type="button" class="btn btn-ghost btn-sm" onclick="removeSample(' + idx + ')" style="color:var(--danger)"><i class="fas fa-times"></i></button>'
        + '</div></div>'
        + '<textarea id="sample_content_' + idx + '" class="form-textarea" rows="3" placeholder="Paste an example post you like...">' + (content || '').replace(/</g, '&lt;') + '</textarea>'
        + '<div id="critique_panel_' + idx + '" class="critique-panel"></div>';
    document.getElementById('samplesContainer').appendChild(div);

    if (sampleCount >= 3) {
        document.getElementById('addSampleBtn').style.display = 'none';
    }
}

function removeSample(idx) {
    var el = document.getElementById('sample_group_' + idx);
    if (el) el.remove();
    sampleCount--;
    document.getElementById('addSampleBtn').style.display = '';
}

function critiqueSample(idx) {
    var content = document.getElementById('sample_content_' + idx).value.trim();
    if (!content) {
        showToast('Enter some content first', 'warning');
        return;
    }

    var panel = document.getElementById('critique_panel_' + idx);
    var themeId = document.getElementById('modal_theme_id').value;

    // Show the analysis lightbox with spring animation
    showAnalysisLightbox();

    var analysisMsgs = ['Scanning content structure...', 'Evaluating tone & messaging...', 'Checking engagement elements...', 'Crafting improved version...'];
    var msgIdx = 0;
    var msgInterval = setInterval(function() {
        msgIdx++;
        if (msgIdx < analysisMsgs.length) {
            document.getElementById('analysisSubtitle').textContent = analysisMsgs[msgIdx];
        }
    }, 800);

    fetch('<?= BASE_URL ?>/content-strategy/critique', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: content, theme_id: themeId || null, csrf_token: CSRF })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        clearInterval(msgInterval);

        // Keep lightbox visible for at least 2.5s total for the effect
        setTimeout(function() {
            hideAnalysisLightbox();

            setTimeout(function() {
                panel.style.display = 'block';

                if (data.error) {
                    panel.innerHTML = '<div style="color:var(--danger);font-size:13px">' + data.error + '</div>';
                    return;
                }

                var html = '';
                if (data.strengths && data.strengths.length) {
                    html += '<div class="critique-section critique-strengths">'
                        + '<div class="critique-section-title"><i class="fas fa-check-circle" style="margin-right:4px"></i> Strengths</div>'
                        + '<ul class="critique-list">' + data.strengths.map(function(s) { return '<li>' + escHtml(s) + '</li>'; }).join('') + '</ul></div>';
                }
                if (data.suggestions && data.suggestions.length) {
                    html += '<div class="critique-section critique-suggestions">'
                        + '<div class="critique-section-title"><i class="fas fa-lightbulb" style="margin-right:4px"></i> Suggestions</div>'
                        + '<ul class="critique-list">' + data.suggestions.map(function(s) { return '<li>' + escHtml(s) + '</li>'; }).join('') + '</ul></div>';
                }
                if (data.revised) {
                    // Format the revised post nicely with line breaks preserved
                    var formattedRevised = data.revised
                        .replace(/\n/g, '<br>')
                        .replace(/(#\w+)/g, '<span style="color:var(--primary);font-weight:500">$1</span>');

                    html += '<div class="critique-section">'
                        + '<div class="critique-section-title" style="color:var(--primary)"><i class="fas fa-magic" style="margin-right:4px"></i> AI-Revised Version</div>'
                        + '<div class="critique-revised" style="white-space:normal">' + formattedRevised + '</div>'
                        + '<div style="margin-top:12px;display:flex;gap:8px">'
                        + '<button class="btn btn-primary btn-sm" onclick="useCritiqueRevision(' + idx + ', this)" data-revised="' + escAttr(data.revised) + '"><i class="fas fa-check"></i> Use AI Version</button>'
                        + '<button class="btn btn-ghost btn-sm" onclick="document.getElementById(\'critique_panel_' + idx + '\').style.display=\'none\'">Dismiss</button>'
                        + '</div></div>';
                }
                panel.innerHTML = html;

                // Scroll to the panel
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 300);
        }, 2500);
    })
    .catch(function() {
        clearInterval(msgInterval);
        hideAnalysisLightbox();
        panel.style.display = 'block';
        panel.innerHTML = '<div style="color:var(--danger);font-size:13px">Network error. Try again.</div>';
    });
}

function showAnalysisLightbox() {
    var lb = document.getElementById('analysisLightbox');
    document.getElementById('analysisTitle').textContent = 'Analyzing your post';
    document.getElementById('analysisSubtitle').textContent = 'Running AI-powered content analysis...';
    lb.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function hideAnalysisLightbox() {
    var lb = document.getElementById('analysisLightbox');
    // Shrink back animation
    var content = lb.querySelector('.analysis-lightbox-content');
    content.style.transform = 'scale(0.85)';
    content.style.opacity = '0';
    content.style.transition = 'transform 0.4s cubic-bezier(0.6, -0.28, 0.74, 0.05), opacity 0.3s ease';

    setTimeout(function() {
        lb.classList.remove('visible');
        document.body.style.overflow = '';
        // Reset for next use
        content.style.transform = '';
        content.style.opacity = '';
        content.style.transition = '';
    }, 400);
}

function useCritiqueRevision(idx, btn) {
    var revised = btn.getAttribute('data-revised');
    document.getElementById('sample_content_' + idx).value = revised;
    document.getElementById('critique_panel_' + idx).style.display = 'none';
    showToast('AI revision applied', 'success');
}

function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
function escAttr(str) {
    return str.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;');
}

// ----- Save Theme -----

function saveTheme() {
    var btn = document.getElementById('saveThemeBtn');
    var name = document.getElementById('modal_name').value.trim();
    if (!name) {
        showToast('Theme name is required', 'warning');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    // Gather samples
    var samples = [];
    document.querySelectorAll('[id^="sample_content_"]').forEach(function(ta) {
        var val = ta.value.trim();
        if (val) samples.push(val);
    });

    var data = {
        csrf_token: CSRF,
        theme_id: document.getElementById('modal_theme_id').value || null,
        name: name,
        description: document.getElementById('modal_description').value,
        copy_instructions: document.getElementById('modal_copy_instructions').value,
        required_elements: {
            phone: document.getElementById('req_phone').checked,
            website: document.getElementById('req_website').checked,
            cta: document.getElementById('req_cta').checked,
            hashtags: document.getElementById('req_hashtags').checked,
            emojis: document.getElementById('req_emojis').checked,
        },
        default_hashtags: document.getElementById('modal_hashtags').value,
        image_style_override: document.getElementById('modal_image_style').value,
        samples: samples
    };

    fetch('<?= BASE_URL ?>/content-strategy/save-theme', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Theme';
        if (result.success) {
            showToast('Theme saved!', 'success');
            closeThemeModal();
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(result.error || 'Save failed', 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Theme';
        showToast('Network error', 'error');
    });
}

// ----- Delete Theme -----

function deleteTheme(id, name) {
    confirmModal(
        'Delete Theme',
        'Are you sure you want to delete <strong>' + escHtml(name) + '</strong>? This cannot be undone.',
        function() {
            fetch('<?= BASE_URL ?>/content-strategy/delete-theme/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: CSRF })
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success) {
                    showToast('Theme deleted', 'success');
                    var card = document.getElementById('theme-card-' + id);
                    if (card) card.remove();
                    THEMES = THEMES.filter(function(t) { return t.id != id; });
                } else {
                    showToast(result.error || 'Delete failed', 'error');
                }
            });
        }
    );
}

// ----- Save Schedule -----

function saveSchedule() {
    var btn = document.getElementById('saveScheduleBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var schedule = {};
    for (var d = 0; d <= 6; d++) {
        var el = document.getElementById('schedule_day_' + d);
        if (el) {
            schedule[d] = el.value || null;
        }
    }

    fetch('<?= BASE_URL ?>/content-strategy/save-schedule', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ schedule: schedule, csrf_token: CSRF })
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Schedule';
        if (result.success) {
            showToast('Schedule saved!', 'success');
        } else {
            showToast(result.error || 'Save failed', 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Schedule';
        showToast('Network error', 'error');
    });
}
</script>
