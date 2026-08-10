<?php
/**
 * Posts API — list articles with pagination and filtering
 */
require __DIR__ . '/includes/markdown.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$postsDir = __DIR__ . '/posts/';
$commentsDir = __DIR__ . '/data/comments/';

// --- TAGS ---
if ($action === 'tags') {
    $tagCounts = [];
    foreach (getPublishedPosts($postsDir) as $post) {
        foreach ($post['tags'] as $tag) {
            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
        }
    }
    arsort($tagCounts);
    echo json_encode(['tags' => $tagCounts], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- SEARCH ---
if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (mb_strlen($q) < 1) {
        echo json_encode(['results' => []]);
        exit;
    }

    $results = [];
    foreach (getPublishedPosts($postsDir) as $post) {
        $haystack = $post['title'] . ' ' . $post['summary'] . ' ' . $post['body'];
        if (mb_stripos($haystack, $q) !== false) {
            $results[] = [
                'slug'     => $post['slug'],
                'title'    => $post['title'],
                'date'     => $post['date'],
                'category' => $post['category'],
                'summary'  => $post['summary'],
            ];
        }
    }
    // Already sorted by date desc from getPublishedPosts, limit to 8
    $results = array_slice($results, 0, 8);

    echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action !== 'list') {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 6;
$filterTag = $_GET['tag'] ?? null;
$filterCat = $_GET['category'] ?? null;

// Get published posts, apply optional filters
$allPosts = getPublishedPosts($postsDir);
if ($filterTag) {
    $allPosts = array_filter($allPosts, fn($p) => in_array($filterTag, $p['tags']));
}
if ($filterCat) {
    $allPosts = array_filter($allPosts, fn($p) => $p['category'] === $filterCat);
}
$allPosts = array_values($allPosts); // re-index after filter

$total = count($allPosts);
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);

$pagePosts = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

$result = array_map(function($p) use ($commentsDir) {
    $commentFile = $commentsDir . $p['slug'] . '.json';
    $commentCount = 0;
    if (file_exists($commentFile)) {
        $comms = json_decode(file_get_contents($commentFile), true);
        $commentCount = is_array($comms) ? count($comms) : 0;
    }
    return [
        'slug'          => $p['slug'],
        'title'         => $p['title'],
        'date'          => $p['date'],
        'category'      => $p['category'],
        'tags'          => $p['tags'],
        'summary'       => $p['summary'],
        'cover'         => $p['cover'],
        'comment_count' => $commentCount,
    ];
}, $pagePosts);

echo json_encode([
    'posts'      => $result,
    'total'      => $total,
    'page'       => $page,
    'totalPages' => $totalPages,
], JSON_UNESCAPED_UNICODE);
