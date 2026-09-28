<?php
/**
 * Seam: Markdown 渲染器 — XSS 防线回归测试
 *
 * 背景 (2026-09-28): renderMarkdown() 此前只有「行内元素」分支调用了 $esc()，
 * 段落(第10步)/无序列表(第9步)/表格单元格(第9b步)/有序列表(第9c步) 原样输出捕获文本。
 * 实测 renderMarkdown('<script>alert(1)</script>') 曾返回 <p><script>alert(1)</script></p>。
 * 修法 = 入口整体 htmlspecialchars 一次, 下游 $esc() 变恒等。
 *
 * 判据: 剥掉渲染器**自身允许产出**的标签后, 输出里不得再出现 '<'。
 * (用「不得出现 <img」这种朴素判据会误报 —— 渲染器自己会生成合法的 <img src="">。)
 */

require_once __DIR__ . '/../includes/markdown.php';

/** 剥掉白名单标签后, 还有没有裸 '<' */
function md_hasLiveTag(string $html): bool {
    $allowed =
        '<\/?(?:p|br|strong|em|del|pre|blockquote|hr|ul|ol|li|table|thead|tbody|tr|td|h[1-6])>'
      . '|<code(?: class="language-[^"]*")?>|<\/code>'
      . '|<a href="[^"]*">|<\/a>'
      . '|<img src="[^"]*" alt="[^"]*" loading="lazy">';
    $stripped = preg_replace('#(?:' . $allowed . ')#', '', $html);
    return strpos($stripped, '<') !== false;
}

echo "[Seam] Markdown XSS\n";

test('renderMarkdown 拒绝 <script>', function () {
    $out = renderMarkdown('<script>alert(document.domain)</script>');
    assertFalse(md_hasLiveTag($out), "产出活标签: $out");
});

test('renderMarkdown 拒绝 <img onerror>', function () {
    $out = renderMarkdown('<img src=x onerror=alert(1)>');
    assertFalse(md_hasLiveTag($out), "产出活标签: $out");
});

test('列表项里的 svg onload 被转义', function () {
    $out = renderMarkdown('- <svg onload=alert(1)>');
    assertFalse(md_hasLiveTag($out), "产出活标签: $out");
});

test('表格单元格里的 payload 被转义', function () {
    $out = renderMarkdown("| a | b |\n|---|---|\n| <img src=x onerror=alert(1)> | y |");
    assertFalse(md_hasLiveTag($out), "产出活标签: $out");
});

test('有序列表 / 引用 / 行内码里的 payload 被转义', function () {
    foreach (['1. <script>x</script>', '> <script>x</script>', '`<script>x</script>`'] as $p) {
        $out = renderMarkdown($p);
        assertFalse(md_hasLiveTag($out), "payload `$p` 产出活标签: $out");
    }
});

test('链接/图片的 javascript: 与 data: 协议被拦', function () {
    $a = renderMarkdown('[点](javascript:alert(1))');
    assertFalse(strpos($a, 'javascript:') !== false, "javascript: 未拦: $a");
    $b = renderMarkdown('[点](data:text/html,x)');
    assertFalse(strpos($b, 'data:') !== false, "data: 未拦: $b");
});

test('正文里的裸 < > 显示为文本而非标签', function () {
    $out = renderMarkdown('比较 a < b 且 c > d');
    assertTrue(strpos($out, 'a &lt; b') !== false, "应实体化, 得到: $out");
});

// ---- 回归: 正常 markdown 必须照常工作 (入口转义最容易误伤这些) ----

test('标题/粗体/斜体/删除线/行内码 仍正常', function () {
    assertTrue(strpos(renderMarkdown('# 你好'), '<h1>你好</h1>') !== false, '标题坏了');
    assertTrue(strpos(renderMarkdown('**粗**'), '<strong>粗</strong>') !== false, '粗体坏了');
    assertTrue(strpos(renderMarkdown('*斜*'), '<em>斜</em>') !== false, '斜体坏了');
    assertTrue(strpos(renderMarkdown('~~删~~'), '<del>删</del>') !== false, '删除线坏了');
    assertTrue(strpos(renderMarkdown('`x=1`'), '<code>x=1</code>') !== false, '行内码坏了');
});

test('代码块仍正常 (含语言标注)', function () {
    $out = renderMarkdown("```php\n\$a=1;\n```");
    assertTrue(strpos($out, '<pre><code class="language-php">') !== false, "代码块坏了: $out");
});

test('块引用仍正常 (入口转义把 > 变成 &gt;, 正则已同步)', function () {
    $out = renderMarkdown('> 引用一句');
    assertTrue(strpos($out, '<blockquote>引用一句</blockquote>') !== false, "块引用坏了: $out");
});

test('无序/有序列表仍正常', function () {
    $ul = renderMarkdown("- 甲\n- 乙");
    assertTrue(strpos($ul, '<ul>') !== false && substr_count($ul, '<li>') === 2, "无序列表坏了: $ul");
    $ol = renderMarkdown("1. 甲\n2. 乙");
    assertTrue(strpos($ol, '<ol>') !== false && substr_count($ol, '<li>') === 2, "有序列表坏了: $ol");
});

test('链接/图片/表格/水平线/段落 仍正常', function () {
    assertTrue(strpos(renderMarkdown('[站](https://a.com)'), '<a href="https://a.com">站</a>') !== false, '链接坏了');
    assertTrue(strpos(renderMarkdown('![猫](c.png)'), '<img src="c.png" alt="猫"') !== false, '图片坏了');
    $tb = renderMarkdown("| a | b |\n|---|---|\n| 1 | 2 |");
    assertTrue(strpos($tb, '<table>') !== false && substr_count($tb, '<td>') >= 3, "表格坏了: $tb");
    assertTrue(strpos(renderMarkdown("a\n\n---\n\nb"), '<hr>') !== false, '水平线坏了');
    assertTrue(strpos(renderMarkdown('普通一句话。'), '<p>普通一句话。</p>') !== false, '段落坏了');
});

test('URL 里的 & 正确实体化为 &amp;', function () {
    $out = renderMarkdown('[点](https://a.com/?x=1&y=2)');
    assertTrue(strpos($out, '&amp;y=2') !== false, "URL 实体化坏了: $out");
});
