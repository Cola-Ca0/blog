---
title: meow~（QCTF2026 re）
date: 2026-10-21
category: Re
tags:
  - CTF
  - Writeup
  - re
draft: false
summary: "( o.o )  Meow! Welcome to the Cat Cafe!"
collection: QCTF2026
---
# meow~

## 第一节：题目
```text
  /\_/\
 ( o.o )  Meow! Welcome to the Cat Cafe!
  > ^ <   The cats are watching you...
          Today's brew: 32 cups of TEA, steeped with one key.
Enter the flag:
```
看上去像是32层TEA
## 第二节：过程

先IDA看看怎么个事情，对main进行反编译
```c
int __fastcall main(int argc, const char **argv, const char **envp)
{
  FILE *v3; // rax
  int v4; // eax
  BOOL v5; // eax
  char Buffer[260]; // [rsp+20h] [rbp-60h] BYREF
  int v8; // [rsp+124h] [rbp+A4h]
  size_t v9; // [rsp+128h] [rbp+A8h]

  _main(a1: argc, a2: argv, a3: envp);
  v9 = 0;
  memset(a1: Buffer, Val: 0, Size: 0x100u);
  print_banner();
  if ( (unsigned int)is_debugger_present() != 0 )
  {
    puts_0(Buffer: "Wrong!");
    exit_0(Code: 0);
  }
  v3 = __acrt_iob_func(Ix: 0);
  if ( fgets_0(Buffer, MaxCount: 256, Stream: v3) == nullptr )
  {
    puts_0(Buffer: "Input error.");
    exit_0(Code: 1);
  }
  v9 = strcspn_0(Str: Buffer, Control: "\r\n");
  Buffer[v9] = 0;
  v8 = v9 == 24;
  v4 = strncmp_0(Str1: Buffer, Str2: "flag{", MaxCount: 5u);
  LOBYTE(v8) = (v4 == 0) & v8;
  v8 = (Buffer[23] == 125) & (unsigned __int8)v8;
  v5 = check_flag(a1: (__int64)Buffer);
  v8 &= v5;
  if ( v8 == 0 )
  {
    puts_0(Buffer: "Wrong!");
    exit_0(Code: 0);
  }
  puts_0(Buffer: "Correct!");
  return 0;
}
```
注意到其中
![](/assets/posts/qctf2026/qctf2026-re-meow-1.png)
输入格式是24字节+flag{...}

而checkflag函数里面
![](/assets/posts/qctf2026/qctf2026-re-meow-2.png)
```c
_BOOL8 __fastcall check_flag(__int64 a1)
{
  _BYTE Buf1[32]; // [rsp+20h] [rbp-20h] BYREF

  encrypt_input(a1, a2: Buf1);
  return memcmp_0(Buf1, Buf2: &expected_cipher, Size: 0x18u) == 0;
}
```
校验 = 加密之后再比较密文，所以这道题只能RE

而encrypt_input函数
```c
{
  int *result; // rax
  int v3; // [rsp+28h] [rbp-18h] BYREF
  int v4; // [rsp+2Ch] [rbp-14h] BYREF
  unsigned __int16 *v5; // [rsp+30h] [rbp-10h]
  int i; // [rsp+3Ch] [rbp-4h]

  for ( i = 0; i <= 2; ++i )
  {
    v5 = (unsigned __int16 *)(a1 + 8 * i);
    v4 = (*((unsigned __int8 *)v5 + 2) << 16) | *v5 | (*((unsigned __int8 *)v5 + 3) << 24);
    v3 = (*((unsigned __int8 *)v5 + 6) << 16) | v5[2] | (*((unsigned __int8 *)v5 + 7) << 24);
    tea_encrypt_block(a1: &v4, a2: &v3);
    *(_DWORD *)(8LL * i + a2) = v4;
    result = (int *)(4 * (2 * i + 1LL) + a2);
    *result = v3;
  }
  return result;
}
```
v4、v3 那两行一开始看着有点晕，其实只是 IDA 把小端读 4 个字节这件小事拆开写了`(*((u8*)v5+2)<<16) | *v5 | (*((u8*)v5+3)<<24)`就是`*(int*)v5`，v3 那行就是`*(int*)(v5+4)`。

说明了它把24字节切成了3个64位每 8 字节独立加密、块间不串联——即ECB模式

同理，tea_encrypt_block函数就是tea加密的过程
```c
_DWORD *__fastcall tea_encrypt_block(_DWORD *a1, _DWORD *a2)
{
  _DWORD *result; // rax
  int i; // [rsp+8h] [rbp-8h]
  int v4; // [rsp+Ch] [rbp-4h]

  v4 = 0;
  for ( i = 0; i <= 31; ++i )
  {
    v4 -= 1640531527; // 这就是tea的指纹，这个数在tea里是特殊的
    *a1 += (*a2 + v4) ^ (16 * *a2 + 2003789133) ^ ((*a2 >> 5) + 2003789165);
    result = a2;
    *a2 += (*a1 + v4) ^ (16 * *a1 + 1936943469) ^ ((*a1 >> 5) + 561603947);
  }
  return result;
}
```
1640531527 这个数要换算：2^32 - 1640531527 = 2654435769 = 0x9E3779B9，也就是 2^32/φ，TEA 每轮加的那个固定值。编译器把加一个大数改写成了减它的补数，所以直接在 IDA 里搜9E3779B9是搜不到的，F5 里只会显示1640531527。

而剩下的  ASCII    2003789133 = Meow  2003789165 = meow  1936943469 = miss
561603947 = key!     就是Meowmeowmisskey!    是tea的密钥
密文在140004010用hex看
![](/assets/posts/qctf2026/qctf2026-re-meow-3.png)
```text
05 7c e0 f0 b9 8a df 6d  b9 06 82 9e f8 14 31 bd  ef 4f 71 0b fc 56 7f 17
```
那我们已经知道了加密方式，剩下的就是写脚本解密了。

```python
import struct

M = 0xffffffff                          # 模拟 C 的 uint32 回绕
DELTA = 0x9e3779b9                      # TEA 轮常量 = floor(2^32 / φ)
K = struct.unpack("<4I", b"Meowmeowmisskey!")

def decrypt(v0, v1):
    s = (DELTA * 32) & M                # 从最后一轮的轮常量开始往回减
    for _ in range(32):
        v1 = (v1 - (((v0 << 4) + K[2]) ^ (v0 + s) ^ ((v0 >> 5) + K[3]))) & M
        v0 = (v0 - (((v1 << 4) + K[0]) ^ (v1 + s) ^ ((v1 >> 5) + K[1]))) & M
        s = (s - DELTA) & M
    return v0, v1


CIPHER = bytes.fromhex("057ce0f0b98adf6db906829ef81431bdef4f710bfc567f17")
flag = b""
for i in range(0, len(CIPHER), 8):      # ECB：每 8 字节一组，各自独立
    v0, v1 = decrypt(*struct.unpack_from("<2I", CIPHER, i))
    flag += struct.pack("<2I", v0, v1)
print(flag.decode())
```
最终flag
`flag{Meow_1ovE_y0u_m0st}`