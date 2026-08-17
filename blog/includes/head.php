<?php
// includes/head.php — 全站公共 <head> 模板 (2026-08-17 从 9 个页面提取, 宪法 2.6 CSS 单一来源)
// 调用前设置: $pageTitle (必填, 原样文本, 本模板负责转义)
// 可选: $pageDesc (description meta) / $extraHead (原始 HTML, 如 og:*) / $editorCss (加载编辑器样式) / $fonts ('full'|'basic'|'code', 默认 'full')
// 宪法 2.1 铁律: theme-init.php 在任何 CSS 渲染前执行 (防 FOUC)
$fontSet = $fonts ?? 'full';
$fontUrl = $fontSet === 'basic'
  ? 'https://fonts.loli.net/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Rajdhani:wght@300;400;500;600;700&display=swap'
  : 'https://fonts.loli.net/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Great+Vibes&family=Noto+Serif+SC:wght@300;400;500;600;700&family=Quicksand:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap';
if ($fontSet === 'code') { $fontUrl = str_replace('&display=swap', '&family=Fira+Code:wght@400;500&display=swap', $fontUrl); }
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/theme-init.php'; ?>
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
<link rel="preconnect" href="https://fonts.loli.net">
<link href="<?= $fontUrl ?>" rel="stylesheet">
