<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page_title = 'Library'; $active = 'library';
$db = ums_db(); $campus = (int)$user['campus_id'];

$q     = trim((string)($_GET['q'] ?? ''));
$fCat  = in_array($_GET['category'] ?? '', LIB_CATEGORIES, true) ? $_GET['category'] : '';
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12; $offset = ($page - 1) * $perPage;

$where = ['campus_id = ?']; $types = 'i'; $args = [$campus];
if ($q !== '')    { $where[] = '(title LIKE ? OR author LIKE ? OR isbn LIKE ?)'; $l = "%$q%"; $types .= 'sss'; array_push($args, $l, $l, $l); }
if ($fCat !== '') { $where[] = 'category = ?'; $types .= 's'; $args[] = $fCat; }
$whereSql = implode(' AND ', $where);

$cs = $db->prepare('SELECT COUNT(*) c FROM ' . tbl('books') . " WHERE $whereSql");
$cs->bind_param($types, ...$args); $cs->execute();
$total = (int)$cs->get_result()->fetch_assoc()['c']; $cs->close();
$pages = max(1, (int)ceil($total / $perPage));

$ls = $db->prepare("SELECT * FROM " . tbl('books') . " WHERE $whereSql ORDER BY title LIMIT ? OFFSET ?");
$ls->bind_param($types . 'ii', ...array_merge($args, [$perPage, $offset]));
$ls->execute();
$rows = $ls->get_result()->fetch_all(MYSQLI_ASSOC); $ls->close();

$agg = $db->query('SELECT COUNT(*) titles, COALESCE(SUM(total_copies),0) copies, COALESCE(SUM(available_copies),0) avail FROM ' . tbl('books') . ' WHERE campus_id=' . $campus)->fetch_assoc();
$issuedNow = (int)$db->query('SELECT COUNT(*) c FROM ' . tbl('book_issues') . ' WHERE campus_id=' . $campus . ' AND status="issued"')->fetch_assoc()['c'];

require __DIR__ . '/../../includes/header.php';
?>
<div class="u-page-head">
  <div><h1>Library</h1><p>Book catalog &amp; circulation</p></div>
  <div style="display:flex;gap:.6rem">
    <a href="<?= lib_url('report.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-chart-pie"></i> Report</a>
    <a href="<?= lib_url('issue.php') ?>" class="u-btn u-btn-soft"><i class="fa-solid fa-right-left"></i> Issue / Return</a>
    <a href="<?= lib_url('create.php') ?>" class="u-btn u-btn-primary"><i class="fa-solid fa-plus"></i> Add Book</a>
  </div>
</div>

<div class="u-chips">
  <div class="u-chip"><span class="ci ic-indigo"><i class="fa-solid fa-book"></i></span><div><small>Titles</small><strong><?= (int)$agg['titles'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-cyan"><i class="fa-solid fa-layer-group"></i></span><div><small>Total Copies</small><strong><?= (int)$agg['copies'] ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-amber"><i class="fa-solid fa-right-from-bracket"></i></span><div><small>Issued Out</small><strong><?= $issuedNow ?></strong></div></div>
  <div class="u-chip"><span class="ci ic-green"><i class="fa-solid fa-circle-check"></i></span><div><small>Available</small><strong><?= (int)$agg['avail'] ?></strong></div></div>
</div>

<div class="u-card" style="margin-bottom:1.1rem">
  <form method="get" class="u-toolbar" style="margin-bottom:0">
    <div class="grow u-search-box"><i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title, author, or ISBN…"></div>
    <select name="category" class="u-select" onchange="this.form.submit()"><option value="">All Categories</option>
      <?php foreach (LIB_CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $fCat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select>
    <button type="submit" class="u-btn u-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
    <?php if ($q||$fCat): ?><a href="<?= lib_url('index.php') ?>" class="u-btn u-btn-soft">Clear</a><?php endif; ?>
  </form>
</div>

<div class="u-card">
  <div class="u-card-head"><h2><i class="fa-solid fa-book-bookmark" style="color:var(--primary)"></i> Catalog</h2><span class="hint"><?= $total ?> title<?= $total===1?'':'s' ?></span></div>
  <?php if (!$rows): ?>
    <div class="u-empty"><i class="fa-solid fa-book"></i><p>No books found<?= ($q||$fCat)?' for these filters':' yet' ?>.</p>
      <a href="<?= lib_url('create.php') ?>" class="u-btn u-btn-primary mt-2"><i class="fa-solid fa-plus"></i> Add the first book</a></div>
  <?php else: ?>
    <div style="overflow-x:auto"><table class="u-table">
      <thead><tr><th>Title</th><th>Category</th><th>Shelf</th><th style="text-align:center">Available</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['title']) ?></strong><?= $r['author'] ? '<br><small style="color:var(--muted)">' . e($r['author']) . '</small>' : '' ?></td>
            <td><span class="st" style="background:rgba(99,102,241,.1);color:var(--primary)"><?= e($r['category']) ?></span></td>
            <td style="color:var(--muted)"><?= e($r['shelf'] ?: '—') ?></td>
            <td style="text-align:center;font-weight:700"><span style="color:<?= (int)$r['available_copies']>0?'var(--success)':'var(--danger)' ?>"><?= (int)$r['available_copies'] ?></span> / <?= (int)$r['total_copies'] ?></td>
            <td><?= active_badge($r['status']) ?></td>
            <td style="text-align:right"><span class="u-act">
              <a href="<?= lib_url('edit.php?id='.(int)$r['id']) ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= lib_url('action.php') ?>" style="display:inline" onsubmit="return confirm('Delete this book?')">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="del" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?= crud_pager($page, $pages, fn($p) => '?' . qs_keep(['q','category'], ['page'=>$p])) ?>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
