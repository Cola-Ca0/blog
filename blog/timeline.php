<?php
/**
 * 时间线 — 文章发布时间轴 (2026-08-27 用户拍板, 参照 lin-xin 时间线)
 * 数据: glob posts/*.md + getPublishedPosts (markdown.php, 宪法 4.4 零缓存)
 */
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/markdown.php';

$posts = getPublishedPosts(__DIR__ . '/posts/');

// 按年份分组 (posts 已按日期倒序)
$timeline = [];
foreach ($posts as $p) {
    $year = strtotime($p['date']) ? date('Y', strtotime($p['date'])) : '未知';
    $timeline[$year][] = $p;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = '时间线 · Cola_CaO';
$pageDesc = '可乐的深潜足迹 — 时间线视角的记录日志。';
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

/* 页头 */
.timeline-hero { position: relative; z-index: 2; text-align: center; padding: 110px 28px 20px; }
.timeline-hero h1 {
  font-family: var(--font-display); font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 700;
  letter-spacing: 0.06em;
  background: linear-gradient(135deg, #d8eaf8 0%, var(--accent) 50%, var(--primary) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.timeline-hero p { color: var(--text-secondary); margin-top: 10px; }

/* 时间线主体 */
.timeline-wrap { position: relative; z-index: 2; max-width: 820px; margin: 0 auto; padding: 40px 28px 80px; }
.tl-year {
  font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; letter-spacing: 0.12em;
  color: var(--accent); margin: 36px 0 18px; display: flex; align-items: center; gap: 10px;
}
.tl-year::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, var(--border-glow), transparent); }

.tl-item { position: relative; padding: 0 0 22px 28px; }
.tl-item::before { /* 节点 */
  content: ''; position: absolute; left: 0; top: 7px; width: 9px; height: 9px; border-radius: 50%;
  background: var(--accent); box-shadow: 0 0 8px rgba(142,208,232,0.55);
}
.tl-item::after { /* 纵线 */
  content: ''; position: absolute; left: 4px; top: 24px; bottom: -2px; width: 1px;
  background: rgba(91,160,224,0.18);
}
.tl-item:last-child::after { display: none; }
.tl-date { font-size: 0.72rem; color: var(--text-muted); letter-spacing: 0.06em; font-family: var(--font-display); }
.tl-title-link { text-decoration: none; }
.tl-title { font-family: var(--font-display); font-weight: 700; font-size: 1.02rem; color: var(--text-primary); margin: 2px 0 4px; transition: color var(--transition-smooth); }
.tl-item:hover .tl-title { color: var(--accent); }
.tl-summary { font-size: 0.82rem; color: var(--text-secondary); line-height: 1.7; }
.tl-tag { display: inline-block; margin-left: 8px; font-size: 0.65rem; color: var(--primary);
  background: rgba(91,160,224,0.08); border: 1px solid rgba(91,160,224,0.18); border-radius: 50px; padding: 1px 8px; }
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php $navActive = 'timeline'; require __DIR__ . '/includes/navbar.php'; ?>

<div class="timeline-hero">
  <h1>DIVE LOG / 潜航时间线</h1>
  <p>一路下潜的记录 — 每一篇都是一次抵达。</p>
</div>

<div class="timeline-wrap">
  <?php foreach ($timeline as $year => $yearPosts): ?>
    <div class="tl-year"><?= $year ?></div>
    <?php foreach ($yearPosts as $p): ?>
      <div class="tl-item section-reveal">
        <div class="tl-date"><?= htmlspecialchars($p['date']) ?></div>
        <a class="tl-title-link" href="<?= $BASE ?>/post/<?= htmlspecialchars($p['slug']) ?>">
          <div class="tl-title"><?= htmlspecialchars($p['title']) ?>
            <span class="tl-tag"><?= htmlspecialchars($p['category']) ?></span>
          </div>
        </a>
        <div class="tl-summary"><?= htmlspecialchars($p['summary']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <?php if (!$posts): ?>
    <p style="color:var(--text-muted);text-align:center;padding:60px">暂无信号 — 等待第一次下潜。</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
