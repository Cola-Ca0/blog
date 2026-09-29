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
    // strict_mode 下服务端签发 sid; 第一次请求回传真实 sid, 第二次复用同一会话
    $p = ['slug' => COMMENTS_SLUG, 'content' => '冷却测试', 'name' => '刷子', 'csrf_token' => COMMENTS_CSRF];
    $r1 = commentsCall(['action' => 'create'], $p, ['csrf_token' => COMMENTS_CSRF]);
    $r2 = commentsCall(['action' => 'create'], $p, ['csrf_token' => COMMENTS_CSRF], $r1['sid'] ?? '');
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

// 2026-09-29 备案口径: 「站内访客内容一律先审后发」必须真的 fail-closed。
// 判据 = 缺 status 字段的评论不得出现在任何公开面 (此前 ?? 'approved' 是 fail-open)。
test('无 status 字段的评论对公开面不可见 (fail-closed)', function () {
    $file = __DIR__ . '/../data/comments/' . COMMENTS_SLUG . '.json';
    file_put_contents($file, json_encode([[
        'id'         => 'legacy1',
        'username'   => '上古访客',
        'content'    => '缺status的历史评论',
        'created_at' => '2020-01-01 00:00:00',
    ]], JSON_UNESCAPED_UNICODE));

    $pub = commentsCall(['action' => 'list', 'slug' => COMMENTS_SLUG], []);
    $seen = false;
    foreach (($pub['body'] ?? []) as $c) if (($c['content'] ?? '') === '缺status的历史评论') $seen = true;
    assertFalse($seen, '缺 status 必须视为未通过 —— 否则对外的「先审后发」声明有缺口');

    // 反向: 管理员仍应看得到 (证明不是一刀切过滤, 审核 UI 没坏)
    $adm = commentsCall(['action' => 'list', 'slug' => COMMENTS_SLUG], [], ['is_admin' => 1, 'username' => 'admin', 'csrf_token' => COMMENTS_CSRF]);
    $adminSees = false;
    foreach (($adm['body'] ?? []) as $c) if (($c['content'] ?? '') === '缺status的历史评论') $adminSees = true;
    assertTrue($adminSees, '管理员列表应仍包含它');
});

// index.php 的首页侧栏 (Latest Signals) 是同一逻辑的另一份内联拷贝, 跑不进 harness,
// 只能用源码守卫。2026-09-29 之前它写的是「只跳过显式 pending」, 缺 status 的会漏上公开面。
test('首页侧栏 Latest Signals 同样 fail-closed (源码守卫)', function () {
    $src = file_get_contents(__DIR__ . '/../index.php');
    assertFalse(str_contains($src, "isset(\$c['status']) && \$c['status'] === 'pending'"),
        'index.php 又变回「只跳过显式 pending」—— 缺 status 的评论会漏上首页侧栏');
    assertTrue(str_contains($src, "(\$c['status'] ?? '') !== 'approved'"),
        'index.php 的 Latest Signals 应为「非 approved 一律跳过」');
});

commentsCleanup();
