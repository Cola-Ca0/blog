<?php
/**
 * Seam: JSON Store — 原子读改写
 *
 * 背景 (2026-09-28): 全站 JSON 写都是「读→改→写」+ file_put_contents(LOCK_EX),
 * 而 LOCK_EX 只锁「写」那一下。并发下后写的覆盖先写的 ——
 * 实测 20 进程并发追加: **旧写法只落盘 3 条(丢 17)**, 改成 jsonUpdate 后 20/20 全中。
 * (那次并发验证是临时脚本跑的, 需要 spawn 多进程; 这里放的是确定性的语义单测。)
 *
 * 另一个更隐蔽的破坏: file_put_contents 内部先截断再写, 没加锁的读会读到半个 JSON,
 * json_decode 返回 null → 调用方回退成空数组 → 写回去就把**整个文件清空**。
 */

echo "[Seam] JSON Store\n";

require_once __DIR__ . '/../includes/json-store.php';

$tmpDir = sys_get_temp_dir() . '/jsonstore_test_' . getmypid();
@mkdir($tmpDir, 0777, true);

test('jsonUpdate 在文件不存在时按默认值创建', function () use ($tmpDir) {
    $f = $tmpDir . '/new.json';
    @unlink($f);
    $r = jsonUpdate($f, function (array &$d) { $d[] = 'a'; });
    assertEquals(['a'], $r);
    assertEquals(['a'], json_decode(file_get_contents($f), true));
});

test('jsonUpdate 追加而不是覆盖', function () use ($tmpDir) {
    $f = $tmpDir . '/append.json';
    @unlink($f);
    jsonUpdate($f, function (array &$d) { $d[] = 1; });
    jsonUpdate($f, function (array &$d) { $d[] = 2; });
    jsonUpdate($f, function (array &$d) { $d[] = 3; });
    assertEquals([1, 2, 3], jsonRead($f));
});

test('mutator 返回 false 时跳过写盘（文件 mtime 不变）', function () use ($tmpDir) {
    $f = $tmpDir . '/skip.json';
    @unlink($f);
    jsonUpdate($f, function (array &$d) { $d[] = 'x'; });

    // 回拨 mtime 便于观察
    touch($f, time() - 100);
    $before = filemtime($f);

    jsonUpdate($f, function (array &$d) { return false; });   // 声明无需改动

    assertEquals($before, filemtime($f), '返回 false 不应写盘');
    assertEquals(['x'], jsonRead($f), '内容不应变化');
});

test('文件损坏时回退到默认值而不是崩溃', function () use ($tmpDir) {
    $f = $tmpDir . '/broken.json';
    file_put_contents($f, '{"half": ');          // 截断的 JSON
    assertEquals([], jsonRead($f), '损坏文件应回退默认值');
    jsonUpdate($f, function (array &$d) { $d[] = 'recovered'; });
    assertEquals(['recovered'], jsonRead($f), '应能从损坏状态恢复');
});

test('jsonRead 对不存在的文件返回默认值', function () use ($tmpDir) {
    assertEquals([], jsonRead($tmpDir . '/nope.json'));
    assertEquals(['d'], jsonRead($tmpDir . '/nope.json', ['d']));
});

test('中文与斜杠不被转义（与全站既有格式一致）', function () use ($tmpDir) {
    $f = $tmpDir . '/cn.json';
    @unlink($f);
    jsonUpdate($f, function (array &$d) { $d['t'] = '深海之下 / 测试'; });
    $raw = file_get_contents($f);
    assertTrue(strpos($raw, '深海之下') !== false, '中文不应被 \\u 转义');
    assertTrue(strpos($raw, '深海之下 / 测试') !== false, '斜杠不应被转义');
});

// 清理
foreach (glob($tmpDir . '/*.json') ?: [] as $f) @unlink($f);
@rmdir($tmpDir);

echo "\n";
