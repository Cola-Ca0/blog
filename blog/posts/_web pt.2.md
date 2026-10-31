---
title: 有趣的命令执行
date: 2026-09-11
category: CTF
tags:
  - CTF
  - Writeup
  - web
summary: 一句话摘要，会显示在文章卡片和搜索结果里（必填，不然卡片空荡荡）。
draft: true
---

# 有趣的命令执行

> 紧接着之前的《关于信息搜集》，我们来进一步的进行讲解。
> 此外有关爆破的内容因为较为简单先不做讲解

# PHP的代码解读

一般来说，当你拿到了php的源码，如果在面对命令执行的语句中，就也可能有拿到shell的手段，我们接下来以ctfshow里的例题为来学习吧

## 字符过滤型代码执行
### pt.1.1

```php
<?php  
  
error_reporting(0);  
if(isset($_GET['c'])){    $c = $_GET['c'];  
    if(!preg_match("/flag/i", $c)){  
        eval($c);  
    }  
      
}else{    highlight_file(__FILE__);  
}
```

因为刚刚开始解出php可能对于一些函数命令有些生疏，所以这边可以逐句解释一下

首先
```php
error_reporting(0);
```

是在屏蔽了所有的错误提示，防止报错而顺带把服务器信息也暴露了

```php
if(isset($_GET['c'])){    $c = $_GET['c'];  
```

检查GET请求中是否传入了参数 c ，如果传入了，把 c 赋值给 $c
GET传参，也就是 `$_GET` 是这道题的其中一个知识点，这让我们知道了这道题是用GET方法来传参的，因此后面的传参可以直接使用 `/?c=...` 来实现

```php
if(!preg_match("/flag/i", $c)){  
        eval($c);  
    } 
```

很关键的一处代码，这是个正则表达式，意思就是，如果 $c 不包含（这个“不”字源自于if后面括号里的感叹号）flag，则执行下面的代码。
而这里 `i` 的意思则是不区分大小写，因此Flag FLag这样的形式都不行
关键的来了，下面的执行的代码竟然是 eval()

`eval()`函数在php里是很危险的函数，相当于把$c里的字符当作命令来执行

而最后一行
```php
else{    highlight_file(__FILE__);  }
```

就是说如果没有传 c 参数，则高亮显示当前文件，而当前文件则是php源码，那么整道题逻辑就看懂了。

我们要做的是拿flag，也就是调用system来cat flag
一般来说cat flag的指令是
```php
system(cat flag);
```
很遗憾的是，但是遗憾的是，这里有两个问题：
1. 一般来说cat这个命令太常见了所以就导致了基本上任何一个搭建平台的人都会把cat给屏掉。
2. 这道题本身就不允许直接输入flag来查询

这里我们需要补充的小知识即
>tac 命令是相当于 cat 的倒过来的拼写，执行的效果也是反着的——cat是从第一行读到最后一行，tac是从最后一行读到第一行。而关键就在于tac相较于cat一般被用作后门代码，是可以使用的

那么问题一解决了
第二个问题我们有三个解法
1. 我们可以通过构造通配符`?`来绕过检测
2. 我们可以通过编码后解码来绕过
3. 我们可以通过php拼接字符串操作来绕过

`值得注意的是，如果以URL的编码来上传，经过浏览器解码之后会直接变成flag从而不能绕过检测`
因此最后我们可以用例如这样的形式来打穿
`https://xxx.ctf.show/?c=system("tac fla?.php")`
或者把里面的内容换成`"tac fla"."g.php"`也可以

>不过这种题目打穿它的方式太多了，这里也就不多说了
### pt.1.2

来看它的加强，其他不变，唯独在判定上变成了
```php
if(!preg_match("/flag|system|php/i", $c))
```
这道题禁用了上道题我们采用的system和php，所以我们需要另辟蹊径

这里是需要学习的地方
>PHP支持用反引号``执行系统命令，等价于shell_exec

因此我们可以使用 
```php
`tac f*`;
```

但是实际上你执行了之后页面并没有显示内容
因为我们还缺少了一个命令就是 echo 
>echo的作用就是把执行的结果显示在页面上

也就是说，你需要在外层再加一个echo
就变成了
```php
?c=echo`tac f*`;
```
这样就可以过了

### pt.1.3

加强pro
```php
if(!preg_match("/flag|system|php|cat|sort|shell|\.| |\'/i", $c))
```
虽然感觉这道题的严格判定看上去并不影响，但是我们注意到了空格也被ban了，因此我们就不能打空格了...吗？
空格在URL的显示是%20，我们只需要找一个可以代替空格，URL和空格不一样的即可
在Linux命令行中，命令与参数之间、参数与参数之间，无论有多少个空格（或 Tab），Linux 的 Shell 都会把它们压缩成一个分隔符来解析。
也就是说，不用空格，用Tab也可以达到同样的效果
>Tab在URL上的格式是%09，和空格不一样，因此在这道题是可以绕过的

就变成了
```php
?c=echo`tac%09f*`;
```
于是就可以过了

那有人要问了要是tab和空格以及所有空白字符（换行%0a，回车%0d）全都不行呢？
1. 可以用\${IFS}变量，在linux中，\$IFS是默认的系统分隔符，系统解析的时候会把它自动替换成分隔符
2. 利用< >的重定向符

不过这些都属于下下策了。

### pt.1.4

666还有第四关
```php
if(!preg_match("/flag|system|php|cat|sort|shell|\.| |\'|\`|echo|\;|\(/i", $c))
```
这下坏了啥都不给用了

我们再了解新的函数
>include是PHP的一个导入文件的关键字，可以把目标文件的字节读进来然后塞到当前位置当代码/文本处理

比如
```php
# a.php
<?php $x = 1; ?>
hello

# b.php
include 'a.php';
# 等价于在 b.php 这行现场写下：
# <?php $x = 1; ?>
# hello
```

但是include在真正的网站中如果不能直接include一个php文件，就是要有文件路径或流
`include $path  #path (php://filter、data://等)`
而文件里的 `<?php ... ?>` 的代码则会被执行
如果遇到了非 `<?php ... ?>` 的内容则会直接当 HTML 原样输出
当然失败了会警告之后继续跑，成功了会返回1，所以 print include 会返回 1

也就相当于我们可以
```php
?c=include"flag.php"
```
>当然include的规则是：后面的路径可以不用跟include直接空格空开，也满足了这道题的筛选条件

当然这样子直接放flag和php是不行的会被过滤
而include没有通配符这种概念...flag\*这样的也不行
不过有个办法就是，因为网站只对c进行过滤，如果我们把里面的flag拉到外面来，也就是说，我们单独再设置一个GET量，这样的话就可以绕过了
** 这是"参数分离 / 拆分过滤"绕过  **
因此我们要引入第二个知识点了

>\$\_GET[1] 来设置一个新的GET请求，而里面的1则是和c一样是新的参数名

表达式可以如下
```php
?c=include$_GET[1]?>&1=...
```

稍微需要注意两点的是
1. **?>** 是用来替代 `;` 的，因为 `;` 也被ban了，而这里的 `?>` 其实是和 `;` 有点区别的，前者是php语句的结束，打了这个就相当于关闭了PHP的标签，而后者则是一句话的语句结束符，在 `?>` 之后，剩下的内容会被当作HTML语言解析。但是如果这句话作为最后一条语句收尾的话， `;` 和 `?>` 的作用就相等了
2. 就算 `?c=include$_GET[1]?>&1=flag.php` 也不行，虽然语句可以执行成功，但是由于flag.php会被当作php语言执行，所以页面不会回显，办法就是绕过php解析器

>绕过php解析器的办法就是把里面的内容给编码（比如base64）之后，让解析器读不出php语言，然后这样就可以输出了

```php
php://filter              # PHP 内置的流包装器：读写数据的中转站
# 相当于php过滤
convert.base64-encode     # 过滤器：数据流过时被 base64 编码（convert 是分类，base64-encode 是过滤器名）
resource=flag.php         # 固定参数：指定真正读哪个文件/流
```

连起来就是
```php
include"php://filter/convert.base64-encode/resource=flag.php"
```

而和之前的合成一下就是最终的payload
```php
?c=include$_GET[1]?>&1=php://filter/convert.base64-encode/resource=flag.php
```

回显结果base64解一下就行了
>小知识点，标准格式应该有个\[read/write]的格式但是不写默认是read所以可以省略，完整payload如下
```php
php://filter/read=convert.base64-encode/resource=flag.php
```


甚至后面一题的
```php
if(!preg_match("/flag|system|php|cat|sort|shell|\.| |\'|\`|echo|\;|\(|\"/i", $c))
```
和
```php
if(!preg_match("/flag|system|php|cat|sort|shell|\.| |\'|\`|echo|\;|\(|\:|\"/i", $c)
```
还有
```php
if(!preg_match("/flag|system|php|cat|sort|shell|\.| |\'|\`|echo|\;|\(|\:|\"|\<|\=/i", $c))
```
都过了，这个payload可太强了

### pt.1.5

稍微加强了下
```php
if(!preg_match("/flag|system|php|cat|sort|shell|\.| |\'|\`|echo|\;|\(|\:|\"|\<|\=|\/|[0-9]/i", $c))
```
不让你用数字了说是

那就把数字改成字母就行了
```php
?c=include$_GET[a]?>&a=php://filter/convert.base64-encode/resource=flag.php
```

注意GET里的a理论上是要用 `'a'` 引起来的（不引起来就不会把这个a当作后面参数a，它只会作为一个常量a来用），因为PHP8环境下不引起来会被当作常量，但是因为这道题的环境是PHP7（恰好兼容）不是PHP8导致恰好可以用纯的 `a` ，而引号题目又不给用，我觉得可能是出题者故意为之...


### pt.2.1

大体和上面一样，也是过滤词，只是情景有些不同
```php
<?php
//flag in flag.php  
error_reporting(0);  
if(isset($_GET['c'])){    $c = $_GET['c'];  
    if(!preg_match("/flag/i", $c)){  
        include($c);  
        echo $flag;  
      
    }  
          
}else{    highlight_file(__FILE__);  
}
```
这里的 c 因为是在include下面的，所以它不再是代码而是路径了
这个时候你会疑惑一下下，include明明是一个路径，怎么执行命令了呢？

回忆一下 `include` 的功能，include 做的是：**把该路径里的内容读出来，如果是php文件，里面 的php代码就会被执行。而如果是纯文本则原样输出**

那这道题flag最后怎么出来呢，代码里面写了
```php
include($c); // 包含 $c 指向的文件
echo $flag;  // 输出变量 $flag
```

而flag.php里面的内容大概就是
```php
<?php $flag = "ctfshow{xxx}"; ?>
```

所以其实我们只需要把路径放到flag.php下面，然后它就会自动返回flag了

**那么我们要怎么做**
这道题`?c=fla?.php`并不行，因为不能有通配符啊喂
所以我们讲一个新知识

`allow_url_include`
>allow_url_include 是PHP的一个安全配置项，控制着include、require这样的语句能不能直接使用URL比如 http:// 或者 data:// 作为包含目标

它决定了你的include(\$c)里的 `$c` 能不能是 远程地址或者是数据流 而不止是简单的一个文件地址，如果开启了，就可以用 `data://` 或 `php://input` 这类伪协议，把一段PHP代码“伪装”成文件让include去执行
要去查看也很简单，我们可以先测data://协议，它通常需要`allow_url_include` 和 `allow_url_fopen` 都开启
```php
?c=data://text/plain,<?php phpinfo(); ?>
```
![[_web pt.2-1.png]]
而最后的结果也确实是这样的

这样的话我们就可以使用 URL 了。但是问题又随之而来：
正常的`?c=data://://text/plain,<?php system('cat fla?.php');?>` 也不可以，这是因为这串里有 `<` `>` `?` `空格` `'` `;` `(` `)`。**query string 里这些字符是"不安全"的**。`>` `<` 虽然合法但是很多客户端和服务器会转义或拒绝。`空格`在URL 里面必须 `%20` 否则非法。而后面的那四个字符虽然合法但是基本上只要是开了WAF就会拦。

那怎么办呢？一般来说遇到这样的情况，我们的标准操作都是把它编码了之后再传，纯数字+字母就不会出现这样的情况。

我们来看data的结构
```text
data:[<mediatype>][;base64],<data>
```

也就是说如果要base64，我们就可以这么写
```text
data://text/plain;base64,PD9waHAg...
│      │         │       │
│      │         │       └── 真正的内容（base64 编码后的数据）
│      │         └────────── 声明"后面的数据是 base64 编码的"，注意后面是分号
│      └──────────────────── MIME 类型：text/plain
└─────────────────────────── 协议名 data
```

其中的MIME类型在这里只是浏览器和解析器来判断它的内容类型，不过在php的include来说，基本不关心MIME类型，但是至少得是一个合法的，text/plain 就是一种。至于前面data://这个双杠其实加不加都可以，php都认，标准写法是data: ，而php里写的是 data://

此外，编码方式有且只有base64可以选，这是由 data:// 协议遵循的 RFC2397 标准决定的（感兴趣可以去看看[RFC 2397: The "data" URL scheme | RFC Editor](https://www.rfc-editor.org/info/rfc2397/)）要么base64编码，要么原文