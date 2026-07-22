<?php
declare(strict_types=1);
session_start();
require_once '../includes/config.php';

// Must be logged in
if (empty($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: portal.php');
    exit;
}

$id         = (int)$_SESSION['student_id'];
$current_pw = trim($_POST['current_password'] ?? '');
$new_pw     = trim($_POST['new_password']     ?? '');
$confirm_pw = trim($_POST['confirm_password'] ?? '');

// Empty fields
if ($current_pw === '' || $new_pw === '' || $confirm_pw === '') {
    header('Location: security.php?pw=empty');
    exit;
}

// Minimum length
if (strlen($new_pw) < 6) {
    header('Location: security.php?pw=short');
    exit;
}

// Confirm match
if ($new_pw !== $confirm_pw) {
    header('Location: security.php?pw=mismatch');
    exit;
}

// Verify current password
$row = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT student_password FROM admissions WHERE id = $id AND status = 'approved'"
));

if (!$row || $row['student_password'] !== $current_pw) {
    header('Location: security.php?pw=wrong');
    exit;
}

// Same as current
if ($new_pw === $current_pw) {
    header('Location: security.php?pw=same');
    exit;
}

// Update password
$stmt = mysqli_prepare($conn, "UPDATE admissions SET student_password = ? WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'si', $new_pw, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header('Location: security.php?pw=success');
exit;
