<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package TUSKOLIFT
 */

?>

	<footer class="site-footer">
		<div class="footer-inner">
			<div class="footer-copy">
				&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <strong>TUSKOLIFT</strong>. All rights reserved.
			</div>
			<div class="footer-links">
				<?php
				/*
				 * 法务/支持链接。
				 *
				 * 原来 Privacy / Terms / Support 三个链接**全部指向同一个** `/contact/#inquiry`
				 * （实测，见 docs/AUDIT-REPORT.md P0-3 D）。这里改成按 slug 动态取页面永久链接：
				 *   - 页面存在 → 链到真实页面（改 slug 也不会断）；
				 *   - 页面不存在 → 不输出该链接，而不是给出一个假地址。
				 * 目前 Terms 页仍需由客户/法务提供文本后创建（见 docs/LAUNCH-BLOCKERS.md）。
				 */
				$tk_footer_links = array(
					'privacy-policy' => 'Privacy',
					'terms-of-service' => 'Terms',
					'support' => 'Support',
				);

				foreach ( $tk_footer_links as $tk_slug => $tk_label ) {
					$tk_page = get_page_by_path( $tk_slug );

					if ( ! $tk_page || 'publish' !== $tk_page->post_status ) {
						continue;
					}

					printf(
						'<a href="%s">%s</a>',
						esc_url( get_permalink( $tk_page ) ),
						esc_html( $tk_label )
					);
				}
				?>
			</div>
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
