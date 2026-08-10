<?php
/**
 * Project Editor — Admin Only
 * /blog/admin/editor-project.php              → new project
 * /blog/admin/editor-project.php?id=xxx       → edit project
 */
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/markdown.php';

if (!$isLoggedIn || !$isAdmin) { header('Location: /blog/login.php'); exit; }

$projectsFile = __DIR__ . '/../projects.json';
$projects = json_decode(file_exists($projectsFile) ? file_get_contents($projectsFile) : '[]', true) ?: [];
$saved = false;
$error = '';

// ========== DELETE HANDLER (before HTML) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['_delete_project'])) {
    $delId = $_GET['id'] ?? '';
    $projects = array_filter($projects, function($p) use ($delId) { return $p['id'] !== $delId; });
    file_put_contents($projectsFile, json_encode(array_values($projects), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    header('Location: /blog/projects/'); exit;
}

// ========== PROJECT CRUD ==========
$isEdit = !empty($_GET['id']);
$editProjectId = $isEdit ? $_GET['id'] : '';
$existingProject = null;

if ($isEdit) {
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
        if ($isEdit) {
            foreach ($projects as $i => $p) { if ($p['id'] === $editProjectId) { $projects[$i] = $entry; break; } }
        } else {
            $projDir = __DIR__ . '/../projects/' . $pId;
            if (!is_dir($projDir)) mkdir($projDir, 0755, true);
            $projects[] = $entry;
        }
        if (file_put_contents($projectsFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
            $saved = true; $isEdit = true; $editProjectId = $pId; $existingProject = $entry;
        } else $error = 'Failed to write projects.json.';
    }
}

$pageTitle = $isEdit ? 'Edit Project' : 'New Project';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/../includes/theme-init.php'; ?>
<title><?= htmlspecialchars($pageTitle) ?> · Admin</title>
<link rel="stylesheet" href="/blog/includes/tokens.css">
<link rel="stylesheet" href="/blog/includes/shared.css">
<link rel="stylesheet" href="/blog/includes/editor-shared.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: var(--font-body);
  background-color: var(--bg-deep);
  color: var(--text-primary);
  line-height: 1.7;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ID field — monospace */
#projIdField { font-family:var(--font-mono,monospace); color:var(--accent); }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/background.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>

<main class="editor-page">
  <a href="/blog/admin/editor.php" class="editor-back">&larr; Editor Hub / 编辑器中心</a>

  <!-- Mode Tabs -->
  <div class="editor-tabs">
    <a href="/blog/admin/editor-article.php" class="editor-tab">Article / 文章</a>
    <a href="/blog/admin/editor-project.php" class="editor-tab active">Project / 项目</a>
  </div>

  <div class="editor-header">
    <h1><?= $isEdit ? 'Edit Project' : 'New Project' ?></h1>
    <span class="badge <?= $isEdit ? 'badge-edit' : 'badge-new' ?>"><?= $isEdit ? 'EDIT MODE' : 'DRAFT MODE' ?></span>
  </div>

  <?php if ($error): ?>
    <div class="editor-msg error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($saved): ?>
    <div class="editor-msg success">
      Project saved. <a href="/blog/projects/">View projects / 查看项目</a>
    </div>
  <?php endif; ?>

  <!-- PROJECT FORM -->
  <form class="editor-form" method="POST">
    <div class="editor-meta-grid">
      <div class="editor-field full-width">
        <label>Project Title / 项目名 <span class="hint">required</span></label>
        <input type="text" name="title" id="titleField" value="<?= htmlspecialchars($_POST['title'] ?? $existingProject['title'] ?? '') ?>" placeholder="My CTF Toolkit" required>
      </div>
      <div class="editor-field">
        <label>ID / 标识符 <span class="hint">folder name, a-z 0-9 hyphens</span></label>
        <input type="text" name="id" id="projIdField" value="<?= htmlspecialchars($_POST['id'] ?? $existingProject['id'] ?? $editProjectId ?? '') ?>" placeholder="my-ctf-tools" required <?= $isEdit ? 'readonly style="opacity:0.6;cursor:not-allowed"' : '' ?>>
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
      <?php if ($isEdit && $existingProject): ?>
      <button type="button" class="btn btn-danger" onclick="deleteItem()">Delete Project</button>
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
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<script src="/blog/includes/editor-shared.js"></script>

<?php if ($isEdit && $existingProject): ?>
<script>
function deleteItem() {
  editorDelete('/blog/admin/editor-project.php?_delete_project=1&id=<?= htmlspecialchars($editProjectId) ?>', '<?= htmlspecialchars(addslashes($existingProject['title'])) ?>', '/blog/projects/');
}
</script>
<?php endif; ?>

<?php if (!$isEdit): ?>
<script>autoSlug('titleField', 'projIdField', 'project');</script>
<?php endif; ?>
</body>
</html>
