CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    excerpt VARCHAR(500) NULL,
    content LONGTEXT NULL,
    featured_image INT UNSIGNED NULL,
    author_id INT UNSIGNED NULL,
    status ENUM('draft', 'scheduled', 'published') NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    published_at DATETIME NULL,
    comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_blog_posts_status (status),
    CONSTRAINT fk_blog_posts_featured_image FOREIGN KEY (featured_image) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_blog_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
