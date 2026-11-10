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
      . '|<img src="[^"]*" alt="[^"]*" loading="lazy">'
      . '|<span class="hl-(?:kw|str|num|fn|var|com)">|</span>';
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

// --- Obsidian wikilink 图片（2026-10-28）---

test('wikilink: 命中 assets 即渲染，路径走 $BASE', function () {
    $GLOBALS['BASE'] = '/blog';
    $name = array_key_first(assetBasenameIndex());
    assertTrue($name !== null, 'assets 索引为空，无法测试');
    $out = renderMarkdown('![[' . $name . ']]');
    assertTrue(strpos($out, '<img src="/blog/assets/') !== false, "应渲染成 assets 路径: $out");
    assertTrue(strpos($out, 'loading="lazy"') !== false, "应带懒加载: $out");
    unset($GLOBALS['BASE']);
});

test('wikilink: 宽度参数转成 width 属性', function () {
    $GLOBALS['BASE'] = '/blog';
    $name = array_key_first(assetBasenameIndex());
    $out = renderMarkdown('![[' . $name . '|320]]');
    assertTrue(strpos($out, 'width="320"') !== false, "应带宽度: $out");
    unset($GLOBALS['BASE']);
});

test('wikilink: 找不到的图保留原文，不静默吞掉', function () {
    $out = renderMarkdown('![[definitely-no-such-asset-xyz.png]]');
    assertTrue(strpos($out, '![[definitely-no-such-asset-xyz.png]]') !== false, "应保留原文: $out");
});

test('wikilink: 路径遍历被拒（只取 basename 查表）', function () {
    $out = renderMarkdown('![[../../users.json]]');
    assertFalse(strpos($out, '<img') !== false, "不得生成 img: $out");
});

// ---- 语法着色 (2026-10-31): highlightCode 逐段转义, 只产出白名单 span ----

test('语法着色: 关键字 / 字符串 / 注释各自成 span', function () {
    $out = renderMarkdown("```python\n# 注释\ndef f(x):\n    return \"a#b\"\n```");
    assertTrue(strpos($out, '<span class="hl-com"># 注释</span>') !== false, "注释没着色: $out");
    assertTrue(strpos($out, '<span class="hl-kw">def</span>') !== false, "关键字没着色: $out");
    assertTrue(strpos($out, '&quot;a#b&quot;') !== false, "字符串内容没保住: $out");
    assertFalse(strpos($out, '<span class="hl-com">#b') !== false, "字符串里的 # 被误判成注释: $out");
});

test('语法着色: 代码块里的 <script> 仍是实体', function () {
    $out = renderMarkdown("```html\n<script>alert(1)</script>\n```");
    assertFalse(strpos($out, '<script>') !== false, "代码块里出现活标签: $out");
    assertTrue(strpos($out, '&lt;script&gt;') !== false, "应保持实体: $out");
});

test('语法着色: 未标注语种不着色', function () {
    $out = renderMarkdown("```\nplain text\n```");
    assertFalse(strpos($out, '<span') !== false, "未标注语种不该产生 span: $out");
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

test('代码块仍正常 (含语言标注 + data-lang 角标)', function () {
    $out = renderMarkdown("```php\n\$a=1;\n```");
    assertTrue(strpos($out, '<pre data-lang="php"><code class="language-php">') !== false, "代码块坏了: $out");
    $out2 = renderMarkdown("```\nplain\n```");
    assertTrue(strpos($out2, '<pre data-lang="text">') !== false, "无标注代码块的回退坏了: $out2");
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

// ---- 白名单放行 (2026-10-02): 自己写的署名/换行要活, 其余 HTML 照旧死 ----

test('署名块 <blockquote style="text-align:right"> 原样渲染', function () {
    $sig = '<blockquote style="text-align:right">Cola_CaO<br>2026.09.22</blockquote>';
    $out = renderMarkdown($sig);
    assertTrue(strpos($out, $sig) !== false, "署名没渲染: $out");
    assertFalse(strpos($out, '&lt;blockquote') !== false, "署名被转义了: $out");
});

test('表格单元格里的 <br> 生效', function () {
    $out = renderMarkdown("| a | b |\n|---|---|\n| x<br>y | z |");
    assertTrue(strpos($out, '<td>x<br>y</td>') !== false, "单元格 br 没生效: $out");
});

test('代码块里的 <br> 保持字面量 (第 1 步占位优先)', function () {
    $out = renderMarkdown("```html\n<br>\n```");
    assertTrue(strpos($out, '&lt;br&gt;') !== false, "代码块里的 br 被放行了: $out");
    assertFalse(strpos($out, '<code class="language-html"><br>') !== false, "代码块里的 br 变活了: $out");
});

test('白名单是字面量匹配 —— 带属性的 br / blockquote 不放行', function () {
    $a = renderMarkdown('<br onload=alert(1)>');
    assertFalse(strpos($a, '<br onload') !== false, "带属性 br 被放行: $a");
    $b = renderMarkdown('<blockquote style="text-align:right" onmouseover=alert(1)>x</blockquote>');
    assertFalse(strpos($b, 'onmouseover=alert(1)>') !== false, "带事件属性的 blockquote 被放行: $b");
    assertFalse(md_hasLiveTag($b), "产出活标签: $b");
});

// ---- 站内图片路径规范化 (2026-10-16): 本地 $BASE='/blog' 与线上 $BASE='' 两端可写 ----

test('图片路径: /blog/assets/ 前缀随 $BASE 归一 (线上不 404)', function () {
    $GLOBALS['BASE'] = '';
    $out = renderMarkdown('![x](/blog/assets/images/posts/a.jpg)');
    assertTrue(strpos($out, 'src="/assets/images/posts/a.jpg"') !== false, "根部署应去掉 /blog 前缀: $out");

    $GLOBALS['BASE'] = '/blog';
    $out = renderMarkdown('![x](/blog/assets/images/posts/a.jpg)');
    assertTrue(strpos($out, 'src="/blog/assets/images/posts/a.jpg"') !== false, "子目录部署应保留: $out");
});

test('图片路径: 裸 assets/ 写法自动补 $BASE', function () {
    $GLOBALS['BASE'] = '';
    $out = renderMarkdown('![x](assets/images/posts/b.jpg)');
    assertTrue(strpos($out, 'src="/assets/images/posts/b.jpg"') !== false, "裸 assets 应补成 /assets: $out");

    $GLOBALS['BASE'] = '/blog';
    $out = renderMarkdown('![x](./assets/images/posts/c.jpg)');
    assertTrue(strpos($out, 'src="/blog/assets/images/posts/c.jpg"') !== false, "./assets 应补成 /blog/assets: $out");

    unset($GLOBALS['BASE']); // 还原, 不污染后续测试
});

test('图片路径: 外链与协议白名单行为不受影响', function () {
    $out = renderMarkdown('![x](https://example.com/a.png)');
    assertTrue(strpos($out, 'src="https://example.com/a.png"') !== false, "外链不应被动: $out");
    $bad = renderMarkdown('![x](javascript:alert(1))');
    assertFalse(strpos($bad, 'javascript:') !== false, "javascript: 仍须被拦截: $bad");
});

// ---- 站内文章链接归一 (2026-10-20): 本地 $BASE='/blog' 与线上 $BASE='' 两端可写 ----

test('文章链接: /blog/post/ 前缀随 $BASE 归一 (线上不 404)', function () {
    $GLOBALS['BASE'] = '';
    $out = renderMarkdown('[x](/blog/post/PRNG)');
    assertTrue(strpos($out, 'href="/post/PRNG"') !== false, "根部署应去掉 /blog 前缀: $out");

    $GLOBALS['BASE'] = '/blog';
    $out = renderMarkdown('[x](/blog/post/PRNG)');
    assertTrue(strpos($out, 'href="/blog/post/PRNG"') !== false, "子目录部署应保留: $out");
});

test('文章链接: /post/ 与裸 post/ 写法自动补 $BASE', function () {
    $GLOBALS['BASE'] = '';
    $out = renderMarkdown('[x](/post/PRNG)');
    assertTrue(strpos($out, 'href="/post/PRNG"') !== false, "根部署 /post 应保留: $out");
    $out = renderMarkdown('[x](post/PRNG)');
    assertTrue(strpos($out, 'href="/post/PRNG"') !== false, "裸 post 应补成 /post: $out");

    $GLOBALS['BASE'] = '/blog';
    $out = renderMarkdown('[x](./post/PRNG)');
    assertTrue(strpos($out, 'href="/blog/post/PRNG"') !== false, "./post 应补成 /blog/post: $out");

    unset($GLOBALS['BASE']); // 还原, 不污染后续测试
});

test('文章链接: 外链与锚点不受影响', function () {
    $GLOBALS['BASE'] = '/blog';
    $out = renderMarkdown('[x](https://example.com/a)');
    assertTrue(strpos($out, 'href="https://example.com/a"') !== false, "外链不应被动: $out");
    $out = renderMarkdown('[x](/blog/post/RSA#intro)');
    assertTrue(strpos($out, 'href="/blog/post/RSA#intro"') !== false, "锚点应保留: $out");
    $bad = renderMarkdown('[x](javascript:alert(1))');
    assertFalse(strpos($bad, 'javascript:') !== false, "javascript: 仍须被拦截: $bad");
    unset($GLOBALS['BASE']);
});
