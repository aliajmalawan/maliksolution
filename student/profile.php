<?php
declare(strict_types=1);
session_start();
require_once '../includes/config.php';

if (empty($_SESSION['student_id'])) { header('Location: login.php'); exit; }

$sid = (int)$_SESSION['student_id'];
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admissions WHERE id=$sid AND status='approved'"));
if (!$row) { session_destroy(); header('Location: login.php?msg=session_expired'); exit; }

$name          = htmlspecialchars($row['student_name']);
$student_id    = htmlspecialchars($row['student_id']);
$program       = htmlspecialchars($row['program']);
$first_name    = htmlspecialchars(explode(' ', $row['student_name'])[0]);
$father        = htmlspecialchars($row['father_name'] ?? '');
$phone         = htmlspecialchars($row['phone'] ?? '');
$email         = htmlspecialchars($row['email'] ?? '');
$address       = htmlspecialchars($row['address'] ?? '');
$username      = htmlspecialchars($row['username'] ?? '');
$applied       = $row['created_at'] ? date('d M Y', strtotime($row['created_at'])) : '—';
$student_photo = $row['student_photo'] ?? '';

$page_title = 'My Profile';
$active_nav = 'profile';
include '_inc_head.php';
?>

<div class="page-hdr">
  <div>
    <h1><i class="fa-solid fa-circle-user me-2 text-primary"></i>My Profile</h1>
    <p>Your personal and academic information</p>
  </div>
  <span class="badge-approved"><i class="fa-solid fa-circle-check"></i> Approved Student</span>
</div>

<div class="row g-3">

  <!-- Avatar card -->
  <div class="col-lg-3 col-md-4">
    <div class="panel text-center" style="padding:1.75rem 1rem">
      <?php if (!empty($student_photo)): ?>
      <div style="width:80px;height:80px;border-radius:50%;overflow:hidden;margin:0 auto 1rem;border:3px solid var(--gold)">
        <img src="../<?= htmlspecialchars($student_photo) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
      </div>
      <?php else: ?>
      <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--navy),#1a4a8a);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem;color:var(--gold)">
        <i class="fa-solid fa-user-graduate"></i>
      </div>
      <?php endif; ?>
      <div style="font-size:1rem;font-weight:800;color:var(--navy);margin-bottom:.25rem"><?= $name ?></div>
      <div style="font-size:.72rem;color:var(--muted);margin-bottom:.5rem"><?= $program ?></div>
      <?php if ($student_id): ?>
      <div style="font-family:monospace;font-size:.78rem;font-weight:700;background:var(--soft);border-radius:8px;padding:.35rem .75rem;display:inline-block;color:var(--navy)"><?= $student_id ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Info table -->
  <div class="col-lg-9 col-md-8">
    <div class="panel">
      <div class="panel-head">
        <div class="panel-head-title"><i class="fa-solid fa-address-card"></i> Personal Information</div>
      </div>
      <div class="panel-body-flush">
        <table class="detail-table">
          <tr><td class="dt-label">Full Name</td><td class="dt-val"><?= $name ?></td></tr>
          <tr><td class="dt-label">Father's Name</td><td class="dt-val"><?= $father ?: '—' ?></td></tr>
          <tr><td class="dt-label">Program</td><td class="dt-val"><?= $program ?></td></tr>
          <tr>
            <td class="dt-label">Student ID</td>
            <td class="dt-val" style="font-family:monospace;font-weight:800;color:var(--navy)"><?= $student_id ?: '—' ?></td>
          </tr>
          <tr><td class="dt-label">Username</td><td class="dt-val"><?= $username ?: '—' ?></td></tr>
          <tr><td class="dt-label">Phone</td><td class="dt-val"><?= $phone ?: '—' ?></td></tr>
          <tr><td class="dt-label">Email</td><td class="dt-val"><?= $email ?: '—' ?></td></tr>
          <?php if ($address): ?>
          <tr><td class="dt-label">Address</td><td class="dt-val"><?= $address ?></td></tr>
          <?php endif; ?>
          <tr><td class="dt-label">Applied On</td><td class="dt-val"><?= $applied ?></td></tr>
          <tr>
            <td class="dt-label">Status</td>
            <td class="dt-val"><span class="badge-approved"><i class="fa-solid fa-circle-check"></i> Approved</span></td>
          </tr>
        </table>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
      <a href="security.php" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-key me-1"></i>Change Password
      </a>
      <a href="portal.php" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Dashboard
      </a>
    </div>
  </div>

</div>

<?php include '_inc_foot.php'; ?>
