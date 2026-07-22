<?php
declare(strict_types=1);
$page_title = 'Search';
$active_nav = '';
include 'header.php';

$q = trim((string)($_GET['q'] ?? ''));
$like = '%' . $q . '%';

/** Run a prepared LIKE search, return rows (empty on missing table). */
function search_rows(mysqli $conn, string $sql, string $like, int $params): array
{
    $out = [];
    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return $out;
        $types = str_repeat('s', $params);
        $args  = array_fill(0, $params, $like);
        mysqli_stmt_bind_param($stmt, $types, ...$args);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($r = mysqli_fetch_assoc($res))) $out[] = $r;
        mysqli_stmt_close($stmt);
    } catch (Exception $e) { /* table missing — skip section */ }
    return $out;
}

$sections = [];
if ($q !== '') {
    $sections = [
        [
            'title' => 'Admissions', 'icon' => 'fa-user-graduate', 'link' => 'admissions.php',
            'rows' => search_rows($conn,
                "SELECT id, student_name t, CONCAT(program, ' · ', phone) s, status b, created_at d FROM admissions
                 WHERE student_name LIKE ? OR father_name LIKE ? OR phone LIKE ? OR email LIKE ? OR program LIKE ? OR student_id LIKE ?
                 ORDER BY created_at DESC LIMIT 15", $like, 6),
        ],
        [
            'title' => 'Contact Queries', 'icon' => 'fa-envelope', 'link' => 'contacts.php',
            'rows' => search_rows($conn,
                "SELECT id, name t, CONCAT(COALESCE(NULLIF(subject,''),'No subject'), ' · ', email) s, '' b, created_at d FROM contacts
                 WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?
                 ORDER BY created_at DESC LIMIT 15", $like, 4),
        ],
        [
            'title' => 'Teachers', 'icon' => 'fa-chalkboard-user', 'link' => 'teachers.php',
            'rows' => search_rows($conn,
                "SELECT id, name t, CONCAT(COALESCE(designation,''), ' ', COALESCE(department,'')) s, status b, created_at d FROM teachers
                 WHERE name LIKE ? OR email LIKE ? OR designation LIKE ? OR department LIKE ?
                 ORDER BY name ASC LIMIT 15", $like, 4),
        ],
        [
            'title' => 'Courses', 'icon' => 'fa-book', 'link' => 'courses.php',
            'rows' => search_rows($conn,
                "SELECT id, name t, COALESCE(description,'') s, status b, created_at d FROM courses
                 WHERE name LIKE ? OR description LIKE ?
                 ORDER BY name ASC LIMIT 15", $like, 2),
        ],
        [
            'title' => 'News', 'icon' => 'fa-newspaper', 'link' => 'manage_news.php',
            'rows' => search_rows($conn,
                "SELECT id, title t, COALESCE(content,'') s, status b, created_at d FROM news
                 WHERE title LIKE ? OR content LIKE ?
                 ORDER BY created_at DESC LIMIT 15", $like, 2),
        ],
    ];
}
$total_hits = array_sum(array_map(fn($s) => count($s['rows']), $sections));
?>

<div class="page-hd">
  <div>
    <h1><i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>Search</h1>
    <p><?= $q !== '' ? $total_hits . ' result' . ($total_hits === 1 ? '' : 's') . ' for “' . htmlspecialchars($q) . '”' : 'Search across students, contacts, teachers, courses, and news' ?></p>
  </div>
  <form method="get" class="d-flex gap-2" style="min-width:min(360px,100%)">
    <input type="search" class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Type to search…" autofocus>
    <button class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
  </form>
</div>

<?php if ($q === ''): ?>
  <div class="cardx">
    <div class="empty-state">
      <i class="fa-solid fa-magnifying-glass"></i>
      <p>Enter a name, phone number, email, student ID, or keyword above.</p>
    </div>
  </div>
<?php elseif ($total_hits === 0): ?>
  <div class="cardx">
    <div class="empty-state">
      <i class="fa-solid fa-magnifying-glass-minus"></i>
      <p>Nothing found for “<?= htmlspecialchars($q) ?>”. Try a shorter keyword.</p>
    </div>
  </div>
<?php else: ?>
  <?php foreach ($sections as $sec): if (empty($sec['rows'])) continue; ?>
    <div class="cardx mb-3">
      <div class="section-hd">
        <h2><i class="fa-solid <?= $sec['icon'] ?> me-2 text-primary"></i><?= $sec['title'] ?>
          <span class="badge bg-primary-subtle text-primary ms-1"><?= count($sec['rows']) ?></span>
        </h2>
        <a href="<?= $sec['link'] ?>" class="btn btn-sm btn-outline-primary">Open <?= $sec['title'] ?></a>
      </div>
      <div class="table-wrap">
        <table class="table align-middle">
          <tbody>
            <?php foreach ($sec['rows'] as $r): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($r['t']) ?></strong>
                  <div class="text-muted small text-truncate" style="max-width:520px"><?= htmlspecialchars(mb_substr(strip_tags((string)$r['s']), 0, 120)) ?></div>
                </td>
                <td class="text-end" style="white-space:nowrap">
                  <?php if (!empty($r['b'])): ?>
                    <span class="status-badge sb-<?= htmlspecialchars($r['b']) ?>" style="text-transform:capitalize"><?= htmlspecialchars($r['b']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-muted small text-end" style="white-space:nowrap">
                  <?= !empty($r['d']) ? date('d M Y', strtotime($r['d'])) : '' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
