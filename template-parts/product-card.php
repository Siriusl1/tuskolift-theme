<?php
/**
 * 商品卡片（必须在循环内调用）
 *
 * 供产品线 / 应用场景归档页与商品详情页「同系列」区域共用，
 * 样式来自 common.css 的 .tk-product-card。
 *
 * @package TUSKOLIFT
 */

$tk_lines     = get_the_terms( get_the_ID(), 'product_line' );
$tk_line_name = ( $tk_lines && ! is_wp_error( $tk_lines ) ) ? $tk_lines[0]->name : '';
?>
<a class="tk-product-card" href="<?php the_permalink(); ?>">
	<?php if ( has_post_thumbnail() ) : ?>
		<?php
		/*
		 * 用 tk-card（560×560 裁切）而不是 medium（300×300）：
		 * 卡片在桌面端宽约 400–560px，medium 在 2 倍屏上会被放大发虚。
		 * tk-card 尺寸此前只注册未使用（见 docs/AUDIT-REPORT.md P1-3），
		 * 现已对全部附件批量生成。
		 */
		the_post_thumbnail( 'tk-card', array( 'class' => 'tk-product-card-image' ) );
		?>
	<?php else : ?>
		<span class="tk-product-card-image tk-product-card-image-placeholder">No image</span>
	<?php endif; ?>

	<?php if ( $tk_line_name ) : ?>
		<p class="tk-product-card-line"><?php echo esc_html( $tk_line_name ); ?></p>
	<?php endif; ?>

	<h3><?php the_title(); ?></h3>

	<?php if ( has_excerpt() ) : ?>
		<p><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
	<?php endif; ?>
</a>
