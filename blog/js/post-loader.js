/**
 * Dynamic Post Loading — fetches from posts-api, renders cards + pagination + tag cloud.
 * Exposes: window.filterByTag(tag)
 */
(function() {
  var grid = document.getElementById('postsGrid');
  var pagination = document.getElementById('pagination');
  if (!grid) return;

  var urlParams = new URLSearchParams(window.location.search);
  var currentPage = parseInt(urlParams.get('page')) || 1;
  var activeTag = urlParams.get('tag') || '';

  function loadPosts(page, tag) {
    if (typeof tag === 'undefined') tag = activeTag;
    grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:40px">Loading transmissions... / 加载信号中...</p>';
    pagination.style.display = 'none';

    var apiUrl = 'posts-api.php?action=list&page=' + page;
    if (tag) apiUrl += '&tag=' + encodeURIComponent(tag);

    fetch(apiUrl)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.posts || data.posts.length === 0) {
          grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:60px">No transmissions received yet / 暂无信号</p>';
          if (activeTag) {
            grid.innerHTML = '<div class="tag-filter-bar"><span>Filtered by: <strong>' + activeTag + '</strong></span> <a href="/blog/" class="tag-filter-clear">Clear filter / 清除筛选</a></div>' + grid.innerHTML;
          }
          return;
        }

        var cardsHtml = data.posts.map(function(p) {
          var tagsHtml = (p.tags || []).map(function(t) {
            return '<span onclick="event.stopPropagation();filterByTag(\'' + t.replace(/'/g, "\\'") + '\')" style="cursor:pointer" title="Filter by ' + t + '">' + t + '</span>';
          }).join('');
          var coverAttr = p.cover ? ' style="--card-cover:url(' + p.cover + ')"' : '';

          return '<article class="article-card"' + coverAttr + '>' +
            '<div class="card-glow-line"></div>' +
            '<div class="card-meta">' +
              '<span class="meta-cat">' + p.category + '</span>' +
              '<span class="meta-date">' + p.date + '</span>' +
            '</div>' +
            '<a href="/blog/post/' + p.slug + '" class="card-title-link"><h3>' + p.title + '</h3></a>' +
            '<p>' + p.summary + '</p>' +
            '<div class="card-footer-row">' +
              '<div class="card-tags">' + tagsHtml + '</div>' +
              '<a href="/blog/post/' + p.slug + '" class="card-read-more">DECODE <span class="arrow">→</span></a>' +
            '</div>' +
          '</article>';
        }).join('');

        if (activeTag) {
          cardsHtml = '<div class="tag-filter-bar"><span>Filtered by: <strong>' + activeTag + '</strong></span> <a href="/blog/" class="tag-filter-clear">Clear filter / 清除筛选</a></div>' + cardsHtml;
        }
        grid.innerHTML = cardsHtml;

        if (data.totalPages > 1) {
          pagination.style.display = 'flex';
          var tagParam = activeTag ? '?tag=' + encodeURIComponent(activeTag) : '';
          var html = '';
          if (data.page > 1) { html += '<a href="/blog/page/' + (data.page - 1) + tagParam + '">&larr;</a>'; }
          else { html += '<span class="disabled">&larr;</span>'; }
          for (var i = 1; i <= data.totalPages; i++) {
            if (i === data.page) { html += '<span class="current">' + i + '</span>'; }
            else { html += '<a href="/blog/page/' + i + tagParam + '">' + i + '</a>'; }
          }
          if (data.page < data.totalPages) { html += '<a href="/blog/page/' + (data.page + 1) + tagParam + '">&rarr;</a>'; }
          else { html += '<span class="disabled">&rarr;</span>'; }
          pagination.innerHTML = html;
        }

        currentPage = data.page;
        activeTag = tag;
      })
      .catch(function() {
        grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:40px">Signal interference... / 信号干扰...</p>';
      });
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
      var newUrl = '/blog/page/' + page;
      if (tagFromUrl) newUrl += '?tag=' + encodeURIComponent(tagFromUrl);
      window.history.pushState({}, '', newUrl);
      window.scrollTo({ top: document.getElementById('postsGrid').offsetTop - 100, behavior: 'smooth' });
    }
  });

  window.filterByTag = function(tag) {
    activeTag = tag;
    currentPage = 1;
    loadPosts(1, tag);
    var newUrl = '/blog/';
    if (tag) newUrl += '?tag=' + encodeURIComponent(tag);
    window.history.pushState({}, '', newUrl);
    window.scrollTo({ top: document.getElementById('postsGrid').offsetTop - 100, behavior: 'smooth' });
  };

  // Load dynamic tag cloud
  fetch('posts-api.php?action=tags')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      var cloud = document.getElementById('tagCloud');
      if (!cloud || !data.tags) return;
      var entries = Object.entries(data.tags);
      if (!entries.length) { cloud.innerHTML = '<span style="font-size:0.7rem;color:var(--text-muted)">No tags yet / 暂无标签</span>'; return; }
      cloud.innerHTML = entries.map(function(e) {
        return '<a href="/blog/?tag=' + encodeURIComponent(e[0]) + '" onclick="event.preventDefault();filterByTag(\'' + e[0].replace(/'/g, "\\'") + '\')">' + e[0] + '</a>';
      }).join('');
    })
    .catch(function() {
      var cloud = document.getElementById('tagCloud');
      if (cloud) cloud.innerHTML = '<span style="font-size:0.7rem;color:var(--text-muted)">Tags unavailable / 标签不可用</span>';
    });
})();
