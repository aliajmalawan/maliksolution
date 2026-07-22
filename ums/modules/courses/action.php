<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Courses — POST controller (create / update / delete). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(crs_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();
$campus = (int)$user['campus_id'];

/** Collect + validate the form fields. */
function crs_formdata(int $campus): array
{
    $type = array_key_exists($_POST['type'] ?? '', CRS_TYPES) ? $_POST['type'] : 'theory';
    $sem  = max(1, min(CRS_SEMESTERS, (int)($_POST['semester'] ?? 1)));
    $cr   = max(0, min(6, (int)($_POST['credit_hours'] ?? 3)));

    // department + prerequisite must be real ids for this campus (or 0)
    $deptId = (int)($_POST['department_id'] ?? 0);
    if ($deptId && !array_key_exists($deptId, dept_options($campus))) $deptId = 0;
    $preId = (int)($_POST['prerequisite_id'] ?? 0);

    return [
        'department_id'   => $deptId,
        'code'            => strtoupper(trim((string)($_POST['code'] ?? ''))),
        'title'           => trim((string)($_POST['title'] ?? '')),
        'credit_hours'    => $cr,
        'semester'        => $sem,
        'type'            => $type,
        'prerequisite_id' => $preId,
        'description'     => trim((string)($_POST['description'] ?? '')),
        'status'          => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];
}

if ($action === 'create') {
    $f = crs_formdata($campus);
    if ($f['title'] === '') { flash_set('error', 'Course title is required.'); redirect(crs_url('create.php')); }

    $stmt = $db->prepare('INSERT INTO ' . tbl('courses') . '
        (campus_id, department_id, code, title, credit_hours, semester, type, prerequisite_id, description, status)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->bind_param('iissiisiss',
        $campus, $f['department_id'], $f['code'], $f['title'], $f['credit_hours'],
        $f['semester'], $f['type'], $f['prerequisite_id'], $f['description'], $f['status']);
    $stmt->execute(); $stmt->close();

    ums_log('course_create', 'Added course ' . $f['title']);
    flash_set('success', 'Course "' . $f['title'] . '" created.');
    redirect(crs_url('index.php'));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!crs_find($id)) { flash_set('error', 'Course not found.'); redirect(crs_url('index.php')); }
    $f = crs_formdata($campus);
    if ($f['title'] === '') { flash_set('error', 'Course title is required.'); redirect(crs_url('edit.php?id=' . $id)); }
    if ($f['prerequisite_id'] === $id) $f['prerequisite_id'] = 0; // no self-prerequisite

    $stmt = $db->prepare('UPDATE ' . tbl('courses') . '
        SET department_id=?, code=?, title=?, credit_hours=?, semester=?, type=?, prerequisite_id=?, description=?, status=? WHERE id=?');
    $stmt->bind_param('issiisissi',
        $f['department_id'], $f['code'], $f['title'], $f['credit_hours'], $f['semester'],
        $f['type'], $f['prerequisite_id'], $f['description'], $f['status'], $id);
    $stmt->execute(); $stmt->close();

    ums_log('course_update', 'Updated course ' . $f['title']);
    flash_set('success', 'Course updated.');
    redirect(crs_url('index.php'));
}

if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = crs_find($id);
    if ($cur) {
        // clear this course as a prerequisite of others, then delete
        $db->query('UPDATE ' . tbl('courses') . ' SET prerequisite_id = 0 WHERE prerequisite_id = ' . $id);
        $stmt = $db->prepare('DELETE FROM ' . tbl('courses') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('course_delete', 'Deleted course ' . $cur['title']);
        flash_set('success', 'Course deleted.');
    }
    redirect(crs_url('index.php'));
}

redirect(crs_url('index.php'));
