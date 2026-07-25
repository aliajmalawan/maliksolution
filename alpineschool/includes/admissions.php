<?php
declare(strict_types=1);
// Shared admissions logic: application numbers, document uploads, status labels.

/** Document slots offered on the admission form. */
function admission_doc_types(): array
{
    return [
        'birth_certificate' => 'Birth Certificate',
        'previous_result' => 'Previous Result Card',
        'student_photo' => 'Student Photograph',
        'father_cnic' => "Father's / Guardian's CNIC",
        'other' => 'Other Supporting Document',
    ];
}

/** Application status labels + badge classes. */
function admission_statuses(): array
{
    return [
        'submitted' => ['label' => 'Submitted', 'badge' => 'badge-new'],
        'under_review' => ['label' => 'Under Review', 'badge' => 'badge-contacted'],
        'shortlisted' => ['label' => 'Shortlisted', 'badge' => 'badge-contacted'],
        'approved' => ['label' => 'Approved', 'badge' => 'badge-published'],
        'rejected' => ['label' => 'Rejected', 'badge' => 'badge-draft'],
        'enrolled' => ['label' => 'Enrolled', 'badge' => 'badge-enrolled'],
    ];
}

function admission_status_label(string $status): string
{
    return admission_statuses()[$status]['label'] ?? ucfirst($status);
}

function admission_status_badge(string $status): string
{
    return admission_statuses()[$status]['badge'] ?? 'badge-draft';
}

/** Generate the next application number, e.g. ALP-2026-0007. */
function next_application_number(PDO $pdo): string
{
    $prefix = strtoupper(trim(get_setting($pdo, 'admission_prefix', 'ALP'))) ?: 'ALP';
    $year = date('Y');
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $stmt = $pdo->prepare('SELECT app_number FROM applications WHERE app_number LIKE ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$prefix . '-' . $year . '-%']);
        $last = $stmt->fetch();
        $next = 1;
        if ($last && preg_match('/-(\d+)$/', (string)$last['app_number'], $m)) {
            $next = (int)$m[1] + 1;
        }
        $candidate = sprintf('%s-%s-%04d', $prefix, $year, $next);
        $check = $pdo->prepare('SELECT id FROM applications WHERE app_number = ?');
        $check->execute([$candidate]);
        if (!$check->fetch()) {
            return $candidate;
        }
    }
    // Extremely unlikely fallback.
    return sprintf('%s-%s-%s', $prefix, $year, strtoupper(bin2hex(random_bytes(2))));
}

/**
 * Store one uploaded admission document (image or PDF, max 5 MB).
 * @return array{path: string, name: string, size: int}|null
 */
function upload_admission_doc(array $file, string $appNumber, string $docType): ?array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf',
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime]) || $file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $subfolder = 'applications/' . preg_replace('/[^A-Za-z0-9-]/', '', $appNumber);
    $destDir = UPLOAD_DIR . '/' . $subfolder;
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $filename = $docType . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
        return null;
    }

    return [
        'path' => 'uploads/' . $subfolder . '/' . $filename,
        'name' => (string)($file['name'] ?? $filename),
        'size' => (int)$file['size'],
    ];
}

/** Class options offered on the admission form. */
function admission_classes(): array
{
    $classes = ['Montessori / Playgroup', 'Nursery', 'Prep'];
    for ($i = 1; $i <= 10; $i++) {
        $classes[] = 'Grade ' . $i;
    }
    return $classes;
}
