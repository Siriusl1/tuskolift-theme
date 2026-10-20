<?php
/**
 * 产品线归档页（/product_line/{slug}/）
 *
 * 标题、导语、配图全部来自后台「商品 → 产品线」里的术语名称 / 描述 / 分类配图。
 *
 * @package TUSKOLIFT
 */

get_header();

$tk_term = get_queried_object();
?>

<main id="primary" tabindex="-1" class="site-main">

	<section class="page-hero">
		<div class="page-hero-inner">
			<p class="eyebrow">Product Line</p>
			<h1><?php echo esc_html( $tk_term->name ); ?></h1>
			<?php if ( $tk_term->description ) : ?>
				<p><?php echo esc_html( $tk_term->description ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="section">
		<div class="section-inner">
			<?php if ( have_posts() ) : ?>

				<div class="tk-product-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/product-card' );
					endwhile;
					?>
				</div>

				<div class="tk-product-card-actions">
					<a class="text-link" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">← BACK TO FULL CATALOG</a>
				</div>

			<?php else : ?>

				<p class="tk-empty">No products in this product line yet. Publish a product and assign it to “<?php echo esc_html( $tk_term->name ); ?>”.</p>

				<div class="tk-product-card-actions">
					<a class="text-link" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">← BACK TO FULL CATALOG</a>
				</div>

			<?php endif; ?>
		</div>
	</section>

</main><!-- #main -->

<?php
get_footer();
