<?php
/**
 * Template Name: Blog Insights Page
 *
 * 行业洞察列表页，只显示「行业洞察」分类下的文章。
 * 分类 slug 由 tuskolift_insights_category_slug() 提供，可通过 filter 改名。
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main">

	<?php
	while ( have_posts() ) :
		the_post();

		get_template_part( 'template-parts/page-hero' );

		$tk_query = tuskolift_query_posts_in_categories( array( tuskolift_insights_category_slug() ), 9 );
		?>

		<section class="section" id="insights">
			<div class="section-inner">
				<?php
				tuskolift_render_list_heading(
					'Insights',
					'What customers should evaluate before buying.',
					''
				);
				tuskolift_render_news_grid(
					$tk_query,
					'No articles in Industry Insights yet. Publish a post and assign it to the “Industry Insights” category.'
				);

				tuskolift_render_category_archive_link( tuskolift_insights_category_slug(), 'VIEW ALL INDUSTRY INSIGHTS' );
				?>
			</div>
		</section>

		<?php if ( trim( get_the_content() ) ) : ?>
			<section class="section">
				<div class="section-inner tk-page-content">
					<?php the_content(); ?>
				</div>
			</section>
		<?php endif; ?>

		<?php
	endwhile;
	?>

</main><!-- #main -->

<?php
get_footer();
