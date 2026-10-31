---
title: intro（QCTF2026 re）
date: 2026-10-21
category: Re
tags:
  - CTF
  - Writeup
  - re
draft: false
summary: "IDA里反编译main"
collection: QCTF2026
---
# intro

## 第一节：题目

用IDA的来
## 第二节：过程

IDA里反编译main
```c
__int64 __fastcall main(int a1, char **a2, char **a3)
{
  char v3; // r14
  __int64 i; // rdx
  char v5; // di
  bool v6; // zf
  const char *v7; // rdi
  _BYTE v9[88]; // [rsp+0h] [rbp-58h] BYREF

  memset(v9, 0, 65);
  puts(s: "Input your flag: ");
  v3 = 0;
  read(fd: 0, buf: v9, nbytes: 0x41u);
  for ( i = 0; i != 65; ++i )
  {
    v5 = aCtf2026[i & 7] ^ v9[i];
    v6 = v5 == byte_2010[i];
    v9[i] = v5;
    v3 |= !v6;
  }
  v7 = "Correct";
  if ( (v3 & 1) != 0 )
    v7 = "Wrong";
  puts(s: v7);
  return 0;
}
```
分析伪代码
输入 65 字节

![](/assets/posts/qctf2026/qctf2026-re-intro-1.png)
IDA里shift+F12搜CTF 点B?ctf2026到.rodata 发现aCtf2026 是密钥 ?ctf2026
每个字节先异或 key[i % 8]，其中 key 是 "?ctf2026" 就在这上面
异或结果与 0x2010 起 65 字节的目标串比较
如果全对了那就回correct
那很简单了我们只要把65个字节直接反解了就是flag了
脚本和intro放在同一目录运行即可

```python
data = open(r'intro', 'rb').read()
tgt = data[0x2010:0x2010 + 65]
key = data[0x2051:0x2051 + 8]
flag = bytes(t ^ key[i % 8] for i, t in enumerate(tgt))
print(flag.decode())
```

最终flag
`flag{51mp13_x0r_cr4ck3d_61371f68aaaee5251e8b3e6cf877fe92b32e6286}`
