<?php
/**
 * 法务页面（Privacy / Terms / Support）的说明与一致性校验。
 *
 * ===========================================================================
 * 这三页为什么和别的页面不一样
 * ===========================================================================
 *
 * 其余 13 个页面用硬编码模板（page-about.php 等），正文写在 PHP 里。
 * 这三页走 WordPress 默认的 page.php，正文存在数据库的 wp_posts.post_content。
 *
 * 这是**有意为之**：法务文案需要法务/负责人自己改，不该每次改一句"last updated"
 * 都找开发改代码并重新部署。所以：
 *
 *   改文案  → 后台「页面 → Privacy Policy / Terms of Service / Support」
 *   看历史  → inc/tk-legal-content.php 里有一份从数据库导出的只读副本，
 *             能进 git、能 diff、能代码审查
 *
 * ===========================================================================
 * 副本与数据库不一致时怎么办
 * ===========================================================================
 *
 * 正常情况下两者应该一致。不一致意味着「有人改了后台文案但没更新副本」，
 * 或者「改了副本但没同步到数据库」。
 *
 * 后台会在「页面」列表顶部提示差异（仅管理员可见），并给出重新导出的命令：
 *
 *   wp eval-file <导出脚本>     # 或者在 bin/ 下用 QA 脚本
 *
 * 不一致**不影响前台显示**（前台始终以数据库为准），只是一个审查信号。
 *
 * ===========================================================================
 * 如果确实想让源码接管渲染
 * ===========================================================================
 *
 * 不推荐，但如果非要：新建 page-privacy-policy.php 等文件，在后台把
 * 「页面模板」选成它，正文改写在 PHP 里。代价是后台编辑彻底失效，
 * 法务每次改文案都要走发布流程。此文件不提供这条路。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 法务页面的登记表。
 *
 * @return array<string, array{id:int, slug:string, const:string, label:string}>
 */
function tuskolift_legal_pages() {
	return array(
		'privacy' => array(
			'id'    => 3,
			'slug'  => 'privacy-policy',
			'const' => 'TUSKOLIFT_LEGAL_PRIVACY_POLICY',
			'label' => 'Privacy Policy',
		),
		'terms'   => array(
			'id'    => 1114,
			'slug'  => 'terms-of-service',
			'const' => 'TUSKOLIFT_LEGAL_TERMS_OF_SERVICE',
			'label' => 'Terms of Service',
		),
		'support' => array(
			'id'    => 1115,
			'slug'  => 'support',
			'const' => 'TUSKOLIFT_LEGAL_SUPPORT',
			'label' => 'Support',
		),
	);
}

/**
 * 判断当前页面是不是法务页（后台提示用）。
 *
 * @return array{key:string, spec:array}|null
 */
function tuskolift_current_legal_page() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return null;
	}

	// 编辑页
	if ( in_array( $screen->base, array( 'post' ), true ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 仅用于只读判断。
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		foreach ( tuskolift_legal_pages() as $key => $spec ) {
			if ( $spec['id'] === $post_id ) {
				return array( 'key' => $key, 'spec' => $spec );
			}
		}
	}

	return null;
}

/**
 * 在法务页与普通页的编辑界面给出「正文在哪里改」的说明。
 *
 * 这是为了解决一个反复出现的坑：
 *   - 硬编码页面：后台改了不生效，但界面上看不出来；
 *   - 法务页：后台改才生效，但代码里有一份副本容易让人以为要改代码。
 */
function tuskolift_page_source_notice() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	// --- 编辑界面 ---
	if ( 'post' === $screen->base ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;

		if ( ! $post_id ) {
			return;
		}

		$legal = tuskolift_current_legal_page();

		if ( $legal ) {
			printf(
				'<div class="notice notice-info"><p><strong>这一页的正文就在下面编辑器里</strong>，改完直接更新即可。'
				. '%s 还有一份只读副本用于代码审查（<code>inc/tk-legal-content.php</code>），'
				. '<strong>那只是副本，改它不会生效</strong>。</p></div>',
				esc_html( $legal['spec']['label'] )
			);
			return;
		}

		$template = (string) get_post_meta( $post_id, '_wp_page_template', true );

		if ( $template && 'default' !== $template ) {
			$pretty = $template;

			$registered = wp_get_theme()->get_page_templates( null, 'page' );
			if ( isset( $registered[ $template ] ) ) {
				$pretty = $registered[ $template ] . '  /  ' . $template;
			}

			printf(
				'<div class="notice notice-warning"><p><strong>这一页的正文由代码模板控制，改这里不会生效。</strong></p>'
				. '<p>当前模板：<code>%s</code>。要改文案或排版，请改主题目录下的这个文件。</p>'
				. '<p>下面的编辑器内容<strong>不会显示在前台</strong>（前台内容完全来自模板）。'
				. '详见 <code>docs/PAGE-MAP.md</code>。</p></div>',
				esc_html( $pretty )
			);
		}

		return;
	}

	// --- 页面列表 ---
	if ( 'edit' === $screen->base ) {
		?>
		<div class="notice notice-info">
			<p>
				<strong>改文案前先看这里：</strong>
				本站大部分页面的正文写在<strong>代码模板</strong>里（例如 <code>page-about.php</code>），
				在后台改正文<strong>不会生效</strong>；只有 Privacy Policy / Terms of Service / Support
				三页的正文在后台。
			</p>
			<p>
				每个页面具体该改哪里，见主题目录下的 <code>docs/PAGE-MAP.md</code>。
				鼠标悬停在下方页面标题上，能看到它的模板文件与真实来源。
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'tuskolift_page_source_notice' );

/**
 * 在页面列表里加一列「正文来源」，一眼看出该去哪改。
 *
 * @param string[] $columns 现有列。
 * @return string[]
 */
function tuskolift_pages_source_column( $columns ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;

		if ( 'title' === $key ) {
			$new['tk_source'] = '正文来源';
		}
	}

	return $new;
}
add_filter( 'manage_pages_columns', 'tuskolift_pages_source_column' );

/**
 * 渲染「正文来源」列。
 *
 * @param string $column  列名。
 * @param int    $post_id 页面 ID。
 */
function tuskolift_pages_source_column_content( $column, $post_id ) {
	if ( 'tk_source' !== $column ) {
		return;
	}

	$template = (string) get_post_meta( $post_id, '_wp_page_template', true );

	if ( '' === $template || 'default' === $template ) {
		echo '<span style="color:#007017;">数据库</span><br><small>后台改这里生效</small>';
		return;
	}

	$registered = wp_get_theme()->get_page_templates( null, 'page' );
	$pretty     = isset( $registered[ $template ] ) ? $registered[ $template ] : $template;

	printf(
		'<span style="color:#b32d2e;">代码</span><br><small><code>%s</code></small>',
		esc_html( $pretty )
	);
}
add_action( 'manage_pages_custom_column', 'tuskolift_pages_source_column_content', 10, 2 );

/**
 * 检查法务页的源码副本与数据库是否一致。
 *
 * @return array<string, array{label:string, db:int, code:int, match:bool}>
 */
function tuskolift_legal_sync_status() {
	global $wpdb;

	$status = array();

	foreach ( tuskolift_legal_pages() as $key => $spec ) {
		$db_content = (string) $wpdb->get_var(
			$wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $spec['id'] )
		);

		$code_content = defined( $spec['const'] ) ? (string) constant( $spec['const'] ) : '';

		$status[ $key ] = array(
			'label' => $spec['label'],
			'db'    => strlen( $db_content ),
			'code'  => strlen( $code_content ),
			'match' => $db_content === $code_content,
		);
	}

	return $status;
}

/**
 * 副本与数据库不一致时，在页面列表提示（仅管理员）。
 */
function tuskolift_legal_sync_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type || 'edit' !== $screen->base ) {
		return;
	}

	$out_of_sync = array();

	foreach ( tuskolift_legal_sync_status() as $row ) {
		if ( ! $row['match'] ) {
			$out_of_sync[] = sprintf( '%s（数据库 %d 字节 / 副本 %d 字节）', $row['label'], $row['db'], $row['code'] );
		}
	}

	if ( ! $out_of_sync ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>法务页面的源码副本与数据库不一致：</strong>%s。</p>'
		. '<p>前台始终显示<strong>数据库</strong>里的版本，所以访客不受影响；'
		. '这只是提醒「有人改了后台文案但没刷新 <code>inc/tk-legal-content.php</code> 里的只读副本」，'
		. '导致代码审查时看到的不是线上内容。</p>'
		. '<p>如需刷新副本，重新运行导出脚本即可（见 <code>docs/PAGE-MAP.md</code> 第 4 节）。</p></div>',
		esc_html( implode( '、', $out_of_sync ) )
	);
}
add_action( 'admin_notices', 'tuskolift_legal_sync_notice' );
