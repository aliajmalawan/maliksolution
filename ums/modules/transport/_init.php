<?php
declare(strict_types=1);

/**
 * Transport module — shared init.
 * Routes/vehicles (ums_transport_routes), student route assignments
 * (ums_transport_assignments) and transport fee collections
 * (ums_transport_payments). Reads Students as passengers. Paid transport
 * fees are read by the Accounts module as "Transport Fee" income
 * (Transport is never coupled back to Accounts).
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'transport_incharge']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('transport_routes') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    route_name VARCHAR(80) NOT NULL,
    vehicle_no VARCHAR(30) NOT NULL DEFAULT "",
    driver_name VARCHAR(80) NOT NULL DEFAULT "",
    driver_phone VARCHAR(30) NOT NULL DEFAULT "",
    stops VARCHAR(400) NOT NULL DEFAULT "",
    capacity INT NOT NULL DEFAULT 20,
    monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM("active","inactive") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('transport_assignments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    route_id INT NOT NULL,
    student_id INT NOT NULL,
    stop VARCHAR(120) NOT NULL DEFAULT "",
    assigned_on DATE NOT NULL,
    ended_on DATE NULL,
    monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM("active","ended") NOT NULL DEFAULT "active",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_route (route_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('transport_payments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    method ENUM("cash","bank","card","online","cheque") NOT NULL DEFAULT "cash",
    month VARCHAR(7) NOT NULL DEFAULT "",
    paid_on DATE NOT NULL,
    reference VARCHAR(80) NOT NULL DEFAULT "",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assign (assignment_id),
    INDEX idx_paid (paid_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const TRANSPORT_METHODS = ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'card' => 'Card', 'online' => 'Online', 'cheque' => 'Cheque'];

if (!function_exists('money')) {
    function money(float $n): string { return 'Rs ' . number_format($n, 0); }
}

function transport_route_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('transport_routes') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

function tassign_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('transport_assignments') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Active passengers currently on a route. */
function transport_route_occupied(int $routeId): int
{
    return (int)ums_db()->query('SELECT COUNT(*) c FROM ' . tbl('transport_assignments') . ' WHERE route_id = ' . $routeId . ' AND status = "active"')->fetch_assoc()['c'];
}

/** Routes with a free seat [id => "Route · Vehicle (n/cap) — Rs fee/mo"]. */
function transport_available_routes(int $campus): array
{
    $out = [];
    $sql = 'SELECT r.id, r.route_name, r.vehicle_no, r.capacity, r.monthly_fee,
            (SELECT COUNT(*) FROM ' . tbl('transport_assignments') . ' a WHERE a.route_id = r.id AND a.status = "active") occ
            FROM ' . tbl('transport_routes') . ' r
            WHERE r.campus_id = ? AND r.status = "active"
            HAVING occ < r.capacity ORDER BY r.route_name';
    $stmt = ums_db()->prepare($sql);
    $stmt->bind_param('i', $campus); $stmt->execute();
    $res = $stmt->get_result();
    while ($x = $res->fetch_assoc()) {
        $lbl = $x['route_name'] . ($x['vehicle_no'] ? ' · ' . $x['vehicle_no'] : '')
             . ' (' . (int)$x['occ'] . '/' . (int)$x['capacity'] . ') — ' . money((float)$x['monthly_fee']) . '/mo';
        $out[(int)$x['id']] = $lbl;
    }
    $stmt->close();
    return $out;
}

/** Students not already on an active route [id => "Name · Reg"]. */
function transport_student_options(int $campus): array
{
    $out = [];
    try {
        $sql = 'SELECT s.id, s.name, s.registration_no FROM ' . tbl('students') . ' s
                WHERE s.campus_id = ? AND s.status = "active"
                AND s.id NOT IN (SELECT student_id FROM ' . tbl('transport_assignments') . ' WHERE status = "active")
                ORDER BY s.name LIMIT 1000';
        $stmt = ums_db()->prepare($sql);
        $stmt->bind_param('i', $campus); $stmt->execute();
        $res = $stmt->get_result();
        while ($x = $res->fetch_assoc()) $out[(int)$x['id']] = $x['name'] . ' · ' . $x['registration_no'];
        $stmt->close();
    } catch (Throwable $t) {}
    return $out;
}

function transport_url(string $path = ''): string
{
    return UMS_URL . '/modules/transport/' . $path;
}
