<?php
/**
 * 主题对插件的硬依赖守卫。
 *
 * 背景（docs/AUDIT-REPORT.md P0-8）：
 *   主题**完全不注册** product / product_line / application 这三个对象
 *   （全主题 grep register_post_type 只命中 inc/tk-inquiry.php 的 inquiry），
 *   却在 6 处硬依赖 my-product-cpt 插件注册的类型：
 *     inc/tk-catalog-rest.php、taxonomy-product_line.php、taxonomy-application.php、
 *     archive-product.php、inc/tk-performance.php 等。
 *   插件一停用 → 商品归档、产品线/应用归档、商品详情、整个 Vue 目录全部失效，
 *   **而且后端不报错**，只有前台静默变空。这属于最难排查的一类故障。
 *
 * 本文件做两件事：
 *   1. 后台醒目提醒缺少哪个依赖；
 *   2. 提供 tuskolift_product_deps_ok()，让 tk-catalog-rest 等文件在依赖缺失时
 *      跳过注册，避免在不存在的数据上执行查询。
 *
 * @package TUSKOLIFT
 */

/**
 * 主题依赖的 post type / taxonomy 是否都已注册。
 *
 * @return array{ok:bool,missing:string[]}
 */
function tuskolift_product_deps_status() {
	$required = array(
		'product'      => 'post_type',
		'product_line' => 'taxonomy',
		'application'  => 'taxonomy',
	);

	$missing = array();

	foreach ( $required as $name => $kind ) {
		if ( 'post_type' === $kind ) {
			if ( ! post_type_exists( $name ) ) {
				$missing[] = "post type `$name`";
			}
		} elseif ( ! taxonomy_exists( $name ) ) {
			$missing[] = "taxonomy `$name`";
		}
	}

	return array(
		'ok'      => empty( $missing ),
		'missing' => $missing,
	);
}

/**
 * 便捷判断：依赖是否满足。
 *
 * @return bool
 */
function tuskolift_product_deps_ok() {
	$status = tuskolift_product_deps_status();

	return $status['ok'];
}

/**
 * 依赖缺失时在后台顶部醒目报错。
 */
function tuskolift_product_deps_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$status = tuskolift_product_deps_status();

	if ( $status['ok'] ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>TUSKOLIFT 主题缺少必需依赖：</strong>%s 未注册。'
		. '在补齐之前，<strong>商品归档页 / 产品线页 / 应用场景页 / 商品详情页 / 商品目录全部无法正常工作</strong>'
		. '（前台会静默变成空白，不会报错）。请到 <a href="%s">插件</a> 页面启用并激活 <strong>My Product CPT</strong> 插件，'
		. '然后到 <a href="%s">设置 → 固定链接</a> 保存一次，刷新 rewrite 规则。</p></div>',
		esc_html( implode( '、', $status['missing'] ) ),
		esc_url( admin_url( 'plugins.php' ) ),
		esc_url( admin_url( 'options-permalink.php' ) )
	);
}
add_action( 'admin_notices', 'tuskolift_product_deps_notice' );
