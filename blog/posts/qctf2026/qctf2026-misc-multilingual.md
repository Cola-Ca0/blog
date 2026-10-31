---
title: Multilingual?（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "附件是三行\"乱码\"，每行是被用错编码读出来的文本。跟名字一样，要变成我们可以看懂的语言"
collection: QCTF2026
---
# Multilingual?

## 第一节：题目

附件是三行"乱码"，每行是被用错编码读出来的文本。跟名字一样，要变成我们可以看懂的语言

## 第二节：过程

1. 一三行各自试组合：先用 latin-1 / cp1252 / gbk / gb2312 把乱码编回字节，再拿 utf-8 / big5 / gb18030 解出来。比如第三行 £°£ä£±£î£ç£ó£ý 一路下来就是编码链的产物。
2. 而第二行就是全角转半角符号就行了，相当于直接给了我们一部分的答案。
3. 三行还原出的三段拼起来就是 flag。
4. ps.这个第一行的有点阴试了好多解码都没解出来...

```python

# 题目中提取出的三行乱码
texts = [
    "¡H¤Ñ¢â¢Ô¡a¢Ü¢²£@¡Ä¢â¢-¢ú",
    "1 d__0 f__3 n c",
    "£°£ä£±£î£ç£ó£ý"
]

# 候选来源编码：把乱码字符"编回"字节时用的编码（哪个能编得回去就用哪个）
source_encodings = ['latin-1', 'iso-8859-1', 'cp1252', 'gbk', 'gb2312', 'gb18030']

# 候选目标编码：字节再按它解码
target_encodings = ['utf-8', 'gbk', 'gb2312', 'gb18030', 'big5', 'utf-16']

print("开始尝试解码...\n")

for i, text in enumerate(texts):
    print(f"--- 正在处理第 {i+1} 行: {text} ---")
    found = False
    for src in source_encodings:
        for tgt in target_encodings:
            if src == tgt:
                continue        # 编解码同一套 = 白转一圈，跳过
            try:
                # 核心逻辑：先按错误编码转回字节，再按正确编码解码
                decoded = text.encode(src).decode(tgt)
                # 过滤掉解码后还是乱码或者空字符串的情况
                # （'�' = 解码失败的替换符；£¡¤ 那批是乱码字母表字符，结果里还留着 = 没还原干净）
                if decoded.strip() and not any(c in decoded for c in ['�', '£', '¡', '¤']):
                    print(f"[成功] 源编码: {src:<10} -> 目标编码: {tgt:<10} => 结果: {decoded}")
                    found = True
            except (UnicodeEncodeError, UnicodeDecodeError):
                pass
    if not found:
        print("未找到完美的编码组合，可能需要手动分析。")
    print()
```

最后 flag：

`?CTF{N3w_W0r1d_0f_3nc0d1ngs}`