USE tsf;

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
