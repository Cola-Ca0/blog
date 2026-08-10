<?php
/**
 * Navbar Module — One nav, 3 pages, single source of truth.
 * Interface: set $navActive before require, e.g. $navActive = 'projects';
 * Variables consumed: $isLoggedIn, $username, $isAdmin (from auth.php)
 */
if (!isset($navActive)) $navActive = 'home';
?>
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
      <li><a href="/blog/about.php"                                                        class="<?= $navActive === 'about'    ? 'active' : '' ?>">ABOUT</a></li>
      <?php if ($isAdmin): ?>
      <li><a href="/blog/admin/editor.php" class="nav-editor-link">EDITOR</a></li>
      <?php endif; ?>
    </ul>
    <button class="nav-hamburger" onclick="toggleMobileNav()" aria-label="Menu" title="Menu">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-auth">
      <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark/light mode" title="Toggle theme / 切换主题">
        <span class="theme-toggle-track">
          <span class="theme-toggle-thumb"></span>
        </span>
      </button>
      <div class="nav-search-wrap">
        <input type="text" id="searchInput" placeholder="Search posts... / 搜索文章..." autocomplete="off">
        <div class="search-results-dropdown" id="searchResults"></div>
      </div>
      <?php if ($isLoggedIn): ?>
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

  function performSearch(q) {
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
          return '<a href="/blog/post/' + p.slug + '" class="sr-item">' +
            '<div class="sr-title">' + p.title + '</div>' +
            '<div class="sr-meta">' + p.category + ' · ' + p.date + '</div>' +
            '<div class="sr-summary">' + p.summary + '</div>' +
          '</a>';
        }).join('');
      })
      .catch(function() {
        results.innerHTML = '<p class="search-empty">Search failed / 搜索失败</p>';
      });
  }

  input.addEventListener('input', function() {
    clearTimeout(timer);
    var q = input.value.trim();
    if (!q) { results.innerHTML = ''; results.classList.remove('has-results'); return; }
    timer = setTimeout(function() { performSearch(q); }, 250);
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-search-wrap')) {
      results.innerHTML = '';
      results.classList.remove('has-results');
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      input.value = '';
      results.innerHTML = '';
      results.classList.remove('has-results');
      input.blur();
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      input.focus();
    }
  });
})();
</script>
