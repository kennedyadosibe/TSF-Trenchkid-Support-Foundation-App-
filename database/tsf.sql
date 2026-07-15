-- ============================================
-- TSF DATABASE SCHEMA
-- Import this file inside the database selected in phpMyAdmin or your MySQL client.
-- Created for: Trenchkid Support Foundation
-- ============================================

-- ============================================
-- ADMIN TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS admin (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(80) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
    full_name    VARCHAR(150) DEFAULT NULL,
    email        VARCHAR(180) NOT NULL UNIQUE,
    phone        VARCHAR(20) DEFAULT NULL,
    mfa_enabled  TINYINT(1) DEFAULT 1,
    last_login   DATETIME DEFAULT NULL,
    login_attempts TINYINT UNSIGNED DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin seed. Set a fresh password with tools/reset_admin_password.php after import.
INSERT INTO admin (username, password, full_name, email)
VALUES ('tsf_admin', '$2y$10$elj.R7hftBzDPS7IFCK0gOjP.PzMQrkgZFlfKWfZXMHswqGgL/X4e', 'TSF Administrator', 'admin@tsfghana.org')
ON DUPLICATE KEY UPDATE username = username;


-- ============================================
-- ADMIN MFA TOKENS
-- ============================================
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
('mission_statement', 'Trenchkid Support Foundation exists to empower underprivileged children with education, digital skills, and mentorship, enabling them to build independent and impactful futures.'),
('home_hero_image', ''),
('home_hero_title', 'Every Child Deserves a Bright Future'),
('home_hero_body', 'Trenchkid Support Foundation bridges the gap between poverty and opportunity - providing education, digital skills, and mentorship to underprivileged children across Ghana.'),
('home_intro_title', 'Breaking Cycles, Building Futures'),
('home_intro_body', 'Trenchkid Support Foundation was born out of a deep belief: that every child, regardless of where they come from, deserves access to quality education, digital skills, and the mentorship to succeed.'),
('home_intro_body_2', 'We don''t give handouts - we give futures. Through targeted programs, community partnerships, and passionate volunteers, TSF transforms lives one child at a time.'),
('home_pillars_title', 'What We Stand For'),
('home_pillars_body', 'Four pillars guide every decision we make and every life we touch.'),
('home_cta_title', 'Ready to Change a Child''s Life?'),
('home_cta_body', 'Your donation - no matter the size - directly funds education, health care, and mentorship for Ghana''s underprivileged children. Every cedi counts.'),
('about_hero_image', ''),
('about_hero_title', 'About TSF'),
('about_hero_body', 'Learn our story, our mission, and the values that drive everything we do for Ghana''s underprivileged children.'),
('about_story_title', 'Founded on Hope & Purpose'),
('about_brief', 'Trenchkid Support Foundation (TSF) was established by passionate young Ghanaians who believe that no child''s future should be limited by poverty. TSF creates pathways through education, digital skills, mentorship, and community support.'),
('about_story_body_2', 'TSF was born as a direct response - a community-driven movement to ensure that no child''s future is determined by the circumstances of their birth.'),
('about_story_body_3', 'Today, TSF empowers underprivileged children with education, digital skills, and mentorship, enabling them to build independent and impactful futures.'),
('about_journey_title', 'How TSF Grew'),
('about_journey_body', 'From a small idea to a movement touching hundreds of lives across Ghana.'),
('about_cta_title', 'Be Part of the Story'),
('about_cta_body', 'Your donation today writes the next chapter for a child in need. Together, we can ensure no talent goes wasted.'),
('impact_hero_image', ''),
('impact_hero_title', 'Our Impact'),
('impact_hero_body', 'Numbers tell part of the story. Behind every target is a child TSF is preparing to reach with care, opportunity, and practical support.'),
('impact_numbers_title', 'TSF Targets'),
('impact_numbers_body', 'Expected reach and program goals TSF is working toward with donors, volunteers, schools, and community partners.'),
('impact_programs_title', 'What We Do'),
('impact_testimonials_title', 'Voices of Change'),
('impact_testimonials_body', 'Hear directly from the children, families, and volunteers whose lives TSF has touched.'),
('impact_reach_title', 'Spreading Across Ghana'),
('impact_reach_body', 'What started in Bolga has grown into a nationwide movement. TSF now operates across multiple regions of Ghana, partnering with local communities, schools, and organisations.'),
('team_hero_image', ''),
('team_hero_title', 'Our Team'),
('team_hero_body', 'Meet the passionate individuals who dedicate their time and talent to empowering Ghana''s children.'),
('team_group_image', 'images/page/team-group-generated.jpg'),
('team_group_image_2', 'images/page/team-group-generated-2.jpg'),
('team_group_title', 'Together for Every Child'),
('team_group_body', 'Meet the young leaders, volunteers, and builders working together to support children, strengthen communities, and grow the TSF mission across Ghana.'),
('team_leadership_title', 'The People Behind TSF'),
('team_leadership_body', 'Our team is built on passion, experience, and an unwavering belief in the potential of every child.'),
('team_advisors_title', 'Our Advisors'),
('team_advisors_body', 'Experienced professionals who guide and support TSF''s strategic direction.'),
('team_cta_title', 'Join Our Team'),
('team_cta_body', 'Are you passionate about empowering children and building futures? TSF is always looking for dedicated volunteers, mentors, and partners to join our movement.'),
('news_hero_image', ''),
('news_hero_title', 'News & Articles'),
('news_hero_body', 'Stay updated with the latest stories, updates, and announcements from Trenchkid Support Foundation.'),
('news_sidebar_cta', 'Every story you read represents a real child''s life. Help us write more stories like these.'),
('donate_hero_image', ''),
('donate_hero_title', 'Make a Donation'),
('donate_hero_body', 'Your generosity transforms lives. Every donation goes directly to supporting underprivileged children in Ghana.'),
('donate_form_title', 'Your Donation Details'),
('donate_form_body', 'Fill in your information below to complete your donation. All transactions are secure.'),
('donate_security_note', 'Your payment is secured and encrypted. TSF will never share your details.'),
('donate_thanks_body', 'Paystack will handle your payment details securely. If you added an email, you may also receive a confirmation there. Your contribution directly funds education, healthcare, and mentorship for underprivileged children across Ghana.'),
('contact_hero_image', ''),
('contact_hero_title', 'Get in Touch'),
('contact_hero_body', 'Whether you want to donate, volunteer, partner, or simply learn more - we''d love to hear from you.'),
('contact_form_title', 'Send Us a Message'),
('contact_form_body', 'Fill out the form and our team will get back to you within 24 hours.'),
('contact_info_title', 'Contact Information'),
('contact_address', 'Bolga, Upper East Region, Ghana'),
('contact_phone', '+233 XX XXX XXXX'),
('contact_email', 'info@tsfghana.org'),
('office_hours', 'Mon - Fri: 8:00 AM - 5:00 PM'),
('contact_volunteer_body', 'TSF welcomes mentors, educators, developers, healthcare workers, and anyone who shares our passion for empowering children. Select "Volunteer" above and tell us your skills!'),
('gallery_hero_image', ''),
('gallery_hero_title', 'Gallery'),
('gallery_hero_body', 'Moments from TSF programs, outreach, mentorship, and community support work.'),
('gallery_section_title', 'TSF Gallery'),
('gallery_section_body', 'Browse snapshots from the communities, children, and volunteers behind the mission.'),
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
('impact_stat', 'Children To Support', 'child', NULL, '1247', NULL, 1),
('impact_stat', 'Projects Planned', 'check', NULL, '48', NULL, 2),
('impact_stat', 'School Partners Targeted', 'school', NULL, '23', NULL, 3),
('impact_stat', 'Youth To Train', 'laptop', NULL, '380', NULL, 4),
('program', 'School Support Program', 'Education', 'We sponsor school fees, uniforms, books, and stationery for children from families who cannot afford them, with a target of supporting 600+ children in school annually.', '600+ children targeted per year', 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=600&q=80', 1),
('program', 'Digital Skills Academy', 'Technology', 'Our intensive digital training covers coding, graphic design, computer literacy, and entrepreneurship - giving youth a competitive edge in the digital economy.', '380+ youth targeted for training', 'https://images.unsplash.com/photo-1504439904031-93ded9f93e4e?w=600&q=80', 2),
('program', 'Youth Mentorship Program', 'Mentorship', 'We pair children with mentors from industry - developers, doctors, teachers, and entrepreneurs - who guide them through education and into career readiness.', '250+ mentor-mentee pairs targeted', 'https://images.unsplash.com/photo-1519340333755-56e9c1d04579?w=600&q=80', 3),
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
