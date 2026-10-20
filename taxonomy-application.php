<?php
/**
 * 应用场景归档页（/application/{slug}/）
 *
 * 标题与导语来自后台「商品 → 应用场景」里的术语名称 / 描述。
 *
 * @package TUSKOLIFT
 */

get_header();

$tk_term = get_queried_object();
?>

<main id="primary" tabindex="-1" class="site-main">

	<section class="page-hero">
		<div class="page-hero-inner">
			<p class="eyebrow">Application</p>
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

				<p class="tk-empty">No products assigned to this application yet. Edit a product and tick this application.</p>

				<div class="tk-product-card-actions">
					<a class="text-link" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">← BACK TO FULL CATALOG</a>
				</div>

			<?php endif; ?>
		</div>
	</section>

</main><!-- #main -->

<?php
get_footer();
