/**
 * Bioluminescent Sparkles — Deep-sea floating light specks
 * Small star/diamond sparkles drifting upward like plankton.
 * Independent from particle-ocean (not music-reactive).
 * Respects prefers-reduced-motion.
 */
(function() {
  var canvas = document.getElementById('sparkleCanvas');
  var ctx = canvas ? canvas.getContext('2d') : null;
  if (!ctx) return;

  var sparkles = [];
  var SPARKLE_COUNT = 25;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    canvas.style.display = 'none'; return;
  }

  function isLightTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light';
  }

  // Opaque base colors — alpha handled by globalAlpha only
  function sparkleColors() {
    if (isLightTheme()) {
      return {
        blue: '100,160,210', cyan: '80,150,200',
        warm: '220,140,120',
        pink: '210,140,165', rose: '200,130,150', coral: '220,150,140'
      };
    }
    return {
      blue: '160,210,255', cyan: '142,208,232',
      warm: '240,160,130',
      pink: '230,170,195', rose: '220,155,175', coral: '240,170,160'
    };
  }

  function createSparkles() {
    sparkles = [];
    var W = canvas.width, H = canvas.height;
    if (!W || !H) return;
    var c = sparkleColors();
    var heartColors = [c.pink, c.rose, c.coral];
    for (var i = 0; i < SPARKLE_COUNT; i++) {
      var isHeart = Math.random() < 0.3; // 30% hearts
      var r = Math.random();
      var rgb;
      if (isHeart) {
        rgb = heartColors[Math.floor(Math.random() * heartColors.length)];
      } else if (r < 0.08) {
        rgb = c.warm;
      } else if (r < 0.3) {
        rgb = c.cyan;
      } else {
        rgb = c.blue;
      }
      sparkles.push({
        x: Math.random() * W,
        y: Math.random() * H,
        size: isHeart ? (9 + Math.random() * 8) : (2.5 + Math.random() * 5),
        vy: isHeart ? -(0.05 + Math.random() * 0.18) : -(0.08 + Math.random() * 0.25),
        vx: (Math.random() - 0.5) * 0.08,
        opacity: 0.3 + Math.random() * 0.55,
        phase: Math.random() * Math.PI * 2,
        freq: 0.0015 + Math.random() * 0.004,
        rgb: rgb,
        shape: isHeart ? 'heart' : 'star'
      });
    }
  }

  function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    createSparkles();
  }

  // 8-point star / diamond
  function drawStar(x, y, size) {
    var hs = size * 0.5;
    var qs = size * 0.15;
    ctx.beginPath();
    ctx.moveTo(x, y - size);
    ctx.lineTo(x + qs, y - hs);
    ctx.lineTo(x + size, y);
    ctx.lineTo(x + qs, y + hs);
    ctx.lineTo(x, y + size);
    ctx.lineTo(x - qs, y + hs);
    ctx.lineTo(x - size, y);
    ctx.lineTo(x - qs, y - hs);
    ctx.closePath();
    ctx.fill();
  }

  // Small bezier heart
  function drawHeart(x, y, size) {
    var s = size * 0.55;
    ctx.beginPath();
    ctx.moveTo(x, y + s * 0.6);
    ctx.bezierCurveTo(x - s, y - s * 0.3, x - s * 0.5, y - s, x, y - s * 0.3);
    ctx.bezierCurveTo(x + s * 0.5, y - s, x + s, y - s * 0.3, x, y + s * 0.6);
    ctx.fill();
  }

  function loop() {
    var W = canvas.width, H = canvas.height;
    ctx.clearRect(0, 0, W, H);

    for (var i = 0; i < sparkles.length; i++) {
      var s = sparkles[i];
      s.y += s.vy;
      s.x += s.vx + Math.sin(Date.now() * s.freq * 0.3 + s.phase) * 0.04;

      var margin = 30;
      if (s.y < -margin) { s.y = H + margin; s.x = Math.random() * W; }
      if (s.x < -margin) s.x = W + margin;
      if (s.x > W + margin) s.x = -margin;

      // Twinkle: 0.5 to 1.0 range — never too dark
      var twinkle = 0.5 + 0.5 * Math.sin(Date.now() * s.freq + s.phase);
      ctx.globalAlpha = s.opacity * twinkle;
      ctx.fillStyle = 'rgb(' + s.rgb + ')';
      ctx.shadowBlur = s.size * 1.2;
      ctx.shadowColor = 'rgb(' + s.rgb + ')';
      if (s.shape === 'heart') {
        drawHeart(s.x, s.y, s.size);
      } else {
        drawStar(s.x, s.y, s.size);
      }
    }

    ctx.globalAlpha = 1;
    requestAnimationFrame(loop);
  }

  function init() {
    resize();
    window.addEventListener('resize', resize);
    loop();
  }

  new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
      if (m.attributeName === 'data-theme') createSparkles();
    });
  }).observe(document.documentElement, { attributes: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
