<?php
/**
 * Lightweight Markdown + Front Matter Parser
 * Zero dependencies, pure PHP regex-based conversion.
 */

/**
 * Parse front matter + raw body only — NO Markdown rendering.
 * Use for list/search/tags where body_html is discarded.
 * Returns null if file missing or front matter invalid.
 */
function parsePostMeta(string $filePath): ?array {
    if (!file_exists($filePath)) return null;

    $content = file_get_contents($filePath);
    if ($content === false) return null;

    $post = [
        'title'    => '',
        'date'     => '',
        'category' => '',
        'tags'     => [],
        'summary'  => '',
        'draft'    => false,
        'updated'  => null,
        'cover'    => null,
        'slug'     => '',
        'body'     => '',
    ];

    // Parse front matter (shared logic — see _parseFrontMatter)
    if (str_starts_with(ltrim($content), '---')) {
        $content = ltrim($content);
        $content = substr($content, 3);
        $endPos = strpos($content, "\n---");
        if ($endPos === false) $endPos = strpos($content, "\r\n---");
        if ($endPos !== false) {
            $fmRaw = substr($content, 0, $endPos);
            $content = ltrim(substr($content, $endPos));
            if (str_starts_with($content, '---')) {
                $content = ltrim(substr($content, 3));
            }
            _parseFrontMatter($fmRaw, $post);
        }
    }

    $post['body'] = trim($content);
    $post['slug'] = pathinfo($filePath, PATHINFO_FILENAME);

    if (empty($post['title']) || empty($post['date'])) return null;

    return $post;
}

/**
 * Parse a .md post file into its metadata + body HTML.
 * Use for detail page, editor, RSS — anywhere body_html is needed.
 * Returns null if file missing or front matter invalid.
 */
function parsePost(string $filePath): ?array {
    $post = parsePostMeta($filePath);
    if ($post === null) return null;

    $post['body_html'] = renderMarkdown($post['body']);
    return $post;
}

/**
 * Internal: parse YAML-style front matter lines into $post array.
 */
function _parseFrontMatter(string $fmRaw, array &$post): void {
    $tagsListMode = false; // 2026-09-18: tags 空值进入 YAML 列表模式，兼容 Obsidian 手写的 "- item" 续行
    foreach (explode("\n", $fmRaw) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if ($tagsListMode && str_starts_with($line, '- ')) {
            $item = trim(trim(substr($line, 2)), '"\'');
            if ($item !== '') $post['tags'][] = $item;
            continue;
        }
        $tagsListMode = false;

        $colon = strpos($line, ':');
        if ($colon === false) continue;

        $key = trim(substr($line, 0, $colon));
        $val = trim(substr($line, $colon + 1));

        switch ($key) {
            case 'title':
                $post['title'] = trim($val, '"\'');
                break;
            case 'date':
                $post['date'] = trim($val, '"\'');
                break;
            case 'updated':
                $post['updated'] = trim($val, '"\'');
                break;
            case 'category':
                $post['category'] = trim($val, '"\'');
                break;
            case 'summary':
                $post['summary'] = trim($val, '"\'');
                break;
            case 'cover':
                $post['cover'] = trim($val, '"\'');
                if ($post['cover'] === '') $post['cover'] = null;
                break;
            case 'draft':
                $post['draft'] = ($val === 'true' || $val === '1');
                break;
            case 'tags':
                if (str_starts_with($val, '[') && str_ends_with($val, ']')) {
                    $decoded = json_decode($val, true);
                    $post['tags'] = is_array($decoded) ? $decoded : [];
                } elseif ($val !== '') {
                    $post['tags'] = array_map('trim', explode(',', $val));
                } else {
                    $post['tags'] = [];
                    $tagsListMode = true;
                }
                break;
        }
    }
}

/**
 * Convert Markdown text to HTML with proper escaping.
 * Order matters: code blocks first (protect), then block elements, then inline.
 */
function renderMarkdown(string $text): string {
    $text = str_replace("\r\n", "\n", $text);

    // ===== 2026-09-28 XSS 修复 (根因) =====
    // 此前只有第 1~8 步的「行内元素」调用了 $esc(); 段落(第10步)/无序列表(第9步)/
    // 表格单元格(第9b步)/有序列表(第9c步)把捕获到的文本原样输出 ——
    // 任何写进正文的 <script> / <img onerror> / <svg onload> 都会原样落到访客浏览器。
    // 判据: renderMarkdown('<script>alert(1)</script>') 曾返回 <p><script>alert(1)</script></p>。
    // 安全类文章粘 XSS payload 极易命中, 且 .htaccess 的 CSP 未锁 script-src, 无第二道拦截。
    //
    // 修法: 入口整体转义一次。下游所有 $esc() 随之变成恒等(避免二次转义),
    // 而段落/列表/表格分支无需改动即自动安全。
    // ⚠️ 副作用已处理: 转义会把块引用标记 '>' 变成 '&gt;', 第 4 步的正则已同步改为匹配 &gt;。
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $placeholders = [];

    // Helper: 恒等 —— 入口已整体转义。保留函数形态以最小化改动面(下游调用点无需逐个改)。
    $esc = fn(string $s): string => $s;

    // Helper: sanitize URL — block javascript: and data: schemes
    // (入口已转义, 这里只做协议白名单, 不再二次转义)
    $safeUrl = function(string $url): string {
        $url = trim($url);
        $lower = strtolower($url);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) return '';
        return $url;
    };

    // 1. Fenced code blocks (protect from later processing)
    $text = preg_replace_callback(
        '/```(\w*)\n(.*?)```/s',
        function ($m) use (&$placeholders, $esc) {
            $lang = $m[1] ? ' class="language-' . $esc($m[1]) . '"' : '';
            $code = $esc($m[2]);
            $key = '%%CODE' . count($placeholders) . '%%';
            $placeholders[$key] = "<pre><code{$lang}>{$code}</code></pre>";
            return $key;
        },
        $text
    );

    // 2. Headings — single pass
    $text = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', fn($m) => '<h' . strlen($m[1]) . '>' . $esc($m[2]) . '</h' . strlen($m[1]) . '>', $text);

    // 3. Horizontal rule (only standalone ---, not in front matter)
    $text = preg_replace('/^---$/m', '<hr>', $text);

    // 4. Blockquote — ⚠️ 入口已整体转义, '>' 已变成 '&gt;', 故匹配 &gt;
    $text = preg_replace_callback('/^&gt;\s+(.+)$/m', fn($m) => '<blockquote>' . $m[1] . '</blockquote>', $text);

    // 5. Images (before links — same bracket syntax)
    $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function($m) use ($esc, $safeUrl) {
        return '<img src="' . $safeUrl($m[2]) . '" alt="' . $esc($m[1]) . '" loading="lazy">';
    }, $text);

    // 6. Links
    $text = preg_replace_callback('/\[([^\]]*)\]\(([^)]+)\)/', function($m) use ($esc, $safeUrl) {
        return '<a href="' . $safeUrl($m[2]) . '">' . $esc($m[1]) . '</a>';
    }, $text);

    // 7. Bold + Italic + Strikethrough
    $text = preg_replace_callback('/\*\*(.+?)\*\*/', fn($m) => '<strong>' . $esc($m[1]) . '</strong>', $text);
    $text = preg_replace_callback('/\*(.+?)\*/',     fn($m) => '<em>' . $esc($m[1]) . '</em>', $text);
    $text = preg_replace_callback('/~~(.+?)~~/',      fn($m) => '<del>' . $esc($m[1]) . '</del>', $text);

    // 8. Inline code (after bold/italic so ** inside code isn't affected)
    $text = preg_replace_callback('/`([^`]+)`/', fn($m) => '<code>' . $esc($m[1]) . '</code>', $text);

    // 9. Unordered lists — group consecutive <li> into <ul>
    $text = preg_replace_callback('/^- (.+)$/m', fn($m) => '<li>' . $m[1] . '</li>', $text);
    $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', '<ul>$1</ul>', $text);
    // 9b. Tables — header + |---| separator + body rows
    // (cells raw: inline transforms already applied upstream, same as paragraphs)
    $text = preg_replace_callback(
        '/^\|(.+)\|\n\|[ :|-]+\|\n((?:\|.*\|\n?)+)/m',
        function ($m) {
            $cells = fn(string $row): string => implode('', array_map(
                fn($c) => '<td>' . trim($c) . '</td>',
                explode('|', trim($row, '|'))
            ));
            $rows = preg_split('/\r?\n/', rtrim($m[2]));
            $body = implode('', array_map(
                fn($r) => '<tr>' . $cells(trim($r, '|')) . '</tr>',
                array_filter($rows, fn($r) => trim($r) !== '')
            ));
            return '<table><thead><tr>' . $cells($m[1]) . '</tr></thead><tbody>' . $body . '</tbody></table>';
        },
        $text
    );

    // 9c. Ordered lists — group consecutive "N. " lines into <ol>
    $text = preg_replace_callback('/^\d+\. (.+)$/m', fn($m) => '<oli>' . $m[1] . '</oli>', $text);
    $text = preg_replace('/((?:<oli>.*<\/oli>\n?)+)/', "<ol>\n$1</ol>", $text);
    $text = str_replace(['<oli>', '</oli>'], ['<li>', '</li>'], $text);

    // 10. Paragraphs — wrap remaining text blocks in <p>
    $blocks = explode("\n\n", $text);
    $blocks = array_map(function ($block) use ($esc) {
        $block = trim($block);
        if ($block === '') return '';
        if (preg_match('/^<(h[1-6]|ul|ol|pre|blockquote|hr|li|table)/', $block)) return $block;
        return '<p>' . str_replace("\n", "<br>", $block) . '</p>';
    }, $blocks);
    $text = implode("\n", $blocks);

    // Restore code block placeholders
    foreach ($placeholders as $key => $html) {
        $text = str_replace($key, $html, $text);
    }

    return $text;
}

/**
 * Scan posts directory, return all published posts sorted by date desc.
 * Uses parsePostMeta — no wasted Markdown rendering.
 */
function getPublishedPosts(string $postsDir): array {
    $posts = [];
    if (is_dir($postsDir)) {
        foreach (glob($postsDir . '*.md') as $file) {
            $post = parsePostMeta($file);
            if ($post === null || $post['draft']) continue;
            $posts[] = $post;
        }
    }
    usort($posts, function ($a, $b) { return strcmp($b['date'], $a['date']); });
    return $posts;
}

/**
 * Validate slug/id format: only a-z, 0-9, hyphens.
 */
function isValidSlug(string $slug): bool {
    return (bool) preg_match('/^[a-zA-Z0-9\-]+$/', $slug);
}

/**
 * Read a JSON file safely, returning an empty array on failure.
 */
function jsonFileRead(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}
