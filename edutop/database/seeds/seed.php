<?php

/**
 * Seeds roles, permissions, default role→permission grants, and the initial
 * Super Admin account (from .env). Safe to re-run — uses upserts and only
 * creates the Super Admin if no users exist yet.
 * Usage: php database/seeds/seed.php
 */

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;
use App\Core\Env;

$pdo = Database::connection();

$roles = [
    'super-admin' => 'Super Admin',
    'admin' => 'Admin',
    'manager' => 'Manager',
    'editor' => 'Editor',
    'marketing' => 'Marketing',
    'support' => 'Support',
    'content-writer' => 'Content Writer',
];

$roleIds = [];
foreach ($roles as $slug => $name) {
    $pdo->prepare('INSERT INTO roles (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)')
        ->execute([$name, $slug]);
    $roleIds[$slug] = (int) Database::one('SELECT id FROM roles WHERE slug = ?', [$slug])['id'];
}
echo "Roles seeded.\n";

$permissionModules = require dirname(__DIR__, 2) . '/config/permissions.php';

$permissionIds = [];
foreach ($permissionModules as $module => $permissions) {
    foreach ($permissions as $slug => $label) {
        $pdo->prepare(
            'INSERT INTO permissions (slug, module, label) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE label = VALUES(label), module = VALUES(module)'
        )->execute([$slug, $module, $label]);
        $permissionIds[$slug] = (int) Database::one('SELECT id FROM permissions WHERE slug = ?', [$slug])['id'];
    }
}
echo "Permissions seeded.\n";

$allPermissions = array_keys($permissionIds);

$defaultGrants = [
    'super-admin' => $allPermissions, // also bypasses checks in App\Core\Auth::can()
    'admin' => array_diff($allPermissions, ['roles.manage']),
    'manager' => ['dashboard.view', 'pages.view', 'pages.manage', 'media.view', 'media.manage', 'blog.view', 'blog.manage', 'blog.publish', 'leads.view', 'leads.manage', 'logs.view'],
    'editor' => ['dashboard.view', 'pages.view', 'pages.manage', 'media.view', 'media.manage', 'blog.view', 'blog.manage'],
    'marketing' => ['dashboard.view', 'blog.view', 'media.view', 'leads.view', 'leads.manage', 'settings.manage'],
    'support' => ['dashboard.view', 'leads.view', 'leads.manage', 'logs.view'],
    'content-writer' => ['dashboard.view', 'blog.view', 'blog.manage', 'media.view'],
];

foreach ($defaultGrants as $roleSlug => $permissionSlugs) {
    $roleId = $roleIds[$roleSlug];
    $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
    $stmt = $pdo->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
    foreach ($permissionSlugs as $slug) {
        if (isset($permissionIds[$slug])) {
            $stmt->execute([$roleId, $permissionIds[$slug]]);
        }
    }
}
echo "Role permissions assigned.\n";

$userCount = (int) Database::one('SELECT COUNT(*) AS c FROM users')['c'];
if ($userCount === 0) {
    $name = Env::get('SUPER_ADMIN_NAME', 'Super Admin');
    $email = Env::get('SUPER_ADMIN_EMAIL', 'admin@edutop.test');
    $password = Env::get('SUPER_ADMIN_PASSWORD');

    if (!$password) {
        echo "SUPER_ADMIN_PASSWORD is not set in .env — skipping Super Admin creation.\n";
    } else {
        $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        )->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $roleIds['super-admin'], 'active']);

        echo "Super Admin created: {$email}\n";
        echo "IMPORTANT: log in and change this password immediately.\n";
    }
} else {
    echo "Users already exist — skipping Super Admin creation.\n";
}

echo "Seeding complete.\n";
