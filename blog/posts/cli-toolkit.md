---
title: "构建高效工作流：我的命令行工具箱"
date: 2026-07-22
category: Productivity
tags: [Tooling, CLI, Productivity]
summary: "从终端美化到自动化脚本，分享一套经过深潜测试的工作流配置。"
draft: false
---

## 终端是开发者的驾驶舱

一个好的终端配置不仅仅是好看，更重要的是提升操作效率。我的选择是 Windows Terminal + Git Bash，配合 Laragon 的 shell 集成。

每天打开终端的瞬间，看到清晰的信息展示和舒适的配色，就像潜水员检查完装备准备下潜：*一切都井然有序*。

## 必备工具清单

- **Laragon** - PHP 开发环境，开箱即用，比 XAMPP 轻量得多
- **VS Code** - 主力编辑器，Continue + DeepSeek API 做 AI 辅助编程
- **Obsidian** - 知识库管理，所有学习笔记的归宿，双向链接让知识形成网络
- **Burp Suite** - Web 安全测试，安全方向的必修课

## 自动化脚本

> 写脚本省下的时间远远超过写脚本花的时间。把重复的事情交给机器，把思考留给自己。

下面是一个快速启动开发环境的批处理脚本：

```batch
@echo off
start "" "C:\laragon\laragon.exe"
start "" "C:\Users\cola\AppData\Local\Programs\Microsoft VS Code\Code.exe"
start "" "C:\Users\cola\AppData\Local\Obsidian\Obsidian.exe"
echo Dev environment ready. Dive in.
```

省去了每天早上逐个打开软件的重复操作，一行命令全部启动。
