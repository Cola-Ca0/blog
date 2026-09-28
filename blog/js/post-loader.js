/**
 * Dynamic Post Loading — fetches from posts-api, renders cards + pagination + tag cloud.
 * Exposes: window.filterByTag(tag)
 */
(function() {
  // 2026-09-28: 由 includes/head.php 注入(= PHP 的 $BASE)。此前本文件 11 处硬编码 '/blog/',
  // 域名根部署下首页卡片链接全部指向不存在的 /blog/post/... → Apache Not Found。
  // ⚠️ 必须用绝对前缀: 本脚本在 /page/2 上也会跑, 那里地址栏不是根目录, 相对路径会解析错。
  var BASE = window.BLOG_BASE || '';

  var grid = document.getElementById('postsGrid');
  var pagination = document.getElementById('pagination');
  if (!grid) return;

  var urlParams = new URLSearchParams(window.location.search);
  var currentPage = parseInt(urlParams.get('page')) || 1;
  var activeTag = urlParams.get('tag') || '';

  // Escape HTML entities to prevent XSS in tag/user-content injection
  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // Attribute-context escaping (quotes included) — 2026-08 审计 §1.2
  function escAttr(str) {
    return escapeHtml(str).replace(/"/g, '&quot;');
  }

  function loadPosts(page, tag) {
    if (typeof tag === 'undefined') tag = activeTag;
    grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:40px">Loading transmissions... / 加载信号中...</p>';
    pagination.style.display = 'none';

    // 2026-08-27 用户方案: 首页自适应页大小 — 量右栏高度, 填平才翻页 (其余页默认 6)
    var apiUrl = BASE + '/posts-api.php?action=list&page=' + page;
    if (tag) apiUrl += '&tag=' + encodeURIComponent(tag);
    if (!tag && page === 1) {
      var sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        var per = Math.ceil(sidebar.offsetHeight / 240) + 1; // 卡均高 240px, +1 保底
        per = Math.min(50, Math.max(6, per));
        apiUrl += '&per=' + per;
      }
    }

    var controller = new AbortController();
    var timeoutId = setTimeout(function() { controller.abort(); }, 10000);

    fetch(apiUrl, { signal: controller.signal })
      .then(function(r) { clearTimeout(timeoutId); return r.json(); })
      .then(function(data) {
        if (!data.posts || data.posts.length === 0) {
          grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:60px">No transmissions received yet / 暂无信号</p>';
          if (activeTag) {
            grid.innerHTML = '<div class="tag-filter-bar"><span>Filtered by: <strong>' + escapeHtml(activeTag) + '</strong></span> <a href="' + BASE + '/" class="tag-filter-clear">Clear filter / 清除筛选</a></div>' + grid.innerHTML;
          }
          return;
        }

        var cardsHtml = data.posts.map(function(p) {
          var tagsHtml = (p.tags || []).map(function(t) {
            var safe = escapeHtml(t);
            return '<span onclick="event.stopPropagation();filterByTag(\'' + safe.replace(/'/g, "\\'") + '\')" style="cursor:pointer" title="Filter by ' + escAttr(safe) + '">' + safe + '</span>';
          }).join('');
          var coverAttr = p.cover ? ' style="--card-cover:url(' + escAttr(p.cover) + ')"' : '';

          return '<article class="article-card"' + coverAttr + '>' +
            '<div class="card-body">' +
            '<div class="card-glow-line"></div>' +
            '<div class="card-meta">' +
              '<span class="meta-cat">' + escapeHtml(p.category) + '</span>' +
              '<span class="meta-date">' + escapeHtml(p.date) + '</span>' +
              '<span class="meta-comments">' + (p.comment_count || 0) + ' signals</span>' +
            '</div>' +
            '<a href="' + BASE + '/post/' + escapeHtml(p.slug) + '" class="card-title-link"><h3>' + escapeHtml(p.title) + '</h3></a>' +
            '<p>' + escapeHtml(p.summary) + '</p>' +
            '<div class="card-footer-row">' +
              '<div class="card-tags">' + tagsHtml + '</div>' +
              '<a href="' + BASE + '/post/' + escapeHtml(p.slug) + '" class="card-read-more">DECODE <span class="arrow">→</span></a>' +
            '</div>' +
            '</div>' + // /card-body
            (p.cover ? '<div class="card-cover-side"><img src="' + escAttr(p.cover) + '" alt="" loading="lazy"></div>' : '') +
          '</article>';
        }).join('');

        if (activeTag) {
          cardsHtml = '<div class="tag-filter-bar"><span>Filtered by: <strong>' + escapeHtml(activeTag) + '</strong></span> <a href="' + BASE + '/" class="tag-filter-clear">Clear filter / 清除筛选</a></div>' + cardsHtml;
        }
        grid.innerHTML = cardsHtml;

        if (data.totalPages > 1) {
          pagination.style.display = 'flex';
          var tagParam = activeTag ? '?tag=' + encodeURIComponent(activeTag) : '';
          var html = '';
          if (data.page > 1) { html += '<a href="' + BASE + '/page/' + (data.page - 1) + tagParam + '">&larr;</a>'; }
          else { html += '<span class="disabled">&larr;</span>'; }
          for (var i = 1; i <= data.totalPages; i++) {
            if (i === data.page) { html += '<span class="current">' + i + '</span>'; }
            else { html += '<a href="' + BASE + '/page/' + i + tagParam + '">' + i + '</a>'; }
          }
          if (data.page < data.totalPages) { html += '<a href="' + BASE + '/page/' + (data.page + 1) + tagParam + '">&rarr;</a>'; }
          else { html += '<span class="disabled">&rarr;</span>'; }
          pagination.innerHTML = html;
        }

        currentPage = data.page;
        activeTag = tag;
      })
      .catch(function() {
        clearTimeout(timeoutId);
        grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:40px">Signal interference / 信号干扰 — <a href="javascript:void(0)" onclick="location.reload()" style="color:var(--accent);text-decoration:underline;cursor:pointer">Retry / 重试</a></p>';
      });
  }

  function scrollToGrid() {
    window.scrollTo({ top: document.getElementById('postsGrid').offsetTop - 100, behavior: 'smooth' });
  }

  loadPosts(currentPage, activeTag);

  pagination.addEventListener('click', function(e) {
    var link = e.target.closest('a');
    if (!link) return;
    var match = link.href.match(/\/page\/(\d+)/);
    if (match) {
      e.preventDefault();
      var page = parseInt(match[1]);
      var tagFromUrl = new URL(link.href).searchParams.get('tag') || '';
      loadPosts(page, tagFromUrl);
      var newUrl = BASE + '/page/' + page;
      if (tagFromUrl) newUrl += '?tag=' + encodeURIComponent(tagFromUrl);
      window.history.pushState({}, '', newUrl);
      scrollToGrid();
    }
  });

  window.filterByTag = function(tag) {
    activeTag = tag;
    currentPage = 1;
    loadPosts(1, tag);
    var newUrl = BASE + '/';
    if (tag) newUrl += '?tag=' + encodeURIComponent(tag);
    window.history.pushState({}, '', newUrl);
    scrollToGrid();
  };

  // Load dynamic tag cloud
  var tagCtrl = new AbortController();
  var tagTimeout = setTimeout(function() { tagCtrl.abort(); }, 8000);
  fetch(BASE + '/posts-api.php?action=tags', { signal: tagCtrl.signal })
    .then(function(r) { clearTimeout(tagTimeout); return r.json(); })
    .then(function(data) {
      var cloud = document.getElementById('tagCloud');
      if (!cloud || !data.tags) return;
      var entries = Object.entries(data.tags);
      if (!entries.length) { cloud.innerHTML = '<span style="font-size:0.7rem;color:var(--text-muted)">No tags yet / 暂无标签</span>'; return; }
      cloud.innerHTML = entries.map(function(e) {
        var safe = escapeHtml(e[0]);
        return '<a href="' + BASE + '/?tag=' + encodeURIComponent(e[0]) + '" onclick="event.preventDefault();filterByTag(\'' + safe.replace(/'/g, "\\'") + '\')">' + safe + '</a>';
      }).join('');
    })
    .catch(function() {
      clearTimeout(tagTimeout);
      var cloud = document.getElementById('tagCloud');
      if (cloud) cloud.innerHTML = '<span style="font-size:0.7rem;color:var(--text-muted)">Tags unavailable / 标签不可用 — <a href="javascript:void(0)" onclick="location.reload()" style="color:var(--accent);text-decoration:underline;cursor:pointer">Retry</a></span>';
    });
})();
