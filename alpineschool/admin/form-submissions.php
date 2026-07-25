<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$forms = $pdo->query('SELECT * FROM forms ORDER BY id')->fetchAll();
$formId = (int)($_GET['form'] ?? ($forms[0]['id'] ?? 0));
$activeForm = null;
foreach ($forms as $form) {
    if ((int)$form['id'] === $formId) {
        $activeForm = $form;
    }
}
if (!$activeForm && $forms) {
    $activeForm = $forms[0];
    $formId = (int)$activeForm['id'];
}

if (isset($_GET['export']) && $activeForm) {
    $stmt = $pdo->prepare('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at');
    $stmt->execute([$formId]);
    $rows = $stmt->fetchAll();

    $columns = [];
    $decoded = [];
    foreach ($rows as $row) {
        $data = json_decode((string)$row['data'], true) ?: [];
        $decoded[] = ['data' => $data, 'created_at' => $row['created_at']];
        foreach (array_keys($data) as $key) {
            $columns[$key] = true;
        }
    }
    $columns = array_keys($columns);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $activeForm['slug'] . '-submissions-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_merge($columns, ['Submitted At']));
    foreach ($decoded as $entry) {
        $line = [];
        foreach ($columns as $col) {
            $line[] = $entry['data'][$col] ?? '';
        }
        $line[] = $entry['created_at'];
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/form-submissions.php?form=' . $formId);
    }
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM form_submissions WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash_set('success', 'Submission deleted.');
    } elseif ($action === 'mark_read') {
        $pdo->prepare('UPDATE form_submissions SET is_read = 1 WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'mark_all_read') {
        $pdo->prepare('UPDATE form_submissions SET is_read = 1 WHERE form_id = ?')->execute([$formId]);
        flash_set('success', 'All submissions marked as read.');
    }
    redirect(BASE_URL . '/admin/form-submissions.php?form=' . $formId);
}

$submissions = [];
if ($activeForm) {
    $stmt = $pdo->prepare('SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC LIMIT 300');
    $stmt->execute([$formId]);
    $submissions = $stmt->fetchAll();
}

$pageTitle = 'Form Submissions';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Submissions</h2>
    <div style="display:flex;gap:8px;">
      <a href="forms.php?form=<?= $formId ?>" class="btn btn-outline btn-sm">⚙ Edit Form</a>
      <?php if ($submissions): ?>
        <a href="form-submissions.php?form=<?= $formId ?>&export=1" class="btn btn-primary btn-sm">⬇ Export CSV</a>
        <form method="post" action="form-submissions.php?form=<?= $formId ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="mark_all_read">
          <button type="submit" class="btn btn-outline btn-sm">Mark All Read</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php foreach ($forms as $form): ?>
      <a href="form-submissions.php?form=<?= (int)$form['id'] ?>" class="btn btn-sm <?= (int)$form['id'] === $formId ? 'btn-primary' : 'btn-outline' ?>"><?= e($form['name']) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2><?= e($activeForm['name'] ?? 'Form') ?> — <?= count($submissions) ?> submission<?= count($submissions) === 1 ? '' : 's' ?></h2></div>
  <?php if (empty($submissions)): ?>
    <div class="empty-state">No submissions yet.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Submission</th><th>When</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($submissions as $sub): $data = json_decode((string)$sub['data'], true) ?: []; ?>
        <tr>
          <td style="max-width:520px;">
            <?php foreach ($data as $label => $value): ?>
              <div style="margin-bottom:3px;"><strong style="font-size:12px;color:var(--text-light);"><?= e((string)$label) ?>:</strong> <?= e(mb_substr((string)$value, 0, 160)) ?></div>
            <?php endforeach; ?>
          </td>
          <td style="white-space:nowrap;"><?= e(time_ago($sub['created_at'])) ?></td>
          <td><span class="badge <?= $sub['is_read'] ? 'badge-published' : 'badge-new' ?>"><?= $sub['is_read'] ? 'Read' : 'New' ?></span></td>
          <td class="actions-cell">
            <?php if (!$sub['is_read']): ?>
            <form method="post" action="form-submissions.php?form=<?= $formId ?>" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm">Mark Read</button>
            </form>
            <?php endif; ?>
            <form method="post" action="form-submissions.php?form=<?= $formId ?>" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$sub['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this submission?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
