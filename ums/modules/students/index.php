<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Students'; $active = 'students';
$db = ums_db(); $campus = (int)$user['campus_id'];
$depts = dept_options($campus);

$q       = trim((string)($_GET['q'] ?? ''));
$fDept   = (int)($_GET['department'] ?? 0);
$fProg   = in_array($_GET['program'] ?? '', program_list(), true) ? $_GET['program'] : '';
$fSem    = (int)($_GET['semester'] ?? 0);
$fStatus = array_key_exists($_GET['status'] ?? '', STU_STATUS) ? $_GET['status'] : '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10; $offset = ($page - 1) * $perPage;

$where = ['s.campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')      { $where[] = '(s.name LIKE ? OR s.registration_no LIKE ? OR s.cnic LIKE ? OR s.phone LIKE ? OR s.email LIKE ?)'; $l = "%$q%"; $types .= 'sssss'; array_push($args, $l, $l, $l, $l, $l); }
if ($fDept)         { $where[] = 's.department_id = ?'; $types .= 'i'; $args[] = $fDept; }
if ($fProg !== '')  { $where[] = 's.program = ?';       $types .= 's'; $args[] = $fProg; }
if ($fSem)          { $where[] = 's.current_semester = ?'; $types .= 'i'; $args[] = $fSem; }
if ($fStatus !=='') { $where[] = 's.status = ?';        $types .= 's'; $args[] = $fStatus; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('students') . " s WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$ls = $db->prepare('SELECT s.*, d.name AS dept_name, u.email AS login_email FROM ' . tbl('students') . ' s
    LEFT JOIN ' . tbl('departments') . ' d ON d.id = s.department_id
    LEFT JOIN ' . tbl('users') . ' u ON u.student_id = s.id
    WHERE ' . $whereSql . ' ORDER BY s.id DESC LIMIT ? OFFSET ?');
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$stats = ['total' => 0, 'active' => 0, 'graduated' => 0, 'suspended' => 0, 'dropped' => 0];
$sr = $db->prepare('SELECT status, COUNT(*) c FROM ' . tbl('students') . ' WHERE campus_id = ? GROUP BY status');
$sr->bind_param('i', $campus); $sr->execute(); $r = $sr->get_result();
while ($x = $r->fetch_assoc()) { $stats[$x['status']] = (int)$x['c']; $stats['total'] += (int)$x['c']; }
$sr->close();

// Enrollable admissions (enrolled, not yet a student) — integration banner
$enrollable = 0;
try {
    $er = $db->query('SELECT COUNT(*) c FROM ' . tbl('admissions') . ' a
        WHERE a.campus_id = ' . $campus . ' AND a.status = "enrolled"
        AND NOT EXISTS (SELECT 1 FROM ' . tbl('students') . ' st WHERE st.admission_id = a.id)');
    $enrollable = (int)$er->fetch_assoc()['c'];
} catch (Throwable $t) {}

$newCreds = null;
if (!empty($_SESSION['stu_new_creds'])) {
    $newCreds = $_SESSION['stu_new_creds'];
    unset($_SESSION['stu_new_creds']);
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Students</h1><p>Enrolled students across all programs and semesters</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= stu_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= stu_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-user-plus"></i> Enroll Student</a>
  </div>
</div>

<?php if ($newCreds): ?>
<div class="u-card" style="margin-bottom:1.1rem;border:1.5px solid var(--primary)">
  <div class="u-card-head"><h2><i class="fa-solid fa-key" style="color:var(--primary)"></i> Login Password — <?= e($newCreds['name']) ?> (<?= e($newCreds['reg']) ?>)</h2></div>
  <p style="color:var(--muted);margin:0 0 .8rem">Share these with the student now — the password will <strong>not</strong> be shown again until you reset it.</p>
  <div class="u-form-grid">
    <div class="u-fld"><label>Username / Email</label><input type="text" readonly value="<?= e($newCreds['email']) ?>" onclick="this.select()"></div>
    <div class="u-fld"><label>Password</label><input type="text" readonly value="<?= e($newCreds['password']) ?>" onclick="this.select()" style="font-family:monospace;font-weight:700"></div>
  </div>
</div>
<?php endif; ?>

<?php if ($enrollable > 0): ?>
  <div class="u-flash info" style="margin-bottom:1.1rem">
    <i class="fa-solid fa-circle-info"></i>
    <?= $enrollable ?> enrolled admission application<?= $enrollable === 1 ? '' : 's' ?> ready to be added as student<?= $enrollable === 1 ? '' : 's' ?>.
    <a href="<?= stu_url('enroll.php') ?>" style="color:inherit;text-decoration:underline;font-weight:800;margin-left:.3rem">Enroll now &rarr;</a>
  </div>
<?php endif; ?>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-user-graduate"></i></span><div><small>Total Students</small><strong><?= $stats['total'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Active</small><strong><?= $stats['active'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-user-graduate"></i></span><div><small>Graduated</small><strong><?= $stats['graduated'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-user-clock"></i></span><div><small>Suspended</small><strong><?= $stats['suspended'] ?></strong></div></div>
  <div class="u-chip"><span class="ci" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="fa-solid fa-user-xmark"></i></span><div><small>Dropped</small><strong><?= $stats['dropped'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, reg no, CNIC, phone, or email…"></div>
    <select name="program" class="u-select" onchange="this.form.submit()"><option value="">All Programs</option>
      <?php foreach (program_list() as $p): ?><option value="<?= e($p) ?>" <?= $fProg===$p?'selected':'' ?>><?= e($p) ?></option><?php endforeach; ?></select>
    <select name="department" class="u-select" onchange="this.form.submit()"><option value="0">All Departments</option>
      <?php foreach ($depts as $id=>$name): ?><option value="<?= $id ?>" <?= $fDept===$id?'selected':'' ?>><?= e($name) ?></option><?php endforeach; ?></select>
    <select name="semester" class="u-select" onchange="this.form.submit()"><option value="0">All Semesters</option>
      <?php for ($i=1;$i<=STU_SEMESTERS;$i++): ?><option value="<?= $i ?>" <?= $fSem===$i?'selected':'' ?>>Semester <?= $i ?></option><?php endfor; ?></select>
    <select name="status" class="u-select" onchange="this.form.submit()"><option value="">All Status</option>
      <?php foreach (STU_STATUS as $k=>$m): ?><option value="<?= $k ?>" <?= $fStatus===$k?'selected':'' ?>><?= e($m[0]) ?></option><?php endforeach; ?></select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q||$fDept||$fProg||$fSem||$fStatus): ?><a href="<?= stu_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-list-ul" style="color:var(--primary)"></i> Students</h2>
    <span class="hint"><?= $total ?> record<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-user-graduate"></i><p>No students found<?= ($q||$fDept||$fProg||$fSem||$fStatus)?' for these filters':' yet' ?>.</p>
      <a href="<?= stu_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-user-plus"></i> Enroll the first student</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Student</th><th>Reg. No.</th><th>Program</th><th>Sem</th><th>Session</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><span style="display:flex;align-items:center;gap:.6rem">
              <?php if ($r['photo'] !== ''): ?><img src="<?= UMS_URL.'/'.e($r['photo']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover">
              <?php else: ?><span class="u-mini-av"><?= e(ini2($r['name'])) ?></span><?php endif; ?>
              <span><strong><?= e($r['name']) ?></strong><br><small style="color:var(--muted)"><?= e($r['dept_name'] ?: ($r['phone'] ?: '—')) ?></small></span></span></td>
            <td style="color:var(--muted);font-weight:700"><?= e($r['registration_no']) ?></td>
            <td style="color:var(--muted)"><?= e($r['program'] ?: '—') ?></td>
            <td style="text-align:center"><?= (int)$r['current_semester'] ?></td>
            <td style="color:var(--muted)"><?= e($r['session'] ?: '—') ?></td>
            <td><?= status_badge($r['status'], STU_STATUS) ?></td>
            <td style="text-align:right"><span class="u-act">
              <button type="button" class="btn-stu-view" title="View"
                data-id="<?= (int)$r['id'] ?>"
                data-name="<?= e($r['name']) ?>"
                data-photo="<?= e($r['photo']) ?>"
                data-father="<?= e($r['father_name']) ?>"
                data-gender="<?= e(ucfirst($r['gender'])) ?>"
                data-dob="<?= $r['dob'] ? e(date('d M Y', strtotime($r['dob']))) : '' ?>"
                data-cnic="<?= e($r['cnic']) ?>"
                data-phone="<?= e($r['phone']) ?>"
                data-email="<?= e($r['email']) ?>"
                data-address="<?= e($r['address']) ?>"
                data-regno="<?= e($r['registration_no']) ?>"
                data-program="<?= e($r['program']) ?>"
                data-dept="<?= e($r['dept_name'] ?: '') ?>"
                data-session="<?= e($r['session']) ?>"
                data-batch="<?= e($r['batch']) ?>"
                data-semester="<?= (int)$r['current_semester'] ?>"
                data-status-label="<?= e(STU_STATUS[$r['status']][0]) ?>"
                data-status-class="<?= e(STU_STATUS[$r['status']][1]) ?>"
                data-admission-id="<?= (int)$r['admission_id'] ?>"
                data-login-email="<?= e($r['login_email'] ?: '') ?>"
                style="border:none;background:none;color:inherit;cursor:pointer;padding:0"
              ><i class="fa-solid fa-eye"></i></button>
              <a href="<?= stu_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= stu_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this student?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q','department','program','semester','status'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>

<!-- ── View Student Modal ──────────────────────────────────── -->
<div class="u-modal-backdrop" id="stuViewModal">
  <div class="u-modal">
    <div class="u-modal-head">
      <h2><i class="fa-solid fa-user-graduate" style="color:var(--primary)"></i> <span id="mv_name">Student</span></h2>
      <button type="button" class="u-modal-close" data-modal-close><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="u-modal-body">
      <div class="u-detail" style="margin-bottom:1.1rem">
        <div class="row-x"><span class="k">Registration No.</span><span class="v" id="mv_regno"></span></div>
        <div class="row-x"><span class="k">Status</span><span class="v" id="mv_status"></span></div>
        <div class="row-x"><span class="k">Program</span><span class="v" id="mv_program"></span></div>
        <div class="row-x"><span class="k">Department</span><span class="v" id="mv_dept"></span></div>
        <div class="row-x"><span class="k">Session</span><span class="v" id="mv_session"></span></div>
        <div class="row-x"><span class="k">Batch</span><span class="v" id="mv_batch"></span></div>
        <div class="row-x"><span class="k">Current Semester</span><span class="v" id="mv_semester"></span></div>
        <div class="row-x" id="mv_adm_row" style="display:none"><span class="k">Admission</span><span class="v"><a href="#" id="mv_adm_link" style="color:var(--primary)">View application</a></span></div>
      </div>
      <div class="u-detail" style="margin-bottom:1.1rem">
        <div class="row-x"><span class="k">Father / Guardian</span><span class="v" id="mv_father"></span></div>
        <div class="row-x"><span class="k">Gender</span><span class="v" id="mv_gender"></span></div>
        <div class="row-x"><span class="k">Date of Birth</span><span class="v" id="mv_dob"></span></div>
        <div class="row-x"><span class="k">CNIC / B-Form</span><span class="v" id="mv_cnic"></span></div>
        <div class="row-x"><span class="k">Phone</span><span class="v" id="mv_phone"></span></div>
        <div class="row-x"><span class="k">Email</span><span class="v" id="mv_email"></span></div>
        <div class="row-x"><span class="k">Address</span><span class="v" id="mv_address"></span></div>
      </div>

      <div style="border-top:1px solid var(--line);padding-top:1rem">
        <h3 style="font-size:.85rem;font-weight:800;margin:0 0 .6rem;display:flex;align-items:center;gap:.5rem"><i class="fa-solid fa-key" style="color:var(--primary)"></i> Login Access</h3>
        <div id="mv_login_has">
          <div class="u-form-grid">
            <div class="u-fld"><label>Username / Email</label><input type="text" readonly id="mv_login_email" onclick="this.select()"></div>
            <div class="u-fld">
              <label>Password</label>
              <div style="display:flex;gap:.5rem;align-items:center">
                <input type="text" readonly value="Hidden — click Reset to generate a new one" style="color:var(--muted);font-style:italic">
                <form method="post" action="<?= stu_url('action.php') ?>" onsubmit="return confirm('Generate a new password? The old one will stop working immediately.')">
                  <?= csrf_field() ?><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" id="mv_reset_id">
                  <button type="submit" class="u-btn u-btn-soft" style="white-space:nowrap"><i class="fa-solid fa-rotate"></i> Reset</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <div id="mv_login_none" class="u-empty" style="padding:1rem;display:none">
          <i class="fa-solid fa-key"></i>
          <p>This student doesn't have a login account yet.</p>
          <form method="post" action="<?= stu_url('action.php') ?>" style="margin-top:.5rem">
            <?= csrf_field() ?><input type="hidden" name="action" value="generate_login"><input type="hidden" name="id" id="mv_gen_id">
            <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-key"></i> Generate Login</button>
          </form>
        </div>
      </div>
    </div>
    <div class="u-modal-foot">
      <a href="#" id="mv_edit_link" class="u-btn u-btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
      <button type="button" class="u-btn u-btn-soft" data-modal-close>Close</button>
    </div>
  </div>
</div>

<?php
$umsUrl = UMS_URL;
$page_scripts = <<<HTML
<script>
document.querySelectorAll('.btn-stu-view').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d = btn.dataset;
    document.getElementById('mv_name').textContent    = d.name || 'Student';
    document.getElementById('mv_regno').textContent    = d.regno || '—';
    document.getElementById('mv_program').textContent  = d.program || '—';
    document.getElementById('mv_dept').textContent     = d.dept || '—';
    document.getElementById('mv_session').textContent  = d.session || '—';
    document.getElementById('mv_batch').textContent    = d.batch || '—';
    document.getElementById('mv_semester').textContent = d.semester || '—';
    document.getElementById('mv_father').textContent   = d.father || '—';
    document.getElementById('mv_gender').textContent   = d.gender || '—';
    document.getElementById('mv_dob').textContent      = d.dob || '—';
    document.getElementById('mv_cnic').textContent     = d.cnic || '—';
    document.getElementById('mv_phone').textContent    = d.phone || '—';
    document.getElementById('mv_email').textContent    = d.email || '—';
    document.getElementById('mv_address').textContent  = d.address || '—';
    document.getElementById('mv_status').innerHTML     = '<span class="st ' + d.statusClass + '">' + d.statusLabel + '</span>';

    var admRow = document.getElementById('mv_adm_row');
    if (d.admissionId && d.admissionId !== '0') {
      document.getElementById('mv_adm_link').href = '$umsUrl/modules/admissions/view.php?id=' + d.admissionId;
      admRow.style.display = '';
    } else {
      admRow.style.display = 'none';
    }

    document.getElementById('mv_edit_link').href = 'edit.php?id=' + d.id;

    var hasLogin = d.loginEmail && d.loginEmail.trim() !== '';
    document.getElementById('mv_login_has').style.display  = hasLogin ? '' : 'none';
    document.getElementById('mv_login_none').style.display = hasLogin ? 'none' : '';
    if (hasLogin) {
      document.getElementById('mv_login_email').value = d.loginEmail;
      document.getElementById('mv_reset_id').value = d.id;
    } else {
      document.getElementById('mv_gen_id').value = d.id;
    }

    window.umsOpenModal('stuViewModal');
  });
});
</script>
HTML;
?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
