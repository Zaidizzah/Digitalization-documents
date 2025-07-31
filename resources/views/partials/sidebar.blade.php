@php
  $user = auth()->user();
@endphp

<!-- Sidebar menu-->
<div class="app-sidebar__overlay" data-toggle="sidebar" aria-label="Close Sidebar"></div>
<aside class="app-sidebar" role="navigation" aria-label="Sidebar Section of Navigation">
  <div class="app-sidebar__user" aria-label="User Information"><img class="app-sidebar__user-avatar" src="https://placehold.co/600x600/{{ substr(fake()->hexColor(), 1, 6) }}/FFF?text={{ substr($user->name, 0, 1) }}" loading="lazy" title="User Image" alt="User Image">
    <div>
      <p class="app-sidebar__user-name" title="{{ $user->name }}">{{ $user->name }}</p>
      <p class="app-sidebar__user-designation" title="{{ $user->role === "Admin" ? "Administrator" : "User" }}">{{ $user->role === "Admin" ? "Administrator" : "User" }}</p>
    </div>
  </div>

  <form action="{{ route('search.index') }}" method="get" role="search" id="app-sidebar__search" class="app-sidebar__search" aria-label="Search Wrapper/Container">
    <label for="app-sidebar__search_input" class="form-label">Search</label>
    <input type="search" name="search" class="form-control" id="app-sidebar__search_input" value="{{ request('search') ?? '' }}" placeholder="Search" aria-required="false" />
    <button class="app-sidebar__search_button"><i class="bi bi-search"></i></button>
  </form>

  <ul class="app-menu" role="menu">
    <li role="menuitem"><a class="app-menu__item {{ set_active('dashboard*') }}" href="{{ route('dashboard.index') }}" title="Dashboard"><i class="app-menu__icon bi bi-speedometer"></i><span class="app-menu__label">Dashboard</span></a></li>
    @can('role-access', 'Admin')
      <li role="menuitem"><a class="app-menu__item {{ set_active('users*') }}" href="{{ route('users.index') }}" title="Manage Users"><i class="app-menu__icon bi bi-people"></i><span class="app-menu__label">Manage Users</span></a></li>
    @endcan
    <li role="menuitem"><a class="app-menu__item {{ set_active('documents*') }}" href="{{ route('documents.index') }}" title="Manage Documents"><i class="app-menu__icon bi bi-files"></i><span class="app-menu__label">Manage Documents</span></a></li>
    <li role="menuitem"><a class="app-menu__item {{ set_active('userguide*') }}" href="{{ route('userguide.index') }}" title="User Guide or application documentary"><i class="app-menu__icon bi bi-code-square"></i><span class="app-menu__label">User Guide</span></a></li>
  </ul>
</aside>