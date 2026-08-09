# Research: Best Practices for a Vanilla PHP Blog (No Framework)

**Project**: Cola_CaO Blog  
**Environment**: PHP 8.x on Laragon (Windows)  
**Constraint**: No framework, no Composer -- raw PHP only  
**Date**: 2026-08-04  

Primary sources used: php.net manual, OWASP Cheat Sheet Series, MDN Web Docs, PHP-FIG standards.

---

## Table of Contents

1. [PHP File Organization (No Framework)](#1-php-file-organization-no-framework)
2. [Sharing CSS Tokens Across PHP Pages](#2-sharing-css-tokens-across-php-pages)
3. [Secure Session-Based Authentication](#3-secure-session-based-authentication)
4. [Avoiding Code Duplication in Vanilla PHP](#4-avoiding-code-duplication-in-vanilla-php)
5. [Path Traversal Prevention](#5-path-traversal-prevention)

---

## 1. PHP File Organization (No Framework)

### Primary Sources

- [php.net: include](https://www.php.net/manual/en/function.include.php) -- "When a file is included, the code it contains inherits the variable scope of the line on which the include occurs."
- [php.net: require_once](https://www.php.net/manual/en/function.require-once.php) -- "The _once suffix ensures the file is included only once, preventing function re-declaration errors."
- [php.net: Variable Scope](https://www.php.net/manual/en/language.variables.scope.php) -- "If the include occurs inside a function within the calling file, then all of the code contained in the called file will behave as though it had been defined inside that function."
- [PHP-FIG: PSR-4 Autoloader](https://www.php-fig.org/psr/psr-4/) -- Reference point for namespace-to-directory mapping (even without Composer).

### Key Findings

**Scope inheritance in includes**: When `include` is used at the top level of a script, the included file inherits the calling file's variable scope. When called inside a function, the included file's code executes in that function's local scope. Notably: "All functions and classes defined in the included file have the global scope" regardless of where the `include` occurs ([php.net: include](https://www.php.net/manual/en/function.include.php)).

**Path resolution pitfall**: Paths in `include` are resolved relative to the *original executing script* (the entry point), not the file doing the including. This is the most common source of broken includes. The fix from the manual: use `__DIR__` (PHP 5.3+) to build absolute paths ([php.net: include, user-contributed notes](https://www.php.net/manual/en/function.include.php)).

**`include` vs `require` vs `_once`**:
- `require` emits `E_ERROR` (fatal) on failure; `include` emits `E_WARNING` only.
- `require_once` / `include_once` prevent re-declaration fatal errors when the same file gets pulled in through multiple paths.
- Since PHP 5, including a file with function definitions twice causes a fatal error -- use `_once` variants for definition files.

**Keep included files outside the document root**: The manual advises placing included PHP files outside `DOCUMENT_ROOT` and adding that directory to `include_path`. This prevents direct web access to utility scripts ([php.net: include](https://www.php.net/manual/en/function.include.php)).

**PSR-4 reference (adaptation without Composer)**: PSR-4 maps a namespace prefix to a base directory. Sub-namespaces correspond to subdirectories, and the class name matches the file name (`ClassName.php`). Even without Composer, you can follow this convention and use `spl_autoload_register()` for your own class loading ([PHP-FIG: PSR-4](https://www.php-fig.org/psr/psr-4/)).

### Concrete Recommendation for Cola_CaO Blog

Use a flat-but-organized structure with a root constant for reliable path resolution:

```
blog/
  public/               <-- Document root (point Apache/Laragon here)
    index.php            <-- Front controller / entry point
    login.php
    logout.php
    admin/
      index.php
      edit.php
    projects/
      index.php
    assets/
      css/
        main.css
        variables.css
      js/
      images/
  src/                   <-- Outside document root
    config.php           <-- DB credentials, constants (returns array)
    auth.php             <-- Authentication logic (functions)
    db.php               <-- Database helpers (functions)
    session.php          <-- Session configuration & helpers
    templates/
      header.php         <-- <head>, nav, opening tags
      footer.php         <-- Closing tags, scripts
      background.php     <-- Shared background/animation layers
    helpers/
      sanitize.php       <-- Input sanitization functions
      render.php         <-- Template rendering (ob_start pattern)
  data/                  <-- Outside document root
    blog-posts/          <-- Flat-file or SQLite storage
  .htaccess              <-- URL rewriting, security headers
```

**Bootstrap pattern** (top of every entry-point file in `public/`):

```php
<?php
// Every public-facing page starts with this
define('APP_ROOT', dirname(__DIR__));  // Points to blog/

require APP_ROOT . '/src/config.php';
require APP_ROOT . '/src/session.php';
require APP_ROOT . '/src/auth.php';
require APP_ROOT . '/src/helpers/sanitize.php';
require APP_ROOT . '/src/helpers/render.php';

// Page-specific logic here...
require APP_ROOT . '/src/templates/header.php';
// ... page content ...
require APP_ROOT . '/src/templates/footer.php';
```

Why this works:
- `__DIR__` resolves relative to the file it appears in, not the entry point -- solving the nested-include path problem per php.net guidance.
- `APP_ROOT` is defined exactly once, then used everywhere for consistent absolute paths.
- All reusable logic lives outside `public/` so it cannot be accessed directly via the web.
- No autoloader needed for a small blog -- manual requires are manageable and explicit.

---

## 2. Sharing CSS Tokens Across PHP Pages

### Primary Sources

- [MDN: Using CSS custom properties](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties) -- "Custom properties defined on `:root` are inherited by all elements in the document."
- [MDN: CSS custom properties -- Cascading variables guide](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Cascading_variables) -- Variables follow the normal cascade; they can be reassigned in more specific selectors, media queries, or inline styles.
- Steve Souders (performance research): [don't use @import](https://www.stevesouders.com/blog/2009/04/09/dont-use-import/) -- "@import in a linked stylesheet forces sequential downloads across all browsers, adding a full roundtrip delay."

### Key Findings

**CSS custom properties are truly global across linked stylesheets**: It does not matter whether the custom properties are declared in an external `.css` file or within the same file -- they participate in the same cascade. Variables defined in `variables.css` are available in `main.css` as long as both are loaded on the page. Confirmed across Firefox, Safari, and Chrome.

**`<link>` vs `@import` -- critical performance difference**:
- Multiple `<link>` tags download **in parallel** across all browsers.
- `@import` inside a CSS file forces **sequential** downloads -- the browser must download and parse the parent file first before discovering the `@import` rule.
- `@import` can cause **Flash of Unstyled Content (FOUC)** because CSS loads after the page finishes downloading.
- `@import` can interfere with CDN delivery and caching.

**The PHP-include approach (embedding `<style>` blocks)**: You could use a PHP include to output a `<style>` block with `:root` variables directly into each page's `<head>`. This guarantees variables are available and eliminates an HTTP request, but:
- The browser cannot cache the CSS separately from the page HTML.
- The CSS is re-sent on every page load, increasing total bytes transferred.
- PHP must process the include on every request.

**The external-file approach (`<link rel="stylesheet">`)**: A separate `variables.css` file linked in each page's `<head>`:
- The browser caches the file after the first load -- subsequent pages served from cache.
- No PHP processing overhead for the CSS content.
- Parallel download with other `<link>` elements.
- **Downside**: Requires browsers to cache properly; cache-busting becomes necessary on updates.

### Concrete Recommendation for Cola_CaO Blog

**Use a separate `variables.css` file linked via `<link>` in the shared PHP header include.**

This is the best balance for a small blog:
- It leverages browser caching (the strongest advantage).
- It requires no PHP processing for CSS.
- It is maintainable: all design tokens in one file, easily editable.

File structure:

**`public/assets/css/variables.css`**:

```css
:root {
    /* Color palette */
    --color-bg:          #0a0a0f;
    --color-surface:     #12121a;
    --color-text:        #e0e0e0;
    --color-text-muted:  #888;
    --color-accent:      #6c5ce7;
    --color-accent-hover:#7d6ff0;
    --color-border:      #2a2a3a;

    /* Typography */
    --font-mono:         'JetBrains Mono', 'Fira Code', monospace;
    --font-body:         'Inter', system-ui, sans-serif;
    --font-size-sm:      0.875rem;
    --font-size-base:    1rem;
    --font-size-lg:      1.25rem;
    --font-size-xl:      2rem;

    /* Spacing */
    --space-xs:  0.25rem;
    --space-sm:  0.5rem;
    --space-md:  1rem;
    --space-lg:  2rem;
    --space-xl:  4rem;

    /* Borders & Shadows */
    --radius-sm: 4px;
    --radius-md: 8px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
}
```

**`src/templates/header.php`** (the shared PHP include):

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Cola_CaO Blog') ?></title>
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
```

Every PHP page (`public/index.php`, `public/login.php`, `public/projects/index.php`) calls `require APP_ROOT . '/src/templates/header.php';` and gets the same CSS tokens via the linked `variables.css`. This gives you:

- **One source of truth** for design tokens.
- **Browser caching** -- the CSS is downloaded once, then served from cache on all subsequent pages.
- **No PHP overhead** serving CSS content.
- **Maintainability** -- edit one file, changes apply everywhere.

**Cache busting for updates** (add to header.php when you release CSS changes):

```php
<link rel="stylesheet" href="/assets/css/variables.css?v=<?= filemtime(APP_ROOT . '/public/assets/css/variables.css') ?>">
```

This uses the file's modification timestamp as the query string, automatically invalidating the cache whenever the file changes.

---

## 3. Secure Session-Based Authentication

### Primary Sources

- [php.net: Session Security](https://www.php.net/manual/en/session.security.php) -- Comprehensive session security guidance.
- [php.net: Session Management Basics](https://www.php.net/manual/en/features.session.security.management.php) -- "session.use_strict_mode is mandatory for general session security. All sites are advised to keep it enabled."
- [php.net: session_regenerate_id](https://www.php.net/manual/en/function.session-regenerate-id.php) -- "This function does not handle unstable networks (mobile, WiFi) well and may cause lost sessions."
- [php.net: session_set_cookie_params](https://www.php.net/manual/en/function.session-set-cookie-params.php) -- PHP 7.3+ supports array-style parameters including `samesite`.
- [php.net: Session Configuration](https://www.php.net/manual/en/session.configuration.php) -- INI directives: `session.use_strict_mode`, `session.cookie_httponly`, `session.cookie_samesite`, `session.gc_maxlifetime`.
- [OWASP: Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html) -- "Session IDs must be regenerated upon authentication and privilege level changes."

### Key Findings

**Session fixation -- the core threat**: An attacker sets a known session ID on the victim's browser before login. Once the victim authenticates, the attacker uses that same ID to hijack the authenticated session. Defense: regenerate the session ID at every privilege level change (OWASP).

**`session.use_strict_mode = 1` is mandatory**: When enabled, PHP rejects uninitialized (externally supplied) session IDs and creates a new one. Without it, an attacker can force a victim to use a crafted session ID. The php.net manual states: "session.use_strict_mode is mandatory for general session security" ([php.net: session.security.ini](https://www.php.net/manual/en/session.security.php)). Note: PHP 8.6 will change the default from `0` to `1`.

**Session ID regeneration after login -- the correct pattern**: The php.net manual warns that `session_regenerate_id(true)` (immediately deleting old session) causes race conditions on unstable networks. The recommended approach uses timestamps:

From the [php.net manual's official example](https://www.php.net/manual/en/function.session-regenerate-id.php):

```php
<?php
session_start();

// Check destroyed timestamp
if (isset($_SESSION['destroyed']) && $_SESSION['destroyed'] < time() - 300) {
    // Old session detected -- possible attack or unstable network
    // Remove all auth flags and force re-login
    remove_all_authentication_flag_from_active_sessions($_SESSION['userid']);
    throw new DestroyedSessionAccessException;
}

$old_sessionid = session_id();
$_SESSION['destroyed'] = time(); // Mark for destruction
session_regenerate_id();

// New session does not need destroyed timestamp
unset($_SESSION['destroyed']);
```

**Cookie parameters -- the three critical flags (OWASP)**:

| Flag | Purpose | Recommendation |
|------|---------|---------------|
| `Secure` | Cookie only sent over HTTPS | Mandatory |
| `HttpOnly` | Blocks `document.cookie` JS access | Mandatory for session cookies |
| `SameSite=Lax` | Blocks cross-site POST cookie sending | Mitigates CSRF |

OWASP states: "Setting the `Secure` flag is mandatory even when the application only serves HTTPS traffic. Without it, an attacker can intercept traffic and inject HTTP references that trick the browser into disclosing the session ID over unencrypted connections."

**Session timeout -- PHP does not enforce it automatically**: The manual is explicit: "Do NOT rely on `session.gc_maxlifetime` for expiration. Attackers can ping the session to keep it alive." Developers must implement timestamp-based timeout checks themselves.

**`session.use_trans_sid` must be disabled**: When enabled, PHP appends `?PHPSESSID=...` to URLs if cookies are rejected. This leaks session IDs in referer headers, browser history, and shared links. Set to `Off`.

**Do not store sessions on a shared server's default `/tmp`**: On shared hosting, other users on the same machine can potentially read session files. Set a custom `session.save_path`.

### Concrete Recommendation for Cola_CaO Blog

Create a dedicated session configuration file at `src/session.php` that is required before any output in every entry-point file:

**`src/session.php`**:

```php
<?php
/**
 * Session configuration for Cola_CaO Blog
 * Must be required BEFORE any HTML output and BEFORE session_start().
 */

// --- 1. Set cookie parameters (MUST be before session_start()) ---
session_set_cookie_params([
    'lifetime' => 0,          // Session cookie (deleted when browser closes)
    'path'     => '/',        // Available across the entire site
    // 'domain'   => '',      // Leave empty -- restrict to current origin
    'secure'   => true,       // HTTPS only (set to false for localhost/Laragon)
    'httponly' => true,       // Block JavaScript access (XSS mitigation)
    'samesite' => 'Lax',      // Block cross-site POST (CSRF mitigation)
]);

// On Laragon (localhost without HTTPS), use this instead:
// session_set_cookie_params([
//     'lifetime' => 0,
//     'path'     => '/',
//     'secure'   => false,   // localhost has no HTTPS
//     'httponly' => true,
//     'samesite' => 'Lax',
// ]);

// --- 2. Set a custom session name (don't use default PHPSESSID) ---
session_name('COLA_SESSION');

// --- 3. Start the session ---
session_start();

// --- 4. Session timeout check (OWASP: 15-30 min idle, 4-8 hr absolute) ---
const SESSION_IDLE_TIMEOUT = 1800;    // 30 minutes
const SESSION_ABSOLUTE_TIMEOUT = 28800; // 8 hours

$now = time();

// Absolute timeout -- force re-login after 8 hours regardless of activity
if (isset($_SESSION['_created_at']) && ($now - $_SESSION['_created_at']) > SESSION_ABSOLUTE_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['_created_at'] = $now;
}

// Idle timeout -- force re-login after 30 minutes of inactivity
if (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity']) > SESSION_IDLE_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['_created_at'] = $now;
}

$_SESSION['_last_activity'] = $now;
if (!isset($_SESSION['_created_at'])) {
    $_SESSION['_created_at'] = $now;
}
```

**Login handling** (`src/auth.php` or inline at `public/login.php`):

```php
<?php
// After verifying credentials against stored password hash...

// Regenerate session ID to prevent fixation (OWASP + php.net)
$_SESSION['destroyed'] = time();
session_regenerate_id(true);  // true = delete old session file
unset($_SESSION['destroyed']);

// Set authentication flags in the NEW session
$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['logged_in']  = true;

// OWASP: Regenerate on privilege changes
// If elevating to admin during the same session:
//   session_regenerate_id(true);
//   $_SESSION['is_admin'] = true;
```

**Logout handling** (inline at `public/logout.php`, or as a helper function):

```php
<?php
// Full logout per OWASP recommendations:
$_SESSION = [];                          // Clear session array

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,                  // Expire in the past
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();                       // Destroy server-side data
header('Location: /');
exit;
```

**Access control helper** (used at the top of protected pages like `public/admin/edit.php`):

```php
<?php
function require_login(): void {
    if (empty($_SESSION['logged_in'])) {
        header('Location: /login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
```

**INI settings for Laragon `php.ini`** (session section):

```ini
; Security defaults (per php.net + OWASP)
session.use_strict_mode   = 1
session.cookie_httponly   = 1
session.cookie_samesite   = Lax
session.use_only_cookies  = 1
session.use_trans_sid     = 0

; Set a private save path (not shared /tmp)
session.save_path         = "Z:/laragon/laragon/www/blog/data/sessions"

; Reasonable GC lifetime
session.gc_maxlifetime    = 28800  ; 8 hours (matches absolute timeout)
```

These will become default values in PHP 8.6 -- setting them now future-proofs the project.

---

## 4. Avoiding Code Duplication in Vanilla PHP

### Primary Sources

- [php.net: include](https://www.php.net/manual/en/function.include.php) -- "When a file is included, parsing drops out of PHP mode and into HTML mode at the beginning of the target file, and resumes again at the end."
- [php.net: extract](https://www.php.net/manual/en/function.extract.php) -- "Import variables from an array into the current symbol table." Flags: `EXTR_SKIP` (safe, no overwrite), `EXTR_OVERWRITE` (default, dangerous).
- [php.net: ob_start](https://www.php.net/manual/en/function.ob-start.php) -- "This function will turn output buffering on. While output buffering is active no output is sent from the script; instead the output is stored in an internal buffer."

### Key Findings

**PHP include for shared HTML fragments**: When a PHP file is included, parsing drops into HTML mode -- any raw HTML in the included file is output directly. This is the simplest pattern for shared UI components. Example from php.net: "Any variables available at that line in the calling file will be available within the called file, from that point forward."

**The `extract()` + output buffering template pattern**: This combines three PHP features into a lightweight template engine:

1. `extract()` -- Converts an associative array into local variables so included templates can use `$title` instead of `$data['title']`.
2. `ob_start()` / `ob_get_clean()` -- Captures output from the included file into a string, rather than sending it to the browser immediately.
3. `include` -- Renders the template file.

This is necessary because `include` inside a function runs in that function's local scope -- external variables are not visible without `extract()` or `global`.

**Security warning on `extract()`**: The php.net manual warns that the default behavior (`EXTR_OVERWRITE`) silently overwrites existing variables. From the manual comments: "Do not use extract() on untrusted data, like `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`." Always use `EXTR_SKIP` or `EXTR_PREFIX_ALL` when the data source is not fully trusted.

**`include` vs `require` for templates**: Use `require` for templates -- if a template file is missing, the page cannot render correctly, so a fatal error is appropriate.

### Concrete Recommendation for Cola_CaO Blog

**Pattern 1: Simple shared PHP partials** (for header, footer, nav, background layers)

These work because `include` at the top level shares scope naturally. No `extract()` or `ob_start()` needed:

**`src/templates/header.php`**:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Cola_CaO') ?> | Cola_CaO Blog</title>
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
    <nav class="navbar">
        <a href="/" class="nav-brand">Cola_CaO</a>
        <div class="nav-links">
            <a href="/">Home</a>
            <a href="/projects/">Projects</a>
            <?php if (!empty($_SESSION['logged_in'])): ?>
                <a href="/admin/">Dashboard</a>
                <a href="/logout.php">Logout</a>
            <?php else: ?>
                <a href="/login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="content">
```

**`src/templates/footer.php`**:

```php
    </main><!-- /.content -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> Cola_CaO</p>
    </footer>
</body>
</html>
```

**`src/templates/background.php`** (shared decorative layers):

```php
<div class="bg-grid"></div>
<div class="bg-noise"></div>
```

Usage on every page (`public/index.php`, `public/projects/index.php`, etc.):

```php
<?php
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/src/config.php';
require APP_ROOT . '/src/session.php';

$pageTitle = 'Home';  // Set before header include

require APP_ROOT . '/src/templates/header.php';
require APP_ROOT . '/src/templates/background.php';
?>

<!-- Page-specific content here -->
<h1>Welcome to my blog</h1>
<?php foreach ($posts as $post): ?>
    <article>...</article>
<?php endforeach; ?>

<?php
require APP_ROOT . '/src/templates/footer.php';
```

**Pattern 2: Output buffering renderer** (for reusable content cards, list items, etc.)

When you need to capture rendered HTML into a string (e.g., to build a blog post card in a loop), use a render function in `src/helpers/render.php`:

```php
<?php
/**
 * Render a template partial and return the result as a string.
 *
 * Uses EXTR_SKIP to prevent template variables from overwriting
 * local variables in an unexpected way.
 *
 * @param string $templatePath Absolute path to the template file
 * @param array  $variables    Associative array of variables for the template
 * @return string Rendered HTML
 */
function render(string $templatePath, array $variables = []): string
{
    extract($variables, EXTR_SKIP);
    ob_start();
    require $templatePath;
    return ob_get_clean();
}
```

Call it from any page:

```php
<?php
$postCardHtml = render(APP_ROOT . '/src/templates/post-card.php', [
    'title'    => $post['title'],
    'excerpt'  => $post['excerpt'],
    'url'      => '/post.php?id=' . $post['id'],
    'date'     => $post['created_at'],
]);
echo $postCardHtml;
```

And `src/templates/post-card.php`:

```php
<article class="post-card">
    <h2><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($title) ?></a></h2>
    <time datetime="<?= htmlspecialchars($date) ?>"><?= htmlspecialchars($date) ?></time>
    <p><?= htmlspecialchars($excerpt) ?></p>
</article>
```

**Why `EXTR_SKIP` and not the default**: The php.net manual's default is `EXTR_OVERWRITE`, which silently overwrites existing local variables. For template rendering, `EXTR_SKIP` is safer -- if a template variable name happens to collide with a function-local variable, the existing variable is preserved rather than silently overwritten.

**Pattern 3: Configuration via returned arrays** (for `src/config.php`)

As documented on php.net's `include` page, included files can use `return`:

```php
<?php
// src/config.php
return [
    'db_path'  => APP_ROOT . '/data/blog.sqlite',
    'site_name' => 'Cola_CaO Blog',
    'posts_per_page' => 10,
    'dev_mode' => true,  // Disable secure cookies on localhost
];
```

Usage:

```php
$config = require APP_ROOT . '/src/config.php';
// $config is now the array from config.php
```

---

## 5. Path Traversal Prevention

### Primary Sources

- [php.net: basename](https://www.php.net/manual/en/function.basename.php) -- "Given a string containing the path to a file or directory, this function will return the trailing name component."
- [php.net: realpath](https://www.php.net/manual/en/function.realpath.php) -- "realpath() expands all symbolic links and resolves references to /./, /../ and extra / characters in the input path and returns the canonicalized absolute pathname."
- [php.net: open_basedir](https://www.php.net/manual/en/ini.core.php#ini.open-basedir) -- "Limit the files that can be accessed by PHP to the specified directory-tree."
- [OWASP: PHP Configuration Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html) -- "open_basedir restricts PHP file operations to specified directories, preventing LFI attacks from reaching sensitive files outside the application root."

### Key Findings

**`basename()` alone is not sufficient**: `basename()` strips directory components (`../../../etc/passwd` becomes `passwd`), but it does not protect against accessing files within the same directory. It should be used as part of a defense-in-depth strategy, not the only defense.

**`realpath()` resolves and validates**: `realpath()` canonicalizes paths by resolving `.`, `..`, and symlinks, returning the absolute path. It returns `false` if the file does not exist. Use this to validate that a resolved path stays within an allowed directory.

**The canonical defense pattern from OWASP**: Combine `basename()` and `realpath()`, then validate the result is within the allowed base path:

```php
$file = basename(realpath($_GET['file']));
include('/home/www/somesite/userpages/' . $file . '.php');
```

**Modern OWASP pattern -- prefix check**: A more robust approach validates the canonicalized path against a known base:

```php
$basePath = realpath('/uploads');
$fullPath = realpath('/uploads/' . $_GET['file']);

if ($fullPath === false || !str_starts_with($fullPath, $basePath . DIRECTORY_SEPARATOR)) {
    throw new SecurityException('Invalid path');
}
$content = file_get_contents($fullPath);
```

**`open_basedir` as defense-in-depth**: This PHP ini directive restricts all file operations (`include`, `fopen`, `file_get_contents`, etc.) to specified directory trees. It "cannot be overridden in .htaccess files" ([OWASP PHP Configuration Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)). Set it in `php.ini` or `httpd.conf` per virtual host.

**Additional hardening**:

| Directive | Value | Why |
|-----------|-------|-----|
| `allow_url_fopen` | `Off` | Prevents treating URLs as local files (blocks LFI to RFI escalation) |
| `allow_url_include` | `Off` | Prevents remote file inclusion entirely |

**OWASP on file uploads**: Never trust `$_FILES['file']['name']` directly. Always sanitize with `basename(realpath())` or generate your own filenames. Additionally, check every extension segment (not just the last one) -- a file named `shell.php.jpg` passes a naive check looking only at the final `.jpg`. Strip NULL bytes (`\0`, `%00`) aggressively, and trim whitespace around extensions (`photo. php` can bypass checks).

**OWASP recommends generated filenames**: For maximum security, generate your own safe filenames (e.g., using `hash('sha256', random_bytes(32))`) rather than preserving the user-provided filename. This eliminates all filename-based attack vectors.

### Concrete Recommendation for Cola_CaO Blog

**For reading blog posts from flat files** (if storing posts as `.md` or `.html` files):

```php
<?php
/**
 * Securely resolve a blog post file path.
 *
 * @param string $slug The user-supplied post slug (from URL)
 * @return string|null The validated absolute file path, or null if invalid
 */
function resolve_post_path(string $slug): ?string
{
    $postsDir = APP_ROOT . '/data/blog-posts/';
    $basePath = realpath($postsDir);

    if ($basePath === false) {
        return null; // Posts directory does not exist
    }

    // Strip any path components -- slug is a name, not a path
    $safeName = basename($slug);

    // Build and resolve the full path
    $candidate = realpath($basePath . DIRECTORY_SEPARATOR . $safeName . '.html');

    // Validate the resolved path is INSIDE the posts directory
    if ($candidate === false) {
        return null; // File does not exist
    }

    if (!str_starts_with($candidate, $basePath . DIRECTORY_SEPARATOR)) {
        return null; // Path traversal attempt -- resolved outside base
    }

    return $candidate;
}

// Usage in public/post.php:
$slug = $_GET['slug'] ?? '';
$path = resolve_post_path($slug);

if ($path === null) {
    http_response_code(404);
    echo 'Post not found';
    exit;
}

$content = file_get_contents($path);
```

**For including template files dynamically** (e.g., admin selects a layout):

```php
<?php
/**
 * Securely include a template by name. Only allows known templates.
 *
 * @param string $name Template name (no path separators allowed)
 * @param array  $vars Variables to pass to the template
 * @return string Rendered template content
 */
function render_template(string $name, array $vars = []): string
{
    // Whitelist: only known template names are allowed
    $allowed = ['post-card', 'hero', 'sidebar', 'comment', 'nav-item'];

    if (!in_array($name, $allowed, true)) {
        throw new \InvalidArgumentException("Unknown template: {$name}");
    }

    $templatePath = APP_ROOT . '/src/templates/' . $name . '.php';

    extract($vars, EXTR_SKIP);
    ob_start();
    require $templatePath;
    return ob_get_clean();
}
```

**For file uploads** (e.g., admin uploads images for blog posts):

```php
<?php
/**
 * Securely handle an uploaded image file.
 *
 * @param array $file Single element from $_FILES
 * @return string|false Generated safe filename, or false on failure
 */
function handle_image_upload(array $file): string|false
{
    // 1. Verify it's actually an uploaded file
    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    // 2. Validate MIME type (whitelist approach)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->file($file['tmp_name']);

    if (!in_array($detectedType, $allowedTypes, true)) {
        return false;
    }

    // 3. Generate a safe filename -- NEVER trust $_FILES['name']
    $extension = match ($detectedType) {
        'image/jpeg' => '.jpg',
        'image/png'  => '.png',
        'image/gif'  => '.gif',
        'image/webp' => '.webp',
    };
    $safeName = hash('sha256', random_bytes(32)) . $extension;

    // 4. Move to a directory OUTSIDE the document root if possible,
    //    or to a directory with PHP execution disabled
    $destPath = APP_ROOT . '/data/uploads/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return false;
    }

    return $safeName;
}
```

**`open_basedir` for Laragon `php.ini`**:

```ini
; Restrict PHP file access to the blog directory tree
open_basedir = "Z:/laragon/laragon/www/blog/;Z:/laragon/laragon/tmp/"

; Disable URL-based file operations
allow_url_fopen   = Off
allow_url_include = Off
```

**Defense-in-depth summary for file access in Cola_CaO Blog:**

| Layer | How | Purpose |
|-------|-----|---------|
| 1. Whitelist | Only allow known template/slug names | Prevents arbitrary file access |
| 2. `basename()` | Strip directory components from user input | Nullifies `../` traversal |
| 3. `realpath()` | Resolve to canonical absolute path | Validates the file exists and resolves symlinks |
| 4. Prefix check | `str_starts_with($resolved, $baseDir)` | Ensures path is inside allowed directory |
| 5. `open_basedir` | PHP ini directive | OS-level sandbox -- last line of defense |

---

## References

All primary sources consulted:

### PHP Manual
- [include](https://www.php.net/manual/en/function.include.php)
- [require_once](https://www.php.net/manual/en/function.require-once.php)
- [Variable Scope](https://www.php.net/manual/en/language.variables.scope.php)
- [extract](https://www.php.net/manual/en/function.extract.php)
- [ob_start](https://www.php.net/manual/en/function.ob-start.php)
- [ob_get_clean](https://www.php.net/manual/en/function.ob-get-clean.php)
- [Session Security](https://www.php.net/manual/en/session.security.php)
- [Session Management Basics](https://www.php.net/manual/en/features.session.security.management.php)
- [session_regenerate_id](https://www.php.net/manual/en/function.session-regenerate-id.php)
- [session_set_cookie_params](https://www.php.net/manual/en/function.session-set-cookie-params.php)
- [Session Configuration (INI)](https://www.php.net/manual/en/session.configuration.php)
- [basename](https://www.php.net/manual/en/function.basename.php)
- [realpath](https://www.php.net/manual/en/function.realpath.php)
- [open_basedir](https://www.php.net/manual/en/ini.core.php#ini.open-basedir)

### OWASP Cheat Sheet Series
- [Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [PHP Configuration Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)

### MDN Web Docs
- [Using CSS custom properties](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)
- [CSS custom properties -- Cascading variables](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Cascading_variables)

### PHP-FIG
- [PSR-4: Autoloader](https://www.php-fig.org/psr/psr-4/)
