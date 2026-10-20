<?php
/**
 * Template Name: Offerings Page
 */
get_header();
?>

<main id="primary" tabindex="-1" class="site-main">


    

<!-- ===== 主要页面内容（仅此区域添加了中文注释） ===== -->

  <!-- 英雄区（Hero）：页面顶部大标题与导语 -->
  <section class="page-hero">
    <div class="page-hero-inner">
      <p class="eyebrow">TUSKOLIFT / Offerings</p>
      <h1>From first prototype to global delivery.</h1>
      <p>Present the manufacturing systems and industry support that make your business easier to scale.</p>
    </div>
  </section>

  <!-- 制造系统区（Manufacturing System）：展示四大制造能力，id="manufacturing" 供锚点跳转 -->
  <section class="section" id="manufacturing">
    <div class="section-inner">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Manufacturing system</p>
          <h2>One connected production journey.</h2>
        </div>
        <p>These capability blocks can become dedicated pages for workshops, equipment and testing.</p>
      </div>
      <div class="grid-4">
        <!-- 能力卡片 01：冷镦成型 -->
        <div class="panel">
          <span class="number">01 / FORMING</span>
          <h3>Cold Heading</h3>
          <p>High-volume forming for repeatable fastener geometry and strength.</p>
        </div>
        <!-- 能力卡片 02：凸轮车削 -->
        <div class="panel">
          <span class="number">02 / TURNING</span>
          <h3>Cam Lathe</h3>
          <p>Efficient turned parts with tight dimensional control.</p>
        </div>
        <!-- 能力卡片 03：级进冲压 -->
        <div class="panel">
          <span class="number">03 / STAMPING</span>
          <h3>Progressive Stamping</h3>
          <p>Servo press lines for complex parts and demanding output.</p>
        </div>
        <!-- 能力卡片 04：精密实验室 -->
        <div class="panel">
          <span class="number">04 / TESTING</span>
          <h3>Precision Lab</h3>
          <p>Inspection plans that make quality visible at every stage.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- 行业解决方案区（Industry Solutions）：选项卡切换不同行业，id="solutions" 供锚点跳转 -->
  <section class="section" id="solutions">
    <div class="section-inner">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Industry solutions</p>
          <h2>Hardware designed around the application.</h2>
        </div>
        <p>Switch the labels and copy below for your target markets.</p>
      </div>
      <!-- 选项卡按钮组：每个按钮携带 data-tab 属性，内容为 JSON 格式的行业信息 -->
      <div class="tabs">
        <button class="active" data-tab='{"number":"01 / 04","title":"Notebook","description":"Precision screws, brass inserts and structural hardware for compact, high-performance devices."}'>Notebook</button>
        <button data-tab='{"number":"02 / 04","title":"Energy Storage","description":"Reliable bolts, nuts, studs and inserts for energy storage connections."}'>Energy Storage</button>
        <button data-tab='{"number":"03 / 04","title":"New Energy Vehicle","description":"High-strength fasteners and turned components for demanding vehicle assemblies."}'>New Energy Vehicle</button>
        <button data-tab='{"number":"04 / 04","title":"Telecom & Networking","description":"Reliable screws, cage nuts and precision parts for connected infrastructure."}'>Telecom &amp; Networking</button>
      </div>
      <!-- 选项卡内容区：显示当前选中行业的详细信息 -->
      <div class="tab-content">
        <span data-tab-number>01 / 04</span>
        <h3 data-tab-title>Notebook</h3>
        <p data-tab-description>Precision screws, brass inserts and structural hardware for compact, high-performance devices.</p>
      </div>
    </div>
  </section>








<?php get_footer(); ?>
