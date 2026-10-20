<?php
/**
 * TUSKOLIFT — 模板辅助函数
 *
 * 把「文章分类」与「查询逻辑」集中在这里，模板里只负责渲染。
 * 分类 slug 都走 filter，需要改名时改一处即可。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 输出英文日期。
 *
 * 不能直接用 get_the_date()：它会走 date_i18n()，跟随站点语言（本站是 zh_CN），
 * 于是 2026-10-09 会显示成「09 10月 2026」。mysql2date() 传 false 就跳过本地化，
 * 始终输出英文月份，符合本站面向海外客户的要求。
 *
 * @param string       $format PHP 日期格式。
 * @param int|WP_Post  $post   文章，默认当前文章。
 * @return string
 */
function tuskolift_date_en( $format = 'd M Y', $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	return mysql2date( $format, $post->post_date, false );
}

/**
 * 判断当前页面是否使用了指定的页面模板。
 *
 * 为什么不用 WordPress 自带的 is_page_template()：
 * 本站在导入时，部分页面的 _wp_page_template 里存的是「模板名称」（如 "Blog News Page"）
 * 而不是文件名（page-blog-news.php）。is_page_template() 是字符串直接比对，
 * 遇到这种数据就匹配不上，会导致该页面的专属 CSS 不加载。
 *
 * 这里同时接受文件名与模板名称，两种存法都能命中，比较稳健。
 *
 * @param string|string[] $files 模板文件名（可多个）。
 * @return bool
 */
function tuskolift_is_page_template( $files ) {
	if ( ! is_singular() ) {
		return false;
	}

	$stored = get_post_meta( get_queried_object_id(), '_wp_page_template', true );

	if ( ! $stored ) {
		return false;
	}

	$registered = wp_get_theme()->get_page_templates( null, 'page' );

	foreach ( (array) $files as $file ) {
		if ( $stored === $file ) {
			return true;
		}

		// _wp_page_template 存的是模板名称的情况。
		if ( isset( $registered[ $file ] ) && $stored === $registered[ $file ] ) {
			return true;
		}
	}

	return false;
}

/**
 * 公司新闻分类 slug。
 *
 * @return string
 */
function tuskolift_news_category_slug() {
	return apply_filters( 'tuskolift_news_category_slug', 'news' );
}

/**
 * 行业洞察分类 slug。
 *
 * @return string
 */
function tuskolift_insights_category_slug() {
	return apply_filters( 'tuskolift_insights_category_slug', 'insights' );
}

/**
 * 统一的文章查询。
 *
 * @param array $args WP_Query 参数，会与默认值合并。
 * @return WP_Query
 */
function tuskolift_query_posts( $args = array() ) {
	return new WP_Query(
		array_merge(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => 3,
				'ignore_sticky_posts' => true,
			),
			$args
		)
	);
}

/**
 * 取多个分类的文章查询。
 *
 * @param string[] $slugs 分类 slug 数组。
 * @param int      $limit 条数。
 * @return WP_Query
 */
function tuskolift_query_posts_in_categories( $slugs, $limit = 3 ) {
	$slugs = array_filter( (array) $slugs );

	if ( ! $slugs ) {
		return tuskolift_query_posts( array( 'posts_per_page' => $limit ) );
	}

	return tuskolift_query_posts(
		array(
			'posts_per_page' => $limit,
			'category_name'  => implode( ',', $slugs ),
		)
	);
}

/**
 * 取产品线的展示配图。
 *
 * 优先用术语自带的「分类配图」；没设置时退回该产品线下第一个商品的特色图片，
 * 都没有则返回空数组，由模板输出占位。
 *
 * @param WP_Term $term 产品线术语。
 * @return array{url:string,alt:string}
 */
function tuskolift_product_line_image( $term ) {
	$url = tuskolift_term_image_url( $term->term_id, 'tk-card' );

	if ( $url ) {
		return array(
			'url' => $url,
			'alt' => $term->name,
		);
	}

	$first = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'ignore_sticky_posts' => true,
			'fields'              => 'ids',
			'tax_query'           => array(
				array(
					'taxonomy' => $term->taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);

	if ( $first->have_posts() ) {
		$thumb_id = get_post_thumbnail_id( $first->posts[0] );

		if ( $thumb_id ) {
			$src = wp_get_attachment_image_src( $thumb_id, 'tk-card' );

			if ( $src ) {
				return array(
					'url' => $src[0],
					'alt' => get_the_title( $first->posts[0] ),
				);
			}
		}
	}

	return array(
		'url' => '',
		'alt' => $term->name,
	);
}

/**
 * 输出「查看某个分类全部文章」的链接。
 *
 * 列表页只展示固定条数，分类归档页才有完整分页，这里把两者接起来。
 *
 * @param string $slug  分类 slug。
 * @param string $label 链接文字。
 */
function tuskolift_render_category_archive_link( $slug, $label ) {
	$term = get_term_by( 'slug', $slug, 'category' );

	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}
	?>
	<p class="tk-archive-link">
		<a class="text-link" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $label ); ?> →</a>
	</p>
	<?php
}

/**
 * 输出文章列表（带空状态）。
 *
 * @param WP_Query $query 查询对象。
 * @param string   $empty 无文章时的提示。
 */
function tuskolift_render_news_grid( $query, $empty = '' ) {
	if ( $query->have_posts() ) {
		echo '<div class="grid-3">';
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/news-card' );
		}
		echo '</div>';
	} else {
		printf(
			'<p class="tk-empty">%s</p>',
			esc_html( $empty ? $empty : 'No articles yet. Publish your first post and it will appear here.' )
		);
	}

	wp_reset_postdata();
}

/**
 * 输出列表区标题（文案来自页面文案字段）。
 *
 * @param string $fallback_eyebrow 兜底小标签。
 * @param string $fallback_title   兜底标题。
 * @param string $fallback_intro   兜底说明。
 */
function tuskolift_render_list_heading( $fallback_eyebrow = '', $fallback_title = '', $fallback_intro = '' ) {
	$page_id = get_the_ID();
	$eyebrow = tuskolift_get_page_copy( '_tk_list_eyebrow', $page_id );
	$title   = tuskolift_get_page_copy( '_tk_list_title', $page_id );
	$intro   = tuskolift_get_page_copy( '_tk_list_intro', $page_id );

	if ( ! $eyebrow ) {
		$eyebrow = $fallback_eyebrow;
	}
	if ( ! $title ) {
		$title = $fallback_title;
	}
	if ( ! $intro ) {
		$intro = $fallback_intro;
	}

	if ( ! $eyebrow && ! $title && ! $intro ) {
		return;
	}
	?>
	<div class="section-heading">
		<div>
			<?php if ( $eyebrow ) : ?>
				<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<?php if ( $title ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>
		</div>
		<?php if ( $intro ) : ?>
			<p><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/* =========================================================================
 * 前台强制英文（只针对插件与核心文案，后台保持中文）
 * ========================================================================= */

/**
 * 让前台显示英文原文，后台仍然显示中文。
 *
 * 本站后台语言是 zh_CN，但站点面向海外客户。受影响的典型场景：
 *   - 评论表单（「发表回复」「您的邮箱地址不会被公开」「必填项已用 * 标注」…）
 *   - 文章上下篇导航的无障碍文本（「文章导航」）
 *   - WPForms 的校验提示（「该字段是必填项。」）
 *
 * 这里在前台把这些文本域还原成英文原文；真正的后台页面不受影响。
 *
 * 注意：日期月份不归 gettext 管（date_i18n 用的是 WP_Locale），
 * 那些地方用 tuskolift_date_en() 单独处理。
 *
 * @param string $translated 翻译后的文本。
 * @param string $original   英文原文。
 * @param string $domain     文本域。
 * @return string
 */
function tuskolift_english_on_frontend( $translated, $original, $domain ) {
	$domains = array( 'default', 'wpforms-lite' );

	if ( ! in_array( $domain, $domains, true ) ) {
		return $translated;
	}

	/*
	 * 判断当前请求是不是「前台」。
	 *
	 * 注意：前台的 WPForms 提交走 wp-admin/admin-ajax.php，
	 * 那种请求 is_admin() 会返回 true，所以要单独识别出来。
	 * 但后台表单编辑器也走 AJAX（action 是别的值），不能一起放行，
	 * 否则后台界面会突然变成英文。
	 */
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- 只用于比较，不做任何写入。
	$action             = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
	$is_frontend_submit = wp_doing_ajax() && 'wpforms_submit' === $action;

	if ( is_admin() && ! $is_frontend_submit ) {
		return $translated; // 后台页面与后台 AJAX 一律保留中文。
	}

	return $original;
}
add_filter( 'gettext', 'tuskolift_english_on_frontend', 10, 3 );
