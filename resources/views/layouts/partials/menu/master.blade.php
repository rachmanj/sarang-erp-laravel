<!-- Master Data -->
<li class="nav-item {{ request()->routeIs('admin.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-database"></i>
        <p>
            Master Data
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <!-- Projects -->
        <li class="nav-item">
            <a href="{{ route('admin.projects.index') }}"
                class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Projects</p>
            </a>
        </li>

        <!-- Departments -->
        <li class="nav-item">
            <a href="{{ route('admin.departments.index') }}"
                class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Departments</p>
            </a>
        </li>

        <!-- Additional Document Types -->
        <li class="nav-item">
            <a href="{{ route('admin.additional-document-types.index') }}"
                class="nav-link {{ request()->routeIs('admin.additional-document-types.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Document Types</p>
            </a>
        </li>

        <!-- Invoice Types -->
        <li class="nav-item">
            <a href="{{ route('admin.invoice-types.index') }}"
                class="nav-link {{ request()->routeIs('admin.invoice-types.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Invoice Types</p>
            </a>
        </li>

        <!-- Suppliers -->
        <li class="nav-item">
            <a href="{{ route('admin.suppliers.index') }}"
                class="nav-link {{ request()->routeIs('admin.suppliers.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Suppliers</p>
            </a>
        </li>

        <!-- Document Status Management -->
        @can('reset-document-status')
            <li class="nav-item">
                <a href="{{ route('admin.document-status.index') }}"
                    class="nav-link {{ request()->routeIs('admin.document-status.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Document Status</p>
                </a>
            </li>
        @endcan
    </ul>
</li>
