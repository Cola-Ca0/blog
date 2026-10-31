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
        if (($post['collection'] ?? '') !== '') continue; // 合集文章不进首页标签云，与默认列表同口径
        foreach ($post['tags'] as $tag) {
            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
        }
    }
    arsort($tagCounts);
    echo json_encode(['tags' => $tagCounts], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- COLLECTIONS ---
/**
 * 合集卡：文章 frontmatter 里写 collection: <id> 即归入；
 * 展示用 metadata 在 collections.json（缺省则用 id 当标题）。
 */
function buildCollectionCards(string $postsDir): array {
    $meta = [];
    $metaFile = __DIR__ . '/collections.json';
    if (file_exists($metaFile)) {
        $m = json_decode(file_get_contents($metaFile), true);
        if (is_array($m)) foreach ($m as $c) if (!empty($c['id'])) $meta[$c['id']] = $c;
    }
    $groups = [];
    foreach (getPublishedPosts($postsDir) as $post) {
        $id = $post['collection'] ?? '';
        if ($id === '') continue;
        $groups[$id] ??= ['total' => 0, 'categories' => [], 'latest' => ''];
        $groups[$id]['total']++;
        $cat = $post['category'] !== '' ? $post['category'] : 'Other';
        $groups[$id]['categories'][$cat] = ($groups[$id]['categories'][$cat] ?? 0) + 1;
        if ($post['date'] > $groups[$id]['latest']) $groups[$id]['latest'] = $post['date'];
    }
    $out = [];
    foreach ($groups as $id => $g) {
        $m = $meta[$id] ?? [];
        arsort($g['categories']);
        $cats = [];
        foreach ($g['categories'] as $name => $n) $cats[] = ['name' => $name, 'count' => $n];
        $out[] = [
            'id'          => $id,
            'title'       => $m['title'] ?? $id,
            'description' => $m['description'] ?? '',
            'date'        => $m['date'] ?? $g['latest'],
            'tags'        => $m['tags'] ?? [],
            'total'       => $g['total'],
            'categories'  => $cats,
        ];
    }
    usort($out, fn($a, $b) => strcmp($b['date'], $a['date']));
    return $out;
}

if ($action === 'collections') {
    echo json_encode(['collections' => buildCollectionCards($postsDir)], JSON_UNESCAPED_UNICODE);
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
/* 2026-10-31 用户定：一页 10 条（原默认 6，且首页曾按侧栏高度自适应——两页条数不一致导致重叠） */
$perPage = isset($_GET['per']) ? min(50, max(4, intval($_GET['per']))) : 10;
$filterTag = $_GET['tag'] ?? null;
$filterCat = $_GET['category'] ?? null;
$hasCollectionParam = isset($_GET['collection']);
$filterCollection = $_GET['collection'] ?? '';

// Get published posts, apply optional filters
$allPosts = getPublishedPosts($postsDir);
// 首页默认只看散篇；合集文章统一走合集页（?collection=<id> 才取）
if ($hasCollectionParam) {
    $allPosts = array_filter($allPosts, fn($p) => ($p['collection'] ?? '') === $filterCollection);
} else {
    $allPosts = array_filter($allPosts, fn($p) => ($p['collection'] ?? '') === '');
}
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
    'posts'       => $result,
    'total'       => $total,
    'page'        => $page,
    'totalPages'  => $totalPages,
    // 首页默认视图（无筛选）时由前端渲染成合集入口卡
    'collections' => (!$hasCollectionParam && !$filterTag && !$filterCat && $page === 1)
        ? buildCollectionCards($postsDir) : [],
], JSON_UNESCAPED_UNICODE);
