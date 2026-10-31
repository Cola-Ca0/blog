---
title: 被揉坏的芙芙（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "一张\"被揉坏\"的图，要用CRC修回来"
collection: QCTF2026
---
# 被揉坏的芙芙

## 第一节：题目

一张"被揉坏"的图，要用CRC修回来
## 第二节：过程

PNG 的 IHDR 宽度2560被改成144 且CRC字段变0 了

**还挤出来了一串神秘字符051F503E**
说明解CRC32的钥匙就是这个，051F503E是密钥

解CRC的脚本
```python
import struct, sys, zlib

SRC = sys.argv[1] if len(sys.argv) > 1 else "芙.png"
HINT = 0x051F503E                     # 题面给的真 CRC

d = open(SRC, "rb").read()
H = struct.unpack(">I", d[0x14:0x18])[0]                    # 高度没被动
fields = (d[0x18], d[0x19], d[0x1A], d[0x1B], d[0x1C])     # 位深/颜色/压缩/滤波/隔行

for w in range(1, 10000):
    data = struct.pack(">IIBBBBB", w, H, *fields)
    if zlib.crc32(b"IHDR" + data) & 0xffffffff == HINT:
        print("width =", w)
```
之后
宽度从 00 00 00 90 改成 00 00 0A 00
CRC从  00 00 00 00改成 05 1F 50 3E
即可
![](/assets/posts/qctf2026/qctf2026-misc-crumpled-fufu-1.png)

最终flag
`?CTF{CrC32_15_1mp0r74n7}`
