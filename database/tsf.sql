-- ============================================
-- TSF DATABASE SCHEMA
-- Database: tsf
-- Created for: Trenchkid Support Foundation
-- ============================================

CREATE DATABASE IF NOT EXISTS tsf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tsf;

-- ============================================
-- ADMIN TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admin (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(80) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
    full_name    VARCHAR(150) DEFAULT NULL,
    email        VARCHAR(180) NOT NULL UNIQUE,
    last_login   DATETIME DEFAULT NULL,
    login_attempts TINYINT UNSIGNED DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin (password: TSF@2025! — bcrypt)
INSERT INTO admin (username, password, full_name, email)
VALUES ('tsf_admin', '$2y$10$elj.R7hftBzDPS7IFCK0gOjP.PzMQrkgZFlfKWfZXMHswqGgL/X4e', 'TSF Administrator', 'admin@tsfghana.org')
ON DUPLICATE KEY UPDATE username = username;


-- ============================================
-- DONORS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS donors (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name   VARCHAR(100) NOT NULL,
    last_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(180) NOT NULL,
    phone        VARCHAR(20) DEFAULT NULL,
    gender       ENUM('Male','Female','Prefer not to say') DEFAULT NULL,
    amount       DECIMAL(12, 2) NOT NULL,
    currency     VARCHAR(5) DEFAULT 'GHS',
    payment_method ENUM('mobile_money','card') NOT NULL,
    mobile_network ENUM('mtn','telecel','airteltigo') DEFAULT NULL,
    transaction_ref VARCHAR(100) DEFAULT NULL COMMENT 'From payment gateway',
    payment_verified TINYINT(1) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created (created_at),
    UNIQUE KEY uniq_transaction_ref (transaction_ref)
) ENGINE=InnoDB;


-- ============================================
-- MESSAGES TABLE (contact + general)
-- ============================================
CREATE TABLE IF NOT EXISTS messages (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150) NOT NULL,
    email        VARCHAR(180) DEFAULT NULL,
    phone        VARCHAR(20) DEFAULT NULL,
    subject      VARCHAR(255) DEFAULT NULL,
    message_type ENUM('general','volunteer','partner','donate','comment') DEFAULT 'general',
    message      TEXT NOT NULL,
    is_read      TINYINT(1) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;


-- ============================================
-- NEWS / ARTICLES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS news (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL UNIQUE,
    content      LONGTEXT NOT NULL,
    category     ENUM('Education','Digital Skills','Health','Community','Partnership','Announcement','General') DEFAULT 'General',
    author_name  VARCHAR(150) DEFAULT 'TSF Communications',
    author_id    INT UNSIGNED DEFAULT NULL,
    cover_image  VARCHAR(500) DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 0,
    is_featured  TINYINT(1) DEFAULT 0,
    published_at DATETIME DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES admin(id) ON DELETE SET NULL,
    INDEX idx_published (is_published, published_at),
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- ============================================
-- SITE SETTINGS / EDITABLE CONTENT
-- ============================================
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Trenchkid Support Foundation'),
('site_tagline', 'Empowering Children, Building Futures'),
('home_intro_title', 'Breaking Cycles, Building Futures'),
('home_intro_body', 'Trenchkid Support Foundation empowers underprivileged children with education, digital skills, healthcare support, and mentorship so they can build independent and impactful futures.'),
('about_brief', 'Trenchkid Support Foundation (TSF) was established by passionate young Ghanaians who believe that no child''s future should be limited by poverty. TSF creates pathways through education, digital skills, mentorship, and community support.'),
('mission_statement', 'Trenchkid Support Foundation exists to empower underprivileged children with education, digital skills, and mentorship, enabling them to build independent and impactful futures.'),
('contact_address', 'Kumasi, Ashanti Region, Ghana'),
('contact_phone', '+233 XX XXX XXXX'),
('contact_email', 'info@tsfghana.org'),
('office_hours', 'Mon - Fri: 8:00 AM - 5:00 PM'),
('facebook_url', 'contact.html'),
('twitter_url', 'contact.html'),
('instagram_url', 'contact.html'),
('linkedin_url', 'contact.html'),
('whatsapp_url', 'contact.html')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ============================================
-- GALLERY
-- ============================================
CREATE TABLE IF NOT EXISTS gallery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    caption TEXT DEFAULT NULL,
    image_url VARCHAR(600) NOT NULL,
    category VARCHAR(80) DEFAULT 'General',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_gallery_image (image_url),
    INDEX idx_active_order (is_active, display_order, created_at)
) ENGINE=InnoDB;

INSERT INTO gallery (title, caption, image_url, category, display_order) VALUES
('Classroom Support', 'Children receiving learning materials through TSF education programs.', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=900&q=80', 'Education', 1),
('Community Outreach', 'Volunteers and community partners working together for children.', 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=900&q=80', 'Community', 2),
('Digital Skills Session', 'Youth learning practical technology skills for the future.', 'https://images.unsplash.com/photo-1504439904031-93ded9f93e4e?w=900&q=80', 'Digital Skills', 3),
('Mentorship Moment', 'Guidance and mentorship help children build confidence.', 'https://images.unsplash.com/photo-1519340333755-56e9c1d04579?w=900&q=80', 'Mentorship', 4)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    caption = VALUES(caption),
    category = VALUES(category),
    display_order = VALUES(display_order),
    is_active = 1;

-- ============================================
-- PASSWORD RESET TOKENS
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;


-- ============================================
-- CSRF TOKENS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS csrf_tokens (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(64) NOT NULL UNIQUE,
    form_type  VARCHAR(50) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;


-- ============================================
-- DONATION TOTALS VIEW
-- ============================================
CREATE OR REPLACE VIEW donation_summary AS
SELECT
    COUNT(*) AS total_donors,
    SUM(amount) AS total_raised,
    MAX(created_at) AS last_donation_at,
    AVG(amount) AS average_donation
FROM donors
WHERE payment_verified = 1;
