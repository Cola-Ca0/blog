---
title: 签到（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "题面给了一串 120 位 A/B："
collection: QCTF2026
---
# 签到

## 第一节：题目

题面给了一串 120 位 A/B：

```text
AAABABBBBAABABAAAABAAAAAAAABBBABAAAABABBAAAAAAAAAAAAABAAAAABAAABAAAABABAAABBAAABBABAAAAABABABBBAAAAAABBAAAABABAAAABAABAA
```

## 第二节：过程

越过4层栅栏就能吃到24块培根，答案放脸上了

![](/assets/posts/qctf2026/qctf2026-crypto-signin-1.png)
选标准型
```text
AAABABAAAAABBABBAAABBAAABBAABAAABBBAABAAAABABAABAAABBAAAAABAAABAAAAAAAABBAAAAABBAABAAAAAAABAABAAAAABAAAAAAAABAABBABABBAA
```

![](/assets/posts/qctf2026/qctf2026-crypto-signin-2.png)
再24培根
解得：`CROSSTHEFENCEANDEATBACON`。

最后 flag：

`flag{CROSS_THE_FENCE_AND_EAT_BACON}`