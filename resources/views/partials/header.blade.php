<!-- Header-->
<header class="app-header" aria-label="Header Section of Navigation"><a class="app-header__logo" href="{{ route('dashboard.index') }}" aria-label="Manage Documents" title="Manage Documents">Manage Documents</a>
  <!-- Sidebar toggle button-->
  <a class="app-sidebar__toggle" href="javascript:void(0)" data-toggle="sidebar" aria-label="Toggle Sidebar" title="Toggle Sidebar"></a>
  <!-- Header Right Menu-->
  <ul class="app-nav">
    <!-- User Menu-->
    <li class="dropdown"><a class="app-nav__item" href="javascript:void(0)" data-bs-toggle="dropdown" aria-label="Open Profile Menu"><i class="bi bi-person fs-4"></i></a>
      <ul class="dropdown-menu settings-menu dropdown-menu-right">
        <li><a class="dropdown-item" href="javascript:void(0)" title="Settings"><i class="bi bi-gear me-2 fs-5"></i> Settings</a></li>
        <li><a class="dropdown-item" href="{{ route('users.profile') }}" title="Profile"><i class="bi bi-person me-2 fs-5"></i> Profile</a></li>
        <li><a class="dropdown-item" href="{{ route('signout') }}" onclick="return confirm('Are you sure you want to logout?')" title="Logout"><i class="bi bi-box-arrow-right me-2 fs-5"></i> Logout</a></li>
      </ul>
    </li>
  </ul>
</header>