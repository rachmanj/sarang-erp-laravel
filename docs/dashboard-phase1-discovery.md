# Dashboard Phase 1 Implementation Kickoff

**Date**: 2025-11-11  
**Scope**: Executive dashboard redesign – Phase 1 (metric inventory and backend payload design)

---

## 1. Metric Inventory (Phase 1 Output)

| Widget / KPI Group          | Metric / Insight                            | Primary Data Source(s)                                                    | Existing Logic References / Notes                                                      |
| --------------------------- | ------------------------------------------- | ------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| Top KPI Strip               | Total Sales (month-to-date)                 | `sales_invoices` (sum `total_amount`, filter by `date`)                   | `SalesInvoiceController@index` filters; reuse `SalesInvoice` scope for posted invoices |
|                             | Total Purchases (month-to-date)             | `purchase_invoices`                                                       | `PurchaseInvoiceController@index`; mirror sales aggregation                            |
|                             | Cash on Hand                                | `journals`, `control_account_balances`, `account_statements`              | Control account balances already computed via `ControlAccountService`                  |
|                             | Pending Approvals                           | `approval_workflows`, `approval_workflow_steps`                           | `ApprovalDashboardController@index` provides pending counts                            |
| Finance Snapshot            | AR Aging (0–30/31–60/61–90/90+)             | `account_statements`, `sales_invoices`, `sales_receipts`                  | `AccountStatementService` composes partner balances                                    |
|                             | AP Aging                                    | `account_statements`, `purchase_invoices`, `purchase_payments`            | Similar to AR; reuse service with vendor context                                       |
|                             | Period Close Readiness                      | `periods` (status), `journals` (unposted), `manual_journal` counts        | `PeriodController` exposes close/open status                                           |
| Sales Pulse                 | Open / Approved Sales Orders                | `sales_orders`                                                            | `SalesOrderController` stats (status column)                                           |
|                             | Deliveries Pending                          | `delivery_orders` (status pending/approved)                               | `DeliveryOrderController`                                                              |
|                             | Credit Utilization (top overdue)            | `customer_credit_limits`, `sales_invoices`                                | `CustomerCreditLimit` model; join with invoices                                        |
| Procurement Pulse           | Open Purchase Orders by aging               | `purchase_orders`                                                         | `PurchaseOrderController` exposes statuses                                             |
|                             | Goods Receipts Awaiting Invoice             | `goods_receipt_po`, `purchase_invoices`                                   | Use document relationship map or join on PO numbers                                    |
|                             | Supplier Performance Score                  | `supplier_performance`                                                    | `SupplierAnalyticsController` already aggregates                                       |
| Inventory & Operations      | Inventory Valuation (by warehouse/category) | `inventory_valuations`, `inventory_warehouse_stock`, `product_categories` | `InventoryController@valuationReport`                                                  |
|                             | Low-Stock Items                             | `inventory_items`, `inventory_warehouse_stock`                            | `InventoryController@lowStock`                                                         |
|                             | GR/GI Queue                                 | `gr_gi_headers` (status pending)                                          | `GRGIController@index`                                                                 |
| Fixed Assets Snapshot       | Asset Count / Value / Book Value            | `assets` (`acquisition_cost`, `current_book_value`)                       | `AssetController` + `AssetReportsController`                                           |
|                             | Depreciation Runs Pending                   | `asset_depreciation_runs` (status)                                        | `AssetDepreciationController`                                                          |
| Compliance & Tax            | Upcoming Tax Deadlines                      | `tax_calendar`, `tax_periods`                                             | `TaxController@calendar`                                                               |
|                             | Recent Audit Log Alerts                     | `audit_logs`                                                              | `AuditLogController@index` (filter severity)                                           |
| ERP Configuration Reminders | Overdue Document Closures / Parameters      | `open items` via `document_closure` fields, `erp_parameters`              | `OpenItemsController`, `ErpParameterController`                                        |

---

## 2. Existing Logic & Reuse Opportunities

-   **Aggregator Services**: `ControlAccountService`, `AccountStatementService`, `SupplierAnalyticsService`, `InventoryController` helpers already compute many aggregates; wrap or extend without duplicating SQL.
-   **Document Relationships**: `DocumentRelationshipService` enables PO↔GRPO↔PI linkage to show pending conversions (e.g., GRPO without invoice).
-   **Permissions**: Spatie `@can` checks already configured across modules—reuse to hide widgets.
-   **Caching Hooks**: `DocumentRelationshipCacheService` demonstrates Redis-based caching pattern; can mirror for dashboard payload.

---

## 3. Aggregated Payload Architecture

### Proposed Endpoint

-   Route: `GET /dashboard/data` (AJAX) or server-side injection via `DashboardController@index`.
-   Response envelope:

```json
{
    "meta": {
        "generated_at": "2025-11-11T08:00:00Z",
        "cache_ttl_seconds": 300
    },
    "kpis": {
        "sales_mtd": 0,
        "purchases_mtd": 0,
        "cash_on_hand": 0,
        "approvals_pending": 0
    },
    "finance": {
        "ar_aging": { "0_30": 0, "31_60": 0, "61_90": 0, "90_plus": 0 },
        "ap_aging": { "0_30": 0, "31_60": 0, "61_90": 0, "90_plus": 0 },
        "period_close": { "open_periods": [], "unposted_journals": 0 }
    },
    "sales_procurement": {
        "sales_orders": { "draft": 0, "approved": 0 },
        "delivery_backlog": 0,
        "purchase_orders": { "issued": 0, "overdue": 0 },
        "supplier_scores": [{ "name": "PT A", "score": 0 }]
    },
    "inventory": {
        "valuation": { "total": 0, "by_category": [], "by_warehouse": [] },
        "low_stock": [],
        "gr_gi_pending": 0
    },
    "assets": {
        "counts": { "total_assets": 0 },
        "values": { "acquisition": 0, "book": 0 },
        "depreciation_pending": 0
    },
    "compliance": {
        "tax_deadlines": [],
        "audit_alerts": []
    },
    "configuration": {
        "overdue_closures": [],
        "parameter_warnings": []
    }
}
```

### Caching Strategy

-   Wrap expensive aggregates (valuation, aging) in cache with 5–15 minute TTL.
-   Provide `force_refresh` query flag for admins (e.g., `?refresh=1`).
-   Invalidate cache on relevant model events (e.g., posting journal, creating invoice) where practical; otherwise rely on TTL.

---

## 4. Immediate Phase 1 Tasks

| Task                                                                            | Output                        | Owner                | Target     |
| ------------------------------------------------------------------------------- | ----------------------------- | -------------------- | ---------- |
| Implement `DashboardDataService` collecting metrics listed above                | Service class with unit tests | Backend engineer     | 2025-11-18 |
| Wire `DashboardController@index` to inject payload into Blade                   | Controller + route update     | Backend engineer     | 2025-11-18 |
| Add feature tests covering permissions & empty-state handling                   | Pest/PHPUnit coverage         | QA/dev               | 2025-11-19 |
| Update `dashboard.blade.php` to consume structured payload (no inline DB calls) | Refactored Blade template     | Frontend Laravel dev | 2025-11-20 |

---

## 5. Risks & Mitigations

-   **Empty sandbox data**: metrics will read zero until seed data is added—show friendly empty states; plan seeding in Phase 2.
-   **Performance spikes**: ensure aggregated queries use indices and caching, mirroring existing services.
-   **Permission leakage**: test each widget with restricted roles; hide sections when user lacks `@can` privileges.

---

## 6. Phase Progression

1. **Phase 1 (current)**: finalize metric inventory, build `DashboardDataService`, inject data into view.
2. **Phase 2**: enhance frontend widgets (charts, cards), add sample data seeds, refine UX.
3. **Phase 3**: full QA, documentation updates (`docs/architecture.md`, `docs/decisions.md`), production cutover.
