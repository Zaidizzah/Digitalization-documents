<style>
    aside.app-userguide-sidebar {
        position: fixed;
        top: 52px;
        right: 301px;
        width: 300px;
        height: calc(100vh - 52.71px);
        overflow: auto;
        background-color: #00897b;
        margin-left: -8px;
        padding: 20px 0;
        border-left: 2px solid var(--primary-color);
        overflow-y: auto; 
        transform: translateX(100%);
        transition: width 0.3s ease, right 0.5 cubic-bezier(0.175, 0.885, 0.32, 1.275), transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    aside.app-userguide-sidebar.active {
        right: 0;
        transform: translateX(0);
    }

    aside.app-userguide-sidebar::before, aside.app-userguide-sidebar::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 30px;
        background: linear-gradient(to bottom, var(--primary-color), transparent); 
    }

    aside.app-userguide-sidebar::after {
        top: unset;
        bottom: 0;
        background: linear-gradient(to top, var(--primary-color), transparent); 
    }

    ul.sidebar-menu {
      list-style: none;
      padding: 0;
    }

    ul.sidebar-menu li.menu-item {
      border-bottom: 1px solid #f0f0f0;
    }

    ul.sidebar-menu li.menu-item :where(a, div).menu-link {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      color: #ffffff;
      text-decoration: none;
      cursor: pointer;
      transition: color 0.3s ease, background-color 0.3s ease;
      user-select: none;
    }

    ul.sidebar-menu li.menu-item :where(a, div).menu-link:hover {
      background-color: #33a39a;
    }

    ul.sidebar-menu li.menu-item :where(a, div).menu-link.active {
      background-color: #f8f9fa;
      color: var(--primary-color);
      box-shadow: inset 0 0 10px 0 rgba(0,0,0,0.42);
    }

    ul.sidebar-menu li.menu-item :where(a, div).menu-link .menu-text {
      flex: 1;
      font-size: 14px;
      font-weight: 500;
    }

    ul.sidebar-menu li.menu-item :where(a, div).menu-link .arrow {
        width: 24px;
        text-align: center;
      font-size: 12px;
      transition: transform 0.3s ease;
    }

    .arrow.rotate {
      transform: rotate(180deg);
    }

    .submenu {
      list-style: none;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      background: #fafafa;
    }

    .submenu.show {
      max-height: 1000px;
    }

    .submenu-item {
      position: relative;
    }

    .submenu-link {
      display: block;
      padding: 12px 20px 12px 52px;
      color: #666;
      text-decoration: none;
      font-size: 13px;
      transition: all 0.3s ease;
    }

    .submenu-link.has-child {
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
    }

    .submenu-link.has-child .arrow {
      font-size: 10px;
    }

    .submenu-link:hover {
      background: #f0f3ff;
      color: #667eea;
      padding-left: 56px;
    }

    .submenu-link.active {
      color: #667eea;
      font-weight: 600;
      background: #f0f3ff;
    }

    /* Level 2 Submenu */
    .submenu-level-2 {
      background: #f5f5f5;
    }

    .submenu-level-2-link {
      padding-left: 68px;
      font-size: 12px;
    }

    .submenu-level-2-link:hover {
      padding-left: 72px;
    }

    /* Level 3 Submenu */
    .submenu-level-3 {
      background: #f0f0f0;
    }

    .submenu-level-3-link {
      padding-left: 84px;
      font-size: 12px;
      color: #777;
    }

    .submenu-level-3-link:hover {
      padding-left: 88px;
    }

    .content {
      flex: 1;
      padding: 30px;
      margin-left: 280px;
      transition: margin-left 0.3s ease;
    }

    .mobile-toggle {
      display: none;
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      z-index: 999;
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
        padding: 20px;
      }

      .mobile-toggle {
        display: block;
      }

      .menu-toggle {
        display: block;
      }

      .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
      }

      .overlay.show {
        display: block;
      }
    }

    .badge {
      background: #667eea;
      color: white;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
    }
</style>

<aside class="app-userguide-sidebar active" role="navigation" aria-label="Sidebar Section of Navigation for User Guide">
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a class="menu-link active" href="#intro">
              <span class="menu-text">Pendahuluan</span>
            </a>
        </li>

        <li class="menu-item">
            <div class="menu-link">
              <span class="menu-text">Memulai</span>
              <span class="arrow">▼</span>
            </div>
            <ul class="submenu">
              <li><a class="submenu-link" href="#install">Instalasi</a></li>
              <li><a class="submenu-link" href="#setup">Konfigurasi Awal</a></li>
              <li><a class="submenu-link" href="#first-steps">Langkah Pertama</a></li>
            </ul>
        </li>

        <li class="menu-item">
            <div class="menu-link">
              <span class="menu-text">Fitur Utama</span>
              <span class="arrow">▼</span>
            </div>
            <ul class="submenu">
              <li><a class="submenu-link" href="#dashboard">Dashboard</a></li>
              <li><a class="submenu-link" href="#users">Manajemen User</a></li>
              <li><a class="submenu-link" href="#reports">Laporan</a></li>
              <li><a class="submenu-link" href="#settings">Pengaturan</a></li>
            </ul>
        </li>

        <li class="menu-item">
            <div class="menu-link">
              <span class="menu-text">Kustomisasi</span>
              <span class="badge">New</span>
              <span class="arrow">▼</span>
            </div>
            <ul class="submenu">
              <li><a class="submenu-link" href="#themes">Tema</a></li>
              <li><a class="submenu-link" href="#layouts">Layout</a></li>
              <li><a class="submenu-link" href="#widgets">Widget</a></li>
            </ul>
        </li>

        <li class="menu-item">
            <div class="menu-link">
              <span class="menu-text">Advanced</span>
              <span class="arrow">▼</span>
            </div>
            <ul class="submenu">
              <li><a class="submenu-link" href="#api">API Integration</a></li>
              <li class="submenu-item">
                  <div class="submenu-link has-child">
                    <span>Plugin</span>
                    <span class="arrow">▼</span>
                  </div>
                  <ul class="submenu submenu-level-2">
                    <li><a class="submenu-link submenu-level-2-link" href="#plugin-install">Instalasi Plugin</a></li>
                    <li><a class="submenu-link submenu-level-2-link" href="#plugin-config">Konfigurasi</a></li>
                    <li class="submenu-item">
                        <div class="submenu-link submenu-level-2-link has-child">
                        <span>Plugin Populer</span>
                          <span class="arrow">▼</span>
                        </div>
                        <ul class="submenu submenu-level-3">
                          <li><a class="submenu-link submenu-level-3-link" href="#plugin-auth">Authentication</a></li>
                          <li><a class="submenu-link submenu-level-3-link" href="#plugin-payment">Payment Gateway</a></li>
                          <li><a class="submenu-link submenu-level-3-link" href="#plugin-email">Email Service</a></li>
                        </ul>
                    </li>zaid.baain|
                  </ul>
              </li>
              <li><a class="submenu-link" href="#security">Keamanan</a></li>
            </ul>
        </li>

        <li class="menu-item">
            <a class="menu-link" href="#faq">
            <span class="menu-text">FAQ</span>
            </a>
        </li>

        <li class="menu-item">
            <a class="menu-link" href="#support">
            <span class="menu-text">Support</span>
            </a>
        </li>
    </ul>
</aside>

<script>
  (() => {
    "use strict";

    const sidebar = document.querySelector('aside.app-userguide-sidebar');
    const toggleButton = document.querySelector('closeBtn');

    function toggleSubmenu(element) {
      event.stopPropagation(); // Prevent event bubbling
      
      const submenu = element.nextElementSibling;
      const arrow = element.querySelector('.arrow');
      
      const isCurrentlyOpen = submenu.classList.contains('show');
      
      // Tutup submenu yang satu level dengan element ini (sibling)
      const parent = element.parentElement.parentElement;
      const siblings = parent.querySelectorAll(':scope > .menu-item > .submenu, :scope > .submenu-item > .submenu');
      
      siblings.forEach(sub => {
        if (sub !== submenu && !submenu.contains(sub)) {
          sub.classList.remove('show');
          const siblingArrow = sub.previousElementSibling.querySelector('.arrow');
          if (siblingArrow) siblingArrow.classList.remove('rotate');
        }
      });
      
      // Toggle submenu saat ini
      if (isCurrentlyOpen) {
        submenu.classList.remove('show');
        arrow.classList.remove('rotate');
        // Tutup semua child submenu
        submenu.querySelectorAll('.submenu').forEach(child => {
          child.classList.remove('show');
          const childArrow = child.previousElementSibling.querySelector('.arrow');
          if (childArrow) childArrow.classList.remove('rotate');
        });
      } else {
        submenu.classList.add('show');
        arrow.classList.add('rotate');
      }
    }

    // mobileToggle.addEventListener('click', () => {
    //   sidebar.classList.add('open');
    //   overlay.classList.add('show');
    //   mobileToggle.style.display = 'none';
    // });

    // closeBtn.addEventListener('click', closeSidebar);
    // overlay.addEventListener('click', closeSidebar);

    function closeSidebar() {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
      mobileToggle.style.display = 'block';
    }

    // Active state untuk submenu (hanya untuk link, bukan yang punya child)
    document.querySelectorAll('.submenu-link:not(.has-child)').forEach(link => {
      link.addEventListener('click', function(e) {
        document.querySelectorAll('.submenu-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      });
    });

    // Active state untuk menu utama
    document.querySelectorAll('.menu-link[href]').forEach(link => {
      link.addEventListener('click', function(e) {
        document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        if (window.innerWidth <= 768) {
          closeSidebar();
        }
      });
    });
  });
</script>
