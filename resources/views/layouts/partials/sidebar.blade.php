<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/dashboard" class="brand-link">
        <img src="{{ asset('adminlte/dist/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
            class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light"><b>DDS</b> - Laravel</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Divider -->
                <li class="nav-header">MAIN</li>

                <!-- Journals -->
                @can('journals.view')
                    <li class="nav-item">
                        <a href="{{ route('journals.index') }}"
                            class="nav-link {{ request()->routeIs('journals.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>Journals</p>
                        </a>
                    </li>
                @endcan
                <!-- Reports Section -->
                @can('view-settings')
                    @include('layouts.partials.menu.reports')
                @endcan

                @can('view-master')
                    @include('layouts.partials.menu.master')
                @endcan

                @can('view-admin')
                    @include('layouts.partials.menu.admin')
                @endcan


            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
