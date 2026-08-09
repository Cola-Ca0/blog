<?php
/**
 * Unified Editor — Admin Only
 * /blog/admin/editor.php                → new article
 * /blog/admin/editor.php?slug=xxx       → edit article
 * /blog/admin/editor.php?type=project   → new project
 * /blog/admin/editor.php?type=project&id=xxx → edit project
 */
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/markdown.php';

if (!$isLoggedIn || !$isAdmin) { header('Location: /blog/login.php'); exit; }

$mode = ($_GET['type'] ?? 'article') === 'project' ? 'project' : 'article';
$postsDir = __DIR__ . '/../posts/';
$projectsFile = __DIR__ . '/../projects.json';
$saved = false;
$error = '';

// ========== ARTICLE LOGIC ==========
$isEdit = false; $editSlug = ''; $existingPost = null;
if ($mode === 'article') {
    $isEdit = !empty($_GET['slug']);
    $editSlug = $isEdit ? $_GET['slug'] : '';
    if ($isEdit) {
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $editSlug)) $error = 'Invalid slug.';
        else { $existingPost = parsePost($postsDir . $editSlug . '.md'); if ($existingPost === null) $error = 'Article not found.'; }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? ''); $slug = trim($_POST['slug'] ?? '');
        $date = trim($_POST['date'] ?? date('Y-m-d')); $category = trim($_POST['category'] ?? '');
        $tagsRaw = trim($_POST['tags'] ?? ''); $summary = trim($_POST['summary'] ?? '');
        $cover = trim($_POST['cover'] ?? ''); $draft = isset($_POST['draft']) ? 'true' : 'false';
        $body = $_POST['body'] ?? '';
        if ($title === '') $error = 'Title is required.';
        elseif ($slug === '') $error = 'Slug is required.';
        elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) $error = 'Slug: only a-z, 0-9, hyphens.';
        elseif ($category === '') $error = 'Category is required.';
        elseif ($body === '') $error = 'Body is required.';
        else {
            $tagsArr = array_filter(array_map('trim', explode(',', $tagsRaw)));
            $tagsJson = !empty($tagsArr) ? '[' . implode(', ', array_map(function($t) { return '"' . addcslashes($t, '"') . '"'; }, $tagsArr)) . ']' : '[]';
            $md = "---\ntitle: \"" . addcslashes($title, '"') . "\"\ndate: $date\n";
            if (!empty($_POST['updated'])) $md .= 'updated: ' . trim($_POST['updated']) . "\n";
            $md .= "category: $category\ntags: $tagsJson\nsummary: \"" . addcslashes($summary, '"') . "\"\n";
            if ($cover !== '') $md .= "cover: $cover\n";
            $md .= "draft: $draft\n---\n\n$body";
            $filePath = $postsDir . $slug . '.md';
            if ($isEdit && $editSlug !== $slug && file_exists($postsDir . $editSlug . '.md')) unlink($postsDir . $editSlug . '.md');
            if (file_put_contents($filePath, $md, LOCK_EX)) {
                $saved = true; $isEdit = true; $editSlug = $slug; $existingPost = parsePost($filePath);
            } else $error = 'Failed to write file.';
        }
    }
}

// ========== PROJECT LOGIC ==========
$isEditProject = false; $editProjectId = ''; $existingProject = null;
if ($mode === 'project') {
    $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
    $isEditProject = !empty($_GET['id']);
    $editProjectId = $isEditProject ? $_GET['id'] : '';
    if ($isEditProject) {
        foreach ($projects as $p) { if ($p['id'] === $editProjectId) { $existingProject = $p; break; } }
        if ($existingProject === null) $error = 'Project not found.';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pTitle = trim($_POST['title'] ?? ''); $pId = trim($_POST['id'] ?? '');
        $pDesc = trim($_POST['description'] ?? ''); $pTags = trim($_POST['tags'] ?? '');
        $pDate = trim($_POST['date'] ?? date('Y-m')); $pStatus = $_POST['status'] ?? 'completed';
        $pFeatured = isset($_POST['featured']); $pFiles = trim($_POST['files'] ?? '');
        $pReadme = trim($_POST['readme'] ?? 'README.md');
        if ($pTitle === '') $error = 'Title is required.';
        elseif ($pId === '') $error = 'ID is required.';
        elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $pId)) $error = 'ID: only a-z, 0-9, hyphens.';
        elseif ($pDesc === '') $error = 'Description is required.';
        else {
            $entry = [
                'id' => $pId, 'title' => $pTitle, 'description' => $pDesc,
                'tags' => array_filter(array_map('trim', explode(',', $pTags))),
                'date' => $pDate, 'status' => $pStatus, 'featured' => $pFeatured,
                'files' => array_filter(array_map('trim', explode(',', $pFiles))),
                'readme' => $pReadme,
            ];
            if ($isEditProject) {
                foreach ($projects as $i => $p) { if ($p['id'] === $editProjectId) { $projects[$i] = $entry; break; } }
            } else {
                // Ensure project folder exists
                $projDir = __DIR__ . '/../projects/' . $pId;
                if (!is_dir($projDir)) mkdir($projDir, 0755, true);
                $projects[] = $entry;
            }
            if (file_put_contents($projectsFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
                $saved = true; $isEditProject = true; $editProjectId = $pId; $existingProject = $entry;
            } else $error = 'Failed to write projects.json.';
        }
    }
}

$pageTitle = $mode === 'project' ? ($isEditProject ? 'Edit Project' : 'New Project') : ($isEdit ? 'Edit: ' . ($existingPost['title'] ?? $editSlug) : 'New Transmission');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · Admin</title>
<link rel="stylesheet" href="/blog/includes/tokens.css">
<link rel="stylesheet" href="/blog/includes/shared.css">
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

.editor-page { max-width: 900px; margin: 0 auto; padding: 100px 24px 60px; }

.editor-header { display:flex; align-items:center; gap:14px; margin-bottom:32px; }
.editor-header h1 { font-family:var(--font-display); font-size:1.4rem; font-weight:700;
  color:var(--accent); letter-spacing:0.04em; }
.editor-header .badge { font-size:0.62rem; padding:3px 10px; border-radius:50px;
  font-family:var(--font-display); letter-spacing:0.06em; }
.badge-new { background:rgba(142,208,232,0.12); color:var(--accent); border:1px solid var(--border-glow); }
.badge-edit { background:rgba(240,128,96,0.12); color:var(--secondary); border:1px solid rgba(240,128,96,0.25); }

/* Mode tabs */
.editor-tabs { display:flex; gap:0; margin-bottom:28px; }
.editor-tab { padding:8px 22px; font-family:var(--font-display); font-size:0.78rem; font-weight:600;
  letter-spacing:0.04em; text-decoration:none; color:var(--text-muted);
  border:1px solid var(--border-glow); transition:var(--transition-smooth); }
.editor-tab:first-child { border-radius:var(--radius-pill) 0 0 var(--radius-pill); }
.editor-tab:last-child { border-radius:0 var(--radius-pill) var(--radius-pill) 0; }
.editor-tab.active { background:var(--primary); color:var(--text-primary); border-color:var(--primary); }
.editor-tab:not(.active):hover { color:var(--accent); border-color:var(--accent); }

.editor-back { display:inline-flex; align-items:center; gap:6px; font-family:var(--font-display);
  font-size:0.72rem; color:var(--text-muted); text-decoration:none; letter-spacing:0.05em;
  margin-bottom:24px; transition:var(--transition-smooth); }
.editor-back:hover { color:var(--accent); }

/* Success/Error messages */
.editor-msg { padding:12px 18px; border-radius:var(--radius-md); margin-bottom:20px;
  font-size:0.82rem; display:flex; align-items:center; gap:8px; }
.editor-msg.success { background:rgba(142,208,232,0.08); border:1px solid var(--border-glow);
  color:var(--accent); }
.editor-msg.success a { color:var(--accent); font-weight:600; }
.editor-msg.error { background:rgba(240,128,96,0.06); border:1px solid rgba(240,128,96,0.3);
  color:var(--secondary); }

/* Form layout: two-column for meta, full-width for body */
.editor-form { display:flex; flex-direction:column; gap:20px; }

.editor-meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.editor-meta-grid .full-width { grid-column:1 / -1; }

.editor-field { display:flex; flex-direction:column; gap:5px; }
.editor-field label { font-family:var(--font-display); font-size:0.68rem; font-weight:600;
  color:var(--text-muted); letter-spacing:0.06em; text-transform:uppercase; }
.editor-field label .hint { font-weight:400; text-transform:none; color:var(--text-haze);
  font-size:0.64rem; letter-spacing:0.03em; }
.editor-field input, .editor-field textarea, .editor-field select {
  background:rgba(0,0,0,0.25); border:1px solid var(--border-glow); border-radius:var(--radius-sm);
  color:var(--text-primary); padding:10px 14px; font-size:0.88rem; font-family:var(--font-body);
  transition:var(--transition-smooth); outline:none; }
.editor-field input:focus, .editor-field textarea:focus {
  border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,160,224,0.1); }
.editor-field textarea { resize:vertical; line-height:1.6; font-family:var(--font-mono,monospace);
  font-size:0.84rem; min-height:400px; }

/* Draft toggle */
.editor-draft-row { display:flex; align-items:center; gap:10px; padding:10px 14px;
  background:rgba(91,160,224,0.04); border-radius:var(--radius-sm); border:1px solid var(--border-glow); }
.editor-draft-row input[type="checkbox"] { accent-color:var(--accent); width:16px; height:16px; }
.editor-draft-row label { font-family:var(--font-display); font-size:0.78rem; font-weight:600;
  color:var(--text-secondary); letter-spacing:0.04em; cursor:pointer; }

/* Buttons */
.editor-actions { display:flex; gap:12px; align-items:center; padding-top:8px; }
.btn { padding:10px 24px; border:none; border-radius:50px; font-family:var(--font-display);
  font-size:0.78rem; font-weight:600; letter-spacing:0.05em; cursor:pointer;
  transition:var(--transition-smooth); text-decoration:none; display:inline-flex;
  align-items:center; gap:6px; }
.btn-primary { background:var(--primary); color:#fff; }
.btn-primary:hover { box-shadow:0 0 20px rgba(91,160,224,0.35); transform:translateY(-1px); }
.btn-secondary { background:transparent; color:var(--text-muted); border:1px solid var(--border-glow); }
.btn-secondary:hover { border-color:var(--accent); color:var(--accent); }
.btn-danger { background:transparent; color:var(--secondary); border:1px solid rgba(240,128,96,0.3); }
.btn-danger:hover { background:rgba(240,128,96,0.08); }

/* Slug field — monospace */
#slugField { font-family:var(--font-mono,monospace); color:var(--accent); }

/* Quick reference */
.quick-ref { margin-top:40px; padding:16px 20px; background:rgba(0,0,0,0.2);
  border-radius:var(--radius-md); border:1px solid rgba(91,160,224,0.1); }
.quick-ref h4 { font-family:var(--font-display); font-size:0.72rem; color:var(--text-muted);
  letter-spacing:0.06em; margin-bottom:10px; }
.quick-ref code { font-size:0.72rem; color:var(--accent); }
.quick-ref .grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 16px;
  font-size:0.72rem; color:var(--text-haze); }
.quick-ref .grid span { color:var(--accent); }

/* Mobile */
@media (max-width: 768px) {
  .editor-page { padding: 80px 16px 40px; }
  .editor-meta-grid { grid-template-columns:1fr; }
  .editor-actions { flex-wrap:wrap; }
  .editor-actions .btn { flex:1; justify-content:center; min-width:120px; }
  .editor-header h1 { font-size:1.1rem; }
  .quick-ref .grid { grid-template-columns:1fr; }
  .editor-field textarea { min-height:300px; }
}
@media (max-width: 480px) {
  .editor-page { padding: 72px 12px 32px; }
  .editor-header { flex-direction:column; align-items:flex-start; gap:8px; }
  .editor-actions { flex-direction:column; }
  .editor-actions .btn { width:100%; }
}
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/background.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>

<main class="editor-page">
  <a href="/blog/" class="editor-back">&larr; Command Center / 指挥中心</a>

  <!-- Mode Tabs -->
  <div class="editor-tabs">
    <a href="/blog/admin/editor.php" class="editor-tab <?= $mode === 'article' ? 'active' : '' ?>">Article / 文章</a>
    <a href="/blog/admin/editor.php?type=project" class="editor-tab <?= $mode === 'project' ? 'active' : '' ?>">Project / 项目</a>
  </div>

  <div class="editor-header">
    <h1><?= $mode === 'project' ? ($isEditProject ? 'Edit Project' : 'New Project') : ($isEdit ? 'Edit Transmission' : 'New Transmission') ?></h1>
    <span class="badge <?= ($isEdit||$isEditProject) ? 'badge-edit' : 'badge-new' ?>"><?= ($isEdit||$isEditProject) ? 'EDIT MODE' : 'DRAFT MODE' ?></span>
  </div>

  <?php if ($error): ?>
    <div class="editor-msg error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($saved): ?>
    <div class="editor-msg success">
      <?php if ($mode === 'project'): ?>
        Project saved. <a href="/blog/projects/">View projects / 查看项目</a>
      <?php else: ?>
        Transmission saved. <a href="/blog/post/<?= htmlspecialchars($editSlug) ?>">View article / 查看文章</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($mode === 'article'): ?>
  <!-- ========== ARTICLE FORM ========== -->
  <form class="editor-form" method="POST">
    <div class="editor-meta-grid">
      <div class="editor-field full-width">
        <label>Title / 标题 <span class="hint">required</span></label>
        <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $existingPost['title'] ?? '') ?>" placeholder="Article title..." required>
      </div>
      <div class="editor-field">
        <label>Slug <span class="hint">URL identifier</span></label>
        <input type="text" name="slug" id="slugField" value="<?= htmlspecialchars($_POST['slug'] ?? $existingPost['slug'] ?? $editSlug ?? '') ?>" placeholder="my-article-slug" required <?= $isEdit ? 'readonly style="opacity:0.6;cursor:not-allowed"' : '' ?>>
      </div>
      <div class="editor-field">
        <label>Date / 日期</label>
        <input type="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? $existingPost['date'] ?? date('Y-m-d')) ?>">
      </div>
      <div class="editor-field">
        <label>Category / 分类 <span class="hint">required</span></label>
        <input type="text" name="category" value="<?= htmlspecialchars($_POST['category'] ?? $existingPost['category'] ?? '') ?>" placeholder="WebDev" required>
      </div>
      <div class="editor-field">
        <label>Tags / 标签 <span class="hint">comma-separated</span></label>
        <input type="text" name="tags" value="<?= htmlspecialchars($_POST['tags'] ?? (isset($existingPost['tags']) ? implode(', ', $existingPost['tags']) : '')) ?>" placeholder="PHP, Security">
      </div>
      <div class="editor-field full-width">
        <label>Summary / 摘要</label>
        <input type="text" name="summary" value="<?= htmlspecialchars($_POST['summary'] ?? $existingPost['summary'] ?? '') ?>" placeholder="Card preview description...">
      </div>
      <div class="editor-field full-width">
        <label>Cover Image / 封面图 <span class="hint">optional</span></label>
        <input type="text" name="cover" value="<?= htmlspecialchars($_POST['cover'] ?? $existingPost['cover'] ?? '') ?>" placeholder="/blog/assets/images/posts/cover.jpg">
      </div>
      <?php if ($isEdit): ?>
      <div class="editor-field">
        <label>Updated Date</label>
        <input type="date" name="updated" value="<?= htmlspecialchars($_POST['updated'] ?? $existingPost['updated'] ?? '') ?>">
      </div>
      <?php endif; ?>
      <div class="editor-draft-row full-width">
        <input type="checkbox" name="draft" id="draftToggle" <?= (($_POST['draft'] ?? $existingPost['draft'] ?? false) ? 'checked' : '') ?>>
        <label for="draftToggle">Draft / 草稿 — hidden from public</label>
      </div>
    </div>
    <div class="editor-field">
      <label>Body / 正文 <span class="hint">Markdown</span></label>
      <textarea name="body" id="bodyField" placeholder="## Start writing..."><?= htmlspecialchars($_POST['body'] ?? $existingPost['body'] ?? '') ?></textarea>
    </div>
    <div class="editor-actions">
      <button type="submit" class="btn btn-primary">TRANSMIT SIGNAL</button>
      <a href="<?= $isEdit ? '/blog/post/' . htmlspecialchars($editSlug) : '/blog/' ?>" class="btn btn-secondary">Cancel</a>
      <?php if ($isEdit && $existingPost): ?>
      <button type="button" class="btn btn-danger" onclick="deleteArticle()">Delete Article</button>
      <?php endif; ?>
    </div>
  </form>

  <!-- Quick Markdown Reference -->
  <div class="quick-ref">
    <h4>Markdown Quick Reference</h4>
    <div class="grid">
      <div><span># H1</span> <span>## H2</span> <span>### H3</span></div>
      <div><span>**bold**</span> <span>*italic*</span> <span>~~strike~~</span></div>
      <div><span>`code`</span> <span>```lang\ncode\n```</span></div>
      <div><span>[text](url)</span> <span>![alt](img)</span></div>
      <div><span>- list item</span> <span>> blockquote</span></div>
      <div><span>---</span> <span>horizontal rule</span></div>
    </div>
  </div>

  <?php else: ?>
  <!-- ========== PROJECT FORM ========== -->
  <form class="editor-form" method="POST">
    <div class="editor-meta-grid">
      <div class="editor-field full-width">
        <label>Project Title / 项目名 <span class="hint">required</span></label>
        <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $existingProject['title'] ?? '') ?>" placeholder="My CTF Toolkit" required>
      </div>
      <div class="editor-field">
        <label>ID / 标识符 <span class="hint">folder name, a-z 0-9 hyphens</span></label>
        <input type="text" name="id" id="projIdField" value="<?= htmlspecialchars($_POST['id'] ?? $existingProject['id'] ?? $editProjectId ?? '') ?>" placeholder="my-ctf-tools" required <?= $isEditProject ? 'readonly style="opacity:0.6;cursor:not-allowed"' : '' ?>>
      </div>
      <div class="editor-field">
        <label>Date / 日期 <span class="hint">YYYY-MM</span></label>
        <input type="text" name="date" value="<?= htmlspecialchars($_POST['date'] ?? $existingProject['date'] ?? date('Y-m')) ?>" placeholder="2026-08">
      </div>
      <div class="editor-field">
        <label>Status / 状态</label>
        <select name="status">
          <?php $status = $_POST['status'] ?? $existingProject['status'] ?? 'completed'; ?>
          <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
          <option value="in-progress" <?= $status === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
          <option value="planned" <?= $status === 'planned' ? 'selected' : '' ?>>Planned</option>
        </select>
      </div>
      <div class="editor-field full-width">
        <label>Description / 描述 <span class="hint">required</span></label>
        <textarea name="description" rows="3" style="min-height:80px;font-family:var(--font-body)" placeholder="Brief project description..." required><?= htmlspecialchars($_POST['description'] ?? $existingProject['description'] ?? '') ?></textarea>
      </div>
      <div class="editor-field">
        <label>Tags / 标签 <span class="hint">comma-separated</span></label>
        <input type="text" name="tags" value="<?= htmlspecialchars($_POST['tags'] ?? (isset($existingProject['tags']) ? implode(', ', $existingProject['tags']) : '')) ?>" placeholder="PHP, Security, CTF">
      </div>
      <div class="editor-field">
        <label>Files / 文件列表 <span class="hint">comma-separated</span></label>
        <input type="text" name="files" value="<?= htmlspecialchars($_POST['files'] ?? (isset($existingProject['files']) ? implode(', ', $existingProject['files']) : '')) ?>" placeholder="scanner.py, payloads.txt, README.md">
      </div>
      <div class="editor-field">
        <label>README Filename <span class="hint">default: README.md</span></label>
        <input type="text" name="readme" value="<?= htmlspecialchars($_POST['readme'] ?? $existingProject['readme'] ?? 'README.md') ?>" placeholder="README.md">
      </div>
      <div class="editor-draft-row full-width">
        <input type="checkbox" name="featured" id="featuredToggle" <?= (($_POST['featured'] ?? $existingProject['featured'] ?? false) ? 'checked' : '') ?>>
        <label for="featuredToggle">Featured / 精选 — show on homepage</label>
      </div>
    </div>
    <div class="editor-actions">
      <button type="submit" class="btn btn-primary">SAVE PROJECT</button>
      <a href="/blog/projects/" class="btn btn-secondary">Cancel</a>
      <?php if ($isEditProject && $existingProject): ?>
      <button type="button" class="btn btn-danger" onclick="deleteProject()">Delete Project</button>
      <?php endif; ?>
    </div>
  </form>

  <div class="quick-ref" style="margin-top:20px">
    <h4>Project Setup</h4>
    <p style="font-size:0.72rem;color:var(--text-haze);line-height:1.6">
      1. Create folder <code>blog/projects/<span style="color:var(--accent)">{id}</span>/</code><br>
      2. Add project files + <code>README.md</code> inside it<br>
      3. Save this form to publish the project entry
    </p>
  </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php if ($mode === 'article' && $isEdit && $existingPost): ?>
<script>
function deleteArticle() {
  if (!confirm('Permanently delete "<?= htmlspecialchars(addslashes($existingPost['title'] ?? $editSlug)) ?>"?\n\nThis cannot be undone.')) return;
  if (!confirm('Are you sure?')) return;
  fetch('/blog/admin/editor.php?slug=<?= htmlspecialchars($editSlug) ?>&_delete=1', { method: 'POST' })
    .then(function() { window.location.href = '/blog/'; })
    .catch(function() { alert('Delete failed.'); });
}
</script>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['_delete'])) {
    $delSlug = $_GET['slug'] ?? '';
    if (preg_match('/^[a-zA-Z0-9\-]+$/', $delSlug)) {
        $delPath = $postsDir . $delSlug . '.md';
        if (file_exists($delPath)) unlink($delPath);
        $commentFile = __DIR__ . '/../data/comments/' . $delSlug . '.json';
        if (file_exists($commentFile)) unlink($commentFile);
    }
    header('Location: /blog/'); exit;
}
endif;
?>

<?php if ($mode === 'project' && $isEditProject && $existingProject): ?>
<script>
function deleteProject() {
  if (!confirm('Delete project "<?= htmlspecialchars(addslashes($existingProject['title'])) ?>"?\n\nThis removes the entry from projects.json but does not delete project files.')) return;
  if (!confirm('Are you sure?')) return;
  fetch('/blog/admin/editor.php?type=project&_delete_project=1&id=<?= htmlspecialchars($editProjectId) ?>', { method: 'POST' })
    .then(function() { window.location.href = '/blog/projects/'; })
    .catch(function() { alert('Delete failed.'); });
}
</script>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['_delete_project'])) {
    $delId = $_GET['id'] ?? '';
    $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
    $projects = array_filter($projects, function($p) use ($delId) { return $p['id'] !== $delId; });
    file_put_contents($projectsFile, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    header('Location: /blog/projects/'); exit;
}
endif;
?>

<!-- Auto slug from title (new article only) -->
<?php if ($mode === 'article' && !$isEdit): ?>
<script>
(function() {
  var titleInput = document.querySelector('input[name="title"]');
  var slugInput = document.getElementById('slugField');
  var slugTouched = false;
  slugInput.addEventListener('input', function() { slugTouched = true; });
  titleInput.addEventListener('input', function() {
    if (slugTouched) return;
    var slug = titleInput.value.replace(/[^a-zA-Z0-9\s-]/g, '').trim().toLowerCase().replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    if (!slug) slug = 'post-' + new Date().toISOString().slice(0,10);
    slugInput.value = slug;
  });
})();
</script>
<?php endif; ?>

<!-- Auto ID from title (new project only) -->
<?php if ($mode === 'project' && !$isEditProject): ?>
<script>
(function() {
  var titleInput = document.querySelector('input[name="title"]');
  var idInput = document.getElementById('projIdField');
  var idTouched = false;
  idInput.addEventListener('input', function() { idTouched = true; });
  titleInput.addEventListener('input', function() {
    if (idTouched) return;
    var id = titleInput.value.replace(/[^a-zA-Z0-9\s-]/g, '').trim().toLowerCase().replace(/\s+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    if (!id) id = 'project-' + new Date().toISOString().slice(0,7);
    idInput.value = id;
  });
})();
</script>
<?php endif; ?>
</body>
</html>
