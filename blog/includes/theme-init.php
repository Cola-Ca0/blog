<?php
/**
 * Theme Init — must run before first paint to prevent FOUC.
 * Include in <head> of every page.
 * 2026-08-27 三场景(深海/浅海/雨窗, 用户拍板): localStorage 'scene' 为主键,
 * 兼容旧 'theme' key(light→daylight, 其余→deepsea)。
 * 映射: deepsea→无 data-theme(暗); daylight/rain→data-theme=light(雨窗=浅海之上落雨)。
 */
?><script>
(function() {
  var html = document.documentElement;
  var SCENES = ['deepsea', 'daylight', 'rain'];
  var SCENE_NAMES = { deepsea: '深海', daylight: '浅海', rain: '雨窗' };

  var saved = localStorage.getItem('scene');
  if (SCENES.indexOf(saved) === -1) {
    // 兼容旧版: 只存过 'theme'
    saved = localStorage.getItem('theme') === 'light' ? 'daylight' : 'deepsea';
    localStorage.setItem('scene', saved);
  }
  html.setAttribute('data-scene', saved);
  if (saved === 'daylight' || saved === 'rain') html.setAttribute('data-theme', 'light');
  else html.removeAttribute('data-theme');

  window.toggleTheme = function() {
    var cur = html.getAttribute('data-scene') || 'deepsea';
    applyScene(SCENES[(SCENES.indexOf(cur) + 1) % SCENES.length]);
  };

  function applyScene(s) {
    html.setAttribute('data-scene', s);
    localStorage.setItem('scene', s);
    if (s === 'daylight' || s === 'rain') html.setAttribute('data-theme', 'light');
    else html.removeAttribute('data-theme');
    var btn = document.querySelector('.theme-toggle');
    if (btn) {
      btn.setAttribute('aria-label', '切换场景 / Scene: ' + SCENE_NAMES[s]);
      btn.title = '切换场景 / Scene: ' + SCENE_NAMES[s] + ' (' + SCENE_NAMES[s] + '→' + SCENE_NAMES[SCENES[(SCENES.indexOf(s) + 1) % SCENES.length]] + ')';
    }
  }

  // navbar 按钮存在后同步一次 aria (theme-init 在 head 先跑, 按钮尚未渲染)
  document.addEventListener('DOMContentLoaded', function() {
    applyScene(html.getAttribute('data-scene') || 'deepsea');
  });
})();
</script>
