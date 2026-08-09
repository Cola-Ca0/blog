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
