<?php
/**
 * Template Name: Products Page
 *
 * 商品列表页：头图 + Vue 应用 + 底部 CTA。
 * 三部分文案都在后台该页面的「页面文案」面板里编辑；
 * 商品本身由 Vue 通过 REST API 读取后台「商品」。
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main">

	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<?php get_template_part( 'template-parts/page-hero' ); ?>

		<div id="product-app"></div>
		<?php
		/*
		 * 服务端渲染的商品清单（noscript）：确保爬虫与无 JS 访客仍能看到全部商品链接。
		 * 见 docs/AUDIT-REPORT.md P1-1。
		 */
		get_template_part( 'template-parts/catalog-noscript' );
		?>

		<?php get_template_part( 'template-parts/page-cta' ); ?>

		<?php
	endwhile;
	?>

</main><!-- #main -->

<?php
get_footer();
