<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Hostel — controller (room CRUD + allot + vacate + collect fee). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(hostel_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function room_formdata(): array
{
    $type = array_key_exists($_POST['room_type'] ?? '', HOSTEL_ROOM_TYPES) ? $_POST['room_type'] : 'double';
    return [
        'block' => trim((string)($_POST['block'] ?? '')),
        'room_no' => trim((string)($_POST['room_no'] ?? '')),
        'room_type' => $type,
        'capacity' => max(1, (int)($_POST['capacity'] ?? 1)),
        'monthly_fee' => max(0.0, (float)($_POST['monthly_fee'] ?? 0)),
        'status' => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];
}

if ($action === 'create') {
    $f = room_formdata();
    if ($f['room_no'] === '') { flash_set('error', 'Room number is required.'); redirect(hostel_url('create.php')); }
    $stmt = $db->prepare('INSERT INTO ' . tbl('hostel_rooms') . '
        (campus_id, block, room_no, room_type, capacity, monthly_fee, status) VALUES (?,?,?,?,?,?,?)');
    $stmt->bind_param('isssids', $campus, $f['block'], $f['room_no'], $f['room_type'], $f['capacity'], $f['monthly_fee'], $f['status']);
    $stmt->execute(); $stmt->close();
    ums_log('room_create', 'Added room ' . $f['room_no']);
    flash_set('success', 'Room added.');
    redirect(hostel_url('index.php'));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = hostel_room_find($id);
    if (!$cur) { flash_set('error', 'Room not found.'); redirect(hostel_url('index.php')); }
    $f = room_formdata();
    if ($f['room_no'] === '') { flash_set('error', 'Room number is required.'); redirect(hostel_url('edit.php?id=' . $id)); }
    $occ = hostel_room_occupied($id);
    if ($f['capacity'] < $occ) { flash_set('error', "Capacity can't be below current occupancy ($occ)."); redirect(hostel_url('edit.php?id=' . $id)); }
    $stmt = $db->prepare('UPDATE ' . tbl('hostel_rooms') . '
        SET block=?, room_no=?, room_type=?, capacity=?, monthly_fee=?, status=? WHERE id=?');
    $stmt->bind_param('sssidsi', $f['block'], $f['room_no'], $f['room_type'], $f['capacity'], $f['monthly_fee'], $f['status'], $id);
    $stmt->execute(); $stmt->close();
    ums_log('room_update', 'Updated room #' . $id);
    flash_set('success', 'Room updated.');
    redirect(hostel_url('index.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = hostel_room_find($id);
    if ($cur) {
        if (hostel_room_occupied($id) > 0) { flash_set('error', 'Cannot delete — the room has active residents.'); redirect(hostel_url('index.php')); }
        $db->query('DELETE FROM ' . tbl('hostel_allotments') . ' WHERE room_id = ' . $id);
        $stmt = $db->prepare('DELETE FROM ' . tbl('hostel_rooms') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('room_delete', 'Deleted room ' . $cur['room_no']);
        flash_set('success', 'Room deleted.');
    }
    redirect(hostel_url('index.php'));
}

if ($action === 'allot') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $stuId  = (int)($_POST['student_id'] ?? 0);
    $room = hostel_room_find($roomId);
    if (!$room || $room['status'] !== 'active') { flash_set('error', 'Select a valid room.'); redirect(hostel_url('allot.php')); }
    if (hostel_room_occupied($roomId) >= (int)$room['capacity']) { flash_set('error', 'That room is full.'); redirect(hostel_url('allot.php')); }
    if ($stuId <= 0 || !array_key_exists($stuId, hostel_student_options($campus))) { flash_set('error', 'Select a valid, unallotted student.'); redirect(hostel_url('allot.php')); }
    $date = date('Y-m-d'); $fee = (float)$room['monthly_fee'];
    $stmt = $db->prepare('INSERT INTO ' . tbl('hostel_allotments') . '
        (campus_id, room_id, student_id, allotted_on, monthly_fee) VALUES (?,?,?,?,?)');
    $stmt->bind_param('iiisd', $campus, $roomId, $stuId, $date, $fee);
    $stmt->execute(); $stmt->close();
    ums_log('hostel_allot', "Allotted student #$stuId to room {$room['room_no']}");
    flash_set('success', 'Room allotted.');
    redirect(hostel_url('allot.php'));
}

if ($action === 'vacate') {
    $id = (int)($_POST['id'] ?? 0);
    $a = allot_find($id);
    if (!$a || $a['status'] !== 'active') { flash_set('error', 'Allotment not found.'); redirect(hostel_url('allot.php')); }
    $stmt = $db->prepare('UPDATE ' . tbl('hostel_allotments') . ' SET status="vacated", vacated_on=? WHERE id=?');
    $today = date('Y-m-d');
    $stmt->bind_param('si', $today, $id); $stmt->execute(); $stmt->close();
    ums_log('hostel_vacate', 'Vacated allotment #' . $id);
    flash_set('success', 'Room vacated.');
    redirect(hostel_url('allot.php'));
}

if ($action === 'collect') {
    $id = (int)($_POST['allotment_id'] ?? 0);
    $a = allot_find($id);
    if (!$a) { flash_set('error', 'Allotment not found.'); redirect(hostel_url('allot.php')); }
    $amount = max(0.0, (float)($_POST['amount'] ?? 0));
    if ($amount <= 0) { flash_set('error', 'Enter a valid amount.'); redirect(hostel_url('allot.php')); }
    $method = array_key_exists($_POST['method'] ?? '', HOSTEL_METHODS) ? $_POST['method'] : 'cash';
    $month  = preg_match('/^\d{4}-\d{2}$/', $_POST['month'] ?? '') ? $_POST['month'] : date('Y-m');
    $ref    = trim((string)($_POST['reference'] ?? ''));
    $paidOn = date('Y-m-d');
    $stuId  = (int)$a['student_id'];
    $stmt = $db->prepare('INSERT INTO ' . tbl('hostel_payments') . '
        (campus_id, allotment_id, student_id, amount, method, month, paid_on, reference) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iiidssss', $campus, $id, $stuId, $amount, $method, $month, $paidOn, $ref);
    $stmt->execute(); $stmt->close();
    ums_log('hostel_collect', 'Hostel fee ' . money($amount) . " for allotment #$id");
    flash_set('success', 'Payment of ' . money($amount) . ' recorded.');
    redirect(hostel_url('allot.php'));
}

redirect(hostel_url('index.php'));
