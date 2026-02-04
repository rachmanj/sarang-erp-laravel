<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class MenuSearchService
{
    /**
     * Get all searchable menu items for the current user
     * 
     * @return array
     */
    public function getSearchableMenuItems(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $items = [];

        // Dashboard
        $items[] = $this->buildMenuItem(
            'Dashboard',
            route('dashboard'),
            'fas fa-tachometer-alt',
            'Dashboard',
            'Dashboard'
        );

        // Approval Dashboard - accessible to all authenticated users
        $items[] = $this->buildMenuItem(
            'Approval Dashboard',
            route('approvals.dashboard'),
            'fas fa-clipboard-check',
            'MAIN',
            'MAIN > Approval Dashboard',
            ['approval', 'approvals', 'dashboard']
        );

        // Inventory Group
        if ($user->hasAnyPermission(['inventory.view', 'inventory.create', 'inventory.update', 'warehouse.view', 'gr-gi.view'])) {
            if ($user->can('inventory.view')) {
                $items[] = $this->buildMenuItem(
                    'Inventory Items',
                    route('inventory.index'),
                    'fas fa-boxes',
                    'Inventory',
                    'MAIN > Inventory > Inventory Items',
                    ['inventory', 'items', 'stock', 'products']
                );
            }
            if ($user->can('inventory.create')) {
                $items[] = $this->buildMenuItem(
                    'Add Item',
                    route('inventory.create'),
                    'fas fa-boxes',
                    'Inventory',
                    'MAIN > Inventory > Add Item',
                    ['inventory', 'add', 'create', 'new item']
                );
            }
            if ($user->can('inventory.view')) {
                $items[] = $this->buildMenuItem(
                    'Low Stock Report',
                    route('inventory.low-stock'),
                    'fas fa-boxes',
                    'Inventory',
                    'MAIN > Inventory > Low Stock Report',
                    ['inventory', 'low stock', 'report', 'alert']
                );
                $items[] = $this->buildMenuItem(
                    'Valuation Report',
                    route('inventory.valuation-report'),
                    'fas fa-boxes',
                    'Inventory',
                    'MAIN > Inventory > Valuation Report',
                    ['inventory', 'valuation', 'report', 'cost']
                );
            }
            if ($user->can('warehouse.view')) {
                $items[] = $this->buildMenuItem(
                    'Warehouses',
                    route('warehouses.index'),
                    'fas fa-boxes',
                    'Inventory',
                    'MAIN > Inventory > Warehouses',
                    ['warehouse', 'warehouses', 'storage']
                );
            }
            if ($user->can('gr-gi.view')) {
                $items[] = $this->buildMenuItem(
                    'GR/GI Management',
                    route('gr-gi.index'),
                    'fas fa-boxes',
                    'Inventory',
                    'MAIN > Inventory > GR/GI Management',
                    ['gr', 'gi', 'goods receipt', 'goods issue', 'transfer']
                );
            }
        }

        // Purchase Group
        if ($user->hasAnyPermission(['ap.invoices.view', 'ap.payments.view'])) {
            $items[] = $this->buildMenuItem(
                'Purchase Dashboard',
                route('purchase.dashboard'),
                'fas fa-shopping-bag',
                'Purchase',
                'MAIN > Purchase > Dashboard',
                ['purchase', 'dashboard', 'buying']
            );
            $items[] = $this->buildMenuItem(
                'Purchase Orders',
                route('purchase-orders.index'),
                'fas fa-shopping-bag',
                'Purchase',
                'MAIN > Purchase > Purchase Orders',
                ['purchase', 'orders', 'po', 'buying']
            );
            $items[] = $this->buildMenuItem(
                'Goods Receipt PO',
                route('goods-receipt-pos.index'),
                'fas fa-shopping-bag',
                'Purchase',
                'MAIN > Purchase > Goods Receipt PO',
                ['goods receipt', 'grpo', 'receiving', 'purchase']
            );
            if ($user->can('ap.invoices.view')) {
                $items[] = $this->buildMenuItem(
                    'Purchase Invoices',
                    route('purchase-invoices.index'),
                    'fas fa-shopping-bag',
                    'Purchase',
                    'MAIN > Purchase > Purchase Invoices',
                    ['purchase', 'invoices', 'ap', 'accounts payable']
                );
            }
            if ($user->can('ap.payments.view')) {
                $items[] = $this->buildMenuItem(
                    'Purchase Payments',
                    route('purchase-payments.index'),
                    'fas fa-shopping-bag',
                    'Purchase',
                    'MAIN > Purchase > Purchase Payments',
                    ['purchase', 'payments', 'ap', 'accounts payable']
                );
            }
        }

        // Sales Group
        if ($user->hasAnyPermission(['ar.invoices.view', 'ar.receipts.view', 'ar.quotations.view'])) {
            $items[] = $this->buildMenuItem(
                'Sales Dashboard',
                route('sales.dashboard'),
                'fas fa-shopping-cart',
                'Sales',
                'MAIN > Sales > Dashboard',
                ['sales', 'dashboard', 'selling']
            );
            if ($user->can('ar.quotations.view')) {
                $items[] = $this->buildMenuItem(
                    'Sales Quotations',
                    route('sales-quotations.index'),
                    'fas fa-shopping-cart',
                    'Sales',
                    'MAIN > Sales > Sales Quotations',
                    ['sales', 'quotations', 'quotes', 'proposals']
                );
            }
            $items[] = $this->buildMenuItem(
                'Sales Orders',
                route('sales-orders.index'),
                'fas fa-shopping-cart',
                'Sales',
                'MAIN > Sales > Sales Orders',
                ['sales', 'orders', 'so', 'selling']
            );
            $items[] = $this->buildMenuItem(
                'Delivery Orders',
                route('delivery-orders.index'),
                'fas fa-shopping-cart',
                'Sales',
                'MAIN > Sales > Delivery Orders',
                ['delivery', 'orders', 'do', 'shipping']
            );
            if ($user->can('ar.invoices.view')) {
                $items[] = $this->buildMenuItem(
                    'Sales Invoices',
                    route('sales-invoices.index'),
                    'fas fa-shopping-cart',
                    'Sales',
                    'MAIN > Sales > Sales Invoices',
                    ['sales', 'invoices', 'ar', 'accounts receivable']
                );
            }
            if ($user->can('ar.receipts.view')) {
                $items[] = $this->buildMenuItem(
                    'Sales Receipts',
                    route('sales-receipts.index'),
                    'fas fa-shopping-cart',
                    'Sales',
                    'MAIN > Sales > Sales Receipts',
                    ['sales', 'receipts', 'ar', 'accounts receivable', 'payment']
                );
            }
        }

        // Fixed Assets Group
        if ($user->hasAnyPermission(['assets.view', 'asset_categories.view', 'assets.depreciation.run', 'assets.disposal.view', 'assets.movement.view'])) {
            if ($user->can('asset_categories.view')) {
                $items[] = $this->buildMenuItem(
                    'Asset Categories',
                    route('asset-categories.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Asset Categories',
                    ['assets', 'categories', 'fixed assets']
                );
            }
            if ($user->can('assets.view')) {
                $items[] = $this->buildMenuItem(
                    'Assets',
                    route('assets.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Assets',
                    ['assets', 'fixed assets', 'property']
                );
            }
            if ($user->can('assets.depreciation.run')) {
                $items[] = $this->buildMenuItem(
                    'Depreciation Runs',
                    route('assets.depreciation.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Depreciation Runs',
                    ['assets', 'depreciation', 'amortization']
                );
            }
            if ($user->can('assets.disposal.view')) {
                $items[] = $this->buildMenuItem(
                    'Asset Disposals',
                    route('assets.disposals.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Asset Disposals',
                    ['assets', 'disposals', 'dispose']
                );
            }
            if ($user->can('assets.movement.view')) {
                $items[] = $this->buildMenuItem(
                    'Asset Movements',
                    route('assets.movements.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Asset Movements',
                    ['assets', 'movements', 'transfer']
                );
            }
            if ($user->can('assets.create')) {
                $items[] = $this->buildMenuItem(
                    'Asset Import',
                    route('assets.import.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Asset Import',
                    ['assets', 'import', 'upload']
                );
            }
            if ($user->can('assets.view')) {
                $items[] = $this->buildMenuItem(
                    'Data Quality',
                    route('assets.data-quality.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Data Quality',
                    ['assets', 'data quality', 'validation']
                );
            }
            if ($user->can('assets.update')) {
                $items[] = $this->buildMenuItem(
                    'Bulk Operations',
                    route('assets.bulk-operations.index'),
                    'fas fa-building',
                    'Fixed Assets',
                    'MAIN > Fixed Assets > Bulk Operations',
                    ['assets', 'bulk', 'operations']
                );
            }
        }

        // Business Partner
        $items[] = $this->buildMenuItem(
            'Business Partners',
            route('business_partners.index'),
            'fas fa-handshake',
            'Business Partner',
            'MAIN > Business Partner > Business Partners',
            ['business partners', 'customers', 'suppliers', 'vendors', 'clients']
        );

        // Accounting Group
        if ($user->hasAnyPermission(['journals.view', 'accounts.view', 'account_statements.view', 'currencies.view'])) {
            $items[] = $this->buildMenuItem(
                'Journals',
                route('journals.index'),
                'fas fa-calculator',
                'Accounting',
                'MAIN > Accounting > Journals',
                ['journals', 'journal entries', 'accounting']
            );
            $items[] = $this->buildMenuItem(
                'Cash Expenses',
                route('cash-expenses.index'),
                'fas fa-calculator',
                'Accounting',
                'MAIN > Accounting > Cash Expenses',
                ['cash', 'expenses', 'petty cash']
            );
            if ($user->can('accounts.view')) {
                $items[] = $this->buildMenuItem(
                    'Accounts',
                    route('accounts.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Accounts',
                    ['accounts', 'chart of accounts', 'coa']
                );
                $items[] = $this->buildMenuItem(
                    'Control Accounts',
                    route('control-accounts.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Control Accounts',
                    ['control accounts', 'subsidiary']
                );
            }
            if ($user->can('account_statements.view')) {
                $items[] = $this->buildMenuItem(
                    'Account Statements',
                    route('account-statements.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Account Statements',
                    ['account statements', 'statements', 'reports']
                );
            }
            if ($user->can('periods.view')) {
                $items[] = $this->buildMenuItem(
                    'Periods',
                    route('periods.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Periods',
                    ['periods', 'fiscal periods', 'accounting periods']
                );
            }
            if ($user->can('currencies.view')) {
                $items[] = $this->buildMenuItem(
                    'Currencies',
                    route('currencies.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Currencies',
                    ['currencies', 'currency', 'exchange']
                );
            }
            if ($user->can('exchange-rates.view')) {
                $items[] = $this->buildMenuItem(
                    'Exchange Rates',
                    route('exchange-rates.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Exchange Rates',
                    ['exchange rates', 'forex', 'currency rates']
                );
            }
            if ($user->can('currency-revaluations.view')) {
                $items[] = $this->buildMenuItem(
                    'Currency Revaluations',
                    route('currency-revaluations.index'),
                    'fas fa-calculator',
                    'Accounting',
                    'MAIN > Accounting > Currency Revaluations',
                    ['currency revaluations', 'revaluation', 'forex adjustment']
                );
            }
        }

        // Master Data Group
        if ($user->hasAnyPermission(['projects.view', 'departments.view', 'inventory.view', 'manage-company-info', 'view_unit_of_measure'])) {
            if ($user->can('manage-company-info')) {
                $items[] = $this->buildMenuItem(
                    'Company Information',
                    route('company-info.index'),
                    'fas fa-database',
                    'Master Data',
                    'MAIN > Master Data > Company Information',
                    ['company', 'information', 'settings']
                );
            }
            if ($user->can('inventory.view')) {
                $items[] = $this->buildMenuItem(
                    'Product Categories',
                    route('product-categories.index'),
                    'fas fa-database',
                    'Master Data',
                    'MAIN > Master Data > Product Categories',
                    ['product categories', 'categories', 'inventory']
                );
            }
            if ($user->can('view_unit_of_measure')) {
                $items[] = $this->buildMenuItem(
                    'Units of Measure',
                    route('unit-of-measures.index'),
                    'fas fa-database',
                    'Master Data',
                    'MAIN > Master Data > Units of Measure',
                    ['units', 'uom', 'measure']
                );
            }
            if ($user->can('projects.view')) {
                $items[] = $this->buildMenuItem(
                    'Projects',
                    route('projects.index'),
                    'fas fa-database',
                    'Master Data',
                    'MAIN > Master Data > Projects',
                    ['projects', 'project management']
                );
            }
            if ($user->can('departments.view')) {
                $items[] = $this->buildMenuItem(
                    'Departments',
                    route('departments.index'),
                    'fas fa-database',
                    'Master Data',
                    'MAIN > Master Data > Departments',
                    ['departments', 'department']
                );
            }
        }

        // Reports
        if ($user->can('reports.view')) {
            $items[] = $this->buildMenuItem(
                'Trial Balance',
                route('reports.trial-balance'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > Trial Balance',
                ['trial balance', 'reports', 'financial']
            );
            $items[] = $this->buildMenuItem(
                'GL Detail',
                route('reports.gl-detail'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > GL Detail',
                ['gl detail', 'general ledger', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'AR Aging',
                route('reports.ar-aging'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > AR Aging',
                ['ar aging', 'accounts receivable', 'aging', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'AP Aging',
                route('reports.ap-aging'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > AP Aging',
                ['ap aging', 'accounts payable', 'aging', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'Cash Ledger',
                route('reports.cash-ledger'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > Cash Ledger',
                ['cash ledger', 'cash', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'AR Party Balances',
                route('reports.ar-balances'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > AR Party Balances',
                ['ar balances', 'accounts receivable', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'AP Party Balances',
                route('reports.ap-balances'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > AP Party Balances',
                ['ap balances', 'accounts payable', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'Downloads',
                route('downloads.index'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > Downloads',
                ['downloads', 'files', 'reports']
            );
            $items[] = $this->buildMenuItem(
                'Withholding Recap',
                route('reports.withholding-recap'),
                'fas fa-chart-bar',
                'Reports',
                'REPORTS > Withholding Recap',
                ['withholding', 'tax', 'reports']
            );
            if ($user->can('reports.open-items')) {
                $items[] = $this->buildMenuItem(
                    'Open Items',
                    route('reports.open-items.index'),
                    'fas fa-chart-bar',
                    'Reports',
                    'REPORTS > Open Items',
                    ['open items', 'reports']
                );
            }
            if ($user->hasAnyPermission(['assets.view', 'assets.disposal.view', 'assets.movement.view'])) {
                $items[] = $this->buildMenuItem(
                    'Asset Reports',
                    route('reports.assets.index'),
                    'fas fa-chart-bar',
                    'Reports',
                    'REPORTS > ASSET REPORTS > Asset Reports',
                    ['asset reports', 'assets', 'reports']
                );
            }
        }

        // Admin
        if ($user->can('view-admin')) {
            $items[] = $this->buildMenuItem(
                'Users',
                route('admin.users.index'),
                'fas fa-users',
                'Admin',
                'ADMIN > Users',
                ['users', 'user management', 'admin']
            );
            $items[] = $this->buildMenuItem(
                'Roles',
                route('admin.roles.index'),
                'fas fa-user-shield',
                'Admin',
                'ADMIN > Roles & Permissions > Roles',
                ['roles', 'permissions', 'admin']
            );
            $items[] = $this->buildMenuItem(
                'Permissions',
                route('admin.permissions.index'),
                'fas fa-user-shield',
                'Admin',
                'ADMIN > Roles & Permissions > Permissions',
                ['permissions', 'roles', 'admin']
            );
            $items[] = $this->buildMenuItem(
                'Approval Workflows',
                route('admin.approval-workflows.index'),
                'fas fa-check-double',
                'Admin',
                'ADMIN > Approval Workflows',
                ['approval workflows', 'workflows', 'admin']
            );
            $items[] = $this->buildMenuItem(
                'ERP Parameters',
                route('erp-parameters.index'),
                'fas fa-cogs',
                'Admin',
                'ADMIN > ERP Parameters',
                ['erp parameters', 'settings', 'admin']
            );
            $items[] = $this->buildMenuItem(
                'Audit Logs',
                route('audit-logs.index'),
                'fas fa-clipboard-list',
                'Admin',
                'ADMIN > Audit Logs',
                ['audit logs', 'logs', 'admin']
            );
            $items[] = $this->buildMenuItem(
                'Activity Dashboard',
                route('activity-dashboard.index'),
                'fas fa-chart-line',
                'Admin',
                'ADMIN > Activity Dashboard',
                ['activity dashboard', 'activity', 'admin']
            );
        }

        return $items;
    }

    /**
     * Build a menu item array
     * 
     * @param string $title
     * @param string $route
     * @param string $icon
     * @param string $category
     * @param string $breadcrumb
     * @param array $keywords
     * @return array
     */
    private function buildMenuItem(string $title, string $route, string $icon, string $category, string $breadcrumb, array $keywords = []): array
    {
        $searchText = strtolower($title . ' ' . $breadcrumb . ' ' . implode(' ', $keywords));

        return [
            'title' => $title,
            'route' => $route,
            'icon' => $icon,
            'category' => $category,
            'breadcrumb' => $breadcrumb,
            'keywords' => $keywords,
            'searchText' => $searchText,
        ];
    }
}
