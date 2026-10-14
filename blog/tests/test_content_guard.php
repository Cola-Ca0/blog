<?php
/**
 * Content Guard — 违法词过滤 + 用户黑名单 (2026-10-14)
 * 检查表 9.3.3 (过滤) / 9.2.2 c) (黑名单) 的回归门禁。
 * 集成用例复用 test_comments.php 的 commentsCall() (须排在其后 require)。
 */
require_once __DIR__ . '/../includes/content-guard.php';

echo "[Content Guard] 违法词过滤 + 黑名单\n";

const GUARD_SLUG = 'test-guard';

function guardCleanup(): void {
    @unlink(__DIR__ . '/../data/comments/' . GUARD_SLUG . '.json');
}

guardCleanup();

// ---------- 单元: 词表与命中 ----------
test('guard_hit: 普通文本通过', function () {
    assertEquals('', guard_hit('这是一条普通评论，聊的是 CTF 学习笔记。'));
});

test('guard_hit: 空文本通过', function () {
    assertEquals('', guard_hit(''));
});

test('guard_hit: 命中返回该词 (大小写不敏感路径)', function () {
    assertTrue(guard_hit('来澳门博彩网站玩') !== '', '赌博类词应命中');
    assertTrue(guard_hit('专业代开发票') !== '', '诈骗类词应命中');
    assertTrue(guard_hit('出售银行卡 量大从优') !== '', '非法交易词应命中');
});

test('guard_words: 基础词表非空且无空串', function () {
    $w = guard_words();
    assertTrue(count($w) >= 15, '词表应不少于 15 条');
    foreach ($w as $x) assertTrue(is_string($x) && $x !== '', '词表项须为非空字符串');
});

test('guard_user_banned: 未注册/匿名返回 false', function () {
    assertFalse(guard_user_banned('绝对不存在的用户xyz'));
    assertFalse(guard_user_banned(''));
});

// ---------- 集成: 评论 API 接线 ----------
test('create 正文含违禁词 → 400 拒收, 且不落盘', function () {
    $r = commentsCall(['action' => 'create'],
        ['slug' => GUARD_SLUG, 'content' => '出售银行卡 量大从优', 'name' => '广告哥', 'csrf_token' => COMMENTS_CSRF],
        ['csrf_token' => COMMENTS_CSRF]);
    assertEquals(400, $r['code'], '违禁词评论必须 400');
    assertFalse(file_exists(__DIR__ . '/../data/comments/' . GUARD_SLUG . '.json'), '被拒内容不得落盘');
});

test('create 昵称含违禁词 → 400 拒收', function () {
    $r = commentsCall(['action' => 'create'],
        ['slug' => GUARD_SLUG, 'content' => '你好', 'name' => '博彩推广', 'csrf_token' => COMMENTS_CSRF],
        ['csrf_token' => COMMENTS_CSRF]);
    assertEquals(400, $r['code'], '昵称含违禁词必须 400');
});

test('黑名单: 封禁后评论 403, 权限与解封闭环; users.json 用后还原', function () {
    $f = __DIR__ . '/../users.json';
    $bak = @file_get_contents($f);
    $had = ($bak !== false);
    try {
        file_put_contents($f, json_encode([['username' => 'guardtest', 'password' => 'x', 'role' => 'user']], JSON_UNESCAPED_UNICODE));

        // 未封禁 → 可评论
        $ok = commentsCall(['action' => 'create'],
            ['slug' => GUARD_SLUG, 'content' => '正常评论', 'csrf_token' => COMMENTS_CSRF],
            ['username' => 'guardtest', 'csrf_token' => COMMENTS_CSRF]);
        assertEquals(200, $ok['code'], '未封禁用户应可评论');

        // 非管理员调 ban → 403
        $noPerm = commentsCall(['action' => 'ban'],
            ['username' => 'guardtest', 'csrf_token' => COMMENTS_CSRF],
            ['csrf_token' => COMMENTS_CSRF]);
        assertEquals(403, $noPerm['code'], '非管理员 ban 应 403');

        // 管理员封禁 → 200
        $ban = commentsCall(['action' => 'ban'],
            ['username' => 'guardtest', 'csrf_token' => COMMENTS_CSRF],
            ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
        assertEquals(200, $ban['code'], 'ban 应 200');

        // 封禁后评论 → 403
        $deny = commentsCall(['action' => 'create'],
            ['slug' => GUARD_SLUG, 'content' => '再来一条', 'csrf_token' => COMMENTS_CSRF],
            ['username' => 'guardtest', 'csrf_token' => COMMENTS_CSRF]);
        assertEquals(403, $deny['code'], '封禁用户评论应 403');

        // 解封 → 恢复可评论
        $unban = commentsCall(['action' => 'unban'],
            ['username' => 'guardtest', 'csrf_token' => COMMENTS_CSRF],
            ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
        assertEquals(200, $unban['code'], 'unban 应 200');
        $again = commentsCall(['action' => 'create'],
            ['slug' => GUARD_SLUG, 'content' => '解封后的评论', 'csrf_token' => COMMENTS_CSRF],
            ['username' => 'guardtest', 'csrf_token' => COMMENTS_CSRF]);
        assertEquals(200, $again['code'], '解封后应恢复可评论');
    } finally {
        if ($had) file_put_contents($f, $bak); else @unlink($f);
    }
});

test('ban 匿名/非注册署名 → 404', function () {
    $r = commentsCall(['action' => 'ban'],
        ['username' => '某个匿名访客名', 'csrf_token' => COMMENTS_CSRF],
        ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
    assertEquals(404, $r['code'], '非注册用户不可封禁');
});

guardCleanup();
