<?php

ob_start();
session_start();

define('APP_ROOT', dirname(__DIR__));

// Load environment config
require_once APP_ROOT . '/config/env.php';

// Autoload core classes
require_once APP_ROOT . '/core/Database.php';
require_once APP_ROOT . '/core/Controller.php';
require_once APP_ROOT . '/core/Model.php';
require_once APP_ROOT . '/core/Router.php';

// Load models
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Post.php';
require_once APP_ROOT . '/app/models/BrandingSetting.php';
require_once APP_ROOT . '/app/models/ContentMemory.php';
require_once APP_ROOT . '/app/models/ArtDirectionSetting.php';
require_once APP_ROOT . '/app/models/ContentTheme.php';
require_once APP_ROOT . '/app/models/ThemeSample.php';
require_once APP_ROOT . '/app/models/ThemeSchedule.php';
require_once APP_ROOT . '/app/models/SmtpSetting.php';
require_once APP_ROOT . '/app/models/ApprovalSetting.php';
require_once APP_ROOT . '/app/models/PostReview.php';
require_once APP_ROOT . '/app/models/ActivityLog.php';
require_once APP_ROOT . '/app/models/ReportSettings.php';
require_once APP_ROOT . '/app/models/GeneratedReport.php';

// Load services
require_once APP_ROOT . '/app/services/AIService.php';
require_once APP_ROOT . '/app/services/ZernioService.php';
require_once APP_ROOT . '/app/services/ContentMemoryService.php';
require_once APP_ROOT . '/app/services/BrandingService.php';
require_once APP_ROOT . '/app/services/ModalService.php';
require_once APP_ROOT . '/app/services/ArtDirectionService.php';
require_once APP_ROOT . '/app/services/ContentStrategyService.php';
require_once APP_ROOT . '/app/services/WizardService.php';
require_once APP_ROOT . '/app/services/EmailService.php';
require_once APP_ROOT . '/app/services/UserManagementService.php';
require_once APP_ROOT . '/app/services/ApprovalService.php';
require_once APP_ROOT . '/app/services/ProfilePdfService.php';
require_once APP_ROOT . '/app/services/ActivityLogService.php';
require_once APP_ROOT . '/app/services/ReportSettingsService.php';
require_once APP_ROOT . '/app/services/ReportGeneratorService.php';

// Single client mode
$GLOBALS['client_id'] = CLIENT_ID;
// Multi-client support (FUTURE)
// $GLOBALS['client_id'] = $_SESSION['client_id'] ?? null;

$router = new Router();
require_once APP_ROOT . '/config/routes.php';
$router->resolve();
