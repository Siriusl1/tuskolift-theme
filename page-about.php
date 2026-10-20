<?php
/**
 * Template Name: About Page
 */
get_header();
?>

<main id="primary" tabindex="-1" class="site-main">


    

<!-- ===== 主要页面内容（仅此区域添加了中文注释） ===== -->

  <!-- 英雄区（Hero）：页面顶部大标题与导语 -->
  <section class="page-hero">
    <div class="page-hero-inner">
      <p class="eyebrow">TUSKOLIFT / About</p>
      <h1>A manufacturing partner that stays close to the detail.</h1>
      <p>TUSKOLIFT designs and manufactures pallet trucks, lift tables, stackers and custom material handling equipment for distributors, warehouses and industrial buyers in more than 60 countries.</p>
    </div>
  </section>

  <!-- 公司简介区（Media Band）：展示成立年份、经验及公司描述，id="company" 供锚点跳转 -->
  <section class="media-band" id="company">
    <div class="block">
      <p class="eyebrow">Since 2015</p>
      <h2>16+</h2>
      <p>years of material handling manufacturing experience.</p>
    </div>
    <div>
      <p class="eyebrow">Our company</p>
      <h2>Engineering precision into every connection.</h2>
      <p>We started as a small hydraulic equipment workshop and now run in-house fabrication, welding, painting and assembly lines. Our engineering team handles OEM and ODM projects from drawing review to first-article approval, so custom units go from concept to serial production without switching suppliers.</p>
      <a class="text-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">TALK TO OUR TEAM</a>
    </div>
  </section>

  <!-- 质量保证区（Quality Section）：展示四个核心价值观，id="quality" 供锚点跳转 -->
  <section class="section" id="quality">
    <div class="section-inner">
      <div class="section-heading">
        <div>
          <p class="eyebrow">What we stand for</p>
          <h2>Capability is useful when it is dependable.</h2>
        </div>
        <p>Four commitments that decide whether a unit still performs after its third year in a warehouse.</p>
      </div>
      <div class="grid-4">
        <div class="panel">
          <span class="number">01</span>
          <h3>Traceability</h3>
          <p>Documented processes from material receipt to final shipment.</p>
        </div>
        <div class="panel">
          <span class="number">02</span>
          <h3>Consistency</h3>
          <p>Stable tooling, inspection and production planning at scale.</p>
        </div>
        <div class="panel">
          <span class="number">03</span>
          <h3>Collaboration</h3>
          <p>Early engineering input helps prevent costly downstream changes.</p>
        </div>
        <div class="panel">
          <span class="number">04</span>
          <h3>Responsibility</h3>
          <p>Quality, safety and responsible operations stay visible in daily work.</p>
        </div>
      </div>
    </div>
  </section>








<?php get_footer(); ?>
