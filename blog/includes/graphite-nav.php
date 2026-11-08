<?php
/**
 * Graphite nav — 石墨主题顶栏 (2026-11-08)
 * 约定: 先设置 $gNavContext = 'home' | 'post'; $BASE / $isLoggedIn / $isAdmin 由页面提供。
 */
if (!isset($gNavContext)) $gNavContext = 'home';
$gHome = ($gNavContext === 'home' && !isset($_GET['collection'])) ? '' : $BASE . '/';
?>
<header class="g-bar">
  <div class="g-bar-in">
    <a class="g-mark" href="<?= $BASE ?>/" aria-label="Cola's security blog">
      <b>Cola_CaO</b><i>/</i><span>Cola's security blog</span>
    </a>
    <nav class="g-nav" aria-label="Main navigation">
      <a href="<?= $gHome ?>#g-top">WORKS</a>
      <a href="<?= $gHome ?>#g-topics">COLLECTIONS</a>
      <a href="<?= $gHome ?>#g-works">PROJECTS</a>
      <a href="<?= $gHome ?>#g-about">ABOUT</a>
    </nav>
    <div class="g-actions">
      <form class="g-search" role="search" onsubmit="return false;">
        <input id="gSearchInput" type="search" placeholder="Search posts…" autocomplete="off" aria-label="Search posts">
        <div class="g-search-results" id="gSearchResults"></div>
      </form>
      <div class="g-mode" role="group" aria-label="Color mode">
        <button type="button" data-g-mode="dark" aria-pressed="true">DARK</button>
        <button type="button" data-g-mode="light" aria-pressed="false">LIGHT</button>
      </div>
      <button type="button" class="g-old" onclick="setScene('deepsea')">OLD</button>
      <?php if (!empty($isLoggedIn)): ?>
        <?php if (!empty($isAdmin)): ?><a class="g-login" href="<?= $BASE ?>/admin/editor.php">EDIT</a><?php endif; ?>
        <a class="g-login" href="<?= $BASE ?>/admin/account.php">ACCOUNT</a>
      <?php else: ?>
        <a class="g-login" href="<?= $BASE ?>/login.php">SIGN IN</a>
      <?php endif; ?>
    </div>
  </div>
</header>
