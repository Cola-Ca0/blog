/* Graphite — 主主题交互 (2026-11-08)
   - 深浅切换 (localStorage graphiteMode)
   - 石墨导航搜索 + Lain 口令
   - 文章目录当前位置高亮 (IntersectionObserver)
*/
(function () {
  var html = document.documentElement;
  var BASE = window.BLOG_BASE || '';

  /* ---- 深浅 ---- */
  var modeBtns = document.querySelectorAll('.g-mode button');
  function markMode(mode) {
    modeBtns.forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.getAttribute('data-g-mode') === mode));
    });
  }
  modeBtns.forEach(function (b) {
    b.addEventListener('click', function () {
      if (window.setGraphiteMode) window.setGraphiteMode(b.getAttribute('data-g-mode'));
      markMode(b.getAttribute('data-g-mode'));
    });
  });
  markMode(html.getAttribute('data-g') || 'dark');

  /* ---- 搜索 / Lain 口令 ---- */
  var input = document.getElementById('gSearchInput');
  var results = document.getElementById('gSearchResults');
  if (input && results) {
    var LAIN_FLAG = 'flag{1_L0v3_L@1n!!!}';
    var timer = null;

    function close() {
      results.innerHTML = '';
      results.classList.remove('is-open');
    }
    function esc(s) {
      return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function unlock(q) {
      if (q !== LAIN_FLAG) return false;
      clearTimeout(timer);
      input.value = '';
      input.blur();
      close();
      if (window.unlockLain) window.unlockLain();
      window.scrollTo(0, 0);
      if (window.playLainBoot) window.playLainBoot();
      return true;
    }
    function search(q) {
      results.innerHTML = '<p class="g-sr-empty">Searching…</p>';
      results.classList.add('is-open');
      fetch(BASE + '/posts-api.php?action=search&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.results || !data.results.length) {
            results.innerHTML = '<p class="g-sr-empty">No posts found</p>';
            return;
          }
          results.innerHTML = data.results.map(function (p) {
            return '<a class="g-sr-item" href="' + BASE + '/post/' + encodeURIComponent(p.slug) + '">' +
              '<span class="g-sr-title">' + esc(p.title) + '</span>' +
              '<span class="g-sr-meta">' + esc(p.date) + ' · ' + esc(p.category) + '</span>' +
              '</a>';
          }).join('');
        })
        .catch(function () {
          results.innerHTML = '<p class="g-sr-empty">Search failed</p>';
        });
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (!q) { close(); return; }
      timer = setTimeout(function () { search(q); }, 250);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && unlock(input.value.trim())) e.preventDefault();
      if (e.key === 'Escape') { input.value = ''; close(); input.blur(); }
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('.g-search')) close();
    });
  }

  /* ---- 目录当前位置 ---- */
  var heads = document.querySelectorAll('.g-article h2[id], .g-article h3[id]');
  var tocLinks = document.querySelectorAll('.g-toc a');
  if (heads.length && tocLinks.length && 'IntersectionObserver' in window) {
    var byId = {};
    tocLinks.forEach(function (a) {
      var id = a.getAttribute('href').slice(1);
      if (id) byId[id] = a;
    });
    var current = null;
    function activate(id) {
      if (current === id || !byId[id]) return;
      current = id;
      tocLinks.forEach(function (a) {
        a.classList.remove('is-active');
        a.removeAttribute('aria-current');
      });
      byId[id].classList.add('is-active');
      byId[id].setAttribute('aria-current', 'true');
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) activate(e.target.id);
      });
    }, { rootMargin: '-84px 0px -70% 0px' });
    heads.forEach(function (h) { io.observe(h); });

    function activateAtPosition() {
      var best = null;
      heads.forEach(function (h) {
        if (h.getBoundingClientRect().top <= 100) best = h.id;
      });
      if (best) activate(best);
    }
    tocLinks.forEach(function (a) {
      a.addEventListener('click', function () {
        activate(a.getAttribute('href').slice(1));
      });
    });
    window.addEventListener('load', activateAtPosition);
    activateAtPosition();
  }
})();
