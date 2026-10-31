---
title: 中国人能飞（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "一道音谱和压缩包的复合题"
collection: QCTF2026
---
# 中国人能飞

## 第一节：题目

一道音谱和压缩包的复合题

## 第二节：过程

对于压缩包，这一看就是伪加密把它改成如下就行

![](/assets/posts/qctf2026/qctf2026-misc-chinese-can-fly-1.png)

解压之后得到第一段flag
`PART1:flag{7h3_Ch1n3$3_`

第二段是音谱

```bash
ffmpeg -y -i "C:/Users/yu/Downloads/中国人能飞/中国人能飞/中国人能飞吗.mp3" -lavfi showspectrumpic=s=4096x2048 -frames:v 1 "C:/Users/yu/Downloads/中国人能飞/spec.png"
```
![](/assets/posts/qctf2026/qctf2026-misc-chinese-can-fly-2.png)
又是莫斯密码
解密之后得到
```text
PART2 = r3411y
```

part3用exiftool找到
```bash
yu@TIAN-XUAN-JI MINGW64 ~/downloads
$ exiftool 1.mp3
ExifTool Version Number         : 13.59
File Name                       : 1.mp3
Directory                       : .
File Size                       : 1231 kB
File Modification Date/Time     : 2026:08:08 11:31:07+08:00
File Access Date/Time           : 2026:10:22 10:49:55+08:00
File Creation Date/Time         : 2026:10:22 10:49:54+08:00
File Permissions                : -rw-rw-rw-
File Type                       : MP3
File Type Extension             : mp3
MIME Type                       : audio/mpeg
MPEG Audio Version              : 1
Audio Layer                     : 3
Sample Rate                     : 44100
Channel Mode                    : Joint Stereo
MS Stereo                       : Off
Intensity Stereo                : Off
Copyright Flag                  : False
Original Media                  : True
Emphasis                        : None
VBR Frames                      : 3788
VBR Bytes                       : 1225904
VBR Scale                       : 80
Encoder                         : LAME3.100.?

ID3 Size                        : 4242
Title                           : 涓浗浜鸿兘椋?

Genre                           : Blues
Part Of Set                     : PART3:_c4n_ph1y!}
WM Mood                         : (Binary data 122 bytes, use -b option to extract)
Artist                          : 鎻戒浆SKAI ISYOURGOD/Chalky Wong
Audio Bitrate                   : 99.1 kbps
Duration                        : 0:01:39 (approx)

```

最终flag

`flag{7h3_Ch1n3$3_r3411y_c4n_ph1y!}`
