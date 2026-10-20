<?php
/**
 * 阻止通过 HTTP 直接下载主题内部的开发文件（docs / bin / vue-products / node_modules）。
 *
 * 背景（docs/AUDIT-REPORT.md P0-2，实测）：
 *   `docs/*.md`、`bin/*.cmd`、`vue-products/package.json` 都能被公网直接 200 下载。
 *   其中 docs/AUDIT-REPORT.md 里有**未经鉴权的漏洞细节**（包括 XSS payload 写法、
 *   管理员账号、数据库表前缀），bin/ 里有 WP-CLI 封装。这些必须永远不可公开。
 *
 * 为什么在主题里做，而不是只靠 nginx：
 *   生产环境已经给出 `docs/nginx-production.conf`（对 docs/、bin/、vue-products/ 直接 deny），
 *   但 Local 的 nginx 配置是 Local 生成的（conf/*.hbs → run/conf），改它会被覆盖，
 *   而且上线时若漏配 nginx，这条防线就没了。
 *
 * 做法：用 PHP 在早期拦截 —— 命中内部路径就 404，并且**在文件被读取之前**退出。
 *   这对 nginx + php-fpm（生产）、Apache（.htaccess 不生效时）都有效，
 *   因为它发生在 WordPress 自己的 bootstrap 阶段。
 *
 * 注意：这不是「隐藏」而是「拒绝」。返回 404 而不是 403，避免泄露这些路径存在的信号。
 *
 * @package TUSKOLIFT
 */

/**
 * 内部路径（相对于主题目录）。
 *
 * @return string[]
 */
function tuskolift_private_theme_dirs() {
	/*
	 * `inc/` 也加进来：直接访问 inc/*.php 会由 PHP 执行（只返回空白），
	 * 不算泄露，但没有任何理由允许这种请求，404 更干净。
	 * 其中 inc/tk-legal-content.php 含法务页面正文快照，尤其不该被直接请求。
	 */
	return array( 'docs', 'bin', 'vue-products', 'node_modules', 'dist-package', 'inc' );
}

/**
 * 当前请求是否在访问主题内部文件。
 *
 * @return bool
 */
function tuskolift_is_private_theme_request() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- 仅做路径匹配，未落库、未输出。

	if ( '' === $uri ) {
		return false;
	}

	$path = strtolower( (string) wp_parse_url( $uri, PHP_URL_PATH ) );

	if ( '' === $path ) {
		return false;
	}

	$theme_base = strtolower( '/wp-content/themes/' . basename( get_template_directory() ) . '/' );

	if ( false === strpos( $path, $theme_base ) ) {
		return false;
	}

	$relative = substr( $path, strlen( $theme_base ) );

	foreach ( tuskolift_private_theme_dirs() as $dir ) {
		if ( 0 === strpos( $relative, $dir . '/' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * 命中内部路径直接 404 并退出。
 *
 * 挂在 `init` 上最早的位置：此时 WordPress 已能判断请求，但内容还没生成。
 */
function tuskolift_block_private_theme_files() {
	if ( ! tuskolift_is_private_theme_request() ) {
		return;
	}

	status_header( 404 );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	exit( 'Not Found' );
}
add_action( 'init', 'tuskolift_block_private_theme_files', 0 );

/*
 * 补充：对 Nginx（生产）与该站点服务器，同时也把规则写进 .htaccess，
 * 以便 Apache 环境下绕过 PHP 也能拦住（若 AllowOverride 开启）。
 * 这里不写文件，只在后台提示；实际规则见 docs/nginx-production.conf。
 */
