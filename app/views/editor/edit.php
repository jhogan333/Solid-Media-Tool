<?php
$csrfToken = $_SESSION['csrf_token'] ?? '';
$post = $post ?? [];
$postId = (int)($post['id'] ?? 0);

// Parse platforms from JSON column, fall back to single platform
$selectedPlatforms = [];
if (!empty($post['platforms'])) {
    $decoded = json_decode($post['platforms'], true);
    if (is_array($decoded)) {
        $selectedPlatforms = $decoded;
    }
}
if (empty($selectedPlatforms) && !empty($post['platform'])) {
    $selectedPlatforms = [$post['platform']];
}

$brandingService = new BrandingService();
$branding = $brandingService->get($GLOBALS['client_id']);
$companyName = htmlspecialchars($branding['company_name'] ?? 'Your Company');
$primaryColor = htmlspecialchars($branding['primary_color'] ?? '#6366f1');
$logoUrl = $branding['logo_url'] ?? '';
$defaultFirstComment = $branding['first_comment'] ?? '';
$postFirstComment = $post['first_comment'] ?? '';
$firstName = htmlspecialchars($_SESSION['first_name'] ?? '');
?>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">
<input type="hidden" id="post-id" value="<?= $postId ?>">
<input type="hidden" id="original-content" value="<?= htmlspecialchars($post['content'] ?? '') ?>">

<!-- Posting Lightbox -->
<div id="posting-lightbox" class="posting-lightbox">
    <div class="posting-lightbox-content">
        <!-- Orbiting ring behind the logo -->
        <div class="posting-orbit-ring">
            <div class="posting-orbit-dot"></div>
            <div class="posting-orbit-dot" style="animation-delay:-1.2s"></div>
            <div class="posting-orbit-dot" style="animation-delay:-2.4s"></div>
        </div>

        <!-- Logo -->
        <div class="posting-lightbox-logo">
            <?php if ($logoUrl): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= $companyName ?>">
            <?php else: ?>
                <div class="posting-logo-fallback"><?= strtoupper(substr($branding['company_name'] ?? 'S', 0, 1)) ?></div>
            <?php endif; ?>
        </div>

        <!-- Pulse rings -->
        <div class="posting-pulse-ring"></div>
        <div class="posting-pulse-ring" style="animation-delay:0.6s"></div>

        <!-- Status text -->
        <h3 class="posting-lightbox-title" id="posting-lightbox-title">Publishing your post</h3>
        <p class="posting-lightbox-subtitle" id="posting-lightbox-subtitle">Connecting to Facebook...</p>

        <!-- Progress bar -->
        <div class="posting-progress-track">
            <div class="posting-progress-bar" id="posting-progress-bar"></div>
        </div>

        <!-- Floating particles -->
        <div class="posting-particles">
            <span></span><span></span><span></span><span></span><span></span>
            <span></span><span></span><span></span><span></span><span></span>
        </div>
    </div>
</div>

<style>
    /* ---- Posting Lightbox ---- */
    .posting-lightbox {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.65);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    .posting-lightbox.visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .posting-lightbox-content {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 60px 48px 48px;
        border-radius: 24px;
        background: linear-gradient(165deg, <?= $primaryColor ?> 0%, color-mix(in srgb, <?= $primaryColor ?> 50%, #0a0a0a) 100%);
        box-shadow:
            0 0 0 1px rgba(0,0,0,0.1),
            0 24px 80px rgba(0,0,0,0.3),
            0 0 120px -20px <?= $primaryColor ?>88;
        max-width: 440px;
        width: 90%;
        overflow: hidden;
    }
    .posting-lightbox-content::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0.5), rgba(255,255,255,0.1));
        background-size: 200% 100%;
        animation: posting-stripe 2.4s ease infinite;
    }
    @keyframes posting-stripe {
        0%,100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .posting-lightbox-logo {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(4px);
        margin-bottom: 12px;
        z-index: 2;
    }
    .posting-lightbox-logo img {
        max-width: 68px;
        max-height: 68px;
        object-fit: contain;
        border-radius: 8px;
        filter: brightness(0) invert(1);
    }
    .posting-logo-fallback {
        font-size: 40px;
        font-weight: 800;
        color: #fff;
    }

    .posting-orbit-ring {
        position: absolute;
        top: 60px;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.15);
        animation: posting-orbit-spin 3.6s linear infinite;
        z-index: 1;
    }
    @keyframes posting-orbit-spin { to { transform: rotate(360deg); } }
    .posting-orbit-dot {
        position: absolute;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #fff;
        top: -5px;
        left: calc(50% - 5px);
        box-shadow: 0 0 12px rgba(255,255,255,0.6);
        animation: posting-orbit-glow 1.2s ease-in-out infinite alternate;
    }
    .posting-orbit-dot:nth-child(2) { top: auto; bottom: -5px; }
    .posting-orbit-dot:nth-child(3) { top: calc(50% - 5px); left: -5px; }
    @keyframes posting-orbit-glow {
        0% { opacity: 0.5; transform: scale(0.8); }
        100% { opacity: 1; transform: scale(1.2); }
    }

    .posting-pulse-ring {
        position: absolute;
        top: 60px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.5);
        opacity: 0;
        z-index: 0;
        animation: posting-pulse 2.4s ease-out infinite;
        pointer-events: none;
    }
    @keyframes posting-pulse {
        0%   { transform: scale(1); opacity: 0.5; }
        100% { transform: scale(2.2); opacity: 0; }
    }

    .posting-lightbox-title {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        margin: 20px 0 8px;
        letter-spacing: -0.3px;
    }
    .posting-lightbox-subtitle {
        font-size: 14px;
        color: rgba(255,255,255,0.75);
        margin: 0 0 28px;
        line-height: 1.5;
    }

    .posting-progress-track {
        width: 100%;
        height: 6px;
        border-radius: 3px;
        background: rgba(0,0,0,0.2);
        overflow: hidden;
        position: relative;
        z-index: 2;
    }
    .posting-progress-bar {
        height: 100%;
        width: 0%;
        border-radius: 3px;
        background: linear-gradient(90deg, rgba(255,255,255,0.8), #fff);
        transition: width 0.4s ease;
        position: relative;
    }
    .posting-progress-bar::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
        animation: posting-shimmer 1.8s ease infinite;
    }
    @keyframes posting-shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .posting-particles {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
        z-index: 0;
    }
    .posting-particles span {
        position: absolute;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: rgba(255,255,255,0.7);
        opacity: 0;
        animation: posting-float 4s ease-in-out infinite;
    }
    .posting-particles span:nth-child(1)  { left:10%; animation-delay:0s; }
    .posting-particles span:nth-child(2)  { left:20%; animation-delay:0.5s; }
    .posting-particles span:nth-child(3)  { left:35%; animation-delay:1s; }
    .posting-particles span:nth-child(4)  { left:50%; animation-delay:1.5s; }
    .posting-particles span:nth-child(5)  { left:65%; animation-delay:2s; }
    .posting-particles span:nth-child(6)  { left:75%; animation-delay:0.3s; }
    .posting-particles span:nth-child(7)  { left:85%; animation-delay:0.8s; }
    .posting-particles span:nth-child(8)  { left:45%; animation-delay:1.2s; }
    .posting-particles span:nth-child(9)  { left:58%; animation-delay:1.8s; }
    .posting-particles span:nth-child(10) { left:90%; animation-delay:2.2s; }
    @keyframes posting-float {
        0%   { bottom: -10px; opacity: 0; transform: scale(0.5); }
        20%  { opacity: 0.6; }
        80%  { opacity: 0.3; }
        100% { bottom: 110%; opacity: 0; transform: scale(1.2); }
    }

    .editor-layout { display: grid; grid-template-columns: 3fr 2fr; gap: 24px; }
    .editor-image-preview {
        width: 100%;
        aspect-ratio: 1;
        background: var(--bg-input);
        border-radius: var(--radius-md);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        margin-bottom: 20px;
        position: relative;
    }
    .editor-image-preview img { width: 100%; height: 100%; object-fit: cover; }
    .editor-image-preview .placeholder-icon {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .editor-image-preview .placeholder-icon i { font-size: 40px; opacity: 0.25; }
    .editor-image-preview .placeholder-icon span { font-size: 13px; }

    /* Platform checkboxes */
    .platform-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .platform-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .platform-option:hover {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.03);
    }
    .platform-option.selected {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.08);
    }
    .platform-option input[type="checkbox"] { display: none; }
    .platform-option .platform-check {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    /* Support both .selected class AND :checked state */
    .platform-option.selected .platform-check,
    .platform-option input[type="checkbox"]:checked ~ .platform-check {
        background: var(--primary);
        border-color: var(--primary);
    }
    .platform-option.selected .platform-check::after,
    .platform-option input[type="checkbox"]:checked ~ .platform-check::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        font-size: 10px;
        color: #fff;
    }
    .platform-option .platform-icon {
        font-size: 16px;
        width: 20px;
        text-align: center;
    }
    .platform-option .platform-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--text);
    }
    .platform-option.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .platform-option.disabled:hover {
        border-color: var(--border);
        background: transparent;
    }

    /* Action bar */
    .editor-action-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 0 0;
        border-top: 1px solid var(--border);
        margin-top: 24px;
        flex-wrap: wrap;
    }
    .editor-action-bar .btn-danger { margin-left: auto; }

    /* Post logs section */
    .post-logs { margin-top: 24px; }
    .log-entry {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .log-entry:last-child { border-bottom: none; }
    .log-platform-badge {
        padding: 3px 10px;
        border-radius: 100px;
        font-size: 11px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .log-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .log-status-dot.success { background: var(--success); }
    .log-status-dot.failed { background: var(--danger); }
    .log-status-dot.pending { background: var(--warning); }

    @media (max-width: 768px) {
        .editor-layout { grid-template-columns: 1fr; }
        .platform-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="section-header">
    <h3 class="section-title">
        <a href="<?= BASE_URL ?>/posts" style="color:var(--text-muted);margin-right:8px" title="Back to posts"><i class="fas fa-arrow-left"></i></a>
        Edit Post
    </h3>
    <span class="badge badge-<?= htmlspecialchars($post['status'] ?? 'draft') ?>"><?= ucfirst(htmlspecialchars($post['status'] ?? 'draft')) ?></span>
</div>

<div class="editor-layout">
    <!-- Left Column: Image + Content -->
    <div>
        <div class="card">
            <div class="editor-image-preview" id="image-preview">
                <?php if (!empty($post['image_url'])): ?>
                    <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Post image" id="preview-img">
                <?php else: ?>
                    <div class="placeholder-icon">
                        <i class="fas fa-image"></i>
                        <span>No image</span>
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-bottom:16px">
                <button type="button" class="btn btn-ghost btn-sm" id="btn-regen-image" onclick="regenerateImage()">
                    <i class="fas fa-sync-alt"></i> Regenerate Image
                </button>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-title">Title</label>
                <input type="text" id="edit-title" class="form-input" value="<?= htmlspecialchars($post['title'] ?? '') ?>" placeholder="Post title">
            </div>

            <div class="form-group" style="margin-bottom:8px">
                <label class="form-label" for="edit-content">Caption / Content</label>
                <textarea id="edit-content" class="form-textarea" rows="12" style="white-space:pre-wrap;line-height:1.7" placeholder="Write your post content..."><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
            </div>
            <div style="margin-bottom:0">
                <button type="button" class="btn btn-ghost btn-sm" id="btn-critique" onclick="critiquePost()">
                    <i class="fas fa-robot"></i> AI Critique
                </button>
                <div id="critique-results" style="display:none;margin-top:12px;padding:16px;background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-md)"></div>
            </div>
        </div>
    </div>

    <!-- Right Column: Settings -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog" style="margin-right:8px;color:var(--text-muted)"></i>Settings</h3>
            </div>

            <!-- Platform Multi-Select -->
            <div class="form-group">
                <label class="form-label">Publish To</label>
                <div class="platform-grid" id="platform-grid">
                    <label class="platform-option <?= in_array('facebook', $selectedPlatforms) ? 'selected' : '' ?>" data-platform="facebook">
                        <input type="checkbox" name="platforms[]" value="facebook" <?= in_array('facebook', $selectedPlatforms) ? 'checked' : '' ?>>
                        <div class="platform-check"></div>
                        <i class="fab fa-facebook platform-icon" style="color:#1877F2"></i>
                        <span class="platform-label">Facebook</span>
                    </label>
                    <label class="platform-option <?= in_array('linkedin', $selectedPlatforms) ? 'selected' : '' ?>" data-platform="linkedin">
                        <input type="checkbox" name="platforms[]" value="linkedin" <?= in_array('linkedin', $selectedPlatforms) ? 'checked' : '' ?>>
                        <div class="platform-check"></div>
                        <i class="fab fa-linkedin platform-icon" style="color:#0A66C2"></i>
                        <span class="platform-label">LinkedIn</span>
                    </label>
                    <!-- Instagram & X/Twitter — hidden until platform integration is ready -->
                    <!-- <label class="platform-option disabled" data-platform="instagram" title="Coming soon">
                        <input type="checkbox" name="platforms[]" value="instagram" disabled>
                        <div class="platform-check"></div>
                        <i class="fab fa-instagram platform-icon" style="color:#E4405F"></i>
                        <span class="platform-label">Instagram</span>
                    </label>
                    <label class="platform-option disabled" data-platform="twitter" title="Coming soon">
                        <input type="checkbox" name="platforms[]" value="twitter" disabled>
                        <div class="platform-check"></div>
                        <i class="fab fa-x-twitter platform-icon" style="color:var(--text)"></i>
                        <span class="platform-label">X / Twitter</span>
                    </label> -->
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-post-type">Post Type</label>
                <select id="edit-post-type" class="form-select">
                    <option value="educational" <?= ($post['post_type'] ?? '') === 'educational' ? 'selected' : '' ?>>Educational</option>
                    <option value="promotional" <?= ($post['post_type'] ?? '') === 'promotional' ? 'selected' : '' ?>>Promotional</option>
                    <option value="engagement" <?= ($post['post_type'] ?? '') === 'engagement' ? 'selected' : '' ?>>Engagement</option>
                    <option value="storytelling" <?= ($post['post_type'] ?? '') === 'storytelling' ? 'selected' : '' ?>>Storytelling</option>
                    <option value="behind_the_scenes" <?= ($post['post_type'] ?? '') === 'behind_the_scenes' ? 'selected' : '' ?>>Behind the Scenes</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-status">Status</label>
                <select id="edit-status" class="form-select">
                    <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="scheduled" <?= ($post['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="failed" <?= ($post['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-scheduled-at">Schedule Date &amp; Time</label>
                <input type="datetime-local" id="edit-scheduled-at" class="form-input" value="<?= !empty($post['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($post['scheduled_at'])) : '' ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-topic">Topic</label>
                <input type="text" id="edit-topic" class="form-input" value="<?= htmlspecialchars($post['topic'] ?? '') ?>" placeholder="Post topic">
            </div>

            <div class="form-group">
                <label class="form-label" for="edit-keywords">Keywords</label>
                <input type="text" id="edit-keywords" class="form-input" value="<?= htmlspecialchars($post['keywords'] ?? '') ?>" placeholder="Comma-separated keywords">
            </div>

            <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="edit-angle">Angle</label>
                <input type="text" id="edit-angle" class="form-input" value="<?= htmlspecialchars($post['angle'] ?? '') ?>" placeholder="Content angle or hook">
            </div>
        </div>

        <!-- Post Logs (if any) -->
        <div class="card post-logs" id="post-logs-card" style="display:none">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history" style="margin-right:8px;color:var(--text-muted)"></i>Posting History</h3>
            </div>
            <div id="post-logs-list"></div>
        </div>
    </div>
</div>

<!-- First Comment -->
<div class="card" style="margin-top:24px">
    <div class="card-header">
        <div>
            <h3 class="card-title"><i class="fas fa-comment-dots" style="margin-right:8px;color:var(--primary)"></i>First Comment</h3>
            <p class="card-subtitle">This comment will be posted automatically when the post is published</p>
        </div>
    </div>
    <div class="form-group" style="margin-bottom:0">
        <textarea id="edit-first-comment" class="form-textarea" rows="3" placeholder="e.g. 📞 587-557-1234&#10;🌐 https://solidtech.ca"><?= htmlspecialchars($postFirstComment ?: $defaultFirstComment) ?></textarea>
        <?php if ($defaultFirstComment && !$postFirstComment): ?>
            <div class="text-small text-muted" style="margin-top:4px">Using default from Branding settings. Edit above to override for this post only.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Bar -->
<style>
.btn-shine {
    position: relative;
    overflow: hidden;
}
.btn-shine::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -60%;
    width: 40%;
    height: 200%;
    background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.35) 45%, rgba(255,255,255,0.1) 50%, transparent 55%);
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
}
.btn-shine:hover::after {
    opacity: 1;
    animation: btnShine 0.7s ease forwards;
}
@keyframes btnShine {
    0% { left: -60%; }
    100% { left: 120%; }
}

/* Save Transition Portal */
.save-portal {
    position: fixed;
    inset: 0;
    z-index: 99995;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0);
    backdrop-filter: blur(0px);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.4s ease, background 0.5s ease, backdrop-filter 0.5s ease;
}
.save-portal.visible {
    opacity: 1;
    visibility: visible;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(10px);
}
.save-portal-content {
    position: relative;
    width: 320px;
    height: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transform: scale(0.6);
    opacity: 0;
    transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
}
.save-portal.visible .save-portal-content {
    transform: scale(1);
    opacity: 1;
}
.save-portal.closing .save-portal-content {
    transform: scale(0.7);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.6, -0.28, 0.74, 0.05), opacity 0.3s ease;
}

/* Vortex rings */
.vortex-ring {
    position: absolute;
    border-radius: 50%;
    border: 2px solid;
}
.vortex-ring-1 { width:280px;height:280px;border-color:rgba(255,255,255,0.08);animation:vortexSpin 8s linear infinite; }
.vortex-ring-2 { width:220px;height:220px;border-color:rgba(255,255,255,0.12);animation:vortexSpin 5s linear infinite reverse; }
.vortex-ring-3 { width:160px;height:160px;border-color:rgba(255,255,255,0.18);border-top-color:rgba(255,255,255,0.5);animation:vortexSpin 3s linear infinite;box-shadow:0 0 30px rgba(255,255,255,0.08); }
.vortex-ring-4 { width:100px;height:100px;border-color:rgba(255,255,255,0.25);border-top-color:#fff;animation:vortexSpin 2s linear infinite reverse;box-shadow:0 0 20px rgba(255,255,255,0.15); }
@keyframes vortexSpin { to { transform: rotate(360deg); } }

/* Sparks */
.save-spark {
    position: absolute;
    width: 3px;
    height: 3px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(255,255,255,0.8);
}

/* Logo in center */
.save-portal-logo {
    position: relative;
    z-index: 5;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
}
.save-portal-logo img { max-width:46px;max-height:46px;filter:brightness(0) invert(1);object-fit:contain; }
.save-portal-logo-text { font-size:28px;font-weight:800;color:#fff; }

.save-portal-status {
    position: relative;
    z-index: 5;
    margin-top: 24px;
    font-size: 15px;
    font-weight: 600;
    color: #fff;
    text-align: center;
    min-height: 22px;
}
</style>

<!-- Save Transition Portal -->
<div class="save-portal" id="savePortal">
    <div class="save-portal-content">
        <div class="vortex-ring vortex-ring-1"></div>
        <div class="vortex-ring vortex-ring-2"></div>
        <div class="vortex-ring vortex-ring-3"></div>
        <div class="vortex-ring vortex-ring-4"></div>
        <div class="save-portal-logo">
            <?php if ($logoUrl): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="">
            <?php else: ?>
                <div class="save-portal-logo-text"><?= strtoupper(substr($branding['company_name'] ?? 'S', 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div class="save-portal-status" id="savePortalStatus">Saving your changes...</div>
        <!-- Sparks container populated by JS -->
        <div id="saveSparkContainer" style="position:absolute;inset:0;overflow:hidden;pointer-events:none"></div>
    </div>
</div>

<div class="editor-action-bar">
    <button class="btn btn-primary btn-shine" id="btn-save" onclick="savePost()">
        <i class="fas fa-save"></i> Save Changes
    </button>
    <button class="btn btn-ghost btn-shine" id="btn-schedule" onclick="schedulePost()">
        <i class="fas fa-clock"></i> Schedule
    </button>
    <button class="btn btn-primary btn-shine" id="btn-post-now" onclick="postNow()" style="background:var(--success);border-color:var(--success)">
        <i class="fas fa-paper-plane"></i> Post Now
    </button>
    <?php if (($post['status'] ?? '') === 'failed'): ?>
    <button class="btn btn-ghost" id="btn-retry" onclick="retryPost()" style="color:var(--warning)">
        <i class="fas fa-redo"></i> Retry Failed
    </button>
    <?php endif; ?>
    <button class="btn btn-danger" id="btn-delete" onclick="deletePost()">
        <i class="fas fa-trash-alt"></i> Delete
    </button>
</div>

<script>
(function() {
const BASE = '<?= rtrim(BASE_URL, '/') ?>';
const postId = document.getElementById('post-id').value;
const csrfToken = () => document.getElementById('csrf-token').value;
let currentImageUrl = '<?= htmlspecialchars($post['image_url'] ?? '', ENT_QUOTES) ?>';

// Platform checkbox toggle
document.querySelectorAll('.platform-option:not(.disabled)').forEach(function(label) {
    label.addEventListener('click', function(e) {
        // Only handle if the click target is NOT the checkbox itself
        if (e.target.type === 'checkbox') return;
        e.preventDefault();
        var cb = this.querySelector('input[type="checkbox"]');
        cb.checked = !cb.checked;
        this.classList.toggle('selected', cb.checked);
    });
});

function getSelectedPlatforms() {
    const checked = document.querySelectorAll('#platform-grid input[type="checkbox"]:checked');
    return Array.from(checked).map(cb => cb.value);
}

function getPostData() {
    const platforms = getSelectedPlatforms();
    return {
        title: document.getElementById('edit-title').value,
        content: document.getElementById('edit-content').value,
        image_url: currentImageUrl,
        platform: platforms[0] || 'facebook',
        platforms: JSON.stringify(platforms),
        post_type: document.getElementById('edit-post-type').value,
        status: document.getElementById('edit-status').value,
        scheduled_at: document.getElementById('edit-scheduled-at').value || '',
        topic: document.getElementById('edit-topic').value,
        keywords: document.getElementById('edit-keywords').value,
        angle: document.getElementById('edit-angle').value,
        first_comment: document.getElementById('edit-first-comment').value,
    };
}

function setLoading(btn, loading, label) {
    if (loading) {
        btn.dataset.origLabel = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (label || 'Loading...');
        btn.disabled = true;
        btn.style.opacity = '0.7';
    } else {
        btn.innerHTML = btn.dataset.origLabel || btn.innerHTML;
        btn.disabled = false;
        btn.style.opacity = '';
    }
}

// ---- Save Portal Animation ----
function showSavePortal(statusText) {
    var portal = document.getElementById('savePortal');
    var status = document.getElementById('savePortalStatus');
    status.textContent = statusText || 'Saving your changes...';

    // Generate sparks
    var sparkContainer = document.getElementById('saveSparkContainer');
    sparkContainer.innerHTML = '';
    for (var i = 0; i < 20; i++) {
        var spark = document.createElement('div');
        spark.className = 'save-spark';
        var angle = (i / 20) * 360;
        var radius = 60 + Math.random() * 80;
        var duration = 1.5 + Math.random() * 2;
        var delay = Math.random() * 2;
        var size = 2 + Math.random() * 3;
        spark.style.cssText = 'width:'+size+'px;height:'+size+'px;left:50%;top:50%;'
            + 'animation:sparkOrbit '+duration+'s linear infinite;'
            + 'animation-delay:-'+delay+'s;'
            + 'transform:rotate('+angle+'deg) translateX('+radius+'px);'
            + 'opacity:'+(0.3+Math.random()*0.7);
        sparkContainer.appendChild(spark);
    }

    // Add spark keyframe if not exists
    if (!document.getElementById('sparkStyle')) {
        var s = document.createElement('style');
        s.id = 'sparkStyle';
        s.textContent = '@keyframes sparkOrbit{0%{transform:rotate(0deg) translateX(70px);opacity:0.8}50%{opacity:0.3}100%{transform:rotate(360deg) translateX(70px);opacity:0.8}}';
        document.head.appendChild(s);
    }

    portal.classList.remove('closing');
    portal.classList.add('visible');
    document.body.style.overflow = 'hidden';

    // Cycle status messages
    var msgs = [statusText || 'Saving your changes...', 'Updating content...', 'Almost there...'];
    var mi = 0;
    window._savePortalMsgInterval = setInterval(function() {
        mi = (mi + 1) % msgs.length;
        status.style.opacity = '0';
        setTimeout(function() {
            status.textContent = msgs[mi];
            status.style.opacity = '1';
        }, 200);
    }, 1200);
    status.style.transition = 'opacity 0.2s ease';
}

function hideSavePortal(redirectUrl) {
    clearInterval(window._savePortalMsgInterval);
    var portal = document.getElementById('savePortal');
    portal.classList.add('closing');

    setTimeout(function() {
        if (redirectUrl) {
            window.location.href = redirectUrl;
        } else {
            portal.classList.remove('visible', 'closing');
            document.body.style.overflow = '';
        }
    }, 500);
}

async function savePost() {
    const btn = document.getElementById('btn-save');
    setLoading(btn, true, 'Saving...');
    showSavePortal('Saving your changes...');

    try {
        const data = getPostData();
        const formData = new FormData();
        formData.append('csrf_token', csrfToken());
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));

        const res = await fetch(BASE + '/posts/update/' + postId, {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (!res.ok || result.error) throw new Error(result.error || 'Save failed');

        // Wait for the animation to play out (min 2.5s)
        await new Promise(r => setTimeout(r, 2500));
        hideSavePortal(BASE + '/posts?highlight=' + postId);
    } catch (err) {
        hideSavePortal();
        showToast(err.message, 'error');
    } finally {
        setLoading(btn, false);
    }
}

async function schedulePost() {
    const scheduledAt = document.getElementById('edit-scheduled-at').value;
    if (!scheduledAt) {
        showToast('Please set a date and time first.', 'warning');
        document.getElementById('edit-scheduled-at').focus();
        return;
    }

    // Prevent scheduling in the past
    const scheduledDate = new Date(scheduledAt);
    const now = new Date();
    if (scheduledDate <= now) {
        showToast('Scheduled time must be in the future. Please pick a later date/time.', 'warning');
        document.getElementById('edit-scheduled-at').focus();
        return;
    }

    const platforms = getSelectedPlatforms();
    if (platforms.length === 0) {
        showToast('Please select at least one platform.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-schedule');
    setLoading(btn, true, 'Scheduling...');
    showSavePortal('Scheduling your post...');

    try {
        const data = getPostData();
        data.status = 'scheduled';
        const formData = new FormData();
        formData.append('csrf_token', csrfToken());
        Object.entries(data).forEach(([k, v]) => formData.append(k, v));

        const saveRes = await fetch(BASE + '/posts/update/' + postId, {
            method: 'POST',
            body: formData
        });
        const saveResult = await saveRes.json();
        if (!saveRes.ok || saveResult.error) throw new Error(saveResult.error || 'Save failed');

        await new Promise(r => setTimeout(r, 2500));
        hideSavePortal(BASE + '/posts?highlight=' + postId);
    } catch (err) {
        hideSavePortal();
        showToast(err.message, 'error');
    } finally {
        setLoading(btn, false);
    }
}

// --- Posting Lightbox helpers ---
const postingLightbox = document.getElementById('posting-lightbox');
const postingProgressBar = document.getElementById('posting-progress-bar');
const postingTitle = document.getElementById('posting-lightbox-title');
const postingSubtitle = document.getElementById('posting-lightbox-subtitle');
let postingProgressInterval = null;

const platformLabels = {
    facebook: 'Facebook',
    linkedin: 'LinkedIn',
    instagram: 'Instagram',
    twitter: 'X / Twitter'
};

function showPostingLightbox(platforms) {
    // Build the subtitle with platform names
    const names = platforms.map(p => platformLabels[p] || p);
    const platformText = names.join(' & ');

    postingTitle.textContent = 'Publishing your post';
    postingSubtitle.textContent = 'Connecting to ' + platformText + '...';
    postingProgressBar.style.width = '0%';
    void postingLightbox.offsetWidth;
    postingLightbox.classList.add('visible');
    document.body.style.overflow = 'hidden';

    // Status messages that cycle through
    const messages = [
        'Connecting to ' + platformText + '...',
        'Uploading your content...',
        'Delivering to ' + platformText + '...',
        'Finalizing your post...',
    ];

    let progress = 0;
    let msgIndex = 0;
    postingProgressInterval = setInterval(() => {
        const remaining = 90 - progress;
        progress += remaining * 0.06 + Math.random() * 2;
        if (progress > 90) progress = 90;
        postingProgressBar.style.width = progress + '%';

        if (progress > (msgIndex + 1) * (85 / messages.length) && msgIndex < messages.length - 1) {
            msgIndex++;
            postingSubtitle.textContent = messages[msgIndex];
        }
    }, 250);
}

function hidePostingLightbox(success, platformText) {
    clearInterval(postingProgressInterval);
    postingProgressBar.style.width = '100%';
    if (success) {
        postingTitle.textContent = 'Posted successfully!';
        postingSubtitle.textContent = 'Your post is now live on ' + platformText;
    } else {
        postingTitle.textContent = 'Posting failed';
        postingSubtitle.textContent = 'Something went wrong. Please try again.';
    }

    setTimeout(() => {
        postingLightbox.classList.remove('visible');
        document.body.style.overflow = '';
    }, 1200);
}

async function postNow() {
    const platforms = getSelectedPlatforms();
    if (platforms.length === 0) {
        showToast('Please select at least one platform to post to.', 'warning');
        return;
    }

    const content = document.getElementById('edit-content').value.trim();
    if (!content) {
        showToast('Post content cannot be empty.', 'warning');
        return;
    }

    const platformText = platforms.map(p => platformLabels[p] || p).join(' & ');

    confirmModal(
        'Post Now',
        'This will immediately publish to <strong>' + platforms.map(p => platformLabels[p] || p).join(' &amp; ') + '</strong>. Are you sure?',
        async () => {
            showPostingLightbox(platforms);

            try {
                // Save first to ensure latest content is stored
                const data = getPostData();
                const saveFormData = new FormData();
                saveFormData.append('csrf_token', csrfToken());
                Object.entries(data).forEach(([k, v]) => saveFormData.append(k, v));

                await fetch(BASE + '/posts/update/' + postId, {
                    method: 'POST',
                    body: saveFormData
                });

                // Now post
                const formData = new FormData();
                formData.append('csrf_token', csrfToken());

                const res = await fetch(BASE + '/posts/post-now/' + postId, {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    document.getElementById('edit-status').value = result.new_status || 'published';
                    let msg = 'Posted successfully to ' + platformText + '!';
                    let allGood = true;
                    if (result.results) {
                        const failed = Object.entries(result.results).filter(([k, v]) => !v.success);
                        if (failed.length > 0) {
                            msg = 'Posted to some platforms. ' + failed.length + ' failed.';
                            allGood = false;
                        }
                    }
                    hidePostingLightbox(allGood, platformText);
                    // Redirect to posts table after a moment
                    setTimeout(() => { window.location.href = BASE + '/posts?highlight=' + postId; }, 2000);
                } else {
                    hidePostingLightbox(false, platformText);
                    setTimeout(() => showToast(result.error || 'Posting failed', 'error'), 1300);
                }

                loadPostLogs();
            } catch (err) {
                hidePostingLightbox(false, platformText);
                setTimeout(() => showToast(err.message, 'error'), 1300);
            }
        }
    );
}

async function retryPost() {
    const btn = document.getElementById('btn-retry');
    if (!btn) return;
    setLoading(btn, true, 'Retrying...');

    try {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken());

        const res = await fetch(BASE + '/posts/retry/' + postId, {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            showToast('Retry completed!', 'success');
            loadPostLogs();
        } else {
            throw new Error(result.error || 'Retry failed');
        }
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading(btn, false);
    }
}

function deletePost() {
    confirmModal('Delete Post', 'Are you sure you want to delete this post? This action cannot be undone.', async () => {
        const btn = document.getElementById('btn-delete');
        setLoading(btn, true, 'Deleting...');

        try {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken());

            const res = await fetch(BASE + '/posts/delete/' + postId, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (!res.ok) throw new Error(result.error || 'Delete failed');

            showToast('Post deleted.', 'success');
            setTimeout(() => window.location.href = BASE + '/posts', 800);
        } catch (err) {
            showToast(err.message, 'error');
            setLoading(btn, false);
        }
    });
}

// Regenerate image
async function regenerateImage() {
    try {
    var content = document.getElementById('edit-content').value.trim();
    if (!content) {
        showToast('Add some content first so the AI knows what image to generate.', 'warning');
        return;
    }

    // Register with global tracker
    try { if (typeof GenTracker !== 'undefined') GenTracker.addTask(postId, 'image'); } catch(e) {}
    var _genTrackerCleanup = function(success) {
        try { if (typeof GenTracker !== 'undefined') { if (success) GenTracker.removeTask(postId); else GenTracker.clearAll(); } } catch(e) {}
    };

    var btn = document.getElementById('btn-regen-image');
    var preview = document.getElementById('image-preview');
    setLoading(btn, true, 'Generating...');

    // Branded AI image generation overlay
    var brandPrimary = '<?= $primaryColor ?>';
    var brandLogo = '<?= addslashes($logoUrl) ?>';

    preview.innerHTML = ''
        + '<div id="portalLoader" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:inherit;overflow:hidden;background:linear-gradient(165deg,' + brandPrimary + ' 0%,#0a0a0a 60%,#000 100%);z-index:10">'
        // Animated shimmer stripe at top
        + '<div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,rgba(255,255,255,0.05),rgba(255,255,255,0.4),rgba(255,255,255,0.05));background-size:200% 100%;animation:aiShimmer 2.4s ease infinite"></div>'
        // Floating particles layer
        + '<div style="position:absolute;inset:0;overflow:hidden;pointer-events:none">'
        + Array.from({length:14}, function(_,i) {
            var x = 5 + Math.random()*90, d = 3+Math.random()*5, del = (Math.random()*3).toFixed(1), sz = 2+Math.random()*3;
            return '<div style="position:absolute;left:'+x+'%;width:'+sz+'px;height:'+sz+'px;border-radius:50%;background:rgba(255,255,255,0.5);animation:aiParticle '+d+'s ease-in-out infinite;animation-delay:-'+del+'s;opacity:0"></div>';
          }).join('')
        + '</div>'
        // Misty ambient glow
        + '<div style="position:absolute;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,0.07),transparent 60%);animation:atomMist 4s ease-in-out infinite"></div>'
        // Atom orbit 1 — wide ellipse
        + '<div style="position:absolute;width:380px;height:140px;border:1px solid rgba(255,255,255,0.07);border-radius:50%;animation:aiSpin 7s linear infinite">'
        + '<div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 12px rgba(255,255,255,0.6);opacity:0.65"></div></div>'
        // Atom orbit 2 — tilted
        + '<div style="position:absolute;width:350px;height:130px;border:1px solid rgba(255,255,255,0.06);border-radius:50%;animation:aiSpin 5.5s linear infinite reverse;transform:rotate(55deg)">'
        + '<div style="position:absolute;width:4px;height:4px;background:rgba(255,255,255,0.9);border-radius:50%;bottom:-2px;left:calc(50% - 2px);box-shadow:0 0 10px rgba(255,255,255,0.5);opacity:0.55"></div></div>'
        // Atom orbit 3 — steep angle
        + '<div style="position:absolute;width:120px;height:340px;border:1px solid rgba(255,255,255,0.05);border-radius:50%;animation:aiSpin 8s linear infinite;transform:rotate(20deg)">'
        + '<div style="position:absolute;width:4px;height:4px;background:rgba(255,255,255,0.8);border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 8px rgba(255,255,255,0.4);opacity:0.5"></div></div>'
        // Orbiting rings (existing)
        + '<div style="position:absolute;width:200px;height:200px;border:1px solid rgba(255,255,255,0.06);border-radius:50%;animation:aiSpin 10s linear infinite"></div>'
        + '<div style="position:absolute;width:150px;height:150px;border:1px dashed rgba(255,255,255,0.08);border-radius:50%;animation:aiSpin 7s linear infinite reverse"></div>'
        + '<div style="position:absolute;width:110px;height:110px;border:2px solid rgba(255,255,255,0.06);border-top-color:rgba(255,255,255,0.5);border-radius:50%;animation:aiSpin 3s linear infinite;box-shadow:0 0 24px rgba(255,255,255,0.06)"></div>'
        // Orbiting dot
        + '<div style="position:absolute;width:170px;height:170px;animation:aiSpin 5s linear infinite">'
        + '<div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 10px rgba(255,255,255,0.7)"></div></div>'
        // Logo in center
        + '<div style="position:relative;z-index:5;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.1);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(255,255,255,0.05)">'
        + (brandLogo
            ? '<img src="'+brandLogo+'" style="max-width:52px;max-height:52px;object-fit:contain;filter:brightness(0) invert(1)" alt="">'
            : '<div style="font-size:30px;font-weight:800;color:#fff"><?= strtoupper(substr($branding['company_name'] ?? 'S', 0, 1)) ?></div>')
        + '</div>'
        // Title badge
        + '<div style="position:relative;z-index:5;margin-top:20px;padding:6px 16px;border-radius:20px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.1);backdrop-filter:blur(4px)">'
        + '<span style="font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.7)">AI Image Generation</span>'
        + '</div>'
        // Status text
        + '<div style="position:relative;z-index:5;margin-top:14px;text-align:center">'
        + '<div style="font-size:15px;font-weight:600;color:#fff;transition:opacity 0.2s ease" id="portalStatus">Initializing...</div>'
        + '<div style="font-size:11px;color:rgba(255,255,255,0.35);margin-top:6px">This may take 2–3 minutes</div>'
        + '</div>'
        + '</div>'
        + '<style>'
        + '@keyframes aiSpin{to{transform:rotate(360deg)}}'
        + '@keyframes aiShimmer{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}'
        + '@keyframes aiParticle{0%{bottom:-10px;opacity:0;transform:scale(.4)}15%{opacity:.6}85%{opacity:.2}100%{bottom:110%;opacity:0;transform:scale(1)}}'
        + '@keyframes atomMist{0%,100%{opacity:0.4;transform:scale(1)}50%{opacity:0.7;transform:scale(1.15)}}'
        + '</style>';

    // Cycle status messages — more messages for longer waits
    var portalMsgs = [
        'Connecting to AI server...',
        'Queuing image generation task...',
        'AI is composing the scene...',
        'Rendering visual elements...',
        'Applying lighting & color grading...',
        'Building composition...',
        'Processing art direction settings...',
        'Refining textures & details...',
        'Enhancing image resolution...',
        'Applying brand watermark...',
        'Running final quality checks...',
        'Almost ready — finalizing...',
        'Still working — complex images take longer...',
        'Hang tight — rendering in progress...',
    ];
    var pmIdx = 0;
    var portalInterval = setInterval(function() {
        pmIdx = (pmIdx + 1) % portalMsgs.length;
        var el = document.getElementById('portalStatus');
        if (el) {
            el.style.opacity = '0';
            setTimeout(function() {
                el.textContent = portalMsgs[pmIdx];
                el.style.opacity = '1';
            }, 200);
        }
    }, 5000);

    try {
        // Build a clean image prompt from title + topic, not the full post body
        const title = document.getElementById('edit-title').value.trim();
        const topic = document.getElementById('edit-topic') ? document.getElementById('edit-topic').value.trim() : '';
        var imagePrompt = 'Professional social media image for: ' + (title || topic || content.substring(0, 100));

        const formData = new FormData();
        formData.append('csrf_token', csrfToken());
        formData.append('prompt', imagePrompt);
        formData.append('uid', 'post_' + postId);

        // Start async image job (returns immediately)
        const res = await fetch(BASE + '/generator/start-image-job', {
            method: 'POST',
            body: formData
        });
        const jobData = await res.json();
        if (!res.ok) throw new Error(jobData.error || 'Failed to start image generation');

        // Poll for completion every 6 seconds
        var pollTimer = setInterval(async function() {
            try {
                const checkRes = await fetch(BASE + '/generator/check-image-jobs');
                const checkData = await checkRes.json();
                var jobs = checkData.jobs || [];
                for (var j = 0; j < jobs.length; j++) {
                    if (jobs[j].uid === 'post_' + postId) {
                        if (jobs[j].status === 'completed' && jobs[j].image_url) {
                            clearInterval(pollTimer);
                            clearInterval(portalInterval);

                            currentImageUrl = jobs[j].image_url;
                            preview.innerHTML = '<img src="' + jobs[j].image_url + '" alt="Post image" id="preview-img">';

                            // Auto-save the new image URL
                            const saveFormData = new FormData();
                            saveFormData.append('csrf_token', csrfToken());
                            const postData = getPostData();
                            Object.entries(postData).forEach(([k, v]) => saveFormData.append(k, v));
                            fetch(BASE + '/posts/update/' + postId, { method: 'POST', body: saveFormData });

                            _genTrackerCleanup(true);
                            setLoading(btn, false);
                            showToast('Image regenerated and saved!', 'success');
                        } else if (jobs[j].status === 'failed') {
                            clearInterval(pollTimer);
                            clearInterval(portalInterval);
                            _genTrackerCleanup(false);
                            setLoading(btn, false);
                            preview.innerHTML = '<div class="placeholder-icon"><i class="fas fa-image"></i><span>Image generation failed</span></div>';
                            showImageErrorModal(jobs[j].error || 'Image generation failed', function() { regenerateImage(); });
                        }
                        break;
                    }
                }
            } catch (e) {}
        }, 6000);

        // Safety timeout after 3 minutes
        setTimeout(function() {
            clearInterval(pollTimer);
            clearInterval(portalInterval);
            _genTrackerCleanup(false);
            setLoading(btn, false);
        }, 180000);

    } catch (err) {
        if (typeof portalInterval !== 'undefined') clearInterval(portalInterval);
        _genTrackerCleanup(false);
        if (preview) preview.innerHTML = '<div class="placeholder-icon"><i class="fas fa-image"></i><span>Image generation failed</span></div>';
        showImageErrorModal(err.message, function() { regenerateImage(); });
        if (btn) setLoading(btn, false);
    }
    } catch (outerErr) {
        // Catch-all: show the actual error so we can debug
        showToast('regenerateImage error: ' + outerErr.message, 'error');
        console.error('regenerateImage error:', outerErr);
    }
}

function showImageErrorModal(errorMsg, retryCallback) {
    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;animation:imgErrFadeIn 0.3s ease';
    overlay.innerHTML = '<div style="background:var(--bg-card);border-radius:20px;max-width:440px;width:90%;padding:32px;box-shadow:0 24px 80px rgba(0,0,0,0.3);animation:imgErrSlideUp 0.4s cubic-bezier(0.34,1.56,0.64,1)">'
        + '<div style="text-align:center;margin-bottom:20px">'
        + '<div style="width:52px;height:52px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 14px"><i class="fas fa-exclamation-triangle" style="color:var(--danger);font-size:22px"></i></div>'
        + '<h3 style="font-size:17px;font-weight:700;color:var(--text);margin-bottom:6px">Image Generation Failed</h3>'
        + '<p style="font-size:13px;color:var(--text-secondary);line-height:1.6">' + (errorMsg || 'An unknown error occurred.').replace(/</g,'&lt;') + '</p>'
        + '</div>'
        + '<div style="background:var(--bg-input);border-radius:var(--radius-sm);padding:14px;margin-bottom:20px">'
        + '<div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:6px">What you can do:</div>'
        + '<div style="font-size:12px;color:var(--text-secondary);line-height:1.6">'
        + '1. Click <strong>Try Again</strong> to regenerate the image<br>'
        + '2. The AI service may be busy — wait a moment and retry<br>'
        + '3. If the issue persists, try simplifying the post title'
        + '</div></div>'
        + '<div style="display:flex;gap:10px;justify-content:flex-end">'
        + '<button class="btn btn-ghost" id="imgErrDismiss">Dismiss</button>'
        + '<button class="btn btn-primary" id="imgErrRetry"><i class="fas fa-redo" style="margin-right:4px"></i> Try Again</button>'
        + '</div></div>';

    // Add keyframes if needed
    if (!document.getElementById('imgErrStyles')) {
        var s = document.createElement('style');
        s.id = 'imgErrStyles';
        s.textContent = '@keyframes imgErrFadeIn{from{opacity:0}to{opacity:1}}@keyframes imgErrSlideUp{from{transform:translateY(30px) scale(0.95);opacity:0}to{transform:translateY(0) scale(1);opacity:1}}';
        document.head.appendChild(s);
    }

    document.body.appendChild(overlay);
    overlay.querySelector('#imgErrDismiss').onclick = function() { overlay.remove(); };
    overlay.querySelector('#imgErrRetry').onclick = function() { overlay.remove(); if (retryCallback) retryCallback(); };
}

// Load posting logs
async function loadPostLogs() {
    try {
        const res = await fetch(BASE + '/posts/logs/' + postId);
        const result = await res.json();

        if (result.logs && result.logs.length > 0) {
            const card = document.getElementById('post-logs-card');
            const list = document.getElementById('post-logs-list');
            card.style.display = 'block';

            list.innerHTML = result.logs.map(log => {
                const date = new Date(log.created_at).toLocaleString();
                const statusClass = log.status === 'success' ? 'success' : (log.status === 'failed' ? 'failed' : 'pending');
                return `<div class="log-entry">
                    <div class="log-status-dot ${statusClass}"></div>
                    <span class="log-platform-badge" style="background:var(--bg-input)">${log.platform}</span>
                    <span style="flex:1;color:var(--text-secondary);font-size:13px">${log.status === 'failed' ? (log.error_message || 'Failed') : 'Published'}</span>
                    <span style="color:var(--text-muted);font-size:12px">${date}</span>
                </div>`;
            }).join('');
        }
    } catch (e) {
        // Silent fail for logs
    }
}

// Load logs on page load
loadPostLogs();

// AI Critique
function getContentChangePercent() {
    var original = (document.getElementById('original-content').value || '').trim();
    var current = (document.getElementById('edit-content').value || '').trim();
    if (!original || !current) return 100; // No original = treat as fully custom
    if (original === current) return 0;

    // Simple Levenshtein-like distance approximation using word comparison
    var origWords = original.toLowerCase().split(/\s+/);
    var currWords = current.toLowerCase().split(/\s+/);
    var maxLen = Math.max(origWords.length, currWords.length);
    if (maxLen === 0) return 0;

    var matches = 0;
    var used = {};
    origWords.forEach(function(w) {
        for (var i = 0; i < currWords.length; i++) {
            if (!used[i] && currWords[i] === w) {
                matches++;
                used[i] = true;
                break;
            }
        }
    });

    var similarity = matches / maxLen;
    return Math.round((1 - similarity) * 100);
}

function critiquePost() {
    var content = document.getElementById('edit-content').value.trim();
    if (!content) { showToast('Enter some content first', 'warning'); return; }

    var btn = document.getElementById('btn-critique');
    var panel = document.getElementById('critique-results');

    // Check if content has changed enough from the AI-generated original
    var changePercent = getContentChangePercent();
    if (changePercent < 30) {
        // Post is still mostly AI-generated — bubbly "optimized" message with robot
        panel.style.display = 'block';
        var primaryColor = '<?= $primaryColor ?>';
        panel.innerHTML = '<div style="animation:criOptBounce 0.5s cubic-bezier(0.34,1.56,0.64,1) both">'
            + '<div style="display:flex;align-items:flex-start;gap:14px;padding:6px">'
            + '<div style="width:48px;height:48px;border-radius:50%;background:' + primaryColor + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;animation:criRobotPop 0.5s cubic-bezier(0.34,1.56,0.64,1) both;animation-delay:0.1s;box-shadow:0 4px 16px rgba(var(--primary-rgb),0.3)">'
            + '<i class="fas fa-robot" style="color:#fff;font-size:20px"></i>'
            + '</div>'
            + '<div style="animation:criTextSlide 0.4s ease both;animation-delay:0.2s">'
            + '<div style="font-size:15px;font-weight:700;color:var(--text);margin-bottom:3px">Looking good! <span style="font-size:16px">&#x1F44D;</span></div>'
            + '<div style="font-size:13px;color:var(--text-secondary);line-height:1.6">This post was crafted by AI and is already optimized for maximum engagement. Make changes to the copy if you\'d like a fresh critique!</div>'
            + '</div></div></div>'
            + '<style>'
            + '@keyframes criOptBounce{0%{opacity:0;transform:scale(0.85) translateY(10px)}100%{opacity:1;transform:scale(1) translateY(0)}}'
            + '@keyframes criRobotPop{0%{opacity:0;transform:scale(0) rotate(-15deg)}100%{opacity:1;transform:scale(1) rotate(0)}}'
            + '@keyframes criTextSlide{0%{opacity:0;transform:translateX(10px)}100%{opacity:1;transform:translateX(0)}}'
            + '</style>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
    panel.style.display = 'block';
    panel.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted)"><i class="fas fa-spinner fa-spin" style="font-size:20px;margin-bottom:8px;display:block"></i>Running AI-powered content analysis...</div>';

    fetch(BASE + '/content-strategy/critique', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: content, csrf_token: csrfToken() })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        panel.style.display = 'block';
        var html = '';
        if (data.strengths && data.strengths.length) {
            html += '<div style="margin-bottom:12px"><div style="font-size:12px;font-weight:700;color:var(--success);margin-bottom:6px"><i class="fas fa-check-circle" style="margin-right:4px"></i> Strengths</div>';
            data.strengths.forEach(function(s) { html += '<div style="font-size:13px;color:var(--text);padding:2px 0 2px 16px">' + s + '</div>'; });
            html += '</div>';
        }
        if (data.suggestions && data.suggestions.length) {
            html += '<div style="margin-bottom:12px"><div style="font-size:12px;font-weight:700;color:var(--warning);margin-bottom:6px"><i class="fas fa-lightbulb" style="margin-right:4px"></i> Suggestions</div>';
            data.suggestions.forEach(function(s) { html += '<div style="font-size:13px;color:var(--text);padding:2px 0 2px 16px">' + s + '</div>'; });
            html += '</div>';
        }
        if (data.revised) {
            var formattedRevised = data.revised
                .replace(/</g, '&lt;')
                .replace(/\n/g, '<br>')
                .replace(/(#\w+)/g, '<span style="color:var(--primary);font-weight:500">$1</span>');

            html += '<div style="margin-bottom:8px"><div style="font-size:12px;font-weight:700;color:var(--primary);margin-bottom:6px"><i class="fas fa-magic" style="margin-right:4px"></i> AI-Revised Version</div>'
                + '<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;font-size:13px;line-height:1.8;color:var(--text)">' + formattedRevised + '</div>'
                + '<div style="margin-top:10px;display:flex;gap:8px">'
                + '<button class="btn btn-primary btn-sm" onclick="document.getElementById(\'edit-content\').value=this.dataset.rev;document.getElementById(\'critique-results\').style.display=\'none\';showToast(\'AI revision applied\',\'success\')" data-rev="' + data.revised.replace(/"/g,'&quot;').replace(/'/g,'&#39;') + '"><i class="fas fa-check"></i> Use AI Version</button>'
                + '<button class="btn btn-ghost btn-sm" onclick="document.getElementById(\'critique-results\').style.display=\'none\'">Dismiss</button>'
                + '</div></div>';
        }
        panel.innerHTML = html || '<div style="color:var(--text-muted)">No feedback available.</div>';
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(function(err) {
        panel.style.display = 'block';
        panel.innerHTML = '<div style="color:var(--danger)"><i class="fas fa-exclamation-circle" style="margin-right:4px"></i> Analysis failed: ' + (err.message || 'Try again.') + '</div>';
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-robot"></i> AI Critique';
    });
}

// Expose functions to global scope for onclick handlers
window.savePost = savePost;
window.schedulePost = schedulePost;
window.postNow = postNow;
window.retryPost = retryPost;
window.deletePost = deletePost;
window.regenerateImage = regenerateImage;
window.critiquePost = critiquePost;
window.loadPostLogs = loadPostLogs;
})();
</script>
