---
title: 艾克赛尔（QCTF2026 re）
date: 2026-10-21
category: Re
tags:
  - CTF
  - Writeup
  - re
draft: false
summary: "走迷宫的EXE，flag就是最后走到终点需要的方式"
collection: QCTF2026
---
# 艾克赛尔

## 第一节：题目

走迷宫的EXE，flag就是最后走到终点需要的方式

## 第二节：过程


IDA打开之后，尝试反汇编（`0x401604` 起）

```c
int main(void)
{
    int start_r, start_c, goal_r, goal_c;

    /* 扫 20×20：7=起点，8=终点（0x401606 双重循环） */
    for (int r = 0; r < 20; r++)
        for (int c = 0; c < 20; c++) {
            if (maze[r][c] == 7) { start_r = r; start_c = c; }
            if (maze[r][c] == 8) { goal_r  = r; goal_c  = c; }
        }

    puts("=== MAZE ===");
    puts("Enter your path in flag{}:");
    char path[128];
    fgets(path, sizeof path, stdin);
    path[strcspn(path, "\r\n")] = 0;             /* 去掉行尾换行 */

    int r = start_r, c = start_c, steps = 0;
    for (int i = 0; path[i]; i++) {
        int dr = 0, dc = 0;
        switch (path[i]) {                       /* 0x401788 起逐字符判方向 */
            case 'W': dr = -1; break;            /* 上 */
            case 'S': dr = +1; break;            /* 下 */
            case 'A': dc = -1; break;            /* 左 */
            case 'D': dc = +1; break;            /* 右 */
            default:  puts("Invalid move!"); return 1;
        }

        r += dr; c += dc;
        if (r < 0 || r > 19 || c < 0 || c > 19) { puts("Wrong path!"); return 1; }
        int v = maze[r][c];
        if (v == 0) { puts("Wrong path!"); return 1; }         /* 撞墙 */
        if (++steps > 55) { puts("Out of moves!"); return 1; } /* 0x37 = 55 */

        if (2 < v && v <= 5) {                   /* 滑行格 3~5，不连锁 */
            for (int k = 0; k < v; k++) {
                r += dr; c += dc;
                if (r < 0 || r > 19 || c < 0 || c > 19) { puts("Wrong path!"); return 1; }
                if (maze[r][c] == 0) { puts("Wrong path!"); return 1; }
            }
        }
    }

    if (r == goal_r && c == goal_c) {
        puts("\nSuccess!");
        printf("Steps: %d\n\n", steps);
        puts("Congratulations!");
        printf("Flag: flag{%s}\n", path);        /* 路径字母就是 flag 内容 */
    } else {
        puts("Wrong path!");
        return 1;
    }
}
```
分析上面的代码，我们发现了起点 (1,1)、终点 (17,10)，0 是墙；3~5 会往那个方向挪对应格数，撞墙和滑行中撞墙都不行，其实这道题可以用BFS来做，因为要求最高只能走55次，所以做的时候可以断定这道题用BFS做出来最短的只有一个——唯一解

迷宫长这个样子
```text
0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0
0 7 1 1 1 1 3 1 1 1 1 1 5 1 1 1 1 1 1 0
0 1 1 1 1 1 1 1 0 0 1 1 1 1 1 1 1 3 1 0
0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 1 0 0
0 1 1 1 1 1 1 1 1 1 1 1 1 1 1 1 1 1 1 0
0 1 1 1 1 1 4 1 1 1 1 1 1 3 1 1 1 1 1 0
0 0 1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0
0 1 1 1 1 3 1 1 1 1 1 1 1 4 1 1 1 1 1 0
0 1 1 0 0 1 1 1 1 1 1 1 1 1 1 1 1 3 1 0
0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 1 0 0
0 1 1 1 1 1 4 1 1 1 1 1 1 3 1 1 1 1 1 0
0 1 1 1 1 1 1 1 1 0 0 1 1 1 1 1 0 1 1 0
0 0 1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0
0 1 1 1 1 1 3 1 1 1 1 1 5 1 1 1 1 1 1 0
0 1 1 1 0 0 1 1 1 1 1 1 1 1 1 1 1 3 1 0
0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 1 0 0
0 1 1 1 1 0 1 1 1 0 1 1 1 0 1 1 1 1 1 0
0 1 1 1 1 1 1 1 1 1 8 1 1 1 1 1 1 1 1 0
0 1 1 0 1 1 1 0 1 1 1 0 1 1 0 1 1 1 0 0
0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0
起点7 终点8，用BFS来做即可
```


于是最终脚本（把脚本和siuumaze.exe放在同目录下）
```python
import struct
from collections import deque

data = open('siuumaze.exe', 'rb').read()
maze = struct.unpack_from('<400i', data, 0x2820)   # 0x404020 换成文件偏移就是 0x2820

def step(r, c, dr, dc):        # 走一格；踩到 3~5 再同方向滑 v 格，滑行不连锁
    r, c = r + dr, c + dc
    if not (0 <= r < 20 and 0 <= c < 20) or maze[r * 20 + c] == 0:
        return None
    v = maze[r * 20 + c]
    if 2 < v <= 5:
        for _ in range(v):
            r, c = r + dr, c + dc
            if not (0 <= r < 20 and 0 <= c < 20) or maze[r * 20 + c] == 0:
                return None
    return r, c

start, goal = (1, 1), (17, 10)
prev = {start: None}
q = deque([start])
while q:
    r, c = q.popleft()
    if (r, c) == goal:
        break
    for ch, (dr, dc) in {'W': (-1, 0), 'S': (1, 0), 'A': (0, -1), 'D': (0, 1)}.items():
        nxt = step(r, c, dr, dc)
        if nxt and nxt not in prev:
            prev[nxt] = ((r, c), ch)
            q.append(nxt)

path = ''
cur = goal
while prev[cur]:
    cur, ch = prev[cur]
    path = ch + path
print(path, len(path))
print('flag{%s}' % path)

```

最后 flag：

`flag{DDDDDDDDSAAAAAAAASSDDDDDDDDSWAAAAAAAASSSDDDDDDDSAAAAAAA}`