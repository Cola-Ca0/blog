<?php
/**
 * 可乐的留言板 - 登录/注册页面
 * 功能：用户登录、注册、人机验证（数学CAPTCHA）
 */
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
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

// ========== 人机验证：生成数学题 ==========
function generateCaptcha() {
    $a = rand(1, 20);
    $b = rand(1, 20);
    $op = rand(0, 1) ? '+' : '-';
    if ($op === '-') {
        // 确保结果非负
        if ($a < $b) { $tmp = $a; $a = $b; $b = $tmp; }
        $answer = $a - $b;
    } else {
        $answer = $a + $b;
    }
    $question = "$a $op $b = ?";
    // 用 password_hash 存储答案，提交时验证
    $hash = password_hash((string)$answer, PASSWORD_BCRYPT);
    return ['question' => $question, 'hash' => $hash];
}

// 初始化验证码
$captcha = generateCaptcha();

$error = '';
$success = '';
$activeTab = 'login'; // 默认登录 tab

// ========== 处理登录 ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $captchaInput = trim($_POST['captcha'] ?? '');
    $captchaHash = $_POST['captcha_hash'] ?? '';

    // 验证人机验证
    if (!password_verify($captchaInput, $captchaHash)) {
        $error = '人机验证答案错误，请重试！';
        $activeTab = 'login';
        $captcha = generateCaptcha(); // 刷新验证码
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
            setcookie('username', $username, time() + 86400 * 7, '/');
            setcookie('is_admin', $isAdmin ? 'true' : 'false', time() + 86400 * 7, '/');
            header('Location: main.php');
            exit;
        } else {
            $error = '用户名或密码错误！';
            $activeTab = 'login';
            $captcha = generateCaptcha();
        }
    }
}

// ========== 处理注册 ==========
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
        // 检查是否重名
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $error = '该用户名已被注册，请换一个！';
                $activeTab = 'register';
                $captcha = generateCaptcha();
                break;
            }
        }

        if ($error === '') {
            // 注册新用户
            $users[] = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'role' => 'user',
                'created_at' => date('Y-m-d H:i:s')
            ];
            saveUsers($users);

            // 自动登录
            setcookie('username', $username, time() + 86400 * 7, '/');
            setcookie('is_admin', 'false', time() + 86400 * 7, '/');
            header('Location: main.php');
            exit;
        }
    }
}

// 如果已登录，直接跳转
if (isset($_COOKIE['username']) && $_COOKIE['username'] !== '') {
    header('Location: main.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍹 可乐的留言板 - 登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Poppins', system-ui, sans-serif;
            background: #0a0e1a;
            color: #d0d8e8;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
            background-image: 
                radial-gradient(2px 2px at 20% 30%, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 40% 70%, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 60% 20%, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 80% 80%, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 10% 90%, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 90% 10%, #fff, rgba(0,0,0,0));
            background-size: 200px 200px, 300px 300px, 250px 250px, 350px 350px, 150px 150px, 150px 150px;
            animation: twinkle 4s infinite alternate;
        }
        @keyframes twinkle { 0% { opacity: 0.5; } 100% { opacity: 1; } }

        .login-container {
            width: 440px;
            max-width: 95%;
            background: rgba(12, 18, 34, 0.88);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 40px 35px;
            box-shadow: 0 0 60px rgba(0, 180, 255, 0.15), inset 0 0 80px rgba(0, 180, 255, 0.02);
            border: 1px solid rgba(0, 180, 255, 0.15);
            position: relative;
            z-index: 2;
        }

        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #00d4ff, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 40px rgba(0, 212, 255, 0.25);
            display: inline-block;
        }
        .login-header .sub {
            color: #6a7a9e;
            font-weight: 300;
            letter-spacing: 3px;
            font-size: 0.8rem;
            margin-top: 4px;
        }

        /* Tab 切换 */
        .tab-nav {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            background: rgba(255,255,255,0.03);
            border-radius: 14px;
            padding: 4px;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .tab-nav .tab-btn {
            flex: 1;
            padding: 12px;
            text-align: center;
            border: none;
            border-radius: 11px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #6a7a9e;
            background: transparent;
            font-family: inherit;
        }
        .tab-nav .tab-btn.active {
            background: linear-gradient(135deg, #00d4ff, #7c3aed);
            color: #fff;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.3);
        }
        .tab-nav .tab-btn:hover:not(.active) {
            color: #b0c4de;
            background: rgba(255,255,255,0.04);
        }

        /* 表单面板 */
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .field label {
            display: block;
            font-size: 0.85rem;
            margin-bottom: 5px;
            color: #8899bb;
            letter-spacing: 0.3px;
        }
        .field input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            color: #e6f0ff;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
            font-family: inherit;
        }
        .field input:focus {
            border-color: #00d4ff;
            box-shadow: 0 0 25px rgba(0, 212, 255, 0.1);
            background: rgba(255,255,255,0.06);
        }

        /* 验证码行 */
        .captcha-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }
        .captcha-row .captcha-field {
            flex: 1;
        }
        .captcha-display {
            background: rgba(0, 212, 255, 0.08);
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 14px;
            padding: 12px 18px;
            color: #00d4ff;
            font-weight: 700;
            font-size: 1.1rem;
            white-space: nowrap;
            letter-spacing: 2px;
            user-select: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            min-width: 110px;
        }
        .captcha-display:hover {
            background: rgba(0, 212, 255, 0.15);
            box-shadow: 0 0 15px rgba(0, 212, 255, 0.15);
        }
        .captcha-hint {
            font-size: 0.7rem;
            color: #5a6a82;
            margin-top: 2px;
        }

        /* 提交按钮 */
        .btn-submit {
            width: 100%;
            padding: 14px 32px;
            background: linear-gradient(135deg, #00d4ff, #7c3aed);
            border: none;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 1px;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
            font-family: inherit;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 45px rgba(0, 212, 255, 0.5);
        }
        .btn-submit:active {
            transform: scale(0.96);
        }

        .btn-register {
            background: linear-gradient(135deg, #a855f7, #fbbf24);
            box-shadow: 0 0 25px rgba(168, 85, 247, 0.3);
        }
        .btn-register:hover {
            box-shadow: 0 0 40px rgba(168, 85, 247, 0.5);
        }

        /* 提示信息 */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            text-align: center;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #ef4444;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.25);
            color: #22c55e;
        }

        /* 底部 */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #3a4a62;
            border-top: 1px solid rgba(255,255,255,0.03);
            padding-top: 16px;
        }

        .back-link {
            display: inline-block;
            margin-top: 16px;
            color: #6a7a9e;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #00d4ff;
        }

        @media (max-width: 500px) {
            .login-container { padding: 24px 18px; }
            .login-header h1 { font-size: 1.6rem; }
            .captcha-row { flex-direction: column; gap: 8px; }
            .captcha-display { min-width: auto; text-align: center; }
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        ::-webkit-scrollbar-thumb { background: #2a3a5a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #00d4ff; }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- 头部 -->
        <div class="login-header">
            <h1>🍹 可乐的留言板</h1>
            <div class="sub">✦ 登录以继续 ✦</div>
        </div>

        <!-- Tab 切换 -->
        <div class="tab-nav">
            <button class="tab-btn active" id="tabLoginBtn" onclick="switchTab('login')">🔑 登录</button>
            <button class="tab-btn" id="tabRegisterBtn" onclick="switchTab('register')">📝 注册</button>
        </div>

        <!-- 错误/成功提示 -->
        <?php if ($error !== ''): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- ==================== 登录面板 ==================== -->
        <div class="tab-panel active" id="panelLogin">
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="captcha_hash" value="<?= htmlspecialchars($captcha['hash']) ?>">
                <div class="form-group">
                    <div class="field">
                        <label for="username">👤 用户名</label>
                        <input type="text" id="username" name="username" placeholder="输入用户名" maxlength="20" required autocomplete="username">
                    </div>
                    <div class="field">
                        <label for="password">🔒 密码</label>
                        <input type="password" id="password" name="password" placeholder="输入密码" required autocomplete="current-password">
                    </div>
                    <!-- 人机验证 -->
                    <div class="field">
                        <label>🤖 人机验证</label>
                        <div class="captcha-row">
                            <div class="captcha-display" id="captchaDisplay1" onclick="refreshCaptcha('login')" title="点击刷新验证码">
                                <?= htmlspecialchars($captcha['question']) ?>
                            </div>
                            <div class="captcha-field">
                                <input type="text" name="captcha" placeholder="请输入答案" required autocomplete="off">
                                <div class="captcha-hint">点击左侧算式可刷新</div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">🚀 登录</button>
                </div>
            </form>
        </div>

        <!-- ==================== 注册面板 ==================== -->
        <div class="tab-panel" id="panelRegister">
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="reg_captcha_hash" id="regCaptchaHash" value="<?= htmlspecialchars($captcha['hash']) ?>">
                <div class="form-group">
                    <div class="field">
                        <label for="reg_username">👤 用户名</label>
                        <input type="text" id="reg_username" name="reg_username" placeholder="2-20 个字符，支持中英文" maxlength="20" required autocomplete="username">
                    </div>
                    <div class="field">
                        <label for="reg_password">🔒 密码</label>
                        <input type="password" id="reg_password" name="reg_password" placeholder="至少 4 位密码" required autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="reg_password2">🔒 确认密码</label>
                        <input type="password" id="reg_password2" name="reg_password2" placeholder="再次输入密码" required autocomplete="new-password">
                    </div>
                    <!-- 人机验证 -->
                    <div class="field">
                        <label>🤖 人机验证</label>
                        <div class="captcha-row">
                            <div class="captcha-display" id="captchaDisplay2" onclick="refreshCaptcha('register')" title="点击刷新验证码">
                                <?= htmlspecialchars($captcha['question']) ?>
                            </div>
                            <div class="captcha-field">
                                <input type="text" name="reg_captcha" placeholder="请输入答案" required autocomplete="off">
                                <div class="captcha-hint">点击左侧算式可刷新</div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit btn-register">📝 注册并登录</button>
                </div>
            </form>
        </div>

        <div class="login-footer">
            <?php
            $quotes = [
                '🚴 "骑行的终点是风，也是自由。"',
                '📷 "按下快门，就是锁住时间。"',
                '💻 "0和1的宇宙里，漏洞是最好的老师。"',
            ];
            echo $quotes[array_rand($quotes)];
            ?>
            <br>
            <a href="main.php" class="back-link">← 以游客身份浏览留言板</a>
        </div>
    </div>

    <!-- 拖尾 canvas（与 main.php 风格一致） -->
    <canvas id="trailCanvas" style="position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999;"></canvas>

    <script>
        // ========== Tab 切换 ==========
        function switchTab(tab) {
            const loginBtn = document.getElementById('tabLoginBtn');
            const registerBtn = document.getElementById('tabRegisterBtn');
            const loginPanel = document.getElementById('panelLogin');
            const registerPanel = document.getElementById('panelRegister');

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

        // 保持上次选中的 tab（当表单提交出错时）
        <?php if ($activeTab === 'register'): ?>
            switchTab('register');
        <?php endif; ?>

        // ========== 刷新验证码（AJAX 方式不可用，用页面刷新模拟） ==========
        function refreshCaptcha(type) {
            // 简单刷新：重新加载页面并保持当前 tab
            const url = new URL(window.location.href);
            url.searchParams.set('tab', type);
            window.location.href = url.toString();
        }

        // ========== 鼠标拖尾（与 main.php 一致） ==========
        const canvas = document.getElementById('trailCanvas');
        const ctx = canvas.getContext('2d');
        let W, H;
        function resizeCanvas() {
            W = window.innerWidth; H = window.innerHeight;
            canvas.width = W; canvas.height = H;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        let trailEnabled = true;
        let particles = [];

        class Particle {
            constructor(x, y) {
                this.x = x; this.y = y;
                this.size = Math.random() * 5 + 2;
                this.life = 1.0;
                this.decay = 0.018 + Math.random() * 0.02;
                this.color = `hsl(${200 + Math.random() * 40}, 80%, 60%)`;
            }
            update() {
                this.x += (Math.random() - 0.5) * 1.0;
                this.y += (Math.random() - 0.5) * 1.0;
                this.life -= this.decay;
                return this.life > 0;
            }
            draw(ctx) {
                const alpha = this.life * 0.7;
                ctx.globalAlpha = alpha;
                ctx.fillStyle = this.color;
                ctx.shadowColor = 'rgba(0, 212, 255, 0.4)';
                ctx.shadowBlur = 12;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
                ctx.shadowBlur = 0;
            }
        }

        document.addEventListener('mousemove', (e) => {
            if (trailEnabled) {
                for (let i = 0; i < 2; i++) {
                    particles.push(new Particle(e.clientX + (Math.random()-0.5)*8, e.clientY + (Math.random()-0.5)*8));
                }
            }
        });

        function animateTrail() {
            if (trailEnabled) {
                particles = particles.filter(p => p.update());
                if (particles.length > 150) particles.splice(0, particles.length - 150);
            }
            ctx.clearRect(0, 0, W, H);
            particles.forEach(p => p.draw(ctx));
            requestAnimationFrame(animateTrail);
        }
        animateTrail();
    </script>
</body>
</html>
