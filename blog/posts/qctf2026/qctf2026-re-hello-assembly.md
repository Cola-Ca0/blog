---
title: Hello_Assembly（QCTF2026 re）
date: 2026-10-21
category: Re
tags:
  - CTF
  - Writeup
  - re
draft: false
summary: "我们把这段汇编等价翻译成 C"
collection: QCTF2026
---
# Hello_Assembly

## 第一节：题目

汇编语言题

## 第二节：过程

我们把这段汇编等价翻译成 C

```c
#include <stdio.h>
#include <string.h>
#define KEY_LEN  5
#define FLAG_LEN 32
static const char key[KEY_LEN] = "Hello";
static const unsigned char cipher[FLAG_LEN] = {
    0x1C, 0x29, 0x3B, 0x2D, 0x17, 0x22, 0x03, 0x60,
    0x12, 0x03, 0x28, 0x03, 0x36, 0x3B, 0x03, 0x1A,
    0x27, 0x22, 0x22, 0x0D, 0x28, 0x0A, 0x60, 0x18,
    0x33, 0x22, 0x0D, 0x21, 0x60, 0x0E, 0x6C, 0x1B
};
int main(void)
{
    char input[65];
    int i;
    int ok = 1;
    printf("Input flag: ");
    fflush(stdout);
    if (fgets(input, sizeof(input), stdin) == NULL)
        ok = 0;
    /* 去掉行尾换行 */
    if (ok) {
        size_t n = strlen(input);
        if (n > 0 && input[n - 1] == '\n')
            input[n - 1] = '\0';
    }
    /* 长度检查（对应 cmp byte ptr [input_buf+1], FLAG_LEN） */
    if (ok && (int)strlen(input) != FLAG_LEN)
        ok = 0;
    /* 逐字节校验：xor key[bx] 后 +3，与 cipher 比较 */
    if (ok) {
        for (i = 0; i < FLAG_LEN; i++) {
            unsigned char al = (unsigned char)input[i];
            al ^= (unsigned char)key[i % KEY_LEN];
            al += 3;
            if (al != cipher[i]) {
                ok = 0;
                break;
            }
        }
    }
    if (ok) {
        printf("\nCorrect!\n");
        return 0;
    } else {
        printf("\nWrong!\n");
        return 1;
    }
}
```
key = Hello
反推明文就是：(cipher[i] - 3) ^ key[i % KEY_LEN]
其中
```text
cipher = [0x1C,0x29,0x3B,0x2D,0x17,0x22,0x03,0x60,
          0x12,0x03,0x28,0x03,0x36,0x3B,0x03,0x1A,
          0x27,0x22,0x22,0x0D,0x28,0x0A,0x60,0x18,
          0x33,0x22,0x0D,0x21,0x60,0x0E,0x6C,0x1B]
```
转一下hex然后直接解就行


```python
cipher = bytes.fromhex("1c293b2d1722036012032803363b031a2722220d280a601833220d21600e6c1b")
key = b"Hello"
print(bytes((c - 3) ^ key[i % 5] for i, c in enumerate(cipher)).decode())

```

最终flag
`QCTF{We1come_To_Assemb1y_Wor1d!}`

