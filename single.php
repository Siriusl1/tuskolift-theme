<?php
/**
 * 文章详情页
 *
 * 标题、正文、特色图片、分类、标签全部来自后台「文章」编辑器。
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main">

	<?php
	while ( have_posts() ) :
		the_post();

		$tk_categories = get_the_category();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'tk-post' ); ?>>

			<header class="tk-post-hero">
				<div class="tk-post-hero-inner">
					<nav class="tk-post-breadcrumb" aria-label="Breadcrumb">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
						<span>/</span>
						<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
						<?php if ( $tk_categories ) : ?>
							<span>/</span>
							<a href="<?php echo esc_url( get_category_link( $tk_categories[0]->term_id ) ); ?>"><?php echo esc_html( $tk_categories[0]->name ); ?></a>
						<?php endif; ?>
					</nav>

					<?php if ( $tk_categories ) : ?>
						<p class="eyebrow"><?php echo esc_html( $tk_categories[0]->name ); ?></p>
					<?php endif; ?>

					<h1><?php the_title(); ?></h1>

					<p class="tk-post-meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( tuskolift_date_en( 'j F Y' ) ); ?></time>
						<?php if ( get_the_author() ) : ?>
							<span>·</span>
							<span><?php echo esc_html( get_the_author() ); ?></span>
						<?php endif; ?>
					</p>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="tk-post-thumb">
					<?php the_post_thumbnail( 'large' ); ?>
				</figure>
			<?php endif; ?>

			<div class="tk-post-content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'tuskolift' ),
						'after'  => '</div>',
					)
				);
				?>
			</div>

			<?php
			$tk_tags = get_the_tags();
			if ( $tk_tags ) :
				?>
				<div class="tk-post-tags">
					<?php foreach ( $tk_tags as $tk_tag ) : ?>
						<a href="<?php echo esc_url( get_tag_link( $tk_tag->term_id ) ); ?>"><?php echo esc_html( $tk_tag->name ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<footer class="tk-post-footer">
				<?php
				the_post_navigation(
					array(
						'prev_text' => '<span class="tk-nav-label">Previous</span><span class="tk-nav-title">%title</span>',
						'next_text' => '<span class="tk-nav-label">Next</span><span class="tk-nav-title">%title</span>',
					)
				);
				?>
			</footer>

		</article>

		<?php
		// 同分类的其它文章。
		if ( $tk_categories ) :
			$tk_related = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'posts_per_page'      => 3,
					'ignore_sticky_posts' => true,
					'post__not_in'        => array( get_the_ID() ),
					'category__in'        => wp_list_pluck( $tk_categories, 'term_id' ),
				)
			);

			if ( $tk_related->have_posts() ) :
				?>
				<section class="tk-related">
					<h2 class="tk-related-title">More from <?php echo esc_html( $tk_categories[0]->name ); ?></h2>
					<div class="grid-3">
						<?php
						while ( $tk_related->have_posts() ) :
							$tk_related->the_post();
							get_template_part( 'template-parts/news-card' );
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
		if ( comments_open() || get_comments_number() ) :
			comments_template();
		endif;
		?>

		<?php
	endwhile;
	?>

</main><!-- #main -->

<?php
get_footer();
