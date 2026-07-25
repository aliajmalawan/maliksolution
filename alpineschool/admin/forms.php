<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$fieldTypes = ['text' => 'Text', 'email' => 'Email', 'tel' => 'Phone', 'number' => 'Number',
    'textarea' => 'Long text', 'select' => 'Dropdown', 'checkbox' => 'Checkbox', 'date' => 'Date'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/forms.php');
    }

    $action = (string)($_POST['action'] ?? '');
    $formId = (int)($_POST['form_id'] ?? 0);
    $back = BASE_URL . '/admin/forms.php?form=' . $formId;

    if ($action === 'create_form') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            flash_set('error', 'Form name is required.');
            redirect(BASE_URL . '/admin/forms.php');
        }
        $slug = slugify($name);
        $base = $slug;
        $n = 1;
        $check = $pdo->prepare('SELECT id FROM forms WHERE slug = ?');
        $check->execute([$slug]);
        while ($check->fetch()) {
            $slug = $base . '-' . (++$n);
            $check->execute([$slug]);
        }
        $pdo->prepare('INSERT INTO forms (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
        $newId = (int)$pdo->lastInsertId();
        log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'form_create', 'Created form "' . $name . '"');
        flash_set('success', 'Form created — now add some fields.');
        redirect(BASE_URL . '/admin/forms.php?form=' . $newId);
    }

    if ($action === 'save_form') {
        $pdo->prepare('UPDATE forms SET name=?, description=?, success_message=?, notify_emails=?, is_active=? WHERE id=?')
            ->execute([
                trim((string)($_POST['name'] ?? '')),
                trim((string)($_POST['description'] ?? '')),
                trim((string)($_POST['success_message'] ?? '')) ?: 'Thank you! Your message has been received.',
                trim((string)($_POST['notify_emails'] ?? '')),
                isset($_POST['is_active']) ? 1 : 0,
                $formId,
            ]);
        flash_set('success', 'Form settings saved.');
        redirect($back);
    }

    if ($action === 'delete_form') {
        $stmt = $pdo->prepare('SELECT slug FROM forms WHERE id = ?');
        $stmt->execute([$formId]);
        $form = $stmt->fetch();
        if ($form && $form['slug'] === 'contact') {
            flash_set('error', 'The Contact form cannot be deleted — the Contact page depends on it.');
            redirect($back);
        }
        $pdo->prepare('DELETE FROM forms WHERE id = ?')->execute([$formId]);
        flash_set('success', 'Form and its submissions deleted.');
        redirect(BASE_URL . '/admin/forms.php');
    }

    if ($action === 'save_field') {
        $id = (int)($_POST['id'] ?? 0);
        $label = trim((string)($_POST['label'] ?? ''));
        $type = (string)($_POST['field_type'] ?? 'text');
        if (!isset($fieldTypes[$type])) {
            $type = 'text';
        }
        if ($label === '') {
            flash_set('error', 'Field label is required.');
            redirect($back);
        }
        $key = slugify((string)($_POST['field_key'] ?? '') ?: $label);
        $key = str_replace('-', '_', $key) ?: 'field';
        $options = trim((string)($_POST['options'] ?? ''));
        $placeholder = trim((string)($_POST['placeholder'] ?? ''));
        $required = isset($_POST['is_required']) ? 1 : 0;
        $half = isset($_POST['half_width']) ? 1 : 0;

        if ($id > 0) {
            $pdo->prepare('UPDATE form_fields SET label=?, field_key=?, field_type=?, options=?, placeholder=?, is_required=?, half_width=? WHERE id=? AND form_id=?')
                ->execute([$label, $key, $type, $options, $placeholder, $required, $half, $id, $formId]);
            flash_set('success', 'Field updated.');
        } else {
            $max = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0) m FROM form_fields WHERE form_id = ' . $formId)->fetch()['m'];
            $pdo->prepare('INSERT INTO form_fields (form_id, label, field_key, field_type, options, placeholder, is_required, half_width, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$formId, $label, $key, $type, $options, $placeholder, $required, $half, $max + 1]);
            flash_set('success', 'Field added — drag it into place.');
        }
        redirect($back);
    }

    if ($action === 'delete_field') {
        $pdo->prepare('DELETE FROM form_fields WHERE id = ? AND form_id = ?')->execute([(int)($_POST['id'] ?? 0), $formId]);
        flash_set('success', 'Field deleted.');
        redirect($back);
    }

    if ($action === 'save_order') {
        $order = array_map('intval', (array)($_POST['order'] ?? []));
        $stmt = $pdo->prepare('UPDATE form_fields SET sort_order = ? WHERE id = ? AND form_id = ?');
        foreach ($order as $i => $id) {
            $stmt->execute([$i + 1, $id, $formId]);
        }
        flash_set('success', 'Field order saved.');
        redirect($back);
    }
}

$forms = $pdo->query('SELECT f.*, (SELECT COUNT(*) FROM form_submissions WHERE form_id = f.id) AS submission_count FROM forms f ORDER BY f.id')->fetchAll();
$activeFormId = (int)($_GET['form'] ?? ($forms[0]['id'] ?? 0));
$activeForm = null;
foreach ($forms as $form) {
    if ((int)$form['id'] === $activeFormId) {
        $activeForm = $form;
    }
}
if (!$activeForm && $forms) {
    $activeForm = $forms[0];
    $activeFormId = (int)$activeForm['id'];
}

$fields = [];
if ($activeForm) {
    $stmt = $pdo->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order, id');
    $stmt->execute([$activeFormId]);
    $fields = $stmt->fetchAll();
}

$editingField = null;
if (isset($_GET['field'])) {
    foreach ($fields as $field) {
        if ((int)$field['id'] === (int)$_GET['field']) {
            $editingField = $field;
        }
    }
}

$pageTitle = 'Form Builder';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Forms</h2>
    <form method="post" action="forms.php" style="display:flex;gap:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_form">
      <input type="text" name="name" placeholder="New form name…" required style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;background:var(--surface-2);color:var(--text);font-family:inherit;">
      <button type="submit" class="btn btn-primary btn-sm">➕ Create Form</button>
    </form>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php foreach ($forms as $form): ?>
      <a href="forms.php?form=<?= (int)$form['id'] ?>" class="btn btn-sm <?= (int)$form['id'] === $activeFormId ? 'btn-primary' : 'btn-outline' ?>">
        <?= e($form['name']) ?> <span style="opacity:.65;">(<?= (int)$form['submission_count'] ?>)</span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($activeForm): ?>
<div class="grid-2" style="grid-template-columns:6fr 6fr;align-items:start;">
  <div class="card">
    <div class="card-header">
      <h2>Fields — <?= e($activeForm['name']) ?></h2>
      <a href="form-submissions.php?form=<?= $activeFormId ?>" class="btn btn-outline btn-sm">📥 Submissions (<?= (int)$activeForm['submission_count'] ?>)</a>
    </div>
    <?php if (empty($fields)): ?>
      <div class="empty-state">No fields yet — add your first field on the right.</div>
    <?php else: ?>
    <p class="form-hint" style="margin-bottom:12px;">Drag ⠿ to reorder, then Save Order.</p>
    <form method="post" action="forms.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_order">
      <input type="hidden" name="form_id" value="<?= $activeFormId ?>">
      <div id="hbList">
        <?php foreach ($fields as $field): ?>
        <div class="hb-row<?= $editingField && (int)$editingField['id'] === (int)$field['id'] ? ' hb-editing' : '' ?>" draggable="true" data-id="<?= (int)$field['id'] ?>">
          <span class="hb-grip">⠿</span>
          <span class="hb-label">
            <?= e($field['label']) ?><?= $field['is_required'] ? ' <span style="color:#dc2626;">*</span>' : '' ?>
            <small style="color:var(--muted);font-weight:400;"> <?= e($fieldTypes[$field['field_type']] ?? $field['field_type']) ?><?= $field['half_width'] ? ' · half' : '' ?></small>
          </span>
          <a href="forms.php?form=<?= $activeFormId ?>&field=<?= (int)$field['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
          <input type="hidden" name="order[]" value="<?= (int)$field['id'] ?>">
        </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:14px;">💾 Save Order</button>
    </form>
    <?php endif; ?>

    <div class="card-header" style="margin-top:26px;"><h2>Form Settings</h2></div>
    <form method="post" action="forms.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_form">
      <input type="hidden" name="form_id" value="<?= $activeFormId ?>">
      <div class="form-group">
        <label for="f_name">Form Name</label>
        <input type="text" id="f_name" name="name" value="<?= e($activeForm['name']) ?>" required>
      </div>
      <div class="form-group">
        <label for="f_success">Success Message</label>
        <input type="text" id="f_success" name="success_message" value="<?= e($activeForm['success_message']) ?>">
      </div>
      <div class="form-group">
        <label for="f_notify">Notification Emails (comma separated)</label>
        <input type="text" id="f_notify" name="notify_emails" placeholder="principal@school.com, office@school.com" value="<?= e($activeForm['notify_emails']) ?>">
        <p class="form-hint">Each submission is emailed here. Falls back to the global contact email if left empty. Configure SMTP in <a href="integrations.php" style="color:var(--primary);">Integrations</a>.</p>
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="is_active" <?= $activeForm['is_active'] ? 'checked' : '' ?>> Active (accepting submissions)</label>
      </div>
      <button type="submit" class="btn btn-primary">Save Form</button>
      <?php if ($activeForm['slug'] !== 'contact'): ?>
      <button type="submit" form="deleteForm" class="btn btn-danger" data-confirm="Delete this form and ALL its submissions?">Delete Form</button>
      <?php endif; ?>
    </form>
    <?php if ($activeForm['slug'] !== 'contact'): ?>
    <form method="post" action="forms.php" id="deleteForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_form">
      <input type="hidden" name="form_id" value="<?= $activeFormId ?>">
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h2><?= $editingField ? 'Edit Field' : 'Add Field' ?></h2>
      <?php if ($editingField): ?><a href="forms.php?form=<?= $activeFormId ?>" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <form method="post" action="forms.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_field">
      <input type="hidden" name="form_id" value="<?= $activeFormId ?>">
      <input type="hidden" name="id" value="<?= e((string)($editingField['id'] ?? '')) ?>">
      <div class="form-row">
        <div class="form-group">
          <label for="label">Label *</label>
          <input type="text" id="label" name="label" required value="<?= e($editingField['label'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="field_type">Field Type</label>
          <select id="field_type" name="field_type">
            <?php foreach ($fieldTypes as $value => $typeLabel): ?>
              <option value="<?= $value ?>" <?= ($editingField['field_type'] ?? 'text') === $value ? 'selected' : '' ?>><?= $typeLabel ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="options">Dropdown Options (comma separated — for Dropdown fields)</label>
        <input type="text" id="options" name="options" placeholder="Grade 1, Grade 2, Grade 3" value="<?= e($editingField['options'] ?? '') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="placeholder">Placeholder</label>
          <input type="text" id="placeholder" name="placeholder" value="<?= e($editingField['placeholder'] ?? '') ?>">
        </div>
        <div class="form-group" style="padding-top:26px;">
          <label style="font-weight:400;"><input type="checkbox" name="is_required" <?= !empty($editingField['is_required']) ? 'checked' : '' ?>> Required field</label>
          <label style="font-weight:400;"><input type="checkbox" name="half_width" <?= !empty($editingField['half_width']) ? 'checked' : '' ?>> Half width (side by side)</label>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><?= $editingField ? 'Update Field' : 'Add Field' ?></button>
      <?php if ($editingField): ?>
        <button type="submit" form="deleteField" class="btn btn-danger" data-confirm="Delete this field?">Delete</button>
      <?php endif; ?>
    </form>
    <?php if ($editingField): ?>
    <form method="post" action="forms.php" id="deleteField">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_field">
      <input type="hidden" name="form_id" value="<?= $activeFormId ?>">
      <input type="hidden" name="id" value="<?= (int)$editingField['id'] ?>">
    </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
