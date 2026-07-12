ALTER TABLE donors
    MODIFY email VARCHAR(180) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS sms_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_id BIGINT UNSIGNED DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('queued','sent','failed') DEFAULT 'queued',
    provider_response TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME DEFAULT NULL,
    FOREIGN KEY (donor_id) REFERENCES donors(id) ON DELETE SET NULL,
    INDEX idx_phone (phone),
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB;
