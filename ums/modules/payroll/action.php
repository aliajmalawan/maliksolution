<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Payroll — controller (generate month · adjust · mark paid/unpaid · delete). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(pay_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

/** Generate payslips for every active teacher missing one for the month. */
if ($action === 'generate') {
    $month = (string)($_POST['month'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) { flash_set('error', 'Pick a valid month.'); redirect(pay_url('index.php')); }

    $teachers = [];
    try {
        $ts = $db->prepare('SELECT id, salary FROM ' . tbl('teachers') . ' WHERE campus_id = ? AND status = "active"');
        $ts->bind_param('i', $campus); $ts->execute();
        $teachers = $ts->get_result()->fetch_all(MYSQLI_ASSOC); $ts->close();
    } catch (Throwable $t) { flash_set('error', 'Teachers module not available.'); redirect(pay_url('index.php')); }

    $stmt = $db->prepare('INSERT IGNORE INTO ' . tbl('payslips') . '
        (campus_id, teacher_id, month, basic_salary, allowances, deductions, net_salary)
        VALUES (?,?,?,?,0,0,?)');
    $tid = 0; $basic = 0.0; $net = 0.0;
    $stmt->bind_param('iisdd', $campus, $tid, $month, $basic, $net);
    $n = 0;
    foreach ($teachers as $t) {
        $tid = (int)$t['id']; $basic = (float)$t['salary']; $net = $basic;
        $stmt->execute();
        $n += $stmt->affected_rows; // INSERT IGNORE → 0 if already existed
    }
    $stmt->close();

    ums_log('payroll_generate', "Generated $n payslip(s) for " . pay_month_label($month));
    flash_set('success', $n > 0 ? "$n payslip(s) generated for " . pay_month_label($month) . '.' : 'All active teachers already have a payslip for ' . pay_month_label($month) . '.');
    redirect(pay_url('index.php?month=' . $month));
}

/** Adjust allowances / deductions / remarks (recompute net). */
if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = pay_find($id);
    if (!$cur) { flash_set('error', 'Payslip not found.'); redirect(pay_url('index.php')); }

    $basic  = max(0, (float)($_POST['basic_salary'] ?? $cur['basic_salary']));
    $allow  = max(0, (float)($_POST['allowances'] ?? 0));
    $deduct = max(0, (float)($_POST['deductions'] ?? 0));
    $remarks = trim((string)($_POST['remarks'] ?? ''));
    $net = pay_net($basic, $allow, $deduct);

    $stmt = $db->prepare('UPDATE ' . tbl('payslips') . '
        SET basic_salary=?, allowances=?, deductions=?, net_salary=?, remarks=? WHERE id=?');
    $stmt->bind_param('ddddsi', $basic, $allow, $deduct, $net, $remarks, $id);
    $stmt->execute(); $stmt->close();

    ums_log('payroll_update', 'Adjusted payslip #' . $id);
    flash_set('success', 'Payslip updated.');
    redirect(pay_url('view.php?id=' . $id));
}

if ($action === 'mark_paid') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = pay_find($id);
    if (!$cur) { flash_set('error', 'Payslip not found.'); redirect(pay_url('index.php')); }
    $method = array_key_exists($_POST['method'] ?? '', PAY_METHODS) ? $_POST['method'] : 'bank';
    $paidOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['paid_on'] ?? '') ? $_POST['paid_on'] : date('Y-m-d');

    $stmt = $db->prepare('UPDATE ' . tbl('payslips') . ' SET status="paid", method=?, paid_on=? WHERE id=?');
    $stmt->bind_param('ssi', $method, $paidOn, $id); $stmt->execute(); $stmt->close();

    ums_log('payroll_paid', 'Paid salary ' . money((float)$cur['net_salary']) . ' · payslip #' . $id);
    flash_set('success', 'Salary marked as paid.');
    redirect(pay_url('view.php?id=' . $id));
}

if ($action === 'mark_unpaid') {
    $id = (int)($_POST['id'] ?? 0);
    if (pay_find($id)) {
        $db->query('UPDATE ' . tbl('payslips') . ' SET status="unpaid", paid_on=NULL WHERE id=' . $id);
        ums_log('payroll_unpaid', 'Reverted payslip #' . $id . ' to unpaid');
        flash_set('success', 'Payslip reverted to unpaid.');
    }
    redirect(pay_url('view.php?id=' . $id));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = pay_find($id);
    if ($cur) {
        $stmt = $db->prepare('DELETE FROM ' . tbl('payslips') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('payroll_delete', 'Deleted payslip #' . $id);
        flash_set('success', 'Payslip deleted.');
        redirect(pay_url('index.php?month=' . $cur['month']));
    }
    redirect(pay_url('index.php'));
}

redirect(pay_url('index.php'));
