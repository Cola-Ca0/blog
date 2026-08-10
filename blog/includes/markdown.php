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
    foreach (explode("\n", $fmRaw) as $line) {
        $line = trim($line);
        if ($line === '') continue;

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
                } else {
                    $post['tags'] = array_map('trim', explode(',', $val));
                }
                break;
        }
    }
}

/**
 * Convert Markdown text to HTML.
 * Order matters: code blocks first (protect), then block elements, then inline.
 */
function renderMarkdown(string $text): string {
    $text = str_replace("\r\n", "\n", $text);
    $placeholders = [];

    // 1. Fenced code blocks (protect from later processing)
    $text = preg_replace_callback(
        '/```(\w*)\n(.*?)```/s',
        function ($m) use (&$placeholders) {
            $lang = $m[1] ? ' class="language-' . htmlspecialchars($m[1]) . '"' : '';
            $code = htmlspecialchars($m[2]);
            $key = '%%CODE' . count($placeholders) . '%%';
            $placeholders[$key] = "<pre><code{$lang}>{$code}</code></pre>";
            return $key;
        },
        $text
    );

    // 2. Headings (must run before horizontal rule ---)
    $text = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $text);
    $text = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $text);
    $text = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $text);
    $text = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $text);

    // 3. Horizontal rule (only standalone ---, not in front matter)
    $text = preg_replace('/^---$/m', '<hr>', $text);

    // 4. Blockquote
    $text = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $text);

    // 5. Images (before links — same bracket syntax)
    $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);

    // 6. Links
    $text = preg_replace('/\[([^\]]*)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);

    // 7. Bold + Italic + Strikethrough
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);

    // 8. Inline code (after bold/italic so ** inside code isn't affected)
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    // 9. Unordered lists — group consecutive <li> into <ul>
    $text = preg_replace('/^- (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', '<ul>$1</ul>', $text);

    // 10. Paragraphs — wrap remaining text blocks in <p>
    $blocks = explode("\n\n", $text);
    $blocks = array_map(function ($block) {
        $block = trim($block);
        if ($block === '') return '';
        // Skip if already wrapped in a block tag
        if (preg_match('/^<(h[1-6]|ul|ol|pre|blockquote|hr|li)/', $block)) return $block;
        return '<p>' . str_replace("\n", "<br>", $block) . '</p>';
    }, $blocks);
    $text = implode("\n", $blocks);

    // Restore code block placeholders
    foreach ($placeholders as $key => $html) {
        $text = str_replace($key, $html, $text);
    }

    return $text;
}
