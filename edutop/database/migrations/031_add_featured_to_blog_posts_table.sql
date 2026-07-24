ALTER TABLE blog_posts
    ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER comments_enabled;
