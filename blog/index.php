<?php
/**
 * Cola_CaO 博客主页
 * 功能：全屏壁纸Hero、登录状态检测、博客内容展示、图廊轮播
 */
require __DIR__ . '/includes/auth.php';
// Load skills from about-content.json for radar chart
$aboutJson = json_decode(file_get_contents(__DIR__ . '/about-content.json'), true) ?: [];
$radarSkills = $aboutJson['skills'] ?? [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/includes/theme-init.php'; ?>
<title>Cola_CaO · 深海之下，别有洞天</title>
<meta name="description" content="可乐的水下研究站 — CS/网络安全/CTF。在深海中记录学习轨迹，分享安全探索与代码思考。">
<meta property="og:title" content="Cola_CaO · 深海之下，别有洞天">
<meta property="og:description" content="可乐的水下研究站 — CS/网络安全/CTF。在深海中记录学习轨迹。">
<meta property="og:type" content="website">
<meta property="og:url" content="http://localhost/blog/">
<link rel="stylesheet" href="includes/tokens.css">
<link rel="stylesheet" href="includes/shared.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400&family=Great+Vibes&family=Noto+Serif+SC:wght@300;400;500;600;700&family=Quicksand:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: var(--font-body);
  background-color: var(--bg-deep);
  color: var(--text-primary);
  line-height: 1.7;
  min-height: 100vh;
  overflow-x: hidden;
  position: relative;
}

/* ============================================================
   Wallpaper Hero — Full Screen
   ============================================================ */
.hero-wallpaper {
  position: relative;
  width: 100%;
  height: 100vh;
  min-height: 600px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.hero-wallpaper .wallpaper-bg {
  position: absolute;
  inset: 0;
  background-image: url('assets/images/wallpaper.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  z-index: 0;
}

/* Fallback gradient when no wallpaper */
/* Only visible when wallpaper image fails to load or is missing */
.hero-wallpaper .wallpaper-fallback {
  position: absolute;
  inset: 0;
  background: linear-gradient(160deg, #051525 0%, #0a2848 30%, #0d1a30 60%, #051520 100%);
  z-index: -1;
}

/* Ocean shimmer overlay on wallpaper */
.hero-wallpaper .wallpaper-shimmer {
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(180deg, rgba(8,24,40,0.15) 0%, transparent 40%, transparent 70%, rgba(8,24,40,0.6) 100%);
  pointer-events: none;
}

/* Subtle caustic light effect */
.hero-wallpaper .caustic-overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  opacity: 0.08;
  background:
    radial-gradient(ellipse at 30% 40%, rgba(142,208,232,0.5) 0%, transparent 50%),
    radial-gradient(ellipse at 70% 30%, rgba(91,160,224,0.4) 0%, transparent 45%),
    radial-gradient(ellipse at 50% 80%, rgba(91,160,224,0.25) 0%, transparent 50%);
  animation: caustic-drift 12s ease-in-out infinite;
}

@keyframes caustic-drift {
  0%, 100% { opacity: 0.06; transform: scale(1); }
  33% { opacity: 0.1; transform: scale(1.02); }
  66% { opacity: 0.07; transform: scale(0.99); }
}

/* Hero text content */
.hero-text-center {
  position: relative;
  z-index: 2;
  text-align: center;
  padding: 60px 20px 20px;
}

.hero-text-center .hero-line1 {
  font-size: clamp(2.4rem, 5.5vw, 4rem);
  font-weight: 400;
  letter-spacing: 0.02em;
  color: #8ed0e8;
  text-shadow: 0 2px 30px rgba(0,0,0,0.5), 0 0 80px rgba(142,208,232,0.35), 0 0 120px rgba(142,208,232,0.15);
  margin-bottom: 12px;
  animation: hero-text-in 1.2s ease-out;
  line-height: 1.3;
}

.hero-text-center .hero-line1 .en {
  font-family: 'Great Vibes', cursive;
  font-size: 1.3em;
  display: block;
}

.hero-text-center .hero-line1 .en-sub {
  font-family: 'Rajdhani', 'Exo 2', sans-serif;
  font-size: 0.45em;
  font-weight: 300;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  display: block;
}

.hero-text-center .hero-line2 {
  font-family: 'Noto Serif SC', 'Georgia', serif;
  font-size: clamp(0.85rem, 1.4vw, 1rem);
  font-weight: 300;
  font-style: italic;
  letter-spacing: 0.03em;
  line-height: 1.5;            /* §4.1: italic descender clearance for y/p/g/j/q */
  padding-bottom: 4px;         /* reserve space so descenders don't clip */
  color: rgba(180,210,235,0.7);
  text-shadow: 0 1px 12px rgba(0,0,0,0.4);
  animation: hero-text-in 1.4s ease-out;
}

@keyframes hero-text-in {
  from { opacity: 0; transform: translateY(50px); filter: blur(6px); }
  to { opacity: 1; transform: translateY(0); filter: blur(0); }
}

/* ============================================================
   Navbar — Glass + Blur
   ============================================================ */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: 66px;
  background: var(--bg-card); backdrop-filter: blur(22px);
  -webkit-backdrop-filter: blur(22px);
  border-bottom: 1px solid var(--border-glow);
  display: flex; align-items: center;
  transition: var(--transition-smooth);
  transform: translateY(-100%);
}
.navbar.visible { transform: translateY(0); }
.navbar:hover { border-bottom-color: var(--border-glow-strong); box-shadow: 0 2px 24px rgba(91,160,224,0.12); }

.nav-inner {
  max-width: 1200px; width: 100%; margin: 0 auto; padding: 0 28px;
  display: flex; align-items: center; justify-content: space-between;
}

/* Brand */
.nav-brand {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none; color: var(--text-primary); transition: var(--transition-smooth);
}
.nav-brand:hover { color: var(--accent); text-shadow: 0 0 20px rgba(142,208,232,0.4); }

.shark-fin-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
  transition: var(--transition-bounce);
  box-shadow: 0 0 12px rgba(91,160,224,0.35);
}
.nav-brand:hover .shark-fin-icon { transform: translateY(-3px) rotate(-5deg); box-shadow: 0 0 20px rgba(142,208,232,0.55); }

.brand-text {
  font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; letter-spacing: 0.04em;
}


/* User greeting when logged in */
.user-greeting {
  display: flex; align-items: center; gap: 10px;
  font-family: var(--font-display); font-size: 0.85rem; color: var(--accent); letter-spacing: 0.04em;
}
.user-greeting .user-avatar-small {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; border: 2px solid rgba(142,208,232,0.3);
}
.user-avatar-small img { width: 100%; height: 100%; object-fit: cover; }

/* ============================================================
   Wave Divider — Smooth transition from hero
   ============================================================ */
.wave-divider {
  position: relative;
  z-index: 3;
  margin-top: -80px;    /* 向上覆盖壁纸底部 80px */
  margin-bottom: -30px; /* 向下覆盖博客顶部 30px */
  line-height: 0;
  pointer-events: none; /* 不阻挡下方内容点击 */
}

.wave-divider .waves {
  width: 100%;
  height: 130px;
  display: block;
}

.wave-parallax use {
  animation: wave-move 12s cubic-bezier(0.55, 0.5, 0.45, 0.5) infinite;
}

.wave-parallax use:nth-child(1) { animation-delay: -2s; animation-duration: 8s; }
.wave-parallax use:nth-child(2) { animation-delay: -4s; animation-duration: 10s; }
.wave-parallax use:nth-child(3) { animation-delay: -6s; animation-duration: 13s; }
.wave-parallax use:nth-child(4) { animation-delay: -8s; animation-duration: 16s; }

@keyframes wave-move {
  0% { transform: translate(-90px, 0); }
  100% { transform: translate(85px, 0); }
}

/* ============================================================
   Music Hero — fills hero left column, bottom-aligns with radar
   ============================================================ */
.music-hero {
  background: var(--bg-card); backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  display: flex; flex-direction: column;
  transition: var(--transition-smooth);
}
.music-hero:hover { border-color: var(--border-glow-strong); box-shadow: var(--shadow-md), inset 0 1px 0 rgba(255,255,255,0.04); }
.music-hero-inner {
  flex:1; display:flex; flex-direction:column; padding:14px 16px; min-height:0;
}
.music-hero-header {
  font-family: var(--font-display); font-size: 0.7rem; font-weight: 600;
  letter-spacing: 0.08em; color: var(--accent); text-transform: uppercase;
  margin-bottom: 12px; display: flex; align-items: center; gap: 6px;
  flex-shrink: 0;
}
.music-hero-header .diamond-sm {
  width: 5px; height: 5px; background: var(--accent);
  transform: rotate(45deg); box-shadow: 0 0 3px var(--accent);
}
/* --- Music Cover Row --- */
.music-cover-row { display:flex; gap:16px; align-items:center; flex:0 0 auto; min-height:0; margin-bottom:8px }
.music-cover-wrap { flex-shrink:0; width:130px; height:130px; margin-left:16px; cursor:pointer; position:relative }
.music-cover-disc {
  width:100%; height:100%; border-radius:50%; overflow:hidden;
  background-color:#0a2848;
  background-image:linear-gradient(135deg,#0a2848,#0d3a5c,#0a2848);
  background-size:cover;background-position:center;
  border:2px solid var(--border-glow); box-shadow:0 0 16px rgba(91,160,224,0.25);
  animation:cover-spin 12s linear infinite paused;
  display:flex;align-items:center;justify-content:center;position:relative
}
.music-cover-disc.playing { animation-play-state:running }
@keyframes cover-spin { 100%{transform:rotate(360deg)} }
.music-cover-inner {
  width:34px;height:34px;border-radius:50%;
  background:radial-gradient(circle,var(--accent),var(--primary));
  box-shadow:0 0 8px rgba(91,160,224,0.5)
}
.music-cover-disc::after {
  content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
  width:24px;height:24px;border-radius:50%;background:var(--bg-deep);z-index:1
}
/* --- Lyrics --- */
.music-lyrics {
  flex:1;min-width:0;overflow:hidden;font-size:0.95rem;color:var(--text-muted);
  line-height:2.4;display:flex;flex-direction:column;justify-content:center;
  text-align:center
}
.music-lyrics p { margin:0;overflow:hidden;text-overflow:ellipsis;transition:all 0.3s }
/* --- Particle Ocean Canvas --- */
.particle-ocean {
  position: fixed; inset: 0; width: 100%; height: 100%;
  pointer-events: none; z-index: 1;
  mask-image: linear-gradient(to bottom, transparent 60%, black 85%, black 100%);
  -webkit-mask-image: linear-gradient(to bottom, transparent 60%, black 85%, black 100%);
}
.sparkle-layer {
  position: fixed; inset: 0; width: 100%; height: 100%;
  pointer-events: none; z-index: 1;
}
/* --- Progress Bar --- */
.music-progress-wrap { display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-shrink:0 }
.music-time { font-family:var(--font-mono,monospace);font-size:0.6rem;color:var(--text-muted);min-width:32px;text-align:center }
.music-progress-bar {
  flex:1;height:8px;background:rgba(91,160,224,0.12);border-radius:4px;cursor:pointer;
  position:relative;overflow:visible
}
.music-progress-bar:hover { height:10px }
.music-progress-fill {
  height:100%;border-radius:4px;
  background:linear-gradient(90deg,var(--primary),var(--accent));
  width:0%;transition:width 0.15s linear;position:relative
}
.music-progress-thumb {
  position:absolute;right:-6px;top:50%;transform:translateY(-50%);
  width:12px;height:12px;border-radius:50%;background:var(--accent);
  box-shadow:0 0 6px var(--accent);opacity:0;transition:opacity 0.2s
}
.music-progress-bar:hover .music-progress-thumb { opacity:1 }
#musicVolume { -webkit-appearance:none;appearance:none;height:4px;background:rgba(91,160,224,0.15);border-radius:2px;outline:none;cursor:pointer;accent-color:var(--accent) }
#musicVolume::-webkit-slider-thumb { -webkit-appearance:none;width:10px;height:10px;border-radius:50%;background:var(--accent);cursor:pointer }
/* --- Controls Row --- */
.music-ctrls { display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px;flex-shrink:0 }
.music-ctrl-btn {
  background:transparent;border:1px solid var(--border-glow);color:var(--text-secondary);
  border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;
  cursor:pointer;font-size:0.6rem;transition:var(--transition-smooth)
}
.music-ctrl-btn:hover { border-color:var(--accent);color:var(--accent);box-shadow:0 0 8px rgba(142,208,232,0.2) }
.music-ctrl-play { width:34px;height:34px;font-size:0.75rem;border-color:var(--accent);color:var(--accent) }
.music-ctrl-mode { font-size:0.52rem;font-weight:700;letter-spacing:0.04em;font-family:var(--font-display);width:auto;padding:0 6px;border-radius:var(--radius-pill) }
/* --- Song List --- */
.music-results { flex:1 1 0;overflow-y:auto;min-height:50px;border-top:1px solid var(--border-glow);padding-top:6px }
.music-result-item {
  display:flex;align-items:center;justify-content:space-between;gap:8px;
  padding:5px 8px;border-radius:var(--radius-sm);cursor:pointer;
  font-size:0.68rem;color:var(--text-secondary);transition:var(--transition-smooth)
}
.music-result-item:hover { background:rgba(91,160,224,0.1);color:var(--text-primary) }
.music-result-item.active { color:var(--accent);background:rgba(91,160,224,0.08) }
.music-result-item .play-btn {
  font-family:var(--font-display);font-size:0.6rem;font-weight:600;letter-spacing:0.06em;
  padding:2px 10px;border-radius:var(--radius-pill);border:1px solid var(--border-glow);
  background:transparent;color:var(--accent);cursor:pointer;transition:var(--transition-smooth)
}
.music-result-item .play-btn:hover { background:var(--primary);color:#fff;border-color:var(--primary) }

/* ============================================================
   Skill Radar Chart — replaces compact gallery in hero right column
   ============================================================ */
.radar-card {
  background: var(--bg-card); backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg);
  padding: 16px; box-shadow: var(--shadow-sm);
  transition: var(--transition-smooth);
}
.radar-card:hover { border-color: var(--border-glow-strong); box-shadow: var(--shadow-md); }
.radar-title {
  font-family: var(--font-display); font-size: 0.7rem; font-weight: 600;
  letter-spacing: 0.08em; color: var(--accent); text-transform: uppercase;
  margin-bottom: 8px; display: flex; align-items: center; gap: 6px;
}
.radar-title .diamond-sm {
  width: 5px; height: 5px; background: var(--accent);
  transform: rotate(45deg); box-shadow: 0 0 3px var(--accent); flex-shrink: 0;
}
.radar-chart { text-align: center; }
.radar-svg { width: 100%; max-width: 260px; height: auto; }
.radar-shape { transition: all 0.6s ease; }
.radar-card:hover .radar-shape { fill: rgba(91,160,224,0.22); }

/* ============================================================
   Blog Content Section (below hero)
   ============================================================ */
.blog-section {
  position: relative; z-index: 2;
  max-width: 1200px; margin: 0 auto; padding: 60px 28px 40px;
}

/* Section transition divider */
.section-divider {
  position: relative; z-index: 2; text-align: center; padding: 8px 0 20px;
}
.section-divider .divider-line {
  display: inline-block; width: 60px; height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
  opacity: 0.5;
}

/* ============================================================
   Hero Content (within blog section, not full-screen)
   ============================================================ */
.blog-hero {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 40px;
  align-items: stretch;
  min-height: 320px;
  position: relative;
  margin-bottom: 50px;
}
.blog-hero-right {
  display: flex; flex-direction: column; gap: 20px;
}

.blog-hero-content { position: relative; display: flex; flex-direction: column; min-height: 0; }

/* Corner brackets */
.blog-hero-content::before, .blog-hero-content::after {
  content: ''; position: absolute; width: 40px; height: 40px;
  border-color: var(--border-glow); border-style: solid; pointer-events: none; transition: var(--transition-smooth);
}
.blog-hero-content::before { top: -10px; left: -10px; border-width: 2px 0 0 2px; border-top-left-radius: 6px; }
.blog-hero-content::after { bottom: -10px; right: -10px; border-width: 0 2px 2px 0; border-bottom-right-radius: 6px; }

.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(142,208,232,0.06); border: 1px solid rgba(142,208,232,0.18);
  border-radius: 50px; padding: 6px 16px; margin-bottom: 20px;
  font-size: 0.78rem; font-weight: 600; letter-spacing: 0.08em;
  color: var(--accent); text-transform: uppercase; font-family: var(--font-display);
}
.hero-badge .diamond {
  width: 8px; height: 8px; background: var(--accent); transform: rotate(45deg); box-shadow: 0 0 6px var(--accent);
}

.blog-hero-title {
  font-family: var(--font-display); font-size: 2.8rem; font-weight: 700;
  line-height: 1.15; letter-spacing: 0.02em; margin-bottom: 16px;
  background: linear-gradient(135deg, #d8eaf8 0%, var(--accent) 50%, var(--primary) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 0 18px rgba(142,208,232,0.25));
}

.blog-hero-subtitle {
  font-size: 1rem; line-height: 1.8; color: var(--text-secondary); max-width: 520px;
}

/* Hero Panel — Character Info */
.hero-panel {
  background: var(--bg-card); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg);
  padding: 28px; position: relative;
  box-shadow: var(--shadow-md), var(--shadow-glow);
  transition: var(--transition-smooth);
}
.hero-panel:hover {
  border-color: var(--border-glow-strong);
  box-shadow: var(--shadow-lg), 0 0 28px rgba(91,160,224,0.25), inset 0 1px 0 rgba(255,255,255,0.04);
  transform: translateY(-3px);
}

.panel-hud-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid rgba(91,160,224,0.18);
}
.hud-label { font-family: var(--font-display); font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em; color: var(--text-muted); text-transform: uppercase; }
.hud-value { font-family: var(--font-display); font-size: 0.85rem; font-weight: 500; color: var(--accent); letter-spacing: 0.04em; }

.panel-avatar-row { display: flex; align-items: center; gap: 18px; margin-bottom: 20px; }

.panel-avatar {
  width: 70px; height: 70px; border-radius: 50%; position: relative; overflow: hidden;
  border: 2px solid rgba(142,208,232,0.3); box-shadow: 0 0 22px rgba(91,160,224,0.3); flex-shrink: 0;
}
.panel-avatar img { width: 100%; height: 100%; object-fit: cover; }
.panel-avatar .avatar-placeholder {
  width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, var(--primary), var(--accent)); font-size: 2rem; color: var(--text-primary);
}
.panel-avatar::after {
  content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
  background: conic-gradient(transparent, rgba(142,208,232,0.18), transparent, rgba(91,160,224,0.12), transparent);
  animation: avatar-spin 9s linear infinite;
}
@keyframes avatar-spin { 100% { transform: rotate(360deg); } }

.panel-name-group h3 { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; letter-spacing: 0.04em; color: var(--text-primary); }
.panel-name-group span { font-size: 0.76rem; color: var(--text-muted); font-weight: 500; letter-spacing: 0.05em; }

.hud-data-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(91,160,224,0.07); }
.hud-data-row:last-child { border-bottom: none; }
.hud-data-label { font-size: 0.78rem; color: var(--text-muted); letter-spacing: 0.05em; font-weight: 500; }
.hud-data-val { font-family: var(--font-display); font-size: 0.85rem; color: var(--accent); font-weight: 600; letter-spacing: 0.04em; }

.hud-bar-wrap { margin-top: 16px; }
.hud-bar-label { display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 6px; letter-spacing: 0.06em; text-transform: uppercase; }
.hud-bar { height: 4px; border-radius: 4px; background: rgba(91,160,224,0.1); overflow: hidden; }
.hud-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, var(--primary), var(--accent)); box-shadow: 0 0 8px rgba(91,160,224,0.45); }

/* ============================================================
   Character Showcase
   ============================================================ */
.char-showcase { margin-bottom: 50px; }
.section-label { display: flex; align-items: center; gap: 10px; margin-bottom: 28px; }
.section-label .diamond-dec {
  width: 10px; height: 10px; background: var(--accent); transform: rotate(45deg);
  box-shadow: 0 0 8px var(--accent); }
.section-label span { font-family: var(--font-display); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; color: var(--text-muted); text-transform: uppercase; }

.char-card-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.char-card {
  background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg); padding: 24px;
  text-align: center; transition: var(--transition-smooth); position: relative; overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.char-card::before { content: ''; position: absolute; top:0;left:0;right:0;height:2px; background: linear-gradient(90deg,transparent,var(--accent),transparent); opacity:0; transition: opacity var(--transition-smooth); }
.char-card:hover { border-color: var(--border-glow-strong); box-shadow: var(--shadow-lg), 0 0 28px rgba(91,160,224,0.22); transform: translateY(-4px); }
.char-card:hover::before { opacity: 1; }
.char-card-icon { font-size: 2.2rem; margin-bottom: 12px; display: block; }
.char-card-title { font-family: var(--font-display); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; }
.char-card-value { font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--text-primary); letter-spacing: 0.04em; }

/* ============================================================
   Content Grid — Blog Posts + Sidebar
   ============================================================ */
.content-grid { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: start; }

/* Posts Header */
.posts-header { display: flex; align-items: center; gap: 14px; margin-bottom: 28px; }
.posts-header .diamond-line { flex: 1; height: 1px; background: linear-gradient(90deg, rgba(91,160,224,0.28), transparent); }
.posts-header h2 { font-family: var(--font-display); font-size: 1.45rem; font-weight: 700; letter-spacing: 0.06em; color: var(--text-primary); white-space: nowrap; }

/* Article Cards */
.article-card {
  background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg); padding: 28px;
  margin-bottom: 24px; transition: var(--transition-smooth); position: relative; overflow: hidden;
  box-shadow: var(--shadow-sm);
}
.article-card::before, .article-card::after {
  content: ''; position: absolute; width: 24px; height: 24px;
  border-color: var(--border-glow); border-style: solid; pointer-events: none; transition: var(--transition-smooth); opacity: 0.45;
}
.article-card::before { top: 8px; left: 8px; border-width: 1px 0 0 1px; }
.article-card::after { bottom: 8px; right: 8px; border-width: 0 1px 1px 0; }
.article-card .card-glow-line { position: absolute; top:0;left:0;right:0;height:2px; background: linear-gradient(90deg,transparent,var(--accent),var(--primary),transparent); opacity:0; transition: opacity var(--transition-smooth); }
.article-card:hover .card-glow-line { opacity: 1; }
.article-card:hover { border-color: var(--border-glow-strong); box-shadow: var(--shadow-lg), 0 0 32px rgba(91,160,224,0.2), inset 0 1px 0 rgba(255,255,255,0.04); transform: translateY(-4px); background: var(--bg-card-hover); }
/* Cover image cards — transition to show cover on hover */
.article-card[style*="--card-cover"] { transition: background 0.45s ease, transform var(--transition-smooth), box-shadow var(--transition-smooth), border-color var(--transition-smooth); }
.card-cover-badge { position:absolute;top:10px;right:10px;font-size:0.75rem;opacity:0.35;z-index:1;transition:opacity 0.3s;pointer-events:none; }
.article-card:hover .card-cover-badge { opacity:0; }
.article-card[style*="--card-cover"]:hover { background: linear-gradient(var(--bg-card-hover), var(--bg-card-hover)), var(--card-cover) center/cover; }
.article-card[style*="--card-cover"]:hover::before,
.article-card[style*="--card-cover"]:hover::after { border-color: var(--border-glow-strong); }
.article-card[style*="--card-cover"]:hover .card-title-link h3 { text-shadow: 0 1px 8px rgba(8,24,40,0.8); }
.article-card[style*="--card-cover"]:hover .card-meta,
.article-card[style*="--card-cover"]:hover .card-tags span,
.article-card[style*="--card-cover"]:hover p { color: rgba(220,240,255,0.85); }
.article-card:hover::before, .article-card:hover::after { border-color: var(--border-glow-strong); opacity: 1; }

.card-meta { display: flex; align-items: center; gap: 16px; margin-bottom: 14px; font-size: 0.76rem; color: var(--text-muted); letter-spacing: 0.04em; }
.card-meta .meta-cat { background: rgba(91,160,224,0.08); color: var(--primary); padding: 3px 10px; border-radius: 50px; font-weight: 600; font-size: 0.7rem; letter-spacing: 0.05em; border: 1px solid rgba(91,160,224,0.18); }
.card-meta .meta-date { display: flex; align-items: center; gap: 4px; }
.card-meta .meta-date::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: var(--text-muted); }

.card-title-link { text-decoration: none; }
.article-card h3 { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; letter-spacing: 0.03em; transition: color var(--transition-smooth); line-height: 1.35; }
.article-card:hover h3 { color: var(--accent); }
.article-card p { color: var(--text-secondary); font-size: 0.9rem; line-height: 1.75; margin-bottom: 16px; }

.card-footer-row { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid rgba(91,160,224,0.09); }
.card-tags { display: flex; gap: 8px; flex-wrap: wrap; }
.card-tags span { font-size: 0.7rem; color: var(--text-muted); letter-spacing: 0.04em; }
.card-tags span::before { content: '#'; color: var(--accent); }
.card-read-more { font-family: var(--font-display); font-size: 0.8rem; font-weight: 600; color: var(--accent); text-decoration: none; letter-spacing: 0.06em; transition: var(--transition-smooth); display: flex; align-items: center; gap: 6px; }
.card-read-more .arrow { display: inline-block; transition: transform var(--transition-smooth); }
.card-read-more:hover { color: var(--primary); text-shadow: 0 0 12px rgba(91,160,224,0.45); }
.card-read-more:hover .arrow { transform: translateX(4px); }

/* Gallery thumb hover */
.gallery-thumb:hover { border-color:var(--border-glow-strong); box-shadow:0 0 12px rgba(91,160,224,0.2); }
.gallery-thumb:hover img { transform:scale(1.08); }

/* Pagination */
.pagination { display:flex; justify-content:center; align-items:center; gap:8px;
  margin-top:32px; padding:20px 0; }
.pagination a, .pagination span { display:inline-flex; align-items:center; justify-content:center;
  min-width:36px; height:36px; padding:0 8px; font-family:var(--font-display); font-size:0.78rem;
  font-weight:600; letter-spacing:0.04em; border-radius:50px; text-decoration:none;
  transition:var(--transition-smooth); }
.pagination a { color:var(--text-secondary); border:1px solid rgba(91,160,224,0.15); }
.pagination a:hover { border-color:var(--accent); color:var(--accent);
  background:rgba(91,160,224,0.08); }
.pagination .current { color:#fff; background:var(--primary); border-color:var(--primary);
  box-shadow:0 0 12px rgba(91,160,224,0.3); }
.pagination .disabled { color:var(--text-haze); border-color:transparent; pointer-events:none; }

/* ============================================================
   Sidebar
   ============================================================ */
.sidebar { position: sticky; top: 86px; }
.sidebar-widget {
  background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg); padding: 24px;
  margin-bottom: 24px; box-shadow: var(--shadow-sm); transition: var(--transition-smooth); position: relative;
}
.sidebar-widget:hover { border-color: var(--border-glow-strong); box-shadow: var(--shadow-md), inset 0 1px 0 rgba(255,255,255,0.04); }

.widget-title { font-family: var(--font-display); font-size: 0.78rem; font-weight: 700; letter-spacing: 0.1em; color: var(--accent); text-transform: uppercase; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 1px solid rgba(91,160,224,0.13); display: flex; align-items: center; gap: 8px; }
.widget-title .diamond-sm { width: 6px; height: 6px; background: var(--accent); transform: rotate(45deg); box-shadow: 0 0 4px var(--accent); }

.about-avatar-wrap { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
.about-avatar {
  width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0; overflow: hidden;
  box-shadow: 0 0 16px rgba(91,160,224,0.2); border: 2px solid rgba(91,160,224,0.2);
}
.about-avatar img { width: 100%; height: 100%; object-fit: cover; }
.about-avatar .avatar-placeholder-sm {
  width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, var(--secondary), var(--primary)); font-size: 1.3rem; color: var(--text-primary);
}

.about-name-stack strong { display: block; font-size: 1rem; color: var(--text-primary); }
.about-name-stack span { font-size: 0.7rem; color: var(--text-muted); letter-spacing: 0.04em; }
.about-bio { font-size: 0.82rem; line-height: 1.75; color: var(--text-secondary); margin-bottom: 14px; }
.about-hud-mini { display: flex; gap: 16px; font-size: 0.7rem; color: var(--text-muted); letter-spacing: 0.04em; }
.about-hud-mini strong { color: var(--accent); font-family: var(--font-display); font-size: 0.85rem; }

/* Social icon links */
.social-links-row { display:flex;gap:10px;margin-top:16px;justify-content:center }
.social-icon-link { color:var(--text-secondary);text-decoration:none;font-size:0.7rem;
  padding:3px 10px;border:1px solid var(--border-glow);border-radius:var(--radius-pill);
  font-family:var(--font-display);letter-spacing:0.04em;transition:var(--transition-smooth); }
.social-icon-link:hover { color:var(--accent);border-color:var(--accent);
  background:rgba(91,160,224,0.08); }

.tag-cloud { display: flex; flex-wrap: wrap; gap: 8px; }
.tag-cloud a { display: inline-block; padding: 5px 14px; background: rgba(91,160,224,0.06); border: 1px solid rgba(91,160,224,0.13); border-radius: 50px; font-size: 0.73rem; color: var(--text-secondary); text-decoration: none; letter-spacing: 0.03em; transition: var(--transition-smooth); }
.tag-cloud a:hover { background: rgba(91,160,224,0.13); border-color: var(--border-glow-strong); color: var(--accent); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(91,160,224,0.16); }

/* Friend links */
.friend-links { display:flex; flex-direction:column; gap:4px; }
.friend-links a { display:block; padding:8px 12px; font-size:0.78rem; color:var(--text-secondary);
  text-decoration:none; border-radius:var(--radius-sm); transition:var(--transition-smooth); }
.friend-links a:hover { background:rgba(91,160,224,0.08); color:var(--accent); padding-left:16px; }

/* Tag filter bar */
.tag-filter-bar { display:flex; align-items:center; gap:12px; padding:10px 16px;
  margin-bottom:20px; background:rgba(91,160,224,0.06); border:1px solid var(--border-glow);
  border-radius:var(--radius-pill); font-size:0.78rem; color:var(--text-secondary);
  font-family:var(--font-display); letter-spacing:0.03em; }
.tag-filter-bar strong { color:var(--accent); }
.tag-filter-clear { color:var(--secondary); text-decoration:none; font-size:0.7rem;
  font-weight:600; letter-spacing:0.04em; margin-left:auto; }
.tag-filter-clear:hover { text-decoration:underline; }
.card-tags span { cursor:pointer; transition:var(--transition-smooth); }
.card-tags span:hover { color:var(--accent); }

/* ============================================================
   Responsive
   ============================================================ */
@media (max-width: 1024px) {
  .blog-hero { grid-template-columns: 1fr; gap: 30px; }
  .hero-panel { max-width: 500px; }
  .content-grid { grid-template-columns: 1fr; }
  .sidebar { position: static; top: auto; }
  .char-card-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .blog-hero-title { font-size: 2rem; }
  .char-card-grid { grid-template-columns: 1fr 1fr; }
  .footer-inner { flex-direction: column; text-align: center; }
  .hero-wallpaper .hero-line1 { font-size: 1.8rem; }
}
@media (max-width: 480px) {
  .blog-hero-title { font-size: 1.6rem; }
  .char-card-grid { grid-template-columns: 1fr; }
  .hero-panel { padding: 18px; }
}
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>

<!-- Particle Ocean — Deep Sea Bioluminescent Visualization -->
<canvas class="particle-ocean" id="particleOcean"></canvas>
<canvas class="sparkle-layer" id="sparkleCanvas"></canvas>


<!-- ============================================================
     Wallpaper Hero — Full Screen
     ============================================================ -->
<section class="hero-wallpaper" id="top">
  <div class="wallpaper-bg"></div>
  <div class="wallpaper-fallback"></div>
  <div class="wallpaper-shimmer"></div>
  <div class="caustic-overlay"></div>

  <div class="hero-text-center">
    <h1 class="hero-line1"><span class="en">Hello</span><br><span class="en-sub">Welcome to Cola's blog</span></h1>
    <p class="hero-line2">"The best way to predict the future is to invent it." <span style="font-style:normal;">- Alan Kay</span></p>
  </div>

</section>

<!-- ============================================================
     SVG Wave Transition
     ============================================================ -->
<div class="wave-divider">
  <svg class="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
    <defs>
      <path id="ocean-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z"></path>
    </defs>
    <g class="wave-parallax">
      <use href="#ocean-wave" x="48" y="0" fill="rgba(91,160,224,0.28)"></use>
      <use href="#ocean-wave" x="48" y="2" fill="rgba(55,110,165,0.5)"></use>
      <use href="#ocean-wave" x="48" y="4" fill="rgba(22,55,88,0.78)"></use>
      <use href="#ocean-wave" x="48" y="6" style="fill:var(--bg-deep)"></use>
    </g>
  </svg>
</div>

<!-- ============================================================
     Navbar (hidden initially, shows on scroll past hero)
     ============================================================ -->
<?php $navActive = 'home'; require __DIR__ . '/includes/navbar.php'; ?>

<!-- Section divider -->
<div class="section-divider" id="blog-start">
  <span class="divider-line"></span>
</div>

<!-- ============================================================
     Blog Content
     ============================================================ -->
<div class="blog-section">

  <?php
  $galleryDir = __DIR__ . '/assets/images/gallery/';
  $slides = [];
  if (is_dir($galleryDir)) {
    for ($i = 1; $i <= 20; $i++) {
      foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $f = $galleryDir . "slide-" . str_pad($i, 2, '0', STR_PAD_LEFT) . '.' . $ext;
        if (file_exists($f)) { $slides[] = 'assets/images/gallery/' . basename($f); break; }
      }
    }
  }
  ?>

  <!-- Blog Hero -->
  <section class="blog-hero">
    <div class="blog-hero-content">
      <div class="hero-badge section-reveal" style="transition-delay:0ms">
        <span class="diamond"></span>
        DEPTH 0x0028 // OCEAN LINK ACTIVE
      </div>
      <h2 class="blog-hero-title section-reveal" style="transition-delay:100ms">深海之下，别有洞天</h2>
      <p class="blog-hero-subtitle section-reveal" style="transition-delay:200ms">
        潜入代码的深海，在寂静中寻找思维的涟漪。这里是可乐的水下基地——每一行代码都是一次深潜。
      </p>

      <!-- Local Music Player -->
      <div class="music-hero section-reveal" id="musicHero" style="flex:1;min-height:0;max-width:100%;margin-top:18px;transition-delay:300ms">
        <div class="music-hero-inner">
          <div class="music-hero-header">
            <span class="diamond-sm"></span> Deep Sea Frequency / 深海频率
          </div>
          <!-- Cover + Lyrics row -->
          <div class="music-cover-row">
            <div class="music-cover-wrap" onclick="togglePlay()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); togglePlay(); }" role="button" tabindex="0" title="Play/Pause" aria-label="Play or pause music">
              <div class="music-cover-disc" id="musicCoverDisc">
                <div class="music-cover-inner"></div>
              </div>
            </div>
            <div class="music-lyrics" id="musicLyrics">
              <p style="color:var(--text-muted)">Select a song to begin</p>
              <p style="color:var(--text-muted)">选择歌曲开始</p>
              <p>&nbsp;</p>
            </div>
          </div>
          <!-- Progress + Volume row -->
          <div class="music-progress-wrap" id="musicProgressWrap">
            <span class="music-time" id="musicCurTime">0:00</span>
            <div class="music-progress-bar" id="musicProgressBar" role="slider" tabindex="0" aria-label="Music progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
              <div class="music-progress-fill" id="musicProgressFill"></div>
              <div class="music-progress-thumb" id="musicProgressThumb"></div>
            </div>
            <span class="music-time" id="musicDurTime">0:00</span>
          </div>
          <!-- Controls row: prev | play/pause | next | mode | volume -->
          <div class="music-ctrls">
            <button class="music-ctrl-btn" onclick="playLocal(currentIdx-1)" title="Previous">&#9664;&#9664;</button>
            <button class="music-ctrl-btn music-ctrl-play" id="musicCtrlPlay" onclick="togglePlay()" aria-label="Play music">&#9654;</button>
            <button class="music-ctrl-btn" onclick="playLocal(currentIdx+1)" title="Next">&#9654;&#9654;</button>
            <button class="music-ctrl-btn music-ctrl-mode" id="musicModeBtn" onclick="cycleMode()" title="List loop">ALL</button>
            <span style="font-size:0.6rem;color:var(--text-muted);margin-left:4px">Vol</span>
            <input type="range" id="musicVolume" min="0" max="100" value="40" style="width:56px;flex-shrink:0">
          </div>
          <audio id="musicAudio" style="display:none"></audio>
          <!-- Song list (scrollable) -->
          <div class="music-results" id="musicResults">
            <p style="font-size:0.7rem;color:var(--text-muted);text-align:center;padding:8px">Loading playlist... / 加载歌单中...</p>
          </div>
        </div>
      </div>
    </div>

    <div class="blog-hero-right">
      <!-- Personal Info Panel -->
      <div class="hero-panel section-reveal" style="transition-delay:400ms">
      <div class="panel-hud-header">
        <span class="hud-label">SYS.PROFILE</span>
        <span class="hud-value">STATUS: <?= $isLoggedIn ? 'AUTHENTICATED' : 'ONLINE' ?></span>
      </div>
      <div class="panel-avatar-row">
        <div class="panel-avatar">
          <?php if (file_exists(__DIR__ . '/assets/images/my-avatar.jpg')): ?>
            <img src="assets/images/my-avatar.jpg" alt="可乐">
          <?php else: ?>
            <span class="avatar-placeholder">C</span>
          <?php endif; ?>
        </div>
        <div class="panel-name-group">
          <h3>Cola_CaO</h3>
          <span>// 可乐 · CS @ 杭师大 · CTF & SRC</span>
        </div>
      </div>
      <div class="hud-data-row">
        <span class="hud-data-label">ROLE</span>
        <span class="hud-data-val">CS Student · CTF Player</span>
      </div>
      <div class="hud-data-row">
        <span class="hud-data-label">FOCUS</span>
        <span class="hud-data-val">Web Security · SRC · Backend</span>
      </div>
      <!-- Skill Bars (§4.9: mock data, replace with real skill levels) -->
      <div class="hud-bar-wrap">
        <div class="hud-bar-label"><span>Web 前端</span><span>90%</span></div>
        <div class="hud-bar"><div class="hud-bar-fill" style="width:90%;"></div></div>
      </div>
      <div class="hud-bar-wrap" style="margin-top:10px;">
        <div class="hud-bar-label"><span>后端开发</span><span>78%</span></div>
        <div class="hud-bar"><div class="hud-bar-fill" style="width:78%;"></div></div>
      </div>
      <div class="hud-bar-wrap" style="margin-top:10px;">
        <div class="hud-bar-label"><span>网络安全</span><span>60%</span></div>
        <div class="hud-bar"><div class="hud-bar-fill" style="width:60%;"></div></div>
      </div>
      <div class="hud-bar-wrap" style="margin-top:10px;">
        <div class="hud-bar-label"><span>开源贡献</span><span>65%</span></div>
        <div class="hud-bar"><div class="hud-bar-fill" style="width:65%;"></div></div>
      </div>
      <!-- Social links row -->
      <div class="social-links-row">
        <a href="https://github.com/Cola-Ca0" target="_blank" rel="noopener" class="social-icon-link" title="GitHub">GitHub</a>
        <a href="mailto:458756060@qq.com" class="social-icon-link" title="Email">Email</a>
        <a href="https://space.bilibili.com/629007860" target="_blank" rel="noopener" class="social-icon-link" title="Bilibili">Bilibili</a>
        <a href="/blog/feed.xml" class="social-icon-link" title="RSS">RSS</a>
      </div>

      <!-- Skill Radar Chart (dynamic from about-content.json) -->
      <?php
      // Map JSON skills to radar: take first 6 skills, compute polygon
      $radarLabels = [];
      $radarValues = [];
      foreach (array_slice($radarSkills, 0, 6) as $sk) {
        $radarLabels[] = $sk['name'];
        $radarValues[] = intval($sk['level'] ?? 50);
      }
      // Pad to 6 if fewer
      while (count($radarValues) < 6) { $radarLabels[] = 'Skill'; $radarValues[] = 50; }
      // Compute polygon points for 6-axis radar (200x200, center 100,100, max radius 84)
      $points = [];
      $dots = [];
      for ($i = 0; $i < 6; $i++) {
        $angle = deg2rad(-90 + $i * 60); // start top, clockwise
        $r = ($radarValues[$i] / 100) * 84;
        $x = 100 + $r * cos($angle);
        $y = 100 + $r * sin($angle);
        $points[] = round($x, 1) . ',' . round($y, 1);
        $dots[] = ['x' => round($x, 1), 'y' => round($y, 1)];
      }
      // Label positions (at max radius + margin)
      $labelPositions = [];
      for ($i = 0; $i < 6; $i++) {
        $angle = deg2rad(-90 + $i * 60);
        $lx = 100 + 96 * cos($angle);
        $ly = 100 + 96 * sin($angle);
        $anchor = ($lx < 90) ? 'end' : (($lx > 110) ? 'start' : 'middle');
        $labelPositions[] = ['x' => round($lx, 1), 'y' => round($ly, 1) + 3, 'anchor' => $anchor];
      }
      ?>
      <div class="radar-card">
        <div class="radar-title"><span class="diamond-sm"></span> Skill Matrix / 技能雷达</div>
        <div class="radar-chart" id="radarChart">
          <svg viewBox="0 0 200 200" class="radar-svg">
            <circle cx="100" cy="100" r="30" fill="none" stroke="var(--border-glow)" stroke-width="0.5"/>
            <circle cx="100" cy="100" r="58" fill="none" stroke="var(--border-glow)" stroke-width="0.5"/>
            <circle cx="100" cy="100" r="84" fill="none" stroke="var(--border-glow)" stroke-width="0.5"/>
            <?php for ($i = 0; $i < 6; $i++): $a = deg2rad(-90 + $i * 60); ?>
            <line x1="<?= 100 + 84 * cos($a) ?>" y1="<?= 100 + 84 * sin($a) ?>" x2="100" y2="100" stroke="var(--border-glow)" stroke-width="0.5"/>
            <?php endfor; ?>
            <polygon points="<?= implode(' ', $points) ?>" fill="rgba(91,160,224,0.15)" stroke="var(--accent)" stroke-width="1.5" class="radar-shape"/>
            <?php foreach ($dots as $d): ?>
            <circle cx="<?= $d['x'] ?>" cy="<?= $d['y'] ?>" r="3" fill="var(--accent)"/>
            <?php endforeach; ?>
            <?php foreach ($labelPositions as $i => $lp): ?>
            <text x="<?= $lp['x'] ?>" y="<?= $lp['y'] ?>" text-anchor="<?= $lp['anchor'] ?>" fill="var(--text-muted)" font-size="8" font-family="var(--font-display)"><?= htmlspecialchars($radarLabels[$i]) ?></text>
            <?php endforeach; ?>
          </svg>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick Stats (§4.9: mock data placeholder) -->
  <section class="char-showcase">
    <div class="section-label">
      <span class="diamond-dec"></span>
      <span>System Metrics / 站点统计</span>
    </div>
    <div class="char-card-grid">
      <div class="char-card">
        <span class="char-card-icon">&#9998;</span>
        <div class="char-card-title">ARTICLES</div>
        <div class="char-card-value">42</div>
      </div>
      <div class="char-card">
        <span class="char-card-icon">&#9881;</span>
        <div class="char-card-title">PROJECTS</div>
        <div class="char-card-value">12</div>
      </div>
      <div class="char-card">
        <span class="char-card-icon">&#9733;</span>
        <div class="char-card-title">STARS</div>
        <div class="char-card-value">1.2k</div>
      </div>
    </div>
  </section>

  <!-- Content Grid -->
  <div class="content-grid">
    <div class="posts-column">
      <div class="posts-header">
        <span class="diamond-line"></span>
        <h2>LATEST TRANSMISSIONS</h2>
        <span class="diamond-line"></span>
      </div>

      <div class="posts-grid" id="postsGrid">
        <p style="color:var(--text-muted);text-align:center;padding:40px">Loading transmissions... / 加载信号中...</p>
      </div>

      <!-- Pagination -->
      <nav class="pagination" id="pagination" style="display:none"></nav>
    </div>

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> About / 关于我</h3>
        <div class="about-avatar-wrap">
          <div class="about-avatar">
            <?php if (file_exists(__DIR__ . '/assets/images/my-avatar.jpg')): ?>
              <img src="assets/images/my-avatar.jpg" alt="可乐">
            <?php else: ?>
              <span class="avatar-placeholder-sm">C</span>
            <?php endif; ?>
          </div>
          <div class="about-name-stack">
            <strong>Cola_CaO</strong>
            <span>// 可乐</span>
          </div>
        </div>
        <p class="about-bio">杭州师范大学 · CS 2026级新生。方向：网络空间安全，CTF + SRC 漏洞挖掘。热爱二次元文化，在 Obsidian 中构建知识库，用代码探索世界的底层逻辑。</p>
        <div class="about-hud-mini">
          <div><strong>CTF</strong> TRAINING</div>
          <div><strong>SRC</strong> HUNTING</div>
          <div><strong>SHARK</strong> MODE</div>
        </div>
      </div>

      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Tags / 标签云</h3>
        <div class="tag-cloud" id="tagCloud">
          <span style="font-size:0.7rem;color:var(--text-muted)">Loading tags... / 加载标签中...</span>
        </div>
      </div>

      <!-- Mini Gallery Preview -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Gallery / 图库</h3>
        <?php
        $allImages = [];
        if (is_dir($galleryDir)) {
          $files = glob($galleryDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
          foreach ($files as $f) { $allImages[] = 'assets/images/gallery/' . basename($f); }
        }
        $preview = array_slice($slides, 0, 4);
        ?>
        <?php if (count($allImages) > 0): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px">
          <?php foreach (array_slice($allImages, 0, 4) as $img): ?>
          <div class="gallery-thumb" style="aspect-ratio:1;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border-glow);transition:var(--transition-smooth);cursor:pointer">
            <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:100%;height:100%;object-fit:cover;transition:transform 0.3s" loading="lazy">
          </div>
          <?php endforeach; ?>
        </div>
        <a href="gallery.php" style="display:block;text-align:center;font-family:var(--font-display);font-size:0.72rem;color:var(--accent);text-decoration:none;letter-spacing:0.06em;padding:6px;border:1px solid var(--border-glow);border-radius:var(--radius-pill);transition:var(--transition-smooth)">
          View all <?= count($allImages) ?> images / 查看全部
        </a>
        <?php else: ?>
        <p style="font-size:0.72rem;color:var(--text-muted);text-align:center;padding:20px 0">Drop images into assets/images/gallery/</p>
        <?php endif; ?>
      </div>

      <!-- Site running time -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Uptime / 运行时间</h3>
        <p id="siteUptime" style="font-family:var(--font-display);font-size:0.85rem;color:var(--accent);letter-spacing:0.03em;text-align:center">--</p>
      </div>

      <!-- Hitokoto 一言 -->
      <div class="sidebar-widget" id="hitokotoWidget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Hitokoto / 一言</h3>
        <p id="hitokotoText" style="font-size:0.82rem;color:var(--text-secondary);line-height:1.8;margin-bottom:6px;font-style:italic;min-height:2.5em">Loading...</p>
        <p id="hitokotoFrom" style="font-size:0.68rem;color:var(--text-haze);text-align:right"></p>
      </div>

      <!-- Friend Links -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Links / 友链</h3>
        <div class="friend-links">
          <a href="https://github.com" target="_blank" rel="noopener">GitHub</a>
          <a href="https://www.bilibili.com" target="_blank" rel="noopener">Bilibili</a>
          <a href="https://moejue.cn" target="_blank" rel="noopener">Moejue</a>
          <a href="https://xz.aliyun.com" target="_blank" rel="noopener">先知社区</a>
        </div>
      </div>

    </aside>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<!-- ============================================================
     Scripts
     ============================================================ -->
<script>
// Navbar visibility via IntersectionObserver (§5.D: no scroll listeners)
(function() {
  var navbar = document.getElementById('navbar');
  var blogStart = document.getElementById('blog-start');
  if (!navbar || !blogStart) return;

  var observer = new IntersectionObserver(function(entries) {
    navbar.classList.toggle('visible', !entries[0].isIntersecting);
  }, { threshold: 0, rootMargin: '-60px 0px 0px 0px' });

  observer.observe(blogStart);
})();

// Smooth scroll for nav links
document.querySelectorAll('a[href^="#"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});

// Section reveal observer
(function() {
  var reveals = document.querySelectorAll('.section-reveal');
  if (!reveals.length) return;
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  reveals.forEach(function(el) { observer.observe(el); });
})();

// Site uptime counter
(function() {
  var el = document.getElementById('siteUptime');
  if (!el) return;
  var start = new Date('2026-08-06');
  function tick() {
    var now = new Date();
    var diff = now - start;
    var days = Math.floor(diff / 86400000);
    var hours = Math.floor((diff % 86400000) / 3600000);
    var mins = Math.floor((diff % 3600000) / 60000);
    el.textContent = days + 'd ' + hours + 'h ' + mins + 'm';
  }
  tick();
  setInterval(tick, 60000);
})();

// Hitokoto 一言
(function() {
  var textEl = document.getElementById('hitokotoText');
  var fromEl = document.getElementById('hitokotoFrom');
  if (!textEl) return;

  function fetchHitokoto() {
    textEl.textContent = 'Loading...';
    fromEl.textContent = '';
    var ctrl = new AbortController();
    var t = setTimeout(function(){ ctrl.abort(); }, 8000);
    fetch('https://v1.hitokoto.cn/?c=a&c=b&c=c&c=d&c=i&c=k', { signal: ctrl.signal })
      .then(function(r){ clearTimeout(t); return r.json(); })
      .then(function(d){
        textEl.textContent = d.hitokoto;
        var from = d.from_who ? d.from_who + ' · ' + d.from : d.from;
        fromEl.textContent = '—— ' + (from || '佚名');
      })
      .catch(function(){
        clearTimeout(t);
        textEl.textContent = '深海之下，别有洞天';
        fromEl.textContent = '—— Cola_CaO';
      });
  }

  fetchHitokoto();
  textEl.style.cursor = 'pointer';
  textEl.title = 'Click to refresh / 点击刷新一言';
  textEl.addEventListener('click', fetchHitokoto);
})();

</script>

<script src="js/particle-ocean.js"></script>
<script src="js/sparkles.js?v=3"></script>
<script src="js/music-player.js"></script>
<script src="js/post-loader.js"></script>
</body>
</html>
