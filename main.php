<?php
/**
 * 可乐的留言板 - 完整版
 * 功能：留言、登录、管理员删除/置顶、标签、彩蛋、折叠拖尾、折叠小游戏、滚动支持
 */
date_default_timezone_set('Asia/Shanghai');
$dataFile = __DIR__ . '/messages.json';
$statsFile = __DIR__ . '/stats.json';
$journalFile = __DIR__ . '/journal.json';
$usersFile = __DIR__ . '/users.json';
$uploadDir = __DIR__ . '/uploads/';

function loadUsers() {
    global $usersFile;
    if (file_exists($usersFile)) {
        $data = json_decode(file_get_contents($usersFile), true);
        if (is_array($data)) return $data;
    }
    return [];
}
function loadStats() {
    global $statsFile;
    if (file_exists($statsFile)) {
        $data = json_decode(file_get_contents($statsFile), true);
        if (is_array($data)) return $data;
    }
    return ['total' => 0, 'today' => 0, 'last_date' => date('Y-m-d')];
}
function saveStats($stats) {
    global $statsFile;
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
}
function loadMessages() {
    global $dataFile;
    if (file_exists($dataFile)) {
        $json = file_get_contents($dataFile);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return [];
}
function saveMessages($messages) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($messages, JSON_PRETTY_PRINT));
}
function loadJournal() {
    global $journalFile;
    if (file_exists($journalFile)) {
        $data = json_decode(file_get_contents($journalFile), true);
        if (is_array($data)) return $data;
    }
    return [];
}
function saveJournal($journal) {
    global $journalFile;
    file_put_contents($journalFile, json_encode($journal, JSON_PRETTY_PRINT));
}

// 登录处理
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $users = loadUsers();
    $loggedIn = false;
    $isAdminUser = false;
    // 从 users.json 验证
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $loggedIn = true;
            $isAdminUser = ($user['role'] ?? 'user') === 'admin';
            break;
        }
    }
    if ($loggedIn) {
        setcookie('username', $username, time() + 86400 * 7, '/');
        setcookie('is_admin', $isAdminUser ? 'true' : 'false', time() + 86400 * 7, '/');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = '用户名或密码错误！';
    }
}
if (isset($_GET['logout'])) {
    setcookie('username', '', time() - 3600, '/');
    setcookie('is_admin', '', time() - 3600, '/');
    header('Location: login.php');
    exit;
}

$is_admin = isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === 'true';
$logged_user = isset($_COOKIE['username']) ? $_COOKIE['username'] : null;

// 管理员操作
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === 'true') {
        $id = $_POST['id'];
        $messages = loadMessages();
        foreach ($messages as $key => $msg) {
            if ($msg['id'] === $id) {
                array_splice($messages, $key, 1);
                break;
            }
        }
        saveMessages($messages);
        $stats = loadStats();
        $stats['total'] = max(0, $stats['total'] - 1);
        saveStats($stats);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
if (isset($_POST['action']) && $_POST['action'] === 'delete_all') {
    if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === 'true') {
        saveMessages([]);
        $stats = loadStats();
        $stats['total'] = 0;
        $stats['today'] = 0;
        saveStats($stats);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
if (isset($_POST['action']) && $_POST['action'] === 'toggle_pin' && isset($_POST['id'])) {
    if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] === 'true') {
        $id = $_POST['id'];
        $messages = loadMessages();
        foreach ($messages as &$msg) {
            if ($msg['id'] === $id) {
                $msg['is_pinned'] = !isset($msg['is_pinned']) ? true : !$msg['is_pinned'];
                break;
            }
        }
        saveMessages($messages);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// 留言提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !isset($_POST['login'])) {
    $name = trim($_POST['name']) ?: '匿名';
    if (isset($_COOKIE['username']) && $name === '匿名') $name = $_COOKIE['username'];
    $message = trim($_POST['message']);
    $tag = isset($_POST['tag']) ? $_POST['tag'] : '🍹 日常';
    if ($message !== '') {
        $messages = loadMessages();
        $messages[] = [
            'id' => uniqid() . '_' . rand(1000, 9999),
            'name' => htmlspecialchars($name),
            'message' => htmlspecialchars($message),
            'tag' => htmlspecialchars($tag),
            'time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'is_pinned' => false
        ];
        saveMessages($messages);
        $stats = loadStats();
        $stats['total']++;
        $today = date('Y-m-d');
        if ($stats['last_date'] !== $today) {
            $stats['today'] = 1;
            $stats['last_date'] = $today;
        } else {
            $stats['today']++;
        }
        saveStats($stats);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ===== 日志操作（仅管理员） =====
if ($is_admin && isset($_POST['journal_action'])) {
    if ($_POST['journal_action'] === 'add') {
        $title = trim($_POST['journal_title'] ?? '');
        $content = trim($_POST['journal_content'] ?? '');
        if ($title !== '' && $content !== '') {
            // 处理图片上传
            $imagePath = '';
            if (isset($_FILES['journal_image']) && $_FILES['journal_image']['error'] === UPLOAD_ERR_OK) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = strtolower(pathinfo($_FILES['journal_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allowed) && $_FILES['journal_image']['size'] < 10 * 1024 * 1024) {
                    $newName = 'journal_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    $dest = $uploadDir . $newName;
                    if (move_uploaded_file($_FILES['journal_image']['tmp_name'], $dest)) {
                        $imagePath = 'uploads/' . $newName;
                    }
                }
            }
            $journal = loadJournal();
            $journal[] = [
                'id' => uniqid('j_', true),
                'title' => htmlspecialchars($title),
                'content' => htmlspecialchars($content),
                'image' => $imagePath,
                'time' => date('Y-m-d H:i:s')
            ];
            saveJournal($journal);
            header('Location: ' . $_SERVER['PHP_SELF'] . '#journal-section');
            exit;
        }
    }
    if ($_POST['journal_action'] === 'delete' && isset($_POST['journal_id'])) {
        $jid = $_POST['journal_id'];
        $journal = loadJournal();
        foreach ($journal as $k => $entry) {
            if ($entry['id'] === $jid) {
                // 删除关联图片
                if (!empty($entry['image']) && file_exists(__DIR__ . '/' . $entry['image'])) {
                    unlink(__DIR__ . '/' . $entry['image']);
                }
                array_splice($journal, $k, 1);
                break;
            }
        }
        saveJournal($journal);
        header('Location: ' . $_SERVER['PHP_SELF'] . '#journal-section');
        exit;
    }
}

// 读取和排序
$messages = loadMessages();
usort($messages, function($a, $b) {
    $pA = isset($a['is_pinned']) && $a['is_pinned'] ? 1 : 0;
    $pB = isset($b['is_pinned']) && $b['is_pinned'] ? 1 : 0;
    if ($pA !== $pB) return $pB - $pA;
    return strtotime($b['time']) - strtotime($a['time']);
});
$stats = loadStats();
$journal = loadJournal();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍹 可乐的留言板</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Poppins', system-ui, sans-serif;
            background: #0a0e1a;
            color: #d0d8e8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        @keyframes twinkle { 0% { opacity: 0.75; } 100% { opacity: 1; } }
        body.no-twinkle { animation: none; opacity: 1; }
        .container {
            max-width: 880px;
            width: 100%;
            background: rgba(12, 18, 34, 0.88);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 40px 35px;
            box-shadow: 0 0 60px rgba(0, 180, 255, 0.15), inset 0 0 80px rgba(0, 180, 255, 0.02);
            border: 1px solid rgba(0, 180, 255, 0.15);
            position: relative;
            z-index: 2;
            margin-bottom: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 15px;
        }
        .header-left h1 {
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #00d4ff, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 40px rgba(0, 212, 255, 0.25);
            display: inline-block;
        }
        .header-left .sub { color: #6a7a9e; font-weight: 300; letter-spacing: 4px; font-size: 0.85rem; margin-top: 2px; }
        .header-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .user-info { color: #b0c4de; font-size: 0.95rem; background: rgba(255,255,255,0.05); padding: 6px 16px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.06); }
        .user-info i { color: #00d4ff; margin-right: 6px; }
        .btn-login, .btn-logout {
            padding: 8px 20px; border-radius: 30px; border: none; font-weight: 600; font-size: 0.9rem;
            cursor: pointer; transition: all 0.25s ease; text-decoration: none; display: inline-block;
        }
        .btn-login { background: linear-gradient(135deg, #00d4ff, #7c3aed); color: #fff; box-shadow: 0 0 20px rgba(0, 212, 255, 0.3); }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(0, 212, 255, 0.5); }
        .btn-logout { background: rgba(255,255,255,0.08); color: #d0d8e8; border: 1px solid rgba(255,255,255,0.1); }
        .btn-logout:hover { background: rgba(255,255,255,0.15); }

        /* 模态框 */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(6px);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #1a2340;
            padding: 40px 35px;
            border-radius: 28px;
            width: 360px;
            max-width: 90%;
            box-shadow: 0 0 60px rgba(0, 180, 255, 0.2);
            border: 1px solid rgba(0, 180, 255, 0.2);
        }
        .modal-box h2 { color: #e6f0ff; margin-bottom: 20px; text-align: center; }
        .modal-box label { display: block; color: #8899bb; margin: 12px 0 4px; font-size: 0.9rem; }
        .modal-box input {
            width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;
            color: #e6f0ff; font-size: 1rem; outline: none;
        }
        .modal-box input:focus { border-color: #00d4ff; box-shadow: 0 0 20px rgba(0, 212, 255, 0.1); }
        .modal-box .btn-submit-modal {
            width: 100%; padding: 14px; margin-top: 20px;
            background: linear-gradient(135deg, #00d4ff, #7c3aed);
            border: none; border-radius: 50px; color: #fff; font-weight: 600; font-size: 1rem;
            cursor: pointer; transition: all 0.3s ease;
        }
        .modal-box .btn-submit-modal:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(0, 212, 255, 0.4); }
        .modal-box .error-msg { color: #ef4444; text-align: center; margin-top: 12px; font-size: 0.9rem; }
        .modal-box .close-modal { text-align: right; margin-bottom: 10px; cursor: pointer; color: #8899bb; }
        .modal-box .close-modal:hover { color: #fff; }

        .stats-bar {
            display: flex; flex-wrap: wrap; gap: 16px 30px;
            background: rgba(255,255,255,0.03); border-radius: 16px;
            padding: 14px 22px; margin-bottom: 28px;
            border: 1px solid rgba(255,255,255,0.06); justify-content: center;
        }
        .stats-bar .stat-item { display: flex; align-items: center; gap: 8px; color: #8899bb; font-size: 0.95rem; }
        .stats-bar .stat-item i { color: #00d4ff; }
        .stats-bar .stat-item strong { color: #e0e8f0; font-weight: 600; }

        .form-group { display: flex; flex-direction: column; gap: 16px; margin-bottom: 28px; }
        .form-row { display: flex; flex-wrap: wrap; gap: 16px; }
        .form-row .field { flex: 1; min-width: 160px; }
        label { display: block; font-size: 0.85rem; margin-bottom: 5px; color: #8899bb; letter-spacing: 0.3px; }
        input, select, textarea {
            width: 100%; padding: 12px 16px; background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 14px;
            color: #e6f0ff; font-size: 1rem; transition: all 0.3s ease; outline: none; font-family: inherit;
        }
        input:focus, select:focus, textarea:focus { border-color: #00d4ff; box-shadow: 0 0 25px rgba(0, 212, 255, 0.1); background: rgba(255,255,255,0.06); }
        select option { background: #1a2340; color: #e6f0ff; }
        textarea { min-height: 80px; resize: vertical; }
        .btn-submit {
            padding: 14px 32px; background: linear-gradient(135deg, #00d4ff, #7c3aed);
            border: none; border-radius: 50px; color: #fff; font-weight: 600; font-size: 1rem;
            cursor: pointer; transition: all 0.3s ease; letter-spacing: 1px; align-self: flex-start;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.3);
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 0 45px rgba(0, 212, 255, 0.5); }
        .btn-submit:active { transform: scale(0.96); }

        .messages-section { margin-top: 18px; }
        .messages-section .section-title {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 18px; color: #8899bb; font-size: 1.1rem;
            border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 12px;
        }
        .messages-section .section-title span { background: rgba(0, 212, 255, 0.12); padding: 2px 14px; border-radius: 20px; font-size: 0.8rem; color: #00d4ff; }

        .message-card {
            background: rgba(255,255,255,0.02); border-left: 4px solid #00d4ff;
            padding: 16px 20px; margin-bottom: 14px; border-radius: 16px;
            transition: all 0.25s ease; box-shadow: 0 2px 12px rgba(0,0,0,0.25);
            position: relative; overflow: hidden;
        }
        .message-card:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); border-left-color: #a855f7; }
        .message-card.pinned { border-left-color: #fbbf24; background: rgba(251, 191, 36, 0.06); }
        .message-card .card-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 0.9rem; }
        .message-card .name-tag { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .message-card .name { color: #00d4ff; font-weight: 600; }
        .message-card .tag-badge { background: rgba(0, 212, 255, 0.15); padding: 0 12px; border-radius: 30px; font-size: 0.7rem; color: #90caf9; border: 1px solid rgba(0, 212, 255, 0.1); }
        .message-card .time { color: #5a6a82; font-size: 0.75rem; }
        .message-card .content { color: #d0d8e8; line-height: 1.6; word-break: break-word; margin-top: 4px; }
        .message-card .ip-hint { font-size: 0.6rem; color: #3a4a62; margin-top: 8px; text-align: right; opacity: 0.6; }
        .message-card .admin-actions { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .message-card .admin-actions button, .message-card .admin-actions a {
            background: rgba(255,255,255,0.05); border: none;
            padding: 4px 12px; border-radius: 20px; color: #a0b4d0; font-size: 0.75rem;
            cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block;
        }
        .message-card .admin-actions button:hover { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .message-card .admin-actions .pin-btn:hover { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }

        .empty-msg { text-align: center; padding: 40px 0; color: #4a5a72; font-style: italic; }

        /* 折叠样式 */
        .collapsible {
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .collapsible::after {
            content: '▼';
            font-size: 0.7rem;
            transition: transform 0.3s;
            color: #8899bb;
        }
        .collapsible.collapsed::after {
            transform: rotate(-90deg);
        }
        .collapse-content {
            overflow: hidden;
            transition: max-height 0.4s ease;
            max-height: 500px;
        }
        .collapse-content.collapsed {
            max-height: 0;
        }

        /* ===== 管理员日志样式 ===== */
        .journal-section {
            max-width: 880px;
            width: 100%;
            background: rgba(12, 18, 34, 0.88);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 40px 35px;
            box-shadow: 0 0 60px rgba(168, 85, 247, 0.12), inset 0 0 80px rgba(168, 85, 247, 0.02);
            border: 1px solid rgba(168, 85, 247, 0.15);
            position: relative;
            z-index: 2;
            margin-bottom: 30px;
        }
        .journal-section .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(168,85,247,0.12);
            padding-bottom: 15px;
        }
        .journal-section .section-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #a855f7, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        .journal-section .section-header .journal-count {
            background: rgba(168,85,247,0.15);
            padding: 2px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #a855f7;
        }
        .journal-entry {
            background: rgba(255,255,255,0.02);
            border-left: 4px solid #a855f7;
            padding: 16px 20px;
            margin-bottom: 14px;
            border-radius: 16px;
            transition: all 0.25s ease;
            box-shadow: 0 2px 12px rgba(0,0,0,0.25);
            position: relative;
        }
        .journal-entry:hover {
            background: rgba(255,255,255,0.04);
            transform: translateX(3px);
            border-left-color: #fbbf24;
        }
        .journal-entry .entry-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            cursor: pointer;
            user-select: none;
        }
        .journal-entry .entry-title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .journal-entry .entry-title {
            color: #c084fc;
            font-weight: 600;
            font-size: 1.05rem;
        }
        .journal-entry .entry-time {
            color: #5a6a82;
            font-size: 0.75rem;
            white-space: nowrap;
        }
        .journal-entry .entry-toggle {
            font-size: 0.7rem;
            color: #8899bb;
            transition: transform 0.3s;
            display: inline-block;
        }
        .journal-entry .entry-toggle.rotated {
            transform: rotate(-90deg);
        }
        .journal-entry .entry-body {
            overflow: hidden;
            transition: max-height 0.4s ease;
            max-height: 2000px;
        }
        .journal-entry .entry-body.collapsed {
            max-height: 0;
        }
        .journal-entry .entry-content {
            color: #d0d8e8;
            line-height: 1.7;
            word-break: break-word;
            margin-top: 10px;
        }
        .journal-entry .entry-image {
            margin-top: 14px;
            border-radius: 12px;
            max-width: 100%;
            max-height: 400px;
            border: 1px solid rgba(168,85,247,0.15);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .journal-entry .entry-image:hover {
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(168,85,247,0.25);
        }
        .journal-entry .admin-journal-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .journal-entry .admin-journal-actions button {
            background: rgba(255,255,255,0.05);
            border: none;
            padding: 4px 14px;
            border-radius: 20px;
            color: #a0b4d0;
            font-size: 0.75rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .journal-entry .admin-journal-actions button:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .journal-form {
            background: rgba(168,85,247,0.04);
            border: 1px solid rgba(168,85,247,0.1);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 28px;
        }
        .journal-form .form-title {
            color: #c084fc;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .journal-form input[type="text"],
        .journal-form textarea {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(168,85,247,0.12);
            border-radius: 12px;
            color: #e6f0ff;
            padding: 12px 16px;
            font-size: 0.95rem;
            outline: none;
            width: 100%;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        .journal-form input[type="text"]:focus,
        .journal-form textarea:focus {
            border-color: #a855f7;
            box-shadow: 0 0 20px rgba(168,85,247,0.12);
            background: rgba(255,255,255,0.06);
        }
        .journal-form textarea {
            min-height: 100px;
            resize: vertical;
            margin-top: 10px;
        }
        .journal-form .file-upload-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .journal-form input[type="file"] {
            color: #8899bb;
            font-size: 0.85rem;
        }
        .journal-form input[type="file"]::file-selector-button {
            background: rgba(168,85,247,0.15);
            border: 1px solid rgba(168,85,247,0.2);
            border-radius: 20px;
            color: #c084fc;
            padding: 6px 16px;
            cursor: pointer;
            margin-right: 10px;
            transition: 0.2s;
        }
        .journal-form input[type="file"]::file-selector-button:hover {
            background: rgba(168,85,247,0.25);
        }
        .journal-form .btn-journal-submit {
            padding: 12px 28px;
            background: linear-gradient(135deg, #a855f7, #fbbf24);
            border: none;
            border-radius: 50px;
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 1px;
            margin-top: 14px;
            box-shadow: 0 0 25px rgba(168,85,247,0.3);
        }
        .journal-form .btn-journal-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px rgba(168,85,247,0.5);
        }
        .journal-empty {
            text-align: center;
            padding: 30px 0;
            color: #4a5a72;
            font-style: italic;
        }

        /* ===== 左侧边栏 ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            background: rgba(12, 18, 34, 0.88);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-left: none;
            border-radius: 0 18px 18px 0;
            padding: 16px 10px 10px 10px;
            box-shadow: 0 0 30px rgba(0, 180, 255, 0.15);
            transition: transform 0.35s ease, padding 0.35s ease, border-radius 0.35s ease;
            width: 190px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .sidebar.collapsed {
            transform: translateX(-162px);
            padding: 12px 8px 12px 4px;
            border-radius: 0 14px 14px 0;
        }
        .sidebar-toggle {
            position: absolute;
            right: -28px;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 70px;
            background: rgba(12, 18, 34, 0.88);
            border: 1px solid rgba(0, 212, 255, 0.15);
            border-left: none;
            border-radius: 0 12px 12px 0;
            cursor: pointer;
            color: #00d4ff;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            writing-mode: vertical-rl;
            letter-spacing: 2px;
            user-select: none;
            z-index: 1001;
        }
        .sidebar-toggle:hover {
            background: rgba(0, 212, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 212, 255, 0.2);
        }
        .sidebar-title {
            color: #00d4ff;
            font-weight: 600;
            font-size: 0.85rem;
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            letter-spacing: 1px;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }
        .sidebar-menu .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #b0c4de;
            cursor: pointer;
            transition: all 0.25s ease;
            font-size: 0.85rem;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            border: 1px solid transparent;
            user-select: none;
        }
        .sidebar-menu .menu-item:hover {
            background: rgba(0, 212, 255, 0.08);
            color: #e6f0ff;
            border-color: rgba(0, 212, 255, 0.15);
        }
        .sidebar-menu .menu-item .menu-icon {
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        /* 呼吸灯开关 */
        .sidebar-menu .twinkle-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #b0c4de;
            cursor: pointer;
            transition: all 0.25s ease;
            font-size: 0.85rem;
            border: 1px solid transparent;
            user-select: none;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-menu .twinkle-toggle:hover {
            background: rgba(0, 212, 255, 0.08);
            border-color: rgba(0, 212, 255, 0.15);
        }
        .sidebar-menu .twinkle-toggle .twinkle-switch {
            position: relative;
            width: 36px;
            height: 20px;
            flex-shrink: 0;
            margin-left: auto;
        }
        .sidebar-menu .twinkle-toggle .twinkle-switch input {
            opacity: 0; width: 0; height: 0;
        }
        .sidebar-menu .twinkle-toggle .twinkle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #2a3a5a;
            border-radius: 20px;
            transition: 0.3s;
        }
        .sidebar-menu .twinkle-toggle .twinkle-slider::before {
            content: '';
            position: absolute;
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background: #8899bb;
            border-radius: 50%;
            transition: 0.3s;
        }
        .sidebar-menu .twinkle-toggle input:checked + .twinkle-slider {
            background: linear-gradient(135deg, #00d4ff, #7c3aed);
        }
        .sidebar-menu .twinkle-toggle input:checked + .twinkle-slider::before {
            transform: translateX(16px);
            background: #fff;
        }
        /* 发发牢骚 — 推到底部，加大间距，左侧对齐 */
        .sidebar-menu .menu-item.easter-egg-item {
            display: none;
            margin-top: auto;
            padding-top: 28px;
            border-top: 1px dashed rgba(251, 191, 36, 0.15);
            border-radius: 0 0 10px 10px;
            color: #fbbf24;
            animation: eggReveal 0.5s ease;
        }
        .sidebar-menu .menu-item.easter-egg-item.revealed {
            display: flex;
        }
        /* 彩蛋触发区域（底部小圆点） */
        .sidebar-easter-egg {
            position: relative;
            min-height: 20px;
            flex-shrink: 0;
        }
        .sidebar-easter-egg .egg-trigger {
            display: inline-block;
            width: 24px;
            height: 24px;
            cursor: pointer;
            opacity: 0.01;
            transition: opacity 0.3s;
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
        }
        .sidebar-easter-egg .egg-trigger:hover {
            opacity: 0.05;
        }
        @keyframes eggReveal {
            0% { opacity: 0; transform: scale(0.8); }
            50% { transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
        .sidebar-easter-egg .click-hint {
            font-size: 0.55rem;
            color: #2a3a5a;
            text-align: center;
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
        }

        /* 拖尾控制和小游戏容器 */
        .trail-controls, .game-container {
            position: fixed;
            z-index: 1000;
            background: rgba(12, 18, 34, 0.85);
            backdrop-filter: blur(8px);
            padding: 14px 18px;
            border-radius: 16px;
            border: 1px solid rgba(0, 180, 255, 0.2);
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
            transition: border-radius 0.3s ease, padding 0.3s ease, min-width 0.3s ease, width 0.3s ease;
            user-select: none;
        }
        .trail-controls {
            bottom: 20px;
            left: 220px;
            min-width: 160px;
        }
        .game-container {
            bottom: 20px;
            right: 20px;
            width: 200px;
        }
        /* 可拖拽面板的拖拽手柄 */
        .trail-controls .drag-handle, .game-container .drag-handle {
            cursor: grab;
        }
        .trail-controls .drag-handle:active, .game-container .drag-handle:active {
            cursor: grabbing;
        }
        /* 折叠后吸附到边缘的紧凑状态 */
        .trail-controls.snapped, .game-container.snapped {
            border-radius: 0 18px 18px 0;
            padding: 8px 10px;
            min-width: unset;
            width: auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
        }
        .trail-controls.snapped-left {
            left: 0;
            border-radius: 0 18px 18px 0;
        }
        .trail-controls.snapped-right {
            right: 0;
            border-radius: 18px 0 0 18px;
            left: auto;
        }
        .game-container.snapped-left {
            left: 0;
            right: auto;
            border-radius: 0 18px 18px 0;
        }
        .game-container.snapped-right {
            right: 0;
            left: auto;
            border-radius: 18px 0 0 18px;
        }
        .trail-controls.snapped .collapse-content,
        .game-container.snapped .collapse-content {
            display: none;
        }
        .trail-controls.snapped .collapsible::after,
        .game-container.snapped .collapsible::after {
            content: '▶';
            font-size: 0.65rem;
        }
        .trail-controls.snapped .collapsible,
        .game-container.snapped .collapsible {
            font-size: 0.7rem;
            gap: 2px;
            writing-mode: vertical-rl;
            letter-spacing: 2px;
        }
        .trail-controls label, .game-container label {
            color: #b0c4de;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            margin-top: 6px;
        }
        .trail-controls select {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #e6f0ff;
            padding: 4px 8px;
            outline: none;
        }
        .trail-controls select option { background: #1a2340; }
        .game-container #game-area {
            width: 100%; height: 100px; background: rgba(255,255,255,0.03);
            border-radius: 12px; margin: 8px 0; cursor: pointer; position: relative;
            border: 1px solid rgba(255,255,255,0.05); overflow: hidden;
        }
        .game-container #game-area .target {
            position: absolute; width: 30px; height: 30px; border-radius: 50%;
            background: radial-gradient(circle, #00d4ff, #7c3aed);
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.6);
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            transition: all 0.1s ease;
        }
        .game-container .game-btn {
            background: linear-gradient(135deg, #00d4ff, #7c3aed);
            border: none; border-radius: 20px; color: #fff;
            padding: 4px 16px; font-size: 0.8rem; cursor: pointer; margin-top: 4px;
        }
        .game-container .game-btn:hover { opacity: 0.8; }
        .game-container #game-score { color: #fbbf24; font-size: 1.4rem; font-weight: 600; }

        .footer-spacer {
            height: 100px;
            color: #3a4a62;
            text-align: center;
            padding: 20px;
            font-size: 0.8rem;
            width: 100%;
        }

        @media (max-width: 600px) {
            .container { padding: 20px 16px; }
            .header-left h1 { font-size: 1.8rem; }
            .header-right { margin-top: 10px; }
            .stats-bar { flex-direction: column; align-items: center; gap: 8px; }
            .btn-submit { width: 100%; justify-content: center; }
            .trail-controls { bottom: 80px; left: 170px; padding: 10px 12px; min-width: 120px; }
            .game-container { bottom: 80px; right: 10px; width: 150px; }
            .journal-section { padding: 20px 16px; }
            .journal-section .section-header h2 { font-size: 1.4rem; }
            .journal-form .file-upload-row { flex-direction: column; align-items: flex-start; }
            .journal-entry .entry-header { flex-direction: column; align-items: flex-start; gap: 4px; }
            .sidebar { width: 155px; }
            .sidebar.collapsed { transform: translateX(-130px); }
            .trail-controls.snapped, .game-container.snapped { padding: 6px 8px; }
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        ::-webkit-scrollbar-thumb { background: #2a3a5a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #00d4ff; }
    </style>
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <div class="header">
            <div class="header-left">
                <h1>🍹 可乐的留言板</h1>
                <div class="sub">✦ 技术 · 生活 · 自由发声 ✦</div>
            </div>
            <div class="header-right">
                <?php if ($logged_user): ?>
                    <span class="user-info"><i>👤</i> <?= htmlspecialchars($logged_user) ?></span>
                    <?php if ($is_admin): ?>
                        <span class="user-info" style="background:rgba(251,191,36,0.15); border-color:rgba(251,191,36,0.2); color:#fbbf24;"><i>⭐</i> 管理员</span>
                    <?php endif; ?>
                    <a href="?logout=1" class="btn-logout">退出</a>
                <?php else: ?>
                    <button class="btn-login" id="showLoginBtn">🔑 登录</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 统计 -->
        <div class="stats-bar">
            <div class="stat-item"><i>📬</i> 总留言 <strong><?= $stats['total'] ?></strong></div>
            <div class="stat-item"><i>📅</i> 今日新增 <strong><?= $stats['today'] ?></strong></div>
            <div class="stat-item"><i>🕒</i> 最后更新 <strong><?= !empty($messages) ? date('m-d H:i', strtotime($messages[0]['time'])) : '暂无' ?></strong></div>
            <div class="stat-item"><i>🌐</i> 访客IP <?= htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '未知') ?></div>
        </div>

        <!-- 表单 -->
        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="form-group">
                <div class="form-row">
                    <div class="field">
                        <label for="name">👤 你的名字（选填）</label>
                        <input type="text" id="name" name="name" value="<?= $logged_user ? htmlspecialchars($logged_user) : '' ?>" placeholder="留空则显示「匿名」" maxlength="20">
                    </div>
                    <div class="field">
                        <label for="tag">🏷️ 标签</label>
                        <select id="tag" name="tag">
                            <option value="💻 技术">💻 技术</option>
                            <option value="🎮 游戏">🎮 游戏</option>
                            <option value="📷 摄影">📷 摄影</option>
                            <option value="🚴 骑行">🚴 骑行</option>
                            <option value="💰 理财">💰 理财</option>
                            <option value="🤔 思考">🤔 思考</option>
                            <option value="🍹 日常" selected>🍹 日常</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="message">💬 留言内容</label>
                    <textarea id="message" name="message" placeholder="写点什么吧……（支持科技、游戏、摄影、骑行话题）" required></textarea>
                </div>
                <button type="submit" class="btn-submit">🚀 发射留言</button>
            </div>
        </form>

        <!-- 留言列表 -->
        <div class="messages-section">
            <div class="section-title"><span>📡 历史留言</span><span><?= count($messages) ?> 条</span></div>
            <?php if (empty($messages)): ?>
                <div class="empty-msg">🌌 还没有留言，来做第一个吧！</div>
            <?php else: ?>
                <?php foreach ($messages as $msg): 
                    $eggClass = '';
                    $content = $msg['message'];
                    if (stripos($content, '网安')!==false || stripos($content, '黑客')!==false || stripos($content, '漏洞')!==false) $eggClass = 'egg-tech';
                    elseif (stripos($content, 'MC')!==false || stripos($content, 'GTA')!==false || stripos($content, '游戏')!==false) $eggClass = 'egg-game';
                    elseif (stripos($content, '摄影')!==false || stripos($content, '相机')!==false) $eggClass = 'egg-photo';
                    $isPinned = isset($msg['is_pinned']) && $msg['is_pinned'];
                ?>
                    <div class="message-card <?= $isPinned ? 'pinned' : '' ?> <?= $eggClass ?>">
                        <div class="card-header">
                            <div class="name-tag">
                                <span class="name"><?= htmlspecialchars($msg['name']) ?></span>
                                <span class="tag-badge"><?= htmlspecialchars($msg['tag']) ?></span>
                                <?php if ($isPinned): ?><span style="font-size:0.7rem; background:rgba(251,191,36,0.2); padding:0 12px; border-radius:20px; color:#fbbf24;">📌 置顶</span><?php endif; ?>
                                <?php if ($eggClass !== ''): ?><span style="font-size:0.7rem; background:rgba(255,215,0,0.2); padding:0 10px; border-radius:20px; color:#fbbf24;">✨ 彩蛋</span><?php endif; ?>
                            </div>
                            <span class="time"><?= $msg['time'] ?></span>
                        </div>
                        <div class="content"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                        <div class="ip-hint">📍 <?= substr($msg['ip'], 0, 10) ?> ・ 来自银河系</div>
                        <?php if ($is_admin): ?>
                            <div class="admin-actions">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($msg['id']) ?>">
                                    <button type="submit" onclick="return confirm('确认删除此留言？')">🗑️ 删除</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_pin">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($msg['id']) ?>">
                                    <button type="submit" class="pin-btn"><?= $isPinned ? '📌 取消置顶' : '📌 置顶' ?></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 管理员删除全部 -->
        <?php if ($is_admin && !empty($messages)): ?>
            <div style="margin-top:20px; text-align:right;">
                <form method="post" onsubmit="return confirm('⚠️ 确定要删除所有留言吗？此操作不可恢复！');">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" style="background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.2); padding:8px 20px; border-radius:30px; cursor:pointer; font-size:0.85rem;">🗑️ 删除全部留言</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- 底部彩蛋 -->
        <div style="margin-top: 30px; text-align: center; font-size:0.8rem; color:#3a4a62; border-top:1px solid rgba(255,255,255,0.03); padding-top:16px;">
            <?php
            $quotes = [
                '🚴 “骑行的终点是风，也是自由。”',
                '📷 “按下快门，就是锁住时间。”',
                '💻 “0和1的宇宙里，漏洞是最好的老师。”',
                '🎮 “在MC里建红石，在GTA里飙车，在现实里写诗。”',
                '💰 “复利是第八大奇迹，而耐心是它的燃料。”',
                '🤔 “思考的深度，决定了你看到的风景。”'
            ];
            echo $quotes[array_rand($quotes)];
            ?>
        </div>
    </div>

    <!-- ===== 左侧边栏 ===== -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-toggle" id="sidebarToggle" title="收起/展开">◀ 菜单</div>
        <div class="sidebar-title">🍹 导航</div>
        <div class="sidebar-menu">
            <a class="menu-item" href="javascript:void(0)" onclick="alert('📋 个人信息面板——待后续完善')">
                <span class="menu-icon">📋</span> 个人信息
            </a>
            <a class="menu-item" href="javascript:void(0)" onclick="alert('👀 cola眼中的自己——待后续完善')">
                <span class="menu-icon">👀</span> cola眼中的自己
            </a>
            <label class="twinkle-toggle" id="twinkleToggleItem">
                <span class="menu-icon">✨</span> 呼吸灯
                <span class="twinkle-switch">
                    <input type="checkbox" id="twinkleCheckbox" checked>
                    <span class="twinkle-slider"></span>
                </span>
            </label>
            <a class="menu-item easter-egg-item" id="easterEggItem" href="javascript:void(0)" onclick="alert('💬 发发牢骚——待后续完善')">
                <span class="menu-icon">💬</span> 发发牢骚
            </a>
        </div>
        <div class="sidebar-easter-egg" id="easterEggZone">
            <span class="click-hint" id="clickHint">.</span>
            <span class="egg-trigger" id="eggTrigger" title=""></span>
        </div>
    </div>

    <!-- 登录模态框 -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal-box">
            <div class="close-modal" id="closeModalBtn">✕ 关闭</div>
            <h2>🔐 管理员登录</h2>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" placeholder="cola" required>
                <label for="password">密码</label>
                <input type="password" id="password" name="password" placeholder="••••••" required>
                <button type="submit" class="btn-submit-modal" name="login">登录</button>
                <?php if (isset($login_error)): ?>
                    <div class="error-msg"><?= $login_error ?></div>
                <?php endif; ?>
                <div style="text-align:center; margin-top:14px; font-size:0.85rem; color:#6a7a9e;">
                    还没有账号？<a href="login.php" style="color:#00d4ff; text-decoration:none;">立即注册 →</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== 拖尾控制（可折叠 + 可拖拽） ===== -->
    <div class="trail-controls" id="trailControls" data-draggable="true">
        <div class="collapsible drag-handle" id="trailToggleHead" onclick="toggleCollapse('trailContent')">
            <span style="color:#00d4ff; font-weight:600;">✨ 鼠标拖尾</span>
            <span style="color:#8899bb; font-size:0.7rem;">（拖拽/折叠）</span>
        </div>
        <div class="collapse-content" id="trailContent">
            <label><input type="checkbox" id="trailToggle" checked> 启用</label>
            <label>款式：
                <select id="trailStyle">
                    <option value="particle">粒子光点</option>
                    <option value="line">流光线条</option>
                    <option value="shape">几何星芒</option>
                </select>
            </label>
        </div>
    </div>

    <!-- ===== 小游戏（可折叠 + 可拖拽） ===== -->
    <div class="game-container" id="gameContainer" data-draggable="true">
        <div class="collapsible drag-handle" id="gameToggleHead" onclick="toggleCollapse('gameContent')">
            <span style="color:#00d4ff; font-weight:400;">🎯 小游戏</span>
            <span style="color:#8899bb; font-size:0.7rem;">（拖拽/折叠）</span>
        </div>
        <div class="collapse-content" id="gameContent">
            <div id="game-area">
                <div class="target" id="gameTarget"></div>
            </div>
            <div>
                <span style="color:#8899bb;">得分：</span><span id="game-score">0</span>
                <button class="game-btn" id="resetGameBtn">重来</button>
            </div>
        </div>
    </div>

    <!-- ===== 管理员日志 ===== -->
    <div class="journal-section" id="journal-section">
        <div class="section-header">
            <h2>📝 管理员日志</h2>
            <span class="journal-count"><?= count($journal) ?> 篇</span>
        </div>

        <?php if ($is_admin): ?>
        <!-- 新建日志表单 -->
        <div class="journal-form" id="journalForm">
            <div class="form-title">✍️ 撰写新日志</div>
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>#journal-section" enctype="multipart/form-data">
                <input type="hidden" name="journal_action" value="add">
                <input type="text" name="journal_title" placeholder="日志标题..." required maxlength="100">
                <textarea name="journal_content" placeholder="日志内容..." required></textarea>
                <div class="file-upload-row">
                    <label style="color:#8899bb; font-size:0.85rem;">🖼️ 上传图片（可选，最大10MB）：</label>
                    <input type="file" name="journal_image" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <button type="submit" class="btn-journal-submit">📝 发布日志</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- 日志列表 -->
        <?php if (empty($journal)): ?>
            <div class="journal-empty">📭 暂无日志，管理员快来写第一篇吧！</div>
        <?php else: ?>
            <?php foreach (array_reverse($journal) as $entry): ?>
                <div class="journal-entry">
                    <div class="entry-header" onclick="toggleJournalEntry('<?= htmlspecialchars($entry['id']) ?>')">
                        <div class="entry-title-row">
                            <span class="entry-toggle" id="toggle_<?= htmlspecialchars($entry['id']) ?>">▼</span>
                            <span class="entry-title"><?= htmlspecialchars($entry['title']) ?></span>
                        </div>
                        <span class="entry-time">🕒 <?= $entry['time'] ?></span>
                    </div>
                    <div class="entry-body" id="body_<?= htmlspecialchars($entry['id']) ?>">
                        <div class="entry-content"><?= nl2br(htmlspecialchars($entry['content'])) ?></div>
                        <?php if (!empty($entry['image'])): ?>
                            <a href="<?= htmlspecialchars($entry['image']) ?>" target="_blank">
                                <img class="entry-image" src="<?= htmlspecialchars($entry['image']) ?>" alt="日志图片" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <div class="admin-journal-actions">
                                <form method="post" style="display:inline;" onsubmit="return confirm('确认删除此日志？关联图片也将被删除。');">
                                    <input type="hidden" name="journal_action" value="delete">
                                    <input type="hidden" name="journal_id" value="<?= htmlspecialchars($entry['id']) ?>">
                                    <button type="submit">🗑️ 删除</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 底部占位，使页面可滚动 -->
    <div class="footer-spacer">
        <p>🍹 可乐的留言板 · 底部预留 · 继续向下滚动可以看到更多内容</p>
        <p style="font-size:0.7rem; color:#2a3a52;">💡 小提示：在左侧栏目最下方连续点击 10 次，或许会有惊喜哦～</p>
    </div>

    <!-- 拖尾canvas -->
    <canvas id="trailCanvas" style="position:fixed; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:9999;"></canvas>

    <script>
        // ========== 折叠功能 ==========
        function toggleCollapse(contentId) {
            const content = document.getElementById(contentId);
            const header = content.parentElement.querySelector('.collapsible');
            const panel = content.closest('.trail-controls') || content.closest('.game-container');
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                header.classList.remove('collapsed');
                // 展开时取消吸附
                if (panel) {
                    panel.classList.remove('snapped', 'snapped-left', 'snapped-right');
                    panel.style.left = panel.dataset.lastLeft || '';
                    panel.style.right = panel.dataset.lastRight || '';
                    panel.style.bottom = panel.dataset.lastBottom || '';
                    panel.style.top = '';
                }
            } else {
                content.classList.add('collapsed');
                header.classList.add('collapsed');
                // 折叠时吸附到最近的边缘
                if (panel && panel.dataset.draggable === 'true') {
                    snapPanel(panel);
                }
            }
            // 持久化面板状态
            if (panel) savePanelState(panel.id);
        }

        function snapPanel(panel) {
            const rect = panel.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const screenW = window.innerWidth;
            // 保存当前位置
            panel.dataset.lastLeft = panel.style.left || getComputedStyle(panel).left;
            panel.dataset.lastRight = panel.style.right || getComputedStyle(panel).right;
            panel.dataset.lastBottom = panel.style.bottom || getComputedStyle(panel).bottom;
            // 判断吸附左边还是右边
            if (centerX < screenW / 2) {
                panel.classList.add('snapped', 'snapped-left');
                panel.classList.remove('snapped-right');
                panel.style.left = '0';
                panel.style.right = 'auto';
            } else {
                panel.classList.add('snapped', 'snapped-right');
                panel.classList.remove('snapped-left');
                panel.style.right = '0';
                panel.style.left = 'auto';
            }
            // 使用之前保存的纵向位置，否则默认居中
            const savedTop = panel.dataset.snappedTop;
            if (savedTop && savedTop !== 'auto') {
                panel.style.top = savedTop;
            } else {
                panel.style.top = Math.max(0, (window.innerHeight - rect.height) / 2) + 'px';
            }
            panel.style.bottom = 'auto';
            panel.style.transform = 'none';
            // 持久化
            savePanelState(panel.id);
        }

        // ========== 侧边栏折叠/展开 ==========
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        let sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            sidebarToggle.innerHTML = '▶';
        }
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebarCollapsed = !sidebarCollapsed;
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                sidebarToggle.innerHTML = '▶';
            } else {
                sidebar.classList.remove('collapsed');
                sidebarToggle.innerHTML = '◀ 菜单';
            }
            localStorage.setItem('sidebar_collapsed', sidebarCollapsed.toString());
        });

        // ========== 呼吸灯开关 ==========
        const twinkleCheckbox = document.getElementById('twinkleCheckbox');
        // 从 localStorage 恢复状态
        if (localStorage.getItem('twinkle_enabled') === 'false') {
            twinkleCheckbox.checked = false;
            document.body.classList.add('no-twinkle');
        }
        twinkleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.remove('no-twinkle');
                localStorage.setItem('twinkle_enabled', 'true');
            } else {
                document.body.classList.add('no-twinkle');
                localStorage.setItem('twinkle_enabled', 'false');
            }
        });

        // ========== 侧边栏彩蛋：连续点击 10 次 ==========
        const eggTrigger = document.getElementById('eggTrigger');
        const easterEggItem = document.getElementById('easterEggItem');
        const clickHint = document.getElementById('clickHint');
        let eggClickCount = 0;
        let eggResetTimer = null;

        // 恢复彩蛋状态
        if (localStorage.getItem('easter_egg_revealed') === 'true') {
            easterEggItem.classList.add('revealed');
            clickHint.textContent = '🎉 彩蛋已解锁！';
        }

        eggTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            eggClickCount++;
            // 重置计时器（2秒内不点击则重置计数）
            clearTimeout(eggResetTimer);
            eggResetTimer = setTimeout(function() {
                eggClickCount = 0;
                clickHint.textContent = '.';
            }, 2000);

            if (eggClickCount >= 10) {
                easterEggItem.classList.add('revealed');
                clickHint.textContent = '🎉 彩蛋已解锁！';
                localStorage.setItem('easter_egg_revealed', 'true');
                eggClickCount = 0;
            } else if (eggClickCount >= 6) {
                clickHint.textContent = '快了... (' + eggClickCount + '/10)';
            } else if (eggClickCount >= 3) {
                clickHint.textContent = '继续...';
            }
        });

        // ========== 可拖拽面板 ==========
        function makeDraggable(panel) {
            const handle = panel.querySelector('.drag-handle');
            if (!handle) return;

            let isDragging = false;
            let startX, startY, startLeft, startTop;
            let wasSnapped = false;
            let snappedSide = ''; // 'left' or 'right'

            handle.addEventListener('mousedown', function(e) {
                wasSnapped = panel.classList.contains('snapped');
                if (wasSnapped) {
                    snappedSide = panel.classList.contains('snapped-left') ? 'left' : 'right';
                }
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                const rect = panel.getBoundingClientRect();
                startLeft = rect.left;
                startTop = rect.top;
                // 统一转为 top/left 定位
                panel.style.top = startTop + 'px';
                panel.style.left = startLeft + 'px';
                panel.style.bottom = 'auto';
                panel.style.right = 'auto';
                panel.style.transform = 'none';
                e.preventDefault();
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                let newLeft = startLeft + dx;
                let newTop = startTop + dy;

                if (wasSnapped) {
                    // 吸附态：锁定横向（保持在边缘），只允许纵向移动
                    if (snappedSide === 'left') {
                        newLeft = 0;
                    } else {
                        newLeft = window.innerWidth - panel.offsetWidth;
                    }
                    // 检查是否拖过了中线——切换侧边
                    const midX = startLeft + panel.offsetWidth / 2 + dx;
                    if (midX > window.innerWidth / 2 && snappedSide === 'left') {
                        snappedSide = 'right';
                        newLeft = window.innerWidth - panel.offsetWidth;
                    } else if (midX < window.innerWidth / 2 && snappedSide === 'right') {
                        snappedSide = 'left';
                        newLeft = 0;
                    }
                }

                // 限制在屏幕内
                const panelW = panel.offsetWidth;
                const panelH = panel.offsetHeight;
                newLeft = Math.max(0, Math.min(window.innerWidth - panelW, newLeft));
                newTop = Math.max(0, Math.min(window.innerHeight - panelH, newTop));

                panel.style.left = newLeft + 'px';
                panel.style.top = newTop + 'px';
            });

            document.addEventListener('mouseup', function() {
                if (!isDragging) return;
                isDragging = false;

                if (wasSnapped) {
                    // 保持在吸附状态，更新 snapped 类
                    panel.classList.add('snapped');
                    panel.classList.remove('snapped-left', 'snapped-right');
                    panel.classList.add(snappedSide === 'left' ? 'snapped-left' : 'snapped-right');
                    // 锁定到边缘
                    if (snappedSide === 'left') {
                        panel.style.left = '0';
                        panel.style.right = 'auto';
                    } else {
                        panel.style.right = '0';
                        panel.style.left = 'auto';
                    }
                    // 保存纵向位置
                    panel.dataset.snappedTop = panel.style.top;
                    panel.style.bottom = 'auto';
                } else {
                    // 检查是否折叠了——如果折叠了，自动吸附
                    const content = panel.querySelector('.collapse-content');
                    if (content && content.classList.contains('collapsed')) {
                        snapPanel(panel);
                    }
                    // 保存位置
                    panel.dataset.lastLeft = panel.style.left;
                    panel.dataset.lastRight = panel.style.right;
                    panel.dataset.lastBottom = 'auto';
                }
                // 持久化面板状态
                savePanelState(panel.id);
                wasSnapped = false;
            });
        }

        // ========== 状态持久化函数 ==========
        function savePanelState(panelId) {
            const panel = document.getElementById(panelId);
            if (!panel) return;
            const content = panel.querySelector('.collapse-content');
            const state = {
                collapsed: content ? content.classList.contains('collapsed') : false,
                snapped: panel.classList.contains('snapped'),
                snappedSide: panel.classList.contains('snapped-left') ? 'left' : 'right',
                top: panel.style.top || '',
                left: panel.style.left || '',
                right: panel.style.right || '',
                bottom: panel.style.bottom || '',
                snappedTop: panel.dataset.snappedTop || '',
                lastLeft: panel.dataset.lastLeft || '',
                lastRight: panel.dataset.lastRight || '',
                lastBottom: panel.dataset.lastBottom || ''
            };
            localStorage.setItem('panel_' + panelId, JSON.stringify(state));
        }

        function restorePanelState(panelId) {
            const panel = document.getElementById(panelId);
            if (!panel) return;
            const saved = localStorage.getItem('panel_' + panelId);
            if (!saved) return;
            try {
                const state = JSON.parse(saved);
                const content = panel.querySelector('.collapse-content');
                const header = panel.querySelector('.collapsible');

                // 恢复 data 属性
                if (state.lastLeft) panel.dataset.lastLeft = state.lastLeft;
                if (state.lastRight) panel.dataset.lastRight = state.lastRight;
                if (state.lastBottom) panel.dataset.lastBottom = state.lastBottom;
                if (state.snappedTop) panel.dataset.snappedTop = state.snappedTop;

                if (state.snapped) {
                    // 吸附状态
                    panel.classList.add('snapped', 'snapped-' + state.snappedSide);
                    if (state.snappedSide === 'left') {
                        panel.style.left = '0';
                        panel.style.right = 'auto';
                    } else {
                        panel.style.right = '0';
                        panel.style.left = 'auto';
                    }
                    panel.style.top = state.snappedTop || Math.max(0, (window.innerHeight - panel.offsetHeight) / 2) + 'px';
                    panel.style.bottom = 'auto';
                    panel.style.transform = 'none';
                }

                if (state.collapsed) {
                    if (content) content.classList.add('collapsed');
                    if (header) header.classList.add('collapsed');
                }

                // 若非吸附且非折叠，恢复自由位置
                if (!state.snapped && !state.collapsed) {
                    if (state.top && state.top !== 'auto') panel.style.top = state.top;
                    if (state.left && state.left !== 'auto') panel.style.left = state.left;
                    if (state.right && state.right !== 'auto') panel.style.right = state.right;
                    if (state.bottom && state.bottom !== 'auto') panel.style.bottom = state.bottom;
                }
            } catch(e) { /* ignore invalid stored data */ }
        }

        // 初始化拖拽
        document.querySelectorAll('[data-draggable="true"]').forEach(makeDraggable);

        // 恢复所有面板持久化状态
        restorePanelState('trailControls');
        restorePanelState('gameContainer');

        // ========== 日志折叠展开 ==========
        function toggleJournalEntry(entryId) {
            const body = document.getElementById('body_' + entryId);
            const toggle = document.getElementById('toggle_' + entryId);
            if (body.classList.contains('collapsed')) {
                body.classList.remove('collapsed');
                toggle.classList.remove('rotated');
            } else {
                body.classList.add('collapsed');
                toggle.classList.add('rotated');
            }
        }

        // ========== 登录弹窗 ==========
        document.getElementById('showLoginBtn')?.addEventListener('click', function() {
            document.getElementById('loginModal').classList.add('active');
        });
        document.getElementById('closeModalBtn')?.addEventListener('click', function() {
            document.getElementById('loginModal').classList.remove('active');
        });
        document.getElementById('loginModal')?.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
        <?php if (isset($login_error)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('loginModal').classList.add('active');
            });
        <?php endif; ?>

        // ========== 鼠标拖尾 ==========
        const canvas = document.getElementById('trailCanvas');
        const ctx = canvas.getContext('2d');
        let W, H;
        function resizeCanvas() {
            W = window.innerWidth; H = window.innerHeight;
            canvas.width = W; canvas.height = H;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        let trailEnabled = true, trailStyle = 'particle';
        let particles = [], trailPoints = [];
        let mouseX = W/2, mouseY = H/2;

        class Particle {
            constructor(x, y) {
                this.x = x; this.y = y;
                this.size = Math.random() * 6 + 2;
                this.life = 1.0;
                this.decay = 0.015 + Math.random() * 0.02;
                this.color = `hsl(${200 + Math.random() * 40}, 80%, 60%)`;
            }
            update() {
                this.x += (Math.random() - 0.5) * 1.2;
                this.y += (Math.random() - 0.5) * 1.2;
                this.life -= this.decay;
                return this.life > 0;
            }
            draw(ctx) {
                const alpha = this.life * 0.8;
                ctx.globalAlpha = alpha;
                ctx.fillStyle = this.color;
                ctx.shadowColor = 'rgba(0, 212, 255, 0.5)';
                ctx.shadowBlur = 15;
                if (trailStyle === 'shape') {
                    ctx.beginPath();
                    const s = this.size * 1.5;
                    for (let i = 0; i < 3; i++) {
                        const angle = (i / 3) * Math.PI * 2 - Math.PI / 2;
                        const px = this.x + Math.cos(angle) * s;
                        const py = this.y + Math.sin(angle) * s;
                        i === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
                    }
                    ctx.closePath();
                    ctx.fill();
                } else {
                    ctx.beginPath();
                    ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.shadowBlur = 0;
            }
        }

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX; mouseY = e.clientY;
            if (trailEnabled) {
                if (trailStyle === 'particle' || trailStyle === 'shape') {
                    for (let i = 0; i < 2; i++) {
                        particles.push(new Particle(mouseX + (Math.random()-0.5)*10, mouseY + (Math.random()-0.5)*10));
                    }
                } else if (trailStyle === 'line') {
                    trailPoints.push({x: mouseX, y: mouseY, life: 1.0});
                    if (trailPoints.length > 50) trailPoints.shift();
                }
            }
        });

        function animateTrail() {
            if (trailEnabled) {
                if (trailStyle === 'particle' || trailStyle === 'shape') {
                    particles = particles.filter(p => p.update());
                    if (particles.length > 200) particles.splice(0, particles.length - 200);
                } else if (trailStyle === 'line') {
                    trailPoints.forEach(p => p.life -= 0.03);
                    trailPoints = trailPoints.filter(p => p.life > 0);
                }
            }
            ctx.clearRect(0, 0, W, H);
            if (trailEnabled) {
                if (trailStyle === 'particle' || trailStyle === 'shape') {
                    particles.forEach(p => p.draw(ctx));
                } else if (trailStyle === 'line') {
                    if (trailPoints.length > 1) {
                        for (let i = 1; i < trailPoints.length; i++) {
                            const p1 = trailPoints[i-1], p2 = trailPoints[i];
                            const alpha = (p1.life + p2.life) / 2 * 0.7;
                            const width = p1.life * 4 + 1;
                            ctx.beginPath();
                            ctx.moveTo(p1.x, p1.y);
                            ctx.lineTo(p2.x, p2.y);
                            ctx.strokeStyle = `rgba(0, 212, 255, ${alpha})`;
                            ctx.shadowColor = 'rgba(0, 212, 255, 0.3)';
                            ctx.shadowBlur = 20;
                            ctx.lineWidth = width;
                            ctx.stroke();
                            ctx.shadowBlur = 0;
                        }
                    }
                }
            }
            requestAnimationFrame(animateTrail);
        }
        animateTrail();

        document.getElementById('trailToggle').addEventListener('change', function(e) {
            trailEnabled = e.target.checked;
            if (!trailEnabled) { particles = []; trailPoints = []; ctx.clearRect(0, 0, W, H); }
        });
        document.getElementById('trailStyle').addEventListener('change', function(e) {
            trailStyle = e.target.value;
            particles = []; trailPoints = []; ctx.clearRect(0, 0, W, H);
        });

        // ========== 小游戏 ==========
        let gameScore = 0;
        const gameArea = document.getElementById('game-area');
        const target = document.getElementById('gameTarget');
        const scoreDisplay = document.getElementById('game-score');

        function moveTarget() {
            const rect = gameArea.getBoundingClientRect();
            const areaW = rect.width, areaH = rect.height;
            const size = 30;
            target.style.left = (Math.random() * (areaW - size)) + 'px';
            target.style.top = (Math.random() * (areaH - size)) + 'px';
        }
        target.addEventListener('click', function(e) {
            e.stopPropagation();
            gameScore++;
            scoreDisplay.textContent = gameScore;
            moveTarget();
            this.style.transform = 'scale(0.8)';
            setTimeout(() => { this.style.transform = ''; }, 100);
        });
        gameArea.addEventListener('click', function() { moveTarget(); });
        document.getElementById('resetGameBtn').addEventListener('click', function() {
            gameScore = 0;
            scoreDisplay.textContent = '0';
            moveTarget();
        });
        moveTarget();
    </script>
</body>
</html>