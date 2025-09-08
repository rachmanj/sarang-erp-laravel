<!-- Master Data -->
<li
    class="nav-item {{ request()->routeIs('projects.*') || request()->routeIs('funds.*') || request()->routeIs('departments.*') ? 'menu-open' : '' }}">
    <a href="#"
        class="nav-link {{ request()->routeIs('projects.*') || request()->routeIs('funds.*') || request()->routeIs('departments.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-database"></i>
        <p>
            Master Data
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('projects.view')
            <li class="nav-item">
                <a href="{{ route('projects.index') }}"
                    class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Projects</p>
                </a>
            </li>
        @endcan

        @can('funds.view')
            <li class="nav-item">
                <a href="{{ route('funds.index') }}" class="nav-link {{ request()->routeIs('funds.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Funds</p>
                </a>
            </li>
        @endcan

        @can('departments.view')
            <li class="nav-item">
                <a href="{{ route('departments.index') }}"
                    class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Departments</p>
                </a>
            </li>
        @endcan


    </ul>
</li>
