# Sales Dashboard Review and Recommendations

## Current State Analysis

### Existing Structure
1. **Purchase Dashboard**: Fully implemented with `PurchaseDashboardController` and `PurchaseDashboardDataService`
   - AP aging analysis
   - Purchase order statistics
   - Purchase invoice statistics
   - Goods receipt statistics
   - Supplier statistics
   - Recent invoices

2. **Sales Dashboard**: **IMPLEMENTED** ✅
   - `SalesDashboardController`: Created
   - `SalesDashboardDataService`: Created with AR aging calculations
   - `resources/views/sales/dashboard.blade.php`: Created
   - Route added in `routes/web/orders.php`
   - Sidebar link updated in `resources/views/layouts/partials/sidebar.blade.php`

3. **Sales Data Available**:
   - `sales_invoices` table with `due_date`, `closure_status`, `status`, `total_amount`
   - `sales_receipt_allocations` table for payment tracking
   - `sales_orders` table
   - `delivery_orders` table
   - `sales_receipts` table

### Database Structure
- **Sales Invoices**: 18 total, 18 posted, 9 open
- **AR Allocation**: `sales_receipt_allocations` links receipts to invoices
- **Closure Status**: Tracks open/closed invoices
- **Due Date**: Available for aging calculations

## Recommended Improvements

### 1. Create Sales Dashboard Controller and Service
**Priority**: High
**Pattern**: Follow `PurchaseDashboardController` and `PurchaseDashboardDataService`

**Components**:
- `SalesDashboardController`: Handle dashboard requests
- `SalesDashboardDataService`: Provide data with caching (300s TTL)
- `resources/views/sales/dashboard.blade.php`: Dashboard view

### 2. AR Invoice Aging Analysis
**Priority**: High
**Pattern**: Similar to AP aging in Purchase Dashboard

**Requirements**:
- Calculate outstanding AR from `sales_invoices` - `sales_receipt_allocations`
- Age invoices by `due_date` or `date + 30 days` if `due_date` is null
- Buckets: Current (not due), 1-30 days, 31-60 days, 61-90 days, 90+ days
- Display aging summary with visual progress bar
- Show top 20 overdue invoices sorted by days past due

**Calculation Logic**:
```php
$outstanding = $invoice->total_amount - SUM(sales_receipt_allocations.amount)
$dueDate = $invoice->due_date ?? $invoice->date->addDays(30)
$daysPastDue = $dueDate->diffInDays(now(), false)
```

### 3. Sales KPIs
**Priority**: High

**Metrics**:
- Sales MTD (Month-to-Date): Sum of posted sales invoices for current month
- Outstanding AR: Total outstanding from open, posted invoices
- Pending Approvals: Count of pending sales order approvals
- Open Sales Orders: Count of open sales orders

### 4. Sales Statistics
**Priority**: Medium

**Components**:
- Sales Orders Overview: Total, draft, approved, closed, open counts and values
- Sales Invoices Overview: Total, draft, posted, open counts and amounts
- Delivery Orders Overview: Total, pending, completed counts
- Top Customers by Outstanding AR: Top 10 customers with highest outstanding AR
- Recent Sales Invoices: Latest 10 invoices with status and outstanding amounts

### 5. Dashboard Features
**Priority**: Medium

**UI Components**:
- KPI Cards: 4 small boxes showing key metrics
- AR Aging Card: Visual aging summary with progress bar
- Sales Orders Card: Status summary and open order value
- Sales Invoices Card: Status summary and outstanding amount
- Delivery Orders Card: Status summary
- Top Customers Table: Outstanding AR by customer
- Recent Invoices Table: Latest invoices with links

### 6. Integration Points
**Priority**: Medium

**Routes**:
- `/sales/dashboard`: Sales dashboard route
- Update sidebar to link to dashboard
- Add refresh parameter support

**Permissions**:
- Use existing `ar.invoices.view` permission
- Consider `sales.dashboard.view` permission

### 7. Performance Optimization
**Priority**: Low

**Caching**:
- Cache dashboard data for 300 seconds (5 minutes)
- Support refresh parameter to bypass cache
- Use Laravel Cache facade

**Query Optimization**:
- Use database aggregations for aging calculations
- Limit detailed aging to top 20 invoices
- Use indexes on `sales_invoices.closure_status`, `sales_invoices.status`, `sales_invoices.due_date`

## Implementation Plan

### Phase 1: Core Dashboard (High Priority)
1. Create `SalesDashboardDataService` with AR aging
2. Create `SalesDashboardController`
3. Create `resources/views/sales/dashboard.blade.php`
4. Add route in `routes/web/orders.php`
5. Update sidebar link

### Phase 2: Enhanced Features (Medium Priority)
1. Add sales order statistics
2. Add delivery order statistics
3. Add top customers table
4. Add recent invoices table

### Phase 3: Optimization (Low Priority)
1. Add caching
2. Optimize queries
3. Add export functionality
4. Add date range filters

## Code Structure

### Service Layer
```php
app/Services/SalesDashboardDataService.php
- getSalesDashboardData(bool $refresh = false): array
- buildSalesKpis(): array
- buildArAging(): array
- calculateOutstandingAr(): float
- buildSalesOrderStats(): array
- buildSalesInvoiceStats(): array
- buildDeliveryOrderStats(): array
- buildCustomerStats(): array
- getRecentInvoices(): Collection
```

### Controller Layer
```php
app/Http/Controllers/SalesDashboardController.php
- index(Request $request): View
```

### View Layer
```php
resources/views/sales/dashboard.blade.php
- KPI cards
- AR aging visualization
- Sales statistics cards
- Top customers table
- Recent invoices table
```

## Benefits

1. **Comprehensive AR Management**: Visual AR aging helps identify overdue invoices
2. **Sales Performance Tracking**: MTD sales and order statistics
3. **Customer Insights**: Top customers by outstanding AR
4. **Operational Efficiency**: Quick access to sales metrics
5. **Consistency**: Matches Purchase Dashboard structure and UX

## Implementation Status

### ✅ Completed
1. **SalesDashboardDataService**: Created with AR aging calculations
   - AR aging buckets: Current, 1-30, 31-60, 61-90, 90+ days
   - Sales KPIs: Sales MTD, Outstanding AR, Pending Approvals, Open Sales Orders
   - Sales Order Statistics: Total, Draft, Approved, Closed, Open counts and values
   - Sales Invoice Statistics: Total, Draft, Posted, Open counts and amounts
   - Delivery Order Statistics: Total, Pending, Delivered, Completed counts
   - Customer Statistics: Top 10 customers by outstanding AR
   - Recent Invoices: Latest 10 invoices with status and outstanding amounts
   - Caching: 300 seconds TTL with refresh support

2. **SalesDashboardController**: Created
   - Index method with refresh parameter support
   - Auth middleware
   - Dependency injection for SalesDashboardDataService

3. **Sales Dashboard View**: Created
   - KPI Cards: 4 small boxes showing key metrics
   - AR Aging Card: Visual aging summary with progress bar
   - Sales Orders Card: Status summary and open order value
   - Sales Invoices Card: Status summary and outstanding amount
   - Delivery Orders Card: Status summary
   - Top Customers Table: Outstanding AR by customer
   - Recent Invoices Table: Latest invoices with links
   - AdminLTE styling consistent with Purchase Dashboard

4. **Routes**: Added
   - `/sales/dashboard`: Sales dashboard route
   - Sidebar link updated to point to dashboard

### Redesign Completed (2026-02-11)

1. ✅ Executive Summary: 5 KPI cards (Sales MTD, Sales YTD, Open Pipeline, Outstanding AR, Collections MTD)
2. ✅ Document-stage cards: SQ, SO, DO, SI, SR with status summaries and values
3. ✅ Filters: Customer, date range, aging bucket (parity with Purchase Dashboard)
4. ✅ AR aging: Overdue badge, High/Medium risk badges, Chart.js bar + pie
5. ✅ Sales Funnel mini-widget: SQ → SO → DO → SI → SR counts
6. ✅ Recent Invoices: Days Overdue column, overdue row highlighting

### Next Steps

1. ✅ Implement Sales Dashboard following Purchase Dashboard pattern
2. ✅ Add AR aging calculations
3. ✅ Create comprehensive dashboard view
4. ✅ Test with real data
5. Update documentation
6. Consider adding date range filters
7. Consider adding export functionality
8. Consider adding drill-down capabilities

