<?php
/**
 * TUSKOLIFT — 后台可编辑字段
 *
 * 本文件把原先写死在模板里的文案/规格搬到 WordPress 后台，
 * 全部使用 WordPress 原生 API（meta box + term meta），不依赖任何第三方插件。
 *
 * 包含三部分：
 *   1. 页面文案        —— 头图(hero)与底部 CTA 的文案，编辑「页面」时可见
 *   2. 商品规格与特点  —— 编辑「商品」时可见
 *   3. 分类法术语图片  —— 产品线 / 应用场景 的配图，用于首页与筛选
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 0. 辅助函数
 * ========================================================================= */

/**
 * 解析「参数名 + 参数值」逐行的文本。
 *
 * 为了不让非技术编辑者踩坑，分隔符同时接受半角竖线、全角竖线、
 * 半角冒号和中文全角冒号：
 *
 *   型号|EPT-2000
 *   额定载重：2000 kg
 *   平台尺寸: 1150 × 550 mm
 *
 * 只写参数名（没有分隔符）时，值留空，前台只显示名称那一列。
 *
 * @param string $raw 后台文本框原始内容。
 * @return array
 */
function tuskolift_parse_pairs( $raw ) {
	$rows = array();
	$raw  = (string) $raw;

	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}

		// 先按竖线切（全角/半角），没有再按冒号切（全角/半角）。
		$parts = preg_split( '/[|｜]/u', $line, 2 );

		if ( count( $parts ) < 2 ) {
			$parts = preg_split( '/[:：]/u', $line, 2 );
		}

		if ( count( $parts ) >= 2 ) {
			$label = trim( $parts[0] );
			$value = trim( $parts[1] );
		} else {
			$label = trim( $parts[0] );
			$value = '';
		}

		if ( '' === $label ) {
			continue;
		}

		$rows[] = array(
			'label' => $label,
			'value' => $value,
		);
	}

	return $rows;
}

/**
 * 解析逐行的文本，返回去空后的字符串数组。
 *
 * @param string $raw 后台文本框原始内容。
 * @return string[]
 */
function tuskolift_parse_lines( $raw ) {
	$out = array();

	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$out[] = $line;
		}
	}

	return $out;
}

/**
 * 读取商品规格。
 *
 * @param int $post_id 商品 ID。
 * @return array
 */
function tuskolift_get_specs( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return tuskolift_parse_pairs( get_post_meta( $post_id, '_tk_specs', true ) );
}

/**
 * 读取商品特点。
 *
 * @param int $post_id 商品 ID。
 * @return string[]
 */
function tuskolift_get_features( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return tuskolift_parse_lines( get_post_meta( $post_id, '_tk_features', true ) );
}

/* =========================================================================
 * 1. 页面文案：头图 + 底部 CTA
 * ========================================================================= */

/**
 * 页面文案字段定义。
 *
 * @return array
 */
function tuskolift_page_copy_fields() {
	return array(
		'_tk_hero_eyebrow'  => array(
			'label' => '头图 · 小标签 eyebrow',
			'type'  => 'text',
			'ph'    => '例如 TUSKOLIFT / Journal',
		),
		'_tk_hero_title'    => array(
			'label' => '头图 · 主标题',
			'type'  => 'text',
			'ph'    => '例如 Notes from the factory floor.',
		),
		'_tk_hero_intro'    => array(
			'label' => '头图 · 导语',
			'type'  => 'textarea',
			'ph'    => '一句话说明这个页面的内容',
		),
		'_tk_cta_title'     => array(
			'label' => '底部 CTA · 主标题',
			'type'  => 'text',
			'ph'    => '例如 Tell us your application…',
		),
		'_tk_cta_text'      => array(
			'label' => '底部 CTA · 说明文字',
			'type'  => 'textarea',
			'ph'    => '',
		),
		'_tk_cta_btn_text'  => array(
			'label' => '底部 CTA · 按钮文字',
			'type'  => 'text',
			'ph'    => '例如 SEND AN INQUIRY →',
		),
		'_tk_cta_btn_url'   => array(
			'label' => '底部 CTA · 按钮链接',
			'type'  => 'text',
			'ph'    => '留空则指向 /contact/#inquiry',
		),
		'_tk_list_eyebrow'  => array(
			'label' => '列表区 · 小标签',
			'type'  => 'text',
			'ph'    => '例如 Latest news',
		),
		'_tk_list_title'    => array(
			'label' => '列表区 · 标题',
			'type'  => 'text',
			'ph'    => '显示在文章/商品列表上方',
		),
		'_tk_list_intro'    => array(
			'label' => '列表区 · 说明',
			'type'  => 'textarea',
			'ph'    => '',
		),
	);
}

/**
 * 读取页面文案（带全局默认值）。
 *
 * @param string $key     字段键名。
 * @param int    $post_id 页面 ID。
 * @return string
 */
function tuskolift_get_page_copy( $key, $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$defaults = array(
		'_tk_cta_btn_url' => home_url( '/contact/#inquiry' ),
	);

	$value = get_post_meta( $post_id, $key, true );

	if ( '' === $value || null === $value ) {
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}

	return $value;
}

/**
 * 注册页面文案 meta box。
 */
function tuskolift_add_page_copy_box() {
	add_meta_box(
		'tuskolift-page-copy',
		'页面文案（头图 / 底部 CTA）',
		'tuskolift_render_page_copy_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tuskolift_add_page_copy_box' );

/**
 * 渲染页面文案 meta box。
 *
 * @param WP_Post $post 当前页面。
 */
function tuskolift_render_page_copy_box( $post ) {
	wp_nonce_field( 'tuskolift_page_copy', 'tuskolift_page_copy_nonce' );

	echo '<p style="margin:0 0 14px;color:#646970;">这里填写的内容会替换模板里原本写死的头图与底部 CTA 文案。留空则前台不显示该部分。</p>';

	echo '<div style="display:grid;gap:14px;">';

	foreach ( tuskolift_page_copy_fields() as $key => $cfg ) {
		$value = get_post_meta( $post->ID, $key, true );

		echo '<div>';
		printf(
			'<label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px;">%2$s</label>',
			esc_attr( $key ),
			esc_html( $cfg['label'] )
		);

		if ( 'textarea' === $cfg['type'] ) {
			printf(
				'<textarea id="%1$s" name="%1$s" rows="3" class="widefat" placeholder="%3$s">%2$s</textarea>',
				esc_attr( $key ),
				esc_textarea( $value ),
				esc_attr( $cfg['ph'] )
			);
		} else {
			printf(
				'<input type="text" id="%1$s" name="%1$s" class="widefat" value="%2$s" placeholder="%3$s">',
				esc_attr( $key ),
				esc_attr( $value ),
				esc_attr( $cfg['ph'] )
			);
		}

		echo '</div>';
	}

	echo '</div>';
}

/**
 * 保存页面文案。
 *
 * @param int $post_id 页面 ID。
 */
function tuskolift_save_page_copy( $post_id ) {
	if ( ! isset( $_POST['tuskolift_page_copy_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['tuskolift_page_copy_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'tuskolift_page_copy' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array_keys( tuskolift_page_copy_fields() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST[ $key ] );

		if ( '_tk_cta_btn_url' === $key ) {
			$value = esc_url_raw( trim( $raw ) );
		} else {
			$value = sanitize_textarea_field( $raw );
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post_page', 'tuskolift_save_page_copy' );

/* =========================================================================
 * 2. 商品：规格参数 + 产品特点
 * ========================================================================= */

/**
 * 注册商品规格 meta box。
 */
function tuskolift_add_product_box() {
	add_meta_box(
		'tuskolift-product-detail',
		'商品详情：规格参数 / 产品特点',
		'tuskolift_render_product_box',
		'product',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tuskolift_add_product_box' );

/**
 * 渲染商品规格 meta box。
 *
 * @param WP_Post $post 当前商品。
 */
function tuskolift_render_product_box( $post ) {
	wp_nonce_field( 'tuskolift_product_detail', 'tuskolift_product_nonce' );

	$specs    = (string) get_post_meta( $post->ID, '_tk_specs', true );
	$features = (string) get_post_meta( $post->ID, '_tk_features', true );
	$featured = (bool) get_post_meta( $post->ID, '_tk_featured', true );

	$lines        = wp_get_post_terms( $post->ID, 'product_line' );
	$has_line     = ( $lines && ! is_wp_error( $lines ) );
	$has_thumb    = has_post_thumbnail( $post->ID );
	$has_excerpt  = '' !== trim( (string) $post->post_excerpt );
	?>
	<style>
		.tk-grid{display:grid;gap:20px;}
		.tk-grid label{display:block;font-weight:600;margin-bottom:4px;}
		.tk-hint{color:#646970;margin:4px 0 0;font-size:12px;}
		.tk-grid code{background:#f0f0f1;padding:1px 5px;border-radius:3px;}
		.tk-checks{display:grid;gap:8px;padding:14px 16px;background:#f6f7f7;border-left:4px solid #72aee6;}
		.tk-checks p{margin:0;font-size:13px;}
		.tk-ok{color:#007017;}
		.tk-warn{color:#b32d2e;font-weight:600;}
		.tk-featured{display:flex;align-items:center;gap:8px;font-weight:600;margin:0;}
	</style>

	<?php if ( ! $has_line || ! $has_thumb || ! $has_excerpt ) : ?>
		<div class="tk-checks" style="margin-bottom:20px;">
			<p><strong>发布前检查：</strong></p>
			<p class="<?php echo $has_line ? 'tk-ok' : 'tk-warn'; ?>">
				<?php echo $has_line ? '✓' : '✗'; ?> 产品线 —— <?php echo $has_line ? '已选择' : '未选择！未选产品线的商品不会出现在任何产品线筛选里，卡片上也不显示产品线名称'; ?>
			</p>
			<p class="<?php echo $has_thumb ? 'tk-ok' : 'tk-warn'; ?>">
				<?php echo $has_thumb ? '✓' : '✗'; ?> 特色图片 —— <?php echo $has_thumb ? '已设置' : '未设置！列表和详情页会显示灰色占位块'; ?>
			</p>
			<p class="<?php echo $has_excerpt ? 'tk-ok' : 'tk-warn'; ?>">
				<?php echo $has_excerpt ? '✓' : '✗'; ?> 摘要 —— <?php echo $has_excerpt ? '已填写' : '未填写！保存时会自动从正文截取一段，也可以在右侧「摘要」里自己写'; ?>
			</p>
		</div>
	<?php else : ?>
		<div class="tk-checks" style="margin-bottom:20px;">
			<p class="tk-ok">✓ 产品线、特色图片、摘要都已就绪，可以直接发布。</p>
		</div>
	<?php endif; ?>

	<div class="tk-grid">
		<div>
			<p class="tk-hint" style="font-size:13px;background:#fcf9e8;border-left:4px solid #dba617;padding:10px 14px;margin:0;">
				<strong>注意：本站面向海外客户，商品标题、摘要、正文、规格参数、产品特点<u>全部用英文填写</u>。</strong>
			</p>
		</div>
		<div>
			<label for="_tk_specs">规格参数（Specifications，用英文）</label>
			<textarea id="_tk_specs" name="_tk_specs" rows="8" class="widefat" placeholder="Model|EPT-2000&#10;Rated capacity: 2000 kg&#10;Fork size|1150 × 550 mm&#10;Service weight: 185 kg"><?php echo esc_textarea( $specs ); ?></textarea>
			<p class="tk-hint">每行一条。分隔符用竖线 <code>|</code> 或冒号 <code>:</code> 都可以，例如 <code>Rated capacity: 2000 kg</code>。只写参数名也行，前台就只显示名称那一列。</p>
		</div>
		<div>
			<label for="_tk_features">产品特点（Key Features，用英文）</label>
			<textarea id="_tk_features" name="_tk_features" rows="6" class="widefat" placeholder="Maintenance-free battery with fast charging&#10;CE certified&#10;Cold-store and stainless steel versions available"><?php echo esc_textarea( $features ); ?></textarea>
			<p class="tk-hint">每行一条，前台渲染成带「—」的项目符号列表。</p>
		</div>
		<div>
			<label class="tk-featured">
				<input type="checkbox" name="_tk_featured" value="1" <?php checked( $featured ); ?>>
				在首页「Featured Lifting Solutions」里优先展示
			</label>
			<p class="tk-hint">首页最多展示 4 个。勾选的会排在前面；勾选不足 4 个时用最新发布的商品补齐。</p>
		</div>
		<div>
			<p class="tk-hint" style="font-size:13px;">
				各字段对应前台的位置：<strong>特色图片</strong> → 列表卡片与详情页主图；<strong>摘要</strong> → 列表卡片文字；<strong>正文</strong> → 详情页介绍（可插入相册、表格等区块）；<strong>产品线 / 应用场景</strong> → 右侧分类框，决定筛选归类。
			</p>
		</div>
	</div>
	<?php
}

/**
 * 保存商品规格与特点。
 *
 * @param int $post_id 商品 ID。
 */
function tuskolift_save_product_detail( $post_id ) {
	if ( ! isset( $_POST['tuskolift_product_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['tuskolift_product_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'tuskolift_product_detail' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( '_tk_specs', '_tk_features' ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$value = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );

		if ( '' === trim( $value ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	// 首页精选开关。
	if ( isset( $_POST['_tk_featured'] ) && '1' === (string) wp_unslash( $_POST['_tk_featured'] ) ) {
		update_post_meta( $post_id, '_tk_featured', 1 );
	} else {
		delete_post_meta( $post_id, '_tk_featured' );
	}
}
add_action( 'save_post_product', 'tuskolift_save_product_detail' );

/* =========================================================================
 * 3. 分类法术语图片（产品线 / 应用场景）
 * ========================================================================= */

/**
 * 需要图片字段的分类法。
 *
 * @return string[]
 */
function tuskolift_image_taxonomies() {
	return array( 'product_line', 'application' );
}

/**
 * 读取术语图片地址。
 *
 * @param int    $term_id 术语 ID。
 * @param string $size    图片尺寸。
 * @return string
 */
function tuskolift_term_image_url( $term_id, $size = 'medium_large' ) {
	$attachment_id = (int) get_term_meta( $term_id, '_tk_term_image_id', true );

	if ( ! $attachment_id ) {
		return '';
	}

	$src = wp_get_attachment_image_src( $attachment_id, $size );

	return $src ? $src[0] : '';
}

/**
 * 术语的排序权重（后台填的数字，越小越靠前）。
 *
 * @param WP_Term $term 术语。
 * @return int
 */
function tuskolift_term_order_value( $term ) {
	$value = get_term_meta( $term->term_id, '_tk_term_order', true );

	if ( '' === $value || null === $value ) {
		return 9999;
	}

	return (int) $value;
}

/**
 * 术语是否在前台隐藏。
 *
 * @param WP_Term $term 术语。
 * @return bool
 */
function tuskolift_is_term_hidden( $term ) {
	return (bool) get_term_meta( $term->term_id, '_tk_term_hidden', true );
}

/**
 * 按后台「排序」字段取术语，默认过滤掉被标记为前台隐藏的。
 *
 * 未填排序的术语排在最后，同权重按名称排序，保证前台顺序稳定可预测。
 *
 * @param string $taxonomy       分类法。
 * @param bool   $include_hidden 是否包含隐藏的术语。
 * @return WP_Term[]
 */
function tuskolift_get_terms_ordered( $taxonomy, $include_hidden = false ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	$out = array();

	foreach ( $terms as $term ) {
		if ( ! $include_hidden && tuskolift_is_term_hidden( $term ) ) {
			continue;
		}

		$out[] = $term;
	}

	usort(
		$out,
		function ( $a, $b ) {
			$order_a = tuskolift_term_order_value( $a );
			$order_b = tuskolift_term_order_value( $b );

			if ( $order_a === $order_b ) {
				return strcasecmp( $a->name, $b->name );
			}

			return $order_a <=> $order_b;
		}
	);

	return $out;
}

/**
 * 把术语列表打包成给 Vue 用的配置。
 *
 * 商品上千时，这里会跑两次「带商品计数的分类查询」（每个分类都要 JOIN 统计），
 * 而只有在后台增删商品/分类时结果才会变，所以缓存 10 分钟。
 * 缓存由 inc/tk-catalog-rest.php 里的钩子在数据变动时主动清掉。
 *
 * @param bool $force 为 true 时跳过缓存。
 * @return array
 */
function tuskolift_get_catalog_term_config( $force = false ) {
	$cache_key = defined( 'TUSKOLIFT_TERM_CACHE_KEY' ) ? TUSKOLIFT_TERM_CACHE_KEY : 'tuskolift_catalog_terms';

	if ( ! $force ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$map = array(
		'lines'        => 'product_line',
		'applications' => 'application',
	);

	$out = array();

	foreach ( $map as $key => $taxonomy ) {
		$out[ $key ] = array();

		foreach ( tuskolift_get_terms_ordered( $taxonomy ) as $term ) {
			$out[ $key ][] = array(
				'name'  => $term->name,
				'slug'  => $term->slug,
				'count' => (int) $term->count,
			);
		}
	}

	if ( defined( 'TUSKOLIFT_TERM_CACHE_TTL' ) ) {
		set_transient( $cache_key, $out, TUSKOLIFT_TERM_CACHE_TTL );
	}

	return $out;
}

/**
 * 在「新建术语」表单里加图片字段。
 *
 * @param string $taxonomy 分类法。
 */
function tuskolift_term_image_add_field( $taxonomy ) {
	?>
	<div class="form-field tk-term-image">
		<label>分类配图</label>
		<div class="tk-term-image-preview"></div>
		<input type="hidden" class="tk-term-image-id" name="tk_term_image_id" value="">
		<button type="button" class="button tk-term-image-select">选择图片</button>
		<button type="button" class="button tk-term-image-remove" style="display:none;">移除</button>
		<p>用于首页「Our Product Range」以及产品线页面的配图。</p>
	</div>

	<div class="form-field">
		<label for="tk_term_order">排序</label>
		<input type="number" id="tk_term_order" name="tk_term_order" value="" step="1" style="width:100px;">
		<p>数字越小越靠前。留空则排在最后（按名称排序）。前台筛选栏与首页卡片都按这个顺序展示。</p>
	</div>

	<div class="form-field">
		<label style="display:flex;align-items:center;gap:8px;font-weight:600;">
			<input type="checkbox" name="tk_term_hidden" value="1">
			在前台隐藏这个分类
		</label>
		<p>勾选后，前台筛选栏和首页都不再显示它，但后台数据、已关联的商品都会保留。</p>
	</div>
	<?php
}

/**
 * 在「编辑术语」表单里加图片字段。
 *
 * @param WP_Term $term     术语对象。
 * @param string  $taxonomy 分类法。
 */
function tuskolift_term_image_edit_field( $term, $taxonomy ) {
	$attachment_id = (int) get_term_meta( $term->term_id, '_tk_term_image_id', true );
	$preview       = $attachment_id ? wp_get_attachment_image( $attachment_id, 'medium', false, array( 'style' => 'max-width:150px;height:auto;display:block;margin-bottom:6px;' ) ) : '';
	$order         = get_term_meta( $term->term_id, '_tk_term_order', true );
	$hidden        = tuskolift_is_term_hidden( $term );
	?>
	<tr class="form-field tk-term-image">
		<th scope="row"><label>分类配图</label></th>
		<td>
			<div class="tk-term-image-preview"><?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image 已转义。 ?></div>
			<input type="hidden" class="tk-term-image-id" name="tk_term_image_id" value="<?php echo esc_attr( $attachment_id ); ?>">
			<button type="button" class="button tk-term-image-select">选择图片</button>
			<button type="button" class="button tk-term-image-remove" style="<?php echo $attachment_id ? '' : 'display:none;'; ?>">移除</button>
			<p class="description">用于首页「Our Product Range」以及产品线页面的配图。</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row"><label for="tk_term_order">排序</label></th>
		<td>
			<input type="number" id="tk_term_order" name="tk_term_order" value="<?php echo '' === $order ? '' : esc_attr( $order ); ?>" step="1" style="width:100px;">
			<p class="description">数字越小越靠前。留空则排在最后（按名称排序）。前台筛选栏与首页卡片都按这个顺序展示。</p>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row">前台可见性</th>
		<td>
			<label style="display:flex;align-items:center;gap:8px;">
				<input type="checkbox" name="tk_term_hidden" value="1" <?php checked( $hidden ); ?>>
				在前台隐藏这个分类
			</label>
			<p class="description">勾选后，前台筛选栏和首页都不再显示它，但后台数据、已关联的商品都会保留。适合停用但不想删除的分类。</p>
		</td>
	</tr>
	<?php
}

/**
 * 保存术语的自定义字段（配图 / 排序 / 前台隐藏）。
 *
 * @param int $term_id 术语 ID。
 */
function tuskolift_save_term_image( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	// 配图。
	if ( isset( $_POST['tk_term_image_id'] ) ) {
		$attachment_id = absint( wp_unslash( $_POST['tk_term_image_id'] ) );

		if ( $attachment_id ) {
			update_term_meta( $term_id, '_tk_term_image_id', $attachment_id );
		} else {
			delete_term_meta( $term_id, '_tk_term_image_id' );
		}
	}

	// 排序。
	if ( isset( $_POST['tk_term_order'] ) ) {
		$order = trim( (string) wp_unslash( $_POST['tk_term_order'] ) );

		if ( '' === $order ) {
			delete_term_meta( $term_id, '_tk_term_order' );
		} else {
			update_term_meta( $term_id, '_tk_term_order', (int) $order );
		}
	}

	// 前台隐藏。
	if ( isset( $_POST['tk_term_hidden'] ) && '1' === (string) wp_unslash( $_POST['tk_term_hidden'] ) ) {
		update_term_meta( $term_id, '_tk_term_hidden', 1 );
	} else {
		delete_term_meta( $term_id, '_tk_term_hidden' );
	}
}

foreach ( tuskolift_image_taxonomies() as $tk_tax ) {
	add_action( "{$tk_tax}_add_form_fields", 'tuskolift_term_image_add_field' );
	add_action( "{$tk_tax}_edit_form_fields", 'tuskolift_term_image_edit_field', 10, 2 );
	add_action( "created_{$tk_tax}", 'tuskolift_save_term_image' );
	add_action( "edited_{$tk_tax}", 'tuskolift_save_term_image' );
}
unset( $tk_tax );

/**
 * 在术语编辑页面加载媒体库与选取脚本。
 *
 * @param string $hook 当前管理页面。
 */
function tuskolift_term_image_admin_assets( $hook ) {
	if ( 'term.php' !== $hook && 'edit-tags.php' !== $hook ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->taxonomy, tuskolift_image_taxonomies(), true ) ) {
		return;
	}

	wp_enqueue_media();

	$script = <<<'JS'
jQuery( function ( $ ) {
	$( document ).on( 'click', '.tk-term-image-select', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.tk-term-image' );
		var frame = wp.media( {
			title: '选择分类配图',
			button: { text: '使用这张图片' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

			$wrap.find( '.tk-term-image-id' ).val( attachment.id );
			$wrap.find( '.tk-term-image-preview' ).html(
				$( '<img>' ).attr( 'src', url ).css( { maxWidth: '150px', height: 'auto', display: 'block', marginBottom: '6px' } )
			);
			$wrap.find( '.tk-term-image-remove' ).show();
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.tk-term-image-remove', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.tk-term-image' );
		$wrap.find( '.tk-term-image-id' ).val( '' );
		$wrap.find( '.tk-term-image-preview' ).empty();
		$( this ).hide();
	} );
} );
JS;

	wp_add_inline_script( 'media-editor', $script );
}
add_action( 'admin_enqueue_scripts', 'tuskolift_term_image_admin_assets' );
