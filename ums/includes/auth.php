<?php
declare(strict_types=1);

/**
 * Authentication — bcrypt, prepared statements, session hardening,
 * simple brute-force damping. Sessions are namespaced under
 * $_SESSION['ums_user'] so the website CMS / legacy portals never collide.
 */

/** Staff roles allowed into the ERP admin panel. */
const UMS_ADMIN_ROLES = ['super_admin', 'admin', 'accountant', 'librarian'];

/**
 * Attempt a login. Returns an error message, or null on success.
 * $allowedRoles gates which roles may sign in here (role-based auth);
 * empty = any role. $remember issues a persistent "remember me" cookie.
 */
function ums_attempt_login(string $email, string $password, array $allowedRoles = [], bool $remember = false): ?string
{
    // brute-force damping: escalating delay after each failure this session
    $fails = (int)($_SESSION['ums_login_fails'] ?? 0);
    if ($fails > 0) {
        sleep(min($fails, 5));
    }

    $stmt = ums_db()->prepare(
        'SELECT id, campus_id, name, email, password_hash, role, status FROM ' . tbl('users') . ' WHERE email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['ums_login_fails'] = $fails + 1;
        ums_log('login_failed', 'Failed UMS login for ' . $email);
        return 'Invalid email or password.';
    }
    if ($user['status'] !== 'active') {
        return 'This account has been suspended. Contact the administrator.';
    }
    // Role-based gate — e.g. keep teachers/students out of the admin panel
    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        ums_log('login_denied', 'Role "' . $user['role'] . '" denied at admin login (' . $email . ')');
        return 'This login is for staff and administrators. Please use your Student or Teacher portal.';
    }

    ums_establish_session($user);

    $up = ums_db()->prepare('UPDATE ' . tbl('users') . ' SET last_login = NOW() WHERE id = ?');
    $up->bind_param('i', $user['id']);
    $up->execute();
    $up->close();

    if ($remember) {
        ums_remember_create((int)$user['id']);
    }

    ums_log('login', 'Signed in to UMS');
    return null;
}

/** Populate the hardened session for a verified user row. */
function ums_establish_session(array $user): void
{
    session_regenerate_id(true);
    unset($_SESSION['ums_login_fails']);
    $_SESSION['ums_user'] = [
        'id'        => (int)$user['id'],
        'campus_id' => (int)$user['campus_id'],
        'name'      => $user['name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];
}

/** The correct landing page for a given role after login. */
function ums_role_home(string $role): string
{
    return in_array($role, UMS_ADMIN_ROLES, true)
        ? UMS_URL . '/admin/dashboard.php'
        : UMS_URL . '/admin/dashboard.php'; // teacher/student portals arrive in a later phase
}

/* ─────────────────────────── Remember me ─────────────────────────── */

/** Issue a persistent login cookie (selector:validator, 30 days). */
function ums_remember_create(int $userId): void
{
    $selector  = bin2hex(random_bytes(9));   // 18 chars
    $validator = bin2hex(random_bytes(32));  // 64 chars
    $hash      = hash('sha256', $validator);
    $expires   = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

    $stmt = ums_db()->prepare(
        'INSERT INTO ' . tbl('remember_tokens') . ' (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $userId, $selector, $hash, $expires);
    $stmt->execute();
    $stmt->close();

    setcookie('ums_remember', $selector . ':' . $validator, [
        'expires'  => time() + 60 * 60 * 24 * 30,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
}

/** On a fresh request with no session, restore login from a valid cookie. */
function ums_remember_login(): void
{
    if (ums_user() || empty($_COOKIE['ums_remember'])) {
        return;
    }
    $parts = explode(':', (string)$_COOKIE['ums_remember'], 2);
    if (count($parts) !== 2) {
        ums_remember_clear();
        return;
    }
    [$selector, $validator] = $parts;

    try {
        $stmt = ums_db()->prepare(
            'SELECT t.id, t.validator_hash, t.expires_at, u.id uid, u.campus_id, u.name, u.email, u.role, u.status
             FROM ' . tbl('remember_tokens') . ' t
             JOIN ' . tbl('users') . ' u ON u.id = t.user_id
             WHERE t.selector = ? LIMIT 1'
        );
        $stmt->bind_param('s', $selector);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (Throwable $t) {
        return;
    }

    // invalid selector, expired, tampered validator, or suspended account
    if (!$row
        || strtotime($row['expires_at']) < time()
        || !hash_equals($row['validator_hash'], hash('sha256', $validator))
        || $row['status'] !== 'active') {
        ums_remember_clear();
        return;
    }

    ums_establish_session([
        'id' => $row['uid'], 'campus_id' => $row['campus_id'], 'name' => $row['name'],
        'email' => $row['email'], 'role' => $row['role'],
    ]);
    ums_log('login_remember', 'Auto sign-in via remember-me');
}

/** Remove the remember cookie and its server-side token. */
function ums_remember_clear(): void
{
    if (!empty($_COOKIE['ums_remember'])) {
        $selector = explode(':', (string)$_COOKIE['ums_remember'], 2)[0];
        try {
            $stmt = ums_db()->prepare('DELETE FROM ' . tbl('remember_tokens') . ' WHERE selector = ?');
            $stmt->bind_param('s', $selector);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $t) { /* ignore */ }
        setcookie('ums_remember', '', [
            'expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        ]);
    }
}

/* ─────────────────────── Forgot / reset password ─────────────────── */

/**
 * Create a password-reset request. Always silent about whether the email
 * exists (prevents user enumeration). Returns the reset URL only in local
 * dev so it can be tested without a mail server; production emails it.
 */
function ums_password_reset_request(string $email): ?string
{
    $stmt = ums_db()->prepare('SELECT id FROM ' . tbl('users') . ' WHERE email = ? AND status = "active" LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return null; // caller shows the same generic message regardless
    }

    $token   = bin2hex(random_bytes(32));
    $hash    = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 60 * 60); // 1 hour
    $uid     = (int)$user['id'];

    $ins = ums_db()->prepare(
        'INSERT INTO ' . tbl('password_resets') . ' (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
    );
    $ins->bind_param('iss', $uid, $hash, $expires);
    $ins->execute();
    $ins->close();

    $link = UMS_URL . '/admin/reset-password.php?uid=' . $uid . '&token=' . $token;
    ums_log('password_reset_request', 'Reset requested for ' . $email);

    // TODO (production): email $link to the user via SMTP.
    return UMS_ENV === 'local' ? $link : null;
}

/** Current logged-in UMS user, or null. */
function ums_user(): ?array
{
    return $_SESSION['ums_user'] ?? null;
}

/** Gate a page: require login (optionally specific roles). */
function require_login(array $roles = []): void
{
    $user = ums_user();
    if (!$user) {
        redirect(UMS_URL . '/admin/login.php');
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Access denied — your role does not permit this page.');
    }
}

/** Destroy the UMS session (leaves other site sessions untouched). */
function ums_logout(): void
{
    ums_log('logout', 'Signed out of UMS');
    ums_remember_clear();
    unset($_SESSION['ums_user']);
    session_regenerate_id(true);
}
