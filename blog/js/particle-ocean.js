/**
 * Particle Ocean — Deep Sea Bioluminescent Visualization
 * 80 small light dots + 20 large glow orbs, reacting to music frequencies.
 * Listens for custom events from music-player.js:
 *   music:play  → creates AudioContext, starts reactive mode
 *   music:pause → returns to idle floating mode
 */
(function() {
  var canvas = document.getElementById('particleOcean');
  var ctx = canvas ? canvas.getContext('2d') : null;
  var audioCtx = null, analyser = null, source = null;
  var particles = [], animId = null, musicActive = false;
  var mouseX = -500, mouseY = -500;
  var time = 0;
  var DOT_COUNT = 80, ORB_COUNT = 20;
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches; // 宪法 2.4

  function isLightTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light';
  }

  function particleColors() {
    if (isLightTheme()) {
      return {
        dotWhite:  'rgba(60,100,140,0.45)',
        dotCyan:   'rgba(58,128,176,0.45)',
        dotBlue:   'rgba(50,110,150,0.4)',
        orbCyan:   { fill:'rgba(58,128,176,0.3)', glow:'rgba(58,128,176,0.4)' },
        orbBlue:   { fill:'rgba(50,110,150,0.28)', glow:'rgba(50,110,150,0.35)' },
        glowWhite: 'rgba(80,130,170,0.4)',
        glowCyan:  'rgba(58,128,176,0.35)',
        glowBlue:  'rgba(50,110,150,0.3)',
        coreWhite: 'rgba(120,170,210,0.7)',
      };
    }
    return {
      dotWhite:  'rgba(220,240,255,0.75)',
      dotCyan:   'rgba(142,208,232,0.6)',
      dotBlue:   'rgba(91,160,224,0.55)',
      orbCyan:   { fill:'rgba(142,208,232,0.28)', glow:'rgba(142,208,232,0.55)' },
      orbBlue:   { fill:'rgba(91,160,224,0.25)', glow:'rgba(91,160,224,0.5)' },
      glowWhite: 'rgba(200,230,255,0.55)',
      glowCyan:  'rgba(142,208,232,0.5)',
      glowBlue:  'rgba(91,160,224,0.45)',
      coreWhite: 'rgba(200,235,255,0.55)',
    };
  }

  function avgFreq(data, lo, hi) {
    var sum = 0, n = hi - lo + 1;
    for (var i = lo; i <= hi; i++) sum += data[i];
    return sum / n / 255;
  }

  function createParticles() {
    particles = [];
    var W = canvas.width, H = canvas.height;
    var pc = particleColors();

    for (var i = 0; i < DOT_COUNT; i++) {
      var hue = Math.random();
      var color, glowColor;
      if (hue < 0.12) { color = pc.dotWhite; glowColor = pc.glowWhite; }
      else if (hue < 0.5) { color = pc.dotCyan; glowColor = pc.glowCyan; }
      else { color = pc.dotBlue; glowColor = pc.glowBlue; }
      particles.push({
        x: Math.random() * W, y: Math.random() * H,
        vx: (Math.random() - 0.5) * 0.15, vy: (Math.random() - 0.5) * 0.15,
        radius: 1 + Math.random() * 4, baseRadius: 1 + Math.random() * 4,
        color: color, glowColor: glowColor, glowBlur: 3 + Math.random() * 8,
        type: 'dot', phase: Math.random() * Math.PI * 2,
        speed: 0.12 + Math.random() * 0.28,
        opacity: 0.3 + Math.random() * 0.5, baseOpacity: 0.3 + Math.random() * 0.5,
        sparkle: 0
      });
    }

    for (var j = 0; j < ORB_COUNT; j++) {
      var orb = Math.random() < 0.35 ? pc.orbCyan : pc.orbBlue;
      particles.push({
        x: Math.random() * W, y: Math.random() * H,
        vx: (Math.random() - 0.5) * 0.08, vy: (Math.random() - 0.5) * 0.08,
        radius: 8 + Math.random() * 7, baseRadius: 8 + Math.random() * 7,
        color: orb.fill, glowColor: orb.glow, glowBlur: 15 + Math.random() * 30,
        type: 'orb', phase: Math.random() * Math.PI * 2,
        speed: 0.04 + Math.random() * 0.12,
        opacity: 0.25 + Math.random() * 0.3, baseOpacity: 0.25 + Math.random() * 0.3,
        sparkle: 0
      });
    }
  }

  function resizeCanvas() {
    if (!canvas) return;
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    if (REDUCED && particles.length) loop(); // 静态模式: 尺寸变化后重画一帧
  }

  function loop() {
    if (!ctx || !canvas) return;
    time += 0.016;
    var W = canvas.width, H = canvas.height;
    ctx.clearRect(0, 0, W, H);

    var freqData = null, bassVal = 0, midVal = 0, highVal = 0;
    if (musicActive && analyser) {
      freqData = new Uint8Array(analyser.frequencyBinCount);
      analyser.getByteFrequencyData(freqData);
      bassVal = avgFreq(freqData, 0, 3);
      midVal = avgFreq(freqData, 4, 20);
      highVal = avgFreq(freqData, 60, Math.min(100, freqData.length - 1));
    }

    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      var spdMul = musicActive ? (1 + midVal * 1.5) : 1;
      p.vx += Math.sin(time * 0.7 + p.phase) * 0.006 * spdMul;
      p.vy += Math.cos(time * 0.6 + p.phase) * 0.006 * spdMul;
      p.vx *= 0.992; p.vy *= 0.992;
      var maxSpd = p.speed * spdMul;
      var spd = Math.sqrt(p.vx * p.vx + p.vy * p.vy);
      if (spd > maxSpd) { p.vx = p.vx / spd * maxSpd; p.vy = p.vy / spd * maxSpd; }

      var dx = p.x - mouseX, dy = p.y - mouseY;
      var dist = Math.sqrt(dx * dx + dy * dy);
      if (dist < 150 && dist > 0) {
        var force = (150 - dist) / 150;
        p.vx += (dx / dist) * force * 0.15;
        p.vy += (dy / dist) * force * 0.15;
      }

      p.x += p.vx; p.y += p.vy;
      var margin = 40;
      if (p.x < -margin) p.x = W + margin;
      if (p.x > W + margin) p.x = -margin;
      if (p.y < -margin) p.y = H + margin;
      if (p.y > H + margin) p.y = -margin;

      var r = p.baseRadius;
      if (musicActive) {
        r = p.type === 'orb'
          ? p.baseRadius * (1 + bassVal * 0.9)
          : p.baseRadius * (1 + highVal * 0.35);
      }

      var alpha = p.baseOpacity;
      if (musicActive && p.type === 'dot') {
        p.sparkle += (highVal - 0.18) * 0.12;
        if (p.sparkle > 0) { alpha = Math.min(1, p.baseOpacity + p.sparkle * 0.7); p.sparkle *= 0.88; }
        if (p.sparkle < 0) p.sparkle = 0;
      }
      if (musicActive && p.type === 'orb') {
        alpha = p.baseOpacity + bassVal * 0.4;
      }

      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.shadowBlur = p.glowBlur * (musicActive ? (1 + bassVal * 0.6) : 1);
      ctx.shadowColor = p.glowColor;
      ctx.fillStyle = p.color;
      ctx.beginPath();
      ctx.arc(p.x, p.y, r, 0, Math.PI * 2);
      ctx.fill();

      if (p.type === 'orb') {
        ctx.shadowBlur = p.glowBlur * 0.6;
        ctx.fillStyle = isLightTheme()
          ? 'rgba(120,170,210,' + (alpha * 0.55).toFixed(2) + ')'
          : 'rgba(200,235,255,' + (alpha * 0.55).toFixed(2) + ')';
        ctx.beginPath();
        ctx.arc(p.x, p.y, r * 0.4, 0, Math.PI * 2);
        ctx.fill();
      }

      ctx.restore();
    }

    if (!REDUCED) animId = requestAnimationFrame(loop); // reduced-motion: 静态单帧
  }

  function start(audio) {
    if (!audio || !canvas) return;
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      analyser = audioCtx.createAnalyser();
      analyser.fftSize = 256;
      analyser.smoothingTimeConstant = 0.75;
      source = audioCtx.createMediaElementSource(audio);
      source.connect(analyser);
      analyser.connect(audioCtx.destination);
      // 共享分析器给声波条 (2026-08-16): 同一 audio 元素只能连一个 MediaElementSource,
      // music-visual.js 不再自建 AudioContext, 改由本文件发布
      window.__oceanAnalyser = analyser;
      document.dispatchEvent(new CustomEvent('ocean:audio-ready', { detail: { analyser } }));
    }
    if (audioCtx.state === 'suspended') audioCtx.resume();
    musicActive = true;
  }

  function pause() {
    musicActive = false;
  }

  // Listen for custom events from music-player
  document.addEventListener('music:play', function(e) { start(e.detail.audio); });
  document.addEventListener('music:pause', function() { pause(); });

  // Init
  function init() {
    if (!canvas || !ctx) return;
    resizeCanvas();
    createParticles();
    window.addEventListener('resize', resizeCanvas);
    document.addEventListener('mousemove', function(e) { mouseX = e.clientX; mouseY = e.clientY; });
    loop();
  }

  // Recreate particles on theme change
  new MutationObserver(function(mutations) {
    mutations.forEach(function(m) { if (m.attributeName === 'data-theme') createParticles(); });
  }).observe(document.documentElement, { attributes: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
