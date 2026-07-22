<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Accounts — controller (add / delete manual transaction). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(acc_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

if ($action === 'create') {
    $type = ($_POST['type'] ?? 'expense') === 'income' ? 'income' : 'expense';
    $cats = $type === 'income' ? ACC_INCOME_CATS : ACC_EXPENSE_CATS;
    $category = in_array($_POST['category'] ?? '', $cats, true) ? $_POST['category'] : $cats[count($cats) - 1];
    $title  = trim((string)($_POST['title'] ?? ''));
    $amount = round(max(0, (float)($_POST['amount'] ?? 0)), 2);
    $method = array_key_exists($_POST['method'] ?? '', ACC_METHODS) ? $_POST['method'] : 'cash';
    $date   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['txn_date'] ?? '') ? $_POST['txn_date'] : date('Y-m-d');
    $ref    = trim((string)($_POST['reference'] ?? ''));
    $uid    = (int)$user['id'];

    if ($amount <= 0) { flash_set('error', 'Enter a positive amount.'); redirect(acc_url('create.php')); }

    $stmt = $db->prepare('INSERT INTO ' . tbl('transactions') . '
        (campus_id, type, category, title, amount, method, txn_date, reference, created_by)
        VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('isssdsssi', $campus, $type, $category, $title, $amount, $method, $date, $ref, $uid);
    $stmt->execute(); $stmt->close();

    ums_log('account_txn', ucfirst($type) . ' ' . money($amount) . ' · ' . $category);
    flash_set('success', ucfirst($type) . ' of ' . money($amount) . ' recorded.');
    redirect(acc_url('index.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = acc_find($id);
    if ($cur && (int)$cur['campus_id'] === $campus) {
        $stmt = $db->prepare('DELETE FROM ' . tbl('transactions') . ' WHERE id = ?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('account_txn_delete', 'Deleted transaction #' . $id);
        flash_set('success', 'Transaction removed.');
    }
    redirect(acc_url('index.php'));
}

redirect(acc_url('index.php'));
