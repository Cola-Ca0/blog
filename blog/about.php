<?php
/**
 * Cola_CaO About / 关于我
 * Content editable via about-content.json
 */
require __DIR__ . '/includes/auth.php';

$aboutFile = __DIR__ . '/about-content.json';
$about = json_decode(file_get_contents($aboutFile), true) ?: [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/includes/theme-init.php'; ?>
<title>About · Cola_CaO</title>
<meta name="description" content="关于可乐 — 杭师大 CS 2026 级新生，网络安全方向，CTF + SRC。了解我的技术栈、学习路线和联系方式。">
<meta property="og:title" content="About · Cola_CaO">
<meta property="og:description" content="杭师大 CS 2026 级，网络安全方向，CTF + SRC。">
<meta property="og:type" content="website">
<link rel="stylesheet" href="includes/tokens.css">
<link rel="stylesheet" href="includes/shared.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&family=Great+Vibes&family=Noto+Serif+SC:wght@300;400;500;600;700&family=Quicksand:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:var(--font-body);background:var(--bg-deep);color:var(--text-primary);line-height:1.7;min-height:100vh;overflow-x:hidden}

.page-wrap{position:relative;z-index:2;max-width:900px;margin:0 auto;padding:100px 28px 60px}
.page-header{text-align:center;margin-bottom:50px}
.page-header h1{font-family:var(--font-display);font-size:2.8rem;font-weight:700;letter-spacing:0.04em}
.page-header .sub{color:var(--text-muted);font-size:0.9rem;margin-top:8px}

.about-card{background:var(--bg-card);backdrop-filter:blur(14px);border:1px solid var(--border-glow);border-radius:var(--radius-lg);padding:36px;margin-bottom:28px;box-shadow:var(--shadow-sm);transition:var(--transition-smooth)}
.about-card:hover{border-color:var(--border-glow-strong);box-shadow:0 8px 28px rgba(91,160,224,0.1),inset 0 1px 0 rgba(255,255,255,0.04)}
.about-card h2{font-family:var(--font-display);font-size:1.2rem;color:var(--accent);letter-spacing:0.06em;margin-bottom:20px;padding-bottom:10px;border-bottom:1px solid var(--border-glow);display:flex;align-items:center;gap:8px}
.about-card h2 .diamond{width:6px;height:6px;background:var(--accent);transform:rotate(45deg);box-shadow:0 0 4px var(--accent)}

.bio-text{font-size:0.95rem;line-height:2;color:var(--text-secondary);white-space:pre-line}

.skills-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.skill-item{display:flex;align-items:center;gap:10px;padding:10px 16px;background:rgba(91,160,224,0.05);border:1px solid rgba(91,160,224,0.1);border-radius:var(--radius-md)}
.skill-icon{font-size:1.2rem;width:32px;text-align:center}
.skill-info{flex:1;min-width:0}
.skill-name{font-family:var(--font-display);font-size:0.82rem;font-weight:600;color:var(--text-primary);letter-spacing:0.04em;display:flex;justify-content:space-between;align-items:center}
.skill-pct{font-size:0.68rem;color:var(--accent);font-weight:500}
.skill-bar-wrap{height:6px;background:rgba(91,160,224,0.1);border-radius:3px;margin-top:4px;overflow:hidden;position:relative}
.skill-bar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--primary),var(--accent));transition:width 0.1s ease}

.timeline{position:relative;padding-left:28px}
.timeline::before{content:'';position:absolute;left:8px;top:4px;bottom:4px;width:1px;background:var(--border-glow)}
.timeline-item{position:relative;margin-bottom:24px;padding-left:20px}
.timeline-item::before{content:'';position:absolute;left:-24px;top:6px;width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 8px var(--accent)}
.timeline-year{font-family:var(--font-display);font-size:0.75rem;color:var(--accent);letter-spacing:0.08em;margin-bottom:2px}
.timeline-title{font-weight:600;color:var(--text-primary);margin-bottom:2px}
.timeline-desc{font-size:0.82rem;color:var(--text-secondary)}

.social-links{display:flex;gap:12px;flex-wrap:wrap}
.social-link{display:flex;align-items:center;gap:8px;padding:10px 20px;background:rgba(91,160,224,0.05);border:1px solid var(--border-glow);border-radius:var(--radius-pill);text-decoration:none;color:var(--text-secondary);font-size:0.85rem;transition:var(--transition-smooth)}
.social-link:hover{border-color:var(--accent);color:var(--accent);background:rgba(91,160,224,0.1);transform:translateY(-2px)}

.admin-badge{text-align:center;margin-top:40px}
.admin-badge a{color:var(--text-muted);font-size:0.72rem;text-decoration:none;letter-spacing:0.06em;transition:color 0.2s}
.admin-badge a:hover{color:var(--accent)}

@media(max-width:768px){.page-header h1{font-size:2rem}.skills-grid{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.skills-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php $navActive = 'about'; require __DIR__ . '/includes/navbar.php'; ?>

<div class="page-wrap">
  <div class="page-header">
    <h1>About / 关于我</h1>
    <p class="sub"><?= htmlspecialchars($about['title'] ?? '') ?></p>
  </div>

  <!-- Bio -->
  <div class="about-card">
    <h2><span class="diamond"></span> Bio / 简介</h2>
    <div class="bio-text"><?= nl2br(htmlspecialchars($about['bio'] ?? '暂无简介。编辑 about-content.json 添加内容。')) ?></div>
  </div>

  <!-- Skills -->
  <?php if (!empty($about['skills'])): ?>
  <div class="about-card">
    <h2><span class="diamond"></span> Skills / 技能</h2>
    <div class="skills-grid" id="skillsGrid">
      <?php foreach ($about['skills'] as $idx => $sk): ?>
      <div class="skill-item" data-skill-index="<?= $idx ?>">
        <span class="skill-icon"><?= htmlspecialchars($sk['icon'] ?? '') ?></span>
        <div class="skill-info">
          <div class="skill-name">
            <?= htmlspecialchars($sk['name']) ?>
            <span class="skill-pct" id="skillPct<?= $idx ?>"><?= (int)($sk['level'] ?? 0) ?>%</span>
          </div>
          <div class="skill-bar-wrap" id="skillBar<?= $idx ?>" style="<?= $isAdmin ? 'cursor:ew-resize' : '' ?>">
            <div class="skill-bar-fill" id="skillFill<?= $idx ?>" style="width:<?= (int)($sk['level'] ?? 0) ?>%"></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if ($isAdmin): ?>
      <div style="grid-column:1/-1;text-align:right;margin-top:8px">
        <button onclick="saveSkills()" id="saveSkillsBtn" style="font-family:var(--font-display);background:var(--primary);color:#fff;border:none;padding:6px 16px;border-radius:var(--radius-pill);cursor:pointer;font-size:0.78rem">Save Skills / 保存技能</button>
        <span id="saveSkillsMsg" style="font-size:0.7rem;color:var(--accent);margin-left:10px"></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Timeline -->
  <?php if (!empty($about['timeline'])): ?>
  <div class="about-card">
    <h2><span class="diamond"></span> Timeline / 时间线</h2>
    <div class="timeline">
      <?php foreach ($about['timeline'] as $tl): ?>
      <div class="timeline-item">
        <div class="timeline-year"><?= htmlspecialchars($tl['year']) ?></div>
        <div class="timeline-title"><?= htmlspecialchars($tl['title']) ?></div>
        <div class="timeline-desc"><?= htmlspecialchars($tl['desc']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Social -->
  <?php if (!empty($about['social'])): ?>
  <div class="about-card">
    <h2><span class="diamond"></span> Links / 联系</h2>
    <div class="social-links">
      <?php foreach ($about['social'] as $sc): ?>
      <a href="<?= htmlspecialchars($sc['url']) ?>" class="social-link" target="_blank" rel="noopener">
        <span><?= htmlspecialchars($sc['icon'] ?? '') ?></span>
        <?= htmlspecialchars($sc['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <div class="about-card" style="margin-top:40px">
    <h2><span class="diamond"></span> Admin / 编辑 <button onclick="toggleEditor()" id="editToggleBtn" style="font-family:var(--font-display);font-size:0.7rem;background:var(--primary);color:#fff;border:none;padding:4px 14px;border-radius:var(--radius-pill);cursor:pointer;margin-left:auto;">Edit / 编辑</button></h2>
    <div id="editorPanel" style="display:none">
      <div style="display:flex;flex-direction:column;gap:14px">
        <div>
          <label style="font-size:0.72rem;color:var(--text-muted);display:block;margin-bottom:4px">Title</label>
          <input id="editTitle" style="width:100%;padding:8px 12px;background:rgba(0,0,0,0.3);color:var(--text-primary);border:1px solid var(--border-glow);border-radius:var(--radius-sm);font-family:var(--font-body);font-size:0.85rem">
        </div>
        <div>
          <label style="font-size:0.72rem;color:var(--text-muted);display:block;margin-bottom:4px">Bio / 简介</label>
          <textarea id="editBio" rows="4" style="width:100%;padding:8px 12px;background:rgba(0,0,0,0.3);color:var(--text-primary);border:1px solid var(--border-glow);border-radius:var(--radius-sm);font-family:var(--font-body);font-size:0.85rem;resize:vertical"></textarea>
        </div>
        <p style="font-size:0.65rem;color:var(--text-muted)">Skills and timeline are edited via about-content.json directly.</p>
        <div style="display:flex;gap:10px">
          <button onclick="saveAboutFields()" style="font-family:var(--font-display);background:var(--primary);color:#fff;border:none;padding:8px 20px;border-radius:var(--radius-pill);cursor:pointer;font-size:0.8rem;">Save / 保存</button>
          <button onclick="toggleEditor()" style="font-family:var(--font-display);background:transparent;color:var(--text-muted);border:1px solid var(--border-glow);padding:8px 20px;border-radius:var(--radius-pill);cursor:pointer;font-size:0.8rem;">Cancel</button>
          <span id="editorMsg" style="font-size:0.75rem;color:var(--accent);align-self:center"></span>
        </div>
      </div>
    </div>
  </div>
  <script>
  var editorOpen = false;
  var aboutData = <?= json_encode($about, JSON_UNESCAPED_UNICODE) ?>;
  function toggleEditor() {
    editorOpen = !editorOpen;
    var panel = document.getElementById('editorPanel');
    var btn = document.getElementById('editToggleBtn');
    panel.style.display = editorOpen ? 'block' : 'none';
    btn.textContent = editorOpen ? 'Close / 关闭' : 'Edit / 编辑';
    if (editorOpen) {
      document.getElementById('editTitle').value = aboutData.title || '';
      document.getElementById('editBio').value = aboutData.bio || '';
    }
  }
  function saveAboutFields() {
    aboutData.title = document.getElementById('editTitle').value;
    aboutData.bio = document.getElementById('editBio').value;
    var body = JSON.stringify(aboutData, null, 2);
    fetch('admin/save-about.php', {method:'POST',body:body,headers:{'Content-Type':'application/json'}}).then(function(r){
      return r.json().then(function(d){ document.getElementById('editorMsg').textContent = d.ok ? 'Saved! Refreshing...' : (d.error||'Failed'); if(d.ok) setTimeout(function(){location.reload()},800); });
    }).catch(function(){ document.getElementById('editorMsg').textContent='Network error'; });
  }
  </script>
  <?php endif; ?>
</div>

<div style="max-width:900px;margin:0 auto;padding:0 28px 60px">
<?php $commentSlug = 'about'; require __DIR__ . '/includes/comment-system.php'; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<?php if ($isAdmin): ?>
<script>
// Draggable skill bars (admin only)
(function() {
  var skillsData = <?= json_encode($about['skills'] ?? []) ?>;
  var dragging = null, dragBar = null, dragIdx = -1;

  document.querySelectorAll('.skill-bar-wrap').forEach(function(bar, i) {
    bar.addEventListener('mousedown', function(e) { startDrag(e, bar, i); });
    bar.addEventListener('touchstart', function(e) { startDrag(e.touches[0], bar, i); }, {passive:false});
  });
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('touchmove', onDrag, {passive:false});
  document.addEventListener('mouseup', stopDrag);
  document.addEventListener('touchend', stopDrag);

  function startDrag(e, bar, idx) {
    dragging = true; dragBar = bar; dragIdx = idx;
    updateFromEvent(e);
    e.preventDefault();
  }
  function onDrag(e) {
    if (!dragging) return;
    updateFromEvent(e.touches ? e.touches[0] : e);
  }
  function stopDrag() { dragging = false; }
  function updateFromEvent(e) {
    var rect = dragBar.getBoundingClientRect();
    var pct = Math.round(Math.max(5, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100)));
    document.getElementById('skillFill' + dragIdx).style.width = pct + '%';
    document.getElementById('skillPct' + dragIdx).textContent = pct + '%';
    if (skillsData[dragIdx]) skillsData[dragIdx].level = pct;
  }

  window.saveSkills = function() {
    var body = JSON.stringify({skills: skillsData.map(function(s){return {name:s.name,level:s.level,icon:s.icon}})}, null, 2);
    fetch('admin/save-skills.php', {method:'POST',body:body,headers:{'Content-Type':'application/json'}}).then(function(r){
      return r.json().then(function(d){
        document.getElementById('saveSkillsMsg').textContent = d.ok ? 'Saved! / 已保存!' : (d.error||'Failed');
      });
    }).catch(function(){ document.getElementById('saveSkillsMsg').textContent = 'Network error'; });
  };
})();
</script>
<?php endif; ?>

</body>
</html>
