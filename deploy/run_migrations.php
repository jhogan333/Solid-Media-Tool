<?php
/**
 * One-time migration runner — DELETE THIS FILE after use.
 */
if (($_GET['key'] ?? '') !== 'solidtech2026deploy') {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$host = 'localhost';
$db   = 'dbn3pvclcgtvkd';
$user = 'upa46ebb8i5q5';
$pass = 'Ngcxlebp#000';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected to: $db\n\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(255) UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `first_name` VARCHAR(100),
        `role` ENUM('admin','editor','reviewer') NOT NULL DEFAULT 'editor',
        `avatar_url` VARCHAR(500),
        `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
        `has_completed_tour` TINYINT(1) NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `invited_by` INT UNSIGNED NULL,
        `last_login_at` DATETIME NULL,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(`role`), INDEX(`email`), INDEX(`client_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: users\n";

    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO `users` (username,email,password,first_name,role,is_active,client_id) VALUES ('admin','admin@solidtech.ca','$adminPass','Admin','admin',1,1)");
    echo "OK: admin user seeded\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT,
        `image_url` VARCHAR(500),
        `post_type` VARCHAR(50) DEFAULT 'image',
        `platform` VARCHAR(20) DEFAULT 'facebook',
        `platforms` TEXT,
        `status` ENUM('draft','pending_review','scheduled','published','failed') DEFAULT 'draft',
        `scheduled_at` DATETIME NULL,
        `zernio_post_id` VARCHAR(100),
        `topic` VARCHAR(255),
        `keywords` TEXT,
        `angle` VARCHAR(255),
        `first_comment` TEXT,
        `content_hash` VARCHAR(64),
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(`status`), INDEX(`platform`), INDEX(`scheduled_at`), INDEX(`client_id`), INDEX(`client_id`,`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: posts\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `social_post_logs` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED NOT NULL,
        `platform` VARCHAR(20) NOT NULL,
        `account_id` VARCHAR(100) NULL,
        `status` ENUM('success','failed') NOT NULL,
        `zernio_post_id` VARCHAR(100),
        `response` LONGTEXT NULL,
        `error_message` TEXT,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`post_id`), INDEX(`platform`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: social_post_logs\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `branding_settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1 UNIQUE,
        `company_name` VARCHAR(100) DEFAULT 'SolidTech',
        `logo_url` VARCHAR(500),
        `primary_color` VARCHAR(20) DEFAULT '#8B1A1A',
        `secondary_color` VARCHAR(20) DEFAULT '#6b1515',
        `tagline` VARCHAR(255),
        `website` VARCHAR(255),
        `phone` VARCHAR(30),
        `first_comment` TEXT,
        `login_bg_url` VARCHAR(500),
        `favicon_url` VARCHAR(500),
        `particles_enabled` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: branding_settings\n";

    $pdo->exec("INSERT IGNORE INTO `branding_settings` (client_id,company_name,primary_color,secondary_color) VALUES (1,'SolidTech','#8B1A1A','#6b1515')");
    echo "OK: branding seeded\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `content_memory` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `topic` VARCHAR(255),
        `keywords` TEXT,
        `angle` VARCHAR(255),
        `content_hash` VARCHAR(64) UNIQUE,
        `post_id` INT UNSIGNED,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(`client_id`), INDEX(`content_hash`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: content_memory\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `art_direction_settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1 UNIQUE,
        `image_style` VARCHAR(50) DEFAULT 'photorealistic',
        `realism_level` TINYINT DEFAULT 8,
        `color_temperature` VARCHAR(20) DEFAULT 'neutral',
        `contrast_level` VARCHAR(20) DEFAULT 'punchy',
        `mood` VARCHAR(30) DEFAULT 'professional',
        `brand_color_bleed` TINYINT DEFAULT 25,
        `illustration_limit` VARCHAR(30) DEFAULT 'never',
        `avoid_list` TEXT,
        `watermark_enabled` TINYINT(1) DEFAULT 1,
        `watermark_website` VARCHAR(255),
        `watermark_logo_position` VARCHAR(20) DEFAULT 'bottom-left',
        `watermark_gradient_opacity` TINYINT DEFAULT 85,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: art_direction_settings\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `content_themes` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `copy_instructions` TEXT,
        `required_elements` JSON,
        `default_hashtags` TEXT,
        `image_style_override` VARCHAR(50) DEFAULT 'global',
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(`client_id`), INDEX(`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: content_themes\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `theme_samples` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `theme_id` INT UNSIGNED NOT NULL,
        `sample_content` TEXT NOT NULL,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`theme_id`) REFERENCES `content_themes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: theme_samples\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `theme_schedule` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `day_of_week` TINYINT NOT NULL,
        `theme_id` INT UNSIGNED NOT NULL,
        UNIQUE KEY `client_day` (`client_id`,`day_of_week`),
        FOREIGN KEY (`theme_id`) REFERENCES `content_themes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: theme_schedule\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `smtp_settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1 UNIQUE,
        `provider` ENUM('smtp','sendgrid','mailgun') DEFAULT 'smtp',
        `smtp_host` VARCHAR(255),
        `smtp_port` INT DEFAULT 587,
        `smtp_user` VARCHAR(255),
        `smtp_pass` VARCHAR(255),
        `smtp_encryption` ENUM('tls','ssl','none') DEFAULT 'tls',
        `sendgrid_api_key` VARCHAR(255),
        `mailgun_api_key` VARCHAR(255),
        `mailgun_domain` VARCHAR(255),
        `from_name` VARCHAR(100),
        `from_email` VARCHAR(255),
        `is_configured` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: smtp_settings\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `approval_settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT UNSIGNED NOT NULL DEFAULT 1 UNIQUE,
        `approval_required` TINYINT(1) DEFAULT 0,
        `min_approvals` INT UNSIGNED DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: approval_settings\n";

    $pdo->exec("INSERT IGNORE INTO `approval_settings` (client_id,approval_required,min_approvals) VALUES (1,0,1)");
    echo "OK: approval_settings seeded\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `post_reviews` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED NOT NULL,
        `reviewer_id` INT UNSIGNED NOT NULL,
        `status` ENUM('approved','changes_requested') NOT NULL,
        `comment` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `post_reviewer` (`post_id`,`reviewer_id`),
        FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "OK: post_reviews\n";

    echo "\n=== ALL 13 TABLES CREATED ===\n";
    echo "Login: admin / admin123\n";
    echo "\n*** DELETE THIS FILE NOW ***\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
