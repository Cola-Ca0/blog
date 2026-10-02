---
title: 关于一些信息搜集
date: 2026-09-18
category: CTF
tags:
  - CTF
  - Writeup
  - web
summary: 这里是一些典型的CTFweb入门的WP
draft: false
---

# 关于一些信息搜集

> 在ctfshow里面有20道信息搜集题目，我觉得有必要挑一些经典的题目来写write up，以防我以后忘记了，同时也是为之后的新手赛做准备吧

## 第一节：拿到一个信息搜集题目要做什么

首先肯定是看网页上面有什么了，当然，一般来说flag绝对不会出现在网页主页面的，那我们要怎么去初步探测呢？这里给了一些初步的方向：
**1**    看源码注释
```html
<h3>web1:where is flag?</h3>
<!-- ctfshow{1c353d76-45d1-457a-86a2-62756db5abab} -->
</body>
```

**1.1** 如果遇到不让看源码的怎么办？  ——在URL前面加view-source:
			view-source:https://c04c22...........

**2** 你可以抓包看看发送包和返回包是什么


## 第二节：之后呢？


**1.robots.txt**

robots本身是君子协议，写着网页的防爬清单，一般比如做大厂渗透测试的时候可能会告诉你哪些是核心资产不能碰。但是对于现在这种渗透测试来说，这无疑是此地无银三百两
`(https://32bd.....ow/robots.txt)` 
就可以得到
`User-agent: *`
`Disallow: /flagishere.txt`
答案就显而易见了


**2.phps源码泄露**

先说好，为什么不是php而是phps
因为本质上，php是一个执行文件，显示在了网页上，所以你还是没办法看到注释
而phps（一般比如说(https://d5a5.....ge.ctf.show/index.phps)）点击之后它会把php源码下载到本地，从而完成了php的源码泄露。
`phps = PHP Source,是 PHP 早年(Apache 配置 x-httpd-php-source)提供的「源码分享」特性——本意是让开发者展示代码，结果变成泄露利器：开发者把 index.php 复制成 index.phps 挂网上分享/备份，忘了删，于是访问 index.phps 直接看到源码——包括硬编码在里面的密码、flag、业务逻辑。`

**如何找到phps？** 先侦查看网站上后缀为php的网页，然后加个s看看能不能下载（可能也会直接显示在网站上）
备份泄露家族：

| **后缀**                  | **来源**       |
| ----------------------- | ------------ |
| .phps                   | php的源码展示特性   |
| .php.bak/.php~/.php.old | 编辑器/手动备份     |
| .php.swp/.php.swo       | vim崩溃留下的交换文件 |
| index.php.txt           | 手动的改名分享      |


**3.bro测试结束之后忘记删源码残留了**

运维部署的时候是把整个web根目录打包传上去解压，压缩包顺手会用目录名，而全世界的web根目录名都习惯叫www
因此(https://6faa........e.ctf.show/www.zip)就有概率解压出根目录文件来

**备份文件名字典**（名字 × 后缀交叉爆破）：

| 类别 | 常见名 |
| --- | --- |
| 目录名系 | www / web / wwwroot / htdocs / html / webroot / public / app |
| 功能语义系 | backup / bak / site / website / source / src / code / dist / release / deploy / 1 / new / old / test / final |
| 域名系 | example.com.zip（按目标域名换，精确制导） |
| 带日期系 | backup-20250101 / site-2024-01-01 |
| 后缀系 | .zip / .rar / .tar.gz / .7z / .tgz |
| 数据库连带 | db.sql / database.sql / dump.sql（也是残留大户） |

先线索后穷举：目标域名、页面标题、报错信息里先捡文件名，字典是最后的穷举手段。

解压之后你有可能发现类似flagxxx.txt的文件。先别半场开香槟，里面的flag大概是错的，这是因为上传的时候是源文件，而后端是可以更改网页上的内容的，但是你得到的那些内容还只是上传之前的根目录——**大概率是不一样的**，所以你要去看里面的php文件去找一找也没有什么其它目录，或者干脆直接把txt这个文件直接放在url的后面，因为此时加载出来的肯定是修改之后的！你大概也会发现网页上的内容会和你根目录下的文件不一样。


**4.git源码泄露**

开发者在本机 `git init` 开发，部署时把**整个项目目录**原样复制上服务器——`.git/` 这个隐藏目录跟着上了生产环境。它是什么？**完整版本库**：每一版源码、每一次提交历史，全在里面。
`另外，.svn/也可以得到和.git/一样的效果，svn是版本控制的亲弟弟，也要看`
所以解决问题只要(https://425e7c.........tf.show/.git/(.svn/))

>`.git/` 泄露给的不只是**当前源码**，是**全部历史**。`git log -p` 能翻出每一次提交的改动——开发过程中删掉的密码、改掉的调试代码、写死又撤回的 flag,尸体全埋在历史提交里。真实世界挖 `.git` 泄露，一半的收获来自**翻旧账**，不是看当前版本。

一般我们用curl的时候，一定要注意在powershell里面curl和curl.exe是不一样的
用powershell的时候我们要打
`curl.exe -i http://xxxxx`


**5.vim swap泄露**

1. **vim 为什么留 swp**:编辑时把缓冲区实时落盘，专防断电/死机/断线——恢复未保存的工作。这是**功能，不是漏洞**；漏洞是它留在了 web 可直达的目录里
2. **为什么会泄露**：正常退出(包括 `:q!`)vim 会删 swp;**异常终止才残留**——「死机」题面就是告诉你残留发生了
3. **为什么服务器乖乖给文件**：你已学的地基原理再次生效——**服务器按扩展名决定处理方式**。`.swp` 不在 PHP 处理名单里，nginx 把它当**静态文件**原样吐出；它既不执行也不解析，等于纯下载
4. **为什么内容可读**：swp 是 vim 私有的块式二进制格式，但**缓冲区文本是明文**躺在数据块里——`strings` 拿它没办法是谣言，拿它有的是办法
5. **为什么比原文件更肥**：swp 里除了当前内容，还有**未保存的修改**(可能比线上版本新)和元数据(编辑者用户名、主机名、时间戳)——真实取证里能顺藤摸瓜定位到人

我们攻击的话就可以直接(https://927b77.....nge.ctf.show/index.php.swp)

**提取**(两条路，便宜优先)：

```bash
strings index.php.swp | grep -i flag    # 快刀:明文直接翻
vim -r index.php.swp                     # 正道:恢复完整编辑缓冲区
```

**纵深**：index 没有就换文件名(`login.php.swp`、`config.php.swp`……文件名来源还是那套：robots/报错/目录爆破)；swp 没有，试 `.swo`、`.swn`—— vim 崩过不止一次的话它们在排队。


**6.Cookie 存放敏感数据**

你直接去F12然后看cookie editor然后看看有没有放敏感数据。这种建站的人和向全宇宙广播地球坐标的人有什么区别。

或者你也可以用命令行
```bash
curl -s -H "Cookie:admin=1""URL"
```


**7. 域名信息隐藏**

```bash
nslookup -type=TXT flag.ctfshow.com
```

有时候你拿到一个域名之后，它的DNS不仅仅是从域名到IP，而是类似于图片一样，会夹带一些其它信息，具体指的是

| **类型**   | **内容**                             |
| -------- | ---------------------------------- |
| A / AAAA | IPv4 / IPv6 地址                     |
| CNAME    | 别名                                 |
| MX       | 邮件服务器                              |
| NS       | 这个域的管辖者                            |
| TXT      | 任意文本（本用于SPF、域名所有权验证，但是flag也可能放在这里） |
隐藏原理在于 子域名 × 记录类型 = 一个巨大的命名空间。`flag.ctfshow.com` 的 TXT 记录是一个**公开但没人会去翻的抽屉**——DNS 天生就是分层公告板，出题人往抽屉里塞一条文本，信息就「藏」进了公共服务里，不需要任何自己的服务器。

当然像刚刚那个命令也可以等价换成别的e.g.

```bash
nslookup -type=MX flag.ctfshow.com
```

**查询链路**：

```
你的电脑 → 递归解析器(运营商 / 223.5.5.5) → 根服务器 → .com TLD → ctfshow 的权威 NS(DNSPod) → TXT 记录
```
`TLD = Top-Level Domain --> 顶级域名(.com .net .org)  一些比如(.cn .jp是国家顶级域名)`

如何通过NS去查找目标？
第 1 步：查目标域的 NS（谁管它）

```
nslookup -type=NS ctfshow.com
```

得到 `f1g1ns1.dnspod.net` / `f1g1ns2.dnspod.net`。

第 2 步：把查询发给这台 NS

nslookup 的完整格式是 `nslookup -type=<记录类型> <域名> <DNS服务器>`——**最后一个参数就是你要指哪台服务器**，写主机名或 IP 都行：

```
nslookup -type=TXT flag.ctfshow.com f1g1ns1.dnspod.net
nslookup -type=TXT flag.ctfshow.com 163.177.5.35
```


**8.信息检索能力**

在挖SRC的时候也很重要，比如你有时候拿到了后台登陆页面全迟迟苦于拿不到密码而进入不了，除了最暴力的爆破以外，我们还可以通过浏览该主网页上的一些信息（电话，邮箱等）或者上网搜索这些有关该网站的信息来把这些字段当作密码来尝试，效果可能会出乎意料的好。

>有这么一道题就是通过邮箱找到了admin的所在地，在后台登录忘记密码之后点击问我们admin住在哪里？直接答上去就能修改密码成功。


>还有一题是让你玩小鸟然后打到101分给你flag，由于我当时玩的时候并没有带鼠标啊这个玩的也太困难了！！！于是view-source:去翻源码然后找到了游戏内部的机制，打开之后出现了
```js
if(score>100)
{
var result=window.confirm("\u4f60\u8d62\u4e86\uff0c\u53bb\u5e7a\u5e7a\u96f6\u70b9\u76ae\u7231\u5403\u76ae\u770b\u770b");
}
```
Unicode转义之后是：你赢了，去幺幺零点皮爱吃皮看看
于是找到了/110.php 拿到了flag


**9.有些类人会把敏感信息写在技术文档，还不改密码**


你点进这个页面，view-source:一下，然后你ctrl+f去搜敏感信息，名字集如下：

① 备份/打包类（价值最高，一击致命）
```
www.zip / www.rar / web.zip / web.rar / backup.zip / bak.zip
site.zip / source.zip / code.zip / 123.zip / admin.zip
```

后缀变体：原文件名 + `.bak` `.old` `.swp` `.save` `~`（你上周刚在 Downloads 见过 `index.php.swp`——vim 崩溃残留） 目录类：`/.git/config`（git 泄露）、`/.svn/entries`、`/.DS_Store`

② 文档/说明类（本题同族）
```
readme / README.md / readme.txt / readme.html
doc / docs / document / documents / documentation
manual / guide / help / help.pdf / note / todo / TODO.txt
changelog / CHANGELOG / update.txt / install.txt / license.txt
中文站：使用手册.pdf / 使用说明.doc / 安装说明
```

③ 配置/敏感类
```
robots.txt / sitemap.xml / security.txt / crossdomain.xml
.env / config.php.bak / web.config.bak / application.yml
phpinfo.php / test.php / info.php / 1.php
dump.sql / db.sql / database.sql / backup.sql
```

④ 后台/入口类
```
admin / admin.html / admin.php / login / login.php
manage / manager / system / console / dashboard / houtai
```

如果运气好，你大概可以找到有些写前端还是后端的抱着拿了工资就走的心态，完全不顾代码审计员的发际线，直接把这种东西写上去了。这波可惜、这波可惜。


**10.源码信息泄露**

有时候看到源码了之后，有概率能看到程序员忘记去掉的敏感路径——有一道 editor 题就是这样：首页源码里一张图片的路径 `editor/upload/banner-app.png`，直接把 `editor/` 这个目录卖了。源码里搜这类线索，重点盯 `editor` `upload` `admin` `api` 这种词，它们大多藏在 img、script 的 src 里。

看到目录只是拿到地图，接下来分两步。

**第一步：这个目录是什么组件**

第三方组件的共性：官网下个 zip，解压丢进网站根目录就能用，**demo 页、示例接口、默认配置一个没删**。你扫到的目录名就是它官网的目录名。常客：

| **路径**                                  | **组件**     | **默认毛病**                                          |
| --------------------------------------- | ---------- | ------------------------------------------------- |
| `/editor/` `/kindeditor/`               | KindEditor | `php/file_manager_json.php` 目录遍历                  |
| `/ueditor/` `/umeditor/`                | 百度 UEditor | `controller.php?action=config` 配置直读；.NET 版有任意文件上传 |
| `/fckeditor/` `/ckeditor/`              | 老牌编辑器      | connectors 上传老洞                                   |
| `/ewebeditor/`                          | 老国产编辑器     | 后台默认 `admin/admin`                                |
| `/phpmyadmin/` `/pma/` `/adminer.php`   | 数据库面板      | 弱口令 + 版本洞                                         |
| `/xheditor/` `/tinymce/` `/wangEditor/` | 其他编辑器      | demo 页 / 上传接口裸奔                                   |

**怎么知道一个组件的包里长什么样？下载同版本对照。** 官网 zip 里有哪些文件是公开信息——把目标目录扫出来的东西和官网包一对，哪个接口能用一目了然。那题里能摸到「图片空间」去遍历目录，本质就是 KindEditor 包里自带的那个 file_manager。

**第二步：看得见 ≠ 够得着**

文件空间能翻遍整台服务器，但**只有落在网站根目录（webroot）里的文件才能拼成 URL 访问**。这题里 nginx 那层的 `flag.sh` 就是例子——拼出 URL 也打不开，够不着的诱饵；真正能打的 `nothinghere/fl000g.txt` 在 web 根里，**和网站同根**才成立。

判断方法：看文件浏览器显示的**物理路径**，把 webroot 前缀（一般是 `/var/www/html`）切掉，剩下的拼到域名后面就是 URL。

**同族的还有框架/中间件自带端点**（SRC 里比 CTF 还常见）：

```
/actuator/env  /actuator/heapdump    # Spring Boot——heapdump 能捞数据库口令
/swagger-ui.html  /v2/api-docs       # 接口文档裸奔 = 全接口地图
/druid/index.html                    # Druid 监控，国产站一抓一大把
/nacos/  /jenkins/  /h2-console      # 默认口令组合
```


**11.SQL备份隐私泄露**

有时候，你去在网站的背后加 /backup.sql 会下载到它的数据库备份文件，里面可能就会有敏感信息

但实际挖的时候文件名不止 `backup.sql` 一个，按「谁产生的」分三层记，比背清单管用：

**① 文件名系**（命名心理学和 zip 同款）

```
backup.sql  db.sql  database.sql  dump.sql  data.sql
mysql.sql  sql.sql  1.sql  a.sql  test.sql
www.sql  web.sql  htdocs.sql             ← 目录名系
wp.sql  wordpress.sql                    ← CMS 系
example.com.sql                          ← 域名系（精确制导）
backup-20250918.sql  db-2025-09-18.sql   ← 日期系
```

**② 扩展名系**（同一个库的不同壳——重点）

| 后缀 | 谁产生的 | 备注 |
| --- | --- | --- |
| `.sql` | mysqldump | 原生输出 |
| `.sql.gz` | `mysqldump \| gzip` | 真实运维最爱，下完先 `gzip -d` |
| `.sql.zip/.rar/.7z` | 打包备份 | 常见于整站备份里 |
| `.sql.bak/.sql.old/.sql~` | 编辑器/手残残留 | phps 家族亲戚 |
| `.dump` | pg_dump | PostgreSQL |
| `.db` `.sqlite` `.sqlite3` | SQLite | 无导出概念，文件即库——下下来直接用 SQL 工具打开 |
| `.bak` | SQL Server | 同名坑：和源码 `.bak` 共用后缀 |
| `.dmp` | Oracle | 少见但认得 |

**③ 路径系**：也可能不叫 backup，而躺在 `/backup/`、`/db/`、`/sql/`、`/data/` 目录下——**目录 × 文件名 × 后缀**三维交叉才是完整字典。

拿到 `.sql` 先 Ctrl+F 搜 `ctfshow`、`flag`、`password`、`INSERT`——八成在这。


**12.key在源码泄露**

 登录页 `view-source` → JS 里 `key` / `iv` **硬编码**，且 `checkForm()` 提交前强制 AES 加密；
 源码注释直接泄露后端校验：`username==='admin' && pazzword==='a599ac85...'`

问题在你现在拿到了加密的密钥，但是通过前端到后端需要加密一次，你总不能直接把加密的密钥再加一次密，解决办法很简单。

**解决办法：别用它的前端。** 绕过 `checkForm()` 的强制加密，让原文直接进后端就行——上一问的思考题在这里收网：前端加密只防"用页面的人"。

（捋一下概念：`key` / `iv` 是前端 AES 的参数，只在"解密反推"那条路用得上；真正要发给后端的是注释里那个固定密码值 `a599ac85...`。）

**三个姿势（推荐序）**

| 方法 | 怎么做 | 什么时候用 |
| --- | --- | --- |
| 直接构造请求 | `curl.exe` / Console fetch / Burp 改包，原文直达后端 | 首选，最快 |
| 禁用 JS 提交 | F12 → 设置 → Debugger → 禁用 JavaScript → 刷新后表单原生提交 | 懒得开终端；最直观体会"绕开前端" |
| 解密反推 | 拿 key + iv 解密抓包密文，还原明文 | 研究向：证明"钥匙是公开的" |

**PowerShell 实战一条**（ps 里 `curl` 是 `Invoke-WebRequest` 的别名，必须写 `curl.exe`）：

```powershell
curl.exe -s -X POST -d "username=admin&pazzword=a599ac85a73384ee3219fa684296eaa62667238d608efa81837030bd1ce1bf04" "http://你的靶机地址/"
```

细节坑：参数名严格照抄源码（`pazzword` 是两个 z），拼错一个字母后端收不到参数，返回的还是登录页。

**原理一句话**：key、iv、算法、比对目标全在客户端 = 攻击者全知——**看得到钥匙的锁不是锁**；前端加密只防"不看源码的人"，安全边界必须落在服务端。真实站点登录接口前端 AES 加密很常见，应对套路同一套：看 JS 拿 key、自造请求重放。

**13.mdb 数据库文件泄露**

这是信息搜集系列的最后一题，也是「备份家族」的第三种形态——源码包（zip）→ 数据库导出（sql）→ **数据库本体（mdb）**。

**一句话原理**：Access 时代**数据库就是一个文件**——ASP 靠 ODBC 读本地 `.mdb`，这个文件放在 web 目录下没设防，就跟图片一样被下载走。所以"脱裤"不一定要注入，**直接把库文件下下来就是**。

**题目链条（三关）**

| 关 | 遇到的事 | 正确姿势 |
| --- | --- | --- |
| ① 找文件 | 题面点名 mdb | 两维交叉：目录（`/db/` `/data/` `/database/`）× 文件名（`db` `data` `database`）+ `.mdb` |
| ② 403 | 访问 `/db/` 被 Forbidden | **403 的是目录列表，不是文件**（`Options -Indexes`）——直接请求文件全路径，照下 |
| ③ 搜不到 | 打开后搜 `ctfshow` 没结果 | mdb 文本是 **UTF-16LE** 存的——换编码：010 hex 搜 `66 00 6C 00 61 00 67 00`，或 `strings -el db.mdb` |

**考古老站的顺手知识**：
- **防下载土办法**（防守方视角）：文件名塞 `#`（`name#.mdb`，访问时写 `%23`）；改名为 `.asp`（IIS 当程序解析直接报错，反而下不了）；目录权限设死。
- **Access 注入前置知识**（后面 SQL 系列会用到）：没有 `information_schema`，表名靠 `exists` 猜、偏移注入。
- **查看器**：EasyAccess / MDB Viewer Plus，或 010 硬翻。

**SRC 视角**：`.mdb` 泄露在国产老站（政府/企业旧站）至今常见，黑话"**脱裤**"就是从这类事件来的——一个文件被拿走 = 全库用户表，没有任何"最小权限"可言。

**14.seed**

在web里面，seed可以生成无限的随机数，同样的seed生成的随机数是一样的，也就是说抓住了seed就可以拿到"一模一样的随机"

当然在我们这里有一道题目，可以通过拿到的第一个随机数来反推种子，至于如何通过随机数来反推种子则需要我们后面密码中的PRNG来说了。

```php
mt_srand(372619038);                          // 常量
mt_srand(time());                             // 时间戳（秒）
mt_srand(microtime(true) * 1000000);          // 微秒
mt_srand(hexdec(substr(md5($flag), 0, 8)));   // 从数据派生
mt_srand(crc32($username));                   // 从字符串派生
mt_srand($_GET['seed']);                      // 用户可控 ← 最惨的一种
```

> [!tip] 别被写法骗了
> `hexdec(substr(md5($flag), 0, 8))` 看着复杂，展开后 ==还是一个整数==。
> 剥掉派生过程，所有 seed 最终都是「一个数」。

```python
import requests
import subprocess
import urllib3
urllib3.disable_warnings()

A = 1335612619
seeds = [1120848884,3908320010]
URL = "https://a6dea7e2-cf5c-4.........."
for s in seeds:
    PHP = r"Z:\laragon\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"

    tok = subprocess.check_output(
    [PHP, "-r", f"mt_srand({s}); mt_rand(); echo mt_rand()+mt_rand();"],
    text=True,).strip()
    r = requests.get(URL, params={"r": A}, cookies={"token": str(tok)}, verify=False)
    print(s, r.status_code, repr(r.text))
```


## 第三节：复盘 / 教训

写的web的WP，主要是怕自己忘记了，记一个笔记应该会在以后用得上，20道题目做了我一周快了感觉就是drain,burn out,exhausted

>主要就是记得拿到这种网页一定要先view-source啊

不说了我的War Thunder J16研发完了周末休息了。

感谢：萦梦Sora、Deepseek、GLM、Claude code

<blockquote style="text-align:right">Cola_CaO<br>2026.9.18</blockquote>
