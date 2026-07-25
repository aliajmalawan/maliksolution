<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/admin/login.php');
}

$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([(int)$_SESSION['admin_id']]);
$currentAdmin = $stmt->fetch();

if (!$currentAdmin) {
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    redirect(BASE_URL . '/admin/login.php');
}

$_SESSION['admin_name'] = $currentAdmin['full_name'];

/**
 * Role hierarchy: editor < admin < super_admin.
 * Pages not listed here are open to every logged-in role (editor+).
 */
function admin_page_min_role(string $page): string
{
    $map = [
        'inquiries.php' => 'admin',
        'applications.php' => 'admin',
        'admission-settings.php' => 'admin',
        'messages.php' => 'admin',
        'analytics.php' => 'admin',
        'newsletter.php' => 'admin',
        'forms.php' => 'admin',
        'form-submissions.php' => 'admin',
        'contact-settings.php' => 'admin',
        'menus.php' => 'admin',
        'theme.php' => 'admin',
        'media.php' => 'admin',
        'seo.php' => 'admin',
        'popup.php' => 'admin',
        'settings.php' => 'super_admin',
        'integrations.php' => 'super_admin',
        'performance.php' => 'super_admin',
        'users.php' => 'super_admin',
        'permissions.php' => 'super_admin',
        'activity.php' => 'super_admin',
        'backup.php' => 'super_admin',
    ];
    return $map[$page] ?? 'editor';
}

function role_level(string $role): int
{
    return ['editor' => 1, 'admin' => 2, 'super_admin' => 3][$role] ?? 0;
}

function has_role(array $admin, string $minRole): bool
{
    return role_level($admin['role']) >= role_level($minRole);
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);
if (!has_role($currentAdmin, admin_page_min_role($currentPage))) {
    flash_set('error', 'You do not have permission to access that page.');
    redirect(BASE_URL . '/admin/index.php');
}

// Any admin write invalidates the public file cache (settings, menus, hero) so
// content changes appear on the site immediately.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    register_shutdown_function(static function (): void {
        cache_clear();
    });
}

$unreadNotifications = (int)$pdo->query('SELECT COUNT(*) c FROM notifications WHERE is_read = 0')->fetch()['c'];
$recentNotifications = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 7')->fetchAll();
