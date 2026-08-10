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
if ($action === 'list') {
    $slug = $_GET['slug'] ?? '';
    if (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid slug']);
        exit;
    }

    $file = $commentsDir . $slug . '.json';
    if (file_exists($file)) {
        $comments = json_decode(file_get_contents($file), true) ?: [];
    } else {
        $comments = [];
    }

    echo json_encode($comments, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- CREATE (requires login) ---
if ($action === 'create') {
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
    $slug = $input['slug'] ?? '';
    $content = trim($input['content'] ?? '');
    $replyTo = $input['reply_to'] ?? null;

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
        'username'   => $username,
        'content'    => $content,
        'created_at' => date('Y-m-d H:i:s'),
        'reply_to'   => $replyTo,
        'is_admin'   => $isAdmin,
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

// --- Unknown action ---
http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
