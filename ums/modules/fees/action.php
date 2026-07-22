<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Fees — controller (challan CRUD + payments). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(fee_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function fee_formdata(int $campus): array
{
    $sid = (int)($_POST['student_id'] ?? 0);
    if ($sid && !array_key_exists($sid, fee_student_options($campus))) $sid = 0;
    $session = in_array($_POST['session'] ?? '', session_list(), true) ? $_POST['session'] : session_list()[0];
    $sem = max(1, min(8, (int)($_POST['semester'] ?? 1)));
    $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['due_date'] ?? '') ? $_POST['due_date'] : null;
    return [
        'student_id'   => $sid,
        'session'      => $session,
        'semester'     => $sem,
        'title'        => trim((string)($_POST['title'] ?? '')),
        'total_amount' => max(0, (float)($_POST['total_amount'] ?? 0)),
        'discount'     => max(0, (float)($_POST['discount'] ?? 0)),
        'fine'         => max(0, (float)($_POST['fine'] ?? 0)),
        'due_date'     => $due,
        'remarks'      => trim((string)($_POST['remarks'] ?? '')),
    ];
}

if ($action === 'create') {
    $f = fee_formdata($campus);
    if ($f['student_id'] === 0 || $f['total_amount'] <= 0) { flash_set('error', 'Student and a positive amount are required.'); redirect(fee_url('create.php')); }

    $stmt = $db->prepare('INSERT INTO ' . tbl('fee_challans') . '
        (campus_id, student_id, session, semester, title, total_amount, discount, fine, due_date, remarks)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iisisdddss', $campus, $f['student_id'], $f['session'], $f['semester'], $f['title'], $f['total_amount'], $f['discount'], $f['fine'], $f['due_date'], $f['remarks']);
    $stmt->execute(); $id = $stmt->insert_id; $stmt->close();

    $no = 'CH-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    $u = $db->prepare('UPDATE ' . tbl('fee_challans') . ' SET challan_no = ? WHERE id = ?');
    $u->bind_param('si', $no, $id); $u->execute(); $u->close();

    ums_log('fee_challan_create', "Created challan $no");
    flash_set('success', "Challan $no created.");
    redirect(fee_url('view.php?id=' . $id));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = fee_find($id);
    if (!$cur) { flash_set('error', 'Challan not found.'); redirect(fee_url('index.php')); }
    $f = fee_formdata($campus);
    if ($f['student_id'] === 0 || $f['total_amount'] <= 0) { flash_set('error', 'Student and a positive amount are required.'); redirect(fee_url('edit.php?id=' . $id)); }

    // recompute status against existing paid amount
    $net = $f['total_amount'] - $f['discount'] + $f['fine'];
    $status = fee_status($net, (float)$cur['paid_amount']);
    $stmt = $db->prepare('UPDATE ' . tbl('fee_challans') . '
        SET student_id=?, session=?, semester=?, title=?, total_amount=?, discount=?, fine=?, due_date=?, remarks=?, status=? WHERE id=?');
    $stmt->bind_param('isisdddsssi', $f['student_id'], $f['session'], $f['semester'], $f['title'], $f['total_amount'], $f['discount'], $f['fine'], $f['due_date'], $f['remarks'], $status, $id);
    $stmt->execute(); $stmt->close();

    ums_log('fee_challan_update', 'Updated challan #' . $id);
    flash_set('success', 'Challan updated.');
    redirect(fee_url('view.php?id=' . $id));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = fee_find($id);
    if ($cur) {
        $db->query('DELETE FROM ' . tbl('fee_payments') . ' WHERE challan_id = ' . $id);
        $stmt = $db->prepare('DELETE FROM ' . tbl('fee_challans') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('fee_challan_delete', 'Deleted challan ' . $cur['challan_no']);
        flash_set('success', 'Challan and its payments deleted.');
    }
    redirect(fee_url('index.php'));
}

if ($action === 'add_payment') {
    $challanId = (int)($_POST['challan_id'] ?? 0);
    $c = fee_find($challanId);
    if (!$c || (int)$c['campus_id'] !== $campus) { flash_set('error', 'Challan not found.'); redirect(fee_url('index.php')); }

    $amount = round(max(0, (float)($_POST['amount'] ?? 0)), 2);
    $method = array_key_exists($_POST['method'] ?? '', FEE_METHODS) ? $_POST['method'] : 'cash';
    $paidOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['paid_on'] ?? '') ? $_POST['paid_on'] : date('Y-m-d');
    $ref    = trim((string)($_POST['reference'] ?? ''));
    $balance = fee_balance($c);

    if ($amount <= 0) { flash_set('error', 'Enter a payment amount.'); redirect(fee_url('view.php?id=' . $challanId)); }
    if ($amount > $balance + 0.001) { flash_set('error', 'Amount exceeds the outstanding balance (' . money($balance) . ').'); redirect(fee_url('view.php?id=' . $challanId)); }

    $sid = (int)$c['student_id']; $uid = (int)$user['id'];
    $stmt = $db->prepare('INSERT INTO ' . tbl('fee_payments') . '
        (campus_id, challan_id, student_id, amount, method, paid_on, reference, received_by)
        VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iiidsssi', $campus, $challanId, $sid, $amount, $method, $paidOn, $ref, $uid);
    $stmt->execute(); $stmt->close();

    fee_recalc($db, $challanId);
    ums_log('fee_payment', money($amount) . " received on challan {$c['challan_no']}");
    flash_set('success', money($amount) . ' payment recorded.');
    redirect(fee_url('view.php?id=' . $challanId));
}

if ($action === 'delete_payment') {
    $pid = (int)($_POST['payment_id'] ?? 0);
    $challanId = (int)($_POST['challan_id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM ' . tbl('fee_payments') . ' WHERE id = ? AND campus_id = ?');
    $stmt->bind_param('ii', $pid, $campus); $stmt->execute(); $stmt->close();
    fee_recalc($db, $challanId);
    ums_log('fee_payment_delete', "Deleted payment #$pid");
    flash_set('success', 'Payment removed.');
    redirect(fee_url('view.php?id=' . $challanId));
}

redirect(fee_url('index.php'));
