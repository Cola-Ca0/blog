<?php
/**
 * Auth Module — Single source of truth for authentication
 * Interface: after require, $isLoggedIn, $username, $isAdmin are set.
 * Include this ONCE at the top of every page that needs auth state.
 */
if (session_status() === PHP_SESSION_NONE) {
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
