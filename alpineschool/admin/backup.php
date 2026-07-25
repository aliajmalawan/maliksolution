<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$backupDir = dirname(__DIR__) . '/database/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    file_put_contents($backupDir . '/.htaccess', "Require all denied\n");
}

$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
$mysqlCli = 'C:\\xampp\\mysql\\bin\\mysql.exe';

/** Validate a backup filename (no paths, only files this tool created). */
function backup_file_valid(string $name): bool
{
    return (bool)preg_match('/^backup-[0-9\-]+\.sql$/', $name);
}

if (isset($_GET['download'])) {
    $name = (string)$_GET['download'];
    $file = $backupDir . '/' . $name;
    if (backup_file_valid($name) && is_file($file)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    flash_set('error', 'Backup file not found.');
    redirect(BASE_URL . '/admin/backup.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect(BASE_URL . '/admin/backup.php');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = 'backup-' . date('Y-m-d-His') . '.sql';
        $file = $backupDir . '/' . $name;
        $cmd = '"' . $mysqldump . '" --user=' . escapeshellarg(DB_USER)
            . (DB_PASS !== '' ? ' --password=' . escapeshellarg(DB_PASS) : '')
            . ' --host=' . escapeshellarg(DB_HOST)
            . ' --single-transaction --routines ' . escapeshellarg(DB_NAME)
            . ' > ' . escapeshellarg($file) . ' 2>&1';
        exec($cmd, $output, $code);
        if ($code === 0 && is_file($file) && filesize($file) > 0) {
            log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'backup_create', 'Created database backup ' . $name);
            flash_set('success', 'Backup created: ' . $name);
        } else {
            @unlink($file);
            flash_set('error', 'Backup failed. Make sure mysqldump is available at ' . $mysqldump);
        }
    } elseif ($action === 'restore') {
        $name = (string)($_POST['file'] ?? '');
        $file = $backupDir . '/' . $name;
        if (backup_file_valid($name) && is_file($file)) {
            $cmd = '"' . $mysqlCli . '" --user=' . escapeshellarg(DB_USER)
                . (DB_PASS !== '' ? ' --password=' . escapeshellarg(DB_PASS) : '')
                . ' --host=' . escapeshellarg(DB_HOST)
                . ' ' . escapeshellarg(DB_NAME)
                . ' < ' . escapeshellarg($file) . ' 2>&1';
            exec($cmd, $output, $code);
            if ($code === 0) {
                log_activity($pdo, (int)$currentAdmin['id'], $currentAdmin['full_name'], 'backup_restore', 'Restored database from ' . $name);
                flash_set('success', 'Database restored from ' . $name . '.');
            } else {
                flash_set('error', 'Restore failed: ' . e(implode(' ', array_slice($output, 0, 3))));
            }
        } else {
            flash_set('error', 'Backup file not found.');
        }
    } elseif ($action === 'delete') {
        $name = (string)($_POST['file'] ?? '');
        $file = $backupDir . '/' . $name;
        if (backup_file_valid($name) && is_file($file)) {
            unlink($file);
            flash_set('success', 'Backup deleted.');
        }
    }
    redirect(BASE_URL . '/admin/backup.php');
}

$backups = [];
foreach (glob($backupDir . '/backup-*.sql') ?: [] as $file) {
    $backups[] = ['name' => basename($file), 'size' => (int)filesize($file), 'mtime' => (int)filemtime($file)];
}
usort($backups, fn($a, $b) => $b['mtime'] <=> $a['mtime']);

$pageTitle = 'Backup & Restore';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Database Backup</h2>
    <form method="post" action="backup.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <button type="submit" class="btn btn-primary">➕ Create Backup Now</button>
    </form>
  </div>
  <p class="form-hint">Backups are full SQL dumps of the <strong><?= e(DB_NAME) ?></strong> database, stored in <code>database/backups/</code> (blocked from web access). Download copies regularly and keep them somewhere safe.</p>
</div>

<div class="card">
  <div class="card-header"><h2>Available Backups (<?= count($backups) ?>)</h2></div>
  <div class="table-wrap">
    <?php if (empty($backups)): ?>
      <div class="empty-state">No backups yet. Create your first one above.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>File</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($backups as $backup): ?>
        <tr>
          <td><?= e($backup['name']) ?></td>
          <td><?= human_filesize($backup['size']) ?></td>
          <td><?= e(date('d M Y, H:i', $backup['mtime'])) ?></td>
          <td class="actions-cell">
            <a href="backup.php?download=<?= e($backup['name']) ?>" class="btn btn-outline btn-sm">⬇ Download</a>
            <form method="post" action="backup.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="restore">
              <input type="hidden" name="file" value="<?= e($backup['name']) ?>">
              <button type="submit" class="btn btn-outline btn-sm" data-confirm="RESTORE the database from this backup? All data changed since this backup will be LOST. This cannot be undone.">↩ Restore</button>
            </form>
            <form method="post" action="backup.php" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="file" value="<?= e($backup['name']) ?>">
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this backup file?">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
