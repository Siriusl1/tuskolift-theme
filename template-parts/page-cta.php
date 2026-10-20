<?php
/**
 * 页面底部 CTA
 *
 * 文案来自后台「页面文案（头图 / 底部 CTA）」面板。
 * 标题与说明都为空时不输出，按钮链接留空则默认指向 /contact/#inquiry。
 *
 * 可传入 $args：
 *   'post_id' —— 指定读取哪个页面的文案，默认当前页面。
 *
 * @package TUSKOLIFT
 */

$tk_cta_page_id = get_the_ID();

if ( ! empty( $args['post_id'] ) ) {
	$tk_cta_page_id = (int) $args['post_id'];
}

$tk_cta_title    = tuskolift_get_page_copy( '_tk_cta_title', $tk_cta_page_id );
$tk_cta_text     = tuskolift_get_page_copy( '_tk_cta_text', $tk_cta_page_id );
$tk_cta_btn_text = tuskolift_get_page_copy( '_tk_cta_btn_text', $tk_cta_page_id );
$tk_cta_btn_url  = tuskolift_get_page_copy( '_tk_cta_btn_url', $tk_cta_page_id );

if ( ! $tk_cta_title && ! $tk_cta_text ) {
	return;
}
?>
<section class="npd-cta">
	<div>
		<?php if ( $tk_cta_title ) : ?>
			<h2><?php echo esc_html( $tk_cta_title ); ?></h2>
		<?php endif; ?>
	</div>
	<div>
		<?php if ( $tk_cta_text ) : ?>
			<p><?php echo esc_html( $tk_cta_text ); ?></p>
		<?php endif; ?>
		<?php if ( $tk_cta_btn_text ) : ?>
			<a href="<?php echo esc_url( $tk_cta_btn_url ); ?>"><?php echo esc_html( $tk_cta_btn_text ); ?></a>
		<?php endif; ?>
	</div>
</section>
