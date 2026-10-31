---
title: Pangbai泰拉记二周目(1)（QCTF2026 re）
date: 2026-10-21
category: Re
tags:
  - CTF
  - Writeup
  - re
draft: false
summary: "怎么藏了五段flag啊！！"
collection: QCTF2026
---
# Pangbai泰拉记二周目(1)

## 第一节：题目

怎么藏了五段flag啊！！
## 第二节：过程

一个apk文件，首先先unzip一下看看里面有什么东西
```bash
$ unzip -l pbterrai.apk | grep -v META-INF
Archive:  pbterrai.apk
  Length      Date    Time    Name
---------  ---------- -----   ----
     2608  1981-01-01 01:01   assets/dexopt/baseline.prof
      262  1981-01-01 01:01   assets/dexopt/baseline.profm
  9488008  1981-01-01 01:01   classes.dex
   574040  1981-01-01 01:01   classes2.dex
     4216  1981-01-01 01:01   lib/arm64-v8a/libpangbaiterrai.so
     2952  1981-01-01 01:01   lib/armeabi-v7a/libpangbaiterrai.so
     3260  1981-01-01 01:01   lib/x86/libpangbaiterrai.so
     4232  1981-01-01 01:01   lib/x86_64/libpangbaiterrai.so
     2285  1981-01-01 01:01   assets/flag3_fragment.png
     1738  1981-01-01 01:01   DebugProbesKt.bin
     1006  1981-01-01 01:01   kotlin/annotation/annotation.kotlin_builtins
     8308  1981-01-01 01:01   kotlin/collections/collections.kotlin_builtins
     2674  1981-01-01 01:01   kotlin/concurrent/atomics/atomics.kotlin_builtins
      137  1981-01-01 01:01   kotlin/coroutines/coroutines.kotlin_builtins
      590  1981-01-01 01:01   kotlin/internal/internal.kotlin_builtins
    29399  1981-01-01 01:01   kotlin/kotlin.kotlin_builtins
     4184  1981-01-01 01:01   kotlin/ranges/ranges.kotlin_builtins
     4827  1981-01-01 01:01   kotlin/reflect/reflect.kotlin_builtins
     6320  1981-01-01 01:01   AndroidManifest.xml
      744  1981-01-01 01:01   res/-1.xml
      352  1981-01-01 01:01   res/-5.xml
     7778  1981-01-01 01:01   res/-6.webp
.....
```
有很多很多，但是我们知道本质上的apk其实就是一个zip，里面有很多一定会有的部分比如manifest.xml resources.arsc这些，而我们分析这些压缩包发现，其实有很多都是res INF 这样不太重要（flag基本不会在这里）的部分，所以我们过滤一下，筛选出比较重要的五部分，看看
```bash
$ unzip -l pbterrai.apk | grep -iE "manifest|\.arsc|flag|\.so$|\.dex$"
  9488008  1981-01-01 01:01   classes.dex
   574040  1981-01-01 01:01   classes2.dex
     4216  1981-01-01 01:01   lib/arm64-v8a/libpangbaiterrai.so
     2952  1981-01-01 01:01   lib/armeabi-v7a/libpangbaiterrai.so
     3260  1981-01-01 01:01   lib/x86/libpangbaiterrai.so
     4232  1981-01-01 01:01   lib/x86_64/libpangbaiterrai.so
     2285  1981-01-01 01:01   assets/flag3_fragment.png
     6320  1981-01-01 01:01   AndroidManifest.xml
  1273176  1981-01-01 01:01   resources.arsc

```
看到了flag3，事实上其他flag也确实藏在了这里的部分文件里，而且和最终结果相呼应的是，结果的flag也由五部分组成，也就是说每个文件格式下都有一个flag
我们把这些文件单独提取出来放到单独的文件里
```bash
unzip -o -q pbterrai.apk -d x AndroidManifest.xml resources.arsc classes.dex assets/flag3_fragment.png lib/x86_64/libpangbaiterrai.so
```
然后我cd 到x目录下去扫里面是否有flag
```bash
strings -e l AndroidManifest.xml                   | grep flag
strings resources.arsc                             | grep flag
strings assets/flag3_fragment.png                  | grep flag
strings classes.dex                                | grep flag
strings lib/x86_64/libpangbaiterrai.so             | grep flag
```
注意manifest因为是二进制字段所以直接搜flag不行，一定要-e l才可以
可以发现输出了很多flag，然后从里面找一找flag的字段，最终整理成下面的
```text
flag1: flag{W31
flag2: c0m3_70_
flag3: 7h3_W0r
flag4: 1d_0f_@
flag5: ndr01d}
```
所以结果就是
`flag{W31c0m3_70_7h3_W0r1d_0f_@ndr01d}`