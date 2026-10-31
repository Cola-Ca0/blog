---
title: ezIcon（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "一个网页里只有一个Icon图片，先下下来呗"
collection: QCTF2026
---
# ezIcon

## 第一节：题目

一个网页里只有一个Icon图片，先下下来呗

## 第二节：过程

Icon图片有个特性就是它虽然看起来是一个图片，但是里面包含了像素不同的图片

![](/assets/posts/qctf2026/qctf2026-misc-ezicon-1.png)

那接下来我们分别提取出6张图片，然后在StegSolve里打开那个256 * 256的图片
RGB均选择0位最小，找到了flag
![](/assets/posts/qctf2026/qctf2026-misc-ezicon-2.png)

最终flag，因为是动态环境所以当时的flag不是这个
`QCTF{35783ba3-7161-4ecb-aa3d-594c9e4a667f}`