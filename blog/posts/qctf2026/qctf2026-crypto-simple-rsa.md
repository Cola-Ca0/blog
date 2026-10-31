---
title: 简单的RSA（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "给定一组未使用填充的参数："
collection: QCTF2026
---
# 简单的RSA

## 第一节：题目
给定一组未使用填充的参数：

```text
n = 6277101735386680763835789512893946382249585600355786358579
e = 65537
c = 1464504621428573518146796322459017865530248645483047228185
```


## 第二节：过程
![](/assets/posts/qctf2026/qctf2026-crypto-simple-rsa-1.png)
简单又强势的办法，除去了较为繁琐的公式...

```python
from Crypto.Util.number import long_to_bytes

p = 79228162514264337593543950397
q = 79228162514264337593543951407
e = 65537
c = 1464504621428573518146796322459017865530248645483047228185

n = p * q
d = pow(e, -1, (p - 1) * (q - 1))
print(long_to_bytes(pow(c, d, n)).decode())

```

最后 flag：

`flag{RSA_is_so_amazing}`
