<?php
declare(strict_types=1);

require_once __DIR__ . '/perf.php';

function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        // Served from the file cache when warm — saves a DB round-trip on every page.
        $cache = cache_remember('settings', function () use ($pdo): array {
            $rows = [];
            foreach ($pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
                $rows[$row['setting_key']] = $row['setting_value'];
            }
            return $rows;
        });
    }
    return $cache[$key] ?? $default;
}

/** Active menu tree for a menu slug, cached to disk. */
function cached_menu(PDO $pdo, string $slug): array
{
    return cache_remember('menu_' . $slug, function () use ($pdo, $slug): array {
        $stmt = $pdo->prepare(
            'SELECT i.* FROM menu_items i INNER JOIN menus m ON m.id = i.menu_id
             WHERE m.slug = ? AND i.is_active = 1 ORDER BY i.sort_order, i.id'
        );
        $stmt->execute([$slug]);
        $children = [];
        foreach ($stmt->fetchAll() as $row) {
            $children[(int)($row['parent_id'] ?? 0)][] = $row;
        }
        return $children;
    });
}

/** Footer link columns, cached to disk. */
function cached_footer_menus(PDO $pdo): array
{
    return cache_remember('menu_footer', function () use ($pdo): array {
        $rows = $pdo->query(
            "SELECT m.name AS menu_name, i.label, i.url, i.icon, i.new_tab
             FROM menus m INNER JOIN menu_items i ON i.menu_id = m.id
             WHERE m.slug IN ('footer-quick', 'footer-explore') AND i.is_active = 1
             ORDER BY FIELD(m.slug, 'footer-quick', 'footer-explore'), i.sort_order, i.id"
        )->fetchAll();
        $columns = [];
        foreach ($rows as $link) {
            $columns[$link['menu_name']][] = $link;
        }
        return $columns;
    });
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function upload_image(array $file, string $subfolder): ?string
{
    global $pdo;

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $ext = $allowed[$mime];
    $destDir = UPLOAD_DIR . '/' . trim($subfolder, '/');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    // SEO-friendly filename from the original name, unique within the folder.
    $originalName = (string)($file['name'] ?? '');
    $filename = seo_filename($originalName, $ext, $destDir);
    $destPath = $destDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return null;
    }

    // Compression / max-width / WebP pipeline (GIFs skipped to preserve animation).
    if ($ext !== 'gif' && isset($pdo)) {
        $newPath = process_uploaded_image($pdo, $destPath, $ext);
        if ($newPath !== null) {
            $destPath = $newPath;
            $filename = basename($newPath);
        }
    }

    $relPath = trim($subfolder, '/') . '/' . $filename;

    if (isset($pdo)) {
        try {
            $pdo->prepare('INSERT IGNORE INTO media (file_path, original_name) VALUES (?, ?)')
                ->execute(['uploads/' . $relPath, $originalName]);
        } catch (PDOException $e) {
            // media table missing (pre-migration import) — non-fatal
        }
    }

    return $relPath;
}

/** Build a unique, SEO-friendly filename like "sports-day-2026.jpg". */
function seo_filename(string $originalName, string $ext, string $destDir): string
{
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    if ($base === '' || preg_match('/^[0-9a-f]{16,}$/', $base)) {
        $base = 'image-' . date('Ymd');
    }
    $base = mb_substr($base, 0, 80);
    $filename = $base . '.' . $ext;
    $n = 1;
    while (file_exists($destDir . '/' . $filename)) {
        $filename = $base . '-' . (++$n) . '.' . $ext;
    }
    return $filename;
}

/** Load a GD image from a file by extension. */
function gd_load(string $path, string $ext)
{
    return match ($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        'png' => @imagecreatefrompng($path),
        'webp' => @imagecreatefromwebp($path),
        default => false,
    };
}

/** Save a GD image to a file by extension (quality 0-100). */
function gd_save($img, string $path, string $ext, int $quality): bool
{
    return match ($ext) {
        'jpg', 'jpeg' => imagejpeg($img, $path, $quality),
        'png' => imagepng($img, $path, (int)round((100 - $quality) / 11.111)),
        'webp' => imagewebp($img, $path, $quality),
        default => false,
    };
}

/**
 * Apply the media settings pipeline to a freshly uploaded image:
 * scale down to media_max_width, re-encode at media_quality, convert to WebP.
 * Returns the (possibly new) absolute path, or null if untouched.
 */
function process_uploaded_image(PDO $pdo, string $path, string $ext): ?string
{
    if (!extension_loaded('gd')) {
        return null;
    }

    $compress = get_setting($pdo, 'media_compress', '1') === '1';
    $toWebp = get_setting($pdo, 'media_webp', '1') === '1' && function_exists('imagewebp');
    $quality = max(30, min(100, (int)get_setting($pdo, 'media_quality', '82')));
    $maxWidth = max(320, (int)get_setting($pdo, 'media_max_width', '1920'));

    $img = gd_load($path, $ext);
    if (!$img) {
        return null;
    }

    $w = imagesx($img);
    $needsResize = $w > $maxWidth;
    $needsConvert = $toWebp && $ext !== 'webp';

    if (!$compress && !$needsResize && !$needsConvert) {
        imagedestroy($img);
        return null;
    }

    if ($needsResize) {
        $img = imagescale($img, $maxWidth);
    }

    if ($ext === 'png' || $ext === 'webp') {
        imagealphablending($img, false);
        imagesavealpha($img, true);
    }

    if ($needsConvert) {
        $newPath = preg_replace('/\.[a-z]+$/i', '.webp', $path);
        if (imagewebp($img, $newPath, $quality)) {
            imagedestroy($img);
            if ($newPath !== $path) {
                unlink($path);
            }
            return $newPath;
        }
        imagedestroy($img);
        return null;
    }

    gd_save($img, $path, $ext, $quality);
    imagedestroy($img);
    return $path;
}

/** Estimated reading time for HTML content, e.g. "3 min read" (~200 wpm). */
function reading_time(?string $html): string
{
    $words = str_word_count(strip_tags((string)$html));
    $minutes = max(1, (int)ceil($words / 200));
    return $minutes . ' min read';
}

/** Alt text stored for a media file ('' when none). */
function media_alt(PDO $pdo, string $filePath, string $fallback = ''): string
{
    static $cache = [];
    if (!array_key_exists($filePath, $cache)) {
        try {
            $stmt = $pdo->prepare('SELECT alt_text FROM media WHERE file_path = ?');
            $stmt->execute([$filePath]);
            $cache[$filePath] = (string)($stmt->fetch()['alt_text'] ?? '');
        } catch (PDOException $e) {
            $cache[$filePath] = '';
        }
    }
    return $cache[$filePath] !== '' ? $cache[$filePath] : $fallback;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** @return array{path: string, size: int}|null */
function upload_file(array $file, string $subfolder, array $allowedMimes): ?array
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedMimes[$mime])) {
        return null;
    }

    if ($file['size'] > 15 * 1024 * 1024) {
        return null;
    }

    $ext = $allowedMimes[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = UPLOAD_DIR . '/' . trim($subfolder, '/');

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $destPath = $destDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return null;
    }

    return ['path' => trim($subfolder, '/') . '/' . $filename, 'size' => (int)$file['size']];
}

/** @return array<string,string> mime => extension, for common office/PDF documents */
function document_mime_map(): array
{
    return [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/zip' => 'zip',
    ];
}

function human_filesize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function format_date(?string $date): string
{
    if (empty($date)) {
        return '';
    }
    return date('d M Y', strtotime($date));
}

/** Upload an MP4/WebM video (max 50 MB). Returns the relative path under uploads/ or null. */
function upload_video(array $file, string $subfolder): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime]) || $file['size'] > 50 * 1024 * 1024) {
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destDir = UPLOAD_DIR . '/' . trim($subfolder, '/');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
        return null;
    }
    return trim($subfolder, '/') . '/' . $filename;
}

/** Extract the video id from a YouTube watch/share/shorts/embed URL. Empty string if not recognized. */
function youtube_id(string $url): string
{
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~', $url, $m)) {
        return $m[1];
    }
    return '';
}

function notify(PDO $pdo, string $type, string $title, string $message, string $link): void
{
    $pdo->prepare('INSERT INTO notifications (type, title, message, link) VALUES (?, ?, ?, ?)')
        ->execute([$type, mb_substr($title, 0, 200), mb_substr($message, 0, 400), $link]);
}

function log_activity(PDO $pdo, ?int $adminId, string $adminName, string $action, string $details = ''): void
{
    $pdo->prepare('INSERT INTO activity_logs (admin_id, admin_name, action, details) VALUES (?, ?, ?, ?)')
        ->execute([$adminId, $adminName, mb_substr($action, 0, 100), mb_substr($details, 0, 400)]);
}

function time_ago(?string $datetime): string
{
    if (empty($datetime)) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $m = (int)floor($diff / 60);
        return $m . ' min' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $h = (int)floor($diff / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400 * 7) {
        $d = (int)floor($diff / 86400);
        return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
    }
    return format_date($datetime);
}

function track_page_view(PDO $pdo): void
{
    $url = (string)strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $referrer = isset($_SERVER['HTTP_REFERER']) ? substr((string)$_SERVER['HTTP_REFERER'], 0, 500) : null;

    $pdo->prepare('INSERT INTO page_views (url, ip, user_agent, referrer, created_at) VALUES (?, ?, ?, ?, NOW())')
        ->execute([substr($url, 0, 255), $ip, $userAgent, $referrer]);
}

/** @return array{browser: string, system: string, device: string} */
function classify_user_agent(string $ua): array
{
    $browser = 'Other';
    if (stripos($ua, 'Edg') !== false) { $browser = 'Edge'; }
    elseif (stripos($ua, 'OPR') !== false || stripos($ua, 'Opera') !== false) { $browser = 'Opera'; }
    elseif (stripos($ua, 'SamsungBrowser') !== false) { $browser = 'Samsung Internet'; }
    elseif (stripos($ua, 'Firefox') !== false) { $browser = 'Firefox'; }
    elseif (stripos($ua, 'Chrome') !== false || stripos($ua, 'CriOS') !== false) { $browser = 'Chrome'; }
    elseif (stripos($ua, 'Safari') !== false) { $browser = 'Safari'; }

    $system = 'Other';
    if (stripos($ua, 'Windows') !== false) { $system = 'Windows'; }
    elseif (stripos($ua, 'Android') !== false) { $system = 'Android'; }
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iOS') !== false) { $system = 'iOS'; }
    elseif (stripos($ua, 'Mac OS') !== false || stripos($ua, 'Macintosh') !== false) { $system = 'macOS'; }
    elseif (stripos($ua, 'Linux') !== false) { $system = 'Linux'; }

    $device = 'Desktop';
    if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) { $device = 'Tablet'; }
    elseif (stripos($ua, 'Mobi') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) { $device = 'Mobile'; }

    return ['browser' => $browser, 'system' => $system, 'device' => $device];
}
