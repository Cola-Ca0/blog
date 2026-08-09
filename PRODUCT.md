# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

PHP (Laragon local server), inline CSS/JS, no framework, no Node.js dependency. Static HTML augmented with PHP for auth and dynamic content.

## Users

Multiple audiences, served by a single personal blog:

- **Self (primary)** — knowledge base: document learning, organize technical knowledge, track CTF/security progress
- **Potential employers & mentors** — evaluate technical ability and thinking depth before an interview or offer
- **CTF / security community** — peers in the cybersecurity track, potential teammates, SRC collaborators
- **Classmates & friends** — people who know Cola_CaO personally, share anime/2D culture interests, follow life updates

## Product Purpose

Cola_CaO's personal blog is the single surface where technical learning, security exploration, and personal life converge. It serves as a living portfolio, a knowledge repository, and a social signal — all in one place.

Success means: a visitor leaves understanding (a) what Cola_CaO is capable of technically, (b) what they care about, and (c) that this is a real person with depth, not a resume keyword list.

## Positioning

A personal panorama blog — not pure technical tutorials, not pure lifestyle. The blog blends CTF writeups and security learning with personal essays and anime culture, showing the complete person behind the code. The Gura/ocean aesthetic is a deliberate identity signal: this is a tech blog that refuses to look like every other tech blog.

## Operating Context

- The user is a CS freshman at Hangzhou Normal University (杭州师范大学), class of 2024
- Focus track: network security (网络空间安全), CTF + SRC vulnerability hunting
- Learning path: Java, PHP, Web security, Kali Linux, Burp Suite
- Tools: Laragon + VS Code + Continue (DeepSeek API), Obsidian for knowledge management
- Hardware: ASUS Tianshuang 4 gaming laptop, plans to add a lightweight Kali laptop
- Currently in summer vacation before university, building foundations

## Capabilities and Constraints

**Confirmed capabilities:**
- Blog post listing with category/date metadata
- User registration and login (PHP sessions, bcrypt, math CAPTCHA)
- Full-screen wallpaper hero with animated wave transition
- Personal info panel with skill bars and social links
- Tag cloud and archive sidebar
- Gura-themed dark ocean aesthetic with sci-fi HUD elements

**Technical constraints:**
- Must stay on Laragon/PHP stack; no Node.js, no npm, no React/Next.js
- All CSS must be inline or self-contained (no build pipeline)
- Deployed locally for now; remote deployment may come later

**Undecided:**
- Comment system (currently none, could add via PHP)
- Admin panel for posting articles (currently HTML hardcoded)
- Static page generation vs dynamic PHP routing

## Brand Commitments

- **Name:** Cola_CaO (可乐)
- **Mascot/Theme:** Gawr Gura (hololive EN) — ocean/深海 aesthetic
- **Color identity:** Ocean blue palette with coral orange accent, dark backgrounds
- **Typography:** Rajdhani (display), Exo 2 (body), Great Vibes (hero script), Noto Serif SC (editorial)
- **Voice:** Technical but approachable, reflective, occasionally playful — a CS student who loves anime, not a corporate engineer
- **Binding references:** moejue.cn (anime blog aesthetic), boxmoe.com (wave transition), blog-style-18 (sci-fi HUD elements)

## Evidence on Hand

- Real user profile with confirmed identity, school, and study direction
- Existing wallpaper image at `blog/assets/images/wallpaper.jpg` (user-provided, 1920x960)
- Existing avatar slots at `blog/assets/images/gura-avatar.jpg` and `blog/assets/images/my-avatar.jpg` (pending user upload)
- Existing PHP codebase: `blog/index.php`, `blog/login.php`, `blog/users.json`
- No fabricated testimonials, no fake metrics (mock data explicitly labeled in code comments per taste-skill §4.9)

## Product Principles

1. **One surface, many audiences.** The blog must work for a potential employer scanning in 30 seconds AND a friend catching up on life. No audience gets a separate silo; they get different layers of the same page.

2. **Real over polished.** Show actual learning progress, not artificial expertise. A CTF writeup admitting "I got stuck here for 3 hours" is more valuable than pretending to be an expert. Authenticity wins over curation.

3. **Identity as infrastructure.** The Gura/ocean theme is not decorative — it signals that this is a personal space, not a corporate blog. The aesthetic is a commitment, not a skin.

4. **Keep the stack boring.** PHP on Laragon is the constraint. Add complexity only when the current tool genuinely blocks a real need. A fast, simple blog that exists beats an ambitious one that never ships.

5. **Knowledge wants to be public.** Learning in public (CTF writeups, code walkthroughs, study notes) compounds: it helps the writer understand, helps the reader learn, and proves capability to employers. Every piece of learning should default to published.

## Accessibility & Inclusion

- Chinese (Simplified) primary language with English elements
- Dark mode only (consistent with ocean/sci-fi aesthetic)
- Reduced motion support via `prefers-reduced-motion` (per taste-skill §6.B)
- WCAG AA contrast target for all interactive elements (per taste-skill §4.5)
