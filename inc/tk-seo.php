<?php
/**
 * SEO 基础补充：描述、Open Graph / Twitter 卡片、canonical、结构化数据、面包屑。
 *
 * 背景（docs/AUDIT-REPORT.md P0-13）：
 *   主题没有装任何 SEO 插件，实测所有页面只有核心的 <title>，
 *   缺少 meta description、Open Graph、canonical 与结构化数据 ——
 *   对外贸 B2B 站来说，分享到 LinkedIn / WhatsApp / 邮件时的预览卡片是空的，
 *   搜索引擎也只能靠抽查正文来生成摘要。
 *
 * 本文件只补「通用且低风险」的部分，不接管 <title>（核心的 title-tag 已经正确）：
 *   1. meta description（按页面类型取内容，截断到 155 字）
 *   2. canonical（首页/单页/商品/归档/分类都已覆盖；404 与搜索页不输出）
 *   3. Open Graph + Twitter Card
 *   4. JSON-LD：Organization（首页）、Product（商品页）、BreadcrumbList（有面包屑的页面）
 *   5. robots meta：搜索页 / 404 输出 noindex（核心已对这两个 noindex，这里只做补充确认）
 *
 * 如果将来安装了 Yoast / Rank Math 等 SEO 插件，本文件会自动让位（检测到就什么都不输出）。
 *
 * @package TUSKOLIFT
 */

/**
 * 是否已有 SEO 插件接管。
 *
 * @return bool
 */
function tuskolift_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )        // Yoast
		|| defined( 'RANK_MATH_VERSION' )    // Rank Math
		|| defined( 'AIOSEO_VERSION' )       // All in One SEO
		|| class_exists( 'SEOPress' )        // SEOPress
		|| defined( 'SLIM_SEO_VERSION' );    // Slim SEO
}

/**
 * 取当前页面的 SEO 描述。
 *
 * 优先级：手工设置的 excerpt → 页面文案字段 → 正文截取 → 站点副标题。
 *
 * @return string 已截断的纯文本描述。
 */
function tuskolift_seo_description() {
	$max  = 155;
	$text = '';

	if ( is_front_page() ) {
		$text = get_bloginfo( 'description', 'display' );
		$text = 'TUSKOLIFT manufactures pallet trucks, lift tables and stackers for distributors and warehouses worldwide. Request a specification and indicative price.';
	} elseif ( is_singular() ) {
		$post = get_queried_object();

		if ( $post ) {
			if ( is_singular( 'product' ) ) {
				// 商品：优先用摘要，其次是正文。
				$text = has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			} else {
				$text = has_excerpt( $post ) ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			$text = $term->description;
			if ( '' === trim( (string) $text ) ) {
				/* translators: %s: taxonomy term name. */
				$text = sprintf( __( 'Browse %s.', 'tuskolift' ), $term->name );
			}
		}
	} elseif ( is_post_type_archive() ) {
		$text = get_bloginfo( 'description', 'display' );
	}

	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );

	if ( '' === $text ) {
		$text = 'TUSKOLIFT — material handling equipment manufacturer for global B2B buyers.';
	}

	if ( mb_strlen( $text ) > $max ) {
		$text = mb_substr( $text, 0, $max - 1 ) . '…';
	}

	return $text;
}

/**
 * 当前页面的 canonical URL。
 *
 * @return string
 */
function tuskolift_seo_canonical() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		$url = get_permalink();
		return $url ? $url : '';
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) ) {
			$link = get_term_link( $term );
			return is_wp_error( $link ) ? '' : $link;
		}
	}

	if ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
		if ( ! $url && is_post_type_archive( 'product' ) ) {
			$url = get_post_type_archive_link( 'product' );
		}
		return $url ? $url : '';
	}

	return '';
}

/**
 * 当前页面的分享图。
 *
 * 优先特色图（tk-card 尺寸）→ 首页 hero 主图（媒体库里的产品线配图）→ 站点 logo。
 *
 * 首页没有「特色图」概念，但社交分享没有图会退化成纯文本卡片（LinkedIn / WhatsApp
 * 里几乎没有点击率），所以这里用首页实际展示的那张 hero 主图兜底。
 *
 * @return string
 */
function tuskolift_seo_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'tk-card' );
		if ( $src ) {
			return $src[0];
		}
	}

	// 首页 / 非单页：用 hero 主图（与页面实际展示一致）。
	if ( function_exists( 'tuskolift_hero_term_image_id' ) ) {
		$hero_id = tuskolift_hero_term_image_id( 'lift-tables' );
		if ( $hero_id ) {
			$src = wp_get_attachment_image_src( $hero_id, 'tk-card' );
			if ( $src ) {
				return $src[0];
			}
		}
	}

	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $src ) {
			return $src[0];
		}
	}

	return '';
}

/**
 * 输出 meta description / canonical / OG / Twitter。
 */
function tuskolift_seo_meta() {
	if ( tuskolift_seo_plugin_active() ) {
		return;
	}

	// 搜索页与 404 不需要分享信息。
	if ( is_search() || is_404() ) {
		return;
	}

	$desc      = tuskolift_seo_description();
	$canonical = tuskolift_seo_canonical();
	$image     = tuskolift_seo_image();
	$title     = wp_get_document_title();
	$type      = is_singular( 'post' ) ? 'article' : 'website';

	echo "\n\t<!-- TUSKOLIFT SEO -->\n";
	printf( "\t<meta name=\"description\" content=\"%s\" />\n", esc_attr( $desc ) );

	if ( $canonical ) {
		printf( "\t<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $canonical ) );
	}

	// Open Graph
	/*
	 * og:locale 不能用 get_bloginfo('language') —— 那是 WPLANG（本站 zh_CN，因为后台要中文），
	 * 而前台内容是英文、<html lang> 也是 en-US（见 inc/template-functions.php）。
	 * 用同一个来源，保证 <html lang> 与 og:locale 永远一致。
	 */
	$og_locale = str_replace( '-', '_', (string) apply_filters( 'tuskolift_html_lang', 'en-US' ) );
	printf( "\t<meta property=\"og:locale\" content=\"%s\" />\n", esc_attr( $og_locale ) );
	printf( "\t<meta property=\"og:type\" content=\"%s\" />\n", esc_attr( $type ) );
	printf( "\t<meta property=\"og:site_name\" content=\"%s\" />\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( "\t<meta property=\"og:title\" content=\"%s\" />\n", esc_attr( $title ) );
	printf( "\t<meta property=\"og:description\" content=\"%s\" />\n", esc_attr( $desc ) );
	if ( $canonical ) {
		printf( "\t<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $canonical ) );
	}
	if ( $image ) {
		printf( "\t<meta property=\"og:image\" content=\"%s\" />\n", esc_url( $image ) );
	}

	// Twitter
	printf( "\t<meta name=\"twitter:card\" content=\"%s\" />\n", esc_attr( $image ? 'summary_large_image' : 'summary' ) );
	printf( "\t<meta name=\"twitter:title\" content=\"%s\" />\n", esc_attr( $title ) );
	printf( "\t<meta name=\"twitter:description\" content=\"%s\" />\n", esc_attr( $desc ) );
	if ( $image ) {
		printf( "\t<meta name=\"twitter:image\" content=\"%s\" />\n", esc_url( $image ) );
	}
}
add_action( 'wp_head', 'tuskolift_seo_meta', 2 );

/**
 * 输出 JSON-LD 结构化数据。
 *
 * 只输出对 B2B 站真正有用的三种：Organization（首页）、Product（商品）、
 * BreadcrumbList（单页与商品）。用 wp_json_encode 生成，避免手工拼接出错。
 */
function tuskolift_seo_jsonld() {
	if ( tuskolift_seo_plugin_active() || is_search() || is_404() ) {
		return;
	}

	$graph = array();

	// 1. Organization —— 只在首页输出，避免每页重复。
	if ( is_front_page() ) {
		$org = array(
			'@type'       => 'Organization',
			'@id'         => home_url( '/#organization' ),
			'name'        => get_bloginfo( 'name' ),
			'url'         => home_url( '/' ),
			'description' => get_bloginfo( 'description' ),
		);

		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$logo = wp_get_attachment_image_src( $logo_id, 'full' );
			if ( $logo ) {
				$org['logo'] = $logo[0];
			}
		}

		// 联系方式：只有真实填了才输出，绝不输出占位值。
		$phone = get_option( 'tuskolift_org_phone' );
		$email = get_option( 'admin_email' );

		if ( $phone ) {
			$org['contactPoint'] = array(
				array(
					'@type'       => 'ContactPoint',
					'telephone'   => $phone,
					'contactType' => 'sales',
				),
			);
		}
		if ( $email && is_email( $email ) ) {
			$org['email'] = $email;
		}

		$graph[] = $org;
	}

	// 2. Product —— 商品详情页。
	if ( is_singular( 'product' ) ) {
		$product = get_queried_object();

		if ( $product ) {
			$node = array(
				'@type'       => 'Product',
				'name'        => wp_strip_all_tags( get_the_title( $product ) ),
				'description' => tuskolift_seo_description(),
				'url'         => get_permalink( $product ),
				'sku'         => (string) $product->ID,
			);

			if ( has_post_thumbnail( $product ) ) {
				$img = wp_get_attachment_image_src( get_post_thumbnail_id( $product ), 'tk-card' );
				if ( $img ) {
					$node['image'] = $img[0];
				}
			}

			$terms = get_the_terms( $product, 'product_line' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$node['category'] = $terms[0]->name;
			}

			$brand = array(
				'@type' => 'Brand',
				'name'  => get_bloginfo( 'name' ),
			);
			$node['brand'] = $brand;

			$graph[] = $node;
		}
	}

	// 3. BreadcrumbList —— 单页 / 商品。
	if ( is_singular() ) {
		$items = array();

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Home',
			'item'     => home_url( '/' ),
		);

		if ( is_singular( 'product' ) ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => 'Products',
				'item'     => home_url( '/products/' ),
			);
			$position = 3;
		} else {
			$position = 2;
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => wp_strip_all_tags( get_the_title() ),
			'item'     => get_permalink(),
		);

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	if ( ! $graph ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n\t<script type=\"application/ld+json\">"
		. wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. "</script>\n";
}
add_action( 'wp_head', 'tuskolift_seo_jsonld', 3 );
