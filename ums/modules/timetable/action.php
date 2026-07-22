<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Timetable — POST controller (create / update / delete) with clash checks. */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(tt_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function tt_form(int $campus): array
{
    $secId = (int)($_POST['section_id'] ?? 0);
    if ($secId && !array_key_exists($secId, tt_section_options($campus))) $secId = 0;
    $courseId = (int)($_POST['course_id'] ?? 0);
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $day = (int)($_POST['day_of_week'] ?? 1);
    if (!array_key_exists($day, TT_DAYS)) $day = 1;
    $start = (string)($_POST['start_time'] ?? '');
    $end   = (string)($_POST['end_time'] ?? '');
    $ok = fn($t) => preg_match('/^\d{2}:\d{2}$/', $t);
    return [
        'section_id' => $secId,
        'day_of_week' => $day,
        'start_time' => $ok($start) ? $start . ':00' : '',
        'end_time'   => $ok($end) ? $end . ':00' : '',
        'course_id'  => $courseId,
        'teacher_id' => $teacherId,
        'room'       => trim((string)($_POST['room'] ?? '')),
    ];
}

/**
 * Return a clash message, or null if the slot is free.
 * Checks the section (can't be in two classes) and the teacher
 * (can't teach two classes) at overlapping times on the same day.
 */
function tt_clash(mysqli $db, int $campus, array $f, int $excludeId = 0): ?string
{
    // Section clash
    $q = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('timetable') . '
        WHERE campus_id=? AND section_id=? AND day_of_week=? AND id<>?
        AND start_time < ? AND ? < end_time');
    $q->bind_param('iiiiss', $campus, $f['section_id'], $f['day_of_week'], $excludeId, $f['end_time'], $f['start_time']);
    $q->execute();
    if ((int)$q->get_result()->fetch_assoc()['c'] > 0) { $q->close(); return 'This section already has a class in that time slot.'; }
    $q->close();

    // Teacher clash (only if a teacher is assigned)
    if ($f['teacher_id'] > 0) {
        $q = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('timetable') . '
            WHERE campus_id=? AND teacher_id=? AND day_of_week=? AND id<>?
            AND start_time < ? AND ? < end_time');
        $q->bind_param('iiiiss', $campus, $f['teacher_id'], $f['day_of_week'], $excludeId, $f['end_time'], $f['start_time']);
        $q->execute();
        if ((int)$q->get_result()->fetch_assoc()['c'] > 0) { $q->close(); return 'That teacher is already teaching another class at this time.'; }
        $q->close();
    }
    return null;
}

if ($action === 'create' || $action === 'update') {
    $id = $action === 'update' ? (int)($_POST['id'] ?? 0) : 0;
    $f  = tt_form($campus);
    $back = tt_url('index.php?section=' . $f['section_id']);

    if ($f['section_id'] === 0 || $f['start_time'] === '' || $f['end_time'] === '') {
        flash_set('error', 'Section, start time and end time are required.');
        redirect($back);
    }
    if ($f['end_time'] <= $f['start_time']) {
        flash_set('error', 'End time must be after the start time.');
        redirect($back);
    }
    if ($msg = tt_clash($db, $campus, $f, $id)) {
        flash_set('error', $msg);
        redirect($back);
    }

    if ($action === 'create') {
        $stmt = $db->prepare('INSERT INTO ' . tbl('timetable') . '
            (campus_id, section_id, day_of_week, start_time, end_time, course_id, teacher_id, room)
            VALUES (?,?,?,?,?,?,?,?)');
        $stmt->bind_param('iiissiis', $campus, $f['section_id'], $f['day_of_week'], $f['start_time'], $f['end_time'], $f['course_id'], $f['teacher_id'], $f['room']);
        $stmt->execute(); $stmt->close();
        ums_log('timetable_create', 'Added period to section #' . $f['section_id']);
        flash_set('success', 'Class period added.');
    } else {
        if (!tt_find($id)) { flash_set('error', 'Period not found.'); redirect($back); }
        $stmt = $db->prepare('UPDATE ' . tbl('timetable') . '
            SET section_id=?, day_of_week=?, start_time=?, end_time=?, course_id=?, teacher_id=?, room=? WHERE id=?');
        $stmt->bind_param('iissiisi', $f['section_id'], $f['day_of_week'], $f['start_time'], $f['end_time'], $f['course_id'], $f['teacher_id'], $f['room'], $id);
        $stmt->execute(); $stmt->close();
        ums_log('timetable_update', 'Updated period #' . $id);
        flash_set('success', 'Class period updated.');
    }
    redirect($back);
}

if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = tt_find($id);
    if ($cur) {
        $stmt = $db->prepare('DELETE FROM ' . tbl('timetable') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('timetable_delete', 'Deleted period #' . $id);
        flash_set('success', 'Class period removed.');
        redirect(tt_url('index.php?section=' . (int)$cur['section_id']));
    }
    redirect(tt_url('index.php'));
}

redirect(tt_url('index.php'));
