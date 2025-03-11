<!-- Sidebar menu-->
<div class="app-sidebar__overlay" data-toggle="sidebar" aria-label="Close Sidebar"></div>
<aside class="app-sidebar" aria-label="Sidebar Section of Navigation">
  <div class="app-sidebar__user" aria-label="User Information"><img class="app-sidebar__user-avatar" src="https://placehold.co/500x600/{{ substr(fake()->hexColor(), 1, 6) }}/FFF?text={{ substr(auth()->user()->name, 0, 1) }}" loading="lazy" title="User Image" alt="User Image">
    <div>
      <p class="app-sidebar__user-name" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</p>
      <p class="app-sidebar__user-designation" title="{{ auth()->user()->role === "Admin" ? "Administrator" : "User" }}">{{ auth()->user()->role === "Admin" ? "Administrator" : "User" }}</p>
    </div>
  </div>
  <ul class="app-menu">
    <li><a class="app-menu__item {{ set_active('dashboard*') }}" href="{{ route('dashboard.index') }}" aria-label="Dashboard" title="Dashboard"><i class="app-menu__icon bi bi-speedometer"></i><span class="app-menu__label">Dashboard</span></a></li>
    @can('role-access', 'Admin')
      <li><a class="app-menu__item {{ set_active('users*') }}" href="{{ route('users.index') }}" aria-label="Manage Users" title="Manage Users"><i class="app-menu__icon bi bi-people"></i><span class="app-menu__label">Manage Users</span></a></li>
    @endcan
    <li><a class="app-menu__item {{ set_active('documents*') }}" href="{{ route('documents.index') }}" aria-label="Manage Documents" title="Manage Documents"><i class="app-menu__icon bi bi-files"></i><span class="app-menu__label">Manage Documents</span></a></li>
    <li><a class="app-menu__item {{ set_active('docs*') }}" href="docs.html"><i class="app-menu__icon bi bi-code-square" aria-label="Docs" title="Docs"></i><span class="app-menu__label">Docs</span></a></li>
  </ul>
</aside>