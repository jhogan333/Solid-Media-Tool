<?php

define('APP_ENV', 'production');
define('APP_NAME', 'SolidTech Social');
define('APP_VERSION', '2.0.0');
define('APP_TIMEZONE', 'America/Edmonton');

if (APP_ENV === 'local') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'solidtech_social');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
    define('BASE_URL', 'http://localhost/Solid-SocialMedia/public');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'dbn3pvclcgtvkd');
    define('DB_USER', 'upa46ebb8i5q5');
    define('DB_PASS', 'Ngcxlebp#000');
    define('DB_PORT', '3306');
    define('BASE_URL', 'https://marketing.aiagentprojects.com/social/public');
}

define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// OpenRouter (text generation)
define('OPENROUTER_API_KEY', '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');
define('OPENROUTER_MODEL', 'openai/gpt-4o-mini');
define('OPENROUTER_URL', 'https://openrouter.ai/api/v1/chat/completions');

// Kie.ai (image generation — NanoBanana2)
define('KIE_API_KEY', 'b163495e6cba54dbcf090c44d1e09c1f');
define('KIE_CREATE_URL', 'https://api.kie.ai/api/v1/jobs/createTask');
define('KIE_STATUS_URL', 'https://api.kie.ai/api/v1/jobs/recordInfo');
define('KIE_MODEL', 'nano-banana-2');

// Zernio (social scheduling)
define('ZERNIO_API_KEY', 'sk_0c5d38edac070812863182cf4474f45eceb8443df240a1a671181ffb4161c1e7');
define('ZERNIO_API_URL', 'https://zernio.com/api/v1');

// Zernio Account IDs
define('ZERNIO_FACEBOOK_ACCOUNT_ID', '69afa57edc8cab9432c7de32');
define('ZERNIO_LINKEDIN_ACCOUNT_ID', '69afad94dc8cab9432c7ee0d');

// Legacy alias
define('AI_API_KEY', OPENROUTER_API_KEY);

define('CLIENT_ID', 1);
