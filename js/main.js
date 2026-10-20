(() => {
  const header = document.getElementById('siteHeader');
  if (header) {
    const updateHeader = () => {
      const scrolled = window.scrollY > 30;
      header.classList.toggle('scrolled', scrolled);
    };
    updateHeader();
    window.addEventListener('scroll', () => {
      window.requestAnimationFrame(updateHeader);
    }, { passive: true });
  }

  const toggle = document.querySelector('.mobile-nav-toggle');
  const nav = document.querySelector('.main-nav');
  if (toggle && nav) {
    const closeMenu = () => {
      nav.classList.remove('is-open', 'open');
      nav.querySelectorAll('.nav-dropdown').forEach((item) => item.classList.remove('submenu-open'));
      toggle.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Open navigation menu');
    };

    const isMobile = () => window.innerWidth <= 768;

    /*
     * 移动端一级导航：第一次点开二级菜单，再点一次才真正跳转。
     *
     * 以前这里是无条件 preventDefault()，导致一级页面（/products/、/about/ 等）
     * 在手机上永远进不去 —— 只能展开子菜单，无法跳转。
     * 现在改成两段式：
     *   未展开 → 展开，并阻止跳转；
     *   已展开 → 放行，正常跳到一级页面。
     * 桌面上二级菜单靠 :hover 展开，点击一律直接跳转，不受影响。
     */
    nav.querySelectorAll('.nav-dropdown > a').forEach((link) => {
      link.addEventListener('click', (event) => {
        if (!isMobile()) return;

        const parent = link.parentElement;
        const submenu = parent.querySelector('.nav-dropdown-menu');

        // 没有子项的项直接跳转
        if (!submenu || !submenu.children.length) {
          return;
        }

        // 已经展开过了：放行，让它跳到一级页面
        if (parent.classList.contains('submenu-open')) {
          return;
        }

        // 第一次点击：展开自己，收起别的，先不跳转
        event.preventDefault();
        nav.querySelectorAll('.nav-dropdown').forEach((item) => {
          if (item !== parent) item.classList.remove('submenu-open');
        });
        parent.classList.add('submenu-open');
      });
    });

    toggle.addEventListener('click', (event) => {
      event.stopPropagation();
      const isOpen = !nav.classList.contains('is-open');
      nav.classList.toggle('is-open', isOpen);
      nav.classList.toggle('open', isOpen);
      toggle.classList.toggle('is-open', isOpen);
      toggle.setAttribute('aria-expanded', String(isOpen));
      toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
      if (!isOpen) {
        nav.querySelectorAll('.nav-dropdown').forEach((item) => item.classList.remove('submenu-open'));
      }
    });

    document.addEventListener('click', (event) => {
      if (!nav.contains(event.target) && !toggle.contains(event.target) && nav.classList.contains('is-open')) {
        closeMenu();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) {
        closeMenu();
      }
    });
  }

  document.querySelectorAll('[data-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-tab]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      const content = JSON.parse(button.dataset.tab);
      document.querySelector('[data-tab-number]').textContent = content.number;
      document.querySelector('[data-tab-title]').textContent = content.title;
      document.querySelector('[data-tab-description]').textContent = content.description;
    });
  });
  /*
   * 表单。
   *
   * 以前这里是 preventDefault() + 显示一句感谢语，数据完全没有提交出去，
   * 客户询价等于被丢弃。现在表单由 inc/tk-inquiry.php 真实处理（提交到 admin-post.php），
   * 这里只负责提交时禁用按钮、防止重复提交。
   */
  document.querySelectorAll('.form, .contact-form').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;
      button.dataset.tkLabel = button.textContent;
      button.textContent = 'SENDING…';
      button.disabled = true;
    });
  });

  // 用浏览器「后退」回到页面时（bfcache），把按钮恢复成可点状态。
  window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelectorAll('button[data-tk-label]').forEach((button) => {
      button.disabled = false;
      button.textContent = button.dataset.tkLabel;
    });
  });
})();
(function () {
            const solutionData = {
                warehouse:     ['01 / 05', 'Warehouse & Logistics', 'Pallet trucks, stackers and lift tables support efficient pallet movement, lifting and positioning throughout warehouse and logistics operations.'],
                manufacturing: ['02 / 05', 'Manufacturing', 'Lift tables, pallet trucks and handling equipment support material movement, positioning and lifting in manufacturing environments.'],
                assembly:      ['03 / 05', 'Assembly Lines', 'Ergonomic lift tables and handling equipment improve efficiency and safety on assembly lines and production areas.'],
                automotive:    ['04 / 05', 'Automotive', 'Pallet trucks, stackers and lift tables handle components and assemblies in automotive and parts production.'],
                maintenance:   ['05 / 05', 'Maintenance & Workshops', 'Lift tables and handling equipment support maintenance, repair and workshop material handling tasks.']
            };

            const tabs = document.querySelectorAll('.solution-tab');
            const number = document.getElementById('solutionNumber');
            const title = document.getElementById('solutionTitle');
            const description = document.getElementById('solutionDescription');

            // 这段只在首页生效，其它页面这些元素不存在，先做空值判断。
            if (!tabs.length || !number || !title || !description) return;

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const content = solutionData[tab.dataset.solution];
                    if (!content) return;
                    tabs.forEach((item) => item.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    number.textContent = content[0];
                    title.textContent = content[1];
                    description.textContent = content[2];
                });
            });
        })();
