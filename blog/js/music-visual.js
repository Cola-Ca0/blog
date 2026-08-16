/**
 * Music Visualizer — 深海声波条 (Web Audio + Canvas, 2026-08-16)
 * 监听 music:play / music:pause (music-player.js 分发)
 * 美学: Signal Blue 光晕频谱条; 静止时一条淡基线。安静, 不喧闹 (宪法 1.2);
 * prefers-reduced-motion → 静态基线 (宪法 2.4)。
 */
(function() {
  var audio = document.getElementById('musicAudio');
  if (!audio || !audio.parentElement) return;
  var canvas = document.createElement('canvas');
  canvas.className = 'music-visual';
  audio.insertAdjacentElement('afterend', canvas);

  var ctx = canvas.getContext('2d');
  var audioCtx = null, analyser = null, freqData = null, rafId = 0;
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var H = 56; // 与 CSS height 一致

  function sizeCanvas() {
    var dpr = window.devicePixelRatio || 1;
    var w = canvas.clientWidth || canvas.parentElement.clientWidth;
    canvas.width = Math.max(1, Math.floor(w * dpr));
    canvas.height = Math.floor(H * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }
  sizeCanvas();
  window.addEventListener('resize', sizeCanvas);

  function drawIdle() {
    var w = canvas.clientWidth;
    ctx.clearRect(0, 0, w, H);
    ctx.strokeStyle = 'rgba(91,160,224,0.35)';
    ctx.shadowColor = 'rgba(91,160,224,0.5)';
    ctx.shadowBlur = 4;
    ctx.beginPath();
    ctx.moveTo(0, H / 2);
    ctx.lineTo(w, H / 2);
    ctx.stroke();
    ctx.shadowBlur = 0;
  }

  function drawBars() {
    var w = canvas.clientWidth;
    ctx.clearRect(0, 0, w, H);
    if (!analyser || !freqData) { drawIdle(); return; }
    analyser.getByteFrequencyData(freqData);
    if (!window.__musicVis?.frames) { // 首帧诊断: 频谱峰值 (0=数据全零)
      var peak = 0; for (var k = 0; k < freqData.length; k++) if (freqData[k] > peak) peak = freqData[k]
      console.log('[music-vis] 首帧频谱峰值:', peak, '| canvas宽:', w)
      window.__musicVis.frames = 0; window.__musicVis.peak = peak
    }
    window.__musicVis.frames++
    var n = 32;
    var slot = w / n;
    var barW = slot * 0.6;
    ctx.fillStyle = 'rgba(91,160,224,0.9)';
    ctx.shadowColor = 'rgba(91,160,224,0.7)';
    ctx.shadowBlur = 6;
    for (var i = 0; i < n; i++) {
      var v = freqData[Math.floor(i * freqData.length / n)] / 255;
      var bh = Math.max(2, v * (H - 10));
      ctx.fillRect(i * slot + slot * 0.2, H / 2 - bh / 2, barW, bh);
    }
    ctx.shadowBlur = 0;
  }

  function loop() {
    drawBars();
    rafId = requestAnimationFrame(loop);
  }

  function start() {
    window.__musicVis = { reduced: REDUCED, state: 'start', hasCtx: !!audioCtx, ctxState: audioCtx ? audioCtx.state : null }
    console.log('[music-vis] music:play 收到 | reduced:', REDUCED, '| hasCtx:', !!audioCtx)
    if (REDUCED) { drawIdle(); return; }
    try {
      if (!audioCtx) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        var src = audioCtx.createMediaElementSource(audio);
        analyser = audioCtx.createAnalyser();
        analyser.fftSize = 64;
        analyser.smoothingTimeConstant = 0.8;
        src.connect(analyser);
        analyser.connect(audioCtx.destination);
        freqData = new Uint8Array(analyser.frequencyBinCount);
        console.log('[music-vis] AudioContext 已创建 | state:', audioCtx.state)
      }
      if (audioCtx.state === 'suspended') audioCtx.resume();
      cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(loop);
    } catch (e) {
      console.log('[music-vis] 异常:', e.message)
      window.__musicVis.error = e.message
      drawIdle()
    }
  }

  function stop() {
    cancelAnimationFrame(rafId);
    drawIdle();
  }

  document.addEventListener('music:play', function() { start(); });
  document.addEventListener('music:pause', function() { stop(); });
  drawIdle();
})();
