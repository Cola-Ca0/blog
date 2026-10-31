---
title: ezFilter（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "需要在1024个文本里面找出真flag..."
collection: QCTF2026
---
# ezFilter

## 第一节：题目
需要在1024个文本里面找出真flag...

首页的帮助说明：

- 文件目录里有 1024 个文本文件，编号 0 到 1023。
- 每个文件形如 QCTF{}，但只有一个是真 flag。
- GET /probe?lo=&hi= 询问闭区间 [ lo, hi ]，回答三种状态之一：hot、warm、cold。
- GET /entry?index= 取回某个编号的文本内容。
- 额度耗尽时返回 401，附带一道 challenge，解出来后把答案放进 X-EZ-Auth 请求头重发。
probe 的判定公式是：

```
length = hi - lo + 1
mid    = lo + length // 2
half   = (length - 1) // 4
middle = [mid - half, mid + half]
```

flag 在 middle 里就是 hot，落在区间内但不在 middle 里就是 warm，不在区间里就是 cold。
## 第二节：过程

实际上我们不用看middle，只用看它是不是cold即可，非cold那flag一定在区间内
1024 个编号最多 10 次就能收敛到一个点。运用二分即可（后面脚本有）

例子
```
probe [0,511] -> cold
probe [512,767] -> cold
probe [768,895] -> cold
probe [896,959] -> hot
probe [896,927] -> hot
probe [896,911] -> cold
probe [912,919] -> hot
probe [912,915] -> cold
probe [916,917] -> warm
probe [916,916] -> hot
flag index = 916
```

666还有第二关，额度不是固定的，请求几次之后 probe 或 entry 都可能返回 401，但是响应体给我们带了一个关卡

```json
{
  "algorithm": "md5",
  "separator": "-",
  "pick": 5,
  "word_count": 16,
  "words": ["...", "..."],
  "target": "12e3b42f845c531540f68252733fd19f",
  "rule": "Join exactly 5 distinct words from words with - (any order, each word at most once). The md5 hex digest of that string, lower case, must equal target."
}
```

从 16 个单词里挑恰好 5 个互不相同的，用分隔符连起来，任意顺序，摘要等于 target
也就是暴力枚举....大概有52万，在计算机面前不是个事，把答案放进 X-EZ-Auth 头，重发同一个请求即可。

当然最后结束之后一定要保留原session！不然会403的（别问我怎么知道的）

```python
# 用法：python qctf2026_ez_filter.py http://<实例地址>
import sys, hashlib
from itertools import permutations
import requests

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://填实例地址").rstrip("/")
S = requests.Session()          # 保证Session相同


def solve(ch):
    """16 个词里挑 5 个排列，拼起来摘要等于 target 的那个就是答案。"""
    ch = ch.get("challenge", ch)
    h = getattr(hashlib, ch["algorithm"])
    for p in permutations(dict.fromkeys(ch["words"]), ch["pick"]):
        s = ch["separator"].join(p)
        if h(s.encode()).hexdigest() == ch["target"]:
            print("[challenge]", s)
            return s


def get(path, **params):
    r = S.get(BASE + path, params=params, timeout=60)
    if r.status_code == 401:                     # 额度没了解题重发即可
        r = S.get(BASE + path, params=params, timeout=60,
                  headers={"X-EZ-Auth": solve(r.json())})
    r.raise_for_status()
    return r


lo, hi = 0, 1023
while lo < hi:
    mid = (lo + hi) // 2
    cold = "cold" in get("/probe", lo=lo, hi=mid).text
    print(f"probe [{lo},{mid}] -> {'cold' if cold else 'hot/warm'}")
    lo, hi = (mid + 1, hi) if cold else (lo, mid)

print("index =", lo)
print(get("/entry", index=lo).text)
```

最后 entry 返回：

```
QCTF{a40f54d8-5786-4930-8850-f89133773efa}
```

最后 flag：`QCTF{a40f54d8-5786-4930-8850-f89133773efa}`
