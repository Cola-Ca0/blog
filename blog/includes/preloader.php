<?php
/**
 * Ocean Preloader — CSS 3D rotating sphere + bubbles
 * Fades out after page load. Inspired by moejue.cn + deepseecommerce.com
 */
?>
<div class="preloader" id="preloader">
  <div class="preloader-bg"></div>
  <div class="preloader-content">
    <!-- 3D CSS rotating ocean sphere -->
    <div class="ocean-sphere">
      <div class="sphere-ring ring-1"></div>
      <div class="sphere-ring ring-2"></div>
      <div class="sphere-ring ring-3"></div>
      <div class="sphere-core"></div>
    </div>
    <div class="preloader-brand">Cola_CaO</div>
    <div class="preloader-sub">Establishing deep-sea link...</div>
  </div>
</div>

<style>
/* ============================================================
   Preloader — Ocean 3D Sphere
   ============================================================ */
.preloader {
  position: fixed; inset: 0; z-index: 9999;
  display: flex; align-items: center; justify-content: center;
  transition: opacity 0.6s ease, visibility 0.6s ease;
}
.preloader.fade-out { opacity: 0; visibility: hidden; pointer-events: none; }
.preloader-bg {
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, #0d2540 0%, #081420 60%, #040a10 100%);
}
[data-theme="light"] .preloader-bg {
  background: radial-gradient(ellipse at center, #e8f2fa 0%, #d6e4f0 60%, #c4d4e4 100%);
}

.preloader-content {
  position: relative; z-index: 1; text-align: center;
}

/* 3D CSS ocean sphere */
.ocean-sphere {
  width: 80px; height: 80px; margin: 0 auto 24px;
  position: relative;
  perspective: 200px;
  animation: sphere-float 3s ease-in-out infinite;
}
@keyframes sphere-float {
  0%,100% { transform: translateY(0); }
  50% { transform: translateY(-12px); }
}

.sphere-ring {
  position: absolute; inset: 0;
  border-radius: 50%;
  border: 2px solid transparent;
  border-top-color: var(--accent);
  animation: ring-spin 2s linear infinite;
}
.ring-1 { animation-duration: 2s; }
.ring-2 {
  animation-duration: 3s;
  animation-direction: reverse;
  inset: 8px;
  border-top-color: var(--primary);
  opacity: 0.7;
}
.ring-3 {
  animation-duration: 4s;
  inset: 16px;
  border-top-color: rgba(142,208,232,0.4);
  opacity: 0.5;
}
@keyframes ring-spin {
  100% { transform: rotateX(60deg) rotateZ(360deg); }
}

.sphere-core {
  position: absolute; inset: 24px; border-radius: 50%;
  background: radial-gradient(circle, var(--accent), var(--primary));
  box-shadow: 0 0 20px rgba(91,160,224,0.5), 0 0 60px rgba(91,160,224,0.2);
  animation: core-pulse 2s ease-in-out infinite;
}
@keyframes core-pulse {
  0%,100% { transform: scale(1); opacity: 0.8; }
  50% { transform: scale(1.15); opacity: 1; }
}

.preloader-brand {
  font-family: var(--font-display);
  font-size: 1.6rem; font-weight: 700; letter-spacing: 0.08em;
  color: var(--accent);
}
.preloader-sub {
  font-family: var(--font-display);
  font-size: 0.7rem; letter-spacing: 0.12em;
  color: var(--text-muted); margin-top: 6px;
  animation: dot-pulse 1.5s steps(4,end) infinite;
}
@keyframes dot-pulse {
  0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; }
}
</style>

<script>
window.addEventListener('load', function() {
  var preloader = document.getElementById('preloader');
  if (preloader) {
    setTimeout(function() { preloader.classList.add('fade-out'); }, 600);
  }
});
</script>
