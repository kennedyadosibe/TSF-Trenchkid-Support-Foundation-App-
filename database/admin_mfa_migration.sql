-- ============================================
-- TSF ADMIN MFA MIGRATION
-- Adds email/SMS one-time-password verification after admin password login.
-- Import this on existing databases before uploading the MFA PHP files.
-- ============================================

ALTER TABLE admin
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS mfa_enabled TINYINT(1) DEFAULT 1 AFTER phone;

CREATE TABLE IF NOT EXISTS admin_mfa_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    delivery_target VARCHAR(180) NOT NULL,
    delivery_method ENUM('email','sms') NOT NULL DEFAULT 'email',
    attempts TINYINT UNSIGNED DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE,
    INDEX idx_admin_active (admin_id, used_at, expires_at),
    INDEX idx_token_hash (token_hash)
) ENGINE=InnoDB;
