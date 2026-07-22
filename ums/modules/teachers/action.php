<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Teachers — POST controller (create / update / delete). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(tch_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

/** Save an uploaded staff photo; returns relative path or ''. */
function tch_photo(): string
{
    if (empty($_FILES['photo']['name']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 2 * 1024 * 1024) return '';
    $ext = strtolower(pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || @getimagesize($_FILES['photo']['tmp_name']) === false) return '';
    $dir = __DIR__ . '/../../uploads/teachers';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = 'tch-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($_FILES['photo']['tmp_name'], "$dir/$name") ? 'uploads/teachers/' . $name : '';
}

function tch_form(int $campus): array
{
    $desig = in_array($_POST['designation'] ?? '', TCH_DESIGNATIONS, true) ? $_POST['designation'] : 'Lecturer';
    $gender = in_array($_POST['gender'] ?? '', ['male', 'female', 'other'], true) ? $_POST['gender'] : 'male';
    $status = array_key_exists($_POST['status'] ?? '', TCH_STATUS) ? $_POST['status'] : 'active';
    $deptId = (int)($_POST['department_id'] ?? 0);
    if ($deptId && !array_key_exists($deptId, dept_options($campus))) $deptId = 0;
    $dob  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dob'] ?? '') ? $_POST['dob'] : null;
    $join = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['joining_date'] ?? '') ? $_POST['joining_date'] : null;

    return [
        'department_id' => $deptId,
        'name'          => trim((string)($_POST['name'] ?? '')),
        'gender'        => $gender,
        'dob'           => $dob,
        'cnic'          => trim((string)($_POST['cnic'] ?? '')),
        'email'         => trim((string)($_POST['email'] ?? '')),
        'phone'         => trim((string)($_POST['phone'] ?? '')),
        'address'       => trim((string)($_POST['address'] ?? '')),
        'designation'   => $desig,
        'qualification' => trim((string)($_POST['qualification'] ?? '')),
        'joining_date'  => $join,
        'salary'        => max(0, (float)($_POST['salary'] ?? 0)),
        'status'        => $status,
    ];
}

if ($action === 'create') {
    $f = tch_form($campus);
    if ($f['name'] === '') { flash_set('error', 'Teacher name is required.'); redirect(tch_url('create.php')); }
    $photo = tch_photo();

    $stmt = $db->prepare('INSERT INTO ' . tbl('teachers') . '
        (campus_id, department_id, name, gender, dob, cnic, email, phone, address, designation, qualification, joining_date, salary, photo, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    // types: i i + 10×s + d + s + s
    $stmt->bind_param('ii' . str_repeat('s', 10) . 'dss',
        $campus, $f['department_id'], $f['name'], $f['gender'], $f['dob'], $f['cnic'], $f['email'], $f['phone'],
        $f['address'], $f['designation'], $f['qualification'], $f['joining_date'], $f['salary'], $photo, $f['status']);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    $empNo = 'TCH-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    $up = $db->prepare('UPDATE ' . tbl('teachers') . ' SET employee_no = ? WHERE id = ?');
    $up->bind_param('si', $empNo, $id); $up->execute(); $up->close();

    ums_log('teacher_create', "Added teacher $empNo — {$f['name']}");
    flash_set('success', "Teacher $empNo added.");
    redirect(tch_url('view.php?id=' . $id));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = tch_find($id);
    if (!$cur) { flash_set('error', 'Teacher not found.'); redirect(tch_url('index.php')); }
    $f = tch_form($campus);
    if ($f['name'] === '') { flash_set('error', 'Teacher name is required.'); redirect(tch_url('edit.php?id=' . $id)); }
    $photo = tch_photo();
    if ($photo === '') $photo = $cur['photo'];

    $stmt = $db->prepare('UPDATE ' . tbl('teachers') . '
        SET department_id=?, name=?, gender=?, dob=?, cnic=?, email=?, phone=?, address=?, designation=?, qualification=?, joining_date=?, salary=?, photo=?, status=?
        WHERE id=?');
    // types: i + 10×s + d + s + s + i
    $stmt->bind_param('i' . str_repeat('s', 10) . 'dssi',
        $f['department_id'], $f['name'], $f['gender'], $f['dob'], $f['cnic'], $f['email'], $f['phone'], $f['address'],
        $f['designation'], $f['qualification'], $f['joining_date'], $f['salary'], $photo, $f['status'], $id);
    $stmt->execute(); $stmt->close();

    ums_log('teacher_update', "Updated teacher {$cur['employee_no']}");
    flash_set('success', 'Teacher updated.');
    redirect(tch_url('view.php?id=' . $id));
}

if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = tch_find($id);
    if ($cur) {
        if ($cur['photo'] !== '' && str_starts_with($cur['photo'], 'uploads/teachers/')) @unlink(__DIR__ . '/../../' . $cur['photo']);
        $stmt = $db->prepare('DELETE FROM ' . tbl('teachers') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('teacher_delete', "Deleted teacher {$cur['employee_no']}");
        flash_set('success', 'Teacher deleted.');
    }
    redirect(tch_url('index.php'));
}

redirect(tch_url('index.php'));
