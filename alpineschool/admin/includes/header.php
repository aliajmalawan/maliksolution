<?php
declare(strict_types=1);
// Expects auth.php to have run: $pdo, $currentAdmin, $unreadNotifications, $recentNotifications.

$logo_path = get_setting($pdo, 'logo_path');
$site_name = get_setting($pdo, 'site_name');
$current = basename($_SERVER['SCRIPT_NAME']);

$theme = ($_COOKIE['admin_theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$sbCollapsed = ($_COOKIE['admin_sb'] ?? '') === 'collapsed';

function admin_nav_active(string $page, string $current): string
{
    return $page === $current ? ' class="active"' : '';
}

/** Render one sidebar link if the current admin's role allows it. */
function nav_link(string $file, string $icon, string $label, string $current, array $admin): void
{
    if (!has_role($admin, admin_page_min_role($file))) {
        return;
    }
    echo '<a href="' . $file . '"' . admin_nav_active($file, $current) . '><span class="ico">' . $icon . '</span><span class="nav-label">' . e($label) . '</span></a>' . "\n";
}

$roleLabels = ['super_admin' => 'Super Admin', 'admin' => 'Admin', 'editor' => 'Editor'];
$notifIcons = ['inquiry' => '🎓', 'message' => '✉️', 'comment' => '💬', 'system' => '⚙️'];
$initials = mb_strtoupper(mb_substr($currentAdmin['full_name'], 0, 1));

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Dashboard') ?> | Admin — <?= e($site_name) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="icon" href="<?= BASE_URL ?>/<?= e($logo_path) ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= @filemtime(dirname(__DIR__) . '/assets/css/admin.css') ?: time() ?>">
</head>
<body>
<div class="admin-wrap<?= $sbCollapsed ? ' sb-collapsed' : '' ?>" id="adminWrap">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img src="<?= BASE_URL ?>/<?= e($logo_path) ?>" alt="Logo">
      <div>
        <strong><?= e($site_name) ?></strong>
        <small>Admin Dashboard</small>
      </div>
    </div>
    <nav class="sidebar-nav">
      <?php nav_link('index.php', '📊', 'Dashboard', $current, $currentAdmin); ?>
      <?php nav_link('analytics.php', '📈', 'Analytics', $current, $currentAdmin); ?>

      <div class="nav-group-title">Content</div>
      <?php nav_link('homepage.php', '🏠', 'Homepage Builder', $current, $currentAdmin); ?>
      <?php nav_link('theme.php', '🎨', 'Theme Builder', $current, $currentAdmin); ?>
      <?php nav_link('menus.php', '🧭', 'Menus', $current, $currentAdmin); ?>

      <?php
      // All content pages live inside one collapsible "Pages" group so the
      // sidebar stays short. Auto-open when the current page is inside it.
      $pagesGroup = [
          ['hero-slides.php', '🖼️', 'Hero Slider'],
          ['pages.php', '📄', 'Pages'],
          ['news.php', '📰', 'News'],
          ['blogs.php', '✍️', 'Blogs'],
          ['blog-comments.php', '💬', 'Comments'],
          ['events.php', '📅', 'Events'],
          ['notices.php', '📢', 'Notice Board'],
          ['gallery.php', '🌄', 'Gallery'],
          ['videos.php', '🎬', 'Videos'],
          ['faculty.php', '👩‍🏫', 'Faculty'],
          ['departments.php', '🏫', 'Departments'],
          ['testimonials.php', '💬', 'Testimonials'],
          ['faqs.php', '❓', 'FAQs'],
          ['partners.php', '🤝', 'Partners'],
          ['downloads.php', '📎', 'Downloads'],
          ['results.php', '🏅', 'Results'],
          ['career.php', '💼', 'Career'],
      ];
      $pagesGroupOpen = in_array($current, array_column($pagesGroup, 0), true);
      ?>
      <div class="nav-collapse<?= $pagesGroupOpen ? ' open' : '' ?>">
        <button type="button" class="nav-collapse-btn<?= $pagesGroupOpen ? ' active' : '' ?>" aria-expanded="<?= $pagesGroupOpen ? 'true' : 'false' ?>" aria-controls="navPagesList">
          <span class="ico">📚</span><span class="nav-label">Pages</span>
          <span class="nav-collapse-caret nav-label" aria-hidden="true">▾</span>
        </button>
        <div class="nav-collapse-list" id="navPagesList"<?= $pagesGroupOpen ? '' : ' hidden' ?>>
          <?php foreach ($pagesGroup as [$file, $icon, $label]) {
              nav_link($file, $icon, $label, $current, $currentAdmin);
          } ?>
        </div>
      </div>

      <?php if (has_role($currentAdmin, 'admin')): ?>
        <div class="nav-group-title">Inbox & Marketing</div>
        <?php nav_link('applications.php', '🎓', 'Applications', $current, $currentAdmin); ?>
        <?php nav_link('admission-settings.php', '🏫', 'Admission Settings', $current, $currentAdmin); ?>
        <?php nav_link('inquiries.php', '📋', 'Admission Inquiries', $current, $currentAdmin); ?>
        <?php nav_link('messages.php', '✉️', 'Contact Messages', $current, $currentAdmin); ?>
        <?php nav_link('forms.php', '🧩', 'Form Builder', $current, $currentAdmin); ?>
        <?php nav_link('form-submissions.php', '📥', 'Form Submissions', $current, $currentAdmin); ?>
        <?php nav_link('contact-settings.php', '📍', 'Contact Settings', $current, $currentAdmin); ?>
        <?php nav_link('newsletter.php', '📬', 'Newsletter', $current, $currentAdmin); ?>
        <?php nav_link('media.php', '🗂️', 'Media Manager', $current, $currentAdmin); ?>
        <?php nav_link('seo.php', '🔎', 'SEO Manager', $current, $currentAdmin); ?>
        <?php nav_link('popup.php', '🪧', 'Popup Manager', $current, $currentAdmin); ?>
      <?php endif; ?>

      <?php if (has_role($currentAdmin, 'super_admin')): ?>
        <div class="nav-group-title">System</div>
        <?php nav_link('settings.php', '⚙️', 'Site Settings', $current, $currentAdmin); ?>
        <?php nav_link('integrations.php', '🔌', 'Integrations', $current, $currentAdmin); ?>
        <?php nav_link('performance.php', '⚡', 'Performance', $current, $currentAdmin); ?>
        <?php nav_link('users.php', '👥', 'Admin Users', $current, $currentAdmin); ?>
        <?php nav_link('permissions.php', '🛡️', 'Permissions', $current, $currentAdmin); ?>
        <?php nav_link('activity.php', '🕒', 'Activity Log', $current, $currentAdmin); ?>
        <?php nav_link('backup.php', '💾', 'Backup & Restore', $current, $currentAdmin); ?>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="logout.php"><span class="ico">🚪</span><span class="nav-label">Log Out</span></a>
    </div>
  </aside>

  <div class="main-content">
    <div class="topbar-admin">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><span></span><span></span><span></span></button>
      <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>

      <form class="top-search" method="get" action="search.php">
        <span class="search-ico">🔍</span>
        <input type="search" name="q" placeholder="Search content, inquiries, users…" value="<?= e((string)($_GET['q'] ?? '')) ?>">
      </form>

      <div class="topbar-right">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="icon-btn" title="View Site">🌐</a>
        <button class="icon-btn" id="themeToggle" title="Toggle dark mode"><?= $theme === 'dark' ? '☀️' : '🌙' ?></button>

        <div class="top-drop" id="notifDrop">
          <button class="icon-btn" data-drop-toggle title="Notifications">
            🔔
            <?php if ($unreadNotifications > 0): ?><span class="notif-dot"><?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?></span><?php endif; ?>
          </button>
          <div class="top-drop-panel">
            <div class="top-drop-head">
              Notifications
              <a href="notifications.php">View all</a>
            </div>
            <div class="notif-list">
              <?php if (empty($recentNotifications)): ?>
                <div class="notif-empty">You're all caught up 🎉</div>
              <?php else: ?>
                <?php foreach ($recentNotifications as $notif): ?>
                  <a href="<?= e($notif['link']) ?>" class="notif-item<?= $notif['is_read'] ? '' : ' unread' ?>">
                    <span class="n-ico"><?= $notifIcons[$notif['type']] ?? '🔔' ?></span>
                    <span>
                      <strong><?= e($notif['title']) ?></strong>
                      <small><?= e($notif['message']) ?></small>
                      <span class="n-time"><?= e(time_ago($notif['created_at'])) ?></span>
                    </span>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="top-drop" id="profileDrop">
          <button class="profile-btn" data-drop-toggle>
            <span class="avatar-circle"><?= e($initials) ?></span>
            <span class="p-name"><?= e($currentAdmin['full_name']) ?><span class="p-role"><?= e($roleLabels[$currentAdmin['role']] ?? $currentAdmin['role']) ?></span></span>
          </button>
          <div class="top-drop-panel profile-menu">
            <a href="profile.php">👤 My Profile</a>
            <?php if (has_role($currentAdmin, 'super_admin')): ?>
              <a href="users.php">👥 Admin Users</a>
              <a href="settings.php">⚙️ Site Settings</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/index.php" target="_blank">🌐 View Website</a>
            <div class="menu-sep"></div>
            <a href="logout.php" class="danger">🚪 Log Out</a>
          </div>
        </div>
      </div>
    </div>
    <div class="page-body">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>
