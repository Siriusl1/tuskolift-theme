<?php
/**
 * 404 页面
 *
 * 改写了 Underscores 的默认版本：原来输出的是「最近文章 / 分类 / 归档 / 标签云」
 * 等未加样式的小工具，语言还是中文。现在改用站点自己的
 * .page-hero / .section / .panel 体系，并给出常用页面入口 + 站内搜索。
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main">

	<section class="page-hero">
		<div class="page-hero-inner">
			<p class="eyebrow">Error 404</p>
			<h1>This page could not be found.</h1>
			<p>The link may be out of date, or the page may have been moved. Try a search, or use one of the shortcuts below.</p>

			<div class="tk-search-hero-form">
				<?php get_template_part( 'template-parts/search-form' ); ?>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="section-inner">
			<div class="section-heading">
				<div>
					<p class="eyebrow">Popular destinations</p>
					<h2>Where would you like to go?</h2>
				</div>
				<p>These are the pages visitors look for most often.</p>
			</div>

			<div class="grid-3">
				<a class="panel" href="<?php echo esc_url( home_url( '/products/' ) ); ?>">
					<span class="number">01</span>
					<h3>Product Catalog</h3>
					<p>Browse the full material handling range by product line or by application.</p>
				</a>
				<a class="panel" href="<?php echo esc_url( home_url( '/offerings/' ) ); ?>">
					<span class="number">02</span>
					<h3>Offerings</h3>
					<p>Manufacturing systems and industry solutions.</p>
				</a>
				<a class="panel" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
					<span class="number">03</span>
					<h3>Blog</h3>
					<p>Company news and industry insights on material handling.</p>
				</a>
				<a class="panel" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">
					<span class="number">04</span>
					<h3>About Us</h3>
					<p>Company profile, quality assurance and factory capability.</p>
				</a>
				<a class="panel" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<span class="number">05</span>
					<h3>Contact</h3>
					<p>Send your requirements and get a recommendation within one business day.</p>
				</a>
				<a class="panel" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="number">06</span>
					<h3>Home</h3>
					<p>Back to the homepage.</p>
				</a>
			</div>
		</div>
	</section>

</main><!-- #main -->

<?php
get_footer();
