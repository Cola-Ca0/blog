/**
 * Music Visualizer — 深海声波条 (Web Audio + Canvas, 2026-08-16)
 * 监听 music:play / music:pause (music-player.js 分发)
 * 分析器共享自 particle-ocean.js (同一 audio 元素只能连一个 MediaElementSource):
 *   particle-ocean 创建 AudioContext 后发布 window.__oceanAnalyser + 'ocean:audio-ready' 事件
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
  var analyser = null, freqData = null, rafId = 0;
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
    if (!window.__musicVis?.frames) { // 首帧诊断
      var peak = 0; for (var k = 0; k < freqData.length; k++) if (freqData[k] > peak) peak = freqData[k]
      console.log('[music-vis] 首帧频谱峰值:', peak, '| canvas宽:', w)
      window.__musicVis.frames = 0; window.__musicVis.peak = peak
    }
    window.__musicVis.frames++
    var n = 32;
    var slot = w / n;
    var barW = slot * 0.35; // 2026-08-16 用户选「细」: 0.6 → 0.35
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

  function adopt(a) {
    if (!a) return
    analyser = a
    freqData = new Uint8Array(a.frequencyBinCount)
    cancelAnimationFrame(rafId)
    rafId = requestAnimationFrame(loop)
  }

  function start() {
    window.__musicVis = { reduced: REDUCED, state: 'start' }
    console.log('[music-vis] music:play 收到 | reduced:', REDUCED)
    if (REDUCED) { drawIdle(); return; }
    // 优先直接共享 (particle-ocean 已建); 否则等它发布 (脚本顺序: 本文件先于 particle-ocean 注册监听)
    if (window.__oceanAnalyser) { adopt(window.__oceanAnalyser); return }
    var done = false
    var onReady = function(e) { done = true; adopt(e.detail?.analyser || window.__oceanAnalyser) }
    document.addEventListener('ocean:audio-ready', onReady)
    setTimeout(function() {
      if (done) return
      document.removeEventListener('ocean:audio-ready', onReady)
      console.log('[music-vis] 等待 ocean 分析器超时 (particle-ocean 未建 AudioContext)')
      drawIdle()
    }, 2000)
  }

  function stop() {
    cancelAnimationFrame(rafId);
    drawIdle();
  }

  document.addEventListener('music:play', function() { start(); });
  document.addEventListener('music:pause', function() { stop(); });
  drawIdle();
})();
