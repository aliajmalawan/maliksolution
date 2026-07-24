<?php
declare(strict_types=1);

/**
 * Phase 1 foundation schema — auth + settings + audit only.
 * Business-module tables (students, courses, fees…) arrive with their
 * own phases. Idempotent: safe to run on every request.
 *
 * Multi-campus/SaaS ready: every future table carries campus_id; the
 * campuses table exists from day one with a default campus.
 */

function ums_migrate(): void
{
    $db = ums_db();

    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('campuses') . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        code VARCHAR(20) NOT NULL UNIQUE,
        address VARCHAR(255) DEFAULT "",
        status ENUM("active","inactive") NOT NULL DEFAULT "active",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('users') . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campus_id INT NOT NULL DEFAULT 1,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM("super_admin","admin","teacher","student","accountant","librarian") NOT NULL DEFAULT "admin",
        status ENUM("active","suspended") NOT NULL DEFAULT "active",
        last_login DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_campus_role (campus_id, role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // Additive link: a UMS login can belong to a student (Students/Admissions
    // modules read/write this; core users schema otherwise untouched).
    $has = $db->query("SHOW COLUMNS FROM " . tbl('users') . " LIKE 'student_id'");
    if ($has && $has->num_rows === 0) {
        $db->query('ALTER TABLE ' . tbl('users') . ' ADD COLUMN student_id INT NULL AFTER campus_id, ADD UNIQUE KEY uniq_student (student_id)');
    }

    // Additive link: a UMS login can belong to a teacher (Teachers module reads/writes this).
    $has = $db->query("SHOW COLUMNS FROM " . tbl('users') . " LIKE 'teacher_id'");
    if ($has && $has->num_rows === 0) {
        $db->query('ALTER TABLE ' . tbl('users') . ' ADD COLUMN teacher_id INT NULL AFTER student_id, ADD UNIQUE KEY uniq_teacher (teacher_id)');
    }

    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('settings') . ' (
        name VARCHAR(100) PRIMARY KEY,
        value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('activity_log') . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL DEFAULT 0,
        user_name VARCHAR(120) DEFAULT "",
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip VARCHAR(45) DEFAULT "",
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // "Remember me" — selector/validator pattern (never store the raw validator)
    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('remember_tokens') . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        selector VARCHAR(32) NOT NULL UNIQUE,
        validator_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // Password reset requests — single-use, time-limited, hashed tokens
    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('password_resets') . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    // ── Seed: default campus + first super admin (change password after login!)
    $row = $db->query('SELECT COUNT(*) c FROM ' . tbl('campuses'))->fetch_assoc();
    if ((int)$row['c'] === 0) {
        $stmt = $db->prepare('INSERT INTO ' . tbl('campuses') . ' (name, code, address) VALUES (?, ?, ?)');
        $name = 'Main Campus'; $code = 'MAIN'; $addr = 'Kehkashan Society, Malir Halt, Karachi';
        $stmt->bind_param('sss', $name, $code, $addr);
        $stmt->execute();
        $stmt->close();
    }

    $row = $db->query('SELECT COUNT(*) c FROM ' . tbl('users'))->fetch_assoc();
    if ((int)$row['c'] === 0) {
        $stmt = $db->prepare('INSERT INTO ' . tbl('users') . ' (campus_id, name, email, password_hash, role) VALUES (1, ?, ?, ?, "super_admin")');
        $name  = 'System Administrator';
        $email = 'admin@ums.local';
        $hash  = password_hash('Ums@2026', PASSWORD_DEFAULT);
        $stmt->bind_param('sss', $name, $email, $hash);
        $stmt->execute();
        $stmt->close();
    }
}
