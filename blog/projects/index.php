<?php
/**
 * Cola_CaO Projects — 项目展示页
 * 功能：项目列表、代码预览、登录用户下载
 */
require __DIR__ . '/../includes/auth.php';

$projectsFile = __DIR__ . '/../projects.json';
$projects = [];
if (file_exists($projectsFile)) {
    $data = json_decode(file_get_contents($projectsFile), true);
    if (is_array($data)) $projects = $data;
}

// Handle file view request
$viewFile    = $_GET['view'] ?? null;
$viewProject = $_GET['project'] ?? null;
$fileContent = '';
$fileLang    = '';

// ============================================================================
// 2026-09-28 严重漏洞修复 —— 路径遍历 → 任意文件读取 (CWE-22)
// ----------------------------------------------------------------------------
// 原实现有两个致命错误, 已实测: 一个刚注册的普通 role=user 账号用两个请求
// (?project=..&download=users.json) 即可读走管理员 bcrypt 哈希、
// posts-private/ 私区文章(破宪法 4.7)、乃至跨仓库文件。
//
//   ① download 分支使用了**未清洗**的 $viewProject —— basename() 只写在 view 分支的
//      if 内部, 而该分支要求 $viewFile 非空。于是 ?project=..&download=... 完全绕过清洗。
//   ② 即便清洗也挡不住: basename('..') === '..'。**basename 根本不是防遍历手段**。
//      而且包含校验的锚点 realpath(__DIR__.'/'.$viewProject) 被攻击者用 '..' 控制 ——
//      等于「拿攻击者指定的目录当基准」, 自证自答, 校验永远通过。
//
// 修法(三重独立防线):
//   ① 项目 id 白名单 [a-zA-Z0-9\-] —— 连 '.' 都不可能出现, 从根上杜绝 '..'
//      (与 post.php:12 / editor-article.php:54 的 slug 同一套约定)
//   ② 文件名 basename 后显式拒绝 '' / '.' / '..'
//   ③ 包含校验锚点固定为 realpath(__DIR__) = projects/ 自身, 不再随用户输入漂移
// ============================================================================
$PROJECTS_ROOT = realpath(__DIR__);
require_once __DIR__ . '/../includes/path-safe.php';   // resolveProjectFile()

if ($viewFile && $viewProject && $isLoggedIn) {
    $filePath = resolveProjectFile($PROJECTS_ROOT, (string)$viewProject, (string)$viewFile);
    if ($filePath !== null) {
        $fileContent = htmlspecialchars(file_get_contents($filePath));
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $langMap = ['php' => 'php', 'py' => 'python', 'js' => 'javascript', 'css' => 'css', 'html' => 'html', 'md' => 'markdown', 'json' => 'json', 'java' => 'java', 'c' => 'c', 'cpp' => 'cpp'];
        $fileLang = $langMap[$ext] ?? '';
    }
}

// Handle download
if (isset($_GET['download']) && $isLoggedIn && $viewProject) {
    $dlFile = resolveProjectFile($PROJECTS_ROOT, (string)$viewProject, (string)$_GET['download']);
    if ($dlFile !== null) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($dlFile) . '"');
        header('Content-Length: ' . filesize($dlFile));
        readfile($dlFile);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = 'Projects · Cola_CaO';
$pageDesc = '可乐的项目展示 — PHP、安全工具、CTF payload 速查。查看源码和下载。';
$extraHead = '<meta property="og:title" content="Projects · Cola_CaO">
<meta property="og:description" content="可乐的项目展示，安全工具与代码作品。">
<meta property="og:type" content="website">';
$fonts = 'code';
require __DIR__ . '/../includes/head.php';
?>
<style>
body{font-family:var(--font-body);background:var(--bg-deep);color:var(--text-primary);line-height:1.7;min-height:100vh;overflow-x:hidden;position:relative}

/* Background */
.bg-grid{position:fixed;inset:0;pointer-events:none;z-index:0;background-image:linear-gradient(rgba(91,160,224,0.07) 1px,transparent 1px),linear-gradient(90deg,rgba(91,160,224,0.07) 1px,transparent 1px);background-size:56px 56px}
.scanlines{position:fixed;inset:0;pointer-events:none;z-index:1;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.025) 2px,rgba(0,0,0,0.025) 4px)}
.bg-orbs{position:fixed;inset:0;pointer-events:none;z-index:0;overflow:hidden}
.orb{position:absolute;border-radius:50%;filter:blur(130px);opacity:0.1;animation:orb-drift 22s ease-in-out infinite}
.orb-1{width:650px;height:650px;background:radial-gradient(circle,var(--primary),transparent 70%);top:-250px;left:-180px}
.orb-2{width:520px;height:520px;background:radial-gradient(circle,var(--accent),transparent 70%);top:35%;right:-220px;animation-delay:-8s;animation-duration:26s}
.orb-3{width:480px;height:480px;background:radial-gradient(circle,var(--secondary),transparent 70%);bottom:-180px;left:28%;animation-delay:-15s;animation-duration:30s;opacity:0.06}
@keyframes orb-drift{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(50px,-35px) scale(1.07)}50%{transform:translate(-25px,25px) scale(0.93)}75%{transform:translate(-40px,-18px) scale(1.04)}}

/* Nav */
.navbar{position:fixed;top:0;left:0;right:0;z-index:100;height:66px;background:var(--bg-card);backdrop-filter:blur(22px);border-bottom:1px solid var(--border-glow);display:flex;align-items:center}
.nav-inner{max-width:1200px;width:100%;margin:0 auto;padding:0 28px;display:flex;align-items:center;justify-content:space-between}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text-primary)}
.nav-brand:hover{color:var(--accent)}
.shark-fin{width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--accent));clip-path:polygon(50% 0%,0% 100%,100% 100%);box-shadow:0 0 12px rgba(91,160,224,0.35)}
.brand-text{font-family:var(--font-display);font-size:1.4rem;font-weight:700;letter-spacing:0.04em}
.nav-links{display:flex;align-items:center;gap:6px;list-style:none}
.nav-links a{color:var(--text-secondary);text-decoration:none;padding:8px 16px;border-radius:var(--radius-sm);font-size:0.9rem;font-weight:500;transition:var(--transition-smooth)}
.nav-links a:hover,.nav-links a.active{color:var(--accent);background:rgba(142,208,232,0.06)}
.nav-auth{display:flex;align-items:center;gap:10px}
.nav-auth a{font-family:var(--font-display);font-size:0.82rem;font-weight:600;letter-spacing:0.06em;text-decoration:none;padding:8px 20px;border-radius:var(--radius-pill);transition:var(--transition-smooth)}
.btn-ghost{background:transparent;color:var(--accent);border:1px solid rgba(142,208,232,0.35)}
.btn-ghost:hover{background:rgba(142,208,232,0.1);border-color:var(--accent)}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--accent));color:var(--text-primary);box-shadow:0 0 16px rgba(91,160,224,0.3)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 0 28px rgba(91,160,224,0.5)}

/* Page */
.page-wrap{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:100px 28px 60px}
.page-header{margin-bottom:50px;text-align:center}
.page-header h1{font-family:var(--font-display);font-size:2.8rem;font-weight:700;letter-spacing:0.04em;color:var(--text-primary)}
.page-header .sub{color:var(--text-muted);font-size:0.9rem;margin-top:8px;letter-spacing:0.06em}

/* Project grid */
.projects-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:24px}

.project-card{background:var(--bg-card);backdrop-filter:blur(14px);border:1px solid var(--border-glow);border-radius:var(--radius-lg);padding:28px;transition:var(--transition-smooth);position:relative;overflow:hidden;box-shadow:var(--shadow-sm)}
.project-card:hover{border-color:var(--border-glow-strong);box-shadow:var(--shadow-md),0 0 28px rgba(91,160,224,0.22),inset 0 1px 0 rgba(255,255,255,0.04);transform:translateY(-4px)}
.project-card .card-top{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.project-card .status-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);box-shadow:0 0 8px var(--accent);animation:pulse 2s ease-in-out infinite;flex-shrink:0}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}
.project-card .date{font-size:0.72rem;color:var(--text-muted);letter-spacing:0.06em;font-family:var(--font-display)}
.project-card h3{font-family:var(--font-display);font-size:1.35rem;font-weight:700;color:var(--text-primary);margin-bottom:8px;letter-spacing:0.03em}
.project-card p{color:var(--text-secondary);font-size:0.88rem;line-height:1.75;margin-bottom:16px}
.project-card .tags{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.project-card .tags span{font-size:0.7rem;padding:3px 10px;border-radius:var(--radius-pill);background:rgba(91,160,224,0.08);color:var(--primary);border:1px solid rgba(91,160,224,0.18);font-weight:600}
.project-card .files-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding-top:14px;border-top:1px solid rgba(91,160,224,0.08)}
.file-link{display:inline-flex;align-items:center;gap:4px;font-family:var(--font-display);font-size:0.75rem;font-weight:600;letter-spacing:0.05em;text-decoration:none;color:var(--accent);padding:4px 10px;border:1px solid rgba(142,208,232,0.2);border-radius:var(--radius-pill);transition:var(--transition-smooth)}
.file-link:hover{border-color:var(--accent);background:rgba(142,208,232,0.08)}
.file-link.locked{color:var(--text-muted);border-color:rgba(104,136,158,0.2);cursor:not-allowed;pointer-events:none}
.file-link.dl{color:var(--accent);border-color:rgba(142,208,232,0.25)}

/* Code preview modal */
.code-overlay{display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,0.7);backdrop-filter:blur(4px);align-items:center;justify-content:center}
.code-overlay.open{display:flex}
.code-modal{width:90vw;max-width:900px;max-height:80vh;background:var(--bg-deep);border:1px solid var(--border-glow-strong);border-radius:var(--radius-lg);overflow:hidden;display:flex;flex-direction:column;box-shadow:0 0 60px rgba(0,0,0,0.5)}
.code-modal-header{display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid var(--border-glow);background:var(--bg-card)}
.code-modal-header h4{font-family:var(--font-display);font-size:0.85rem;color:var(--accent);letter-spacing:0.06em}
.code-modal-close{background:none;border:1px solid var(--border-glow);color:var(--text-secondary);padding:6px 14px;border-radius:var(--radius-pill);cursor:pointer;font-family:var(--font-display);font-size:0.78rem;transition:var(--transition-smooth)}
.code-modal-close:hover{border-color:var(--secondary);color:var(--secondary)}
.code-modal-body{flex:1;overflow:auto;padding:0}
.code-modal-body pre{margin:0;padding:24px;font-family:var(--font-mono);font-size:0.82rem;line-height:1.6;color:var(--text-secondary);white-space:pre-wrap;word-break:break-all;background:rgba(0,0,0,0.2)}
.code-modal-footer{padding:12px 24px;border-top:1px solid var(--border-glow);display:flex;justify-content:space-between;align-items:center;background:var(--bg-card)}
.code-modal-footer .lang-tag{font-family:var(--font-display);font-size:0.7rem;color:var(--text-muted);letter-spacing:0.08em;text-transform:uppercase}

/* Empty state */
.empty-state{text-align:center;padding:80px 20px;color:var(--text-muted)}
.empty-state .icon{font-size:3rem;margin-bottom:16px}
.empty-state p{font-size:0.9rem;letter-spacing:0.04em}

/* Footer */
.site-footer{padding:40px 0;border-top:1px solid var(--border-glow);position:relative;z-index:2;text-align:center;color:var(--text-muted);font-size:0.8rem}
.site-footer a{color:var(--accent);text-decoration:none}
.site-footer a:hover{color:var(--primary)}

@media(max-width:768px){.projects-grid{grid-template-columns:1fr}.page-header h1{font-size:2rem}.nav-links{display:none}}

</style>
</head>
<body>
<?php require __DIR__ . '/../includes/preloader.php'; ?>
<?php require __DIR__ . '/../includes/background.php'; ?>
<?php $navActive = 'projects'; require __DIR__ . '/../includes/navbar.php'; ?>

<div class="page-wrap">
  <div class="page-header">
    <h1>Projects / 项目</h1>
    <p class="sub">// 代码 · 文档 · 实验 — 来自深海研究站的工程日志</p>
  </div>

  <?php if (count($projects) === 0): ?>
  <div class="empty-state">
    <div class="icon">&#9881;</div>
    <p>暂无项目。将项目文件夹放入 <code>blog/projects/</code> 并在 <code>projects.json</code> 中添加条目即可发布。</p>
  </div>
  <?php else: ?>
  <div class="projects-grid">
    <?php foreach ($projects as $proj): ?>
    <div class="project-card">
      <div class="card-top">
        <span class="status-dot"></span>
        <span class="date"><?= htmlspecialchars($proj['date']) ?></span>
      </div>
      <h3>
        <?= htmlspecialchars($proj['title']) ?>
        <?php if ($isAdmin): ?>
        <a href="<?= $BASE ?>/admin/editor-project.php?id=<?= urlencode($proj['id']) ?>" class="admin-edit-link" title="Edit project">[EDIT]</a>
        <?php endif; ?>
      </h3>
      <p><?= htmlspecialchars($proj['description']) ?></p>
      <div class="tags">
        <?php foreach ($proj['tags'] as $tag): ?>
          <span><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="files-row">
        <?php foreach ($proj['files'] as $file): ?>
          <?php if ($isLoggedIn): ?>
            <a href="?project=<?= urlencode($proj['id']) ?>&view=<?= urlencode($file) ?>#code" class="file-link">&#9998; <?= htmlspecialchars($file) ?></a>
          <?php else: ?>
            <span class="file-link locked" title="登录后预览">&#128274; <?= htmlspecialchars($file) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($isLoggedIn): ?>
          <?php foreach ($proj['files'] as $file): ?>
            <a href="?project=<?= urlencode($proj['id']) ?>&download=<?= urlencode($file) ?>" class="file-link dl">&#11015; 下载 <?= htmlspecialchars($file) ?></a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Code Preview Modal -->
<?php if ($fileContent !== ''): ?>
<div class="code-overlay open" id="codeOverlay" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="code-modal">
    <div class="code-modal-header">
      <h4>&#9998; <?= htmlspecialchars($viewProject) ?>/<?= htmlspecialchars($viewFile) ?></h4>
      <button class="code-modal-close" onclick="document.getElementById('codeOverlay').classList.remove('open');window.location.href='?'">关闭 / CLOSE</button>
    </div>
    <div class="code-modal-body">
      <pre><?= $fileContent ?></pre>
    </div>
    <div class="code-modal-footer">
      <span class="lang-tag"><?= $fileLang ?: 'plain' ?></span>
      <a href="?project=<?= urlencode($viewProject) ?>&download=<?= urlencode($viewFile) ?>" class="file-link dl">&#11015; Download <?= htmlspecialchars($viewFile) ?></a>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
