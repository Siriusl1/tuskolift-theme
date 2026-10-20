<?php
/**
 * 归档页（分类 / 标签 / 日期 / 作者）
 *
 * 使用与 blog 列表一致的卡片网格，样式来自 common.css + css/pages/blog.css。
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main">

	<section class="page-hero">
		<div class="page-hero-inner">
			<p class="eyebrow">
				<?php
				if ( is_category() ) {
					echo 'Category';
				} elseif ( is_tag() ) {
					echo 'Tag';
				} elseif ( is_author() ) {
					echo 'Author';
				} elseif ( is_year() || is_month() || is_day() ) {
					echo 'Archive';
				} else {
					echo 'Archive';
				}
				?>
			</p>
			<?php
			/*
			 * 这里刻意不用 the_archive_title()：
			 * 站点语言是 zh_CN，它会输出「分类：」「标签：」这类中文前缀，
			 * 而本站面向海外客户，标题必须全英文。所以直接输出术语名 / 日期。
			 */
			$tk_queried = get_queried_object();

			if ( ( is_category() || is_tag() || is_tax() ) && $tk_queried instanceof WP_Term ) {
				echo '<h1>' . esc_html( $tk_queried->name ) . '</h1>';

				if ( $tk_queried->description ) {
					echo '<p>' . esc_html( $tk_queried->description ) . '</p>';
				}
			} elseif ( is_author() && $tk_queried instanceof WP_User ) {
				echo '<h1>' . esc_html( $tk_queried->display_name ) . '</h1>';
			} elseif ( is_day() ) {
				echo '<h1>' . esc_html( tuskolift_date_en( 'j F Y' ) ) . '</h1>';
			} elseif ( is_month() ) {
				echo '<h1>' . esc_html( tuskolift_date_en( 'F Y' ) ) . '</h1>';
			} elseif ( is_year() ) {
				echo '<h1>' . esc_html( tuskolift_date_en( 'Y' ) ) . '</h1>';
			} else {
				echo '<h1>' . esc_html( wp_strip_all_tags( get_the_archive_title() ) ) . '</h1>';
			}
			?>
		</div>
	</section>

	<section class="section">
		<div class="section-inner">
			<?php if ( have_posts() ) : ?>

				<div class="grid-3">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/news-card' );
					endwhile;
					?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => '← Previous',
						'next_text' => 'Next →',
						// 站点语言是 zh_CN，不给这个参数的话 aria-label 会是「文章导航」。
						'screen_reader_text' => 'Posts navigation',
					)
				);
				?>

			<?php else : ?>

				<p class="tk-empty">Nothing found here yet.</p>

			<?php endif; ?>
		</div>
	</section>

</main><!-- #main -->

<?php
get_footer();
