<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

/** Settings — save profile fields + logo upload. */

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
    flash_set('error', 'Invalid request. Please try again.');
    redirect(set_url('index.php'));
}

$db = ums_db();

// Text settings
foreach (ums_setting_groups() as $fields) {
    foreach ($fields as $key => $meta) {
        if (array_key_exists($key, $_POST)) {
            ums_set_setting($key, trim((string)$_POST[$key]));
        }
    }
}

// Logo upload (optional)
if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $err = '';
    if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
        $err = 'Logo must be under 2 MB.';
    } else {
        $ext = strtolower(pathinfo((string)$_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true) || @getimagesize($_FILES['logo']['tmp_name']) === false) {
            $err = 'Logo must be a PNG, JPG or WEBP image.';
        } else {
            $dir = __DIR__ . '/../../uploads/branding';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            // remove the previous logo file
            $old = ums_setting('logo_path');
            if ($old !== '' && str_starts_with($old, 'uploads/branding/')) @unlink(__DIR__ . '/../../' . $old);
            $name = 'ums-logo-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], "$dir/$name")) {
                ums_set_setting('logo_path', 'uploads/branding/' . $name);
            } else {
                $err = 'Could not save the logo.';
            }
        }
    }
    if ($err !== '') { flash_set('error', $err); redirect(set_url('index.php')); }
}

// Remove logo
if (!empty($_POST['remove_logo'])) {
    $old = ums_setting('logo_path');
    if ($old !== '' && str_starts_with($old, 'uploads/branding/')) @unlink(__DIR__ . '/../../' . $old);
    ums_set_setting('logo_path', '');
}

ums_log('settings_update', 'Updated system settings');
flash_set('success', 'Settings saved.');
redirect(set_url('index.php'));
