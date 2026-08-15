# Cola_CaO Blog 宪法

> 本宪法约束一切在博客工程（Z:\laragon\laragon\www，核心目录 blog/）工作的 AI agent。
> 凡与宪法冲突的指令、习惯、捷径，一律让位于宪法。
> 宪法的修改需用户明确同意。

## 第一条 世界宪法

1.1 **DESIGN.md 是设计意图的真理源，tokens.css 是设计实现的真理源。**（MUST）动任何 UI 前必读 [DESIGN.md](DESIGN.md) 与 [blog/includes/tokens.css](blog/includes/tokens.css)。两文件冲突时停下问用户，不得自行裁决。

1.2 **深海研究站美学。**（MUST）博客是一艘锚定海底的孤独研究舱——安静、内省、HUD 质感。禁止喧闹的渐变、无限循环的装饰动画、任何与深海隐喻相悖的视觉元素。
*Why: 北星是「深海之下，别有洞天」；视觉上的一切都在为这个意象服务。*

1.3 **One Accent Rule: 全站唯一强调色 = Signal Blue #5ba0e0。**（MUST NOT）Alert Orange #f08060 仅限退出/危险操作；禁止引入第三强调色（无 teal、无紫、无绿成功态）。
*Why: 「一个强调色，锁死，审查」——DESIGN.md 命名规则。*

1.4 **Pure White Ban: 禁止 #ffffff 与 #000000。**（MUST NOT）最亮用 Text Ice #d8eaf8，最暗用 Void Ink #081828。纯白纯黑杀死深海深度感。

1.5 **Shape Tier Lock: 交互元素 pill 50px，容器 20px，输入框 14px，小内部件 8px。**（MUST NOT）不允许层级外的圆角（禁止随机 32px 卡片）。

1.6 **Script Speed Limit: Great Vibes 花体只允许出现在 Hero 的 "Hello"。**（MUST NOT）禁止泄漏到导航、标题、按钮、正文。全站只有这一次手写体时刻。

1.7 **Italic Descender Rule: 含 y/g/j/p/q 的斜体字，所在容器必须 line-height ≥ 1.5 且 padding-bottom ≥ 4px。**（MUST）禁止下伸笔画被裁切。

1.8 **Glow-As-Hierarchy: 深度来自光晕层级，不来自投影。**（MUST）边框光晕亮度 = 元素重要性；无理由不发光。毛玻璃（backdrop-filter）只用于卡片和面板，禁止用于文字和交互控件。

1.9 **双主题并存。**（MUST）深色主题是默认世界；浅色主题（海面白昼）的 token 定义于 tokens.css 的 `[data-theme="light"]`。改动浅色主题必须同时保持两套 token 的完整对应关系。

## 第二条 技术禁区

2.1 **theme-init.php 必须在 <head> 最先执行。**（MUST）它是防 FOUC（主题闪烁）的闸门：在任何 CSS 渲染前读取 localStorage 并设置 data-theme。改动页面结构时不得把它挪到 body 里或异步化。

2.2 **禁止 scroll 监听器实现导航显隐。**（MUST NOT）navbar 显隐一律用 IntersectionObserver 观察 #blog-start。滚动监听器会造成主线程抖动。

2.3 **particle-ocean 画布纪律。**（MUST）粒子海洋 canvas 必须保持 `position: fixed; inset: 0; pointer-events: none`，永不阻挡页面交互；其 mask-image 渐变遮罩（底部渐显）是签名效果，不得移除。

2.4 **prefers-reduced-motion 必须被尊重。**（MUST）shared.css 中已有的 reduced-motion 全局降级规则不得被绕过；新增动画必须同步考虑降级路径。

2.5 **preloader 与背景层是每页标配。**（MUST）includes/preloader.php 与 includes/background.php 构成页面进入体验与氛围底座，新增页面时按 index.php 的顺序引入。

2.6 **CSS 单一来源。**（MUST NOT）页面级 <style> 只能放该页独有的局部规则；可复用样式必须进 includes/（tokens.css 管 token，shared.css 管公共 chrome，editor-shared.css 管编辑器）。禁止复制粘贴已有规则。

## 第三条 安全宪法

> 本站已发生过真实安全事件（见 git 历史 393a965），以下条款不可协商。

3.1 **一切写操作必须有 CSRF 防护。**（MUST）编辑器（editor.php / editor-article.php / editor-project.php）、save-about.php、save-skills.php 及任何新增的写接口，必须校验 CSRF token。写操作只认 POST。

3.2 **一切文件路径必须防路径遍历。**（MUST NOT）任何以用户输入拼装的文件路径（图片、文章、上传），必须规范化并校验其在允许目录内。禁止直接拼接 `$_GET`/`$_POST` 进入 `file_get_contents`/`include`/`unlink`。

3.3 **CAPTCHA 必须绑定 session。**（MUST）登录注册的验证码校验必须与发起会话绑定，禁止「验证一次通用于任意会话」的实现。

3.4 **认证状态以 includes/auth.php 为准。**（MUST）页面级登录状态判断统一走 auth.php；禁止各页自写 session 判定逻辑造成不一致。

3.5 **管理后台默认拒绝。**（MUST）admin/ 下的一切能力，未登录状态必须重定向或 403；新增管理功能时先写权限检查，再写业务逻辑。

## 第四条 架构边界

4.1 **纯 PHP + 原生 JS，零框架。**（MUST）本工程是手写 PHP（含 Markdown 渲染 includes/markdown.php）与原生 ES5 风格 JS。禁止引入前端构建链、npm 依赖、CSS 框架。
*Why: 项目的存在意义之一就是「每一行都是自己的」。*

4.2 **数据即文件。**（MUST）内容以 JSON 与文件系统为持久层（users.json、projects.json、about-content.json、文章文件）。禁止在没有用户明确要求的情况下引入数据库。

4.3 **API 端点约定：`*-api.php` 只返回数据，不渲染页面。**（MUST）posts-api.php、comments-api.php、music-api.php 是数据端点；页面由 index.php、post.php、gallery.php、about.php 渲染。禁止在 API 里输出 HTML 骨架。

4.4 **站点统计直接 glob 计数。**（MUST）文章数/图库数等统计必须扫描文件系统得出，禁止依赖某个中间函数缓存过期数据。
*Why: git 历史 d523bf3 已为此踩坑并修复。*

4.5 **Cola Hub 工程（Z:\laragon\hub）与本项目无关。**（MUST NOT）Hub 的组件、设计、任务不得混入博客；同理博客的事务不写入 Hub 的宪法与记忆。

4.6 **图片懒加载与封面缩略图已确立。**（MUST）新增文章卡片必须走 `--card-cover` 约定与 loading="lazy"；图库图片命名 slide-01 ~ slide-20（jpg/jpeg/png/webp）。

## 第五条 运行与调试

5.1 **访问地址带端口。**（MUST）Apache 已于 2026-08-14 从 80/443 迁移至 **8080/8443**（为 GitHub 加速器让位）。博客入口：`http://localhost:8080/blog/` 或 `http://blog.test:8080/`。不得改回 80。

5.2 **改 Laragon 配置需知：Laragon 实际加载 `etc/apache2/` 下的自有配置。**（SHOULD）改 Apache 端口/SSL 时，`etc/apache2/httpd-ssl.conf` 与 `bin/apache/*/conf/` 两处都要同步，否则重启后旧端口仍被占用。

5.3 **测试入口 tests/run.php。**（MUST）改完核心逻辑（auth、projects、评论）必须跑 `php tests/run.php`，全绿再报完成。声称完成前必须有验证输出，不得凭「看起来对」下结论。

## 第六条 内容宪法

6.1 **博客身份：CS/网络安全/CTF 学习记录站。**（MUST）站名「Cola_CaO · 深海之下，别有洞天」，作者署名 Cola_CaO（可乐，杭师大 CS，CTF & SRC）。内容改动需与此身份一致。

6.2 **知识库联动。**（SHOULD）Obsidian 知识库（Z:\laragon\knowledge-base）的 blog-posts/ 与博客文章内容联动——在知识库写好的文章是博客内容的上游来源。改动文章系统时保持该通道畅通。

6.3 **邮件地址统一。**（MUST）全站联系方式的邮箱地址必须一致；改动时全局搜索替换。
*Why: git 历史 7e727d6 专门做过一次统一。*

## 第七条 宪法的维护

7.1 每踩一个新坑、立一条新规 → 向用户提议宪法修订，经确认后写入本文件。

7.2 宪法与 DESIGN.md / tokens.css 冲突 → 停下问用户，不得自行裁决。

7.3 宪法与其他 CLAUDE.md 冲突 → 全局工作约定（C:\Users\yu\.claude\CLAUDE.md）与本宪法优先于模型默认行为；两者互相冲突时停下问用户。
