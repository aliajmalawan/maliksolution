<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/admissions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
require_once dirname(__DIR__) . '/includes/seo.php';

$statuses = admission_statuses();

// ---------- CSV export ----------
if (isset($_GET['export'])) {
    $rows = $pdo->query('SELECT a.* FROM applications a ORDER BY a.created_at DESC')->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="applications-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Application No', 'Student', 'DOB', 'Gender', 'Class', 'Previous School',
        'Father/Guardian', 'Occupation', 'CNIC', 'Mother', 'Phone', 'Email', 'Address',
        'Status', 'Remarks', 'Submitted']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['app_number'], $r['student_name'], $r['date_of_birth'], $r['gender'], $r['class_applying'],
            $r['previous_school'], $r['father_name'], $r['father_occupation'], $r['father_cnic'],
            $r['mother_name'], $r['guardian_phone'], $r['guardian_email'], $r['address'],
            admission_status_label($r['status']), $r['admin_remarks'], $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

// ---------- Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/applications.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        // Remove uploaded files with the record.
        $stmt = $pdo->prepare('SELECT file_path FROM application_documents WHERE application_id = ?');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $doc) {
            $full = dirname(__DIR__) . '/' . $doc['file_path'];
            if (preg_match('#^uploads/applications/#', $doc['file_path']) && is_file($full)) {
                unlink($full);
            }
        }
        $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'application_delete', 'Deleted application #' . $id);
        flash_set('success', 'Application and its documents deleted.');
        redirect(BASE_URL . '/admin/applications.php');
    }

    if ($action === 'review') {
        $status = (string)($_POST['status'] ?? 'submitted');
        if (!isset($statuses[$status])) {
            $status = 'submitted';
        }
        $remarks = trim((string)($_POST['admin_remarks'] ?? ''));
        $notifyApplicant = isset($_POST['notify_applicant']);

        $stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
        $stmt->execute([$id]);
        $app = $stmt->fetch();
        if (!$app) {
            flash_set('error', 'Application not found.');
            redirect(BASE_URL . '/admin/applications.php');
        }

        $pdo->prepare('UPDATE applications SET status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?')
            ->execute([$status, $remarks, (int)$currentAdmin['id'], $id]);

        // Mirror an approval/enrolment into the legacy inquiries board.
        if (in_array($status, ['approved', 'enrolled'], true)) {
            $pdo->prepare("UPDATE inquiries SET status = 'enrolled' WHERE message = ?")
                ->execute(['Online application ' . $app['app_number']]);
        }

        $mailNote = '';
        if ($notifyApplicant && $app['guardian_email'] !== '') {
            $result = send_mail(
                $pdo,
                (string)$app['guardian_email'],
                'Update on your application ' . $app['app_number'],
                submission_email_html($pdo, 'Application Update', array_filter([
                    'Application Number' => $app['app_number'],
                    'Student' => $app['student_name'],
                    'New Status' => admission_status_label($status),
                    'Remarks' => $remarks,
                    'Track your application' => seo_site_url($pdo) . '/application-status.php?app=' . $app['app_number'],
                ]))
            );
            $mailNote = $result['ok'] ? ' Applicant notified by email.' : ' (Email notice failed: ' . $result['message'] . ')';
        }

        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'application_review',
            $app['app_number'] . ' → ' . admission_status_label($status));
        flash_set('success', 'Application ' . $app['app_number'] . ' marked as ' . admission_status_label($status) . '.' . $mailNote);
        redirect(BASE_URL . '/admin/applications.php?app=' . $id);
    }
}

// ---------- Detail view ----------
$detail = null;
if (!empty($_GET['app'])) {
    $stmt = $pdo->prepare('SELECT a.*, ad.full_name AS reviewer FROM applications a LEFT JOIN admins ad ON ad.id = a.reviewed_by WHERE a.id = ?');
    $stmt->execute([(int)$_GET['app']]);
    $detail = $stmt->fetch() ?: null;
}

// ---------- List ----------
$filter = (string)($_GET['status'] ?? 'all');
$search = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];
if (isset($statuses[$filter])) {
    $where[] = 'a.status = ?';
    $params[] = $filter;
}
if ($search !== '') {
    $where[] = '(a.app_number LIKE ? OR a.student_name LIKE ? OR a.father_name LIKE ? OR a.guardian_phone LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT a.* FROM applications a $whereSql ORDER BY a.created_at DESC LIMIT 300"
);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$counts = ['all' => (int)$pdo->query('SELECT COUNT(*) c FROM applications')->fetch()['c']];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM applications GROUP BY status')->fetchAll() as $row) {
    $counts[$row['status']] = (int)$row['c'];
}

$pageTitle = 'Admission Applications';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($detail): ?>
<div class="card">
  <div class="card-header">
    <h2><?= e($detail['app_number']) ?> — <?= e($detail['student_name']) ?></h2>
    <div style="display:flex;gap:8px;">
      <a href="<?= BASE_URL ?>/application-print.php?id=<?= (int)$detail['id'] ?>" target="_blank" class="btn btn-outline btn-sm">🖨 Print</a>
      <a href="applications.php" class="btn btn-outline btn-sm">← Back to List</a>
    </div>
  </div>

  <div class="grid-2" style="align-items:start;">
    <div>
      <div class="table-wrap">
        <table>
          <tbody>
            <tr><th style="width:38%;">Status</th><td><span class="badge <?= admission_status_badge($detail['status']) ?>"><?= e(admission_status_label($detail['status'])) ?></span></td></tr>
            <tr><th>Class Applied For</th><td><?= e($detail['class_applying']) ?></td></tr>
            <tr><th>Date of Birth</th><td><?= $detail['date_of_birth'] ? format_date($detail['date_of_birth']) : '—' ?></td></tr>
            <tr><th>Gender</th><td><?= $detail['gender'] ? e(ucfirst($detail['gender'])) : '—' ?></td></tr>
            <tr><th>Previous School</th><td><?= e($detail['previous_school']) ?: '—' ?></td></tr>
            <tr><th>Father / Guardian</th><td><?= e($detail['father_name']) ?><?= $detail['father_occupation'] ? ' <small style="color:var(--muted);">(' . e($detail['father_occupation']) . ')</small>' : '' ?></td></tr>
            <tr><th>CNIC</th><td><?= e($detail['father_cnic']) ?: '—' ?></td></tr>
            <tr><th>Mother</th><td><?= e($detail['mother_name']) ?: '—' ?></td></tr>
            <tr><th>Phone</th><td><a href="tel:<?= e($detail['guardian_phone']) ?>" style="color:var(--primary);"><?= e($detail['guardian_phone']) ?></a></td></tr>
            <tr><th>Email</th><td><?= $detail['guardian_email'] ? '<a href="mailto:' . e($detail['guardian_email']) . '" style="color:var(--primary);">' . e($detail['guardian_email']) . '</a>' : '—' ?></td></tr>
            <tr><th>Address</th><td><?= e($detail['address']) ?: '—' ?></td></tr>
            <tr><th>Notes from Applicant</th><td><?= $detail['notes'] ? nl2br(e($detail['notes'])) : '—' ?></td></tr>
            <tr><th>Submitted</th><td><?= e(date('d M Y, H:i', strtotime($detail['created_at']))) ?></td></tr>
            <?php if ($detail['reviewed_at']): ?>
            <tr><th>Last Reviewed</th><td><?= e(time_ago($detail['reviewed_at'])) ?> by <?= e($detail['reviewer'] ?? '—') ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div>
      <div class="card-header"><h2>Review Decision</h2></div>
      <form method="post" action="applications.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="review">
        <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <?php foreach ($statuses as $value => $meta): ?>
              <option value="<?= $value ?>" <?= $detail['status'] === $value ? 'selected' : '' ?>><?= e($meta['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="admin_remarks">Remarks (shown to the applicant on their status page)</label>
          <textarea id="admin_remarks" name="admin_remarks" style="min-height:90px;"><?= e($detail['admin_remarks'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label style="font-weight:400;">
            <input type="checkbox" name="notify_applicant" <?= $detail['guardian_email'] ? 'checked' : 'disabled' ?>>
            Email the applicant about this decision
            <?php if (!$detail['guardian_email']): ?><span class="form-hint" style="display:inline;">(no email on file)</span><?php endif; ?>
          </label>
        </div>
        <button type="submit" class="btn btn-primary">Save Review</button>
        <button type="submit" form="deleteApp" class="btn btn-danger" data-confirm="Delete this application and all its uploaded documents permanently?">Delete</button>
      </form>
      <form method="post" action="applications.php" id="deleteApp">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h2>Applications (<?= count($applications) ?>)</h2>
    <div style="display:flex;gap:8px;">
      <form method="get" action="applications.php" style="display:flex;gap:6px;">
        <input type="hidden" name="status" value="<?= e($filter) ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search number, name, phone…"
               style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--surface-2);color:var(--text);font-family:inherit;font-size:13px;">
        <button type="submit" class="btn btn-outline btn-sm">Search</button>
      </form>
      <?php if ($applications): ?>
        <a href="applications.php?export=1" class="btn btn-primary btn-sm">⬇ Export CSV</a>
      <?php endif; ?>
    </div>
  </div>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    <a href="applications.php" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">All (<?= $counts['all'] ?>)</a>
    <?php foreach ($statuses as $value => $meta): ?>
      <a href="applications.php?status=<?= $value ?>" class="btn btn-sm <?= $filter === $value ? 'btn-primary' : 'btn-outline' ?>">
        <?= e($meta['label']) ?> (<?= $counts[$value] ?? 0 ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <div class="table-wrap">
    <?php if (empty($applications)): ?>
      <div class="empty-state">No applications<?= $filter !== 'all' || $search !== '' ? ' match this filter' : ' yet' ?>.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>App No.</th><th>Student</th><th>Class</th><th>Guardian</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($applications as $app): ?>
        <tr>
          <td style="white-space:nowrap;font-weight:600;"><?= e($app['app_number']) ?></td>
          <td><?= e($app['student_name']) ?></td>
          <td><?= e($app['class_applying']) ?></td>
          <td><?= e($app['father_name']) ?><br><small style="color:var(--muted);"><?= e($app['guardian_phone']) ?></small></td>
          <td><span class="badge <?= admission_status_badge($app['status']) ?>"><?= e(admission_status_label($app['status'])) ?></span></td>
          <td style="white-space:nowrap;"><?= e(time_ago($app['created_at'])) ?></td>
          <td class="actions-cell">
            <a href="applications.php?app=<?= (int)$app['id'] ?>" class="btn btn-outline btn-sm">Review</a>
            <a href="<?= BASE_URL ?>/application-print.php?id=<?= (int)$app['id'] ?>" target="_blank" class="btn btn-outline btn-sm">🖨</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<style>
.doc-list { display:flex; flex-direction:column; gap:10px; }
.doc-item { display:flex; gap:12px; align-items:center; padding:10px 12px; border:1px solid var(--border); border-radius:10px; background:var(--surface-2); transition:.15s; }
.doc-item:hover { border-color:var(--primary); }
.doc-item img { width:56px; height:56px; object-fit:cover; border-radius:8px; flex-shrink:0; }
.doc-item .doc-pdf { width:56px; height:56px; display:flex; align-items:center; justify-content:center; font-size:26px; background:var(--surface); border-radius:8px; flex-shrink:0; }
.doc-item strong { display:block; font-size:13.5px; }
.doc-item small { color:var(--text-light); font-size:11.5px; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
