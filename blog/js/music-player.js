/**
 * Local Music Player — custom controls + LRC lyrics sync
 * Dispatches: music:play (detail.audio), music:pause
 */
(function() {
  var audio = document.getElementById('musicAudio');
  var resultsDiv = document.getElementById('musicResults');
  var lyricsBox = document.getElementById('musicLyrics');
  var progressBar = document.getElementById('musicProgressBar');
  var progressFill = document.getElementById('musicProgressFill');
  var progressThumb = document.getElementById('musicProgressThumb');
  var curTimeEl = document.getElementById('musicCurTime');
  var durTimeEl = document.getElementById('musicDurTime');
  var coverDisc = document.getElementById('musicCoverDisc');
  var ctrlPlayBtn = document.getElementById('musicCtrlPlay');
  var songs = [];
  var lrcData = null;
  var currentIdx = -1;
  var playMode = 'loop';
  var modeBtn = document.getElementById('musicModeBtn');
  var modeLabels = {'loop':'ALL','single':'ONE','shuffle':'RND'};
  var modeTitles = {'loop':'List loop / 列表循环','single':'Single repeat / 单曲循环','shuffle':'Shuffle / 随机播放'};
  var shuffleOrder = [];
  var dragging = false;

  function fmtTime(s) { var m=Math.floor(s/60), sec=Math.floor(s%60); return m+':'+(sec<10?'0':'')+sec; }

  // Load playlist
  var listCtrl = new AbortController();
  var listTimeout = setTimeout(function(){ listCtrl.abort(); }, 10000);
  fetch('music-api.php?action=list', { signal: listCtrl.signal }).then(function(r){ clearTimeout(listTimeout); return r.json() }).then(function(data){
    songs = data;
    if (songs.length === 0) {
      resultsDiv.innerHTML = '<p style="font-size:0.68rem;color:var(--text-muted);text-align:center;padding:16px">Drop .mp3 + .lrc into assets/music/</p>';
      return;
    }
    renderPlaylist();
  }).catch(function(){
    clearTimeout(listTimeout);
    resultsDiv.innerHTML = '<p style="font-size:0.68rem;color:var(--text-muted);text-align:center;padding:16px">Failed to load / 加载失败 — <a href="javascript:void(0)" onclick="location.reload()" style="color:var(--accent);text-decoration:underline;cursor:pointer">Retry</a></p>';
  });

  function renderPlaylist() {
    resultsDiv.innerHTML = songs.map(function(s, i) {
      return '<div class="music-result-item' + (i===currentIdx?' active':'') + '" onclick="playLocal(' + i + ')">' +
        '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + s.name + '</span>' +
        '<button class="play-btn" onclick="event.stopPropagation();playLocal(' + i + ')">' + (i===currentIdx&&!audio.paused?'Pause':'Play') + '</button>' +
        '</div>';
    }).join('');
  }

  function parseLrc(raw) {
    raw = raw.replace(/^﻿/, '');
    var times=[], texts=[];
    var re = /\[(\d{1,2}):(\d{1,2})(?:\.(\d{1,3}))?\](.*)/;
    raw.split(/\r?\n/).forEach(function(l){
      var m = l.match(re);
      if (m) {
        var min = parseInt(m[1]), sec = parseInt(m[2]);
        var ms = m[3] ? parseInt(m[3].padEnd(3,'0')) / 1000 : 0;
        times.push(min*60 + sec + ms);
        texts.push((m[4]||'').trim());
      }
    });
    return {times:times, texts:texts};
  }

  function renderLyrics(cur) {
    if (!lrcData||!lrcData.times.length) return;
    var a=-1; for(var i=0;i<lrcData.times.length;i++) if(lrcData.times[i]<=cur) a=i;
    var lines=[]; for(var j=Math.max(0,a-1);j<=Math.min(lrcData.times.length-1,a+1);j++) {
      var s=j===a?'color:var(--accent);font-weight:600;font-size:1.15rem':'color:var(--text-muted);font-size:0.9rem';
      var txt = lrcData.texts[j] || '';
      if (!txt) txt = '<span style="opacity:0.35">~ music ~</span>';
      lines.push('<p style="'+s+'">'+txt+'</p>');
    }
    lyricsBox.innerHTML = lines.join('');
  }

  progressBar.addEventListener('click', function(e) {
    if (!audio.duration) return;
    audio.currentTime = (e.offsetX / progressBar.offsetWidth) * audio.duration;
  });

  progressBar.addEventListener('keydown', function(e) {
    if (!audio.duration) return;
    if (e.key === 'ArrowRight') { e.preventDefault(); audio.currentTime = Math.min(audio.duration, audio.currentTime + 5); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); audio.currentTime = Math.max(0, audio.currentTime - 5); }
  });

  audio.addEventListener('timeupdate', function() {
    if (!audio.duration) return;
    var pct = (audio.currentTime/audio.duration)*100;
    progressFill.style.width = pct + '%';
    progressThumb.style.left = pct + '%';
    progressBar.setAttribute('aria-valuenow', Math.round(pct));
    curTimeEl.textContent = fmtTime(audio.currentTime);
    if (lrcData) renderLyrics(audio.currentTime);
  });

  audio.addEventListener('loadedmetadata', function() {
    durTimeEl.textContent = fmtTime(audio.duration);
  });

  audio.addEventListener('play', function() {
    ctrlPlayBtn.innerHTML = '&#9646;&#9646;';
    coverDisc.classList.add('playing');
    audio.dispatchEvent(new CustomEvent('music:play', { detail: { audio: audio }, bubbles: true }));
    renderPlaylist();
  });

  audio.addEventListener('pause', function() {
    ctrlPlayBtn.innerHTML = '&#9654;';
    coverDisc.classList.remove('playing');
    audio.dispatchEvent(new CustomEvent('music:pause', { bubbles: true }));
    renderPlaylist();
  });

  audio.addEventListener('ended', function() {
    coverDisc.classList.remove('playing');
    audio.dispatchEvent(new CustomEvent('music:pause', { bubbles: true }));
    if (playMode === 'single') {
      audio.currentTime = 0; audio.play().catch(function(){});
    } else if (playMode === 'shuffle') {
      var next = shuffleOrder[Math.floor(Math.random() * shuffleOrder.length)];
      playLocal(next);
    } else {
      if (currentIdx+1 < songs.length) playLocal(currentIdx+1);
      else if (songs.length > 0) playLocal(0);
      else renderPlaylist();
    }
  });

  window.cycleMode = function() {
    playMode = playMode === 'loop' ? 'single' : (playMode === 'single' ? 'shuffle' : 'loop');
    modeBtn.textContent = modeLabels[playMode];
    modeBtn.title = modeTitles[playMode];
    if (playMode === 'shuffle' && songs.length > 0) {
      shuffleOrder = songs.map(function(_,i){return i});
    }
  };

  document.getElementById('musicVolume').addEventListener('input', function() {
    audio.volume = this.value / 100;
  });
  audio.volume = 0.4;

  window.togglePlay = function() {
    if (!audio.src) return;
    if (audio.paused) audio.play().catch(function(){});
    else audio.pause();
  };

  window.currentIdx = currentIdx;

  window.playLocal = function(idx) {
    idx = ((idx % songs.length) + songs.length) % songs.length;
    if (idx < 0 || idx >= songs.length) return;
    var song = songs[idx];
    if (!song) return;
    if (idx === currentIdx) { if (audio.paused) audio.play().catch(function(){}); else audio.pause(); return; }
    currentIdx = idx;
    window.currentIdx = idx;
    audio.src = song.url;
    audio.volume = 0.4;
    lrcData = null;
    progressFill.style.width = '0%'; progressThumb.style.left = '0%';
    curTimeEl.textContent = '0:00'; durTimeEl.textContent = '0:00';
    lyricsBox.innerHTML = '<p style="color:var(--accent);font-size:0.9rem">' + song.name + '</p><p style="color:var(--text-muted)">&nbsp;</p><p style="color:var(--text-muted)">&nbsp;</p>';
    audio.play().catch(function(){});
    renderPlaylist();
    if (song.hasCover) {
      fetch('music-api.php?action=cover&file=' + encodeURIComponent(song.name + '.mp3'))
        .then(function(r){return r.json()}).then(function(d){
          if (d.cover) coverDisc.style.backgroundImage = 'url(' + d.cover + ')';
        }).catch(function(){});
    } else {
      coverDisc.style.backgroundImage = '';
    }
    if (song.hasLrc) {
      fetch('music-api.php?action=lyrics&file=' + encodeURIComponent(song.name + '.lrc'))
        .then(function(r){return r.text()}).then(function(raw){lrcData=parseLrc(raw)}).catch(function(){});
    }
  };
})();
