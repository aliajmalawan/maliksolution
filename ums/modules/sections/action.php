<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Sections — POST controller (create / update / delete / assign / unassign). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(sec_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

function sec_form(int $campus): array
{
    $program = trim((string)($_POST['program'] ?? ''));
    if (!in_array($program, program_list(), true)) $program = '';
    $session = in_array($_POST['session'] ?? '', session_list(), true) ? $_POST['session'] : session_list()[0];
    $sem = max(1, min(8, (int)($_POST['semester'] ?? 1)));
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $deptId = (int)($_POST['department_id'] ?? 0);
    if ($deptId && !array_key_exists($deptId, dept_options($campus))) $deptId = 0;
    $teacherId = (int)($_POST['class_teacher_id'] ?? 0);
    if ($teacherId && !array_key_exists($teacherId, sec_teacher_options($campus))) $teacherId = 0;

    return [
        'department_id'    => $deptId,
        'program'          => $program,
        'semester'         => $sem,
        'session'          => $session,
        'name'             => trim((string)($_POST['name'] ?? 'A')) ?: 'A',
        'class_teacher_id' => $teacherId,
        'capacity'         => max(1, min(500, (int)($_POST['capacity'] ?? 50))),
        'room'             => trim((string)($_POST['room'] ?? '')),
        'status'           => $status,
    ];
}

if ($action === 'create') {
    $f = sec_form($campus);
    if ($f['program'] === '') { flash_set('error', 'Please select a program.'); redirect(sec_url('create.php')); }

    $stmt = $db->prepare('INSERT INTO ' . tbl('sections') . '
        (campus_id, department_id, program, semester, session, name, class_teacher_id, capacity, room, status)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    // i i s i s s i i s s
    $stmt->bind_param('iisissiiss',
        $campus, $f['department_id'], $f['program'], $f['semester'], $f['session'],
        $f['name'], $f['class_teacher_id'], $f['capacity'], $f['room'], $f['status']);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    ums_log('section_create', 'Created section ' . sec_label($f));
    flash_set('success', 'Section created.');
    redirect(sec_url('view.php?id=' . $id));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!sec_find($id)) { flash_set('error', 'Section not found.'); redirect(sec_url('index.php')); }
    $f = sec_form($campus);
    if ($f['program'] === '') { flash_set('error', 'Please select a program.'); redirect(sec_url('edit.php?id=' . $id)); }

    $stmt = $db->prepare('UPDATE ' . tbl('sections') . '
        SET department_id=?, program=?, semester=?, session=?, name=?, class_teacher_id=?, capacity=?, room=?, status=? WHERE id=?');
    // i s i s s i i s s i
    $stmt->bind_param('isissiissi',
        $f['department_id'], $f['program'], $f['semester'], $f['session'], $f['name'],
        $f['class_teacher_id'], $f['capacity'], $f['room'], $f['status'], $id);
    $stmt->execute();
    $stmt->close();

    ums_log('section_update', 'Updated section #' . $id);
    flash_set('success', 'Section updated.');
    redirect(sec_url('view.php?id=' . $id));
}

if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = sec_find($id);
    if ($cur) {
        // Release any students assigned to this section
        try { $db->query('UPDATE ' . tbl('students') . ' SET section_id = 0 WHERE section_id = ' . $id); } catch (Throwable $t) {}
        $stmt = $db->prepare('DELETE FROM ' . tbl('sections') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('section_delete', 'Deleted section #' . $id);
        flash_set('success', 'Section deleted.');
    }
    redirect(sec_url('index.php'));
}

if ($action === 'assign_students') {
    $id  = (int)($_POST['id'] ?? 0);
    $sec = sec_find($id);
    if (!$sec) { flash_set('error', 'Section not found.'); redirect(sec_url('index.php')); }
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['student_ids'] ?? []))));
    $n = 0;
    foreach ($ids as $sid) {
        // Only assign eligible, unassigned students of the same campus
        $stmt = $db->prepare('UPDATE ' . tbl('students') . ' SET section_id = ? WHERE id = ? AND campus_id = ? AND section_id = 0');
        $stmt->bind_param('iii', $id, $sid, $campus);
        $stmt->execute();
        $n += $stmt->affected_rows;
        $stmt->close();
    }
    ums_log('section_assign', "Assigned $n student(s) to section #$id");
    flash_set('success', "$n student(s) added to the section.");
    redirect(sec_url('view.php?id=' . $id));
}

if ($action === 'unassign_student') {
    $id  = (int)($_POST['id'] ?? 0);
    $sid = (int)($_POST['student_id'] ?? 0);
    $stmt = $db->prepare('UPDATE ' . tbl('students') . ' SET section_id = 0 WHERE id = ? AND section_id = ? AND campus_id = ?');
    $stmt->bind_param('iii', $sid, $id, $campus);
    $stmt->execute();
    $stmt->close();
    ums_log('section_unassign', "Removed student #$sid from section #$id");
    flash_set('success', 'Student removed from the section.');
    redirect(sec_url('view.php?id=' . $id));
}

redirect(sec_url('index.php'));
