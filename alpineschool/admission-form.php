<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/admissions.php';
require_once __DIR__ . '/includes/spam.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/seo.php';

$admissionsOpen = get_setting($pdo, 'admission_open', '1') === '1';
$session = get_setting($pdo, 'admission_session', '2026-27');
$instructions = get_setting($pdo, 'admission_instructions');

$formError = '';
$fieldErrors = [];   // field name => message, shown inline
$submitted = null;   // holds the created application on success
$old = [];

// The same handler serves both the normal POST and the AJAX submit; only the
// response format differs, so validation can never drift between the two paths.
$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

/** Send the AJAX response and stop. */
function admission_json(array $payload): never
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admissionsOpen) {
    if (!csrf_verify()) {
        $formError = 'Your session expired. Please refresh the page and try again.';
        $old = $_POST;
        if ($isAjax) {
            admission_json(['status' => 'error', 'message' => $formError, 'errors' => [], 'csrf' => csrf_token()]);
        }
    } else {
        $fields = [
            'student_name' => trim((string)($_POST['student_name'] ?? '')),
            'date_of_birth' => trim((string)($_POST['date_of_birth'] ?? '')),
            'gender' => (string)($_POST['gender'] ?? ''),
            'class_applying' => trim((string)($_POST['class_applying'] ?? '')),
            'previous_school' => trim((string)($_POST['previous_school'] ?? '')),
            'father_name' => trim((string)($_POST['father_name'] ?? '')),
            'father_occupation' => trim((string)($_POST['father_occupation'] ?? '')),
            'father_cnic' => trim((string)($_POST['father_cnic'] ?? '')),
            'mother_name' => trim((string)($_POST['mother_name'] ?? '')),
            'guardian_phone' => trim((string)($_POST['guardian_phone'] ?? '')),
            'guardian_email' => trim((string)($_POST['guardian_email'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'notes' => trim((string)($_POST['notes'] ?? '')),
        ];
        $old = $fields;

        $errors = [];
        if ($fields['student_name'] === '') {
            $errors['student_name'] = "Student's full name is required.";
        }
        if ($fields['class_applying'] === '') {
            $errors['class_applying'] = 'Please choose the class being applied for.';
        }
        if ($fields['father_name'] === '') {
            $errors['father_name'] = "Father's / guardian's name is required.";
        }
        if ($fields['guardian_phone'] === '') {
            $errors['guardian_phone'] = 'A contact phone number is required.';
        } elseif (!preg_match('/^[0-9 +()\-]{7,20}$/', $fields['guardian_phone'])) {
            $errors['guardian_phone'] = 'Please enter a valid phone number.';
        }
        if ($fields['guardian_email'] !== '' && !filter_var($fields['guardian_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['guardian_email'] = 'Please enter a valid email address.';
        }
        if ($fields['date_of_birth'] !== '' && strtotime($fields['date_of_birth']) > time()) {
            $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
        }
        if (!in_array($fields['gender'], ['male', 'female', 'other', ''], true)) {
            $fields['gender'] = '';
        }

        // Reject oversized/unsupported documents up-front with a field-level message.
        foreach (admission_doc_types() as $docType => $docLabel) {
            $file = $_FILES['doc_' . $docType] ?? null;
            if (!$file || empty($file['name'])) {
                continue;
            }
            if (($file['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_INI_SIZE || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
                $errors['doc_' . $docType] = $docLabel . ' is too large (max 5 MB).';
            }
        }

        $spam = spam_check($pdo, [$fields['student_name'], $fields['father_name'], $fields['notes']]);
        if ($spam !== null && $errors === []) {
            $errors['_form'] = 'Your submission was blocked: ' . $spam . '.';
        }

        if ($errors) {
            $fieldErrors = $errors;
            $count = count($errors);
            $formError = $count === 1
                ? reset($errors)
                : 'Please correct the ' . $count . ' highlighted fields below.';
            if ($isAjax) {
                admission_json(['status' => 'error', 'message' => $formError, 'errors' => $errors, 'csrf' => csrf_token()]);
            }
        } else {
            $appNumber = next_application_number($pdo);

            $pdo->prepare(
                'INSERT INTO applications
                 (app_number, student_name, date_of_birth, gender, class_applying, previous_school,
                  father_name, father_occupation, father_cnic, mother_name, guardian_phone, guardian_email,
                  address, notes, ip)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $appNumber,
                $fields['student_name'],
                $fields['date_of_birth'] ?: null,
                $fields['gender'] ?: null,
                $fields['class_applying'],
                $fields['previous_school'],
                $fields['father_name'],
                $fields['father_occupation'],
                $fields['father_cnic'],
                $fields['mother_name'],
                $fields['guardian_phone'],
                $fields['guardian_email'],
                $fields['address'],
                $fields['notes'],
                (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            ]);
            $appId = (int)$pdo->lastInsertId();

            // Documents
            $docInsert = $pdo->prepare('INSERT INTO application_documents (application_id, doc_type, file_path, original_name, file_size) VALUES (?, ?, ?, ?, ?)');
            $uploadedDocs = 0;
            foreach (array_keys(admission_doc_types()) as $docType) {
                if (!empty($_FILES['doc_' . $docType]['name'])) {
                    $doc = upload_admission_doc($_FILES['doc_' . $docType], $appNumber, $docType);
                    if ($doc) {
                        $docInsert->execute([$appId, $docType, $doc['path'], $doc['name'], $doc['size']]);
                        $uploadedDocs++;
                    }
                }
            }

            // Keep the legacy inquiries inbox in sync.
            $pdo->prepare('INSERT INTO inquiries (student_name, parent_name, phone, email, class_applying, message) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([
                    $fields['student_name'], $fields['father_name'], $fields['guardian_phone'],
                    $fields['guardian_email'], $fields['class_applying'],
                    'Online application ' . $appNumber,
                ]);

            notify($pdo, 'inquiry', 'New online application ' . $appNumber,
                $fields['student_name'] . ' — ' . $fields['class_applying'], 'applications.php?app=' . $appId);

            // Notify the school
            $recipients = trim(get_setting($pdo, 'admission_notify_emails')) ?: get_setting($pdo, 'email');
            if ($recipients !== '') {
                send_mail($pdo, $recipients, 'New admission application ' . $appNumber,
                    submission_email_html($pdo, 'Admission Application', [
                        'Application Number' => $appNumber,
                        'Student' => $fields['student_name'],
                        'Class' => $fields['class_applying'],
                        'Father / Guardian' => $fields['father_name'],
                        'Phone' => $fields['guardian_phone'],
                        'Email' => $fields['guardian_email'],
                    ]));
            }

            // Confirmation to the applicant
            if ($fields['guardian_email'] !== '') {
                send_mail($pdo, $fields['guardian_email'], 'Your application ' . $appNumber . ' has been received',
                    submission_email_html($pdo, 'Application Received', [
                        'Application Number' => $appNumber,
                        'Student' => $fields['student_name'],
                        'Class Applied For' => $fields['class_applying'],
                        'Status' => 'Submitted — our admissions team will review it shortly.',
                        'Track your application' => seo_site_url($pdo) . '/application-status.php?app=' . $appNumber,
                    ]));
            }

            $stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
            $stmt->execute([$appId]);
            $submitted = $stmt->fetch();
            $submitted['doc_count'] = $uploadedDocs;

            if ($isAjax) {
                admission_json([
                    'status' => 'success',
                    'message' => 'Application submitted successfully.',
                    'errors' => [],
                    'app_number' => $appNumber,
                    'doc_count' => $uploadedDocs,
                    'student_name' => $fields['student_name'],
                    'class_applying' => $fields['class_applying'],
                    'status_url' => 'application-status.php?app=' . urlencode($appNumber),
                    'print_url' => 'application-print.php?app=' . urlencode($appNumber),
                ]);
            }
        }
    }
}

$pageTitle = 'Online Admission Form';
$breadcrumbs = [['label' => 'Admissions', 'url' => 'admissions.php'], ['label' => 'Apply Online']];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
  <div class="container">
    <h1>Online Admission Form</h1>
    <p class="crumb"><?= seo_breadcrumb_html($breadcrumbs) ?></p>
  </div>
</div>

<section class="section">
  <div class="container" style="max-width:860px;">

    <?php if ($submitted): ?>
      <div class="app-success">
        <div style="font-size:56px;">🎉</div>
        <h2>Application Submitted</h2>
        <p>Thank you! We have received the application for <strong><?= e($submitted['student_name']) ?></strong>.</p>
        <div class="app-number-box">
          <small>Your Application Number</small>
          <strong><?= e($submitted['app_number']) ?></strong>
        </div>
        <p style="color:var(--text-light);">Please save this number — you'll need it to track your application status<?= $submitted['guardian_email'] ? ' (a confirmation email has also been sent to you)' : '' ?>.</p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:20px;">
          <a href="application-status.php?app=<?= e($submitted['app_number']) ?>" class="btn btn-dark">Track Application</a>
          <a href="application-print.php?app=<?= e($submitted['app_number']) ?>" target="_blank" class="btn btn-outline">🖨 Print Application</a>
        </div>
      </div>

    <?php elseif (!$admissionsOpen): ?>
      <div role="alert" class="alert alert-error" style="text-align:center;">
        <strong>Admissions are currently closed.</strong><br>
        Please check back soon or <a href="contact.php">contact us</a> for the next admission window.
      </div>

    <?php else: ?>
      <div class="app-intro">
        <span class="eyebrow">Session <?= e($session) ?></span>
        <p><?= e($instructions) ?></p>
      </div>

      <?php if ($formError): ?>
        <div role="alert" class="alert alert-error"><?= e($formError) ?></div>
      <?php endif; ?>

      <form class="form-card" method="post" action="admission-form.php" enctype="multipart/form-data"
            aria-label="Online admission application"
            data-ajax data-success-template="application" data-loading-text="Submitting application…">
        <?= csrf_field() ?>
        <?= spam_fields() ?>
        <?php
        /** Inline error paragraph for a hand-coded field (mirrors render_form_fields). */
        function app_field_error(array $fieldErrors, string $name): string
        {
            $msg = (string)($fieldErrors[$name] ?? '');
            return '<p class="field-error" id="' . $name . '_err"' . ($msg === '' ? ' hidden' : '') . '>' . e($msg) . '</p>';
        }
        /** Attributes marking a field invalid, tied to its error message. */
        function app_invalid(array $fieldErrors, string $name): string
        {
            return isset($fieldErrors[$name])
                ? ' aria-invalid="true" aria-describedby="' . $name . '_err"'
                : '';
        }
        ?>

        <h3 class="app-section-title">1. Student Details</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="student_name">Student's Full Name *</label>
            <input type="text" id="student_name" name="student_name" required value="<?= e($old['student_name'] ?? '') ?>"<?= app_invalid($fieldErrors, 'student_name') ?>>
            <?= app_field_error($fieldErrors, 'student_name') ?>
          </div>
          <div class="form-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($old['date_of_birth'] ?? '') ?>"<?= app_invalid($fieldErrors, 'date_of_birth') ?>>
            <?= app_field_error($fieldErrors, 'date_of_birth') ?>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
              <option value="">Please choose…</option>
              <?php foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= ($old['gender'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="class_applying">Class Applying For *</label>
            <select id="class_applying" name="class_applying" required<?= app_invalid($fieldErrors, 'class_applying') ?>>
              <option value="">Please choose…</option>
              <?php foreach (admission_classes() as $class): ?>
                <option value="<?= e($class) ?>" <?= ($old['class_applying'] ?? '') === $class ? 'selected' : '' ?>><?= e($class) ?></option>
              <?php endforeach; ?>
            </select>
            <?= app_field_error($fieldErrors, 'class_applying') ?>
          </div>
        </div>
        <div class="form-group">
          <label for="previous_school">Previous School (if any)</label>
          <input type="text" id="previous_school" name="previous_school" value="<?= e($old['previous_school'] ?? '') ?>">
        </div>

        <h3 class="app-section-title">2. Parent / Guardian Details</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="father_name">Father's / Guardian's Name *</label>
            <input type="text" id="father_name" name="father_name" required value="<?= e($old['father_name'] ?? '') ?>"<?= app_invalid($fieldErrors, 'father_name') ?>>
            <?= app_field_error($fieldErrors, 'father_name') ?>
          </div>
          <div class="form-group">
            <label for="father_occupation">Occupation</label>
            <input type="text" id="father_occupation" name="father_occupation" value="<?= e($old['father_occupation'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="father_cnic">CNIC Number</label>
            <input type="text" id="father_cnic" name="father_cnic" placeholder="00000-0000000-0" value="<?= e($old['father_cnic'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="mother_name">Mother's Name</label>
            <input type="text" id="mother_name" name="mother_name" value="<?= e($old['mother_name'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="guardian_phone">Contact Phone *</label>
            <input type="tel" id="guardian_phone" name="guardian_phone" required value="<?= e($old['guardian_phone'] ?? '') ?>"<?= app_invalid($fieldErrors, 'guardian_phone') ?>>
            <?= app_field_error($fieldErrors, 'guardian_phone') ?>
          </div>
          <div class="form-group">
            <label for="guardian_email">Email Address</label>
            <input type="email" id="guardian_email" name="guardian_email" value="<?= e($old['guardian_email'] ?? '') ?>"<?= app_invalid($fieldErrors, 'guardian_email') ?>>
            <?= app_field_error($fieldErrors, 'guardian_email') ?>
            <p class="form-hint">We'll email your application number here.</p>
          </div>
        </div>
        <div class="form-group">
          <label for="address">Home Address</label>
          <input type="text" id="address" name="address" value="<?= e($old['address'] ?? '') ?>">
        </div>

        <h3 class="app-section-title">3. Anything Else?</h3>
        <div class="form-group">
          <label for="notes">Additional Notes (optional)</label>
          <textarea id="notes" name="notes" placeholder="Medical conditions, siblings at the school, questions…"><?= e($old['notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Application</button>
        <p class="form-hint" style="margin-top:10px;">Already applied? <a href="application-status.php" style="color:var(--primary);font-weight:600;">Track your application →</a></p>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
