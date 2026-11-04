<?php
/**
 * 404 — 主题化错误页 (2026-11-04)
 * .htaccess 末尾把「不存在的路径」内部重写到这里, 仍返回 404 状态码。
 * 与站内其他页共用 head / preloader / background / navbar / footer, 所以四个场景各自成套。
 */
http_response_code(404);
require __DIR__ . '/includes/auth.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = '404 · 信号丢失 · Cola_CaO';
$pageDesc = '这个地址不存在。';
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

.err-wrap {
  position: relative; z-index: 2;
  min-height: 100vh; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center; padding: 110px 24px 90px;
}
.err-code {
  font-family: var(--font-display);
  font-size: clamp(4rem, 16vw, 9rem); font-weight: 700;
  letter-spacing: 0.08em; line-height: 1;
  background: linear-gradient(135deg, #d8eaf8 0%, var(--accent) 55%, var(--primary) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.err-title {
  font-family: var(--font-display);
  font-size: clamp(1rem, 2.4vw, 1.35rem);
  letter-spacing: 0.14em; text-transform: uppercase;
  color: var(--text-primary); margin-top: 20px;
}
/* 隐藏场景专属的一行: 其他三场景不显示 */
.err-lain {
  display: none;
  margin-top: 12px;
  font-family: var(--font-display);
  font-size: 0.72rem; letter-spacing: 0.3em;
  color: var(--accent);
}
html[data-scene="lain"] .err-lain { display: block; }
html[data-scene="lain"] .err-title {
  color: var(--accent);
  text-shadow: 0 0 18px rgba(76, 237, 151, 0.45);
}
html[data-scene="lain"] .err-code {
  background: none; -webkit-text-fill-color: currentColor;
  color: var(--accent);
  text-shadow: 0 0 1px rgba(220, 247, 233, 0.8), 0 0 18px rgba(76, 237, 151, 0.5), 0 0 60px rgba(76, 237, 151, 0.2);
}
.err-sub {
  color: var(--text-secondary); margin-top: 14px;
  font-size: 0.9rem; max-width: 34em;
}
.err-back {
  display: inline-block; margin-top: 36px;
  font-family: var(--font-display); font-size: 0.78rem; letter-spacing: 0.12em;
  color: var(--accent); text-decoration: none;
  border: 1px solid var(--border-glow); border-radius: var(--radius-pill);
  padding: 9px 22px; transition: var(--transition-smooth);
}
.err-back:hover { border-color: var(--accent); box-shadow: 0 0 18px rgba(76, 237, 151, 0.2); }
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php $navActive = ''; require __DIR__ . '/includes/navbar.php'; ?>

<main class="err-wrap">
  <div class="err-code">404</div>
  <h1 class="err-title">Signal Lost / 信号丢失</h1>
  <p class="err-lain">NO CARRIER · 0x0194</p>
  <p class="err-sub">这个地址不存在，或者它从来没有存在过。检查一下链接，或者回到有光的地方。</p>
  <a class="err-back" href="<?= $BASE ?>/">← 返回首页 / Return</a>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
