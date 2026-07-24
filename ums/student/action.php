<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(sp_url('portal.php'));
}

$action = (string)($_POST['action'] ?? '');
$db = ums_db();

if ($action === 'profile_save') {
    $email   = trim((string)($_POST['email'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));

    $stmt = $db->prepare('UPDATE ' . tbl('students') . ' SET email=?, phone=?, address=? WHERE id=?');
    $stmt->bind_param('sssi', $email, $phone, $address, $studentId);
    $stmt->execute(); $stmt->close();

    ums_log('student_profile_update', 'Updated own profile');
    flash_set('success', 'Profile updated.');
    redirect(sp_url('profile.php'));
}

if ($action === 'change_password') {
    $cur  = (string)($_POST['current_password'] ?? '');
    $new1 = (string)($_POST['new_password'] ?? '');
    $new2 = (string)($_POST['confirm_password'] ?? '');

    $login = stu_login_find($studentId);
    if (!$login || !password_verify($cur, $login['password_hash'])) {
        flash_set('error', 'Current password is incorrect.');
        redirect(sp_url('change_password.php'));
    }
    if (strlen($new1) < 6) {
        flash_set('error', 'New password must be at least 6 characters.');
        redirect(sp_url('change_password.php'));
    }
    if ($new1 !== $new2) {
        flash_set('error', 'New passwords do not match.');
        redirect(sp_url('change_password.php'));
    }

    $hash = password_hash($new1, PASSWORD_DEFAULT);
    $up = $db->prepare('UPDATE ' . tbl('users') . ' SET password_hash = ? WHERE id = ?');
    $up->bind_param('si', $hash, $login['id']); $up->execute(); $up->close();

    ums_log('student_password_change', 'Changed own password');
    flash_set('success', 'Password changed successfully.');
    redirect(sp_url('change_password.php'));
}

redirect(sp_url('portal.php'));
