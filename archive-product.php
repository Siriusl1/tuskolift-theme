<?php
/**
 * 商品归档页（/product/）
 *
 * 与 /products/ 页面共用同一套头图/CTA 文案（取自「产品」页面的页面文案字段），
 * 商品列表同样交给 Vue 渲染。
 *
 * @package TUSKOLIFT
 */

get_header();

$tk_products_page = get_page_by_path( 'products' );
$tk_page_id       = $tk_products_page ? $tk_products_page->ID : 0;
?>

<main id="primary" tabindex="-1" class="site-main">

	<?php if ( $tk_page_id ) : ?>

		<section class="page-hero">
			<div class="page-hero-inner">
				<?php
				$tk_eyebrow = tuskolift_get_page_copy( '_tk_hero_eyebrow', $tk_page_id );
				$tk_title   = tuskolift_get_page_copy( '_tk_hero_title', $tk_page_id );
				$tk_intro   = tuskolift_get_page_copy( '_tk_hero_intro', $tk_page_id );

				if ( $tk_eyebrow ) :
					?>
					<p class="eyebrow"><?php echo esc_html( $tk_eyebrow ); ?></p>
				<?php endif; ?>

				<?php if ( $tk_title ) : ?>
					<h1><?php echo esc_html( $tk_title ); ?></h1>
				<?php endif; ?>

				<?php if ( $tk_intro ) : ?>
					<p><?php echo esc_html( $tk_intro ); ?></p>
				<?php endif; ?>
			</div>
		</section>

	<?php endif; ?>

	<div id="product-app"></div>
		<?php
		/*
		 * 服务端渲染的商品清单（noscript）：确保爬虫与无 JS 访客仍能看到全部商品链接。
		 * 见 docs/AUDIT-REPORT.md P1-1。
		 */
		get_template_part( 'template-parts/catalog-noscript' );
		?>

	<?php
	if ( $tk_page_id ) {
		get_template_part( 'template-parts/page-cta', null, array( 'post_id' => $tk_page_id ) );
	}
	?>

</main><!-- #main -->

<?php
get_footer();
