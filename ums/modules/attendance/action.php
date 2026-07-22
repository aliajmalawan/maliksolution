<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Attendance — save controller (upsert one record per student/section/course/date). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(att_url('mark.php'));
}

$db      = ums_db();
$campus  = (int)$user['campus_id'];
$secId   = (int)($_POST['section_id'] ?? 0);
$courseId = max(0, (int)($_POST['course_id'] ?? 0));
$date    = (string)($_POST['a_date'] ?? '');
$uid     = (int)$user['id'];

$sec = att_section($secId);
if (!$sec || (int)$sec['campus_id'] !== $campus || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    flash_set('error', 'Please choose a valid section and date.');
    redirect(att_url('mark.php'));
}

$statusMap = (array)($_POST['status'] ?? []);
$valid = array_keys(ATT_STATUS);

$stmt = $db->prepare('INSERT INTO ' . tbl('attendance') . '
    (campus_id, section_id, course_id, student_id, a_date, status, marked_by)
    VALUES (?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)');

$sid = 0; $stt = 'present';
$stmt->bind_param('iiiissi', $campus, $secId, $courseId, $sid, $date, $stt, $uid);

$n = 0;
foreach ($statusMap as $k => $v) {
    $sid = (int)$k;
    $stt = in_array($v, $valid, true) ? $v : 'present';
    if ($sid > 0) { $stmt->execute(); $n++; }
}
$stmt->close();

ums_log('attendance_mark', "Marked $n students · section #$secId · $date" . ($courseId ? " · course #$courseId" : ''));
flash_set('success', "Attendance saved for $n student(s).");
redirect(att_url('mark.php?section=' . $secId . '&date=' . urlencode($date) . '&course=' . $courseId));
