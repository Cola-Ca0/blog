<?php
require __DIR__ . '/includes/auth.php';
$galleryDir = __DIR__ . '/assets/images/gallery/';
$allImages = [];
$slides = [];
if (is_dir($galleryDir)) {
  $files = glob($galleryDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
  foreach ($files as $f) {
    $name = basename($f);
    $allImages[] = ['path' => 'assets/images/gallery/' . $name, 'name' => $name, 'inSlide' => preg_match('/^slide-\d{2}\./', $name)];
    if (preg_match('/^slide-\d{2}\./', $name)) $slides[] = 'assets/images/gallery/' . $name;
  }
}
sort($slides);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/includes/theme-init.php'; ?>
<title>Gallery / 图库 · Cola_CaO</title>
<link rel="stylesheet" href="includes/tokens.css">
<link rel="stylesheet" href="includes/shared.css">
<link rel="preconnect" href="https://fonts.loli.net">
<link href="https://fonts.loli.net/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&family=Quicksand:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:var(--font-body);background:var(--bg-deep);color:var(--text-primary);line-height:1.7;min-height:100vh}
.page-wrap{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:100px 28px 60px}
.page-header{text-align:center;margin-bottom:40px}
.page-header h1{font-family:var(--font-display);font-size:2.4rem;font-weight:700;letter-spacing:0.04em}
.page-header .sub{color:var(--text-muted);font-size:0.85rem;margin-top:6px}

/* Grid */
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.gallery-item{position:relative;border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border-glow);background:var(--bg-card);transition:var(--transition-smooth);cursor:pointer;aspect-ratio:1}
.gallery-item:hover{border-color:var(--border-glow-strong);box-shadow:0 0 24px rgba(91,160,224,0.2);transform:translateY(-3px)}
.gallery-item img{width:100%;height:100%;object-fit:cover}
.gallery-item .badge{position:absolute;top:8px;right:8px;font-family:var(--font-display);font-size:0.6rem;letter-spacing:0.06em;padding:3px 8px;border-radius:var(--radius-pill);background:rgba(11,30,48,0.8);color:var(--accent);border:1px solid var(--border-glow)}

/* Lightbox */
.lightbox{display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,0.9);align-items:center;justify-content:center;flex-direction:column}
.lightbox.open{display:flex}
.lightbox img{max-width:90vw;max-height:80vh;object-fit:contain;border-radius:var(--radius-md)}
.lightbox .lb-caption{font-family:var(--font-display);color:var(--text-secondary);margin-top:12px;font-size:0.8rem;letter-spacing:0.04em}
.lightbox .lb-close{position:absolute;top:20px;right:24px;font-size:1.5rem;color:var(--text-muted);cursor:pointer;background:none;border:none;transition:color 0.2s}
.lightbox .lb-close:hover{color:var(--accent)}
.lightbox .lb-nav{position:absolute;top:50%;transform:translateY(-50%);font-size:2rem;color:rgba(255,255,255,0.5);cursor:pointer;background:none;border:none;padding:12px;transition:color 0.2s}
.lightbox .lb-nav:hover{color:#fff}
.lb-prev{left:16px}.lb-next{right:16px}

.empty-state{text-align:center;padding:60px 20px;color:var(--text-muted)}

@media(max-width:768px){.gallery-grid{grid-template-columns:repeat(2,1fr)}.page-header h1{font-size:1.8rem}}
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php $navActive = 'gallery'; require __DIR__ . '/includes/navbar.php'; ?>

<div class="page-wrap">
  <div class="page-header">
    <h1>Gallery / 图库</h1>
    <p class="sub"><?= count($allImages) ?> images total · <?= count($slides) ?> in slideshow rotation</p>
  </div>

  <?php if (count($allImages) === 0): ?>
  <div class="empty-state"><p>Drop images into assets/images/gallery/<br>slide-01.jpg format = rotation; anything else = gallery only</p></div>
  <?php else: ?>
  <div class="gallery-grid" id="galleryGrid">
    <?php foreach ($allImages as $img): ?>
    <div class="gallery-item" onclick="openLightbox('<?= htmlspecialchars($img['path']) ?>','<?= htmlspecialchars($img['name']) ?>')">
      <img src="<?= htmlspecialchars($img['path']) ?>" alt="<?= htmlspecialchars($img['name']) ?>" loading="lazy">
      <?php if ($img['inSlide']): ?><span class="badge">SLIDE</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="lightbox" id="lightbox" onclick="if(event.target===this||event.target.classList.contains('lb-close'))this.classList.remove('open')">
  <button class="lb-close" onclick="document.getElementById('lightbox').classList.remove('open')">&times;</button>
  <button class="lb-nav lb-prev" onclick="lbNav(-1)">&lsaquo;</button>
  <button class="lb-nav lb-next" onclick="lbNav(1)">&rsaquo;</button>
  <img src="" alt="" id="lbImg">
  <div class="lb-caption" id="lbCaption"></div>
</div>

<script>
var lbImages = <?= json_encode(array_map(function($i){return $i['path'];}, $allImages)) ?>;
var lbIndex = 0;
function openLightbox(path, name) {
  lbIndex = lbImages.indexOf(path);
  document.getElementById('lbImg').src = path;
  document.getElementById('lbCaption').textContent = name;
  document.getElementById('lightbox').classList.add('open');
}
function lbNav(dir) {
  lbIndex = ((lbIndex + dir) % lbImages.length + lbImages.length) % lbImages.length;
  document.getElementById('lbImg').src = lbImages[lbIndex];
}
</script>
</body>
</html>
