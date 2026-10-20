<?php
/**
 * TUSKOLIFT — 询盘存档（WPForms 的备份镜像）
 *
 * 前台表单现在完全由 WPForms 提供（表单 ID 9 = 联系询盘，ID 97 = 首页报价）。
 * 本文件不做任何表单渲染或提交处理，只做一件事：
 *
 *   ⚠️ WPForms Lite 版**不会把投稿记录存进数据库**，只发通知邮件。
 *      一旦邮件投递失败（SMTP 挂了、被当成垃圾邮件、收件人写错），
 *      客户询盘就会无声无息地消失，而且后台查不到任何痕迹。
 *
 *   所以这里挂到 WPForms 自己的 `wpforms_process_complete` 钩子上，
 *   把每条提交额外抄一份到后台「询盘记录」里。纯备份，不影响 WPForms 的任何行为。
 *
 * 如果不需要这个保险，整份文件连同 functions.php 里的 require 一起删掉即可。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 1. 询盘存档文章类型
 * ========================================================================= */

/**
 * 注册 inquiry 文章类型。
 */
function tuskolift_register_inquiry_cpt() {
	register_post_type(
		'inquiry',
		array(
			'labels'              => array(
				'name'          => '询盘记录',
				'singular_name' => '询盘',
				'menu_name'     => '询盘记录',
				'all_items'     => '全部询盘',
				'edit_item'     => '查看询盘',
				'search_items'  => '搜索询盘',
				'not_found'     => '还没有收到询盘',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'editor' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'rewrite'             => false,
		)
	);
}
add_action( 'init', 'tuskolift_register_inquiry_cpt' );

/**
 * 询盘列表的列。
 *
 * @param array $columns 原列。
 * @return array
 */
function tuskolift_inquiry_columns( $columns ) {
	return array(
		'cb'         => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'      => '客户',
		'tk_email'   => '邮箱',
		'tk_company' => '公司',
		'tk_phone'   => '电话',
		'tk_country' => '国家',
		'tk_product' => '产品 / 应用',
		'tk_form'    => '来源表单',
		'date'       => '时间',
	);
}
add_filter( 'manage_inquiry_posts_columns', 'tuskolift_inquiry_columns' );

/**
 * 渲染询盘列表的自定义列。
 *
 * @param string $column  列名。
 * @param int    $post_id 询盘 ID。
 */
function tuskolift_inquiry_column_content( $column, $post_id ) {
	$map = array(
		'tk_email'   => '_tk_inq_email',
		'tk_company' => '_tk_inq_company',
		'tk_phone'   => '_tk_inq_phone',
		'tk_country' => '_tk_inq_country',
		'tk_product' => '_tk_inq_product',
		'tk_form'    => '_tk_inq_form',
	);

	if ( ! isset( $map[ $column ] ) ) {
		return;
	}

	$value = get_post_meta( $post_id, $map[ $column ], true );

	if ( 'tk_email' === $column && $value ) {
		printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $value ) );
	} else {
		echo $value ? esc_html( $value ) : '—';
	}
}
add_action( 'manage_inquiry_posts_custom_column', 'tuskolift_inquiry_column_content', 10, 2 );

/* =========================================================================
 * 2. 把 WPForms 的提交抄一份进来
 * ========================================================================= */

/**
 * WPForms 提交成功后的镜像存档。
 *
 * @param array $fields   清洗后的字段数据。
 * @param array $entry    原始提交数据。
 * @param array $form_data 表单配置。
 * @param int   $entry_id 投稿 ID（Lite 版为 0）。
 */
function tuskolift_archive_wpforms_entry( $fields, $entry, $form_data, $entry_id ) {
	if ( empty( $fields ) || ! is_array( $fields ) ) {
		return;
	}

	$values = array();

	foreach ( $fields as $field ) {
		if ( empty( $field['name'] ) ) {
			continue;
		}

		$value = isset( $field['value'] ) ? $field['value'] : '';

		if ( is_array( $value ) ) {
			$value = implode( ', ', array_filter( $value ) );
		}

		$values[ $field['name'] ] = trim( (string) $value );
	}

	if ( ! $values ) {
		return;
	}

	/*
	 * 字段名到 meta 键的映射。
	 * 前台的字段标签是英文（Name / Email / Company …），但编辑者可能改标签，
	 * 所以这里按「标签的小写形式」做宽松匹配，匹配不到就存成通用 meta。
	 */
	$known = array(
		'name'                 => '_tk_inq_name',
		'email'                => '_tk_inq_email',
		'company'              => '_tk_inq_company',
		'phone'                => '_tk_inq_phone',
		'country'              => '_tk_inq_country',
		'product / application' => '_tk_inq_product',
		'load capacity'        => '_tk_inq_capacity',
		'lifting height'       => '_tk_inq_height',
		'quantity'             => '_tk_inq_quantity',
	);

	$title_parts = array();

	if ( ! empty( $values['Name'] ) ) {
		$title_parts[] = $values['Name'];
	}

	if ( ! empty( $values['Company'] ) ) {
		$title_parts[] = $values['Company'];
	}

	if ( ! $title_parts && ! empty( $values['Email'] ) ) {
		$title_parts[] = $values['Email'];
	}

	$title = $title_parts ? implode( ' — ', $title_parts ) : 'Website inquiry';

	$message = '';
	foreach ( array( 'Message', 'Additional Requirements' ) as $key ) {
		if ( ! empty( $values[ $key ] ) ) {
			$message .= $values[ $key ] . "\n\n";
		}
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'inquiry',
			'post_status'  => 'private',
			'post_title'   => $title,
			'post_content' => trim( $message ),
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return;
	}

	foreach ( $values as $label => $value ) {
		if ( '' === $value ) {
			continue;
		}

		$key = isset( $known[ strtolower( $label ) ] ) ? $known[ strtolower( $label ) ] : '_tk_inq_' . sanitize_key( $label );

		update_post_meta( $post_id, $key, $value );
	}

	// 记录来源表单与页面，便于追溯。
	if ( ! empty( $form_data['settings']['form_title'] ) ) {
		update_post_meta( $post_id, '_tk_inq_form', $form_data['settings']['form_title'] );
	}

	if ( ! empty( $form_data['id'] ) ) {
		update_post_meta( $post_id, '_tk_inq_form_id', (int) $form_data['id'] );
	}

	if ( ! empty( $entry['entry_meta']['page_url'] ) ) {
		update_post_meta( $post_id, '_tk_inq_page', $entry['entry_meta']['page_url'] );
	}
}
add_action( 'wpforms_process_complete', 'tuskolift_archive_wpforms_entry', 10, 4 );

/**
 * 询盘按时间倒序。
 *
 * @param WP_Query $query 查询。
 */
function tuskolift_inquiry_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'inquiry' === $query->get( 'post_type' ) ) {
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
	}
}
add_action( 'pre_get_posts', 'tuskolift_inquiry_admin_order' );
