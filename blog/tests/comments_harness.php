<?php
/**
 * CLI 测试傀儡 (2026-08-16): 子进程内跑 comments-api 调度
 * 用法: php comments_harness.php <base64(json{get,post,sess,sid})>
 * 输出: {"code": <http_status>, "body": <json>}
 * 隔离理由: comments-api.php 顶层 exit 会杀死测试进程, 子进程隔离 + shutdown 包裹最干净。
 * 传参走 base64: Windows 下 escapeshellarg 会弄坏含引号的 JSON (实测 403 假失败)。
 */
$env = json_decode(base64_decode($argv[1] ?? ''), true) ?: [];
$_GET = $env['get'] ?? [];
if (!empty($env['sid'])) session_id($env['sid']);
require __DIR__ . '/../includes/auth.php';
$_POST = $env['post'] ?? [];
foreach (($env['sess'] ?? []) as $k => $v) $_SESSION[$k] = $v; // 覆盖注入 (csrf/is_admin/username)
ob_start();
register_shutdown_function(function () {
    $out = ob_get_clean();
    $code = http_response_code(); // CLI 下未显式设置时返回 false → 兜为 200
    $decoded = json_decode($out, true);
    echo json_encode(['code' => $code === false ? 200 : $code, 'body' => $decoded === null ? $out : $decoded]);
});
require __DIR__ . '/../comments-api.php';
