---
title: 雾上的伊拉斯谟（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "《雾上的伊拉斯谟》是一个多入口的 Web 应用。首页是一份古籍索引，页面风格模仿伊拉斯谟《愚人颂》的校勘工作。题目提供的入口包括："
collection: QCTF2026
---
# 雾上的伊拉斯谟

## 第一节：题目

《雾上的伊拉斯谟》是一个多入口的 Web 应用。首页是一份古籍索引，页面风格模仿伊拉斯谟《愚人颂》的校勘工作。题目提供的入口包括：

| 端点                              | 作用                                                                 |
| ------------------------------- | ------------------------------------------------------------------ |
| GET /                           | 索引页，CSP default-src 'self'，XFO DENY                                |
| GET /framed                     | 旧版校样页，脚本会以 From-Fetch: 1 预取 /view，把结果塞进 sandbox="" 的 iframe srcdoc |
| GET /view                       | 需要 From-Fetch 请求头，响应体是请求者 folios cookie 的单次 URL 解码原文               |
| GET /notes/add?note=            | 用白名单 sanitizer 净化后写入 folios cookie                                 |
| POST /api/chat                  | 词条查询，手写 alias 表加子串匹配                                               |
| POST /api/gloss                 | 服务端 JavaScript 沙箱，renderer 是异步函数体                                  |
| POST /api/report                | 把 HTML 交给 bot 审阅，约 10 到 16 秒后状态变为 visited                          |
| GET /reference/open?next=       | 开放重定向                                                              |
| /debug/pprof/*、/health、/metrics | Go 代理自身暴露的调试面                                                      |

题目的提示是 **不限制 Agent；切记渗透攻击面的思考（真的难吗，换一换思路）**。

## 第二节：过程

### 第一步：把应用面摸清

我们先把前端资源全部拉下来。首页 app.js 不大，读完之后应用的行为基本清楚：

- /view 的响应体不是请求参数，而是 folios cookie。只要带一个非空的 From-Fetch 头，任何值都会被接受。
- /view 的响应头是 Cache-Control: private, max-age=300 加 Vary: Cookie，没有 CSP，也没有 X-Frame-Options。也就是说，它是一个可以进入浏览器私有缓存、并且能执行脚本的反射点。
- /framed 页面的 framed.js 会先 fetch("/view" + location.search, {headers: {"From-Fetch": "1"}})，再把返回的 HTML 写进 iframe 的 srcdoc，sandbox 为空字符串。
- /notes/add 会把 note 净化成只允许 b、i、a、img 的 HTML，包进 article 标签，URL 编码一次后写进 folios cookie。
- /api/report 是 bot 入口，返回一个 12 位十六进制 id，GET /api/report/ 可以查状态。

这些线索合起来，题目的主戏显然是缓存 XSS：先在 /view 种下一份带脚本的私有缓存，再让 bot 以顶层导航的方式访问同一个 URL，命中缓存后脚本在目标源执行，偷走 bot 的 cookie。

### 第二步：缓存 XSS 链的复现

我们把整条链在真实挑战站上跑通了，不依赖任何推测：

1. 用 /notes/add 写入一个包含脚本的 note，cookie 变成净化后的 article。
2. 用带 From-Fetch: 1 的请求访问 /view，响应被浏览器以 private, max-age=300、Vary: Cookie 缓存。
3. 同一个浏览器对同一个 URL 做顶层导航。服务端不会再要求 From-Fetch，因为请求根本没有到服务端，直接命中私有缓存。
4. 响应里没有 CSP，脚本执行。

本地 Edge 155 和真实挑战站都验证了这条路径，document.title 和 document.cookie 都能读到。我们还测了一组缓存键矩阵：陈旧的裸响应只有后退前进导航会放行，新鲜的 max-age 响应加直接导航可以命中，跨站脚本设置 location 一定会 miss，因为缓存分区带了 cn_ 之类的键。这些结论和作者那篇《有趣的绕过，Cache XSS》一致，题目就是那篇文章的实战化。

剩下的问题是 bot。我们朝 /api/report 提交了各种外带载荷，img、script、meta refresh、开放重定向，外部信标用了 Burp Collaborator 和自建的 requestrepo 捕获箱两条通道。结果是零命中。可以确定 bot 要么没有外网出口，要么审阅流程不是我们想象的顶层渲染。缓存 XSS 这一线在真实站上可复现，但没法把 bot 的 cookie 带出来。

### 第三步：vm2 沙箱线

/api/gloss 是另一条主线。renderer 会被放进 vm2 里执行，返回原始值，结果以字符串回显。

我们花了很多时间做指纹和行为对照：

- 远程是一个 vm2 加固 fork。prepareStackTrace 的清洗器与 vm2 3.12.2 逐字一致，并且额外冻结了 Error、Object、Array、Function 的 prototype。
- 沙箱注入的是宿主 JSON 对象，JSON.isProxy 为 true，JSON.parse 和 JSON.stringify 是宿主函数。
- vm 选项是 eval: false、wasm: false，globalThis 里额外有 console、global、GLOBAL、root、marginaliaInput。
- 构造函数链全部被拦，constructor.constructor 拿到的仍然是沙箱 Function。
- prepareStackTrace 的原始帧、宿主回调、跨请求持久化都试过，没有出口。
- 唯一的行为异常是远程 JSON.stringify(x, replacer) 的 replacer 不执行。本地 vm2 3.12.2 会执行。这条差异说明远程的桥接层和上游不完全一样，是我们当时最后一条没有穷尽的线索。

这一线没有逃逸成功。我们把 vm2 的各种边角都测了一遍，结论是这座沙箱在当时的版本上是关死的。

### 第四步：换一换思路，把作者算进攻击面

题目的提示说 **渗透攻击面的思考**。我们之前一直在打 HTTP 服务和沙箱，但攻击面不只有目标服务器。题目作者、作者的博客、作者的公开仓库，都是攻击面的一部分。

作者在题面里署名 YMsora。我们顺着这个名字找到他的博客 ymsora.com，再从博客的外链找到 GitHub 账号 github.com/YMs0ra。账号下有一个仓库 Erasmus，名字和题目一样。

仓库的 main 分支是空的。提交历史里有一条叫 clean history 的记录，把 main 分支清成空树。如果只看 main，会以为作者已经把敏感内容删干净了。

但是 git 的引用不止 main。用 ls-remote 把仓库的全部分支和 tag 列出来：

```bash
git ls-remote https://github.com/YMs0ra/Erasmus
```

输出里除了 main，还有一个 refs/heads/legacy 分支和 refs/tags/v47。这两个引用都没有被 clean history 覆盖。

### 第五步：legacy 分支里的 vault-note

把 legacy 分支拉到本地：

```bash
git clone -b legacy https://github.com/YMs0ra/Erasmus
cd Erasmus
git log --oneline
```

分支里有一个文件 web/vault-note.txt，提交信息是 ledger。文件内容只有一行：

```
/opylf57uzdgquwp0n1ogqzki6oep4oxy3e5bazt628iwewt7
```

这是一条秘密路径。回到挑战站，把这个路径拼到主机后面直接 GET：

```bash
curl -s "http://3000-21111ed6-9826-4230-861e-6c3b32a7a2b7.challenge.ctfplus.cn/opylf57uzdgquwp0n1ogqzki6oep4oxy3e5bazt628iwewt7"
```

服务端返回 200，Content-Type 是 text/plain，正文就是 flag。不需要经过缓存 XSS，不需要经过 vm2，也不需要 bot。

最后 flag：`flag{5e421a4e-3fd8-4163-9cbb-ef48a37207f9}`