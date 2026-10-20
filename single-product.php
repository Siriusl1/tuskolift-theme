<?php
/**
 * 商品详情页模板
 *
 * 所有内容均来自后台「商品」：
 *   - 标题 / 正文（区块编辑器）/ 摘要 / 特色图片 / 产品线 / 应用场景
 *   - 规格参数与产品特点来自 meta box（inc/tk-fields.php）
 *
 * @package TUSKOLIFT
 */

get_header();

while ( have_posts() ) :
	the_post();

	$product_id   = get_the_ID();
	$specs        = tuskolift_get_specs( $product_id );
	$features     = tuskolift_get_features( $product_id );
	$product_line = get_the_terms( $product_id, 'product_line' );
	$applications = get_the_terms( $product_id, 'application' );
	$line_name    = ( $product_line && ! is_wp_error( $product_line ) ) ? $product_line[0]->name : '';
	$excerpt      = get_the_excerpt();
	?>

	<main id="primary" tabindex="-1" class="site-main">

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'npd-pd' ); ?>>

			<section class="npd-pd-hero">
				<div class="npd-pd-hero-inner">
					<nav class="npd-pd-breadcrumb" aria-label="Breadcrumb">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
						<span>/</span>
						<a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">Products</a>
						<?php if ( $line_name && $product_line && ! is_wp_error( $product_line ) ) : ?>
							<span>/</span>
							<a href="<?php echo esc_url( get_term_link( $product_line[0] ) ); ?>"><?php echo esc_html( $line_name ); ?></a>
						<?php endif; ?>
						<span>/</span>
						<span class="npd-pd-breadcrumb-current"><?php the_title(); ?></span>
					</nav>

					<?php if ( $line_name ) : ?>
						<p class="npd-eyebrow"><?php echo esc_html( $line_name ); ?></p>
					<?php endif; ?>

					<h1><?php the_title(); ?></h1>

					<?php if ( $excerpt ) : ?>
						<p class="npd-pd-lead"><?php echo esc_html( wp_strip_all_tags( $excerpt ) ); ?></p>
					<?php endif; ?>

					<?php if ( $applications && ! is_wp_error( $applications ) ) : ?>
						<div class="npd-pd-tags">
							<?php foreach ( $applications as $application ) : ?>
								<a class="npd-pd-tag" href="<?php echo esc_url( get_term_link( $application ) ); ?>"><?php echo esc_html( $application->name ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<section class="npd-pd-body">
				<div class="npd-pd-media">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large', array( 'class' => 'npd-pd-image' ) ); ?>
					<?php else : ?>
						<div class="npd-pd-image npd-pd-image-placeholder">
							<span>No image yet — set a Featured Image for this product.</span>
						</div>
					<?php endif; ?>
				</div>

				<div class="npd-pd-info">
					<?php if ( $specs ) : ?>
						<h2 class="npd-pd-subtitle">Specifications</h2>
						<table class="npd-pd-specs">
							<tbody>
								<?php foreach ( $specs as $spec ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( $spec['label'] ); ?></th>
										<td><?php echo esc_html( $spec['value'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if ( $features ) : ?>
						<h2 class="npd-pd-subtitle">Key Features</h2>
						<ul class="npd-pd-features">
							<?php foreach ( $features as $feature ) : ?>
								<li><?php echo esc_html( $feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( ! $specs && ! $features ) : ?>
						<p class="npd-pd-hint">
							Specifications and key features have not been added yet. Fill them in from the product editor under “Product details: specifications / features”.
						</p>
					<?php endif; ?>

					<div class="npd-pd-actions">
						<a class="npd-pd-btn npd-pd-btn-primary" href="<?php echo esc_url( home_url( '/contact/#inquiry' ) ); ?>">REQUEST A QUOTE</a>
						<a class="npd-pd-btn npd-pd-btn-secondary" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">BACK TO CATALOG</a>
					</div>
				</div>
			</section>

			<?php if ( trim( get_the_content() ) ) : ?>
				<section class="npd-pd-content">
					<div class="npd-pd-content-inner">
						<?php the_content(); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php
			// 同产品线的其它商品。
			if ( $product_line && ! is_wp_error( $product_line ) ) :
				$related = new WP_Query(
					array(
						'post_type'           => 'product',
						'posts_per_page'      => 3,
						'post__not_in'        => array( $product_id ),
						'ignore_sticky_posts' => true,
						'tax_query'           => array(
							array(
								'taxonomy' => 'product_line',
								'field'    => 'term_id',
								'terms'    => wp_list_pluck( $product_line, 'term_id' ),
							),
						),
					)
				);

				if ( $related->have_posts() ) :
					?>
					<section class="npd-pd-related">
						<h2 class="npd-pd-related-title">More in <?php echo esc_html( $line_name ); ?></h2>
						<div class="tk-product-grid">
							<?php
							while ( $related->have_posts() ) :
								$related->the_post();
								get_template_part( 'template-parts/product-card' );
							endwhile;
							?>
						</div>
					</section>
					<?php
				endif;

				wp_reset_postdata();
			endif;
			?>

			<?php
			$products_page = get_page_by_path( 'products' );

			if ( $products_page ) {
				get_template_part( 'template-parts/page-cta', null, array( 'post_id' => $products_page->ID ) );
			}
			?>

		</article>

	</main><!-- #main -->

	<?php
endwhile;

get_footer();
