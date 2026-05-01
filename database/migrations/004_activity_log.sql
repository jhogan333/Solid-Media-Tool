-- Migration 004: Activity Log
-- Audit trail of every meaningful user action in the system.
-- Admin-only viewing. Hooked from controllers and services.
--
-- ROLLBACK: DROP TABLE IF EXISTS activity_logs;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    user_name VARCHAR(150) NULL,
    user_role VARCHAR(20) NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_created (client_id, created_at),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
