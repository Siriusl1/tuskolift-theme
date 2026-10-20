<?php
/**
 * 页面头图（hero）
 *
 * 文案来自后台编辑页面时的「页面文案（头图 / 底部 CTA）」面板。
 * 三项都为空时不输出任何内容，避免出现空白头图。
 *
 * @package TUSKOLIFT
 */

$tk_page_id = get_the_ID();
$tk_eyebrow = tuskolift_get_page_copy( '_tk_hero_eyebrow', $tk_page_id );
$tk_title   = tuskolift_get_page_copy( '_tk_hero_title', $tk_page_id );
$tk_intro   = tuskolift_get_page_copy( '_tk_hero_intro', $tk_page_id );

if ( ! $tk_eyebrow && ! $tk_title && ! $tk_intro ) {
	return;
}
?>
<section class="page-hero">
	<div class="page-hero-inner">
		<?php if ( $tk_eyebrow ) : ?>
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
