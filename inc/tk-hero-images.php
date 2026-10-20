<?php
/**
 * 首页 hero 图片：从媒体库取附件，而不是硬编码主题目录路径。
 *
 * 为什么需要：首页 4 张 hero 图原来直接写主题目录里的 .png
 * （<theme>/doc/白底贴标-/4-脚踩下降款.png 等），完全绕过媒体库与 WP 图片管线：
 * 没有 srcset、没有 width/height（导致 CLS）、没有压缩子尺寸，
 * 实测 4 张合计 2,491,546 B（≈2.38 MB），其中 25-定制.png 单张 1,191,325 B。
 * 见 docs/AUDIT-REPORT.md P0-5。
 *
 * 这 4 张图在媒体库里**已经存在**：附件 #71/#70/#72/#73 是各产品线的配图
 * （通过 term meta _tk_term_image_id 绑定），所以直接复用，不再在主题目录留副本。
 *
 * @package TUSKOLIFT
 */

/**
 * 取某产品线术语配图的附件 ID。
 *
 * @param string $term_slug 产品线 slug。
 * @return int 附件 ID；取不到返回 0。
 */
function tuskolift_hero_term_image_id( $term_slug ) {
	$term = get_term_by( 'slug', $term_slug, 'product_line' );

	if ( ! $term || is_wp_error( $term ) ) {
		return 0;
	}

	return (int) get_term_meta( $term->term_id, '_tk_term_image_id', true );
}

/**
 * 输出首页 hero 图片。
 *
 * 走 wp_get_attachment_image()，因此自动获得：
 *   - srcset / sizes（按视口挑合适的子尺寸）
 *   - width / height（避免 CLS）
 *   - loading / decoding / fetchpriority（由参数控制）
 *
 * @param string $term_slug   用来查配图的产品线 slug。
 * @param string $size        主尺寸。
 * @param string $alt         替代文字。
 * @param bool   $is_lcp      是否为 LCP 主图（true 时 eager + high 优先级 + sync 解码）。
 * @param string $sizes_attr  sizes 属性，决定浏览器挑哪一档。
 * @return void
 */
function tuskolift_hero_image( $term_slug, $size, $alt, $is_lcp = false, $sizes_attr = '(max-width: 900px) 90vw, 50vw' ) {
	$attachment_id = tuskolift_hero_term_image_id( $term_slug );

	// 媒体库里没有配图时不输出空 <img>，交给模板里的兜底文案。
	if ( ! $attachment_id ) {
		return;
	}

	$attr = array(
		'alt'   => $alt,
		'class' => 'tk-hero-image',
		'sizes' => $sizes_attr,
	);

	if ( $is_lcp ) {
		/*
		 * LCP 图必须 eager + fetchpriority=high。核心规定 lazy 与
		 * fetchpriority="high" 互斥（见 wp-includes/media.php），所以这里显式关掉 lazy。
		 */
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'sync';
	} else {
		$attr['loading']  = 'lazy';
		$attr['decoding'] = 'async';
	}

	echo wp_get_attachment_image( $attachment_id, $size, false, $attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- 核心已转义。
}
