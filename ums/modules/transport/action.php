<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Transport — controller (route CRUD + assign + end + collect fee). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(transport_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function route_formdata(): array
{
    return [
        'route_name' => trim((string)($_POST['route_name'] ?? '')),
        'vehicle_no' => trim((string)($_POST['vehicle_no'] ?? '')),
        'driver_name' => trim((string)($_POST['driver_name'] ?? '')),
        'driver_phone' => trim((string)($_POST['driver_phone'] ?? '')),
        'stops' => trim((string)($_POST['stops'] ?? '')),
        'capacity' => max(1, (int)($_POST['capacity'] ?? 1)),
        'monthly_fee' => max(0.0, (float)($_POST['monthly_fee'] ?? 0)),
        'status' => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];
}

if ($action === 'create') {
    $f = route_formdata();
    if ($f['route_name'] === '') { flash_set('error', 'Route name is required.'); redirect(transport_url('create.php')); }
    $stmt = $db->prepare('INSERT INTO ' . tbl('transport_routes') . '
        (campus_id, route_name, vehicle_no, driver_name, driver_phone, stops, capacity, monthly_fee, status)
        VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('isssssids', $campus, $f['route_name'], $f['vehicle_no'], $f['driver_name'], $f['driver_phone'], $f['stops'], $f['capacity'], $f['monthly_fee'], $f['status']);
    $stmt->execute(); $stmt->close();
    ums_log('route_create', 'Added route ' . $f['route_name']);
    flash_set('success', 'Route added.');
    redirect(transport_url('index.php'));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = transport_route_find($id);
    if (!$cur) { flash_set('error', 'Route not found.'); redirect(transport_url('index.php')); }
    $f = route_formdata();
    if ($f['route_name'] === '') { flash_set('error', 'Route name is required.'); redirect(transport_url('edit.php?id=' . $id)); }
    $occ = transport_route_occupied($id);
    if ($f['capacity'] < $occ) { flash_set('error', "Capacity can't be below current passengers ($occ)."); redirect(transport_url('edit.php?id=' . $id)); }
    $stmt = $db->prepare('UPDATE ' . tbl('transport_routes') . '
        SET route_name=?, vehicle_no=?, driver_name=?, driver_phone=?, stops=?, capacity=?, monthly_fee=?, status=? WHERE id=?');
    $stmt->bind_param('sssssidsi', $f['route_name'], $f['vehicle_no'], $f['driver_name'], $f['driver_phone'], $f['stops'], $f['capacity'], $f['monthly_fee'], $f['status'], $id);
    $stmt->execute(); $stmt->close();
    ums_log('route_update', 'Updated route #' . $id);
    flash_set('success', 'Route updated.');
    redirect(transport_url('index.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = transport_route_find($id);
    if ($cur) {
        if (transport_route_occupied($id) > 0) { flash_set('error', 'Cannot delete — the route has active passengers.'); redirect(transport_url('index.php')); }
        $db->query('DELETE FROM ' . tbl('transport_assignments') . ' WHERE route_id = ' . $id);
        $stmt = $db->prepare('DELETE FROM ' . tbl('transport_routes') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('route_delete', 'Deleted route ' . $cur['route_name']);
        flash_set('success', 'Route deleted.');
    }
    redirect(transport_url('index.php'));
}

if ($action === 'assign') {
    $routeId = (int)($_POST['route_id'] ?? 0);
    $stuId   = (int)($_POST['student_id'] ?? 0);
    $stop    = trim((string)($_POST['stop'] ?? ''));
    $route = transport_route_find($routeId);
    if (!$route || $route['status'] !== 'active') { flash_set('error', 'Select a valid route.'); redirect(transport_url('assign.php')); }
    if (transport_route_occupied($routeId) >= (int)$route['capacity']) { flash_set('error', 'That route is full.'); redirect(transport_url('assign.php')); }
    if ($stuId <= 0 || !array_key_exists($stuId, transport_student_options($campus))) { flash_set('error', 'Select a valid, unassigned student.'); redirect(transport_url('assign.php')); }
    $date = date('Y-m-d'); $fee = (float)$route['monthly_fee'];
    $stmt = $db->prepare('INSERT INTO ' . tbl('transport_assignments') . '
        (campus_id, route_id, student_id, stop, assigned_on, monthly_fee) VALUES (?,?,?,?,?,?)');
    $stmt->bind_param('iiissd', $campus, $routeId, $stuId, $stop, $date, $fee);
    $stmt->execute(); $stmt->close();
    ums_log('transport_assign', "Assigned student #$stuId to route {$route['route_name']}");
    flash_set('success', 'Student assigned to route.');
    redirect(transport_url('assign.php'));
}

if ($action === 'end') {
    $id = (int)($_POST['id'] ?? 0);
    $a = tassign_find($id);
    if (!$a || $a['status'] !== 'active') { flash_set('error', 'Assignment not found.'); redirect(transport_url('assign.php')); }
    $stmt = $db->prepare('UPDATE ' . tbl('transport_assignments') . ' SET status="ended", ended_on=? WHERE id=?');
    $today = date('Y-m-d');
    $stmt->bind_param('si', $today, $id); $stmt->execute(); $stmt->close();
    ums_log('transport_end', 'Ended assignment #' . $id);
    flash_set('success', 'Assignment ended.');
    redirect(transport_url('assign.php'));
}

if ($action === 'collect') {
    $id = (int)($_POST['assignment_id'] ?? 0);
    $a = tassign_find($id);
    if (!$a) { flash_set('error', 'Assignment not found.'); redirect(transport_url('assign.php')); }
    $amount = max(0.0, (float)($_POST['amount'] ?? 0));
    if ($amount <= 0) { flash_set('error', 'Enter a valid amount.'); redirect(transport_url('assign.php')); }
    $method = array_key_exists($_POST['method'] ?? '', TRANSPORT_METHODS) ? $_POST['method'] : 'cash';
    $month  = preg_match('/^\d{4}-\d{2}$/', $_POST['month'] ?? '') ? $_POST['month'] : date('Y-m');
    $ref    = trim((string)($_POST['reference'] ?? ''));
    $paidOn = date('Y-m-d');
    $stuId  = (int)$a['student_id'];
    $stmt = $db->prepare('INSERT INTO ' . tbl('transport_payments') . '
        (campus_id, assignment_id, student_id, amount, method, month, paid_on, reference) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iiidssss', $campus, $id, $stuId, $amount, $method, $month, $paidOn, $ref);
    $stmt->execute(); $stmt->close();
    ums_log('transport_collect', 'Transport fee ' . money($amount) . " for assignment #$id");
    flash_set('success', 'Payment of ' . money($amount) . ' recorded.');
    redirect(transport_url('assign.php'));
}

redirect(transport_url('index.php'));
