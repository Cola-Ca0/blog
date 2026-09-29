<?php
/**
 * Cola_CaO 博客主页
 * 功能：全屏壁纸Hero、登录状态检测、博客内容展示、图廊轮播
 */
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/config.php'; // 站点地址唯一来源 (2026-09-14 上线准备)
require __DIR__ . '/includes/markdown.php'; // 深海密件区列表 (2026-09-10)
// Load skills from about-content.json for radar chart
$aboutJson = json_decode(file_get_contents(__DIR__ . '/about-content.json'), true) ?: [];
$radarSkills = $aboutJson['skills'] ?? [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = 'Cola_CaO · 深海之下，别有洞天';
$pageDesc = '可乐的水下研究站 — CS/网络安全/CTF。在深海中记录学习轨迹，分享安全探索与代码思考。';
$extraHead = '<meta property="og:title" content="Cola_CaO · 深海之下，别有洞天">
<meta property="og:description" content="可乐的水下研究站 — CS/网络安全/CTF。在深海中记录学习轨迹。">
<meta property="og:type" content="website">
<meta property="og:url" content="' . $SITE_URL . '/">';
require __DIR__ . '/includes/head.php';
?>
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
/* Hero sonar pulse (2026-08-16 用户选定) — 低频声呐涟漪; reduced-motion 由 shared.css 全局规则自动降级 */
.hero-sonar {
  position: absolute;
  left: 50%; top: 74%;
  width: 10px; height: 10px; margin: -5px 0 0 -5px;
  border-radius: 50%;
  border: 1px solid rgba(91, 160, 224, 0.5);
  box-shadow: 0 0 14px rgba(91, 160, 224, 0.3), inset 0 0 8px rgba(91, 160, 224, 0.25);
  pointer-events: none;
  opacity: 0;
  animation: sonar-ping 7s ease-out infinite;
}
@keyframes sonar-ping {
  0%   { opacity: 0; transform: scale(0.15); }
  5%   { opacity: 0.65; }
  100% { opacity: 0; transform: scale(30); }
}

/* 生物荧光呼吸 (2026-08-16 用户选定) — 侧栏一言卡片缓慢明暗 */
#hitokotoWidget { position: relative; overflow: hidden; }
#hitokotoWidget::after {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(ellipse at 50% 100%, rgba(91,160,224,0.12), transparent 70%);
  animation: bio-breathe 5.5s ease-in-out infinite;
}
@keyframes bio-breathe { 0%,100%{opacity:0.15} 50%{opacity:0.6} }


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
  margin-bottom: 22px;
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
  font-size: clamp(0.78rem, 1.2vw, 0.9rem);   /* 2026-08-16 用户选定: 小一号, 与 Hello 拉开节奏 */
  font-weight: 300;
  font-style: italic;
  letter-spacing: 0.03em;
  line-height: 1.5;            /* §4.1: italic descender clearance for y/p/g/j/q */
  padding-bottom: 4px;         /* reserve space so descenders don't clip */
  color: rgba(180,210,235,0.68);  /* 2026-08-21 审查: 0.58→0.68 可读性 (对比 ~2.5→3.4:1) */
  text-shadow: 0 1px 12px rgba(0,0,0,0.4);
  animation: hero-text-in 1.4s ease-out;
}
[data-theme="light"] .hero-text-center .hero-line2 { color: rgba(26,48,64,0.72); }

/* 打字机光标 (2026-08-27 用户拍板) — 与 hero 同色系, reduced-motion 由 shared.css 全局降级 */
.hero-text-center .hero-line2 .tw-cursor {
  display: inline-block;
  margin-left: 1px;
  animation: tw-blink 0.9s steps(1) infinite;
}
@keyframes tw-blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

@keyframes hero-text-in {
  from { opacity: 0; transform: translateY(50px); filter: blur(6px); }
  to { opacity: 1; transform: translateY(0); filter: blur(0); }
}

/* ============================================================
   Navbar — Glass + Blur
   2026-08-16 去重: 公共 navbar 规则统一走 shared.css;
   本页仅保留首页专属的「滚过 Hero 才现身」行为。
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
  cursor:pointer;font-size:0.6rem;transition:var(--transition-smooth);
  position:relative;
}
/* 触控热区扩展至 44px (ui-ux-pro-max §2 touch-target-size; 视觉尺寸不变) */
.music-ctrl-btn::before { content:''; position:absolute; inset:-8px; }
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
.music-result-item .play-btn:hover { background:var(--primary);color:var(--text-primary);border-color:var(--primary) }

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
.content-grid { display: grid; grid-template-columns: 1fr 340px; gap: 40px; align-items: stretch; } /* 2026-08-27 用户: 左右栏等高 */
.posts-column { display: flex; flex-direction: column; }
.posts-grid { flex: 1; } /* 文章区吃满高度, pagination 落底, 与侧栏对齐 */

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
/* 封面右图 (2026-08-27 用户拍板, 参照 mizuki: 图片右边栏不占整行) */
.article-card:has(.card-cover-side) { display:grid; grid-template-columns: 1fr 96px; gap: 18px; align-items: center; }
.card-body { min-width: 0; }
.card-cover-side { height:88px; overflow:hidden; border-radius:var(--radius-sm); margin:4px 0; align-self:center; }
.card-cover-side img { width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.5s ease; }
.article-card:hover .card-cover-side img { transform:scale(1.08); }
/* 2026-08-27 用户: 移除 hover 封面背景浮现 (封面只留右侧图栏) */
.article-card[style*="--card-cover"]:hover { background: var(--bg-card-hover); }
.article-card[style*="--card-cover"]:hover::before,
.article-card[style*="--card-cover"]:hover::after { border-color: var(--border-glow-strong); }
.article-card:hover::before, .article-card:hover::after { border-color: var(--border-glow-strong); opacity: 1; }

.card-meta { display: flex; align-items: center; gap: 16px; margin-bottom: 14px; font-size: 0.76rem; color: var(--text-muted); letter-spacing: 0.04em; }
.card-meta .meta-cat { background: rgba(91,160,224,0.08); color: var(--primary); padding: 3px 10px; border-radius: 50px; font-weight: 600; font-size: 0.7rem; letter-spacing: 0.05em; border: 1px solid rgba(91,160,224,0.18); }
.card-meta .meta-date { display: flex; align-items: center; gap: 4px; }
.meta-comments { font-size:0.7rem;color:var(--text-haze);margin-left:auto; }
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
.pagination .current { color:var(--text-primary); background:var(--primary); border-color:var(--primary);
  box-shadow:0 0 12px rgba(91,160,224,0.3); }
.pagination .disabled { color:var(--text-haze); border-color:transparent; pointer-events:none; }

/* ============================================================
   Sidebar
   ============================================================ */
.sidebar { position: sticky; top: 86px; }
.sidebar-widget {
  background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--border-glow); border-radius: var(--radius-lg); padding: 24px;
  margin-bottom: 28px; box-shadow: var(--shadow-sm); transition: var(--transition-smooth); position: relative;
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
  background: linear-gradient(135deg, var(--primary), var(--accent)); font-size: 1.3rem; color: var(--text-primary);
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

/* ============================================================
   Latest Signals / 最新信号 — 侧栏组件 (2026-08 用户拍板: 低频安静, 状态点 3s 脉冲, hover 光晕 0.35)
   ============================================================ */
.signal-list { display:flex; flex-direction:column; gap:2px; }
.signal-item {
  display:flex; align-items:flex-start; gap:10px; text-decoration:none;
  padding:8px 10px; border-radius:var(--radius-sm); border:1px solid transparent;
  transition:var(--transition-smooth);
}
.signal-item:hover { background:rgba(91,160,224,0.08); border-color:rgba(91,160,224,0.35); box-shadow:0 0 12px rgba(91,160,224,0.35); }
.signal-dot {
  flex-shrink:0; width:7px; height:7px; margin-top:6px; border-radius:50%;
  background:var(--accent); box-shadow:0 0 6px rgba(142,208,232,0.5);
  animation:signal-pulse 3s ease-in-out infinite;
}
@keyframes signal-pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.45; transform:scale(0.82); } }
.signal-body { display:flex; flex-direction:column; gap:2px; min-width:0; }
.signal-meta { font-family:var(--font-display); font-size:0.68rem; color:var(--accent); letter-spacing:0.04em; }
.signal-text { font-size:0.75rem; color:var(--text-secondary); line-height:1.5; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.signal-item:hover .signal-text { color:var(--text-primary); }

/* ============================================================
   Dive Calendar / 深潜日历 — 侧栏组件 (2026-08 用户拍板: HUD 热力图, glob 直读零缓存, 今日低频脉冲 3s)
   ============================================================ */
.dive-cal-head { display:flex; justify-content:space-between; font-family:var(--font-display);
  font-size:0.68rem; color:var(--text-muted); letter-spacing:0.06em; margin-bottom:10px; }
.dive-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
.dive-cal-dow { text-align:center; font-size:0.6rem; color:var(--text-haze); font-family:var(--font-display); padding-bottom:2px; }
.dive-cal-cell {
  aspect-ratio:1; display:flex; align-items:center; justify-content:center;
  font-size:0.66rem; color:var(--text-muted); border-radius:var(--radius-sm);
  border:1px solid transparent; transition:var(--transition-smooth);
}
/* 2026-09-10: 四段强度热力图 (--viz-* 顺序色阶, 科研冰雪蓝阶; 深浅主题各自反转) */
.dive-cal-cell.posted:hover { filter:brightness(1.15); box-shadow:0 0 12px rgba(91,160,224,0.35); }
.dive-cal-cell.lv1 { color:var(--viz-1-ink); background:var(--viz-1-bg); }
.dive-cal-cell.lv2 { color:var(--viz-2-ink); background:var(--viz-2-bg); }
.dive-cal-cell.lv3 { color:var(--viz-3-ink); background:var(--viz-3-bg); box-shadow:0 0 10px rgba(142,208,232,0.25); }
.dive-cal-cell.today { color:var(--bg-deep); background:var(--primary); border-color:var(--primary);
  font-weight:600; animation:signal-pulse 3s ease-in-out infinite; }

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
  /* 封面右图 <768px 回顶部横幅 (2026-08-27) */
  .article-card:has(.card-cover-side) { grid-template-columns: 1fr; gap: 12px; }
  .card-cover-side { height:110px; width:100%; }
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
  <div class="hero-sonar" aria-hidden="true"></div>

  <div class="hero-text-center">
    <h1 class="hero-line1"><span class="en">Hello</span><br><span class="en-sub">Welcome to Cola's blog</span></h1>
    <p class="hero-line2 typewriter">"今天也要向深海，发一条温柔的信号。" <span style="font-style:normal;">— 深海研究站日志</span></p>
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
  $allImages = $slides = [];
  if (is_dir($galleryDir)) {
    $files = glob($galleryDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    foreach ($files as $f) {
      $name = basename($f);
      $allImages[] = 'assets/images/gallery/' . $name;
      if (preg_match('/^slide-\d{2}\./', $name)) $slides[] = 'assets/images/gallery/' . $name;
    }
  }
  sort($slides);
  ?>

  <!-- Blog Hero -->
  <section class="blog-hero">
    <div class="blog-hero-content">
      <div class="hero-badge section-reveal reveal-left" style="transition-delay:0ms">
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
            <button class="music-ctrl-btn" onclick="playLocal(currentIdx-1)" title="Previous" aria-label="上一首">&#9664;&#9664;</button>
            <button class="music-ctrl-btn music-ctrl-play" id="musicCtrlPlay" onclick="togglePlay()" aria-label="Play music">&#9654;</button>
            <button class="music-ctrl-btn" onclick="playLocal(currentIdx+1)" title="Next" aria-label="下一首">&#9654;&#9654;</button>
            <button class="music-ctrl-btn music-ctrl-mode" id="musicModeBtn" onclick="cycleMode()" title="List loop" aria-label="播放模式">ALL</button>
            <span style="font-size:0.6rem;color:var(--text-muted);margin-left:4px">Vol</span>
            <input type="range" id="musicVolume" min="0" max="100" value="40" style="width:56px;flex-shrink:0" aria-label="音量">
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
      <div class="hero-panel section-reveal reveal-right" style="transition-delay:400ms">
      <div class="panel-hud-header">
        <span class="hud-label">SYS.PROFILE</span>
        <span class="hud-value">STATUS: <?= $isLoggedIn ? 'AUTHENTICATED' : 'ONLINE' ?></span>
      </div>
      <div class="panel-avatar-row">
        <div class="panel-avatar">
          <?php if (file_exists(__DIR__ . '/assets/images/my-avatar.jpg')): ?>
            <img src="<?= $BASE ?>/assets/images/my-avatar.jpg" alt="可乐">
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
      <!-- Skill Bars (2026-08 数据源统一: 与雷达图同读 about-content.json, 不再写死 mock) -->
      <?php foreach (array_slice($radarSkills, 0, 4) as $barIdx => $barSk): $barLv = intval($barSk['level'] ?? 0); ?>
      <div class="hud-bar-wrap"<?= $barIdx > 0 ? ' style="margin-top:10px;"' : '' ?>>
        <div class="hud-bar-label"><span><?= htmlspecialchars($barSk['name']) ?></span><span><?= $barLv ?>%</span></div>
        <div class="hud-bar"><div class="hud-bar-fill" style="width:<?= $barLv ?>%;"></div></div>
      </div>
      <?php endforeach; ?>
      <!-- Social links row -->
      <div class="social-links-row">
        <a href="https://github.com/Cola-Ca0" target="_blank" rel="noopener" class="social-icon-link" title="GitHub">GitHub</a>
        <a href="mailto:cola_ca0@qq.com" class="social-icon-link" title="Email">Email</a>
        <a href="https://space.bilibili.com/629007860" target="_blank" rel="noopener" class="social-icon-link" title="Bilibili">Bilibili</a>
        <a href="<?= $BASE ?>/feed.xml" class="social-icon-link" title="RSS">RSS</a>
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

  <!-- Quick Stats — real metrics -->
  <?php
    $postCount = count(glob(__DIR__ . '/posts/*.md'));
    $projectsData = json_decode(file_exists(__DIR__ . '/projects.json') ? file_get_contents(__DIR__ . '/projects.json') : '[]', true) ?: [];
    $projectCount = count($projectsData);
    $commentCount = 0;
    $commentDir = __DIR__ . '/data/comments/';
    if (is_dir($commentDir)) {
      foreach (glob($commentDir . '*.json') as $cf) {
        $c = json_decode(file_get_contents($cf), true);
        if (is_array($c)) $commentCount += count($c);
      }
    }
  ?>
  <section class="char-showcase">
    <div class="section-label">
      <span class="diamond-dec"></span>
      <span>System Metrics / 站点统计</span>
    </div>
    <div class="char-card-grid">
      <div class="char-card">
        <span class="char-card-icon">&#9998;</span>
        <div class="char-card-title">ARTICLES</div>
        <div class="char-card-value"><?= $postCount ?></div>
      </div>
      <div class="char-card">
        <span class="char-card-icon">&#9881;</span>
        <div class="char-card-title">PROJECTS</div>
        <div class="char-card-value"><?= $projectCount ?></div>
      </div>
      <div class="char-card">
        <span class="char-card-icon">&#9993;</span>
        <div class="char-card-title">SIGNALS</div>
        <div class="char-card-value"><?= $commentCount ?></div>
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
      <?php
      // 深潜日志 — 海况(日期种子)/本周下潜/任务进度 (数值来自 data/ambience.json, 宪法 4.2 数据即文件)
      $ambience = json_decode(file_exists(__DIR__ . '/data/ambience.json') ? file_get_contents(__DIR__ . '/data/ambience.json') : '{}', true) ?: [];
      $seas = $ambience['seas'] ?? [];
      $sea = $seas ? $seas[crc32(date('Ymd')) % count($seas)] : ['name' => '平静', 'adv' => ''];
      $weekStart = strtotime('monday this week');
      $weekDives = 0;
      foreach (glob(__DIR__ . '/posts/*.md') as $pf) { if (filemtime($pf) >= $weekStart) $weekDives++; }
      $weekText = ($ambience['weekly'] ?? [])[$weekDives === 0 ? 0 : ($weekDives <= 2 ? 1 : 2)]['text'] ?? '';
      $mission = $ambience['mission'] ?? ['label' => '任务', 'done' => 0, 'total' => 1, 'unit' => ''];
      $pct = $mission['total'] > 0 ? round($mission['done'] / $mission['total'] * 100) : 0;
      $days = floor((time() - strtotime($ambience['since'] ?? '2026-08-06')) / 86400) + 1;
      ?>
      <div class="sidebar-widget" id="diveLogWidget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Dive Log / 深潜日志</h3>
        <div class="hud-data-row">
          <span class="hud-data-label">今日海况</span>
          <span class="hud-data-val"><?= htmlspecialchars($sea['name']) ?></span>
        </div>
        <p style="font-size:0.78rem;color:var(--text-secondary);line-height:1.8;margin-bottom:12px"><?= htmlspecialchars($sea['adv']) ?></p>
        <div class="hud-data-row">
          <span class="hud-data-label">本周下潜</span>
          <span class="hud-data-val">× <?= $weekDives ?></span>
        </div>
        <p style="font-size:0.78rem;color:var(--text-secondary);line-height:1.8;margin-bottom:14px"><?= htmlspecialchars($weekText) ?></p>
        <div class="hud-bar-wrap">
          <div class="hud-bar-label"><span><?= htmlspecialchars($mission['label']) ?> · 第 <?= $days ?> 天</span><span><?= $pct ?>%</span></div>
          <div class="hud-bar"><div class="hud-bar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
      </div>

      <?php
      // 深海密件区 — 仅站长可见的 AI 代笔文章私区 (2026-09-10, 宪法 3.5 默认拒绝)
      $privatePosts = $isAdmin ? getPublishedPosts(__DIR__ . '/posts-private/') : [];
      ?>
      <?php if ($isAdmin): ?>
      <div class="sidebar-widget" id="vaultWidget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Classified / 深海密件</h3>
        <?php if ($privatePosts): ?>
        <ul style="list-style:none;margin:0;padding:0">
          <?php foreach ($privatePosts as $pp): ?>
          <li style="margin-bottom:8px">
            <a href="<?= $BASE ?>/post/<?= htmlspecialchars($pp['slug']) ?>" style="font-size:0.8rem;color:var(--text-secondary);text-decoration:none;letter-spacing:0.02em"><?= htmlspecialchars($pp['title']) ?></a>
          </li>
          <?php endforeach; ?>
        </ul>
        <p style="font-size:0.72rem;color:var(--text-muted);margin:10px 0 0">共 <?= count($privatePosts) ?> 件 · 矿石原料, 消化重写后移回公海</p>
        <?php else: ?>
        <p style="font-size:0.78rem;color:var(--text-muted);margin:0">密室空空 —— 公海只留你自己的声音。</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> About / 关于我</h3>
        <div class="about-avatar-wrap">
          <div class="about-avatar">
            <?php if (file_exists(__DIR__ . '/assets/images/my-avatar.jpg')): ?>
              <img src="<?= $BASE ?>/assets/images/my-avatar.jpg" alt="可乐">
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

      <!-- Latest Signals — 最近已审评论 (2026-08 用户拍板; 直读 data/comments/*.json, 宪法 4.2/4.4 零缓存) -->
      <?php
      $shorten = function ($s, $n) {
        $s = trim((string)$s);
        if (preg_match('/^.{0,' . (int)$n . '}/us', $s, $m)) {
          $out = $m[0];
          return $out !== $s ? $out . '…' : $out;
        }
        return $s;
      };
      $signals = [];
      if (is_dir($commentDir)) {
        foreach (glob($commentDir . '*.json') as $cf) {
          $cslug = basename($cf, '.json');
          $clist = json_decode(file_get_contents($cf), true);
          if (!is_array($clist)) continue;
          foreach ($clist as $c) {
            if (!is_array($c)) continue;
            // 2026-09-29: 原写法只跳过显式 pending —— 缺 status 字段的仍会出现在首页侧栏 (fail-open)。
            // 与 comments-api.php / treehole.php 统一: 非 approved 一律不上公开面。
            if (($c['status'] ?? '') !== 'approved') continue;
            $signals[] = [
              'slug' => $cslug,
              'name' => (string)($c['username'] ?? '访客'),
              'text' => trim((string)($c['content'] ?? '')),
              'time' => (string)($c['created_at'] ?? '')
            ];
          }
        }
      }
      usort($signals, function ($a, $b) { return strcmp($b['time'], $a['time']); });
      $signals = array_slice($signals, 0, 5);
      ?>
      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Latest Signals / 最新信号</h3>
        <?php if ($signals): ?>
        <div class="signal-list">
          <?php foreach ($signals as $sig): ?>
          <a class="signal-item" href="<?= $sig['slug'] === 'about' ? 'about.php' : 'post.php?slug=' . rawurlencode($sig['slug']) ?>#comments">
            <span class="signal-dot" aria-hidden="true"></span>
            <span class="signal-body">
              <span class="signal-meta"><?= htmlspecialchars($shorten($sig['name'], 12)) ?> · <?= htmlspecialchars($sig['time']) ?></span>
              <span class="signal-text"><?= htmlspecialchars($shorten($sig['text'], 60)) ?></span>
            </span>
          </a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="font-size:0.72rem;color:var(--text-muted);text-align:center;padding:16px 0">No signals yet. / 暂无信号</p>
        <?php endif; ?>
      </div>

      <!-- Dive Calendar — 当月发文热力图 (2026-08 用户拍板; glob 直读 posts/*.md, 宪法 4.4 零缓存) -->
      <?php
      $calYear = (int)date('Y'); $calMonth = (int)date('n');
      $postDays = [];
      foreach (glob(__DIR__ . '/posts/*.md') as $pf) { // 只统计当月, 防跨月同日污染 (2026-08-27 审查)
        if ((int)date('n', filemtime($pf)) !== $calMonth) continue;
        $pd = (int)date('j', filemtime($pf));
        $postDays[$pd] = ($postDays[$pd] ?? 0) + 1;
      }
      $daysInMonth = (int)date('t', mktime(0, 0, 0, $calMonth, 1, $calYear));
      $firstDow = (int)date('w', mktime(0, 0, 0, $calMonth, 1, $calYear));
      $calGrid = array_fill(0, $firstDow, null);
      for ($d = 1; $d <= $daysInMonth; $d++) $calGrid[] = $d;
      $calToday = (int)date('j');
      ?>
      <div class="sidebar-widget">
        <h3 class="widget-title"><span class="diamond-sm"></span> Dive Calendar / 深潜日历</h3>
        <div class="dive-cal-head">
          <span><?= sprintf('%04d-%02d', $calYear, $calMonth) ?></span>
          <span><?= count($postDays) ?> DAYS DIVED / <?= count($postDays) ?> 天下潜</span>
        </div>
        <div class="dive-cal-grid">
          <?php foreach (['日', '一', '二', '三', '四', '五', '六'] as $wd): ?>
          <span class="dive-cal-dow"><?= $wd ?></span>
          <?php endforeach; ?>
          <?php foreach ($calGrid as $cd): ?>
            <?php if ($cd === null): ?>
            <span class="dive-cal-cell"></span>
            <?php else: $dCnt = $postDays[$cd] ?? 0; $dToday = ($cd === $calToday); ?>
            <span class="dive-cal-cell<?= $dCnt ? ' posted lv' . min(3, $dCnt) : '' ?><?= $dToday ? ' today' : '' ?>"<?= $dCnt ? ' title="' . sprintf('%04d-%02d-%02d', $calYear, $calMonth, $cd) . ' · ' . $dCnt . ' 篇 / posts"' : '' ?>><?= $cd ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
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
        <?php $preview = array_slice($slides, 0, 4); ?>
        <?php if (count($allImages) > 0): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px">
          <?php foreach (array_slice($allImages, 0, 4) as $img): ?>
          <div class="gallery-thumb" style="aspect-ratio:1;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border-glow);transition:var(--transition-smooth);cursor:pointer">
            <img src="<?= $BASE . '/' . htmlspecialchars($img) ?>" alt="" style="width:100%;height:100%;object-fit:cover;transition:transform 0.3s" loading="lazy">
          </div>
          <?php endforeach; ?>
        </div>
        <a href="<?= $BASE ?>/gallery.php" style="display:block;text-align:center;font-family:var(--font-display);font-size:0.72rem;color:var(--accent);text-decoration:none;letter-spacing:0.06em;padding:6px;border:1px solid var(--border-glow);border-radius:var(--radius-pill);transition:var(--transition-smooth)">
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
          <a href="https://www.ymsora.com/" target="_blank" rel="noopener">Sora大佬</a>
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
  // 2026-09-10 修: 哨兵从细分割线 #blog-start 换成整屏 hero #top。
  // 旧逻辑在矮视口 (如 VS Code Simple Browser) 下分割线开局就在屏幕外 → 判定「已滚过」→ 导航开局即现身。
  // hero 顶格 100vh, 任何视口高度下 scrollY=0 都相交 → 导航必藏, 滚过 hero 才现身。
  var hero = document.getElementById('top');
  if (!navbar || !hero) return;

  var observer = new IntersectionObserver(function(entries) {
    navbar.classList.toggle('visible', !entries[0].isIntersecting);
  }, { threshold: 0, rootMargin: '-60px 0px 0px 0px' });

  observer.observe(hero);
})();

// Smooth scroll for nav links
document.querySelectorAll('a[href^="#"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});

// Section reveal observer —— 2026-09-28 已移到 includes/footer.php。
// 原因: 此前只有本页有这段, 而 timeline.php / treehole.php 也用 .section-reveal,
// 导致那两页内容永远 opacity:0 (DOM 里有、看不见)。footer 被所有页面引用, 放那里才对。

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

// 一言 — 本地治愈系科技文案池 (2026-08-27 脱离 hitokoto 外部 API; 点击轮换)
(function() {
  var textEl = document.getElementById('hitokotoText');
  var fromEl = document.getElementById('hitokotoFrom');
  if (!textEl) return;
  var verses = [], idx = -1;
  var FALLBACK = [
    { text: '深海之下，别有洞天。', from: 'Cola_CaO' },
    { text: '雨点敲窗之前，先敲了敲我的终端。', from: '深海研究站日志' }
  ];

  function show(v) {
    textEl.textContent = v.text;
    fromEl.textContent = '—— ' + (v.from || '佚名');
  }

  function pick(cycle) {
    if (!verses.length) { show(FALLBACK[0]); return; }
    idx = cycle
      ? (idx + 1 + Math.floor(Math.random() * (verses.length - 1))) % verses.length
      : Math.floor(Math.random() * verses.length);
    show(verses[idx]);
  }

  fetch('<?= $BASE ?>/data/ambience.json')
    .then(function(r) { return r.json(); })
    .then(function(d) { verses = d.verses || []; pick(false); })
    .catch(function() { pick(false); });

  textEl.style.cursor = 'pointer';
  textEl.title = '点击换一句 / Click to refresh';
  textEl.addEventListener('click', function() { pick(true); });
})();

// Crew 舱员台词轮换 — 已移除 (2026-08-27 用户: 角色素材自行挑选后再放)

</script>

<!-- 2026-09-28: 一律绝对 BASE 前缀。本页也被 /page/N 重写命中, 那里相对 "js/x.js" → /page/js/x.js -->
<script src="<?= $BASE ?>/js/particle-ocean.js"></script>
<script src="<?= $BASE ?>/js/rain-layer.js"></script>
<script src="<?= $BASE ?>/js/typewriter.js"></script>
<script src="<?= $BASE ?>/js/sparkles.js?v=3"></script>
<script src="<?= $BASE ?>/js/music-player.js"></script>
<script src="<?= $BASE ?>/js/music-visual.js"></script>
<script src="<?= $BASE ?>/js/post-loader.js?v=20260928"></script>
</body>
</html>
