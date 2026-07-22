<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Students — POST controller (create / update / delete). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(stu_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

/** Save an uploaded student photo; returns relative path or ''. */
function stu_photo(): string
{
    if (empty($_FILES['photo']['name']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 2 * 1024 * 1024) return '';
    $ext = strtolower(pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || @getimagesize($_FILES['photo']['tmp_name']) === false) return '';
    $dir = __DIR__ . '/../../uploads/students';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = 'stu-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    return move_uploaded_file($_FILES['photo']['tmp_name'], "$dir/$name") ? 'uploads/students/' . $name : '';
}

function stu_form(int $campus): array
{
    $gender = in_array($_POST['gender'] ?? '', ['male', 'female', 'other'], true) ? $_POST['gender'] : 'male';
    $status = array_key_exists($_POST['status'] ?? '', STU_STATUS) ? $_POST['status'] : 'active';
    $program = trim((string)($_POST['program'] ?? ''));
    if (!in_array($program, program_list(), true)) $program = '';
    $session = in_array($_POST['session'] ?? '', session_list(), true) ? $_POST['session'] : session_list()[0];
    $sem = max(1, min(STU_SEMESTERS, (int)($_POST['current_semester'] ?? 1)));
    $deptId = (int)($_POST['department_id'] ?? 0);
    if ($deptId && !array_key_exists($deptId, dept_options($campus))) $deptId = 0;
    $dob = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dob'] ?? '') ? $_POST['dob'] : null;

    return [
        'department_id'    => $deptId,
        'admission_id'     => max(0, (int)($_POST['admission_id'] ?? 0)),
        'name'             => trim((string)($_POST['name'] ?? '')),
        'father_name'      => trim((string)($_POST['father_name'] ?? '')),
        'gender'           => $gender,
        'dob'              => $dob,
        'cnic'             => trim((string)($_POST['cnic'] ?? '')),
        'email'            => trim((string)($_POST['email'] ?? '')),
        'phone'            => trim((string)($_POST['phone'] ?? '')),
        'address'          => trim((string)($_POST['address'] ?? '')),
        'program'          => $program,
        'session'          => $session,
        'batch'            => trim((string)($_POST['batch'] ?? '')),
        'current_semester' => $sem,
        'status'           => $status,
    ];
}

if ($action === 'create') {
    $f = stu_form($campus);
    if ($f['name'] === '') { flash_set('error', 'Student name is required.'); redirect(stu_url('create.php')); }
    $photo = stu_photo();

    $stmt = $db->prepare('INSERT INTO ' . tbl('students') . '
        (campus_id, department_id, admission_id, registration_no, name, father_name, gender, dob, cnic, email, phone, address, program, session, batch, current_semester, photo, status)
        VALUES (?,?,?,"",?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    // types: i i i + 11×s + i + s + s  (registration_no filled after insert)
    $stmt->bind_param('iii' . str_repeat('s', 11) . 'iss',
        $campus, $f['department_id'], $f['admission_id'], $f['name'], $f['father_name'], $f['gender'], $f['dob'],
        $f['cnic'], $f['email'], $f['phone'], $f['address'], $f['program'], $f['session'], $f['batch'],
        $f['current_semester'], $photo, $f['status']);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    $reg = 'STU-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    $up = $db->prepare('UPDATE ' . tbl('students') . ' SET registration_no = ? WHERE id = ?');
    $up->bind_param('si', $reg, $id); $up->execute(); $up->close();

    // If enrolled from an admission, mark that application as enrolled
    if ($f['admission_id'] > 0) {
        try {
            $mk = $db->prepare('UPDATE ' . tbl('admissions') . ' SET status="enrolled", approved_at=COALESCE(approved_at,NOW()) WHERE id=? AND campus_id=?');
            $mk->bind_param('ii', $f['admission_id'], $campus); $mk->execute(); $mk->close();
        } catch (Throwable $t) {}
    }

    ums_log('student_create', "Added student $reg — {$f['name']}");
    flash_set('success', "Student $reg enrolled.");
    redirect(stu_url('view.php?id=' . $id));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = stu_find($id);
    if (!$cur) { flash_set('error', 'Student not found.'); redirect(stu_url('index.php')); }
    $f = stu_form($campus);
    if ($f['name'] === '') { flash_set('error', 'Student name is required.'); redirect(stu_url('edit.php?id=' . $id)); }
    $photo = stu_photo();
    if ($photo === '') $photo = $cur['photo'];

    $stmt = $db->prepare('UPDATE ' . tbl('students') . '
        SET department_id=?, name=?, father_name=?, gender=?, dob=?, cnic=?, email=?, phone=?, address=?, program=?, session=?, batch=?, current_semester=?, photo=?, status=?
        WHERE id=?');
    // 16 params: department_id(i) + name..batch (11×s) + current_semester(i) + photo(s) + status(s) + id(i)
    $stmt->bind_param('i' . str_repeat('s', 11) . 'issi',
        $f['department_id'], $f['name'], $f['father_name'], $f['gender'], $f['dob'], $f['cnic'], $f['email'],
        $f['phone'], $f['address'], $f['program'], $f['session'], $f['batch'], $f['current_semester'], $photo, $f['status'], $id);
    $stmt->execute(); $stmt->close();

    ums_log('student_update', "Updated student {$cur['registration_no']}");
    flash_set('success', 'Student updated.');
    redirect(stu_url('view.php?id=' . $id));
}

if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = stu_find($id);
    if ($cur) {
        if ($cur['photo'] !== '' && str_starts_with($cur['photo'], 'uploads/students/')) @unlink(__DIR__ . '/../../' . $cur['photo']);
        $stmt = $db->prepare('DELETE FROM ' . tbl('students') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('student_delete', "Deleted student {$cur['registration_no']}");
        flash_set('success', 'Student deleted.');
    }
    redirect(stu_url('index.php'));
}

redirect(stu_url('index.php'));
