<?php
/**
 * Terms — 用户服务协议与隐私政策 (2026-10-14)
 *
 * 依据: 公安《交互式服务安全检查表》
 *   - 9.2.1  用户注册时签订服务协议, 告知权利义务
 *   - 10.1.1 个人电子信息处理规则在显著位置公示
 * 入口: 注册表单勾选链接 (login.php) + 页脚常驻链接 (footer.php)
 */
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<?php
$pageTitle = 'Terms · Cola_CaO';
$pageDesc = '用户服务协议与隐私政策 — 本站的服务规则、内容管理方式与个人信息保护措施。';
$extraHead = '<meta property="og:title" content="Terms · Cola_CaO">
<meta property="og:description" content="用户服务协议与隐私政策。">
<meta property="og:type" content="website">';
require __DIR__ . '/includes/head.php';
?>
<style>
body{font-family:var(--font-body);background:var(--bg-deep);color:var(--text-primary);line-height:1.7;min-height:100vh;overflow-x:hidden}

.page-wrap{position:relative;z-index:2;max-width:900px;margin:0 auto;padding:100px 28px 60px}
.page-header{text-align:center;margin-bottom:50px}
.page-header h1{font-family:var(--font-display);font-size:2.8rem;font-weight:700;letter-spacing:0.04em}
.page-header .sub{color:var(--text-muted);font-size:0.9rem;margin-top:8px}

.about-card{background:var(--bg-card);backdrop-filter:blur(14px);border:1px solid var(--border-glow);border-radius:var(--radius-lg);padding:36px;margin-bottom:28px;box-shadow:var(--shadow-sm);transition:var(--transition-smooth)}
.about-card:hover{border-color:var(--border-glow-strong);box-shadow:0 8px 28px rgba(91,160,224,0.1),inset 0 1px 0 rgba(255,255,255,0.04)}
.about-card h2{font-family:var(--font-display);font-size:1.2rem;color:var(--accent);letter-spacing:0.06em;margin-bottom:20px;padding-bottom:10px;border-bottom:1px solid var(--border-glow);display:flex;align-items:center;gap:8px}
.about-card h2 .diamond{width:6px;height:6px;background:var(--accent);transform:rotate(45deg);box-shadow:0 0 4px var(--accent)}

.terms-body{font-size:0.9rem;line-height:2;color:var(--text-secondary)}
.terms-body h3{font-size:0.92rem;color:var(--text-primary);font-weight:600;margin:18px 0 6px;letter-spacing:0.02em}
.terms-body h3:first-child{margin-top:0}
.terms-body p{margin:0 0 6px}
.terms-body a{color:var(--accent)}
.updated{text-align:center;color:var(--text-muted);font-size:0.78rem;margin-top:32px}

@media(max-width:768px){.page-header h1{font-size:2rem}}
</style>
</head>
<body>
<?php require __DIR__ . '/includes/preloader.php'; ?>
<?php require __DIR__ . '/includes/background.php'; ?>
<?php $navActive = ''; require __DIR__ . '/includes/navbar.php'; ?>

<div class="page-wrap">
  <div class="page-header">
    <h1>Terms / 条款</h1>
    <p class="sub">用户服务协议与隐私政策</p>
  </div>

  <div class="about-card">
    <h2><span class="diamond"></span> 用户服务协议</h2>
    <div class="terms-body">
      <h3>一、服务说明</h3>
      <p>本站（知识灯塔，co1acao.com）为个人运营的非经营性博客，提供文章浏览、评论与留言服务。用户使用本站服务即视为同意本协议。</p>

      <h3>二、用户义务</h3>
      <p>用户在本站注册、发表评论或留言时，须遵守《中华人民共和国网络安全法》等法律法规，不得制作、复制、发布、传播含有下列内容的信息：</p>
      <p>（一）反对宪法确定的基本原则的；（二）危害国家安全、泄露国家秘密、颠覆国家政权、破坏国家统一的；（三）煽动民族仇恨、民族歧视，破坏民族团结的；（四）宣扬邪教和封建迷信的；（五）散布谣言，扰乱社会秩序、破坏社会稳定的；（六）散布淫秽、色情、赌博、暴力、恐怖或者教唆犯罪的；（七）侮辱、诽谤他人，侵害他人合法权益的；（八）含有法律、行政法规禁止的其他内容的。</p>
      <p>用户不得利用本站从事任何危害计算机网络安全的活动。</p>

      <h3>三、内容管理（先审后发）</h3>
      <p>本站对用户发布的信息实行<b>先审后发</b>：所有评论、留言均须经站方审核通过后方可公开展示。站方采用关键词过滤等技术措施辅助拦截违法有害信息；对违法有害信息一律不予发布或立即删除，保留相关记录，并依法向主管部门报告。</p>

      <h3>四、账号管理</h3>
      <p>用户应妥善保管账号，不得转让他人。本站对多次、大量发布违法有害信息的账号实行黑名单管理，禁止其继续发布内容。</p>

      <h3>五、知识产权</h3>
      <p>本站原创文章的著作权归作者所有，转载请注明出处并保留原文链接。</p>

      <h3>六、协议变更</h3>
      <p>本协议如有变更将在本页公示，用户继续使用本站服务即视为同意变更后的协议。</p>
    </div>
  </div>

  <div class="about-card">
    <h2><span class="diamond"></span> 隐私政策（个人电子信息保护）</h2>
    <div class="terms-body">
      <h3>一、收集范围</h3>
      <p>注册时收集：用户名、密码（以 bcrypt 单向加密存储，站方无法获知明文）。发表评论或留言时收集：昵称、内容。服务器自动记录访问日志（含 IP 地址、访问时间、请求内容等）。本站不收集上述范围以外的个人信息。</p>

      <h3>二、使用目的</h3>
      <p>仅用于提供账号与评论功能、保障站点安全运行、安全审计，以及依法配合监管部门工作。</p>

      <h3>三、存储与保护</h3>
      <p>全部数据存储于中国境内的服务器；全站采用 HTTPS 加密传输；账号密码加密存储；访问日志留存不少于 6 个月。</p>

      <h3>四、对外提供</h3>
      <p>除法律法规规定或公安机关、监管部门依法调取外，本站不向任何第三方提供、出售或共享用户个人信息。</p>

      <h3>五、用户权利</h3>
      <p>用户可随时修改自己的昵称与密码，可删除自己发布的评论；如需注销账号或行使其他个人信息权利，请通过下方渠道联系站方。</p>

      <h3>六、投诉与举报</h3>
      <p>如发现违法有害信息，或认为本站处理个人信息的行为侵害了您的合法权益，请通过举报邮箱联系我们：<a href="mailto:cola_ca0@qq.com">cola_ca0@qq.com</a>。站方将及时核实处理。</p>
    </div>
  </div>

  <p class="updated">更新日期：2026 年 10 月 14 日</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
