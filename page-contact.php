<?php
/**
 * Template Name: Contact Page
 *
 * 联系我们页面（/contact/）。
 *
 * 位置说明（本轮从 template-parts/ 迁到根目录）：
 *   这个模板原本在 template-parts/page-contact.php，页面 #21 的
 *   _wp_page_template 直接存了 "template-parts/page-contact.php" 这个子目录路径。
 *   WordPress 的 locate_template() 确实能加载子目录里的文件，所以它一直能用，
 *   但有两个副作用：
 *     1. 不符合惯例 —— 找 /contact/ 的模板时，在主题根目录看不到 page-contact.php；
 *     2. 子目录里的 "Template Name" 不会出现在后台「页面模板」下拉框里。
 *   现已移到根目录，_wp_page_template 同步改成 page-contact.php。
 *   template-parts/page-contact.php 保留为转发文件，避免两份代码分叉。
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main">
    <section class="page-hero">
    <div class="page-hero-inner">
      <p class="eyebrow">TUSKOLIFT / Contact</p>
      <h1>Bring us your next precision challenge.</h1>
      <p>Share the basics of your project and give your sales or engineering team a clear starting point.</p>
    </div>
  </section>

  <!-- 咨询表单区（Inquiry）：包含联系信息和询价表单，id="inquiry" 供锚点跳转 -->
  <section class="section" id="inquiry">
    <div class="section-inner">
      <div class="contact-band">
        <div>
          <p class="eyebrow">Start a conversation</p>
          <h2>Tell us what you are building.</h2>
          <p>Send us your application details and our engineering team will reply with a specification recommendation within one business day.</p>
          <p id="contact-sales"><strong>Sales &amp; Engineering</strong><br>Use the form on this page to reach our team.<br>Mon–Sat, 08:30–18:00 (GMT+8)</p>
        </div>
        <?php
        /*
         * 询盘表单 —— 由 WPForms 渲染（表单 ID 9「Contact Inquiry」）。
         * 要改字段、改收件邮箱、改确认语，都在后台「WPForms → 所有表单」里改，
         * 不需要动这个模板文件。
         */
        tuskolift_render_inquiry_form( TUSKOLIFT_FORM_CONTACT, '联系表单' );
        ?>
      </div>
    </div>
  </section>

  <!-- 全球支持区（Global Support）：展示三个地点的团队与职能 -->
  <section class="section">
    <div class="section-inner">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Global support</p>
          <h2>One team, wherever production happens.</h2>
        </div>
        <p>Headquarters, production and export support are handled by a single team, so your inquiry never gets lost between departments.</p>
      </div>
      <div class="grid-3">
        <div class="panel">
          <span class="number">01</span>
          <h3>Engineering &amp; Manufacturing</h3>
          <p>R&amp;D, sales and manufacturing headquarters. Product design, welding, assembly and inspection take place on one site.</p>
        </div>
        <div class="panel">
          <span class="number">02</span>
          <h3>Component Supply</h3>
          <p>Production support for hydraulics, fasteners and precision parts, with incoming inspection before assembly.</p>
        </div>
        <div class="panel">
          <span class="number">03</span>
          <h3>Export &amp; After-Sales</h3>
          <p>Export documentation, spare-parts kits and technical support for distributors in more than 60 countries.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
