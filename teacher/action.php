<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['teacher_id'])) { header('Location: login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: portal.php'); exit; }

require_once __DIR__ . '/../includes/config.php';
$tid    = (int)$_SESSION['teacher_id'];
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

function safe(string $v): string { return trim($v); }

/* ══ QUIZ ══════════════════════════════════════════════ */
if ($action === 'quiz_save') {
    $title    = safe($_POST['title']       ?? '');
    $desc     = safe($_POST['description'] ?? '');
    $cid      = (int)($_POST['course_id']  ?? 0);
    $sid      = (int)($_POST['subject_id'] ?? 0);
    $dur      = max(1, (int)($_POST['duration_minutes'] ?? 30));
    $marks    = max(1, (int)($_POST['total_marks'] ?? 100));
    $status   = in_array($_POST['status']??'', ['draft','active','closed']) ? $_POST['status'] : 'draft';

    if ($id > 0) {
        $st = mysqli_prepare($conn, "UPDATE teacher_quizzes SET title=?,description=?,course_id=?,subject_id=?,duration_minutes=?,total_marks=?,status=? WHERE id=? AND teacher_id=?");
        mysqli_stmt_bind_param($st,'ssiiisii',$title,$desc,$cid,$sid,$dur,$marks,$status,$id,$tid);
    } else {
        $st = mysqli_prepare($conn, "INSERT INTO teacher_quizzes (teacher_id,course_id,subject_id,title,description,duration_minutes,total_marks,status) VALUES (?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st,'iiisssis',$tid,$cid,$sid,$title,$desc,$dur,$marks,$status);
    }
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: quizzes.php?msg=saved'); exit;
}
if ($action === 'quiz_delete' && $id > 0) {
    $st = mysqli_prepare($conn, "DELETE FROM teacher_quizzes WHERE id=? AND teacher_id=?");
    mysqli_stmt_bind_param($st,'ii',$id,$tid);
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: quizzes.php?msg=deleted'); exit;
}

/* ══ ASSIGNMENT ════════════════════════════════════════ */
if ($action === 'assign_save') {
    $title   = safe($_POST['title']       ?? '');
    $desc    = safe($_POST['description'] ?? '');
    $cid     = (int)($_POST['course_id']  ?? 0);
    $sid     = (int)($_POST['subject_id'] ?? 0);
    $due     = safe($_POST['due_date']    ?? '');
    $marks   = max(1, (int)($_POST['total_marks'] ?? 100));
    $status  = in_array($_POST['status']??'', ['draft','active','closed']) ? $_POST['status'] : 'draft';
    $due_val = $due !== '' ? $due : null;

    if ($id > 0) {
        $st = mysqli_prepare($conn, "UPDATE teacher_assignments SET title=?,description=?,course_id=?,subject_id=?,due_date=?,total_marks=?,status=? WHERE id=? AND teacher_id=?");
        mysqli_stmt_bind_param($st,'ssiiisii',$title,$desc,$cid,$sid,$due_val,$marks,$status,$id,$tid);
    } else {
        $st = mysqli_prepare($conn, "INSERT INTO teacher_assignments (teacher_id,course_id,subject_id,title,description,due_date,total_marks,status) VALUES (?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st,'iiissssi',$tid,$cid,$sid,$title,$desc,$due_val,$marks,$status);
    }
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: assignments.php?msg=saved'); exit;
}
if ($action === 'assign_delete' && $id > 0) {
    $st = mysqli_prepare($conn, "DELETE FROM teacher_assignments WHERE id=? AND teacher_id=?");
    mysqli_stmt_bind_param($st,'ii',$id,$tid);
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: assignments.php?msg=deleted'); exit;
}

/* ══ TEST ══════════════════════════════════════════════ */
if ($action === 'test_save') {
    $title   = safe($_POST['title']        ?? '');
    $desc    = safe($_POST['description']  ?? '');
    $cid     = (int)($_POST['course_id']   ?? 0);
    $sid     = (int)($_POST['subject_id']  ?? 0);
    $tdate   = safe($_POST['test_date']    ?? '');
    $dur     = max(1, (int)($_POST['duration_minutes'] ?? 60));
    $marks   = max(1, (int)($_POST['total_marks'] ?? 100));
    $venue   = safe($_POST['venue']        ?? '');
    $status  = in_array($_POST['status']??'',['scheduled','completed','cancelled']) ? $_POST['status'] : 'scheduled';
    $td_val  = $tdate !== '' ? $tdate : null;

    if ($id > 0) {
        $st = mysqli_prepare($conn, "UPDATE teacher_tests SET title=?,description=?,course_id=?,subject_id=?,test_date=?,duration_minutes=?,total_marks=?,venue=?,status=? WHERE id=? AND teacher_id=?");
        mysqli_stmt_bind_param($st,'ssiisiisii',$title,$desc,$cid,$sid,$td_val,$dur,$marks,$venue,$status,$id,$tid);
    } else {
        $st = mysqli_prepare($conn, "INSERT INTO teacher_tests (teacher_id,course_id,subject_id,title,description,test_date,duration_minutes,total_marks,venue,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st,'iiisssiss',$tid,$cid,$sid,$title,$desc,$td_val,$dur,$marks,$venue,$status);
    }
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: tests.php?msg=saved'); exit;
}
if ($action === 'test_delete' && $id > 0) {
    $st = mysqli_prepare($conn, "DELETE FROM teacher_tests WHERE id=? AND teacher_id=?");
    mysqli_stmt_bind_param($st,'ii',$id,$tid);
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: tests.php?msg=deleted'); exit;
}

/* ══ ANNOUNCEMENT ══════════════════════════════════════ */
if ($action === 'ann_save') {
    $title    = safe($_POST['title']    ?? '');
    $content  = safe($_POST['content'] ?? '');
    $cid      = (int)($_POST['course_id'] ?? 0);
    $priority = in_array($_POST['priority']??'',['normal','important','urgent']) ? $_POST['priority'] : 'normal';
    $status   = in_array($_POST['status']??'',['active','archived']) ? $_POST['status'] : 'active';

    if ($id > 0) {
        $st = mysqli_prepare($conn, "UPDATE teacher_announcements SET title=?,content=?,course_id=?,priority=?,status=? WHERE id=? AND teacher_id=?");
        mysqli_stmt_bind_param($st,'ssissii',$title,$content,$cid,$priority,$status,$id,$tid);
    } else {
        $st = mysqli_prepare($conn, "INSERT INTO teacher_announcements (teacher_id,course_id,title,content,priority,status) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($st,'iissss',$tid,$cid,$title,$content,$priority,$status);
    }
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: announcements.php?msg=saved'); exit;
}
if ($action === 'ann_delete' && $id > 0) {
    $st = mysqli_prepare($conn, "DELETE FROM teacher_announcements WHERE id=? AND teacher_id=?");
    mysqli_stmt_bind_param($st,'ii',$id,$tid);
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: announcements.php?msg=deleted'); exit;
}

/* ══ PROFILE ═══════════════════════════════════════════ */
if ($action === 'profile_save') {
    $desig = safe($_POST['designation']  ?? '');
    $dept  = safe($_POST['department']   ?? '');
    $qual  = safe($_POST['qualification']?? '');
    $exp   = safe($_POST['experience']   ?? '');
    $email = safe($_POST['email']        ?? '');
    $phone = safe($_POST['phone']        ?? '');
    $bio   = safe($_POST['bio']          ?? '');
    $st = mysqli_prepare($conn, "UPDATE teachers SET designation=?,department=?,qualification=?,experience=?,email=?,phone=?,bio=? WHERE id=?");
    mysqli_stmt_bind_param($st,'sssssssi',$desig,$dept,$qual,$exp,$email,$phone,$bio,$tid);
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: profile.php?msg=saved'); exit;
}

/* ══ CHANGE PASSWORD ═══════════════════════════════════ */
if ($action === 'change_password') {
    $cur  = $_POST['current_password'] ?? '';
    $new1 = $_POST['new_password']     ?? '';
    $new2 = $_POST['confirm_password'] ?? '';
    $tr   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT teacher_password_hash FROM teachers WHERE id=$tid"));
    if (!password_verify($cur, $tr['teacher_password_hash'] ?? '')) {
        header('Location: change_password.php?err=wrong'); exit;
    }
    if (strlen($new1) < 6) { header('Location: change_password.php?err=short'); exit; }
    if ($new1 !== $new2)   { header('Location: change_password.php?err=match'); exit; }
    $hash = password_hash($new1, PASSWORD_DEFAULT);
    $st = mysqli_prepare($conn, "UPDATE teachers SET teacher_password_hash=? WHERE id=?");
    mysqli_stmt_bind_param($st,'si',$hash,$tid);
    mysqli_stmt_execute($st); mysqli_stmt_close($st);
    header('Location: change_password.php?msg=saved'); exit;
}

header('Location: portal.php'); exit;
