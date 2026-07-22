<?php
declare(strict_types=1);
$page_title = 'My Profile';
$active_nav = 'profile';
include 'header.php';
?>

<?php if (!empty($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show">
  <i class="fa-solid fa-circle-check me-2"></i>Profile updated successfully.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-hd">
  <div><h1><i class="fa-solid fa-user-circle me-2 text-primary"></i>My Profile</h1><p>Update your professional information shown on the faculty page.</p></div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="cardx text-center">
      <?php if (!empty($teacher['photo'])): ?>
        <img src="../assets/uploads/teachers/<?= htmlspecialchars($teacher['photo']) ?>"
             style="width:110px;height:110px;border-radius:50%;object-fit:cover;object-position:center 15%;border:3px solid var(--line);margin-bottom:1rem">
      <?php else: ?>
        <div style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#e2eaf6,#c8d8ee);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;color:#8aa4cc;margin:0 auto 1rem;border:3px solid var(--line)"><?= $initials ?></div>
      <?php endif; ?>
      <h4 style="font-size:1.05rem;font-weight:800;color:var(--navy)"><?= htmlspecialchars($teacher['name']) ?></h4>
      <p class="text-muted" style="font-size:.82rem"><?= htmlspecialchars($teacher['designation'] ?: '—') ?></p>
      <hr>
      <div class="text-start" style="font-size:.82rem">
        <div class="mb-2"><strong>Username:</strong> <code><?= htmlspecialchars($teacher['teacher_username'] ?? '') ?></code></div>
        <div class="mb-2"><strong>Department:</strong> <?= htmlspecialchars($teacher['department'] ?: '—') ?></div>
        <div class="mb-2"><strong>Experience:</strong> <?= htmlspecialchars($teacher['experience'] ?: '—') ?></div>
        <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($teacher['email'] ?: '—') ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars($teacher['phone'] ?: '—') ?></div>
      </div>
      <div class="mt-3">
        <a href="change_password.php" class="btn btn-outline-primary btn-sm w-100">
          <i class="fa-solid fa-key me-1"></i>Change Password
        </a>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="cardx">
      <div class="sec-hd"><h2><i class="fa-solid fa-pen me-2 text-primary"></i>Edit Profile</h2></div>
      <form method="POST" action="action.php">
        <input type="hidden" name="action" value="profile_save">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Designation</label>
            <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($teacher['designation']) ?>" placeholder="e.g. Senior Lecturer">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Department</label>
            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($teacher['department']) ?>" placeholder="e.g. Physics">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Qualification</label>
            <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($teacher['qualification']) ?>" placeholder="e.g. M.Sc Physics">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Experience</label>
            <input type="text" name="experience" class="form-control" value="<?= htmlspecialchars($teacher['experience']) ?>" placeholder="e.g. 10 Years">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-bold">Short Bio</label>
            <textarea name="bio" class="form-control" rows="4" placeholder="Brief professional background…"><?= htmlspecialchars($teacher['bio'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Save Profile</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
