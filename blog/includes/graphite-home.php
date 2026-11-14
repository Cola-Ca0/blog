<?php
/**
 * Graphite home — 石墨主主题首页 (2026-11-08)
 * 由 index.php 在 </body> 前 require; 复用 $publishedAll / $projectsData / $aboutJson / $friendsData。
 * 只在 html[data-scene="graphite"] 下显示 (graphite.css)。
 */
$gCollection = isset($_GET['collection']) ? (string) $_GET['collection'] : '';
$gCollMetaAll = json_decode((string) @file_get_contents(__DIR__ . '/../collections.json'), true) ?: [];
$gCollMetaById = [];
foreach ($gCollMetaAll as $gc) if (!empty($gc['id'])) $gCollMetaById[$gc['id']] = $gc;

$gSingles = array_values(array_filter($publishedAll, fn($p) => ($p['collection'] ?? '') === ''));
$gCollPosts = array_values(array_filter($publishedAll, fn($p) => ($p['collection'] ?? '') !== ''));

if ($gCollection !== '') {
    $gList = array_values(array_filter($publishedAll, fn($p) => ($p['collection'] ?? '') === $gCollection));
    $gListTitle = $gCollMetaById[$gCollection]['title'] ?? $gCollection;
    $gFeatured = null;
    $gRest = $gList;
    $gNoBase = 0;
    $gNoTotal = count($gList);
} else {
    $gList = $gSingles;
    $gListTitle = 'EARLIER WORKS';
    $gFeatured = $gSingles[0] ?? null;
    $gRest = array_slice($gSingles, 1, 10);
    $gNoBase = 1;
    $gNoTotal = count($gSingles);
}

$gColl = $gCollMetaAll[0] ?? null;
$gCollCount = 0;
$gCollLast = '';
if ($gColl !== null) {
    foreach ($gCollPosts as $cp) {
        if (($cp['collection'] ?? '') !== ($gColl['id'] ?? '')) continue;
        $gCollCount++;
        if ($cp['date'] > $gCollLast) $gCollLast = $cp['date'];
    }
}

$gProjects = $projectsData;
usort($gProjects, fn($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
$gProjectDrawings = [
    'misc-dashouzi' => 'misc-dashouzi.svg',
    'cola-hub' => 'cola-hub.svg',
    'cyber-toolkit' => 'cyber-toolkit.svg',
    'lighthouse' => 'lighthouse.svg',
];

$gD = fn(string $s): string => str_replace('-', '.', substr($s, 0, 10));
$gE = fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$gBioLines = preg_split('/\R/u', trim((string) ($aboutJson['bio'] ?? '')));
$gBioLines = array_values(array_filter(array_map('trim', $gBioLines), fn($line) => $line !== ''));
$gBioHtml = [];
foreach ($gBioLines as $gBioLine) {
    $gBioText = $gE($gBioLine);
    $gBioHtml[] = str_contains($gBioLine, 'SUSCTF2026') ? '<strong>' . $gBioText . '</strong>' : $gBioText;
}
$gBioHtml = implode('<br>', $gBioHtml);
$gLine = $heroLine ?? ['text' => '“Lay down your pieces. And let\'s begin OBJECT CREATION”', 'from' => 'world.execute(me)'];
$gNo = function (int $i, int $total): string {
    return sprintf('№ %03d', max(0, $total - $i));
};
?>
<div class="g-home">
  <?php $gNavContext = 'home'; require __DIR__ . '/graphite-nav.php'; ?>
  <main class="g-main">
    <section class="g-masthead" id="g-top">
      <div class="g-mast-copy">
        <h1>Cola's security blog</h1>
        <p class="g-lede"><em><?= $gE($gLine['text']) ?></em></p>
        <p class="g-attr">— <?= $gE($gLine['from']) ?></p>
        <p class="g-links">
          <a class="mail" href="mailto:cola_ca0@qq.com">cola_ca0@qq.com</a>
          <a href="https://github.com/Cola-Ca0">GitHub</a>
          <a href="https://space.bilibili.com/629007860">Bilibili</a>
        </p>
      </div>
      <?php if ($gFeatured !== null): ?>
      <a class="g-spotlight" href="<?= $gE($BASE . '/post/' . $gFeatured['slug']) ?>">
        <p class="label">LATEST WORK</p>
        <h2><?= $gE($gFeatured['title']) ?></h2>
        <p><?= $gE($gFeatured['summary']) ?></p>
        <p class="g-spot-foot">
          <span class="g-meta"><?= $gE($gD((string) $gFeatured['date'])) ?> · <?= $gE($gFeatured['category'] !== '' ? $gFeatured['category'] : '笔记') ?></span>
          <span class="g-more">READ →</span>
        </p>
      </a>
      <?php endif; ?>
    </section>

    <section class="g-sec" id="g-notes" aria-labelledby="g-notes-h">
      <div class="g-sec-head">
        <h2 id="g-notes-h"><?= $gE($gCollection !== '' ? $gListTitle : 'EARLIER WORKS') ?></h2><span class="g-rule"></span>
        <span class="g-count"><?= count($gRest) ?> POSTS</span>
      </div>
      <?php if ($gCollection !== ''): ?>
      <p style="margin:10px 0 0"><a class="g-art-back" href="<?= $BASE ?>/">← ALL WORKS</a></p>
      <?php endif; ?>
      <div class="g-ledger">
        <?php foreach ($gRest as $i => $p): ?>
        <a class="g-row" href="<?= $gE($BASE . '/post/' . $p['slug']) ?>">
          <span class="g-no"><?= $gE($gNo($i + $gNoBase, $gNoTotal)) ?></span>
          <time datetime="<?= $gE(substr((string) $p['date'], 0, 10)) ?>"><?= $gE($gD((string) $p['date'])) ?></time>
          <h3><?= $gE($p['title']) ?></h3>
          <span class="g-cat"><?= $gE($p['category'] !== '' ? $p['category'] : '笔记') ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <?php if ($gColl !== null && $gCollection === ''): ?>
    <section class="g-sec" id="g-topics" aria-labelledby="g-topics-h">
      <div class="g-sec-head">
        <h2 id="g-topics-h">COLLECTIONS</h2><span class="g-rule"></span>
        <span class="g-count"><?= $gCollCount ?> POSTS</span>
      </div>
      <a class="g-topic" href="<?= $BASE ?>/?collection=<?= urlencode((string) $gColl['id']) ?>">
        <div>
          <h3><?= $gE($gColl['title'] ?? $gColl['id']) ?></h3>
          <p><?= $gE($gColl['description'] ?? '') ?></p>
        </div>
        <span class="g-meta"><?= $gE($gCollLast !== '' ? $gD($gCollLast) : '') ?><br><?= $gCollCount ?> POSTS</span>
      </a>
    </section>
    <?php endif; ?>

    <?php if (count($gHonors) > 0): ?>
    <section class="g-sec" id="g-honors" aria-labelledby="g-honors-h">
      <div class="g-sec-head">
        <h2 id="g-honors-h">HONORS</h2><span class="g-rule"></span>
      </div>
      <ul class="g-ledger g-honors">
        <?php foreach ($gHonors as $honor): ?>
        <li class="g-honor-row">
          <time datetime="<?= $gE($honor['date']) ?>"><?= $gE($gD($honor['date'])) ?></time>
          <div class="g-honor-body">
            <h3><?= $gE($honor['title']) ?></h3>
            <?php if (($honor['note'] ?? '') !== ''): ?>
            <p class="g-honor-note"><?= $gE($honor['note']) ?></p>
            <?php endif; ?>
          </div>
          <span class="g-honor-rank"><?= $gE($honor['rank']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <section class="g-sec" id="g-works" aria-labelledby="g-works-h">
      <div class="g-sec-head">
        <h2 id="g-works-h">PROJECTS</h2><span class="g-rule"></span>
        <span class="g-count"><?= count($gProjects) ?> PROJECTS</span>
      </div>
      <div>
        <?php foreach ($gProjects as $pj): ?>
        <?php
          $gUrl = (string) ($pj['url'] ?? '');
          $gTag = $gUrl !== '' ? 'a' : 'div';
          $gDrawing = $gProjectDrawings[$pj['id'] ?? ''] ?? null;
        ?>
        <<?= $gTag ?> class="g-work<?= $gDrawing !== null ? ' g-work-illustrated' : '' ?>"<?= $gTag === 'a' ? ' href="' . $gE($gUrl) . '" target="_blank" rel="noopener"' : '' ?>>
          <?php if ($gDrawing !== null): ?>
          <span class="g-work-drawing" aria-hidden="true">
            <img src="<?= $gE($BASE . '/assets/images/graphite/' . $gDrawing) ?>" width="128" height="92" alt="" loading="lazy">
          </span>
          <?php endif; ?>
          <div class="g-work-copy">
            <h3><?= $gE((string) $pj['title']) ?></h3>
            <p><?= $gE((string) $pj['description']) ?></p>
          </div>
          <span class="g-meta"><?= $gE((string) ($pj['date'] ?? '')) ?><br><?= $gE($gUrl !== '' ? 'OPEN SOURCE ↗' : (string) ($pj['note'] ?? 'LOCAL')) ?></span>
        </<?= $gTag ?>>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="g-sec" id="g-about" aria-labelledby="g-about-h">
      <div class="g-sec-head">
        <h2 id="g-about-h">ABOUT</h2><span class="g-rule"></span>
      </div>
      <div class="g-about-grid">
        <div>
          <p><?= $gBioHtml ?></p>
        </div>
        <div class="g-contact">
          <dl>
            <dt>EMAIL</dt><dd><a href="mailto:cola_ca0@qq.com">cola_ca0@qq.com</a></dd>
            <dt>GitHub</dt><dd><a href="https://github.com/Cola-Ca0" target="_blank" rel="noopener">Cola-Ca0 ↗</a></dd>
            <dt>Bilibili</dt><dd><a href="https://space.bilibili.com/629007860" target="_blank" rel="noopener">629007860 ↗</a></dd>
          </dl>
          <figure class="g-about-portrait">
            <img src="<?= $gE($BASE . '/assets/images/graphite/gura-sketch.webp') ?>" width="640" height="640" alt="Gawr Gura 戴着鲨鱼兜帽的二创素描" loading="lazy">
            <figcaption><a href="https://hololive.hololivepro.com/en/talents/gawr-gura/" target="_blank" rel="noopener">Gawr Gura</a></figcaption>
          </figure>
        </div>
      </div>
    </section>

    <?php if (count($friendsData) > 0): ?>
    <section class="g-sec" id="g-friends" aria-labelledby="g-friends-h">
      <div class="g-sec-head">
        <h2 id="g-friends-h">FRIENDS</h2><span class="g-rule"></span>
      </div>
      <ul class="g-friends">
        <?php foreach ($friendsData as $friend): ?>
        <?php $gFriendDetail = trim($friend['desc'] ?? '') !== '' ? $friend['desc'] : (string) parse_url($friend['url'], PHP_URL_HOST); ?>
        <li>
          <a class="g-friend" href="<?= $gE($friend['url']) ?>" target="_blank" rel="noopener noreferrer">
            <span class="g-friend-name"><?= $gE($friend['name']) ?></span>
            <span class="g-friend-detail"><?= $gE($gFriendDetail) ?></span>
            <span class="g-friend-arrow" aria-hidden="true">↗</span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <?php require __DIR__ . '/graphite-foot.php'; ?>
  </main>
</div>
