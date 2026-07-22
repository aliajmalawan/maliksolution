<?php
declare(strict_types=1);

/**
 * Shared CRUD helpers — small, reusable pieces every data module uses
 * (pagination, filter-preserving query strings, initials, status badges).
 * Keeps each module lean and consistent. Additive: does not change any
 * existing module.
 */

/** Initials from a full name, e.g. "Nadia Aslam" → "NA". */
function ini2(string $name): string
{
    $p = preg_split('/\s+/', trim($name)) ?: [];
    return strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[count($p) - 1] ?? '', 0, 1)) ?: '–';
}

/** Rebuild the current query string, keeping the given keys and merging extras. */
function qs_keep(array $keys, array $extra = []): string
{
    $base = [];
    foreach ($keys as $k) {
        $v = $_GET[$k] ?? '';
        if ($v !== '') $base[$k] = $v;
    }
    return http_build_query(array_merge($base, $extra));
}

/**
 * Render a pagination bar. $urlFn(int $page): string builds each page URL.
 */
function crud_pager(int $page, int $pages, callable $urlFn): string
{
    if ($pages <= 1) return '';
    ob_start(); ?>
    <div class="u-pager">
      <a class="<?= $page <= 1 ? 'dis' : '' ?>" href="<?= e($urlFn($page - 1)) ?>"><i class="fa-solid fa-chevron-left"></i></a>
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <?php if ($p == 1 || $p == $pages || abs($p - $page) <= 2): ?>
          <a class="<?= $p == $page ? 'cur' : '' ?>" href="<?= e($urlFn($p)) ?>"><?= $p ?></a>
        <?php elseif (abs($p - $page) === 3): ?>
          <span class="dis">…</span>
        <?php endif; ?>
      <?php endfor; ?>
      <a class="<?= $page >= $pages ? 'dis' : '' ?>" href="<?= e($urlFn($page + 1)) ?>"><i class="fa-solid fa-chevron-right"></i></a>
    </div>
    <?php return (string)ob_get_clean();
}

/** Active/inactive pill. */
function active_badge(string $status): string
{
    return $status === 'active'
        ? '<span class="st st-approved">Active</span>'
        : '<span class="st" style="color:var(--muted);background:var(--bg)">Inactive</span>';
}

/**
 * Degree programs and academic sessions offered.
 * Shared across people modules until the Academic/Programs module (Phase 2)
 * replaces this with a real ums_programs table.
 */
function program_list(): array
{
    return [
        'BS Computer Science', 'BS Software Engineering', 'BS Information Technology',
        'BS Data Science', 'BS Artificial Intelligence', 'BS Cyber Security',
        'BBA', 'BS Accounting & Finance', 'BS Economics',
        'BS Mathematics', 'BS Physics', 'BS English',
    ];
}

function session_list(): array
{
    return ['Fall 2026', 'Spring 2027', 'Fall 2027'];
}

/** Render a status badge from a [label, cssClass] map. */
function status_badge(string $status, array $map): string
{
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'st'];
    return '<span class="st ' . $cls . '">' . e($label) . '</span>';
}

/** Read one UMS setting from ums_settings (cached per request). */
function ums_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $r = ums_db()->query('SELECT name, value FROM ' . tbl('settings'));
            while ($r && ($x = $r->fetch_assoc())) $cache[$x['name']] = (string)$x['value'];
        } catch (Throwable $t) { /* settings table not ready */ }
    }
    return ($cache[$key] ?? '') !== '' ? $cache[$key] : $default;
}

/** Write one UMS setting (creates the row if missing). Clears the cache. */
function ums_set_setting(string $key, string $value): void
{
    $db = ums_db();
    $db->query('CREATE TABLE IF NOT EXISTS ' . tbl('settings') . ' (name VARCHAR(100) PRIMARY KEY, value TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $stmt = $db->prepare('REPLACE INTO ' . tbl('settings') . ' (name, value) VALUES (?, ?)');
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
    $stmt->close();
}

/**
 * The institute name for official documents. Prefers the UMS setting,
 * then the website CMS site_name, then a sensible default.
 */
function ums_inst_name(mysqli $db): string
{
    $n = ums_setting('institute_name');
    if ($n !== '') return $n;
    try {
        $r = $db->query("SELECT value FROM settings WHERE name = 'site_name' LIMIT 1");
        if ($r && ($x = $r->fetch_assoc()) && $x['value'] !== '') return $x['value'];
    } catch (Throwable $t) { /* website settings unavailable */ }
    return 'Malik Solution';
}
