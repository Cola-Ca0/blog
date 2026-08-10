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
 * Convert Markdown text to HTML with proper escaping.
 * Order matters: code blocks first (protect), then block elements, then inline.
 */
function renderMarkdown(string $text): string {
    $text = str_replace("\r\n", "\n", $text);
    $placeholders = [];

    // Helper: escape text content (not attributes)
    $esc = function(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };

    // Helper: sanitize URL — block javascript: and data: schemes
    $safeUrl = function(string $url): string {
        $url = trim($url);
        $lower = strtolower($url);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) return '';
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
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

    // 4. Blockquote
    $text = preg_replace_callback('/^>\s+(.+)$/m', fn($m) => '<blockquote>' . $esc($m[1]) . '</blockquote>', $text);

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
    $text = preg_replace_callback('/^- (.+)$/m', fn($m) => '<li>' . $esc($m[1]) . '</li>', $text);
    $text = preg_replace('/((?:<li>.*<\/li>\n?)+)/', '<ul>$1</ul>', $text);

    // 10. Paragraphs — wrap remaining text blocks in <p>
    $blocks = explode("\n\n", $text);
    $blocks = array_map(function ($block) use ($esc) {
        $block = trim($block);
        if ($block === '') return '';
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
