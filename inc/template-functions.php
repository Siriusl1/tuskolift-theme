<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package TUSKOLIFT
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function tuskolift_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// 本站设计里没有侧栏（原 Underscores 的 sidebar 已整体移除），
	// 所以不再需要判断 is_active_sidebar() 来加 no-sidebar 类。

	return $classes;
}
add_filter( 'body_class', 'tuskolift_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 *
 * 本站不使用 pingback / trackback：它们是 XML-RPC 上最常见的放大攻击与
 * 探测入口（/xmlrpc.php 暴露的 pingback.ping 可被用来做端口扫描和 SSRF）。
 * 外部博客站也没有理由对这个 B2B 企业站点发起 trackback，所以直接关闭，
 * 同时不再输出 pingback 自动发现头（避免给攻击者一个入口提示）。
 *
 * 见 docs/AUDIT-REPORT.md P1-5。
 *
 * @return bool
 */
function tuskolift_disable_pingbacks() {
	return false;
}
add_filter( 'xmlrpc_enabled', 'tuskolift_disable_pingbacks' );
add_filter( 'pings_open', 'tuskolift_disable_pingbacks' );

/**
 * 移除 wp_head 里的 RSD / wlwmanifest / shortlink 等前台无用输出。
 *
 * 这些是博客时代的遗迹，企业站用不到，还会额外暴露接口路径。
 */
function tuskolift_clean_head_links() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
add_action( 'init', 'tuskolift_clean_head_links' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function tuskolift_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'tuskolift_pingback_header' );

/**
 * 把前台 <html lang> 改成英文。
 *
 * 为什么需要：站点 WPLANG 是 zh_CN（后台要中文），但**前台全部是英文文案**。
 * 核心的 language_attributes() 直接读 WPLANG，于是 17 个页面全部输出
 * <html lang="zh-CN"> —— 屏幕阅读器会用中文发音规则念英文，
 * 搜索引擎也会把语言判定错。实测见 docs/VERIFICATION-RESULT.md 第 10 项。
 *
 * 只改 lang，不动 dir，也不改 WPLANG（后台保持中文）。
 *
 * @param string $output 核心生成的属性串，例如 'lang="zh-CN" class="no-js"'。
 * @return string
 */
function tuskolift_filter_language_attributes( $output ) {
	// 前台语言固定英文；可用 filter 覆盖，方便将来做多语言。
	$lang = apply_filters( 'tuskolift_html_lang', 'en-US' );

	if ( preg_match( '/lang="[^"]*"/', $output ) ) {
		return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $lang ) . '"', $output, 1 );
	}

	return 'lang="' . esc_attr( $lang ) . '" ' . $output;
}
add_filter( 'language_attributes', 'tuskolift_filter_language_attributes' );
