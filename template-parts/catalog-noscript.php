<?php
/**
 * 商品目录的「无 JS 兜底」列表（服务端渲染）。
 *
 * 背景（docs/AUDIT-REPORT.md P1-1，实测）：
 *   /products/ 的 <main> 只有 789 个字符、**0 个商品链接**，商品完全由 Vue 在客户端
 *   fetch REST 后渲染。搜索引擎虽然能执行 JS，但抓取预算有限、首屏可能抓不全；
 *   更糟的是「JS 被禁用 / 内网拦截 / 旧浏览器」的访客会看到一片空白。
 *
 * 这里输出一份服务端渲染的完整分类导航 + 商品清单：
 *   - 放在 <noscript> 里，正常浏览器不会看到它（Vue 渲染时也不会重复）；
 *   - 爬虫能直接解析到所有商品链接（<a href> 真实可达）；
 *   - 不做任何样式依赖，纯语义化列表。
 *
 * @package TUSKOLIFT
 */

if ( ! tuskolift_product_deps_ok() ) {
	return;
}

$tk_lines = tuskolift_get_terms_ordered( 'product_line' );
$tk_apps  = tuskolift_get_terms_ordered( 'application' );

$tk_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 300,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
	)
);
?>
<noscript>
	<div class="tk-noscript-catalog">
		<p><strong>JavaScript is disabled.</strong> Below is the complete product catalog as plain links.</p>

		<?php if ( $tk_lines ) : ?>
			<h2>Product lines</h2>
			<ul>
				<?php foreach ( $tk_lines as $tk_line ) : ?>
					<li>
						<a href="<?php echo esc_url( get_term_link( $tk_line ) ); ?>">
							<?php echo esc_html( $tk_line->name ); ?>
						</a>
						<?php if ( $tk_line->description ) : ?>
							— <?php echo esc_html( wp_strip_all_tags( $tk_line->description ) ); ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $tk_apps ) : ?>
			<h2>Applications</h2>
			<ul>
				<?php foreach ( $tk_apps as $tk_app ) : ?>
					<li>
						<a href="<?php echo esc_url( get_term_link( $tk_app ) ); ?>">
							<?php echo esc_html( $tk_app->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $tk_products ) : ?>
			<h2>All products (<?php echo (int) count( $tk_products ); ?>)</h2>
			<ul>
				<?php foreach ( $tk_products as $tk_product ) : ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $tk_product ) ); ?>">
							<?php echo esc_html( get_the_title( $tk_product ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</noscript>
