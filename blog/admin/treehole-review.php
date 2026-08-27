<?php
/**
 * 树洞审核 — Admin Only (2026-08-27)
 * 宪法 3.x: is_admin 守卫 + CSRF + 写只认 POST
 */
require __DIR__ . '/../includes/auth.php';
if (!$isLoggedIn || !$isAdmin) { header('Location: /blog/login.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['csrf_token'];

$treeholeDir = __DIR__ . '/../data/treehole/';

// ========== 动作 (POST only) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        http_response_code(403); exit('403 Forbidden');
    }
    $id = preg_replace('/[^a-f0-9]/', '', $_POST['id'] ?? '');
    $action = $_POST['action'] ?? '';
    $file = $treeholeDir . $id . '.json';
    if ($id && file_exists($file)) {
        $t = json_decode(file_get_contents($file), true);
        if ($action === 'approve') { $t['status'] = 'approved'; file_put_contents($file, json_encode($t, JSON_UNESCAPED_UNICODE), LOCK_EX); }
        elseif ($action === 'reject') { $t['status'] = 'rejected'; file_put_contents($file, json_encode($t, JSON_UNESCAPED_UNICODE), LOCK_EX); }
        elseif ($action === 'delete') { unlink($file); }
    }
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

// ========== 列表 ==========
$items = ['pending' => [], 'done' => []];
foreach (glob($treeholeDir . '*.json') as $f) {
    $t = json_decode(file_get_contents($f), true);
    if (!is_array($t)) continue;
    $t['id'] = basename($f, '.json');
    $items[($t['status'] === 'pending') ? 'pending' : 'done'][] = $t;
}
usort($items['pending'], fn($a, $b) => $a['ts'] <=> $b['ts']);
usort($items['done'], fn($a, $b) => $b['ts'] <=> $a['ts']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = '树洞审核 · Admin';
$fonts = 'basic';
require __DIR__ . '/../includes/head.php';
?>
<style>
body { font-family: var(--font-body); background-color: var(--bg-deep); color: var(--text-primary); line-height: 1.7; padding: 40px 28px; }
.wrap { max-width: 720px; margin: 0 auto; }
h1 { font-family: var(--font-display); font-size: 1.3rem; margin-bottom: 20px; color: var(--accent); }
.block { margin-bottom: 28px; }
.block h2 { font-size: 0.85rem; color: var(--text-muted); letter-spacing: 0.06em; margin-bottom: 12px; }
.item { background: rgba(8,28,48,0.72); border: 1px solid var(--border-glow); border-radius: 14px; padding: 16px; margin-bottom: 12px; }
.item .content { margin-bottom: 8px; }
.item .meta { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px; }
.btns { display: flex; gap: 8px; }
.btns form { display: inline; }
.btns button { font-size: 0.72rem; padding: 4px 14px; border-radius: 50px; border: 1px solid var(--border-glow); background: transparent; color: var(--text-secondary); cursor: pointer; transition: 0.2s; }
.btns .ok { color: var(--accent); border-color: rgba(142,208,232,0.35); }
.btns .ok:hover { background: rgba(142,208,232,0.1); }
.btns .rm { color: var(--secondary); border-color: rgba(240,128,96,0.3); }
.btns .rm:hover { background: rgba(240,128,96,0.08); }
.empty { color: var(--text-muted); font-size: 0.85rem; }
a { color: var(--accent); font-size: 0.8rem; }
</style>
</head>
<body>
<div class="wrap">
  <h1>树洞审核 / Tree Hole Review</h1>

  <div class="block">
    <h2>待审核 (<?= count($items['pending']) ?>)</h2>
    <?php if (!$items['pending']): ?><p class="empty">无待审核 — 树洞安静。</p><?php endif; ?>
    <?php foreach ($items['pending'] as $t): ?>
    <div class="item">
      <div class="content"><?= htmlspecialchars($t['content']) ?></div>
      <div class="meta"><?= htmlspecialchars($t['nick'] ?: '匿名') ?> · <?= date('Y-m-d H:i', $t['ts']) ?></div>
      <div class="btns">
        <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="ok" name="action" value="approve">通过</button></form>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button name="action" value="reject">拒绝</button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="block">
    <h2>已处理 (<?= count($items['done']) ?>)</h2>
    <?php if (!$items['done']): ?><p class="empty">—</p><?php endif; ?>
    <?php foreach ($items['done'] as $t): ?>
    <div class="item">
      <div class="content"><?= htmlspecialchars($t['content']) ?></div>
      <div class="meta"><?= htmlspecialchars($t['nick'] ?: '匿名') ?> · <?= date('Y-m-d H:i', $t['ts']) ?> · <?= $t['status'] ?></div>
      <div class="btns">
        <form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="rm" name="action" value="delete">删除</button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <a href="/blog/admin/editor.php">← 返回 Editor</a>
</div>
</body>
</html>
