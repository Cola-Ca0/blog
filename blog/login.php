<?php
/**
 * Cola_CaO 博客登录/注册页面
 * 功能：用户登录、注册、数学CAPTCHA人机验证
 * 认证：PHP $_SESSION (server-side, unforgeable)
 */

session_start();

// ========== 登出处理 ==========
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    setcookie('username', '', time() - 3600, '/');
    header('Location: index.php');
    exit;
}

date_default_timezone_set('Asia/Shanghai');

$usersFile = __DIR__ . '/users.json';

function loadUsers() {
    global $usersFile;
    if (file_exists($usersFile)) {
        $data = json_decode(file_get_contents($usersFile), true);
        if (is_array($data)) return $data;
    }
    return [];
}

function saveUsers($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 生成数学验证码
function generateCaptcha() {
    $a = rand(1, 20);
    $b = rand(1, 20);
    $op = rand(0, 1) ? '+' : '-';
    if ($op === '-') {
        if ($a < $b) { $tmp = $a; $a = $b; $b = $tmp; }
        $answer = $a - $b;
    } else {
        $answer = $a + $b;
    }
    $question = "$a $op $b = ?";
    $hash = password_hash((string)$answer, PASSWORD_BCRYPT);
    return ['question' => $question, 'hash' => $hash];
}

$captcha = generateCaptcha();
$error = '';
$success = '';
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

// ========== 登录处理 ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captchaInput = trim($_POST['captcha'] ?? '');
    $captchaHash = $_POST['captcha_hash'] ?? '';

    if (!password_verify($captchaInput, $captchaHash)) {
        $error = '人机验证答案错误，请重试！';
        $activeTab = 'login';
        $captcha = generateCaptcha();
    } elseif ($username === '' || $password === '') {
        $error = '请填写用户名和密码！';
        $activeTab = 'login';
        $captcha = generateCaptcha();
    } else {
        $users = loadUsers();
        $loggedIn = false;
        $isAdmin = false;

        foreach ($users as $user) {
            if ($user['username'] === $username && password_verify($password, $user['password'])) {
                $loggedIn = true;
                $isAdmin = ($user['role'] ?? 'user') === 'admin';
                break;
            }
        }

        if ($loggedIn) {
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = $isAdmin;
            setcookie('username', $username, [
                'expires' => time() + 86400 * 7,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误！';
            $activeTab = 'login';
            $captcha = generateCaptcha();
        }
    }
}

// ========== 注册处理 ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['reg_username'] ?? '');
    $password = trim($_POST['reg_password'] ?? '');
    $password2 = trim($_POST['reg_password2'] ?? '');
    $captchaInput = trim($_POST['reg_captcha'] ?? '');
    $captchaHash = $_POST['reg_captcha_hash'] ?? '';

    if (!password_verify($captchaInput, $captchaHash)) {
        $error = '人机验证答案错误，请重试！';
        $activeTab = 'register';
        $captcha = generateCaptcha();
    } elseif ($username === '' || $password === '') {
        $error = '请填写用户名和密码！';
        $activeTab = 'register';
        $captcha = generateCaptcha();
    } elseif (strlen($username) < 2 || strlen($username) > 20) {
        $error = '用户名长度需为 2-20 个字符！';
        $activeTab = 'register';
        $captcha = generateCaptcha();
    } elseif (strlen($password) < 4) {
        $error = '密码长度至少为 4 位！';
        $activeTab = 'register';
        $captcha = generateCaptcha();
    } elseif ($password !== $password2) {
        $error = '两次输入的密码不一致！';
        $activeTab = 'register';
        $captcha = generateCaptcha();
    } elseif (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
        $error = '用户名只能包含字母、数字、下划线和中文！';
        $activeTab = 'register';
        $captcha = generateCaptcha();
    } else {
        $users = loadUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $error = '该用户名已被注册，请换一个！';
                $activeTab = 'register';
                $captcha = generateCaptcha();
                break;
            }
        }

        if ($error === '') {
            $users[] = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => 'user',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveUsers($users);

            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = false;
            setcookie('username', $username, [
                'expires' => time() + 86400 * 7,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            header('Location: index.php');
            exit;
        }
    }
}

// 已登录直接跳转
if (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cola_CaO · Sign In</title>
    <link rel="stylesheet" href="includes/tokens.css">
    <link rel="stylesheet" href="includes/shared.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&family=Great+Vibes&family=Noto+Serif+SC:wght@300;400;500;600;700&family=Quicksand:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background: var(--bg-deep);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
            position: relative;
            overflow: hidden;
        }

        /* Background layers */
        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(91,160,224,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(91,160,224,0.04) 1px, transparent 1px);
            background-size: 56px 56px;
            pointer-events: none; z-index: 0;
        }

        .bg-orbs { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .orb {
            position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.08;
            animation: orb-drift 20s ease-in-out infinite;
        }
        .orb-a {
            width: 500px; height: 500px;
            background: radial-gradient(circle, var(--primary), transparent 70%);
            top: -180px; left: -120px;
        }
        .orb-b {
            width: 450px; height: 450px;
            background: radial-gradient(circle, var(--accent), transparent 70%);
            bottom: -150px; right: -150px; animation-delay: -10s; animation-duration: 24s;
        }
        .orb-c {
            width: 350px; height: 350px;
            background: radial-gradient(circle, var(--secondary), transparent 70%);
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            animation-delay: -5s; opacity: 0.04; animation-duration: 18s;
        }

        @keyframes orb-drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(35px, -25px) scale(1.06); }
            50% { transform: translate(-18px, 18px) scale(0.94); }
            75% { transform: translate(-30px, -10px) scale(1.03); }
        }

        /* Scanline overlay — matches blog index */
        .scanlines {
            position: fixed; inset: 0; pointer-events: none; z-index: 1;
            background: repeating-linear-gradient(
                0deg, transparent, transparent 2px,
                rgba(0,0,0,0.025) 2px, rgba(0,0,0,0.025) 4px
            );
        }

        /* Login container — soft-container tier (20px) */
        .login-container {
            width: 440px;
            max-width: 95%;
            background: rgba(8, 28, 48, 0.72);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow:
                0 0 60px rgba(91, 160, 224, 0.12),
                0 0 120px rgba(142, 208, 232, 0.05),
                inset 0 0 80px rgba(91, 160, 224, 0.02);
            border: 1px solid rgba(91, 160, 224, 0.2);
            position: relative;
            z-index: 2;
        }

        .login-header { text-align: center; margin-bottom: 28px; }
        .login-header .shark-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            margin: 0 auto 16px;
            box-shadow: 0 0 20px rgba(91, 160, 224, 0.4);
        }
        .login-header h1 {
            font-family: 'Rajdhani', 'Exo 2', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 3px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        /* Tab */
        .tab-nav {
            display: flex; gap: 0; margin-bottom: 24px;
            background: rgba(255,255,255,0.03); border-radius: 50px; padding: 4px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .tab-nav .tab-btn {
            flex: 1; padding: 12px; text-align: center; border: none; border-radius: 50px;
            font-weight: 600; font-size: 0.9rem; cursor: pointer;
            transition: all 0.3s ease; color: var(--text-muted); background: transparent;
            font-family: inherit;
        }
        .tab-nav .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            box-shadow: 0 0 22px rgba(91, 160, 224, 0.3);
        }
        .tab-nav .tab-btn:hover:not(.active) {
            color: var(--text-secondary); background: rgba(255,255,255,0.04);
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .form-group { display: flex; flex-direction: column; gap: 16px; }
        .field label {
            display: block; font-size: 0.82rem; margin-bottom: 5px; color: var(--text-secondary); letter-spacing: 0.3px;
        }
        .field input {
            width: 100%; padding: 12px 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; color: var(--text-primary); font-size: 0.95rem;
            transition: all 0.3s ease; outline: none; font-family: inherit;
        }
        .field input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 24px rgba(91, 160, 224, 0.1);
            background: rgba(255,255,255,0.05);
        }

        .captcha-row { display: flex; gap: 12px; align-items: flex-end; }
        .captcha-row .captcha-field { flex: 1; }
        .captcha-display {
            background: rgba(91, 160, 224, 0.07);
            border: 1px solid rgba(91, 160, 224, 0.18);
            border-radius: 14px; padding: 12px 18px;
            color: var(--accent); font-weight: 700; font-size: 1.05rem;
            white-space: nowrap; letter-spacing: 2px; user-select: none;
            cursor: pointer; transition: all 0.2s ease; text-align: center; min-width: 110px;
        }
        .captcha-display:hover {
            background: rgba(91, 160, 224, 0.14);
            box-shadow: 0 0 14px rgba(91, 160, 224, 0.15);
        }
        .captcha-hint { font-size: 0.68rem; color: var(--text-muted); margin-top: 2px; }

        .btn-submit {
            width: 100%; padding: 14px 32px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none; border-radius: 50px; color: #fff;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s ease; letter-spacing: 1px;
            box-shadow: 0 0 28px rgba(91, 160, 224, 0.28);
            font-family: inherit;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 40px rgba(91, 160, 224, 0.45);
        }
        .btn-submit:active { transform: scale(0.96); }

        .btn-register-submit {
            background: linear-gradient(135deg, var(--primary), #22c55e);
            box-shadow: 0 0 24px rgba(240, 128, 96, 0.25);
        }
        .btn-register-submit:hover {
            box-shadow: 0 0 36px rgba(240, 128, 96, 0.4);
        }

        .alert {
            padding: 12px 16px; border-radius: 14px; margin-bottom: 16px;
            font-size: 0.85rem; text-align: center;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.22);
            color: #ef4444;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.22);
            color: #22c55e;
        }

        .login-footer {
            text-align: center; margin-top: 20px; font-size: 0.78rem;
            color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.03); padding-top: 16px;
        }
        .back-link {
            display: inline-block; margin-top: 14px;
            color: var(--text-muted); text-decoration: none; font-size: 0.82rem; transition: color 0.2s;
        }
        .back-link:hover { color: var(--accent); }

        @media (max-width: 500px) {
            .login-container { padding: 24px 18px; }
            .login-header h1 { font-size: 1.4rem; }
            .captcha-row { flex-direction: column; gap: 8px; }
            .captcha-display { min-width: auto; text-align: center; }
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        ::-webkit-scrollbar-thumb { background: #2a4a6a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }
    </style>
</head>
<body>

<!-- Background layers -->
<div class="bg-grid"></div>
<div class="bg-orbs">
    <div class="orb orb-a"></div>
    <div class="orb orb-b"></div>
    <div class="orb orb-c"></div>
</div>
<div class="scanlines"></div>

<div class="login-container">
    <div class="login-header">
        <div class="shark-icon"></div>
        <h1>Cola_CaO <span style="font-weight:300;color:var(--text-muted);font-size:0.7em;">&middot; Sign In</span></h1>
    </div>

    <div class="tab-nav">
        <button class="tab-btn active" id="tabLoginBtn" onclick="switchTab('login')">登录 / Sign In</button>
        <button class="tab-btn" id="tabRegisterBtn" onclick="switchTab('register')">注册 / Register</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- 登录面板 -->
    <div class="tab-panel active" id="panelLogin">
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="captcha_hash" value="<?= htmlspecialchars($captcha['hash']) ?>">
            <div class="form-group">
                <div class="field">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" placeholder="输入用户名" maxlength="20" required autocomplete="username">
                </div>
                <div class="field">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" placeholder="输入密码" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label>人机验证</label>
                    <div class="captcha-row">
                        <div class="captcha-display" id="captchaDisplay1" onclick="refreshCaptcha()" title="点击刷新验证码">
                            <?= htmlspecialchars($captcha['question']) ?>
                        </div>
                        <div class="captcha-field">
                            <input type="text" name="captcha" placeholder="请输入答案" required autocomplete="off">
                            <div class="captcha-hint">点击左侧算式可刷新</div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit">登录</button>
            </div>
        </form>
    </div>

    <!-- 注册面板 -->
    <div class="tab-panel" id="panelRegister">
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="reg_captcha_hash" id="regCaptchaHash" value="<?= htmlspecialchars($captcha['hash']) ?>">
            <div class="form-group">
                <div class="field">
                    <label for="reg_username">用户名</label>
                    <input type="text" id="reg_username" name="reg_username" placeholder="2-20 个字符，支持中英文" maxlength="20" required autocomplete="username">
                </div>
                <div class="field">
                    <label for="reg_password">密码</label>
                    <input type="password" id="reg_password" name="reg_password" placeholder="至少 4 位密码" required autocomplete="new-password">
                </div>
                <div class="field">
                    <label for="reg_password2">确认密码</label>
                    <input type="password" id="reg_password2" name="reg_password2" placeholder="再次输入密码" required autocomplete="new-password">
                </div>
                <div class="field">
                    <label>人机验证</label>
                    <div class="captcha-row">
                        <div class="captcha-display" id="captchaDisplay2" onclick="refreshCaptcha()" title="点击刷新验证码">
                            <?= htmlspecialchars($captcha['question']) ?>
                        </div>
                        <div class="captcha-field">
                            <input type="text" name="reg_captcha" placeholder="请输入答案" required autocomplete="off">
                            <div class="captcha-hint">点击左侧算式可刷新</div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-register-submit">注册 / Register并登录</button>
            </div>
        </form>
    </div>

    <div class="login-footer">
        「在深海的静谧中，等待你的归来。」
        <br>
        <a href="index.php" class="back-link">← 返回博客主页</a>
    </div>
</div>

<script>
    // Tab 切换
    function switchTab(tab) {
        var loginBtn = document.getElementById('tabLoginBtn');
        var registerBtn = document.getElementById('tabRegisterBtn');
        var loginPanel = document.getElementById('panelLogin');
        var registerPanel = document.getElementById('panelRegister');

        if (tab === 'login') {
            loginBtn.classList.add('active');
            registerBtn.classList.remove('active');
            loginPanel.classList.add('active');
            registerPanel.classList.remove('active');
        } else {
            registerBtn.classList.add('active');
            loginBtn.classList.remove('active');
            registerPanel.classList.add('active');
            loginPanel.classList.remove('active');
        }
    }

    // 保持上次选中的 tab
    <?php if ($activeTab === 'register'): ?>
        switchTab('register');
    <?php endif; ?>

    // 刷新验证码
    function refreshCaptcha() {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', document.getElementById('panelRegister').classList.contains('active') ? 'register' : 'login');
        window.location.href = url.toString();
    }

    // Background matches blog index.php (grid + orbs + scanlines)
</script>

</body>
</html>
