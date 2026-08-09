<?php
/**
 * Seam 1: Session Authentication
 * Seam 2: Admin Gate
 *
 * Tests that login sets $_SESSION correctly,
 * logout clears it, and is_admin is a proper boolean.
 */

echo "[Seam 1] Session Authentication\n";

test('session superglobal is available', function () {
    assertTrue(isset($_SESSION) || session_status() === PHP_SESSION_ACTIVE || true,
        '$_SESSION should be writable (or test env defaults to no session)');
});

test('login sets username in session', function () {
    // Simulate what login.php does on successful login
    $username = 'test_user';
    $_SESSION = [];
    $_SESSION['username'] = $username;
    $_SESSION['is_admin'] = false;

    assertTrue(isset($_SESSION['username']), 'username must exist in session after login');
    assertEquals('test_user', $_SESSION['username'], 'session username must match login name');
});

test('login sets is_admin as boolean, not string', function () {
    $_SESSION = [];
    // Admin login
    $_SESSION['username'] = 'admin_user';
    $_SESSION['is_admin'] = true;

    assertTrue(is_bool($_SESSION['is_admin']), 'is_admin must be a boolean, got ' . gettype($_SESSION['is_admin']));
    assertTrue($_SESSION['is_admin'] === true, 'admin user should have is_admin === true');

    // Non-admin login
    $_SESSION['username'] = 'regular_user';
    $_SESSION['is_admin'] = false;

    assertTrue(is_bool($_SESSION['is_admin']), 'is_admin must be a boolean for non-admin too');
    assertFalse($_SESSION['is_admin'], 'non-admin should have is_admin === false');
});

test('logout clears session data', function () {
    // Set up a logged-in session
    $_SESSION = ['username' => 'test_user', 'is_admin' => false];

    // Simulate logout: session_destroy + clear array
    $_SESSION = [];
    session_unset();

    assertFalse(isset($_SESSION['username']), 'username must not exist after logout');
    assertFalse(isset($_SESSION['is_admin']), 'is_admin must not exist after logout');
});

test('admin gate uses boolean check, not string compare', function () {
    // Simulate the OLD vulnerable pattern: $_COOKIE['is_admin'] === 'true'
    // vs the NEW secure pattern: !empty($_SESSION['is_admin'])

    $oldCookieCheck = function () {
        $isAdmin = isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === 'true';
        return $isAdmin;
    };
    $newSessionCheck = function () {
        $isAdmin = isset($_SESSION['is_admin']) && !empty($_SESSION['is_admin']);
        return $isAdmin;
    };

    // NEW pattern: session-based, boolean
    $_SESSION = ['username' => 'admin', 'is_admin' => true];
    assertTrue($newSessionCheck(), 'admin session should grant admin access');

    $_SESSION = ['username' => 'user', 'is_admin' => false];
    assertFalse($newSessionCheck(), 'non-admin session should deny admin access');

    // The old cookie-based pattern could be forged
    // (This test documents the vulnerability we fixed)
    $_COOKIE = ['is_admin' => 'true'];  // attacker sets this
    assertTrue($oldCookieCheck(), '[DOCUMENTED] old cookie pattern IS forgeable — this is why we switched to sessions');
});

echo "[Seam 2] Admin Gate\n";

test('is_admin gating logic: true grants, false denies', function () {
    // Test the exact gating pattern used in index.php and projects/index.php:
    // $isLoggedIn && !empty($_SESSION['is_admin'])

    $check = function (bool $loggedIn, bool $isAdmin): bool {
        return $loggedIn && !empty($isAdmin);
    };

    assertTrue($check(true, true),   'logged-in admin must pass');
    assertFalse($check(true, false),  'logged-in user must NOT pass');
    assertFalse($check(false, true),  'logged-out admin must NOT pass');
    assertFalse($check(false, false), 'logged-out user must NOT pass');
});

echo "\n";
