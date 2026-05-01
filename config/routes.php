<?php

// Auth routes
$router->get('/login', 'AuthController', 'loginForm');
$router->post('/login', 'AuthController', 'login');
$router->post('/login-ajax', 'AuthController', 'loginAjax');
$router->post('/forgot-password', 'AuthController', 'forgotPassword');
$router->post('/easter-egg-email', 'AuthController', 'easterEggEmail');
$router->get('/logout', 'AuthController', 'logout');

// Dashboard
$router->get('/', 'DashboardController', 'index');
$router->get('/dashboard', 'DashboardController', 'index');

// Generator
$router->get('/generator', 'GeneratorController', 'index');
$router->post('/generator/week', 'GeneratorController', 'generateWeek');
$router->post('/generator/single', 'GeneratorController', 'generateSingle');
$router->post('/generator/regenerate-text', 'GeneratorController', 'regenerateText');
$router->post('/generator/regenerate-image', 'GeneratorController', 'regenerateImage');
$router->post('/generator/start-image-job', 'GeneratorController', 'startImageJob');
$router->get('/generator/check-image-jobs', 'GeneratorController', 'checkImageJobs');

// Posts / Editor
$router->get('/posts', 'PostController', 'index');
$router->get('/posts/edit/{id}', 'PostController', 'edit');
$router->post('/posts/save', 'PostController', 'save');
$router->post('/posts/update/{id}', 'PostController', 'update');
$router->post('/posts/delete/{id}', 'PostController', 'delete');
$router->post('/posts/schedule/{id}', 'PostController', 'schedule');
$router->post('/posts/post-now/{id}', 'PostController', 'postNow');
$router->post('/posts/retry/{id}', 'PostController', 'retry');
$router->get('/posts/logs/{id}', 'PostController', 'logs');

// Calendar
$router->get('/calendar', 'CalendarController', 'index');
$router->get('/calendar/events', 'CalendarController', 'events');

// Reporting
$router->get('/reporting', 'ReportingController', 'index');
$router->get('/reporting/export-csv', 'ReportingController', 'exportCsv');

// Branding
$router->get('/branding', 'BrandingController', 'index');
$router->post('/branding/save', 'BrandingController', 'save');
$router->post('/branding/save-api', 'BrandingController', 'saveApi');
$router->post('/branding/test-api', 'BrandingController', 'testApi');

// Art Direction
$router->get('/art-direction', 'ArtDirectionController', 'index');
$router->post('/art-direction/save', 'ArtDirectionController', 'save');
$router->post('/art-direction/preview', 'ArtDirectionController', 'preview');
$router->post('/art-direction/apply-preset', 'ArtDirectionController', 'applyPreset');

// Content Strategy
$router->get('/content-strategy', 'ContentStrategyController', 'index');
$router->post('/content-strategy/save-theme', 'ContentStrategyController', 'saveTheme');
$router->post('/content-strategy/delete-theme/{id}', 'ContentStrategyController', 'deleteTheme');
$router->post('/content-strategy/save-schedule', 'ContentStrategyController', 'saveSchedule');
$router->post('/content-strategy/critique', 'ContentStrategyController', 'critique');

// Setup Wizard
$router->get('/wizard', 'WizardController', 'index');
$router->post('/wizard/scan-website', 'WizardController', 'scanWebsite');
$router->post('/wizard/suggest-themes', 'WizardController', 'suggestThemes');
$router->post('/wizard/save', 'WizardController', 'save');

// User Management
$router->get('/users', 'UserController', 'index');
$router->post('/users/create', 'UserController', 'create');
$router->post('/users/update/{id}', 'UserController', 'update');
$router->post('/users/deactivate/{id}', 'UserController', 'deactivate');
$router->post('/users/activate/{id}', 'UserController', 'activate');
$router->post('/users/delete/{id}', 'UserController', 'deleteUser');
$router->post('/users/restore/{id}', 'UserController', 'restoreUser');
$router->post('/users/permanent-delete/{id}', 'UserController', 'permanentDelete');
$router->post('/users/resend-invite/{id}', 'UserController', 'resendInvite');
$router->post('/users/save-approval-settings', 'UserController', 'saveApprovalSettings');

// SMTP / Email Settings
$router->get('/smtp', 'SmtpController', 'index');
$router->post('/smtp/save', 'SmtpController', 'save');
$router->post('/smtp/test', 'SmtpController', 'test');

// Post Reviews
$router->get('/reviews', 'ReviewController', 'index');
$router->post('/reviews/approve/{id}', 'ReviewController', 'approve');
$router->post('/reviews/request-changes/{id}', 'ReviewController', 'requestChanges');

// Activity Log (admin only)
$router->get('/settings/activity-log', 'ActivityLogController', 'index');

// Generated Reports (Phase 2)
$router->post('/reports/generate',       'ReportsController', 'generate');
$router->get ('/reports/view/{id}',      'ReportsController', 'show');
$router->get ('/reports/library',        'ReportsController', 'libraryList');
$router->post('/reports/settings/save',  'ReportsController', 'saveSettings');
$router->post('/reports/delete/{id}',    'ReportsController', 'delete');

// Phase 3 — share tokens + public shared view (no auth)
$router->post('/reports/share/{id}',     'ReportsController', 'share');
$router->post('/reports/unshare/{id}',   'ReportsController', 'unshare');
$router->get ('/shared/{token}',         'SharedReportController', 'show');

// Auth extras (password change, tour complete)
$router->post('/auth/change-password', 'AuthController', 'changePassword');
$router->post('/auth/complete-tour', 'AuthController', 'completeTour');

// Memory
$router->get('/memory', 'MemoryController', 'index');

// Documentation
$router->get('/docs', 'DocumentationController', 'index');

// API endpoints
$router->get('/api/posts', 'PostController', 'apiList');
$router->get('/api/stats', 'DashboardController', 'apiStats');
$router->get('/api/posts-by-status', 'DashboardController', 'apiPostsByStatus');
