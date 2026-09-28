<?php
/**
 * Site Config — 站点地址唯一来源 (2026-09-14 上线准备)
 * 本地默认 Laragon; 线上部署时在 blog/ 根建 config-override.php (已 gitignore) 覆盖:
 *   <?php
 *   $SITE_URL = 'https://你的域名';   // og:url / RSS / 站内绝对链接前缀
 *   $BASE     = '';                   // 部署子路径: 域名根部署用 '', 子目录部署用 '/blog'
 */

// 2026-09-28 修复: 此前只有 login.php 设了时区, 其余写入口全用 PHP 默认值。
// Laragon 与多数发行版的默认是 date.timezone=UTC —— 于是 comments-api / editor-article /
// account 写出来的时间戳全部比真实时间**早 8 小时**(评论显示的时间是错的)。
// 时区属站点配置, 放这里一处修、全站生效。
date_default_timezone_set('Asia/Shanghai');

$SITE_URL = 'http://localhost:8080/blog';
$BASE     = '/blog';
if (is_file(__DIR__ . '/../config-override.php')) {
    require __DIR__ . '/../config-override.php';
}
