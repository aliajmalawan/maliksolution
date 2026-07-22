<?php
declare(strict_types=1);

/**
 * Library module — shared init.
 * Book catalog (ums_books) and issue/return records with late fines
 * (ums_book_issues). Reads Students as borrowers. Paid fines are read by
 * the Accounts module as "Library Fine" income (Library is not coupled back).
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'librarian']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('books') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL DEFAULT "",
    isbn VARCHAR(30) NOT NULL DEFAULT "",
    category VARCHAR(60) NOT NULL DEFAULT "General",
    publisher VARCHAR(150) NOT NULL DEFAULT "",
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    shelf VARCHAR(40) NOT NULL DEFAULT "",
    status ENUM("active","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cat (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('book_issues') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    fine DECIMAL(10,2) NOT NULL DEFAULT 0,
    fine_paid TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM("issued","returned","lost") NOT NULL DEFAULT "issued",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_book (book_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const LIB_CATEGORIES = ['General', 'Computer Science', 'Engineering', 'Business', 'Mathematics', 'Science', 'Literature', 'Reference', 'Fiction', 'Islamic Studies'];

if (!function_exists('money')) {
    function money(float $n): string { return 'Rs ' . number_format($n, 0); }
}

/** Late fine per day (from settings, default Rs 10). */
function lib_fine_rate(): float
{
    return (float)ums_setting('library_fine_per_day', '10');
}

/** Compute the fine for returning on $returnDate against $dueDate. */
function lib_fine(string $dueDate, string $returnDate): float
{
    $due = strtotime($dueDate); $ret = strtotime($returnDate);
    if (!$due || !$ret || $ret <= $due) return 0.0;
    $days = (int)ceil(($ret - $due) / 86400);
    return round($days * lib_fine_rate(), 2);
}

function book_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('books') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

function issue_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('book_issues') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Available books [id => "Title — Author (n left)"]. */
function lib_available_books(int $campus): array
{
    $out = [];
    $stmt = ums_db()->prepare('SELECT id, title, author, available_copies FROM ' . tbl('books') . ' WHERE campus_id = ? AND status = "active" AND available_copies > 0 ORDER BY title');
    $stmt->bind_param('i', $campus); $stmt->execute();
    $r = $stmt->get_result();
    while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['title'] . ($x['author'] ? ' — ' . $x['author'] : '') . ' (' . (int)$x['available_copies'] . ' left)';
    $stmt->close();
    return $out;
}

/** Students [id => "Name · Reg"]. */
function lib_student_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, name, registration_no FROM ' . tbl('students') . ' WHERE campus_id = ? AND status = "active" ORDER BY name LIMIT 1000');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['name'] . ' · ' . $x['registration_no'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

function lib_url(string $path = ''): string
{
    return UMS_URL . '/modules/library/' . $path;
}
