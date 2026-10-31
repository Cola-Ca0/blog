---
title: Strange_rc4（QCTF2026 re）
date: 2026-10-21
category: Re
tags:
  - CTF
  - Writeup
  - re
draft: false
summary: "flag是加密之后的一串31字节的原始二进制密文，c1是它的加密程序，用法是 C1.exe <输入文件> <输出文件>。"
collection: QCTF2026
---
# Strange_rc4

## 第一节：题目

flag是加密之后的一串31字节的原始二进制密文，c1是它的加密程序，用法是 `C1.exe <输入文件> <输出文件>`。
也就是说这道题要我们求 C1 的逆。标题叫 Strange_rc4，那肯定不是原来的rc4的流程。

## 第二节：过程

我们要求C1的逆，先把C1放进IDA里看看怎么个事，可以看到比较乱一时半会看不到main，我们shift+F12，Usage: cmain.exe input output
![](/assets/posts/qctf2026/qctf2026-re-strange-rc4-1.png)
之后x进入交叉引用，只有一处，之后F5构建伪代码，然后rename那些看不懂的部分，就可以得到
```c
int __fastcall main(int argc, char **argv)
{
    FILE *fin;
    FILE *fout;
    int size;
    void *buf;
    uint8_t state[296];

    if (argc != 3) {
        printf("Usage: cmain.exe <input> <output>\n");
        return 1;
    }

    fin = NULL;
    if (fopen_s(&fin, argv[1], "rb") != 0) {          // argv+8 = argv[1]
        printf("[!] cannot open input\n");
        return 1;
    }

    fseek(fin, 0, SEEK_END);
    size = ftell(fin);                                 // 先量长度
    fseek(fin, 0, SEEK_SET);
    buf = malloc(size);
    if (buf == NULL)
        return 1;

    fread(buf, 1, size, fin);
    fclose(fin);

    memset(state, 0, 296);
    rc4_init(state, rc4_key, 16, buf, size);           // 密钥 16 字节
    rc4_ksa(state);
    rc4_prga(state);                                   // 原地加密 buf

    fout = NULL;
    if (fopen_s(&fout, argv[2], "wb") != 0) {          // argv+16 = argv[2]
        printf("[!] cannot open output\n");
        return 1;
    }
    fwrite(buf, 1, size, fout);
    fclose(fout);
    free(buf);
    printf("[+] encrypted %ld bytes -> %s\n", size, argv[2]);
    return 0;
}

```
发现了吗？size不变说明了明文密文长度相同，rc4_prga(state)直接fwrite(buf,...)，没有把结果返回buf，就是原地加密了

不过事实上这道题不用这么复杂，都说了是Strange_rc4了，那我们造几个输入喂给C1，看看它到底怎么变的
先造两个只差一个字节的输入，各跑一次，看看他们的区别
testA：31个0x00
```text
a078244ef1bb5512ae5987d88ee781c453f63cf4ed89f091ec4a345f1f1d46
```
testB：31个0x00，只把第11个字节（偏移 10）从00改成01
```text
a078244ef1bb5512ae5988d88ee781c453f63cf4ed89f091ec4a345f1f1d46
```
发现了吗？只有第11个字节不一样，说明输出第i个字节只由输入第i个字节决定，31个位置互不干扰

既然互不干扰，那就盯住一个位置，看看它到底做了个什么变化。我们造8 个1字节的文件，内容分别是0到7，经过C1之后的变化：
```text
0x00 -> 0xa0    0x04 -> 0x9c
0x01 -> 0x9f    0x05 -> 0x9b
0x02 -> 0x9e    0x06 -> 0x9a
0x03 -> 0x9d    0x07 -> 0x99
```
发现了加密的规律，是每个输出挨着减1——低位被翻转了，这说明是异或；整体又比输入高了0x9f。写出来就是

```text
c = (p ^ k) + 1
```

k是那个位置的密钥流字节，这里k = 0x9f。
如果我们令p=0，那么c=k+1。也就是说，把文件全填0跑一次C1，输出减1就是整条密钥流
那解出flag就轻而易举了

```text
key: 9f77234df0ba5411ad5886d78de680c352f53bf3ec88ef90eb49335e1e1c45
```

反过来解就一行：p=(c-1)^k
脚本：
```python
import os, subprocess, tempfile

C1 = r"C1.exe"
enc = open("flag.enc", "rb").read()

tmp = tempfile.mkdtemp()
f_in = os.path.join(tmp, "zero.bin")
f_out = os.path.join(tmp, "zero.enc")
open(f_in, "wb").write(bytes(len(enc)))                 # 全零，长度和密文一致
subprocess.run([C1, f_in, f_out], check=True)

k = bytes(x - 1 for x in open(f_out, "rb").read())      # 全零输入 → 减 1 就是密钥流
print(bytes(((c - 1) & 0xFF) ^ k[i] for i, c in enumerate(enc)).decode())
```

最终flag
```text
flag{Y0u_cr4ck_7h3_57r4ng3_RC4}
```
所以免去了很多不必要的读代码的时间，拿到这种题目还是先拿一些测试数据看看有没有规律比较好
