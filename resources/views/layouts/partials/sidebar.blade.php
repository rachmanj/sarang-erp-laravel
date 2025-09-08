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

                <!-- Accounting Group -->
                @can('journals.view')
                    @php
                        $acctActive = request()->routeIs('journals.*');
                    @endphp
                    <li class="nav-item {{ $acctActive ? 'menu-is-opening menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $acctActive ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calculator"></i>
                            <p>
                                Accounting
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('journals.index') }}"
                                    class="nav-link {{ request()->routeIs('journals.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Journals</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan

                <!-- Sales Group -->
                @canany(['ar.invoices.view', 'ar.receipts.view'])
                    @php
                        $salesActive = request()->routeIs('sales-invoices.*') || request()->routeIs('sales-receipts.*');
                    @endphp
                    <li class="nav-item {{ $salesActive ? 'menu-is-opening menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $salesActive ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>
                                Sales
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('ar.invoices.view')
                                <li class="nav-item">
                                    <a href="{{ route('sales-invoices.index') }}"
                                        class="nav-link {{ request()->routeIs('sales-invoices.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Sales Invoices</p>
                                    </a>
                                </li>
                            @endcan
                            @can('ar.receipts.view')
                                <li class="nav-item">
                                    <a href="{{ route('sales-receipts.index') }}"
                                        class="nav-link {{ request()->routeIs('sales-receipts.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Sales Receipts</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                <!-- Purchase Group -->
                @canany(['ap.invoices.view', 'ap.payments.view'])
                    @php
                        $purchaseActive =
                            request()->routeIs('purchase-invoices.*') || request()->routeIs('purchase-payments.*');
                    @endphp
                    <li class="nav-item {{ $purchaseActive ? 'menu-is-opening menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $purchaseActive ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-bag"></i>
                            <p>
                                Purchase
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('ap.invoices.view')
                                <li class="nav-item">
                                    <a href="{{ route('purchase-invoices.index') }}"
                                        class="nav-link {{ request()->routeIs('purchase-invoices.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Purchase Invoices</p>
                                    </a>
                                </li>
                            @endcan
                            @can('ap.payments.view')
                                <li class="nav-item">
                                    <a href="{{ route('purchase-payments.index') }}"
                                        class="nav-link {{ request()->routeIs('purchase-payments.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Purchase Payments</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
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
