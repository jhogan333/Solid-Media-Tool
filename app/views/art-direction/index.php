<?php
$s = $settings ?? [];
$b = $branding ?? [];
$preview = $promptPreview ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<style>
.art-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
}
@media (max-width: 768px) {
    .art-grid { grid-template-columns: 1fr; }
}

.art-grid .card {
    border-left: 4px solid var(--primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
}
.art-grid .card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.03) 0%, transparent 50%);
    pointer-events: none;
}
.art-grid .card:hover {
    box-shadow: 0 8px 24px rgba(var(--primary-rgb), 0.1), 0 2px 8px rgba(0,0,0,0.04);
}
.slider-group {
    display: flex;
    align-items: center;
    gap: 12px;
}
.slider-group input[type="range"] {
    flex: 1;
    accent-color: var(--primary);
    height: 6px;
}
.slider-value {
    min-width: 36px;
    text-align: center;
    font-size: 14px;
    font-weight: 700;
    color: var(--primary);
    background: rgba(var(--primary-rgb), 0.08);
    border: 1px solid rgba(var(--primary-rgb), 0.15);
    border-radius: var(--radius-sm);
    padding: 4px 8px;
}

.preset-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
.preset-btn {
    padding: 12px 14px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s ease;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.preset-btn:hover {
    border-color: var(--primary);
    background: rgba(var(--primary-rgb), 0.08);
    color: var(--primary);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.1);
}
.preset-btn.active {
    border-color: var(--primary);
    background: rgba(var(--primary-rgb), 0.12);
    color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
}

.prompt-preview {
    width: 100%;
    min-height: 120px;
    padding: 14px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    font-size: 12px;
    line-height: 1.6;
    color: var(--text-secondary);
    font-family: 'SF Mono', 'Consolas', monospace;
    resize: none;
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

.section-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--primary);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid rgba(var(--primary-rgb), 0.15);
}

.prompt-preview {
    border-left: 3px solid rgba(var(--primary-rgb), 0.3);
}
</style>

<div class="art-grid">
    <!-- Left Column: Image Style Settings -->
    <div>
        <div class="card" style="margin-bottom:24px">
            <div class="card-header">
                <div>
                    <div class="card-title"><i class="fas fa-camera" style="margin-right:8px;color:var(--primary)"></i> Image Style</div>
                    <div class="card-subtitle">Control how AI-generated images look</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Default Image Style</label>
                <select id="image_style" class="form-input" onchange="updatePreview()">
                    <option value="photorealistic" <?= ($s['image_style'] ?? '') === 'photorealistic' ? 'selected' : '' ?>>Photorealistic</option>
                    <option value="mixed" <?= ($s['image_style'] ?? '') === 'mixed' ? 'selected' : '' ?>>Mixed (Photo + Graphics)</option>
                    <option value="technical_diagram" <?= ($s['image_style'] ?? '') === 'technical_diagram' ? 'selected' : '' ?>>Technical Diagram</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Realism Level</label>
                <div class="slider-group">
                    <span style="font-size:12px;color:var(--text-muted)">Stylized</span>
                    <input type="range" id="realism_level" min="1" max="10" value="<?= (int)($s['realism_level'] ?? 8) ?>" oninput="document.getElementById('realism_val').textContent=this.value;updatePreview()">
                    <span style="font-size:12px;color:var(--text-muted)">Hyper-Real</span>
                    <span class="slider-value" id="realism_val"><?= (int)($s['realism_level'] ?? 8) ?></span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Color Temperature</label>
                <select id="color_temperature" class="form-input" onchange="updatePreview()">
                    <option value="cold" <?= ($s['color_temperature'] ?? '') === 'cold' ? 'selected' : '' ?>>Cold (Blue Undertones, Deep Blacks)</option>
                    <option value="neutral" <?= ($s['color_temperature'] ?? '') === 'neutral' ? 'selected' : '' ?>>Neutral (Balanced)</option>
                    <option value="warm" <?= ($s['color_temperature'] ?? '') === 'warm' ? 'selected' : '' ?>>Warm (Golden Tones)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Contrast</label>
                <select id="contrast_level" class="form-input" onchange="updatePreview()">
                    <option value="subtle" <?= ($s['contrast_level'] ?? '') === 'subtle' ? 'selected' : '' ?>>Subtle</option>
                    <option value="balanced" <?= ($s['contrast_level'] ?? '') === 'balanced' ? 'selected' : '' ?>>Balanced</option>
                    <option value="punchy" <?= ($s['contrast_level'] ?? '') === 'punchy' ? 'selected' : '' ?>>Punchy (Recommended)</option>
                    <option value="maximum" <?= ($s['contrast_level'] ?? '') === 'maximum' ? 'selected' : '' ?>>Maximum</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Mood</label>
                <select id="mood" class="form-input" onchange="updatePreview()">
                    <option value="professional" <?= ($s['mood'] ?? '') === 'professional' ? 'selected' : '' ?>>Professional & Corporate</option>
                    <option value="dramatic" <?= ($s['mood'] ?? '') === 'dramatic' ? 'selected' : '' ?>>Dramatic & Cinematic</option>
                    <option value="moody_dark" <?= ($s['mood'] ?? '') === 'moody_dark' ? 'selected' : '' ?>>Moody & Dark</option>
                    <option value="clean_bright" <?= ($s['mood'] ?? '') === 'clean_bright' ? 'selected' : '' ?>>Clean & Bright</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Brand Color Bleed</label>
                <div class="slider-group">
                    <span style="font-size:12px;color:var(--text-muted)">None</span>
                    <input type="range" id="brand_color_bleed" min="0" max="100" value="<?= (int)($s['brand_color_bleed'] ?? 25) ?>" oninput="document.getElementById('bleed_val').textContent=this.value+'%';updatePreview()">
                    <span style="font-size:12px;color:var(--text-muted)">Heavy</span>
                    <span class="slider-value" id="bleed_val"><?= (int)($s['brand_color_bleed'] ?? 25) ?>%</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Illustration Frequency</label>
                <select id="illustration_limit" class="form-input" onchange="updatePreview()">
                    <option value="never" <?= ($s['illustration_limit'] ?? '') === 'never' ? 'selected' : '' ?>>Never (Always Photorealistic)</option>
                    <option value="max_1_per_week" <?= ($s['illustration_limit'] ?? '') === 'max_1_per_week' ? 'selected' : '' ?>>Max 1 Per Week</option>
                    <option value="max_2_per_week" <?= ($s['illustration_limit'] ?? '') === 'max_2_per_week' ? 'selected' : '' ?>>Max 2 Per Week</option>
                    <option value="no_limit" <?= ($s['illustration_limit'] ?? '') === 'no_limit' ? 'selected' : '' ?>>No Limit</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Things to Exclude from Images <span style="font-weight:400;color:var(--text-muted)">(negative prompt)</span></label>
                <textarea id="avoid_list" class="form-textarea" rows="3" oninput="updatePreview()" placeholder="children, pets, outdoor scenes, crowds, clutter, hands holding phones..."><?= htmlspecialchars($s['avoid_list'] ?? '') ?></textarea>
                <div class="text-small text-muted" style="margin-top:6px;line-height:1.5">
                    Comma-separated subjects or elements you never want appearing in generated images.
                    Style (photorealistic vs illustrated) is already controlled by the Realism Level above &mdash;
                    use this field for <em>content</em>, not style. Example: <code>children, pets, outdoor scenes, crowds, stock-photo handshakes</code>.
                </div>
            </div>
        </div>

        <!-- Style Presets -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header">
                <div>
                    <div class="card-title"><i class="fas fa-sliders-h" style="margin-right:8px;color:var(--primary)"></i> Quick Presets</div>
                    <div class="card-subtitle">One-click style presets — sets all values above</div>
                </div>
            </div>
            <div class="preset-grid">
                <button type="button" class="preset-btn" onclick="loadPreset('corporate_it')">
                    <i class="fas fa-building" style="margin-right:4px"></i> Corporate IT
                </button>
                <button type="button" class="preset-btn" onclick="loadPreset('tech_magazine')">
                    <i class="fas fa-newspaper" style="margin-right:4px"></i> Tech Magazine
                </button>
                <button type="button" class="preset-btn" onclick="loadPreset('dark_dramatic')">
                    <i class="fas fa-moon" style="margin-right:4px"></i> Dark & Dramatic
                </button>
                <button type="button" class="preset-btn" onclick="loadPreset('clean_professional')">
                    <i class="fas fa-sun" style="margin-right:4px"></i> Clean Professional
                </button>
            </div>
        </div>
    </div>

    <!-- Right Column: Watermark + Preview -->
    <div>
        <div class="card" style="margin-bottom:24px">
            <div class="card-header">
                <div>
                    <div class="card-title"><i class="fas fa-stamp" style="margin-right:8px;color:var(--primary)"></i> Watermark & Overlay</div>
                    <div class="card-subtitle">Control the branded overlay on generated images</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Enable Watermark</label>
                <div style="display:flex;align-items:center;gap:12px;margin-top:4px">
                    <label class="toggle-switch">
                        <input type="checkbox" id="watermark_enabled" <?= ($s['watermark_enabled'] ?? 1) ? 'checked' : '' ?> onchange="updatePreview()">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="text-small" style="color:var(--text-secondary)">Show logo, website, and gradient on images</span>
                </div>
            </div>

            <div id="watermark-fields">
                <div class="form-group">
                    <label class="form-label">Website Text</label>
                    <input type="text" id="watermark_website" class="form-input" value="<?= htmlspecialchars($s['watermark_website'] ?: ($b['website'] ?? '')) ?>" placeholder="e.g. yourcompany.com">
                    <div class="text-small text-muted" style="margin-top:4px">Displayed on the bottom of every generated image</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Logo Position</label>
                    <select id="watermark_logo_position" class="form-input">
                        <option value="bottom-left" <?= ($s['watermark_logo_position'] ?? '') === 'bottom-left' ? 'selected' : '' ?>>Bottom Left</option>
                        <option value="bottom-right" <?= ($s['watermark_logo_position'] ?? '') === 'bottom-right' ? 'selected' : '' ?>>Bottom Right</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Gradient Opacity</label>
                    <div class="slider-group">
                        <span style="font-size:12px;color:var(--text-muted)">Transparent</span>
                        <input type="range" id="watermark_gradient_opacity" min="0" max="100" value="<?= (int)($s['watermark_gradient_opacity'] ?? 85) ?>" oninput="document.getElementById('opacity_val').textContent=this.value+'%'">
                        <span style="font-size:12px;color:var(--text-muted)">Opaque</span>
                        <span class="slider-value" id="opacity_val"><?= (int)($s['watermark_gradient_opacity'] ?? 85) ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prompt Preview (hidden from UI — kept in DOM so updatePreview() still works) -->
        <div class="card" style="margin-bottom:24px;display:none" aria-hidden="true">
            <div class="card-header">
                <div>
                    <div class="card-title"><i class="fas fa-code" style="margin-right:8px;color:var(--primary)"></i> Prompt Preview</div>
                    <div class="card-subtitle">This text is appended to every image generation prompt</div>
                </div>
            </div>
            <textarea class="prompt-preview" id="prompt_preview" readonly><?= htmlspecialchars($preview) ?></textarea>
        </div>

        <!-- Save -->
        <div style="display:flex;justify-content:flex-end">
            <button type="button" class="btn btn-primary" onclick="saveArtDirection()" id="saveBtn" style="padding:12px 32px;font-size:15px">
                <i class="fas fa-save"></i> Save Art Direction
            </button>
        </div>
    </div>
</div>

<script>
var previewTimer = null;

function getFormData() {
    return {
        image_style: document.getElementById('image_style').value,
        realism_level: parseInt(document.getElementById('realism_level').value),
        color_temperature: document.getElementById('color_temperature').value,
        contrast_level: document.getElementById('contrast_level').value,
        mood: document.getElementById('mood').value,
        brand_color_bleed: parseInt(document.getElementById('brand_color_bleed').value),
        illustration_limit: document.getElementById('illustration_limit').value,
        avoid_list: document.getElementById('avoid_list').value,
        watermark_enabled: document.getElementById('watermark_enabled').checked ? 1 : 0,
        watermark_website: document.getElementById('watermark_website').value,
        watermark_logo_position: document.getElementById('watermark_logo_position').value,
        watermark_gradient_opacity: parseInt(document.getElementById('watermark_gradient_opacity').value)
    };
}

function updatePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(function() {
        var data = getFormData();
        fetch('<?= BASE_URL ?>/art-direction/preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.preview) {
                document.getElementById('prompt_preview').value = result.preview;
            }
        });
    }, 300);
}

function loadPreset(name) {
    fetch('<?= BASE_URL ?>/art-direction/apply-preset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ preset: name })
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.settings) {
            var s = result.settings;
            if (s.image_style) document.getElementById('image_style').value = s.image_style;
            if (s.realism_level) {
                document.getElementById('realism_level').value = s.realism_level;
                document.getElementById('realism_val').textContent = s.realism_level;
            }
            if (s.color_temperature) document.getElementById('color_temperature').value = s.color_temperature;
            if (s.contrast_level) document.getElementById('contrast_level').value = s.contrast_level;
            if (s.mood) document.getElementById('mood').value = s.mood;
            if (s.brand_color_bleed !== undefined) {
                document.getElementById('brand_color_bleed').value = s.brand_color_bleed;
                document.getElementById('bleed_val').textContent = s.brand_color_bleed + '%';
            }
            if (s.illustration_limit) document.getElementById('illustration_limit').value = s.illustration_limit;

            // Highlight active preset
            document.querySelectorAll('.preset-btn').forEach(function(btn) { btn.classList.remove('active'); });
            event.target.closest('.preset-btn').classList.add('active');

            updatePreview();
            showToast('Preset applied — review and save', 'info');
        }
    });
}

function saveArtDirection() {
    var btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    var data = getFormData();
    data.csrf_token = '<?= $csrfToken ?>';

    fetch('<?= BASE_URL ?>/art-direction/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Art Direction';
        if (result.success) {
            showToast('Art direction saved successfully!', 'success');
        } else {
            showToast(result.error || 'Save failed', 'error');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Art Direction';
        showToast('Network error', 'error');
    });
}

// Toggle watermark fields visibility
document.getElementById('watermark_enabled').addEventListener('change', function() {
    document.getElementById('watermark-fields').style.opacity = this.checked ? '1' : '0.4';
    document.getElementById('watermark-fields').style.pointerEvents = this.checked ? 'auto' : 'none';
});
// Init state
(function() {
    var enabled = document.getElementById('watermark_enabled').checked;
    document.getElementById('watermark-fields').style.opacity = enabled ? '1' : '0.4';
    document.getElementById('watermark-fields').style.pointerEvents = enabled ? 'auto' : 'none';
})();
</script>
