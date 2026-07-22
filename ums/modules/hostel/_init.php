<?php
declare(strict_types=1);

/**
 * Hostel module — shared init.
 * Rooms (ums_hostel_rooms), student allotments (ums_hostel_allotments) and
 * hostel fee collections (ums_hostel_payments). Reads Students as residents.
 * Paid hostel fees are read by the Accounts module as "Hostel Fee" income
 * (Hostel is never coupled back to Accounts).
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'hostel_warden']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('hostel_rooms') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    block VARCHAR(40) NOT NULL DEFAULT "",
    room_no VARCHAR(30) NOT NULL,
    room_type ENUM("single","double","triple","dormitory") NOT NULL DEFAULT "double",
    capacity INT NOT NULL DEFAULT 2,
    monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM("active","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_block (block),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('hostel_allotments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    room_id INT NOT NULL,
    student_id INT NOT NULL,
    allotted_on DATE NOT NULL,
    vacated_on DATE NULL,
    monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM("active","vacated") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room (room_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('hostel_payments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    allotment_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    method ENUM("cash","bank","card","online","cheque") NOT NULL DEFAULT "cash",
    month VARCHAR(7) NOT NULL DEFAULT "",
    paid_on DATE NOT NULL,
    reference VARCHAR(80) NOT NULL DEFAULT "",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_allot (allotment_id),
    INDEX idx_paid (paid_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const HOSTEL_ROOM_TYPES = ['single' => 'Single', 'double' => 'Double', 'triple' => 'Triple', 'dormitory' => 'Dormitory'];
const HOSTEL_METHODS    = ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'card' => 'Card', 'online' => 'Online', 'cheque' => 'Cheque'];

if (!function_exists('money')) {
    function money(float $n): string { return 'Rs ' . number_format($n, 0); }
}

function hostel_room_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('hostel_rooms') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

function allot_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('hostel_allotments') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Active residents currently in a room. */
function hostel_room_occupied(int $roomId): int
{
    return (int)ums_db()->query('SELECT COUNT(*) c FROM ' . tbl('hostel_allotments') . ' WHERE room_id = ' . $roomId . ' AND status = "active"')->fetch_assoc()['c'];
}

/** Rooms with a free bed [id => "Block · Room (n/cap) — Rs fee"]. */
function hostel_available_rooms(int $campus): array
{
    $out = [];
    $sql = 'SELECT r.id, r.block, r.room_no, r.capacity, r.monthly_fee,
            (SELECT COUNT(*) FROM ' . tbl('hostel_allotments') . ' a WHERE a.room_id = r.id AND a.status = "active") occ
            FROM ' . tbl('hostel_rooms') . ' r
            WHERE r.campus_id = ? AND r.status = "active"
            HAVING occ < r.capacity ORDER BY r.block, r.room_no';
    $stmt = ums_db()->prepare($sql);
    $stmt->bind_param('i', $campus); $stmt->execute();
    $res = $stmt->get_result();
    while ($x = $res->fetch_assoc()) {
        $lbl = ($x['block'] !== '' ? $x['block'] . ' · ' : '') . 'Room ' . $x['room_no']
             . ' (' . (int)$x['occ'] . '/' . (int)$x['capacity'] . ') — ' . money((float)$x['monthly_fee']) . '/mo';
        $out[(int)$x['id']] = $lbl;
    }
    $stmt->close();
    return $out;
}

/** Students not already in an active allotment [id => "Name · Reg"]. */
function hostel_student_options(int $campus): array
{
    $out = [];
    try {
        $sql = 'SELECT s.id, s.name, s.registration_no FROM ' . tbl('students') . ' s
                WHERE s.campus_id = ? AND s.status = "active"
                AND s.id NOT IN (SELECT student_id FROM ' . tbl('hostel_allotments') . ' WHERE status = "active")
                ORDER BY s.name LIMIT 1000';
        $stmt = ums_db()->prepare($sql);
        $stmt->bind_param('i', $campus); $stmt->execute();
        $res = $stmt->get_result();
        while ($x = $res->fetch_assoc()) $out[(int)$x['id']] = $x['name'] . ' · ' . $x['registration_no'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

function hostel_url(string $path = ''): string
{
    return UMS_URL . '/modules/hostel/' . $path;
}
