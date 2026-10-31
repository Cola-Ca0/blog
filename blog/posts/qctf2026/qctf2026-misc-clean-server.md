---
title: 据说服务器很干净（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "流量分析题，上wireshark"
collection: QCTF2026
---
# 据说服务器很干净

## 第一节：题目

流量分析题，上wireshark
## 第二节：过程

找到这一条download的
![](/assets/posts/qctf2026/qctf2026-misc-clean-server-1.png)

然后follow stream，可以得到

```http
POST /download HTTP/1.1
Host: 192.168.1.200
User-Agent: Mozilla/5.0
Content-Type: application/x-www-form-urlencoded
Content-Length: 27
Connection: close

action=download&file=backup
HTTP/1.1 200 OK
Content-Type: text/html; charset=utf-8
Connection: close

====BACKUP ZIP BASE64====
UEsDBBQAAQAIAABgAV2yV62PrgEAABACAAAJAAAAcHJveHkudHh0gk9FBaLWUqgZ6gCoOl7kXGnSYlt+QfzDxNzSKA4q+7E5wRHi5DPZnBoFCqhwvHFevYfputxtYR5yOCKuZhraQa9kQ+0uSEjkpG9qFm8izcATiHPGHSQZ8V0toyKvRUWsQeZ2/8hVWQABdGhUhuWBNHxTkyIT0esjpoh7etR4L6KTH4w+58reR5HTm/drzxjPN8hjdJ/zGcD/uitewzENqUpSnv9IGvuoGIHdCNKksz9IQjCPyMSmTUVtQEbI/9i/Fa2WyGFr6mUdRwsxdB2DAja5nvzRiwMSMqlxRjRUgpoFM5W1kxS8aVFCUTZCeilFvF2BUE8OggKHzw7K7nqpHmjBQbzBvzegVfg+zk/c5JgjEyLJDDSOp4AOywLQUf0ejyxbL6ybWAw7yoVurnmBvmcxwInCRoY/RrQ2CnOG8es/oqFu0K7SgWqgmbTgSjM0o4tWHZnpQbhtO54Asfwu1fmrweO0Q7sxxO6mHwP+X+tWwSCyum3dhWm5Luf77bg24VGd/iUNG0ttaIcT3umJ+xNiQY4rp9hysHwWp8TbDrx8ZxD+pVfqoME8sKY7JVBLAQIUABQAAQAIAABgAV2yV62PrgEAABACAAAJAAAAAAAAAAAAAAAAAAAAAABwcm94eS50eHRQSwUGAAAAAAEAAQA3AAAA1QEAAAAA
```
这是个用base64存着的zip文件

```bash
yu@TIAN-XUAN-JI MINGW64 ~/downloads/312
$ python -c "import base64;open('backup.zip','wb').write(base64.b64decode(open('b64.txt').read()))"
```
然后ARCHPR暴力一下密码
0520
解压之后得到
```text
噔噔噔噔——毕业清理记录+中转配置（自用，别让 Freya 发现）

192.168.1.200:8080 —— 之前折腾东西的时候用过，应该还在。
那些“不方便直接出现”的东西，记得先DES-ECB加密一下再丢过去。
老规矩，还是用以前那个密码，password，这么多年懒得换了。
好像有人问过我为什么不用明文……这不是显得专业一点嘛。
服务器交接的时候太忙了，这个zip忘记删了。

应该不会有人闲着没事去翻这个吧？:)

```

接下来处理8080端口/shell.php的那几条流量：请求里的 x= 是base64(DES-ECB 密文)
密钥就是password
依次找到
```text
x=KZKnxWfJKi20CX9Bk6lBGg==
x=Q9PGN3Uq8pIrhHx7ArfKdw==
x=Q9PGN3Uq8pL/g46fn3JV3/+njB6TDmIa
```

回包
```text
1beFU91A2RLR6DWKNP8OSGgc/mTww5+XodOVg3UPQ2vAl/qug1HsaJ5IBCcUhFw89M6R0xFdTks=
6nTKrBFjKFyr21BebcHTR0YqPyTVnY1B0LPwTYzc0Ko=
```

这些内容都DES解密
```python
import base64
from Crypto.Cipher import DES

KEY = b"password"

def dec(s):
    raw = DES.new(KEY, DES.MODE_ECB).decrypt(base64.b64decode(s))
    return raw[:-raw[-1]]

for s in [
    "KZKnxWfJKi20CX9Bk6lBGg==",
    "Q9PGN3Uq8pIrhHx7ArfKdw==",
    "Q9PGN3Uq8pL/g46fn3JV3/+njB6TDmIa",
    "1beFU91A2RLR6DWKNP8OSGgc/mTww5+XodOVg3UPQ2vAl/qug1HsaJ5IBCcUhFw89M6R0xFdTks=",
    "6nTKrBFjKFyr21BebcHTR0YqPyTVnY1B0LPwTYzc0Ko=",
]:
    print(dec(s))

```

```text
b'phpinfo();'
b"system('ls /');"
b"system('cat /flag');"
b'bin\nboot\ndev\netc\nflag\nhome\nlib\nproc\nroot\ntmp\nusr\nvar\n'
b'?CTF{mitm_des_webshell_2026}'
```
最终flag
`?CTF{mitm_des_webshell_2026}`