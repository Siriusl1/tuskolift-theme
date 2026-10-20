<?php
/**
 * TUSKOLIFT — 后台编辑体验优化
 *
 * 面向非技术编辑者，包含三部分：
 *   1. 后台「使用指南」页面（商品 / Blog 的完整操作说明）
 *   2. 新文章默认归类到「公司新闻」，避免发完看不到
 *   3. 没填摘要时自动从正文截取，保证列表卡片不会空着
 *
 * @package TUSKOLIFT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 1. 新文章默认分类
 * ========================================================================= */

/**
 * 把「默认分类」指到公司新闻，这样新发的文章不会掉进「未分类」而在前台消失。
 *
 * @param mixed $value 默认分类 ID。
 * @return int
 */
function tuskolift_default_category( $value ) {
	if ( ! taxonomy_exists( 'category' ) ) {
		return $value;
	}

	$term = get_term_by( 'slug', tuskolift_news_category_slug(), 'category' );

	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}

	return $value;
}
add_filter( 'option_default_category', 'tuskolift_default_category' );

/* =========================================================================
 * 2. 自动摘要
 * ========================================================================= */

/**
 * 按语言智能截断文本：中文按字数，英文按词数。
 *
 * @param string $text  纯文本。
 * @param int    $chars 中文字数上限。
 * @param int    $words 英文词数上限。
 * @return string
 */
function tuskolift_trim_text( $text, $chars = 110, $words = 32 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );

	if ( '' === $text ) {
		return '';
	}

	// 含中日韩字符就按字符数截断（中文没有空格，按词截断会整段保留）。
	if ( preg_match( '/[\x{3040}-\x{30ff}\x{3400}-\x{4dbf}\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u', $text ) ) {
		if ( mb_strlen( $text, 'UTF-8' ) > $chars ) {
			return mb_substr( $text, 0, $chars, 'UTF-8' ) . '…';
		}

		return $text;
	}

	return wp_trim_words( $text, $words, '…' );
}

/**
 * 保存文章 / 商品时，如果摘要为空就自动从正文生成。
 *
 * 只在摘要真的为空时介入，编辑者手写的摘要永远不会被覆盖。
 *
 * @param array $data    将要写入数据库的数据。
 * @param array $postarr 原始提交数据。
 * @return array
 */
function tuskolift_auto_excerpt( $data, $postarr ) {
	if ( empty( $data['post_type'] ) || ! in_array( $data['post_type'], array( 'post', 'product' ), true ) ) {
		return $data;
	}

	if ( in_array( $data['post_status'], array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
		return $data;
	}

	if ( '' !== trim( (string) $data['post_excerpt'] ) ) {
		return $data;
	}

	$text = wp_strip_all_tags( strip_shortcodes( (string) $data['post_content'] ) );
	$text = tuskolift_trim_text( $text );

	if ( '' !== $text ) {
		$data['post_excerpt'] = $text;
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'tuskolift_auto_excerpt', 20, 2 );

/* =========================================================================
 * 3. 后台「使用指南」页面
 * ========================================================================= */

/**
 * 注册使用指南菜单。
 */
function tuskolift_register_guide_page() {
	add_menu_page(
		'TUSKOLIFT 使用指南',
		'使用指南',
		'edit_posts',
		'tuskolift-guide',
		'tuskolift_render_guide_page',
		'dashicons-book-alt',
		3
	);
}
add_action( 'admin_menu', 'tuskolift_register_guide_page' );

/**
 * 渲染使用指南页面。
 */
function tuskolift_render_guide_page() {
	$products_url  = admin_url( 'edit.php?post_type=product' );
	$new_product   = admin_url( 'post-new.php?post_type=product' );
	$lines_url     = admin_url( 'edit-tags.php?taxonomy=product_line&post_type=product' );
	$apps_url      = admin_url( 'edit-tags.php?taxonomy=application&post_type=product' );
	$posts_url     = admin_url( 'edit.php' );
	$new_post      = admin_url( 'post-new.php' );
	$cats_url      = admin_url( 'edit-tags.php?taxonomy=category' );
	$site_url      = home_url( '/' );
	$products_page = home_url( '/products/' );
	$blog_page     = home_url( '/blog/' );
	?>
	<div class="wrap tk-guide">
		<style>
			.tk-guide{max-width:960px;}
			.tk-guide h1{margin-bottom:6px;}
			.tk-guide .tk-lead{font-size:15px;color:#50575e;margin:0 0 26px;}
			.tk-guide .tk-card{background:#fff;border:1px solid #dcdcde;border-left:4px solid #2271b1;padding:20px 24px;margin:0 0 20px;}
			.tk-guide .tk-card h2{margin-top:0;font-size:18px;}
			.tk-guide .tk-card h3{font-size:14px;margin:22px 0 8px;}
			.tk-guide ol,.tk-guide ul{margin:0 0 12px 20px;}
			.tk-guide li{margin-bottom:7px;line-height:1.7;}
			.tk-guide table{border-collapse:collapse;width:100%;margin:10px 0 18px;}
			.tk-guide th,.tk-guide td{border:1px solid #dcdcde;padding:9px 12px;text-align:left;font-size:13px;vertical-align:top;}
			.tk-guide th{background:#f6f7f7;font-weight:600;}
			.tk-guide code{background:#f0f0f1;padding:2px 6px;border-radius:3px;font-size:13px;}
			.tk-guide .tk-tip{background:#f0f6fc;border-left:4px solid #72aee6;padding:12px 16px;margin:14px 0;font-size:13px;line-height:1.7;}
			.tk-guide .tk-warn{background:#fcf0f1;border-left:4px solid #d63638;padding:12px 16px;margin:14px 0;font-size:13px;line-height:1.7;}
			.tk-guide .tk-ok{background:#f0f6f0;border-left:4px solid #00a32a;padding:12px 16px;margin:14px 0;font-size:13px;line-height:1.7;}
			.tk-guide .tk-faq{margin:0;}
			.tk-guide .tk-faq dt{font-weight:600;margin-top:16px;}
			.tk-guide .tk-faq dd{margin:6px 0 0 0;color:#50575e;line-height:1.7;}
		</style>

		<h1>TUSKOLIFT 网站内容使用指南</h1>
		<p class="tk-lead">
			这份指南说明怎么在后台维护<strong>商品</strong>和 <strong>Blog 文章</strong>。
			全程不需要写代码，改完保存后刷新前台页面即可看到效果。
		</p>

		<div class="tk-card" style="border-left-color:#2271b1;">
			<h2>目录</h2>
			<ol style="margin-bottom:0;">
				<li><a href="#tk-sec-1">商品（Products）</a></li>
				<li><a href="#tk-sec-2">图片上传规范</a>　<span style="color:#d63638;">← 最常出问题，建议先看</span></li>
				<li><a href="#tk-sec-3">产品上传注意事项</a></li>
				<li><a href="#tk-sec-4">Blog 文章</a></li>
				<li><a href="#tk-sec-5">邮箱与询盘通知</a></li>
				<li><a href="#tk-sec-6">日常检查清单</a></li>
				<li><a href="#tk-sec-7">不要做的事</a></li>
				<li><a href="#tk-sec-8">出问题找谁</a></li>
				<li><a href="#tk-sec-9">常见问题</a></li>
			</ol>
		</div>

		<div class="tk-card" id="tk-sec-1">
			<h2>一、商品（Products）</h2>

			<h3>发布一个新商品</h3>
			<ol>
				<li>左侧菜单 <strong>商品 → 添加新商品</strong>。</li>
				<li><strong>标题</strong>填商品名称，例如 <code>Electric Pallet Truck</code>。</li>
				<li>在右侧「商品分类」里勾选 <strong>产品线</strong>（必选）和 <strong>应用场景</strong>（可选）。
					<div class="tk-warn">不勾产品线的商品，不会出现在任何产品线筛选里，卡片上也不会显示产品线名称。</div>
				</li>
				<li>在右侧「特色图片」里选一张主图。
					<div class="tk-tip">建议用<strong>正方形白底图</strong>（PNG 或 JPG）。不要用 SVG 或体积过大的原图，否则列表加载会变慢、也可能显示不出来。</div>
				</li>
				<li>「摘要」写一两句卖点，会显示在列表卡片上。
					<div class="tk-tip">留空也没关系——保存时会自动从下面的正文里截取一段。</div>
				</li>
				<li>「商品详情：规格参数 / 产品特点」面板：
					<ul>
						<li><strong>规格参数</strong>：一行一条。分隔符用竖线 <code>|</code> 或中文冒号 <code>：</code> 都行，例如
							<br><code>额定载重：2000 kg</code>
							<br><code>平台尺寸|1150 × 550 mm</code>
						</li>
						<li><strong>产品特点</strong>：一行一条，前台会显示成带「—」的列表。</li>
					</ul>
				</li>
				<li>上面的大编辑器写详细介绍，可以用「相册」「表格」等区块排版，内容会出现在详情页。</li>
				<li>想让它出现在首页「Featured Lifting Solutions」里，勾选面板上的
					<strong>「在首页 Featured Lifting Solutions 里优先展示」</strong>（首页最多 4 个）。</li>
				<li>点右上角<strong>发布</strong>。前台 <code>/products/</code> 和商品详情页立刻能看到。</li>
			</ol>

			<h3>商品字段对应前台的哪里</h3>
			<table>
				<tr><th>后台字段</th><th>前台显示位置</th></tr>
				<tr><td>标题</td><td>列表卡片标题、详情页大标题</td></tr>
				<tr><td>特色图片</td><td>列表卡片缩略图、详情页主图、首页精选</td></tr>
				<tr><td>摘要</td><td>列表卡片文字、详情页副标题</td></tr>
				<tr><td>规格参数</td><td>详情页「Specifications」表格</td></tr>
				<tr><td>产品特点</td><td>详情页「Key Features」列表</td></tr>
				<tr><td>正文编辑器</td><td>详情页正文区</td></tr>
				<tr><td>产品线</td><td>列表筛选栏左侧、详情页面包屑、产品线独立页面</td></tr>
				<tr><td>应用场景</td><td>列表筛选栏右侧、卡片上的标签</td></tr>
			</table>

			<h3>管理产品线 / 应用场景</h3>
			<p>
				<a class="button" href="<?php echo esc_url( $lines_url ); ?>">管理产品线</a>
				<a class="button" href="<?php echo esc_url( $apps_url ); ?>">管理应用场景</a>
			</p>
			<p>每个分类（术语）都有这些可填项：</p>
			<table>
				<tr><th>字段</th><th>作用</th></tr>
				<tr><td>名称</td><td>前台显示的分类名</td></tr>
				<tr><td>描述</td><td>首页产品卡片上的说明文字；产品线独立页面的导语</td></tr>
				<tr><td>分类配图</td><td>首页「Our Product Range」的卡片配图</td></tr>
				<tr><td>排序</td><td>数字越小越靠前。前台筛选栏和首页卡片都按它排序；留空则排最后（按名称排）</td></tr>
				<tr><td>在前台隐藏</td><td>打勾后前台不再显示这个分类，但后台数据和已关联的商品都保留。适合临时停用的分类</td></tr>
			</table>
			<div class="tk-tip">
				新建一个产品线之后，它会<strong>立刻</strong>出现在 <code>/products/</code> 的筛选栏和首页卡片里，
				即使还没有商品。这时计数会显示 0，点进去是空的——属于正常现象，发布该产品线的商品即可。
			</div>
		</div>

		<div class="tk-card" id="tk-sec-2" style="border-left-color:#d63638;">
			<h2>二、图片上传规范</h2>

			<div class="tk-warn">
				<strong>这是最容易出问题、也最影响访问速度的一环。</strong>
				一张没压过的手机原图可能有 3~5 MB，一个商品列表页几十张图就是几百 MB，
				手机用户会等到放弃。请务必按下面的要求处理图片。
			</div>

			<h3>硬性要求</h3>
			<table>
				<tr><th>项目</th><th>要求</th><th>说明</th></tr>
				<tr>
					<td>单张体积</td>
					<td><strong>≤ 500 KB</strong></td>
					<td>越小越好。超过 500 KB 系统会在后台给你一条提醒（不会拦住你上传）</td>
				</tr>
				<tr>
					<td>形状</td>
					<td><strong>正方形</strong></td>
					<td>列表卡片是正方形框，非正方形的图会被裁掉边缘，主体可能被切</td>
				</tr>
				<tr>
					<td>背景</td>
					<td><strong>白底</strong></td>
					<td>卡片底色是浅灰，白底图看起来最干净统一</td>
				</tr>
				<tr>
					<td>格式</td>
					<td><strong>PNG 或 JPG</strong></td>
					<td>产品照片用 JPG（更小），带透明/纯色块用 PNG。不要用 SVG</td>
				</tr>
				<tr>
					<td>最长边</td>
					<td><strong>≤ 1600 px</strong></td>
					<td>超过 1600px 系统会<strong>自动缩小</strong>，但你自己先缩好质量更可控</td>
				</tr>
			</table>

			<h3>上传前一定要压缩</h3>
			<ol>
				<li>打开 <strong>Squoosh</strong>（<code>squoosh.app</code>，免费、不用注册）或 <strong>TinyPNG</strong>（<code>tinypng.com</code>）。</li>
				<li>把图片拖进去，看它压完的体积。</li>
				<li>如果还是超过 500 KB，在 Squoosh 里把「尺寸」的长边改成 1200 或 1000，质量拖到 75~82 之间。</li>
				<li>下载压缩后的图，用这个文件上传（不要用原始文件）。</li>
			</ol>

			<div class="tk-tip">
				<strong>怎么判断压到位了：</strong>把图片放到 100% 显示，
				肉眼看不出明显模糊、锯齿，就可以了。
				商品图会显示得比较小，通常压得狠一点也没关系。
			</div>

			<div class="tk-tip">
				<strong>上传后系统会自动帮你做两件事</strong>（不用你管）：
				<ul style="margin-bottom:0;">
					<li>超过 1600px 的图，自动缩到最长边 1600px；</li>
					<li>自动生成小尺寸版本（560×560 的列表卡片图等），前台列表用的永远是小图，不是原图。</li>
				</ul>
			</div>

			<div class="tk-warn">
				<strong>绝对不要直接传的东西：</strong>
				<ul style="margin-bottom:0;">
					<li>相机 / 手机拍的原始照片（动辄好几 MB，还带 EXIF 信息）；</li>
					<li>设计稿导出的超大 PNG（比如 4000×4000）；</li>
					<li>从别人网站直接「另存为」的图（可能有大体积、也可能有版权问题）。</li>
				</ul>
			</div>
		</div>

		<div class="tk-card" id="tk-sec-3" style="border-left-color:#dba617;">
			<h2>三、产品上传注意事项</h2>

			<h3>填写要求</h3>
			<ol>
				<li><strong>按字段要求填，必填项不要留空。</strong>
					特别容易漏的是右侧「商品分类 → 产品线」——不勾的话商品不会出现在任何筛选里。</li>
				<li><strong>信息要完整</strong>：标题、描述、分类、图片四样缺一不可。
					<ul>
						<li>标题：写清楚型号 / 品类，例如 <code>Electric Pallet Truck 2000kg</code>；</li>
						<li>描述：先写一句卖点当摘要（会显示在列表卡片上）；</li>
						<li>分类：产品线必选，应用场景按需勾选；</li>
						<li>图片：右侧「特色图片」必须选一张。</li>
					</ul>
				</li>
				<li><strong>不要在「产品描述」里插入原图。</strong>
					<div class="tk-warn" style="margin-bottom:6px;">
						图片请走右侧的<strong>「特色图片」</strong>专用字段。
						正文里手工插的图不会自动生成小尺寸版本，
						会按你插入的原始尺寸加载，直接拖慢详情页。
					</div>
					正文里需要补充说明图（比如尺寸标注图）时，
					插入后请点一下图片 → 在右侧「图片尺寸」里选 <strong>Medium</strong> 或 <strong>Large</strong>，
					<span style="color:#d63638;">不要选「完整尺寸」</span>。
				</li>
			</ol>

			<h3>上传后必须检查</h3>
			<ol>
				<li>点右上角<strong>发布</strong>，然后点「查看商品」到前台看。</li>
				<li>回到 <code>/products/</code> 列表页，找到刚发布的商品，检查：
					<ul>
						<li>图片<strong>显示正常</strong>吗？（不是空白、不是破图）</li>
						<li>图片<strong>有没有变形</strong>？（被拉扁 / 拉长）</li>
						<li>图片<strong>是不是太大</strong>？（页面加载明显卡顿）</li>
						<li>产品线和标签显示对吗？</li>
					</ul>
				</li>
				<li>有问题就回到商品编辑页，换图或压缩后重传。</li>
			</ol>

			<div class="tk-tip">
				<strong>图片显示不出来的排查顺序：</strong>
				① 确认右侧「特色图片」里真的选了图 →
				② 确认不是 SVG 格式 →
				③ 强制刷新 <code>Ctrl + F5</code> →
				④ 还不行就联系开发。
			</div>
		</div>

		<div class="tk-card" id="tk-sec-4" style="border-left-color:#00a32a;">
			<h2>四、Blog 文章</h2>

			<h3>发布一篇文章</h3>
			<ol>
				<li>左侧菜单 <strong>文章 → 写文章</strong>。</li>
				<li>填<strong>标题</strong>，正文用区块编辑器写。</li>
				<li>右侧「文章分类」勾选：
					<ul>
						<li><strong>Company News</strong> → 出现在「公司新闻」和 Blog 总览</li>
						<li><strong>Industry Insights</strong> → 出现在「行业洞察」和 Blog 总览</li>
					</ul>
					<div class="tk-tip">新文章默认已经勾好 <strong>Company News</strong>，一般不用改。想归到「行业洞察」就换成那一个。</div>
					<div class="tk-warn">两个分类都不勾的文章，不会出现在任何 Blog 列表里。</div>
				</li>
				<li>「摘要」写一句话当卡片简介（留空会自动从正文截取）。</li>
				<li>「特色图片」选一张配图（可选，会显示在文章详情页顶部）。</li>
				<li>点<strong>发布</strong>。</li>
			</ol>

			<h3>文章出现的位置</h3>
			<table>
				<tr><th>页面</th><th>显示内容</th></tr>
				<tr><td><code>/blog/</code> Blog 总览</td><td>两个分类的最新 6 篇混排</td></tr>
				<tr><td><code>/blog/blog-news/</code> 公司新闻</td><td>Company News 分类，最多 9 篇</td></tr>
				<tr><td><code>/blog/blog-insights/</code> 行业洞察</td><td>Industry Insights 分类，最多 9 篇</td></tr>
				<tr><td><code>/category/news/</code>、<code>/category/insights/</code></td><td>该分类的全部文章，带分页</td></tr>
			</table>
			<p>
				<a class="button" href="<?php echo esc_url( $posts_url ); ?>">管理文章</a>
				<a class="button" href="<?php echo esc_url( $new_post ); ?>">写新文章</a>
				<a class="button" href="<?php echo esc_url( $cats_url ); ?>">管理分类</a>
			</p>

			<h3>改 Blog 页面顶部的文案</h3>
			<ol>
				<li>左侧菜单 <strong>页面 → 全部页面</strong>，打开 <code>Blog</code> / <code>Blog News</code> / <code>Blog Insights</code>。</li>
				<li>找到「页面文案（头图 / 底部 CTA）」面板，里面可以改：
					<ul>
						<li>头图小标签、主标题、导语</li>
						<li>列表区小标签、标题、说明</li>
						<li>底部 CTA 的标题、说明、按钮文字和链接</li>
					</ul>
				</li>
				<li>更新即可。留空的部分前台不显示。</li>
			</ol>
		</div>

		<div class="tk-card" id="tk-sec-5" style="border-left-color:#dba617;">
			<h2>五、邮箱与询盘通知</h2>

			<div class="tk-ok">
				<strong>还没买域名和邮箱？现在什么都不用做。</strong>
				每条询盘都会自动存进左侧「<strong>询盘记录</strong>」，一封都不会丢。
				只是收不到邮件提醒，需要你隔几天进后台看一眼。
			</div>

			<h3>拿到邮箱后，只要做这两步</h3>
			<ol>
				<li>后台 → <strong>设置 → 常规</strong> → 把「管理邮箱地址」改成真实邮箱
					（例如 <code>sales@你的域名.com</code>）→ 保存。</li>
				<li>后台 → <strong>WPForms → 所有表单 → 点开表单 → 设置 → 通知</strong>：
					确认「Send To Email Address」仍然是 <code>{admin_email}</code>（不用改，它会自动用上一步那个邮箱），
					顺便可以把邮件标题改成你想要的样子。</li>
			</ol>
			<p>
				做完这两步，每次有人提交表单你就会收到邮件，而且
				<strong>直接点「回复」就是回给客户</strong>（Reply-To 已经配好了）。
			</p>

			<h3>如果收不到邮件，再装一个 SMTP 插件</h3>
			<p>WordPress 默认用服务器直接发信，很容易被 Gmail / Outlook 当垃圾邮件，甚至发不出去。正式上线建议装一个免费的 SMTP 插件：</p>
			<ol>
				<li>后台 → <strong>插件 → 安装插件</strong> → 搜 <code>WP Mail SMTP</code>（或 <code>Fluent SMTP</code>）→ 安装并启用。</li>
				<li>进设置向导时，<strong>不要选 Gmail / Outlook / 微软</strong>——那些会要求填「客户端 ID / 密钥」，
					需要去 Google 或微软的开发者后台申请，很麻烦。</li>
				<li>请选 <strong>「其他 SMTP」/ Other SMTP</strong>，只需要填 4 个值：SMTP 主机、端口、用户名、密码。
					这 4 个值在你的<strong>邮箱服务商后台</strong>（网页版邮箱的「设置 → POP3/SMTP/IMAP」那一页）都能查到。</li>
				<li>保存后点插件里的<strong>「发送测试邮件」</strong>，填你自己的邮箱，能收到就成功了。</li>
			</ol>

			<div class="tk-tip">
				常见服务商的 SMTP 服务器（请以服务商后台的实际说明为准）：
				<table>
					<tr><th>邮箱</th><th>SMTP 主机</th><th>端口</th></tr>
					<tr><td>腾讯企业邮箱</td><td><code>smtp.exmail.qq.com</code></td><td>465 (SSL)</td></tr>
					<tr><td>阿里云企业邮箱</td><td><code>smtp.qiye.aliyun.com</code></td><td>465 (SSL)</td></tr>
					<tr><td>网易企业邮箱</td><td><code>smtphz.qiye.163.com</code></td><td>465 (SSL)</td></tr>
					<tr><td>Zoho Mail（有免费版）</td><td><code>smtp.zoho.com</code></td><td>465 (SSL)</td></tr>
					<tr><td>Gmail</td><td><code>smtp.gmail.com</code></td><td>465 (SSL)</td></tr>
					<tr><td>Outlook / Microsoft 365</td><td><code>smtp.office365.com</code></td><td>587 (STARTTLS)</td></tr>
				</table>
				用户名一般填完整邮箱地址；密码多数服务商要求用「<strong>客户端专用密码 / 授权码</strong>」，不是你的登录密码。
				Gmail 还必须先开启两步验证才能生成应用专用密码。
			</div>

			<h3>怎么确认整条链路通了</h3>
			<ol>
				<li>打开前台 <code>/contact/</code>，自己填一遍表单提交。</li>
				<li>看你的邮箱有没有收到（顺便检查垃圾邮件箱）。</li>
				<li>回到后台 →「询盘记录」，应该也新增了一条。</li>
			</ol>

			<div class="tk-warn">
				<strong>为什么这里特别强调「询盘记录」：</strong>
				WPForms 免费版<strong>不会把投稿存进数据库</strong>，只发邮件。
				万一邮件失败，客户提交的内容就找不回来了。
				所以本站额外把所有提交抄了一份到「询盘记录」——邮件出问题也不会丢单。
			</div>
		</div>

		<div class="tk-card" id="tk-sec-6" style="border-left-color:#00a32a;">
			<h2>六、日常检查清单</h2>

			<p>建议每周花 5 分钟，把下面几项过一遍。绝大多数问题都能在这时候提前发现。</p>

			<h3>每周检查</h3>
			<table>
				<tr><th>检查项</th><th>怎么看</th><th>正常的表现</th></tr>
				<tr>
					<td>首页</td>
					<td><a href="<?php echo esc_url( $site_url ); ?>" target="_blank">打开首页</a></td>
					<td>图片、视频、产品卡片都正常显示，没有空白块</td>
				</tr>
				<tr>
					<td>产品列表页</td>
					<td><a href="<?php echo esc_url( $products_page ); ?>" target="_blank">打开 <code>/products/</code></a></td>
					<td>能看到商品卡片，左侧筛选栏正常，图片不变形</td>
				</tr>
				<tr>
					<td>产品详情页</td>
					<td>从列表点进任意一个商品</td>
					<td>主图、规格表、产品特点都显示出来</td>
				</tr>
				<tr>
					<td>联系页</td>
					<td><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" target="_blank">打开 <code>/contact/</code></a></td>
					<td>表单字段完整显示，能看到提交按钮</td>
				</tr>
				<tr>
					<td>Blog 列表</td>
					<td><a href="<?php echo esc_url( $blog_page ); ?>" target="_blank">打开 <code>/blog/</code></a></td>
					<td>文章卡片正常，点击能进详情页</td>
				</tr>
			</table>

			<h3>联系表单要定期真的提交一次</h3>
			<div class="tk-warn">
				<strong>为什么必须实测：</strong>
				联系表单带一个一次性的安全校验码（nonce）。
				如果将来开了页面缓存，而 <code>/contact/</code> 没有被排除，
				访客拿到的会是<strong>别人那份</strong>校验码，提交就会失败——
				客户以为发出去了，其实你根本没收到。
			</div>
			<ol>
				<li>打开 <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" target="_blank"><code>/contact/</code></a>。</li>
				<li>自己填一遍并提交（姓名写「测试」、邮箱填你自己的）。</li>
				<li>确认页面提示提交成功。</li>
				<li>回到后台左侧「<strong>询盘记录</strong>」，确认新增了刚才这条。
					<div class="tk-tip" style="margin-bottom:0;">
						看到记录 = 数据一定存下来了。<strong>哪怕邮件没收到，询盘也不会丢。</strong>
					</div>
				</li>
				<li>建议<strong>每月</strong>做一次。</li>
			</ol>

			<h3>前台看不到刚改的内容？按这个顺序排查</h3>
			<ol>
				<li><strong>强制刷新</strong>：按 <code>Ctrl + F5</code>（Mac 是 <code>Cmd + Shift + R</code>）。
					<div class="tk-tip" style="margin-bottom:6px;">
						浏览器会缓存页面。90% 的「改了没变化」都是这个原因。
					</div>
				</li>
				<li>换个浏览器，或用<strong>无痕窗口</strong>再打开一次。</li>
				<li>确认内容是<strong>已发布</strong>状态，不是草稿。</li>
				<li>确认「商品分类 / 文章分类」勾了（没勾分类的内容不会出现在列表里）。</li>
				<li>以上都正常但还是看不到 → <strong>联系开发清缓存</strong>，并告诉对方：
					<ul style="margin-bottom:0;">
						<li>你改了哪个页面 / 哪个商品（给链接或标题）；</li>
						<li>你做了什么操作、大概什么时间；</li>
						<li>你期望看到什么、实际看到了什么；</li>
						<li>如果能截图，附一张截图。</li>
					</ul>
				</li>
			</ol>
		</div>

		<div class="tk-card" id="tk-sec-7" style="border-left-color:#d63638;">
			<h2>七、不要做的事</h2>

			<div class="tk-warn">
				下面这些操作<strong>可能让整站打不开，或者让数据永久丢失</strong>。
				有需要请先联系开发人员。
			</div>

			<h3>不要安装插件 / 换主题</h3>
			<ul>
				<li>不要自己装新插件。两个插件可能互相冲突，导致前台白屏。</li>
				<li>不要切换主题。一切换，现在的页面布局和样式会全部失效。</li>
				<li>不要删除已有的插件，尤其是 <strong><code>My Product CPT</code></strong>
					——它负责注册「商品」这个类型，<strong>删了商品会全部消失</strong>。</li>
			</ul>

			<h3>不要改设置</h3>
			<ul>
				<li>不要动 <strong>设置 → 固定链接</strong>。改错了所有页面都会变成 404。</li>
				<li>不要动 <strong>设置 → 常规</strong> 里的「WordPress 地址」「站点地址」。</li>
				<li>不要改用户权限、不要新建管理员账号。</li>
			</ul>

			<h3>不要删数据</h3>
			<ul>
				<li>不要随意删除<strong>商品</strong>、<strong>分类</strong>、<strong>媒体库里的图片</strong>。
					删掉分类会让商品失去归类；删掉图片会让线上页面出现破图。</li>
				<li>想「下架」一个商品：把发布状态改成<strong>草稿</strong>即可，不要删。</li>
				<li>想「停用」一个分类：编辑该分类勾上<strong>「在前台隐藏这个分类」</strong>，不要删。</li>
				<li>不要删除名为 <code>products</code>、<code>blog</code>、<code>blog-news</code>、
					<code>blog-insights</code>、<code>contact</code> 的<strong>页面</strong>，
					它们承载着商品列表、Blog 列表和联系页。</li>
			</ul>

			<h3>不要改代码 / 缓存 / 服务器</h3>
			<ul>
				<li>不要编辑任何代码文件，包括 <strong>外观 → 主题文件编辑器</strong>。
					一个符号写错就会整站白屏。</li>
				<li>不要改缓存、CDN、服务器相关设置。</li>
				<li>不要动 <code>.htaccess</code> 或任何服务器配置文件。</li>
			</ul>

			<div class="tk-tip">
				<strong>万一真的改坏了怎么办：</strong>立刻停止操作，
				把「刚才点了什么、改了什么」告诉开发人员。
				WordPress 有修订版本和回收站，多数情况下可以恢复，
				但<strong>越早说越容易救回来</strong>。
			</div>
		</div>

		<div class="tk-card" id="tk-sec-8" style="border-left-color:#2271b1;">
			<h2>八、出问题找谁</h2>

			<p>遇到下面这些情况，请联系开发人员：</p>
			<ul>
				<li>前台打不开、白屏、报错；</li>
				<li>图片显示不出来 / 变形 / 加载特别慢；</li>
				<li>联系表单提交失败，或收不到提交记录；</li>
				<li>后台某个页面报错、按钮点了没反应；</li>
				<li>改了内容前台看不到，强刷也没用；</li>
				<li>需要装插件、改主题、改固定链接、改缓存 / CDN 设置；</li>
				<li>商品要批量导入 / 批量修改（数量大时手工做不现实）。</li>
			</ul>

			<h3>联系方式</h3>
			<table>
				<tr><th>角色</th><th>负责范围</th><th>联系方式</th></tr>
				<tr>
					<td><strong>开发人员</strong></td>
					<td>代码、插件、主题、缓存、CDN、服务器、批量导入</td>
					<td><em>［请填写：姓名 / 微信 / 邮箱［请填写］］</em></td>
				</tr>
				<tr>
					<td><strong>网站负责人</strong></td>
					<td>内容审核、商品上架优先级、文案最终确认</td>
					<td><em>［请填写］</em></td>
				</tr>
				<tr>
					<td><strong>紧急联系</strong></td>
					<td>网站打不开等影响营业的故障</td>
					<td><em>［请填写］</em></td>
				</tr>
			</table>

			<div class="tk-tip">
				<strong>报问题时请附上这些信息</strong>，能大幅加快处理：
				<ol style="margin:8px 0 0 20px;">
					<li>出问题的页面链接；</li>
					<li>你做了什么操作（尽量具体）；</li>
					<li>什么时间发生的；</li>
					<li>期望看到什么、实际看到什么；</li>
					<li>截图（如果有报错信息，请把报错文字也一起发）。</li>
				</ol>
			</div>

			<h3>技术改动必须先通知开发</h3>
			<p>下面这些属于「技术改动」，<strong>即使你有权限，也请先沟通再操作</strong>：</p>
			<table>
				<tr><th>改动类型</th><th>为什么需要先沟通</th></tr>
				<tr><td>安装 / 删除 / 更新插件</td><td>可能与其他插件冲突，导致前台白屏</td></tr>
				<tr><td>更换主题、改主题设置</td><td>整站样式与页面布局会变</td></tr>
				<tr><td>修改固定链接结构</td><td>已收录的链接会全部失效，影响搜索引擎排名</td></tr>
				<tr><td>调整缓存设置</td><td>可能导致表单失效或看到旧内容</td></tr>
				<tr><td>调整 CDN 设置</td><td>可能导致图片不更新或无法访问</td></tr>
				<tr><td>修改用户角色与权限</td><td>可能造成内容被误删或信息泄露</td></tr>
			</table>

			<p style="margin-bottom:0;">
				<strong>前台地址：</strong>
				<a href="<?php echo esc_url( $site_url ); ?>" target="_blank"><?php echo esc_html( $site_url ); ?></a>
				　<strong>商品页：</strong>
				<a href="<?php echo esc_url( $products_page ); ?>" target="_blank"><?php echo esc_html( $products_page ); ?></a>
				　<strong>Blog：</strong>
				<a href="<?php echo esc_url( $blog_page ); ?>" target="_blank"><?php echo esc_html( $blog_page ); ?></a>
			</p>
		</div>

		<div class="tk-card" id="tk-sec-9" style="border-left-color:#8c8f94;">
			<h2>九、常见问题</h2>
			<dl class="tk-faq">
				<dt>我改了内容，前台怎么没变化？</dt>
				<dd>先按 <code>Ctrl + F5</code>（Mac 是 <code>Cmd + Shift + R</code>）强制刷新。如果还不行，检查是不是发布了草稿状态的内容——草稿不会显示在前台。详见
					<a href="#tk-sec-6">第六节「前台看不到刚改的内容」</a>。</dd>

				<dt>图片太大 / 上传很慢 / 前台加载很慢？</dt>
				<dd>几乎都是没压缩就上传了。用 Squoosh 或 TinyPNG 压到 <strong>500 KB 以内</strong>再传，
					并把最长边控制在 1600px 以内。详见 <a href="#tk-sec-2">第二节 图片上传规范</a>。</dd>

				<dt>图片显示出来了，但是扁的 / 被裁掉一块？</dt>
				<dd>因为图不是正方形。列表卡片用的是正方形框，非正方形的图会居中裁切。
					换一张<strong>正方形、白底</strong>的图即可。</dd>

				<dt>新商品没出现在筛选栏里？</dt>
				<dd>检查右侧「商品分类 → 产品线」有没有勾。没勾产品线的商品只在「All Products」里能看到，卡片上也不显示产品线名称。编辑商品页面顶部会有红色提示帮你看出来。</dd>

				<dt>商品 / 文章的图显示不出来？</dt>
				<dd>确认在右侧「特色图片」里选了图。另外不要用 SVG 格式，也尽量不要直接传几 MB 的原始大图——先在图片工具里压到 500KB 以内再上传。</dd>

				<dt>文章发布了但 Blog 页面看不到？</dt>
				<dd>99% 是分类没勾。打开那篇文章，右侧「文章分类」里勾上 <strong>Company News</strong> 或 <strong>Industry Insights</strong>，然后更新。</dd>

				<dt>联系表单提交失败，提示「会话已过期」？</dt>
				<dd>这是缓存导致的（表单的安全校验码被缓存了）。请<strong>立刻联系开发人员</strong>，
					并说明表单提交不了。同时提醒对方检查 <code>/contact/</code> 是否已被加入缓存排除名单。</dd>

				<dt>想临时下架一个商品，又不想删掉？</dt>
				<dd>把「发布」状态改成<strong>草稿</strong>再更新。它会立刻从列表消失，但所有内容、图片、规格都还留着，随时改回发布即可。</dd>

				<dt>想下架一个产品线分类？</dt>
				<dd>不用删。编辑该分类，勾上<strong>「在前台隐藏这个分类」</strong>即可——前台筛选栏和首页都不再显示，但已经归到这个分类的商品不会丢。</dd>

				<dt>首页产品卡片的顺序怎么调？</dt>
				<dd>到「商品 → 产品线」，编辑每个分类，填「排序」数字（越小越靠前）。首页卡片和 <code>/products/</code> 的筛选栏都按这个顺序。</dd>

				<dt>首页「Featured Lifting Solutions」怎么控制显示哪些商品？</dt>
				<dd>编辑商品，勾选「在首页 Featured Lifting Solutions 里优先展示」。首页最多显示 4 个；勾选不足 4 个时，系统用最新发布的商品补齐。</dd>

				<dt>规格参数怎么填才不会显示错？</dt>
				<dd>一行一条，中间用 <code>|</code> 或 <code>：</code> 分开参数名和值。只写参数名也可以，前台就只显示名称那一列。</dd>

				<dt>不小心改错了能恢复吗？</dt>
				<dd>可以。WordPress 会为每篇文章和商品保存「修订版本」，在编辑器右侧「文章 → 修订版本」里可以对比并还原到之前的版本。删掉的内容会先进回收站保留 30 天。<strong>但发现改错请尽快联系开发，越早越容易恢复。</strong></dd>
			</dl>
		</div>
	</div>
	<?php
}

/**
 * 在后台首页加一个指向使用指南的小面板，方便新同事找到。
 */
function tuskolift_add_dashboard_widget() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_add_dashboard_widget(
		'tuskolift_guide_widget',
		'TUSKOLIFT 内容维护',
		'tuskolift_render_dashboard_widget'
	);
}
add_action( 'wp_dashboard_setup', 'tuskolift_add_dashboard_widget' );

/**
 * 渲染后台首页面板。
 */
function tuskolift_render_dashboard_widget() {
	$guide  = admin_url( 'admin.php?page=tuskolift-guide' );
	$new_p  = admin_url( 'post-new.php?post_type=product' );
	$new_a  = admin_url( 'post-new.php' );
	?>
	<p style="font-size:13px;line-height:1.7;">
		商品和 Blog 文章都可以直接在后台发布，不需要改代码。
	</p>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( $new_p ); ?>">添加新商品</a>
		<a class="button" href="<?php echo esc_url( $new_a ); ?>">写新文章</a>
		<a class="button" href="<?php echo esc_url( $guide ); ?>">查看使用指南</a>
	</p>
	<?php
}

/* =========================================================================
 * 4. 管理邮箱还没换成真实地址时的提醒
 * ========================================================================= */

/**
 * 判断管理邮箱是不是「还没配置」的占位地址。
 *
 * 只认几种本地/保留后缀和 WordPress 默认占位值。一旦换成真实邮箱，
 * 提醒会自己消失，不需要手动关掉。
 *
 * @return bool
 */
function tuskolift_admin_email_is_placeholder() {
	$email = (string) get_option( 'admin_email' );

	if ( ! is_email( $email ) ) {
		return true;
	}

	$domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );

	foreach ( array( '.local', '.localhost', '.test', '.example', '.invalid' ) as $tld ) {
		if ( substr( $domain, -strlen( $tld ) ) === $tld ) {
			return true;
		}
	}

	// WordPress / WP Engine 的安装默认值。
	return false !== strpos( $domain, 'wpengine' ) || false !== strpos( $email, 'example.com' );
}

/**
 * 在后台首页与询盘列表页顶部提示「管理邮箱还没配」。
 *
 * 询盘会照常存进「询盘记录」，所以这里强调的是「收不到邮件通知」而不是「会丢单」。
 */
function tuskolift_admin_email_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! tuskolift_admin_email_is_placeholder() ) {
		return;
	}

	$settings = admin_url( 'options-general.php' );
	$guide    = admin_url( 'admin.php?page=tuskolift-guide' );
	$inquiries = admin_url( 'edit.php?post_type=inquiry' );
	?>
	<div class="notice notice-warning">
		<p>
			<strong>询盘邮件通知还没生效：</strong>
			当前「管理邮箱地址」是安装时的占位值
			<code><?php echo esc_html( get_option( 'admin_email' ) ); ?></code>，这个地址收不到信。
		</p>
		<p>
			好消息是<strong>一封询盘都不会丢</strong>——每条提交都会存进
			<a href="<?php echo esc_url( $inquiries ); ?>">询盘记录</a>。
			等你买好域名和邮箱后，到
			<a href="<?php echo esc_url( $settings ); ?>">设置 → 常规</a>
			把「管理邮箱地址」改成真实邮箱即可，这条提醒会自动消失。
			具体步骤见 <a href="<?php echo esc_url( $guide ); ?>">使用指南 → 邮箱与询盘通知</a>。
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'tuskolift_admin_email_notice' );

/* =========================================================================
 * 5. 首页「工厂实力」数字的设置项
 *
 * 首页原来把产量面积/型号数/国家数写死成 `XX,000㎡` / `XX+` / `XX+`，访客能直接看到
 * 占位符（docs/AUDIT-REPORT.md P0-3 A）。这些是真实经营数据，开发方不能编造，
 * 所以做成「设置 → 常规」里的三个字段：填了就显示，不填就整项不输出。
 * ========================================================================= */

/**
 * 注册「设置 → 常规」里的工厂实力字段。
 */
function tuskolift_register_site_stat_settings() {
	$fields = array(
		'tuskolift_stat_area'      => '首页工厂实力：生产面积（如 12,000㎡）',
		'tuskolift_stat_models'    => '首页工厂实力：型号数量（如 60+）',
		'tuskolift_stat_countries' => '首页工厂实力：服务国家数（如 40+）',
	);

	foreach ( $fields as $key => $label ) {
		register_setting(
			'general',
			$key,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_field(
			$key,
			esc_html( $label ),
			static function () use ( $key ) {
				printf(
					'<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text" />',
					esc_attr( $key ),
					esc_attr( (string) get_option( $key ) )
				);
				echo '<p class="description">留空则首页不显示这一项（不会显示 XX 占位符）。</p>';
			},
			'general'
		);
	}
}
add_action( 'admin_init', 'tuskolift_register_site_stat_settings' );

/**
 * 工厂实力数字还没填全时提醒管理员。
 */
function tuskolift_admin_stats_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$missing = array();
	foreach ( array(
		'tuskolift_stat_area'      => '生产面积',
		'tuskolift_stat_models'    => '型号数量',
		'tuskolift_stat_countries' => '国家数量',
	) as $key => $label ) {
		if ( '' === trim( (string) get_option( $key ) ) ) {
			$missing[] = $label;
		}
	}

	if ( ! $missing ) {
		return;
	}

	printf(
		'<div class="notice notice-info"><p><strong>首页还有 %d 项工厂实力数字没填：</strong>%s。这些是公司真实经营数据，需由网站负责人提供；未填时首页自动隐藏该项，不会显示占位符。请到 <a href="%s">设置 → 常规</a> 填写。</p></div>',
		count( $missing ),
		esc_html( implode( '、', $missing ) ),
		esc_url( admin_url( 'options-general.php' ) )
	);
}
add_action( 'admin_notices', 'tuskolift_admin_stats_notice' );
