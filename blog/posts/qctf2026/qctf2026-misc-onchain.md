---
title: 上链，走起！！！（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "Web3啊，审核大大你看到的话顺便看看Web3简介——把信任交给算法 · ColaCaO"
collection: QCTF2026
---
# 上链，走起！！！

## 第一节：题目
Web3啊，审核大大你看到的话顺便看看[Web3简介——把信任交给算法 · Cola_CaO](https://co1acao.com/post/web3-betterlink)
自己写的喔
这道题是合约分析题
合约是 Welcome：

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract Welcome {
    string internal constant PASSWORD = "welcome_to_?ctf2026";

    bool internal _solved;
    uint256 public helloCount;

    event Hello(address indexed player);

    function sayHello(string calldata word) external {
        require(
            keccak256(bytes(word)) == keccak256(bytes(PASSWORD)),
            "Welcome: wrong word"
        );

        _solved = true;
        helloCount += 1;

        emit Hello(msg.sender);
    }

    function solved() external view returns (bool) {
        return _solved;
    }
}
```

目标是把合约里的 solved 变成 true，然后在平台上点验证。

## 第二节：过程

也就是说合约只有一个写函数 sayHello，它把传入的 word 和PASSWORD 做 keccak256 比较。相等就把  _ solved 变成True

运用`cast` 来解决
打开git bash
```bash
export PATH="/盘:$PATH"

export RPC="http://地址"
export KEY="私钥"
export CONTRACT="合约地址"

cast codesize "$CONTRACT" --rpc-url "$RPC"
cast call "$CONTRACT" "solved()(bool)" --rpc-url "$RPC"
cast send "$CONTRACT" "sayHello(string)" "welcome_to_?ctf2026" --private-key "$KEY" --rpc-url "$RPC"
cast call "$CONTRACT" "solved()(bool)" --rpc-url "$RPC"
cast call "$CONTRACT" "helloCount()(uint256)" --rpc-url "$RPC"

```
然后就通关了
实际的回显
```bash
yu@TIAN-XUAN-JI MINGW64 ~
$ export RPC="http://8545-203e2495-838e-47cb-970b-431185593691.challenge.ctfplus.cn"                
yu@TIAN-XUAN-JI MINGW64 ~
$ export KEY="b0a87e8eb3afc0ab6fad35d437a00f16823cfe923e5d2badc0a5bfd2e2e54eec"                     
yu@TIAN-XUAN-JI MINGW64 ~
$ export CONTRACT="0x64179A9fFbA757e8Fc4D0C55362a5DE7091b2948"                                      
yu@TIAN-XUAN-JI MINGW64 ~
$ cast codesize "$CONTRACT" --rpc-url "$RPC"
cast call "$CONTRACT" "solved()(bool)" --rpc-url "$RPC"
cast send "$CONTRACT" "sayHello(string)" "welcome_to_?ctf2026" --private-key "$KEY" --rpc-url "$RPC"
cast call "$CONTRACT" "solved()(bool)" --rpc-url "$RPC"
cast call "$CONTRACT" "helloCount()(uint256)" --rpc-url "$RPC"
602
false

blockHash            0x925c811ec086d1b2f6e5e0fdaff29b420f053f681d24eb29d50a64b32e1e49fc
blockNumber          2
contractAddress
cumulativeGasUsed    67833
effectiveGasPrice    1876529225
from                 0xE00dA8C392041303BA6275B34D1023bBA87B3E98
gasUsed              67833
logs                 [{"address":"0x64179a9ffba757e8fc4d0c55362a5de7091b2948","topics":["0xd282f389399565f3671145f5916e51652b60eee8e5c759293a2f5771b8ddfd2e","0x000000000000000000000000e00da8c392041303ba6275b34d1023bba87b3e98"],"data":"0x","blockHash":"0x925c811ec086d1b2f6e5e0fdaff29b420f053f681d24eb29d50a64b32e1e49fc","blockNumber":"0x2","blockTimestamp":"0x6ad842ce","transactionHash":"0x193a31c2ab95f95b8ca59a13db59b8bf52069e6e3f90a0c4b0940155a93fa150","transactionIndex":"0x0","logIndex":"0x0","removed":false}]
logsBloom            0x00000000002000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000040000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000080000000000000001000000000004000000000000008000000000000000000000400000000000000000000000000000000000000000000010000000000000000800000000000000000000000000000000000000000000000000000000000000000000000000000000
root
status               1 (success)
transactionHash      0x193a31c2ab95f95b8ca59a13db59b8bf52069e6e3f90a0c4b0940155a93fa150
transactionIndex     0
type                 2
blobGasPrice         1
blobGasUsed
to                   0x64179A9fFbA757e8Fc4D0C55362a5DE7091b2948
true
1

```
![](/assets/posts/qctf2026/qctf2026-misc-onchain-1.png)

复现flag
`QCTF{46d4fabe-b03f-4912-b71a-5b80b57f03d4}`
