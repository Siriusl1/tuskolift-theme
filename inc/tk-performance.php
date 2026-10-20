<?php
/**
 * TUSKOLIFT — 渲染性能与上线前的加速设置
 *
 * 商品上到上千时，真正拖慢访问的不是「页面数量」而是这几件事：
 *   1. 一次把上千条商品塞进一个页面（已由 inc/tk-catalog-rest.php 的服务端分页解决）；
 *   2. 列表页加载原图（一张几 MB 的图 × 几十张，首屏直接废掉）；
 *   3. 每次访问都实时跑分类计数、特色图、术语表等查询；
 *   4. 页头塞了一堆用不到的东西（emoji 脚本、区块库 CSS、REST 链接等）。
 *
 * 这个文件集中处理后三项。
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 1. 图片：让列表页永远不加载原图
 * ========================================================================= */

/**
 * 给商品特色图注册一个「目录卡片」尺寸。
 *
 * 目录卡片的图片容器是正方形，原图往往 1000px 以上、单张 1~3 MB。
 * 用固定尺寸的小图可以把这个量级压到几十 KB。
 */
function tuskolift_image_sizes() {
	add_image_size( 'tk-card', 560, 560, true );      // 目录卡片（2 倍屏也够）。
	add_image_size( 'tk-card-sm', 300, 300, true );   // 移动端 / 相关商品小图。
}
add_action( 'after_setup_theme', 'tuskolift_image_sizes' );

/**
 * 删除：原来这里有个 tuskolift_featured_image_quality()。
 *
 * 它挂在 wp_get_attachment_image_attributes 上，企图通过 $args['quality'] = 82
 * 影响输出图片的质量。**这个过滤器拿到的 $args 是 HTML 属性数组，核心并不认 quality**，
 * 所以它对图片质量毫无作用；副作用反而是把 `quality="82"` 当成普通属性写进 <img>。
 *
 * 实测（docs/VERIFICATION-RESULT.md 第 8/11 项）：
 *   - 修复前首页 quality= 出现 0 次 —— 因为当时没有任何渲染路径用到 tk-card/medium_large
 *     这个尺寸名，过滤器从未命中，所以「不存在非法属性」这个结论是**碰巧成立**的；
 *   - 启用 tk-card 之后首页立即出现 4 处、商品详情页 1 处 `quality="82"`，
 *     说明 AUDIT-REPORT.md 附录 B 第 5 条「推翻：实测 0 次命中」不成立。
 *
 * 真正生效的是下面的 jpeg_quality 过滤器，这里直接删掉死代码。
 */

/**
 * 限制生成的多余图片尺寸，减少每张图占用的磁盘与生成耗时。
 *
 * 商品站的图片只需要：缩略图、卡片图、内容图、大图。
 * 去掉 1536x1536 / 2048x2048 这两个超大尺寸（商品详情也不需要那么大的图）。
 *
 * @param array $sizes 尺寸定义。
 * @return array
 */
function tuskolift_filter_image_sizes( $sizes ) {
	unset( $sizes['1536x1536'], $sizes['2048x2048'] );

	return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'tuskolift_filter_image_sizes' );

/* -------------------------------------------------------------------------
 * 1b. 把「图片上传规范」变成程序自动执行，而不是只写在说明书里
 *
 * 使用指南里要求运营：正方形白底、最长边 ≤ 1600px、单张 ≤ 500KB。
 * 但人总会忘。下面几条让 WordPress 在上传时自动兜底：
 *   - 超大图自动缩到最长边 1600px（原图 4000px 也不会拖慢前台）；
 *   - JPEG 统一压到质量 82；
 *   - 超过 500KB 时在后台给编辑一条提示（不阻断上传，只提醒）。
 *
 * 注意：这些都只作用于「新上传」。
 *       已经存在的历史图片不受影响 —— 批量重新生成留到 staging/生产做。
 * ----------------------------------------------------------------------- */

/**
 * 上传图片的最长边上限（像素）。
 *
 * 与使用指南里的「最长边 ≤ 1600px」保持一致。
 * 详情页大图显示宽度约 1024px，1600px 在 2 倍屏上也够清晰。
 */
define( 'TUSKOLIFT_MAX_IMAGE_EDGE', 1600 );

/**
 * 上传图片的体积提醒阈值（字节）。
 *
 * 500KB。超过只提示、不拒绝 —— 硬性拒绝会让运营在紧急上架时卡住，
 * 而提示足以让人回去压缩。
 */
define( 'TUSKOLIFT_IMAGE_WARN_BYTES', 500 * 1024 );

/**
 * 用「大图缩放」机制自动把超长边的图缩下来。
 *
 * WordPress 自带的 big_image_size_threshold 本来就是这个用途：
 * 上传一张超过阈值的图时，它会自动生成一张缩小的「原图」并把它设为新原图。
 * 默认值是 2560，对本站偏大（1600 足够），这里改小。
 *
 * 用 filter 而不是直接改 option，是为了让设置跟着主题走、可版本控制。
 *
 * @param int $threshold 原阈值。
 * @return int
 */
function tuskolift_big_image_threshold( $threshold ) {
	return TUSKOLIFT_MAX_IMAGE_EDGE;
}
add_filter( 'big_image_size_threshold', 'tuskolift_big_image_threshold' );

/**
 * JPEG 上传统一压缩质量。
 *
 * WordPress 默认 82 已算合理，但明确写出来避免以后被别的设置改掉，
 * 同时让「为什么要压」这件事在代码里可见。
 *
 * @param int $quality 质量。
 * @return int
 */
function tuskolift_jpeg_quality( $quality ) {
	return 82;
}
add_filter( 'jpeg_quality', 'tuskolift_jpeg_quality' );

/**
 * 上传后用后台提示告诉编辑「这张图有点大」。
 *
 * 只在本次请求刚上传完时提示，且使用 transient 传递，
 * 避免刷新页面还一直显示。
 *
 * @param array $upload 上传结果。
 * @return array
 */
function tuskolift_flag_oversized_upload( $upload ) {
	if ( empty( $upload['file'] ) || empty( $upload['type'] ) ) {
		return $upload;
	}

	// 只关心图片。
	if ( 0 !== strpos( (string) $upload['type'], 'image/' ) ) {
		return $upload;
	}

	$path = $upload['file'];

	if ( ! file_exists( $path ) ) {
		return $upload;
	}

	$bytes = (int) filesize( $path );

	if ( $bytes <= TUSKOLIFT_IMAGE_WARN_BYTES ) {
		return $upload;
	}

	// 记下文件名，稍后在 admin_notices 里提示。
	$pending = get_transient( 'tuskolift_oversized_uploads' );
	$pending = is_array( $pending ) ? $pending : array();

	$pending[] = array(
		'name' => wp_basename( $path ),
		'kb'   => (int) round( $bytes / 1024 ),
	);

	set_transient( 'tuskolift_oversized_uploads', $pending, 60 );

	return $upload;
}
add_filter( 'wp_handle_upload', 'tuskolift_flag_oversized_upload' );

/**
 * 在后台显示「刚上传的图片偏大」的提示。
 */
function tuskolift_oversized_upload_notice() {
	if ( ! current_user_can( 'upload_files' ) ) {
		return;
	}

	$pending = get_transient( 'tuskolift_oversized_uploads' );

	if ( ! is_array( $pending ) || ! $pending ) {
		return;
	}

	// 取一次就删掉，避免一直提示。
	delete_transient( 'tuskolift_oversized_uploads' );

	$guide = admin_url( 'admin.php?page=tuskolift-guide' );
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong>有图片体积偏大，建议压缩后重新上传：</strong>
			<?php
			$items = array();

			foreach ( $pending as $item ) {
				$items[] = sprintf( '%s（%d KB）', $item['name'], $item['kb'] );
			}

			echo esc_html( implode( '、', $items ) );
			?>
		</p>
		<p>
			规范是<strong>单张不超过 500KB、最长边不超过 1600px</strong>。
			超大图会拖慢前台加载（尤其是手机端）。
			可以先用 Squoosh 或 TinyPNG 压一下，再重新上传替换。
			详见 <a href="<?php echo esc_url( $guide ); ?>">使用指南 → 图片上传规范</a>。
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'tuskolift_oversized_upload_notice' );

/* =========================================================================
 * 2. 去掉前台用不到的东西
 * ========================================================================= */

/**
 * 关掉 emoji 脚本。
 *
 * WordPress 默认会在每个页面注入一段 emoji 检测脚本 + 一个小图片请求。
 * 站点文案是英文、不用 emoji 表情，直接去掉。
 */
function tuskolift_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'tuskolift_disable_emoji' );

/**
 * 前台去掉区块编辑器的 CSS。
 *
 * 本站前台全部由主题模板渲染，正文用的是经典内容，没有使用古腾堡的区块样式。
 * 但 WordPress 默认会在前台加载 wp-block-library 等约 100 KB 的 CSS。
 */
function tuskolift_dequeue_block_css() {
	if ( is_admin() ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'tuskolift_dequeue_block_css', 100 );

/**
 * 去掉页头里前台用不到的 meta 标签。
 *
 * - wp_generator：暴露 WordPress 版本号（安全性 + 少一次输出）
 * - rsd_link / wlwmanifest：老式博客客户端发现协议，用不到
 * - REST API 的 <link rel="https://api.w.org/">：本站前台不依赖它
 */
function tuskolift_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
}
add_action( 'init', 'tuskolift_clean_head' );

/* =========================================================================
 * 3. 长列表的默认排序与查询优化
 * ========================================================================= */

/**
 * 商品归档页按 menu_order 再按标题排序，并关掉 sticky 处理。
 *
 * @param WP_Query $query 查询对象。
 */
function tuskolift_catalog_query_tuning( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// 商品归档页：按后台「排序」字段，其次按标题，保证翻页顺序稳定。
	if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( array( 'product_line', 'application' ) ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$query->set( 'ignore_sticky_posts', true );
	}
}
add_action( 'pre_get_posts', 'tuskolift_catalog_query_tuning' );

/* =========================================================================
 * 4. 给「询盘记录」后台列表减负
 * ========================================================================= */

/**
 * 询盘后台列表不做计数查询（后台列表页不需要精确总数，翻页足够）。
 *
 * @param WP_Query $query 查询对象。
 */
function tuskolift_inquiry_light_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'edit-inquiry' === $screen->id ) {
		$query->set( 'no_found_rows', true );
	}
}
add_action( 'pre_get_posts', 'tuskolift_inquiry_light_query' );

/* =========================================================================
 * 5. 资源加载：给静态资源加长缓存头（依赖服务器支持，Apache 下自动生效）
 * ========================================================================= */

/**
 * 通过 .htaccess 风格的资源缓存由服务器负责；这里只做「版本号」保证更新能生效。
 *
 * 主题的 CSS/JS 都用 _S_VERSION 做版本参数（functions.php 顶部），
 * Vue 产物用 my_enqueue_vue_products() 里的独立版本号 —— 两者改文件后都要 +1，
 * 否则浏览器会拿旧缓存。这一点在文档里已单独强调。
 */

/**
 * 在后台给管理员一个直观的「商品规模」提示。
 *
 * 商品超过 500 个时提醒：目录页依赖服务端分页，若改回一次性拉取会立刻变慢。
 */
function tuskolift_scale_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'dashboard' !== $screen->id ) {
		return;
	}

	$count = (int) wp_count_posts( 'product' )->publish;
	if ( $count < 500 ) {
		return;
	}
	?>
	<div class="notice notice-info">
		<p>
			<strong>商品规模提示：</strong>当前已发布 <strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong> 个商品。
			前台目录走服务端分页（每页 24 条，「加载更多」按需取），因此商品数量增加不会拖慢首屏。
			如果以后要改回一次性拉取，请先看主题文档里的性能说明。
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'tuskolift_scale_notice' );
