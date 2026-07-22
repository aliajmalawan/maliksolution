<?php
declare(strict_types=1);
/**
 * ERP admin shell — opens the page: <head>, sidebar, topbar, <main>.
 * Expects: $page_title (string), $active (sidebar key). Close with footer.php.
 */

$user = ums_user();
$page_title = $page_title ?? 'Dashboard';
$active     = $active ?? '';
$theme      = ($_COOKIE['ums_theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
$initials   = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1));

// Sample notifications (Phase 1 UI only — real feed arrives with modules)
$ums_notifications = [
    ['fa-user-plus', 'ic-indigo', 'New admission application', 'Ayesha Khan applied for BS Computer Science', '5 min ago'],
    ['fa-money-bill-wave', 'ic-green', 'Fee payment received', 'Challan #10382 — Rs 45,000 (Fall 2026)', '32 min ago'],
    ['fa-triangle-exclamation', 'ic-amber', 'Low attendance alert', 'BSCS-3A attendance below 75% this week', '2 hrs ago'],
    ['fa-calendar-check', 'ic-cyan', 'Exam schedule published', 'Mid-term datesheet for Fall 2026 is live', 'Yesterday'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> · <?= e(UMS_NAME) ?> Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= UMS_URL ?>/assets/css/ums.css?v=<?= e(UMS_VERSION) ?>">
</head>
<body>

<?php require __DIR__ . '/sidebar.php'; ?>
<div class="u-side-backdrop" id="umsBackdrop"></div>

<!-- ── Topbar ── -->
<header class="u-top">
  <button class="u-burger" id="umsBurger" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>

  <div class="u-search">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="search" placeholder="Search students, courses, invoices…  (coming with modules)" disabled>
  </div>

  <div class="u-top-right">
    <button class="u-icon-btn" id="umsTheme" title="Toggle dark mode">
      <i class="fa-solid <?= $theme === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
    </button>

    <div style="position:relative">
      <button class="u-icon-btn" data-pop="popNoti" title="Notifications">
        <i class="fa-regular fa-bell"></i><span class="u-dot"></span>
      </button>
      <div class="u-pop" id="popNoti">
        <div class="u-pop-head">Notifications <span class="badge-soft"><?= count($ums_notifications) ?> new</span></div>
        <?php foreach ($ums_notifications as [$ic, $cls, $title, $sub, $when]): ?>
          <div class="u-noti">
            <span class="ic <?= $cls ?>" style="color:#fff"><i class="fa-solid <?= $ic ?>"></i></span>
            <div><strong><?= e($title) ?></strong><span style="font-size:.76rem;color:var(--muted)"><?= e($sub) ?></span><small><?= e($when) ?></small></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="position:relative">
      <button class="u-avatar" data-pop="popUser">
        <span class="av"><?= e($initials) ?></span>
        <span class="who">
          <strong><?= e($user['name'] ?? '') ?></strong>
          <small><?= e(ucwords(str_replace('_', ' ', $user['role'] ?? ''))) ?> · <?= e(UMS_NAME) ?></small>
        </span>
      </button>
      <div class="u-pop u-pop-menu" id="popUser" style="width:210px">
        <a href="#"><i class="fa-solid fa-user"></i>My Profile <span class="u-soon" style="margin-left:auto">soon</span></a>
        <a href="#"><i class="fa-solid fa-gear"></i>Settings <span class="u-soon" style="margin-left:auto">soon</span></a>
        <a href="<?= UMS_URL ?>/admin/logout.php" style="color:var(--danger)"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger)"></i>Sign Out</a>
      </div>
    </div>
  </div>
</header>

<!-- ── Main ── -->
<main class="u-main">
<?php if ($__flash = flash_get()): ?>
  <div class="u-flash <?= e($__flash['type']) ?>">
    <i class="fa-solid <?= $__flash['type'] === 'success' ? 'fa-circle-check' : ($__flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
    <?= e($__flash['message']) ?>
  </div>
<?php endif; ?>
