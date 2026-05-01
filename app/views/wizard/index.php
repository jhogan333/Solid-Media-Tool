<?php
$b = $branding ?? [];
$isRerun = $isRerun ?? false;
$existingThemes = $existingThemes ?? [];
$primaryColor = $b['primary_color'] ?? '#6366f1';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$isMandatory = $isMandatory ?? false;
?>

<style>
.wizard-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: wizFadeIn 0.4s ease;
}
@keyframes wizFadeIn { from { opacity: 0; } to { opacity: 1; } }

.wizard-container {
    background: var(--bg-card);
    border-radius: 24px;
    max-width: 600px;
    width: 94%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 80px rgba(0,0,0,0.4);
    animation: wizSlideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes wizSlideUp { from { transform: translateY(40px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }

.wizard-header {
    background: linear-gradient(165deg, <?= $primaryColor ?> 0%, color-mix(in srgb, <?= $primaryColor ?> 50%, #0a0a0a) 100%);
    padding: 32px 32px 28px;
    border-radius: 24px 24px 0 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
}
.wizard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.4), rgba(255,255,255,0.1));
    background-size: 200% 100%;
    animation: wiz-stripe 2.4s ease infinite;
}
@keyframes wiz-stripe { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

/* Atom orbits in header */
.wizard-header-atom {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 200px;
    height: 200px;
    pointer-events: none;
}
.wizard-header-atom .wh-orbit {
    position: absolute;
    inset: 0;
    border-radius: 50%;
}
.wizard-header-atom .wh-orbit:nth-child(1) {
    width: 220px; height: 80px; top: calc(50% - 40px); left: calc(50% - 110px);
    border: 1.5px solid rgba(255,255,255,0.15);
    animation: aiSpin 7s linear infinite;
}
.wizard-header-atom .wh-orbit:nth-child(2) {
    width: 200px; height: 70px; top: calc(50% - 35px); left: calc(50% - 100px);
    border: 1.5px solid rgba(255,255,255,0.12);
    animation: aiSpin 5.5s linear infinite reverse;
    transform: rotate(55deg);
}
.wizard-header-atom .wh-orbit:nth-child(3) {
    width: 60px; height: 180px; top: calc(50% - 90px); left: calc(50% - 30px);
    border: 1.5px solid rgba(255,255,255,0.10);
    animation: aiSpin 8s linear infinite;
    transform: rotate(25deg);
}
.wh-dot {
    position: absolute;
    width: 5px; height: 5px;
    background: #fff;
    border-radius: 50%;
    top: -2.5px; left: calc(50% - 2.5px);
    box-shadow: 0 0 12px rgba(255,255,255,0.7);
    opacity: 0.75;
}
@keyframes aiSpin { to { transform: rotate(360deg); } }

.wizard-title {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
}
.wizard-subtitle {
    font-size: 14px;
    color: rgba(255,255,255,0.7);
}

/* Steps indicator */
.wizard-steps {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}
.wizard-step-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255,255,255,0.25);
    transition: all 0.3s ease;
}
.wizard-step-dot.active {
    background: #fff;
    transform: scale(1.3);
    box-shadow: 0 0 10px rgba(255,255,255,0.5);
}
.wizard-step-dot.done {
    background: rgba(255,255,255,0.7);
}
.wizard-step-counter {
    margin-top: 10px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.wizard-body {
    padding: 28px 32px 32px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
/* Custom scrollbar — dark, minimal */
.wizard-body::-webkit-scrollbar { width: 6px; }
.wizard-body::-webkit-scrollbar-track { background: transparent; }
.wizard-body::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); border-radius: 3px; }
.wizard-body::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.35); }

/* Theme items staggered glow */
.theme-check-item {
    animation: themeItemIn 0.4s cubic-bezier(0.23,1,0.32,1) both;
    position: relative;
    overflow: hidden;
}
.theme-check-item::after {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(var(--primary-rgb), 0.12), transparent);
    animation: themeGlowSweep 0.6s ease forwards;
    pointer-events: none;
}
.theme-check-item:nth-child(1) { animation-delay: 0.1s; }
.theme-check-item:nth-child(1)::after { animation-delay: 0.25s; }
.theme-check-item:nth-child(2) { animation-delay: 0.18s; }
.theme-check-item:nth-child(2)::after { animation-delay: 0.33s; }
.theme-check-item:nth-child(3) { animation-delay: 0.26s; }
.theme-check-item:nth-child(3)::after { animation-delay: 0.41s; }
.theme-check-item:nth-child(4) { animation-delay: 0.34s; }
.theme-check-item:nth-child(4)::after { animation-delay: 0.49s; }
.theme-check-item:nth-child(5) { animation-delay: 0.42s; }
.theme-check-item:nth-child(5)::after { animation-delay: 0.57s; }
.theme-check-item:nth-child(6) { animation-delay: 0.50s; }
.theme-check-item:nth-child(6)::after { animation-delay: 0.65s; }
.theme-check-item:nth-child(7) { animation-delay: 0.58s; }
.theme-check-item:nth-child(7)::after { animation-delay: 0.73s; }

@keyframes themeItemIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes themeGlowSweep {
    0% { left: -100%; opacity: 0; }
    30% { opacity: 1; }
    100% { left: 100%; opacity: 0; }
}

.wizard-step { display: none; }
.wizard-step.active {
    display: block;
    animation: wizStepAssemble 0.65s cubic-bezier(0.23,1,0.32,1) both;
}
.wizard-step.exiting {
    display: block;
    animation: wizStepDissolve 0.35s ease forwards;
    pointer-events: none;
}
.wizard-step.active-back {
    display: block;
    animation: wizStepAssembleBack 0.65s cubic-bezier(0.23,1,0.32,1) both;
}
@keyframes wizStepAssemble {
    0% { opacity:0; transform:perspective(800px) rotateY(-4deg) translateX(50px) scale(0.95); filter:blur(6px); }
    60% { filter:blur(0); }
    100% { opacity:1; transform:perspective(800px) rotateY(0) translateX(0) scale(1); filter:blur(0); }
}
@keyframes wizStepDissolve {
    0% { opacity:1; transform:perspective(800px) rotateY(0) translateX(0) scale(1); filter:blur(0); }
    100% { opacity:0; transform:perspective(800px) rotateY(4deg) translateX(-50px) scale(0.95); filter:blur(6px); }
}
@keyframes wizStepAssembleBack {
    0% { opacity:0; transform:perspective(800px) rotateY(4deg) translateX(-50px) scale(0.95); filter:blur(6px); }
    60% { filter:blur(0); }
    100% { opacity:1; transform:perspective(800px) rotateY(0) translateX(0) scale(1); filter:blur(0); }
}

/* Cascade form elements within wizard steps */
.wizard-step.active .form-group,
.wizard-step.active-back .form-group,
.wizard-step.active .wizard-footer,
.wizard-step.active-back .wizard-footer {
    animation: wizElCascade 0.4s cubic-bezier(0.23,1,0.32,1) both;
}
.wizard-step.active .form-group:nth-child(1) { animation-delay:0.15s; }
.wizard-step.active .form-group:nth-child(2) { animation-delay:0.22s; }
.wizard-step.active .form-group:nth-child(3) { animation-delay:0.29s; }
.wizard-step.active .form-group:nth-child(4) { animation-delay:0.36s; }
.wizard-step.active .form-group:nth-child(5) { animation-delay:0.43s; }
.wizard-step.active .wizard-footer { animation-delay:0.5s; }
@keyframes wizElCascade { from { opacity:0; transform:translateY(14px); filter:blur(3px); } to { opacity:1; transform:translateY(0); filter:blur(0); } }

.wizard-step-title {
    font-size: 17px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}
.wizard-step-desc {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 20px;
}

.wizard-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
}

/* AI Loading State */
.wizard-ai-loading {
    text-align: center;
    padding: 40px 20px;
}
.wizard-ai-ring {
    width: 80px;
    height: 80px;
    border: 2px solid rgba(var(--primary-rgb), 0.2);
    border-radius: 50%;
    margin: 0 auto 20px;
    position: relative;
    animation: wizSpin 2.4s linear infinite;
}
@keyframes wizSpin { to { transform: rotate(360deg); } }
.wizard-ai-ring::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border: 2px solid transparent;
    border-top-color: var(--primary);
    border-radius: 50%;
}
.wizard-ai-status {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}
.wizard-ai-detail {
    font-size: 13px;
    color: var(--text-muted);
}

/* Theme Checklist */
.theme-check-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.theme-check-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 8px;
    transition: all var(--transition);
}
.theme-check-item:hover { background: rgba(var(--primary-rgb), 0.04); }
.theme-check-item input[type="checkbox"] {
    accent-color: var(--primary);
    margin-top: 3px;
    flex-shrink: 0;
}
.theme-check-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}
.theme-check-desc {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
}

/* Color picker mini */
.wizard-color-row {
    display: flex;
    gap: 10px;
    align-items: center;
}
.wizard-color-row input[type="color"] {
    width: 44px;
    height: 44px;
    border: 2px solid var(--border);
    border-radius: 12px;
    padding: 2px;
    cursor: pointer;
    background: var(--bg-input);
}
.wizard-color-row input[type="color"]::-webkit-color-swatch-wrapper { padding: 2px; }
.wizard-color-row input[type="color"]::-webkit-color-swatch { border: none; border-radius: 6px; }

/* Rerun banner */
.rerun-banner {
    background: rgba(var(--primary-rgb), 0.08);
    border: 1px solid rgba(var(--primary-rgb), 0.25);
    border-radius: var(--radius-md);
    padding: 12px 16px;
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Summary */
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-light);
    font-size: 13px;
}
.summary-label { color: var(--text-muted); font-weight: 500; }
.summary-value { color: var(--text); font-weight: 600; }
</style>

<div class="wizard-overlay" id="wizardOverlay">
    <div class="wizard-container">
        <div class="wizard-header" id="wizardHeader">
            <!-- Atom effect behind text -->
            <div class="wizard-header-atom">
                <div class="wh-orbit"><div class="wh-dot"></div></div>
                <div class="wh-orbit"><div class="wh-dot"></div></div>
                <div class="wh-orbit"><div class="wh-dot"></div></div>
            </div>
            <div style="position:relative;z-index:2">
                <div class="wizard-title" id="wizTitle">Setup Wizard</div>
                <div class="wizard-subtitle" id="wizSubtitle">Let's get your social media engine configured</div>
                <div class="wizard-steps" id="wizardSteps">
                    <div class="wizard-step-dot active" data-step="1"></div>
                    <div class="wizard-step-dot" data-step="2"></div>
                    <div class="wizard-step-dot" data-step="3"></div>
                    <div class="wizard-step-dot" data-step="4"></div>
                    <div class="wizard-step-dot" data-step="5"></div>
                </div>
                <div class="wizard-step-counter" id="wizStepCounter">Step 1 of 5</div>
            </div>
        </div>

        <div class="wizard-body">
            <?php $isSubsequentAdmin = $isSubsequentAdmin ?? false; ?>
            <?php if ($isSubsequentAdmin): ?>
            <div class="rerun-banner" style="background:rgba(var(--primary-rgb),0.08);border-color:var(--primary);color:var(--text)">
                <i class="fas fa-info-circle" style="color:var(--primary)"></i>
                <span>This wizard has already been completed by the first administrator. All settings are pre-populated from the existing configuration. You can review and adjust anything, or simply <strong>Cancel</strong> to skip and start using the system.</span>
            </div>
            <?php elseif ($isRerun): ?>
            <div class="rerun-banner">
                <i class="fas fa-exclamation-triangle"></i>
                Re-running wizard. Your current values are shown as defaults. Changes will override existing settings.
            </div>
            <?php endif; ?>

            <!-- Step 1: Company Basics -->
            <div class="wizard-step active" id="wizStep1">
                <div class="wizard-step-title">Tell us about your company</div>
                <div class="wizard-step-desc">This information helps AI create on-brand content</div>

                <div class="form-group">
                    <label class="form-label">Company Name *</label>
                    <input type="text" id="wiz_company" class="form-input" value="<?= htmlspecialchars($b['company_name'] ?? '') ?>" placeholder="Your company name">
                </div>
                <div class="form-group">
                    <label class="form-label">Website URL</label>
                    <input type="text" id="wiz_website" class="form-input" value="<?= htmlspecialchars($b['website'] ?? '') ?>" placeholder="https://yourcompany.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" id="wiz_phone" class="form-input" value="<?= htmlspecialchars($b['phone'] ?? '') ?>" placeholder="e.g. 587-557-1234">
                </div>
                <div class="form-group">
                    <label class="form-label">Industry</label>
                    <?php $wizIndustry = $b['industry'] ?? ''; ?>
                    <select id="wiz_industry" class="form-input">
                        <option value="">Select your industry</option>
                        <?php foreach (['Information Technology', 'Healthcare', 'Finance', 'Marketing', 'Real Estate', 'Education', 'Legal', 'Construction', 'Retail', 'Manufacturing', 'Other'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $wizIndustry === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wizard-footer">
                    <?php if (!empty($isSubsequentAdmin)): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-ghost" onclick="sessionStorage.removeItem('needs_wizard')">Skip Wizard</a>
                    <?php elseif ($isRerun && empty($isMandatory)): ?>
                    <a href="<?= BASE_URL ?>/branding" class="btn btn-ghost">Cancel</a>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    <button class="btn btn-primary" onclick="wizNext(1)">Next <i class="fas fa-arrow-right" style="margin-left:4px"></i></button>
                </div>
            </div>

            <!-- Step 2: Website Scan -->
            <div class="wizard-step" id="wizStep2">
                <div id="wizScanContent">
                    <div class="wizard-step-title">Scanning your website</div>
                    <div class="wizard-step-desc">AI is analyzing your website to extract key information</div>
                    <div class="wizard-ai-loading">
                        <div class="wizard-ai-ring"></div>
                        <div class="wizard-ai-status" id="wizScanStatus">Connecting...</div>
                        <div class="wizard-ai-detail" id="wizScanDetail">Fetching website content</div>
                    </div>
                </div>
                <div id="wizScanResults" style="display:none">
                    <div class="wizard-step-title">Website Analysis Complete</div>
                    <div class="wizard-step-desc">Review and edit the extracted information</div>

                    <div class="form-group">
                        <label class="form-label">About Your Company</label>
                        <textarea id="wiz_about" class="form-textarea" rows="3" placeholder="Brief description of what your company does"><?= htmlspecialchars($b['about_company'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Key Services</label>
                        <input type="text" id="wiz_services" class="form-input" placeholder="Comma-separated services" value="<?= htmlspecialchars($b['key_services'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Industry Keywords</label>
                        <input type="text" id="wiz_keywords" class="form-input" placeholder="Comma-separated keywords for social media" value="<?= htmlspecialchars($b['industry_keywords'] ?? '') ?>">
                    </div>
                </div>

                <div class="wizard-footer" id="wizStep2Footer" style="display:none">
                    <button class="btn btn-ghost" onclick="wizBack(2)"><i class="fas fa-arrow-left" style="margin-right:4px"></i> Back</button>
                    <button class="btn btn-primary" onclick="wizNext(2)">Next <i class="fas fa-arrow-right" style="margin-left:4px"></i></button>
                </div>
            </div>

            <!-- Step 3: Brand Identity -->
            <div class="wizard-step" id="wizStep3">
                <div class="wizard-step-title">Brand Identity</div>
                <div class="wizard-step-desc">Set your visual brand — these appear throughout the app and on generated images</div>

                <div class="form-group">
                    <label class="form-label">Tagline</label>
                    <input type="text" id="wiz_tagline" class="form-input" value="<?= htmlspecialchars($b['tagline'] ?? '') ?>" placeholder="Your company tagline or slogan">
                </div>
                <div class="form-group">
                    <label class="form-label">Brand Color</label>
                    <div class="wizard-color-row">
                        <input type="color" id="wiz_primary" value="<?= htmlspecialchars($b['primary_color'] ?? '#6366f1') ?>">
                        <input type="text" id="wiz_primary_hex" class="form-input" value="<?= htmlspecialchars($b['primary_color'] ?? '#6366f1') ?>" maxlength="7" style="max-width:120px">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Favicon <span style="font-weight:400;color:var(--text-muted)">(browser tab icon)</span></label>
                    <div style="display:flex;align-items:center;gap:16px">
                        <div id="wiz_favicon_preview" style="width:48px;height:48px;border-radius:8px;border:1px solid var(--border);background:var(--bg-input);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                            <?php $favUrl = $b['favicon_url'] ?? ''; if ($favUrl): ?>
                                <img src="<?= htmlspecialchars($favUrl) ?>" style="width:32px;height:32px;object-fit:contain">
                            <?php else: ?>
                                <i class="fas fa-globe" style="color:var(--text-muted);font-size:18px"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1">
                            <label class="btn btn-ghost btn-sm" style="cursor:pointer;display:inline-flex">
                                <i class="fas fa-upload" style="margin-right:4px"></i> Upload Favicon
                                <input type="file" id="wiz_favicon_input" accept="image/png,image/x-icon,image/svg+xml,image/jpeg" onchange="wizPreviewFavicon(this)" style="display:none">
                            </label>
                            <div class="text-small text-muted" style="margin-top:4px">PNG or ICO, square image preferred</div>
                        </div>
                    </div>
                </div>

                <div class="wizard-footer">
                    <button class="btn btn-ghost" onclick="wizBack(3)"><i class="fas fa-arrow-left" style="margin-right:4px"></i> Back</button>
                    <button class="btn btn-primary" onclick="wizNext(3)">Next <i class="fas fa-arrow-right" style="margin-left:4px"></i></button>
                </div>
            </div>

            <!-- Step 4: Theme Suggestions -->
            <div class="wizard-step" id="wizStep4">
                <div id="wizThemeLoading">
                    <div class="wizard-step-title">Generating Theme Suggestions</div>
                    <div class="wizard-step-desc">AI is creating content themes tailored to your business</div>
                    <div class="wizard-ai-loading">
                        <div class="wizard-ai-ring"></div>
                        <div class="wizard-ai-status">Thinking...</div>
                        <div class="wizard-ai-detail">Analyzing your business for theme ideas</div>
                    </div>
                </div>
                <div id="wizThemeResults" style="display:none">
                    <div class="wizard-step-title">Suggested Content Themes</div>
                    <div class="wizard-step-desc">Select the themes you want — you can always add or edit them later</div>

                    <div style="margin-bottom:12px;display:flex;gap:8px">
                        <button class="btn btn-ghost btn-sm" onclick="wizToggleAllThemes(true)">Select All</button>
                        <button class="btn btn-ghost btn-sm" onclick="wizToggleAllThemes(false)">Deselect All</button>
                    </div>

                    <ul class="theme-check-list" id="wizThemeList">
                        <!-- Populated by JS -->
                    </ul>
                </div>

                <div class="wizard-footer" id="wizStep4Footer" style="display:none">
                    <button class="btn btn-ghost" onclick="wizBack(4)"><i class="fas fa-arrow-left" style="margin-right:4px"></i> Back</button>
                    <button class="btn btn-primary" onclick="wizNext(4)">Next <i class="fas fa-arrow-right" style="margin-left:4px"></i></button>
                </div>
            </div>

            <!-- Step 5: Review & Save -->
            <div class="wizard-step" id="wizStep5">
                <div class="wizard-step-title">Review & Complete</div>
                <div class="wizard-step-desc">Everything looks good? Hit Complete to apply all settings.</div>

                <div id="wizSummary" style="margin-bottom:16px"></div>

                <div class="wizard-footer">
                    <button class="btn btn-ghost" onclick="wizBack(5)"><i class="fas fa-arrow-left" style="margin-right:4px"></i> Back</button>
                    <button class="btn btn-primary" onclick="wizComplete()" id="wizCompleteBtn" style="padding:12px 28px">
                        <i class="fas fa-check"></i> Complete Setup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var wizardData = {
    company_name: '<?= addslashes($b['company_name'] ?? '') ?>',
    website: '<?= addslashes($b['website'] ?? '') ?>',
    phone: '<?= addslashes($b['phone'] ?? '') ?>',
    industry: '',
    about: '',
    services: [],
    keywords: [],
    tagline: '<?= addslashes($b['tagline'] ?? '') ?>',
    primary_color: '<?= $primaryColor ?>',
    themes: []
};
var suggestedThemes = [];
var currentStep = 1;

// Favicon preview
function wizPreviewFavicon(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('wiz_favicon_preview').innerHTML = '<img src="' + e.target.result + '" style="width:32px;height:32px;object-fit:contain">';
            wizardData.favicon_base64 = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Color sync
document.getElementById('wiz_primary').addEventListener('input', function() {
    document.getElementById('wiz_primary_hex').value = this.value;
});
document.getElementById('wiz_primary_hex').addEventListener('input', function() {
    if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('wiz_primary').value = this.value;
});
function setStep(step, direction) {
    var oldStep = currentStep;
    var isForward = direction !== 'back';
    currentStep = step;

    // Dissolve out old step
    var oldEl = document.getElementById('wizStep' + oldStep);
    if (oldEl && oldStep !== step) {
        oldEl.classList.remove('active', 'active-back');
        oldEl.classList.add('exiting');

        setTimeout(function() {
            oldEl.classList.remove('exiting');
            // Assemble in new step
            var newEl = document.getElementById('wizStep' + step);
            newEl.className = 'wizard-step ' + (isForward ? 'active' : 'active-back');
        }, 350);
    } else {
        document.getElementById('wizStep' + step).className = 'wizard-step active';
    }

    // Update dots with animation
    document.querySelectorAll('.wizard-step-dot').forEach(function(dot) {
        var s = parseInt(dot.dataset.step);
        dot.classList.remove('active', 'done');
        if (s === step) dot.classList.add('active');
        else if (s < step) dot.classList.add('done');
    });

    // Update step counter text
    var counter = document.getElementById('wizStepCounter');
    if (counter) counter.textContent = 'Step ' + step + ' of 5';
}

function wizNext(fromStep) {
    if (fromStep === 1) {
        var name = document.getElementById('wiz_company').value.trim();
        if (!name) { showToast('Company name is required', 'warning'); return; }
        wizardData.company_name = name;
        wizardData.website = document.getElementById('wiz_website').value.trim();
        wizardData.phone = document.getElementById('wiz_phone').value.trim();
        wizardData.industry = document.getElementById('wiz_industry').value;

        setStep(2);
        if (wizardData.website) {
            startWebsiteScan();
        } else {
            showScanSkipped();
        }
    } else if (fromStep === 2) {
        wizardData.about = document.getElementById('wiz_about').value.trim();
        wizardData.services = document.getElementById('wiz_services').value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        wizardData.keywords = document.getElementById('wiz_keywords').value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        setStep(3);
    } else if (fromStep === 3) {
        wizardData.tagline = document.getElementById('wiz_tagline').value.trim();
        wizardData.primary_color = document.getElementById('wiz_primary').value;
        setStep(4);
        startThemeSuggestions();
    } else if (fromStep === 4) {
        gatherSelectedThemes();
        setStep(5);
        buildSummary();
    }
}

function wizBack(fromStep) {
    setStep(fromStep - 1);
    if (fromStep - 1 === 2) {
        document.getElementById('wizScanResults').style.display = '';
        document.getElementById('wizScanContent').style.display = 'none';
        document.getElementById('wizStep2Footer').style.display = '';
    }
}

function startWebsiteScan() {
    document.getElementById('wizScanContent').style.display = '';
    document.getElementById('wizScanResults').style.display = 'none';
    document.getElementById('wizStep2Footer').style.display = 'none';

    var msgs = ['Connecting to website...', 'Downloading page content...', 'Analyzing with AI...', 'Extracting business info...'];
    var mi = 0;
    var interval = setInterval(function() {
        mi++;
        if (mi < msgs.length) {
            document.getElementById('wizScanStatus').textContent = msgs[mi];
        }
    }, 2500);

    fetch('<?= BASE_URL ?>/wizard/scan-website', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: wizardData.website, csrf_token: '<?= $csrfToken ?>' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        clearInterval(interval);
        if (data.error) {
            showScanSkipped(data.error);
            return;
        }
        // Fill results
        if (data.company_name && !wizardData.company_name) wizardData.company_name = data.company_name;
        document.getElementById('wiz_about').value = data.about || '';
        document.getElementById('wiz_services').value = (data.services || []).join(', ');
        document.getElementById('wiz_keywords').value = (data.keywords || []).join(', ');
        if (data.phone && !wizardData.phone) {
            wizardData.phone = data.phone;
            document.getElementById('wiz_phone').value = data.phone;
        }
        if (data.tagline) document.getElementById('wiz_tagline').value = data.tagline;
        if (data.industry) document.getElementById('wiz_industry').value = data.industry;

        wizardData.about = data.about || '';
        wizardData.services = data.services || [];
        wizardData.keywords = data.keywords || [];

        document.getElementById('wizScanContent').style.display = 'none';
        document.getElementById('wizScanResults').style.display = '';
        document.getElementById('wizStep2Footer').style.display = '';
    })
    .catch(function() {
        clearInterval(interval);
        showScanSkipped('Network error during scan');
    });
}

function showScanSkipped(msg) {
    document.getElementById('wizScanContent').style.display = 'none';
    document.getElementById('wizScanResults').style.display = '';
    document.getElementById('wizStep2Footer').style.display = '';
    if (msg) {
        showToast(msg + ' — enter info manually.', 'warning');
    }
}

function startThemeSuggestions() {
    document.getElementById('wizThemeLoading').style.display = '';
    document.getElementById('wizThemeResults').style.display = 'none';
    document.getElementById('wizStep4Footer').style.display = 'none';

    fetch('<?= BASE_URL ?>/wizard/suggest-themes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            company_name: wizardData.company_name,
            industry: wizardData.industry,
            services: wizardData.services,
            about: wizardData.about,
            keywords: wizardData.keywords,
            csrf_token: '<?= $csrfToken ?>'
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        suggestedThemes = data.themes || [];
        renderThemeList();
    })
    .catch(function() {
        suggestedThemes = [];
        renderThemeList();
        showToast('Could not generate theme suggestions', 'warning');
    });
}

function renderThemeList() {
    var list = document.getElementById('wizThemeList');
    list.innerHTML = '';
    if (suggestedThemes.length === 0) {
        list.innerHTML = '<li style="padding:20px;text-align:center;color:var(--text-muted)">No themes suggested. You can add themes manually in Content Strategy.</li>';
    } else {
        suggestedThemes.forEach(function(theme, i) {
            var li = document.createElement('li');
            li.className = 'theme-check-item';
            li.innerHTML = '<input type="checkbox" id="wizTheme_' + i + '" checked>'
                + '<div>'
                + '<div class="theme-check-name">' + escHtml(theme.name || 'Theme ' + (i+1)) + '</div>'
                + '<div class="theme-check-desc">' + escHtml(theme.description || '') + '</div>'
                + '</div>';
            list.appendChild(li);
        });
    }
    document.getElementById('wizThemeLoading').style.display = 'none';
    document.getElementById('wizThemeResults').style.display = '';
    document.getElementById('wizStep4Footer').style.display = '';
}

function wizToggleAllThemes(checked) {
    document.querySelectorAll('[id^="wizTheme_"]').forEach(function(cb) { cb.checked = checked; });
}

function gatherSelectedThemes() {
    wizardData.themes = [];
    suggestedThemes.forEach(function(theme, i) {
        var cb = document.getElementById('wizTheme_' + i);
        if (cb && cb.checked) {
            wizardData.themes.push(theme);
        }
    });
}

function escHtml(str) {
    var d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function buildSummary() {
    var html = '<div class="summary-row"><span class="summary-label">Company</span><span class="summary-value">' + escHtml(wizardData.company_name) + '</span></div>';
    if (wizardData.website) html += '<div class="summary-row"><span class="summary-label">Website</span><span class="summary-value">' + escHtml(wizardData.website) + '</span></div>';
    if (wizardData.phone) html += '<div class="summary-row"><span class="summary-label">Phone</span><span class="summary-value">' + escHtml(wizardData.phone) + '</span></div>';
    if (wizardData.industry) html += '<div class="summary-row"><span class="summary-label">Industry</span><span class="summary-value">' + escHtml(wizardData.industry) + '</span></div>';
    if (wizardData.tagline) html += '<div class="summary-row"><span class="summary-label">Tagline</span><span class="summary-value">' + escHtml(wizardData.tagline) + '</span></div>';
    html += '<div class="summary-row"><span class="summary-label">Primary Color</span><span class="summary-value"><span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:' + wizardData.primary_color + ';vertical-align:middle;margin-right:6px"></span>' + wizardData.primary_color + '</span></div>';
    html += '<div class="summary-row"><span class="summary-label">Themes</span><span class="summary-value">' + wizardData.themes.length + ' selected</span></div>';
    document.getElementById('wizSummary').innerHTML = html;
}

function wizComplete() {
    var btn = document.getElementById('wizCompleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var payload = Object.assign({}, wizardData, { csrf_token: '<?= $csrfToken ?>' });

    fetch('<?= BASE_URL ?>/wizard/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            showBrandReveal();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Complete Setup';
            showToast(result.error || 'Save failed', 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Complete Setup';
        showToast('Network error', 'error');
    });
}

function showBrandReveal() {
    var color = wizardData.primary_color || '<?= $primaryColor ?>';
    var company = wizardData.company_name || 'Your Company';

    // Replace wizard content with brand reveal
    var overlay = document.getElementById('wizardOverlay');
    overlay.innerHTML = '<div id="brandReveal" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(165deg,' + color + ' 0%, color-mix(in srgb, ' + color + ' 40%, #0a0a0a) 60%, #0a0a0a 100%);position:relative;overflow:hidden">'
        // Ambient mist
        + '<div style="position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,0.06),transparent 60%);animation:brMist 4s ease-in-out infinite"></div>'
        // Large atom orbits
        + '<div style="position:absolute;width:400px;height:150px;border:1px solid rgba(255,255,255,0.06);border-radius:50%;animation:brSpin 7s linear infinite">'
        + '<div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 10px rgba(255,255,255,0.5);opacity:0.6"></div></div>'
        + '<div style="position:absolute;width:380px;height:140px;border:1px solid rgba(255,255,255,0.05);border-radius:50%;animation:brSpin 5.5s linear infinite reverse;transform:rotate(55deg)">'
        + '<div style="position:absolute;width:4px;height:4px;background:rgba(255,255,255,0.8);border-radius:50%;bottom:-2px;left:calc(50% - 2px);box-shadow:0 0 8px rgba(255,255,255,0.4);opacity:0.5"></div></div>'
        + '<div style="position:absolute;width:120px;height:350px;border:1px solid rgba(255,255,255,0.04);border-radius:50%;animation:brSpin 8s linear infinite;transform:rotate(20deg)">'
        + '<div style="position:absolute;width:4px;height:4px;background:rgba(255,255,255,0.7);border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 8px rgba(255,255,255,0.3);opacity:0.4"></div></div>'
        // Orbiting ring
        + '<div style="position:absolute;width:200px;height:200px;border:2px solid rgba(255,255,255,0.1);border-radius:50%;animation:brSpin 4s linear infinite">'
        + '<div style="position:absolute;width:8px;height:8px;background:#fff;border-radius:50%;top:-4px;left:calc(50% - 4px);box-shadow:0 0 14px rgba(255,255,255,0.6);animation:brGlow 1.2s ease-in-out infinite alternate"></div>'
        + '<div style="position:absolute;width:6px;height:6px;background:#fff;border-radius:50%;bottom:-3px;left:calc(50% - 3px);box-shadow:0 0 12px rgba(255,255,255,0.5);animation:brGlow 1.2s ease-in-out infinite alternate;animation-delay:-0.6s"></div>'
        + '</div>'
        // Pulse rings
        + '<div style="position:absolute;width:140px;height:140px;border:2px solid rgba(255,255,255,0.25);border-radius:50%;animation:brPulse 2.4s ease-out infinite"></div>'
        + '<div style="position:absolute;width:140px;height:140px;border:2px solid rgba(255,255,255,0.25);border-radius:50%;animation:brPulse 2.4s ease-out infinite;animation-delay:0.6s"></div>'
        + '<div style="position:absolute;width:140px;height:140px;border:1px solid rgba(255,255,255,0.15);border-radius:50%;animation:brPulse 2.4s ease-out infinite;animation-delay:1.2s"></div>'
        // Content
        + '<div style="position:relative;z-index:10;text-align:center">'
        + '<div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.12);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 0 40px rgba(255,255,255,0.06)">'
        + '<img src="<?= BASE_URL ?>/favicon-48.png" style="width:52px;height:52px;filter:brightness(0) invert(1)" alt="">'
        + '</div>'
        + '<div id="brTypewriter" style="font-size:22px;font-weight:700;color:#fff;margin-bottom:8px;min-height:30px;letter-spacing:-0.02em"></div>'
        + '<div style="font-size:14px;color:rgba(255,255,255,0.55)">' + escHtml(company) + '</div>'
        + '</div>'
        // Particles — more of them, varied sizes
        + '<div style="position:absolute;inset:0;overflow:hidden;pointer-events:none">'
        + Array.from({length:20}, function(_,i) {
            var sz = 2 + Math.random() * 4;
            var x = 3 + Math.random() * 94;
            var dur = 3 + Math.random() * 3;
            var del = Math.random() * 4;
            return '<span style="position:absolute;width:'+sz+'px;height:'+sz+'px;border-radius:50%;background:rgba(255,255,255,'+(0.3+Math.random()*0.4).toFixed(1)+');left:'+x.toFixed(0)+'%;animation:brFloat '+dur.toFixed(1)+'s ease-in-out infinite;animation-delay:-'+del.toFixed(1)+'s;opacity:0"></span>';
          }).join('')
        + '</div>'
        + '</div>';

    // Add keyframes
    var style = document.createElement('style');
    style.textContent = '@keyframes brSpin{to{transform:rotate(360deg)}}@keyframes brGlow{0%{opacity:.5;transform:scale(.8)}100%{opacity:1;transform:scale(1.3)}}@keyframes brPulse{0%{transform:scale(1);opacity:.5}100%{transform:scale(2.5);opacity:0}}@keyframes brFloat{0%{bottom:-10px;opacity:0;transform:scale(.5)}20%{opacity:.6}80%{opacity:.2}100%{bottom:110%;opacity:0;transform:scale(1.2)}}@keyframes brMist{0%,100%{opacity:.4;transform:scale(1)}50%{opacity:.65;transform:scale(1.1)}}';
    document.head.appendChild(style);

    // Typewriter effect
    var messages = ['Building your profile...', 'Applying your brand...', 'Configuring themes...', 'Almost ready...', 'Welcome aboard!'];
    var msgIdx = 0;
    var charIdx = 0;
    var typeEl = document.getElementById('brTypewriter');

    function typewrite() {
        if (msgIdx >= messages.length) {
            setTimeout(function() {
                window.location.href = '<?= BASE_URL ?>/dashboard';
            }, 800);
            return;
        }
        var msg = messages[msgIdx];
        if (charIdx <= msg.length) {
            typeEl.textContent = msg.substring(0, charIdx);
            charIdx++;
            setTimeout(typewrite, 40);
        } else {
            setTimeout(function() {
                charIdx = 0;
                msgIdx++;
                typewrite();
            }, 1000);
        }
    }
    setTimeout(typewrite, 400);
}
</script>
