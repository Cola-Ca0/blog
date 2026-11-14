---
name: Cola_CaO Blog
description: A personal tech blog dressed as a deep-sea research station — quiet, introspective, with tactile HUD elements.
colors:
  signal-blue: "#5ba0e0"
  signal-blue-soft: "#7ab8e8"
  alert-orange: "#f08060"
  haze-cyan: "#8ed0e8"
  void-ink: "#081828"
  panel-steel: "rgba(8, 28, 48, 0.72)"
  panel-steel-hover: "rgba(10, 32, 55, 0.85)"
  border-glow-dim: "rgba(91, 160, 224, 0.28)"
  border-glow-bright: "rgba(91, 160, 224, 0.5)"
  text-ice: "#d8eaf8"
  text-frost: "#98bcd8"
  text-haze: "#68889e"
typography:
  display:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "clamp(2rem, 5vw, 3.6rem)"
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "0.02em"
  headline:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "1.45rem"
    fontWeight: 700
    lineHeight: 1.35
  title:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "0.85rem"
    fontWeight: 600
    letterSpacing: "0.04em"
  body:
    fontFamily: "'Exo 2', 'Quicksand', sans-serif"
    fontSize: "0.9rem"
    fontWeight: 400
    lineHeight: 1.75
  label:
    fontFamily: "'Rajdhani', 'Exo 2', sans-serif"
    fontSize: "0.72rem"
    fontWeight: 600
    letterSpacing: "0.1em"
  script:
    fontFamily: "'Great Vibes', cursive"
    fontSize: "clamp(2.4rem, 5.5vw, 4rem)"
    fontWeight: 400
  editorial:
    fontFamily: "'Noto Serif SC', 'Georgia', serif"
    fontStyle: "italic"
    fontWeight: 300
rounded:
  sm: "8px"
  md: "14px"
  lg: "20px"
  xl: "24px"
  pill: "50px"
spacing:
  container: "max-width: 1200px; padding: 0 28px"
  section-gap: "40px"
  card-inner: "24-28px"
  nav-height: "66px"
components:
  button-primary:
    backgroundColor: "linear-gradient(135deg, {colors.signal-blue}, {colors.haze-cyan})"
    textColor: "#ffffff"
    rounded: "{rounded.pill}"
    padding: "8px 20px"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.haze-cyan}"
    rounded: "{rounded.pill}"
    padding: "8px 20px"
  card-post:
    backgroundColor: "{colors.panel-steel}"
    rounded: "{rounded.lg}"
  card-sidebar:
    backgroundColor: "{colors.panel-steel}"
    rounded: "{rounded.lg}"
  tag-chip:
    backgroundColor: "rgba(91, 160, 224, 0.06)"
    textColor: "{colors.text-frost}"
    rounded: "{rounded.pill}"
---

# Design System: Cola_CaO Blog

## Overview

**Creative North Star: "The Deep-Sea Station"**

The blog is a solitary research pod anchored on the ocean floor. Through its viewports, marine life drifts past in the blue-black water. Inside, quiet instruments hum: a sonar ping, an oxygen gauge, a terminal waiting for the next command. The station is functional first — but function in this environment is inherently beautiful.

The aesthetic is quiet and introspective. No screaming gradients, no attention-seeking animations. The depth of the ocean does the emotional work; the interface just needs to stay out of the way and feel trustworthy. When something glows, it glows for a reason: a status indicator, a hover state, a focused input. Light is signal.

This is a tech blog dressed as a HUD — not the aggressive neon of cyberpunk, but the subdued instrumentation of a real research vessel. The visitor should feel like they've put on a headset and tuned into a private channel deep underwater.

**Key Characteristics:**
- Dark-mode-only, high contrast within a narrow blue tonal range
- Glow-as-hierarchy: brighter border glow = higher importance
- Tactile HUD feedback: buttons depress, cards lift, status dots pulse
- Single accent color (Signal Blue) carried everywhere with monastic consistency
- Sci-fi instrumentation vocabulary (diamond decorators, corner brackets, scanlines, data rows) applied with restraint
- Glassmorphism reserved for cards and panels only; never on text or interactive elements

**Hero Wallpaper (2026-08-16, user's call).** The hero wallpaper (`assets/images/wallpaper.jpg`) is an intentional personal choice — an anime ocean illustration (Gura). It overrides the "abstract deep-sea" description above: the hero may carry bright sky tones that would otherwise violate the Pure White Ban. The wallpaper is exempt from the ban; all other chrome and content areas still follow it. The wave shimmer overlay bridges the illustration with the station metaphor.

**Mascot (2026-08-27, user's call).** No Live2D 看板娘 / waifu mascot. Tried once before without success; the research pod stays crewless. Do not re-propose mascot features — the station's voice is instrumentation, not characters.

**二次元温度升级 (2026-08-27, user's call, 参照 mizuki/lin-xin).** The station keeps its deep-sea stillness but gains warm accents: body font switched to **Zen Maru Gothic** (圆体, CJK) stacked over Exo 2 — roundness × code; hero 引言 **打字机逐字动效** (typewriter + blinking cursor, reduced-motion shows full text); 文章卡封面改为**右侧图栏** (not full-width, mizuki-style; <768px returns to top banner); 滚动渐入加 **reveal-left/right** 方向变体 (mizuki scroll-fade, 仍温和不翻页); 新增 **树洞 treehole.php** (匿名倾诉, CSRF + 审核后公开) 与 **文章时间轴 timeline.php**; 侧栏 **Crew 舱员卡** — a single pixel-art character card (明日香, 本地素材不入库, 点击换台词, 低调装饰). 无看板娘 & One Accent 等红线全部保持。

## Colors

The palette reads like a submarine instrument panel: cool blues for structure and depth, a single warm accent for alerts and highlights. Every color has a technical codename that fits the station metaphor.

### Primary
- **Signal Blue** (#5ba0e0): The station's main operating frequency. Used on primary buttons, status indicators, hover glows, and the hero headline. It is the one accent that carries across the entire page.
- **Signal Blue Soft** (#7ab8e8): A lighter harmonic of the primary. Used in gradient transitions, secondary hover states, and the wave divider.

### Secondary
- **Alert Orange** (#f08060): Reserved for logout buttons and attention-requiring UI. Use sparingly — its warmth is a deliberate contrast against the cool ocean palette.

### Tertiary
- **Haze Cyan** (#8ed0e8): Ghost buttons, hero text, border glow, the diamond decorator elements. It is the most-used decorative color, appearing wherever subtle illumination is needed without the full weight of Signal Blue.

### Neutral
- **Void Ink** (#081828): The page background. The color of deep ocean at night. Everything floats on this.
- **Panel Steel** (rgba(8, 28, 48, 0.72)): Semi-transparent card and panel background. The translucency lets background orbs and grid lines ghost through.
- **Border Glow Dim** (rgba(91, 160, 224, 0.28)): Default card and panel borders.
- **Border Glow Bright** (rgba(91, 160, 224, 0.5)): Hover state borders. Cards "wake up" when the cursor approaches.
- **Text Ice** (#d8eaf8): Primary body text. Nearly white but tinted blue — never pure white.
- **Text Frost** (#98bcd8): Secondary text. Muted but readable.
- **Text Haze** (#68889e): Tertiary text, metadata, muted labels.

### Named Rules
**The One Accent Rule.** Signal Blue is the only accent color on the page. Alert Orange is reserved for destructive/exit actions only. No third accent is introduced anywhere — no teal status badges, no purple hover states, no warm-grey section that suddenly gets a blue CTA. One accent, locked, audited.

**The Pure White Ban.** No `#ffffff` and no `#000000` anywhere on the page. Text Ice is the brightest value; Void Ink is the darkest. Pure values kill depth in a dark-mode station aesthetic.

## Typography

**Display Font:** Rajdhani (with Exo 2 fallback)
**Body Font:** Exo 2 (with Quicksand fallback)
**Script Font:** Great Vibes (hero only)
**Editorial Font:** Noto Serif SC (quotes and editorial moments only)

**Character:** Rajdhani brings the sci-fi instrumentation voice — wide, geometric, slightly futuristic. Exo 2 softens it for body reading — a humanist-tech hybrid that reads comfortably at small sizes. Great Vibes is the one decorative departure, reserved exclusively for the hero "Hello" — one moment of warmth before the station instruments take over.

### Hierarchy
- **Display** (Rajdhani, 700, clamp(2rem, 5vw, 3.6rem), line-height 1.15): Section hero headlines. Used once per page section.
- **Headline** (Rajdhani, 700, 1.45rem, line-height 1.35): Blog post titles.
- **Title** (Rajdhani, 600, 0.85rem, letter-spacing 0.04em): Sidebar widget titles, HUD labels.
- **Body** (Exo 2, 400, 0.9rem, line-height 1.75): Article excerpts, bio text, any paragraph content. Max line length should not exceed 75 characters.
- **Label** (Rajdhani, 600, 0.72rem, letter-spacing 0.1em, uppercase): HUD data labels, category tags, eyebrow text.
- **Script** (Great Vibes, 400, clamp(2.4rem, 5.5vw, 4rem)): Hero welcome text only. Never used elsewhere.
- **Editorial** (Noto Serif SC, 300, italic): Quotes and attribution. Serif is justified here because the aesthetic function is genuinely editorial — quoting a historical figure in a reflective moment.

### Named Rules
**The Script Speed Limit.** Great Vibes appears exactly once on the entire page: the hero "Hello". It never leaks into navigation, headlines, buttons, or body copy. One script moment, one page, one message.

**The Italic Descender Rule.** Every italic word containing `y`, `g`, `j`, `p`, or `q` must have `line-height >= 1.5` and `padding-bottom >= 4px` on its containing element. No clipped descenders shipped.

## Layout

The page uses a single-column centered container (max-width 1200px, 28px side padding) over a fixed dark background. The hero breaks out of the container — a full-viewport wallpaper with centered text that makes no reference to the container below until the wave divider pulls the eye down.

Below the wave, the layout alternates: split hero (text left, personal panel right) → three-column stat cards → two-column blog + sidebar grid (1fr + 340px). The sidebar is sticky at `top: 86px` for persistent access.

Spacing rhythm: sections are separated by 40-60px vertical gaps. Cards carry 24-28px internal padding. The nav bar is fixed at 66px, sits above everything at z-index 100, and only becomes visible once the user scrolls past the full-screen hero. The navbar spans the full viewport width (2026-09-10 user's call: brand pinned to the left edge, action cluster to the right edge — no 1200px centering cap), link labels never wrap (white-space: nowrap), and CSS assets carry mtime cache fingerprints (`?v=`) so style updates reach every visitor without manual hard-refresh.

**Data-viz sequential ramp (2026-09-10):** the Dive Calendar heatmap grades post-count into four steps via `--viz-1/2/3` tokens, drawn from the "ice research" palette (a Douyin find, user-submitted). The 4-step blue ramp (#becddc / #90a8c5 / #556c8d / #344c67) passed sequential validation (monotonic lightness, single hue); the palette's pink and lavender were rejected under the One Accent Rule, and the full 7-color set is banned as a *categorical* palette (CVD ΔE 4.3, normal-vision ΔE 5.4 — fails the chart-color floor). Dark theme inverts the ramp (higher count = brighter blue, so hot cells never sink into the background); light theme follows ink convention (higher = darker). Light-theme card surface moved to the palette's ice white #f0f4f8 family.

Breakpoints collapse predictably: the hero panel stacks at 1024px, the sidebar drops below content at 1024px, card grids go 2-column at 768px and single-column at 480px.

## Elevation & Depth

This system uses **glow-based layering**, not shadow-based elevation. Cards rest at a dim border-glow state. On hover, the border glow brightens, a colored box-shadow appears, and the card lifts 2-4px — like a console panel responding to proximity. The ocean orbs (large blurred radial gradients) provide ambient depth behind all content.

### Shadow Vocabulary
- **ambient-sm** (`box-shadow: 0 2px 12px rgba(91, 160, 224, 0.07)`): Default card state. Barely perceptible.
- **ambient-md** (`box-shadow: 0 8px 28px rgba(91, 160, 224, 0.1)`): Sidebar widget hover.
- **ambient-lg** (`box-shadow: 0 16px 44px rgba(91, 160, 224, 0.15)`): Card hover, hero panel hover.
- **glow-signal** (`box-shadow: 0 0 20px rgba(91, 160, 224, 0.2), 0 0 55px rgba(142, 208, 232, 0.08)`): Hero panel at rest, primary button glow. Light as presence, not decoration.

### Named Rules
**The Glow-As-Hierarchy Rule.** Brightness of border glow = importance of element. Dim border = resting container. Bright border + colored shadow = the element is active, interactive, or elevated. Never glow without a reason; glow is the station's way of saying "pay attention."

## Shapes

The shape language follows a documented two-tier system (per taste-skill §4.4):
- **Pill (50px radius):** All interactive elements — buttons, badges, chips, tags, hero eyebrow. The circle-pill shape signals "clickable" or "categorical" at a glance.
- **Soft container (20px radius):** All cards, panels, and sidebar widgets. Soft but not round — containers have presence but don't roll.
- **Medium (14px radius):** Form inputs only.
- **Sharp (8px radius):** Small internal elements only.

No square (0px) elements exist on this page. The station has no sharp corners — everything is softened by the water.

## Components

### Buttons
- **Shape:** Pill (50px radius). Buttons are always round-ended capsules.
- **Primary:** Signal Blue to Haze Cyan gradient background, white text, 8px vertical / 20px horizontal padding. Glow shadow on rest, brightens on hover, lifts 2px.
- **Ghost:** Transparent background, Haze Cyan text, 1px Haze Cyan border at 35% opacity. On hover, background fills to 10% Haze Cyan, border goes solid.
- **Destructive (logout):** Transparent background, Alert Orange text, 1px Alert Orange border. Reserved for sign-out only.
- **Hover/Focus:** All buttons use `transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1)`. Hover lifts 2px; active presses down `scale(0.96)`.

### Chips / Tags
- **Style:** Transparent background with 6% Signal Blue tint, 1px Border Glow Dim border, Text Frost color. Pill shape.
- **Hover:** Background deepens to 13% tint, border brightens, text shifts to Haze Cyan. Lift 2px.

### Cards
- **Corner Style:** 20px radius (soft container tier).
- **Background:** Panel Steel (semi-transparent dark panel with backdrop-blur).
- **Border:** 1px Border Glow Dim at rest, Border Glow Bright on hover.
- **Shadow:** ambient-sm at rest, ambient-lg on hover.
- **Internal Padding:** 24-28px.
- **Decorators:** Corner brackets (::before / ::after pseudo-elements) appear on article cards — subtle sci-fi framing. A glow-line sweeps across the top on hover.

### Inputs / Fields
- **Style:** Transparent background with 3% white fill, 1px subtle border, 14px radius.
- **Focus:** Border shifts to Signal Blue, a soft blue outer glow appears.
- **Dark theme only:** No light-mode inputs exist in this system.

### Navigation
- **Style:** Fixed top bar, 66px height, Panel Steel background with backdrop-blur(22px). Bottom border: 1px Border Glow Dim.
- **Brand:** Shark-fin CSS clip-path logo (36px), Rajdhani brand text.
- **Links:** Rajdhani, 0.9rem, Text Frost. Hover: Haze Cyan with underline animation. Active: subtle background fill.
- **Auth buttons:** Ghost (Sign In) and Primary (Sign Up) at the right edge.
- **Mobile:** Links collapse; auth buttons remain visible.

## Do's and Don'ts

### Do:
- **Do** use Signal Blue as the only accent — one color, one voice, everywhere.
- **Do** glow only when the element needs attention (hover, focus, active status).
- **Do** maintain the shape tier system: pill for interactive, soft-container for cards, medium for inputs.
- **Do** use the wave divider between hero and content — it is a signature, not an afterthought.
- **Do** keep the deep-sea metaphor consistent: no sunny palettes, no warm earth tones (except Alert Orange for alerts).
- **Do** use the corner-bracket decorator pattern on hero and article cards only — don't over-decorate.

### Don't:
- **Don't** introduce a second accent color. No teal badges, no purple hover, no green success states. Signal Blue or nothing.
- **Don't** use pure white (#ffffff) or pure black (#000000). Text Ice and Void Ink are the ceilings.
- **Don't** add more glassmorphism surfaces. Cards and panels only — never on text, never on buttons.
- **Don't** increase border-radius beyond the tier system. No random 32px cards next to 20px cards.
- **Don't** animate continuously without purpose. Pulse = status indicator. Float = deliberate. Infinite-loop micro-animations on static content are noise.
- **Don't** ship a section without its mobile collapse declared. Every multi-column layout must specify its < 768px behavior.

---

# Graphite — 主主题（2026-11-08，用户拍板）

> 上一节「The Deep-Sea Station」描述的是**旧世界**（deepsea / daylight / rain / lain），继续有效。
> 本节描述默认世界 Graphite。实现真理源：[blog/includes/graphite.css](blog/includes/graphite.css)。

## 定位

Graphite 是编辑部式的阅读界面：灰绿纸面、衬线标题、窄正文栏、右侧目录。它不沿用深海隐喻，也不继承旧世界的任何 token。旧世界经「旧版 / VIEW OLD THEME」进入；Lain 仍只认 flag 口令。两世界通过 `html[data-scene]` 切换，互不补丁。

**Key Characteristics:**
- 动效保持克制：仅报头两组一次轻入场，以及 hover/focus 状态反馈；无循环、无逐段正文出现、无滚动视差。
- 单强调色：鼠尾草绿；元信息统一沙色
- 排版优先：正文 820px 阅读栏 + 粘性目录，标题衬线、正文无衬线、代码 Fira Code
- 形状：容器与按钮直角（0），行内代码与输入框 2px，代码窗口 8px——只有代码窗有圆角

## Tokens

| Token | Dark | Light |
|---|---|---|
| `--g-bg` | `#161917` | `#f7f5ef` |
| `--g-raised` | `#1d201e` | `#fcfaf5` |
| `--g-hover` | `#252e28` | `#e9eee5` |
| `--g-rule` / `--g-rule-soft` | `#3b453e` / `#242a26` | `#d6dbd1` / `#ebeee7` |
| `--g-control-rule`（控件边界） | `#68776b` | `#858f80` |
| `--g-ink` | `#dde1db` | `#2c322e` |
| `--g-ink-2` / `--g-ink-3` | `#a8b1aa` / `#848e87` | `#545e57` / `#65706a` |
| `--g-accent`（唯一强调色） | `#9cc5a8` | `#3f6d55` |
| `--g-meta`（日期/编号） | `#c2a878` | `#8a6537` |
| `--g-code-bg` | `#111413` | `#efeee7` |
| `--g-tk-key` | `#7cb7f0` | `#2f5fa8` |
| `--g-tk-str` | `#d69a7a` | `#a14f38` |
| `--g-tk-com` | `#93aa8b` | `#46683e` |
| `--g-tk-num` | `#a9c98f` | `#3c753c` |
| `--g-tk-fn` | `#dcc98a` | `#7a5c26` |

深浅档由 `html[data-g="dark\|light"]` 控制，选择存 localStorage `graphiteMode`。

**报头纸面（2026-11-09）**：首页报头用局部纸面 token，不是全局底色——dark 纸面 `#1e2320`、卡片 `#28322b`；light 纸面 `#f4efe4`、卡片 `#e6ebdf`、沙色元信息 `#7a5b30`。标题字重 500，引文 1.25rem。

## 配色微调（2026-11-09）

- 浅色全局底纸、浮层与代码底纸略暖；报头的既定纸面和卡片局部 token 保持不变。
- 暗色 hover 提亮，主分隔线更清楚；soft 分隔线继续保持克制。装饰线不承担控件识别职责。
- 新增一个辅助 token --g-control-rule，仅用于搜索框、搜索结果面板、深浅模式控件边界；两档控件边界对相邻纸面均不低于 3:1。
- 代码注释在两档分别提高明度或加深；浅色数字随代码底纸微调，确保代码文本不低于 4.5:1。
- 强调色仍只有鼠尾草绿，meta 沙色与字体保持现值。

## Typography

- **标题**：`Noto Serif SC`（回退 Source Han Serif / Songti / SimSun），600
- **正文**：`Segoe UI Variable Text` → `Noto Sans SC` → `PingFang SC` → `Microsoft YaHei`
- **代码/编号**：`Fira Code`（自托管 `assets/fonts/fira-code/`，只在 Graphite 下加载），回退 Cascadia Mono / Consolas
- 中文 Noto 依赖系统安装；Fira Code 随仓库部署，不是系统字体

## Layout

- 外框 `--g-wrap: 1200px`，两侧 28px 内边距
- 首页报头两栏：`1fr / 384px`（左简介+歌词，右 LATEST WORK 卡），< 900px 收单栏
- 文章页：正文 `--g-measure: 820px` + 右侧粘性目录（CONTENTS，240px，间距 80px），目录超高时内部滚动 + 省略号；≤1215px 目录收起
- 正文长串（PATH、长行内代码）允许任意断行（`overflow-wrap:anywhere`）；`pre` 与表格保留各自的横向滚动
- 顶栏：≤1120px 品牌收短、导航收进 MENU 下拉（761–1120px 显示 MENU；≤760px 保持紧凑顶栏，不显示导航）；≤760px 收起 OLD 按钮，页脚的「VIEW OLD THEME」始终保留
- 代码窗口：`data-lang` 语言标签 + Mac 三色圆点（`#ff5f57` `#febc2e` `#28c840`），横向滚动不换行

## 交互微过渡（2026-11-09）

- Graphite 使用 `--g-dur:160ms` 与 `--g-ease:cubic-bezier(.2,0,0,1)`。
- 主要覆盖交互元素及其随状态变色的标题；常规过渡为 color、background-color、border-color、text-decoration-color、opacity。导航下划线、主推卡顶边和友链箭头的 transform 不移动布局。
- 根节点、body 不做主题过渡；主题按钮处于 hover / active / focus-visible 时关闭 Graphite 内容的过渡，深浅切换瞬时生效。
- prefers-reduced-motion:reduce 下，Graphite 页面及其后代、生成的伪元素全部 transition:none / animation:none；报头保持可见、无位移，交互标记瞬时切换。旧世界不受影响。

## 报头入场与交互位移（2026-11-09）

- 报头左简介组与右文章卡在每次首页加载时入场一次：opacity 与 translateY(8px)，440ms，右组延迟 60ms；总长 500ms。
- 由 CSS 在 `html.js` 条件下触发（head 内联脚本在样式之前挂上 .js）；无 JS 时不触发、内容默认可见，不依赖 FCP/IO 计时。
- reduce 下 animation:none；切换场景不会重复播放。
- 导航 ::after 下划线以 scaleX 展开，160ms；友链独立箭头向右 2px，160ms。鼠标与键盘焦点同样反馈，正文与交互区域本身不移动。
- 首页主推卡在悬停或键盘聚焦时，以鼠尾草绿描亮顶边一次（440ms）。减少动态效果时边线直接亮起。
- 主题切换继续瞬时生效；不使用 rAF 循环和 scroll 监听器。reduce 下动画与过渡全部关闭。
- 首页与文章页 html 提供 data-scene="graphite" / data-g="dark" 静态默认值，无 JS 时 Graphite 内容可读且旧 preloader 让位；theme-init 仍在 head 原位置同步覆盖保存偏好，不改变双标记结构。

## 阅读进度条（2026-11-09）

- 文章页顶部 2px 鼠尾草绿进度条，`animation-timeline: scroll(root block)` 纯 CSS 驱动，无 JS、无 scroll 监听。
- 仅在 `@supports (animation-timeline: scroll(root block))` 下渲染；不支持的浏览器不显示，不影响布局。
- reduced-motion 下随全局规则关闭。

## 代码复制（2026-11-09）

- 每个 `pre[data-lang]` 由 JS 包一层 `.g-codewrap`，右上角放 COPY 按钮；按钮不随代码横向滚动移动，语言标签相应左移。
- 复制成功显示 COPIED 1.2s；Clipboard API 不可用时回退 textarea + execCommand；失败显示 FAILED。
- 按钮默认 opacity .55，hover / focus-within 提到 1；无 JS 时不生成按钮，代码块保持原样。

## 目录当前位置标记（2026-11-09）

- 目录当前项保留变色，并在固定槽位显示 2px 鼠尾草绿短线；主级与子级文字位置保持不变。
- 短线通过 ::before 实现，仅 opacity 做 100ms 过渡，不滑动；reduced-motion 下 transition:none。
- 目录底部 26px 渐隐（mask-image）提示可滚动；底部留白 28px，滚到末端时末项不被遮。
- 继续使用原有 is-active / aria-current，不改变目录检测逻辑、内部滚动、隐藏滚动条及底部留白。

## 荣誉账本 HONORS（2026-11-09）

- 数据来源为 blog/honors.json：数组，字段 date（YYYY-MM）、title、rank、note（可空）；仅迁移已有荣誉事实。bio 仅删除迁出的对应整行。
- 首页 #g-honors 位于 COLLECTIONS 与 PROJECTS 之间；使用 g-sec 标题与 ledger 分隔线，日期 / 赛事名 / 名次三列，日期等宽，名次使用 --g-meta。
- ≤760px 时排列为日期与名次一行、赛事名下一行；可选 note 位于赛事名下，无内容时不输出。
- 荣誉名次按用户指定显示为 3rd 🥉；赛事名称、日期与事实保持原值。
- 列表按 date 倒序排列（YYYY-MM 字符串比较）；导航与首页共用有效数据；有效条目为零时整段与 HONORS 导航入口均不输出。动态文本统一 htmlspecialchars 转义。

## 友链 FRIENDS（2026-11-09）

- blog/friends.json 是两套首页共用的唯一友链数据源：数组，字段 name、url、desc（可空）。现为三条（Sora大佬 / Moejue / 先知社区）；GitHub 与 Bilibili 按用户决定移除，不补写描述。
- index.php 读取并筛选一次，只保留有效 http/https URL；动态文本与 URL 使用 htmlspecialchars 转义。两处外链均使用 target="_blank" rel="noopener noreferrer"。
- Graphite 首页 #g-friends 位于 ABOUT 之后，桌面每行两条友链，列间距 32px；每条内部使用名称 / 域名或描述 / ↗ 的行式布局，padding 12px 10px。≤760px 收为单列，名称与箭头一行、详情下一行，padding 10px 4px、行内间距 2px；长串允许断行。名称 .97rem、详情 .82rem，沿用正文栈与现有颜色 token。
- 悬停和键盘焦点复用 160ms 微过渡：名称变色并显示下划线，独立箭头右移 2px；reduce 下取消位移。旧侧栏保留原 friend-links 样式，只更换数据循环。
- 有效数据为空时，Graphite 区块与旧侧栏对应整个 widget 均不输出。FRIENDS 不新增顶栏导航入口。

## 作用域纪律

石墨规则一律以 `html[data-scene="graphite"]` 开头，并且只挂在 `.g-home` / `.g-post` / `body.g-page` 上；旧页面（about/gallery/timeline/terms/login）不受影响。旧世界的 preloader、粒子海洋、navbar、页脚在石墨页面里全部 `display:none`（见 graphite.css 第 54–70 行）。
