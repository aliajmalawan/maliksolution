CREATE TABLE IF NOT EXISTS blog_post_seo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL UNIQUE,
    seo_title VARCHAR(190) NULL,
    meta_description VARCHAR(500) NULL,
    meta_keywords VARCHAR(500) NULL,
    canonical_url VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    og_title VARCHAR(190) NULL,
    og_description VARCHAR(500) NULL,
    twitter_card VARCHAR(50) NOT NULL DEFAULT 'summary_large_image',
    robots VARCHAR(100) NOT NULL DEFAULT 'index,follow',
    schema_markup LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    CONSTRAINT fk_blog_post_seo_post FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
