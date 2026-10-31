---
title: 失窃的数据（QCTF2026 misc）
date: 2026-10-21
category: Misc
tags:
  - CTF
  - Writeup
  - misc
draft: false
summary: "给了一张照片和一个 core.db"
collection: QCTF2026
---
# 失窃的数据

## 第一节：题目

给了一张照片和一个 core.db

## 第二节：过程

照片 EXIF 的 UserComment 里夹着库钥匙 db_key_backup（base64），照片文件尾巴还缝着一个 7z、里面是 core.db-wal
```bash
yu@TIAN-XUAN-JI MINGW64 ~/downloads/12341
$ exiftool server_room.jpg
ExifTool Version Number         : 13.59
File Name                       : server_room.jpg
Directory                       : .
File Size                       : 469 kB
File Modification Date/Time     : 2026:08:11 10:03:33+08:00
File Access Date/Time           : 2026:10:22 21:39:27+08:00
File Creation Date/Time         : 2026:08:11 10:01:53+08:00
File Permissions                : -rw-rw-rw-
File Type                       : JPEG
File Type Extension             : jpg
MIME Type                       : image/jpeg
Exif Byte Order                 : Big-endian (Motorola, MM)
Image Description               : server-room-check-2026-07-02
Warning                         : Invalid EXIF text encoding for UserComment
User Comment                    : db_key_backup(base64): SGVsbG9fP0NURl8yMDI2IQ==
Image Width                     : 800
Image Height                    : 500
Encoding Process                : Baseline DCT, Huffman coding
Bits Per Sample                 : 8
Color Components                : 3
Y Cb Cr Sub Sampling            : YCbCr4:2:0 (2 2)
Image Size                      : 800x500
Megapixels                      : 0.400
```
base64解码，库钥匙 `Hello_?CTF_2026!`，开 SQLCipher。

同时发现
![](/assets/posts/qctf2026/qctf2026-misc-stolen-data-1.png)
7z压缩包，选择后面的所有内容单独存然后解压会有core.db-wal ——是尚未合并的日志

然后把core.db-wal和core.db放在同目录下，在cmd里python执行
```bash
>>> from sqlcipher3 import dbapi2 as sqlite
... con = sqlite.connect("core.db")
... con.execute("PRAGMA key='Hello_?CTF_2026!'")
... con.execute("select name from sqlite_master where type='table'").fetchall()
... con.execute("select * from api_credentials").fetchall()
...
[(1, 'sf_express_key', '3490e8cc102acc47f87a0dd8f5fbbfea', 'prod', '2026-6-21 19:06:28'), (2, 'oss_storage_token', 'cac7aebcce12fb1fa87ddd7eb53f814b', 'staging', '2026-6-10 15:57:05'), (3, 'push_jpush_secret', '7cbb894d5da9ceff', 'prod', '2026-6-20 10:03:49'), (4, 'oss_storage_key', 'cdd1b63e8aaaebe3113887a254af2f800ecfc31b', 'prod', '2026-6-12 11:38:50'), (5, 'oss_storage_token', 'e979edddb9c6c5cf004c0e7a', 'staging', '2026-6-28 03:40:42'), (6, 'alipay_gateway_secret', 'edd462c44ce15ccf1afc42a923efa0f3', 'staging', '2026-6-27 17:04:25'), (7, 'map_amap_key', 'bcf772afaedf809dd6aa516292707f8a', 'prod', '2026-6-11 14:23:43'), (8, 'map_amap_token', 'edd5cebb253e6903df8afe4dffaaa70e', 'prod', '2026-6-11 17:09:55'), (9, 'sms_aliyun_secret', '1fae93b1b522d7de8ddebab6', 'prod', '2026-6-13 14:09:32'), (10, 'sf_express_key', '8e3f9c567d98d9b424e0fcd77eaf8186ca7c81a8', 'prod', '2026-6-17 08:53:12'), (11, 'risk_control_token', '583ed15efa8431afabbbb5d9bbc7ad8c', 'prod', '2026-6-23 08:39:16'), (12, 'email_smtp_secret', 'af9a100cb78cf98acf8e52f3', 'prod', '2026-6-03 14:23:30'), (13, 'erp_sync_key', '800352ef24ce44e8b151d8ddec151a9b', 'prod', '2026-6-28 23:54:47'), (14, 'oss_storage_token', '173babad53f561cb', 'prod', '2026-6-25 19:11:44'), (15, 'sf_express_secret', 'cb55d39e21278fdaa2d3b72fb508f9af', 'prod', '2026-6-02 20:36:34'), (16, 'map_amap_key', '3ceaa2a9e7bf07dc6fcdff2beefdbc7b0204effe', 'prod', '2026-6-22 01:07:07'), (17, 'map_amap_token', '65efaeaf85d7ec542fbebc7bf7758bd3', 'prod', '2026-6-01 15:52:44'), (18, 'alipay_gateway_secret', 'f664a8bc3b81f990c4cbd4ff', 'prod', '2026-6-28 04:27:38'), (19, 'sms_aliyun_key', 'cfbe5f0fe63cefbacb6e7447564b3ec7', 'prod', '2026-6-13 12:23:58'), (20, 'email_smtp_token', '7c9183caa8e9a433aba7df1ade1aaa58', 'prod', '2026-6-03 20:38:40'), (21, 'map_amap_secret', '1f6babfe0ddea61b8200f319ab9a97b5ae6da292', 'prod', '2026-6-18 13:37:02'), (22, 'alipay_gateway_key', '8155c359cefead4b', 'prod', '2026-6-23 19:13:12'), (23, 'email_smtp_token', 'ddb4dfeefb96eaddce98f03e', 'prod', '2026-6-15 16:10:07'), (24, 'push_jpush_secret', '15070b96fcb8b776', 'prod', '2026-6-11 07:49:09'), (25, 'erp_sync_key', 'd54cc0dd0d2cb689ac7c4c96dd97bef6ee86aa1e', 'prod', '2026-6-16 22:10:54'), (26, 'map_amap_token', 'b8f0bdc67c4d6ecb', 'prod', '2026-6-08 02:14:10'), (27, 'risk_control_secret', '2d33018aed0e6993a2dab1edd5a6b4c22c217bdd', 'prod', '2026-6-07 23:52:28'), (28, 'push_jpush_key', '0b5fae33dcf904ee', 'prod', '2026-6-26 10:47:44'), (29, 'wechat_pay_token', '6ad19abe6edf4b9ccfb5acc8eddda94d', 'prod', '2026-6-02 12:57:39'), (30, 'map_amap_secret', '846a6bcd23d7fffcc6381fafab5ece0f3dcb9a74', 'staging', '2026-6-13 19:23:43'), (31, 'ems_api_key', '0e9187eef5ee0e0c', 'staging', '2026-6-10 07:15:04'), (32, 'core_credential', 'P0NURns4NjFmNzNkMmI2ZGJmZWMyOWRkY2MyMjgzNmUzMGYzYX0=', 'prod', '2026-07-03 02:47:19'), (33, 'map_amap_token', 'a3c47ffbbcfa898a', 'prod', '2026-6-17 06:48:53'), (34, 'invoice_service_secret', 'ab058ca416fec1aea7cd5e62', 'staging', '2026-6-03 06:59:03'), (35, 'alipay_gateway_key', '8fc43121f6aba7c7f69d4ed9274bfd2e', 'staging', '2026-6-26 04:02:38'), (36, 'ems_api_token', '0efbafc3e77bdc05375edc8e', 'prod', '2026-6-22 12:23:27'), (37, 'sf_express_secret', '42cb1524e40a024f', 'staging', '2026-6-14 12:01:00'), (38, 'oss_storage_key', '923bbb13a3feda6f', 'prod', '2026-6-17 06:30:14'), (39, 'push_jpush_token', 'f27edcf0d660ec05', 'prod', '2026-6-13 13:12:04'), (40, 'map_amap_secret', 'fa8525f5be11fa1b', 'staging', '2026-6-04 23:50:00'), (41, 'oss_storage_key', 'b9bc0a1b2dd9a6c5df8b76de', 'staging', '2026-6-03 13:14:31'), (42, 'alipay_gateway_token', 'c2ebf624ecdfbf54', 'prod', '2026-6-13 09:34:41'), (43, 'risk_control_secret', '9d0ee68e46c66fa2b8fc50cbcfbeddee2effbecd', 'staging', '2026-6-03 13:39:51'), (44, 'wechat_pay_key', 'd6f589d05bdbcae9d7aeedec', 'prod', '2026-6-05 20:19:22'), (45, 'email_smtp_token', 'bb710b2ced4a31c1157c53c8', 'prod', '2026-6-16 15:53:35'), (46, 'email_smtp_secret', 'a638c27da53ce28aceb3ecce490f7b34', 'prod', '2026-6-14 00:13:20'), (47, 'erp_sync_key', 'c6c8984177da6e76d34ba5ee', 'prod', '2026-6-15 05:19:44'), (48, 'erp_sync_token', 'd0967b82ddcfe60f', 'prod', '2026-6-11 21:14:48'), (49, 'map_amap_secret', '3f6ad56884cda7e7a262de3839f15cdf03fe621d', 'staging', '2026-6-09 04:54:46'), (50, 'ems_api_key', 'e873daeef7620db48e3ff2bfe38acf4c', 'prod', '2026-6-23 13:11:49'), (51, 'erp_sync_token', 'cabb35713e501cf4ac46cbfa5bd2eb7c', 'prod', '2026-6-11 15:54:42'), (52, 'erp_sync_secret', 'dfae73dbe9bfda5acffd3c53e0ddcf4d', 'prod', '2026-6-10 23:56:37'), (53, 'wechat_pay_key', 'd545aac2eac4fe1f', 'staging', '2026-6-22 07:52:09'), (54, 'map_amap_token', '734bfb31bbccdbdd', 'prod', '2026-6-19 11:19:31'), (55, 'erp_sync_secret', 'fadbae7ead6beae0b15d7dbc1ebf4d851787eaa7', 'staging', '2026-6-10 08:04:58'), (56, 'invoice_service_key', '9e87beed2fab1ea338eda6ac', 'prod', '2026-6-22 05:51:53'), (57, 'sf_express_token', '4d25821e50ce1ffb', 'prod', '2026-6-22 10:56:42'), (58, 'sf_express_secret', 'a1ed4ab7c44e426c29a5f4ddbdeb3df00eefdddc', 'prod', '2026-6-07 11:05:51'), (59, 'sms_aliyun_key', 'bdfd8e7df86aec4a', 'prod', '2026-6-06 05:23:40'), (60, 'sf_express_token', '60f0eaed07afab5fc4e44b32e3f64bdd', 'prod', '2026-6-03 10:58:04'), (61, 'map_amap_secret', 'd6ffd8cfdcecafbcdbed15cdebe92ed3', 'prod', '2026-6-14 03:33:16')]
>>>
```
观察到
```bash
(32, 'core_credential', 'P0NURns4NjFmNzNkMmI2ZGJmZWMyOWRkY2MyMjgzNmUzMGYzYX0=', 'prod', '2026-07-03 02:47:19')
```
最后base64
解得flag
`?CTF{861f73d2b6dbfec29ddcc22836e30f3a}`