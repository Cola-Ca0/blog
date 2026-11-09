<?php
/**
 * Graphite post — 石墨主主题文章页 (2026-11-08)
 * 由 post.php 在 </body> 前 require; 接收 $post / $slug / $BASE / $isAdmin。
 */
$gAll = getPublishedPosts(__DIR__ . '/../posts/');
$gSingles = array_values(array_filter($gAll, fn($p) => ($p['collection'] ?? '') === ''));
$gInList = ($post['collection'] ?? '') !== ''
    ? array_values(array_filter($gAll, fn($p) => ($p['collection'] ?? '') === $post['collection']))
    : $gSingles;

$gIndexAll = null;
foreach ($gAll as $i => $p) if ($p['slug'] === $slug) { $gIndexAll = $i; break; }
$gPrev = ($gIndexAll !== null && $gIndexAll > 0) ? $gAll[$gIndexAll - 1] : null;       // 更新的一篇
$gNext = ($gIndexAll !== null && isset($gAll[$gIndexAll + 1])) ? $gAll[$gIndexAll + 1] : null; // 更早的一篇

$gIndexNo = null;
foreach ($gInList as $i => $p) if ($p['slug'] === $slug) { $gIndexNo = $i; break; }
$gFolio = $gIndexNo !== null ? sprintf('№ %03d', count($gInList) - $gIndexNo) : '';

$gBody = (string) $post['body_html'];
/* 正文里若自带与标题重复的 H1, 去掉 (石墨页头已经有 H1) */
$gBody = preg_replace('/^\s*<h1[^>]*>.*?<\/h1>\s*/is', '', $gBody, 1);
/* 文章内部的 # 标题降为 H2: 页面已有唯一 H1, 且与石墨的章节样式统一 */
$gBody = preg_replace('/<h1(\s[^>]*)?>(.*?)<\/h1>/is', '<h2$1>$2</h2>', $gBody);

/* 给 H2/H3 加锚点, 同时收集目录 */
$gToc = [];
$gTocIndex = 0;
$gBody = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/is', function ($m) use (&$gToc, &$gTocIndex) {
    $gTocIndex++;
    $id = 'h-' . $gTocIndex;
    $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES, 'UTF-8'));
    if ($text !== '') $gToc[] = ['level' => (int) $m[1], 'id' => $id, 'text' => $text];
    return '<h' . $m[1] . ' id="' . $id . '">' . $m[3] . '</h' . $m[1] . '>';
}, $gBody);

/* 表格统一包一层横向滚动容器 */
$gBody = preg_replace('/<table(\s[^>]*)?>/i', '<div class="g-table-wrap"><table$1>', $gBody);
$gBody = str_replace('</table>', '</table></div>', $gBody);

$gPlain = trim(strip_tags($gBody));
$gChars = mb_strlen($gPlain);
$gReadMin = max(1, (int) ceil($gChars / 500));
$gD = fn(string $s): string => str_replace('-', '.', substr($s, 0, 10));
$gE = fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$gUpdated = !empty($post['updated']) ? (string) $post['updated'] : (string) $post['date'];
?>
<div class="g-post">
  <?php $gNavContext = 'post'; require __DIR__ . '/graphite-nav.php'; ?>
  <main class="g-main">
    <div class="g-art-layout<?= empty($gToc) ? ' no-toc' : '' ?>">
      <div class="g-art-main">
        <header class="g-art-head">
          <p class="g-art-meta">
            <span class="by">Cola_CaO</span>
            <time datetime="<?= $gE(substr((string) $post['date'], 0, 10)) ?>"><?= $gE($gD((string) $post['date'])) ?></time>
            <span><?= $gE($post['category'] !== '' ? $post['category'] : '笔记') ?></span>
            <span class="read">≈ <?= $gReadMin ?> MIN READ</span>
            <?php if ($isAdmin): ?>
            <a href="<?= $BASE ?>/admin/editor-article.php?slug=<?= $gE($slug) ?>">EDIT</a>
            <?php endif; ?>
            <?php if ($gFolio !== ''): ?><span class="folio"><?= $gE($gFolio) ?></span><?php endif; ?>
          </p>
          <h1><?= $gE($post['title']) ?></h1>
          <?php if ($post['summary'] !== ''): ?>
          <p class="g-art-summary"><?= $gE($post['summary']) ?></p>
          <?php endif; ?>
          <?php if (!empty($post['tags'])): ?>
          <ul class="g-art-tags">
            <?php foreach ($post['tags'] as $tag): ?><li><?= $gE($tag) ?></li><?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </header>

        <article class="g-article">
          <?= $gBody ?>
        </article>

        <footer class="g-art-foot">
          <p class="g-art-end" aria-hidden="true"></p>
          <p class="g-art-colophon">FULL TEXT · ABOUT <?= number_format($gChars) ?> CHARACTERS · <?= $gReadMin ?> MIN READ · UPDATED <?= $gE($gD($gUpdated)) ?></p>
          <?php if ($gPrev !== null || $gNext !== null): ?>
          <nav class="g-art-nav" aria-label="Previous / Next">
            <?php if ($gPrev !== null): ?>
            <a href="<?= $gE($BASE . '/post/' . $gPrev['slug']) ?>"><span>PREVIOUS</span><b><?= $gE($gPrev['title']) ?></b></a>
            <?php endif; ?>
            <?php if ($gNext !== null): ?>
            <a class="next" href="<?= $gE($BASE . '/post/' . $gNext['slug']) ?>"><span>NEXT</span><b><?= $gE($gNext['title']) ?></b></a>
            <?php endif; ?>
          </nav>
          <?php endif; ?>
          <a class="g-art-back" href="<?= $BASE ?>/#g-notes">← BACK TO WORKS</a>
        </footer>
      </div>

      <?php if (!empty($gToc)): ?>
      <aside class="g-toc" aria-label="Contents">
        <p class="g-toc-title">CONTENTS</p>
        <nav>
          <?php foreach ($gToc as $item): ?>
          <a<?= $item['level'] === 3 ? ' class="sub"' : '' ?> href="#<?= $gE($item['id']) ?>" title="<?= $gE($item['text']) ?>"><?= $gE($item['text']) ?></a>
          <?php endforeach; ?>
        </nav>
      </aside>
      <?php endif; ?>
    </div>

    <?php require __DIR__ . '/graphite-foot.php'; ?>
  </main>
</div>
