<?php
/**
 * Theme Init — must run before first paint to prevent FOUC.
 * Include in <head> of every page.
 *
 * 2026-08-27 三场景(深海/浅海/雨窗, 用户拍板): localStorage 'scene' 为主键。
 * 2026-11-02 隐藏场景 lain: 由搜索框口令解锁。
 * 2026-11-08 Graphite 主主题: scene='graphite' 为新默认; 石墨自身用 data-g 切深/浅。
 *   旧三场景保留, 通过「返回旧版本主题」链接切换 (setScene('deepsea'))。
 *   Lain 仍只认口令。
 */
?><script>
(function() {
  var html = document.documentElement;
  var SCENES = ['deepsea', 'daylight', 'rain'];
  var SCENE_NAMES = { deepsea: '深海', daylight: '浅海', rain: '雨窗', lain: 'Phosphor', graphite: '石墨' };

  /* 主主题迁移: 老访客的 scene 可能是 deepsea/daylight/rain。
     只在主题版本号变化时强制切到 graphite 一次, 之后尊重用户选择。 */
  var THEME_VERSION = 'graphite-1';
  var saved = localStorage.getItem('scene');
  if (localStorage.getItem('themeVersion') !== THEME_VERSION) {
    localStorage.setItem('themeVersion', THEME_VERSION);
    saved = 'graphite';
    localStorage.setItem('scene', saved);
  }
  if (saved !== 'graphite' && saved !== 'lain' && SCENES.indexOf(saved) === -1) {
    saved = 'graphite';
    localStorage.setItem('scene', saved);
  }

  var mode = localStorage.getItem('graphiteMode');
  html.setAttribute('data-g', mode === 'light' ? 'light' : 'dark');

  html.setAttribute('data-scene', saved);
  if (saved === 'daylight' || saved === 'rain') html.setAttribute('data-theme', 'light');
  else html.removeAttribute('data-theme');

  /* 隐藏场景 Lain 的中文等宽字体只在 lain 下加载 (97 个 unicode-range 分片)。 */
  var LAIN_FONT = '<?= $BASE ?>/assets/fonts/sarasa-mono/index.css';
  function loadLainFont() {
    if (document.getElementById('lainFont')) return;
    var l = document.createElement('link');
    l.id = 'lainFont';
    l.rel = 'stylesheet';
    l.href = LAIN_FONT;
    document.head.appendChild(l);
  }
  window.loadLainFont = loadLainFont;
  if (saved === 'lain') loadLainFont();

  /* Graphite 的代码字体 Fira Code 只在 graphite 下加载。 */
  var G_FONT = '<?= $BASE ?>/assets/fonts/fira-code/index.css';
  function loadGraphiteFont() {
    if (document.getElementById('graphiteFont')) return;
    var l = document.createElement('link');
    l.id = 'graphiteFont';
    l.rel = 'stylesheet';
    l.href = G_FONT;
    document.head.appendChild(l);
  }
  window.loadGraphiteFont = loadGraphiteFont;
  if (saved === 'graphite') loadGraphiteFont();

  function setMode(m) {
    html.setAttribute('data-g', m === 'light' ? 'light' : 'dark');
    localStorage.setItem('graphiteMode', m === 'light' ? 'light' : 'dark');
    var btns = document.querySelectorAll('.g-mode button');
    for (var i = 0; i < btns.length; i++) {
      btns[i].setAttribute('aria-pressed', String(btns[i].getAttribute('data-g-mode') === m));
    }
  }
  window.setGraphiteMode = setMode;
  window.toggleGraphiteMode = function() {
    setMode(html.getAttribute('data-g') === 'light' ? 'dark' : 'light');
  };

  function applyScene(s) {
    if (s !== 'graphite' && s !== 'lain' && SCENES.indexOf(s) === -1) s = 'graphite';
    html.setAttribute('data-scene', s);
    localStorage.setItem('scene', s);
    if (s === 'daylight' || s === 'rain') html.setAttribute('data-theme', 'light');
    else html.removeAttribute('data-theme');
    if (s === 'lain') loadLainFont();
    if (s === 'graphite') loadGraphiteFont();

    /* 切离 Lain 时收掉未播完的闪屏, 并还原被解码改写的 Hero 标题 */
    if (s !== 'lain') {
      if (html.classList.contains('lain-booting')) {
        html.classList.remove('lain-booting');
        if (window.cancelLainBoot) window.cancelLainBoot();
      }
      if (window.restoreLainHero) window.restoreLainHero();
    }

    var btn = document.querySelector('.theme-toggle');
    if (btn) {
      if (s === 'lain') {
        btn.setAttribute('aria-label', '退出隐藏场景 / Leave Phosphor');
        btn.title = '退出隐藏场景 / Leave Phosphor';
      } else if (s === 'graphite') {
        btn.setAttribute('aria-label', '切换深浅 / Graphite mode');
        btn.title = '切换深浅 / Graphite mode';
      } else {
        var next = SCENES[(SCENES.indexOf(s) + 1) % SCENES.length];
        btn.setAttribute('aria-label', '切换场景 / Scene: ' + SCENE_NAMES[s]);
        btn.title = '切换场景 / Scene: ' + SCENE_NAMES[s] + ' (' + SCENE_NAMES[s] + '→' + SCENE_NAMES[next] + ')';
      }
    }
  }

  window.setScene = applyScene;
  window.applyScene = applyScene;

  window.toggleTheme = function() {
    var cur = html.getAttribute('data-scene') || 'graphite';
    if (cur === 'graphite') {
      /* 只有含石墨标记的页面才能切深浅; 其他旧页面回落到深海, 避免开关失效 */
      if (document.querySelector('.g-home, .g-post')) { window.toggleGraphiteMode(); }
      else { applyScene('deepsea'); }
      return;
    }
    if (cur === 'lain') {
      var back = localStorage.getItem('lainReturn');
      if (!back || back === 'lain' || SCENES.indexOf(back) === -1) back = 'graphite';
      applyScene(back);
      return;
    }
    applyScene(SCENES[(SCENES.indexOf(cur) + 1) % SCENES.length]);
  };

  /* 口令解锁 Lain: 从任何场景进入, 退出时回到解锁前场景 */
  window.unlockLain = function() {
    var cur = html.getAttribute('data-scene') || 'graphite';
    if (cur !== 'lain') localStorage.setItem('lainReturn', cur);
    applyScene('lain');
  };

  document.addEventListener('DOMContentLoaded', function() {
    applyScene(html.getAttribute('data-scene') || 'graphite');
    setMode(html.getAttribute('data-g') || 'dark');
  });
})();
</script>
