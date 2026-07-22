<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Examination — controller (exam CRUD + marks upsert). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(exam_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function exam_formdata(int $campus): array
{
    $type = array_key_exists($_POST['exam_type'] ?? '', EXAM_TYPES) ? $_POST['exam_type'] : 'midterm';
    $secId = (int)($_POST['section_id'] ?? 0);
    if ($secId && !array_key_exists($secId, exam_section_options($campus))) $secId = 0;
    $courseId = (int)($_POST['course_id'] ?? 0);
    $session = in_array($_POST['session'] ?? '', session_list(), true) ? $_POST['session'] : session_list()[0];
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['exam_date'] ?? '') ? $_POST['exam_date'] : null;
    $total = max(1, (int)($_POST['total_marks'] ?? 100));
    $pass  = max(0, min($total, (int)($_POST['passing_marks'] ?? 40)));
    $wt    = max(0, min(100, (int)($_POST['weightage'] ?? 100)));
    return [
        'title'         => trim((string)($_POST['title'] ?? '')),
        'exam_type'     => $type,
        'section_id'    => $secId,
        'course_id'     => $courseId,
        'session'       => $session,
        'exam_date'     => $date,
        'total_marks'   => $total,
        'passing_marks' => $pass,
        'weightage'     => $wt,
        'status'        => ($_POST['status'] ?? 'scheduled') === 'completed' ? 'completed' : 'scheduled',
    ];
}

if ($action === 'create') {
    $f = exam_formdata($campus);
    if ($f['title'] === '' || $f['section_id'] === 0) { flash_set('error', 'Title and section are required.'); redirect(exam_url('create.php')); }
    $stmt = $db->prepare('INSERT INTO ' . tbl('exams') . '
        (campus_id, title, exam_type, section_id, course_id, session, exam_date, total_marks, passing_marks, weightage, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('issiissiiis', $campus, $f['title'], $f['exam_type'], $f['section_id'], $f['course_id'], $f['session'], $f['exam_date'], $f['total_marks'], $f['passing_marks'], $f['weightage'], $f['status']);
    $stmt->execute(); $id = $stmt->insert_id; $stmt->close();
    ums_log('exam_create', 'Created exam ' . $f['title']);
    flash_set('success', 'Exam created. Now enter marks.');
    redirect(exam_url('marks.php?exam=' . $id));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!exam_find($id)) { flash_set('error', 'Exam not found.'); redirect(exam_url('index.php')); }
    $f = exam_formdata($campus);
    if ($f['title'] === '' || $f['section_id'] === 0) { flash_set('error', 'Title and section are required.'); redirect(exam_url('edit.php?id=' . $id)); }
    $stmt = $db->prepare('UPDATE ' . tbl('exams') . '
        SET title=?, exam_type=?, section_id=?, course_id=?, session=?, exam_date=?, total_marks=?, passing_marks=?, weightage=?, status=? WHERE id=?');
    $stmt->bind_param('ssiissiiisi', $f['title'], $f['exam_type'], $f['section_id'], $f['course_id'], $f['session'], $f['exam_date'], $f['total_marks'], $f['passing_marks'], $f['weightage'], $f['status'], $id);
    $stmt->execute(); $stmt->close();
    ums_log('exam_update', 'Updated exam #' . $id);
    flash_set('success', 'Exam updated.');
    redirect(exam_url('index.php'));
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $cur = exam_find($id);
    if ($cur) {
        $db->query('DELETE FROM ' . tbl('exam_marks') . ' WHERE exam_id = ' . $id);
        $stmt = $db->prepare('DELETE FROM ' . tbl('exams') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('exam_delete', 'Deleted exam ' . $cur['title']);
        flash_set('success', 'Exam and its marks deleted.');
    }
    redirect(exam_url('index.php'));
}

if ($action === 'save_marks') {
    $examId = (int)($_POST['exam_id'] ?? 0);
    $exam = exam_find($examId);
    if (!$exam || (int)$exam['campus_id'] !== $campus) { flash_set('error', 'Exam not found.'); redirect(exam_url('index.php')); }

    $total   = (float)$exam['total_marks'];
    $marks   = (array)($_POST['marks'] ?? []);
    $absents = (array)($_POST['absent'] ?? []);

    $stmt = $db->prepare('INSERT INTO ' . tbl('exam_marks') . '
        (campus_id, exam_id, student_id, obtained_marks, absent, remarks)
        VALUES (?,?,?,?,?,"")
        ON DUPLICATE KEY UPDATE obtained_marks = VALUES(obtained_marks), absent = VALUES(absent)');
    $sid = 0; $obt = 0.0; $abs = 0;
    $stmt->bind_param('iiidi', $campus, $examId, $sid, $obt, $abs);

    $n = 0;
    foreach ($marks as $k => $v) {
        $sid = (int)$k;
        if ($sid <= 0) continue;
        $abs = !empty($absents[$k]) ? 1 : 0;
        $obt = $abs ? 0.0 : max(0.0, min($total, (float)$v));
        $stmt->execute();
        $n++;
    }
    $stmt->close();

    // Mark the exam completed once marks are recorded
    $db->query('UPDATE ' . tbl('exams') . ' SET status = "completed" WHERE id = ' . $examId);

    ums_log('exam_marks', "Saved marks for $n student(s) · exam #$examId");
    flash_set('success', "Marks saved for $n student(s).");
    redirect(exam_url('marks.php?exam=' . $examId));
}

redirect(exam_url('index.php'));
