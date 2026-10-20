<?php
/**
 * Template Name: Contact Inquiry Page
 */
get_header();
?>

<main id="primary" tabindex="-1" class="site-main">


    


  <section class="page-hero">
    <div class="page-hero-inner">
      <p class="eyebrow">TUSKOLIFT / Sales Inquiry</p>
      <h1>Tell us what you need and we will guide the next step.</h1>
      <p>Share the load, quantity, environment and operating requirements so we can recommend the most suitable equipment faster.</p>
    </div>
  </section>

  <section class="section" id="inquiry">
    <div class="section-inner">
      <div class="contact-band">
        <div>
          <p class="eyebrow">Start a conversation</p>
          <h2>We are ready to help with your material handling needs.</h2>
          <p>Tell us the load, lift height and working environment. We will come back with a specification recommendation and an indicative price for your warehouse or industrial project.</p>
          <p id="inquiry-contact"><strong>Sales &amp; Engineering</strong><br>Use the form on this page to reach our team.<br>Mon–Sat, 08:30–18:00 (GMT+8)</p>
        </div>
        <?php
        // 询盘表单由 WPForms 渲染（表单 ID 9「Contact Inquiry」）。
        tuskolift_render_inquiry_form( TUSKOLIFT_FORM_CONTACT, '联系表单' );
        ?>
      </div>
    </div>
  </section>








<?php get_footer(); ?>
