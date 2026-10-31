---
title: Advanced Encryption tandard（QCTF2026 crypto）
date: 2026-10-21
category: Crypto
tags:
  - CTF
  - Writeup
  - crypto
draft: false
summary: "去掉了S，把 SubBytes 整步删了，导致现在 GF(2) 全是线性运算"
collection: QCTF2026
---
# Advanced Encryption tandard

## 第一节：题目

去掉了S，把 SubBytes 整步删了，导致现在 GF(2) 全是线性运算

## 第二节：过程

于是

```
E_k(x) = E_k(0) ⊕ P(x)
```

其中 `E_k(0)` 就是输出最前面那 16 字节，`P` 是第零轮密钥版加密。

既然 `P` 用零轮密钥，那么每一轮的 `AddRoundKey` 都是“异或 0”，什么都不用做，复杂的那些全都不用做。`P` 最后就只剩了：

```
9 次的 (ShiftRows → MixColumns) + 最后一次的 ShiftRows
```



拿到服务器的hex之后直接
`python dec.py 拿到的hex`


```python

import sys

def xtime(v):
    """GF(2^8) 乘 2：左移一位，溢出就异或 0x1B"""
    return ((v << 1) & 0xFF) ^ (0x1B if v & 0x80 else 0)


def shift_rows(s):
    for i in range(4):
        t = [s[j][i] for j in range(4)]
        for j in range(4):
            s[j][i] = t[(i + j) % 4]


def P(x):
    s = [list(x[i * 4:(i + 1) * 4]) for i in range(4)]
    for _ in range(9):
        shift_rows(s)
        for i in range(4):
            t1 = [s[i][j] ^ s[i][(j + 1) % 4] for j in range(4)]
            t2 = [xtime(t1[j]) ^ s[i][(j + 1) % 4] ^ t1[(j + 2) % 4] for j in range(4)]
            s[i] = t2
    shift_rows(s)
    return bytes(s[i][j] for i in range(4) for j in range(4))


def decrypt(h):
    ct = bytes.fromhex("".join(h.split()))
    ct0, body = ct[:16], ct[16:]
    out = b""
    for k in range(0, len(body), 16):
        i = k // 16 + 1                                   # 计数器从 1 开始，16 字节小端
        ks = bytes(a ^ b for a, b in zip(ct0, P(i.to_bytes(16, "little"))))
        out += bytes(a ^ b for a, b in zip(body[k:k + 16], ks))
    return out


if __name__ == "__main__":
    print(decrypt(sys.argv[1]).decode())
```

## 题目源码

**advanced_encryption_tandard.c**

```c
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
const unsigned char rcon[] = {0x00, 0x01, 0x02, 0x04, 0x08, 0x10, 0x20, 0x40, 0x80, 0x1b, 0x36};
char secret_key[16], flag[1000];
unsigned char round_keys[44][4], enc[1024];
void get_secret_key()
{
	FILE *const random = fopen("/dev/random", "rb");
	if (random == NULL)
	{
		fputs("Error when getting secret key: cannot open /dev/random\n", stderr);
		exit(0x10);
	}
	if (fread(secret_key, 1, 16, random) != 16)
	{
		fputs("Error when getting secret key: cannot read enough bytes\n", stderr);
		exit(0x11);
	}
	if (fclose(random))
	{
		fputs("Error when getting secret key: cannot close /dev/random\n", stderr);
		exit(0x12);
	}
	return;
}
size_t get_flag(const char *const path)
{
	FILE *const file = fopen(path, "r");
	if (file == NULL)
	{
		fprintf(stderr, "Error when getting flag: cannot open \"%s\"\n", path);
		exit(0x20);
	}
	if (fgets(flag, sizeof(flag) / sizeof(char), file) - flag == sizeof(flag) / sizeof(char))
	{
		fputs("Error when getting flag: flag too long\n", stderr);
		exit(0x21);
	}
	const size_t len_flag = strcspn(flag, "\r\n");
	if (len_flag == 0)
	{
		fputs("Error when getting flag: no flag is found\n", stderr);
		exit(0x22);
	}
	flag[len_flag] = '\0';
	if (fclose(file))
	{
		fprintf(stderr, "Error when getting flag: cannot close \"%s\"\n", path);
		exit(0x23);
	}
	return len_flag;
}
void key_expansion()
{
	for (unsigned i = 0; i < 4; ++i)
		for (unsigned j = 0; j < 4; ++j)
			round_keys[i][j] = secret_key[i * 4 + j];
	for (unsigned i = 4; i < 44; ++i)
		if (i % 4)
			for (unsigned j = 0; j < 4; ++j)
				round_keys[i][j] = round_keys[i - 4][j] ^ round_keys[i - 1][j];
		else
		{
			for (unsigned j = 0; j < 4; ++j)
				round_keys[i][j] = round_keys[i - 4][j] ^ round_keys[i - 1][(j + 1) % 4];
			round_keys[i][0] ^= rcon[i / 4];
		}
	return;
}
void add_round_key(unsigned char state[][4], const unsigned char round_key[][4])
{
	for (unsigned i = 0; i < 4; ++i)
		for (unsigned j = 0; j < 4; ++j)
			state[i][j] ^= round_key[i][j];
	return;
}
void shift_rows(unsigned char state[][4])
{
	unsigned char temp[4];
	for (unsigned i = 0; i < 4; ++i)
	{
		for (unsigned j = 0; j < 4; ++j)
			temp[j] = state[j][i];
		for (unsigned j = 0; j < 4; ++j)
			state[j][i] = temp[(i + j) % 4];
	}
	return;
}
unsigned char xtimes(const unsigned char val)
{
	return (val << 1) ^ (((signed char)(val) >> 7) & 0x1b);
}
void mix_columns(unsigned char state[][4])
{
	for (unsigned i = 0; i < 4; ++i)
	{
		unsigned char temp1[4], temp2[4];
		for (unsigned j = 0; j < 4; ++j)
			temp1[j] = state[i][j] ^ state[i][(j + 1) % 4];
		for (unsigned j = 0; j < 4; ++j)
			temp2[j] = xtimes(temp1[j]) ^ state[i][(j + 1) % 4] ^ temp1[(j + 2) % 4];
		for (unsigned j = 0; j < 4; ++j)
			state[i][j] = temp2[j];
	}
	return;
}
void aes_encrypt(const char block[16], unsigned char *const result)
{
	unsigned char state[4][4];
	for (unsigned i = 0; i < 16; ++i)
		state[i / 4][i % 4] = block[i];
	add_round_key(state, round_keys);
	for (unsigned round = 1; round <= 9; ++round)
	{
		shift_rows(state);
		mix_columns(state);
		add_round_key(state, round_keys + round * 4);
	}
	shift_rows(state);
	add_round_key(state, round_keys + 40);
	for (unsigned i = 0; i < 16; ++i)
		result[i] = state[i / 4][i % 4];
	return;
}
int main()
{
	const char endian_test[4] = {1, 2, 3, 4};
	if (*(const int *)(endian_test) != 0x04030201)
	{
		fputs("Environment error : this program must run on a little-endian platform\n", stderr);
		exit(0x01);
	}
	const size_t len_flag = get_flag("./flag");
	get_secret_key();
	key_expansion();
	char counter[16] = {};
	unsigned char result[16];
	aes_encrypt(counter, result);
	for (unsigned i = 0; i < 16; ++i)
		enc[i] = result[i];
	unsigned char *p_enc = enc + 16;
	for (size_t i = 0; i < len_flag;)
	{
		++*(__uint128_t *)(counter);
		aes_encrypt(counter, result);
		for (unsigned j = 0; j < 16 && i < len_flag; ++i, ++j)
			*(p_enc++) = flag[i] ^ result[j];
	}
	for (const unsigned char *i = enc; i != p_enc; ++i)
		printf("%02hhx", *i);
	putchar('\n');
	return 0;
}
```



## 关键源码（突破口）

把 **aes_encrypt** 的轮函数抠出来：标准 AES 的 SubBytes 一步都没出现——整个加密因此退化成 GF(2) 上的线性映射。

```c
void aes_encrypt(const char block[16], unsigned char *const result)
{
	unsigned char state[4][4];
	for (unsigned i = 0; i < 16; ++i)
		state[i / 4][i % 4] = block[i];
	add_round_key(state, round_keys);
	for (unsigned round = 1; round <= 9; ++round)
	{
		shift_rows(state);
		mix_columns(state);
		add_round_key(state, round_keys + round * 4);
	}
	shift_rows(state);
	add_round_key(state, round_keys + 40);
	for (unsigned i = 0; i < 16; ++i)
		result[i] = state[i / 4][i % 4];
	return;
}
```


最终flag（复现）
```
Z:\laragon\ctf\code>python dec.py 801e7435dbfa2d549ff2f728444a7f5be4721552a0c91835fac3c41125664d69bc2d5901bf99497fa690c310692e1b6cb72f1556eace4937fe8f
flag{35be129a-2283-4dcd-9b68-fd711ac14dfa}
```

