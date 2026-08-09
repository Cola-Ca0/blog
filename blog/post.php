<?php
/**
 * Article Detail Page
 * URL: /blog/post/{slug} via .htaccess rewrite → post.php?slug={slug}
 */
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/markdown.php';

// Security: validate slug — only a-z, 0-9, hyphens
$slug = $_GET['slug'] ?? '';
if (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
    http_response_code(404);
    echo 'Invalid slug';
    exit;
}

// Load post
$postPath = __DIR__ . '/posts/' . $slug . '.md';
$post = parsePost($postPath);
if ($post === null || $post['draft']) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signal Lost · Cola_CaO</title>
    <link rel="stylesheet" href="/blog/includes/tokens.css">
    <link rel="stylesheet" href="/blog/includes/shared.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      .not-found { display:flex; flex-direction:column; align-items:center; justify-content:center;
        min-height:80vh; text-align:center; padding:40px 20px; }
      .not-found .code { font-family:'Rajdhani',sans-serif; font-size:6rem; font-weight:700;
        color:var(--accent); text-shadow:0 0 40px rgba(142,208,232,0.4); }
      .not-found p { color:var(--text-muted); margin:16px 0; }
      .not-found a { color:var(--accent); text-decoration:none; border-bottom:1px solid var(--border-glow); }
    </style>
    </head>
    <body style="background:var(--bg-deep)">
    <div class="not-found">
      <div class="code">0x0194</div>
      <p>Signal lost // 信号丢失</p>
      <p style="font-size:0.8rem">This transmission does not exist or has been classified.</p>
      <p><a href="/blog/">Return to Surface / 返回水面</a></p>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$pageTitle = htmlspecialchars($post['title']) . ' · Cola_CaO';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<meta name="description" content="<?= htmlspecialchars($post['summary']) ?>">
<meta property="og:title" content="<?= htmlspecialchars($post['title']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($post['summary']) ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="http://localhost/blog/post/<?= htmlspecialchars($slug) ?>">
<link rel="stylesheet" href="/blog/includes/tokens.css">
<link rel="stylesheet" href="/blog/includes/shared.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Great+Vibes&family=Noto+Serif+SC:wght@300;400;500;600;700&family=Quicksand:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Reset & Base — must match index.php */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html { scroll-behavior: smooth; font-size: 16px; }
body {
  font-family: var(--font-body);
  background-color: var(--bg-deep);
  color: var(--text-primary);
  line-height: 1.7;
  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--bg-deep); }
::-webkit-scrollbar-thumb { background: rgba(91,160,224,0.28); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: rgba(91,160,224,0.45); }
::selection { background: rgba(91,160,224,0.35); color: #fff; }

/* Article layout */
.article-page { max-width: 800px; margin: 0 auto; padding: 100px 24px 60px; }

.article-header { margin-bottom: 40px; }
.article-cover { width:100%; max-height:400px; object-fit:cover; border-radius:var(--radius-lg);
  border:1px solid var(--border-glow); margin-bottom:32px; }
.article-meta { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.article-meta .meta-cat { background:rgba(91,160,224,0.08); color:var(--primary); padding:3px 12px;
  border-radius:50px; font-family:var(--font-display); font-size:0.72rem; font-weight:600;
  letter-spacing:0.05em; border:1px solid rgba(91,160,224,0.18); }
.article-meta .meta-date { font-family:var(--font-mono,monospace); font-size:0.78rem; color:var(--text-muted); }
.article-meta .meta-read-time { font-family:var(--font-display); font-size:0.7rem; color:var(--text-muted);
  letter-spacing:0.04em; }
.article-meta .meta-updated { font-family:var(--font-mono,monospace); font-size:0.72rem; color:var(--text-haze);
  font-style:italic; }
.article-title { font-family:var(--font-display); font-size:clamp(1.6rem,3vw,2.4rem); font-weight:700;
  color:var(--text-primary); letter-spacing:0.02em; line-height:1.25; margin-bottom:8px; }
.admin-edit-link { font-family:var(--font-display); font-size:0.65rem; font-weight:600;
  color:var(--secondary); text-decoration:none; letter-spacing:0.08em; margin-left:12px;
  padding:3px 10px; border:1px solid rgba(240,128,96,0.25); border-radius:50px;
  background:rgba(240,128,96,0.06); transition:var(--transition-smooth);
  vertical-align:middle; display:inline-block; }
.admin-edit-link:hover { color:var(--text-primary); background:var(--secondary); border-color:var(--secondary);
  box-shadow:0 0 14px rgba(240,128,96,0.3); }
.article-tags { display:flex; gap:8px; flex-wrap:wrap; }
.article-tags span { font-size:0.7rem; color:var(--accent); background:rgba(91,160,224,0.06);
  padding:2px 10px; border-radius:50px; border:1px solid rgba(91,160,224,0.13); }

/* Article body — rendered markdown */
.article-body { color:var(--text-primary); line-height:1.85; font-size:0.95rem; }
.article-body h1, .article-body h2, .article-body h3 { font-family:var(--font-display);
  color:var(--accent); margin:36px 0 14px; letter-spacing:0.03em; }
.article-body h2 { font-size:1.4rem; border-left:3px solid var(--accent); padding-left:14px; }
.article-body h3 { font-size:1.15rem; }
.article-body p { margin-bottom:18px; }
.article-body a { color:var(--accent); text-decoration:none; border-bottom:1px solid rgba(142,208,232,0.3); }
.article-body a:hover { border-bottom-color:var(--accent); }
.article-body blockquote { border-left:3px solid var(--border-glow); padding:10px 18px;
  margin:20px 0; color:var(--text-secondary); font-style:italic; background:rgba(91,160,224,0.04);
  border-radius:0 var(--radius-sm) var(--radius-sm) 0; }
.article-body code { font-family:var(--font-mono,monospace); font-size:0.82rem;
  background:rgba(91,160,224,0.08); padding:2px 6px; border-radius:3px; color:var(--accent); }
.article-body pre { background:rgba(0,0,0,0.35); padding:18px 44px 18px 20px; border-radius:var(--radius-md);
  overflow-x:auto; margin:20px 0; border:1px solid var(--border-glow); position:relative; }
.code-copy-btn { position:absolute; top:8px; right:8px; background:rgba(91,160,224,0.1);
  border:1px solid var(--border-glow); color:var(--text-muted); font-size:0.65rem;
  font-family:var(--font-display); letter-spacing:0.05em; cursor:pointer;
  padding:3px 8px; border-radius:var(--radius-sm); transition:var(--transition-smooth);
  opacity:0; }
.article-body pre:hover .code-copy-btn { opacity:1; }
.code-copy-btn:hover { background:var(--primary); color:var(--text-primary); border-color:var(--primary); }
.code-copy-btn.copied { background:var(--accent); color:#081828; border-color:var(--accent); }
.article-body pre code { background:none; padding:0; color:var(--text-primary); font-size:0.82rem;
  line-height:1.6; }
.article-body ul, .article-body ol { margin:12px 0 18px 24px; }
.article-body li { margin-bottom:6px; }
.article-body img { max-width:100%; border-radius:var(--radius-md); margin:16px 0; }
.article-body hr { border:none; height:1px; background:linear-gradient(90deg,transparent,var(--border-glow),transparent);
  margin:32px 0; }

/* Reading progress bar */
.reading-progress { position:fixed; top:0; left:0; height:2.5px;
  background:linear-gradient(90deg,var(--primary),var(--accent));
  z-index:101; width:0%; box-shadow:0 0 6px rgba(91,160,224,0.4);
  transition:width 0.1s linear; }

/* Floating back button — fixed bottom-left */
.article-back { display:inline-flex; align-items:center; gap:8px; font-family:var(--font-display);
  font-size:0.75rem; font-weight:600; color:var(--accent); text-decoration:none; letter-spacing:0.06em;
  padding:8px 18px; border:1px solid var(--border-glow); border-radius:50px;
  background:var(--bg-card); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
  transition:var(--transition-smooth);
  position:fixed; bottom:24px; left:24px; z-index:90; }
.article-back:hover { color:var(--text-primary); background:var(--primary); border-color:var(--primary);
  box-shadow:0 0 20px rgba(91,160,224,0.35); transform:translateY(-2px); }

/* Comments section */
.comments-section { margin-top:60px; padding-top:40px;
  border-top:1px solid var(--border-glow); }
.comments-section h3 { font-family:var(--font-display); font-size:1rem; font-weight:600;
  color:var(--accent); letter-spacing:0.05em; margin-bottom:20px; }

/* Mobile */
@media (max-width: 768px) {
  .article-page { padding: 80px 16px 40px; }
  .article-title { font-size:1.4rem; }
  .article-meta { gap:8px; }
  .article-body { font-size:0.88rem; }
  .article-body h2 { font-size:1.2rem; }
  .article-body pre { padding:12px 14px; font-size:0.75rem; }
  .comment-item { margin-left:0 !important; }
  .article-back { bottom:16px; left:16px; font-size:0.7rem; padding:6px 14px; }
  .comment-form { padding:12px; }
}
@media (max-width: 480px) {
  .article-page { padding: 72px 12px 32px; }
  .article-title { font-size:1.25rem; }
  .article-meta { flex-direction:column; align-items:flex-start; }
  .article-body h2 { font-size:1.1rem; }
  .article-back { bottom:12px; left:12px; font-size:0.65rem; padding:5px 12px; }
}
</style>
</head>
<body>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php require __DIR__ . '/includes/navbar.php'; ?>

<div class="reading-progress" id="readingProgress"></div>

<a href="/blog/" class="article-back">&larr; Return to Surface / 返回水面</a>

<main class="article-page">

  <header class="article-header">
    <?php if ($post['cover']): ?>
      <img class="article-cover" src="<?= htmlspecialchars($post['cover']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
    <?php endif; ?>

    <div class="article-meta">
      <span class="meta-cat"><?= htmlspecialchars($post['category']) ?></span>
      <span class="meta-date"><?= htmlspecialchars($post['date']) ?></span>
      <span class="meta-read-time"><?= max(1, ceil(mb_strlen(strip_tags($post['body_html'])) / 500)) ?> min read</span>
      <?php if ($post['updated']): ?>
        <span class="meta-updated">Updated <?= htmlspecialchars($post['updated']) ?></span>
      <?php endif; ?>
    </div>

    <h1 class="article-title">
      <?= htmlspecialchars($post['title']) ?>
      <?php if ($isAdmin): ?>
      <a href="/blog/admin/editor.php?slug=<?= htmlspecialchars($slug) ?>" class="admin-edit-link" title="Edit this article">[EDIT]</a>
      <?php endif; ?>
    </h1>

    <?php if (!empty($post['tags'])): ?>
    <div class="article-tags">
      <?php foreach ($post['tags'] as $tag): ?>
        <span><?= htmlspecialchars($tag) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </header>

  <div class="article-body">
    <?= $post['body_html'] ?>
  </div>

  <!-- Comments -->
  <?php $commentSlug = $slug; require __DIR__ . '/includes/comment-system.php'; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

<!-- Code Copy Buttons -->
<script>
(function() {
  var pres = document.querySelectorAll('.article-body pre');
  pres.forEach(function(pre) {
    var btn = document.createElement('button');
    btn.className = 'code-copy-btn';
    btn.textContent = 'COPY';
    btn.onclick = function() {
      var code = pre.querySelector('code');
      var text = code ? code.textContent : pre.textContent;
      navigator.clipboard.writeText(text).then(function() {
        btn.textContent = 'COPIED!';
        btn.classList.add('copied');
        setTimeout(function() { btn.textContent = 'COPY'; btn.classList.remove('copied'); }, 1500);
      }).catch(function() {
        btn.textContent = 'FAILED';
        setTimeout(function() { btn.textContent = 'COPY'; }, 1500);
      });
    };
    pre.appendChild(btn);
  });
})();
</script>

<!-- Reading Progress -->
<script>
(function() {
  var bar = document.getElementById('readingProgress');
  if (!bar) return;
  var ticking = false;
  window.addEventListener('scroll', function() {
    if (!ticking) {
      requestAnimationFrame(function() {
        var scrollTop = window.scrollY;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var pct = docHeight > 0 ? Math.min(100, (scrollTop / docHeight) * 100) : 0;
        bar.style.width = pct + '%';
        ticking = false;
      });
      ticking = true;
    }
  });
})();
</script>

</body>
</html>
