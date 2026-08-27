/**
 * Typewriter — 打字机动效 (2026-08-27 用户拍板, 参照 mizuki)
 * 用法: class="typewriter" 的元素自动逐字打出 + 光标闪烁。
 * 宪法 2.4: prefers-reduced-motion 时直接全显。
 * no-JS 兜底: 文本原生在 DOM, JS 未加载时不拆字, 静态完整。
 */
(function() {
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function typewrite(el, text, speed, done) {
    var i = 0;
    el.textContent = '';
    var cursor = document.createElement('span');
    cursor.className = 'tw-cursor';
    cursor.textContent = '▏';
    el.appendChild(cursor);
    var timer = setInterval(function() {
      if (i < text.length) {
        cursor.insertAdjacentText('beforebegin', text[i]);
        i += 1;
      } else {
        clearInterval(timer);
        if (done) done();
      }
    }, speed);
  }

  function run(el) {
    var full = el.getAttribute('data-typewriter-text');
    if (!full) {
      // 未指定则用元素现有文本 (保留空格与标点)
      full = el.textContent.trim();
    }
    var speed = parseInt(el.getAttribute('data-typewriter-speed') || '60', 10);
    if (REDUCED || el.hasAttribute('data-typewriter-static')) {
      el.textContent = full;
      return;
    }
    typewrite(el, full, speed);
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.typewriter').forEach(run);
  });
})();
