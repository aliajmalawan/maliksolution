<?php
declare(strict_types=1);

/**
 * HR & Payroll module — shared init.
 * Monthly salary slips for staff (teachers). Basic salary comes from the
 * teacher record; allowances/deductions are adjustable per slip. Owns
 * ums_payslips. Paid slips are read by the Accounts module as "Salaries"
 * expenses (the Accounts module reads this table; Payroll is not coupled
 * back to it).
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'accountant']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('payslips') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    teacher_id INT NOT NULL,
    month CHAR(7) NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    allowances DECIMAL(12,2) NOT NULL DEFAULT 0,
    deductions DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM("unpaid","paid") NOT NULL DEFAULT "unpaid",
    method ENUM("cash","bank","card","online","cheque") NOT NULL DEFAULT "bank",
    paid_on DATE NULL,
    remarks VARCHAR(255) NOT NULL DEFAULT "",
    created_by INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_slip (teacher_id, month),
    INDEX idx_month (month),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const PAY_METHODS = ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'card' => 'Card', 'online' => 'Online', 'cheque' => 'Cheque'];
const PAY_STATUS  = ['unpaid' => ['Unpaid', 'st-pending'], 'paid' => ['Paid', 'st-approved']];

if (!function_exists('money')) {
    function money(float $n): string { return 'Rs ' . number_format($n, 0); }
}

/** Net = basic + allowances − deductions. */
function pay_net(float $basic, float $allow, float $deduct): float
{
    return round($basic + $allow - $deduct, 2);
}

/** A month string like "2026-07" → "July 2026". */
function pay_month_label(string $m): string
{
    $ts = strtotime($m . '-01');
    return $ts ? date('F Y', $ts) : $m;
}

function pay_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('payslips') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

function pay_url(string $path = ''): string
{
    return UMS_URL . '/modules/payroll/' . $path;
}
