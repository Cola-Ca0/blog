<?php
/**
 * 部署路径守卫 (2026-09-28)
 *
 * 站内一切指向自身资源的路径, 必须经 $BASE 前缀, 且不得写成相对路径。
 *
 * 为什么值得单独一个测试文件 —— 这条规则已经被踩过**两次**:
 *   1) 707631f 之前: PHP 里 17 处硬编码 '/blog/' (CSS/导航/评论/编辑器)
 *   2) 02efb72 之前: JS 里 15 处 (post-loader.js 11 + music-player.js 3 + index.php 7 相对 src)
 * 而**本地永远看不出问题**: 本地 $BASE='/blog' 恰好与硬编码字面量一致,
 * 所以全站绿、测试全绿、浏览器一切正常 —— 只有线上域名根部署($BASE='')才炸。
 * 这类「本地不可见」的缺陷, 靠人眼复查是靠不住的, 只能靠断言。
 *
 * 两个独立的失配模式:
 *   ① 硬编码 '/blog/...'  → 线上多出一层不存在的前缀 → Apache Not Found
 *   ② 相对路径 'x'        → 本地/线上在 /page/N 上都会解析错 (地址栏不是目录)
 */

$blogRoot = dirname(__DIR__);

// ── ① JS 不得硬编码 '/blog/' 前缀 ──────────────────────────────────────
test('JS 无硬编码 /blog/ 前缀 (须用 window.BLOG_BASE)', function () use ($blogRoot) {
    foreach (glob($blogRoot . '/js/*.js') as $f) {
        foreach (file($f, FILE_IGNORE_NEW_LINES) as $i => $line) {
            if (preg_match('~^\s*(//|\*|/\*)~', $line)) continue;   // 注释里说明历史是允许的
            foreach (["'/blog/", '"/blog/'] as $needle) {
                assertFalse(str_contains($line, $needle),
                    basename($f) . ':' . ($i + 1) . " 出现硬编码 $needle —— 应改用 BASE + '/...'");
            }
        }
    }
});

// ── ② JS 不得用相对路径 fetch (在 /page/N 上会解析成 /page/xxx.php) ────
test('JS 的 fetch 无相对路径 (须用 window.BLOG_BASE)', function () use ($blogRoot) {
    foreach (glob($blogRoot . '/js/*.js') as $f) {
        foreach (file($f, FILE_IGNORE_NEW_LINES) as $i => $line) {
            if (preg_match('~^\s*(//|\*|/\*)~', $line)) continue;
            if (!preg_match_all('~fetch\(\s*([\'"])(.*?)\1~', $line, $m)) continue;
            foreach ($m[2] as $url) {
                assertTrue(str_starts_with($url, '/') || preg_match('~^https?://~', $url),
                    basename($f) . ':' . ($i + 1) . " fetch('$url') 是相对路径 —— 应写成 BASE + '/$url'");
            }
        }
    }
});

// ── ③ index.php 的 src/href 不得是相对路径 ─────────────────────────────
// 只测 index.php: 它被 .htaccess 的 ^page/([0-9]+)$ 重写命中, 地址栏停在 /page/2,
// 此时 "js/x.js" 会解析成 /page/js/x.js。其余页面(如 login.php 的 href="index.php")
// 是真的同目录兄弟文件, 相对写法正确, 不在此列。
test('index.php 无相对 src/href (会被 /page/N 重写解析错)', function () use ($blogRoot) {
    foreach (file($blogRoot . '/index.php', FILE_IGNORE_NEW_LINES) as $i => $line) {
        if (preg_match('~^\s*(//|\*|/\*|<!--)~', $line)) continue;
        if (!preg_match_all('~\b(src|href)="([^"]*)"~', $line, $m, PREG_SET_ORDER)) continue;
        foreach ($m as $attr) {
            $v = $attr[2];
            if ($v === '' || str_starts_with($v, '<?php') || str_starts_with($v, '<?=')) continue;
            assertTrue(str_starts_with($v, '/') || str_starts_with($v, '#')
                    || preg_match('~^(https?:)?//|^mailto:|^javascript:~', $v),
                'index.php:' . ($i + 1) . " {$attr[1]}=\"$v\" 是相对路径");
        }
    }
});

// ── ④ head.php 必须把 $BASE 注入给 JS ──────────────────────────────────
test('head.php 向 JS 注入 window.BLOG_BASE', function () use ($blogRoot) {
    $head = file_get_contents($blogRoot . '/includes/head.php');
    assertTrue(str_contains($head, 'window.BLOG_BASE'),
        'head.php 少了 window.BLOG_BASE —— 所有 JS 会退化成 BASE="" 然后拼出错误路径');
});
