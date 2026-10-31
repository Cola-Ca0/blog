---
title: 这里是……后室?（QCTF2026 pwn）
date: 2026-10-21
category: Pwn
tags:
  - CTF
  - Writeup
  - pwn
draft: false
summary: "Let escape the backroom"
collection: QCTF2026
---
# 这里是……后室?

## 第一节：题目

Let escape the backroom
## 第二节：过程

pwn文件，标准流程先打开IDA看看main函数
```c
int __fastcall main(int argc, const char **argv, const char **envp)
{
  char v4; // [rsp+Bh] [rbp-A5h] BYREF
  unsigned int seed; // [rsp+Ch] [rbp-A4h]
  struct sigaction act; // [rsp+10h] [rbp-A0h] BYREF
  unsigned __int64 v7; // [rsp+A8h] [rbp-8h]

  v7 = __readfsqword(0x28u);
  act.sa_handler = (__sighandler_t)segv_handler;
  sigemptyset(set: &act.sa_mask);
  act.sa_flags = 0;
  sigaction(sig: 11, &act, oact: nullptr);
  sigaction(sig: 7, &act, oact: nullptr);
  setvbuf(stream: stdout, buf: nullptr, modes: 2, n: 0);
  seed = time(timer: nullptr);
  srand(seed);
  wel();
  __isoc99_scanf(a1: &unk_259D, a2: &v4);
  if ( v4 == 89 || v4 == 121 )
    level0();
  if ( v4 == 78 || v4 == 110 )
  {
    puts(s: &byte_25A0);
    puts(s: &byte_25C8);
  }
  else
  {
    puts(s: &byte_25E1);
  }
  return 0;
}
```
这里setvbuf分析出输出无缓冲，而sigaction又防止了程序不会直接崩溃。
关键在于
```c
  if ( v4 == 89 || v4 == 121 )
    level0();
```
这里题目引用了level0函数，我们进去看看
```c
void __noreturn level0()
{
  int v0; // [rsp+Ch] [rbp-44h] BYREF
  int v1; // [rsp+10h] [rbp-40h]
  int v2; // [rsp+14h] [rbp-3Ch]
  int v3; // [rsp+18h] [rbp-38h]
  unsigned int random; // [rsp+1Ch] [rbp-34h]
  _QWORD v5[6]; // [rsp+20h] [rbp-30h] BYREF

  v5[5] = __readfsqword(0x28u);
  v0 = 0;
  memset(v5, 0, 40);
  v3 = 0;
  puts(s: &byte_2338);
  puts(s: &byte_2370);
  puts(s: &byte_23D8);
  puts(s: &byte_2430);
  while ( 1 )
  {
    puts(s: "====================");
    printf(format: &byte_2440, (unsigned int)v0);
    if ( v0 > 19 )
      real();
    puts(s: "====================");
    puts(s: &byte_2458);
    v1 = 0;
    __isoc99_scanf();
    --v1;
    puts(s: &byte_2480);
    v2 = 0;
    __isoc99_scanf();
    if ( v2 > 0 )
    {
      if ( v2 > 20 )
      {
        puts(s: &byte_20FC);
        usleep(useconds: 0x7A120u);
        puts(s: &byte_20FC);
        usleep(useconds: 0x7A120u);
        puts(s: &byte_20FC);
        usleep(useconds: 0x7A120u);
        puts(s: &byte_24C8);
        exit(status: 0);
      }
      *((_DWORD *)v5 + v1) += v2;
      puts(s: &byte_24FF);
      if ( v3 > 16 )
        die();
      random = get_random(a1: 1, a2: 3);
      success(a1: random, a2: &v0);
      v3 += random;
    }
    else
    {
      puts(s: &byte_24A0);
    }
  }
}
```
我们惊讶地注意到了
```c
      *((_DWORD *)v5 + v1) += v2;
```
其中的v1直接scanf来，没有做检查，虽然v2——驻留时间要在(0,20]之间，但是房间号v1没有检查，这也为我们后面越界埋下伏笔

之前的函数我们分析发现了一个很重要的问题，就是
```c
if ( v0 > 19 ) real(); 
if ( v3 > 16 ) die();  
```
而get_random是(1,3)说明了我们永远没办法通过正常手段通关，v3 = v1，到了16就自动死了，就算你最好情况当时v3=16，加了random的3 也是19，而v1要20才能过。

但是虽然后面还要分析各种函数，都比不上自己玩两把
可以看到下面我对于房间号尝试了越界，然后也试过时间越界，但是显然在程序里，时间是不能越界的
我们观察一下，房间号如果越界都会去哪里呢？
![](/assets/posts/qctf2026/qctf2026-pwn-backrooms-1.png)
发现了吗？如果v1越界了，往负的地址走，而数组底 rbp-0x30和计数rbp0x44中间差了20个字节也就是5个int，当房间号是-4的时候，v1的下标就是-5，最后算出来的落点是rbp-0x44，就会落在杏仁水计数上，我们就可以一口气加v2瓶杏仁水.。
而v2决定了最后的杏仁水加了多少，那就写20。

```bash
你醒啦？
恭喜你，你卡入了后室（？
不过，要离开这里也十分简单～
你只需要收集二十瓶杏仁水并交给管理者，就可以回到现实世界。
你，准备好了吗？(Y/N)
y
现在你的眼前有10扇门，编号为1~10。
你每次可以任选一扇门，调查任意时间，每次调查都有概率获得杏仁水。
但是，随着累计驻留时间的增加，实体也会越来越有可能发现你。
助你好运～
====================
杏仁水数量：0
====================
请选择要调查的房间号(1~10)：
11
请决定驻留时间(1~20)：
20
（你推开了门……）
……
……
……
你平安完成了调查。
杏仁水+1。
====================
杏仁水数量：1
====================
请选择要调查的房间号(1~10)：
-1
请决定驻留时间(1~20)：
-4
临阵脱逃么？有点意思。
====================
杏仁水数量：1
====================
请选择要调查的房间号(1~10)：
11
请决定驻留时间(1~20)：
-4
临阵脱逃么？有点意思。
====================
杏仁水数量：1
====================
请选择要调查的房间号(1~10)：
11
请决定驻留时间(1~20)：
1
（你推开了门……）
……
……
……
你平安完成了调查。
杏仁水+3。
====================
杏仁水数量：4
====================
请选择要调查的房间号(1~10)：
-4
请决定驻留时间(1~20)：
20
（你推开了门……）
……
……
……
你平安完成了调查。
杏仁水+1。
====================
杏仁水数量：25
====================   
看来你成功收集了二十瓶杏仁水呢。
那么按照约定，我将送你回到现实。
……
（你感到一阵眩晕，随后，眼前出现了熟悉的城市）
等一下，
喝杯水再走吧，放心，只是普通的水（笑）
请选择一杯水(1~9):
10
你喝了一口水。
……
……
……
这白开水怎么没味儿啊？
QCTF{We1c0m3_7o_r3@1_w0r1d}
```
最终flag
`QCTF{We1c0m3_7o_r3@1_w0r1d}`