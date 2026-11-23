<?php
/**
 * 队内战况归档 — TPCTF 2026 作战看板只读快照
 * 2026-11-23 用户需求: 把 board.php 的对话归档进博客, 首页 PROJECTS 卡片进入,
 * 密码门与看板相同 (session 记住), 只读。
 *
 * 数据: data/board-archive.json (由 board.php?api=1&since=0 全量导出, .htaccess 已禁 .json 直取)
 * 安全宪法 3.1/3.5: 只认 POST、CSRF、密码用 bcrypt 校验、失败延迟。
 */
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/config.php';

const BOARD_ARCHIVE_HASH = '$2y$10$XedKScMaKTEYWOpeywZTcudCeo/Odus/Ok3JzPQi89/AQvT1Eqal6';
const BOARD_ARCHIVE_DATA = __DIR__ . '/data/board-archive.json';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$gateError = '';
$authorized = !empty($_SESSION['board_ok']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
        $gateError = '会话已失效，请刷新页面重试。';
    } elseif (password_verify((string) $_POST['password'], BOARD_ARCHIVE_HASH)) {
        $_SESSION['board_ok'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: ' . $BASE . '/board.php', true, 303);
        exit;
    } else {
        usleep(400000);
        $gateError = '密码不对。';
    }
}

$items = [];
$total = 0;
$lastTs = 0;
if ($authorized) {
    $raw = @file_get_contents(BOARD_ARCHIVE_DATA);
    $json = $raw !== false ? json_decode($raw, true) : null;
    if (is_array($json) && isset($json['items']) && is_array($json['items'])) {
        $items = $json['items'];
        usort($items, fn($a, $b) => ($a['id'] ?? 0) <=> ($b['id'] ?? 0));
        $total = count($items);
        $lastTs = (int) ($items[$total - 1]['t'] ?? 0);
    }
}

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$authorClass = function (string $from): string {
    $f = strtolower($from);
    if (str_contains($f, 'claude')) return 'who-claude';
    if (str_contains($f, 'dorian')) return 'who-dorian';
    if (str_contains($f, 'x_x')) return 'who-xx';
    return 'who-cola';
};

$kindLabel = ['flag' => 'FLAG', 'note' => 'NOTE', 'ask' => 'ASK', 'status' => 'STATUS'];

$fmtDay = fn(int $ts): string => date('Y-m-d', $ts);
$fmtTime = fn(int $ts): string => date('H:i', $ts);

// 渲染前脱敏: 归档里出现过 WireGuard 私钥与看板口令, 不该再原样落到页面上
$redact = function (string $t): string {
    $t = preg_replace('/PrivateKey\s*=\s*[A-Za-z0-9+\/=]{16,}/', 'PrivateKey = [已脱敏]', $t);
    $t = preg_replace('/\bpf_[A-Za-z0-9_\-]{16,}/', 'pf_[已脱敏]', $t);
    return str_replace('L@1n123456', '[口令已脱敏]', $t);
};

$pageTitle = 'TPCTF 2026 作战归档 · Cola_CaO';
?>
<!DOCTYPE html>
<html lang="zh-CN" data-scene="graphite" data-g="dark">
<head>
<?php
$pageDesc = 'TPCTF 2026 人机协同赛道的队内作战记录归档';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
require __DIR__ . '/includes/head.php';
?>
<style>
/* 队内战况归档 — 本页专属样式, 颜色全部取自 graphite token */
body.g-archive { background: var(--g-bg); color: var(--g-ink); font-family: var(--g-font-body); }
.a-wrap { max-width: var(--g-measure); margin: 0 auto; padding: 48px 20px 96px; }
.a-top { display:flex; align-items:baseline; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:8px; }
.a-top h1 { font-family: var(--g-font-display); font-size: 1.6rem; margin: 0; letter-spacing: .02em; }
.a-back { font-size: .82rem; color: var(--g-accent); text-decoration: none; }
.a-back:hover { text-decoration: underline; }
.a-tools { display: flex; align-items: center; gap: 14px; }
.a-gate-mode { display: flex; justify-content: flex-end; margin: -6px 0 14px; }
.a-sub { color: var(--g-meta); font-size: .82rem; margin: 0 0 28px; }
.a-rule { height:1px; background: var(--g-rule-soft); margin: 28px 0; }

/* 密码门 */
.a-gate { max-width: 360px; margin: 12vh auto 0; border:1px solid var(--g-rule); background: var(--g-raised); padding: 28px; border-radius: 14px; }
.a-gate h1 { font-family: var(--g-font-display); font-size: 1.15rem; margin: 0 0 6px; }
.a-gate p { color: var(--g-meta); font-size: .82rem; margin: 0 0 18px; }
.a-gate input { width:100%; box-sizing:border-box; font: inherit; padding: 10px 12px; border-radius: 8px; border:1px solid var(--g-rule); background: var(--g-code-bg); color: var(--g-ink); }
.a-gate input:focus { outline:none; border-color: var(--g-control-rule); }
.a-gate button { margin-top: 12px; width:100%; font: inherit; font-weight:600; padding: 10px; border:0; border-radius: 8px; background: var(--g-accent); color: var(--g-bg); cursor:pointer; }
.a-gate button:hover { filter: brightness(1.08); }
.a-err { color: #d98c7a; font-size: .82rem; margin: 10px 0 0; }

/* 时间线 */
.a-day { position: sticky; top: 0; z-index: 2; background: var(--g-bg); padding: 10px 0 8px; margin-top: 8px;
         font-family: var(--g-font-mono); font-size: .74rem; letter-spacing: .12em; color: var(--g-meta); border-bottom:1px solid var(--g-rule-soft); }
.a-item { display:grid; grid-template-columns: 56px 1fr; gap: 14px; padding: 14px 0 14px 0; border-bottom: 1px solid var(--g-rule-soft); }
.a-item:last-child { border-bottom: 0; }
.a-time { font-family: var(--g-font-mono); font-size: .74rem; color: var(--g-meta); padding-top: 3px; }
.a-head { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom: 6px; }
.a-who { font-family: var(--g-font-mono); font-size: .76rem; font-weight: 600; }
.who-claude { color: var(--g-accent); }
.who-dorian { color: var(--g-tk-key); }
.who-xx { color: var(--g-meta); }
.who-cola { color: var(--g-ink); opacity: .75; }
.a-kind { font-family: var(--g-font-mono); font-size: .64rem; letter-spacing: .1em; padding: 1px 6px; border-radius: 4px; border:1px solid var(--g-rule); color: var(--g-meta); }
.a-kind.k-flag { border-color: var(--g-accent-dim); color: var(--g-accent); }
.a-kind.k-ask { border-color: var(--g-meta); color: var(--g-meta); }
.a-text { white-space: pre-wrap; word-break: break-word; font-size: .92rem; line-height: 1.75; margin: 0; }
.a-text.clip { max-height: 9.5em; overflow: hidden; -webkit-mask-image: linear-gradient(#000 60%, transparent); mask-image: linear-gradient(#000 60%, transparent); }
.a-more { margin-top: 6px; font-size: .78rem; color: var(--g-accent); background:none; border:0; cursor:pointer; padding:0; font-family: var(--g-font-body); }
.a-foot { margin-top: 40px; color: var(--g-meta); font-size: .78rem; }
@media (max-width: 560px) {
  .a-item { grid-template-columns: 44px 1fr; gap: 10px; }
  .a-wrap { padding: 32px 16px 72px; }
}
</style>
</head>
<body class="g-page g-archive">
<div class="a-wrap">
<?php if (!$authorized): ?>
  <form class="a-gate" method="post" action="<?= $h($BASE) ?>/board.php">
    <div class="a-gate-mode">
      <div class="g-mode" role="group" aria-label="Color mode">
        <button type="button" data-g-mode="dark" aria-pressed="true">DARK</button>
        <button type="button" data-g-mode="light" aria-pressed="false">LIGHT</button>
      </div>
    </div>
    <h1>队内战况归档</h1>
    <p>TPCTF 2026 · 输入密码查看</p>
    <input type="password" name="password" autofocus autocomplete="current-password" placeholder="密码" required>
    <input type="hidden" name="csrf_token" value="<?= $h($_SESSION['csrf_token']) ?>">
    <button type="submit">进入</button>
    <?php if ($gateError !== ''): ?><p class="a-err"><?= $h($gateError) ?></p><?php endif; ?>
  </form>
<?php else: ?>
  <div class="a-top">
    <h1>TPCTF 2026 作战归档</h1>
    <div class="a-tools">
      <div class="g-mode" role="group" aria-label="Color mode">
        <button type="button" data-g-mode="dark" aria-pressed="true">DARK</button>
        <button type="button" data-g-mode="light" aria-pressed="false">LIGHT</button>
      </div>
      <a class="a-back" href="<?= $h($BASE) ?>/">← 返回博客</a>
    </div>
  </div>
  <p class="a-sub">L@1n 队内看板 · 共 <?= (int) $total ?> 条记录<?= $lastTs ? ' · 最后一条 ' . $h(date('Y-m-d H:i', $lastTs)) : '' ?></p>
  <div class="a-rule"></div>

  <?php $curDay = ''; foreach ($items as $it):
      $ts = (int) ($it['t'] ?? 0);
      $day = $fmtDay($ts);
      if ($day !== $curDay): $curDay = $day; ?>
    <div class="a-day"><?= $h($day) ?></div>
  <?php endif;
      $from = (string) ($it['from'] ?? '');
      $kind = (string) ($it['kind'] ?? 'status');
      $text = $redact((string) ($it['text'] ?? ''));
      $long = mb_strlen($text) > 420;
  ?>
    <div class="a-item">
      <div class="a-time"><?= $h($fmtTime($ts)) ?></div>
      <div>
        <div class="a-head">
          <span class="a-who <?= $h($authorClass($from)) ?>"><?= $h($from) ?></span>
          <span class="a-kind k-<?= $h($kind) ?>"><?= $h($kindLabel[$kind] ?? strtoupper($kind)) ?></span>
        </div>
        <p class="a-text<?= $long ? ' clip' : '' ?>"><?= $h($text) ?></p>
        <?php if ($long): ?>
        <button type="button" class="a-more" onclick="var t=this.previousElementSibling;t.classList.toggle('clip');this.textContent=t.classList.contains('clip')?'展开全文':'收起';">展开全文</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <p class="a-foot">此页为只读快照，原始看板已于赛后冻结。密码与看板一致。</p>
<?php endif; ?>
</div>
</body>
</html>
