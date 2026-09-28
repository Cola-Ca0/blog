<?php
/**
 * JSON Store — 对 JSON 文件的**原子**读-改-写
 *
 * 2026-09-28 新增。起因: 渗透测试实测出并发写会静默丢数据(12 条丢 9 条),
 * 而且本站唯一的账号在并发的渗透测试写入中, 把用户刚改的密码覆盖回了旧哈希。
 *
 * 为什么原来的写法不够:
 *   `file_put_contents($f, json_encode($d), LOCK_EX)` —— LOCK_EX **只锁「写」这一下**,
 *   不锁「读 → 改 → 写」整个过程。两个请求各自读到旧内容、各自追加、后写的覆盖先写的。
 *   更糟的是 file_put_contents 内部是**先截断再写**, 没加锁的读可能读到半个 JSON,
 *   json_decode 返回 null, 调用方回退成空数组, 再写回去 —— **整个文件被清空**。
 *
 * 本模块把整段读改写放进一个排他锁里, 消除上述两个问题。
 */

/**
 * 原子读-改-写。
 *
 * @param string   $file    目标文件(不存在则创建)
 * @param callable $mutator function(array &$data) —— 就地修改 $data;
 *                          也可 return 一个新数组来整体替换;
 *                          **return false 表示「本次不改动」, 跳过写盘**。
 * @param array    $default 文件为空/损坏时的初始值
 * @return array|false      写入成功返回最终数据; 失败返回 false
 */
function jsonUpdate(string $file, callable $mutator, array $default = []) {
    $fh = @fopen($file, 'c+');
    if (!$fh) return false;

    if (!flock($fh, LOCK_EX)) { fclose($fh); return false; }

    try {
        $raw  = stream_get_contents($fh);
        $data = ($raw === '' || $raw === false) ? $default : json_decode($raw, true);
        if (!is_array($data)) $data = $default;

        $ret = $mutator($data);
        if ($ret === false) return $data;          // 调用方声明「无需改动」→ 不写盘
        if (is_array($ret)) $data = $ret;

        $out = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($out === false) return false;

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, $out);
        fflush($fh);
        return $data;
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/**
 * 原子读(共享锁)。写入持排他锁期间, 读会等待, 因此不会读到半个 JSON。
 *
 * @return array 文件不存在/为空/损坏时返回 $default
 */
function jsonRead(string $file, array $default = []): array {
    if (!is_file($file)) return $default;
    $fh = @fopen($file, 'r');
    if (!$fh) return $default;
    if (!flock($fh, LOCK_SH)) { fclose($fh); return $default; }
    try {
        $raw  = stream_get_contents($fh);
        $data = ($raw === '' || $raw === false) ? $default : json_decode($raw, true);
        return is_array($data) ? $data : $default;
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}
