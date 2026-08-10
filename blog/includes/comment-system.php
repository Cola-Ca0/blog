<?php
/**
 * Comment System — Single source for all comment UI
 * Set $commentSlug before require, e.g. $commentSlug = 'about';
 * Requires: auth.php (for $isLoggedIn, $username, $isAdmin)
 */
if (!isset($commentSlug)) $commentSlug = 'about';
?>

<section class="comments-section" id="comments">
  <h3 style="font-family:var(--font-display);font-size:1rem;font-weight:600;color:var(--accent);letter-spacing:0.05em;margin-bottom:20px;text-align:center">
    Deep Sea Signals / 深海信号
  </h3>

  <?php if ($isLoggedIn): ?>
  <div class="comment-form" style="margin-bottom:24px;padding:16px;background:rgba(91,160,224,0.04);border-radius:var(--radius-md);border:1px solid var(--border-glow)">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
      <span style="font-family:var(--font-display);font-size:0.82rem;font-weight:600;color:var(--accent)"><?= htmlspecialchars($username) ?></span>
      <?php if ($isAdmin): ?>
      <span style="font-size:0.6rem;color:var(--secondary);font-family:var(--font-display);letter-spacing:0.06em">[ADMIN]</span>
      <?php endif; ?>
      <span style="font-size:0.68rem;color:var(--text-haze)">// Transmitting comment</span>
    </div>
    <textarea id="commentText" rows="3" style="width:100%;background:rgba(0,0,0,0.25);border:1px solid var(--border-glow);border-radius:var(--radius-sm);color:var(--text-primary);padding:10px 12px;font-size:0.85rem;resize:vertical;box-sizing:border-box" placeholder="Leave a signal... / 留下信号..."></textarea>
    <button onclick="submitComment()" style="margin-top:8px;padding:6px 20px;background:var(--primary);color:var(--text-primary);border:none;border-radius:50px;font-size:0.75rem;cursor:pointer;font-family:var(--font-display);font-weight:600;letter-spacing:0.05em">TRANSMIT SIGNAL</button>
    <span id="commentMsg" style="margin-left:12px;font-size:0.72rem"></span>
  </div>
  <?php else: ?>
  <div style="margin-bottom:24px;padding:20px;text-align:center;background:rgba(91,160,224,0.03);border-radius:var(--radius-md);border:1px solid var(--border-glow)">
    <p style="color:var(--text-muted);font-size:0.82rem;margin-bottom:4px">Deep Sea Station identification required...</p>
    <p style="color:var(--text-haze);font-size:0.75rem;margin-bottom:12px">Please login to transmit signals / 请登录以发送信号</p>
    <a href="/blog/login.php" style="display:inline-block;padding:6px 20px;background:var(--primary);color:var(--text-primary);text-decoration:none;border-radius:50px;font-size:0.75rem;font-family:var(--font-display);font-weight:600;letter-spacing:0.05em">LOGIN // 登录</a>
  </div>
  <?php endif; ?>

  <div id="commentsContainer">
    <p style="color:var(--text-muted);font-size:0.82rem;">Loading comments... / 加载评论中...</p>
  </div>
</section>

<script>
(function() {
  var slug = <?= json_encode($commentSlug) ?>;
  var isLoggedIn = <?= json_encode($isLoggedIn) ?>;
  var isAdmin = <?= json_encode($isAdmin) ?>;
  var username = <?= json_encode($username) ?>;
  var csrfToken = <?= json_encode($csrfToken ?? '') ?>;
  var container = document.getElementById('commentsContainer');
  if (!container) return;

  window.loadComments = function() {
    fetch('/blog/comments-api.php?action=list&slug=' + slug)
      .then(function(r) { return r.json(); })
      .then(function(comments) {
        if (!comments || !comments.length) {
          container.innerHTML = '<p style="color:var(--text-muted);font-size:0.82rem;text-align:center;padding:24px">No signals yet. Be the first to transmit. / 暂无信号，成为第一个发送者。</p>';
          return;
        }
        renderComments(comments);
      }).catch(function() { container.innerHTML = '<p style="color:var(--text-muted);font-size:0.82rem;">Failed to load comments.</p>'; });
  };

  function renderComments(comments) {
    function findReplies(pid) { return comments.filter(function(c) { return c.reply_to === pid; }); }
    function renderComment(c, level) {
      var ml = level * 28;
      var bl = level > 0 ? 'border-left:2px solid var(--border-glow);' : '';
      var pl = level > 0 ? 'padding-left:16px;' : '';
      var badge = c.is_admin ? ' <span style="font-size:0.6rem;color:var(--secondary);font-family:var(--font-display);letter-spacing:0.06em">[ADMIN]</span>' : '';
      var canDel = isLoggedIn && (c.username === username || isAdmin);
      var h = '<div class="comment-item" style="margin-bottom:12px;margin-left:' + ml + 'px;padding:10px 14px;background:rgba(91,160,224,0.03);border-radius:var(--radius-sm);border:1px solid rgba(91,160,224,0.06);' + bl + pl + '">' +
        '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">' +
          '<span style="font-family:var(--font-display);font-size:0.8rem;font-weight:600;color:var(--accent)">' + c.username + '</span>' + badge +
          '<span style="font-size:0.66rem;color:var(--text-haze)">' + c.created_at + '</span>' +
          (canDel ? ' <button onclick="deleteComment(\'' + c.id + '\')" style="background:none;border:none;color:var(--text-haze);cursor:pointer;font-size:0.62rem;margin-left:auto;transition:color 0.2s" onmouseover="this.style.color=\'var(--secondary)\'" onmouseout="this.style.color=\'var(--text-haze)\'">Delete</button>' : '') +
        '</div>' +
        '<div style="font-size:0.85rem;color:var(--text-primary);line-height:1.65">' + c.content.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>') + '</div>';
      if (isLoggedIn && level < 2) {
        h += '<button onclick="showReplyForm(\'' + c.id + '\')" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:0.66rem;margin-top:4px;padding:2px 8px;border-radius:var(--radius-pill);transition:all 0.2s" onmouseover="this.style.background=\'rgba(91,160,224,0.08)\';this.style.color=\'var(--accent)\'" onmouseout="this.style.background=\'none\';this.style.color=\'var(--text-muted)\'">Reply / 回复</button>' +
          '<div id="replyForm-' + c.id + '" style="display:none;margin-top:8px"></div>';
      }
      h += '</div>';
      var kids = findReplies(c.id);
      if (level < 2) for (var i = 0; i < kids.length; i++) h += renderComment(kids[i], level + 1);
      return h;
    }
    var roots = comments.filter(function(c) { return !c.reply_to; });
    var html = '';
    for (var i = 0; i < roots.length; i++) html += renderComment(roots[i], 0);
    container.innerHTML = html;
  }

  window.showReplyForm = function(pid) {
    var d = document.getElementById('replyForm-' + pid);
    d.style.display = 'block';
    d.innerHTML = '<textarea id="replyText-' + pid + '" rows="2" style="width:100%;background:rgba(0,0,0,0.25);border:1px solid var(--border-glow);border-radius:var(--radius-sm);color:var(--text-primary);padding:8px 10px;font-size:0.82rem;resize:vertical;box-sizing:border-box" placeholder="Your reply..."></textarea>' +
      '<button onclick="submitReply(\'' + pid + '\')" style="margin-top:6px;padding:5px 16px;background:var(--primary);color:var(--text-primary);border:none;border-radius:50px;font-size:0.72rem;cursor:pointer;font-family:var(--font-display);letter-spacing:0.04em">TRANSMIT</button>' +
      '<button onclick="cancelReply(\'' + pid + '\')" style="margin-top:6px;margin-left:6px;padding:5px 16px;background:transparent;color:var(--text-muted);border:1px solid var(--border-glow);border-radius:50px;font-size:0.72rem;cursor:pointer">Cancel</button>';
  };
  window.cancelReply = function(pid) { var d = document.getElementById('replyForm-' + pid); d.style.display = 'none'; d.innerHTML = ''; };
  window.submitReply = function(pid) {
    var t = document.getElementById('replyText-' + pid), c = t.value.trim();
    if (!c) return;
    var d = document.getElementById('replyForm-' + pid);
    d.innerHTML = '<span style="font-size:0.72rem;color:var(--text-muted)">Transmitting...</span>';
    fetch('/blog/comments-api.php?action=create', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({slug:slug,content:c,reply_to:pid,csrf_token:csrfToken}) })
      .then(function(r){return r.json()}).then(function(data){ if(data.success) loadComments(); else d.innerHTML = '<span style="font-size:0.72rem;color:var(--secondary)">Error</span>'; })
      .catch(function(){ d.innerHTML = '<span style="font-size:0.72rem;color:var(--secondary)">Failed</span>'; });
  };
  window.deleteComment = function(id) {
    if (!confirm('Delete this comment?')) return;
    fetch('/blog/comments-api.php?action=delete', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id,csrf_token:csrfToken}) })
      .then(function(r){return r.json()}).then(function(data){ if(data.success) loadComments(); });
  };
  loadComments();
})();

function submitComment() {
  var t = document.getElementById('commentText'), c = t.value.trim();
  if (!c) return;
  var m = document.getElementById('commentMsg');
  m.style.color = 'var(--text-muted)'; m.textContent = 'Transmitting...';
  fetch('/blog/comments-api.php?action=create', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({slug:<?= json_encode($commentSlug) ?>,content:c,reply_to:null,csrf_token:csrfToken}) })
    .then(function(r){return r.json()}).then(function(data){
      if(data.success){ t.value=''; m.textContent=''; loadComments(); }
      else { m.style.color='var(--secondary)'; m.textContent='Error: '+(data.error||'Unknown'); }
    }).catch(function(){ m.style.color='var(--secondary)'; m.textContent='Failed'; });
}
</script>
