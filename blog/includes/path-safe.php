<?php
/**
 * Path Safety — projects/<id>/<file> 的解析与包含校验
 *
 * 2026-09-28 从 projects/index.php 抽出成独立模块。
 * 抽出理由: 原实现只存在于页面文件里, 测试只能「重写一份同样的逻辑」来测
 * (tests/test_projects.php 原文就写着 "Replicate the exact gating logic") ——
 * 那测的是测试自己的副本, 不是真代码。结果: 真实代码有严重路径遍历,
 * 测试却全绿。**测试必须测真代码, 否则比没有测试更危险。**
 *
 * 背景 (为什么这么写):
 *   - basename('..') === '..' —— **basename 不是防遍历手段**, 别拿它当防线
 *   - 包含校验的锚点必须**固定**, 不能由用户输入推导, 否则等于「拿攻击者指定的目录当基准」
 */

/**
 * 解析并校验 projects/<project>/<file>
 *
 * @param string $root    projects 目录的 realpath(锚点, 调用方传入固定值)
 * @param string $project 项目 id(用户输入)
 * @param string $file    文件名(用户输入)
 * @return string|null    通过校验的**绝对真实路径**; 任何一步不合法返回 null
 */
function resolveProjectFile(string $root, string $project, string $file): ?string {
    if ($root === '' || $project === '' || $file === '') return null;

    // 防线 ①: 项目 id 白名单 —— 连 '.' 都不允许, 从根上杜绝 '..'
    if (preg_match('/[^a-zA-Z0-9\-]/', $project)) return null;

    // 防线 ②: 剥掉目录成分, 并显式拒绝 '.', '..', ''
    $name = basename(str_replace('\\', '/', $file));
    if ($name === '' || $name === '.' || $name === '..') return null;

    // 防线 ③: 解析真实路径, 并要求落在固定锚点 $root 之下
    $path = realpath($root . DIRECTORY_SEPARATOR . $project . DIRECTORY_SEPARATOR . $name);
    if ($path === false || !is_file($path)) return null;
    if (!str_starts_with($path, $root . DIRECTORY_SEPARATOR)) return null;

    return $path;
}
