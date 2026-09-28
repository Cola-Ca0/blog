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
      <a href="<?= $BASE ?>/">Home</a>
      <a href="<?= $BASE ?>/projects/">Projects</a>
      <a href="<?= $BASE ?>/about.php">About</a>
      <a href="<?= $BASE ?>/feed.xml">RSS</a>
      <a href="https://github.com/Cola-Ca0" target="_blank" rel="noopener">GitHub</a>
    </div>
    <div class="footer-hud">
      Cola_CaO // PHP · Vanilla JS · Deep Sea Station
    </div>
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

<!-- Section reveal observer (2026-09-28 从 index.php 提出; 宪法 2.6 单一来源) -->
<script>
(function() {
  // 背景: .section-reveal 的 CSS 是 opacity:0, 靠 JS 加 .visible 才显示;
  // no-JS 兜底 html:not(.js) 不生效, 因为 head.php 无条件加了 .js。
  // 此前这段只写在 index.php 里 —— 于是 timeline.php / treehole.php 的内容
  // 永远停在 opacity:0: DOM 里有、肉眼看不见 (用户实测"审核通过了却不显示")。
  // 放进 footer.php = 每个页面都有 (footer 被所有页面引用)。
  var reveals = document.querySelectorAll('.section-reveal');
  if (!reveals.length) return;

  // 兜底: 不支持 IntersectionObserver 时直接全显, 不给"隐形内容"
  if (!('IntersectionObserver' in window)) {
    reveals.forEach(function(el) { el.classList.add('visible'); });
    return;
  }

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  reveals.forEach(function(el) { observer.observe(el); });

  // 再兜一层: 首屏已可见但 threshold 未达标的元素(极短页面) 1 秒后强制显示
  setTimeout(function() {
    reveals.forEach(function(el) {
      var r = el.getBoundingClientRect();
      if (r.top < window.innerHeight && r.bottom > 0) el.classList.add('visible');
    });
  }, 1000);
})();
</script>
