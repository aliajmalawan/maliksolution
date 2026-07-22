<?php
/* One-time script: generates portal credentials for all teachers that don't have them.
   Delete this file after running. */
require_once __DIR__ . '/../includes/config.php';

function tcp_make_password(int $len = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $p = '';
    for ($i = 0; $i < $len; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    return $p;
}

$rows = mysqli_query($conn, "SELECT id, name FROM teachers WHERE teacher_username IS NULL OR teacher_username = '' ORDER BY id");
$results = [];
while ($t = mysqli_fetch_assoc($rows)) {
    $tid   = (int)$t['id'];
    $uname = 'TCH' . str_pad((string)$tid, 3, '0', STR_PAD_LEFT);
    $plain = tcp_make_password();
    $hash  = password_hash($plain, PASSWORD_DEFAULT);
    $st = mysqli_prepare($conn, "UPDATE teachers SET teacher_username=?, teacher_password_hash=? WHERE id=?");
    mysqli_stmt_bind_param($st, 'ssi', $uname, $hash, $tid);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    // create upload folder
    $dir = __DIR__ . "/../assets/uploads/teachers/$tid/";
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $results[] = ['id' => $tid, 'name' => $t['name'], 'username' => $uname, 'password' => $plain];
}
?><!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Teacher Credentials Setup</title>
<style>body{font-family:Arial,sans-serif;max-width:800px;margin:2rem auto;padding:1rem}
table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:.6rem .9rem;text-align:left}
th{background:#071f40;color:#fff}tr:nth-child(even){background:#f9f9f9}
code{background:#e8f5e9;padding:.2rem .5rem;border-radius:4px;font-size:.95rem}
.warn{background:#fff3cd;border:1px solid #ffc107;padding:1rem;border-radius:8px;margin-bottom:1.5rem}
</style></head><body>
<h2>Teacher Portal Credentials</h2>
<?php if (empty($results)): ?>
<p style="color:green">✅ All teachers already have credentials. Nothing to generate.</p>
<?php else: ?>
<div class="warn">⚠️ <strong>Save these passwords now</strong> — they will not be shown again. Then delete this file.</div>
<table>
  <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Password</th><th>Portal URL</th></tr></thead>
  <tbody>
  <?php foreach ($results as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><code><?= htmlspecialchars($r['username']) ?></code></td>
      <td><code><?= htmlspecialchars($r['password']) ?></code></td>
      <td><a href="login.php">Teacher/login.php</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<p style="margin-top:1.5rem;color:#dc3545"><strong>🗑️ Delete this file (setup_creds.php) after copying the credentials above.</strong></p>
<?php endif; ?>
</body></html>
