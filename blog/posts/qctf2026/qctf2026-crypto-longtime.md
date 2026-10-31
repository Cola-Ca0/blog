---
title: LongTime（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "from hashlib import sha256"
collection: QCTF2026
---
# LongTime

## 第一节：题目

题目是
```python
from hashlib import sha256
from secret import flag

MASK = (1 << 128) - 1
SEED = 0x6A09E667F3BCC908BB67AE8584CAA73B
ADD = 0x9E3779B97F4A7C15F39CC0605CEDC835
ROUNDS = (1 << 64) + 20261015


def step(x):
    x ^= (x << 13) & MASK
    x ^= x >> 7
    x ^= (x << 17) & MASK
    return (x ^ ADD) & MASK


state = SEED
for _ in range(ROUNDS):
    state = step(state)

key = sha256(state.to_bytes(16, "big")).digest()
ciphertext = bytes(a ^ b for a, b in zip(flag, key))

print(f"ciphertext = {ciphertext.hex()}")

```

一个 128 位的状态机。从 SEED 出发，一轮执行一次step：
```python
MASK = (1 << 128) - 1
def step(x):
    x ^= (x << 13) & MASK
    x ^= x >> 7
    x ^= (x << 17) & MASK
    return (x ^ ADD) & MASK
```
三次XOR 异或ADD，循环 ROUNDS = (1 << 64) + 20261015 次
最后的 state 取 sha256 当流密钥，和 flag 异或得到密文：

```
ciphertext = ba1bf5fe6c4592c3dc10ef3c4ff9c512724782ac32465e442c6c1f014c145b43
```

显然，这个计算量非常大，很耗时。不过题目都说了真的要循环下去吗？那肯定不是这么走的。
## 第二节：过程

这道题是用仿射变换+快速幂来跳过循环的
（说明：以下内容在AI的辅助理解下完成，这真的没有学到啊...）

在了解之后，我知道了
**仿射变换把 重复 2^64 次”变成求一个矩阵的 N 次幂”**
似乎就简单很多了

GF(2) 上的仿射变换可以用多一个常数坐标的办法变成线性变换。把状态扩成 129 维：
```
v = (x, 1)
```
映射变成
```
M = [ L   ADD ]
    [ 0    1  ]
```
原来 128 位的线性部分放左上角，ADD 放最后一列，最后一行只有右下角是 1，保证常数坐标永远是 1。这样每一步都是 129×129 的矩阵乘法。

然后用二进制快速幂。矩阵元素只有 0 和 1，用位运算实现一次乘法很快。

取结果向量的低128位sha256与密文XOR即可得到答案。


脚本：
```python
import hashlib

MASK = (1 << 128) - 1
SEED = 0x6A09E667F3BCC908BB67AE8584CAA73B
ADD = 0x9E3779B97F4A7C15F39CC0605CEDC835
ROUNDS = (1 << 64) + 20261015
CT = bytes.fromhex("ba1bf5fe6c4592c3dc10ef3c4ff9c512724782ac32465e442c6c1f014c145b43")


def step(x):                          # 一次仿射变换 x -> Lx ^ ADD
    x ^= (x << 13) & MASK
    x ^= x >> 7
    x ^= (x << 17) & MASK
    return (x ^ ADD) & MASK


def apply(A, v):                      # 矩阵乘向量：v 的每个 1 位挑一列异或起来
    r = 0
    while v:
        r ^= A[(v & -v).bit_length() - 1]
        v &= v - 1
    return r


def mul(A, B):                        # A·B 的第 j 列 = A 作用在 B 的第 j 列上
    return [apply(A, b) for b in B]


def matpow(A, e):
    R = [1 << j for j in range(129)]  # 单位阵的列
    while e:
        if e & 1:
            R = mul(R, A)
        A = mul(A, A)
        e >>= 1
    return R


# 补一维：状态 (x, 1)，前 128 列是 L 的列，最后一列是 (ADD, 1)
M = [step(1 << j) ^ ADD for j in range(128)] + [ADD | (1 << 128)]

# 自检：小指数下矩阵幂必须和逐轮模拟一致
for E in (0, 1, 2, 3, 17, 1000):
    d = SEED
    for _ in range(E):
        d = step(d)
    assert apply(matpow(M, E), SEED | (1 << 128)) & MASK == d, E
print("self-check OK")

state = apply(matpow(M, ROUNDS), SEED | (1 << 128)) & MASK
key = hashlib.sha256(state.to_bytes(16, "big")).digest()
print(f"state = {state:032x}")
print(bytes(a ^ b for a, b in zip(CT, key)))
```

结果：
```
self-check OK
state = e8edcc3b102b68396e398e3d9d4256e5
b'flag{affine_xorshift_skips_fast}'
```

最后 flag：`flag{affine_xorshift_skips_fast}`
