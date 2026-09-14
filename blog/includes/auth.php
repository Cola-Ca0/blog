<?php
/**
 * Auth Module — Single source of truth for authentication
 * Interface: after require, $isLoggedIn, $username, $isAdmin are set.
 * Include this ONCE at the top of every page that needs auth state.
 */
if (session_status() === PHP_SESSION_NONE) {
    // 2026-08 上线加固 (审计 §3.2): HttpOnly + SameSite=Lax + strict_mode, 必须在 session_start 之前
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    // 2026-09-14 上线准备: HTTPS 下 cookie 标记 Secure (本地 HTTP 不受影响)
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}
$isLoggedIn = isset($_SESSION['username']) && $_SESSION['username'] !== '';
$username   = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : '';
$isAdmin    = $isLoggedIn && !empty($_SESSION['is_admin']);

// CSRF token — one per session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token']; // available to all pages that require auth
