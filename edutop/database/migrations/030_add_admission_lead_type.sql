ALTER TABLE leads MODIFY type ENUM('contact','demo','admission') NOT NULL DEFAULT 'contact';
