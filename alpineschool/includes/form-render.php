<?php
declare(strict_types=1);
// Renders + processes a dynamic form (built in Admin → Form Builder).
require_once __DIR__ . '/spam.php';
require_once __DIR__ . '/mailer.php';

/** Load a form and its fields by slug. Returns null when missing/inactive. */
function load_form(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM forms WHERE slug = ? AND is_active = 1');
    $stmt->execute([$slug]);
    $form = $stmt->fetch();
    if (!$form) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order, id');
    $stmt->execute([(int)$form['id']]);
    $form['fields'] = $stmt->fetchAll();
    return $form;
}

/**
 * Handle a POST for the given form.
 * @return array{status: 'none'|'success'|'error', message: string, data: array}
 */
function handle_form_submit(PDO $pdo, array $form): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (string)($_POST['form_slug'] ?? '') !== $form['slug']) {
        return ['status' => 'none', 'message' => '', 'data' => [], 'errors' => []];
    }
    if (!csrf_verify()) {
        return ['status' => 'error', 'message' => 'Your session expired. Please refresh the page and try again.', 'data' => [], 'errors' => []];
    }

    $values = [];
    $errors = [];      // field_key => message, for inline display
    foreach ($form['fields'] as $field) {
        $key = $field['field_key'];
        $raw = $field['field_type'] === 'checkbox'
            ? (isset($_POST[$key]) ? 'Yes' : 'No')
            : trim((string)($_POST[$key] ?? ''));

        if ($field['is_required'] && ($raw === '' || ($field['field_type'] === 'checkbox' && $raw === 'No'))) {
            $errors[$key] = $field['label'] . ' is required.';
            continue;
        }
        if ($raw !== '' && $field['field_type'] === 'email' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            $errors[$key] = 'Please enter a valid email address.';
            continue;
        }
        if ($raw !== '' && $field['field_type'] === 'tel' && !preg_match('/^[0-9 +()\-]{7,20}$/', $raw)) {
            $errors[$key] = 'Please enter a valid phone number.';
            continue;
        }
        $values[$field['label']] = mb_substr($raw, 0, 3000);
    }

    if ($errors) {
        $count = count($errors);
        return [
            'status' => 'error',
            'message' => $count === 1
                ? reset($errors)
                : 'Please correct the ' . $count . ' highlighted fields below.',
            'data' => $_POST,
            'errors' => $errors,
        ];
    }

    $spam = spam_check($pdo, array_values($values));
    if ($spam !== null) {
        // Silently accept honeypot hits so bots don't learn; show a real message for rate limits.
        if (in_array($spam, ['honeypot', 'blocked content', 'too many links'], true)) {
            return ['status' => 'success', 'message' => $form['success_message'], 'data' => [], 'errors' => []];
        }
        return ['status' => 'error', 'message' => 'Your submission was blocked: ' . $spam . '.', 'data' => $_POST, 'errors' => []];
    }

    $pdo->prepare('INSERT INTO form_submissions (form_id, data, ip) VALUES (?, ?, ?)')
        ->execute([(int)$form['id'], json_encode($values, JSON_UNESCAPED_UNICODE), (string)($_SERVER['REMOTE_ADDR'] ?? '')]);

    // Legacy mirror: the Contact form also feeds the existing Contact Messages inbox.
    if ($form['slug'] === 'contact') {
        $pdo->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                $values['Your Name'] ?? ($values['Name'] ?? 'Website visitor'),
                $values['Email'] ?? '',
                $values['Phone'] ?? '',
                $values['Subject'] ?? '',
                $values['Message'] ?? '',
            ]);
    }

    notify($pdo, 'message', 'New ' . $form['name'] . ' submission',
        (string)(reset($values) ?: 'View submission'), 'form-submissions.php?form=' . (int)$form['id']);

    // Email notification (never blocks the user if mail fails).
    $recipients = trim((string)$form['notify_emails'])
        ?: trim(get_setting($pdo, 'contact_notify_emails'))
        ?: get_setting($pdo, 'email');
    if ($recipients !== '') {
        send_mail($pdo, $recipients, 'New ' . $form['name'] . ' submission', submission_email_html($pdo, $form['name'], $values));
    }

    return ['status' => 'success', 'message' => $form['success_message'], 'data' => [], 'errors' => []];
}

/**
 * Render a form result banner that assistive tech announces.
 * role="alert" for errors (interrupts), role="status" for success (polite).
 */
function form_alert_html(array $result): string
{
    if (($result['status'] ?? 'none') === 'none' || ($result['message'] ?? '') === '') {
        return '';
    }
    $isError = $result['status'] === 'error';
    return '<div class="alert alert-' . ($isError ? 'error' : 'success') . '"'
        . ' role="' . ($isError ? 'alert' : 'status') . '" aria-live="' . ($isError ? 'assertive' : 'polite') . '">'
        . '<span class="sr-only">' . ($isError ? 'Error: ' : 'Success: ') . '</span>'
        . e((string)$result['message'])
        . '</div>';
}

/** Render the form's fields + spam/CSRF hidden inputs. */
function render_form_fields(array $form, array $old = [], array $errors = []): void
{
    $buffer = [];
    $flush = function () use (&$buffer) {
        if (!$buffer) {
            return;
        }
        if (count($buffer) === 1) {
            echo '<div class="form-group">' . $buffer[0] . '</div>';
        } else {
            echo '<div class="form-row">';
            foreach ($buffer as $html) {
                echo '<div class="form-group">' . $html . '</div>';
            }
            echo '</div>';
        }
        $buffer = [];
    };

    foreach ($form['fields'] as $field) {
        $key = $field['field_key'];
        $id = 'f_' . $key;
        $value = (string)($old[$key] ?? '');
        // aria-required duplicates `required` for older screen readers that
        // don't map the native attribute; the visual "*" gets a text equivalent.
        $req = $field['is_required'] ? ' required aria-required="true"' : '';
        $star = $field['is_required'] ? ' <span aria-hidden="true">*</span><span class="sr-only">(required)</span>' : '';
        $ph = $field['placeholder'] !== '' ? ' placeholder="' . e($field['placeholder']) . '"' : '';

        // Inline error: the input points at its message so screen readers read them together.
        $errMsg = (string)($errors[$key] ?? '');
        $errId = $id . '_err';
        $invalid = $errMsg !== '' ? ' aria-invalid="true" aria-describedby="' . $errId . '"' : '';
        $errHtml = '<p class="field-error" id="' . $errId . '"' . ($errMsg === '' ? ' hidden' : '') . '>'
            . ($errMsg !== '' ? e($errMsg) : '') . '</p>';

        $html = '<label for="' . $id . '">' . e($field['label']) . $star . '</label>';

        switch ($field['field_type']) {
            case 'textarea':
                $html .= '<textarea id="' . $id . '" name="' . e($key) . '"' . $req . $invalid . $ph . '>' . e($value) . '</textarea>';
                break;
            case 'select':
                $html .= '<select id="' . $id . '" name="' . e($key) . '"' . $req . $invalid . '>';
                $html .= '<option value="">Please choose…</option>';
                foreach (array_filter(array_map('trim', explode(',', (string)$field['options']))) as $opt) {
                    $sel = $value === $opt ? ' selected' : '';
                    $html .= '<option value="' . e($opt) . '"' . $sel . '>' . e($opt) . '</option>';
                }
                $html .= '</select>';
                break;
            case 'checkbox':
                $checked = $value !== '' ? ' checked' : '';
                $html = '<label for="' . $id . '" style="font-weight:400;"><input type="checkbox" id="' . $id . '" name="' . e($key) . '"' . $checked . $req . $invalid . '> ' . e($field['label']) . $star . '</label>';
                break;
            default:
                $type = in_array($field['field_type'], ['email', 'tel', 'number', 'date'], true) ? $field['field_type'] : 'text';
                $html .= '<input type="' . $type . '" id="' . $id . '" name="' . e($key) . '" value="' . e($value) . '"' . $req . $invalid . $ph . '>';
        }

        $html .= $errHtml;

        if ($field['half_width']) {
            $buffer[] = $html;
            if (count($buffer) === 2) {
                $flush();
            }
        } else {
            $flush();
            echo '<div class="form-group">' . $html . '</div>';
        }
    }
    $flush();

    echo csrf_field();
    echo '<input type="hidden" name="form_slug" value="' . e($form['slug']) . '">';
    echo spam_fields();
}
