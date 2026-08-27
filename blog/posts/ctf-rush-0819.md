---
title: "单日 12 题：CTF 速刷复盘（4 → 16/100）"
date: 2026-08-19
category: CTF
tags: ["CTF", "Writeup", "Web", "Crypto", "Stego"]
summary: "单日 12 题的刷题实录：Cookie 伪造、SQLi 五步、LFI 三板斧、zip 生日爆破……以及今天最贵的五条教训。"
draft: false
---

# 2026-08-19 CTF 速刷复盘（4 → 16/100）

> 单日 +12 题 | 平台: CTFshow | 双线模式: 本机 curl 打靶 + 浏览器/Burp 实操

## 今日题单

### Web 三连（第一章收尾）
| 题 | 打法一句话 | flag |
|----|-----------|------|
| Cookie 伪造 | guest/guest 弱口令 → 登录拿 `role=guest` → 伪造 `role=admin` | `CTF{cookie_injection_is_fun}` |
| 签到 | 注释里 base64 解码 | `ctfshow{4776c948-...}` |
| web2 SQLi | 万能密码 `' or 1=1#` → union 五步（列数→显示位→爆表→爆列→取数） | `ctfshow{89bc7d76-...}` |
| web3 LFI | `include($_GET[url])` → php://filter 读源码 → php://input RCE → find 搜 flag | `ctfshow{f5041d83-...}` |

### 萌新密码
| 题 | 打法一句话 | flag |
|----|-----------|------|
| 密码1 | hex → base64 → 栅栏密码(栏2) | `KEY{dffb06a3-...}` |
| 密码2 | 键盘包围：键盘上圈住中间键（rdcvbg→f） | `KEY{fwy}` |
| 密码3 | 摩斯 → 培根（M/D 变体） | `flag{GUOWANG}`（大写！） |

### 萌新隐写/杂项
| 题 | 打法一句话 | flag |
|----|-----------|------|
| 隐写2 | zip 生日爆破——**199 前缀全数字**，不是合法日期 | `flag{brute_force}` |
| 杂项·银行卡 | 97年10月1日+小五 → 971015 | `flag{971015}` |
| 杂项·md5 | somd5 反查 `md5(话+ctf)` = helloctf → hello | `flag{hello}` |
| 隐写·word | doc 二进制搜 UTF-16LE `f\0l\0a\0g\0` | `flag{word_stega}` |
| 密码·加餐 | base64 → **HTML 实体还原** `&lt;`→`<` → Ascii85（25字符=5×5） | `flag{base_base_base}` |

## 踩坑记录（今日最贵教训）
1. **「生日做密码」= 前缀范围，不是日期字典** — `19981000` 不是合法日期！先跑 `1990xxxx` 全数字，再跑合法日期（浪费 1.5h）
2. **网页复制密文带 HTML 实体** — `<` → `&lt;`，base85 长度永远不对；解出乱码先查实体
3. **Set-Cookie 换 session ≠ 登录成功** — 无 PHPSESSID 的请求 PHP 必然发新 session，别把会话建立当身份变化
4. **zipfile 多线程竞态** — 共享文件句柄会吞掉正确密码，每线程必须独立 ZipFile 实例
5. **GET 请求带 body 无效** — PHP 不解析 GET body 进 `$_POST`

## 方法论沉淀
- **Web 认证绕过通用流程**：先弱口令拿合法会话 → 观察 Set-Cookie 下发 → 伪造角色 cookie 重放验证端点（详见 2026-08-19-Cookie伪造）
- **SQLi 五步**：列数 → 显示位 → information_schema 爆表 → 爆列 → 取数（详见 2026-08-19-SQL注入入门）
- **LFI 三板斧**：/etc/passwd 验证 → php://filter 读源码 → php://input RCE + find 多关键词（详见 2026-08-19-web3-文件包含）
- **找 flag 组合**：`find / -name "*flag*" -o -name "*ctf*" -o -name "*key*" 2>/dev/null`，排除 /sys /proc 噪音
- **编码识别**：解一半「像 flag 但顺序乱」→ 栅栏；字符集含符号 → base85/Ascii85；`=` 结尾 → base64

## 关联笔记
- 2026-08-19-Cookie伪造
- 2026-08-19-SQL注入入门 / 2026-08-19-web2-SQL注入
- 2026-08-19-web3-文件包含
- 2026-08-19-萌新隐写2-zip爆破
- 2026-08-18-CTF首日四题

## 下一步
- 一句话木马/反弹 shell 前，先打命令执行 RCE 过渡题
- 每 5~10 题合并一篇博客文章（面试作品集素材）
