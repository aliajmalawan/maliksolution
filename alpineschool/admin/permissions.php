<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

/** page => human label, grouped for display. Access level comes from admin_page_min_role(). */
$pageGroups = [
    'Overview' => ['index.php' => 'Dashboard', 'analytics.php' => 'Analytics', 'search.php' => 'Admin Search', 'notifications.php' => 'Notifications', 'profile.php' => 'My Profile'],
    'Content' => ['homepage.php' => 'Homepage Builder', 'theme.php' => 'Theme Builder', 'hero-slides.php' => 'Hero Slider', 'pages.php' => 'Pages', 'menus.php' => 'Menus', 'news.php' => 'News', 'blogs.php' => 'Blogs', 'events.php' => 'Events', 'gallery.php' => 'Gallery', 'videos.php' => 'Videos', 'faculty.php' => 'Faculty', 'departments.php' => 'Departments', 'testimonials.php' => 'Testimonials', 'faqs.php' => 'FAQs', 'partners.php' => 'Partners', 'notices.php' => 'Notice Board'],
    'School Office' => ['downloads.php' => 'Downloads', 'results.php' => 'Results', 'career.php' => 'Career'],
    'Inbox & Marketing' => ['applications.php' => 'Applications', 'admission-settings.php' => 'Admission Settings', 'inquiries.php' => 'Admission Inquiries', 'messages.php' => 'Contact Messages', 'forms.php' => 'Form Builder', 'form-submissions.php' => 'Form Submissions', 'contact-settings.php' => 'Contact Settings', 'newsletter.php' => 'Newsletter', 'seo.php' => 'SEO Manager', 'popup.php' => 'Popup Manager', 'media.php' => 'Media Manager'],
    'System' => ['settings.php' => 'Site Settings', 'integrations.php' => 'Integrations', 'performance.php' => 'Performance', 'users.php' => 'Admin Users', 'permissions.php' => 'Permissions', 'activity.php' => 'Activity Log', 'backup.php' => 'Backup & Restore'],
];

$roles = ['editor' => 'Editor', 'admin' => 'Admin', 'super_admin' => 'Super Admin'];

$pageTitle = 'Permissions';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header"><h2>Role Permissions Matrix</h2></div>
  <p class="form-hint" style="margin-bottom:16px;">
    Access is hierarchical: <strong>Editor</strong> → content only · <strong>Admin</strong> → everything an editor has, plus inbox &amp; marketing · <strong>Super Admin</strong> → full system access.
    Roles are assigned per user on the <a href="users.php" style="color:var(--primary);">Admin Users</a> page. The matrix below is enforced automatically on every request.
  </p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Admin Page</th>
          <?php foreach ($roles as $label): ?><th style="text-align:center;"><?= e($label) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pageGroups as $group => $pages): ?>
        <tr><td colspan="4" style="background:var(--surface-2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);"><?= e($group) ?></td></tr>
        <?php foreach ($pages as $file => $label): $minRole = admin_page_min_role($file); ?>
        <tr>
          <td><?= e($label) ?></td>
          <?php foreach (array_keys($roles) as $role): ?>
            <td style="text-align:center;"><?= role_level($role) >= role_level($minRole) ? '<span style="color:var(--good);font-weight:700;">✓</span>' : '<span style="color:var(--muted);">—</span>' ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
