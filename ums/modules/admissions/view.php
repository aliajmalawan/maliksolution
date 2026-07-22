<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id  = (int)($_GET['id'] ?? 0);
$adm = adm_find($id);
if (!$adm) { flash_set('error', 'Application not found.'); redirect(adm_url('index.php')); }

$page_title = 'Application ' . $adm['application_no'];
$active     = 'admissions';

/** One status-change button. */
function adm_status_btn(array $adm, string $to, string $icon, string $label, string $cls): void
{
    if ($adm['status'] === $to) return;
    echo '<form method="post" action="' . adm_url('action.php') . '" style="display:inline">'
       . csrf_field()
       . '<input type="hidden" name="action" value="set_status">'
       . '<input type="hidden" name="status" value="' . e($to) . '">'
       . '<input type="hidden" name="back" value="view">'
       . '<input type="hidden" name="id" value="' . (int)$adm['id'] . '">'
       . '<button type="submit" class="u-btn ' . $cls . '"><i class="fa-solid ' . $icon . '"></i> ' . e($label) . '</button>'
       . '</form>';
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div>
    <h1><?= e($adm['student_name']) ?> <?= adm_badge($adm['status']) ?></h1>
    <p><?= e($adm['application_no']) ?> · Applied <?= e(date('d M Y, h:i A', strtotime($adm['applied_at']))) ?></p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a href="<?= adm_url('index.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-arrow-left"></i> Back</a>
    <a href="<?= adm_url('edit.php?id=' . $id) ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-pen"></i> Edit</a>
    <a href="<?= adm_url('print.php?id=' . $id) ?>" target="_blank" class="u-btn u-btn-primary"><i class="fa-solid fa-print"></i> Print Form</a>
  </div>
</div>

<div class="u-grid g-main">
  <div>
    <!-- Applicant -->
    <div class="u-card" style="margin-bottom:1.1rem">
      <div class="u-card-head"><h2><i class="fa-solid fa-user" style="color:var(--primary)"></i> Applicant</h2></div>
      <div class="u-detail">
        <div class="row-x"><span class="k">Full Name</span><span class="v"><?= e($adm['student_name']) ?></span></div>
        <div class="row-x"><span class="k">Father / Guardian</span><span class="v"><?= e($adm['father_name'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Gender</span><span class="v"><?= e(ucfirst($adm['gender'])) ?></span></div>
        <div class="row-x"><span class="k">Date of Birth</span><span class="v"><?= $adm['dob'] ? e(date('d M Y', strtotime($adm['dob']))) : '—' ?></span></div>
        <div class="row-x"><span class="k">CNIC / B-Form</span><span class="v"><?= e($adm['cnic'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Phone</span><span class="v"><?= e($adm['phone'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Email</span><span class="v"><?= e($adm['email'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Address</span><span class="v"><?= e($adm['address'] ?: '—') ?></span></div>
      </div>
    </div>
    <!-- Academics -->
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-graduation-cap" style="color:var(--primary)"></i> Program &amp; Academics</h2></div>
      <div class="u-detail">
        <div class="row-x"><span class="k">Program</span><span class="v"><?= e($adm['program']) ?></span></div>
        <div class="row-x"><span class="k">Session</span><span class="v"><?= e($adm['session']) ?></span></div>
        <div class="row-x"><span class="k">Last Qualification</span><span class="v"><?= e($adm['last_qualification'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Board / University</span><span class="v"><?= e($adm['board_university'] ?: '—') ?></span></div>
        <div class="row-x"><span class="k">Marks</span><span class="v"><?= $adm['total_marks'] > 0 ? (int)$adm['obtained_marks'] . ' / ' . (int)$adm['total_marks'] : '—' ?></span></div>
        <div class="row-x"><span class="k">Merit Score</span><span class="v"><?= $adm['total_marks'] > 0 ? number_format((float)$adm['merit_score'], 2) . '%' : '—' ?></span></div>
      </div>
      <?php if (trim((string)$adm['remarks']) !== ''): ?>
        <div style="margin-top:1rem;padding-top:.8rem;border-top:1px solid var(--line)">
          <small style="color:var(--muted);font-weight:800;text-transform:uppercase;letter-spacing:.05em">Remarks</small>
          <p style="margin:.3rem 0 0;font-size:.88rem"><?= nl2br(e($adm['remarks'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <!-- Photo + merit -->
    <div class="u-card" style="margin-bottom:1.1rem;text-align:center">
      <?php if ($adm['photo'] !== ''): ?>
        <img src="<?= UMS_URL . '/' . e($adm['photo']) ?>" alt="Applicant" style="width:120px;height:120px;object-fit:cover;border-radius:16px;border:1px solid var(--line)">
      <?php else: ?>
        <div style="width:120px;height:120px;border-radius:16px;margin:0 auto;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:2.2rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif"><?= e(adm_ini($adm['student_name'])) ?></div>
      <?php endif; ?>
      <h3 style="margin:.8rem 0 .1rem;font-weight:800"><?= e($adm['student_name']) ?></h3>
      <p style="color:var(--muted);font-size:.82rem;margin:0"><?= e($adm['program']) ?></p>
    </div>

    <!-- Status actions -->
    <div class="u-card">
      <div class="u-card-head"><h2><i class="fa-solid fa-gears" style="color:var(--primary)"></i> Actions</h2></div>
      <p style="color:var(--muted);font-size:.8rem;margin:0 0 .9rem">Current status: <?= adm_badge($adm['status']) ?></p>
      <div style="display:flex;flex-direction:column;gap:.6rem" class="no-print">
        <?php
        adm_status_btn($adm, 'approved', 'fa-circle-check', 'Approve Application', 'u-btn-primary');
        adm_status_btn($adm, 'enrolled', 'fa-user-graduate', 'Mark as Enrolled', 'u-btn-soft');
        adm_status_btn($adm, 'rejected', 'fa-circle-xmark', 'Reject Application', 'u-btn-soft');
        adm_status_btn($adm, 'pending', 'fa-rotate-left', 'Reset to Pending', 'u-btn-soft');
        ?>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  .u-side, .u-top, .u-page-head .u-btn, .no-print, .u-side-backdrop { display: none !important; }
  .u-main { margin: 0 !important; padding: 1rem !important; }
  body { background: #fff; }
}
</style>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
