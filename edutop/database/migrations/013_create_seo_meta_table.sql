CREATE TABLE IF NOT EXISTS seo_meta (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL UNIQUE,
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
    CONSTRAINT fk_seo_meta_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
