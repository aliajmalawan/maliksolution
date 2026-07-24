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

/** Fetch one student by id, or null. Shared by the Students module and the Student Portal. */
function stu_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('students') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Fetch one teacher by id, or null. Shared by the Teachers module and the Teacher Portal. */
function tch_find(int $id): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('teachers') . ' WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Department name lookup, [id => name]. Shared across modules and portals. */
function dept_options(int $campus): array
{
    $out = [];
    try {
        $stmt = ums_db()->prepare('SELECT id, name FROM ' . tbl('departments') . ' WHERE campus_id = ? AND status = "active" ORDER BY name');
        $stmt->bind_param('i', $campus); $stmt->execute();
        $r = $stmt->get_result();
        while ($x = $r->fetch_assoc()) $out[(int)$x['id']] = $x['name'];
        $stmt->close();
    } catch (Throwable $t) { /* departments module not in use yet */ }
    return $out;
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

/* ── Student login credentials (Students + Admissions both manage these) ── */

/** A readable random password: upper + lower + digit + symbol, 10 chars. */
function stu_gen_password(): string
{
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghjkmnpqrstuvwxyz';
    $digits = '23456789';
    $symbols = '!@#$%';
    $pw = $upper[random_int(0, strlen($upper) - 1)]
        . $lower[random_int(0, strlen($lower) - 1)]
        . $digits[random_int(0, strlen($digits) - 1)]
        . $symbols[random_int(0, strlen($symbols) - 1)];
    $all = $upper . $lower . $digits;
    for ($i = 0; $i < 6; $i++) $pw .= $all[random_int(0, strlen($all) - 1)];
    return str_shuffle($pw);
}

/**
 * Create a UMS login (role=student) for a student record.
 * Prefers the student's own email if it's valid and not already taken;
 * otherwise falls back to a synthetic, guaranteed-unique login based on
 * their registration number. Returns the plaintext credentials once
 * (never stored/logged in plaintext), or null if a login already exists.
 */
function stu_create_login(int $studentId, string $name, string $email, string $regNo, int $campus): ?array
{
    $db = ums_db();

    $chk = $db->prepare('SELECT id FROM ' . tbl('users') . ' WHERE student_id = ? LIMIT 1');
    $chk->bind_param('i', $studentId); $chk->execute();
    if ($chk->get_result()->fetch_assoc()) { $chk->close(); return null; }
    $chk->close();

    $loginEmail = '';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $ck = $db->prepare('SELECT id FROM ' . tbl('users') . ' WHERE email = ? LIMIT 1');
        $ck->bind_param('s', $email); $ck->execute();
        if (!$ck->get_result()->fetch_assoc()) $loginEmail = $email;
        $ck->close();
    }
    if ($loginEmail === '') {
        $loginEmail = strtolower($regNo) . '@students.ums.local';
    }

    $password = stu_gen_password();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $db->prepare('INSERT INTO ' . tbl('users') . ' (campus_id, student_id, name, email, password_hash, role, status) VALUES (?,?,?,?,?,"student","active")');
    $ins->bind_param('iisss', $campus, $studentId, $name, $loginEmail, $hash);
    $ins->execute(); $ins->close();

    return ['email' => $loginEmail, 'password' => $password];
}

/** The UMS login account linked to a student, if one exists. */
function stu_login_find(int $studentId): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('users') . ' WHERE student_id = ? LIMIT 1');
    $stmt->bind_param('i', $studentId); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Generate a fresh password for a student's existing login. Returns the new plaintext, or null if no login exists. */
function stu_reset_password(int $studentId): ?string
{
    $login = stu_login_find($studentId);
    if (!$login) return null;
    $password = stu_gen_password();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $up = ums_db()->prepare('UPDATE ' . tbl('users') . ' SET password_hash = ? WHERE id = ?');
    $up->bind_param('si', $hash, $login['id']); $up->execute(); $up->close();
    return $password;
}

/* ── Teacher login credentials (mirrors the student login pattern above) ── */

/**
 * Create a UMS login (role=teacher) for a teacher record. Same rules as
 * stu_create_login(): prefers their own email, falls back to a synthetic
 * login based on employee number. Returns plaintext creds once, or null
 * if a login already exists.
 */
function tch_create_login(int $teacherId, string $name, string $email, string $empNo, int $campus): ?array
{
    $db = ums_db();

    $chk = $db->prepare('SELECT id FROM ' . tbl('users') . ' WHERE teacher_id = ? LIMIT 1');
    $chk->bind_param('i', $teacherId); $chk->execute();
    if ($chk->get_result()->fetch_assoc()) { $chk->close(); return null; }
    $chk->close();

    $loginEmail = '';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $ck = $db->prepare('SELECT id FROM ' . tbl('users') . ' WHERE email = ? LIMIT 1');
        $ck->bind_param('s', $email); $ck->execute();
        if (!$ck->get_result()->fetch_assoc()) $loginEmail = $email;
        $ck->close();
    }
    if ($loginEmail === '') {
        $loginEmail = strtolower($empNo) . '@faculty.ums.local';
    }

    $password = stu_gen_password();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $db->prepare('INSERT INTO ' . tbl('users') . ' (campus_id, teacher_id, name, email, password_hash, role, status) VALUES (?,?,?,?,?,"teacher","active")');
    $ins->bind_param('iisss', $campus, $teacherId, $name, $loginEmail, $hash);
    $ins->execute(); $ins->close();

    return ['email' => $loginEmail, 'password' => $password];
}

/** The UMS login account linked to a teacher, if one exists. */
function tch_login_find(int $teacherId): ?array
{
    $stmt = ums_db()->prepare('SELECT * FROM ' . tbl('users') . ' WHERE teacher_id = ? LIMIT 1');
    $stmt->bind_param('i', $teacherId); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $row ?: null;
}

/** Generate a fresh password for a teacher's existing login. Returns the new plaintext, or null if no login exists. */
function tch_reset_password(int $teacherId): ?string
{
    $login = tch_login_find($teacherId);
    if (!$login) return null;
    $password = stu_gen_password();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $up = ums_db()->prepare('UPDATE ' . tbl('users') . ' SET password_hash = ? WHERE id = ?');
    $up->bind_param('si', $hash, $login['id']); $up->execute(); $up->close();
    return $password;
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
