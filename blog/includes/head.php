<?php
// includes/head.php — 全站公共 <head> 模板 (2026-08-17 从 9 个页面提取, 宪法 2.6 CSS 单一来源)
// 调用前设置: $pageTitle (必填, 原样文本, 本模板负责转义)
// 可选: $pageDesc (description meta) / $extraHead (原始 HTML, 如 og:*) / $editorCss (加载编辑器样式)
// 宪法 2.1 铁律: theme-init.php 在任何 CSS 渲染前执行 (防 FOUC)
// 2026-08-27 提速: 全站字体本地化 (Exo2/Rajdhani/Great Vibes 全部 assets/fonts 自托管), 彻底脱离 fonts.loli.net 渲染阻塞
$fontSet = $fonts ?? 'full'; // 'full'|'basic'|'code' 保留兼容; 字体一律本地, 仅编辑器页后续可按需加载 Fira Code
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/theme-init.php'; ?>
<script>document.documentElement.classList.add('js');</script>
<title><?= htmlspecialchars($pageTitle ?? 'Cola_CaO') ?></title>
<?php if (!empty($pageDesc)): ?>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php endif; ?>
<?= $extraHead ?? '' ?>
<link rel="stylesheet" href="/blog/includes/tokens.css">
<link rel="stylesheet" href="/blog/includes/shared.css">
<?php if (!empty($editorCss)): ?>
<link rel="stylesheet" href="/blog/includes/editor-shared.css">
<?php endif; ?>
<!-- 本地字体 (2026-08-27: Exo2/Rajdhani woff 切片自托管, Great Vibes 同先例; 无外网请求) -->
<link rel="stylesheet" href="/blog/assets/fonts/index.css">
<link rel="stylesheet" href="/blog/assets/fonts/great-vibes/index.css">
<?php if ($fontSet === 'code'): ?>
<link rel="stylesheet" href="/blog/assets/fonts/fira-code/index.css">
<?php endif; ?>
