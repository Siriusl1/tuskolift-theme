<?php
/**
 * The front page template
 *
 * Displays the homepage content from the static HTML index.html
 *
 * @package TUSKOLIFT
 */

get_header();
?>

<main id="primary" tabindex="-1" class="site-main index-banner">

	<!-- 01 HERO BANNER -->
             
        <section class="hero-section" id="hero">
            <div class="hero-inner">
                <div class="hero-copy">
                    <p class="hero-kicker">TUSKOLIFT Material Handling Equipment</p>

                    <h1>
                        One-Stop <span class="brand-highlight">Material Handling</span><br>
                        Equipment Solutions Provider
                    </h1>

                    <p class="hero-subtitle">
                        Hydraulic Lift Tables &amp;<br>Material Handling Equipment Manufacturer
                    </p>

                    <ul class="hero-points">
                        <li>16+ Years of Manufacturing Experience</li>
                        <li>OEM &amp; Customized Solutions Available</li>
                        <li>Multiple Capacities &amp; Configurations</li>
                        <li>Worldwide B2B Supply &amp; Shipping</li>
                    </ul>

                    <div class="hero-cta">
                        <a href="#contact" class="hero-btn hero-btn-primary">Get a Quote</a>
                        <a href="<?php echo esc_url( home_url( '/products/' ) ); ?>" class="hero-btn hero-btn-outline">Explore Products</a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-main-product">
                        <?php
                        /*
                         * 主图是首页 LCP 元素：走媒体库 + tk-card，自动带 srcset/width/height，
                         * 并显式 eager + fetchpriority=high（见 inc/tk-hero-images.php）。
                         * 原先是硬编码 doc/白底贴标-/4-脚踩下降款.png（534,150 B）。
                         */
                        tuskolift_hero_image( 'lift-tables', 'tk-card', 'Hydraulic Scissor Lift Table', true );
                        ?>
                        <span>Hydraulic Lift Table</span>
                    </div>

                    <div class="hero-sub-products">
                        <div class="hero-sub-product">
                            <?php tuskolift_hero_image( 'pallet-trucks', 'tk-card', 'Manual Pallet Truck' ); ?>
                            <span>Pallet Truck</span>
                        </div>
                        <div class="hero-sub-product">
                            <?php tuskolift_hero_image( 'high-lift', 'tk-card', 'High-Lift Pallet Truck' ); ?>
                            <span>High-Lift</span>
                        </div>
                        <div class="hero-sub-product">
                            <?php tuskolift_hero_image( 'pallet-stackers', 'tk-card', 'Pallet Stacker' ); ?>
                            <span>Pallet Stacker</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        
             <!-- 02 Our Product Range -->
             
        <section class="template-section" id="product-categories" style="padding-bottom:0;">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Our Product Range</p>
                    <h2>Complete Material Handling Solutions for Global B2B Buyers</h2>
                </div>
                <p>Six core product families — manual, semi-electric and electric configurations available across lift tables, pallet trucks and stackers.</p>
            </div>
        </section>

        <div class="product-grid">
            <?php
            /*
             * 商品范围：循环后台「商品 → 产品线」里的分类。
             * 名称、描述、配图、排序、「前台隐藏」都在后台维护。
             */
            $tk_lines = tuskolift_get_terms_ordered( 'product_line' );

            if ( $tk_lines ) :
                foreach ( $tk_lines as $tk_line ) :
                    $tk_image = tuskolift_product_line_image( $tk_line );
                    $tk_desc  = $tk_line->description ? $tk_line->description : 'View the full range of this product line.';
                    ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <?php if ( $tk_image['url'] ) : ?>
                                <img src="<?php echo esc_url( $tk_image['url'] ); ?>" alt="<?php echo esc_attr( $tk_image['alt'] ); ?>">
                            <?php else : ?>
                                <div class="product-image-empty">No image set for this product line</div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <h3 class="title"><?php echo esc_html( $tk_line->name ); ?></h3>
                                <p class="desc"><?php echo esc_html( $tk_desc ); ?></p>
                                <a href="<?php echo esc_url( home_url( '/products/' ) . '#' . $tk_line->slug ); ?>" class="btn-link">VIEW MORE →</a>
                            </div>
                        </div>
                    </div>
                    <?php
                endforeach;
            else :
                ?>
                <p class="tk-empty">No product lines yet. Add one under Products → Product Lines in the dashboard.</p>
            <?php endif; ?>
        </div>

        
             <!-- 03 Featured Lifting Solutions -->
             
        <section class="template-section" id="featured-products">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Featured Lifting Solutions</p>
                    <h2>Reliable Equipment for Lifting, Handling and Positioning</h2>
                </div>
                <p>Representative products from our main product families — each supported by full specifications and customization options.</p>
            </div>

            <div class="featured-grid">
                <?php
                /*
                 * 精选商品：后台勾了「首页精选」的商品优先，不足 4 个时用最新发布的商品补齐。
                 *
                 * 这里以前是「取 100 条商品再在 PHP 里排序取前 4」——
                 * 商品上千时等于每次打开首页都查 100 条 + 100 次 meta 查询，只为显示 4 个。
                 * 现在改成两步直查：
                 *   1. 先只捞「首页精选」，最多 4 条（meta_query + 数量上限，由数据库完成）；
                 *   2. 不够 4 个时，再用一条排除已选 ID 的查询补齐。
                 * 两次查询都带 posts_per_page 上限，跟商品总数无关。
                 */
                $tk_featured_ids = get_posts(
                    array(
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => 4,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'fields'         => 'ids',
                        'no_found_rows'  => true, // 不需要分页总数，省一次 COUNT。
                        'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                            array(
                                'key'   => '_tk_featured',
                                'value' => '1',
                            ),
                        ),
                    )
                );

                $tk_ids = array_map( 'intval', $tk_featured_ids );

                // 精选不足 4 个，用最新的商品补满（排除已经选中的）。
                if ( count( $tk_ids ) < 4 ) {
                    $tk_fill = get_posts(
                        array(
                            'post_type'      => 'product',
                            'post_status'    => 'publish',
                            'posts_per_page' => 4 - count( $tk_ids ),
                            'orderby'        => 'date',
                            'order'          => 'DESC',
                            'fields'         => 'ids',
                            'no_found_rows'  => true,
                            'post__not_in'   => $tk_ids ? $tk_ids : array( 0 ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
                        )
                    );

                    $tk_ids = array_merge( $tk_ids, array_map( 'intval', $tk_fill ) );
                }

                $tk_featured = $tk_ids
                    ? new WP_Query(
                        array(
                            'post_type'      => 'product',
                            'post__in'       => $tk_ids,
                            'orderby'        => 'post__in',
                            'posts_per_page' => 4,
                            'no_found_rows'  => true,
                        )
                    )
                    : new WP_Query( array( 'post_type' => 'product', 'post__in' => array( 0 ) ) );

                if ( $tk_featured->have_posts() ) :
                    $tk_index = 0;
                    while ( $tk_featured->have_posts() ) :
                        $tk_featured->the_post();
                        ++$tk_index;
                        ?>
                        <a class="featured-card" href="<?php the_permalink(); ?>">
                            <div class="featured-number"><?php echo esc_html( sprintf( '%02d', $tk_index ) ); ?></div>
                            <div>
                                <h3><?php the_title(); ?></h3>
                                <?php if ( has_excerpt() ) : ?>
                                    <p><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
                                <?php endif; ?>
                                <span>VIEW DETAILS →</span>
                            </div>
                        </a>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <p class="tk-empty">No published products yet. Publish one under Products → Add New Product and it will appear here.</p>
                <?php endif; ?>
            </div>
        </section>

        
             <!-- 04 Why Choose TUSKOLIFT -->
             
        <section class="template-section" id="why-choose">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Why Choose TUSKOLIFT</p>
                    <h2>Built for Reliable Quality, Flexible Solutions and Global B2B Supply</h2>
                </div>
                <p>Four reasons international distributors, wholesalers and industrial buyers choose TUSKOLIFT as their material handling partner.</p>
            </div>

            <div class="capability-grid">
                <article class="capability-card">
                    <strong>01 / FACTORY DIRECT</strong>
                    <h3>Factory Direct Supply</h3>
                    <p>Direct communication with the manufacturer for consistent quality and competitive B2B pricing.</p>
                </article>
                <article class="capability-card">
                    <strong>02 / CUSTOMIZATION</strong>
                    <h3>Flexible Customization</h3>
                    <p>Customize platform size, lifting height, load capacity, color and other configurations.</p>
                </article>
                <article class="capability-card">
                    <strong>03 / QUALITY</strong>
                    <h3>Strict Quality Control</h3>
                    <p>Inspection and testing throughout production to ensure reliable performance before shipment.</p>
                </article>
                <article class="capability-card">
                    <strong>04 / GLOBAL SUPPORT</strong>
                    <h3>Global B2B Support</h3>
                    <p>Export packaging, documentation and shipping support for distributors and industrial buyers.</p>
                </article>
            </div>
        </section>

        
             <!-- 05 Industry Applications -->
             
        <section class="template-section" id="solutions">
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Industry Applications</p>
                    <h2>Lifting Solutions for Multiple Industries</h2>
                </div>
                <p>Designed to improve material handling efficiency across different work environments — from warehouse floors to production lines.</p>
            </div>

            <div class="solution-tabs" role="tablist" aria-label="Industry applications">
                <button class="solution-tab is-active" type="button" data-solution="warehouse">Warehouse &amp; Logistics</button>
                <button class="solution-tab" type="button" data-solution="manufacturing">Manufacturing</button>
                <button class="solution-tab" type="button" data-solution="assembly">Assembly Lines</button>
                <button class="solution-tab" type="button" data-solution="automotive">Automotive</button>
                <button class="solution-tab" type="button" data-solution="maintenance">Maintenance &amp; Workshops</button>
            </div>

            <div class="solution-content">
                <div class="solution-number" id="solutionNumber">01 / 05</div>
                <div class="solution-copy">
                    <h3 id="solutionTitle">Warehouse &amp; Logistics</h3>
                    <p id="solutionDescription">Pallet trucks, stackers and lift tables support efficient pallet movement, lifting and positioning throughout warehouse and logistics operations.</p>
                </div>
            </div>

            <a class="text-link" href="<?php echo esc_url( home_url( '/offerings/offerings-solutions/' ) ); ?>">EXPLORE APPLICATIONS →</a>
        </section>

        
             <!-- 06 OEM / Custom Solutions -->
             
        <section class="oem-section" id="oem">
            <div class="oem-copy">
                <p class="section-kicker">OEM / Custom Solutions</p>
                <h3>Need a Custom Lifting Solution?</h3>
                <p>
                    From standard equipment to solutions tailored to your requirements —
                    tell us the load capacity, platform size, lifting height, application
                    and quantity, and our team will recommend the suitable configuration.
                </p>
                <ul class="oem-features">
                    <li>Custom Platform Sizes</li>
                    <li>Different Load Capacities</li>
                    <li>Custom Lifting Heights</li>
                    <li>OEM / Private Label Support</li>
                </ul>
                <a class="text-link" href="#contact">SEND US YOUR REQUIREMENTS →</a>
            </div>

            <div class="oem-steps">
                <div class="oem-step">
                    <strong>01 / REQUIREMENTS</strong>
                    <h4>Tell Us Your Requirements</h4>
                    <p>Load capacity, platform size, lifting height, application, quantity, etc.</p>
                </div>
                <div class="oem-step">
                    <strong>02 / ENGINEERING</strong>
                    <h4>Engineering Solution</h4>
                    <p>Our team recommends the suitable configuration based on your application.</p>
                </div>
                <div class="oem-step">
                    <strong>03 / PRODUCTION</strong>
                    <h4>Production &amp; Delivery</h4>
                    <p>Manufacturing, inspection and export shipment — with OEM / private label support.</p>
                </div>
            </div>
        </section>

        
             <!-- 07 Factory & Quality -->
             
        <section class="about-section" id="company">
            <div class="about-video">
                <?php
                /*
                 * 工厂视频。
                 *
                 * 修复记录（P0-5 / P0-6）：
                 *   原来视频硬编码在主题目录 doc/白底贴标-/home-video.mp4 —— 主题目录里
                 *   那 54 MB 的原始素材对运行完全无用，却会被打进生产包。现已把视频导入
                 *   媒体库（附件 #1117），模板从媒体库取地址，doc/ 目录可以整体删除。
                 *
                 * poster：用媒体库里已有的产品线配图，让首屏先显示实拍图而不是黑框。
                 *
                 * 仍待办（需客户决策 / 需转码工具，见 docs/LAUNCH-BLOCKERS.md）：
                 *   - 视频 7.16 MB，建议转码成 H.264 720p 目标 < 1.5 MB，或托管到 YouTube/Vimeo
                 *   - 补 <track kind="captions">（当前无字幕）
                 * 这两项不影响安全与可用性，故不阻塞上线，但影响海外客户的加载体验。
                 */
                $tk_video_id = (int) apply_filters( 'tuskolift_factory_video_id', 1117 );

                // 附件被删/被换成图片时不要输出空的 <video>。
                $tk_video_url = '';
                if ( $tk_video_id && 'attachment' === get_post_type( $tk_video_id ) ) {
                    $tk_video_mime = get_post_mime_type( $tk_video_id );
                    if ( $tk_video_mime && 0 === strpos( $tk_video_mime, 'video/' ) ) {
                        $tk_video_url = wp_get_attachment_url( $tk_video_id );
                    }
                }

                $tk_video_poster_id = tuskolift_hero_term_image_id( 'lift-tables' );
                $tk_video_poster    = $tk_video_poster_id ? wp_get_attachment_image_url( $tk_video_poster_id, 'tk-card' ) : '';
                ?>

                <?php if ( $tk_video_url ) : ?>
                    <video
                        src="<?php echo esc_url( $tk_video_url ); ?>"
                        <?php if ( $tk_video_poster ) : ?>poster="<?php echo esc_url( $tk_video_poster ); ?>"<?php endif; ?>
                        controls
                        muted
                        playsinline
                        preload="metadata">
                    </video>
                <?php elseif ( $tk_video_poster ) : ?>
                    <?php // 视频缺失时退回一张静态图，而不是留一个空黑框。 ?>
                    <img src="<?php echo esc_url( $tk_video_poster ); ?>" alt="TUSKOLIFT manufacturing facility" loading="lazy" decoding="async">
                <?php endif; ?>
            </div>

            <div class="about-copy">
                <p class="section-kicker">Built by Experience. Tested for Reliability.</p>
                <h3>Professional Manufacturing for Consistent Quality and Long-Term Performance</h3>
                <p>
                    From raw material to finished equipment, our production and inspection
                    processes are built around the reliability requirements of international
                    B2B buyers.
                </p>

                <?php
                /*
                 * 工厂实力数字 —— 可配置，不再硬编码。
                 *
                 * 原来这里直接写死了 `XX,000㎡` / `XX+` / `XX+`，访客一眼就能看到占位符
                 * （实测命中，见 docs/AUDIT-REPORT.md P0-3 A）。
                 * 这些是公司的真实经营数据，**不能由开发方凭空填**，所以：
                 *   - 在后台「设置 → 常规」会多出三个字段可填（见 inc/tk-admin.php 的
                 *     tuskolift_register_setting_fields()；也可用 wp option update 写入）；
                 *   - 填了 → 显示真实数字；
                 *   - 没填 → 该项整块不输出，绝不显示 "XX" 占位。
                 * 详见 docs/LAUNCH-BLOCKERS.md「首页工厂实力真实数字」。
                 */
                $tk_stats = array(
                    array( 'value' => '16+', 'label' => 'Years Manufacturing' ),
                    array( 'value' => get_option( 'tuskolift_stat_area' ), 'label' => 'Production Area' ),
                    array( 'value' => get_option( 'tuskolift_stat_models' ), 'label' => 'Product Models' ),
                    array( 'value' => get_option( 'tuskolift_stat_countries' ), 'label' => 'Countries Served' ),
                );
                $tk_stats = array_filter(
                    $tk_stats,
                    static function ( $s ) {
                        return '' !== trim( (string) $s['value'] );
                    }
                );
                ?>
                <?php if ( $tk_stats ) : ?>
                <div class="factory-stats">
                    <?php foreach ( $tk_stats as $tk_stat ) : ?>
                        <div class="factory-stat">
                            <b><?php echo esc_html( $tk_stat['value'] ); ?></b>
                            <span><?php echo esc_html( $tk_stat['label'] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <a class="text-link" href="<?php echo esc_url( home_url( '/about/about-quality/' ) ); ?>">WATCH OUR FACTORY / ABOUT TUSKOLIFT →</a>
            </div>
        </section>

        
             <!-- 08 Global B2B Supply -->
             
        <section class="template-section esg-section" id="global-supply">
            <div class="esg-copy">
                <p class="section-kicker">Global B2B Supply</p>
                <h3>Your Global Material Handling Partner</h3>
                <p>
                    Supporting distributors, wholesalers and industrial buyers with export-ready
                    solutions — from bulk orders and packaging to documentation and worldwide shipping.
                </p>
                <a class="text-link" href="#contact">BECOME A TUSKOLIFT PARTNER →</a>
            </div>

            <div class="esg-markers">
                <div class="esg-marker">
                    <strong>01 / BULK ORDER</strong>
                    <span>Bulk order support for distributors and wholesalers.</span>
                </div>
                <div class="esg-marker">
                    <strong>02 / EXPORT PACKAGING</strong>
                    <span>Export-ready packaging designed for long-distance shipping.</span>
                </div>
                <div class="esg-marker">
                    <strong>03 / DOCUMENTATION</strong>
                    <span>Technical documentation and worldwide shipping support.</span>
                </div>
                <div class="esg-marker">
                    <strong>04 / SHIPPING</strong>
                    <span>Worldwide shipping support with container loading coordination.</span>
                </div>
            </div>
        </section>

        
             <!-- 09 Final RFQ -->
             
        <section class="contact-section" id="contact">
            <div>
                <p class="section-kicker">Start a Conversation</p>
                <h2>Looking for the Right Lifting Equipment?</h2>
                <p>
                    Tell us what you need — we'll recommend a suitable solution. Share your
                    product, application, load capacity, lifting height, quantity and country,
                    and our team will get back to you with a quote or configuration suggestion.
                </p>
            </div>

            <?php
            /*
             * 首页 RFQ 表单 —— 由 WPForms 渲染（表单 ID 97「Homepage Quote Request」）。
             * 字段、收件邮箱、确认语都在后台「WPForms → 所有表单」里维护。
             */
            tuskolift_render_inquiry_form( TUSKOLIFT_FORM_QUOTE, '首页报价表单' );
            ?>
        </section>

</main>

<?php get_footer(); ?>
