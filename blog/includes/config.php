<?php
/**
 * Site Config — 站点地址唯一来源 (2026-09-14 上线准备)
 * 本地默认 Laragon; 线上部署时在 blog/ 根建 config-override.php (已 gitignore) 覆盖:
 *   <?php
 *   $SITE_URL = 'https://你的域名';   // og:url / RSS / 站内绝对链接前缀
 *   $BASE     = '';                   // 部署子路径: 域名根部署用 '', 子目录部署用 '/blog'
 */
$SITE_URL = 'http://localhost:8080/blog';
$BASE     = '/blog';
if (is_file(__DIR__ . '/../config-override.php')) {
    require __DIR__ . '/../config-override.php';
}
