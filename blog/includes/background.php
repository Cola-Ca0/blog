<?php
/**
 * Background Module — Grid + Orbs + Scanlines, single source
 * Interface: just require it. Outputs 3 background div layers.
 */
?>
<div class="bg-layers">
  <div class="bg-grid"></div>
  <div class="bg-orbs">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
  </div>
  <!-- 雨窗场景暖光灯罩 (2026-08-27; 仅 scene=rain 时 opacity:1) -->
  <div class="bg-warmglow"></div>
  <div class="scanlines"></div>
</div>

<!-- 隐藏场景 Lain 图层 (2026-11-02; 仅 scene=lain 时可见, 其余场景不显示) -->
<div class="lain-atmo" aria-hidden="true"></div>
<div class="lain-wires" aria-hidden="true"></div>
<!-- 2026-11-03 PHOSPHOR: 0101 / 代码雨背景 (Canvas, 仅 lain 场景绘制) -->
<canvas class="lain-rain" id="lainRain" aria-hidden="true"></canvas>
<div class="lain-crt" aria-hidden="true"></div>
<!-- 角落 HUD: 层号 / 链路地址 / 实时时钟 (时钟由 lain-rain.js 驱动) -->
<div class="lain-hud" aria-hidden="true">LAYER 07 · 0x7F3A · <b id="lainHudClock">--:--:--</b> · <i>LINK STABLE</i></div>
<script src="<?= $BASE ?>/js/lain-rain.js" defer></script>
