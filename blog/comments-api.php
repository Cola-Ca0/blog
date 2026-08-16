<?php
/**
 * Comments API — list, create, delete
 */
require __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$commentsDir = __DIR__ . '/data/comments/';
if (!is_dir($commentsDir)) mkdir($commentsDir, 0755, true);

$action = $_GET['action'] ?? '';

// --- LIST ---
// 2026-08-16 审核: 公开列表只回已通过; 管理员回全部 (带 status 供审核 UI)
if ($action === 'list') {
    $slug = $_GET['slug'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slug']);
        exit;
    }

    $file = $commentsDir . $slug . '.json';
    $comments = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    if (!$isAdmin) {
        $comments = array_values(array_filter($comments, fn($c) => ($c['status'] ?? 'approved') === 'approved'));
    }

    echo json_encode($comments, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- CREATE (访客可留言, 2026-08-16) ---
if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // CSRF check
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    $slug = $input['slug'] ?? '';
    $content = trim($input['content'] ?? '');
    $replyTo = $input['reply_to'] ?? null;
    $guestName = trim($input['name'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slug']);
        exit;
    }
    if ($content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Content is required']);
        exit;
    }
    if (mb_strlen($content) > 2000) {
        http_response_code(400);
        echo json_encode(['error' => 'Content too long (max 2000 chars)']);
        exit;
    }
    // 2026-08-16: 非管理员提交冷却 60s (个人站防刷最小方案)
    if (!$isAdmin) {
        if (isset($_SESSION['last_comment_ts']) && time() - $_SESSION['last_comment_ts'] < 60) {
            http_response_code(429);
            echo json_encode(['error' => '评论太频繁, 请稍后再试']);
            exit;
        }
        $_SESSION['last_comment_ts'] = time();
    }

    // Validate reply_to points to an existing comment, if provided
    if ($replyTo !== null) {
        $file = $commentsDir . $slug . '.json';
        $existing = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
        $parentExists = false;
        foreach ($existing as $c) {
            if ($c['id'] === $replyTo) { $parentExists = true; break; }
        }
        if (!$parentExists) {
            http_response_code(400);
            echo json_encode(['error' => 'Parent comment not found']);
            exit;
        }
    }

    $comment = [
        'id'         => bin2hex(random_bytes(4)),
        'username'   => $isLoggedIn ? $username : (mb_substr($guestName, 0, 30) !== '' ? mb_substr($guestName, 0, 30) : '匿名访客'),
        'content'    => $content,
        'created_at' => date('Y-m-d H:i:s'),
        'reply_to'   => $replyTo,
        'is_admin'   => $isAdmin,
        'status'     => $isAdmin ? 'approved' : 'pending', // 2026-08-16: 访客/普通用户留言待管理员审核
    ];

    $file = $commentsDir . $slug . '.json';
    $comments = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    $comments[] = $comment;
    file_put_contents($file, json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);

    echo json_encode(['success' => true, 'comment' => $comment], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- DELETE (requires login, own comment or admin) ---
if ($action === 'delete') {
    if (!$isLoggedIn) {
        http_response_code(401);
        echo json_encode(['error' => 'Login required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // CSRF check
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    $commentId = $input['id'] ?? '';

    if ($commentId === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Comment ID required']);
        exit;
    }

    // Search all comment files for this ID
    $found = false;
    foreach (glob($commentsDir . '*.json') as $file) {
        $comments = json_decode(file_get_contents($file), true) ?: [];
        foreach ($comments as $i => $c) {
            if ($c['id'] === $commentId) {
                // Permission: own comment, or admin
                if ($c['username'] !== $username && !$isAdmin) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Permission denied']);
                    exit;
                }
                array_splice($comments, $i, 1);
                file_put_contents($file, json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
                $found = true;
                break 2;
            }
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Comment not found']);
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

// --- APPROVE (admin only, 2026-08-16 审核) ---
if ($action === 'approve') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin required']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    $commentId = $input['id'] ?? '';
    $slug = $input['slug'] ?? '';
    if ($commentId === '' || !preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid params']);
        exit;
    }
    $file = $commentsDir . $slug . '.json';
    $comments = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];
    foreach ($comments as $i => $c) {
        if ($c['id'] === $commentId) {
            $comments[$i]['status'] = 'approved';
            file_put_contents($file, json_encode($comments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode(['error' => 'Comment not found']);
    exit;
}

// --- Unknown action ---
http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
