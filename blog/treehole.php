<?php
/**
 * 树洞 Tree Hole — 匿名倾诉板 (2026-08-27 用户拍板, 参照 lin-xin 树洞)
 * 安全宪法 3.x: CSRF 校验 / 只认 POST / 长度限制 / 同会话 60s 冷却 / 审核后公开
 * 数据: data/treehole/{id}.json (宪法 4.2 数据即文件)
 */
require __DIR__ . '/includes/auth.php';

// CSRF token (会话级)
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrfToken = $_SESSION['csrf_token'];

$treeholeDir = __DIR__ . '/data/treehole/';
if (!is_dir($treeholeDir)) mkdir($treeholeDir, 0755, true);

$error = '';
$success = '';

// ========== 提交 (只认 POST) ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = '会话已失效，请刷新页面重试。';
    } else {
        $nick = mb_substr(trim($_POST['nick'] ?? ''), 0, 16);
        $content = trim($_POST['content'] ?? '');
        if ($content === '' || mb_strlen($content) > 200) {
            $error = '请写点什么（1-200 字）。';
        } elseif (isset($_SESSION['treehole_last']) && time() - $_SESSION['treehole_last'] < 60) {
            $error = '请给树洞一点安静的时间（60 秒冷却）。';
        } else {
            $id = bin2hex(random_bytes(8));
            file_put_contents(
                $treeholeDir . $id . '.json',
                json_encode(['content' => $content, 'nick' => $nick, 'ts' => time(), 'status' => 'pending'], JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
            $_SESSION['treehole_last'] = time();
            $success = '已投入树洞 — 审核通过后就会出现在这里。';
        }
    }
}

// ========== 读取已公开 ==========
$treeholes = [];
foreach (glob($treeholeDir . '*.json') as $f) {
    $t = json_decode(file_get_contents($f), true);
    if (is_array($t) && ($t['status'] ?? '') === 'approved') $treeholes[] = $t;
}
usort($treeholes, function ($a, $b) { return $b['ts'] <=> $a['ts']; });
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = '树洞 · Cola_CaO';
$pageDesc = '树洞 — 一位匿名倾诉者的漂流瓶。';
require __DIR__ . '/includes/head.php';
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
.treehole-wrap { position: relative; z-index: 2; max-width: 760px; margin: 0 auto; padding: 110px 28px 60px; }
.treehole-head { text-align: center; margin-bottom: 8px; }
.treehole-head h1 {
  font-family: var(--font-display); font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 700; letter-spacing: 0.06em;
  background: linear-gradient(135deg, #d8eaf8 0%, var(--accent) 50%, var(--primary) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.treehole-sub { text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 34px; }

/* 投递表单 — pill 输入的立体感沿用输入框 14px 圆角 */
.hole-form {
  background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 40px;
  box-shadow: var(--shadow-sm);
}
.hole-form textarea, .hole-form input {
  width: 100%; background: rgba(0,0,0,0.2); color: var(--text-primary);
  border: 1px solid var(--border-glow); border-radius: var(--radius-md); padding: 10px 14px;
  font-size: 0.85rem; font-family: var(--font-body); outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.hole-form textarea { min-height: 90px; resize: vertical; }
.hole-form textarea:focus, .hole-form input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(91,160,224,0.08); }
.hole-form-row { display: flex; gap: 12px; margin-top: 12px; align-items: center; }
.hole-form-row input { flex: 1; }
.hole-submit {
  font-family: var(--font-display); font-weight: 700; letter-spacing: 0.05em; font-size: 0.8rem;
  background: linear-gradient(135deg, var(--primary), var(--accent)); color: var(--text-primary);
  border: none; border-radius: var(--radius-pill); padding: 9px 22px; cursor: pointer;
  box-shadow: 0 0 16px rgba(91,160,224,0.3); transition: var(--transition-smooth);
}
.hole-submit:hover { transform: translateY(-2px); box-shadow: 0 0 28px rgba(91,160,224,0.5); }
.hole-msg { text-align: center; font-size: 0.8rem; margin-bottom: 16px; }
.hole-msg.error { color: var(--secondary); }
.hole-msg.ok { color: var(--accent); }

/* 洞列表 — 漂流瓶便签 */
.hole-item {
  background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg); padding: 18px 22px;
  margin-bottom: 16px; box-shadow: var(--shadow-sm); position: relative;
}
.hole-item:hover { border-color: var(--border-glow-strong); box-shadow: var(--shadow-md); }
.hole-text { font-size: 0.9rem; color: var(--text-primary); line-height: 1.75; }
.hole-meta { display: flex; gap: 10px; align-items: center; margin-top: 10px; font-size: 0.68rem; color: var(--text-haze); }
.hole-meta .hole-nick { color: var(--accent); }
.empty-hole { text-align: center; color: var(--text-muted); padding: 40px 0; font-size: 0.85rem; }
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php $navActive = 'treehole'; require __DIR__ . '/includes/navbar.php'; ?>

<div class="treehole-wrap">
  <div class="treehole-head">
    <h1>TREE HOLE / 树洞</h1>
  </div>
  <p class="treehole-sub">有些话只想说给海听 — 投进树洞，你会被温柔地接住。</p>

  <?php if ($error): ?><div class="hole-msg error"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?><div class="hole-msg ok"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form class="hole-form" method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <textarea name="content" maxlength="200" placeholder="写下你想说的（1-200 字）…" required></textarea>
    <div class="hole-form-row">
      <input type="text" name="nick" maxlength="16" placeholder="匿名署名（可留空）">
      <button type="submit" class="hole-submit">投进树洞</button>
    </div>
  </form>

  <?php if ($treeholes): ?>
    <?php foreach (array_slice($treeholes, 0, 50) as $t): ?>
      <div class="hole-item section-reveal">
        <div class="hole-text"><?= htmlspecialchars($t['content']) ?></div>
        <div class="hole-meta">
          <span class="hole-nick"><?= htmlspecialchars($t['nick'] ?: '匿名') ?></span>
          <span><?= date('m-d H:i', $t['ts']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="empty-hole">树洞空空的 — 等第一位倾诉者。</div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
