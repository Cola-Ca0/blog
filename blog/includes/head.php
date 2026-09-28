<?php
// includes/head.php — 全站公共 <head> 模板 (2026-08-17 从 9 个页面提取, 宪法 2.6 CSS 单一来源)
// 调用前设置: $pageTitle (必填, 原样文本, 本模板负责转义)
// 可选: $pageDesc (description meta) / $extraHead (原始 HTML, 如 og:*) / $editorCss (加载编辑器样式)
// 宪法 2.1 铁律: theme-init.php 在任何 CSS 渲染前执行 (防 FOUC)
// 2026-08-27 提速: 全站字体本地化 (Exo2/Rajdhani/Great Vibes 全部 assets/fonts 自托管), 彻底脱离 fonts.loli.net 渲染阻塞
$fontSet = $fonts ?? 'full'; // 'full'|'basic'|'code' 保留兼容; 字体一律本地, 仅编辑器页后续可按需加载 Fira Code

// 2026-09-28 部署修复: $BASE 必须来自 config.php 单一来源。
// 此前本文件第 20 行的 CSS 路径硬编码 '/blog/includes/', 而字体那几行用的是 $BASE ——
// 同一文件两套写法。本地 $BASE='/blog' 恰好与硬编码一致所以看不出来;
// 线上是域名根部署($BASE=''), 硬编码会让**全站 CSS 404 → 整站无样式**。
require_once __DIR__ . '/config.php';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/theme-init.php'; ?>
<script>document.documentElement.classList.add('js');</script>
<?php
// 2026-09-28 部署修复(第二批): 把 $BASE 暴露给前端 JS。
// 上一批只修了 PHP 里的 17 处硬编码 —— 漏掉 JS, 因为 JS 拿不到 PHP 变量:
//   post-loader.js 11 处 '/blog/...'、music-player.js 3 处相对路径、index.php 的 js/ 相对 src。
// 本地 $BASE='/blog' 恰好与硬编码一致所以看不出来; 线上是域名根部署($BASE=''),
// 表现 = 首页**卡片链接全部 404**(点进去是 Apache Not Found 页)、翻页与音乐 API 全断。
// ⚠️ index.php 也由 /page/N 重写命中, 此时地址栏不含文件名 —— 任何**相对路径**都会解析错
//    (js/x.js → /page/js/x.js), 所以下面这些一律用绝对 BASE 前缀, 不用相对。
?>
<script>window.BLOG_BASE = <?= json_encode($BASE) ?>;</script>
<title><?= htmlspecialchars($pageTitle ?? 'Cola_CaO') ?></title>
<?php if (!empty($pageDesc)): ?>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php endif; ?>
<?= $extraHead ?? '' ?>
<?php
// CSS 缓存指纹 (2026-09-10): 以文件 mtime 作版本号, 改文件即自动换 URL, 根治「改版后访客拿旧缓存」
$cssV = fn (string $f): string => $BASE . '/includes/' . $f . '?v=' . filemtime(__DIR__ . '/' . $f);
?>
<link rel="stylesheet" href="<?= $cssV('tokens.css') ?>">
<link rel="stylesheet" href="<?= $cssV('shared.css') ?>">
<?php if (!empty($editorCss)): ?>
<link rel="stylesheet" href="<?= $cssV('editor-shared.css') ?>">
<?php endif; ?>
<!-- 本地字体 (2026-08-27: Exo2/Rajdhani woff 切片自托管, Great Vibes 同先例; 无外网请求) -->
<link rel="stylesheet" href="<?= $BASE ?>/assets/fonts/index.css">
<link rel="stylesheet" href="<?= $BASE ?>/assets/fonts/great-vibes/index.css">
<?php if ($fontSet === 'code'): ?>
<link rel="stylesheet" href="<?= $BASE ?>/assets/fonts/fira-code/index.css">
<?php endif; ?>
