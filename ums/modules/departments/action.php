<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Departments — POST controller (create / update / delete). */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(dept_url('index.php'));
}

$action = (string)($_POST['action'] ?? '');
$db     = ums_db();

/** Collect + validate the form fields. */
function dept_form(): array
{
    return [
        'name'        => trim((string)($_POST['name'] ?? '')),
        'code'        => strtoupper(trim((string)($_POST['code'] ?? ''))),
        'head_name'   => trim((string)($_POST['head_name'] ?? '')),
        'email'       => trim((string)($_POST['email'] ?? '')),
        'phone'       => trim((string)($_POST['phone'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'status'      => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
    ];
}

if ($action === 'create') {
    $f = dept_form();
    if ($f['name'] === '') { flash_set('error', 'Department name is required.'); redirect(dept_url('create.php')); }

    $stmt = $db->prepare('INSERT INTO ' . tbl('departments') . '
        (campus_id, name, code, head_name, email, phone, description, status)
        VALUES (?,?,?,?,?,?,?,?)');
    $campus = (int)$user['campus_id'];
    $stmt->bind_param('isssssss', $campus, $f['name'], $f['code'], $f['head_name'], $f['email'], $f['phone'], $f['description'], $f['status']);
    $stmt->execute();
    $stmt->close();

    ums_log('department_create', 'Added department ' . $f['name']);
    flash_set('success', 'Department "' . $f['name'] . '" created.');
    redirect(dept_url('index.php'));
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if (!dept_find($id)) { flash_set('error', 'Department not found.'); redirect(dept_url('index.php')); }
    $f = dept_form();
    if ($f['name'] === '') { flash_set('error', 'Department name is required.'); redirect(dept_url('edit.php?id=' . $id)); }

    $stmt = $db->prepare('UPDATE ' . tbl('departments') . '
        SET name=?, code=?, head_name=?, email=?, phone=?, description=?, status=? WHERE id=?');
    $stmt->bind_param('sssssssi', $f['name'], $f['code'], $f['head_name'], $f['email'], $f['phone'], $f['description'], $f['status'], $id);
    $stmt->execute();
    $stmt->close();

    ums_log('department_update', 'Updated department ' . $f['name']);
    flash_set('success', 'Department updated.');
    redirect(dept_url('index.php'));
}

if ($action === 'delete') {
    $id  = (int)($_POST['id'] ?? 0);
    $cur = dept_find($id);
    if ($cur) {
        // Guard: don't orphan courses linked to this department
        $linked = 0;
        try {
            $c = $db->prepare('SELECT COUNT(*) n FROM ' . tbl('courses') . ' WHERE department_id = ?');
            $c->bind_param('i', $id); $c->execute();
            $linked = (int)$c->get_result()->fetch_assoc()['n']; $c->close();
        } catch (Throwable $t) { /* courses table not created yet */ }

        if ($linked > 0) {
            flash_set('error', "Cannot delete “{$cur['name']}” — $linked course(s) are still linked to it.");
            redirect(dept_url('index.php'));
        }
        $stmt = $db->prepare('DELETE FROM ' . tbl('departments') . ' WHERE id=?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        ums_log('department_delete', 'Deleted department ' . $cur['name']);
        flash_set('success', 'Department deleted.');
    }
    redirect(dept_url('index.php'));
}

redirect(dept_url('index.php'));
