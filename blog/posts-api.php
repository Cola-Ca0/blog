<?php
/**
 * Posts API — list articles with pagination and filtering
 */
require __DIR__ . '/includes/markdown.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$postsDir = __DIR__ . '/posts/';

// --- TAGS ---
if ($action === 'tags') {
    $tagCounts = [];
    if (is_dir($postsDir)) {
        foreach (glob($postsDir . '*.md') as $file) {
            $post = parsePostMeta($file);
            if ($post === null || $post['draft']) continue;
            foreach ($post['tags'] as $tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
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
    if (is_dir($postsDir)) {
        foreach (glob($postsDir . '*.md') as $file) {
            $post = parsePostMeta($file);
            if ($post === null || $post['draft']) continue;

            // Search in title, summary, and body
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
    }

    // Sort by date desc, limit to 8
    usort($results, function ($a, $b) { return strcmp($b['date'], $a['date']); });
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

// Scan all .md files
$allPosts = [];
if (is_dir($postsDir)) {
    foreach (glob($postsDir . '*.md') as $file) {
        $post = parsePostMeta($file);
        if ($post === null) continue;
        if ($post['draft']) continue;

        // Apply tag filter
        if ($filterTag && !in_array($filterTag, $post['tags'])) continue;
        // Apply category filter
        if ($filterCat && $post['category'] !== $filterCat) continue;

        $allPosts[] = $post;
    }
}

// Sort by date descending
usort($allPosts, function ($a, $b) {
    return strcmp($b['date'], $a['date']);
});

$total = count($allPosts);
$totalPages = max(1, ceil($total / $perPage));
$page = min($page, $totalPages);

// Slice for current page
$pagePosts = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

// Strip body fields from list response (only needed on detail page)
$result = [];
foreach ($pagePosts as $p) {
    $result[] = [
        'slug'     => $p['slug'],
        'title'    => $p['title'],
        'date'     => $p['date'],
        'category' => $p['category'],
        'tags'     => $p['tags'],
        'summary'  => $p['summary'],
        'cover'    => $p['cover'],
    ];
}

echo json_encode([
    'posts'      => $result,
    'total'      => $total,
    'page'       => $page,
    'totalPages' => $totalPages,
], JSON_UNESCAPED_UNICODE);
