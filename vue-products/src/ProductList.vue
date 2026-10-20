<template>
  <section class="npd-catalog-layout" id="catalog">
    <aside class="npd-sidebar" aria-label="Product filters">
      <!--
        筛选分组。
        桌面端两组始终展开（标题不可点）；
        移动端（≤900px）默认折叠，点标题展开，再点收起 —— 逻辑见下方 toggleGroup()。
      -->
      <div
        class="npd-filter-group"
        :class="{ 'is-open': isGroupOpen('line') }"
      >
        <button
          class="npd-filter-title"
          type="button"
          aria-controls="npd-options-line"
          :aria-expanded="isGroupOpen('line') ? 'true' : 'false'"
          @click="toggleGroup('line')"
        >
          <span>Browse by Product Line</span>
          <span class="npd-filter-caret" aria-hidden="true"></span>
        </button>

        <div id="npd-options-line" class="npd-filter-options">
          <button
            class="npd-filter-btn"
            :class="{ 'is-active': activeType === 'line' && activeFilter === 'all' }"
            @click="setFilter('line', 'all', 'All Products')"
          >
            <span>All Products</span>
            <!--
              这里必须用服务端返回的 total，不能用 products.length。
              products 只是「已加载的条数」（每页 24），商品超过 24 个时会显示成
              「All Products 24」而实际有几百个 —— 与本文件下面 setFilter 处的注释自相矛盾。
            -->
            <span class="npd-filter-count">{{ total }}</span>
          </button>
          <button
            v-for="line in productLines"
            :key="line.slug"
            :id="'line-' + line.slug"
            class="npd-filter-btn"
            :class="{ 'is-active': activeType === 'line' && activeFilter === line.slug, 'is-empty': !line.count }"
            @click="setFilter('line', line.slug, line.name)"
          >
            <span>{{ line.name }}</span>
            <span class="npd-filter-count">{{ line.count }}</span>
          </button>
        </div>
      </div>

      <div
        class="npd-filter-group"
        :class="{ 'is-open': isGroupOpen('application') }"
      >
        <button
          class="npd-filter-title"
          type="button"
          aria-controls="npd-options-application"
          :aria-expanded="isGroupOpen('application') ? 'true' : 'false'"
          @click="toggleGroup('application')"
        >
          <span>Browse by Application</span>
          <span class="npd-filter-caret" aria-hidden="true"></span>
        </button>

        <div id="npd-options-application" class="npd-filter-options">
          <button
            v-for="app in applications"
            :key="app.slug"
            :id="'app-' + app.slug"
            class="npd-filter-btn"
            :class="{ 'is-active': activeType === 'application' && activeFilter === app.slug, 'is-empty': !app.count }"
            @click="setFilter('application', app.slug, app.name)"
          >
            <span>{{ app.name }}</span>
            <span class="npd-filter-count">{{ app.count }}</span>
          </button>
          <p v-if="!applications.length" class="npd-filter-hint">
            No application categories yet.
          </p>
        </div>
      </div>
    </aside>

    <div class="npd-catalog-results">
      <div class="npd-results-bar">
        <div>
          <p class="section-kicker">Product Catalog</p>
          <h2>{{ resultTitle }}</h2>
        </div>
        <!-- 用服务端返回的总数，而不是"已加载条数"——分页后后者只是当前页的量 -->
        <span class="npd-results-count">{{ total }} products</span>
      </div>

      <article
        v-for="product in filteredProducts"
        :key="product.id"
        class="npd-product-card"
      >
        <div class="npd-card-image">
          <img
            v-if="product.featured_image"
            :src="product.featured_image"
            :alt="product.title"
            loading="lazy"
          >
          <span v-else class="npd-card-placeholder">暂无配图<br><small>请在后台设置「特色图片」</small></span>
        </div>
        <div class="npd-card-info">
          <p class="npd-card-kicker">{{ product.product_line_name }}</p>
          <!--
            用文本插值（{{ }}）而不是 v-html。
            改自 v-html：REST 返回的标题来自 Author 可写的商品标题，
            一旦还原成 HTML 就会变成可执行的标签（存储型 XSS）。
            文本插值由 Vue 自动转义，实体只会显示为实体文本，永远不会成为标签。
          -->
          <h3>{{ product.title }}</h3>
          <p>{{ product.excerpt }}</p>
          <div class="npd-tags">
            <span v-for="app in product.applications" :key="app.slug" class="npd-tag">{{ app.name }}</span>
          </div>
          <a class="npd-detail-link" :href="product.link">VIEW PRODUCT →</a>
        </div>
      </article>

      <div v-if="loading" class="npd-empty is-visible">
        Loading products…
      </div>

      <div v-else-if="error" class="npd-empty is-visible">
        Failed to load products: {{ error }}
      </div>

      <div v-else-if="filteredProducts.length === 0" class="npd-empty is-visible">
        No products are currently assigned to this category.
      </div>

      <!--
        加载更多：商品上千时不会一次拉完，按需再取下一批。
        这样首屏只需传输 24 条，而不是把上千条全部塞进页面。
      -->
      <div v-else-if="page < totalPages" class="npd-load-more">
        <button
          type="button"
          class="npd-load-more-btn"
          :disabled="loadingMore"
          @click="loadMore"
        >
          {{ loadingMore ? 'LOADING…' : 'LOAD MORE PRODUCTS' }}
        </button>
        <p class="npd-load-more-hint">
          Showing {{ filteredProducts.length }} of {{ total }}
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

// 相对路径，自动跟随当前站点域名。
// 商品目录走主题自己的轻量接口（服务端分页 + 字段裁剪），见 inc/tk-catalog-rest.php。
const API = '/wp-json/tuskolift/v1';

const products = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const error = ref(null);

// 服务端分页状态：商品上千时不可能一次拉完，按页取 + 「加载更多」
const PER_PAGE = 24;
const page = ref(1);
const total = ref(0);
const totalPages = ref(1);

const activeType = ref('line');
const activeFilter = ref('all');
const resultTitle = ref('All Products');

/*
 * 首屏锚点直筛：/products/#pallet-trucks
 * 在第一次请求之前就把筛选条件定好，这样第一个请求就带上了 line/apply 参数，
 * 不必"先拉全部再筛一遍"（商品多时那等于白拉一页）。
 * 分类名要等接口返回术语表才知道，所以这里先只记 slug，拿到术语后再补 label。
 */
const initialHash = decodeURIComponent((window.location.hash || '').replace(/^#/, '')).trim();
let hashApplied = false;

const productLines = ref([]);
const applications = ref([]);

/* -------------------------------------------------------------------------
 * 筛选分组折叠（仅移动端）
 *
 * 桌面端（>900px）两组始终展开，标题不可点；
 * 移动端（≤900px）默认全部收起，点标题展开一组、再点收起。
 * isMobile 在 setup 阶段就同步求值，避免首屏先展开再收起闪一下。
 * ----------------------------------------------------------------------- */
const MOBILE_QUERY = '(max-width: 900px)';
const isMobile = ref(
  typeof window !== 'undefined' && typeof window.matchMedia === 'function'
    ? window.matchMedia(MOBILE_QUERY).matches
    : false
);
const openGroup = ref(null);
const isGroupOpen = (key) => !isMobile.value || openGroup.value === key;

const toggleGroup = (key) => {
  if (!isMobile.value) return; // 桌面端标题不响应点击
  openGroup.value = openGroup.value === key ? null : key;
};

// 后台注入的分类法配置（含自定义排序、前台隐藏过滤、商品计数）。
// 由 functions.php 的 my_enqueue_vue_products() 通过 wp_add_inline_script 输出。
const injectedTerms = window.TK_CATALOG_TERMS || null;

// 术语名同样走文本插值，不再需要实体解码。
// 这里原来有个 decodeEntities()（textarea.innerHTML 回填再读 value），它会把
// `&lt;` 还原成字面量 `<`；配合 v-html 就是 XSS 的第二环，已一并删除。
const mapTerms = (terms) =>
  (terms || []).map((term) => ({
    name: term.name,
    slug: term.slug,
    count: term.count || 0,
  }));

// 只有拿不到后台顺序时才自己排：有商品的排前面，其余按名称
const sortByCountThenName = (list) =>
  list.slice().sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));

/*
 * 筛选已经在服务端完成（接口带 line / apply 参数），
 * 所以这里不再做本地过滤 —— 本地过滤在"只加载了第一页"时结果是错的。
 */
const filteredProducts = computed(() => products.value);

/**
 * 切换筛选：重置到第 1 页并重新请求。
 *
 * 必须重新请求而不是本地过滤：分页后本地只有当前页的数据，
 * 本地过滤会把"这个分类下其它页的商品"漏掉。
 */
const setFilter = (type, filter, label, skipFetch = false) => {
  const changed = activeType.value !== type || activeFilter.value !== filter;
  activeType.value = type;
  activeFilter.value = filter;
  resultTitle.value = label;

  if (skipFetch || !changed) return;
  page.value = 1;
  loading.value = true;
  error.value = null;
  fetchProducts();
};

// 移动端展开某一组筛选（桌面端无需处理）
const revealGroup = (type) => {
  if (isMobile.value) openGroup.value = type;
};

/*
 * 支持 /products/#pallet-trucks 这类锚点直接筛出对应产品线（导航栏与首页卡片都用这个）。
 *
 * 注意 onHashChange 参数：
 *   首次载入时（fetchProducts 内部调用）传 false —— 那一页已经按当前筛选取回来了，
 *   再触发一次请求就重复了；
 *   用户后续手动改锚点（hashchange 事件）时传 true，需要真的重新取数据。
 */
const applyHashFilter = (refetch = false) => {
  const slug = decodeURIComponent((window.location.hash || '').replace(/^#/, '')).trim();
  if (!slug) return;

  const line = productLines.value.find((l) => l.slug === slug);
  if (line) {
    setFilter('line', line.slug, line.name, !refetch);
    revealGroup('line');
    return;
  }

  const app = applications.value.find((a) => a.slug === slug);
  if (app) {
    setFilter('application', app.slug, app.name, !refetch);
    revealGroup('application');
  }
};

const fetchProducts = async () => {
  try {
    /*
     * 走主题自带的 /tuskolift/v1/catalog 接口，而不是 /wp/v2/products?_embed。
     *
     * 原因（商品上千时特别关键）：
     *   WordPress 核心把 REST 的 per_page 上限写死成 100
     *   （wp-includes/rest-api/endpoints/class-wp-rest-controller.php），
     *   传 1000 也只返回 100 条 —— 第 101 个商品起前台会静默消失。
     *   自建接口由我们自己分页，并且只返回卡片需要的字段（不带 content.rendered、
     *   不带 _embed 的整包图片/分类对象），单页体积小得多。
     */
    const params = new URLSearchParams();
    params.set('page', String(page.value));
    params.set('per_page', String(PER_PAGE));

    /*
     * 首个请求可能还没解析出「锚点对应哪个分类」（术语表要等接口返回），
     * 这时直接把 URL 里的 slug 交给服务端筛，避免先拉一页无关商品再丢掉。
     */
    const effectiveType = activeFilter.value === 'all' && ! hashApplied && initialHash
      ? 'line'
      : activeType.value;
    const effectiveFilter = activeFilter.value === 'all' && ! hashApplied && initialHash
      ? initialHash
      : activeFilter.value;

    if (effectiveType === 'line' && effectiveFilter !== 'all') {
      params.set('line', effectiveFilter);
    }
    if (effectiveType === 'application' && effectiveFilter !== 'all') {
      params.set('apply', effectiveFilter);
    }

    const res = await fetch(`${API}/catalog?${params.toString()}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const payload = await res.json();

    const mapped = (payload.items || []).map((item) => ({
      id: item.id,
      // 直接透传：渲染端用文本插值，实体只会显示为实体文本
      title: item.title,
      excerpt: item.excerpt,
      link: item.link,
      featured_image: item.featured_image,
      product_line_name: item.product_line_name,
      product_line_slug: item.product_line_slug,
      applications: (item.applications || []).map((a) => ({
        name: a.name,
        slug: a.slug,
      })),
    }));

    // 第一页替换，后续页累加（「加载更多」）
    products.value = page.value === 1 ? mapped : products.value.concat(mapped);
    total.value = payload.total || mapped.length;
    totalPages.value = payload.total_pages || 1;

    if ( injectedTerms ) {
      // 顺序 = 后台「排序」字段的顺序，隐藏的分类已在服务端过滤掉
      productLines.value = mapTerms( injectedTerms.lines );
      applications.value = mapTerms( injectedTerms.applications );
    } else {
      // 没有注入配置时退回用当前页的商品反推分类（顺序不保证，自己排一下）
      const lineMap = new Map();
      const appMap = new Map();

      products.value.forEach((p) => {
        if (p.product_line_slug && !lineMap.has(p.product_line_slug)) {
          lineMap.set(p.product_line_slug, {
            name: p.product_line_name,
            slug: p.product_line_slug,
            count: 0,
          });
        }
        p.applications.forEach((a) => {
          if (!appMap.has(a.slug)) {
            appMap.set(a.slug, { name: a.name, slug: a.slug, count: 0 });
          }
        });
      });

      productLines.value = sortByCountThenName( Array.from( lineMap.values() ) );
      applications.value = sortByCountThenName( Array.from( appMap.values() ) );
    }

    if ( ! hashApplied ) {
      hashApplied = true;
      applyHashFilter( false );
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

// 「加载更多」：商品多于一页时，把下一页追加进来
const loadMore = () => {
  if (loadingMore.value || page.value >= totalPages.value) return;
  loadingMore.value = true;
  page.value += 1;
  fetchProducts().finally(() => {
    loadingMore.value = false;
  });
};

onMounted(() => {
  fetchProducts();
  window.addEventListener('hashchange', applyHashFilter);

  // 桌面端 ↔ 移动端切换时同步折叠状态：回到桌面端就全部展开（openGroup 清空）
  if (typeof window.matchMedia === 'function') {
    const mq = window.matchMedia(MOBILE_QUERY);
    const syncViewport = (event) => {
      isMobile.value = event.matches;
      if (!event.matches) openGroup.value = null;
    };
    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', syncViewport);
    } else if (typeof mq.addListener === 'function') {
      mq.addListener(syncViewport); // 老版 Safari
    }
  }
});
</script>

<style scoped>
.npd-catalog-layout{
            width:min(calc(100% - 80px),1440px);
            margin:0 auto;
            display:grid;
            grid-template-columns:280px minmax(0,1fr);
            gap:60px;
            padding:80px 0 110px;
        }
        .npd-sidebar{
            position:sticky;
            top:110px;
            align-self:start;
        }
        /* 筛选分组。桌面端两组常开；移动端折叠，展开状态由 .is-open 控制（见文件末尾的 @media） */
        .npd-filter-group{
            border-top:1px solid #1a1a1a;
            padding:18px 0 24px;
        }
        .npd-filter-group:last-child{border-bottom:1px solid #ddd;}
        /* 标题。桌面端只是个不可点的小标题；移动端才是折叠开关 */
        .npd-filter-title{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            width:100%;
            margin:0 0 10px;
            padding:0;
            border:0;
            background:transparent;
            color:#1a1a1a;
            font:inherit;
            font-size:12px;
            font-weight:700;
            letter-spacing:.14em;
            text-transform:uppercase;
            text-align:left;
            cursor:default;
        }
        /* 折叠箭头只在移动端出现 */
        .npd-filter-caret{display:none;}
        .npd-filter-options{
            display:grid;
            gap:8px;
        }
        .npd-filter-btn{
            display:flex;
            align-items:baseline;
            gap:8px;
            width:100%;
            border:0;
            background:transparent;
            color:#666;
            padding:10px 0;
            text-align:left;
            font:inherit;
            font-size:15px;
            cursor:pointer;
            transition:color .2s ease,padding-left .2s ease;
        }
        .npd-filter-btn > span:first-child{flex:1 1 auto;}
        .npd-filter-btn:hover,.npd-filter-btn.is-active{
            color:#1a1a1a;
            padding-left:8px;
            font-weight:700;
        }
        .npd-filter-btn.is-active::before{
            content:"— ";
            flex:0 0 auto;
            color:#344069;
        }
        .npd-filter-btn.is-empty{color:#767676;}
        /* 放在 .is-active 之后，保证计数不会被加粗 */
        .npd-filter-btn .npd-filter-count{
            flex:0 0 auto;
            color:#767676;
            font-size:12px;
            font-weight:400;
            font-variant-numeric:tabular-nums;
        }
        .npd-filter-hint{
            margin:6px 0 0;
            color:#aaa;
            font-size:12px;
            line-height:1.6;
        }
        .npd-results-bar{
            display:flex;
            justify-content:space-between;
            align-items:end;
            gap:24px;
            padding-bottom:24px;
            border-bottom:1px solid #ddd;
            margin-bottom:16px;
        }
        .npd-results-bar h2{
            font-size:clamp(32px,4vw,52px);
            line-height:1.05;
            letter-spacing:-.04em;
        }
        .npd-results-count{
            color:#777;
            font-size:13px;
            white-space:nowrap;
        }
        .npd-product-card{
            display:grid;
            grid-template-columns:minmax(280px,.9fr) minmax(0,1.1fr);
            gap:40px;
            padding:42px 0;
            border-bottom:1px solid #e5e5e5;
        }
        .npd-product-card[hidden]{display:none;}
        .npd-card-image{
            aspect-ratio:1/1;
            background:#f4f4f2;
            overflow:hidden;
            display:grid;
            place-items:center;
        }
        .npd-card-image img{
            width:100%;height:100%;object-fit:contain;
            transition:transform .5s ease;
        }
        .npd-card-placeholder{
            display:grid;
            place-items:center;
            padding:24px;
            color:#aaa;
            font-size:13px;
            line-height:1.7;
            text-align:center;
        }
        .npd-card-placeholder small{color:#bfbfbf;font-size:11px;}
        /*
         * 图片放大效果。
         * 触发条件刻意绑在「图片本身」而不是整张卡片上：卡片是一整行（图 + 大段文字），
         * 绑卡片会导致鼠标扫过文字区域时图片也跟着动，看起来像"鼠标在页面上乱动图片就动"。
         * 外面套 hover:hover 是为了让触屏设备点图片不会留下一个"粘住"的放大状态。
         */
        @media (hover: hover) {
            .npd-card-image:hover img{transform:scale(1.035);}
        }
        /* 键盘可达性：焦点落在卡片内的链接上时也给同样反馈 */
        .npd-product-card:focus-within .npd-card-image img{transform:scale(1.035);}
        .npd-card-info{align-self:center;}
        .npd-card-kicker{
            color:#344069;
            font-size:11px;
            font-weight:700;
            letter-spacing:.13em;
            text-transform:uppercase;
            margin-bottom:12px;
        }
        .npd-card-info h3{
            font-size:clamp(28px,3vw,42px);
            line-height:1.05;
            letter-spacing:-.035em;
            margin-bottom:16px;
        }
        .npd-card-info p{
            color:#666;
            line-height:1.75;
            max-width:620px;
            margin-bottom:20px;
        }
        .npd-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;}
        .npd-tag{
            border:1px solid #d7d7d7;
            padding:7px 10px;
            font-size:11px;
            letter-spacing:.05em;
            color:#555;
            text-transform:uppercase;
        }
        .npd-detail-link{
            display:inline-flex;
            color:#1a1a1a;
            text-decoration:none;
            font-size:12px;
            font-weight:700;
            letter-spacing:.1em;
            border-bottom:1px solid #1a1a1a;
            padding-bottom:7px;
        }
        .npd-empty{
            display:none;
            padding:70px 0;
            color:#777;
            font-size:18px;
        }
        .npd-empty.is-visible{display:block;}

        /* 「加载更多」按钮（商品多于一页时出现） */
        .npd-load-more{
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:12px;
            padding:44px 0 8px;
        }
        .npd-load-more-btn{
            min-height:52px;
            padding:0 40px;
            border:0;
            background:#171717;
            color:#fff;
            font:inherit;
            font-size:12px;
            font-weight:700;
            letter-spacing:.12em;
            cursor:pointer;
            transition:background .2s ease;
        }
        .npd-load-more-btn:hover:not(:disabled){background:#344069;}
        .npd-load-more-btn:disabled{opacity:.55;cursor:default;}
        .npd-load-more-hint{
            margin:0;
            color:#888;
            font-size:12px;
            letter-spacing:.04em;
        }
        /* 商品目录的响应式规则统一放在本文件末尾的 @media 里，这里不再重复一遍。
           原来这里还有一份与 css/pages/products.css 完全重复的移动端规则，
           两份定义取值不一致（gap 38px vs 0、sidebar 两列 vs 单列），已合并到末尾那份。 */

        /* 这里原来还复制了一整份静态站的页面样式（.npd-page-hero / .npd-anchorbar /
           .npd-section / .npd-product / .npd-main-image / .npd-primary / .npd-cta 等）。
           但 Vue 的 scoped 样式只会作用到组件自己渲染的元素（带 data-v 属性），
           这些类对应的标记其实由 PHP 模板输出，scoped 规则根本匹配不到 —— 属于死代码，
           而且会与 css/common.css、css/pages/*.css 里的同名规则互相干扰。现已全部删除：
             .npd-cta       -> 统一到 css/common.css
             .npd-page-hero -> 页面用的是通用 .page-hero（css/common.css）
             .npd-pd-*      -> css/pages/product-single.css
         */

        /* =========================================================================
           商品目录的移动端布局（≤900px）
           桌面端是「280px 侧栏 + 结果」两列。窄屏下 280px + 60px 间距放不进
           390px 视口，会把页面撑宽；整页横向溢出后被 header 的 overflow-x:hidden
           裁掉，页头右侧的汉堡按钮就"看不见"了（但它的点击区域还在）。
           所以这里必须改成单列。
           ========================================================================= */
        @media (max-width: 900px) {
            .npd-catalog-layout {
                grid-template-columns: minmax(0, 1fr);
                gap: 0;
                width: min(calc(100% - 40px), 1440px);
                padding: 40px 0 64px;
            }
            .npd-sidebar {
                position: static;
                top: auto;
                margin-bottom: 26px;
            }

            /* 折叠开关：标题变成整行可点，右侧带箭头 */
            .npd-filter-group {
                padding: 0;
                border-top: 1px solid #e0e0e0;
            }
            .npd-filter-group:last-child { border-bottom: 1px solid #e0e0e0; }
            .npd-filter-title {
                margin: 0;
                padding: 16px 2px;
                font-size: 13px;
                letter-spacing: .1em;
                cursor: pointer;
            }
            .npd-filter-caret {
                display: block;
                flex: 0 0 auto;
                width: 9px;
                height: 9px;
                margin-right: 5px;
                border-right: 1.5px solid #1a1a1a;
                border-bottom: 1.5px solid #1a1a1a;
                transform: rotate(45deg) translate(-2px, -2px);
                transition: transform .2s ease;
            }
            .npd-filter-group.is-open .npd-filter-caret {
                transform: rotate(-135deg) translate(-2px, -2px);
            }
            /* 移动端默认收起，只有 .is-open 的那组显示选项 */
            .npd-filter-options { display: none; }
            .npd-filter-group.is-open .npd-filter-options {
                display: grid;
                padding-bottom: 14px;
            }
            .npd-filter-btn { padding: 9px 0; font-size: 14px; }

            /* 结果区同样改单列 */
            .npd-results-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .npd-results-bar h2 { font-size: clamp(28px, 7vw, 40px); }
            .npd-product-card {
                grid-template-columns: minmax(0, 1fr);
                gap: 22px;
                padding: 30px 0;
            }
            .npd-card-info h3 { font-size: clamp(24px, 6vw, 32px); }
        }
</style>