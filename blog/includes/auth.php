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
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if ($https) {
        ini_set('session.cookie_secure', '1');
    }

    // 2026-09-28 用户需求: 会话保持 30 天, 免去频繁重新登录。
    // 两处都要设, 缺一不可:
    //   ① cookie lifetime  —— 决定浏览器关掉后 cookie 还留多久
    //   ② gc_maxlifetime   —— 决定服务端 session 文件多久被回收
    // 只设 ① 不够: 服务端默认 24 分钟就回收 session 文件, 回来照样是登出状态。
    // ⚠️ Apache 环境下 gc_maxlifetime 还受系统 sessionclean 定时任务影响(它读 php.ini 而非运行时 ini_set),
    //    因此线上需配套写入 php.ini 片段 —— 见 docs/上线手册-国内轻量.md。
    $sessionTtl = 60 * 60 * 24 * 30;
    ini_set('session.gc_maxlifetime', (string)$sessionTtl);
    session_set_cookie_params([
        'lifetime' => $sessionTtl,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https,
    ]);
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
