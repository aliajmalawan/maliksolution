<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

// ── Download a full SQL dump (pure PHP — no shell access needed) ──
if (($_GET['download'] ?? '') === '1') {
    $dbname = $database;
    $tables = [];
    $res = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($res)) $tables[] = $row[0];

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $dbname . '-backup-' . date('Y-m-d-His') . '.sql"');

    echo "-- Malik Solution database backup\n";
    echo "-- Database: `$dbname`  Generated: " . date('Y-m-d H:i:s') . "\n\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $create = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
        echo "DROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n";

        $rows = mysqli_query($conn, "SELECT * FROM `$table`");
        while ($rows && ($r = mysqli_fetch_assoc($rows))) {
            $cols = '`' . implode('`, `', array_keys($r)) . '`';
            $vals = implode(', ', array_map(
                fn($v) => $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string)$v) . "'",
                array_values($r)
            ));
            echo "INSERT INTO `$table` ($cols) VALUES ($vals);\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";

    log_activity($conn, 'backup', 'Downloaded full database backup');
    exit;
}

$page_title = 'Backup';
$active_nav = 'backup';
include 'header.php';

// Table overview
$tables = [];
$total_rows = 0; $total_size = 0.0;
$res = mysqli_query($conn, "SELECT TABLE_NAME t, TABLE_ROWS r, (DATA_LENGTH + INDEX_LENGTH) s
                            FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . mysqli_real_escape_string($conn, $database) . "'
                            ORDER BY TABLE_NAME");
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $tables[] = $row;
    $total_rows += (int)$row['r'];
    $total_size += (float)$row['s'];
}
$fmt_size = fn(float $b): string => $b >= 1048576 ? round($b / 1048576, 2) . ' MB' : round($b / 1024, 1) . ' KB';
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-database me-2 text-primary"></i>Backup</h1>
    <p>Download a full SQL backup of the <strong><?= htmlspecialchars($database) ?></strong> database</p>
  </div>
  <a href="backup.php?download=1" class="btn btn-primary btn-sm">
    <i class="fa-solid fa-download me-1"></i>Download Backup (.sql)
  </a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="cardx stat-card">
      <div><p>Tables</p><h3><?= count($tables) ?></h3></div>
      <div class="stat-icon si-blue"><i class="fa-solid fa-table"></i></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="cardx stat-card">
      <div><p>Total Rows (approx.)</p><h3><?= number_format($total_rows) ?></h3></div>
      <div class="stat-icon si-green"><i class="fa-solid fa-list-ol"></i></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="cardx stat-card">
      <div><p>Database Size</p><h3><?= $fmt_size($total_size) ?></h3></div>
      <div class="stat-icon si-purple"><i class="fa-solid fa-hard-drive"></i></div>
    </div>
  </div>
</div>

<div class="cardx">
  <div class="section-hd"><h2><i class="fa-solid fa-table me-2 text-primary"></i>Tables</h2></div>
  <div class="table-wrap">
    <table class="table align-middle">
      <thead><tr><th>Table</th><th>Rows (approx.)</th><th>Size</th></tr></thead>
      <tbody>
        <?php foreach ($tables as $t): ?>
          <tr>
            <td><strong><?= htmlspecialchars($t['t']) ?></strong></td>
            <td class="text-muted"><?= number_format((int)$t['r']) ?></td>
            <td class="text-muted"><?= $fmt_size((float)$t['s']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-muted small mb-0 mt-3">
    <i class="fa-solid fa-circle-info me-1"></i>
    The backup file contains CREATE TABLE and INSERT statements for every table.
    Restore it with phpMyAdmin (Import) or <code>mysql -u root <?= htmlspecialchars($database) ?> &lt; backup.sql</code>.
  </p>
</div>

<?php include 'footer.php'; ?>
