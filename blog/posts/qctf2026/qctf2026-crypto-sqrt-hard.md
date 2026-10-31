---
title: 手撕平方根-Hard（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "跟 Easy 是同一个服务升级版：128 关全过才给 flag，交上去的 x要求小于等于10000、服务器又只回“最小的那个根”——现成的平方交上去换不回任何信息。做的时候卡了三天…"
collection: QCTF2026
---
# 手撕平方根-Hard

## 第一节：题目

跟 Easy 是同一个服务升级版：128 关全过才给 flag，交上去的 x要求小于等于10000、服务器又只回“最小的那个根”——现成的平方交上去换不回任何信息。做的时候卡了三天还是四天一直没有想出来....最后通过询问AI了解到了Jacobi 符号，才破的局。
## 第二节：过程
Jacobi 符号即只靠 n 和 x 就能免费算出来的一个 ±1 值，提前告诉你 x 在模 n 下有没有平方根。
这题解题关键在于：我们要算 Jacobi 符号——而 n 是公开的，Jacobi 是免费能算的。而J(x) = -1 的 x 必无根，直接跳过；J(x) = +1 的有根率约 50%，所以每关挑一组三角探针 [a, b, c, ab, ac, bc]（都挑 J=+1 的）
其实之前掉过坑了以为 a b c都是固定的，但是发现如果固定了abc那最终通关的概率会非常低，于是后面想到了，根据题目动态变化abc可以让正确率提高
如何选择abc？
```python
good = []
for p in (2,3,5,7,11,13,17,19,23,29,31,37,41,43):
    if jacobi(p, n) == 1:      # 只看 n 和 p，不需要分解 n
        good.append(p)
        if len(good) == 3: break
a, b, c = good
```
jacobi(p, n) 是脚本里自己实现的 Jacobi 符号：只用公开的 n 和 p 就能算，直接获取
然后打128次就行了，虽然仍然会有结构性死亡的概率，但是概率很小，128次方之后成功的概率还有18%左右，那多做几次就行了

最后脚本

```python

# py -3.12 sqhard_simple.py <host> <port> [maxlv]
import re
import sys
from math import gcd

from pwn import context, remote
from sympy import jacobi_symbol, sqrt_mod
from sympy.ntheory.modular import crt

context.log_level = "error"
HOST, PORT = sys.argv[1], int(sys.argv[2])
MAXLV = int(sys.argv[3]) if len(sys.argv) > 3 else 128


def pick(n):
    """前三个 J=+1 的小素数：乘积不超 9999，每个探针约一半概率有根"""
    return [p for p in (2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31) if jacobi_symbol(p, n) == 1][:3]


def ask(io, x):
    io.sendline(str(x).encode())
    io.recvuntil(b"y = ")
    s = io.recvline().strip()
    return None if s == b"None" else int(s)


def split(xs, ys, n):
    """在已问到的 (x, y) 里找同一个数的两个不同根：差值/和与 n 求 gcd"""
    y_of = dict(zip(xs, ys))
    live = [(x, y) for x, y in y_of.items() if y is not None]
    for xa, ya in live:
        for k in range(2, 300):
            xk = k * k * xa
            if xk > 9999:
                break
            yk = y_of.get(xk)
            if yk is None:
                continue
            for d in (yk - k * ya, yk + k * ya):
                g = gcd(d % n, n)
                if 1 < g < n:
                    return g
    for xa, ya in live:                       # 乘积网：ya·yb 应是 xa·xb 的根
        for xb, yb in live:
            yk = y_of.get(xa * xb)
            if yk is None:
                continue
            for d in (yk - ya * yb, yk + ya * yb):
                g = gcd(d % n, n)
                if 1 < g < n:
                    return g
    return None


def main():
    while True:
        try:
            io = remote(HOST, PORT, timeout=20)
            lv = 1
            while lv <= MAXLV:
                n = int(re.findall(rb"n = (\d+)", io.recvuntil(b"x = ", timeout=30))[-1])

                trip = pick(n)
                a, b, c = trip if len(trip) == 3 else (15, 10, 6)   # 兜底三角
                xs, ys = [], []
                for x in (a, b, c, a * b, a * c, b * c):
                    xs.append(x); ys.append(ask(io, x))
                    if ys[-1] is not None:
                        break

                xb, yb = xs[-1], ys[-1]
                if yb is not None:                # 按 k·yb 离 n/2 的远近降序折叠
                    ks = [k for k in range(2, 300) if k * k * xb <= 9999]
                    ks.sort(key=lambda k: -min(k * yb % n, n - k * yb % n))
                    for k in ks:
                        if len(xs) == 7:
                            break
                        if k * k * xb not in xs:
                            xs.append(k * k * xb); ys.append(ask(io, k * k * xb))
                while len(xs) < 7:               # 协议固定 7 问，补满
                    xs.append(30); ys.append(ask(io, 30))

                X = int(re.findall(rb"x = (\d+)", io.recvuntil(b"y = ", timeout=20))[-1])
                p = split(xs, ys, n)
                ans = None
                if p:
                    q = n // p
                    rp, rq = sqrt_mod(X % p, p), sqrt_mod(X % q, q)
                    if rp is not None and rq is not None:
                        ans = int(crt([p, q], [int(rp), int(rq)])[0])
                io.sendline(str(ans if ans is not None else 1).encode())
                print(f"L{lv}: {'OK' if ans is not None else 'DEATH'}")
                lv = lv + 1 if ans is not None else 1
            print(io.recvall(timeout=10).decode("utf-8", "replace"))
            return
        except Exception as e:
            print("reconnect:", str(e)[:80])


if __name__ == "__main__":
    main()
```

最后 flag：
`flag{4eeae838-d967-4f1d-8bdc-869fb8067c08}`