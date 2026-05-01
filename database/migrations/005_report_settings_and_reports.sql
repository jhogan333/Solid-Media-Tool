-- Migration 005: Report Settings, Generated Reports, Dark Logo
-- Phase 2: Cost Savings card + Generated Reports system.
--
-- ROLLBACK:
--   DROP TABLE IF EXISTS generated_reports;
--   DROP TABLE IF EXISTS report_settings;
--   ALTER TABLE branding_settings DROP COLUMN dark_logo_url;

CREATE TABLE IF NOT EXISTS report_settings (
    client_id INT UNSIGNED PRIMARY KEY,
    minutes_per_post INT UNSIGNED NOT NULL DEFAULT 30,
    hourly_rate DECIMAL(6,2) NOT NULL DEFAULT 29.00,
    currency_symbol VARCHAR(5) NOT NULL DEFAULT '$',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS generated_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    created_by_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    date_range_start DATE NOT NULL,
    date_range_end DATE NOT NULL,
    report_data JSON NOT NULL,
    share_token VARCHAR(64) NULL UNIQUE,
    shared_at TIMESTAMP NULL,
    view_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_created (client_id, created_at),
    INDEX idx_share_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add dark logo variant to branding_settings for use on light report pages
-- where the primary (often white) logo would be invisible.
ALTER TABLE branding_settings
  ADD COLUMN dark_logo_url VARCHAR(500) NULL AFTER logo_url;
