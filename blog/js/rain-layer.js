/**
 * Rain Layer — 雨窗场景的安静雨线 (2026-08-27 用户拍板)
 * 纪律: canvas 由本脚本自建并注入 body; 样式 .rain-layer 在 shared.css
 *   (宪法 2.3: fixed/inset 0/pointer-events:none; 宪法 2.4: prefers-reduced-motion 降级=不画静态雨线)
 * 安静参数(用户确定基调): 26 条细线, 慢速斜落, 低透明, 无雨声
 */
(function() {
  var DROP_COUNT = 26;
  var WIND = -0.22; // 风倾角斜率 (约 -12°)
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var canvas = null, ctx = null;
  var drops = [], animId = null, active = false;

  function isRain() {
    return document.documentElement.getAttribute('data-scene') === 'rain';
  }

  function ensureCanvas() {
    if (canvas) return;
    canvas = document.createElement('canvas');
    canvas.className = 'rain-layer';
    canvas.setAttribute('aria-hidden', 'true');
    canvas.id = 'rainCanvas';
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    document.body.appendChild(canvas);
    ctx = canvas.getContext('2d');
  }

  function mkDrop() {
    return {
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      speed: 2.2 + Math.random() * 2.2,
      len: 14 + Math.random() * 18,
      alpha: 0.16 + Math.random() * 0.22
    };
  }

  function loop() {
    if (!ctx || !canvas) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.lineWidth = 1;
    ctx.lineCap = 'round';
    for (var i = 0; i < drops.length; i++) {
      var d = drops[i];
      d.y += d.speed;
      d.x += d.speed * WIND;
      if (d.y > canvas.height + d.len || d.x < -d.len) { d = drops[i] = mkDrop(); d.y = -d.len; }
      ctx.strokeStyle = 'rgba(180, 212, 236,' + d.alpha.toFixed(2) + ')';
      ctx.beginPath();
      ctx.moveTo(d.x, d.y);
      ctx.lineTo(d.x + d.len * WIND, d.y + d.len);
      ctx.stroke();
    }
    animId = requestAnimationFrame(loop);
  }

  function start() {
    if (active || REDUCED) return;
    ensureCanvas();
    active = true;
    drops = [];
    for (var i = 0; i < DROP_COUNT; i++) drops.push(mkDrop());
    loop();
  }

  function stop() {
    active = false;
    if (animId) cancelAnimationFrame(animId);
    animId = null;
    if (ctx && canvas) ctx.clearRect(0, 0, canvas.width, canvas.height);
  }

  // 场景切换时启停
  new MutationObserver(function() {
    if (isRain()) start(); else stop();
  }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-scene'] });

  window.addEventListener('resize', function() {
    if (!canvas || !active) return;
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  });

  if (isRain()) start();
})();
