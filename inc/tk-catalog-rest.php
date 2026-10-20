<?php
/**
 * TUSKOLIFT — 商品目录的轻量 REST 接口
 *
 * 为什么需要它：
 *   原来的做法是前端请求 /wp-json/wp/v2/products?_embed&per_page=100。
 *   在「商品上千」的规模下这个做法有三个硬伤：
 *     1. WordPress 的 REST per_page 上限写死在核心代码里
 *        （wp-includes/rest-api/endpoints/class-wp-rest-controller.php: maximum => 100），
 *        per_page 传再大也只给 100 条 —— 第 101 个商品起前台**静默消失**；
 *     2. _embed 会把每个商品的特色图对象、分类对象整包塞进响应，
 *        再加上 content.rendered（整篇正文 HTML），响应体会膨胀到前端根本用不到的程度；
 *     3. 没有服务端筛选，前端要先把全部商品拉下来再在浏览器里过滤。
 *
 *   这个接口做三件事：
 *     - 服务端分页（默认 24 条一页），彻底绕开 per_page 上限；
 *     - 只输出目录卡片真正要用的字段（id/title/链接/主图/产品线/应用场景/摘要）；
 *     - 支持按 product_line / application 服务端筛选，前端切筛选走新请求而不是本地过滤。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 目录接口每页默认条数与上限。
 *
 * 上限不是照抄 REST 的 100，而是自己定一个更保守的值：
 * 单页返回 60 条已经足够「一次看到大部分商品」，再大只会拖慢首屏。
 */
define( 'TUSKOLIFT_CATALOG_PER_PAGE', 24 );
define( 'TUSKOLIFT_CATALOG_MAX_PER_PAGE', 60 );

/**
 * 取出一个商品的目录卡片数据。
 *
 * @param WP_Post $post 商品。
 * @return array
 */
function tuskolift_catalog_item( $post ) {
	$product_line_name = '';
	$product_line_slug = '';
	$applications      = array();

	$lines = get_the_terms( $post->ID, 'product_line' );
	if ( $lines && ! is_wp_error( $lines ) ) {
		$line              = $lines[0];
		$product_line_name = $line->name;
		$product_line_slug = $line->slug;
	}

	$apps = get_the_terms( $post->ID, 'application' );
	if ( $apps && ! is_wp_error( $apps ) ) {
		foreach ( $apps as $app ) {
			$applications[] = array(
				'name' => $app->name,
				'slug' => $app->slug,
			);
		}
	}

	// 主图：优先取特色图的 medium 尺寸，避免列表页加载原始大图。
	$featured_image = '';
	$thumb_id       = get_post_thumbnail_id( $post->ID );
	if ( $thumb_id ) {
		$src = wp_get_attachment_image_src( $thumb_id, 'tk-card' );
		if ( ! $src ) {
			$src = wp_get_attachment_image_src( $thumb_id, 'large' );
		}
		if ( $src ) {
			$featured_image = $src[0];
		}
	}

	// 摘要：没有手写摘要就用正文截一段，但绝不返回整篇 content.rendered。
	$excerpt = $post->post_excerpt;
	if ( '' === trim( (string) $excerpt ) ) {
		$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 24, '…' );
	} else {
		$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), 24, '…' );
	}

	/*
	 * 安全（修复的存储型 XSS：REST 不转义 + 前端解码实体 + v-html 注入）
	 *
	 * 主修复在前端（v-html → 文本插值 {{ }}），这里做第二道防线。
	 *
	 * 为什么不用 esc_html()？—— 实测它对本题的 payload 无效，这是 WordPress 的一个反直觉行为：
	 *   核心 esc_html() -> _wp_specialchars() 内部先调 wp_kses_normalize_entities()，
	 *   该函数把「合法命名实体」原样保留（`&lt;` 仍是 `&lt;`），随后
	 *   htmlspecialchars(..., $double_encode = false) 不会再转义它。
	 *   实测对比：
	 *     esc_html('a & b')      === 'a &amp; b'   ← 裸 & 会转义
	 *     esc_html('&lt;img&gt;') === '&lt;img&gt;'  ← 已存的实体原样返回，没变成 &amp;lt;
	 *   Author 写进标题的正是 `&lt;img ...&gt;`（KSES 认为 lt/gt 是合法实体，不剥离），
	 *   所以「加个 esc_html 就安全」在这里并不成立。完整矩阵见 docs/FIX-LOG.md。
	 *
	 * 这里用 strip_tags() + 二次编码（$double = true）双保险：
	 *   - strip_tags()  应对「库里存的其实是字面 <img>」（未来某次改写导致）；
	 *   - 二次编码      应对「库里存的是 &lt;img&gt;」（当前实测情况）——
	 *                   前端即便再次解码，拿到的也只是一层文本实体，不会合成标签。
	 * 代价：术语名里合法的 `&` 会显示成 `&amp;`（只有真正含 & 的名称才会遇到，
	 *      需要时把 $double 置 false 即可，前端文本插值仍然安全）。
	 *
	 * 前端已同时改为文本插值，两层同时存在才是完整修复。
	 */
	$double_encode = true;

	return array(
		'id'                => (int) $post->ID,
		'title'             => tuskolift_catalog_escape_text( get_the_title( $post ), $double_encode ),
		'link'              => esc_url( get_permalink( $post ) ),
		'featured_image'    => esc_url( $featured_image ),
		'excerpt'           => tuskolift_catalog_escape_text( $excerpt, $double_encode ),
		'product_line_name' => tuskolift_catalog_escape_text( $product_line_name, $double_encode ),
		'product_line_slug' => sanitize_title( $product_line_slug ),
		'applications'      => array_map(
			static function ( $app ) use ( $double_encode ) {
				return array(
					'name' => tuskolift_catalog_escape_text( $app['name'], $double_encode ),
					'slug' => sanitize_title( $app['slug'] ),
				);
			},
			$applications
		),
	);
}

/**
 * 把一段纯文本转成「安全交给前端」的形式。
 *
 * @param string $text          原始文本。
 * @param bool   $double_encode 是否对已存实体再做一层编码（默认 true，见调用处说明）。
 * @return string
 */
function tuskolift_catalog_escape_text( $text, $double_encode = true ) {
	$text = strip_tags( (string) $text );

	if ( '' === trim( $text ) ) {
		return '';
	}

	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', (bool) $double_encode );
}

/**
 * 处理 /wp-json/tuskolift/v1/catalog 请求。
 *
 * 支持的查询参数：
 *   line      产品线 slug（all 或省略 = 全部）
 *   apply     应用场景 slug（不传 = 全部）
 *   page      页码，从 1 开始
 *   per_page  每页条数（上限 TUSKOLIFT_CATALOG_MAX_PER_PAGE）
 *
 * @param WP_REST_Request $request 请求。
 * @return WP_REST_Response
 */
function tuskolift_rest_catalog( WP_REST_Request $request ) {
	$line     = sanitize_title( (string) $request->get_param( 'line' ) );
	$apply    = sanitize_title( (string) $request->get_param( 'apply' ) );
	$page     = max( 1, (int) $request->get_param( 'page' ) );
	$per_page = (int) $request->get_param( 'per_page' );

	if ( $per_page < 1 ) {
		$per_page = TUSKOLIFT_CATALOG_PER_PAGE;
	}
	$per_page = min( $per_page, TUSKOLIFT_CATALOG_MAX_PER_PAGE );

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
		'no_found_rows'  => false, // 需要总数来算分页。
	);

	$tax_query = array();
	if ( $line && 'all' !== $line ) {
		$tax_query[] = array(
			'taxonomy' => 'product_line',
			'field'    => 'slug',
			'terms'    => $line,
		);
	}
	if ( $apply ) {
		$tax_query[] = array(
			'taxonomy' => 'application',
			'field'    => 'slug',
			'terms'    => $apply,
		);
	}
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	/*
	 * 响应缓存（transient）。
	 *
	 * 背景（docs/AUDIT-REPORT.md P0-12）：此前每次请求都跑完整个 WP_Query（含一次 COUNT），
	 * 且响应没有任何 Cache-Control。商品目录是**公开只读**数据，只有商品/术语变动时才会变，
	 * 所以按「筛选条件 + 页码」缓存 300 秒；商品保存或术语变动时由
	 * tuskolift_flush_catalog_cache() 立即清空，不会出现"后台改了前台不更新"。
	 */
	$cache_key = 'tuskolift_catalog_' . md5( wp_json_encode( array( $line, $apply, $page, $per_page ) ) );
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		$response = new WP_REST_Response( $cached, 200 );
		$response->header( 'X-TK-Cache', 'HIT' );

		return tuskolift_catalog_cache_headers( $response );
	}

	$query = new WP_Query( $args );
	$items = array();

	foreach ( $query->posts as $post ) {
		$items[] = tuskolift_catalog_item( $post );
	}

	$payload = array(
		'items'       => $items,
		'total'       => (int) $query->found_posts,
		'total_pages' => (int) $query->max_num_pages,
		'page'        => $page,
		'per_page'    => $per_page,
	);

	set_transient( $cache_key, $payload, TUSKOLIFT_CATALOG_CACHE_TTL );
	tuskolift_catalog_cache_register_key( $cache_key );

	$response = new WP_REST_Response( $payload, 200 );
	$response->header( 'X-TK-Cache', 'MISS' );

	return tuskolift_catalog_cache_headers( $response );
}

/**
 * 给目录接口加可缓存响应头。
 *
 * 目录数据是公开只读的，允许浏览器与 CDN 缓存 300 秒（与 transient 同步）；
 * 同时声明 Vary: Accept-Encoding，避免 CDN 把 gzip/非 gzip 变体串了。
 *
 * @param WP_REST_Response $response 响应。
 * @return WP_REST_Response
 */
function tuskolift_catalog_cache_headers( $response ) {
	$response->header( 'Cache-Control', 'public, max-age=' . TUSKOLIFT_CATALOG_CACHE_TTL . ', stale-while-revalidate=60' );
	$response->header( 'Vary', 'Accept-Encoding' );

	return $response;
}

/**
 * 注册目录接口。
 */
function tuskolift_register_catalog_rest() {
	register_rest_route(
		'tuskolift/v1',
		'/catalog',
		array(
			'methods'             => 'GET',
			'callback'            => 'tuskolift_rest_catalog',
			'permission_callback' => '__return_true', // 公开只读数据，前台任何访客都要能读。
			'args'                => array(
				'line'     => array( 'type' => 'string' ),
				'apply'    => array( 'type' => 'string' ),
				'page'     => array( 'type' => 'integer' ),
				'per_page' => array( 'type' => 'integer' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'tuskolift_register_catalog_rest' );

/**
 * 给商品列表加一个缓存的「商品计数」接口用的 transient 前缀。
 *
 * 分类的 count（各产品线/应用场景下有多少商品）在列表页每次都要用，
 * 但它在商品增删时才会变，所以缓存 10 分钟，避免每次访问都跑一遍带 JOIN 的计数查询。
 */
define( 'TUSKOLIFT_TERM_CACHE_KEY', 'tuskolift_catalog_terms' );
define( 'TUSKOLIFT_TERM_CACHE_TTL', 600 );

/*
 * 目录接口（/wp-json/tuskolift/v1/catalog）的响应缓存时长，与下面的 Cache-Control 同步。
 *
 * 原来每次请求都完整跑一遍 WP_Query（含 COUNT），且响应无 Cache-Control ——
 * 商品一多，首页/目录页每次访问都要打一次全量查询。见 docs/AUDIT-REPORT.md P0-12。
 */
if ( ! defined( 'TUSKOLIFT_CATALOG_CACHE_TTL' ) ) {
	define( 'TUSKOLIFT_CATALOG_CACHE_TTL', 300 );
}

/** 目录响应缓存的索引键，用于「一次性清空所有分页组合」。 */
if ( ! defined( 'TUSKOLIFT_CATALOG_CACHE_INDEX' ) ) {
	define( 'TUSKOLIFT_CATALOG_CACHE_INDEX', 'tuskolift_catalog_cache_index' );
}

/**
 * 注册一个目录缓存的键，便于之后批量清空。
 *
 * @param string $key transient 键。
 */
function tuskolift_catalog_cache_register_key( $key ) {
	$index = get_option( TUSKOLIFT_CATALOG_CACHE_INDEX, array() );

	if ( ! is_array( $index ) ) {
		$index = array();
	}

	// 避免无限增长：超过 500 个键就整体丢弃索引（下次 flush 会走「全量删除」）。
	if ( count( $index ) > 500 ) {
		$index = array();
	}

	if ( ! in_array( $key, $index, true ) ) {
		$index[] = $key;
		update_option( TUSKOLIFT_CATALOG_CACHE_INDEX, $index, false );
	}
}

/**
 * 清空所有目录响应缓存。
 */
function tuskolift_flush_catalog_cache() {
	$index = get_option( TUSKOLIFT_CATALOG_CACHE_INDEX, array() );

	if ( is_array( $index ) ) {
		foreach ( $index as $key ) {
			delete_transient( $key );
		}
	}

	delete_option( TUSKOLIFT_CATALOG_CACHE_INDEX );
}

/**
 * 商品/分类变动时清掉计数缓存与目录响应缓存。
 *
 * 注意：原来的 `deleted_post` 是**无参**挂载的，任何文章（包括日志、页面、
 * 甚至 WPForms 提交）被删除都会清一次缓存，属于误触发。这里改成带参，
 * 只对 product 生效。
 *
 * @param int $post_id 被删除的文章 ID。
 */
function tuskolift_flush_term_cache( $post_id = 0 ) {
	delete_transient( TUSKOLIFT_TERM_CACHE_KEY );

	// 非商品删除（日志/页面/表单提交）不影响目录，只清计数缓存即可。
	if ( $post_id && 'product' !== get_post_type( $post_id ) ) {
		return;
	}

	tuskolift_flush_catalog_cache();
}
add_action( 'save_post_product', 'tuskolift_flush_term_cache' );
add_action( 'deleted_post', 'tuskolift_flush_term_cache' );
add_action( 'created_product_line', 'tuskolift_flush_term_cache' );
add_action( 'edited_product_line', 'tuskolift_flush_term_cache' );
add_action( 'delete_product_line', 'tuskolift_flush_term_cache' );
add_action( 'created_application', 'tuskolift_flush_term_cache' );
add_action( 'edited_application', 'tuskolift_flush_term_cache' );
add_action( 'delete_application', 'tuskolift_flush_term_cache' );
