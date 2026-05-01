<?php
$branding = (new BrandingService())->get($GLOBALS['client_id']);
$primaryColor = $branding['primary_color'] ?? '#6366f1';
$companyName = $branding['company_name'] ?? APP_NAME;
$logoUrl = $branding['logo_url'] ?? '';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '', '/');
if ($basePath && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath));
}
$currentPath = $currentPath ?: '/';

// Admin first-time: force wizard before anything else
if (!empty($_SESSION['needs_wizard']) && $_SESSION['role'] === 'admin' && $currentPath !== '/wizard') {
    $wizService = new WizardService();
    if (!$wizService->isSetupComplete($GLOBALS['client_id'])) {
        // First admin ever — must complete wizard
        header('Location: ' . BASE_URL . '/wizard');
        exit;
    } else {
        // Subsequent admin — redirect to wizard but they can cancel
        // Only redirect once (not in a loop)
        if (empty($_SESSION['wizard_shown'])) {
            $_SESSION['wizard_shown'] = true;
            header('Location: ' . BASE_URL . '/wizard');
            exit;
        } else {
            // They've been shown the wizard — clear flag, start tour
            unset($_SESSION['needs_wizard']);
            unset($_SESSION['wizard_shown']);
            $_SESSION['needs_tour'] = true;
        }
    }
}

$darkMode = $_COOKIE['darkMode'] ?? 'false';
$isDark = $darkMode === 'true';

// RBAC — current user role
$userRole = $_SESSION['role'] ?? 'reviewer';

// Compute RGB values for the brand color (used by rgba() in CSS vars)
$priR = hexdec(substr(ltrim($primaryColor, '#'), 0, 2));
$priG = hexdec(substr(ltrim($primaryColor, '#'), 2, 2));
$priB = hexdec(substr(ltrim($primaryColor, '#'), 4, 2));
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $isDark ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= htmlspecialchars($companyName) ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>/apple-touch-icon.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
    <style>
        :root {
            --primary: <?= htmlspecialchars($primaryColor) ?>;
            --primary-rgb: <?= "$priR,$priG,$priB" ?>;
            --sidebar-gradient: linear-gradient(180deg, <?= htmlspecialchars($primaryColor) ?> 0%, #0a0a0a 60%, #000000 100%);
        }
    </style>
</head>
<body>

<div class="app-layout">
    <!-- Mobile sidebar backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeMobileSidebar()"></div>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <!-- Constellation effect behind logo -->
            <canvas class="brand-constellation" id="brand-constellation"></canvas>
            <?php if ($logoUrl): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="sidebar-logo sidebar-expanded-only">
            <?php else: ?>
                <span class="sidebar-brand-text"><?= htmlspecialchars($companyName) ?></span>
            <?php endif; ?>
            <div class="sidebar-collapsed-initial"><?= strtoupper(substr($companyName, 0, 1)) ?></div>
        </div>

        <nav class="sidebar-nav">
            <!-- Group 1: Content & Operations (open by default) -->
            <?php
            // Determine which group should be open based on current path
            $group1Paths = ['/', '/dashboard', '/generator', '/posts', '/reviews', '/calendar', '/reporting'];
            $group2Paths = ['/content-strategy', '/art-direction', '/branding', '/wizard', '/users', '/smtp'];
            $group3Paths = ['/memory', '/docs'];
            $g1Open = false;
            $g2Open = false;
            $g3Open = false;
            foreach ($group1Paths as $gp) { if (str_starts_with($currentPath, $gp)) { $g1Open = true; break; } }
            foreach ($group2Paths as $gp) { if (str_starts_with($currentPath, $gp)) { $g2Open = true; break; } }
            foreach ($group3Paths as $gp) { if (str_starts_with($currentPath, $gp)) { $g3Open = true; break; } }
            // Default to Content open if no group matched
            if (!$g1Open && !$g2Open && !$g3Open) $g1Open = true;
            ?>

            <div class="nav-group <?= $g1Open ? 'open' : '' ?>">
                <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                    <span class="nav-group-label">Content</span>
                    <i class="fas fa-chevron-down nav-group-arrow"></i>
                </button>
                <div class="nav-group-items">
                    <a href="<?= BASE_URL ?>/dashboard" class="nav-item <?= in_array($currentPath, ['/', '/dashboard']) ? 'active' : '' ?>">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <?php if (in_array($userRole, ['admin', 'editor'])): ?>
                    <a href="<?= BASE_URL ?>/generator" class="nav-item <?= str_starts_with($currentPath, '/generator') ? 'active' : '' ?>">
                        <i class="fas fa-magic"></i>
                        <span>Generator</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/posts" class="nav-item <?= str_starts_with($currentPath, '/posts') ? 'active' : '' ?>">
                        <i class="fas fa-edit"></i>
                        <span>Posts</span>
                    </a>
                    <?php if (in_array($userRole, ['admin', 'reviewer'])): ?>
                    <a href="<?= BASE_URL ?>/reviews" class="nav-item <?= str_starts_with($currentPath, '/reviews') ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Reviews</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/calendar" class="nav-item <?= str_starts_with($currentPath, '/calendar') ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Calendar</span>
                    </a>
                    <?php if (in_array($userRole, ['admin', 'editor'])): ?>
                    <a href="<?= BASE_URL ?>/reporting" class="nav-item <?= str_starts_with($currentPath, '/reporting') ? 'active' : '' ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($userRole === 'admin'): ?>
            <!-- Group 2: Settings & Configuration -->
            <div class="nav-group <?= $g2Open ? 'open' : '' ?>">
                <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                    <span class="nav-group-label">Settings</span>
                    <i class="fas fa-chevron-down nav-group-arrow"></i>
                </button>
                <div class="nav-group-items">
                    <a href="<?= BASE_URL ?>/content-strategy" class="nav-item <?= str_starts_with($currentPath, '/content-strategy') ? 'active' : '' ?>">
                        <i class="fas fa-chess"></i>
                        <span>Strategy</span>
                    </a>
                    <a href="<?= BASE_URL ?>/art-direction" class="nav-item <?= str_starts_with($currentPath, '/art-direction') ? 'active' : '' ?>">
                        <i class="fas fa-camera"></i>
                        <span>Art Direction</span>
                    </a>
                    <a href="<?= BASE_URL ?>/branding" class="nav-item <?= str_starts_with($currentPath, '/branding') || str_starts_with($currentPath, '/wizard') ? 'active' : '' ?>">
                        <i class="fas fa-palette"></i>
                        <span>Branding</span>
                    </a>
                    <a href="<?= BASE_URL ?>/users" class="nav-item <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog"></i>
                        <span>Users</span>
                    </a>
                    <a href="<?= BASE_URL ?>/smtp" class="nav-item <?= str_starts_with($currentPath, '/smtp') ? 'active' : '' ?>">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Email</span>
                    </a>
                    <a href="<?= BASE_URL ?>/settings/activity-log" class="nav-item <?= str_starts_with($currentPath, '/settings/activity-log') ? 'active' : '' ?>">
                        <i class="fas fa-history"></i>
                        <span>Activity Log</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Group 3: Resources -->
            <div class="nav-group <?= $g3Open ? 'open' : '' ?>">
                <button class="nav-group-toggle" onclick="toggleNavGroup(this)">
                    <span class="nav-group-label">Resources</span>
                    <i class="fas fa-chevron-down nav-group-arrow"></i>
                </button>
                <div class="nav-group-items">
                    <?php if (in_array($userRole, ['admin', 'editor'])): ?>
                    <a href="<?= BASE_URL ?>/memory" class="nav-item <?= str_starts_with($currentPath, '/memory') ? 'active' : '' ?>">
                        <i class="fas fa-brain"></i>
                        <span>Memory</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/docs" class="nav-item <?= str_starts_with($currentPath, '/docs') ? 'active' : '' ?>">
                        <i class="fas fa-book"></i>
                        <span>Docs</span>
                    </a>
                </div>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user-card" id="sidebarUserCard">
                <canvas class="logout-particles" id="logoutParticles"></canvas>
                <div class="sidebar-user-card-inner">
                    <div class="user-monogram" style="background:linear-gradient(135deg, <?= $primaryColor ?>, color-mix(in srgb, <?= $primaryColor ?> 60%, #000))">
                        <?= strtoupper(substr($_SESSION['first_name'] ?? $_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-identity">
                        <span class="user-identity-name"><?= htmlspecialchars($_SESSION['first_name'] ?: ($_SESSION['username'] ?? 'User')) ?></span>
                        <span class="user-identity-role"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'admin')) ?></span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/logout" class="sidebar-logout-btn" title="Sign out" onclick="openLogoutConfirm(event)">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Top Bar -->
        <header class="topbar">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
            <div class="topbar-actions" style="display:flex;align-items:center;gap:10px">
                <!-- Global Generation Status Pill -->
                <div id="genStatusPill" style="display:none;padding:6px 14px;border-radius:20px;background:linear-gradient(135deg,<?= $primaryColor ?>,color-mix(in srgb, <?= $primaryColor ?> 60%, #000));font-size:11px;font-weight:600;color:#fff;cursor:pointer;white-space:nowrap;animation:genPillGlow 2s ease-in-out infinite;transition:all 0.3s ease" onclick="onGenPillClick()">
                    <i class="fas fa-spinner fa-spin" style="margin-right:5px;font-size:10px"></i>
                    <span id="genStatusText">Generating...</span>
                </div>
                <button class="theme-toggle-branded" onclick="restartTour()" title="Take a guided tour" id="tourRestartBtn">
                    <i class="fas fa-question"></i>
                </button>
                <button class="theme-toggle-branded" onclick="toggleTheme()" title="Toggle dark mode" id="themeToggleBtn">
                    <i class="fas <?= $isDark ? 'fa-sun' : 'fa-moon' ?>" id="theme-icon"></i>
                </button>
            </div>
            <style>
            @keyframes genPillGlow {
                0%,100% { box-shadow: 0 0 8px rgba(<?= "$priR,$priG,$priB" ?>, 0.3); }
                50% { box-shadow: 0 0 20px rgba(<?= "$priR,$priG,$priB" ?>, 0.6), 0 0 40px rgba(<?= "$priR,$priG,$priB" ?>, 0.2); }
            }
            </style>
        </header>

        <!-- Page Content -->
        <main class="main-content">
            <?= $content ?>
        </main>
    </div>
</div>

<!-- Modal System -->
<?= ModalService::render() ?>

<!-- Cinematic Page Transition -->
<div id="cinematicTransition" style="position:fixed;inset:0;z-index:99995;display:flex;align-items:center;justify-content:center;flex-direction:column;background:linear-gradient(165deg,<?= $primaryColor ?> 0%,color-mix(in srgb,<?= $primaryColor ?> 35%,#0a0a0a) 55%,#0a0a0a 100%);opacity:0;pointer-events:none;transition:opacity 0.4s ease">
    <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none" id="cinParticles"></div>
    <div id="cinSpinner" style="width:48px;height:48px;border:2.5px solid rgba(255,255,255,0.12);border-top-color:rgba(255,255,255,0.7);border-radius:50%;animation:cinSpin 0.7s linear infinite;margin-bottom:16px"></div>
    <div id="cinText" style="font-size:14px;font-weight:600;color:rgba(255,255,255,0.6);letter-spacing:0.03em">Loading...</div>
    <div style="width:140px;height:2px;background:rgba(255,255,255,0.1);border-radius:2px;margin-top:14px;overflow:hidden">
        <div id="cinBar" style="width:0;height:100%;background:linear-gradient(90deg,rgba(255,255,255,0.7),#fff);border-radius:2px;transition:width 0.8s cubic-bezier(0.4,0,0.2,1)"></div>
    </div>
</div>
<style>
@keyframes cinSpin { to { transform: rotate(360deg); } }
</style>
<script>
(function(){
    // Generate particles
    var pc = document.getElementById('cinParticles');
    for(var i=0;i<10;i++){
        var s=document.createElement('span');
        s.style.cssText='position:absolute;width:3px;height:3px;border-radius:50%;background:rgba(255,255,255,0.4);opacity:0;animation:cinFloat '+(2+Math.random()*2)+'s ease-in-out infinite;animation-delay:-'+(Math.random()*3)+'s;left:'+(5+Math.random()*90)+'%';
        pc.appendChild(s);
    }
    var st=document.createElement('style');
    st.textContent='@keyframes cinFloat{0%{bottom:-5px;opacity:0}20%{opacity:.5}80%{opacity:.15}100%{bottom:105%;opacity:0}}';
    document.head.appendChild(st);

    // Cinematic navigation function
    window.cinematicNav = function(href, label) {
        var el = document.getElementById('cinematicTransition');
        document.getElementById('cinText').textContent = label || 'Preparing...';
        el.style.opacity = '1';
        el.style.pointerEvents = 'all';
        setTimeout(function(){ document.getElementById('cinBar').style.width = '100%'; }, 50);
        setTimeout(function(){ window.location.href = href; }, 1000);
    };

    // Auto-attach to .cinematic-link elements
    document.addEventListener('click', function(e) {
        var link = e.target.closest('.cinematic-link');
        if (link) {
            e.preventDefault();
            var href = link.getAttribute('href');
            var label = link.getAttribute('data-cin-label') || 'Loading...';
            cinematicNav(href, label);
        }
    });
})();
</script>

<!-- Toast Container -->
<div id="toast-container"></div>

<!-- Forced Password Change — Cinematic Flow -->
<?php if (!empty($_SESSION['must_change_password'])): ?>

<!-- Phase 1: Intro gradient with lock + atom -->
<div id="pwIntroOverlay" style="position:fixed;inset:0;z-index:99990;display:flex;align-items:center;justify-content:center;flex-direction:column;background:linear-gradient(165deg,<?= $primaryColor ?> 0%,color-mix(in srgb,<?= $primaryColor ?> 35%,#0a0a0a) 55%,#0a0a0a 100%);opacity:1;overflow:hidden">
    <!-- Atom orbits -->
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:280px;height:280px;pointer-events:none">
        <div style="position:absolute;width:300px;height:110px;top:calc(50% - 55px);left:calc(50% - 150px);border:1.5px solid rgba(255,255,255,0.1);border-radius:50%;animation:cinSpin 7s linear infinite"><div style="position:absolute;width:6px;height:6px;background:#fff;border-radius:50%;top:-3px;left:calc(50% - 3px);box-shadow:0 0 12px rgba(255,255,255,0.7)"></div></div>
        <div style="position:absolute;width:260px;height:90px;top:calc(50% - 45px);left:calc(50% - 130px);border:1.5px solid rgba(255,255,255,0.08);border-radius:50%;animation:cinSpin 5s linear infinite reverse;transform:rotate(55deg)"><div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2.5px;left:calc(50% - 2.5px);box-shadow:0 0 10px rgba(255,255,255,0.6)"></div></div>
        <div style="position:absolute;width:80px;height:250px;top:calc(50% - 125px);left:calc(50% - 40px);border:1.5px solid rgba(255,255,255,0.06);border-radius:50%;animation:cinSpin 9s linear infinite;transform:rotate(25deg)"><div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2.5px;left:calc(50% - 2.5px);box-shadow:0 0 10px rgba(255,255,255,0.5)"></div></div>
    </div>
    <!-- Pulse rings -->
    <div style="position:absolute;top:50%;left:50%;width:100px;height:100px;margin:-50px 0 0 -50px;border-radius:50%;border:2px solid rgba(255,255,255,0.2);animation:tbPulse 2.4s ease-out infinite;pointer-events:none"></div>
    <div style="position:absolute;top:50%;left:50%;width:100px;height:100px;margin:-50px 0 0 -50px;border-radius:50%;border:2px solid rgba(255,255,255,0.2);animation:tbPulse 2.4s ease-out infinite 0.8s;pointer-events:none"></div>
    <!-- Center content -->
    <div style="position:relative;z-index:10;text-align:center">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.1);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;animation:tbIconIn 0.5s cubic-bezier(0.34,1.56,0.64,1) 0.2s both">
            <svg width="28" height="28" viewBox="0 0 448 512" fill="#fff"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></svg>
        </div>
        <div style="font-size:20px;font-weight:700;color:#fff;margin-bottom:6px;opacity:0;animation:tbTextIn 0.4s ease 0.5s forwards">Password Reset Required</div>
        <div style="font-size:14px;color:rgba(255,255,255,0.5);opacity:0;animation:tbTextIn 0.4s ease 0.7s forwards" id="pwIntroSub">Securing your account...</div>
    </div>
</div>

<!-- Phase 2: Password form lightbox -->
<div id="pwChangeOverlay" style="position:fixed;inset:0;z-index:99991;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);display:none;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border-radius:24px;max-width:420px;width:92%;padding:36px;box-shadow:0 24px 80px rgba(0,0,0,0.4);animation:wizSlideUp 0.5s cubic-bezier(0.34,1.56,0.64,1)">
        <div style="text-align:center;margin-bottom:24px">
            <div style="width:56px;height:56px;border-radius:50%;background:<?= $primaryColor ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                <svg width="22" height="22" viewBox="0 0 448 512" fill="#fff"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></svg>
            </div>
            <h3 style="font-size:18px;font-weight:700;color:var(--text);margin-bottom:4px">Set Your New Password</h3>
            <p style="font-size:13px;color:var(--text-muted)">Choose a strong password to secure your account</p>
        </div>
        <div class="form-group">
            <label class="form-label" for="pw_new">New Password</label>
            <input type="password" id="pw_new" class="form-input" placeholder="Min 8 characters" minlength="8">
        </div>
        <div class="form-group">
            <label class="form-label" for="pw_confirm">Confirm Password</label>
            <input type="password" id="pw_confirm" class="form-input" placeholder="Re-enter password">
        </div>
        <button class="btn btn-primary w-full" id="pwChangeBtn" onclick="submitPasswordChange()" style="margin-top:8px;position:relative;overflow:hidden">
            <i class="fas fa-check"></i> Set New Password
            <span style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent);animation:alertShine 3s ease 1s infinite"></span>
        </button>
    </div>
</div>

<!-- Phase 3: Saving overlay -->
<div id="pwSavingOverlay" style="position:fixed;inset:0;z-index:99992;display:none;align-items:center;justify-content:center;flex-direction:column;background:linear-gradient(165deg,<?= $primaryColor ?> 0%,color-mix(in srgb,<?= $primaryColor ?> 35%,#0a0a0a) 55%,#0a0a0a 100%)">
    <div style="position:relative;z-index:10;text-align:center">
        <div style="width:48px;height:48px;border:2.5px solid rgba(255,255,255,0.12);border-top-color:rgba(255,255,255,0.7);border-radius:50%;animation:cinSpin 0.7s linear infinite;margin:0 auto 16px"></div>
        <div style="font-size:16px;font-weight:600;color:rgba(255,255,255,0.7)">Updating your password...</div>
        <div style="font-size:13px;color:rgba(255,255,255,0.4);margin-top:6px">One moment please</div>
    </div>
</div>

<!-- Phase 4: Success lightbox — branded gradient with atom -->
<div id="pwSuccessOverlay" style="position:fixed;inset:0;z-index:99993;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);backdrop-filter:blur(8px)">
    <div id="pwSuccessCard" style="background:linear-gradient(165deg,<?= $primaryColor ?> 0%,color-mix(in srgb,<?= $primaryColor ?> 35%,#0a0a0a) 55%,#0a0a0a 100%);border-radius:24px;max-width:400px;width:90%;padding:44px 36px;text-align:center;box-shadow:0 24px 80px rgba(0,0,0,0.5);position:relative;overflow:hidden;animation:wizSlideUp 0.5s cubic-bezier(0.34,1.56,0.64,1);transition:all 0.4s cubic-bezier(0.23,1,0.32,1)">
        <!-- Atom orbits behind checkmark -->
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);width:200px;height:200px;pointer-events:none">
            <div style="position:absolute;width:220px;height:80px;top:calc(50% - 40px);left:calc(50% - 110px);border:1.5px solid rgba(255,255,255,0.08);border-radius:50%;animation:cinSpin 6s linear infinite"><div style="position:absolute;width:5px;height:5px;background:#fff;border-radius:50%;top:-2.5px;left:calc(50% - 2.5px);box-shadow:0 0 10px rgba(255,255,255,0.6)"></div></div>
            <div style="position:absolute;width:190px;height:70px;top:calc(50% - 35px);left:calc(50% - 95px);border:1.5px solid rgba(255,255,255,0.06);border-radius:50%;animation:cinSpin 4.5s linear infinite reverse;transform:rotate(55deg)"><div style="position:absolute;width:4px;height:4px;background:#fff;border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 8px rgba(255,255,255,0.5)"></div></div>
            <div style="position:absolute;width:60px;height:180px;top:calc(50% - 90px);left:calc(50% - 30px);border:1.5px solid rgba(255,255,255,0.05);border-radius:50%;animation:cinSpin 8s linear infinite;transform:rotate(25deg)"><div style="position:absolute;width:4px;height:4px;background:#fff;border-radius:50%;top:-2px;left:calc(50% - 2px);box-shadow:0 0 8px rgba(255,255,255,0.4)"></div></div>
        </div>
        <!-- Pulse rings -->
        <div style="position:absolute;top:38%;left:50%;width:80px;height:80px;margin:-40px 0 0 -40px;border-radius:50%;border:2px solid rgba(255,255,255,0.15);animation:tbPulse 2.4s ease-out infinite;pointer-events:none"></div>
        <div style="position:absolute;top:38%;left:50%;width:80px;height:80px;margin:-40px 0 0 -40px;border-radius:50%;border:2px solid rgba(255,255,255,0.15);animation:tbPulse 2.4s ease-out infinite 0.8s;pointer-events:none"></div>
        <!-- Checkmark -->
        <div style="position:relative;z-index:10;width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 0 20px rgba(255,255,255,0.1)">
            <i class="fas fa-check" style="color:#fff;font-size:28px;filter:drop-shadow(0 0 8px rgba(255,255,255,0.5))"></i>
        </div>
        <h3 style="font-size:20px;font-weight:700;color:#fff;margin-bottom:8px;position:relative;z-index:10">Password Updated</h3>
        <p style="font-size:14px;color:rgba(255,255,255,0.55);line-height:1.6;margin-bottom:28px;position:relative;z-index:10">Your password has been successfully updated.<br>You can now close this window.</p>
        <button class="btn" onclick="dismissPwSuccess()" style="padding:12px 36px;position:relative;overflow:hidden;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);color:#fff;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;backdrop-filter:blur(4px);z-index:10;transition:all 0.2s ease" onmouseover="this.style.background='rgba(255,255,255,0.25)';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='rgba(255,255,255,0.15)';this.style.transform='translateY(0)'">
            OK
            <span style="position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);animation:alertShine 3s ease 0.5s infinite"></span>
        </button>
    </div>
</div>

<style>
@keyframes wizSlideUp { from { transform: translateY(40px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
@keyframes alertShine { 0%{left:-100%} 30%{left:100%} 100%{left:100%} }
@keyframes tbPulse { 0%{transform:scale(1);opacity:.5} 100%{transform:scale(3);opacity:0} }
@keyframes tbIconIn { to{opacity:1;transform:scale(1)} }
@keyframes tbTextIn { to{opacity:1} }
</style>
<script>
(function() {
    // Phase 1: Show intro for 2 seconds with cycling text
    var introSub = document.getElementById('pwIntroSub');
    var introMsgs = ['Securing your account...', 'Preparing password reset...', 'Almost ready...'];
    var iIdx = 0;
    var introTimer = setInterval(function() {
        iIdx++;
        if (iIdx < introMsgs.length) {
            introSub.style.opacity = '0';
            setTimeout(function() {
                introSub.textContent = introMsgs[iIdx];
                introSub.style.opacity = '1';
            }, 200);
        }
    }, 700);

    // After 2s, fade intro and show form
    setTimeout(function() {
        clearInterval(introTimer);
        var intro = document.getElementById('pwIntroOverlay');
        intro.style.transition = 'opacity 0.4s ease';
        intro.style.opacity = '0';
        setTimeout(function() {
            intro.style.display = 'none';
            var form = document.getElementById('pwChangeOverlay');
            form.style.display = 'flex';
        }, 400);
    }, 2000);
})();

function submitPasswordChange() {
    var newPw = document.getElementById('pw_new').value;
    var confirmPw = document.getElementById('pw_confirm').value;
    if (newPw.length < 8) { showToast('Password must be at least 8 characters', 'warning'); return; }
    if (newPw !== confirmPw) { showToast('Passwords do not match', 'warning'); return; }

    // Show saving overlay
    document.getElementById('pwChangeOverlay').style.display = 'none';
    var saving = document.getElementById('pwSavingOverlay');
    saving.style.display = 'flex';

    fetch('<?= BASE_URL ?>/auth/change-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ new_password: newPw, confirm_password: confirmPw, csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            // Show saving for 1.5s, then show success
            setTimeout(function() {
                saving.style.display = 'none';
                document.getElementById('pwSuccessOverlay').style.display = 'flex';
            }, 1500);
        } else {
            saving.style.display = 'none';
            document.getElementById('pwChangeOverlay').style.display = 'flex';
            showToast(data.error || 'Failed to update password', 'error');
        }
    })
    .catch(function() {
        saving.style.display = 'none';
        document.getElementById('pwChangeOverlay').style.display = 'flex';
        showToast('Network error', 'error');
    });
}

function dismissPwSuccess() {
    var card = document.getElementById('pwSuccessCard');
    var overlay = document.getElementById('pwSuccessOverlay');
    card.style.transform = 'scale(0.9)';
    card.style.opacity = '0';
    overlay.style.transition = 'opacity 0.3s ease';
    overlay.style.opacity = '0';
    setTimeout(function() { overlay.style.display = 'none'; }, 350);
}
</script>
<?php endif; ?>

<!-- Onboarding Tour (always loaded for resume/restart capability) -->
<?php if (empty($_SESSION['must_change_password'])): ?>
<?php include APP_ROOT . '/app/views/components/tour.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($_SESSION['needs_tour'])): ?>
    // First-time user — auto-start after delay
    if (localStorage.getItem('tourActive') !== '1') {
        setTimeout(startTour, 800);
    }
    <?php endif; ?>
});

function restartTour() {
    if (typeof startTourDirect === 'function') {
        startTourDirect();
    }
}
</script>
<?php else: ?>
<script>function restartTour() { if (typeof showToast === 'function') showToast('Please change your password first.', 'info'); }</script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/js/app.js"></script>
<script>
// Animate stat numbers counting up on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.stat-value').forEach(function(el, index) {
        var target = parseInt(el.textContent.replace(/[^0-9]/g, ''));
        if (isNaN(target) || target === 0) return;
        var suffix = el.textContent.replace(/[0-9,]/g, '').trim();
        var duration = 1500; // 1.5 seconds for the count-up
        el.textContent = '0' + suffix;

        // Stagger start: each card waits a bit longer
        var startDelay = 600 + (index * 200);

        setTimeout(function() {
            var start = performance.now();
            function tick(now) {
                var elapsed = now - start;
                var progress = Math.min(elapsed / duration, 1);
                // Ease-out quart for a satisfying deceleration
                var eased = 1 - Math.pow(1 - progress, 4);
                var current = Math.round(target * eased);
                el.textContent = current.toLocaleString() + suffix;
                if (progress < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        }, startDelay);
    });
});

// ---- Global Generation Status Tracking ----
// Global image generation tracker — polls server for image job status
var GenTracker = {
    pill: null,
    pillText: null,
    pollInterval: null,
    completedUids: [],

    init: function() {
        this.pill = document.getElementById('genStatusPill');
        this.pillText = document.getElementById('genStatusText');

        // Check if there are active image jobs in sessionStorage
        var jobs = this.getActiveJobs();
        if (Object.keys(jobs).length > 0) {
            this.showPill();
            this.startPolling();
        }
    },

    getActiveJobs: function() {
        try { return JSON.parse(sessionStorage.getItem('gen_image_jobs') || '{}'); } catch(e) { return {}; }
    },

    showPill: function() {
        if (!this.pill) return;
        var jobs = this.getActiveJobs();
        var count = Object.keys(jobs).length;
        if (count === 0 && this.completedUids.length === 0) {
            this.pill.style.display = 'none';
            return;
        }

        this.pill.style.display = '';

        if (count > 0) {
            // Still generating
            var icon = this.pill.querySelector('i');
            icon.className = 'fas fa-spinner fa-spin';
            icon.style.marginRight = '5px';
            this.pillText.textContent = count > 1 ? count + ' images generating...' : '1 image generating...';
            this.pill.style.background = '';
            this.pill.style.animation = '';
            this.pill.style.boxShadow = '';
        } else if (this.completedUids.length > 0) {
            // All done — show "View Posts" button
            var icon = this.pill.querySelector('i');
            icon.className = 'fas fa-check-circle';
            var n = this.completedUids.length;
            this.pillText.textContent = n + ' image' + (n > 1 ? 's' : '') + ' ready — View Posts';
            this.pill.style.animation = 'none';
            this.pill.style.boxShadow = '0 0 12px rgba(34,197,94,0.4)';
            this.pill.style.background = 'var(--success)';

            // Auto-hide after 10 seconds
            var self = this;
            setTimeout(function() {
                self.completedUids = [];
                self.pill.style.display = 'none';
                self.pill.style.animation = '';
                self.pill.style.boxShadow = '';
                self.pill.style.background = '';
            }, 10000);
        }
    },

    startPolling: function() {
        if (this.pollInterval) return;
        var self = this;
        this.pollInterval = setInterval(function() {
            var jobs = self.getActiveJobs();
            if (Object.keys(jobs).length === 0) {
                clearInterval(self.pollInterval);
                self.pollInterval = null;
                self.showPill();
                return;
            }

            // Poll server for job status
            fetch('<?= BASE_URL ?>/generator/check-image-jobs')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var serverJobs = data.jobs || [];
                var activeJobs = self.getActiveJobs();
                var changed = false;

                serverJobs.forEach(function(sj) {
                    if (activeJobs[sj.uid]) {
                        if (sj.status === 'completed') {
                            self.completedUids.push(sj.uid);
                            delete activeJobs[sj.uid];
                            changed = true;

                            // Update sessionStorage gen_posts with the image URL
                            try {
                                var posts = JSON.parse(sessionStorage.getItem('gen_posts') || '[]');
                                posts.forEach(function(p) {
                                    if (p.uid === sj.uid) p.image_url = sj.image_url;
                                });
                                sessionStorage.setItem('gen_posts', JSON.stringify(posts));
                            } catch(e) {}
                        } else if (sj.status === 'failed') {
                            delete activeJobs[sj.uid];
                            changed = true;
                        }
                    }
                });

                if (changed) {
                    sessionStorage.setItem('gen_image_jobs', JSON.stringify(activeJobs));
                    self.showPill();
                }
            })
            .catch(function() {});
        }, 7000); // Poll every 7 seconds
    }
};

window.updateGenPill = function() {
    GenTracker.showPill();
    var jobs = GenTracker.getActiveJobs();
    if (Object.keys(jobs).length > 0 && !GenTracker.pollInterval) {
        GenTracker.startPolling();
    }
};

function onGenPillClick() {
    if (GenTracker.completedUids.length > 0) {
        // Navigate to generator to see the posts
        window.location.href = '<?= BASE_URL ?>/generator';
    }
}

document.addEventListener('DOMContentLoaded', function() { GenTracker.init(); });
</script>

<!-- Keyboard Shortcuts -->
<div id="kbShortcutsModal" style="display:none;position:fixed;inset:0;z-index:99980;background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);align-items:center;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:rgba(15,23,42,0.95);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-radius:16px;max-width:380px;width:92%;padding:28px 32px;box-shadow:0 24px 80px rgba(0,0,0,0.5);animation:kbModalIn 0.2s ease">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
            <h3 style="font-size:16px;font-weight:700;color:#f1f5f9;margin:0">Keyboard Shortcuts</h3>
            <button onclick="document.getElementById('kbShortcutsModal').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:18px;cursor:pointer;padding:0;line-height:1">&times;</button>
        </div>
        <div style="display:grid;grid-template-columns:auto 1fr;gap:8px 16px;font-size:13px">
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">N</kbd>
            <span style="color:#cbd5e1;line-height:28px">New post (Generator)</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">G</kbd>
            <span style="color:#cbd5e1;line-height:28px">Generator</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">P</kbd>
            <span style="color:#cbd5e1;line-height:28px">Posts</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">C</kbd>
            <span style="color:#cbd5e1;line-height:28px">Calendar</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">R</kbd>
            <span style="color:#cbd5e1;line-height:28px">Reports</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">/</kbd>
            <span style="color:#cbd5e1;line-height:28px">Focus search</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">?</kbd>
            <span style="color:#cbd5e1;line-height:28px">Show this help</span>
            <kbd style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:6px;padding:3px 10px;font-family:inherit;font-size:12px;font-weight:600;color:#e2e8f0;text-align:center;min-width:28px">Esc</kbd>
            <span style="color:#cbd5e1;line-height:28px">Dismiss modal</span>
        </div>
    </div>
</div>
<style>
@keyframes kbModalIn { from { transform: scale(0.92); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
<script>
(function() {
    var BASE = '<?= BASE_URL ?>';
    document.addEventListener('keydown', function(e) {
        // Ignore when typing in inputs
        var tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) {
            // Only handle Escape inside inputs (to dismiss modal)
            if (e.key === 'Escape') {
                var modal = document.getElementById('kbShortcutsModal');
                if (modal.style.display === 'flex') { modal.style.display = 'none'; }
            }
            return;
        }
        // Skip if modifier keys are held (Ctrl, Alt, Meta) — allow Shift for ?
        if (e.ctrlKey || e.altKey || e.metaKey) return;

        var modal = document.getElementById('kbShortcutsModal');

        switch (e.key) {
            case 'n':
            case 'N':
            case 'g':
            case 'G':
                e.preventDefault();
                window.location.href = BASE + '/generator';
                break;
            case 'p':
            case 'P':
                e.preventDefault();
                window.location.href = BASE + '/posts';
                break;
            case 'c':
            case 'C':
                e.preventDefault();
                window.location.href = BASE + '/calendar';
                break;
            case 'r':
            case 'R':
                e.preventDefault();
                window.location.href = BASE + '/reporting';
                break;
            case '/':
                e.preventDefault();
                var searchEl = document.querySelector('#filter-search') ||
                               document.querySelector('.form-input[placeholder*="Search"]');
                if (searchEl) { searchEl.focus(); searchEl.select(); }
                break;
            case '?':
                e.preventDefault();
                modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
                break;
            case 'Escape':
                if (modal.style.display === 'flex') { modal.style.display = 'none'; }
                break;
        }
    });
})();
</script>
<!-- ═══ EASTER EGG: Ctrl+Shift+J ═══ -->
<div id="ee" style="display:none">
<div id="eeBlack" style="position:fixed;inset:0;z-index:999999;background:#000;opacity:0;transition:opacity 1s ease;pointer-events:all"></div>
<canvas id="eeCanvas" style="position:fixed;inset:0;z-index:1000000;pointer-events:none"></canvas>
<div id="eeText" style="position:fixed;inset:0;z-index:1000001;display:flex;align-items:center;justify-content:center;flex-direction:column;pointer-events:none;opacity:0;padding:16px;overflow:hidden">
    <div id="eeSubtitle" style="font-size:clamp(10px,3vw,14px);font-weight:300;letter-spacing:0.2em;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:12px;font-family:monospace;text-align:center;max-width:90vw"></div>
    <div id="eeName" style="font-size:0px;font-weight:900;color:#fff;letter-spacing:-2px;text-shadow:0 0 40px rgba(255,255,255,0.5);transition:all 0.8s cubic-bezier(0.34,1.56,0.64,1);filter:blur(20px);font-family:'Inter',sans-serif;padding:0 16px;word-break:break-word;max-width:100vw"></div>
    <div id="ee333" style="font-size:120px;font-weight:900;color:rgba(255,255,255,0.03);position:absolute;font-family:monospace;pointer-events:none"></div>
</div>
<!-- Matrix rain layer -->
<canvas id="eeMatrix" style="position:fixed;inset:0;z-index:999999;pointer-events:none;opacity:0;transition:opacity 0.5s ease"></canvas>
<!-- Contact lightbox with matrix background -->
<div id="eeLightbox" style="position:fixed;inset:0;z-index:1000002;display:none;align-items:center;justify-content:center;background:#000">
    <canvas id="eeLbMatrix" style="position:absolute;inset:0;z-index:0"></canvas>
    <div id="eeLbCard" style="background:linear-gradient(165deg,#1a1a2e 0%,#0a0a0a 100%);border:1px solid rgba(255,255,255,0.08);border-radius:24px;max-width:420px;width:92%;padding:clamp(24px,5vw,40px);text-align:center;position:relative;box-shadow:0 32px 80px rgba(0,0,0,0.6);overflow:hidden;z-index:5">
        <button onclick="closeEE()" style="position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.5);font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:10;transition:all 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">&times;</button>
        <div id="eeLbParticles" style="position:absolute;inset:0;overflow:hidden;pointer-events:none"></div>
        <div style="position:relative;z-index:5">
            <div style="font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:rgba(255,255,255,0.3);margin-bottom:16px">Developed By</div>
            <div style="font-size:32px;font-weight:900;color:#fff;margin-bottom:4px;text-shadow:0 0 20px rgba(255,255,255,0.15)">Jason Hogan</div>
            <div style="font-size:12px;color:rgba(255,255,255,0.25);margin-bottom:24px;font-family:monospace">// Full-Stack Engineer & AI Architect</div>
            <div style="display:flex;flex-direction:column;gap:10px;align-items:center;margin-bottom:20px">
                <a href="tel:+15879837066" style="display:flex;align-items:center;gap:10px;padding:12px 24px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#fff;text-decoration:none;font-size:14px;font-weight:500;width:100%;max-width:280px;transition:all 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.12)';this.style.borderColor='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.borderColor='rgba(255,255,255,0.1)'">
                    <i class="fas fa-phone" style="font-size:16px;color:rgba(255,255,255,0.5);width:20px;text-align:center"></i>
                    587-983-7066
                </a>
            </div>
            <!-- Email capture -->
            <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:18px">
                <div style="font-size:11px;color:rgba(255,255,255,0.3);margin-bottom:10px;font-family:monospace">// Want to know more? Enter your email:</div>
                <div style="display:flex;gap:8px;max-width:300px;margin:0 auto" id="eeEmailWrap">
                    <input type="email" id="eeEmailInput" placeholder="your@email.com" style="flex:1;padding:10px 14px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:8px;color:#fff;font-size:13px;font-family:inherit;outline:none;transition:border-color 0.2s" onfocus="this.style.borderColor='rgba(100,150,255,0.4)'" onblur="this.style.borderColor='rgba(255,255,255,0.12)'">
                    <button onclick="sendEasterEggEmail()" id="eeSendBtn" style="padding:10px 16px;background:rgba(26,58,107,0.6);border:1px solid rgba(26,58,107,0.8);border-radius:8px;color:#fff;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 0.2s;font-family:inherit" onmouseover="this.style.background='rgba(26,58,107,0.9)'" onmouseout="this.style.background='rgba(26,58,107,0.6)'">Send</button>
                </div>
                <div id="eeEmailStatus" style="font-size:11px;color:rgba(255,255,255,0.2);margin-top:8px;font-family:monospace;min-height:16px"></div>
            </div>
            <div style="margin-top:16px;font-size:10px;color:rgba(255,255,255,0.12);font-family:monospace">v2.0 // Built with Claude AI // <?= date('Y') ?></div>
        </div>
    </div>
</div>
<!-- Subliminal flash -->
<div id="eeSubliminal" style="position:fixed;inset:0;z-index:1000003;display:none;align-items:center;justify-content:center;background:#000;pointer-events:all">
    <div id="eeSubliminalText" style="font-size:clamp(28px,8vw,48px);font-weight:900;color:#fff;text-align:center;font-family:'Inter',sans-serif;padding:0 16px"></div>
</div>
<!-- Technical difficulties -->
<div id="eeTechDiff" style="position:fixed;inset:0;z-index:1000003;display:none;align-items:center;justify-content:center;flex-direction:column;background:#0a0a0a;font-family:monospace;pointer-events:all">
    <div style="font-size:64px;margin-bottom:20px">&#x26A0;</div>
    <div style="font-size:18px;font-weight:700;color:#ef4444;margin-bottom:8px">FATAL ERROR</div>
    <div style="font-size:13px;color:#64748b;max-width:400px;text-align:center;line-height:1.8">
        <span style="color:#ef4444">Segmentation fault</span> (core dumped)<br>
        <span style="color:#f59e0b">Warning:</span> Cannot allocate memory in <span style="color:#94a3b8">/app/core/brain.php</span> on line <span style="color:#fff">333</span><br>
        <span style="color:#ef4444">Stack trace:</span> 0x7f3a2b... → awesomeness_overflow()
    </div>
    <div style="margin-top:16px;font-size:11px;color:#334155">Please contact your system administrator. Error code: JH-333</div>
</div>
</div>

<script>
(function(){
var eeActive = false;
// Desktop: Ctrl+Shift+J
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'J') {
        e.preventDefault();
        if (eeActive) return;
        eeActive = true;
        launchEasterEgg();
    }
});
// Mobile: Triple-tap on the page title in the topbar
var eeTapCount = 0, eeTapTimer = null;
document.addEventListener('DOMContentLoaded', function() {
    var title = document.querySelector('.topbar-title');
    if (title) {
        title.addEventListener('click', function() {
            eeTapCount++;
            if (eeTapCount === 1) {
                eeTapTimer = setTimeout(function() { eeTapCount = 0; }, 800);
            }
            if (eeTapCount >= 3) {
                clearTimeout(eeTapTimer);
                eeTapCount = 0;
                if (!eeActive) { eeActive = true; launchEasterEgg(); }
            }
        });
    }
});

function launchEasterEgg() {
    var container = document.getElementById('ee');
    container.style.display = 'block';
    var black = document.getElementById('eeBlack');
    var canvas = document.getElementById('eeCanvas');
    var ctx = canvas.getContext('2d');
    var matrixCanvas = document.getElementById('eeMatrix');
    var mctx = matrixCanvas.getContext('2d');

    canvas.width = matrixCanvas.width = window.innerWidth;
    canvas.height = matrixCanvas.height = window.innerHeight;

    // ═══ SOUND: Cinematic impact ═══
    try {
        var audio = new (window.AudioContext || window.webkitAudioContext)();
        // Deep bass hit
        var osc1 = audio.createOscillator();
        var gain1 = audio.createGain();
        osc1.type = 'sine'; osc1.frequency.setValueAtTime(40, audio.currentTime);
        osc1.frequency.exponentialRampToValueAtTime(20, audio.currentTime + 1.5);
        gain1.gain.setValueAtTime(0.8, audio.currentTime);
        gain1.gain.exponentialRampToValueAtTime(0.01, audio.currentTime + 2);
        osc1.connect(gain1); gain1.connect(audio.destination);
        osc1.start(); osc1.stop(audio.currentTime + 2);
        // Rising synth sweep
        var osc2 = audio.createOscillator();
        var gain2 = audio.createGain();
        osc2.type = 'sawtooth'; osc2.frequency.setValueAtTime(80, audio.currentTime + 0.3);
        osc2.frequency.exponentialRampToValueAtTime(800, audio.currentTime + 2);
        gain2.gain.setValueAtTime(0.15, audio.currentTime + 0.3);
        gain2.gain.exponentialRampToValueAtTime(0.01, audio.currentTime + 2.5);
        osc2.connect(gain2); gain2.connect(audio.destination);
        osc2.start(audio.currentTime + 0.3); osc2.stop(audio.currentTime + 2.5);
        // High freq sparkle
        var osc3 = audio.createOscillator();
        var gain3 = audio.createGain();
        osc3.type = 'sine'; osc3.frequency.setValueAtTime(2000, audio.currentTime + 1);
        gain3.gain.setValueAtTime(0.1, audio.currentTime + 1);
        gain3.gain.exponentialRampToValueAtTime(0.01, audio.currentTime + 1.5);
        osc3.connect(gain3); gain3.connect(audio.destination);
        osc3.start(audio.currentTime + 1); osc3.stop(audio.currentTime + 1.5);
    } catch(e) {}

    // Phase 1: Fade to black
    requestAnimationFrame(function() { black.style.opacity = '1'; });

    // Phase 2: Matrix rain starts
    setTimeout(function() {
        matrixCanvas.style.opacity = '0.6';
        var cols = Math.floor(canvas.width / 14);
        var drops = new Array(cols).fill(0);
        var chars = 'JASONHOGAN333アイウエオカキクケコ01█▓░</>{}=+*#@!?$%^&'.split('');
        var matrixTimer = setInterval(function() {
            mctx.fillStyle = 'rgba(0,0,0,0.05)';
            mctx.fillRect(0, 0, matrixCanvas.width, matrixCanvas.height);
            mctx.fillStyle = '#0f0';
            mctx.font = '14px monospace';
            for (var i = 0; i < drops.length; i++) {
                var ch = chars[Math.floor(Math.random() * chars.length)];
                // Mix in red and gold occasionally
                mctx.fillStyle = Math.random() > 0.95 ? '#f00' : Math.random() > 0.9 ? '#ff0' : '#0f0';
                mctx.fillText(ch, i * 14, drops[i] * 14);
                if (drops[i] * 14 > matrixCanvas.height && Math.random() > 0.975) drops[i] = 0;
                drops[i]++;
            }
        }, 33);
        setTimeout(function() { clearInterval(matrixTimer); }, 8000);
    }, 1200);

    // Phase 3: Subtitle text types in
    setTimeout(function() {
        var textEl = document.getElementById('eeText');
        textEl.style.opacity = '1';
        var sub = document.getElementById('eeSubtitle');
        var subText = '// THIS APPLICATION WAS DEVELOPED BY //';
        var si = 0;
        var typeTimer = setInterval(function() {
            si++;
            sub.textContent = subText.substring(0, si);
            if (si >= subText.length) clearInterval(typeTimer);
        }, 40);

        // 333 subliminal
        var s333 = document.getElementById('ee333');
        s333.textContent = '333';
        s333.style.opacity = '0.04';
    }, 2000);

    // Phase 4: Name EXPLODES in
    setTimeout(function() {
        var name = document.getElementById('eeName');
        name.textContent = 'JASON HOGAN';
        name.style.fontSize = window.innerWidth < 500 ? '36px' : '72px';
        name.style.letterSpacing = window.innerWidth < 500 ? '-1px' : '-2px';
        name.style.filter = 'blur(0px)';
        name.style.textShadow = '0 0 80px rgba(255,255,255,0.8), 0 0 160px rgba(255,100,100,0.4)';

        // Flash the screen white
        ctx.fillStyle = 'rgba(255,255,255,0.3)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        setTimeout(function() { ctx.clearRect(0, 0, canvas.width, canvas.height); }, 100);
    }, 3500);

    // Phase 5: PARTICLE EXPLOSION
    setTimeout(function() {
        var particles = [];
        var cx = canvas.width / 2, cy = canvas.height / 2;
        for (var i = 0; i < 300; i++) {
            var angle = Math.random() * Math.PI * 2;
            var speed = 2 + Math.random() * 12;
            var size = 1 + Math.random() * 4;
            var colors = ['#fff','#ff4444','#ffaa00','#ff6600','#ffffff','#ff0000','#ffd700'];
            particles.push({
                x: cx, y: cy,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                size: size,
                color: colors[Math.floor(Math.random() * colors.length)],
                life: 1,
                decay: 0.005 + Math.random() * 0.015
            });
        }
        // Shockwave
        var shockwave = { r: 0, maxR: Math.max(canvas.width, canvas.height) * 0.7, alpha: 0.6 };

        function drawExplosion() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            // Shockwave ring
            if (shockwave.r < shockwave.maxR) {
                ctx.beginPath();
                ctx.arc(cx, cy, shockwave.r, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(255,255,255,' + shockwave.alpha + ')';
                ctx.lineWidth = 3;
                ctx.stroke();
                shockwave.r += 15;
                shockwave.alpha *= 0.97;
            }
            // Particles
            var alive = false;
            particles.forEach(function(p) {
                if (p.life <= 0) return;
                alive = true;
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.05; // gravity
                p.life -= p.decay;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
                ctx.fillStyle = p.color;
                ctx.globalAlpha = p.life;
                ctx.fill();
                ctx.globalAlpha = 1;
            });
            if (alive) requestAnimationFrame(drawExplosion);
        }
        drawExplosion();

        // Subliminal flash: "333" big
        var s333 = document.getElementById('ee333');
        s333.style.fontSize = '200px';
        s333.style.color = 'rgba(255,0,0,0.08)';
        setTimeout(function() { s333.style.color = 'rgba(255,255,255,0.02)'; }, 300);
    }, 4000);

    // Phase 6: Text fades, second blackout
    setTimeout(function() {
        document.getElementById('eeText').style.opacity = '0';
        document.getElementById('eeText').style.transition = 'opacity 0.8s ease';
        matrixCanvas.style.opacity = '0';
    }, 6500);

    // Phase 7: Show contact lightbox with matrix background
    setTimeout(function() {
        canvas.style.display = 'none';
        matrixCanvas.style.display = 'none';
        document.getElementById('eeText').style.display = 'none';

        var lb = document.getElementById('eeLightbox');
        lb.style.display = 'flex';
        lb.style.opacity = '0';
        lb.style.transition = 'opacity 0.5s ease';
        requestAnimationFrame(function() { lb.style.opacity = '1'; });

        // Start matrix rain on lightbox background
        var lbMatrix = document.getElementById('eeLbMatrix');
        lbMatrix.width = window.innerWidth;
        lbMatrix.height = window.innerHeight;
        var lbMctx = lbMatrix.getContext('2d');
        var lbCols = Math.floor(lbMatrix.width / 14);
        var lbDrops = new Array(lbCols).fill(0);
        var lbChars = 'JASONHOGAN33301ABCDEF<>{}[]@#$%*+='.split('');
        window._eeLbMatrixTimer = setInterval(function() {
            lbMctx.fillStyle = 'rgba(0,0,0,0.06)';
            lbMctx.fillRect(0, 0, lbMatrix.width, lbMatrix.height);
            lbMctx.font = '14px monospace';
            for (var i = 0; i < lbDrops.length; i++) {
                var ch = lbChars[Math.floor(Math.random() * lbChars.length)];
                lbMctx.fillStyle = Math.random() > 0.92 ? '#4488ff' : Math.random() > 0.85 ? '#2266cc' : '#0f0';
                lbMctx.fillText(ch, i * 14, lbDrops[i] * 14);
                if (lbDrops[i] * 14 > lbMatrix.height && Math.random() > 0.975) lbDrops[i] = 0;
                lbDrops[i]++;
            }
        }, 40);

        // Generate particles for card
        var pc = document.getElementById('eeLbParticles');
        pc.innerHTML = '';
        for (var i = 0; i < 20; i++) {
            var s = document.createElement('span');
            var sz = 1 + Math.random() * 3;
            s.style.cssText = 'position:absolute;width:'+sz+'px;height:'+sz+'px;border-radius:50%;background:rgba(255,255,255,0.3);opacity:0;left:'+(Math.random()*100)+'%;animation:cinFloat '+(2+Math.random()*3)+'s ease-in-out infinite;animation-delay:-'+(Math.random()*4)+'s';
            pc.appendChild(s);
        }

        // Reset email input
        document.getElementById('eeEmailInput').value = '';
        document.getElementById('eeEmailStatus').textContent = '';
    }, 7500);
}

window.sendEasterEggEmail = function() {
    var email = document.getElementById('eeEmailInput').value.trim();
    var status = document.getElementById('eeEmailStatus');
    var btn = document.getElementById('eeSendBtn');
    if (!email || email.indexOf('@') < 0) {
        status.textContent = '// invalid email address';
        status.style.color = 'rgba(255,100,100,0.5)';
        return;
    }
    btn.disabled = true;
    btn.textContent = 'Sending...';
    status.textContent = '// transmitting...';
    status.style.color = 'rgba(100,200,255,0.4)';

    fetch('<?= BASE_URL ?>/easter-egg-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.textContent = 'Sent!';
        btn.style.background = 'rgba(34,197,94,0.4)';
        status.textContent = '// profile sent to ' + email;
        status.style.color = 'rgba(100,255,150,0.4)';

        // Retro 8-bit sound effect + laugh
        try {
            var a = new (window.AudioContext || window.webkitAudioContext)();
            // 8-bit coin sound
            var o1 = a.createOscillator(); var g1 = a.createGain();
            o1.type = 'square'; o1.frequency.setValueAtTime(988, a.currentTime);
            o1.frequency.setValueAtTime(1319, a.currentTime + 0.08);
            g1.gain.setValueAtTime(0.3, a.currentTime);
            g1.gain.exponentialRampToValueAtTime(0.01, a.currentTime + 0.3);
            o1.connect(g1); g1.connect(a.destination);
            o1.start(); o1.stop(a.currentTime + 0.3);
            // Rising "haha" chirp
            var o2 = a.createOscillator(); var g2 = a.createGain();
            o2.type = 'sawtooth';
            o2.frequency.setValueAtTime(300, a.currentTime + 0.35);
            o2.frequency.exponentialRampToValueAtTime(600, a.currentTime + 0.5);
            o2.frequency.exponentialRampToValueAtTime(400, a.currentTime + 0.6);
            o2.frequency.exponentialRampToValueAtTime(800, a.currentTime + 0.75);
            g2.gain.setValueAtTime(0.15, a.currentTime + 0.35);
            g2.gain.exponentialRampToValueAtTime(0.01, a.currentTime + 0.9);
            o2.connect(g2); g2.connect(a.destination);
            o2.start(a.currentTime + 0.35); o2.stop(a.currentTime + 0.9);
        } catch(e) {}

        // Lightning flash
        var flash = document.createElement('div');
        flash.style.cssText = 'position:fixed;inset:0;z-index:1000004;background:#fff;pointer-events:none;opacity:0.6';
        document.body.appendChild(flash);
        setTimeout(function() { flash.style.opacity = '0'; flash.style.transition = 'opacity 0.15s'; }, 50);
        setTimeout(function() { flash.style.opacity = '0.4'; }, 200);
        setTimeout(function() { flash.style.opacity = '0'; }, 250);
        setTimeout(function() { flash.remove(); }, 400);

        // After 1.5s, auto-close and transition to the finale
        setTimeout(function() { closeEE(); }, 1500);
    })
    .catch(function() {
        btn.textContent = 'Send';
        btn.disabled = false;
        status.textContent = '// transmission failed';
        status.style.color = 'rgba(255,100,100,0.5)';
    });
};

window.closeEE = function() {
    // Stop matrix rain
    if (window._eeLbMatrixTimer) { clearInterval(window._eeLbMatrixTimer); window._eeLbMatrixTimer = null; }
    var lb = document.getElementById('eeLightbox');
    lb.style.opacity = '0';

    setTimeout(function() {
        lb.style.display = 'none';

        // Subliminal flash: "JASON IS THE GREATEST DEV"
        var sub = document.getElementById('eeSubliminal');
        var subText = document.getElementById('eeSubliminalText');
        sub.style.display = 'flex';
        subText.textContent = 'JASON IS THE\nGREATEST DEV';
        subText.style.whiteSpace = 'pre-line';

        var flashCount = 0;
        var flashTimer = setInterval(function() {
            subText.style.opacity = subText.style.opacity === '0' ? '1' : '0';
            flashCount++;
            if (flashCount >= 6) {
                clearInterval(flashTimer);
                sub.style.display = 'none';

                // Technical difficulties screen
                var td = document.getElementById('eeTechDiff');
                td.style.display = 'flex';

                setTimeout(function() {
                    td.style.transition = 'opacity 0.5s ease';
                    td.style.opacity = '0';
                    setTimeout(function() {
                        // Clean up everything
                        td.style.display = 'none';
                        td.style.opacity = '1';
                        document.getElementById('eeBlack').style.opacity = '0';
                        document.getElementById('ee').style.display = 'none';

                        // Reset all elements for next trigger
                        document.getElementById('eeCanvas').style.display = '';
                        document.getElementById('eeMatrix').style.display = '';
                        document.getElementById('eeMatrix').style.opacity = '0';
                        document.getElementById('eeText').style.display = '';
                        document.getElementById('eeText').style.opacity = '0';
                        document.getElementById('eeText').style.transition = '';
                        document.getElementById('eeName').style.fontSize = '0px';
                        document.getElementById('eeName').style.filter = 'blur(20px)';
                        document.getElementById('eeName').style.textShadow = '';
                        document.getElementById('eeSubtitle').textContent = '';
                        document.getElementById('ee333').style.fontSize = '120px';
                        document.getElementById('ee333').textContent = '';
                        var canv = document.getElementById('eeCanvas');
                        canv.getContext('2d').clearRect(0, 0, canv.width, canv.height);
                        var mcanv = document.getElementById('eeMatrix');
                        mcanv.getContext('2d').clearRect(0, 0, mcanv.width, mcanv.height);
                        var lbMcanv = document.getElementById('eeLbMatrix');
                        if (lbMcanv) lbMcanv.getContext('2d').clearRect(0, 0, lbMcanv.width, lbMcanv.height);

                        eeActive = false;
                    }, 500);
                }, 3000);
            }
        }, 250);
    }, 500);
};
})();
</script>

<!-- Logout card particle effect —
     Mystical brand-color particle network that renders only on hover.
     Complements the top-left logo constellation in public/js/app.js. -->
<script>
(function() {
    var card = document.getElementById('sidebarUserCard');
    var canvas = document.getElementById('logoutParticles');
    if (!card || !canvas) return;

    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var nodes = [];
    var particles = [];
    var running = false;
    var fading = false;
    var rafId = null;
    var fadeAlpha = 0; // 0 = fully visible, 1 = fully faded out (canvas gets erased)
    var lastT = 0;

    // Read the brand color from CSS vars. Fallback to red if unavailable.
    function readBrand() {
        var cs = getComputedStyle(document.documentElement);
        var rgb = cs.getPropertyValue('--primary-rgb').trim();
        return rgb || '132, 30, 30';
    }
    var BRAND_RGB = readBrand();

    function resize() {
        var rect = card.getBoundingClientRect();
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(rect.height * dpr));
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function seed() {
        resize();
        var w = canvas.width / dpr;
        var h = canvas.height / dpr;
        nodes = [];
        particles = [];
        // Constellation nodes — slower drifting points with connecting lines
        for (var i = 0; i < 10; i++) {
            nodes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.22,
                vy: (Math.random() - 0.5) * 0.22,
                r: Math.random() * 1.4 + 0.6,
                phase: Math.random() * Math.PI * 2
            });
        }
        // Free-floating particles — smaller, wispy specks that drift upward
        for (var j = 0; j < 14; j++) {
            particles.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.18,
                vy: -(Math.random() * 0.22 + 0.05),
                r: Math.random() * 0.9 + 0.3,
                life: Math.random(),
                maxLife: 1 + Math.random() * 1.2
            });
        }
    }

    function step(t) {
        if (!running && !fading) { rafId = null; return; }
        if (!lastT) lastT = t;
        var dt = Math.min(0.05, (t - lastT) / 1000);
        lastT = t;

        var w = canvas.width / dpr;
        var h = canvas.height / dpr;

        // Fade handling: if fading out, increase fadeAlpha until 1 then stop
        if (fading) {
            fadeAlpha += dt * 1.8;
            if (fadeAlpha >= 1) {
                fading = false;
                ctx.clearRect(0, 0, w, h);
                rafId = null;
                lastT = 0;
                return;
            }
        } else {
            // Running: pull fadeAlpha toward 0 for a smooth re-entry
            if (fadeAlpha > 0) fadeAlpha = Math.max(0, fadeAlpha - dt * 3);
        }

        var visibility = 1 - fadeAlpha;

        ctx.clearRect(0, 0, w, h);

        // Update + draw nodes
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            n.x += n.vx;
            n.y += n.vy;
            n.phase += dt * 1.5;
            if (n.x < 0 || n.x > w) n.vx *= -1;
            if (n.y < 0 || n.y > h) n.vy *= -1;
            n.x = Math.max(0, Math.min(w, n.x));
            n.y = Math.max(0, Math.min(h, n.y));
        }

        // Draw connecting lines between near nodes — subtle brand tint
        var linkDist = 52;
        for (var i = 0; i < nodes.length; i++) {
            for (var j = i + 1; j < nodes.length; j++) {
                var dx = nodes[i].x - nodes[j].x;
                var dy = nodes[i].y - nodes[j].y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < linkDist) {
                    var alpha = (1 - dist / linkDist) * 0.32 * visibility;
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.strokeStyle = 'rgba(' + BRAND_RGB + ',' + alpha + ')';
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }
            }
        }

        // Draw node dots with a gentle pulse via sin(phase)
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            var pulse = 0.55 + 0.45 * (Math.sin(n.phase) * 0.5 + 0.5);
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r * pulse, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + BRAND_RGB + ',' + (0.85 * visibility * pulse) + ')';
            ctx.fill();
            // Soft halo
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r * 2.6, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + BRAND_RGB + ',' + (0.12 * visibility * pulse) + ')';
            ctx.fill();
        }

        // Update + draw free particles
        for (var p = 0; p < particles.length; p++) {
            var pt = particles[p];
            pt.x += pt.vx;
            pt.y += pt.vy;
            pt.life += dt;
            if (pt.life > pt.maxLife || pt.y < -5) {
                pt.x = Math.random() * w;
                pt.y = h + 3;
                pt.life = 0;
                pt.maxLife = 1 + Math.random() * 1.2;
            }
            var lifeFade = Math.sin((pt.life / pt.maxLife) * Math.PI); // ease in+out
            var a = lifeFade * 0.75 * visibility;
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, pt.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(' + BRAND_RGB + ',' + a + ')';
            ctx.fill();
        }

        rafId = requestAnimationFrame(step);
    }

    function start() {
        if (running) return;
        running = true;
        fading = false;
        seed();
        lastT = 0;
        if (!rafId) rafId = requestAnimationFrame(step);
    }
    function stop() {
        if (!running) return;
        running = false;
        fading = true; // let the loop fade out gracefully then stop itself
        if (!rafId) rafId = requestAnimationFrame(step);
    }

    card.addEventListener('mouseenter', start);
    card.addEventListener('mouseleave', stop);
    card.addEventListener('focusin', start);
    card.addEventListener('focusout', stop);

    // Re-seed if window resizes while hovered
    window.addEventListener('resize', function() {
        if (running) seed();
    });

    // Pause when tab is hidden to save CPU
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            running = false;
            fading = false;
            if (rafId) cancelAnimationFrame(rafId);
            rafId = null;
            ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        }
    });
})();
</script>

<!-- Custom Dropdown Enhancer -->
<script>
/**
 * Custom dropdown enhancer — replaces native <select class="form-select">
 * with a custom button + panel so hover/selection can be painted in brand
 * colors without the "two colors at once" glitch caused by fighting native
 * <option> rendering. The underlying <select> stays in the DOM so form
 * submission, .value reads, and onchange handlers all work unchanged.
 * On mobile (<=640px) we defer to native selects via CSS.
 */
(function() {
    if (window.__cddAttached) return;
    window.__cddAttached = true;

    var openPanel = null;

    function closeOpen() {
        if (openPanel) {
            openPanel.classList.remove('open');
            openPanel = null;
        }
    }

    function enhance(sel) {
        if (sel.__cddDone) return;
        if (sel.multiple) return; // skip multi-selects
        sel.__cddDone = true;

        var wrap = document.createElement('div');
        wrap.className = 'cdd-wrap';
        // If the native select has a flex/min-width parent, honor it
        if (sel.style.minWidth) wrap.style.minWidth = sel.style.minWidth;

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cdd-btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');

        var panel = document.createElement('div');
        panel.className = 'cdd-panel';
        panel.setAttribute('role', 'listbox');

        // Move the native select inside the wrap and hide it (CSS)
        sel.parentNode.insertBefore(wrap, sel);
        sel.classList.add('cdd-native');
        wrap.appendChild(sel);
        wrap.appendChild(btn);
        wrap.appendChild(panel);

        function rebuildOptions() {
            panel.innerHTML = '';
            Array.prototype.forEach.call(sel.options, function(opt, idx) {
                var el = document.createElement('div');
                el.className = 'cdd-opt';
                el.setAttribute('role', 'option');
                el.setAttribute('data-value', opt.value);
                el.setAttribute('data-idx', String(idx));
                el.textContent = opt.textContent;
                if (opt.disabled) {
                    el.style.opacity = '0.5';
                    el.style.pointerEvents = 'none';
                }
                if (idx === sel.selectedIndex) el.classList.add('selected');
                el.addEventListener('mouseenter', function() {
                    panel.querySelectorAll('.cdd-opt.active').forEach(function(x) { x.classList.remove('active'); });
                    el.classList.add('active');
                });
                el.addEventListener('click', function(ev) {
                    ev.stopPropagation();
                    selectIdx(idx);
                    close();
                });
                panel.appendChild(el);
            });
            updateLabel();
        }

        function updateLabel() {
            var opt = sel.options[sel.selectedIndex];
            btn.textContent = opt ? opt.textContent : '';
        }

        function selectIdx(idx) {
            if (idx < 0 || idx >= sel.options.length) return;
            if (sel.selectedIndex === idx) {
                updateLabel();
                return;
            }
            sel.selectedIndex = idx;
            updateLabel();
            panel.querySelectorAll('.cdd-opt.selected').forEach(function(x) { x.classList.remove('selected'); });
            var newEl = panel.querySelector('.cdd-opt[data-idx="' + idx + '"]');
            if (newEl) newEl.classList.add('selected');
            // Fire change event so existing onchange handlers run
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            sel.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function positionPanel() {
            var rect = btn.getBoundingClientRect();
            var vh = window.innerHeight;
            panel.style.width = rect.width + 'px';
            // Prefer below, flip above if there's not enough room
            var spaceBelow = vh - rect.bottom - 12;
            var spaceAbove = rect.top - 12;
            var maxH = 280;
            if (spaceBelow >= Math.min(maxH, 160) || spaceBelow >= spaceAbove) {
                panel.style.top = (rect.bottom + 4) + 'px';
                panel.style.maxHeight = Math.min(maxH, Math.max(120, spaceBelow)) + 'px';
            } else {
                var h = Math.min(maxH, Math.max(120, spaceAbove));
                panel.style.top = (rect.top - 4 - h) + 'px';
                panel.style.maxHeight = h + 'px';
            }
            panel.style.left = rect.left + 'px';
        }
        function open() {
            if (openPanel === wrap) return;
            closeOpen();
            rebuildOptions(); // rebuild in case options changed dynamically
            wrap.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
            openPanel = wrap;
            positionPanel();
            // Highlight the currently-selected option as active
            var selEl = panel.querySelector('.cdd-opt.selected');
            if (selEl) {
                panel.querySelectorAll('.cdd-opt.active').forEach(function(x) { x.classList.remove('active'); });
                selEl.classList.add('active');
                selEl.scrollIntoView({ block: 'nearest' });
            }
        }
        function close() {
            wrap.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
            if (openPanel === wrap) openPanel = null;
        }
        function toggle() {
            if (wrap.classList.contains('open')) close(); else open();
        }

        btn.addEventListener('click', function(ev) {
            ev.stopPropagation();
            toggle();
        });
        btn.addEventListener('keydown', function(ev) {
            if (ev.key === 'ArrowDown' || ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault();
                if (!wrap.classList.contains('open')) open();
                else {
                    var active = panel.querySelector('.cdd-opt.active');
                    var next = active && active.nextElementSibling;
                    if (next) {
                        panel.querySelectorAll('.cdd-opt.active').forEach(function(x) { x.classList.remove('active'); });
                        next.classList.add('active');
                        next.scrollIntoView({ block: 'nearest' });
                    }
                }
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                if (!wrap.classList.contains('open')) { open(); return; }
                var active = panel.querySelector('.cdd-opt.active');
                var prev = active && active.previousElementSibling;
                if (prev) {
                    panel.querySelectorAll('.cdd-opt.active').forEach(function(x) { x.classList.remove('active'); });
                    prev.classList.add('active');
                    prev.scrollIntoView({ block: 'nearest' });
                }
            } else if (ev.key === 'Enter') {
                if (wrap.classList.contains('open')) {
                    var active = panel.querySelector('.cdd-opt.active');
                    if (active) {
                        selectIdx(parseInt(active.getAttribute('data-idx'), 10));
                        close();
                    }
                }
            } else if (ev.key === 'Escape') {
                close();
            }
        });

        // If the underlying <select> has its value changed programmatically
        // (e.g. filterPosts(), DOM manipulation), resync our label.
        var observer = new MutationObserver(function() { updateLabel(); });
        observer.observe(sel, { attributes: true, childList: true, subtree: true });

        rebuildOptions();
    }

    function scan(root) {
        (root || document).querySelectorAll('select.form-select:not(.cdd-native)').forEach(enhance);
    }

    // Close any open panel on outside click, Escape, resize, or scroll
    // (scroll close is important because the panel is position:fixed and
    // won't follow the button if the page scrolls under it).
    document.addEventListener('click', function() { closeOpen(); });
    document.addEventListener('keydown', function(ev) { if (ev.key === 'Escape') closeOpen(); });
    window.addEventListener('resize', function() { closeOpen(); });
    window.addEventListener('scroll', function() { closeOpen(); }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { scan(); });
    } else {
        scan();
    }

    // Expose a hook so dynamically-added selects can be enhanced on demand
    window.enhanceDropdowns = function(root) { scan(root); };
})();
</script>

<!-- Logout Confirmation Modal -->
<div id="logoutConfirmModal" class="logout-confirm-backdrop" onclick="if(event.target===this)closeLogoutConfirm()" aria-hidden="true">
    <div class="logout-confirm-card" role="dialog" aria-labelledby="logoutConfirmTitle" aria-modal="true">
        <!-- Particle canvas — same aesthetic as sidebar-user-card hover -->
        <canvas class="logout-modal-particles" id="logoutModalParticles" aria-hidden="true"></canvas>
        <div class="logout-confirm-icon-wrap">
            <!-- Thin-stroke door-open SVG, 3x the previous 42px icon -->
            <svg class="logout-confirm-icon" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                    <!-- Floor line -->
                    <line x1="12" y1="108" x2="108" y2="108" opacity="0.55"/>
                    <!-- Door frame left pillar -->
                    <line x1="22" y1="14" x2="22" y2="108"/>
                    <!-- Door frame right pillar -->
                    <line x1="82" y1="22" x2="82" y2="108"/>
                    <!-- Door frame top -->
                    <line x1="22" y1="14" x2="82" y2="22"/>
                    <!-- Open door (swung slightly outward) -->
                    <path d="M34 28 L68 18 L68 104 L34 112 Z"/>
                    <!-- Doorknob -->
                    <circle cx="40" cy="68" r="2.5" fill="currentColor" stroke="none"/>
                    <!-- Motion lines suggesting the door just opened -->
                    <line x1="90" y1="50" x2="104" y2="46" opacity="0.6"/>
                    <line x1="90" y1="60" x2="106" y2="60" opacity="0.45"/>
                    <line x1="90" y1="70" x2="104" y2="74" opacity="0.3"/>
                </g>
            </svg>
        </div>
        <h2 id="logoutConfirmTitle" class="logout-confirm-title">Sign out?</h2>
        <p class="logout-confirm-body">You'll need to sign in again to continue where you left off.</p>
        <div class="logout-confirm-actions">
            <button type="button" class="btn-logout-cancel" onclick="closeLogoutConfirm()">Cancel</button>
            <button type="button" class="btn-logout-yes" onclick="confirmLogout()">Yes, sign out</button>
        </div>
    </div>
</div>
<script>
function openLogoutConfirm(ev) {
    if (ev) ev.preventDefault();
    var m = document.getElementById('logoutConfirmModal');
    if (!m) return;
    m.classList.add('active');
    m.setAttribute('aria-hidden', 'false');
    if (window.startLogoutModalParticles) window.startLogoutModalParticles();
    // Focus the Cancel button after the entrance animation completes
    setTimeout(function() {
        var cancelBtn = m.querySelector('.btn-logout-cancel');
        if (cancelBtn) cancelBtn.focus();
    }, 1000);
    return false;
}
function closeLogoutConfirm() {
    var m = document.getElementById('logoutConfirmModal');
    if (!m) return;
    m.classList.remove('active');
    m.setAttribute('aria-hidden', 'true');
    if (window.stopLogoutModalParticles) window.stopLogoutModalParticles();
}
function confirmLogout() {
    // Let the exit animation play briefly before navigating
    closeLogoutConfirm();
    setTimeout(function() {
        window.location.href = '<?= BASE_URL ?>/logout';
    }, 450);
}
// Escape closes the modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var m = document.getElementById('logoutConfirmModal');
        if (m && m.classList.contains('active')) closeLogoutConfirm();
    }
});

// ---- Logout modal particle system ----
// Same constellation + floating particle pattern as the sidebar user card
// hover effect, but rendered in white so it reads against the brand-red
// gradient card. Runs only while the modal is open.
(function() {
    var canvas = document.getElementById('logoutModalParticles');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var nodes = [];
    var particles = [];
    var running = false;
    var rafId = null;
    var lastT = 0;

    function resize() {
        var rect = canvas.getBoundingClientRect();
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(rect.height * dpr));
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function seed() {
        resize();
        var w = canvas.width / dpr;
        var h = canvas.height / dpr;
        nodes = [];
        particles = [];
        // Constellation nodes — 16 white drifting points with connecting lines
        for (var i = 0; i < 16; i++) {
            nodes.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                r: Math.random() * 1.8 + 0.8,
                phase: Math.random() * Math.PI * 2
            });
        }
        // Free-floating wispy particles
        for (var j = 0; j < 22; j++) {
            particles.push({
                x: Math.random() * w,
                y: Math.random() * h,
                vx: (Math.random() - 0.5) * 0.22,
                vy: -(Math.random() * 0.28 + 0.06),
                r: Math.random() * 1.2 + 0.35,
                life: Math.random(),
                maxLife: 1.2 + Math.random() * 1.4
            });
        }
    }

    function step(t) {
        if (!running) { rafId = null; return; }
        if (!lastT) lastT = t;
        var dt = Math.min(0.05, (t - lastT) / 1000);
        lastT = t;
        var w = canvas.width / dpr;
        var h = canvas.height / dpr;

        ctx.clearRect(0, 0, w, h);

        // Update + draw nodes
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            n.x += n.vx;
            n.y += n.vy;
            n.phase += dt * 1.5;
            if (n.x < 0 || n.x > w) n.vx *= -1;
            if (n.y < 0 || n.y > h) n.vy *= -1;
            n.x = Math.max(0, Math.min(w, n.x));
            n.y = Math.max(0, Math.min(h, n.y));
        }

        // Connecting lines between near nodes
        var linkDist = 95;
        for (var i = 0; i < nodes.length; i++) {
            for (var j = i + 1; j < nodes.length; j++) {
                var dx = nodes[i].x - nodes[j].x;
                var dy = nodes[i].y - nodes[j].y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < linkDist) {
                    var alpha = (1 - dist / linkDist) * 0.35;
                    ctx.beginPath();
                    ctx.moveTo(nodes[i].x, nodes[i].y);
                    ctx.lineTo(nodes[j].x, nodes[j].y);
                    ctx.strokeStyle = 'rgba(255,255,255,' + alpha + ')';
                    ctx.lineWidth = 0.75;
                    ctx.stroke();
                }
            }
        }

        // Draw node dots with pulse
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            var pulse = 0.55 + 0.45 * (Math.sin(n.phase) * 0.5 + 0.5);
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r * pulse, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,' + (0.9 * pulse) + ')';
            ctx.fill();
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r * 2.8, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,' + (0.14 * pulse) + ')';
            ctx.fill();
        }

        // Floating particles
        for (var p = 0; p < particles.length; p++) {
            var pt = particles[p];
            pt.x += pt.vx;
            pt.y += pt.vy;
            pt.life += dt;
            if (pt.life > pt.maxLife || pt.y < -5) {
                pt.x = Math.random() * w;
                pt.y = h + 3;
                pt.life = 0;
                pt.maxLife = 1.2 + Math.random() * 1.4;
            }
            var lifeFade = Math.sin((pt.life / pt.maxLife) * Math.PI);
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, pt.r, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255,255,255,' + (lifeFade * 0.8) + ')';
            ctx.fill();
        }

        rafId = requestAnimationFrame(step);
    }

    window.startLogoutModalParticles = function() {
        if (running) return;
        running = true;
        lastT = 0;
        seed();
        if (!rafId) rafId = requestAnimationFrame(step);
    };
    window.stopLogoutModalParticles = function() {
        running = false;
        // Let the current frame finish; the next call short-circuits
        setTimeout(function() {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            ctx.clearRect(0, 0, canvas.width / dpr, canvas.height / dpr);
        }, 1000);
    };
})();
</script>
</body>
</html>
