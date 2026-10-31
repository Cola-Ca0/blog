---
title: ezVCS（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "先拉.git重建仓库"
collection: QCTF2026
---
# ezVCS

## 第一节：题目

git版本分叉

## 第二节：过程
先拉.git重建仓库
然后我们只要列出所有的分支然后从中找flag就行了

```bash
# 把 .git 扒回来、重建出仓库之后：
git log --all --oneline          # 全部提交（含各分支）
git stash list                   # ★ stash@{0} 在这儿
git stash show -p stash@{0}      # diff 里就是缺的另一截 flag
```

```bash

yu@TIAN-XUAN-JI MINGW64 ~/Downloads/new_project (1)/new_project (dev)
$ git stash show -p
diff --git a/note.md b/note.md
index 1ef36ed..b56ac87 100644
--- a/note.md
+++ b/note.md
@@ -2,4 +2,4 @@

 - `Git` is a popular **version control system** (***VCS***) trusted by lots of developers.
 - In ?CTF, we also use Git for source code management.
-- You should know that the flag is `?CTF{G1t_m@ke5_a_d
+- You should know that the flag is `?CTF{G1t_m@ke5_a_d3v310p3r_s_D@y!}`

```

最后 flag：

`?CTF{G1t_m@ke5_a_d3v310p3r_s_D@y!}`