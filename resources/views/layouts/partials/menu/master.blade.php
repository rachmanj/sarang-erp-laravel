<!-- Master Data -->
<li
    class="nav-item {{ request()->routeIs('projects.*') || request()->routeIs('funds.*') || request()->routeIs('departments.*') || request()->routeIs('assets.*') || request()->routeIs('asset-categories.*') || request()->routeIs('assets.disposals.*') || request()->routeIs('assets.movements.*') || request()->routeIs('assets.import.*') || request()->routeIs('assets.data-quality.*') || request()->routeIs('assets.bulk-operations.*') ? 'menu-open' : '' }}">
    <a href="#"
        class="nav-link {{ request()->routeIs('projects.*') || request()->routeIs('funds.*') || request()->routeIs('departments.*') || request()->routeIs('assets.*') || request()->routeIs('asset-categories.*') || request()->routeIs('assets.disposals.*') || request()->routeIs('assets.movements.*') || request()->routeIs('assets.import.*') || request()->routeIs('assets.data-quality.*') || request()->routeIs('assets.bulk-operations.*') ? 'active' : '' }}">
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

        <!-- Fixed Assets -->
        @canany(['assets.view', 'asset_categories.view'])
            <li class="nav-header">Fixed Assets</li>
        @endcanany

        @can('asset_categories.view')
            <li class="nav-item">
                <a href="{{ route('asset-categories.index') }}"
                    class="nav-link {{ request()->routeIs('asset-categories.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Asset Categories</p>
                </a>
            </li>
        @endcan

        @can('assets.view')
            <li class="nav-item">
                <a href="{{ route('assets.index') }}"
                    class="nav-link {{ request()->routeIs('assets.*') && !request()->routeIs('assets.depreciation.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Assets</p>
                </a>
            </li>
        @endcan

        @can('assets.depreciation.run')
            <li class="nav-item">
                <a href="{{ route('assets.depreciation.index') }}"
                    class="nav-link {{ request()->routeIs('assets.depreciation.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Depreciation Runs</p>
                </a>
            </li>
        @endcan

        @can('assets.disposal.view')
            <li class="nav-item">
                <a href="{{ route('assets.disposals.index') }}"
                    class="nav-link {{ request()->routeIs('assets.disposals.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Asset Disposals</p>
                </a>
            </li>
        @endcan

        @can('assets.movement.view')
            <li class="nav-item">
                <a href="{{ route('assets.movements.index') }}"
                    class="nav-link {{ request()->routeIs('assets.movements.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Asset Movements</p>
                </a>
            </li>
        @endcan

        @can('assets.create')
            <li class="nav-item">
                <a href="{{ route('assets.import.index') }}"
                    class="nav-link {{ request()->routeIs('assets.import.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Asset Import</p>
                </a>
            </li>
        @endcan

        @can('assets.view')
            <li class="nav-item">
                <a href="{{ route('assets.data-quality.index') }}"
                    class="nav-link {{ request()->routeIs('assets.data-quality.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Data Quality</p>
                </a>
            </li>
        @endcan

        @can('assets.update')
            <li class="nav-item">
                <a href="{{ route('assets.bulk-operations.index') }}"
                    class="nav-link {{ request()->routeIs('assets.bulk-operations.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Bulk Operations</p>
                </a>
            </li>
        @endcan

    </ul>
</li>
