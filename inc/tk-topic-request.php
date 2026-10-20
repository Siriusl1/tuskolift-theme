<?php
/**
 * TUSKOLIFT — 文章选题建议（Blog 「Suggest a topic」）
 *
 * ===========================================================================
 * 为什么单独一份文件、单独一个 CPT
 * ===========================================================================
 *
 * 需求：/blog/ 页面上要有一个「求更新」入口，让访客提交与本公司产业相关的问题，
 *       我们整理后在 Blog 发布，形成互动循环。
 *
 * 设计决策：**不复用「询盘记录」，而是新建「选题建议」CPT。**
 * 理由：
 *   1. 语义不同 —— 询盘是「买设备」，选题是「想读到什么」，两者的处理人、SLA 完全不同；
 *   2. 混在一起会让销售在询盘列表里翻到一堆非销售线索，看不到真正的商机；
 *   3. 选题建议的审核流是「采纳 → 写成文章 → 发布」，需要独立的状态与来源追踪。
 *
 * ===========================================================================
 * 数据流
 * ===========================================================================
 *
 *   访客在 /blog/ 提交（WPForms 表单 #1119「Blog Topic Suggestion」）
 *     → wpforms_process_complete
 *     → 本文件落库为 post_type=topic_request（private，前台不公开）
 *     → 后台「选题建议」列表可见，可按状态筛选
 *     → 编辑采纳后写成文章，并在文章里回链来源
 *
 *   邮件的处理方式与询盘一致：靠 WPForms 的通知，失败也不丢数据（因为已落库）。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 1. 选题建议文章类型
 * ========================================================================= */

/**
 * 注册 topic_request 文章类型。
 */
function tuskolift_register_topic_request_cpt() {
	register_post_type(
		'topic_request',
		array(
			'labels'              => array(
				'name'          => '选题建议',
				'singular_name' => '选题建议',
				'menu_name'     => '选题建议',
				'all_items'     => '全部选题建议',
				'edit_item'     => '查看建议',
				'search_items'  => '搜索建议',
				'not_found'     => '还没有收到选题建议',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-lightbulb',
			'menu_position'       => 27,
			'supports'            => array( 'title', 'editor' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'tuskolift_register_topic_request_cpt' );

/* =========================================================================
 * 2. 处理状态（未处理 / 已采纳 / 已发布 / 不采纳）
 * ========================================================================= */

/**
 * 可用的处理状态。
 *
 * @return array<string, string>
 */
function tuskolift_topic_statuses() {
	return array(
		'new'      => '待处理',
		'accepted' => '已采纳（待撰写）',
		'published'=> '已发布',
		'declined' => '不采纳',
	);
}

/**
 * 在编辑页面加「处理状态」下拉框。
 */
function tuskolift_topic_add_metabox() {
	add_meta_box(
		'tkolift_topic_status',
		'处理状态',
		'tuskolift_topic_render_metabox',
		'topic_request',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tuskolift_topic_add_metabox' );

/**
 * 渲染状态选择框。
 *
 * @param WP_Post $post 当前文章。
 */
function tuskolift_topic_render_metabox( $post ) {
	wp_nonce_field( 'tuskolift_topic_status', 'tuskolift_topic_status_nonce' );

	$current = (string) get_post_meta( $post->ID, '_tk_topic_status', true );
	if ( '' === $current ) {
		$current = 'new';
	}

	echo '<p><select name="tk_topic_status" style="width:100%">';
	foreach ( tuskolift_topic_statuses() as $key => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select></p>';

	$source = (string) get_post_meta( $post->ID, '_tk_topic_source_url', true );
	if ( $source ) {
		printf(
			'<p style="margin-top:8px"><strong>提交页面：</strong><br><a href="%s" target="_blank" rel="noopener">%s</a></p>',
			esc_url( $source ),
			esc_html( $source )
		);
	}

	printf(
		'<p style="margin-top:8px;color:#666"><strong>来源表单：</strong>%s</p>',
		esc_html( (string) get_post_meta( $post->ID, '_tk_topic_form', true ) ?: '—' )
	);

	$email = (string) get_post_meta( $post->ID, '_tk_topic_email', true );
	if ( $email ) {
		printf(
			'<p style="color:#666"><strong>联系邮箱：</strong><br><a href="mailto:%1$s">%1$s</a></p>',
			esc_attr( $email )
		);
	}
}

/**
 * 保存处理状态。
 *
 * @param int $post_id 文章 ID。
 */
function tuskolift_topic_save_status( $post_id ) {
	if ( ! isset( $_POST['tuskolift_topic_status_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['tuskolift_topic_status_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'tuskolift_topic_status' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['tk_topic_status'] ) ) {
		return;
	}

	$value = sanitize_key( wp_unslash( $_POST['tk_topic_status'] ) );

	if ( ! array_key_exists( $value, tuskolift_topic_statuses() ) ) {
		return;
	}

	update_post_meta( $post_id, '_tk_topic_status', $value );
}
add_action( 'save_post_topic_request', 'tuskolift_topic_save_status' );

/* =========================================================================
 * 3. 后台列表
 * ========================================================================= */

/**
 * 后台列表列。
 *
 * @param array $columns 原列。
 * @return array
 */
function tuskolift_topic_columns( $columns ) {
	return array(
		'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'     => '选题',
		'tk_author' => '提交人',
		'tk_email'  => '邮箱',
		'tk_status' => '状态',
		'tk_form'   => '来源',
		'date'      => '时间',
	);
}
add_filter( 'manage_topic_request_posts_columns', 'tuskolift_topic_columns' );

/**
 * 渲染列表列。
 *
 * @param string $column  列名。
 * @param int    $post_id 文章 ID。
 */
function tuskolift_topic_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'tk_author':
			echo esc_html( (string) get_post_meta( $post_id, '_tk_topic_author', true ) ?: '—' );
			break;

		case 'tk_email':
			$email = (string) get_post_meta( $post_id, '_tk_topic_email', true );
			if ( $email ) {
				printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) );
			} else {
				echo '—';
			}
			break;

		case 'tk_status':
			$status  = (string) get_post_meta( $post_id, '_tk_topic_status', true ) ?: 'new';
			$labels  = tuskolift_topic_statuses();
			$label   = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
			$colors  = array(
				'new'       => '#b32d2e',
				'accepted'  => '#996800',
				'published' => '#007017',
				'declined'  => '#666666',
			);
			$color   = isset( $colors[ $status ] ) ? $colors[ $status ] : '#666';
			printf( '<span style="color:%s;font-weight:600">%s</span>', esc_attr( $color ), esc_html( $label ) );
			break;

		case 'tk_form':
			echo esc_html( (string) get_post_meta( $post_id, '_tk_topic_form', true ) ?: '—' );
			break;
	}
}
add_action( 'manage_topic_request_posts_custom_column', 'tuskolift_topic_column_content', 10, 2 );

/**
 * 列表顶部按状态筛选。
 */
function tuskolift_topic_status_filter() {
	global $typenow;

	if ( 'topic_request' !== $typenow ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只读筛选。
	$current = isset( $_GET['tk_status'] ) ? sanitize_key( wp_unslash( $_GET['tk_status'] ) ) : '';

	echo '<select name="tk_status"><option value="">全部状态</option>';
	foreach ( tuskolift_topic_statuses() as $key => $label ) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'tuskolift_topic_status_filter' );

/**
 * 应用状态筛选。
 *
 * @param WP_Query $query 查询。
 */
function tuskolift_topic_filter_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'topic_request' !== $query->get( 'post_type' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$status = isset( $_GET['tk_status'] ) ? sanitize_key( wp_unslash( $_GET['tk_status'] ) ) : '';

	if ( $status && array_key_exists( $status, tuskolift_topic_statuses() ) ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_tk_topic_status',
					'value' => $status,
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'tuskolift_topic_filter_query' );

/* =========================================================================
 * 4. 把 WPForms 的选题提交落库
 * ========================================================================= */

/**
 * WPForms 提交完成后，把「选题建议」表单的提交存成 topic_request。
 *
 * 只处理 tuskolift_topic_form_id() 指定的表单，其它表单（询盘）不受影响。
 *
 * @param array $fields   清洗后的字段数据。
 * @param array $entry    原始提交数据。
 * @param array $form_data 表单配置。
 * @param int   $entry_id 投稿 ID（Lite 版为 0）。
 */
function tuskolift_archive_topic_request( $fields, $entry, $form_data, $entry_id ) {
	if ( empty( $fields ) || ! is_array( $fields ) ) {
		return;
	}

	$topic_form_id = function_exists( 'tuskolift_topic_form_id' ) ? tuskolift_topic_form_id() : 0;

	if ( ! $topic_form_id || (int) ( $form_data['id'] ?? 0 ) !== (int) $topic_form_id ) {
		return;
	}

	// 字段值收集：$fields 是 id => [name,value,...]
	$values = array();
	foreach ( $fields as $field ) {
		if ( empty( $field['name'] ) ) {
			continue;
		}
		$value = isset( $field['value'] ) ? $field['value'] : '';
		if ( is_array( $value ) ) {
			$value = implode( ', ', array_filter( $value ) );
		}
		$values[ strtolower( trim( (string) $field['name'] ) ) ] = trim( (string) $value );
	}

	if ( ! $values ) {
		return;
	}

	$title_text = isset( $values['topic'] ) ? $values['topic'] : '';
	if ( '' === $title_text ) {
		// 兜底：取第一个非空字段。
		foreach ( $values as $v ) {
			if ( '' !== $v ) {
				$title_text = $v;
				break;
			}
		}
	}

	$title = mb_substr( $title_text, 0, 80 );
	$body  = isset( $values['details'] ) ? $values['details'] : '';

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'topic_request',
			'post_status'  => 'private',
			'post_title'   => $title ? $title : '（未填写选题）',
			'post_content' => trim( $body ),
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return;
	}

	$meta_map = array(
		'_tk_topic_author'     => array( 'name', 'your name' ),
		'_tk_topic_email'      => array( 'email', 'your email' ),
		'_tk_topic_company'    => array( 'company', 'company name' ),
		'_tk_topic_role'       => array( 'role', 'your role', 'industry role' ),
		'_tk_topic_category'   => array( 'topic area', 'industry topic', 'category' ),
		'_tk_topic_consent'    => array( 'consent', 'permission' ),
	);

	foreach ( $meta_map as $meta_key => $candidates ) {
		foreach ( $candidates as $candidate ) {
			if ( isset( $values[ $candidate ] ) && '' !== $values[ $candidate ] ) {
				update_post_meta( $post_id, $meta_key, $values[ $candidate ] );
				break;
			}
		}
	}

	// 默认状态：待处理。
	update_post_meta( $post_id, '_tk_topic_status', 'new' );

	// 来源追踪：哪个页面提交的、哪个表单。
	$page_url = isset( $entry['entry_meta']['page_url'] ) ? (string) $entry['entry_meta']['page_url'] : '';
	if ( $page_url ) {
		update_post_meta( $post_id, '_tk_topic_source_url', esc_url_raw( $page_url ) );
	}

	$form_title = isset( $form_data['settings']['form_title'] ) ? (string) $form_data['settings']['form_title'] : '';
	if ( $form_title ) {
		update_post_meta( $post_id, '_tk_topic_form', $form_title );
	}
	update_post_meta( $post_id, '_tk_topic_form_id', (int) $form_data['id'] );
}
add_action( 'wpforms_process_complete', 'tuskolift_archive_topic_request', 10, 4 );
