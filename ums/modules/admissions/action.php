<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/**
 * Admissions — POST controller. Handles create / update / delete /
 * status changes. All writes use prepared statements and CSRF checks.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(adm_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();

/** Save an uploaded applicant photo; returns relative path or ''. */
function adm_save_photo(): string
{
    if (empty($_FILES['photo']['name']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 2 * 1024 * 1024) {
        return '';
    }
    $ext = strtolower(pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || @getimagesize($_FILES['photo']['tmp_name']) === false) {
        return '';
    }
    $dir = __DIR__ . '/../../uploads/admissions';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = 'app-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], "$dir/$name")) {
        return '';
    }
    return 'uploads/admissions/' . $name;
}

/** Collect + sanitise the shared form fields. */
function adm_form(): array
{
    $obtained = max(0, (int)($_POST['obtained_marks'] ?? 0));
    $total    = max(0, (int)($_POST['total_marks'] ?? 0));
    $merit    = $total > 0 ? round($obtained / $total * 100, 2) : 0.0;
    $program  = trim((string)($_POST['program'] ?? ''));
    if (!in_array($program, ADM_PROGRAMS, true)) $program = '';
    $session  = trim((string)($_POST['session'] ?? ''));
    if (!in_array($session, ADM_SESSIONS, true)) $session = ADM_SESSIONS[0];
    $gender   = in_array($_POST['gender'] ?? '', ['male', 'female', 'other'], true) ? $_POST['gender'] : 'male';
    $dob      = trim((string)($_POST['dob'] ?? ''));
    $dob      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ? $dob : null;

    return [
        'student_name'       => trim((string)($_POST['student_name'] ?? '')),
        'father_name'        => trim((string)($_POST['father_name'] ?? '')),
        'gender'             => $gender,
        'dob'                => $dob,
        'cnic'               => trim((string)($_POST['cnic'] ?? '')),
        'email'              => trim((string)($_POST['email'] ?? '')),
        'phone'              => trim((string)($_POST['phone'] ?? '')),
        'address'            => trim((string)($_POST['address'] ?? '')),
        'program'            => $program,
        'session'            => $session,
        'last_qualification' => trim((string)($_POST['last_qualification'] ?? '')),
        'obtained_marks'     => $obtained,
        'total_marks'        => $total,
        'board_university'   => trim((string)($_POST['board_university'] ?? '')),
        'merit_score'        => $merit,
        'remarks'            => trim((string)($_POST['remarks'] ?? '')),
    ];
}

// ─────────────────────────────── CREATE ───────────────────────────────
if ($action === 'create') {
    $f = adm_form();
    if ($f['student_name'] === '' || $f['program'] === '') {
        flash_set('error', 'Student name and program are required.');
        redirect(adm_url('create.php'));
    }
    $photo = adm_save_photo();

    $stmt = $db->prepare('INSERT INTO ' . tbl('admissions') . '
        (campus_id, student_name, father_name, gender, dob, cnic, email, phone, address,
         program, session, last_qualification, obtained_marks, total_marks, board_university, merit_score, photo, remarks, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"pending")');
    $campus = (int)$user['campus_id'];
    // 18 params: i + 11×s + i + i + s + d + s + s
    $stmt->bind_param(
        'isssssssssssiisdss',
        $campus, $f['student_name'], $f['father_name'], $f['gender'], $f['dob'], $f['cnic'],
        $f['email'], $f['phone'], $f['address'], $f['program'], $f['session'], $f['last_qualification'],
        $f['obtained_marks'], $f['total_marks'], $f['board_university'], $f['merit_score'], $photo, $f['remarks']
    );
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    // Application number derived from the id: APP-YYYY-####
    $appNo = 'APP-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
    $up = $db->prepare('UPDATE ' . tbl('admissions') . ' SET application_no = ? WHERE id = ?');
    $up->bind_param('si', $appNo, $id);
    $up->execute();
    $up->close();

    ums_log('admission_create', "New application $appNo — {$f['student_name']}");
    flash_set('success', "Application $appNo created for {$f['student_name']}.");
    redirect(adm_url('view.php?id=' . $id));
}

// ─────────────────────────────── UPDATE ───────────────────────────────
if ($action === 'update') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = adm_find($id);
    if (!$cur) { flash_set('error', 'Application not found.'); redirect(adm_url('index.php')); }

    $f = adm_form();
    if ($f['student_name'] === '' || $f['program'] === '') {
        flash_set('error', 'Student name and program are required.');
        redirect(adm_url('edit.php?id=' . $id));
    }
    $photo = adm_save_photo();
    if ($photo === '') $photo = $cur['photo']; // keep existing

    $stmt = $db->prepare('UPDATE ' . tbl('admissions') . ' SET
        student_name=?, father_name=?, gender=?, dob=?, cnic=?, email=?, phone=?, address=?,
        program=?, session=?, last_qualification=?, obtained_marks=?, total_marks=?, board_university=?, merit_score=?, photo=?, remarks=?
        WHERE id=?');
    $stmt->bind_param(
        'sssssssssssiisdssi',
        $f['student_name'], $f['father_name'], $f['gender'], $f['dob'], $f['cnic'], $f['email'], $f['phone'], $f['address'],
        $f['program'], $f['session'], $f['last_qualification'], $f['obtained_marks'], $f['total_marks'], $f['board_university'],
        $f['merit_score'], $photo, $f['remarks'], $id
    );
    $stmt->execute();
    $stmt->close();

    ums_log('admission_update', "Updated application {$cur['application_no']}");
    flash_set('success', 'Application updated.');
    redirect(adm_url('view.php?id=' . $id));
}

// ────────────────────────── STATUS CHANGE ─────────────────────────────
if ($action === 'set_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    $back   = (string)($_POST['back'] ?? 'index');
    if (!isset(ADM_STATUS[$status])) { flash_set('error', 'Invalid status.'); redirect(adm_url('index.php')); }
    $cur = adm_find($id);
    if (!$cur) { flash_set('error', 'Application not found.'); redirect(adm_url('index.php')); }

    // Auto-stamp the approval date the first time it is approved/enrolled;
    // clear it if the application is sent back to pending or rejected.
    if (in_array($status, ['approved', 'enrolled'], true)) {
        $stmt = $db->prepare('UPDATE ' . tbl('admissions') . '
            SET status=?, approved_at = COALESCE(approved_at, NOW()) WHERE id=?');
    } else {
        $stmt = $db->prepare('UPDATE ' . tbl('admissions') . '
            SET status=?, approved_at = NULL WHERE id=?');
    }
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
    $stmt->close();

    ums_log('admission_status', "{$cur['application_no']} → $status");
    flash_set('success', "Application {$cur['application_no']} marked as " . ADM_STATUS[$status][0] . '.');
    redirect($back === 'view' ? adm_url('view.php?id=' . $id) : adm_url('index.php'));
}

// ────────────────────── STUDENT LOGIN CREDENTIALS ─────────────────────
if ($action === 'reset_password') {
    $id  = (int)($_POST['id'] ?? 0);
    $adm = adm_find($id);
    $stu = $adm ? adm_linked_student($id) : null;
    if (!$adm || !$stu) { flash_set('error', 'This application is not linked to an enrolled student.'); redirect(adm_url('index.php')); }

    $newPw = stu_reset_password((int)$stu['id']);
    if ($newPw === null) {
        flash_set('error', 'This student has no login account yet.');
    } else {
        $login = stu_login_find((int)$stu['id']);
        $_SESSION['stu_new_creds'] = ['student_id' => (int)$stu['id'], 'name' => $stu['name'], 'reg' => $stu['registration_no'], 'email' => $login['email'], 'password' => $newPw];
        ums_log('student_password_reset', "Reset login password for {$stu['registration_no']}");
        flash_set('success', 'Password reset.');
    }
    redirect(adm_url('view.php?id=' . $id));
}

if ($action === 'generate_login') {
    $id  = (int)($_POST['id'] ?? 0);
    $adm = adm_find($id);
    $stu = $adm ? adm_linked_student($id) : null;
    if (!$adm || !$stu) { flash_set('error', 'This application is not linked to an enrolled student.'); redirect(adm_url('index.php')); }

    $creds = stu_create_login((int)$stu['id'], $stu['name'], $stu['email'], $stu['registration_no'], (int)$stu['campus_id']);
    if ($creds === null) {
        flash_set('error', 'This student already has a login account.');
    } else {
        $_SESSION['stu_new_creds'] = ['student_id' => (int)$stu['id'], 'name' => $stu['name'], 'reg' => $stu['registration_no'], 'email' => $creds['email'], 'password' => $creds['password']];
        ums_log('student_login_generate', "Generated login for {$stu['registration_no']}");
        flash_set('success', 'Login account created.');
    }
    redirect(adm_url('view.php?id=' . $id));
}

// ─────────────────────────────── DELETE ───────────────────────────────
if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = adm_find($id);
    if ($cur) {
        if ($cur['photo'] !== '' && str_starts_with($cur['photo'], 'uploads/admissions/')) {
            @unlink(__DIR__ . '/../../' . $cur['photo']);
        }
        $stmt = $db->prepare('DELETE FROM ' . tbl('admissions') . ' WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        ums_log('admission_delete', "Deleted application {$cur['application_no']}");
        flash_set('success', 'Application deleted.');
    }
    redirect(adm_url('index.php'));
}

redirect(adm_url('index.php'));
