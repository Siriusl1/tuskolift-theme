<?php
/**
 * Template Name: Blog Page
 *
 * 文章总览页。头图与列表区标题来自后台「页面文案」面板，
 * 文章本身来自后台「文章」，正文编辑器里的内容会追加在列表下方。
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

		$tk_query = tuskolift_query_posts_in_categories(
			array( tuskolift_news_category_slug(), tuskolift_insights_category_slug() ),
			6
		);
		?>

		<section class="section" id="news">
			<div class="section-inner">
				<?php
				tuskolift_render_list_heading(
					'Latest news',
					'Keep your customers close to the work.',
					''
				);
				tuskolift_render_news_grid(
					$tk_query,
					'No articles yet. Publish a post and assign it to “Company News” or “Industry Insights”.'
				);

				tuskolift_render_category_archive_link( tuskolift_news_category_slug(), 'VIEW ALL COMPANY NEWS' );
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
