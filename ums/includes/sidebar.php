<?php
declare(strict_types=1);
/**
 * ERP sidebar — the full module map from day one. Modules that ship in
 * later phases render with a "soon" badge and no link, so demos always
 * show the complete product scope.
 */

$nav = [
    'Main' => [
        ['dashboard', 'fa-gauge-high', 'Dashboard', UMS_URL . '/admin/dashboard.php', true],
    ],
    'Academics' => [
        ['admissions', 'fa-user-plus', 'Admissions', UMS_URL . '/modules/admissions/index.php', true],
        ['academic', 'fa-sitemap', 'Departments', UMS_URL . '/modules/departments/index.php', true],
        ['courses', 'fa-book-open', 'Courses', UMS_URL . '/modules/courses/index.php', true],
        ['classes', 'fa-chalkboard', 'Classes & Sections', UMS_URL . '/modules/sections/index.php', true],
        ['timetable', 'fa-table-cells', 'Timetable', UMS_URL . '/modules/timetable/index.php', true],
    ],
    'People' => [
        ['students', 'fa-user-graduate', 'Students', UMS_URL . '/modules/students/index.php', true],
        ['teachers', 'fa-person-chalkboard', 'Teachers', UMS_URL . '/modules/teachers/index.php', true],
        ['attendance', 'fa-clipboard-user', 'Attendance', UMS_URL . '/modules/attendance/index.php', true],
    ],
    'Examination' => [
        ['exams', 'fa-file-pen', 'Exams', UMS_URL . '/modules/examination/index.php', true],
        ['results', 'fa-square-poll-vertical', 'Results & GPA', UMS_URL . '/modules/results/index.php', true],
    ],
    'Finance' => [
        ['fees', 'fa-money-bill-wave', 'Fee Management', UMS_URL . '/modules/fees/index.php', true],
        ['accounts', 'fa-calculator', 'Accounts', UMS_URL . '/modules/accounts/index.php', true],
        ['payroll', 'fa-file-invoice-dollar', 'HR & Payroll', UMS_URL . '/modules/payroll/index.php', true],
    ],
    'Campus' => [
        ['library', 'fa-book-bookmark', 'Library', UMS_URL . '/modules/library/index.php', true],
        ['hostel', 'fa-bed', 'Hostel', UMS_URL . '/modules/hostel/index.php', true],
        ['transport', 'fa-bus', 'Transport', UMS_URL . '/modules/transport/index.php', true],
    ],
    'System' => [
        ['notices', 'fa-bullhorn', 'Notifications', '#', false],
        ['reports', 'fa-chart-pie', 'Reports', UMS_URL . '/modules/reports/index.php', true],
        ['settings', 'fa-gear', 'System Settings', UMS_URL . '/modules/settings/index.php', true],
    ],
];
?>
<aside class="u-side">
  <div class="u-side-brand">
    <span class="u-side-logo"><i class="fa-solid fa-graduation-cap"></i></span>
    <div>
      <strong><?= e(UMS_NAME) ?></strong>
      <small>University ERP</small>
    </div>
  </div>

  <nav class="u-side-nav">
    <?php foreach ($nav as $section => $items): ?>
      <div class="u-side-sec"><?= e($section) ?></div>
      <?php foreach ($items as [$key, $icon, $label, $href, $ready]): ?>
        <a class="u-nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>">
          <i class="fa-solid <?= $icon ?>"></i><span><?= e($label) ?></span>
          <?php if (!$ready): ?><span class="u-soon">soon</span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="u-side-foot">
    v<?= e(UMS_VERSION) ?> · &copy; <?= date('Y') ?> Malik Solution
  </div>
</aside>
