<?php
/**
 * 石墨数据文件测试 (2026-11-09)
 *
 * honors.json / friends.json 是手工维护的数据源；结构错误不会报错，
 * 只会让整段区块或整个导航入口静默消失。这里把「消失」变成红灯。
 */

$dataRoot = dirname(__DIR__);

test('honors.json 结构与字段有效', function () use ($dataRoot) {
    $raw = @file_get_contents($dataRoot . '/honors.json');
    assertTrue($raw !== false, 'honors.json 不存在');
    $data = json_decode($raw, true);
    assertTrue(is_array($data) && array_is_list($data), 'honors.json 必须是 JSON 数组');
    foreach ($data as $i => $h) {
        assertTrue(is_array($h), "第 $i 条不是对象");
        assertTrue(isset($h['date']) && is_string($h['date'])
            && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $h['date']), "第 $i 条 date 必须是 YYYY-MM");
        assertTrue(isset($h['title']) && is_string($h['title']) && trim($h['title']) !== '',
            "第 $i 条 title 不能为空");
        assertTrue(isset($h['rank']) && is_string($h['rank']) && trim($h['rank']) !== '',
            "第 $i 条 rank 不能为空");
        assertTrue(!isset($h['note']) || is_string($h['note']), "第 $i 条 note 必须是字符串");
    }
});

test('friends.json 结构有效且 URL 只允许 http/https', function () use ($dataRoot) {
    $raw = @file_get_contents($dataRoot . '/friends.json');
    assertTrue($raw !== false, 'friends.json 不存在');
    $data = json_decode($raw, true);
    assertTrue(is_array($data) && array_is_list($data), 'friends.json 必须是 JSON 数组');
    $names = [];
    foreach ($data as $i => $f) {
        assertTrue(is_array($f), "第 $i 条不是对象");
        assertTrue(isset($f['name']) && is_string($f['name']) && trim($f['name']) !== '',
            "第 $i 条 name 不能为空");
        assertFalse(isset($names[$f['name']]), "友链名称重复：{$f['name']}");
        $names[$f['name']] = true;
        assertTrue(isset($f['url']) && is_string($f['url'])
            && filter_var($f['url'], FILTER_VALIDATE_URL) !== false, "第 $i 条 url 不是合法 URL");
        $scheme = strtolower((string) parse_url($f['url'], PHP_URL_SCHEME));
        assertTrue(in_array($scheme, ['http', 'https'], true), "第 $i 条 url 协议必须是 http/https");
    }
});
