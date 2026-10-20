<?php
/**
 * TUSKOLIFT functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package TUSKOLIFT
 */

if ( ! defined( '_S_VERSION' ) ) {
	/*
	 * 全站 CSS/JS 的版本号（style.css / common.css / index.css / main.js 都用它）。
	 * 改了这几个文件就要把这个号 +1，否则浏览器会一直用缓存里的旧版本。
	 *
	 * 注意：Vue 产物（dist/assets/）**不用**这个号 —— 它是构建时自动算的，
	 * 见下面的 tuskolift_vue_asset_version()。
	 */
	define( '_S_VERSION', '1.6.0' );
}

if ( ! defined( 'TK_VUE_VERSION' ) ) {
	/*
	 * Vue 产物的「兜底」版本号。
	 *
	 * 正常情况下用不到：my_enqueue_vue_products() 会去读构建生成的
	 * dist/assets/version.json，那里面的版本号是按产物内容算出来的哈希，
	 * 内容一变就自动变，不需要人工维护。
	 *
	 * 只有在这种情况才会退回到这个常量：
	 *   - 还没跑过 npm run build（dist/assets/version.json 不存在）
	 *   - 有人手工删掉了那个文件
	 * 所以它的作用只是「保证不会因为读不到版本号而出错」。
	 */
	define( 'TK_VUE_VERSION', '1.6.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function tuskolift_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on TUSKOLIFT, use a find and replace
		* to change 'tuskolift' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'tuskolift', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'tuskolift' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'tuskolift_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'tuskolift_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function tuskolift_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'tuskolift_content_width', 640 );
}
add_action( 'after_setup_theme', 'tuskolift_content_width', 0 );

/*
 * 说明：这里原本有 tuskolift_widgets_init() / register_sidebar()，
 * 是 Underscores 起始主题自带的侧栏注册。本项目的静态站点设计里没有侧栏，
 * 那 5 个 block-* 小工具也只可能在搜索结果页出现，因此整块移除。
 */

/**
 * Enqueue scripts and styles.
 */
function tuskolift_scripts() {
    // 1. 主样式 (style.css)
    wp_enqueue_style( 'tuskolift-style', get_stylesheet_uri(), array(), _S_VERSION );

    // 2. 你本地的 common.css
    wp_enqueue_style( 'tuskolift-common', get_template_directory_uri() . '/css/common.css', array(), _S_VERSION );

    // 3. 你本地的 index.css（新增这一行！）
    wp_enqueue_style( 'tuskolift-index', get_template_directory_uri() . '/css/index.css', array(), _S_VERSION );

    // 4. 你本地的 main.js
    wp_enqueue_script( 'tuskolift-main', get_template_directory_uri() . '/js/main.js', array(), _S_VERSION, true );

    /*
     * 5. 页面独有样式。
     *
     * 这里原来有一张 13 条的 $page_css_map，但 css/pages/ 下实际只存在 3 个文件，
     * 另外 10 条（about / offerings / contact / blog-news …）都指向不存在的 CSS，
     * 靠 file_exists() 兜底才没报错——那些页面的样式本来就写在 css/index.css 里。
     * 现在改成明确的白名单，只按需加载真实存在的文件，并避免与下面的条件重复入队。
     *
     * 注：原来还有一个 products.css，但它是整站静态 CSS 的搬运（1228 行里绝大部分是
     * 死代码，且含一份与 common.css 冲突的页头/导航副本）。商品页现在只需要：
     * 头图 → common.css 的 .page-hero；商品目录 → Vue 组件自带作用域样式；
     * 底部 CTA → common.css 的 .npd-cta。所以该文件已删除，无需再加载。
     */
    $page_css = array();

    // 商品详情页
    if ( is_singular( 'product' ) ) {
        $page_css[] = 'product-single';
    }

    // Blog 列表页 / 文章详情 / 搜索结果 / 分类·标签·日期·作者归档
    // 注意 is_home() 要排除首页：本站 home 就是 front-page.php，不需要 blog 样式。
    $is_blog_context = tuskolift_is_page_template(
        array(
            'page-blog.php',
            'page-blog-news.php',
            'page-blog-insights.php',
        )
    ) || is_singular( 'post' ) || ( is_home() && ! is_front_page() ) || is_category() || is_tag() || is_date() || is_author() || is_search();

    if ( $is_blog_context ) {
        $page_css[] = 'blog';
    }

    foreach ( array_unique( $page_css ) as $css_name ) {
        $css_path = get_template_directory() . '/css/pages/' . $css_name . '.css';

        if ( ! file_exists( $css_path ) ) {
            continue;
        }

        wp_enqueue_style(
            'tuskolift-' . $css_name,
            get_template_directory_uri() . '/css/pages/' . $css_name . '.css',
            array( 'tuskolift-common' ),
            _S_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'tuskolift_scripts' );

/**
 * 后台可编辑字段（页面文案 / 商品规格 / 分类配图）。
 */
require get_template_directory() . '/inc/tk-fields.php';

/**
 * 模板辅助函数（文章查询与列表渲染）。
 */
require get_template_directory() . '/inc/tk-templates.php';

/**
 * 后台编辑体验（使用指南 / 默认分类 / 自动摘要）。
 */
require get_template_directory() . '/inc/tk-admin.php';

/**
 * 询盘表单处理（真实落库 + 邮件通知）。
 */
require get_template_directory() . '/inc/tk-inquiry.php';

/**
 * 商品目录的轻量 REST 接口（服务端分页 + 字段裁剪 + 筛选）。
 * 商品上千时，原来的 /wp/v2/products?_embed&per_page=100 会因核心的 per_page 上限丢数据。
 */
require get_template_directory() . '/inc/tk-catalog-rest.php';

/**
 * 渲染性能与上线加速（图片尺寸、去掉前台无用资源、长列表查询优化）。
 */
require get_template_directory() . '/inc/tk-performance.php';

/**
 * 首页 hero 图片：从媒体库取图（srcset / width-height / 裁切子尺寸），
 * 不再硬编码主题目录里的原始 PNG。
 */
require get_template_directory() . '/inc/tk-hero-images.php';

/**
 * 插件依赖守卫：product / product_line / application 由 my-product-cpt 提供，
 * 插件停用时后台醒目报错，而不是让前台静默变空。
 */
require get_template_directory() . '/inc/tk-plugin-deps.php';

/**
 * 询盘表单 ID 常量 + 表单缺失时的可见提示。
 */
require get_template_directory() . '/inc/tk-forms.php';

/**
 * 文章选题建议（/blog/ 的「Suggest a topic」）。
 * 独立 CPT + 独立落库，和销售询盘分开，避免两条业务线混在一起。
 */
require get_template_directory() . '/inc/tk-topic-request.php';

/**
 * SEO 基础：描述 / canonical / Open Graph / JSON-LD 结构化数据。
 * 装了 Yoast / Rank Math 等 SEO 插件时自动让位。
 */
require get_template_directory() . '/inc/tk-seo.php';

/**
 * 拒绝 HTTP 直接访问主题内部开发文件（docs / bin / vue-products）。
 * 这些文件里有审计细节与工具脚本，必须永远不可公开（docs/AUDIT-REPORT.md P0-2）。
 */
require get_template_directory() . '/inc/tk-private-files.php';

/**
 * 法务页面（Privacy / Terms / Support）。
 * 这三页正文在数据库里（后台可改），inc/tk-legal-content.php 是供代码审查的只读副本。
 * 本文件负责：编辑界面提示「正文在哪改」、页面列表「正文来源」列、副本一致性检查。
 */
require get_template_directory() . '/inc/tk-legal-content.php';
require get_template_directory() . '/inc/tk-legal-pages.php';

/**
 * Implement the Custom Header feature.
 */
/*
 * 页眉图片（Custom Header）与 Theme Customizer 都已移除。
 * 本站的顶部导航、页眉文案全部写死在 header.php 与各页面模板里，
 * 不需要后台再提供一套「页眉图片 / 站点标题颜色」设置。
 */

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * 读取 Vue 产物的版本号（构建时自动生成的内容哈希）。
 *
 * 构建脚本 vue-products/vite-plugins/build-version.js 会在
 * dist/assets/version.json 里写入一个按产物内容算出的哈希：
 *
 *     { "version": "daa79ba47638", "built_at": "...", "files": { ... } }
 *
 * 内容没变 → 哈希不变 → 浏览器继续用缓存；
 * 内容变了 → 哈希自动变 → 前台立刻拿到新文件。
 * 于是「改完 Vue 要记得手动 +1」这件事彻底不需要人记了。
 *
 * 读取失败时退回 TK_VUE_VERSION 常量（例如还没跑过 npm run build）。
 *
 * @return string
 */
function tuskolift_vue_asset_version() {
	static $version = null;

	if ( null !== $version ) {
		return $version;
	}

	$version = TK_VUE_VERSION;

	$manifest = get_template_directory() . '/dist/assets/version.json';

	if ( ! file_exists( $manifest ) ) {
		return $version;
	}

	$raw = file_get_contents( $manifest ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- 本地文件，非远程请求。

	if ( false === $raw ) {
		return $version;
	}

	$data = json_decode( $raw, true );

	if ( is_array( $data ) && ! empty( $data['version'] ) && is_string( $data['version'] ) ) {
		// 只保留字母数字，避免异常内容进入 URL。
		$clean = preg_replace( '/[^A-Za-z0-9._-]/', '', $data['version'] );

		if ( '' !== $clean ) {
			$version = $clean;
		}
	}

	return $version;
}

function my_enqueue_vue_products() {
    // 商品列表页（page-products.php 模板）与商品归档页 /product/ 都需要 Vue。
    if ( ! tuskolift_is_page_template( 'page-products.php' ) && ! is_post_type_archive( 'product' ) ) {
        return;
    }

    $dist_uri = get_template_directory_uri() . '/dist/assets';
    $dist_dir = get_template_directory() . '/dist/assets';

    // 版本号来自构建产物的内容哈希，见 tuskolift_vue_asset_version()。
    // 不需要手工维护：跑一次 npm run build 就会自动更新。
    $vue_version = tuskolift_vue_asset_version();

    // 打包后的 JS：vite.config.js 已把 entryFileNames 固定为 assets/index.js
    if ( file_exists( $dist_dir . '/index.js' ) ) {
        wp_enqueue_script(
            'vue-products',
            $dist_uri . '/index.js',
            array(),
            $vue_version,
            true // 放在 </body> 前，保证 #product-app 已存在
        );
    }

    // 打包后的 CSS：assetFileNames 已固定为 assets/[name].[ext] => index.css
    if ( file_exists( $dist_dir . '/index.css' ) ) {
        wp_enqueue_style(
            'vue-products-style',
            $dist_uri . '/index.css',
            array(),
            $vue_version
        );
    }

    // 把后台维护的分类法（含自定义排序与「前台隐藏」设置）注入给 Vue，
    // 前端就不用再猜顺序，也省掉两个 REST 请求。
    wp_add_inline_script(
        'vue-products',
        'window.TK_CATALOG_TERMS = ' . wp_json_encode( tuskolift_get_catalog_term_config() ) . ';',
        'before'
    );
}
add_action( 'wp_enqueue_scripts', 'my_enqueue_vue_products' );
