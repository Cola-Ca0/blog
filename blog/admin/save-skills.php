<?php
/**
 * Admin: Save skills to about-content.json
 */
require __DIR__ . '/../includes/auth.php';
if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit; }

// CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403); echo json_encode(['error' => 'Invalid CSRF token']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['skills'])) { http_response_code(400); echo json_encode(['error' => 'Invalid']); exit; }

$aboutFile = __DIR__ . '/../about-content.json';
$about = json_decode(file_get_contents($aboutFile), true) ?: [];
$about['skills'] = $input['skills'];

// 2026-11-04 备用技能 (PHOSPHOR 隐藏场景用): 可选字段, 只在明确传了数组时才覆盖
if (isset($input['standbySkills']) && is_array($input['standbySkills'])) {
    $standby = [];
    foreach ($input['standbySkills'] as $s) {
        if (!is_array($s) || !isset($s['name'])) continue;
        $standby[] = [
            'name'  => (string) $s['name'],
            'level' => max(0, min(100, (int) ($s['level'] ?? 0))),
            'icon'  => isset($s['icon']) ? (string) $s['icon'] : '◇',
        ];
    }
    $about['standbySkills'] = $standby;
}

$written = file_put_contents($aboutFile, json_encode($about, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
if ($written === false) { http_response_code(500); echo json_encode(['error' => 'Write failed']); exit; }

echo json_encode(['ok' => true, 'bytes' => $written]);
