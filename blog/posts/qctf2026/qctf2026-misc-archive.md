---
title: 压缩包（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "第一步伪加密这么改"
collection: QCTF2026
---
# 压缩包

## 第一节：题目

套娃zip
## 第二节：过程

第一步伪加密这么改
![](/assets/posts/qctf2026/qctf2026-misc-archive-1.png)

第二步掩码攻击，用ARCHPR
```text
but there is a string of mysterious characters here:
qctf2026????
do you know what it is? qwq
```
解出来是`qctf2026g0od`


最终flag
`?CTF{Thi3_is_@_ez_z1p!}`