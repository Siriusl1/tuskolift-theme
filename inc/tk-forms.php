<?php
/**
 * 询盘表单 ID 的集中管理 + 缺表单时的可见提醒。
 *
 * 背景（docs/AUDIT-REPORT.md P0-8）：
 *   表单 ID `9` 与 `97` 硬编码在 3 个模板里。一旦 wpforms-lite 被停用、或某个表单被删除，
 *   前台表单**渲染为空**，而询盘会 100% 静默丢失、没有任何错误提示。
 *
 * 本文件：
 *   1. 把两个 ID 提为常量，并允许用 filter / option 覆盖（换表单不必改模板）；
 *   2. 表单不存在时：前台给出明确提示（而不是空白），后台给出 error 级提醒。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'TUSKOLIFT_FORM_CONTACT' ) ) {
	/**
	 * 「联系我们」表单（Contact Inquiry）的 ID。当前实测为 9。
	 */
	define( 'TUSKOLIFT_FORM_CONTACT', (int) apply_filters( 'tuskolift_form_contact_id', 9 ) );
}

if ( ! defined( 'TUSKOLIFT_FORM_QUOTE' ) ) {
	/**
	 * 首页「报价请求」表单（Homepage Quote Request）的 ID。当前实测为 97。
	 */
	define( 'TUSKOLIFT_FORM_QUOTE', (int) apply_filters( 'tuskolift_form_quote_id', 97 ) );
}

if ( ! defined( 'TUSKOLIFT_FORM_TOPIC' ) ) {
	/**
	 * 「选题建议」表单（Blog → Suggest a topic）的 ID。
	 */
	define( 'TUSKOLIFT_FORM_TOPIC', (int) apply_filters( 'tuskolift_form_topic_id_raw', 1119 ) );
}

/**
 * 「选题建议」表单 ID。
 *
 * 这是给访客提交「想读到什么」的入口，和销售询盘是两条业务线：
 *   - 询盘 → 落库为「询盘记录」，走销售跟进；
 *   - 选题 → 落库为「选题建议」，走内容运营（采纳 → 写成文章 → 发布）。
 *
 * 用函数而不是直接读常量，方便测试与多环境用 filter 覆盖。
 *
 * @return int
 */
function tuskolift_topic_form_id() {
	return (int) apply_filters( 'tuskolift_form_topic_id', TUSKOLIFT_FORM_TOPIC );
}

/**
 * 渲染一个 WPForms 表单，并在表单缺失时给出明确提示。
 *
 * @param int    $form_id 表单 ID。
 * @param string $context 上下文，仅用于后台提示文案。
 * @return void
 */
function tuskolift_render_inquiry_form( $form_id, $context = '' ) {
	$form_id = (int) $form_id;

	// 表单不存在（插件停用 / 表单被删）时，不能静默输出空字符串。
	if ( ! $form_id || ! get_post( $form_id ) || 'wpforms' !== get_post_type( $form_id ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			printf(
				'<p class="tk-form-missing" role="alert"><strong>表单未就绪。</strong> 找不到 WPForms 表单 #%d%s。'
				. '请启用 WPForms Lite 插件，或在后台「WPForms → 所有表单」确认该表单仍在。</p>',
				$form_id,
				$context ? '（' . esc_html( $context ) . '）' : ''
			);
		} else {
			printf(
				'<p class="tk-form-missing" role="alert">Our inquiry form is temporarily unavailable. '
				. 'Please e-mail us using the address on the <a href="%s">contact page</a>.</p>',
				esc_url( home_url( '/contact/contact-locations/' ) )
			);
		}

		return;
	}

	echo do_shortcode( sprintf( '[wpforms id="%d"]', $form_id ) );
}

/**
 * 后台提醒：询盘表单不齐。
 */
function tuskolift_inquiry_form_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$broken = array();
	foreach ( array(
		'联系页 / 询盘页表单' => TUSKOLIFT_FORM_CONTACT,
		'首页报价表单'       => TUSKOLIFT_FORM_QUOTE,
		'Blog 选题建议表单'  => tuskolift_topic_form_id(),
	) as $label => $id ) {
		$post = get_post( $id );
		if ( ! $post || 'wpforms' !== $post->post_type ) {
			$broken[] = $label . '（#' . $id . '）';
		}
	}

	if ( ! $broken ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>表单缺失：</strong>%s。'
		. '访客的提交不会落库也不会发信。请启用 WPForms Lite，或修改 '
		. '<code>inc/tk-forms.php</code> 里的 <code>TUSKOLIFT_FORM_CONTACT</code> / '
		. '<code>TUSKOLIFT_FORM_QUOTE</code> / <code>TUSKOLIFT_FORM_TOPIC</code> 指向正确表单。</p></div>',
		esc_html( implode( '、', $broken ) )
	);
}
add_action( 'admin_notices', 'tuskolift_inquiry_form_notice' );
