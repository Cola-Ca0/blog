<?php
/**
 * Footer Module — One footer, one source.
 */
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="shark-fin-icon"></div>
      <span>Cola_CaO · 深海之下，别有洞天</span>
    </div>
    <div class="footer-links">
      <a href="https://github.com/Cola-Ca0" target="_blank" rel="noopener">GitHub</a><a href="https://space.bilibili.com/629007860" target="_blank" rel="noopener">Bilibili</a><a href="/blog/feed.xml">RSS</a>
    </div>
    <div class="footer-hud">Cola_CaO // Blog since 2026</div>
  </div>
</footer>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top / 回到顶部" aria-label="Back to top">&#9650;</button>

<!-- Theme Toggle (must run first) -->
<script>
(function() {
  var html = document.documentElement;
  var saved = localStorage.getItem('theme');
  if (saved === 'light') html.setAttribute('data-theme', 'light');

  window.toggleTheme = function() {
    var current = html.getAttribute('data-theme');
    if (current === 'light') {
      html.removeAttribute('data-theme');
      localStorage.setItem('theme', 'dark');
    } else {
      html.setAttribute('data-theme', 'light');
      localStorage.setItem('theme', 'light');
    }
  };
})();
</script>

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

  // Close dropdown on click outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-search-wrap')) {
      results.innerHTML = '';
      results.classList.remove('has-results');
    }
  });

  // Escape clears search
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

<!-- Back to Top trigger -->
<script>
(function() {
  var btn = document.getElementById('backToTop');
  if (!btn) return;
  var ticking = false;
  window.addEventListener('scroll', function() {
    if (!ticking) {
      requestAnimationFrame(function() {
        btn.classList.toggle('visible', window.scrollY > 400);
        ticking = false;
      });
      ticking = true;
    }
  });
})();
</script>
