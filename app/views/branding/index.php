<?php
$b = $branding ?? [];
$companyName = htmlspecialchars($b['company_name'] ?? '');
$tagline = htmlspecialchars($b['tagline'] ?? '');
$website = htmlspecialchars($b['website'] ?? '');
$phone = htmlspecialchars($b['phone'] ?? '');
$industry = htmlspecialchars($b['industry'] ?? '');
$aboutCompany = htmlspecialchars($b['about_company'] ?? '');
$keyServices = htmlspecialchars($b['key_services'] ?? '');
$industryKeywords = htmlspecialchars($b['industry_keywords'] ?? '');
$firstComment = htmlspecialchars($b['first_comment'] ?? '');
$primaryColor = htmlspecialchars($b['primary_color'] ?? '#6366f1');
$logoUrl = $b['logo_url'] ?? '';
$loginBgUrl = $b['login_bg_url'] ?? '';
$particlesEnabled = ($b['particles_enabled'] ?? 1) ? true : false;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<style>
.branding-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
}
@media (max-width: 768px) {
    .branding-grid { grid-template-columns: 1fr; }
}

.color-picker-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.color-picker-row input[type="color"] {
    width: 44px;
    height: 44px;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    padding: 2px;
    cursor: pointer;
    background: var(--bg-input);
    flex-shrink: 0;
}
.color-picker-row input[type="color"]::-webkit-color-swatch-wrapper { padding: 2px; }
.color-picker-row input[type="color"]::-webkit-color-swatch { border: none; border-radius: 6px; }
.color-picker-row .form-input { flex: 1; }
.color-swatch {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    border: 2px solid var(--border);
    flex-shrink: 0;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 26px;
    cursor: pointer;
    transition: all var(--transition);
}
.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    left: 3px;
    top: 2px;
    background: var(--text-muted);
    border-radius: 50%;
    transition: all var(--transition);
}
.toggle-switch input:checked + .toggle-slider {
    background: var(--primary);
    border-color: var(--primary);
}
.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(22px);
    background: #fff;
}

.file-upload-area {
    border: 2px dashed var(--border);
    border-radius: var(--radius-md);
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all var(--transition);
    background: var(--bg-input);
    position: relative;
}
.file-upload-area:hover {
    border-color: var(--primary);
    background: rgba(var(--primary-rgb), 0.04);
}
.file-upload-area.logo-upload-area:hover {
    border-color: transparent;
    filter: brightness(1.15);
}
.file-upload-area input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
.file-upload-icon {
    font-size: 24px;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.file-upload-text {
    font-size: 13px;
    color: var(--text-secondary);
    font-weight: 500;
}
.file-upload-hint {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 4px;
}
.file-upload-preview {
    margin-bottom: 12px;
}
.file-upload-preview img {
    max-height: 80px;
    max-width: 100%;
    border-radius: var(--radius-sm);
    margin: 0 auto;
    object-fit: contain;
}
.file-upload-preview-bg img {
    max-height: 100px;
    max-width: 100%;
    border-radius: var(--radius-sm);
    margin: 0 auto;
    object-fit: cover;
}
.file-upload-preview { position: relative; }
.file-remove-btn {
    position: absolute;
    top: -6px;
    right: calc(50% - 46px);
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: var(--danger, #ef4444);
    color: #fff;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    z-index: 5;
    transition: transform 0.15s ease;
}
.file-remove-btn:hover { transform: scale(1.15); }

/* Live Preview */
.login-preview-wrapper {
    background: linear-gradient(180deg, <?= $primaryColor ?> 0%, #0a0a0a 60%, #000000 100%);
    border-radius: var(--radius-lg);
    padding: 40px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    position: relative;
    overflow: hidden;
}
.login-preview-card {
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 20px;
    padding: 32px 28px;
    width: 260px;
    text-align: center;
}
.preview-logo-area {
    text-align: center;
    margin: 0 auto 12px;
}
.preview-logo-area img {
    max-width: 140px;
    max-height: 40px;
    object-fit: contain;
    margin: 0 auto;
    filter: brightness(0) invert(1);
}
.preview-logo-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    margin: 0 auto;
}
.preview-heading {
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 4px;
}
.preview-subtext {
    color: rgba(255,255,255,0.45);
    font-size: 11px;
    margin-bottom: 20px;
}
.preview-input {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    margin-bottom: 10px;
    color: rgba(255,255,255,0.3);
    font-size: 11px;
}
.preview-btn {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    margin-top: 4px;
}
</style>

<div style="margin-bottom:28px">
    <a href="<?= BASE_URL ?>/wizard?rerun=1" class="wizard-cta-btn">
        <div class="wizard-cta-inner">
            <div class="wizard-cta-icon"><i class="fas fa-magic"></i></div>
            <div class="wizard-cta-text">
                <span class="wizard-cta-title">Run Setup Wizard</span>
                <span class="wizard-cta-desc">Scan your website, set up branding, and generate content themes with AI</span>
            </div>
            <i class="fas fa-arrow-right wizard-cta-arrow"></i>
        </div>
    </a>
</div>
<style>
.wizard-cta-btn {
    display: block;
    text-decoration: none;
    padding: 22px 28px;
    border-radius: var(--radius-lg);
    background: linear-gradient(135deg, <?= $primaryColor ?> 0%, color-mix(in srgb, <?= $primaryColor ?> 55%, #0a0a0a) 100%);
    color: #fff;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    animation: wizCtaBreath 3s ease-in-out infinite;
}
.wizard-cta-btn::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -75%;
    width: 50%;
    height: 200%;
    background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.2) 45%, rgba(255,255,255,0.06) 50%, transparent 55%);
    animation: wizCtaSweep 4s ease-in-out infinite;
    pointer-events: none;
}
.wizard-cta-btn:hover {
    transform: translateY(-3px) scale(1.01);
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    animation: none;
}
.wizard-cta-inner {
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    z-index: 1;
}
.wizard-cta-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    backdrop-filter: blur(4px);
}
.wizard-cta-text { flex: 1; }
.wizard-cta-title { display: block; font-size: 16px; font-weight: 700; letter-spacing: -0.01em; }
.wizard-cta-desc { display: block; font-size: 12px; color: rgba(255,255,255,0.6); margin-top: 3px; line-height: 1.4; }
.wizard-cta-arrow { font-size: 16px; opacity: 0.5; transition: all 0.3s ease; flex-shrink: 0; }
.wizard-cta-btn:hover .wizard-cta-arrow { opacity: 1; transform: translateX(4px); }
@keyframes wizCtaBreath {
    0%,100% { box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
    50% { box-shadow: 0 6px 28px rgba(0,0,0,0.22), 0 0 12px rgba(var(--primary-rgb), 0.15); }
}
@keyframes wizCtaSweep {
    0%,70% { left: -75%; opacity: 0; }
    75% { opacity: 1; }
    100% { left: 125%; opacity: 0; }
}
</style>

<form method="POST" action="<?= BASE_URL ?>/branding/save" enctype="multipart/form-data" id="brandingForm">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <div class="card" style="margin-bottom:28px">
        <div class="card-header">
            <div>
                <div class="card-title">Brand Identity</div>
                <div class="card-subtitle">Customize how your app looks and feels</div>
            </div>
        </div>

        <div class="branding-grid">
            <!-- Left Column -->
            <div>
                <div class="form-group">
                    <label class="form-label" for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-input" value="<?= $companyName ?>" placeholder="Your company name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="tagline">Tagline</label>
                    <input type="text" id="tagline" name="tagline" class="form-input" value="<?= $tagline ?>" placeholder="A short tagline or slogan">
                </div>
                <div class="form-group">
                    <label class="form-label" for="website">Website</label>
                    <input type="text" id="website" name="website" class="form-input" value="<?= $website ?>" placeholder="e.g. solidtech.ca">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-input" value="<?= $phone ?>" placeholder="e.g. 587-557-1234">
                </div>

                <div class="form-group">
                    <label class="form-label" for="industry">Industry</label>
                    <select id="industry" name="industry" class="form-input">
                        <option value="">Select your industry</option>
                        <?php foreach (['Information Technology', 'Healthcare', 'Finance & Banking', 'Real Estate', 'Education', 'Retail & E-commerce', 'Manufacturing', 'Legal', 'Marketing & Advertising', 'Construction', 'Other'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $industry === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="about_company">About Your Company <span style="font-weight:400;color:var(--text-muted)">(used by AI for context)</span></label>
                    <textarea id="about_company" name="about_company" class="form-textarea" rows="3" placeholder="A brief description of what your company does and who you serve"><?= $aboutCompany ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="key_services">Key Services <span style="font-weight:400;color:var(--text-muted)">(comma-separated)</span></label>
                    <input type="text" id="key_services" name="key_services" class="form-input" value="<?= $keyServices ?>" placeholder="e.g. Managed IT, Cybersecurity, Cloud Solutions">
                </div>

                <div class="form-group">
                    <label class="form-label" for="industry_keywords">Industry Keywords <span style="font-weight:400;color:var(--text-muted)">(comma-separated, used by AI)</span></label>
                    <input type="text" id="industry_keywords" name="industry_keywords" class="form-input" value="<?= $industryKeywords ?>" placeholder="e.g. IT support, network security, HIPAA compliance">
                </div>

                <div class="form-group">
                    <label class="form-label" for="first_comment">Default First Comment</label>
                    <textarea id="first_comment" name="first_comment" class="form-textarea" rows="3" placeholder="This comment will be automatically posted on every new post. e.g. &#x1F4DE; 587-557-1234&#10;&#x1F310; https://solidtech.ca"><?= $firstComment ?></textarea>
                    <div class="text-small text-muted" style="margin-top:4px">Applied to all posts unless overridden on the individual post.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Brand Color</label>
                    <div class="color-picker-row">
                        <input type="color" id="primary_color" name="primary_color" value="<?= $primaryColor ?>">
                        <input type="text" id="primary_color_text" class="form-input" value="<?= $primaryColor ?>" maxlength="7" placeholder="#6366f1">
                        <div class="color-swatch" id="primary_swatch" style="background:<?= $primaryColor ?>"></div>
                    </div>
                    <small style="display:block;margin-top:6px;color:var(--text-muted);font-size:12px">This color drives buttons, accents, gradients, charts, and report styling across the app.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Login Particles Effect</label>
                    <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
                        <label class="toggle-switch">
                            <input type="checkbox" name="particles_enabled" value="1" <?= $particlesEnabled ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="text-small" style="color:var(--text-secondary)">Show animated particles on login screen</span>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <div class="form-group">
                    <label class="form-label">Logo</label>
                    <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                    <div class="file-upload-area logo-upload-area" id="logoUploadArea" style="background: linear-gradient(135deg, <?= $primaryColor ?> 0%, color-mix(in srgb, <?= $primaryColor ?> 55%, #000000) 100%); border-color: transparent;">
                        <?php if ($logoUrl): ?>
                            <div class="file-upload-preview" id="logoPreviewWrap">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('logo')" title="Remove logo"><i class="fas fa-times"></i></button>
                                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Current logo" id="logoPreview">
                            </div>
                        <?php else: ?>
                            <div class="file-upload-preview" id="logoPreviewWrap" style="display:none">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('logo')" title="Remove logo"><i class="fas fa-times"></i></button>
                                <img src="" alt="Logo preview" id="logoPreview">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload-icon" style="color: rgba(255,255,255,0.7)"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div class="file-upload-text" style="color: rgba(255,255,255,0.9)">Click to upload logo</div>
                        <div class="file-upload-hint" style="color: rgba(255,255,255,0.5)">JPG, PNG, GIF, WebP or SVG (max 2MB)</div>
                        <input type="file" name="logo" accept="image/*" onchange="previewFile(this, 'logoPreview', 'logoPreviewWrap')">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Dark Logo (for light backgrounds)</label>
                    <input type="hidden" name="remove_dark_logo" id="remove_dark_logo" value="0">
                    <div class="file-upload-area" style="background:#f8fafc;border-color:#e2e8f0">
                        <?php $darkLogoUrl = $b['dark_logo_url'] ?? ''; if ($darkLogoUrl): ?>
                            <div class="file-upload-preview" id="darkLogoPreviewWrap">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('dark_logo')" title="Remove dark logo"><i class="fas fa-times"></i></button>
                                <img src="<?= htmlspecialchars($darkLogoUrl) ?>" alt="Current dark logo" id="darkLogoPreview">
                            </div>
                        <?php else: ?>
                            <div class="file-upload-preview" id="darkLogoPreviewWrap" style="display:none">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('dark_logo')" title="Remove dark logo"><i class="fas fa-times"></i></button>
                                <img src="" alt="Dark logo preview" id="darkLogoPreview">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload-icon" style="color:#64748b"><i class="fas fa-moon"></i></div>
                        <div class="file-upload-text" style="color:#1e293b">Click to upload dark variant</div>
                        <div class="file-upload-hint" style="color:#94a3b8">Used on generated PDFs &amp; report pages. Optional.</div>
                        <input type="file" name="dark_logo" accept="image/*" onchange="previewFile(this, 'darkLogoPreview', 'darkLogoPreviewWrap')">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Login Background</label>
                    <input type="hidden" name="remove_login_bg" id="remove_login_bg" value="0">
                    <div class="file-upload-area">
                        <?php if ($loginBgUrl): ?>
                            <div class="file-upload-preview file-upload-preview-bg" id="bgPreviewWrap">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('login_bg')" title="Remove background"><i class="fas fa-times"></i></button>
                                <img src="<?= htmlspecialchars($loginBgUrl) ?>" alt="Current background" id="bgPreview">
                            </div>
                        <?php else: ?>
                            <div class="file-upload-preview file-upload-preview-bg" id="bgPreviewWrap" style="display:none">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('login_bg')" title="Remove background"><i class="fas fa-times"></i></button>
                                <img src="" alt="Background preview" id="bgPreview">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload-icon"><i class="fas fa-image"></i></div>
                        <div class="file-upload-text">Click to upload background</div>
                        <div class="file-upload-hint">Recommended: 1920x1080 or larger</div>
                        <input type="file" name="login_bg" accept="image/*" onchange="previewFile(this, 'bgPreview', 'bgPreviewWrap')">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Favicon</label>
                    <input type="hidden" name="remove_favicon" id="remove_favicon" value="0">
                    <div class="file-upload-area">
                        <?php $faviconUrl = $b['favicon_url'] ?? ''; if ($faviconUrl): ?>
                            <div class="file-upload-preview" id="faviconPreviewWrap">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('favicon')" title="Remove favicon"><i class="fas fa-times"></i></button>
                                <img src="<?= htmlspecialchars($faviconUrl) ?>" alt="Current favicon" id="faviconPreview" style="max-height:48px;image-rendering:pixelated">
                            </div>
                        <?php else: ?>
                            <div class="file-upload-preview" id="faviconPreviewWrap" style="display:none">
                                <button type="button" class="file-remove-btn" onclick="removeUpload('favicon')" title="Remove favicon"><i class="fas fa-times"></i></button>
                                <img src="" alt="Favicon preview" id="faviconPreview" style="max-height:48px;image-rendering:pixelated">
                            </div>
                        <?php endif; ?>
                        <div class="file-upload-icon"><i class="fas fa-globe"></i></div>
                        <div class="file-upload-text">Click to upload favicon</div>
                        <div class="file-upload-hint">PNG or ICO, square image (auto-resized to 16/32/48px)</div>
                        <input type="file" name="favicon" accept="image/png,image/x-icon,image/svg+xml,image/jpeg" onchange="previewFile(this, 'faviconPreview', 'faviconPreviewWrap')">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Personalization -->
    <div class="card" style="margin-bottom:28px">
        <div class="card-header">
            <div>
                <div class="card-title">Personalization</div>
                <div class="card-subtitle">Make the experience feel tailored — your first name will appear in greetings and messages throughout the app</div>
            </div>
        </div>
        <div style="max-width:400px">
            <div class="form-group">
                <label class="form-label" for="first_name">Your First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-input" value="<?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>" placeholder="e.g. Emily">
            </div>
        </div>
    </div>

    <!-- API Settings (saved separately via JS) -->
    <div class="card" style="margin-bottom:28px">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-key" style="margin-right:6px;color:var(--primary)"></i> API Settings</div>
                <div class="card-subtitle">Connect your AI services for content and image generation</div>
            </div>
        </div>
        <div style="padding:0 24px 24px">
            <div class="branding-grid">
                <div>
                    <h3 style="font-size:14px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:8px">
                        <span style="display:inline-flex;width:24px;height:24px;align-items:center;justify-content:center;border-radius:6px;background:rgba(var(--primary-rgb),0.1);font-size:11px;color:var(--primary)"><i class="fas fa-comment-dots"></i></span>
                        OpenRouter — Text Generation
                    </h3>
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">Generates social media copy, captions, and hashtags.</p>
                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <div style="display:flex;gap:8px">
                            <input type="password" id="openrouter_key" class="form-input" placeholder="sk-or-v1-..." value="<?= htmlspecialchars(OPENROUTER_API_KEY) ?>" style="flex:1">
                            <button type="button" class="btn btn-secondary" onclick="togglePasswordVisibility('openrouter_key', this)" style="padding:8px 12px;white-space:nowrap">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Model</label>
                        <select id="openrouter_model" class="form-input">
                            <option value="openai/gpt-4o-mini" <?= OPENROUTER_MODEL === 'openai/gpt-4o-mini' ? 'selected' : '' ?>>GPT-4o Mini (fast, cheap)</option>
                            <option value="openai/gpt-4o" <?= OPENROUTER_MODEL === 'openai/gpt-4o' ? 'selected' : '' ?>>GPT-4o (balanced)</option>
                            <option value="anthropic/claude-3.5-sonnet" <?= OPENROUTER_MODEL === 'anthropic/claude-3.5-sonnet' ? 'selected' : '' ?>>Claude 3.5 Sonnet</option>
                            <option value="google/gemini-pro-1.5" <?= OPENROUTER_MODEL === 'google/gemini-pro-1.5' ? 'selected' : '' ?>>Gemini Pro 1.5</option>
                            <option value="meta-llama/llama-3.1-70b-instruct" <?= OPENROUTER_MODEL === 'meta-llama/llama-3.1-70b-instruct' ? 'selected' : '' ?>>Llama 3.1 70B</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="testApi('openrouter')" id="testOpenRouterBtn" style="margin-top:4px">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                    <span id="openrouterStatus" style="margin-left:10px;font-size:13px"></span>
                </div>
                <div>
                    <h3 style="font-size:14px;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:8px">
                        <span style="display:inline-flex;width:24px;height:24px;align-items:center;justify-content:center;border-radius:6px;background:rgba(var(--primary-rgb),0.1);font-size:11px;color:var(--primary)"><i class="fas fa-image"></i></span>
                        Kie.ai — Image Generation
                    </h3>
                    <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">Generates social media images using Google NanoBanana2.</p>
                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <div style="display:flex;gap:8px">
                            <input type="password" id="kie_key" class="form-input" placeholder="Your Kie.ai API key" value="<?= htmlspecialchars(KIE_API_KEY) ?>" style="flex:1">
                            <button type="button" class="btn btn-secondary" onclick="togglePasswordVisibility('kie_key', this)" style="padding:8px 12px;white-space:nowrap">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
                        <i class="fas fa-external-link-alt" style="margin-right:4px"></i>
                        Get your key at <a href="https://kie.ai/api-key" target="_blank" style="color:var(--primary);text-decoration:underline">kie.ai/api-key</a>
                    </p>
                    <button type="button" class="btn btn-secondary" onclick="testApi('kie')" id="testKieBtn" style="margin-top:4px">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                    <span id="kieStatus" style="margin-left:10px;font-size:13px"></span>
                </div>
            </div>
            <div style="border-top:1px solid var(--border);margin-top:20px;padding-top:16px;display:flex;justify-content:flex-end">
                <button type="button" class="btn btn-primary" onclick="saveApiKeys()" id="saveApiBtn" style="padding:10px 24px">
                    <i class="fas fa-save"></i> Save API Keys
                </button>
            </div>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="card" style="margin-bottom:28px">
        <div class="card-header">
            <div>
                <div class="card-title">Login Preview</div>
                <div class="card-subtitle">See how your login screen will look</div>
            </div>
        </div>
        <div class="login-preview-wrapper" id="previewWrapper">
            <div class="login-preview-card">
                <div class="preview-logo-area" id="previewLogo">
                    <?php if ($logoUrl): ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo">
                    <?php else: ?>
                        <div class="preview-logo-placeholder" id="previewLogoPlaceholder" style="background:<?= $primaryColor ?>">S</div>
                    <?php endif; ?>
                </div>
                <div class="preview-heading" id="previewCompanyName" <?= $logoUrl ? 'style="display:none"' : '' ?>><?= $companyName ?: 'Your Company' ?></div>
                <div class="preview-subtext">Sign in to your account</div>
                <div class="preview-input">Username</div>
                <div class="preview-input">Password</div>
                <div class="preview-btn" id="previewBtn" style="background:<?= $primaryColor ?>">Sign In</div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div style="display:flex;justify-content:flex-end">
        <button type="submit" class="btn btn-primary" style="padding:12px 32px;font-size:15px">
            <i class="fas fa-save"></i> Save Branding
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Success toast on save
    <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
    showToast('Branding settings saved successfully!', 'success');
    <?php endif; ?>

    // Sync color picker with text input
    var primaryPicker = document.getElementById('primary_color');
    var primaryText = document.getElementById('primary_color_text');
    var primarySwatch = document.getElementById('primary_swatch');

    primaryPicker.addEventListener('input', function() {
        primaryText.value = this.value;
        primarySwatch.style.background = this.value;
        updatePreview();
    });
    primaryText.addEventListener('input', function() {
        if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
            primaryPicker.value = this.value;
            primarySwatch.style.background = this.value;
            updatePreview();
        }
    });

    // Live preview updates
    var companyNameInput = document.getElementById('company_name');
    companyNameInput.addEventListener('input', function() {
        document.getElementById('previewCompanyName').textContent = this.value || 'Your Company';
    });

    function updatePreview() {
        var color = primaryPicker.value;
        document.getElementById('previewBtn').style.background = color;
        document.getElementById('previewWrapper').style.background =
            'linear-gradient(180deg, ' + color + ' 0%, #0a0a0a 60%, #000000 100%)';
        var placeholder = document.getElementById('previewLogoPlaceholder');
        if (placeholder) {
            placeholder.style.background = color;
        }
        // Update logo upload area gradient — uses the brand color + a darker shade of itself
        var logoArea = document.getElementById('logoUploadArea');
        if (logoArea) {
            logoArea.style.background = 'linear-gradient(135deg, ' + color + ' 0%, color-mix(in srgb, ' + color + ' 55%, #000) 100%)';
        }
    }
});

function removeUpload(field) {
    // Map field names to their elements
    var map = {
        'logo': { preview: 'logoPreviewWrap', img: 'logoPreview', input: 'logo', hidden: 'remove_logo' },
        'dark_logo': { preview: 'darkLogoPreviewWrap', img: 'darkLogoPreview', input: 'dark_logo', hidden: 'remove_dark_logo' },
        'login_bg': { preview: 'bgPreviewWrap', img: 'bgPreview', input: 'login_bg', hidden: 'remove_login_bg' },
        'favicon': { preview: 'faviconPreviewWrap', img: 'faviconPreview', input: 'favicon', hidden: 'remove_favicon' },
    };
    var m = map[field];
    if (!m) return;

    // Hide the preview
    var wrap = document.getElementById(m.preview);
    if (wrap) wrap.style.display = 'none';

    // Clear the image src
    var img = document.getElementById(m.img);
    if (img) img.src = '';

    // Clear the file input
    var fileInput = document.querySelector('input[name="' + m.input + '"]');
    if (fileInput) fileInput.value = '';

    // Set the hidden removal flag
    var hidden = document.getElementById(m.hidden);
    if (hidden) hidden.value = '1';

    // Update live preview if logo was removed
    if (field === 'logo') {
        var previewLogo = document.getElementById('previewLogo');
        var compName = document.getElementById('company_name').value || 'Your Company';
        previewLogo.innerHTML = '<div class="preview-logo-placeholder" id="previewLogoPlaceholder" style="background:' + document.getElementById('primary_color').value + '">' + compName.charAt(0).toUpperCase() + '</div>';
        document.getElementById('previewCompanyName').style.display = '';
    }

    showToast(field.replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); }) + ' removed. Save to apply.', 'info');
}

function previewFile(input, imgId, wrapId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById(imgId);
            img.src = e.target.result;
            if (wrapId) {
                var wrap = document.getElementById(wrapId);
                if (wrap) wrap.style.display = '';
            }
            if (imgId === 'logoPreview') {
                var previewLogo = document.getElementById('previewLogo');
                previewLogo.innerHTML = '<img src="' + e.target.result + '" alt="Logo">';
                document.getElementById('previewCompanyName').style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

function saveApiKeys() {
    var btn = document.getElementById('saveApiBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var data = {
        openrouter_key: document.getElementById('openrouter_key').value,
        openrouter_model: document.getElementById('openrouter_model').value,
        kie_key: document.getElementById('kie_key').value,
        csrf_token: '<?= $csrfToken ?>'
    };

    fetch('<?= BASE_URL ?>/branding/save-api', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save API Keys';
        if (result.success) {
            showToast('API keys saved successfully', 'success');
        } else {
            showToast(result.error || 'Failed to save', 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save API Keys';
        showToast('Network error', 'error');
    });
}

function testApi(service) {
    var btn = document.getElementById('test' + (service === 'openrouter' ? 'OpenRouter' : 'Kie') + 'Btn');
    var status = document.getElementById(service + (service === 'openrouter' ? 'Status' : 'Status'));
    if (service === 'openrouter') status = document.getElementById('openrouterStatus');
    else status = document.getElementById('kieStatus');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    status.innerHTML = '';

    var key = service === 'openrouter'
        ? document.getElementById('openrouter_key').value
        : document.getElementById('kie_key').value;

    fetch('<?= BASE_URL ?>/branding/test-api', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ service: service, key: key, csrf_token: '<?= $csrfToken ?>' })
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Test Connection';
        if (result.ok) {
            status.innerHTML = '<span style="color:var(--success)"><i class="fas fa-check-circle"></i> Connected</span>';
        } else {
            status.innerHTML = '<span style="color:var(--danger)"><i class="fas fa-times-circle"></i> ' + (result.error || 'Failed') + '</span>';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Test Connection';
        status.innerHTML = '<span style="color:var(--danger)"><i class="fas fa-times-circle"></i> Network error</span>';
    });
}
</script>
