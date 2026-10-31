<?php
/**
 * Seam 6: 合集字段解析
 *
 * 文章 frontmatter 里的 `collection: <id>` 决定它是否进首页默认列表
 * （见 posts-api.php 的 list 分支）。解析器一旦漏掉这个键，合集文章会
 * 静默混回首页——所以单独守一条。
 */

echo "[Seam 6] Collection Frontmatter\n";

require_once __DIR__ . '/../includes/markdown.php';

$collTmp = sys_get_temp_dir() . '/mdz-coll-' . bin2hex(random_bytes(4));
@mkdir($collTmp, 0777, true);

function _mkCollPost(string $dir, string $name, string $extra): string {
    $p = $dir . '/' . $name;
    file_put_contents($p, "---\ntitle: T\ndate: 2026-10-28\ncategory: Misc\n{$extra}---\nbody\n");
    return $p;
}

test('collection 字段被解析出来', function () use ($collTmp) {
    $post = parsePostMeta(_mkCollPost($collTmp, 'a.md', "collection: QCTF2026\n"));
    assertEquals('QCTF2026', $post['collection']);
});

test('没有 collection 字段时默认空串', function () use ($collTmp) {
    $post = parsePostMeta(_mkCollPost($collTmp, 'b.md', "summary: x\n"));
    assertEquals('', $post['collection']);
});

test('collection 值两边的引号被剥掉', function () use ($collTmp) {
    $post = parsePostMeta(_mkCollPost($collTmp, 'c.md', "collection: \"SUSCTF2026\"\n"));
    assertEquals('SUSCTF2026', $post['collection']);
});

foreach (glob($collTmp . '/*.md') ?: [] as $f) @unlink($f);
@rmdir($collTmp);
