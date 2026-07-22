<?php
declare(strict_types=1);

/**
 * Accounts module — shared init.
 * A financial ledger: manual income/expense entries (ums_transactions)
 * PLUS fee collections pulled read-only from the Fees module as income.
 * The Fees module is never modified — Accounts simply unions its payments
 * into the ledger and summaries. Payroll will feed salary expenses later.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin', 'accountant']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('transactions') . ' (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus_id INT NOT NULL DEFAULT 1,
    type ENUM("income","expense") NOT NULL DEFAULT "expense",
    category VARCHAR(60) NOT NULL DEFAULT "Other",
    title VARCHAR(180) NOT NULL DEFAULT "",
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    method ENUM("cash","bank","card","online","cheque") NOT NULL DEFAULT "cash",
    txn_date DATE NOT NULL,
    reference VARCHAR(80) NOT NULL DEFAULT "",
    created_by INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_date (txn_date),
    INDEX idx_cat (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

const ACC_METHODS = ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'card' => 'Card', 'online' => 'Online', 'cheque' => 'Cheque'];
const ACC_INCOME_CATS  = ['Fee Collection', 'Donation', 'Grant', 'Rent Income', 'Other Income'];
const ACC_EXPENSE_CATS = ['Salaries', 'Utilities', 'Rent', 'Supplies', 'Maintenance', 'Transport', 'Marketing', 'Examination', 'Other Expense'];

if (!function_exists('money')) {
    function money(float $n): string { return 'Rs ' . number_format($n, 0); }
}

function acc_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('transactions') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Does the Fees module's payments table exist? (for read-only income union) */
function acc_has_fees(): bool
{
    try { return (bool)ums_db()->query("SHOW TABLES LIKE '" . tbl('fee_payments') . "'")->num_rows; }
    catch (Throwable $t) { return false; }
}

/** Does the Payroll module's payslips table exist? (for read-only expense union) */
function acc_has_payroll(): bool
{
    try { return (bool)ums_db()->query("SHOW TABLES LIKE '" . tbl('payslips') . "'")->num_rows; }
    catch (Throwable $t) { return false; }
}

/** Does the Library module's issues table exist? (for read-only fine income union) */
function acc_has_library(): bool
{
    try { return (bool)ums_db()->query("SHOW TABLES LIKE '" . tbl('book_issues') . "'")->num_rows; }
    catch (Throwable $t) { return false; }
}

/** Does the Hostel module's payments table exist? (for read-only fee income union) */
function acc_has_hostel(): bool
{
    try { return (bool)ums_db()->query("SHOW TABLES LIKE '" . tbl('hostel_payments') . "'")->num_rows; }
    catch (Throwable $t) { return false; }
}

/** Does the Transport module's payments table exist? (for read-only fee income union) */
function acc_has_transport(): bool
{
    try { return (bool)ums_db()->query("SHOW TABLES LIKE '" . tbl('transport_payments') . "'")->num_rows; }
    catch (Throwable $t) { return false; }
}

/**
 * Totals for a date window (or all-time). Income = manual income +
 * fee collections; Expense = manual expense. Returns [income, expense, net].
 */
function acc_totals(mysqli $db, int $campus, string $from = '', string $to = ''): array
{
    $cond = 'campus_id = ' . $campus;
    if ($from !== '') $cond .= " AND txn_date >= '" . $db->real_escape_string($from) . "'";
    if ($to !== '')   $cond .= " AND txn_date <= '" . $db->real_escape_string($to) . "'";

    $r = $db->query('SELECT
        COALESCE(SUM(CASE WHEN type="income" THEN amount END),0) inc,
        COALESCE(SUM(CASE WHEN type="expense" THEN amount END),0) exp
        FROM ' . tbl('transactions') . " WHERE $cond")->fetch_assoc();
    $income = (float)$r['inc']; $expense = (float)$r['exp'];

    if (acc_has_fees()) {
        $fcond = 'campus_id = ' . $campus;
        if ($from !== '') $fcond .= " AND paid_on >= '" . $db->real_escape_string($from) . "'";
        if ($to !== '')   $fcond .= " AND paid_on <= '" . $db->real_escape_string($to) . "'";
        $income += (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('fee_payments') . " WHERE $fcond")->fetch_assoc()['s'];
    }
    // Paid library fines count as income
    if (acc_has_library()) {
        $lcond = "campus_id = $campus AND fine > 0 AND fine_paid = 1";
        if ($from !== '') $lcond .= " AND return_date >= '" . $db->real_escape_string($from) . "'";
        if ($to !== '')   $lcond .= " AND return_date <= '" . $db->real_escape_string($to) . "'";
        $income += (float)$db->query('SELECT COALESCE(SUM(fine),0) s FROM ' . tbl('book_issues') . " WHERE $lcond")->fetch_assoc()['s'];
    }
    // Hostel fee collections count as income
    if (acc_has_hostel()) {
        $hcond = 'campus_id = ' . $campus;
        if ($from !== '') $hcond .= " AND paid_on >= '" . $db->real_escape_string($from) . "'";
        if ($to !== '')   $hcond .= " AND paid_on <= '" . $db->real_escape_string($to) . "'";
        $income += (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('hostel_payments') . " WHERE $hcond")->fetch_assoc()['s'];
    }
    // Transport fee collections count as income
    if (acc_has_transport()) {
        $tcond = 'campus_id = ' . $campus;
        if ($from !== '') $tcond .= " AND paid_on >= '" . $db->real_escape_string($from) . "'";
        if ($to !== '')   $tcond .= " AND paid_on <= '" . $db->real_escape_string($to) . "'";
        $income += (float)$db->query('SELECT COALESCE(SUM(amount),0) s FROM ' . tbl('transport_payments') . " WHERE $tcond")->fetch_assoc()['s'];
    }
    // Paid salaries from Payroll count as Salary expense
    if (acc_has_payroll()) {
        $pcond = "campus_id = $campus AND status = 'paid'";
        if ($from !== '') $pcond .= " AND paid_on >= '" . $db->real_escape_string($from) . "'";
        if ($to !== '')   $pcond .= " AND paid_on <= '" . $db->real_escape_string($to) . "'";
        $expense += (float)$db->query('SELECT COALESCE(SUM(net_salary),0) s FROM ' . tbl('payslips') . " WHERE $pcond")->fetch_assoc()['s'];
    }
    return [$income, $expense, $income - $expense];
}

function acc_url(string $path = ''): string
{
    return UMS_URL . '/modules/accounts/' . $path;
}
