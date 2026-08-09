<?php
/**
 * RSS 2.0 Feed — last 10 published posts
 */
require __DIR__ . '/includes/markdown.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$siteUrl = 'http://localhost/blog';
$postsDir = __DIR__ . '/posts/';

// Collect published posts, sorted by date desc
$posts = [];
if (is_dir($postsDir)) {
    foreach (glob($postsDir . '*.md') as $file) {
        $post = parsePost($file);
        if ($post === null || $post['draft']) continue;
        $posts[] = $post;
    }
}
usort($posts, function ($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Limit to 10
$posts = array_slice($posts, 0, 10);

// Build RSS XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>Cola_CaO · Deep Sea Station</title>
  <link><?= htmlspecialchars($siteUrl) ?></link>
  <description>深海之下，别有洞天 — 可乐的水下研究站。CS / 网络安全 / CTF / SRC。</description>
  <language>zh-CN</language>
  <lastBuildDate><?= date('r') ?></lastBuildDate>
  <atom:link href="<?= $siteUrl ?>/feed.xml" rel="self" type="application/rss+xml"/>

<?php foreach ($posts as $post): ?>
  <item>
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link><?= htmlspecialchars($siteUrl . '/post/' . $post['slug']) ?></link>
    <guid isPermaLink="true"><?= htmlspecialchars($siteUrl . '/post/' . $post['slug']) ?></guid>
    <pubDate><?= date('r', strtotime($post['date'])) ?></pubDate>
    <category><?= htmlspecialchars($post['category']) ?></category>
    <description><![CDATA[<?= $post['body_html'] ?>]]></description>
  </item>
<?php endforeach; ?>

</channel>
</rss>
