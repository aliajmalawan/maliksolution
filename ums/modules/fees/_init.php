<?php
declare(strict_types=1);

/**
 * Fee Management module — shared init.
 * Fee challans (invoices) issued to students, and payments recorded
 * against them (supports partial payment). Owns ums_fee_challans +
 * ums_fee_payments. Reads Students. Feeds the Accounts module later.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'accountant']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('fee_challans') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    student_id INT NOT NULL,
    challan_no VARCHAR(30) NOT NULL DEFAULT "",
    session VARCHAR(40) NOT NULL DEFAULT "",
    semester TINYINT NOT NULL DEFAULT 1,
    title VARCHAR(160) NOT NULL DEFAULT "",
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    fine DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_date DATE NULL,
    remarks VARCHAR(255) NOT NULL DEFAULT "",
    status ENUM("unpaid","partial","paid") NOT NULL DEFAULT "unpaid",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_session (session)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('fee_payments') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    challan_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    method ENUM("cash","bank","card","online","cheque") NOT NULL DEFAULT "cash",
    paid_on DATE NOT NULL,
    reference VARCHAR(80) NOT NULL DEFAULT "",
    received_by INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_challan (challan_id),
    INDEX idx_paid_on (paid_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const FEE_METHODS = ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'card' => 'Card', 'online' => 'Online', 'cheque' => 'Cheque'];
const FEE_STATUS  = [
    'unpaid'  => ['Unpaid',  'st-rejected'],
    'partial' => ['Partial', 'st-pending'],
    'paid'    => ['Paid',    'st-approved'],
];

/** Net payable = total − discount + fine. */
function fee_net(array $c): float
{
    return (float)$c['total_amount'] - (float)$c['discount'] + (float)$c['fine'];
}

/** Remaining balance on a challan. */
function fee_balance(array $c): float
{
    return round(fee_net($c) - (float)$c['paid_amount'], 2);
}

/** Derive status from net + paid. */
function fee_status(float $net, float $paid): string
{
    if ($paid <= 0.001) return 'unpaid';
    if ($paid >= $net - 0.001) return 'paid';
    return 'partial';
}

/** Format money. */
function money(float $n): string
{
    return 'Rs ' . number_format($n, 0);
}

function fee_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('fee_challans') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Recompute + persist paid_amount and status from the payments table. */
function fee_recalc(mysqli $db, int $challanId): void
{
    $c = fee_find($challanId);
    if (!$c) return;
    $sum = 0.0;
    $q = $db->prepare('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('fee_payments') . ' WHERE challan_id = ?');
    $q->bind_param('i', $challanId); $q->execute();
    $sum = (float)$q->get_result()->fetch_assoc()['s']; $q->close();
    $status = fee_status(fee_net($c), $sum);
    $u = $db->prepare('UPDATE ' . tbl('fee_challans') . ' SET paid_amount = ?, status = ? WHERE id = ?');
    $u->bind_param('dsi', $sum, $status, $challanId); $u->execute(); $u->close();
}

/** Students [id => "Name · Reg"] for pickers. */
function fee_student_options(int $campus): array
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

function fee_url(string $path = ''): string
{
    return UMS_URL . '/modules/fees/' . $path;
}
