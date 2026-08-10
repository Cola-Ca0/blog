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
