<?php
/**
 * 评论系统 (2026-08-16): 访客留言 + 管理员审核
 */

echo "[Seam 3] Comments: guest + moderation\n";

const COMMENTS_SLUG = 'test-comments';
const COMMENTS_CSRF = 'testtoken123456';

function commentsCall(array $get, array $post, array $sess = [], string $sid = ''): array {
    $env = ['get' => $get, 'post' => $post, 'sess' => $sess, 'sid' => $sid];
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/comments_harness.php')
        . ' ' . escapeshellarg(base64_encode(json_encode($env, JSON_UNESCAPED_UNICODE)));
    $out = shell_exec($cmd);
    $j = json_decode((string)$out, true);
    return $j ?: ['code' => -1, 'body' => $out];
}

function commentsCleanup(): void {
    @unlink(__DIR__ . '/../data/comments/' . COMMENTS_SLUG . '.json');
}

commentsCleanup();

test('访客留言: 200 + status=pending + 名字生效', function () {
    $r = commentsCall(['action' => 'create'], ['slug' => COMMENTS_SLUG, 'content' => '访客测试留言', 'name' => '路人甲', 'csrf_token' => COMMENTS_CSRF], ['csrf_token' => COMMENTS_CSRF]);
    assertEquals(200, $r['code'], 'create 应 200');
    assertTrue(($r['body']['success'] ?? false), 'success=true');
    assertEquals('pending', $r['body']['comment']['status'] ?? '', '访客评论进入待审核');
    assertEquals('路人甲', $r['body']['comment']['username'] ?? '', '访客名字生效');
});

test('无 CSRF → 403', function () {
    $r = commentsCall(['action' => 'create'], ['slug' => COMMENTS_SLUG, 'content' => 'x', 'name' => 'x'], ['csrf_token' => COMMENTS_CSRF]);
    assertEquals(403, $r['code'], '缺 CSRF token 必须 403');
});

test('非管理员 60s 冷却 → 429 (同会话)', function () {
    $sid = 'cool-' . bin2hex(random_bytes(4));
    $p = ['slug' => COMMENTS_SLUG, 'content' => '冷却测试', 'name' => '刷子', 'csrf_token' => COMMENTS_CSRF];
    $r1 = commentsCall(['action' => 'create'], $p, ['csrf_token' => COMMENTS_CSRF], $sid);
    $r2 = commentsCall(['action' => 'create'], $p, ['csrf_token' => COMMENTS_CSRF], $sid);
    assertEquals(200, $r1['code'], '第一次应放行');
    assertEquals(429, $r2['code'], '60s 内第二次应 429');
});

test('公开 list 不含待审核', function () {
    $r = commentsCall(['action' => 'list', 'slug' => COMMENTS_SLUG], []);
    assertEquals(200, $r['code'], 'list 应 200');
    assertFalse(count($r['body']) > 0 && $r['body'][0]['content'] === '访客测试留言', '待审核内容不可公开见');
});

test('管理员 list 可见待审核', function () {
    $r = commentsCall(['action' => 'list', 'slug' => COMMENTS_SLUG], [], ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
    $found = false;
    foreach (($r['body'] ?? []) as $c) if ($c['content'] === '访客测试留言' && $c['status'] === 'pending') $found = true;
    assertTrue($found, '管理员列表包含 pending 评论');
});

test('approve: 管理员通过后公开可见; 非管理员 403', function () {
    // 找 pending id
    $list = commentsCall(['action' => 'list', 'slug' => COMMENTS_SLUG], [], ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
    $id = '';
    foreach (($list['body'] ?? []) as $c) if ($c['status'] === 'pending') { $id = $c['id']; break; }
    assertTrue($id !== '', '存在待审核评论');
    // 非管理员 approve → 403
    $deny = commentsCall(['action' => 'approve'], ['id' => $id, 'slug' => COMMENTS_SLUG, 'csrf_token' => COMMENTS_CSRF], ['csrf_token' => COMMENTS_CSRF]);
    assertEquals(403, $deny['code'], '非管理员 approve 应 403');
    // 管理员 approve → success
    $ok = commentsCall(['action' => 'approve'], ['id' => $id, 'slug' => COMMENTS_SLUG, 'csrf_token' => COMMENTS_CSRF], ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
    assertEquals(200, $ok['code'], '管理员 approve 应 200');
    // 公开可见
    $pub = commentsCall(['action' => 'list', 'slug' => COMMENTS_SLUG], []);
    $seen = false;
    foreach (($pub['body'] ?? []) as $c) if ($c['id'] === $id) $seen = true;
    assertTrue($seen, '通过后公开列表可见');
});

commentsCleanup();
