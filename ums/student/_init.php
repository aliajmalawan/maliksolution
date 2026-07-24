<?php
declare(strict_types=1);

/**
 * Student Portal — shared init. Self-service student area, separate from
 * ums/admin/ (staff-only). Logs in via the same ums_users table (role =
 * "student"), linked to a real ums_students record via student_id.
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/crud.php';
require_login(['student']);

$authUser  = ums_user();
$studentId = (int)($authUser['student_id'] ?? 0);
$student   = $studentId > 0 ? stu_find($studentId) : null;
if (!$student || $student['status'] !== 'active') {
    ums_logout();
    redirect(UMS_URL . '/student/login.php?err=account');
}

function sp_url(string $path = ''): string
{
    return UMS_URL . '/student/' . $path;
}
