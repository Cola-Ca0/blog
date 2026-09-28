<?php
/**
 * Editor Hub — Admin Only
 * Entry point with tab selector, delegates to editor-article.php / editor-project.php
 */
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';   // 2026-09-28: $BASE 单一来源(此前重定向硬编码 '/blog/login.php')

if (!$isLoggedIn || !$isAdmin) { header('Location: ' . $BASE . '/login.php'); exit; }

// 2026-09-28: 待审计数 —— 此前 treehole-review.php 没有任何入口(孤岛),
// 评论审核也藏在文章页底部, 管理员无从知道"有没有东西要审"。这里统一暴露。
$pendingTreehole = 0;
foreach (glob(__DIR__ . '/../data/treehole/*.json') ?: [] as $f) {
    $t = json_decode(@file_get_contents($f) ?: '[]', true);
    if (is_array($t) && ($t['status'] ?? '') === 'pending') $pendingTreehole++;
}
$pendingComments = 0;
foreach (glob(__DIR__ . '/../data/comments/*.json') ?: [] as $f) {
    $c = json_decode(@file_get_contents($f) ?: '[]', true);
    if (!is_array($c)) continue;
    foreach ($c as $row) {
        if (is_array($row) && ($row['status'] ?? '') === 'pending') $pendingComments++;
    }
}
$pendingTotal = $pendingTreehole + $pendingComments;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = 'Editor · Admin';
$editorCss = true;
$fonts = 'basic';
require __DIR__ . '/../includes/head.php';
?>
<style>
body {
  font-family: var(--font-body);
  background-color: var(--bg-deep);
  color: var(--text-primary);
  line-height: 1.7;
  min-height: 100vh;
  overflow-x: hidden;
}

.editor-hub { max-width: 500px; margin: 0 auto; padding: 120px 24px 60px; text-align: center; }
.editor-hub h1 { font-family:var(--font-display); font-size:1.6rem; font-weight:700;
  color:var(--accent); letter-spacing:0.04em; margin-bottom:10px; }
.editor-hub .subtitle { color:var(--text-muted); font-size:0.85rem; margin-bottom:36px; }

.hub-cards { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; }
.hub-card { display:flex; flex-direction:column; align-items:center; gap:14px;
  background:var(--bg-card); border:1px solid var(--border-glow); border-radius:var(--radius-lg);
  padding:32px 28px; text-decoration:none; transition:var(--transition-smooth);
  width:200px; }
.hub-card:hover { border-color:var(--border-glow-strong); box-shadow:0 0 28px rgba(91,160,224,0.2);
  transform:translateY(-4px); }
.hub-card .icon { font-size:2.2rem; }
.hub-card .label { font-family:var(--font-display); font-size:0.95rem; font-weight:600;
  color:var(--text-primary); letter-spacing:0.04em; }
.hub-card .desc { font-size:0.72rem; color:var(--text-muted); line-height:1.5; }

.hub-back { display:inline-flex; align-items:center; gap:6px; font-family:var(--font-display);
  font-size:0.72rem; color:var(--text-muted); text-decoration:none; letter-spacing:0.05em;
  margin-bottom:30px; transition:var(--transition-smooth); }
.hub-back:hover { color:var(--accent); }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/background.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>

<main class="editor-hub">
  <a href="<?= $BASE ?>/" class="hub-back">&larr; Command Center / 指挥中心</a>

  <h1>Editor / 编辑器</h1>
  <p class="subtitle">Choose content type to edit / 选择要编辑的内容类型</p>

  <?php if ($pendingTotal > 0): ?>
  <p class="subtitle" style="color:var(--secondary);margin-bottom:22px;">
    ● 有 <b><?= (int)$pendingTotal ?></b> 条待审核
    （树洞 <?= (int)$pendingTreehole ?> · 评论 <?= (int)$pendingComments ?>）
  </p>
  <?php endif; ?>

  <div class="hub-cards">
    <a href="<?= $BASE ?>/admin/editor-article.php" class="hub-card">
      <span class="icon">📝</span>
      <span class="label">Article / 文章</span>
      <span class="desc">Write or edit a new blog transmission</span>
    </a>
    <a href="<?= $BASE ?>/admin/editor-project.php" class="hub-card">
      <span class="icon">📦</span>
      <span class="label">Project / 项目</span>
      <span class="desc">Add or edit a project entry</span>
    </a>
    <a href="<?= $BASE ?>/admin/treehole-review.php" class="hub-card">
      <span class="icon">🕳️</span>
      <span class="label">Review / 审核</span>
      <span class="desc">树洞待审<?= $pendingTreehole > 0 ? ' · ' . (int)$pendingTreehole . ' 条' : '' ?>；评论在文章页评论区就地审核</span>
    </a>
    <a href="<?= $BASE ?>/admin/account.php" class="hub-card">
      <span class="icon">⚙️</span>
      <span class="label">Account / 账号</span>
      <span class="desc">修改昵称与密码</span>
    </a>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
