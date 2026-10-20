<?php
/**
 * 搜索结果页
 *
 * 改写了 Underscores 的默认版本：使用站点自己的 .page-hero / .section /
 * .news-card 体系，结果卡片统一显示「内容类型 + 日期」，并复用站点的搜索表单。
 *
 * @package TUSKOLIFT
 */

get_header();

global $wp_query;

$tk_total = (int) $wp_query->found_posts;
$tk_query = get_search_query();
?>

<main id="primary" tabindex="-1" class="site-main">

	<section class="page-hero">
		<div class="page-hero-inner">
			<p class="eyebrow">Search</p>

			<?php if ( $tk_query ) : ?>
				<h1>Results for “<?php echo esc_html( $tk_query ); ?>”</h1>
			<?php else : ?>
				<h1>What are you looking for?</h1>
			<?php endif; ?>

			<p>
				<?php
				if ( $tk_total > 0 ) {
					printf(
						/* translators: %d: number of results. */
						esc_html( _n( '%d result found.', '%d results found.', $tk_total, 'tuskolift' ) ),
						$tk_total
					);
				} else {
					echo 'No matches found — try a different keyword, or use the links below.';
				}
				?>
			</p>

			<div class="tk-search-hero-form">
				<?php get_template_part( 'template-parts/search-form' ); ?>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="section-inner">
			<?php if ( have_posts() ) : ?>

				<div class="grid-3">
					<?php
					while ( have_posts() ) :
						the_post();

						$tk_type   = get_post_type();
						$tk_labels = array(
							'post'    => 'Article',
							'page'    => 'Page',
							'product' => 'Product',
						);
						$tk_label  = isset( $tk_labels[ $tk_type ] ) ? $tk_labels[ $tk_type ] : ucfirst( $tk_type );
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'news-card' ); ?>>
							<p class="tk-card-meta">
								<?php echo esc_html( strtoupper( $tk_label ) ); ?>
								<?php if ( 'post' === $tk_type ) : ?>
									· <?php echo esc_html( strtoupper( tuskolift_date_en( 'd M Y' ) ) ); ?>
								<?php endif; ?>
							</p>

							<h3><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h3>

							<?php if ( has_excerpt() ) : ?>
								<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 26, '…' ) ); ?></p>
							<?php endif; ?>

							<a class="text-link" href="<?php the_permalink(); ?>">READ MORE</a>
						</article>
						<?php
					endwhile;
					?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'           => 1,
						'prev_text'          => '← Previous',
						'next_text'          => 'Next →',
						// 站点语言是 zh_CN，不给这个参数的话 aria-label 会是中文。
						'screen_reader_text' => 'Search results navigation',
					)
				);
				?>

			<?php else : ?>

				<p class="tk-empty">
					Nothing matched <strong><?php echo esc_html( $tk_query ? $tk_query : 'your search' ); ?></strong>.
					Try a shorter keyword, or browse the catalog and articles below.
				</p>

				<div class="grid-3" style="margin-top:34px;">
					<a class="panel" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">
						<span class="number">01</span>
						<h3>Products</h3>
						<p>Browse material handling equipment by product line or application.</p>
					</a>
					<a class="panel" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
						<span class="number">02</span>
						<h3>Blog</h3>
						<p>Company news and industry insights on material handling.</p>
					</a>
					<a class="panel" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
						<span class="number">03</span>
						<h3>Contact</h3>
						<p>Tell us your requirements and we will recommend a suitable solution.</p>
					</a>
				</div>

			<?php endif; ?>
		</div>
	</section>

</main><!-- #main -->

<?php
get_footer();
