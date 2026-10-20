<?php
/**
 * 站内搜索表单
 *
 * 用站点自己的样式，不使用 WordPress 默认的搜索表单标记
 * （默认标记在这个主题里没有对应 CSS，会显示成裸控件）。
 *
 * @package TUSKOLIFT
 */

$tk_search_id = wp_unique_id( 'tk-search-' );
?>
<form class="tk-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $tk_search_id ); ?>"><?php esc_html_e( 'Search for:', 'tuskolift' ); ?></label>
	<input
		type="search"
		id="<?php echo esc_attr( $tk_search_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="Search products, articles…"
	>
	<button type="submit">SEARCH</button>
</form>
