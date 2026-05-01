<?php
$tourRole = $_SESSION['role'] ?? 'admin';
$brandingService = new BrandingService();
$tourBrand = $brandingService->get($GLOBALS['client_id']);
$tourPrimaryColor = $tourBrand['primary_color'] ?? '#6366f1';
$tourCompany = htmlspecialchars($tourBrand['company_name'] ?? 'SolidTech');
$faviconUrl = BASE_URL . '/favicon-48.png';

// Steps: target (CSS selector), title, text, url (page to navigate to), group (accordion 1/2/3)
// Tour selectors updated April 2026 after Phases 1-3 rollout — some old
// selectors (.main-content .card) were too generic; new ones point at
// specific IDs/classes that survive future refactors.

$adminSteps = [
    // ── DASHBOARD ──
    ['target' => '.nav-item[href*="/dashboard"]', 'title' => 'Welcome to your command center', 'text' => 'This is the Dashboard — the first screen you see after logging in. Let\'s take a quick tour.', 'url' => '/dashboard', 'group' => 1, 'position' => 'right'],
    ['target' => '.stats-grid', 'title' => 'Live stats at a glance', 'text' => 'These cards show your content pipeline: total posts, scheduled, published, and drafts. Click any card to filter the Posts page to that status.', 'url' => null, 'group' => 0, 'position' => 'bottom'],
    ['target' => '.quick-actions-grid', 'title' => 'Quick actions', 'text' => 'Jump straight to the most-used features. Generate content, check the calendar, or view reports — one click away.', 'url' => null, 'group' => 0, 'position' => 'bottom'],

    // ── GENERATOR ──
    ['target' => '.nav-item[href*="/generator"]', 'title' => 'Content Generator', 'text' => 'Where AI creates on-brand content for you.', 'url' => '/generator', 'group' => 1, 'position' => 'right'],
    ['target' => '#btn-generate-single', 'title' => 'Plan & Generate', 'text' => 'Generate a full week of posts or a single focused post. The AI uses your themes, brand voice, and content memory to create unique content every time.', 'url' => null, 'group' => 0, 'position' => 'top'],

    // ── POSTS ──
    ['target' => '.nav-item[href*="/posts"]', 'title' => 'Posts Manager', 'text' => 'Browse and manage all your content.', 'url' => '/posts', 'group' => 1, 'position' => 'right'],
    ['target' => '#kanbanView', 'title' => 'Kanban view (default)', 'text' => 'Posts are grouped by platform and split into "Scheduled & Drafts" (blue) and "Published" (green) buckets. Click any bucket header to collapse/expand it. Drafts appear with a dashed orange border so you can spot incomplete posts at a glance.', 'url' => null, 'group' => 0, 'position' => 'top'],

    // ── CALENDAR ──
    ['target' => '.nav-item[href*="/calendar"]', 'title' => 'Content Calendar', 'text' => 'Visual monthly view of your schedule. Color-coded dots show each post\'s status. Hover for previews, click for details.', 'url' => '/calendar', 'group' => 1, 'position' => 'right'],

    // ── REPORTS ──
    ['target' => '.nav-item[href*="/reporting"]', 'title' => 'Reports & Analytics', 'text' => 'Track performance, manage failed posts, and generate branded client reports.', 'url' => '/reporting', 'group' => 1, 'position' => 'right'],
    ['target' => '#savings-card', 'title' => 'Estimated cost savings', 'text' => 'This card shows how much labor your automation replaces in real dollars. Click the info icon to see the math, or "Edit math" to tune the minutes-per-post and hourly rate for your market.', 'url' => null, 'group' => 0, 'position' => 'bottom'],
    ['target' => '#savings-card .btn-primary', 'title' => 'Generate a branded report', 'text' => 'Click here to open the report wizard. Pick a date range, choose view-or-email delivery, and the AI will build a full branded report in about 10 seconds — complete with charts, post cards, executive summary, and helpful tips.', 'url' => null, 'group' => 0, 'position' => 'left'],

    // ── STRATEGY + DESIGN ──
    ['target' => '.nav-item[href*="/content-strategy"]', 'title' => 'Content Strategy', 'text' => 'Define themes, assign them to days of the week, and give the AI copy instructions and sample posts to follow.', 'url' => '/content-strategy', 'group' => 2, 'position' => 'right'],
    ['target' => '.nav-item[href*="/art-direction"]', 'title' => 'Art Direction', 'text' => 'Control how AI-generated images look: realism level, subject exclusions, watermark placement, and quick presets.', 'url' => '/art-direction', 'group' => 2, 'position' => 'right'],

    // ── ADMIN SETTINGS ──
    ['target' => '.nav-item[href*="/branding"]', 'title' => 'Branding', 'text' => 'Set your logo (plus a dark logo variant for reports), colors, favicon, contact info, and API keys. Everything here applies platform-wide.', 'url' => '/branding', 'group' => 2, 'position' => 'right'],
    ['target' => '.nav-item[href*="/users"]', 'title' => 'User Management', 'text' => 'Invite Editors and Reviewers, configure the approval workflow, and manage your team.', 'url' => '/users', 'group' => 2, 'position' => 'right'],
    ['target' => '.nav-item[href*="/smtp"]', 'title' => 'Email settings', 'text' => 'Configure your email provider for user invitations and report delivery emails. Supports SMTP, SendGrid, Mailgun, and Emailit.', 'url' => '/smtp', 'group' => 2, 'position' => 'right'],
    ['target' => '.nav-item[href*="/settings/activity-log"]', 'title' => 'Activity Log', 'text' => 'Admin-only audit trail. Every login, post edit, role change, setting update, and publish is logged here. Filter by user, action type, or date range to answer "who did what, when?"', 'url' => '/settings/activity-log', 'group' => 2, 'position' => 'right'],

    // ── FINALE ──
    ['target' => null, 'title' => 'You\'re all set!', 'text' => 'You have full control over ' . $tourCompany . '\'s social media engine. Head to the Generator to create your first batch of content, then generate a report to show off the results.', 'url' => null, 'group' => 0, 'position' => 'center'],
];

$editorSteps = [
    // ── DASHBOARD ──
    ['target' => '.nav-item[href*="/dashboard"]', 'title' => 'Welcome to your home base', 'text' => 'The Dashboard is where you\'ll land every time you log in. Let\'s see what\'s here.', 'url' => '/dashboard', 'group' => 1, 'position' => 'right'],
    ['target' => '.stats-grid', 'title' => 'Your content stats', 'text' => 'Track your posts at a glance: how many are scheduled, published, or still in draft. Click any card to filter the Posts page.', 'url' => null, 'group' => 0, 'position' => 'bottom'],

    // ── GENERATOR ──
    ['target' => '.nav-item[href*="/generator"]', 'title' => 'Content Generator', 'text' => 'Create AI-powered content. Generate a full week or individual posts.', 'url' => '/generator', 'group' => 1, 'position' => 'right'],
    ['target' => '#btn-generate-single', 'title' => 'AI-powered creation', 'text' => 'Pick a topic and post type, let the AI generate on-brand copy, and edit the result before saving as a draft.', 'url' => null, 'group' => 0, 'position' => 'top'],

    // ── POSTS ──
    ['target' => '.nav-item[href*="/posts"]', 'title' => 'Posts Manager', 'text' => 'Browse all your content in kanban or table view.', 'url' => '/posts', 'group' => 1, 'position' => 'right'],
    ['target' => '#kanbanView', 'title' => 'Scheduled & Drafts vs Published', 'text' => 'The board splits into two collapsible buckets per platform: upcoming work on top, published posts below. Drafts show as dashed orange cards so you can see what still needs to be scheduled.', 'url' => null, 'group' => 0, 'position' => 'top'],

    // ── CALENDAR ──
    ['target' => '.nav-item[href*="/calendar"]', 'title' => 'Content Calendar', 'text' => 'See your schedule visually. Spot gaps and plan ahead. Color-coded by status.', 'url' => '/calendar', 'group' => 1, 'position' => 'right'],

    // ── REPORTS ──
    ['target' => '.nav-item[href*="/reporting"]', 'title' => 'Reports', 'text' => 'Track which posts went out and spot any failures. You can also generate branded reports from here.', 'url' => '/reporting', 'group' => 1, 'position' => 'right'],
    ['target' => '#savings-card', 'title' => 'Cost savings', 'text' => 'See how much labor the automated workflow is saving in real dollars, based on the math your admin has configured.', 'url' => null, 'group' => 0, 'position' => 'bottom'],
    ['target' => '#savings-card .btn-primary', 'title' => 'Generate a client report', 'text' => 'Click here to build a branded report for any date range. Deliver it by email or view it directly and save as PDF — perfect for client updates.', 'url' => null, 'group' => 0, 'position' => 'left'],

    // ── FINALE ──
    ['target' => null, 'title' => 'Ready to create!', 'text' => 'Head to the Generator to start making content for ' . $tourCompany . '. Happy posting!', 'url' => null, 'group' => 0, 'position' => 'center'],
];

$reviewerSteps = [
    // ── DASHBOARD ──
    ['target' => '.nav-item[href*="/dashboard"]', 'title' => 'Welcome', 'text' => 'The Dashboard gives you an overview of the content pipeline. Let\'s look around.', 'url' => '/dashboard', 'group' => 1, 'position' => 'right'],
    ['target' => '.stats-grid', 'title' => 'Content overview', 'text' => 'See the big picture: how many posts are in the pipeline, what\'s scheduled, and what\'s been published.', 'url' => null, 'group' => 0, 'position' => 'bottom'],

    // ── REVIEWS (main workspace) ──
    ['target' => '.nav-item[href*="/reviews"]', 'title' => 'Review Queue', 'text' => 'This is your main workspace. Posts needing your approval appear here.', 'url' => '/reviews', 'group' => 1, 'position' => 'right'],
    ['target' => '.main-content', 'title' => 'Approve or request changes', 'text' => 'Each post shows its content preview and approval progress. Click Approve to give the green light, or Request Changes to send feedback to the editor.', 'url' => null, 'group' => 0, 'position' => 'top'],

    // ── CALENDAR (view only) ──
    ['target' => '.nav-item[href*="/calendar"]', 'title' => 'Content Calendar', 'text' => 'See what\'s scheduled and when posts are going out across all platforms. Read-only for reviewers.', 'url' => '/calendar', 'group' => 1, 'position' => 'right'],

    // ── REPORTS (view only) ──
    ['target' => '.nav-item[href*="/reporting"]', 'title' => 'Reports', 'text' => 'You can view the reports page to track what\'s been published and how it performed. Admins and editors are the ones who generate new reports.', 'url' => '/reporting', 'group' => 1, 'position' => 'right'],

    // ── FINALE ──
    ['target' => null, 'title' => 'Ready to review!', 'text' => 'Check the Review Queue for posts awaiting your approval. The team is counting on you!', 'url' => null, 'group' => 0, 'position' => 'center'],
];

$tourSteps = match ($tourRole) {
    'admin' => $adminSteps,
    'editor' => $editorSteps,
    'reviewer' => $reviewerSteps,
    default => $editorSteps,
};
?>

<style>
/* ═══ Tour Overlay & Spotlight ═══ */
.tour-overlay {
    position: fixed;
    inset: 0;
    z-index: 99998;
    background: rgba(0,0,0,0.6);
    transition: opacity 0.3s ease;
    pointer-events: auto;
}
.tour-spotlight {
    position: fixed;
    z-index: 99999;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.55);
    border-radius: 12px;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
}

/* ═══ Page Transition Overlay ═══ */
.tour-transition {
    position: fixed;
    inset: 0;
    z-index: 100001;
    background: linear-gradient(165deg, <?= htmlspecialchars($tourPrimaryColor) ?> 0%, color-mix(in srgb, <?= htmlspecialchars($tourPrimaryColor) ?> 35%, #0a0a0a) 55%, #0a0a0a 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.35s ease;
}
.tour-transition.active {
    opacity: 1;
    pointer-events: all;
    pointer-events: all;
}

/* Transition inner elements */
.tour-tr-ring {
    width: 52px; height: 52px;
    border: 2.5px solid rgba(255,255,255,0.15);
    border-top-color: rgba(255,255,255,0.7);
    border-radius: 50%;
    animation: tourTrSpin 0.8s linear infinite;
    margin-bottom: 16px;
}
@keyframes tourTrSpin { to { transform: rotate(360deg); } }

.tour-tr-text {
    font-size: 14px;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    letter-spacing: 0.03em;
}

/* Ambient particles in transition */
.tour-tr-particles {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}
.tour-tr-particles span {
    position: absolute;
    width: 3px; height: 3px;
    border-radius: 50%;
    background: rgba(255,255,255,0.4);
    opacity: 0;
    animation: tourTrFloat 2.5s ease-in-out infinite;
}
@keyframes tourTrFloat {
    0% { bottom: -5px; opacity: 0; }
    20% { opacity: 0.5; }
    80% { opacity: 0.15; }
    100% { bottom: 105%; opacity: 0; }
}

/* ═══ Tooltip ═══ */
.tour-tooltip {
    position: fixed;
    z-index: 100000;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 18px;
    padding: 24px 24px 20px 80px;
    max-width: 380px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.05);
    opacity: 0;
    transform: translateY(10px) scale(0.97);
    transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.tour-tooltip.visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.tour-avatar {
    position: absolute;
    left: 18px;
    top: 22px;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: <?= htmlspecialchars($tourPrimaryColor) ?>;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.tour-avatar img {
    width: 28px; height: 28px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}
.tour-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text, #1a1a2e);
    margin-bottom: 6px;
}
.tour-text {
    font-size: 13px;
    color: var(--text-secondary, #64748b);
    line-height: 1.65;
    margin-bottom: 18px;
}

/* Progress dots */
.tour-progress {
    display: flex;
    gap: 5px;
    margin-bottom: 14px;
}
.tour-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--border, #e2e8f0);
    transition: all 0.3s ease;
}
.tour-dot.active {
    background: <?= htmlspecialchars($tourPrimaryColor) ?>;
    transform: scale(1.25);
    box-shadow: 0 0 6px <?= htmlspecialchars($tourPrimaryColor) ?>66;
}
.tour-dot.done {
    background: <?= htmlspecialchars($tourPrimaryColor) ?>;
    opacity: 0.5;
}

.tour-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tour-step-counter {
    font-size: 12px;
    color: var(--text-muted, #94a3b8);
    font-weight: 500;
}
.tour-btn-next {
    padding: 9px 22px;
    border: none;
    border-radius: 8px;
    background: <?= htmlspecialchars($tourPrimaryColor) ?>;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.tour-btn-next:hover { filter: brightness(1.1); transform: translateY(-1px); }
.tour-btn-next::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
    animation: tourBtnShine 3s ease infinite;
}
@keyframes tourBtnShine {
    0% { left: -100%; }
    30% { left: 100%; }
    100% { left: 100%; }
}
.tour-btn-skip {
    padding: 8px 12px;
    border: none;
    background: transparent;
    color: var(--text-muted, #94a3b8);
    font-size: 12px;
    font-family: inherit;
    cursor: pointer;
    transition: color 0.2s ease;
}
.tour-btn-skip:hover { color: var(--text, #1a1a2e); }

/* Center mode (finale) */
.tour-tooltip.center-mode {
    padding: 36px;
    text-align: center;
    max-width: 420px;
}
.tour-tooltip.center-mode .tour-avatar {
    position: static;
    margin: 0 auto 18px;
    width: 60px; height: 60px;
}
.tour-tooltip.center-mode .tour-avatar img { width: 36px; height: 36px; }
.tour-tooltip.center-mode .tour-title { font-size: 20px; }
.tour-tooltip.center-mode .tour-progress { justify-content: center; }
.tour-tooltip.center-mode .tour-footer { justify-content: center; }
.tour-tooltip.center-mode .tour-step-counter { display: none; }

/* Welcome intro card */
.tour-welcome {
    position: fixed;
    inset: 0;
    z-index: 100002;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(165deg, <?= htmlspecialchars($tourPrimaryColor) ?> 0%, color-mix(in srgb, <?= htmlspecialchars($tourPrimaryColor) ?> 35%, #0a0a0a) 55%, #0a0a0a 100%);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.4s ease;
}
.tour-welcome.active { opacity: 1; pointer-events: all; }
.tour-welcome-card {
    text-align: center;
    color: #fff;
    max-width: 400px;
    padding: 40px;
    opacity: 0;
    transform: scale(0.9) translateY(20px);
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.tour-welcome.active .tour-welcome-card {
    opacity: 1;
    transform: scale(1) translateY(0);
}
.tour-welcome-icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.tour-welcome-icon img {
    width: 44px; height: 44px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}
.tour-welcome h2 {
    font-size: 24px;
    font-weight: 800;
    margin-bottom: 8px;
}
.tour-welcome p {
    font-size: 15px;
    color: rgba(255,255,255,0.6);
    line-height: 1.6;
    margin-bottom: 28px;
}
.tour-welcome-btn {
    padding: 12px 32px;
    border: none;
    border-radius: 8px;
    background: #fff;
    color: <?= htmlspecialchars($tourPrimaryColor) ?>;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.tour-welcome-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
.tour-skip-welcome {
    display: block;
    margin-top: 16px;
    background: none;
    border: none;
    color: rgba(255,255,255,0.4);
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
}
.tour-skip-welcome:hover { color: rgba(255,255,255,0.7); }
</style>

<!-- Welcome Intro -->
<div class="tour-welcome" id="tourWelcome">
    <div class="tour-welcome-card">
        <div class="tour-welcome-icon">
            <img src="<?= $faviconUrl ?>" alt="">
        </div>
        <h2>Welcome to <?= $tourCompany ?> Social</h2>
        <p>Let's take a quick tour of the platform. We'll walk you through the key features so you can hit the ground running.</p>
        <button class="tour-welcome-btn" onclick="beginTour()">Start Tour</button>
        <button class="tour-skip-welcome" onclick="skipTourEntirely()">Skip, I'll explore on my own</button>
    </div>
</div>

<!-- Overlay, Spotlight, Tooltip -->
<div id="tourOverlay" class="tour-overlay" style="display:none"></div>
<div id="tourSpotlight" class="tour-spotlight" style="display:none"></div>
<div id="tourTooltip" class="tour-tooltip" style="display:none">
    <div class="tour-avatar" id="tourAvatar">
        <img src="<?= $faviconUrl ?>" alt="">
    </div>
    <div class="tour-title" id="tourTitle"></div>
    <div class="tour-text" id="tourText"></div>
    <div class="tour-progress" id="tourProgress"></div>
    <div class="tour-footer">
        <span class="tour-step-counter" id="tourCounter"></span>
        <div>
            <button class="tour-btn-skip" id="tourSkipBtn" onclick="tourSkip()">Skip Tour</button>
            <button class="tour-btn-next" id="tourNextBtn" onclick="tourNext()">Next</button>
        </div>
    </div>
</div>

<!-- Page Transition Overlay (between steps) -->
<div class="tour-transition" id="tourTransition">
    <div class="tour-tr-particles" id="tourTrParticles"></div>
    <div class="tour-tr-ring"></div>
    <div class="tour-tr-text" id="tourTrText">Loading...</div>
</div>

<!-- Tour Boot Sequence (restart intro) -->
<div class="tour-boot" id="tourBoot">
    <div class="tour-boot-particles" id="tourBootParticles"></div>
    <!-- Atom orbits -->
    <div class="tour-boot-atom">
        <div class="tb-orbit tb-orbit-1"><div class="tb-electron"></div></div>
        <div class="tb-orbit tb-orbit-2"><div class="tb-electron"></div></div>
        <div class="tb-orbit tb-orbit-3"><div class="tb-electron"></div></div>
    </div>
    <!-- Pulse rings -->
    <div class="tb-pulse"></div>
    <div class="tb-pulse" style="animation-delay:0.6s"></div>
    <div class="tb-pulse" style="animation-delay:1.2s"></div>
    <!-- Center content -->
    <div class="tour-boot-center">
        <div class="tour-boot-icon">
            <img src="<?= $faviconUrl ?>" alt="">
        </div>
        <div class="tour-boot-title" id="tourBootTitle">Initializing Tour</div>
        <div class="tour-boot-sub" id="tourBootSub">Preparing your guided experience...</div>
        <div class="tour-boot-bar">
            <div class="tour-boot-bar-fill" id="tourBootBar"></div>
        </div>
    </div>
</div>
<style>
.tour-boot {
    position: fixed; inset: 0; z-index: 100003;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(165deg, <?= htmlspecialchars($tourPrimaryColor) ?> 0%, color-mix(in srgb, <?= htmlspecialchars($tourPrimaryColor) ?> 35%, #0a0a0a) 55%, #0a0a0a 100%);
    opacity: 0; pointer-events: none;
    transition: opacity 0.4s ease;
    overflow: hidden;
}
.tour-boot.active { opacity: 1; pointer-events: all; }

.tour-boot-particles {
    position: absolute; inset: 0; overflow: hidden; pointer-events: none;
}
.tour-boot-particles span {
    position: absolute; width: 3px; height: 3px; border-radius: 50%;
    background: rgba(255,255,255,0.4); opacity: 0;
    animation: tourTrFloat 3s ease-in-out infinite;
}

/* Atom */
.tour-boot-atom {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 300px; height: 300px; pointer-events: none;
}
.tb-orbit {
    position: absolute; border-radius: 50%;
}
.tb-orbit-1 {
    width: 320px; height: 120px; top: calc(50% - 60px); left: calc(50% - 160px);
    border: 1.5px solid rgba(255,255,255,0.1);
    animation: tourTrSpin 7s linear infinite;
}
.tb-orbit-2 {
    width: 280px; height: 100px; top: calc(50% - 50px); left: calc(50% - 140px);
    border: 1.5px solid rgba(255,255,255,0.08);
    animation: tourTrSpin 5s linear infinite reverse;
    transform: rotate(55deg);
}
.tb-orbit-3 {
    width: 100px; height: 280px; top: calc(50% - 140px); left: calc(50% - 50px);
    border: 1.5px solid rgba(255,255,255,0.06);
    animation: tourTrSpin 9s linear infinite;
    transform: rotate(25deg);
}
.tb-electron {
    position: absolute; width: 6px; height: 6px;
    background: #fff; border-radius: 50%;
    top: -3px; left: calc(50% - 3px);
    box-shadow: 0 0 14px rgba(255,255,255,0.7);
}

/* Pulse rings */
.tb-pulse {
    position: absolute; top: 50%; left: 50%;
    width: 100px; height: 100px; margin: -50px 0 0 -50px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.25);
    animation: tbPulse 2.4s ease-out infinite; pointer-events: none;
}
@keyframes tbPulse {
    0% { transform: scale(1); opacity: 0.5; }
    100% { transform: scale(3); opacity: 0; }
}

/* Center content */
.tour-boot-center {
    position: relative; z-index: 10;
    display: flex; flex-direction: column; align-items: center;
    text-align: center;
}
.tour-boot-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(255,255,255,0.1); backdrop-filter: blur(4px);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 20px;
    opacity: 0; transform: scale(0.5);
    animation: tbIconIn 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.3s forwards;
}
.tour-boot-icon img {
    width: 42px; height: 42px; object-fit: contain;
    filter: brightness(0) invert(1);
}
@keyframes tbIconIn { to { opacity: 1; transform: scale(1); } }

.tour-boot-title {
    font-size: 20px; font-weight: 700; color: #fff;
    margin-bottom: 8px;
    opacity: 0; animation: tbTextIn 0.4s ease 0.6s forwards;
}
.tour-boot-sub {
    font-size: 14px; color: rgba(255,255,255,0.5);
    margin-bottom: 24px; min-height: 20px;
    opacity: 0; animation: tbTextIn 0.4s ease 0.8s forwards;
    transition: opacity 0.25s ease;
}
@keyframes tbTextIn { to { opacity: 1; } }

.tour-boot-bar {
    width: 160px; height: 2px;
    background: rgba(255,255,255,0.1); border-radius: 2px;
    overflow: hidden;
    opacity: 0; animation: tbTextIn 0.3s ease 1s forwards;
}
.tour-boot-bar-fill {
    width: 0; height: 100%;
    background: linear-gradient(90deg, rgba(255,255,255,0.6), #fff);
    border-radius: 2px;
    transition: width 2s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
(function() {
    var BASE = '<?= BASE_URL ?>';
    var STEPS = <?= json_encode(array_values($tourSteps)) ?>;
    var currentStep = 0;
    var isManualRestart = false;

    // Generate particles for transition + boot
    var pCont = document.getElementById('tourTrParticles');
    for (var i = 0; i < 12; i++) {
        var sp = document.createElement('span');
        sp.style.left = (5 + Math.random() * 90) + '%';
        sp.style.animationDuration = (2 + Math.random() * 2) + 's';
        sp.style.animationDelay = '-' + (Math.random() * 3) + 's';
        pCont.appendChild(sp);
    }
    var bCont = document.getElementById('tourBootParticles');
    for (var i = 0; i < 18; i++) {
        var sp = document.createElement('span');
        sp.style.left = (3 + Math.random() * 94) + '%';
        sp.style.animationDuration = (2.5 + Math.random() * 2.5) + 's';
        sp.style.animationDelay = '-' + (Math.random() * 4) + 's';
        sp.style.width = sp.style.height = (2 + Math.random() * 3) + 'px';
        bCont.appendChild(sp);
    }

    // Check if resuming mid-tour
    var savedStep = localStorage.getItem('tourStep');
    var tourActive = localStorage.getItem('tourActive');

    window.startTour = function() {
        // Show welcome screen
        var welcome = document.getElementById('tourWelcome');
        if (welcome) {
            welcome.classList.add('active');
        }
    };

    window.startTourDirect = function() {
        // Show cinematic boot sequence, then start tour
        isManualRestart = true;
        var boot = document.getElementById('tourBoot');
        var subEl = document.getElementById('tourBootSub');
        var barEl = document.getElementById('tourBootBar');
        boot.classList.add('active');

        // Start progress bar
        setTimeout(function() { barEl.style.width = '100%'; }, 200);

        // Cycle status messages
        var msgs = [
            'Preparing your guided experience...',
            'Loading system modules...',
            'Mapping interface components...',
            'Calibrating AI tour engine...',
            'Stand by — almost ready...'
        ];
        var msgIdx = 0;
        var msgTimer = setInterval(function() {
            msgIdx++;
            if (msgIdx < msgs.length) {
                subEl.style.opacity = '0';
                setTimeout(function() {
                    subEl.textContent = msgs[msgIdx];
                    subEl.style.opacity = '1';
                }, 200);
            }
        }, 600);

        // After 3 seconds, start the actual tour
        setTimeout(function() {
            clearInterval(msgTimer);
            boot.style.transition = 'opacity 0.4s ease';
            boot.classList.remove('active');
            setTimeout(function() {
                localStorage.setItem('tourActive', '1');
                localStorage.setItem('tourStep', '0');
                currentStep = 0;
                navigateToStep(0);
            }, 400);
        }, 3000);
    };

    window.beginTour = function() {
        // Dismiss welcome, then show boot sequence
        var welcome = document.getElementById('tourWelcome');
        if (welcome) {
            welcome.style.transition = 'opacity 0.4s ease';
            welcome.style.opacity = '0';
            setTimeout(function() { welcome.remove(); }, 400);
        }
        // Trigger the boot sequence (same as restart)
        setTimeout(function() {
            isManualRestart = false;
            var boot = document.getElementById('tourBoot');
            var subEl = document.getElementById('tourBootSub');
            var barEl = document.getElementById('tourBootBar');
            boot.classList.add('active');
            setTimeout(function() { barEl.style.width = '100%'; }, 200);

            var msgs = [
                'Preparing your guided experience...',
                'Loading system modules...',
                'Mapping interface components...',
                'Calibrating AI tour engine...',
                'Stand by — almost ready...'
            ];
            var msgIdx = 0;
            var msgTimer = setInterval(function() {
                msgIdx++;
                if (msgIdx < msgs.length) {
                    subEl.style.opacity = '0';
                    setTimeout(function() {
                        subEl.textContent = msgs[msgIdx];
                        subEl.style.opacity = '1';
                    }, 200);
                }
            }, 600);

            setTimeout(function() {
                clearInterval(msgTimer);
                boot.style.transition = 'opacity 0.4s ease';
                boot.classList.remove('active');
                localStorage.setItem('tourActive', '1');
                localStorage.setItem('tourStep', '0');
                currentStep = 0;
                setTimeout(function() { showStep(0); }, 400);
            }, 3000);
        }, 450);
    };

    window.skipTourEntirely = function() {
        var welcome = document.getElementById('tourWelcome');
        if (welcome) {
            welcome.style.opacity = '0';
            setTimeout(function() { welcome.remove(); }, 400);
        }
        endTour();
    };

    window.tourNext = function() {
        var nextIdx = currentStep + 1;
        if (nextIdx >= STEPS.length) {
            endTour();
            return;
        }
        navigateToStep(nextIdx);
    };

    window.tourSkip = function() {
        endTour();
    };

    function navigateToStep(idx) {
        var step = STEPS[idx];
        currentStep = idx;
        localStorage.setItem('tourStep', idx.toString());

        // Check if we need to change pages
        var currentPath = window.location.pathname;
        var needsNav = step.url && !currentPath.endsWith(step.url) && !currentPath.endsWith(step.url + '/');

        if (needsNav) {
            // Show transition overlay, then navigate
            showTransition(step.title);
            setTimeout(function() {
                window.location.href = BASE + step.url;
            }, 800);
        } else {
            // Same page — just show the step
            showStep(idx);
        }
    }

    function showTransition(title) {
        var tr = document.getElementById('tourTransition');
        document.getElementById('tourTrText').textContent = 'Loading ' + title + '...';
        tr.classList.add('active');

        // Hide current tooltip
        hideTooltip();
    }

    function hideTransition() {
        var tr = document.getElementById('tourTransition');
        tr.style.transition = 'opacity 0.4s ease';
        tr.classList.remove('active');
    }

    function showStep(idx) {
        var step = STEPS[idx];
        var tooltip = document.getElementById('tourTooltip');
        var spotlight = document.getElementById('tourSpotlight');
        var overlay = document.getElementById('tourOverlay');

        // Open the correct accordion group
        if (step.group > 0) {
            openAccordionGroup(step.group);
        }

        // Small delay for accordion animation
        setTimeout(function() {
            // Update tooltip content
            document.getElementById('tourTitle').textContent = step.title;
            document.getElementById('tourText').textContent = step.text;
            document.getElementById('tourCounter').textContent = 'Step ' + (idx + 1) + ' of ' + STEPS.length;

            var isLast = idx === STEPS.length - 1;
            document.getElementById('tourNextBtn').textContent = isLast ? 'Get Started' : 'Next';
            document.getElementById('tourSkipBtn').style.display = isLast ? 'none' : '';

            // Build progress dots
            var progHtml = '';
            for (var i = 0; i < STEPS.length; i++) {
                var cls = 'tour-dot';
                if (i === idx) cls += ' active';
                else if (i < idx) cls += ' done';
                progHtml += '<div class="' + cls + '"></div>';
            }
            document.getElementById('tourProgress').innerHTML = progHtml;

            if (!step.target || step.position === 'center') {
                // Center mode (finale)
                spotlight.style.display = 'none';
                overlay.style.display = '';
                tooltip.className = 'tour-tooltip center-mode';
                tooltip.style.display = '';
                tooltip.style.left = '50%';
                tooltip.style.top = '50%';
                tooltip.style.transform = 'translate(-50%, -50%)';
                requestAnimationFrame(function() { tooltip.classList.add('visible'); });
            } else {
                tooltip.className = 'tour-tooltip';
                var target = document.querySelector(step.target);
                if (!target) {
                    // Skip missing elements
                    currentStep++;
                    localStorage.setItem('tourStep', currentStep.toString());
                    showStep(currentStep);
                    return;
                }

                // Scroll target into view if needed
                target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                setTimeout(function() {
                    var rect = target.getBoundingClientRect();
                    var pad = 6;

                    spotlight.style.display = '';
                    spotlight.style.left = (rect.left - pad) + 'px';
                    spotlight.style.top = (rect.top - pad) + 'px';
                    spotlight.style.width = (rect.width + pad * 2) + 'px';
                    spotlight.style.height = (rect.height + pad * 2) + 'px';
                    overlay.style.display = 'none';

                    tooltip.style.display = '';
                    tooltip.style.transform = '';

                    var tooltipWidth = 380;

                    if (step.position === 'bottom') {
                        // Position below the element (for on-page UI highlights)
                        var leftPos = rect.left + (rect.width / 2) - (tooltipWidth / 2);
                        leftPos = Math.max(20, Math.min(leftPos, window.innerWidth - tooltipWidth - 20));
                        tooltip.style.left = leftPos + 'px';
                        tooltip.style.top = (rect.bottom + 14) + 'px';

                        // If tooltip would go below viewport, position above instead
                        if (rect.bottom + 14 + 200 > window.innerHeight) {
                            tooltip.style.top = Math.max(12, rect.top - 14) + 'px';
                            tooltip.style.transform = 'translateY(-100%)';
                        }
                    } else {
                        // Position to the right (for sidebar nav items)
                        var leftPos = rect.right + 18;
                        if (leftPos + tooltipWidth > window.innerWidth - 20) {
                            leftPos = window.innerWidth - tooltipWidth - 20;
                        }
                        tooltip.style.left = leftPos + 'px';
                        tooltip.style.top = Math.max(12, rect.top - 10) + 'px';
                    }

                    requestAnimationFrame(function() { tooltip.classList.add('visible'); });
                }, 150);
            }
        }, step.group > 0 ? 300 : 100);
    }

    function hideTooltip() {
        var tooltip = document.getElementById('tourTooltip');
        var spotlight = document.getElementById('tourSpotlight');
        var overlay = document.getElementById('tourOverlay');
        tooltip.classList.remove('visible');
        tooltip.style.display = 'none';
        spotlight.style.display = 'none';
        overlay.style.display = 'none';
    }

    function openAccordionGroup(groupNum) {
        var groups = document.querySelectorAll('.nav-group');
        groups.forEach(function(g, i) {
            if (i + 1 === groupNum) {
                g.classList.add('open');
            } else {
                g.classList.remove('open');
            }
        });
    }

    function endTour() {
        hideTooltip();
        localStorage.removeItem('tourStep');
        localStorage.removeItem('tourActive');

        // Mark complete on server (only if not a manual restart replay)
        if (!isManualRestart) {
            fetch(BASE + '/auth/complete-tour', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
            });
        }

        if (typeof showToast === 'function') {
            showToast('Tour complete! Enjoy the app.', 'success');
        }
    }

    // Resume tour if returning from a page navigation
    if (tourActive === '1' && savedStep !== null) {
        currentStep = parseInt(savedStep, 10);
        // Fade out transition overlay if it was showing
        setTimeout(function() {
            hideTransition();
            setTimeout(function() { showStep(currentStep); }, 450);
        }, 300);
    }
})();
</script>
