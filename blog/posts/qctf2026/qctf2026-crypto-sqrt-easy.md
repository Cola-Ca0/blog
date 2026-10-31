---
title: 手撕平方根-Easy（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "服务器开的是求平方根的服务，我们要交x返y和c，目标是分解n然后还原flag"
collection: QCTF2026
---
# 手撕平方根-Easy

## 第一节：题目

服务器开的是求平方根的服务，我们要交x返y和c，目标是分解n然后还原flag

## 第二节：过程

分析题目给的代码我们发现了
1.交完一次必须重启再交
2.Oracle是先求根再CRT
```python
yp, yq = Sqrt(x, p), Sqrt(x, q)
y = (yp * q * invert(q, p) + yq * p * invert(p, q)) % n
```
但我们的方程是模 n，答案必须同时在 mod p 和 mod q 下成立。两边各有两种选择，自由组合就是 4 种：

| mod p | mod q | CRT 拼出来的根 |
| ----- | ----- | --------- |
| s     | s     | `s`       |
| −s    | −s    | `−s`      |
| s     | −s    | `t`       |
| −s    | s     | `−t`      |

3.sqrt函数
```python
if powmod(x, pM1D2, p) == p - 1:
    return None
```
是用欧拉判据看是不是二次剩余，否则返回None

4.加密
Rabin + XOR 掩码
```python
padded_flag = flag + urandom(128 - len(flag))
l, r = padded_flag[:64], padded_flag[64:]
l = strxor(l, sha3_512(r).digest())
r = strxor(r, sha3_512(l).digest())
m = mpz(int.from_bytes(r + l, byteorder="little"))
c = m * m % n
```

攻击面在符号没有保护，导致了我们交 x = s²，返回的根 y 是 ±s 

（使用了AI帮助理解）
x = s²。在模 p 下，y² ≡ x 只有两个解：`s` 和 `-s`（p 是奇素数）。模 q 同样只有 `s` 和 `-s`。

只要 y ≠ ±s (mod n)，**gcd(y - s, n)** 就可以直接打开n

虽然每次只有一半的概率成功，但是没关系可以多试几次

最后分解n拿到pq之后把c模然后平方根之后按XOR还原明文即可

```python
# 交 x=s^2，oracle 回一个根 y；y != ±s 时 gcd(y-s, n) 直接劈开 n（每次约 1/2）
import random, sys
from math import gcd
from hashlib import sha3_512
from pwn import remote, context
from sympy import sqrt_mod
from sympy.ntheory.modular import crt

context.log_level = "error"

def get_instance(host, port):
    while True:
        io = remote(host, port)
        io.recvuntil(b"n = "); n = int(io.recvline())
        s = random.randrange(2, n - 1)
        io.recvuntil(b"x = "); io.sendline(str(pow(s, 2, n)).encode())
        line = io.recvline()
        if b"None" in line:                      # 这个 s 不行，换一个
            io.close(); continue
        y = int(line.split(b"=")[1])
        g = gcd(y - s, n)
        if g in (1, n):                          # 回的是 ±s，白问
            io.close(); continue
        io.recvuntil(b"c = "); c = int(io.recvline()); io.close()
        return n, g, n // g, c

def unpad(m):
    b = m.to_bytes(128, "little")
    R1, L1 = b[:64], b[64:]
    r = bytes(x ^ y for x, y in zip(R1, sha3_512(L1).digest()))
    l = bytes(x ^ y for x, y in zip(L1, sha3_512(r).digest()))
    return l + r

if len(sys.argv) >= 3:
    host, port = sys.argv[1], int(sys.argv[2])
else:
    host, port = input("nc 地址（host port，或 host:port）: ").replace(":", " ").split()[:2]
    port = int(port)
n, p, q, c = get_instance(host, port)

for ap in {int(sqrt_mod(c % p, p)), -int(sqrt_mod(c % p, p)) % p}:
    for aq in {int(sqrt_mod(c % q, q)), -int(sqrt_mod(c % q, q)) % q}:
        s = unpad(int(crt([p, q], [ap, aq])[0]))
        if s.startswith(b"flag{") and b"}" in s:
            print(s[:s.index(b"}") + 1].decode())


```

最后 flag：

`flag{21a0c7b8-494e-42b1-9269-06bd09da48bf}`