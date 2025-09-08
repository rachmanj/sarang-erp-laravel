<!-- Divider -->
<li class="nav-header">ADMIN</li>

<!-- Users -->
<li class="nav-item">
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-users"></i>
        <p>Users</p>
    </a>
</li>

<!-- Roles & Permissions -->
<li
    class="nav-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'menu-open' : '' }}">
    <a href="#"
        class="nav-link {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-user-shield"></i>
        <p>
            Roles & Permissions
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.roles.index') }}"
                class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Roles</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.permissions.index') }}"
                class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Permissions</p>
            </a>
        </li>
    </ul>
</li>
