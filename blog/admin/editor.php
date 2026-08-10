<?php
/**
 * Editor Hub — Admin Only
 * Entry point with tab selector, delegates to editor-article.php / editor-project.php
 */
require __DIR__ . '/../includes/auth.php';

if (!$isLoggedIn || !$isAdmin) { header('Location: /blog/login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editor · Admin</title>
<link rel="stylesheet" href="/blog/includes/tokens.css">
<link rel="stylesheet" href="/blog/includes/shared.css">
<link rel="stylesheet" href="/blog/includes/editor-shared.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; font-size: 16px; }
body {
  font-family: var(--font-body);
  background-color: var(--bg-deep);
  color: var(--text-primary);
  line-height: 1.7;
  min-height: 100vh;
  overflow-x: hidden;
}
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--bg-deep); }
::-webkit-scrollbar-thumb { background: rgba(91,160,224,0.28); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: rgba(91,160,224,0.45); }
::selection { background: rgba(91,160,224,0.35); color: #fff; }

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
  <a href="/blog/" class="hub-back">&larr; Command Center / 指挥中心</a>

  <h1>Editor / 编辑器</h1>
  <p class="subtitle">Choose content type to edit / 选择要编辑的内容类型</p>

  <div class="hub-cards">
    <a href="/blog/admin/editor-article.php" class="hub-card">
      <span class="icon">📝</span>
      <span class="label">Article / 文章</span>
      <span class="desc">Write or edit a new blog transmission</span>
    </a>
    <a href="/blog/admin/editor-project.php" class="hub-card">
      <span class="icon">📦</span>
      <span class="label">Project / 项目</span>
      <span class="desc">Add or edit a project entry</span>
    </a>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
