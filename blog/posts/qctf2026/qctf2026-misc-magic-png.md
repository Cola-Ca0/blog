---
title: magic_png（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "是一张普通照片，010无明显夹带如pk png，故打开 StegSolve"
collection: QCTF2026
---
# magic_png

## 第一节：题目
是一张普通照片，010无明显夹带如pk png，故打开 StegSolve 

## 第二节：过程
RGB全选择最低位，不选Alpha
预览区直接出现一段可读文本：
![](/assets/posts/qctf2026/qctf2026-misc-magic-png-1.png)

```
MTEgMTExMTEgMDEwIDAwMCAwMDAxMSAwMDExMDEgMDAwMDEgMDAxMCAxIDAwMDExIDAxMCAwMDExMDEgMTAwMCAxMDAwMCAwMDAwMSAwMDExMDEgMDExMTEgMTAgMDAxMTAxIDAxMDAgMDAwIDEwMDA=
```

Base64
结果是：

```
11 11111 010 000 00011 001101 00001 0010 1 00011 010 001101 1000 10000 00001 001101 01111 10 001101 0100 000 1000
```

显然是摩斯密码1 = _      0 = .

得到：

```
M0RS3 / 4FT3R / B64 / 1N / LSB
```

最后 flag：`flag{M0RS3_4FT3R_B64_1N_LSB}`