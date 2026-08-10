<?php
/**
 * Admin: Save about-content.json
 * Only accessible by logged-in admin users
 */
require __DIR__ . '/../includes/auth.php';

if (!$isAdmin) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit; }

// CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403); echo json_encode(['error' => 'Invalid CSRF token']); exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if ($data === null) { http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); exit; }

$targetFile = __DIR__ . '/../about-content.json';
$written = file_put_contents($targetFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

if ($written === false) { http_response_code(500); echo json_encode(['error' => 'Write failed']); exit; }

echo json_encode(['ok' => true, 'bytes' => $written]);
