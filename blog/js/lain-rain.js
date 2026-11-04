/**
 * PHOSPHOR · 0101 / 代码雨背景 (2026-11-03)
 * - 只在 data-scene="lain" 时绘制; 其他场景不跑 rAF
 * - 阅读文章时只画 0/1, 列更疏、更暗、更慢, 不抢正文
 * - 低帧率 + 低透明度; reduced-motion / 后台标签页自动停
 */
(function () {
  var canvas = document.getElementById('lainRain');
  if (!canvas || !canvas.getContext) return;

  var ctx = canvas.getContext('2d');
  var html = document.documentElement;
  var mq = window.matchMedia('(prefers-reduced-motion: reduce)');
  var reduced = mq.matches;

  /* 文章页: 只留 0101, 稀疏、暗、慢 */
  var READING = !!document.querySelector('.article-page, .article-body');
  if (READING) html.classList.add('lain-reading');

  var GLYPHS = '01010101{}[]()<>/\\|;:=+*#$%&@!?';
  var BINARY = '01';
  var TOKENS = ['0x7F3A', 'null', 'void', 'fn', 'if', '=>', '::', '0xFF', '404', '0b1010'];
  var FONT = 12;
  var COL_W = READING ? 26 : 16;
  var TRAIL = READING ? 4 : 6;
  var FRAME_MS = 70; /* ~14fps: 下落速度按 dt 走, 降帧不降观感, 但少画 1/6 的帧 */
  var SPEED_MIN = READING ? 7 : 12;
  var SPEED_MAX = READING ? 24 : 40;
  var ALPHA_MIN = READING ? 0.10 : 0.22;
  var ALPHA_MAX = READING ? 0.24 : 0.52;
  var MUT_MIN = READING ? 0.20 : 0.12;
  var MUT_MAX = READING ? 0.60 : 0.40;

  var W = 0, H = 0, DPR = 1;
  var cols = [];
  var raf = null, last = 0, running = false;
  var resizeTimer = null;
  var ghostRestore = null; /* 技能栏抖动的撤销句柄 (切场景时用) */

  function rand(n) { return Math.floor(Math.random() * n); }
  function glyph() {
    var set = READING ? BINARY : GLYPHS;
    return set.charAt(rand(set.length));
  }
  function makeChars(n) {
    var a = [];
    for (var i = 0; i < n; i++) a.push(glyph());
    return a;
  }

  /* 景深: 近列更大更亮更快, 远列更小更暗更慢。
     没有这一层, 雨是一张均匀纹理; 有了这层, 雨才是背后的空间。 */
  /* 只取 3 档字号: 配合按 near 排序的列, 每帧 ctx.font 从 ~90 次降到 3 次 */
  function depthSize(c) { return READING ? 12 : 10 + Math.round(c.near * 2) * 2.5; }
  function depthAlpha(c) {
    return c.alpha * (READING ? 0.78 + c.near * 0.34 : 0.40 + c.near * 0.92);
  }
  function depthSpeed(c) {
    return c.speed * (READING ? 0.82 + c.near * 0.28 : 0.52 + c.near * 0.96);
  }

  function buildCols() {
    cols = [];
    var n = Math.ceil(W / COL_W);
    for (var i = 0; i < n; i++) {
      var near = Math.random();
      /* 长 token 只落在远列, 否则会压到邻列 */
      var token = !READING && near < 0.7 && Math.random() < 0.07;
      cols.push({
        x: i * COL_W + 4,
        /* 初次铺满一屏, 刷新/切换后立刻能看到雨, 不是等十几秒才落下来 */
        y: Math.random() * H,
        near: near,
        speed: SPEED_MIN + Math.random() * (SPEED_MAX - SPEED_MIN),
        alpha: ALPHA_MIN + Math.random() * (ALPHA_MAX - ALPHA_MIN),
        token: token,
        headToken: token ? TOKENS[rand(TOKENS.length)] : '',
        chars: makeChars(TRAIL + 1),
        mut: MUT_MIN + Math.random() * (MUT_MAX - MUT_MIN)
      });
    }
    /* 按景深排序: 同字号的列排在一起, 绘制时 font 只在换档时设一次。
       列之间互不影响 (x/speed 都挂在列上), 重排不改变画面。 */
    cols.sort(function (a, b) { return a.near - b.near; });
  }

  function resize() {
    W = window.innerWidth;
    H = window.innerHeight;
    DPR = Math.min(window.devicePixelRatio || 1, 1.5);
    canvas.width = Math.floor(W * DPR);
    canvas.height = Math.floor(H * DPR);
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    ctx.font = FONT + 'px "Lucida Console","Courier New",monospace';
    ctx.textBaseline = 'top';
    buildCols();
    if (running) ctx.clearRect(0, 0, W, H);
  }

  function draw(ts) {
    if (!running) return;
    raf = window.requestAnimationFrame(draw);
    if (ts - last < FRAME_MS) return;
    var dt = Math.min(140, ts - last) / 1000;
    last = ts;

    /* 启动闪屏/后台标签页/其他场景: 跳过绘制, 但保持循环以便恢复 */
    if (document.hidden) return;
    if (html.getAttribute('data-scene') !== 'lain') return;
    /* 启动闪屏与 hero 解码期间停画: 那 300ms 里主线程要重绘标题,
       背景画布每帧再插一脚就是肉眼可见的掉帧 */
    if (html.classList.contains('lain-booting')) return;
    if (html.classList.contains('lain-decoding')) return;

    ctx.clearRect(0, 0, W, H);
    var lastSize = 0;
    for (var i = 0; i < cols.length; i++) {
      var c = cols[i];
      var size = depthSize(c);
      if (c.y > -size && c.y < H + size) {
        if (size !== lastSize) {
          ctx.font = size + 'px "Lucida Console","Courier New",monospace';
          lastSize = size;
        }
        /* 头部: 偶尔一个代码 token, 其余是 0/1 与符号 */
        ctx.globalAlpha = Math.min(1, depthAlpha(c) + (READING ? 0.05 : 0.12));
        ctx.fillStyle = c.token ? '#a5fac6' : '#76faad';
        ctx.fillText(c.token && c.headToken ? c.headToken : c.chars[0], c.x, c.y);

        /* 拖尾: 渐隐字符, 让每列读成一条流而不是散点 */
        ctx.fillStyle = '#1e7849';
        for (var k = 1; k <= TRAIL; k++) {
          ctx.globalAlpha = Math.max(0.03, depthAlpha(c) * (0.78 - k * 0.11));
          ctx.fillText(c.chars[k], c.x, c.y - k * size * 1.28);
        }
      }

      /* 低频换字: 代码在流里缓慢变化, 不是每帧闪 */
      c.mut -= dt;
      if (c.mut <= 0) {
        c.chars[rand(c.chars.length)] = glyph();
        c.mut = MUT_MIN + Math.random() * (MUT_MAX - MUT_MIN);
      }

      c.y += depthSpeed(c) * dt;
      if (c.y > H + 60) {
        c.y = -Math.random() * H * 0.35;
        c.speed = SPEED_MIN + Math.random() * (SPEED_MAX - SPEED_MIN);
        c.alpha = ALPHA_MIN + Math.random() * (ALPHA_MAX - ALPHA_MIN);
        c.token = !READING && Math.random() < 0.07;
        c.headToken = c.token ? TOKENS[rand(TOKENS.length)] : '';
        c.chars = makeChars(TRAIL + 1);
        c.mut = MUT_MIN + Math.random() * (MUT_MAX - MUT_MIN);
      }
    }
    ctx.globalAlpha = 1;
  }

  function start() {
    if (reduced || running || !canvas.isConnected) return;
    running = true;
    last = 0;
    raf = window.requestAnimationFrame(draw);
  }

  function stop() {
    running = false;
    if (raf) window.cancelAnimationFrame(raf);
    raf = null;
    ctx.clearRect(0, 0, W, H);
  }

  function sync() {
    if (!reduced && !document.hidden && html.getAttribute('data-scene') === 'lain') start();
    else {
      stop();
      /* 切走时别把 lain 头像 / 备用技能留在原地 */
      if (avatarEl) avatarEl.classList.remove('is-glitch');
      if (ghostRestore) ghostRestore();
    }
  }

  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(resize, 160);
  }, false);

  document.addEventListener('visibilitychange', sync, false);

  if (mq.addEventListener) mq.addEventListener('change', function (e) {
    reduced = e.matches;
    sync();
  });
  else if (mq.addListener) mq.addListener(function (e) {
    reduced = e.matches;
    sync();
  });

  /* 场景切换: deepsea/daylight/rain 时彻底停掉 */
  if (window.MutationObserver) {
    new MutationObserver(sync).observe(html, { attributes: true, attributeFilter: ['data-scene'] });
  }

  /* 头像信号不稳: 每隔 10–15s 抖一次。
     一次抖动 = 快切几下来回 (70/55/100/60ms, 这段就是"闪"), 然后停在 lain 头像 0.9s
     让人看清换的是谁, 再收回到正常。全程硬切, 没有过渡。 */
  var avatarEl = document.querySelector('.panel-avatar');
  if (avatarEl && !reduced) {
    var AV_SEQ = [[1, 70], [0, 55], [1, 100], [0, 60], [1, 900]];
    var avatarNext, avatarRun;
    avatarRun = function (i) {
      var step = AV_SEQ[i];
      if (!step) {
        avatarEl.classList.remove('is-glitch', 'is-flicker');
        avatarNext();
        return;
      }
      if (step[0]) avatarEl.classList.add('is-glitch');
      else avatarEl.classList.remove('is-glitch');
      avatarEl.classList.add('is-flicker');
      setTimeout(function () { avatarRun(i + 1); }, step[1] + Math.random() * 45);
    };
    avatarNext = function () {
      setTimeout(function () {
        if (!document.hidden && html.getAttribute('data-scene') === 'lain') avatarRun(0);
        else avatarNext();
      }, 10000 + Math.random() * 5000);
    };
    avatarNext();
  }

  /* 技能栏 + 雷达联动抖动: 隔 12–20s 挑一条, 名字先乱码再失真扭动, 最后落到"备用技能"。
     进度条与雷达是同一个技能, 所以两边必须一起变。
     数据来自 index.php 的 .lain-ghost-skills[data-skills], 不进 about-content.json。 */
  var ghostBox = document.querySelector('.lain-ghost-skills');
  var barRows = [].slice.call(document.querySelectorAll('.hero-panel .hud-bar-wrap'));
  var radarSvg = document.querySelector('.radar-svg');
  var radarPoly = radarSvg && radarSvg.querySelector('.radar-shape');
  var radarDots = radarSvg ? [].slice.call(radarSvg.querySelectorAll('circle[r="3"]')) : [];
  var radarTexts = radarSvg ? [].slice.call(radarSvg.querySelectorAll('text[data-axis]')) : [];
  var radarVals = null;
  if (radarSvg) {
    radarVals = (radarSvg.getAttribute('data-values') || '').split(',').map(function (x) {
      return parseInt(x, 10) || 0;
    });
    while (radarVals.length < 6) radarVals.push(50);
  }
  function radarApply(vals) {
    if (!radarPoly) return;
    var pts = [];
    for (var i = 0; i < 6; i++) {
      var a = (-90 + i * 60) * Math.PI / 180;
      var r = (vals[i] / 100) * 84;
      var x = 100 + r * Math.cos(a), y = 100 + r * Math.sin(a);
      pts.push(x.toFixed(1) + ',' + y.toFixed(1));
      if (radarDots[i]) {
        radarDots[i].setAttribute('cx', x.toFixed(1));
        radarDots[i].setAttribute('cy', y.toFixed(1));
      }
    }
    radarPoly.setAttribute('points', pts.join(' '));
  }

  if (ghostBox && barRows.length && !reduced) {
    var ghosts = [];
    try { ghosts = JSON.parse(ghostBox.getAttribute('data-skills') || '[]'); } catch (e) { ghosts = []; }
    if (ghosts.length) {
      var GLY = '01<>/[]{}=+*#@$%&';
      var ghostNext, ghostPick;

      ghostPick = function (j) {
        var row = barRows[j];
        if (!row) return;
        var nameEl = row.querySelector('.hud-bar-label span:first-child');
        var pctEl = row.querySelector('.hud-bar-label span:last-child');
        var fill = row.querySelector('.hud-bar-fill');
        if (!nameEl || !pctEl || !fill) return;

        var rText = radarTexts[j] || null;
        var oName = nameEl.textContent, oPct = pctEl.textContent, oW = fill.style.width;
        var oRName = rText ? rText.textContent : '', oRVal = radarVals ? radarVals[j] : 0;
        var g = ghosts[Math.floor(Math.random() * ghosts.length)];
        var done = false, scr = null, raf = null, hold = null;

        ghostRestore = function () {
          done = true;
          if (scr) clearInterval(scr);
          if (hold) clearTimeout(hold);
          if (raf) cancelAnimationFrame(raf);
          row.classList.remove('is-glitch');
          if (rText) rText.classList.remove('is-glitch');
          radarPoly && radarPoly.classList.remove('is-glitch');
          nameEl.textContent = oName;
          pctEl.textContent = oPct;
          fill.style.width = oW;
          if (rText) rText.textContent = oRName;
          if (radarVals) { radarVals[j] = oRVal; radarApply(radarVals); }
          ghostRestore = null;
        };

        row.classList.add('is-glitch');
        if (rText) rText.classList.add('is-glitch');
        if (radarPoly) radarPoly.classList.add('is-glitch');

        var n = 0;
        scr = setInterval(function () {
          var s = '';
          for (var i = 0; i < oName.length; i++) s += GLY.charAt(Math.floor(Math.random() * GLY.length));
          nameEl.textContent = s;
          if (rText) rText.textContent = s;
          if (++n < 4) return;

          clearInterval(scr); scr = null;
          nameEl.textContent = g.name;
          if (rText) rText.textContent = g.name;

          var from = parseInt(oPct, 10) || 0, to = g.level, t0 = null;
          var step = function (ts) {
            if (done) return;
            if (t0 === null) t0 = ts;
            var k = Math.min(1, (ts - t0) / 340);
            var v = Math.round(from + (to - from) * k + (Math.random() - 0.5) * 20 * (1 - k));
            v = Math.max(0, Math.min(100, v));
            pctEl.textContent = v + '%';
            fill.style.width = v + '%';
            if (radarVals) { radarVals[j] = v; radarApply(radarVals); }
            if (k < 1) { raf = requestAnimationFrame(step); return; }
            pctEl.textContent = to + '%';
            fill.style.width = to + '%';
            if (radarVals) { radarVals[j] = to; radarApply(radarVals); }
            raf = null;
            hold = setTimeout(function () {
              if (!done && ghostRestore) ghostRestore();
              ghostNext();
            }, 1100);
          };
          raf = requestAnimationFrame(step);
        }, 55);
      };

      ghostNext = function () {
        setTimeout(function () {
          if (!document.hidden && html.getAttribute('data-scene') === 'lain') {
            ghostPick(Math.floor(Math.random() * barRows.length));
          } else ghostNext();
        }, 12000 + Math.random() * 8000);
      };
      ghostNext();
    }
  }

  /* 示波器: hero 里那条信号线, 70ms 推一格 (只在 Lain 场景更新) */
  var scopeLine = document.getElementById('lainScopeLine');
  if (scopeLine && !reduced) {
    /* 密集采样 + 短步进: 波速靠"每步移动多少"决定, 不靠降帧。
       之前 120ms/56 点是靠掉帧变慢, 看起来就是卡; 现在 45ms/140 点, 同样速度但顺滑。 */
    var SCOPE_W = 200, SCOPE_N = 140, scopeBuf = [];
    /* 预热: 一开始就填满一整条波形, 否则头 6 秒左半段是条直线 */
    var scopeV = 38;
    for (var si = 0; si < SCOPE_N; si++) {
      scopeV = scopeV + (38 - scopeV) * 0.045 + (Math.random() - 0.5) * 7;
      scopeBuf.push(Math.max(6, Math.min(70, scopeV)));
    }
    setInterval(function () {
      if (document.hidden || html.getAttribute('data-scene') !== 'lain') return;
      scopeBuf.shift();
      var prev = scopeBuf[scopeBuf.length - 1];
      /* 往中线回拉一点, 再叠抖动 —— 读作"信号", 而不是纯随机噪声 */
      var v = prev + (38 - prev) * 0.045 + (Math.random() - 0.5) * 7;
      scopeBuf.push(Math.max(6, Math.min(70, v)));
      var pts = '';
      for (var i = 0; i < SCOPE_N; i++) {
        pts += (i * (SCOPE_W / (SCOPE_N - 1))).toFixed(1) + ',' + scopeBuf[i].toFixed(1) + ' ';
      }
      scopeLine.setAttribute('points', pts);
    }, 120);
  }

  /* 时钟: 角落 HUD + 系统自检屏 (其他场景不可见, 照常走也不费什么) */
  var clocks = [];
  var hudClock = document.getElementById('lainHudClock');
  var fetchClock = document.getElementById('lainFetchClock');
  if (hudClock) clocks.push(hudClock);
  if (fetchClock) clocks.push(fetchClock);
  if (clocks.length) {
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    var tickClocks = function () {
      var d = new Date();
      var t = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
      for (var i = 0; i < clocks.length; i++) clocks[i].textContent = t;
    };
    tickClocks();
    setInterval(tickClocks, 1000);
  }

  resize();
  sync();
})();
