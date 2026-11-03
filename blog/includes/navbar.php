<?php
/**
 * Navbar Module — One nav, 3 pages, single source of truth.
 * Interface: set $navActive before require, e.g. $navActive = 'projects';
 * Variables consumed: $isLoggedIn, $username, $isAdmin (from auth.php)
 */
if (!isset($navActive)) $navActive = 'home';
// 2026-09-28 部署修复: 本文件此前混用 $BASE 与硬编码 '/blog/'(第 12/17/18/104 行),
// 域名根部署($BASE='')会让导航全 404。统一走 config.php 单一来源。
require_once __DIR__ . '/config.php';
?>
<a href="#blog-start" class="skip-link">Skip to content / 跳到内容</a>
<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="<?= ($navActive === 'home') ? '#top' : $BASE . '/index.php' ?>" class="nav-brand">
      <div class="shark-fin-icon"></div>
      <span class="brand-text">Cola_CaO</span>
    </a>
    <ul class="nav-links">
      <li><a href="<?= ($navActive === 'home') ? '#top' : $BASE . '/index.php' ?>"          class="<?= $navActive === 'home'     ? 'active' : '' ?>">HOME</a></li>
      <li><a href="<?= ($navActive === 'home') ? '#blog-start' : $BASE . '/index.php#blog-start' ?>" class="<?= $navActive === 'blog'     ? 'active' : '' ?>">BLOG</a></li>
      <li><a href="<?= $BASE ?>/projects/index.php"                                               class="<?= $navActive === 'projects' ? 'active' : '' ?>">PROJECTS</a></li>
      <li><a href="<?= $BASE ?>/timeline.php"                                                     class="<?= $navActive === 'timeline' ? 'active' : '' ?>">TIMELINE</a></li>
      <li><a href="<?= $BASE ?>/treehole.php"                                                     class="<?= $navActive === 'treehole' ? 'active' : '' ?>">TREEHOLE</a></li>
      <li><a href="<?= $BASE ?>/about.php"                                                        class="<?= $navActive === 'about'    ? 'active' : '' ?>">ABOUT</a></li>
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
        <a href="<?= $BASE ?>/admin/editor.php" class="nav-editor-mini" title="Editor / 编辑器" aria-label="Editor">&#9998;</a>
        <?php endif; ?>
        <a href="<?= $BASE ?>/admin/account.php" class="nav-editor-mini" title="Account / 修改密码" aria-label="Account">&#9881;</a>
        <div class="user-greeting">
          <div class="user-avatar-small">
            <?php if (file_exists(__DIR__ . '/../assets/images/my-avatar.jpg')): ?>
              <img src="<?= $BASE ?>/assets/images/my-avatar.jpg" alt="avatar">
            <?php else: ?>
              <span style="font-size:0.9rem;">C</span>
            <?php endif; ?>
          </div>
          <span><?= $username ?></span>
          <?php if ($isAdmin): ?><span style="font-size:0.65rem;color:var(--secondary);">[ADMIN]</span><?php endif; ?>
        </div>
        <a href="<?= $BASE ?>/login.php?action=logout" class="btn-logout">LOGOUT</a>
      <?php else: ?>
        <a href="<?= $BASE ?>/login.php" class="btn-login">SIGN IN</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Mobile Navigation Panel -->
<div class="mobile-nav-panel" id="mobileNavPanel">
  <a href="<?= $BASE ?>/">HOME</a>
  <a href="<?= $BASE ?>/#blog-start">BLOG</a>
  <a href="<?= $BASE ?>/projects/">PROJECTS</a>
  <a href="<?= $BASE ?>/timeline.php">TIMELINE</a>
  <a href="<?= $BASE ?>/treehole.php">TREEHOLE</a>
  <a href="<?= $BASE ?>/about.php">ABOUT</a>
  <?php if ($isAdmin): ?>
  <a href="<?= $BASE ?>/admin/editor.php">EDITOR</a>
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

  // 2026-11-02 隐藏场景口令: 只在完全匹配时触发, 不进入搜索请求
  var LAIN_FLAG = 'flag{1_L0v3_L@1n!!!}';
  function tryUnlockLain(q) {
    if (q !== LAIN_FLAG) return false;
    clearTimeout(timer);
    input.value = '';
    input.blur();
    results.innerHTML = '';
    results.classList.remove('has-results');
    activeIndex = -1;
    if (window.unlockLain) window.unlockLain();
    window.scrollTo(0, 0);          /* 闪屏对 Hero/页首播放 */
    if (window.playLainBoot) window.playLainBoot();
    return true;
  }

  // 2026-08 审计 §1.3: 搜索结果渲染层统一转义, 防反射型 DOM XSS
  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function performSearch(q) {
    activeIndex = -1;
    results.innerHTML = '<p class="search-empty">Searching... / 搜索中...</p>';
    results.classList.add('has-results');
    fetch('<?= $BASE ?>/posts-api.php?action=search&q=' + encodeURIComponent(q))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.results || !data.results.length) {
          results.innerHTML = '<p class="search-empty">No signals found / 未找到信号</p>';
          return;
        }
        results.innerHTML = data.results.map(function(p) {
          return '<a href="<?= $BASE ?>/post/' + esc(p.slug) + '" class="sr-item">' +
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

  /* 口令只在 Enter 提交 (2026-11-03 用户拍板): 拼完不再自动触发 */
  input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && tryUnlockLain(input.value.trim())) e.preventDefault();
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

<!-- ============================================================
     Lain 隐藏场景 · 启动闪屏 RASTER LOCK (2026-11-03 PHOSPHOR)
     只在搜索框按 Enter 命中口令时触发; 普通页面加载不播
     ============================================================ -->
<div class="lain-boot" id="lainBoot" aria-hidden="true">
  <div class="lb-field"></div>
  <div class="lb-raster"></div>
  <div class="lb-band"></div>
  <div class="lb-term" aria-hidden="true">
    <div class="lb-term-head"><span>WIRED://BOOT</span><span>NODE 04</span></div>
    <div class="lb-line lb-cmd"><span class="lb-prompt">lain@wired:~$</span> <span class="lb-cmd-text">connect --node wired --user lain</span><span class="lb-caret">▌</span></div>
    <div class="lb-line lb-out" style="--i:0"><span class="lb-ok">[  OK  ]</span> raster lock</div>
    <div class="lb-line lb-out" style="--i:1"><span class="lb-ok">[  OK  ]</span> phosphor warmup 525nm</div>
    <div class="lb-line lb-out" style="--i:2"><span class="lb-ok">[  OK  ]</span> decrypt identity</div>
    <div class="lb-line lb-out" style="--i:3"><span class="lb-ok">[  OK  ]</span> sync layer 07</div>
    <div class="lb-line lb-out" style="--i:4"><span class="lb-ok">[  OK  ]</span> link 0x7F3A</div>
    <div class="lb-line lb-out" style="--i:5"><span class="lb-ok">[  OK  ]</span> signal acquired</div>
    <div class="lb-line lb-out lb-final" style="--i:6"><span class="lb-arrow">&gt;</span> PRESENT DAY, PRESENT TIME</div>
  </div>
  <div class="lb-seam"></div>
  <div class="lb-flash"></div>
  <div class="lb-hint">click / any key to skip</div>
</div>
<div class="lain-toast" id="lainToast" role="status" aria-live="polite"></div>

<script>
(function() {
  var html = document.documentElement;
  var boot = document.getElementById('lainBoot');
  var toast = document.getElementById('lainToast');
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var bootPreTimer = null, bootTimer = null, bootOutTimer = null, bootFxTimer = null;
  var toastTimer = null, toastAfter = null;
  var heroEl = null, heroOrig = '', heroRaf = null, heroFlicker = null;

  function showLainToast(msg, ms) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function() { toast.classList.remove('show'); }, ms || 3200);
  }
  window.showLainToast = showLainToast;

  function clearLainBoot() {
    clearTimeout(bootPreTimer);
    clearTimeout(bootTimer);
    clearTimeout(bootOutTimer);
    clearTimeout(bootFxTimer);
    clearTimeout(toastAfter);
    html.classList.remove('lain-booting', 'lain-preload', 'lain-out');
  }

  /* 2.59s 内容在遮罩仍不透明时先恢复, 2.69s 再淡出 */
  function endLainBoot(instant) {
    if (!html.classList.contains('lain-booting')) return;
    clearTimeout(bootTimer);
    html.classList.add('lain-preload', 'lain-out');
    bootFxTimer = setTimeout(startLainHeroFx, instant ? 0 : 360);
    toastAfter = setTimeout(function() {
      showLainToast('WIRED LINK ESTABLISHED · 点 EXIT 退出');
    }, instant ? 0 : 300);
    bootOutTimer = setTimeout(function() {
      html.classList.remove('lain-booting', 'lain-preload', 'lain-out');
    }, 340);
  }

  window.playLainBoot = function() {
    if (!boot) return;
    if (REDUCED) {
      startLainHeroFx();
      showLainToast('WIRED LINK ESTABLISHED · 点 EXIT 退出');
      return;
    }
    clearLainBoot();
    void boot.offsetWidth;
    html.classList.add('lain-booting');
    bootPreTimer = setTimeout(function() { html.classList.add('lain-preload'); }, 2590);
    bootTimer = setTimeout(function() { endLainBoot(false); }, 2690);
  };

  window.cancelLainBoot = function() { clearLainBoot(); };

  function skipLainBoot() {
    if (!html.classList.contains('lain-booting')) return;
    endLainBoot(true);
  }
  document.addEventListener('pointerdown', skipLainBoot, true);
  document.addEventListener('keydown', function(e) {
    if (e.key !== 'F5' && e.key !== 'F12') skipLainBoot();
  }, true);

  /* ---- Hero 标题: 解码 + 单字母磷光 (仅首页) ---- */
  function getHeroEl() { return document.querySelector('.hero-line1 .en'); }

  function splitLetters(el, text) {
    el.textContent = '';
    for (var i = 0; i < text.length; i++) {
      var sp = document.createElement('span');
      sp.className = 'lain-letter';
      sp.textContent = text.charAt(i);
      el.appendChild(sp);
    }
  }

  function startFlicker(el) {
    clearTimeout(heroFlicker);
    var tick = function() {
      var letters = el.querySelectorAll('.lain-letter');
      if (!letters.length) return;
      var one = letters[Math.floor(Math.random() * letters.length)];
      one.classList.add('is-dim');
      setTimeout(function() { one.classList.remove('is-dim'); }, 80);
      heroFlicker = setTimeout(tick, 8000 + Math.random() * 4000);
    };
    heroFlicker = setTimeout(tick, 6000 + Math.random() * 3000);
  }

  function startLainHeroFx() {
    var el = getHeroEl();
    if (!el || REDUCED) return;
    if (!heroOrig) heroOrig = el.textContent;
    var target = heroOrig.toUpperCase();
    var total = 18, frame = 0, start = null;
    var glyphs = '01<>/\|[]{}=+*#@$%&';
    if (heroRaf) cancelAnimationFrame(heroRaf);
    function step(ts) {
      if (start === null) start = ts;
      frame = Math.min(total, Math.round((ts - start) / (300 / total)));
      var lock = Math.floor(target.length * frame / total);
      var out = '';
      for (var i = 0; i < target.length; i++) {
        out += i < lock ? target.charAt(i) : glyphs.charAt(Math.floor(Math.random() * glyphs.length));
      }
      el.textContent = out;
      if (frame < total) {
        heroRaf = requestAnimationFrame(step);
      } else {
        el.textContent = '';
        splitLetters(el, target);
        startFlicker(el);
        heroRaf = null;
      }
    }
    heroRaf = requestAnimationFrame(step);
  }

  /* 刷新后已在隐藏场景: 只闪字母, 重新播解码反而像卡住 */
  window.startLainHeroFlicker = function() {
    var el = getHeroEl();
    if (!el || REDUCED) return;
    if (!heroOrig) heroOrig = el.textContent;
    if (!el.querySelector('.lain-letter')) splitLetters(el, heroOrig);
    startFlicker(el);
  };

  window.restoreLainHero = function() {
    clearTimeout(bootFxTimer);
    if (heroRaf) { cancelAnimationFrame(heroRaf); heroRaf = null; }
    if (heroFlicker) { clearTimeout(heroFlicker); heroFlicker = null; }
    var el = getHeroEl();
    if (el && heroOrig) el.textContent = heroOrig;
    heroOrig = '';
  };

  if (html.getAttribute('data-scene') === 'lain') {
    setTimeout(function() { window.startLainHeroFlicker(); }, 400);
  }
})();
</script>
