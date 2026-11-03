<?php
/**
 * Theme Init — must run before first paint to prevent FOUC.
 * Include in <head> of every page.
 * 2026-08-27 三场景(深海/浅海/雨窗, 用户拍板): localStorage 'scene' 为主键,
 * 兼容旧 'theme' key(light→daylight, 其余→deepsea)。
 * 映射: deepsea→无 data-theme(暗); daylight/rain→data-theme=light(雨窗=浅海之上落雨)。
 * 2026-11-02 隐藏场景 lain: 不进入普通三档循环, 由搜索框口令解锁; 主题按钮在 Lain 下
 * 变成一个红色"退出"位, 点击回到解锁前的场景 (localStorage 'lainReturn')。
 */
?><script>
(function() {
  var html = document.documentElement;
  var SCENES = ['deepsea', 'daylight', 'rain'];
  var SCENE_NAMES = { deepsea: '深海', daylight: '浅海', rain: '雨窗', lain: 'Phosphor' };

  var saved = localStorage.getItem('scene');
  if (saved !== 'lain' && SCENES.indexOf(saved) === -1) {
    // 兼容旧版: 只存过 'theme'
    saved = localStorage.getItem('theme') === 'light' ? 'daylight' : 'deepsea';
    localStorage.setItem('scene', saved);
  }
  html.setAttribute('data-scene', saved);
  if (saved === 'daylight' || saved === 'rain') html.setAttribute('data-theme', 'light');
  else html.removeAttribute('data-theme');

  /* 自托管中文等宽字体只在 Lain 场景下加载。
     index.css 有 89KB (97 条 unicode-range), 不能让其他三个场景陪跑;
     在这里同步插入 <link>, 可与 lain.css 并行下载, 不额外阻塞渲染。 */
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

  window.toggleTheme = function() {
    var cur = html.getAttribute('data-scene') || 'deepsea';
    /* 隐藏场景只有一个出口: 回到解锁前的普通场景 */
    if (cur === 'lain') {
      var back = localStorage.getItem('lainReturn');
      if (!back || back === 'lain' || SCENES.indexOf(back) === -1) back = 'deepsea';
      applyScene(back);
      return;
    }
    applyScene(SCENES[(SCENES.indexOf(cur) + 1) % SCENES.length]);
  };

  function applyScene(s) {
    html.setAttribute('data-scene', s);
    localStorage.setItem('scene', s);
    if (s === 'daylight' || s === 'rain') html.setAttribute('data-theme', 'light');
    else html.removeAttribute('data-theme');
    if (s === 'lain') loadLainFont();

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
      } else {
        var next = SCENES[(SCENES.indexOf(s) + 1) % SCENES.length];
        btn.setAttribute('aria-label', '切换场景 / Scene: ' + SCENE_NAMES[s]);
        btn.title = '切换场景 / Scene: ' + SCENE_NAMES[s] + ' (' + SCENE_NAMES[s] + '→' + SCENE_NAMES[next] + ')';
      }
    }
  }

  /* 供导航栏口令解锁调用 */
  window.applyScene = applyScene;
  window.unlockLain = function() {
    var cur = html.getAttribute('data-scene') || 'deepsea';
    if (cur !== 'lain') localStorage.setItem('lainReturn', cur);
    applyScene('lain');
  };

  // navbar 按钮存在后同步一次 aria (theme-init 在 head 先跑, 按钮尚未渲染)
  document.addEventListener('DOMContentLoaded', function() {
    applyScene(html.getAttribute('data-scene') || 'deepsea');
  });
})();
</script>
