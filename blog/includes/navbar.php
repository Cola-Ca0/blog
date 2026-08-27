<?php
/**
 * Navbar Module — One nav, 3 pages, single source of truth.
 * Interface: set $navActive before require, e.g. $navActive = 'projects';
 * Variables consumed: $isLoggedIn, $username, $isAdmin (from auth.php)
 */
if (!isset($navActive)) $navActive = 'home';
?>
<a href="#blog-start" class="skip-link">Skip to content / 跳到内容</a>
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="<?= ($navActive === 'home') ? '#top' : '/blog/index.php' ?>" class="nav-brand">
      <div class="shark-fin-icon"></div>
      <span class="brand-text">Cola_CaO</span>
    </a>
    <ul class="nav-links">
      <li><a href="<?= ($navActive === 'home') ? '#top' : '/blog/index.php' ?>"          class="<?= $navActive === 'home'     ? 'active' : '' ?>">HOME</a></li>
      <li><a href="<?= ($navActive === 'home') ? '#blog-start' : '/blog/index.php#blog-start' ?>" class="<?= $navActive === 'blog'     ? 'active' : '' ?>">BLOG</a></li>
      <li><a href="/blog/projects/index.php"                                               class="<?= $navActive === 'projects' ? 'active' : '' ?>">PROJECTS</a></li>
      <li><a href="/blog/timeline.php"                                                     class="<?= $navActive === 'timeline' ? 'active' : '' ?>">TIMELINE</a></li>
      <li><a href="/blog/treehole.php"                                                     class="<?= $navActive === 'treehole' ? 'active' : '' ?>">TREEHOLE</a></li>
      <li><a href="/blog/about.php"                                                        class="<?= $navActive === 'about'    ? 'active' : '' ?>">ABOUT</a></li>
    </ul>
    <button class="nav-hamburger" onclick="toggleMobileNav()" aria-label="Menu" title="Menu">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-auth">
      <button class="theme-toggle" onclick="toggleTheme()" aria-label="切换场景 / Scene" title="切换场景 / Scene">
        <span class="theme-toggle-track">
          <span class="theme-toggle-thumb"></span>
        </span>
      </button>
      <div class="nav-search-wrap">
        <input type="text" id="searchInput" placeholder="Search posts... / 搜索文章..." autocomplete="off">
        <div class="search-results-dropdown" id="searchResults"></div>
      </div>
      <?php if ($isLoggedIn): ?>
        <?php if ($isAdmin): ?>
        <a href="/blog/admin/editor.php" class="nav-editor-mini" title="Editor / 编辑器" aria-label="Editor">&#9998;</a>
        <?php endif; ?>
        <div class="user-greeting">
          <div class="user-avatar-small">
            <?php if (file_exists(__DIR__ . '/../assets/images/my-avatar.jpg')): ?>
              <img src="/blog/assets/images/my-avatar.jpg" alt="avatar">
            <?php else: ?>
              <span style="font-size:0.9rem;">C</span>
            <?php endif; ?>
          </div>
          <span><?= $username ?></span>
          <?php if ($isAdmin): ?><span style="font-size:0.65rem;color:var(--secondary);">[ADMIN]</span><?php endif; ?>
        </div>
        <a href="/blog/login.php?action=logout" class="btn-logout">LOGOUT</a>
      <?php else: ?>
        <a href="/blog/login.php" class="btn-login">SIGN IN</a>
        <a href="/blog/login.php?tab=register" class="btn-register">SIGN UP</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Mobile Navigation Panel -->
<div class="mobile-nav-panel" id="mobileNavPanel">
  <a href="/blog/">HOME</a>
  <a href="/blog/#blog-start">BLOG</a>
  <a href="/blog/projects/">PROJECTS</a>
  <a href="/blog/timeline.php">TIMELINE</a>
  <a href="/blog/treehole.php">TREEHOLE</a>
  <a href="/blog/about.php">ABOUT</a>
  <?php if ($isAdmin): ?>
  <a href="/blog/admin/editor.php">EDITOR</a>
  <?php endif; ?>
</div>

<!-- Mobile Nav Toggle -->
<script>
window.toggleMobileNav = function() {
  var btn = document.querySelector('.nav-hamburger');
  var panel = document.getElementById('mobileNavPanel');
  if (!btn || !panel) return;
  btn.classList.toggle('open');
  panel.classList.toggle('open');
};
</script>

<!-- Inline Search -->
<script>
(function() {
  var input = document.getElementById('searchInput');
  var results = document.getElementById('searchResults');
  if (!input || !results) return;
  var timer = null;
  var activeIndex = -1;

  // 2026-08 审计 §1.3: 搜索结果渲染层统一转义, 防反射型 DOM XSS
  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function performSearch(q) {
    activeIndex = -1;
    results.innerHTML = '<p class="search-empty">Searching... / 搜索中...</p>';
    results.classList.add('has-results');
    fetch('/blog/posts-api.php?action=search&q=' + encodeURIComponent(q))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.results || !data.results.length) {
          results.innerHTML = '<p class="search-empty">No signals found / 未找到信号</p>';
          return;
        }
        results.innerHTML = data.results.map(function(p) {
          return '<a href="/blog/post/' + esc(p.slug) + '" class="sr-item">' +
            '<div class="sr-title">' + esc(p.title) + '</div>' +
            '<div class="sr-meta">' + esc(p.category) + ' · ' + esc(p.date) + '</div>' +
            '<div class="sr-summary">' + esc(p.summary) + '</div>' +
          '</a>';
        }).join('');
      })
      .catch(function() {
        results.innerHTML = '<p class="search-empty">Search failed / 搜索失败</p>';
      });
  }

  function updateActive() {
    var items = results.querySelectorAll('.sr-item');
    for (var i = 0; i < items.length; i++) {
      items[i].classList.toggle('sr-active', i === activeIndex);
    }
    if (activeIndex >= 0 && items[activeIndex]) {
      items[activeIndex].scrollIntoView({ block: 'nearest' });
    }
  }

  input.addEventListener('input', function() {
    clearTimeout(timer);
    activeIndex = -1;
    var q = input.value.trim();
    if (!q) { results.innerHTML = ''; results.classList.remove('has-results'); return; }
    timer = setTimeout(function() { performSearch(q); }, 250);
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-search-wrap')) {
      results.innerHTML = '';
      results.classList.remove('has-results');
      activeIndex = -1;
    }
  });

  document.addEventListener('keydown', function(e) {
    var items;
    if (e.key === 'Escape') {
      input.value = '';
      results.innerHTML = '';
      results.classList.remove('has-results');
      activeIndex = -1;
      input.blur();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      input.focus();
    }
    if (input !== document.activeElement) return;
    items = results.querySelectorAll('.sr-item');
    if (!items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, items.length - 1); updateActive(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, 0); updateActive(); }
    else if (e.key === 'Enter' && activeIndex >= 0) { e.preventDefault(); window.location.href = items[activeIndex].getAttribute('href'); }
  });
})();
</script>
