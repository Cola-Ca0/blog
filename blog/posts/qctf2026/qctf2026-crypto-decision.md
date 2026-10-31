---
title: Decision（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "题目是给了一个40行的数据和一个代码，虽然但是这40行数据参杂了随机数组，让这道题变得较为困难一些"
collection: QCTF2026
---
# Decision

## 第一节：题目

题目是给了一个40行的数据和一个代码，虽然但是这40行数据参杂了随机数组，让这道题变得较为困难一些
## 第二节：过程

代码结构主要是
```python
for _ in range(40):
    coin = randint(0, 1)
    key += str(coin)              # 40 位答案串，最后就是 AES 的钥匙原料
    x, y = randint(0, p-2), randint(0, p-2)
    if coin:
        z = x * y % (p - 1)      # 真 DDH 组
    else:
        z = randint(0, p - 2)    # 随机组
    f.write(str((pow(g,x,p), pow(g,y,p), pow(g,z,p))) + '\n')

key = sha256(key.encode()).digest()[:16]
enc = AES.new(key, AES.MODE_ECB).encrypt(pad(flag, 16))
```
而我们想到了，40行数据并非所有的数据都是有用的，代码里面的抛硬币就是说明了这串40行代码中会存在随机组和真DDH组
所以目标：判断 40 行里哪些是真DDH组，拼出 40 位串，sha256 取前 16 字节解 AES。
如何判断真DDH？事实上，因为DDH的解密方式是
```text
给定 (A, B, C) = (g^x, g^y, g^z)：
真 DDH  ⇔  z ≡ x·y (mod ord(g))
```
而你不知道xy，再加上40组本身并不是很多，因此可以用排除+暴力的方式拿到flag
由χ(g^x) = (-1)^x得 x、y、z 的奇偶。真组必满足：
```text
parity(z) == parity(x) & parity(y)
```
我们可以通过这个方式排除14组
接下来我们用 通过答案反推排除的方式
即答案中包含如CTF flag的开头
于是把 26 个没有排除的全都枚举，每个候选都试一次 AES 首块解密；只有正确的 40 位串能让第一块以 flag{ 开头，这波算是暴力了

完整脚本
```python
# 用法: py -3.12 decision_simple.py output.txt
import hashlib
import sys
from multiprocessing import Pool

from Crypto.Cipher import AES
from Crypto.Util.Padding import unpad

P = 9706421526104134675216178698618252594384526616513912867587820435227225196046220898188947059107560993264628438128588538107847960717709029325452940057166067
ENC = bytes.fromhex("3923d855c2a08a7b9b0ce86682c52925a52e53428271c1b9e488fe9747bc63b7")
OUT = sys.argv[1] if len(sys.argv) > 1 else "output.txt"

TPL = UNKNOWN = None            # 由 init() 在子进程里填好


def odd(a):
    """χ(a) = a^((p-1)/2) mod p；χ(2) = -1，所以 χ(g^x) = -1 等价于 x 是奇数"""
    return pow(a, (P - 1) // 2, P) != 1


def load(path):
    tpl, unknown = bytearray(b"?" * 40), []
    for i, (gx, gy, gz) in enumerate(eval(line) for line in open(path) if line.strip()):
        if odd(gz) != (odd(gx) and odd(gy)):
            tpl[i] = ord("0")           # 奇偶不符，铁定 coin=0
        else:
            unknown.append(i)
    return tpl, unknown


def init(path):                     # Windows 下子进程是重新导入模块，用 initializer 传数据
    global TPL, UNKNOWN
    TPL, UNKNOWN = load(path)


def check(m):
    t = bytearray(TPL)
    for pos in UNKNOWN:             # 把 m 的二进制位填进存疑位置
        t[pos] = ord("0") + (m & 1)
        m >>= 1
    key = hashlib.sha256(t).digest()[:16]
    return bytes(t) if AES.new(key, AES.MODE_ECB).decrypt(ENC[:16]).startswith(b"flag{") else None


if __name__ == "__main__":
    tpl, unknown = load(OUT)
    print(f"存疑 {len(unknown)} 位，2^{len(unknown)} 个候选")
    with Pool(initializer=init, initargs=(OUT,)) as pool:
        for r in pool.imap_unordered(check, range(1 << len(unknown)), chunksize=1 << 14):
            if r:
                print("coin =", r.decode())
                key = hashlib.sha256(r).digest()[:16]
                print(unpad(AES.new(key, AES.MODE_ECB).decrypt(ENC), 16).decode())
                pool.terminate()
                break
```
结果
```python
存疑 26 位，2^26 个候选
coin = 0011011000101011011000011000100001010001
flag{7h15_DDH_15_vu1n_r16h7?}
```
最后flag
`flag{7h15_DDH_15_vu1n_r16h7?}`
