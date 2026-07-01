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
    email        VARCHAR(180) DEFAULT NULL,
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
-- SMS NOTIFICATIONS
-- ============================================
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
('contact_address', 'Bolga, Upper East Region, Ghana'),
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
-- REPEATABLE PAGE CONTENT ITEMS
-- ============================================
CREATE TABLE IF NOT EXISTS content_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_type VARCHAR(60) NOT NULL,
    title VARCHAR(220) NOT NULL,
    subtitle VARCHAR(220) DEFAULT NULL,
    body TEXT DEFAULT NULL,
    meta_value VARCHAR(220) DEFAULT NULL,
    image_url VARCHAR(600) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_content_item_seed (item_type, title),
    INDEX idx_type_active_order (item_type, is_active, display_order, created_at)
) ENGINE=InnoDB;

INSERT INTO content_items (item_type, title, subtitle, body, meta_value, image_url, display_order) VALUES
('team_member', 'Emmanuel K.', 'Founder & Executive Director', 'A social entrepreneur and youth advocate with 8+ years of experience in community development. Emmanuel founded TSF after witnessing talented youth unable to reach their potential due to poverty.', 'EK', NULL, 1),
('team_member', 'Abena B.', 'Programs Director', 'A certified educator and child rights advocate. Abena oversees all TSF program delivery, ensuring every child receives maximum benefit from our interventions.', 'AB', NULL, 2),
('team_member', 'Kwesi A.', 'Digital Skills Lead', 'A software developer and digital skills trainer passionate about bridging the tech gap. Kwesi leads TSF''s Digital Skills Academy and all technology initiatives.', 'KA', NULL, 3),
('team_member', 'Fatima D.', 'Finance & Accountability', 'A certified accountant ensuring every donation is tracked, properly allocated, and reported transparently. Fatima upholds TSF''s commitment to financial integrity.', 'FD', NULL, 4),
('team_member', 'Samuel A.', 'Community Outreach Coordinator', 'Samuel bridges TSF with communities across Ghana, identifying children in need, building local partnerships, and ensuring programmes reach those who need them most.', 'SA', NULL, 5),
('team_member', 'Grace N.', 'Communications Manager', 'A media and communications specialist who tells TSF''s story to the world. Grace manages all outreach, social media, and donor communications with clarity and heart.', 'GN', NULL, 6),
('advisor', 'Prof. Bernard Amoako', 'Education Specialist, UG', NULL, 'PB', NULL, 1),
('advisor', 'Dr. Ama Owusu', 'Child Rights Advocate', NULL, 'DO', NULL, 2),
('advisor', 'James Appiah', 'Tech Entrepreneur & Investor', NULL, 'JA', NULL, 3),
('advisor', 'Mary Kusi', 'NGO Governance Expert', NULL, 'MK', NULL, 4),
('impact_stat', 'Children Supported', 'child', NULL, '1247', NULL, 1),
('impact_stat', 'Projects Completed', 'check', NULL, '48', NULL, 2),
('impact_stat', 'Schools Partnered', 'school', NULL, '23', NULL, 3),
('impact_stat', 'Digital Skills Trained', 'laptop', NULL, '380', NULL, 4),
('program', 'School Support Program', 'Education', 'We sponsor school fees, uniforms, books, and stationery for children from families who cannot afford them, keeping 600+ children in school annually.', '600+ children kept in school per year', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=600&q=80', 1),
('program', 'Digital Skills Academy', 'Technology', 'Our intensive digital training covers coding, graphic design, computer literacy, and entrepreneurship - giving youth a competitive edge in the digital economy.', '380+ graduates trained', 'https://images.unsplash.com/photo-1504439904031-93ded9f93e4e?w=600&q=80', 2),
('program', 'Youth Mentorship Program', 'Mentorship', 'We pair children with mentors from industry - developers, doctors, teachers, and entrepreneurs - who guide them through education and into career readiness.', '250+ mentor-mentee pairs active', 'https://images.unsplash.com/photo-1519340333755-56e9c1d04579?w=600&q=80', 3),
('testimonial', 'Akosua K.', 'TSF Scholar, Bolga', 'TSF paid my school fees when my mother could no longer afford them. Today I am in my second year at university studying computer science. TSF did not just save my education - they saved my future.', 'AK', NULL, 1),
('testimonial', 'Kofi M.', 'Digital Skills Graduate', 'Through TSF''s digital skills program, I learned how to design graphics. Now I run my own small design business and support my siblings. This foundation does not give charity - it gives power.', 'KM', NULL, 2),
('testimonial', 'Esther A.', 'Volunteer Mentor', 'As a volunteer mentor with TSF, I have witnessed firsthand the transformation in these children''s confidence and ambition. Every session reminds me why this work matters so deeply.', 'EA', NULL, 3),
('region', 'Upper East Region', NULL, NULL, NULL, NULL, 1),
('region', 'Greater Accra', NULL, NULL, NULL, NULL, 2),
('region', 'Brong-Ahafo', NULL, NULL, NULL, NULL, 3),
('region', 'Northern Region', NULL, NULL, NULL, NULL, 4),
('faq', 'How do I know my donation is being used properly?', NULL, 'TSF maintains full financial transparency. Every donation is recorded, tracked, and reported. We publish regular impact reports and our Finance Manager ensures strict accountability. You can view donation allocation on our Donate page.', NULL, NULL, 1),
('faq', 'Can I donate from outside Ghana?', NULL, 'Absolutely! We accept international donations via Visa/Mastercard and Google Pay. International supporters are a vital part of our community - every contribution makes a difference regardless of where it comes from.', NULL, NULL, 2),
('faq', 'How can I volunteer with TSF?', NULL, 'Select "Volunteer" in the contact form above and tell us your skills, availability, and what area you would like to contribute to. We welcome mentors, educators, tech professionals, healthcare workers, and administrators.', NULL, NULL, 3),
('faq', 'How does TSF select children to support?', NULL, 'Children are identified through our community coordinators, school partnerships, and referrals from trusted community leaders. We assess financial need, academic potential, and family circumstances before enrolling a child in our programs.', NULL, NULL, 4),
('faq', 'Can organisations partner with TSF?', NULL, 'Yes! We actively seek partnerships with schools, tech companies, healthcare organisations, and community groups. Select "Partnership" in the contact form and our team will reach out to discuss collaboration opportunities.', NULL, NULL, 5)
ON DUPLICATE KEY UPDATE
    subtitle = VALUES(subtitle),
    body = VALUES(body),
    meta_value = VALUES(meta_value),
    image_url = VALUES(image_url),
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
