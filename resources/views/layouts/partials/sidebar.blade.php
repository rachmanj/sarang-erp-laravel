<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/dashboard" class="brand-link">
        <img src="{{ asset('adminlte/dist/img/AdminLTELogo.png') }}" alt="AdminLTE Logo"
            class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light"><b>Sarange-ERP</b></span>
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

                <!-- Approval Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('approvals.dashboard') }}"
                        class="nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-check"></i>
                        <p>Approval Dashboard</p>
                    </a>
                </li>

                <!-- 1. Inventory Group -->
                @canany(['inventory.view', 'inventory.create', 'inventory.update', 'warehouse.view', 'gr-gi.view'])
                    @php
                        $inventoryActive =
                            request()->routeIs('inventory.*') ||
                            request()->routeIs('warehouses.*') ||
                            request()->routeIs('gr-gi.*');
                    @endphp
                    <li class="nav-item {{ $inventoryActive ? 'menu-is-opening menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $inventoryActive ? 'active' : '' }}">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>
                                Inventory
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('inventory.view')
                                <li class="nav-item">
                                    <a href="{{ route('inventory.dashboard') }}"
                                        class="nav-link {{ request()->routeIs('inventory.dashboard') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>
                            @endcan
                            @can('inventory.view')
                                <li class="nav-item">
                                    <a href="{{ route('inventory.index') }}"
                                        class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Inventory Items</p>
                                    </a>
                                </li>
                            @endcan
                            @can('inventory.create')
                                <li class="nav-item">
                                    <a href="{{ route('inventory.create') }}"
                                        class="nav-link {{ request()->routeIs('inventory.create') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Add Item</p>
                                    </a>
                                </li>
                            @endcan
                            @can('inventory.view')
                                <li class="nav-item">
                                    <a href="{{ route('inventory.low-stock') }}"
                                        class="nav-link {{ request()->routeIs('inventory.low-stock') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Low Stock Report</p>
                                    </a>
                                </li>
                            @endcan
                            @can('inventory.view')
                                <li class="nav-item">
                                    <a href="{{ route('inventory.valuation-report') }}"
                                        class="nav-link {{ request()->routeIs('inventory.valuation-report') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Valuation Report</p>
                                    </a>
                                </li>
                            @endcan
                            @can('inventory.view')
                                <li class="nav-item">
                                    <a href="{{ route('inventory.detail-report') }}"
                                        class="nav-link {{ request()->routeIs('inventory.detail-report') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Detail Report</p>
                                    </a>
                                </li>
                            @endcan
                            @can('warehouse.view')
                                <li class="nav-item">
                                    <a href="{{ route('warehouses.index') }}"
                                        class="nav-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Warehouses</p>
                                    </a>
                                </li>
                            @endcan
                            @can('gr-gi.view')
                                <li class="nav-item">
                                    <a href="{{ route('gr-gi.index') }}"
                                        class="nav-link {{ request()->routeIs('gr-gi.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>GR/GI Management</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                <!-- 2. Purchase Group -->
                @canany(['ap.invoices.view', 'ap.payments.view'])
                    @php
                        $purchaseActive =
                            request()->routeIs('purchase.dashboard') ||
                            request()->routeIs('purchase-invoices.*') ||
                            request()->routeIs('purchase-payments.*') ||
                            request()->routeIs('purchase-orders.*') ||
                            request()->routeIs('goods-receipt-pos.*');
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
                            <li class="nav-item">
                                <a href="{{ route('purchase.dashboard') }}" class="nav-link {{ request()->routeIs('purchase.dashboard') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('purchase-orders.index') }}"
                                    class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Purchase Orders</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('goods-receipt-pos.index') }}"
                                    class="nav-link {{ request()->routeIs('goods-receipt-pos.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Goods Receipt PO</p>
                                </a>
                            </li>
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

                <!-- 3. Sales Group -->
                @canany(['ar.invoices.view', 'ar.receipts.view', 'ar.quotations.view'])
                    @php
                        $salesActive =
                            request()->routeIs('sales-invoices.*') ||
                            request()->routeIs('sales-receipts.*') ||
                            request()->routeIs('sales-orders.*') ||
                            request()->routeIs('sales-quotations.*') ||
                            request()->routeIs('delivery-orders.*');
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
                            <li class="nav-item">
                                <a href="{{ route('sales.dashboard') }}"
                                    class="nav-link {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Dashboard</p>
                                </a>
                            </li>
                            @can('ar.quotations.view')
                                <li class="nav-item">
                                    <a href="{{ route('sales-quotations.index') }}"
                                        class="nav-link {{ request()->routeIs('sales-quotations.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Sales Quotations</p>
                                    </a>
                                </li>
                            @endcan
                            <li class="nav-item">
                                <a href="{{ route('sales-orders.index') }}"
                                    class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Sales Orders</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('delivery-orders.index') }}"
                                    class="nav-link {{ request()->routeIs('delivery-orders.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Delivery Orders</p>
                                </a>
                            </li>
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

                <!-- 4. Fixed Assets Group -->
                @canany(['assets.view', 'asset_categories.view', 'assets.depreciation.run', 'assets.disposal.view',
                    'assets.movement.view'])
                    @php
                        $assetsActive = request()->routeIs('assets.*') || request()->routeIs('asset-categories.*');
                    @endphp
                    <li class="nav-item {{ $assetsActive ? 'menu-is-opening menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $assetsActive ? 'active' : '' }}">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                Fixed Assets
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
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
                @endcanany

                <!-- 5. Business Partner Group -->
                <li
                    class="nav-item {{ request()->routeIs('business_partners.*') ? 'menu-is-opening menu-open' : '' }}">
                    <a href="#"
                        class="nav-link {{ request()->routeIs('business_partners.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>
                            Business Partner
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('business_partners.index') }}"
                                class="nav-link {{ request()->routeIs('business_partners.*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Business Partners</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- 6. Accounting Group -->
                @canany(['journals.view', 'accounts.view', 'account_statements.view', 'currencies.view'])
                    @php
                        $acctActive =
                            request()->routeIs('journals.*') ||
                            request()->routeIs('accounts.*') ||
                            request()->routeIs('periods.*') ||
                            request()->routeIs('cash-expenses.*') ||
                            request()->routeIs('account-statements.*') ||
                            request()->routeIs('control-accounts.*') ||
                            request()->routeIs('currencies.*') ||
                            request()->routeIs('exchange-rates.*') ||
                            request()->routeIs('currency-revaluations.*');
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
                            <li class="nav-item">
                                <a href="{{ route('cash-expenses.index') }}"
                                    class="nav-link {{ request()->routeIs('cash-expenses.*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Cash Expenses</p>
                                </a>
                            </li>
                            @can('accounts.view')
                                <li class="nav-item">
                                    <a href="{{ route('accounts.index') }}"
                                        class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Accounts</p>
                                    </a>
                                </li>
                            @endcan
                            @can('account_statements.view')
                                <li class="nav-item">
                                    <a href="{{ route('account-statements.index') }}"
                                        class="nav-link {{ request()->routeIs('account-statements.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Account Statements</p>
                                    </a>
                                </li>
                            @endcan
                            @can('accounts.view')
                                <li class="nav-item">
                                    <a href="{{ route('control-accounts.index') }}"
                                        class="nav-link {{ request()->routeIs('control-accounts.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Control Accounts</p>
                                    </a>
                                </li>
                            @endcan
                            @can('periods.view')
                                <li class="nav-item">
                                    <a href="{{ route('periods.index') }}"
                                        class="nav-link {{ request()->routeIs('periods.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Periods</p>
                                    </a>
                                </li>
                            @endcan
                            @can('currencies.view')
                                <li class="nav-item">
                                    <a href="{{ route('currencies.index') }}"
                                        class="nav-link {{ request()->routeIs('currencies.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Currencies</p>
                                    </a>
                                </li>
                            @endcan
                            @can('exchange-rates.view')
                                <li class="nav-item">
                                    <a href="{{ route('exchange-rates.index') }}"
                                        class="nav-link {{ request()->routeIs('exchange-rates.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Exchange Rates</p>
                                    </a>
                                </li>
                            @endcan
                            @can('currency-revaluations.view')
                                <li class="nav-item">
                                    <a href="{{ route('currency-revaluations.index') }}"
                                        class="nav-link {{ request()->routeIs('currency-revaluations.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Currency Revaluations</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                <!-- 7. Master Data Group -->
                @canany(['projects.view', 'departments.view', 'inventory.view', 'manage-company-info', 'view_unit_of_measure'])
                    @php
                        $masterDataActive =
                            request()->routeIs('projects.*') ||
                            request()->routeIs('departments.*') ||
                            request()->routeIs('product-categories.*') ||
                            request()->routeIs('company-info.*') ||
                            request()->routeIs('unit-of-measures.*');
                    @endphp
                    <li class="nav-item {{ $masterDataActive ? 'menu-is-opening menu-open' : '' }}">
                        <a href="#" class="nav-link {{ $masterDataActive ? 'active' : '' }}">
                            <i class="nav-icon fas fa-database"></i>
                            <p>
                                Master Data
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('manage-company-info')
                                <li class="nav-item">
                                    <a href="{{ route('company-info.index') }}"
                                        class="nav-link {{ request()->routeIs('company-info.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Company Information</p>
                                    </a>
                                </li>
                            @endcan
                            @can('inventory.view')
                                <li class="nav-item">
                                    <a href="{{ route('product-categories.index') }}"
                                        class="nav-link {{ request()->routeIs('product-categories.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Product Categories</p>
                                    </a>
                                </li>
                            @endcan
                            @can('view_unit_of_measure')
                                <li class="nav-item">
                                    <a href="{{ route('unit-of-measures.index') }}"
                                        class="nav-link {{ request()->routeIs('unit-of-measures.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Units of Measure</p>
                                    </a>
                                </li>
                            @endcan
                            @can('projects.view')
                                <li class="nav-item">
                                    <a href="{{ route('projects.index') }}"
                                        class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Projects</p>
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
                @endcanany

                <!-- Reports Section -->
                @include('layouts.partials.menu.reports')

                @can('view-admin')
                    @include('layouts.partials.menu.admin')
                @endcan


            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
