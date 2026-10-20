<?php
/**
 * 文章卡片（必须在循环内调用）
 *
 * 结构与原先写死的静态卡片完全一致，因此沿用 common.css / index.css 的既有样式。
 *
 * @package TUSKOLIFT
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'news-card' ); ?>>
	<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( strtoupper( tuskolift_date_en( 'd M Y' ) ) ); ?></time>

	<h3>
		<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
	</h3>

	<?php
	$tk_excerpt = get_the_excerpt();
	if ( $tk_excerpt ) :
		?>
		<p><?php echo esc_html( wp_strip_all_tags( $tk_excerpt ) ); ?></p>
	<?php endif; ?>

	<a class="text-link" href="<?php the_permalink(); ?>">READ STORY</a>
</article><!-- #post-<?php the_ID(); ?> -->
