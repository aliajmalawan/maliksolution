-- The Alpine School — Haroonabad Campus
-- Full schema + seed data

CREATE DATABASE IF NOT EXISTS alpine_school_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alpine_school_db;

-- ---------------------------------------------------------------
-- admins
-- ---------------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin','admin','editor') NOT NULL DEFAULT 'admin',
    last_login_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- default password: Alpine@2026  (change after first login)
INSERT INTO admins (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$KgdLxnFxhfm10hP3B/IZ6uO0DcuIVitPiKD/6B7kG1towDUbHtK5S', 'Alpine Admin', 'super_admin');

-- ---------------------------------------------------------------
-- notifications (admin inbox alerts)
-- ---------------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message VARCHAR(400),
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_read (is_read),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- activity_logs (who did what in the admin panel)
-- ---------------------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    admin_name VARCHAR(100),
    action VARCHAR(100) NOT NULL,
    details VARCHAR(400),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activity_created (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- settings (key/value)
-- ---------------------------------------------------------------
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'The Alpine School'),
('campus_name', 'Haroonabad Campus'),
('tagline', 'Perfection | Progress | Prosperity'),
('motto', 'A Knowledge Gate Way'),
('phone', '0333-4276313'),
('whatsapp', '923334276313'),
('email', 'info@thealpineschoolhn.com'),
('address', '43/A, Y-Block, Housing Colony, Haroonabad'),
('facebook', 'https://facebook.com/alpineschoolofficial'),
('instagram', 'https://instagram.com/thealpineschool'),
('tiktok', 'https://tiktok.com/@the.alpine.school'),
('website', 'https://www.thealpineschoolhn.com'),
('map_embed', ''),
('primary_color', '#2E1B6B'),
('primary_dark', '#161233'),
('secondary_color', '#8A8D93'),
('accent_color', '#7ED321'),
('accent2_color', '#D4AF37'),
('logo_path', 'assets/images/logo.jpg'),
('footer_text', '© 2026 The Alpine School — Haroonabad Campus. A Project of Alpine School System. All rights reserved.'),
('stats_students', '600'),
('stats_faculty', '35'),
('stats_years', '8'),
('stats_results', '98');

-- ---------------------------------------------------------------
-- pages (rich content editable from dashboard)
-- ---------------------------------------------------------------
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    body LONGTEXT,
    meta_description VARCHAR(300),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO pages (slug, title, body, meta_description) VALUES
('about', 'About Us',
'<p>The Alpine School — Haroonabad Campus is proud to be part of the Alpine School System, a growing network built on the motto <strong>"A Knowledge Gate Way"</strong>. Since opening our doors, we have been committed to shaping confident, capable learners through a balance of strong academics, character building, and modern skills.</p>
<p>Our campus at 43/A, Y-Block, Housing Colony, Haroonabad, brings together experienced teachers, a caring administration, and an environment where every child is encouraged to grow with <strong>Perfection, Progress, and Prosperity</strong>.</p>
<h3>Our Mission</h3>
<p>To provide quality, values-based education that equips every student with the knowledge, skills, and character needed to succeed in a changing world.</p>
<h3>Our Vision</h3>
<p>To be Haroonabad''s most trusted institution for academic excellence and holistic student development.</p>',
'About The Alpine School Haroonabad Campus — our mission, vision, and story.'),

('principal-message', 'Message from the Principal',
'<p>Welcome to The Alpine School, Haroonabad Campus. Every child who walks through our gates is met with the same promise: an education rooted in discipline, curiosity, and care.</p>
<p>Our team works every day to make sure students are not just prepared for exams, but for life — building strong character alongside strong academics. We invite you to visit our campus and see the Alpine difference for yourself.</p>
<p><strong>— Principal, The Alpine School (Haroonabad Campus)</strong></p>',
'A message from the Principal of The Alpine School, Haroonabad Campus.'),

('academics', 'Academics',
'<p>The Alpine School offers a structured academic journey from early years through secondary level, combining a strong curriculum with modern teaching methods and dedicated faculty support.</p>',
'Academic programs offered at The Alpine School, Haroonabad Campus.'),

('admissions', 'Admissions',
'<p>Admissions at The Alpine School, Haroonabad Campus are open for the new academic session. We welcome families who share our commitment to Perfection, Progress, and Prosperity.</p>',
'How to apply for admission at The Alpine School, Haroonabad Campus.');

-- ---------------------------------------------------------------
-- hero_slides
-- ---------------------------------------------------------------
CREATE TABLE hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(200),
    subtitle VARCHAR(300),
    button_text VARCHAR(100),
    button_link VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO hero_slides (image_path, title, subtitle, button_text, button_link, sort_order, is_active) VALUES
('uploads/gallery/campus-life-1.jpg', 'The Alpine School', 'Haroonabad Campus — Perfection, Progress, Prosperity', 'Apply for Admission', 'admissions.php', 1, 1),
('uploads/gallery/award-ceremony-1.jpg', 'Celebrating Every Achievement', 'Recognizing our students at every step of their journey', 'Learn More', 'about.php', 2, 1),
('uploads/gallery/cultural-day-1.jpg', 'A Knowledge Gate Way', 'Building character, culture, and confidence together', 'View Gallery', 'gallery.php', 3, 1);

-- ---------------------------------------------------------------
-- news
-- ---------------------------------------------------------------
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    excerpt VARCHAR(400),
    body LONGTEXT,
    image_path VARCHAR(255),
    is_published TINYINT(1) DEFAULT 1,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO news (title, slug, excerpt, body, image_path, is_published, published_at) VALUES
('Ramadan Kareem Activities at Alpine School', 'ramadan-kareem-activities', 'Our students took part in special Ramadan-themed activities focused on kindness, gratitude, and reflection.', '<p>Students across the campus took part in Ramadan-themed classroom activities, including Quran recitation, and lessons on kindness and gratitude, as part of our character-building program.</p>', 'uploads/gallery/islamic-studies-1.jpg', 1, '2026-03-10 10:00:00'),
('Annual Award Ceremony Recognizes Student Achievers', 'annual-award-ceremony', 'The Alpine School honored outstanding students for their academic and co-curricular achievements.', '<p>The Alpine School held its annual award ceremony, recognizing students for excellence in academics, discipline, and co-curricular participation. Parents and staff joined in celebrating the achievements of our young learners.</p>', 'uploads/gallery/award-ceremony-1.jpg', 1, '2026-02-20 10:00:00'),
('Cultural Day Celebrates Sindhi Heritage', 'cultural-day-sindhi-heritage', 'Students dressed in traditional attire and showcased Sindhi culture as part of our Cultural Day celebrations.', '<p>Our Cultural Day brought heritage to life, with students dressing in traditional Sindhi attire and learning about the rich cultural history of the region through a dedicated exhibition.</p>', 'uploads/gallery/cultural-day-1.jpg', 1, '2026-01-15 10:00:00');

-- ---------------------------------------------------------------
-- events
-- ---------------------------------------------------------------
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time VARCHAR(50),
    location VARCHAR(200),
    image_path VARCHAR(255)
) ENGINE=InnoDB;

INSERT INTO events (title, description, event_date, event_time, location, image_path) VALUES
('Annual Sports & Fun Day', 'A day full of sports, games, and fun activities for all students.', '2026-08-15', '9:00 AM - 2:00 PM', 'Alpine School Ground, Haroonabad', 'uploads/gallery/fun-day-1.jpg'),
('Mother''s Day Celebration', 'A special celebration honoring mothers with crafts and performances by students.', '2026-05-10', '10:00 AM - 12:00 PM', 'Alpine School Campus, Haroonabad', 'uploads/gallery/mothers-day-1.jpg'),
('New Session Orientation', 'Orientation session for new and returning students and their parents.', '2026-08-01', '9:00 AM - 11:00 AM', 'Alpine School Campus, Haroonabad', 'uploads/gallery/campus-life-1.jpg');

-- ---------------------------------------------------------------
-- gallery
-- ---------------------------------------------------------------
CREATE TABLE gallery_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO gallery_categories (name, slug) VALUES
('Islamic Studies Day', 'islamic-studies-day'),
('Award Ceremony', 'award-ceremony'),
('Mother''s Day', 'mothers-day'),
('Fun Day', 'fun-day'),
('Cultural Day', 'cultural-day'),
('Campus Life', 'campus-life');

CREATE TABLE gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255),
    sort_order INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO gallery_images (category_id, image_path, caption, sort_order) VALUES
(1, 'uploads/gallery/islamic-studies-1.jpg', 'Students during Ramadan activities', 1),
(1, 'uploads/gallery/islamic-studies-2.jpg', 'Quran recitation session', 2),
(2, 'uploads/gallery/award-ceremony-1.jpg', 'Students receiving awards from the Principal', 1),
(2, 'uploads/gallery/award-ceremony-2.jpg', 'Young achievers with their certificates', 2),
(3, 'uploads/gallery/mothers-day-1.jpg', 'Mother''s Day craft activity', 1),
(4, 'uploads/gallery/fun-day-1.jpg', 'Students enjoying Fun Day', 1),
(5, 'uploads/gallery/cultural-day-1.jpg', 'Students in traditional Sindhi attire', 1),
(6, 'uploads/gallery/campus-life-1.jpg', 'Students on a campus outing', 1);

-- ---------------------------------------------------------------
-- faculty
-- ---------------------------------------------------------------
CREATE TABLE faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    designation VARCHAR(150),
    department VARCHAR(150),
    photo_path VARCHAR(255),
    bio TEXT,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- inquiries (admissions form submissions)
-- ---------------------------------------------------------------
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(150) NOT NULL,
    parent_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(150),
    class_applying VARCHAR(100),
    message TEXT,
    status ENUM('new','contacted','enrolled') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- contact_messages
-- ---------------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(50),
    subject VARCHAR(200),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- page_views (public site traffic log)
-- ---------------------------------------------------------------
CREATE TABLE page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    referrer VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_views_created_at (created_at),
    INDEX idx_page_views_url (url)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- blogs
-- ---------------------------------------------------------------
CREATE TABLE blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    excerpt VARCHAR(400),
    body LONGTEXT,
    image_path VARCHAR(255),
    is_published TINYINT(1) DEFAULT 1,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- downloads
-- ---------------------------------------------------------------
CREATE TABLE downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- careers
-- ---------------------------------------------------------------
CREATE TABLE careers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location VARCHAR(150),
    job_type ENUM('full-time','part-time','contract') DEFAULT 'full-time',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- results
-- ---------------------------------------------------------------
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    class_name VARCHAR(100),
    file_path VARCHAR(255),
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- additional simple content pages (rendered like `about`/`academics`)
-- ---------------------------------------------------------------
INSERT INTO pages (slug, title, body, meta_description) VALUES
('director-message', 'Message from the Director',
'<p>Welcome to The Alpine School, Haroonabad Campus. As Director, I am proud of the community we have built — one that places equal value on academic rigor, character, and genuine care for every child.</p>
<p>Our vision is simple: to give every student the tools, confidence, and values they need to succeed well beyond the classroom. Thank you for trusting us with that journey.</p>
<p><strong>— Director, The Alpine School (Haroonabad Campus)</strong></p>',
'A message from the Director of The Alpine School, Haroonabad Campus.'),

('campus-life', 'Campus Life',
'<p>Life at The Alpine School goes far beyond textbooks. From cultural celebrations and sports days to Islamic studies activities and award ceremonies, our campus is alive with events that build confidence, community, and character.</p>
<p>Students take part in a rich calendar of co-curricular activities throughout the year, captured in our <a href="gallery.php">Gallery</a> and <a href="events.php">Events</a> pages.</p>',
'Explore campus life at The Alpine School, Haroonabad Campus — events, activities, and community.'),

('programs', 'Our Programs',
'<p>The Alpine School offers a structured academic journey from early years through secondary level, with programs designed to balance strong academics with character building and modern skills.</p>
<h3>Early Years</h3>
<p>A nurturing foundation focused on literacy, numeracy, and social development.</p>
<h3>Primary &amp; Middle School</h3>
<p>A broad curriculum building core subject strength alongside co-curricular exposure.</p>
<h3>Secondary School</h3>
<p>Exam-focused preparation paired with leadership and life-skills development.</p>',
'Academic programs offered at The Alpine School, Haroonabad Campus, from early years through secondary level.'),

('fee-structure', 'Fee Structure',
'<p>For detailed, up-to-date fee information for your child''s class, please contact our admissions office directly — fees vary by grade level and are reviewed annually.</p>
<p>Our team is happy to walk you through tuition, registration, and any available concessions during your visit.</p>',
'Fee structure information for The Alpine School, Haroonabad Campus. Contact admissions for current rates.'),

('facilities', 'Facilities',
'<p>The Alpine School campus is equipped to support learning both inside and outside the classroom.</p>
<h3>Learning Spaces</h3>
<p>Bright, well-ventilated classrooms designed for focused learning.</p>
<h3>Sports &amp; Play</h3>
<p>Dedicated outdoor space for sports, games, and physical education.</p>
<h3>Safety &amp; Care</h3>
<p>A secure, supervised campus with a caring administrative team.</p>',
'Facilities available at The Alpine School, Haroonabad Campus.'),

('achievements', 'Achievements',
'<p>The Alpine School takes pride in the accomplishments of its students — from academic excellence to co-curricular recognition at our annual award ceremonies.</p>
<p>Visit our <a href="news.php">News</a> page for recent highlights, or <a href="gallery.php">Gallery</a> to see our award ceremony photos.</p>',
'Student and school achievements at The Alpine School, Haroonabad Campus.'),

('privacy-policy', 'Privacy Policy',
'<p>The Alpine School respects the privacy of families who interact with this website. Information submitted through our admissions inquiry or contact forms — such as name, phone number, and email — is used solely to respond to your inquiry and is never sold or shared with third parties.</p>
<p>We take reasonable measures to protect the information you share with us. If you have questions about how your information is handled, please contact us using the details on our <a href="contact.php">Contact</a> page.</p>',
'Privacy Policy for The Alpine School, Haroonabad Campus website.'),

('terms', 'Terms & Conditions',
'<p>By using this website, you agree to use its content for personal, informational purposes only. Content, images, and branding on this site belong to The Alpine School, Haroonabad Campus, and may not be reproduced without permission.</p>
<p>Admission, enrollment, and fee terms are governed separately by the school''s official admissions agreement, available from our administration office.</p>',
'Terms and Conditions for The Alpine School, Haroonabad Campus website.');

-- ---------------------------------------------------------------
-- CMS modules: menus, departments, videos, notices, testimonials,
-- faqs, partners, newsletter + settings keys
-- ---------------------------------------------------------------
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL DEFAULT '#',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menu_items (id, parent_id, label, url, sort_order) VALUES
(1, NULL, 'Home', 'index.php', 1),
(2, NULL, 'About', '#', 2),
(3, NULL, 'Academics', '#', 3),
(4, NULL, 'Admissions', '#', 4),
(5, NULL, 'School Life', '#', 5),
(10, 2, 'About Us', 'about.php', 1),
(11, 2, 'Principal Message', 'principal-message.php', 2),
(12, 2, 'Director Message', 'director-message.php', 3),
(13, 2, 'Campus Life', 'campus-life.php', 4),
(14, 2, 'Facilities', 'facilities.php', 5),
(15, 2, 'Achievements', 'achievements.php', 6),
(20, 3, 'Academics', 'academics.php', 1),
(21, 3, 'Programs', 'programs.php', 2),
(22, 3, 'Results', 'results.php', 3),
(23, 3, 'Downloads', 'downloads.php', 4),
(30, 4, 'Admissions', 'admissions.php', 1),
(31, 4, 'Fee Structure', 'fee-structure.php', 2),
(40, 5, 'Gallery', 'gallery.php', 1),
(41, 5, 'Videos', 'videos.php', 2),
(42, 5, 'News', 'news.php', 3),
(43, 5, 'Blogs', 'blogs.php', 4),
(44, 5, 'Events', 'events.php', 5),
(45, 5, 'Faculty', 'faculty.php', 6),
(46, 5, 'FAQs', 'faqs.php', 7),
(47, 5, 'Career', 'career.php', 8);

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(400),
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO departments (name, description, sort_order) VALUES
('Early Years', 'Playgroup, Nursery and KG classes', 1),
('Primary', 'Grades 1 to 5', 2),
('Secondary', 'Grades 6 to 10', 3);

CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    youtube_url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    body TEXT,
    starts_at DATE NULL,
    ends_at DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    role VARCHAR(150),
    quote TEXT NOT NULL,
    photo_path VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(300) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    logo_path VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('favicon_path', ''),
('seo_meta_keywords', ''),
('seo_meta_description', ''),
('seo_google_analytics', ''),
('seo_robots', 'index, follow'),
('popup_enabled', '0'),
('popup_title', ''),
('popup_text', ''),
('popup_image', ''),
('popup_link', ''),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', ''),
('sms_api_key', ''),
('sms_sender_id', ''),
('whatsapp_api_key', ''),
('api_access_key', '')
ON DUPLICATE KEY UPDATE setting_value = setting_value;


-- ---------------------------------------------------------------
-- homepage builder sections
-- ---------------------------------------------------------------
CREATE TABLE homepage_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    settings TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO homepage_sections (section_key, label, is_active, sort_order, settings) VALUES
('hero', 'Hero Slider', 1, 1, '{}'),
('stats', 'Stats Bar', 1, 2, '{}'),
('about', 'About / Welcome', 1, 3, '{}'),
('why-us', 'Why Choose Us', 1, 4, '{}'),
('news', 'Latest News', 1, 5, '{}'),
('events', 'Upcoming Events', 1, 6, '{}'),
('testimonials', 'Testimonials', 1, 7, '{}'),
('partners', 'Partners & Affiliations', 1, 8, '{}'),
('cta', 'Admissions Call-to-Action', 1, 9, '{}');

-- theme builder settings
INSERT INTO settings (setting_key, setting_value) VALUES
('theme_font_heading', 'Poppins'),
('theme_font_body', 'Inter'),
('theme_header_style', 'light'),
('theme_footer_style', 'dark'),
('theme_btn_style', 'pill'),
('theme_btn_uppercase', '0'),
('theme_animations', '1'),
('theme_section_spacing', 'regular'),
('theme_radius', '14'),
('theme_shadow', 'soft')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- dynamic menu builder: menus + extended menu_items
CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus (id, name, slug) VALUES
(1, 'Header Menu', 'main'),
(2, 'Quick Links', 'footer-quick'),
(3, 'Explore', 'footer-explore');

ALTER TABLE menu_items
  ADD COLUMN menu_id INT NOT NULL DEFAULT 1 AFTER id,
  ADD COLUMN icon VARCHAR(50) NULL AFTER label,
  ADD COLUMN new_tab TINYINT(1) DEFAULT 0 AFTER url,
  ADD INDEX idx_menu_items_menu (menu_id);

UPDATE menu_items SET menu_id = 1;

INSERT INTO menu_items (menu_id, parent_id, label, url, sort_order) VALUES
(2, NULL, 'About Us', 'about.php', 1),
(2, NULL, 'Academics', 'academics.php', 2),
(2, NULL, 'Admissions', 'admissions.php', 3),
(2, NULL, 'Fee Structure', 'fee-structure.php', 4),
(2, NULL, 'Results', 'results.php', 5),
(2, NULL, 'Downloads', 'downloads.php', 6),
(2, NULL, 'Career', 'career.php', 7),
(3, NULL, 'Gallery', 'gallery.php', 1),
(3, NULL, 'News', 'news.php', 2),
(3, NULL, 'Blogs', 'blogs.php', 3),
(3, NULL, 'Events', 'events.php', 4),
(3, NULL, 'Search', 'search.php', 5),
(3, NULL, 'Privacy Policy', 'privacy-policy.php', 6),
(3, NULL, 'Terms & Conditions', 'terms.php', 7);

-- media manager: metadata + settings
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL UNIQUE,
    original_name VARCHAR(255),
    alt_text VARCHAR(300) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('media_compress', '1'),
('media_quality', '82'),
('media_webp', '1'),
('media_max_width', '1920')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- gallery albums
CREATE TABLE gallery_albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description VARCHAR(400),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE gallery_images
  ADD COLUMN album_id INT NULL AFTER category_id,
  ADD CONSTRAINT fk_gallery_images_album FOREIGN KEY (album_id) REFERENCES gallery_albums(id) ON DELETE SET NULL;

-- Seed: one album per existing category, adopting its images
INSERT INTO gallery_albums (category_id, name, slug, description)
SELECT id, name, CONCAT(slug, '-album'), CONCAT(name, ' photo collection') FROM gallery_categories;

UPDATE gallery_images gi
INNER JOIN gallery_albums ga ON ga.category_id = gi.category_id
SET gi.album_id = ga.id;

-- blog system: categories, tags, comments, authors, views
CREATE TABLE blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blog_categories (name, slug) VALUES
('School Life', 'school-life'),
('Parenting Tips', 'parenting-tips'),
('Study Skills', 'study-skills');

CREATE TABLE blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_post_tags (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blogs(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190),
    comment TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blogs(id) ON DELETE CASCADE,
    INDEX idx_blog_comments_post (post_id, is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE blogs
  ADD COLUMN category_id INT NULL AFTER slug,
  ADD COLUMN author_id INT NULL AFTER category_id,
  ADD COLUMN meta_description VARCHAR(300) DEFAULT '' AFTER excerpt,
  ADD COLUMN views INT DEFAULT 0 AFTER is_published,
  ADD INDEX idx_blogs_category (category_id);

-- SEO: canonical, OG, twitter, schema.org
INSERT INTO settings (setting_key, setting_value) VALUES
('seo_site_url', 'http://localhost/AlpineSchool'),
('seo_og_image', ''),
('seo_twitter_handle', ''),
('seo_org_type', 'School'),
('seo_founding_year', '2018')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- dynamic forms, contact settings, spam protection
CREATE TABLE forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description VARCHAR(400),
    success_message VARCHAR(400) DEFAULT 'Thank you! Your message has been received.',
    notify_emails VARCHAR(400) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    label VARCHAR(150) NOT NULL,
    field_key VARCHAR(60) NOT NULL,
    field_type ENUM('text','email','tel','number','textarea','select','checkbox','date') NOT NULL DEFAULT 'text',
    options VARCHAR(600) DEFAULT '',
    placeholder VARCHAR(200) DEFAULT '',
    is_required TINYINT(1) DEFAULT 0,
    half_width TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    data TEXT NOT NULL,
    ip VARCHAR(45),
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    INDEX idx_form_submissions (form_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO forms (id, name, slug, description, success_message, notify_emails) VALUES
(1, 'Contact Form', 'contact', 'Main contact form on the Contact page.', 'Thank you for reaching out! We will get back to you soon.', '');

INSERT INTO form_fields (form_id, label, field_key, field_type, placeholder, is_required, half_width, sort_order) VALUES
(1, 'Your Name', 'name', 'text', '', 1, 1, 1),
(1, 'Phone', 'phone', 'tel', '', 0, 1, 2),
(1, 'Email', 'email', 'email', '', 0, 1, 3),
(1, 'Subject', 'subject', 'text', '', 0, 1, 4),
(1, 'Message', 'message', 'textarea', 'How can we help you?', 1, 0, 5);

INSERT INTO settings (setting_key, setting_value) VALUES
('contact_map_lat', '29.6103'),
('contact_map_lng', '73.1385'),
('contact_notify_emails', ''),
('contact_float_whatsapp', '1'),
('contact_float_call', '1'),
('spam_min_seconds', '3'),
('spam_max_per_hour', '5'),
('spam_keywords', 'viagra, casino, crypto, loan offer, seo services')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- online admissions: applications + documents
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_number VARCHAR(30) NOT NULL UNIQUE,
    student_name VARCHAR(150) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other') NULL,
    class_applying VARCHAR(100) NOT NULL,
    previous_school VARCHAR(200),
    father_name VARCHAR(150) NOT NULL,
    father_occupation VARCHAR(150),
    father_cnic VARCHAR(30),
    mother_name VARCHAR(150),
    guardian_phone VARCHAR(50) NOT NULL,
    guardian_email VARCHAR(150),
    address VARCHAR(400),
    notes TEXT,
    status ENUM('submitted','under_review','shortlisted','approved','rejected','enrolled') NOT NULL DEFAULT 'submitted',
    admin_remarks TEXT,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    ip VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_applications_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    doc_type VARCHAR(60) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_size INT DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('admission_prefix', 'ALP'),
('admission_open', '1'),
('admission_session', '2026-27'),
('admission_notify_emails', ''),
('admission_instructions', 'Complete all sections and upload the required documents. You will receive an application number to track your status.')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- performance settings
INSERT INTO settings (setting_key, setting_value) VALUES
('perf_cache', '1'),
('perf_minify', '1'),
('perf_html_minify', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- homepage v2: quick-links, programs, principal, gallery-preview sections
INSERT INTO homepage_sections (section_key, label, is_active, sort_order, settings) VALUES
('quick-links', 'Quick Links Strip', 1, 2, '{}'),
('programs', 'Our Programs', 1, 6, '{}'),
('principal', "Principal's Message", 1, 7, '{}'),
('gallery-preview', 'Gallery Preview', 1, 10, '{}');

UPDATE homepage_sections SET sort_order = 1  WHERE section_key = 'hero';
UPDATE homepage_sections SET sort_order = 2  WHERE section_key = 'quick-links';
UPDATE homepage_sections SET sort_order = 3  WHERE section_key = 'stats';
UPDATE homepage_sections SET sort_order = 4  WHERE section_key = 'about';
UPDATE homepage_sections SET sort_order = 5  WHERE section_key = 'why-us';
UPDATE homepage_sections SET sort_order = 6  WHERE section_key = 'programs';
UPDATE homepage_sections SET sort_order = 7  WHERE section_key = 'principal';
UPDATE homepage_sections SET sort_order = 8  WHERE section_key = 'news';
UPDATE homepage_sections SET sort_order = 9  WHERE section_key = 'events';
UPDATE homepage_sections SET sort_order = 10 WHERE section_key = 'gallery-preview';
UPDATE homepage_sections SET sort_order = 11 WHERE section_key = 'testimonials';
UPDATE homepage_sections SET sort_order = 12 WHERE section_key = 'partners';
UPDATE homepage_sections SET sort_order = 13 WHERE section_key = 'cta';

-- Per-page content sections (Admin -> Pages -> Sections)
CREATE TABLE page_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id INT NOT NULL,
    section_key VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    settings LONGTEXT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO page_sections (page_id, section_key, label, settings, sort_order)
SELECT id, 'content', 'Main Content', JSON_OBJECT('heading', '', 'body', COALESCE(body, '')), 1
FROM pages
WHERE slug IN ('principal-message','director-message','campus-life','programs','fee-structure','facilities','achievements','privacy-policy','terms');
