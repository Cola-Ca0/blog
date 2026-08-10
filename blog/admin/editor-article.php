<?php
/**
 * Article Editor — Admin Only
 * /blog/admin/editor-article.php              → new article
 * /blog/admin/editor-article.php?slug=xxx     → edit article
 */
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/markdown.php';

if (!$isLoggedIn || !$isAdmin) { header('Location: /blog/login.php'); exit; }

$postsDir = __DIR__ . '/../posts/';
$saved = false;
$error = '';

// ========== DELETE HANDLER (before HTML) ==========
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

// ========== ARTICLE CRUD ==========
$isEdit = !empty($_GET['slug']);
$editSlug = $isEdit ? $_GET['slug'] : '';
$existingPost = null;

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

$pageTitle = $isEdit ? 'Edit: ' . ($existingPost['title'] ?? $editSlug) : 'New Transmission';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> · Admin</title>
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

/* Slug field — monospace */
#slugField { font-family:var(--font-mono,monospace); color:var(--accent); }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/background.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>

<main class="editor-page">
  <a href="/blog/admin/editor.php" class="editor-back">&larr; Editor Hub / 编辑器中心</a>

  <!-- Mode Tabs -->
  <div class="editor-tabs">
    <a href="/blog/admin/editor-article.php" class="editor-tab active">Article / 文章</a>
    <a href="/blog/admin/editor-project.php" class="editor-tab">Project / 项目</a>
  </div>

  <div class="editor-header">
    <h1><?= $isEdit ? 'Edit Transmission' : 'New Transmission' ?></h1>
    <span class="badge <?= $isEdit ? 'badge-edit' : 'badge-new' ?>"><?= $isEdit ? 'EDIT MODE' : 'DRAFT MODE' ?></span>
  </div>

  <?php if ($error): ?>
    <div class="editor-msg error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($saved): ?>
    <div class="editor-msg success">
      Transmission saved. <a href="/blog/post/<?= htmlspecialchars($editSlug) ?>">View article / 查看文章</a>
    </div>
  <?php endif; ?>

  <!-- ARTICLE FORM -->
  <form class="editor-form" method="POST">
    <div class="editor-meta-grid">
      <div class="editor-field full-width">
        <label>Title / 标题 <span class="hint">required</span></label>
        <input type="text" name="title" id="titleField" value="<?= htmlspecialchars($_POST['title'] ?? $existingPost['title'] ?? '') ?>" placeholder="Article title..." required>
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
      <a href="<?= $isEdit ? '/blog/post/' . htmlspecialchars($editSlug) : '/blog/admin/editor.php' ?>" class="btn btn-secondary">Cancel</a>
      <?php if ($isEdit && $existingPost): ?>
      <button type="button" class="btn btn-danger" onclick="deleteItem()">Delete Article</button>
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
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php if ($isEdit && $existingPost): ?>
<script>
function deleteItem() {
  if (!confirm('Permanently delete "<?= htmlspecialchars(addslashes($existingPost['title'] ?? $editSlug)) ?>"?\n\nThis cannot be undone.')) return;
  if (!confirm('Are you sure?')) return;
  fetch('/blog/admin/editor-article.php?slug=<?= htmlspecialchars($editSlug) ?>&_delete=1', { method: 'POST' })
    .then(function() { window.location.href = '/blog/'; })
    .catch(function() { alert('Delete failed.'); });
}
</script>
<?php endif; ?>

<?php if (!$isEdit): ?>
<script>
// Auto slug from title (new article only)
(function() {
  var titleInput = document.getElementById('titleField');
  var slugInput = document.getElementById('slugField');
  if (!titleInput || !slugInput) return;
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
</body>
</html>
