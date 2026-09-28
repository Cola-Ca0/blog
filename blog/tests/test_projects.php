<?php
/**
 * Seam 5: Project File Path Safety
 *
 * ⚠️ 2026-09-28 重写。原版在文件内**重新实现了一份**同样的逻辑来测
 *    (原文注释: "Replicate the exact gating logic from projects/index.php"),
 *    于是测的是测试自己的副本, 不是真代码 —— 真实代码存在严重路径遍历
 *    (普通用户 ?project=..&download=users.json 可读走管理员哈希与私区文章),
 *    测试却全绿。**测试必须调用真代码, 否则比没有测试更危险。**
 *
 * 现在直接 require includes/path-safe.php 的 resolveProjectFile()。
 * 另补上原测试漏掉的致命反例: basename('..') === '..'。
 */

echo "[Seam 5] Project File Path Safety\n";

require_once __DIR__ . '/../includes/path-safe.php';

$testDir = realpath(__DIR__ . '/../projects/');
$demoDir = $testDir . '/demo-web-app';
if (!is_dir($demoDir)) mkdir($demoDir, 0777, true);

test('basename 不是防遍历手段（原测试漏掉的致命反例）', function () {
    // 这两个断言证明「用 basename 防穿越」这个假设是错的
    assertEquals('..', basename('..'), 'basename("..") 仍是 ".." —— 挡不住一级遍历');
    assertEquals('.', basename('.'), 'basename(".") 仍是 "."');
    // 多级确实会被剥掉, 所以只防一级的反例才是关键
    assertEquals('passwd', basename('../../../etc/passwd'));
});

test('正常文件访问放行', function () use ($testDir) {
    assertTrue(resolveProjectFile($testDir, 'demo-web-app', 'auth.php') !== null,
        '读取 demo-web-app/auth.php 应被允许');
});

test('★回归: project=.. 必须被拒（这是真实被打穿的那个 payload）', function () use ($testDir) {
    // 攻击实测: /projects/index.php?project=..&download=users.json → 读走管理员哈希
    assertFalse(resolveProjectFile($testDir, '..', 'users.json') !== null,
        'project=".." 必须被拒 —— 它会把锚点抬到 projects/ 的父目录');
    assertFalse(resolveProjectFile($testDir, '../', 'users.json') !== null, '../ 变体必须被拒');
    assertFalse(resolveProjectFile($testDir, '../posts-private', 'x.md') !== null, '一级跳目录必须被拒');
    assertFalse(resolveProjectFile($testDir, '../../../../hub', 'CLAUDE.md') !== null, '跨仓库必须被拒');
});

test('文件名不能含目录成分', function () use ($testDir) {
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', '../../../users.json') !== null, '../ 文件名必须被拒');
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', '../../../../etc/passwd') !== null, '绝对穿越必须被拒');
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', '..') !== null, 'file=".." 必须被拒');
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', '/etc/passwd') !== null, '绝对路径必须被拒');
});

test('反斜杠分隔符同样被剥（Windows 形态）', function () use ($testDir) {
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', '..\\..\\users.json') !== null,
        '反斜杠穿越必须被拒');
});

test('空值与不存在的一律被拒', function () use ($testDir) {
    assertFalse(resolveProjectFile($testDir, '', 'auth.php') !== null, '空 project 必须被拒');
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', '') !== null, '空 file 必须被拒');
    assertFalse(resolveProjectFile($testDir, 'nonexistent-project', 'f.php') !== null, '不存在的项目必须被拒');
    assertFalse(resolveProjectFile($testDir, 'demo-web-app', 'secret-keys.txt') !== null, '不存在的文件必须被拒');
});

test('白名单外字符的项目 id 一律拒（杜绝 . / \\ 等）', function () use ($testDir) {
    foreach (['a.b', 'a/b', 'a\\b', 'a b', 'a%2e', '.', '..'] as $bad) {
        assertFalse(resolveProjectFile($testDir, $bad, 'auth.php') !== null, "project='$bad' 必须被拒");
    }
});

test('兄弟目录逃逸被拒', function () use ($testDir) {
    $dirA = $testDir . '/dir-a';
    $dirB = $testDir . '/dir-b';
    @mkdir($dirA); @mkdir($dirB);
    file_put_contents($dirB . '/secret.txt', 'hidden');

    assertFalse(resolveProjectFile($testDir, 'dir-a', '../dir-b/secret.txt') !== null,
        '不得通过 dir-a 读到 dir-b 的文件');

    @unlink($dirB . '/secret.txt');
    @rmdir($dirB);
    @rmdir($dirA);
});

echo "\n";
