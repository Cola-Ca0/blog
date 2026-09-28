<?php
/**
 * Account — 修改昵称 / 修改密码
 * 2026-09-28 用户需求: 上线前能自助改密码与昵称 (公网弱口令=裸奔, 见上线检查清单)
 *
 * 权限: 任何已登录用户, 但**只能改自己那条** (按 $_SESSION['username'] 定位)
 * 写操作: 只认 POST + CSRF (宪法 3.1)
 *
 * ⚠️ 昵称即身份: $_SESSION['username'] 存的就是昵称, 评论归属(comments-api.php:148)
 *    也按昵称比对。所以改昵称必须做三件事, 缺一不可:
 *      ① 查重(否则两人同名就能互删评论)
 *      ② 同步 $_SESSION['username'](否则改完立刻"变成"另一个人)
 *      ③ 回填历史评论的署名(否则旧的评论认不出来, 自己删不了自己的)
 *
 * ⚠️ 若日后改成「邮箱登录」(见 docs/research/), 定位键要从 $user['username']
 *    改成 $user['email'] —— 本文件只有 $matchUserIndex() 一处需要动。
 */
require __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';   // $BASE 单一来源
require_once __DIR__ . '/../includes/json-store.php'; // 2026-09-28: 原子读改写（用户改密曾被并发写覆盖）

if (!$isLoggedIn) { header('Location: ' . $BASE . '/login.php'); exit; }

$usersFile    = __DIR__ . '/../users.json';
$commentsDir  = __DIR__ . '/../data/comments';

/** 取出当前登录用户那条记录的下标 —— 换登录键时只改这里 */
function matchUserIndex(array $users, string $me): int {
    foreach ($users as $i => $u) {
        if (($u['username'] ?? '') === $me) return (int)$i;
    }
    return -1;
}

/** 把某署名下的历史评论改成新署名; 返回改动条数（原子读改写） */
function propagateNickname(string $dir, string $old, string $new): int {
    $n = 0;
    foreach (glob($dir . '/*.json') ?: [] as $f) {
        jsonUpdate($f, function (array &$d) use ($old, $new, &$n) {
            $changed = false;
            foreach ($d as $k => $c) {
                if (is_array($c) && ($c['username'] ?? null) === $old) {
                    $d[$k]['username'] = $new;
                    $changed = true;
                    $n++;
                }
            }
            if (!$changed) return false;   // 本文件没有该署名 → 不写盘
        });
    }
    return $n;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = '会话校验失败，请刷新页面重试！';
    } else {
        $users = json_decode(@file_get_contents($usersFile) ?: '[]', true);
        if (!is_array($users)) $users = [];
        $idx = matchUserIndex($users, $_SESSION['username'] ?? '');

        if ($idx < 0) {
            $error = '找不到当前账号，请重新登录。';
        }

        // ---------------- 改昵称 ----------------
        elseif ($action === 'nickname') {
            $nick = trim($_POST['nickname'] ?? '');
            if ($nick === '' || mb_strlen($nick) < 2 || mb_strlen($nick) > 20) {
                $error = '昵称长度需为 2-20 个字符。';
            } elseif (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $nick)) {
                $error = '昵称只能包含字母、数字、下划线和中文。';
            } elseif ($nick === $users[$idx]['username']) {
                $error = '新昵称与当前相同。';
            } else {
                // 查重: 昵称是身份标识, 重名会让两人能互删评论
                $dup = false;
                foreach ($users as $i => $u) {
                    if ($i !== $idx && ($u['username'] ?? '') === $nick) { $dup = true; break; }
                }
                if ($dup) {
                    $error = '该昵称已被占用，请换一个。';
                } else {
                    // 2026-09-28: 查重 + 改名放进同一把锁(与注册同款竞态); 锁内重查,
                    // 否则两个并发改名可能都通过查重、后写的覆盖先写的。
                    $old = $users[$idx]['username'];
                    $me  = $_SESSION['username'] ?? '';
                    $dup = false;
                    $wrote = jsonUpdate($usersFile, function (array &$users) use ($me, $nick, &$dup) {
                        foreach ($users as $u) {
                            if (($u['username'] ?? '') === $nick) { $dup = true; return false; }
                        }
                        $i = matchUserIndex($users, $me);
                        if ($i < 0) return false;
                        $users[$i]['username'] = $nick;
                        $users[$i]['nickname_changed_at'] = date('Y-m-d H:i:s');
                    });
                    if ($dup) {
                        $error = '该昵称已被占用，请换一个。';
                    } elseif ($wrote === false) {
                        $error = '写入失败：users.json 不可写。';
                    } else {
                        $n = propagateNickname($commentsDir, $old, $nick);
                        $_SESSION['username'] = $nick;      // 关键: 同步会话身份
                        $success = "昵称已改为「{$nick}」，并同步更新了 {$n} 条历史评论的署名。";
                    }
                }
            }
        }

        // ---------------- 改密码 ----------------
        elseif ($action === 'password') {
            $cur  = $_POST['current_password'] ?? '';
            $new  = $_POST['new_password'] ?? '';
            $new2 = $_POST['new_password2'] ?? '';

            if ($cur === '' || $new === '' || $new2 === '') {
                $error = '请填写全部字段。';
            } elseif (strlen($new) < 8) {
                $error = '新密码至少 8 位。';
            } elseif ($new !== $new2) {
                $error = '两次输入的新密码不一致。';
            } elseif ($new === $cur) {
                $error = '新密码不能与当前密码相同。';
            } elseif (!password_verify($cur, $users[$idx]['password'] ?? '')) {
                $error = '当前密码不正确。';
            } else {
                // 2026-09-28: 改为原子读改写。此前是「锁外读整个 users.json → 改一项 → 整份写回」,
                // 期间任何并发写(注册/改名/其他会话)都会被这份陈旧快照覆盖 ——
                // 用户真实遇到过: 改密成功了, 被并发的另一进程写回旧哈希。
                $me = $_SESSION['username'] ?? '';
                $wrote = jsonUpdate($usersFile, function (array &$users) use ($me, $new) {
                    $i = matchUserIndex($users, $me);
                    if ($i < 0) return false;                       // 账号没了 → 不写盘
                    $users[$i]['password'] = password_hash($new, PASSWORD_BCRYPT);
                    $users[$i]['password_changed_at'] = date('Y-m-d H:i:s');
                });
                if ($wrote === false) {
                    $error = '写入失败：users.json 不可写（检查文件权限）。';
                } else {
                    session_regenerate_id(true);   // 防会话固定; 当前会话保持登录(刚验过身份)
                    $success = '密码已更新。下次登录请用新密码。';
                }
            }
        }

        else {
            $error = '未知操作。';
        }
    }
}

// 重新读取当前账号信息用于展示
$users = json_decode(@file_get_contents($usersFile) ?: '[]', true);
$meIdx = is_array($users) ? matchUserIndex($users, $_SESSION['username'] ?? '') : -1;
$me = $meIdx >= 0 ? $users[$meIdx] : [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = 'Account · 账号设置';
$fonts = 'basic';
require __DIR__ . '/../includes/head.php';
?>
<style>
body { font-family: var(--font-body); background-color: var(--bg-deep); color: var(--text-primary);
  line-height: 1.7; min-height: 100vh; overflow-x: hidden; }

.acct { max-width: 500px; margin: 0 auto; padding: 120px 24px 60px; }
.acct-back { display:inline-flex; align-items:center; gap:6px; font-family:var(--font-display);
  font-size:0.72rem; color:var(--text-muted); text-decoration:none; letter-spacing:0.05em;
  margin-bottom:30px; transition:var(--transition-smooth); }
.acct-back:hover { color: var(--accent); }

.acct h1 { font-family:var(--font-display); font-size:1.6rem; font-weight:700;
  color:var(--accent); letter-spacing:0.04em; margin-bottom:10px; text-align:center; }
.acct .subtitle { color:var(--text-muted); font-size:0.85rem; margin-bottom:30px; text-align:center; }

.acct-card { background:var(--bg-card); border:1px solid var(--border-glow);
  border-radius:var(--radius-lg); padding:28px; margin-bottom:20px; }
.acct-card h2 { font-family:var(--font-display); font-size:0.95rem; font-weight:600;
  color:var(--text-primary); letter-spacing:0.05em; margin-bottom:18px; }

.acct-who { display:flex; align-items:baseline; gap:10px; padding-bottom:16px; margin-bottom:22px;
  border-bottom:1px solid var(--border-card); font-size:0.8rem; color:var(--text-muted); }
.acct-who b { color:var(--text-primary); font-weight:600; }

.field { margin-bottom:16px; }
.field label { display:block; font-size:0.8rem; color:var(--text-secondary); margin-bottom:6px; }
.field input { width:100%; padding:11px 15px; background:rgba(255,255,255,0.03);
  border:1px solid var(--border-glow); border-radius:var(--radius-sm);
  color:var(--text-primary); font-size:0.92rem; font-family:inherit; outline:none;
  transition:var(--transition-smooth); }
.field input:focus { border-color:var(--primary); box-shadow:0 0 22px rgba(91,160,224,0.12); }
.field .hint { font-size:0.7rem; color:var(--text-muted); margin-top:4px; }

.btn { width:100%; padding:13px 30px; margin-top:6px; background:linear-gradient(135deg,var(--primary),var(--accent));
  border:none; border-radius:var(--radius-pill); color:var(--text-primary);
  font-family:inherit; font-weight:600; font-size:0.92rem; letter-spacing:1px; cursor:pointer;
  box-shadow:0 0 26px rgba(91,160,224,0.26); transition:var(--transition-smooth); }
.btn:hover { transform:translateY(-2px); box-shadow:0 0 36px rgba(91,160,224,0.42); }

.alert { padding:11px 15px; border-radius:var(--radius-sm); margin-bottom:18px; font-size:0.84rem; text-align:center; }
.alert-error { background:rgba(240,128,96,0.1); border:1px solid rgba(240,128,96,0.22); color:var(--secondary); }
.alert-ok { background:rgba(91,160,224,0.1); border:1px solid rgba(91,160,224,0.22); color:var(--primary); }

.acct-note { margin-top:16px; font-size:0.72rem; color:var(--text-muted); line-height:1.75; }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/background.php'; ?>
<?php require __DIR__ . '/../includes/navbar.php'; ?>

<main class="acct">
  <a href="<?= $BASE ?>/" class="acct-back">&larr; Command Center / 指挥中心</a>

  <h1>Account / 账号</h1>
  <p class="subtitle">Nickname &amp; Password / 昵称与密码</p>

  <?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success !== ''): ?><div class="alert alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="acct-card">
    <div class="acct-who">
      <span>当前账号</span><b><?= htmlspecialchars($_SESSION['username'] ?? '') ?></b>
      <?php if ($isAdmin): ?><span style="color:var(--secondary);font-size:0.68rem;">[ADMIN]</span><?php endif; ?>
    </div>

    <h2>修改昵称 / Nickname</h2>
    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="action" value="nickname">
      <div class="field">
        <label for="nickname">新昵称</label>
        <input type="text" id="nickname" name="nickname" required minlength="2" maxlength="20"
               value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
        <div class="hint">2-20 字，支持中英文数字下划线；<b>不可与已有账号重名</b>。</div>
      </div>
      <button type="submit" class="btn">更新昵称</button>
    </form>
    <div class="acct-note">
      · 改昵称会<b>同时更新你过去所有评论的署名</b>，否则旧评论将认不出归属、你自己也删不掉。
    </div>
  </div>

  <div class="acct-card">
    <h2>修改密码 / Password</h2>
    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="action" value="password">

      <div class="field">
        <label for="current_password">当前密码 / Current</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
      </div>
      <div class="field">
        <label for="new_password">新密码 / New</label>
        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
        <div class="hint">至少 8 位。建议混合大小写 + 数字 + 符号。</div>
      </div>
      <div class="field">
        <label for="new_password2">确认新密码 / Confirm</label>
        <input type="password" id="new_password2" name="new_password2" required minlength="8" autocomplete="new-password">
      </div>
      <button type="submit" class="btn">更新密码</button>
    </form>
    <div class="acct-note">
      · 改密后当前会话保持登录，其他设备上的旧会话不会立刻失效。<br>
      · 忘记密码需上服务器直接编辑 <code>users.json</code>（无邮箱找回，见部署手册）。
      <?php if (!empty($me['password_changed_at'])): ?>
      <br>· 上次改密：<b><?= htmlspecialchars($me['password_changed_at']) ?></b>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
