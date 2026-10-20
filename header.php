<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package TUSKOLIFT
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    /*
     * 站点图标。
     *
     * 实测问题（docs/VERIFICATION-RESULT.md 第 11 项）：页面原本没有声明任何
     * <link rel="icon">，浏览器因此请求 /favicon.ico，而 WordPress 核心会把它
     * 302 重定向到 wp-includes/images/w-logo-gray-white-bg.png —— WordPress 自己的
     * 默认 logo，不是 TUSKOLIFT 的品牌标识。
     *
     * assets/icon-*.png 由 bin 脚本从 screenshot.png 居中裁切生成（32 / 180）。
     * 文件不存在时什么都不输出，不会产生 404。
     */
    $tk_icon_dir = get_template_directory();
    $tk_icon_uri = get_template_directory_uri();
    ?>

    <?php if ( file_exists( $tk_icon_dir . '/assets/icon-32.png' ) ) : ?>
        <link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( $tk_icon_uri . '/assets/icon-32.png' ); ?>" />
    <?php endif; ?>

    <?php if ( file_exists( $tk_icon_dir . '/assets/icon-180.png' ) ) : ?>
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( $tk_icon_uri . '/assets/icon-180.png' ); ?>" />
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'tuskolift' ); ?></a>

	<header class="site-header" id="siteHeader">
		<div class="header-inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand">TUSKOLIFT</a>

			<button class="mobile-nav-toggle" type="button" aria-label="<?php esc_attr_e( 'Open navigation menu', 'tuskolift' ); ?>" aria-expanded="false" aria-controls="mainNav">
				<span></span>
				<span></span>
				<span></span>
			</button>

			<nav class="main-nav" id="mainNav">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<div class="nav-dropdown">
					<a href="<?php echo esc_url( home_url( '/products/' ) ); ?>">Products</a>
					<div class="nav-dropdown-menu">
						<a href="<?php echo esc_url( home_url( '/products/' ) . '#pallet-trucks' ); ?>">Pallet Trucks</a>
						<a href="<?php echo esc_url( home_url( '/products/' ) . '#lift-tables' ); ?>">Scissor Lift Tables</a>
						<a href="<?php echo esc_url( home_url( '/products/' ) . '#pallet-stackers' ); ?>">Pallet Stackers</a>
						<a href="<?php echo esc_url( home_url( '/products/' ) . '#high-lift' ); ?>">High-Lift</a>
                        <a href="<?php echo esc_url( home_url( '/products/' ) . '#non-standard' ); ?>">Non-Standard Products</a>
                        <a href="<?php echo esc_url( home_url( '/products/' ) . '#stainless-steel' ); ?>">Stainless Steel Products</a>
					</div>
				</div>
				<div class="nav-dropdown">
					<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About Us</a>
					<div class="nav-dropdown-menu">
						<a href="<?php echo esc_url( home_url( '/about/about-company/' ) ); ?>">Company Profile</a>
						<a href="<?php echo esc_url( home_url( '/about/about-quality/' ) ); ?>">Quality Assurance</a>
					</div>
				</div>
				<div class="nav-dropdown">
					<a href="<?php echo esc_url( home_url( '/offerings/' ) ); ?>">Offerings</a>
					<div class="nav-dropdown-menu">
						<a href="<?php echo esc_url( home_url( '/offerings/offerings-manufacturing/' ) ); ?>">Warehouse Solutions</a>
						<a href="<?php echo esc_url( home_url( '/offerings/offerings-solutions/' ) ); ?>">Applications</a>
					</div>
				</div>
				<div class="nav-dropdown">
					<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a>
					<div class="nav-dropdown-menu">
						<a href="<?php echo esc_url( home_url( '/blog/blog-news/' ) ); ?>">Company News</a>
						<a href="<?php echo esc_url( home_url( '/blog/blog-insights/' ) ); ?>">Industry Insights</a>
					</div>
				</div>
				<div class="nav-dropdown">
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a>
					<div class="nav-dropdown-menu">
						<a href="<?php echo esc_url( home_url( '/contact/contact-inquiry/' ) ); ?>">Sales Inquiry</a>
						<a href="<?php echo esc_url( home_url( '/contact/contact-locations/' ) ); ?>">Global Locations</a>
					</div>
				</div>
			</nav>
			<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="nav-cta">Get A Quote</a>
		</div>
	</header><!-- #masthead -->
