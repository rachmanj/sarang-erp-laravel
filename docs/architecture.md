Purpose: Technical reference for understanding system design and development patterns
Last Updated: 2026-06-25 (FIFO Layer Repair UI; legacy inventory/SR repair; HELP + Menu Search corpus sync)

## Architecture Documentation Guidelines

### Document Purpose

This document describes the CURRENT WORKING STATE of the application architecture. It serves as:

-   Technical reference for understanding how the system currently works
-   Onboarding guide for new developers
-   Design pattern documentation for consistent development
-   Schema and data flow documentation reflecting actual implementation

### What TO Include

-   **Current Technology Stack**: Technologies actually in use
-   **Working Components**: Components that are implemented and functional
-   **Actual Database Schema**: Tables, fields, and relationships as they exist
-   **Implemented Data Flows**: How data actually moves through the system
-   **Working API Endpoints**: Routes that are active and functional
-   **Deployment Patterns**: How the system is actually deployed
-   **Security Measures**: Security implementations that are active

### What NOT to Include

-   **Issues or Bugs**: These belong in `MEMORY.md` with technical debt entries
-   **Limitations or Problems**: Document what IS working, not what isn't
-   **Future Plans**: Enhancement ideas belong in `backlog.md`
-   **Deprecated Features**: Remove outdated information rather than marking as deprecated
-   **Wishlist Items**: Planned features that aren't implemented yet

### Update Guidelines

-   **Reflect Reality**: Always document the actual current state, not intended state
-   **Schema Notes**: When database schema has unused fields, note them factually
-   **Cross-Reference**: Link to other docs when appropriate, but don't duplicate content

### For AI Coding Agents

-   **Investigate Before Updating**: Use codebase search to verify current implementation
-   **Move Issues to Memory**: If you discover problems, document them in `MEMORY.md`
-   **Factual Documentation**: Describe what exists, not what should exist

---

# Sarange ERP System Architecture

## Project Overview

Sarange ERP is a comprehensive Enterprise Resource Planning system built with Laravel 12, designed for Indonesian businesses. It provides complete financial management, fixed asset management, procurement, sales, and reporting capabilities with role-based access control and multi-dimensional accounting.

## Current System Status

**Production Readiness**: 95% Complete ✅  
**Last Comprehensive Testing**: 2025-09-21 (manual E2E); 2026-03-31 Purchase Invoice inventory idempotency (`PurchaseInvoiceInventoryTransactionTest`); 2026-04-06 PI invoice date validation (`PurchaseInvoiceDateValidationTest`)  
**Testing Status**: End-to-end validation successful; PHPUnit uses dedicated MySQL database `sarang_erp_test` (see `phpunit.xml`) so `RefreshDatabase` does not wipe the development database

### Validated Functionality

-   ✅ **Inventory Management**: Complete CRUD operations, multi-category support, validation, low stock alerts, and valuation report pages rendering without backend errors (routes correctly prioritise static report URLs over item detail routes and low stock logic uses warehouse stock quantities)
-   ✅ **Purchase Workflow**: PO → GRPO → PI → PP complete workflow validated
-   ✅ **Sales Workflow**: SO → DO → SI → SR complete workflow validated
-   ✅ **Financial Integration**: Automatic document numbering, tax calculations, journal entries
-   ✅ **User Interface**: Professional AdminLTE integration, responsive design, form validation
-   ✅ **Data Management**: Business partner consolidation, field mapping resolution, data persistence
-   ✅ **Customer Invoice API**: Active customers (`BusinessPartner`, `partner_type = customer`) can list and read their **sales invoices** via **`GET /api/v1/invoices`** and **`GET /api/v1/invoices/{invoice_no}`** using Bearer tokens backed by **`customer_api_keys`** (hashed at rest). Issuance/revocation: **`/admin/customers/{businessPartner}/api-keys`** (`view-admin` + `business_partners.manage`). Maintainer reference: **`docs/customer-invoice-api-reference.md`**.

### Critical Issues Resolved

-   ✅ Field mapping issues (business_partner_id vs vendor_id/customer_id)
-   ✅ DocumentClosureService import issues and missing models
-   ✅ View template references (customers → business_partners)
-   ✅ Form submission failures and validation errors

## Technology Stack

-   **Backend**: Laravel 12 (PHP 8.2+)
-   **Frontend**: Blade templates with AdminLTE 3.14, jQuery, DataTables, SweetAlert2
-   **Database**: MySQL with comprehensive schema (150+ migrations)
-   **Authentication**: Laravel Auth with Spatie Permission package
-   **PDF Generation**: DomPDF for document printing
-   **Print Layout Selection**: Delivery Orders, Sales Invoices, and Purchase Orders support Standard (A4/Laser) and Dot Matrix print layouts via `?layout=dotmatrix`; dropdown on show pages lets users choose. Dot matrix layout: 9.5in width, Courier New, compact for 80-column printers. Sales Invoice has six layouts (standard, dotmatrix, pt_csj, cv_saranghae, pt_csj_dotmatrix, cv_saranghae_dotmatrix); PT CSJ dotmatrix uses "Authorized" signature only, right-aligned.
-   **Excel Export**: Laravel Excel (Maatwebsite)
-   **UI Framework**: AdminLTE 3 with Bootstrap 4
-   **Timezone**: Asia/Singapore (configured)

## Navigation Structure

The system uses a hierarchical sidebar navigation structure optimized for trading company operations:

### Main Navigation Sections

1. **Dashboard** - System overview and key metrics
2. **MAIN** - Core business operations (ordered by business process flow):
    - **Inventory** - Item management, stock levels, valuation reports, low stock alerts
    - **Purchase** - Dashboard, purchase orders, goods receipts, purchase invoices, purchase payments
    - **Sales** - Dashboard, sales orders, delivery orders, sales invoices, sales receipts
    - **Fixed Assets** - Asset categories, assets, depreciation, disposals, movements, import, data quality, bulk operations
    - **Business Partner** - Unified customer and supplier management with tabbed interface
    - **Accounting** - Journals, cash expenses, accounts, periods, **bank accounts**, **bank reconciliation (AI-assisted)**
    - **Master Data** - Projects, departments
3. **REPORTS** - Comprehensive reporting modules
4. **ADMIN** - User management, roles and permissions

### Navigation Features

-   **Role-Based Access**: Menu items are conditionally displayed based on user permissions
-   **Active State Management**: Current page highlighting and menu expansion
-   **Responsive Design**: Collapsible sidebar with mobile-friendly navigation
-   **Icon Integration**: FontAwesome icons for visual navigation cues
-   **Breadcrumb Navigation**: Page-level breadcrumb trails for deep navigation
-   **Menu Search Bar**: Global search functionality in navbar for quick menu item discovery and navigation with permission-aware filtering, keyboard navigation (Arrow keys, Enter, Escape), and real-time autocomplete results

## Core Components

### 1. Financial Management System

-   **Chart of Accounts**: Hierarchical account structure with 5 types (asset, liability, net_assets, income, expense). **Per-account ledger drill-down (2026-06-24)**: `accounts.show` (`/accounts/{account}`) lists posted journal lines for one account with opening/closing balance summary, date filter, and clickable source-document links (`JournalSourceUrlResolver`); data via `ReportService::getAccountLedger()` (reuses `JournalReportQueryBuilder`). COA index rows link to this page.
-   **Journal Management**: Manual journal entries with entity-aware numbering (code 12) and multi-currency support, entity resolution from source documents
-   **Period Management**: Financial period closing with validation
-   **Posting Service**: Centralized accounting posting with balance validation and foreign currency handling

### Dashboard Aggregation Layer

-   **DashboardDataService**: Consolidates KPI metrics, finance aging, sales/procurement stats, inventory insights, asset snapshots, compliance signals, and configuration alerts into a single cached payload (300s TTL) for the AdminLTE dashboard.
-   **DashboardController**: Injects the aggregated payload into `dashboard.blade.php`, supports `?refresh=1` to bypass cache, and keeps route logic slim.
-   **Blade Layout**: Rebuilt to consume the structured payload, rendering KPI info boxes, finance cards, inventory tables, and compliance alerts without inline database calls while respecting existing permission checks for fixed asset panels.
-   **Purchase Dashboard**: Comprehensive purchase analytics with `PurchaseDashboardDataService` providing AP aging analysis, purchase order statistics, purchase invoice statistics, goods receipt statistics, supplier statistics, and recent invoices with 300s TTL caching.
-   **Sales Dashboard**: Comprehensive sales analytics with `SalesDashboardDataService` providing AR aging analysis, sales order statistics, sales invoice statistics, delivery order statistics, customer statistics, and recent invoices with 300s TTL caching.
-   **Account Statements** (`account_statements` / `AST-…`): Saved GL or Business Partner period statements with **draft → finalized** workflow (`AccountStatement::finalize`), optional **cancel** in model; **create** validation uses `nullable` + `required_if` for `account_id` / `business_partner_id` so hidden empty selects do not fail `exists` checks. Distinct from the **Business Partner** screen **Account statement** tab (live GL view; see `BusinessPartnerAccountStatementService`). HELP: `docs/manuals/account-statements-module-manual-*.md`.
-   **Control Account System**: Enterprise-level control account architecture with automatic balance tracking, subsidiary ledger management, and reconciliation dashboard for financial control and compliance
-   **Entity-Aware Numbering System**: Centralized document numbering service with universal EEYYDDNNNNN format across all document types

### 1.1. Multi-Currency Management System

-   **Currency Master**: Support for multiple currencies with IDR as base currency, including USD, SGD, EUR, CNY, JPY, MYR, AUD, GBP, HKD
-   **Exchange Rate Management**: Daily exchange rate entry with automatic inverse rate calculation and historical rate tracking
-   **Foreign Currency Transactions**: All financial documents support foreign currency with automatic IDR conversion using exchange rates
-   **Dual Currency Reporting**: Financial reports display both foreign currency amounts and IDR equivalents
-   **FX Gain/Loss Tracking**: Automatic calculation and posting of realized and unrealized foreign exchange gains/losses
-   **Currency Revaluation**: Periodic revaluation of foreign currency balances with journal entry generation
-   **Multi-Currency Forms**: Purchase Orders, Sales Orders, and Journal Entries support currency selection with real-time exchange rate updates

### 2. Accounts Receivable (AR) Module

-   **Sales Dashboard**: Comprehensive sales analytics dashboard with AR aging analysis, sales KPIs (Sales MTD, Outstanding AR, Pending Approvals, Open Sales Orders), sales order statistics, sales invoice statistics, delivery order statistics, top customers by outstanding AR, and recent invoices visualization.
-   **Sales Invoices**: Customer billing with line items, tax codes, and dimensions (SINV-YYYYMM-######). **Direct Sale mode** (`is_direct_sale` on `sales_invoices`): bypasses SO/DO; create via `sales-invoices.create?direct=1` or Direct Sale toggle on SI create; lines require `inventory_item_id`; post runs **`DirectSalesPostingService`** + **`DirectSalesInvoiceJournalBuilder`** (Cr Revenue, Cr PPN, Dr COGS, Cr Inventory Available, Dr AR, Dr WTax prepaid) and **`InventoryService::processSaleTransaction`** (`reference_type=sales_invoice_line`). **Credit** leaves AR open for later Sales Receipt; **Cash** auto-creates + posts a Sales Receipt to the selected cash/bank account. Deletion restores stock via **`DirectSalesPostingService::reverseInventory`**. Line items include Part No. column (from part_number_id or delivery_order_line_id). **Item Code Resolution**: When creating SI from DO, `resolveLineDataFromDeliveryOrder()` in SalesInvoiceController ensures item_code, item_name, inventory_item_id, part_number_id are populated from the DO line when form data is missing—either by delivery_order_line_id lookup or by index-based match when delivery_order_line_id is null. Eager load `lines.partNumber`, `lines.deliveryOrderLine.partNumber` for show/print. **List index** (`sales_invoices/index.blade.php`): server-side DataTables includes **`sum_total_amount`** for the same filters as the grid (date, search, status, **company entity** radio); footer row **Totals (filtered)** (pattern aligned with Purchase Invoice list). **Export Excel**: `GET /sales-invoices/export` (`sales-invoices.export`, `ar.invoices.view`) uses the same filter query as the list (`buildSalesInvoiceListQuery` / `applySalesInvoiceListFilters`) via Maatwebsite Excel (`SalesInvoiceListExport`). **Discounts**: **Line** `discount_amount` / `discount_percentage` reduce **DPP** before VAT/WTax (same math as **Sales Order** lines via `SalesOrderLine::computeAmountFromPricing` / `SalesInvoicePostingMath`). **Header** discount is document-level (after Σ line amounts); header **`total_amount`** is **amount due** (net of header discount) for AR, allocations, and validation commands. **Line `amount`**: tax-inclusive gross per line (net DPP + PPN − WTax). **Display** (show/print): **Amount** column shows stored line `amount`; **Discount** column shows line DPP discount when &gt; 0; footer from **`SalesInvoicePostingMath::invoiceFooterTotals()`** includes optional **Gross total** / **Header discount** rows when applicable, then **Amount due**. **Posting** (`SalesInvoiceController::post`): normal path uses `SalesInvoiceJournalBuilder` + `SalesInvoicePostingMath::summarizeLinesForPosting`; regular path credits AR UnInvoice at **gross before WTax** (net + wtax), debits AR at net, debits PPh 23 prepaid when WTax &gt; 0; `TaxService::syncPostedSalesInvoice` runs inside the posting DB transaction. Opening-balance path differs. **`php artisan sales-invoices:validate-posted-journals`** (registered in `App\Console\Kernel`): compares journals to expected amounts vs **`total_amount`**. **Multi-GRPO**: `App\Services\SalesInvoiceService` targets **`GoodsReceiptPO`**; `SalesInvoiceGrpoCombination::goodsReceipt()` → `GoodsReceiptPO`. New SI **`store()`** resolves **`currency_id`** to IDR (or base) to satisfy FK.
-   **Sales Credit Memos**: AR credit notes tied 1:1 to a **posted** Sales Invoice (`sales_credit_memos.sales_invoice_id` unique). Posting reverses SI amounts via **`SalesInvoicePostingMath`** on the source invoice lines (PPN at rate/100, WTax prepaid credit when applicable). Routes under `/sales-credit-memos`; `PostingService` `source_type` `sales_credit_memo`. Permissions: `ar.credit-memos.view|create|post`.
-   **Sales Receipts**: Payment collection with invoice-first flow and explicit allocation (SR-YYYYMM-######). Select customer → load outstanding invoices via `getAvailableInvoices` (requires **`company_entity_id`**; filters `sales_invoices.company_entity_id`) → select invoices and allocation amounts → receipt lines auto-populated. Receipt total must match allocation total. **Store/update** reject allocations when invoice entity ≠ receipt company. Create/edit UI reloads invoices when **Company** changes. Mirrors Purchase Payment pattern. **Posting (current)**: `SalesReceiptJournalBuilder` + `CashJournalLineBuilder` debit the COA account from receipt lines (not hard-coded cash). **Legacy repair**: SRs posted before 2026-06-23 may have debited `1.1.1.01` Kas di Tangan regardless of selected bank — use `php artisan sales-receipts:repair-bank-journals` (`--dry-run`, `--receipt-no=`, `--id=`, `--force`).
-   **Sales Orders**: Customer order management with entity-aware numbering (code 06). **Discounts**: Header and line (percentage or amount); line discount reduces DPP before VAT/WTax; header discount on Σ line totals; manual discounts skip **`applyCustomerPricingTier`** BP auto-discount. **`total_amount`** = Σ line amounts; **`net_amount`** = total − header discount.
-   **AR Aging**: Customer payment tracking and aging analysis with buckets (Current, 1-30, 31-60, 61-90, 90+ days) calculated from sales invoices minus sales receipt allocations
-   **AR Balances**: Customer account balance reporting

### 3. Accounts Payable (AP) Module

-   **Purchase Dashboard**: Comprehensive purchase analytics dashboard with AP aging analysis, purchase KPIs (Purchases MTD, Outstanding AP, Pending Approvals, Open Purchase Orders), purchase order statistics, purchase invoice statistics, goods receipt statistics, top suppliers by outstanding AP, and recent invoices visualization.
-   **Purchase Invoices**: Vendor billing with line items, tax handling (PINV-YYYYMM-######), VAT and Amount After VAT columns in list/detail, header and line discounts (percentage or amount), VAT calculated on net amount after line discount, detail page with vendor info/financial summary/related documents, Select Item modal with accurate Available Qty from `inventory_warehouse_stock` (warehouse-specific or total), print view uses `businessPartner` relation (not vendors table). **Invoice date** on create/draft update is validated **`before_or_equal:today`** (app timezone) unless **Opening Balance Invoice** or permission **`ap.invoices.future_date`**. See `docs/manuals/purchase-invoice-manual-id.md` and `docs/manuals/purchase-invoice-manual-en.md`.
-   **Purchase Payments**: Vendor payment processing with allocation (PP-YYYYMM-######)
-   **Purchase Orders**: Vendor order management with automatic numbering (PO-YYYYMM-######). **Create/Edit forms** (`resources/views/purchase_orders/create.blade.php`, `edit.blade.php`): line and footer totals use a shared JS engine; **header discount** scales each line’s payable (net + VAT − WTax) consistently when header % or amount applies. **`updatingHeaderDiscount`** must be initialized at the **start** of `$(document).ready` before any call to `updateTotals()` (including `initializeExistingLines()` on edit and the initial Add Line trigger on create) so the callback does not throw a temporal dead zone **ReferenceError**—otherwise delegated handlers (e.g. **item search** → `#itemSelectionModal`) never register. Header **0%** clears a stale **Discount Amount** field on recalc; VAT/WTax reads tolerate empty Select2 values; session toasts use **`@json`** for safe JS strings.
-   **Goods Receipt PO**: Purchase Order-based inventory receipt processing with automatic numbering (GR-YYYYMM-######). **Journal**: `GRPOJournalService::createJournalEntries()` posts once (Dr Inventory per product-category account / Cr AP UnInvoice `2.1.1.03`); manual GRPO store must not call `PostingService` again. **Copy guard**: PO must have `approval_status=approved` and `status=ordered` (same as `canBeReceived()`).
-   **AP Aging**: Vendor payment tracking and aging analysis with buckets (Current, 1-30, 31-60, 61-90, 90+ days) calculated from purchase invoices minus purchase payment allocations
-   **AP Balances**: Vendor account balance reporting

### 4. Enhanced Inventory Management System

#### 4.1. Dual-Type Inventory System

-   **Item Types**: Support for both physical items and services with item_type field
-   **Order Types**: Purchase and Sales orders support both item and service types
-   **Inventory Impact**: Only 'item' type affects stock quantities, 'service' type bypasses inventory
-   **Document Flow**:
    -   Item PO → GRPO → Sales Invoice (with multi-GRPO combination)
    -   Service PO → Purchase Invoice (direct, no GRPO needed)
-   **Type Validation**: Prevents mixing item/service types within same order
-   **Numbering**: Different prefixes for copied documents (GRPO vs GR)

#### 4.2. Item Category Account Mapping System

-   **Account Integration**: Each product category maps to 3 specific accounts:
    -   **Inventory Account**: For inventory valuation (e.g., "Inventory - Stationery")
    -   **COGS Account**: For cost of goods sold (e.g., "COGS - Stationery")
    -   **Sales Account**: For revenue recognition (e.g., "Sales - Stationery")
-   **Automatic Mapping**: Items inherit account mappings from their category
-   **Account Inheritance**: Sub-categories can inherit accounts from parent categories if not explicitly set
-   **Service Categories**: Support for service-only categories without inventory accounts
-   **CRUD Interface**: Complete Product Category management system with:
    -   **ProductCategoryController**: Full CRUD operations with validation and hierarchical support
    -   **AdminLTE Views**: Index with table/tree view toggle, create, show, edit views with proper form handling
    -   **Account Selection**: Dropdown interfaces for selecting inventory, COGS, and sales accounts
    -   **Hierarchical Support**: Full parent-child category relationships with account inheritance
    -   **Hierarchical Display**: Categories display with full path (e.g., "Parent > Child > Grandchild") in dropdowns
    -   **Tree View**: Visual hierarchical tree display with color-coded levels and expandable structure
    -   **Parent Selection**: Only root categories shown as potential parents to prevent circular references
    -   **Audit Integration**: Complete audit trail for category changes
    -   **Menu Integration**: Accessible via Master Data → Product Categories
-   **Model Methods**: `getHierarchicalName()`, `getHierarchicalPath()`, `isRoot()`, `getDescendants()`, `getInvalidParentIds()` for hierarchical operations
-   **Sample Categories**: Welding, Electrical, Otomotif, Lifting Tools, Consumables, Stationery, Electronics, Chemical, Bolt Nut, Safety, Tools with proper account mappings

#### 4.3. Multi-Warehouse Management

-   **Warehouse Master Data**: Complete warehouse information with contact details and status
-   **Per-Warehouse Stock Tracking**: Individual stock levels for each item-warehouse combination
-   **Default Warehouse Assignment**: Items can have default warehouses for automatic assignment
-   **Stock Transfers**: Inter-warehouse stock transfer capabilities with full audit trail. Dedicated transfer page (`/warehouses/transfer`) with custom Bootstrap autocomplete search for efficient item selection from large inventories (case-insensitive search by code or name, debounced AJAX, keyboard navigation, search term highlighting), comprehensive stock information display (source warehouse stock, destination warehouse stock, after-transfer projections), and real-time stock updates with validation alerts
-   **Warehouse-Specific Reorder Points**: Different reorder points per warehouse
-   **Sample Warehouses**: Main Warehouse, Branch Warehouse, Storage Facility
-   **Stock Accuracy Verification**: `php artisan inventory:check-accuracy {item_code?}` command verifies accuracy between `current_stock` (from transactions) and sum of warehouse stock records, showing detailed breakdown including transaction analysis and warehouse allocation
-   **Stock Reconciliation**: `php artisan inventory:reconcile-warehouse-stock {item_code?} {--warehouse_id=}` command reconciles warehouse stock from transactions, allocating stock to warehouses for transactions missing `warehouse_id` and creating/updating warehouse stock records to ensure `current_stock` equals sum of warehouse stock records
-   **Item detail transaction history** (`/inventory/{id}` show page): transaction and valuation dates display as **`d M Y`** (e.g. `05 Jun 2026`), aligned with account ledger and purchase invoice inventory panels
-   **Legacy PI duplicate cleanup**: `php artisan inventory:report-purchase-invoice-duplicates` lists duplicate `purchase_invoice` + `item_id` groups; `php artisan inventory:fix-duplicate-transaction --item={code|id}` or `--invoice={PI id|invoice_no}` `[--dry-run] [--force]` removes duplicate rows (prefers row with `purchase_invoice_line_id`, deletes later dupes) and recalculates warehouse stock via `updateItemValuationAfterDataRepair()` (tolerant FIFO). Structural idempotency via `purchase_invoice_line_id` (2026-03-31) does not auto-remove pre-migration duplicates from repost-without-unpost scenarios.
-   **FIFO Layer Repair (self-service UI)**: Routes `inventory/fifo-repair/*` (`inventory.fifo-repair.index|show|repair`), permission `inventory.adjust`. `InventoryFifoRepairService` diagnoses strict FIFO replay failures and layer/stock mismatches; repair inserts backdated `adjustment` rows with `reference_type=fifo_layer_repair` before failing outbound transactions. Index lists FIFO items needing repair; item show page displays warning banner when `diagnose()` is not `ok`. Manuals: `docs/manuals/inventory-fifo-repair-manual-*.md`.
-   **InventoryService FIFO helpers**: `getFifoReplayError()`, `findFifoLayerDeficits()`, `getTolerantFifoLayerQuantity()`, `buildFifoLayers($transactions, bool $strict)`, `updateItemValuationAfterDataRepair()` (tolerant replay for post-cleanup recalc), `calculateUnitCostForRepair()`.

#### 4.4. Sales Price Level System

-   **Three Price Levels**: Level 1 (default), Level 2, Level 3 with flexible pricing
-   **Customer Assignment**: Customers can have default price levels
-   **Item-Specific Pricing**: Individual items can have different prices for each level
-   **Percentage-Based Calculations**: Support for percentage-based price calculations
-   **Customer Overrides**: Customer-specific price overrides for individual items
-   **Flexible Pricing**: Both fixed prices and percentage-based calculations supported
-   **UI Entry Points**: Price levels are configured on the Inventory Item create/edit forms and resolved at runtime via AJAX when selecting items and customers on Sales documents

#### 4.5. Comprehensive Audit Trail System

-   **System-Wide Tracking**: Complete audit trail for all inventory-related changes
-   **Change Tracking**: Old and new values captured for all modifications
-   **User Attribution**: Full user tracking with IP address and user agent
-   **Entity-Specific Logs**: Separate audit trails for items, transactions, warehouses
-   **Action Types**: Created, Updated, Deleted, Approved, Rejected, Transferred, Adjusted
-   **Search and Filtering**: Comprehensive audit log management with filtering capabilities
-   **Current Status**: Foundation implemented (database schema, model, service, controller, routes) - See `docs/audit-trail-analysis-and-recommendations.md` for detailed analysis and 5-phase implementation plan
-   **Implementation Plan**:
    -   Phase 1: Complete Core UI (2-3 days) - Create missing views, sidebar integration, DataTables
    -   Phase 2: Automatic Logging (2-3 days) - Model Observers, Auditable trait, comprehensive model integration
    -   Phase 3: Module Integration (5-8 days) - Workflow logging (Purchase/Sales/Accounting), Business Partner activity, Fixed Asset lifecycle
    -   Phase 4: Enhanced Features (6-10 days) - Activity dashboard, advanced filtering, export/reporting, inline widgets
    -   Phase 5: Optimization (5-9 days) - Log archiving, retention policies, performance optimization
-   **Documentation**: Detailed action plans available in `docs/audit-trail-phase1-detailed-action-plan.md` through `docs/audit-trail-phase4-detailed-action-plan.md`

### 5. Fixed Asset Management

-   **Asset Register**: Complete asset lifecycle management
-   **Asset Categories**: Configurable categories with depreciation settings
-   **Depreciation Management**: Automated depreciation calculation and posting
-   **Asset Disposal**: Disposal process with gain/loss calculation and entity-aware numbering (code 10)
-   **Asset Movement**: Transfer tracking between departments/projects
-   **Data Quality**: Duplicate detection, completeness checks, consistency validation

### 5. Procurement Management

-   **Purchase Orders**: Vendor order management with approval workflow (PO-YYYYMM-######)
-   **Goods Receipt PO**: Purchase Order-based inventory receipt processing (GR-YYYYMM-######)
-   **Vendor Management**: Vendor master data with performance tracking

#### 5.0 Approval Workflow Management System

-   **Workflow Configuration**: Admin UI for creating and managing approval workflows by document type (Purchase Order, Sales Order)
-   **Workflow Steps**: Configurable sequential approval steps with role assignments (Officer, Supervisor, Manager)
-   **Approval Thresholds**: Amount-based threshold configuration determining required approval levels
-   **Threshold Management**: Create, edit, and delete approval thresholds with overlap validation
-   **Workflow Steps Management**: Dynamic step addition/removal with step order, role, approval type (Sequential/Parallel), and required flag
-   **Admin Interface**: Complete CRUD operations with DataTables integration, modal-based threshold management, and comprehensive validation
-   **Database Schema**: `approval_workflows`, `approval_workflow_steps`, `approval_thresholds` tables with proper relationships
-   **Service Integration**: ApprovalWorkflowService integration with PurchaseService and SalesService for automatic workflow creation
-   **Default Configuration**: ApprovalWorkflowSeeder provides default workflows and thresholds for purchase_order and sales_order document types

#### 5.1 Goods Receipt PO System Architecture

-   **Vendor-First Workflow**: Users must select vendor before accessing Purchase Orders, ensuring data consistency
-   **Dynamic PO Filtering**: AJAX-powered Purchase Order dropdown filtered by selected vendor and remaining quantities
-   **Copy Remaining Lines**: Automated copying of Purchase Order lines with remaining quantities (pending_qty > 0)
-   **Smart Quantity Calculation**: Automatic calculation of remaining quantities (PO qty - received qty) for accurate line population
-   **Remaining Quantity Display**: Dedicated "Remaining Qty" column in GRPO lines table showing pending quantities from source PO
-   **PO-Based Item Filtering**: Item selection modal filters items to show only those from selected PO with remaining quantities
-   **Enhanced User Interface**: Simplified interface for warehouse users with financial columns removed (amount, VAT, WTax)
-   **Intelligent Item Selection**: Modal displays items with "From PO" category and remaining quantities in stock column
-   **Database Schema**: goods_receipt_po and goods_receipt_po_lines tables with proper foreign key relationships
-   **Model Structure**: GoodsReceiptPO and GoodsReceiptPOLine models with comprehensive relationships
-   **Controller Architecture**: GoodsReceiptPOController with AJAX endpoints for vendor-specific PO retrieval and line copying
-   **Route Structure**: goods-receipt-pos.\* routes with enhanced AJAX endpoints (/vendor-pos, /remaining-lines)
-   **JavaScript Enhancement**: Dynamic form handling with vendor selection triggering PO filtering, copy functionality, and item filtering
-   **User Interface**: Professional AdminLTE integration with enhanced form controls, remaining quantity tracking, and guided user experience
-   **Inventory Transaction Creation**: Automatic inventory transaction creation when GRPO is created or received using `InventoryService::processPurchaseTransaction()` with reference_type='goods_receipt_po', ensuring complete audit trail and proper stock tracking
-   **Retroactive Fix Support**: `fixInventoryTransactions()` method available for existing GRPOs that were created before inventory transaction creation was implemented

### 6. Sales Management

-   **Sales Orders**: Customer order management (SO-YYYYMM-######) with approval workflow, inventory item display, and auto-recovery for missing approval records
-   **Delivery Orders**: Delivery management with inventory reservation and revenue recognition (DO-YYYYMM-######)
-   **Customer Management**: Customer master data with credit management

#### 6.0 Sales Order Approval Workflow System

-   **Approval Workflow**: Multi-level approval process (Officer, Supervisor, Manager) based on order amount thresholds
-   **Auto-Recovery Mechanism**: `SalesService::approveSalesOrder()` automatically creates missing approval records if they don't exist, preventing approval failures
-   **Approval Records**: `sales_order_approvals` table stores individual approval records with user_id, approval_level, status (pending/approved/rejected), and comments
-   **Status Progression**: Draft → Pending Approval → Ordered (when all approvals complete)
-   **Fix Commands**: `php artisan sales-order:fix-approval {orderNo}` or `--all` to bulk-fix Sales Orders with missing approval records
-   **Role Management**: `php artisan role:ensure-officer` command ensures "officer" role exists in both Spatie Permission system and approval workflow system
-   **Fix Route**: `/sales-orders/fix-approval/{orderNo}` for ad-hoc approval workflow fixes
-   **Dual Role System**: Approval workflows use `user_roles` table (officer/supervisor/manager) while UI displays Spatie Permission roles from `roles` table

#### 6.1 Sales Order Display Enhancement

-   **Item Code Column**: Displays inventory item code from `inventory_items.code` via relationship, falls back to `sales_order_lines.item_code` if relationship unavailable
-   **Item Name Column**: Displays inventory item name from `inventory_items.name` via relationship, falls back to `sales_order_lines.item_name` if relationship unavailable
-   **Eager Loading**: `SalesOrderController::show()` eager loads `lines.inventoryItem` relationship for optimal performance
-   **Data Consistency**: Prefers relationship data over denormalized fields for accurate inventory information display

### 6.1. Warehouse Selection System

-   **Comprehensive Warehouse Integration**: Warehouse selection functionality across all order types (Purchase Orders, Goods Receipt PO, Sales Orders, Delivery Orders) with required field validation
-   **Single Warehouse Selection**: Each order type supports single warehouse selection (destination warehouse for POs, source warehouse for SOs, single warehouse for DOs)
-   **GRPO Default Logic**: Goods Receipt PO defaults to the original Purchase Order's warehouse but allows manual changes for flexibility
-   **Transit Warehouse Filtering**: Automatic filtering of transit warehouses from manual selection dropdowns since transit warehouses are only used for automatic ITO/ITI activities
-   **Database Schema Integration**: warehouse_id foreign key fields added to all order tables with proper constraints and relationships
-   **Model Relationships**: BelongsTo relationships between order models and Warehouse model with proper fillable field configuration
-   **Controller Validation**: Comprehensive validation rules ensuring warehouse_id is required and exists in warehouses table
-   **View Integration**: Professional warehouse selection dropdowns using Select2BS4 with active warehouse filtering and proper error handling
-   **Service Layer Support**: Service methods updated to handle warehouse_id parameter passing and business logic integration
-   **Transit Warehouse Logic**: Transit warehouses follow naming convention (e.g., WH001_TRANSIT for WH001) and are automatically used in ITO/ITI operations based on source warehouse

### 6.2. GR/GI Management System

-   **Goods Receipt (GR)**: Non-purchase receiving operations with automatic journal integration
-   **Goods Issue (GI)**: Non-sales issuing operations with FIFO/weighted-average cost valuation
-   **Purpose Management**: Configurable GR/GI purposes (Customer Return, Donation, Sample, etc.)
-   **Account Mapping**: Automatic account mapping based on item categories and purposes
-   **Approval Workflow**: Draft → Pending Approval → Approved status progression
-   **Inventory Transaction Creation**: Automatic inventory transaction creation when GR/GI is approved using `GRGIService::updateWarehouseStock()` with reference_type='gr_gi', proper unit cost calculation, and item valuation updates via `InventoryService::updateItemValuationAfterDataRepair()` (tolerant FIFO refresh so legacy layer gaps surface as repairable data issues rather than hard-blocking approval when stock qty is otherwise consistent)
-   **Retroactive Fix Support**: `fixInventoryTransactions()` method available for existing GR/GI documents that were approved before inventory transaction creation was implemented
-   **Quantity Display Format**: Quantity column displays with 2 decimal places for consistency
-   **Journal Integration**: Automatic journal entry creation on document approval via PostingService integration (GR: Debit=item category account, Credit=purpose account; GI: Debit=purpose account, Credit=item category account)
-   **PostingService Integration**: GRGIService uses centralized PostingService for journal creation, ensuring consistent journal schema, entity resolution, currency handling, and control account balance updates
-   **Valuation Methods**: FIFO and weighted average (LIFO removed — PSAK 14); manual unit cost on adjustments
-   **SweetAlert2 Integration**: Professional confirmation dialogs for critical operations
-   **Seeder Requirements**: GRGIPurposeSeeder and GRGIAccountMappingSeeder must be run for system initialization

### 6.3. Delivery Order System

-   **Simplified Flow**: Draft → Approve → In Transit → Mark as Delivered → Completed. Picking step removed; stock reduces at approval. `DeliveryService::approveDeliveryOrder()` sets status to `in_transit`, auto-sets `picked_qty = ordered_qty` per line, calls `reduceStockOnApproval()` for inventory sale transactions, and creates inventory reservation journal. `markAsDelivered()` creates revenue recognition journal and sets status to `completed`.
-   **Multiple Partial DOs per SO**: A Sales Order can have multiple Delivery Orders. `DeliveryService::getDeliveredQtyForSalesOrderLineExcludingDo()` sums `delivered_qty` from DO lines whose header status is **not** `cancelled` or **`reversed`** (optionally excluding current DO); `syncSalesOrderLineFromDeliveries()` updates `SalesOrderLine.delivered_qty`. New DO creation uses remaining qty = SO qty - allocated; `getRemainingLinesForSalesOrder()` returns `ordered_qty`, `remaining_qty`, `max_qty` per line (Remain Qty = SO qty - delivered by other DOs).
-   **Delivery Items Table**: Columns No, Item Code, Item Name, Ordered Qty (SO line original), Remain Qty (SO - delivered by others, read-only), Delivery Qty (qty for this DO, editable when draft), Action (edit/delete when draft). VAT, WTax, Unit Price hidden from create/edit/show/print (kept in DB for invoicing).
-   **Inventory Stock Reduction**: Stock reduces at **approval** via `reduceStockOnApproval()` (not at pick). `DeliveryService::processSaleTransaction()` creates inventory sale transactions for `ordered_qty` per line. Reversal via `processAdjustmentTransaction` when DO is cancelled with picked_qty > 0.
-   **Mark as Delivered**: Single button with modal (date, time, delivered by). `markAsDelivered()` sets `delivered_at`, `delivered_by`, `actual_delivery_date`; sets all line `delivered_qty = ordered_qty`; syncs SO lines; creates revenue recognition journal via `DeliveryJournalService::createRevenueRecognition()`; sets status to `completed`. `delivery_orders` table has `delivered_at` (datetime) and `delivered_by` (string).
-   **Journal Flow**: Approve → Inventory Reservation journal (DR Inventory Reserved, CR Inventory Available). Mark as Delivered → Revenue Recognition journal (Revenue, COGS, AR UnInvoice, release Inventory Reserved). Complete Delivery step removed; revenue recognition merged into Mark as Delivered.
-   **Status Tracking**: draft, in_transit, ready, delivered (transient), completed, **reversed** (after **Reverse delivery**), cancelled. Legacy flows may still show picking, packed, partial_delivered on older rows.
-   **Approval Workflows**: Multi-level approval process with proper authorization controls
-   **Delivery Tracking**: Logistics cost tracking, performance metrics, and customer satisfaction monitoring
-   **Cancel DO (UI)**: Detail page (`delivery_orders/show.blade.php`) exposes **Cancel delivery order** when `DeliveryOrder::canBeCancelled()` (status draft, picking, packed, or in_transit): `DELETE` → `delivery-orders.destroy` → `DeliveryService::cancelDeliveryOrder()` (status cancelled; release reservation; inventory adjustment for picked qty). Distinct from **Reject** (approval pending only). Manual: `docs/manuals/delivery-order-manual-id.md` (Cancel vs partial shipment vs SO).
-   **Reverse delivery (UI)**: When `DeliveryOrder::canBeReversed()` — status **partial_delivered**, **delivered**, or **completed**; no row in `delivery_order_sales_invoice`; if DO `closure_status` is closed by a sales invoice, a **posted** `SalesCreditMemo` for that `closed_by_document_id` SI is required; pivot must be empty (unlink DO–SI first). `POST /delivery-orders/{id}/reverse` → `DeliveryService::reverseDeliveryOrder()` (permission `delivery-orders.reverse`): `DeliveryJournalService::reverseOriginalJournalsForDeliveryOrder()` reverses each original journal for `DeliveryOrder::class` (skips entries already reversed); inventory restored via `processAdjustmentTransaction` per DO line sale qty; header status **reversed**; `AuditLogService` logs action `reversed`. See `reversalBlockReason()` on `DeliveryOrder` for user-facing messages.
-   **Print Functionality**: Professional delivery order documents (No, Item Code, Item Name, Delivery Qty). **Print Layout Selection**: Dropdown on show page offers Standard (A4/Laser) and Dot Matrix layouts; `?layout=dotmatrix` returns compact 9.5in Courier layout for dot matrix printers.
-   **DataTables Search**: `filterColumn()` overrides for do_number, sales_order_no, customer, created_by to map Yajra search to correct table columns (do.do_number, so.order_no, c.name, u.name); fixes "Column not found" when searching DO list.
-   **DeliveryJournalService COGS Fallback**: `getCOGSAccount()` accepts code 5.1.1, 5.1, or name containing "Cost of Goods Sold"/"HPP Barang Dagangan"; Indonesian chart uses 5.1 (HPP Barang Dagangan) when 5.1.1 absent.
-   **Data Integrity**: Foreign key constraint handling with graceful NULL assignment when inventory items are deleted
-   **Sales Order Integration**: Customer-based filtering for Sales Order selection. Create Delivery Order available when SO status is `confirmed` or `processing`.
-   **Item Display**: Fallback chain for displaying item information (item_code → inventoryItem->code, description → inventoryItem->name → item_name)
-   **Backfill Command**: `php artisan delivery-orders:backfill-inventory-transactions` with `--dry-run` for existing DO lines with picked/delivered qty but no inventory transactions

### 7. Control Account Management System

-   **Control Accounts**: Summary accounts representing totals of subsidiary ledger groups (AR Control, AP Control, Inventory Control, Fixed Assets Control)
-   **Subsidiary Ledger Management**: Individual subsidiary accounts linked to control accounts (Business Partners, Inventory Items, Fixed Assets)
-   **Automatic Balance Tracking**: Real-time balance updates through PostingService integration
-   **Reconciliation Dashboard**: Comprehensive reconciliation interface with variance detection and exception reporting
-   **Multi-Dimensional Support**: Control account balances tracked by project and department dimensions
-   **Exception Reporting**: Automatic identification of accounts with variances above tolerance levels
-   **Audit Trail**: Complete transaction history and reconciliation tracking

### 8. Corrected Accounting Flow with Intermediate Accounts

-   **Intermediate Accounts**: AR UnInvoice (1.1.2.04) and AP UnInvoice (2.1.1.03) for proper accrual accounting
-   **GRPO Accounting**: Debit Inventory Account, Credit AP UnInvoice (goods received but not yet invoiced)
-   **Purchase Invoice Accounting (Credit)**: Debit AP UnInvoice, Credit Utang Dagang (liability transfer from intermediate to final)
-   **Purchase Invoice Accounting (Direct Cash)**: Debit Inventory Account, Credit Cash Account (immediate cash payment, no Purchase Payment needed)
-   **Purchase Payment Accounting**: Debit Utang Dagang, Credit Cash/Bank (from payment line `account_id`, grouped by COA account)
-   **Delivery Order Accounting**: Debit AR UnInvoice, Credit Revenue (goods delivered but not yet invoiced)
-   **Sales Invoice Accounting (posted, from DO)**: Credit **AR UnInvoice** (1.1.2.04) for tax-inclusive gross (clears uninvoiced AR); Debit **Piutang Dagang** (1.1.2.01) for the same gross; Debit **revenue** (each line’s account) for **PPN component** reclassified from DO-time gross revenue; Credit **PPN Keluaran** (2.1.2) for output VAT. **Direct Sale SI** (`is_direct_sale`): full recognition in one journal — Cr Revenue (scaled DPP per line), Cr PPN, Dr COGS, Cr Inventory Available (1.1.3.02), Dr AR; cash mode adds auto Sales Receipt (Dr Cash/Bank, Cr AR). **Opening balance SI**: Debit AR (gross); Credit Saldo Awal Laba Ditahan (3.3.1) for gross−PPN; Credit PPN when applicable.
-   **Sales Receipt Accounting**: Debit Cash/Bank (from receipt line `account_id`, grouped by COA account via `CashJournalLineBuilder`), Credit Piutang Dagang (receivable settlement). Journal entity resolved from `SalesReceipt.company_entity_id` via `PostingService::resolveJournalEntity()`. **Legacy data**: pre-2026-06-23 postings used hard-coded `1.1.1.01` and often default entity (CV) — repair via `sales-receipts:repair-bank-journals` (reverse + repost).
-   **Automatic Journal Generation**: All transactions automatically create balanced journal entries
-   **Account Mapping Logic**: Inventory accounts mapped by item categories, liability/receivable accounts by business partner type
-   **Direct Cash Purchase Flow**: Simplified workflow (PI → Post) for immediate cash purchases with automatic inventory transaction creation and cash account selection support

### 9. Multi-Dimensional Accounting

-   **Projects**: Project-based cost tracking
-   **Departments**: Departmental cost allocation

### 10. Reporting & Analytics

-   **Trial Balance**: Real-time financial position reporting
-   **GL Detail**: Detailed general ledger with filtering
-   **Cash Ledger**: Cash flow tracking and reporting
-   **Asset Reports**: Comprehensive asset reporting suite
-   **AR/AP Reports**: Customer and vendor analysis
-   **Withholding Tax**: Tax reporting and compliance
-   **Document Creation Logs**: Unified list of core trade documents (PO, GRPO, PI, PP, SO, DO, SI, SR) ordered by **`created_at`**, with filters (date range, document type, supplier/customer). Route: `GET /reports/document-creation-logs` (`reports.document-creation-logs.index`). Permission: **`reports.open-items`** (same as Open Items). Service: `App\Services\DocumentCreationLogsService`; controller: `App\Http\Controllers\Reports\DocumentCreationLogsController`

### 10. Indonesian Tax Compliance System

-   **Tax Transaction Management**: Comprehensive tracking of all tax transactions (PPN, PPh 21-26, PPh 4(2))
-   **Tax Period Management**: Monthly/quarterly/annual tax period management with status tracking
-   **Tax Report Generation**: Automatic SPT (Surat Pemberitahuan Tahunan) report generation
-   **Tax Settings Configuration**: Configurable tax rates, company information, and reporting preferences
-   **Compliance Monitoring**: Overdue tracking, audit trail, and compliance status monitoring
-   **Integration**: Automatic tax calculation with purchase/sales systems

### 11. Advanced Trading Analytics System (Phase 4)

-   **COGS Foundation**: Comprehensive Cost of Goods Sold tracking with multiple valuation methods (FIFO, LIFO, Weighted Average)
-   **Cost Allocation**: Automatic cost allocation across products, customers, and suppliers with configurable methods
-   **Margin Analysis**: Real-time profitability analysis with gross and net margin calculations
-   **Supplier Analytics**: Performance tracking, cost optimization, risk assessment, and supplier ranking
-   **Business Intelligence**: Advanced analytics with insights generation, recommendations engine, and KPI tracking
-   **Unified Dashboard**: Integrated analytics platform combining all trading components for comprehensive decision making

### 12. User Management & Security

-   **Role-Based Access Control**: Granular permission system
-   **User Management**: Complete user lifecycle management
-   **Permission Management**: Fine-grained access control
-   **Session Management**: Secure authentication and session handling

### 13. Training & Documentation System

-   **Comprehensive Training Materials**: Complete 3-day training workshop package with 9 comprehensive documents
-   **Module-Based Training**: 7 specialized training modules covering all major system components
-   **Story-Based Learning**: 35+ realistic business scenarios with hands-on exercises
-   **Assessment Framework**: Multi-level evaluation system with certification levels (Basic, Intermediate, Advanced, Expert)
-   **Indonesian Business Context**: All training materials tailored for Indonesian trading company operations
-   **Implementation Guidelines**: Detailed delivery structure, success metrics, and post-training support

### 14. Business Partner Management System

-   **Unified Partner Management**: Single interface for managing customers and suppliers with partner_type classification (customer, supplier)
-   **Default Currency Assignment**: Business partners automatically receive base currency (IDR) as default when `default_currency_id` is not provided during creation or update, ensuring data integrity and preventing null currency issues
-   **Account Mapping**: Business partners can be assigned specific GL accounts with automatic default assignment (Customer→AR, Supplier→AP)
-   **Conditional Relationship Loading**: BusinessPartnerService conditionally loads relationships (purchaseOrders, salesOrders, purchaseInvoices, salesInvoices) only if corresponding database tables exist, preventing errors during schema evolution or partial migrations
-   **Defensive View Logic**: Blade views verify both table existence (`Schema::hasTable()`) and relationship loading status (`relationLoaded()`) before accessing relationship data, ensuring graceful handling of missing tables or relationships
-   **Account Statement (detail tab)**: **`BusinessPartnerAccountStatementService`** builds the partner **Account statement** tab from **posted** `journal_lines` on trade AP/AR **control accounts** (by account `code`) and journals linked to partner documents via `source_type` / `source_id`, plus lines on the partner’s optional **`account_id`**; see **`docs/BUSINESS-PARTNER-ACCOUNT-STATEMENT.md`**
-   **Tabbed Interface**: Organized partner data across General Information, Contact Details, Addresses, Taxation & Terms (with Accounting section), Banking & Financial, Transactions, and **Account statement**
-   **Flexible Data Storage**: BusinessPartnerDetail model enables custom field storage without schema changes
-   **Multiple Contacts**: Support for multiple contact persons per partner with different contact types (primary, billing, shipping, technical, sales, support)
-   **Multiple Addresses**: Support for multiple addresses per partner with different address types (billing, shipping, registered, warehouse, office)
-   **Office/Warehouse Address Resolution for Sales Documents**: `BusinessPartner::officeAddress` / `default_office_address` resolve `office → registered → billing → primary`; `BusinessPartner::warehouseAddress` / `default_warehouse_address` resolve `warehouse → shipping → primary`. **Sales Invoice** print/PDF "Bill To" uses the customer's **office** address (resolved live at render time; no address stored on `sales_invoices`). **Sales Order** and **Delivery Order** create forms auto-fill `delivery_address` from the customer's **warehouse** address (still editable text, with the Sales Order's own saved `delivery_address` taking precedence when converting SO → DO). This models "SI bills to the customer's office, goods deliver to the customer's warehouse" without new document columns.
-   **Transactions tab**: Shows recent operational activity (e.g. orders, invoices) as implemented in the partner detail view; this is **not** identical to the GL-based Account statement tab
-   **Backward Compatibility**: Maintained compatibility with existing PurchaseOrder, SalesOrder, and DeliveryOrder models
-   **Data Migration**: Comprehensive migration from separate customers and vendors tables to unified business_partners structure
-   **Field Mapping Consistency**: All controllers, services, forms, and JavaScript use business_partner_id consistently across the entire ERP system
-   **Form Submission Integrity**: All forms submit correctly with proper field validation and JavaScript handling

### 15. Master Data Management System

-   **Projects Management**: Project-based cost tracking with comprehensive CRUD operations
-   **Departments Management**: Departmental cost allocation and organizational structure management
-   **SweetAlert2 Integration**: Consistent confirmation dialogs and success notifications across all Master Data features
-   **JSON API Responses**: Proper AJAX handling with JSON success/error responses for seamless user experience
-   **DataTable Integration**: Dynamic data loading with search, sorting, and pagination capabilities

### 16. Comprehensive Entity-Aware Document Numbering System

-   **Centralized Service**: DocumentNumberingService provides unified entity-aware document numbering across all document types
-   **Universal Entity Format**: All document types now use `EEYYDDNNNNN` format (Entity code, 2-digit year, document code, 5-digit sequence)
    -   **Format Breakdown**: `EE` (2-digit entity code) + `YY` (2-digit year) + `DD` (2-digit document code) + `NNNNN` (5-digit sequence)
    -   **Example**: `71250100001` = PT CSJ (71) + 2025 (25) + Purchase Order (01) + Sequence 00001
-   **Document Code Assignment**:
    -   PO `01`, GRPO `02`, PI `03`, Purchase Payment `04`, Sales Order `06`, DO `07`, SI `08`, Sales Receipt `09`
    -   Asset Disposal `10`, Cash Expense `11`, Journal `12`, Account Statement `13`
-   **Entity Resolution**:
    -   Purchase/Sales documents: Inherit entity from document creator or base document (PO→GRPO, SO→DO)
    -   Asset Disposal: Resolve from Asset→PurchaseInvoice entity chain, fallback to default
    -   Cash Expense/Journal/Account Statement: Use default entity
    -   Manual Journals: Default entity assignment
-   **Thread-Safe Operations**: Database transactions with proper locking prevent duplicate numbers
-   **Year-Based Sequences**: Automatic sequence reset on January 1st per entity/document type/year
-   **Sequence Management**: DocumentSequence model tracks last sequence per-entity/per-document/per-year (`company_entity_id`, `document_code`, `year`, `current_number`)
-   **Legacy Format**: PREFIX-YYYYMM-###### format is completely deprecated
-   **Error Handling**: Comprehensive exception handling, validation, and entity resolution
-   **Database Persistence**: Sequence tracking stored in document_sequences table with unique composite keys
-   **CompanyEntityService Integration**: Automatic entity resolution, default entity fallback, and entity context propagation

### 17. Document Closure System

-   **Document Lifecycle Management**: Comprehensive tracking of document status (open/closed) throughout business workflows
-   **Automatic Closure Logic**: Documents automatically close when subsequent documents fulfill their requirements
-   **Closure Chain Management**: Purchase Orders closed by Goods Receipt PO, Goods Receipt PO closed by Purchase Invoices, Purchase Invoices closed by Purchase Payments, Sales Orders closed by Delivery Orders, Delivery Orders closed by Sales Invoices, Sales Invoices closed by Sales Receipts
-   **Partial Closure Support**: Documents can be partially fulfilled by multiple subsequent documents with quantity/amount tracking
-   **Manual Closure Override**: Permission-based manual closure and reversal capabilities for corrections and exceptions
-   **Closure Tracking**: Complete audit trail of closure events including closing document type, ID, timestamp, and user attribution
-   **ERP Parameters Configuration**: User-configurable business rules including overdue thresholds, auto-closure settings, and price difference handling
-   **Open Items Reporting**: Comprehensive reporting system for monitoring outstanding documents with aging analysis and exception identification
-   **Document creator attribution (`created_by`)**: Core trade document tables expose nullable **`created_by`** → `users` where applicable: **purchase_orders**, **sales_orders**, **goods_receipt_po**, **purchase_invoices**, **sales_invoices**, **delivery_orders**, **purchase_payments**, **sales_receipts** (see migrations `2026_04_07_*` and earlier trading-enhancement migrations). **GR/GI** headers (`gr_gi_headers`) use required **`created_by`**. **Accounting journals** (`journals`) record the posting user via **`posted_by`** (manual journal flow sets `posted_by` at post time). Legacy rows may have `created_by` null until backfilled; new creates set `Auth::id()` where implemented.
-   **Database Schema Enhancement**: Added closure_status, closed_by_document_type, closed_by_document_id, closed_at, and closed_by_user_id fields to all document tables
-   **Service Layer Architecture**: DocumentClosureService for closure logic and OpenItemsService for reporting with comprehensive business rule validation
-   **UI Integration**: Status indicators in DataTables, closure information in document views, and dedicated Open Items report interface
-   **Performance Optimization**: Database indexes on closure_status and closed_by fields for efficient querying

### 18. Unified Design System

-   **Consistent UI Patterns**: All create pages follow unified design standards with card-outline styling
-   **Professional Visual Design**: Enhanced headers with relevant icons, proper color schemes, and visual hierarchy
-   **Responsive Form Layouts**: 3-column responsive layouts with proper Bootstrap grid implementation
-   **Enhanced User Experience**: Select2BS4 integration for improved dropdown functionality with search capabilities
-   **Real-Time Calculations**: Automatic total calculations with Indonesian number formatting across all forms
-   **Professional Table Design**: Card-outline table sections with striped styling and proper action buttons
-   **Improved Navigation**: Consistent breadcrumb navigation and "Back" buttons across all pages
-   **Form Validation**: Comprehensive error handling with proper field indicators and validation messages
-   **Button Styling**: Consistent button design with FontAwesome icons and professional styling
-   **Page Structure**: Standardized page layout with proper sections, headers, and footers
-   **Accessibility**: Proper form labels, required field indicators, and semantic HTML structure

## Database Schema

### Core Tables (52 migrations total - consolidated from 51, plus Phase 3 tax compliance and Phase 4 advanced trading analytics)

#### Financial Tables

-   `accounts`: Chart of accounts with hierarchical structure
-   `journals`: Journal headers with entity-aware numbering (code 12) and multi-currency support (currency_id, exchange_rate, company_entity_id resolved from source documents)
-   `journal_lines`: Journal line items with dimensions and foreign currency amounts (currency_id, exchange_rate, debit_foreign, credit_foreign)
-   `periods`: Financial periods with close/open status
-   `account_statements`: Account statement headers with opening/closing balances, entity-aware numbering (code 13), `company_entity_id` for default entity assignment
-   `account_statement_lines`: Statement line items with transaction details and running balances
-   `control_accounts`: Control account definitions linking GL accounts to control types (AR, AP, Inventory, Fixed Assets)
-   `subsidiary_ledger_accounts`: Subsidiary ledger accounts linking individual entities to control accounts
-   `control_account_balances`: Control account balances with multi-dimensional accounting support (projects/departments)

#### Multi-Currency Tables

-   `currencies`: Currency master data with code, name, symbol, decimal places, and base currency flag
-   `exchange_rates`: Exchange rate management with from/to currency pairs, effective dates, rate types (daily/manual/custom), and source tracking
-   `currency_revaluations`: Currency revaluation headers for periodic FX adjustments
-   `currency_revaluation_lines`: Individual account revaluation details with original and revalued amounts

#### AR/AP Tables

-   `sales_invoices` / `sales_invoice_lines`: Customer billing with `company_entity_id` for letterhead/accounting context
-   `sales_receipts` / `sales_receipt_lines`: Customer payments with `company_entity_id`
-   `purchase_invoices` / `purchase_invoice_lines`: Vendor billing with `company_entity_id`
-   `purchase_payments` / `purchase_payment_lines`: Vendor payments with `company_entity_id`
-   `sales_receipt_allocations` / `purchase_payment_allocations`: Explicit invoice allocation tracking for AR/AP payments

#### Asset Management Tables

-   `asset_categories`: Asset classification with depreciation rules
-   `assets`: Complete asset register with financial tracking
-   `asset_depreciation_entries`: Depreciation transaction history
-   `asset_depreciation_runs`: Depreciation batch processing
-   `asset_disposals`: Asset disposal transactions
-   `asset_movements`: Asset transfer tracking

#### Business Partner Tables

-   `business_partners`: Unified customer and supplier master data with partner_type classification (customer, supplier), account_id for GL account mapping, and default_currency_id (foreign key to currencies table) - automatically set to base currency (IDR) if not provided during creation or update via BusinessPartnerService
-   `business_partner_contacts`: Multiple contact persons per partner with contact types
-   `business_partner_addresses`: Multiple addresses per partner with address types
-   `business_partner_details`: Flexible custom field storage for partner-specific data
-   `tax_codes`: Tax configuration
-   `bank_accounts`: Operational bank master linked to COA via `account_id` (account number used for PDF auto-detect). Routes: `/bank-accounts`.
-   `bank_statements` / `bank_statement_lines`: Imported statement headers + normalized lines (`direction` debit|credit, `line_hash` dedupe, `match_status`).
-   `bank_reconciliations` / `bank_reconciliation_matches`: Reconciliation sessions and matches to `journal_lines` (or adjustment journals via `PostingService`, `source_type` `bank_reconciliation`).
-   `bank_transactions`: Legacy stub (unused by reconciliation module).
-   **Bank Reconciliation flow**: Upload PDF → `smalot/pdfparser` text extract → OpenRouter LLM JSON normalize → deterministic + AI matching → optional adjustment journals → finalize when statement closing equals reconciled book balance. UI: `/bank-reconciliation` workbench. Permissions: `bank_accounts.*`, `bank_reconciliation.*`.

#### Company Entity Tables

-   `company_entities`: Legal entity master data powering multi-letterhead workflows (code, legal name, NPWP/tax number, address, phone/email, website, logo_path, letterhead_meta JSON, is_active). Seeds include PT Cahaya Sarange Jaya (`code 71`) and CV Cahaya Saranghae (`code 72`) referencing logos in `public/logo_pt_csj.png` and `public/logo_cv_saranghae.png`.
-   `document_sequences`: Extended with `company_entity_id`, `document_code`, `year`, and `current_number` columns (plus nullable legacy fields) to support per-entity/per-document/per-year sequencing for the upcoming `EEYYDD99999` format.
-   `CompanyEntityService`: Central helper that lists active entities, resolves default entity context (**PT Cahaya Sarange Jaya**, code `71` via `CompanyEntity::DEFAULT_CODE`), and propagates `company_entity_id` when copying documents (PO → GRPO, SO → DO, DO → SI, etc.).

#### Order Management Tables

-   `sales_orders` / `sales_order_lines`: Sales order processing with order_type (item/service), business_partner_id, **company_entity_id** (letterhead + posting context), warehouse_id (single source warehouse), and document closure fields (closure_status, closed_by_document_type, closed_by_document_id, closed_at, closed_by_user_id)
-   `purchase_orders` / `purchase_order_lines`: Purchase order processing with order_type (item/service), business_partner_id, **company_entity_id**, warehouse_id (single destination warehouse), and document closure fields (closure_status, closed_by_document_type, closed_by_document_id, closed_at, closed_by_user_id)
-   `goods_receipt_po` / `goods_receipt_po_lines`: Purchase Order-based inventory receipt with source tracking (purchase_order_id), business_partner_id, **company_entity_id**, warehouse_id (defaults to PO's warehouse but allows changes), and document closure fields (closure_status, closed_by_document_type, closed_by_document_id, closed_at, closed_by_user_id)
-   `sales_invoice_grpo_combinations`: Multi-GRPO Sales Invoice tracking
-   `delivery_orders` / `delivery_order_lines`: Delivery order processing with inventory reservation, revenue recognition, business_partner_id, **company_entity_id**, warehouse_id (single warehouse), and document closure fields (closure_status, closed_by_document_type, closed_by_document_id, closed_at, closed_by_user_id)
-   `delivery_tracking`: Delivery tracking with logistics cost and performance metrics

#### GR/GI Management Tables

-   `gr_gi_purposes`: GR/GI purpose definitions with type (goods_receipt/goods_issue), code, name, description, and status
-   `gr_gi_headers`: GR/GI document headers with document_number, document_type, purpose_id, warehouse_id, transaction_date, reference_number, notes, total_amount, status, approval workflow fields (approved_by, approved_at, cancelled_by, cancelled_at), and audit fields
-   `gr_gi_lines`: GR/GI line items with header_id, item_id, quantity, unit_price, total_amount, and notes
-   `gr_gi_account_mappings`: Account mapping configuration linking purposes and item categories to debit/credit accounts for automatic journal entry generation
-   `gr_gi_journal_entries`: Journal entry tracking linking GR/GI documents to generated journal entries for audit trail and reconciliation

#### Document Relationship Management Tables

-   `document_relationships`: Polymorphic relationship storage for document connections supporting base/target document relationships across all document types with automatic relationship initialization from existing data
-   `document_analytics`: Usage tracking and performance analytics with user behavior analysis and system optimization data including comprehensive indexing for performance

#### Dimension Tables

-   `projects`: Project dimension for cost tracking
-   `departments`: Department dimension for cost allocation

#### Trading Company Tables (Phase 1-3)

-   `product_categories`: Hierarchical product categorization with account mapping (inventory_account_id, cogs_account_id, sales_account_id)
-   `inventory_items`: Product master data with pricing, stock levels, item_type (item/service), default_warehouse_id, and price levels (selling_price_level_2, selling_price_level_3, percentage fields)
-   `inventory_transactions`: Stock movement tracking with cost allocation and warehouse_id. All inventory-affecting documents (GRPO, GR/GI, Purchase Invoice, Sales Invoice, Delivery Order lines) create inventory transactions with proper reference_type and reference_id for complete audit trail and transaction history. Delivery Order lines use reference_type='delivery_order_line' when Picked Qty or Delivered Qty is updated. Transaction quantities are stored with sign: positive for purchases/adjustments, negative for sales. Stock calculation sums all transaction quantities (purchases + adjustments + sales) since sales are already negative.
-   `inventory_valuations`: Real-time inventory valuation with multiple methods. Valuations are automatically updated when transactions occur. Use `php artisan inventory:fix-valuation` command to correct any historical valuation records with incorrect quantities.
-   `warehouses`: Warehouse master data with contact information and status
-   `inventory_warehouse_stock`: Per-warehouse stock tracking with quantity_on_hand, reserved_quantity, available_quantity, and warehouse-specific reorder points
-   `audit_logs`: System-wide audit trail with entity_type, entity_id, action, old_values, new_values, user tracking, IP address, user agent, and timestamps. Currently integrated with Inventory, Warehouse, and Product Categories modules. See `docs/audit-trail-analysis-and-recommendations.md` for comprehensive analysis and 5-phase enhancement plan.
-   `customer_item_price_levels`: Customer-specific price level overrides with custom pricing capabilities
-   `tax_transactions`: Enhanced individual tax calculation tracking with Indonesian compliance
-   `tax_periods`: Tax reporting periods with status management
-   `tax_reports`: SPT report generation and submission tracking
-   `tax_settings`: Configurable tax rates and company information
-   `tax_compliance_logs`: Complete audit trail for tax operations

#### Advanced Trading Analytics Tables (Phase 4)

-   `cost_allocation_methods`: Configurable cost allocation methods (direct, percentage, activity-based)
-   `cost_categories`: Cost categorization for better tracking and analysis
-   `cost_allocations`: Cost allocation rules and configurations
-   `cost_histories`: Historical cost tracking with transaction details
-   `product_cost_summaries`: Aggregated product cost data with period-based summaries
-   `customer_cost_allocations`: Customer-specific cost allocation tracking
-   `margin_analyses`: Comprehensive margin analysis with profitability metrics
-   `supplier_cost_analyses`: Supplier cost analysis and performance tracking
-   `supplier_performances`: Supplier performance metrics and scoring
-   `supplier_comparisons`: Supplier comparison data and benchmarking
-   `business_intelligences`: Business intelligence reports and analytics data

#### Auto-Numbering Tables

-   `document_sequences`: Sequence tracking per document type, month, **and company entity**. Legacy columns (`document_type`, `year_month`, `last_sequence`) remain for backward compatibility while new fields (`company_entity_id`, `document_code`, `year`, `current_number`) enable the upcoming `EEYYDD99999` numbering format.
-   `cash_expenses`: Cash expense tracking with entity-aware numbering (code `11`) and creator attribution. **List UI** (`/cash-expenses`): server-side DataTables; **date range** filter via AdminLTE **daterangepicker** (presets + clear); `GET /cash-expenses/data` accepts `from` / `to` (`whereDate` on `ce.date`). Posted on create (no draft).
-   `asset_disposals`: Asset disposal transactions with automatic numbering (DIS-YYYYMM-######)

#### Document Closure System Tables

-   `erp_parameters`: System-wide configurable parameters with category-based organization (document_closure, system_settings, price_handling), parameter_key, parameter_name, parameter_value, data_type, description, is_active, and audit fields (created_by, updated_by, timestamps)

#### System Tables

-   `users`: User management with role integration and username field
-   `roles` / `permissions`: RBAC system (Spatie) with consolidated permissions

### Migration Consolidation (2025-01-15)

The database schema has been consolidated from 51 to 44 migration files for improved maintainability:

-   **Table Modifications Merged**: Column additions and foreign key constraints consolidated into original table creation migrations
-   **Foreign Key Dependencies**: Proper ordering established to resolve dependency conflicts
-   **Permissions Consolidated**: All permission additions merged into single migration file
-   **Schema Integrity**: All relationships and constraints preserved and verified through fresh migration testing

## API Design

### Customer Invoice API (machine clients)

-   **Purpose**: Read-only access to **`sales_invoices`** (and lines on detail) for the linked **active customer** business partner.
-   **Security**: Plain token shown once at creation; DB holds **`hash('sha256', token)`** only. Expiry optional (`expires_at`). Inactive/suspended partners or non-customer keys receive **401**.
-   **Deploy**: Requires migration **`customer_api_keys`** (`php artisan migrate`).

### Route Structure

-   **Web Routes**: Traditional Laravel web routes with middleware
-   **Permission-Based Access**: All routes protected with granular permissions
-   **RESTful Design**: Standard CRUD operations for all entities
-   **DataTables Integration**: AJAX endpoints for dynamic data loading with business_partners table integration

### Key Endpoints

-   `/dashboard`: Main dashboard with summary statistics
-   `/accounts/*`: Chart of accounts management
-   `/journals/*`: Journal entry management
-   `/account-statements/*`: Account statement generation and management for GL accounts and Business Partners
-   `/control-accounts/*`: Control account management with CRUD operations, reconciliation dashboard, and balance tracking
-   `/sales/dashboard`: Sales dashboard with AR aging analysis, sales KPIs, and comprehensive sales statistics
-   `/purchase/dashboard`: Purchase dashboard with AP aging analysis, purchase KPIs, and comprehensive purchase statistics
-   `/sales-invoices/*`: AR invoice management
-   `/delivery-orders/*`: Delivery order management with inventory reservation and revenue recognition
-   `/purchase-invoices/*`: AP invoice management
-   `/assets/*`: Fixed asset management
-   `/inventory/*`: Enhanced inventory management with CRUD operations, stock management, reports, price level management, and audit trails
-   `/product-categories/*`: Product category management with CRUD operations, account mapping, hierarchical support (parent-child relationships with account inheritance), tree/table view toggle, hierarchical display in dropdowns, and audit integration
-   `/warehouses/*`: Multi-warehouse management with CRUD operations, stock transfers, and warehouse-specific reporting
-   `/gr-gi/*`: GR/GI management with CRUD operations, approval workflow, journal integration, and account mapping
-   `/audit-logs/*`: System-wide audit trail management with filtering and search capabilities. Routes configured but views missing - see Phase 1 implementation plan. Future enhancements include activity dashboard (`/admin/activity-dashboard`), advanced filtering with saved presets, export/reporting, and inline widgets.
-   `/tax/*`: Indonesian tax compliance management with transactions, periods, reports, settings
-   `/cogs/*`: Cost of Goods Sold management with cost allocation, margin analysis, optimization
-   `/supplier-analytics/*`: Supplier performance analytics with comparisons, optimization opportunities
-   `/business-intelligence/*`: Business intelligence with reports, insights, KPI dashboard
-   `/analytics/*`: Unified analytics dashboard integrating all trading components
-   `/projects/*`: Project management with CRUD operations and SweetAlert2 integration
-   `/funds/*`: Fund management with CRUD operations and SweetAlert2 integration
-   `/departments/*`: Department management with CRUD operations and SweetAlert2 integration
-   `/erp-parameters/*`: ERP Parameters management with CRUD operations, category-based organization, and bulk updates
-   `/reports/open-items/*`: Open Items reporting with comprehensive document status monitoring, aging analysis, and Excel export
-   `/reports/document-creation-logs`: Document Creation Logs — merged list of PO, GRPO, PI, PP, SO, DO, SI, SR by `created_at` (permission `reports.open-items`)
-   `/reports/*`: Comprehensive reporting suite (permission `reports.view` unless noted). **Core financial statements** (see `App\Services\Reports\ReportService`, `App\Http\Controllers\Reports\ReportsController`, `routes/web/reports.php`):
    -   **Trial Balance** & **GL Detail**: Posted journals by default; `include_unposted=1` includes drafts. GL Detail includes **running balance** and account picker. Shared filters via `JournalReportQueryBuilder` (`period_year`/`period_month`, optional `company_entity_id`). Exports: `export=csv`, `export=pdf` (Dompdf views under `resources/views/reports/pdf/`).
    -   **Balance Sheet**: Asset / liability / `net_assets` only; hierarchical rows from COA `parent_id` with parent **rollup** (child sums). Uses `accounts.report_group` / `normal_balance` where set. JSON includes `totals.unclosed_pnl_cumulative` and `difference_vs_unclosed_pnl` (tie-out to cumulative P&amp;L in TB). UI/PDF corporate header (`entity_name` = `config('app.name')`). **Reference**: `docs/financial-statements-reports.md`.
    -   **AR/AP**: Aging and party balances share allocation-netted as-of logic; **Subledger Reconciliation** (`reports.subledger-reconciliation`) compares aging totals to GL control accounts (`control_type` `ar`/`ap`).
    -   **Cash Ledger**: Defaults to first postable account under `config('cash_flow.account_prefixes.cash_and_bank')` (typically `1.1.1.x`).
    -   **Profit & Loss**: Period P&amp;L by COA buckets (4 revenue, 5 COGS, 6 operating, 7 other); same hierarchy/rollup pattern as BS within each section.
    -   **Cash Flow (indirect)**: Starts from net income + depreciation add-back + working capital deltas from **balance sheet display** balances by **prefix** lists in `config/cash_flow.php` (`tax_payables`, `input_vat_prepaid_assets`, `short_term_borrowings`, `equity_financing_prefixes`, etc.). Financing excludes `3.3` retained earnings by default to avoid double-counting net income. `ReportService::balanceSheetDisplayTotalForPrefixes()` supports reconciliation tests.
-   `/admin/*`: User and role management (users, roles, permissions, assistant report, approval workflows)
-   `/admin/customers/{businessPartner}/api-keys`: Create/list/revoke **customer API keys** (customers only; **`business_partners.manage`** plus **`view-admin`**); powers external **`/api/v1/invoices`** clients — see **`docs/customer-invoice-api-reference.md`**

### API Endpoints

-   **`GET /api/v1/invoices`**, **`GET /api/v1/invoices/{invoice_no}`**: Customer-facing **sales invoice** JSON API (scoped to authenticated customer). Auth: **`Authorization: Bearer {token}`**; middleware **`customer.api`** (`CustomerApiAuthentication`); tokens stored as SHA-256 in **`customer_api_keys`**. Query filters on list: `status`, `date_from`, `date_to`, `per_page`. **`invoice_no`** route pattern: `[A-Za-z0-9._\-]+`. Full contract: **`docs/customer-invoice-api-reference.md`**.
-   `/api/menu/search`: Menu search API endpoint returning permission-filtered menu items for authenticated users with optional query parameter for server-side filtering
-   `/admin/approval-workflows/*`: Approval workflow management with CRUD operations, workflow step configuration, and threshold management

### In-app HELP (scoped assistant)

-   **Portable blueprint** (for other projects): `docs/HELP-CHATBOX-REFERENCE.md` — RAG chatbox architecture, API shape, `help:reindex`, env vars, UX notes (vs Domain Assistant).
-   **Routes** (authenticated, throttled): `POST /help/ask`, `POST /help/feedback`. **No** persistence of chat Q&A; only bug/feature submissions are stored (`help_feedback`).
-   **Knowledge**: Markdown manuals under `docs/manuals/` (chunked by `##` headings), plus `docs/manuals/help-navigation.json` for menu-path hints. Vector rows live in `help_embeddings` (JSON embedding column). Rebuild index: `php artisan help:reindex` (requires `OPENROUTER_API_KEY`). Maintainer index and authoring rules: `docs/manuals/README.md`.
-   **Manuals (high-signal additions for retrieval)**: `sales-invoice-manual-id.md` / `sales-invoice-manual-en.md` (includes **Sales Credit Memo** section), **`sales-receipt-manual-id.md`** / **`sales-receipt-manual-en.md`** (**Sales Receipt**: create, **edit draft**, post, `ar.receipts.*` permissions), `in-app-help-manual-id.md` / `in-app-help-manual-en.md`; `delivery-order-manual-id.md` (**Cancel** vs **Reverse delivery** vs partial shipment); **`sales-workflow-corrections-help-id.md`** / **`sales-workflow-corrections-help-en.md`** (chunked keywords: CM, reverse DO, Relationship Map, wrong **Company entity**); **`checklist-perbaikan-salah-entitas-so-id.md`** (operational checklist); **`inventory-fifo-repair-manual-en.md`** / **`inventory-fifo-repair-manual-id.md`** (FIFO layer self-service repair, GR/GI replay errors, link to legacy PI duplicate commands). **`help-navigation.json`**: entries `sales-credit-memos`, **`sales-receipts`** (draft edit paths), `document-relationship-map`, `company-entity-correction`, **`inventory-fifo-repair`**, **`legacy-inventory-data-repair`**, **`menu-search`**; expanded `delivery-orders` keywords for reverse; `inventory-valuation` cross-links FIFO repair.
-   **External calls**: [OpenRouter](https://openrouter.ai/) for embeddings + chat completion only; API key stays **server-side** (`config/services.php` → `openrouter.*`, `App\Services\Help\HelpOpenRouterClient`). **Timeouts**: `OPENROUTER_TIMEOUT` (default 240s), `OPENROUTER_CONNECT_TIMEOUT` (30s); embedding batches retry up to `OPENROUTER_EMBEDDING_RETRIES`; `HELP_REINDEX_BATCH_SIZE` (default 6) reduces payload size when `help:reindex` hits slow networks or cURL 28.
-   **Optional**: `HELP_FEEDBACK_NOTIFY_EMAIL` sends a plain-text email when feedback is submitted (failures are logged; DB row still created).
-   **UI**: Navbar launcher uses **`fas fa-book-open`** in a circular gradient badge (styles in `resources/views/layouts/partials/head.blade.php`; markup in `layouts/partials/navbar.blade.php`). Help modal: **`modal-dialog-scrollable` is not used** — the answer region `#help-answer` is the scroll container (`overflow-y: auto`, `min-height: 0`, touch-friendly) to avoid nested scroll issues; after each reply the script resets `scrollTop` and focuses the answer box for keyboard scroll.
-   **Views**: `resources/views/layouts/partials/help-panel.blade.php` (How-to + Report/request tabs, formatted answer HTML from client-side formatter).

```mermaid
flowchart LR
    subgraph help_ui [Browser]
        NB[Navbar book-open launcher]
        MOD[Help modal]
        ANS[Answer box scroll region]
    end
    subgraph help_api [Laravel]
        ASK["POST /help/ask"]
        FB["POST /help/feedback"]
        ASK --> SVC[HelpAssistantService]
        SVC --> DB[(help_embeddings)]
        SVC --> ORAPI[OpenRouter API]
        FB --> HF[(help_feedback)]
    end
    subgraph knowledge [Knowledge prep]
        MD["docs/manuals/*.md"]
        NAV[help-navigation.json]
        MD --> REINDEX[help:reindex]
        NAV --> REINDEX
        REINDEX --> DB
    end
    NB --> MOD
    MOD --> ASK
    MOD --> ANS
```

### Domain Assistant (live ERP tools + threads)

-   **Purpose**: Chat with OpenRouter **function calling** into `DomainAssistantDataService` (scoped Eloquent queries). **Not** the same as HELP (no manuals RAG; conversations are persisted).
-   **Permission**: `access-domain-assistant` (migrations `access_domain_assistant_permission` + `sync_domain_assistant_roles`; `RolePermissionSeeder`). Navbar: **`fas fa-robot`** → `GET /assistant` (distinct from HELP **`fas fa-book-open`**).
-   **Config**: `config/services.php` → `domain_assistant.*` (`DOMAIN_ASSISTANT_ENABLED`, `DOMAIN_ASSISTANT_MODEL`, daily limit, tools toggle). Requires `OPENROUTER_API_KEY` (shared with HELP embeddings/chat client registration).
-   **Routes** (auth + permission): `assistant/*` — threads CRUD, `POST /assistant/chat`. **Admin** (`view-admin`): `GET /admin/assistant-report` — reads `assistant_request_logs`.
-   **Tables**: `assistant_conversations`, `assistant_messages`, `assistant_request_logs`.
-   **Core classes**: `App\Services\Assistant\DomainAssistantService` (tool loop), `DomainAssistantDataService` (tools), `DomainAssistantOpenRouterClient` (singleton in `AppServiceProvider`), `AssistantConversationManager`. Route model binding: `{conversation}` scoped to `auth()->id()` in `AppServiceProvider::boot()`.
-   **Entity scoping (important)**: List/browse without an invoice number uses **`scopeCompanyEntity`** (default `company_entity_id` unless user has `see-all-record-switch` and sends `show_all_records`). **Sales Invoice** and **Purchase Invoice** lookup by **`invoice_query`** or **`get_*_invoice_detail`** use **`scopeActiveCompanyEntities`** (`whereIn` active `company_entities`) so invoices on non-default entities (e.g. PT vs CV) are not missed.
-   **Tools (representative)**: `get_erp_summary`, `search_sales_orders`, **`search_sales_invoices`**, **`get_sales_invoice_detail`** (header + lines from `sales_invoice_lines`), **`search_purchase_invoices`**, **`get_purchase_invoice_detail`** (header + lines from `purchase_invoice_lines`), `search_purchase_orders`, **`search_delivery_orders`** (supports **`do_number_query`** for DO/SJ by number across active entities), `search_goods_receipt_po`, `search_inventory_items`, `search_business_partners`.
-   **UI**: `resources/views/assistant/index.blade.php` — terminal / hacker skin (JetBrains Mono, green-on-black), scoped under `.assistant-terminal`.
-   **HELP knowledge**: User-facing how-to for Domain Assistant vs HELP lives in **`docs/manuals/domain-assistant-manual-en.md`** / **`domain-assistant-manual-id.md`**; **`help-navigation.json`** entry `domain-assistant`. After manual edits, run **`php artisan help:reindex`** (HELP pipeline only; Domain Assistant tools do not use `help_embeddings`).

```mermaid
flowchart LR
    subgraph da_ui [Browser]
        BOT[Navbar robot icon]
        PG[Assistant page]
    end
    subgraph da_api [Laravel]
        CHAT["POST /assistant/chat"]
        CHAT --> SVC[DomainAssistantService]
        SVC --> DATA[DomainAssistantDataService]
        DATA --> DBL[(ERP tables)]
        SVC --> OR2[OpenRouter chat+tools]
        CHAT --> LOG[(assistant_request_logs)]
        CHAT --> MSG[(assistant_messages)]
    end
    BOT --> PG
    PG --> CHAT
```

## Data Flow

```mermaid
graph TD
    A[User Input] --> B[Authentication Check]
    B --> C[Permission Validation]
    C --> D[Data Validation]
    D --> E[Business Logic Processing]
    E --> F[Document Numbering Service]
    F --> G[Posting Service]
    G --> H[Journal Creation]
    H --> I[Database Transaction]
    I --> J[Response Generation]
    J --> K[View Rendering]

    F --> L[Sequence Generation]
    L --> M[Month-Based Tracking]
    M --> N[Thread-Safe Locking]

    G --> O[Period Validation]
    O --> P[Balance Validation]
    P --> Q[Dimension Assignment]
    Q --> R[Control Account Balance Update]

    S[Sales Order] --> T[Delivery Order Creation]
    T --> U[Delivery Approval]
    U --> V[Stock Reduction + Inventory Reservation]
    V --> W[In Transit]
    W --> X[Mark as Delivered]
    X --> Y[Revenue Recognition]
    Y --> Z[Journal Entries]
    Z --> AA[Control Account Reconciliation]

    BB[Control Account Setup] --> CC[Subsidiary Ledger Creation]
    CC --> DD[Balance Initialization]
    DD --> EE[Reconciliation Dashboard]
```

## Security Implementation

### Authentication & Authorization

-   **Laravel Auth**: Standard authentication with session management
-   **Spatie Permission**: Role-based access control with granular permissions
-   **Middleware Protection**: All routes protected with appropriate middleware
-   **CSRF Protection**: Built-in CSRF token validation

### Permission System

-   **Granular Permissions**: 55+ specific permissions across all modules including Phase 4 analytics, Document Closure System, and GR/GI Management
-   **Role-Based Access**: Predefined roles (admin, manager, user) with custom roles
-   **Module-Level Security**: Each module has view/create/update/delete permissions
-   **Analytics Permissions**: COGS, supplier analytics, business intelligence, and unified analytics access control
-   **Document Closure Permissions**: manage-erp-parameters for ERP Parameters management, **reports.open-items** for Open Items reporting and **Document Creation Logs** (same permission)
-   **GR/GI Permissions**: gr-gi.view/create/update/delete/approve for comprehensive GR/GI management access control
-   **Approval Workflow Permissions**: admin.approval-workflows for approval workflow configuration and management
-   **Data-Level Security**: Dimension-based data access control

### Data Protection

-   **Input Validation**: Comprehensive validation rules for all inputs
-   **SQL Injection Prevention**: Eloquent ORM with parameterized queries
-   **XSS Protection**: Blade template escaping and input sanitization
-   **Session Security**: Secure session configuration with proper timeouts

### User Interface Enhancements

-   **SweetAlert2 Confirmation System**: Comprehensive confirmation dialog system for critical operations across entire ERP system
-   **Global JavaScript Handlers**: Event listeners for data-confirm attributes in forms and buttons with automatic prevention of default actions
-   **Professional Styling**: Consistent SweetAlert2 design with proper colors (#3085d6 for confirm, #d33 for cancel), question icons, and user-friendly button text
-   **Approval Workflow Integration**: Seamless integration with Purchase Order and Sales Order approval processes with proper confirmation dialogs
-   **Global Configuration**: Centralized SweetAlert2 configuration in public/js/sweetalert2-config.js with consistent styling and behavior
-   **AJAX Response Handling**: Proper JSON responses for all CRUD operations with comprehensive error handling
-   **DataTable Integration**: Dynamic data loading with search, sorting, and pagination capabilities
-   **Modal Management**: Consistent modal dialogs for create/edit operations with proper form validation
-   **Layout Standardization**: Consistent AdminLTE layout structure across all pages with proper breadcrumb navigation, card styling, and responsive design
-   **Enhanced Item Display**: Professional item information display with item codes in bold and item names in muted text for improved readability
-   **Workflow Optimization**: Improved user workflows with index page redirects after document creation for better user experience
-   **Purchase Order Edit System**: Comprehensive edit functionality with sophisticated JavaScript calculation engine, proper VAT/WTax handling, and consistent UI matching create page structure; init order guarantees `updateTotals()` runs without ReferenceError so **item search** and totals handlers always bind
-   **Advanced Calculation Engine**: Real-time calculation system with proper event handlers for quantity, price, VAT, WTax, line discount, and **header discount** (scaled payables); footer **Amount Due** matches scaled lines; empty Select2 tax values treated as zero percent
-   **Enhanced Form Validation**: Comprehensive form validation with proper error handling, date field mapping, and database field alignment
-   **SweetAlert2 Integration**: Professional confirmation dialogs for critical edit operations with proper user interaction handling
-   **GRPO Show Page Enhancements**: Comprehensive GRPO show page improvements including Base Document button enablement with proper document relationship initialization, PO link correction redirecting to PO show page instead of index page, Back to GRPO List button repositioning to right edge with float-right class, duplicate Preview Journal button removal keeping only one in document navigation section, DocumentNavigationController API fixes removing problematic cache service dependency, DocumentRelationshipService field mapping corrections for proper document number display, and comprehensive browser testing validation across all improvements

## Deployment

### Development Environment

-   **Laravel Sail**: Docker-based development environment
-   **Vite**: Asset compilation and hot reloading
-   **Queue Workers**: Background job processing
-   **Log Monitoring**: Laravel Pail for real-time log monitoring
-   **PHPUnit / feature tests**: `phpunit.xml` sets `DB_DATABASE` to a dedicated schema (e.g. `sarang_erp_test`) so tests using `RefreshDatabase` do not run `migrate:fresh` against the database named in `.env`. Create that database once on the same MySQL server. SQLite in-memory was not viable because at least one migration inspects MySQL `information_schema`.

### Production Considerations

-   **Database**: MySQL with proper indexing and optimization
-   **Caching**: Laravel cache system for performance
-   **Queue Processing**: Background job processing for PDF generation
-   **File Storage**: Local storage with potential for cloud integration
-   **Backup Strategy**: Database backup and recovery procedures

### Key Dependencies

-   **Laravel Framework**: 12.x with PHP 8.2+ requirement
-   **AdminLTE**: 3.14 for UI framework
-   **Spatie Permission**: 6.15 for RBAC
-   **DomPDF**: 2.0.8 for PDF generation
-   **Laravel Excel**: 1.1 for Excel export/import
-   **DataTables**: 12.x for dynamic table functionality

---

## Trading Company Modification Architecture

### Overview

The system has been analyzed for trading company (perusahaan dagang) operations with comprehensive modification recommendations documented in `docs/TRADING-COMPANY-MODIFICATION-PLAN.md`. This section outlines the architectural changes required for PSAK compliance and Indonesian tax regulations.

### Required Database Schema Extensions

#### New Tables for Trading Operations

```sql
-- Inventory Management
inventory_items: Product master data with trading-specific fields
inventory_transactions: Stock movement tracking with cost allocation; PI lines link via purchase_invoice_line_id for idempotency. All inventory-affecting documents create inventory transactions with reference_type and reference_id for audit trail.
inventory_valuations: Cost tracking with multiple valuation methods

-- Tax Compliance
tax_codes: PPN/PPh rate configuration
tax_transactions: Tax calculation tracking
tax_reports: Compliance reporting data

-- Trading Operations
supplier_performance: Vendor evaluation metrics
customer_credit_limits: Credit management
sales_commissions: Commission tracking
margin_analysis: Profitability tracking
```

#### Modified Chart of Accounts Structure

The current CoA requires complete restructuring for PSAK compliance:

-   **7 Main Categories**: Assets, Liabilities, Equity, Revenue, COGS, Operating Expenses, Other Income/Expenses
-   **Trading-Specific Accounts**: Inventory, COGS, Sales Returns, Purchase Discounts
-   **Tax Accounts**: PPN Masukan/Keluaran, PPh 21/22/23/25
-   **Indonesian Compliance**: Rupiah formatting, PSAK-standard reporting

### Required Controller Extensions

#### New Controllers

-   `InventoryController`: Stock management and valuation
-   `COGSController`: Cost of goods sold calculation
-   `PPNController`: VAT management and reporting
-   `PPhController`: Income tax automation
-   `MarginAnalysisController`: Profitability analysis
-   `TradingReportsController`: Trading-specific reporting

#### Enhanced Existing Controllers

-   `PurchaseOrderController`: Supplier comparison, freight tracking
-   `SalesOrderController`: Credit limits, commission tracking
-   `ReportsController`: PSAK-compliant financial statements

### Required Service Layer Additions

#### New Services

-   `InventoryService`: Stock valuation methods (FIFO, LIFO, Weighted Average)
-   `COGSService`: Automatic cost calculation and allocation
-   `TaxCalculationService`: PPN/PPh automation
-   `MarginAnalysisService`: Profitability calculations
-   `TradingReportService`: Compliance reporting

### Data Flow Modifications

```mermaid
graph TD
    A[Purchase Order] --> B[Inventory Receipt]
    B --> C[COGS Calculation]
    C --> D[Tax Calculation]
    D --> E[Journal Posting]
    E --> F[Inventory Update]

    G[Sales Order] --> H[Inventory Deduction]
    H --> I[COGS Recognition]
    I --> J[Revenue Recognition]
    J --> K[Tax Calculation]
    K --> L[Journal Posting]

    M[Tax Reporting] --> N[PPN/PPh Reports]
    N --> O[Compliance Dashboard]
```

### Security Considerations

#### New Permissions Required

-   `inventory.view/create/update/delete/adjust/transfer`: Enhanced inventory management with warehouse and price level support
-   `admin.view`: System-wide audit trail access
-   `cogs.view/calculate`: Cost of goods sold access
-   `tax.ppn.view/calculate/report`: VAT management
-   `tax.pph.view/calculate/report`: Income tax management
-   `margin.view/analyze`: Profitability analysis
-   `trading.reports.view`: Trading-specific reporting

#### Data Security

-   Inventory cost data protection
-   Tax calculation accuracy validation
-   Customer credit information security
-   Supplier performance data confidentiality

### Integration Points

#### External Systems

-   **E-Faktur**: Electronic invoice system integration
-   **Tax Authority APIs**: Automated tax reporting
-   **Banking Systems**: Payment processing integration
-   **Supplier Portals**: Automated purchase order processing

#### Internal Integrations

-   **Multi-dimensional Accounting**: Project/fund/department tracking
-   **Fixed Asset Management**: Equipment and depreciation
-   **User Management**: Role-based access control
-   **Reporting System**: Unified financial reporting

### Performance Considerations

#### Database Optimization

-   Inventory transaction indexing for real-time queries
-   Tax calculation caching for performance
-   Report generation optimization
-   Large dataset handling for inventory valuation

#### System Scalability

-   Inventory transaction volume handling
-   Concurrent user access for stock updates
-   Report generation under load
-   Tax calculation performance optimization

### Compliance Architecture

#### PSAK Compliance

-   Indonesian financial statement formats
-   Revenue recognition standards
-   Inventory valuation methods
-   Asset and liability classification

#### Tax Compliance

-   PPN (VAT) 11% calculation and reporting
-   PPh automation (21, 22, 23, 25)
-   Monthly tax reporting (SPT Masa)
-   Annual tax return preparation (SPT Tahunan)

### Deployment Considerations

#### Development Environment

-   Inventory data migration scripts
-   Tax configuration setup
-   PSAK-compliant reporting templates
-   User training materials

#### Production Deployment

-   Data migration from current system
-   Tax configuration validation
-   Compliance testing procedures
-   User acceptance testing protocols

## Document Navigation & Journal Preview System Architecture

### Overview

The Document Navigation & Journal Preview system provides comprehensive workflow visibility and accounting transparency across all document types in the ERP system. This system enables users to navigate between related documents, preview journal entries before execution, and visualize complete document workflows through interactive relationship maps.

### Core Components

#### Database Schema

**document_relationships Table**

-   Polymorphic relationship storage for document connections
-   Supports base/target document relationships across all document types
-   Automatic relationship initialization from existing data

**document_analytics Table**

-   Usage tracking and performance analytics
-   User behavior analysis and system optimization data
-   Comprehensive indexing for performance

#### Service Layer Architecture

**DocumentRelationshipService**

-   Core relationship management logic
-   Permission-based access control
-   Relationship initialization and maintenance
-   **Purchase chain sync (2026-04-20)**: Writes `document_relationships` when purchase documents are created or linked so **Relationship Map** and **Base/Target Document** buttons match the PO → GRPO → PI → PP workflow:
    -   `syncGoodsReceiptPORelationships()` — after GRPO create/copy (`GoodsReceiptPOController@store`, `GRPOCopyService`); PO ↔ GRPO edges when `purchase_order_id` is set.
    -   `syncPurchaseInvoiceRelationships()` — after PI create/copy (`PurchaseInvoiceController@store`, `PurchaseInvoiceCopyService`); upstream prefers **GRPO → PI** when `goods_receipt_id` is set, else **PO → PI** when `purchase_order_id` only; downstream **PI → PP** from `purchase_payment_allocations`.
    -   `syncPurchasePaymentRelationships()` — after PP store (`PurchasePaymentController@store`).
    -   `initializeExistingRelationships()` / `DocumentRelationshipSeeder`: correct morph classes `App\Models\Accounting\PurchaseInvoice` and `App\Models\Accounting\PurchasePayment`; removes legacy rows using `App\Models\PurchaseInvoice` / `App\Models\PurchasePayment`; adds **PI → PO (no GRPO)** backfill via `initializePIPurchaseOrderRelationships()`.
-   **API slug note**: `DocumentNavigationController` uses **singular** route keys (e.g. `purchase-invoice`, `goods-receipt-po`). `DocumentRelationshipController` (Relationship Map modal) uses **plural** keys (e.g. `purchase-invoices`, `goods-receipt-pos`). Blade show pages pass the slug expected by each endpoint.
-   **Purchase Order navigation card (2026-06-20)**: The `purchase_orders/show` page now embeds the shared `components.document-navigation` Base/Target navigation card (`documentType => 'purchase-order'`), completing Base/Target coverage across the full Purchase chain (PO → GRPO → PI → PP). Because a Purchase Order has no GL posting, the component is rendered with `showPreviewJournal => false`; this new optional flag (defaults `true`) suppresses the `PreviewJournalButton` for document types that `JournalPreviewController` does not support.

#### Create Target Document buttons (2026-06-20)

Show-page header toolbars expose **Create Target Document** actions for the next step in each chain. These are distinct from the Base/Target navigation card (which jumps to **already-created** linked documents).

| Source show page | Target | Route / pattern | Prefill |
|---|---|---|---|
| Sales Quotation | Sales Order | `sales-quotations.convert` → POST `convert-to-sales-order` | Service copies quotation → SO |
| Sales Quotation | Sales Invoice (skip chain) | `sales-invoices.create?quotation_id=` | `$prefill` in SI create |
| Sales Order | Delivery Order | `delivery-orders.create?sales_order_id=` | Form pre-select |
| Sales Order | Sales Invoice | `sales-orders.create-invoice` | `$prefill` + `$sales_order_id` |
| Delivery Order | Sales Invoice | `delivery-orders.create-invoice` → redirect `?delivery_order_id=` | `$prefill` in SI create |
| Sales Invoice | Sales Receipt | `sales-receipts.create?sales_invoice_id=` | `$prefill` allocations + JS auto-select |
| Sales Invoice | Credit Memo | `sales-credit-memos.create?sales_invoice_id=` | `$invoice` in create view |
| Purchase Order (item) | GRPO | `purchase-orders.show-copy-to-grpo` → POST copy | Copy service → draft GRPO |
| Purchase Order (service) | Purchase Invoice | `purchase-orders.show-copy-to-purchase-invoice` → GET execute | Copy service → draft PI |
| GRPO | Purchase Invoice | `goods-receipt-pos.create-invoice` | `$prefill` in PI create |
| Purchase Invoice | Purchase Payment | `purchase-payments.create?purchase_invoice_id=` | `$prefill` allocations + JS auto-select |

**Visibility guards (examples):** PI → PP requires posted, remaining balance, not cash/direct purchase (`$canCreatePayment`); SI → SR requires posted, remaining balance, not opening balance (`$canCreateReceipt`); SO → SI requires approved + ordered/confirmed/processing/delivered; GRPO → PI requires `status === 'received'`.

**Copy-service status fix:** `GRPOCopyService` and `PurchaseInvoiceCopyService` validate `status === 'ordered'` (not `approved`) to match PO approval workflow.

**Files:** `PurchaseInvoiceController@show`, `PurchasePaymentController@create`, `SalesInvoiceController@show`, `SalesReceiptController@create`, `purchase_invoices/show`, `sales_invoices/show`, `purchase_payments/create`, `sales_receipts/create`, `purchase_orders/copy_to_grpo`, `purchase_orders/copy_to_purchase_invoice`, `sales_quotations/convert`.

#### Open / Closed index filter (2026-06-20)

All eight workflow document index pages include a shared **All / Open / Closed** switch (`components.open-closed-filter`), defaulting to **Open**. Filtering uses live-computed completion via `App\Support\DocumentOpenState` (not stored `closure_status`):

| Document | Open = | Closed = |
|---|---|---|
| Sales Invoice | draft or posted with receipt balance > 0.01 | posted and fully receipted |
| Purchase Invoice | draft or posted with payment balance > 0.01 | posted and fully paid |
| Sales Order | lines with `delivered_qty < qty` (or no lines) | all lines fully delivered |
| Purchase Order | lines with `received_qty < qty` (or no lines) | all lines fully received |
| Delivery Order | undelivered/uninvoiced qty; excludes cancelled/reversed from open | cancelled/reversed or all delivered qty invoiced |
| GRPO | not linked to any Purchase Invoice | invoiced (pivot or legacy `goods_receipt_id`) |
| Sales Receipt / Purchase Payment | `status = draft` | `status = posted` |

AJAX param: `open_state` (`all|open|closed`). Wired in controller `data()` methods (SI, PI, SR, PP) and inline route closures in `routes/web/orders.php` (SO, PO, DO, GRPO). PO index replaced the old `closure_status` dropdown with this switch.

#### Cascade document deletion (2026-06-20)

Accounting-safe delete for all ten workflow document types. Two modes via split-button dropdown on each show page:

| Mode | Behavior |
|------|----------|
| **Delete this document only** (`mode=single`) | Deletes only the selected document after reversing its own journals/tax/inventory/allocations and reopening its base closure. **Blocked** when downstream (target) documents still exist. |
| **Delete with related documents** (`mode=cascade`, default) | Leaf-first cascade delete of the selected document and all targets in one transaction. |

```mermaid
flowchart TB
  subgraph engine [DocumentDeletionService]
    previewCascade[previewCascade]
    previewSingle[previewSingle]
    deleteSingle[deleteSingle]
    assert[assertDeletable]
    deleteCascade[delete cascade leaf-first]
  end
  graph[DocumentDeletionGraph]
  handlers[Per-type Handlers]
  support[DocumentDeletionSupport]
  previewCascade --> graph
  previewSingle --> graph
  deleteSingle --> assert
  deleteSingle --> handlers
  deleteCascade --> assert
  deleteCascade --> handlers
  handlers --> support
```

**Core files:** `app/Services/Documents/DocumentDeletionService.php`, `DocumentDeletionGraph.php`, `DocumentDescriptor.php`, `Support/DocumentDeletionSupport.php`, `Handlers/*DeletionHandler.php`, `app/Http/Controllers/Concerns/HandlesDocumentDeletion.php`, `resources/views/components/document-delete-button.blade.php`.

**Discovery:** `DocumentDeletionGraph` unions FKs, pivots, and allocation tables (not only `document_relationships`): SQ→SO (`converted_to_sales_order_id`), SO→DO, DO→SI (`delivery_order_sales_invoice`), SI→SCM/SR, PO→GRPO, GRPO→PI, PI→PP. `descendants()` returns target chain only (used to block single delete). Shared receipts/payments that allocate to invoices outside the delete set are blocked.

**Reversal policy:** Posted documents call `PostingService::reverseJournal()` (offsetting entries; `skip_postable_validation` on reversals for legacy non-postable header accounts). Tax rows deleted by `reference_type`. Inventory reversed via compensating `adjustment` transactions + `updateItemValuation()`. GRPO uses `GRPOJournalService::reverseJournalEntries()` plus inventory reversal. PI unpost logic extracted to `PurchaseInvoiceUnpostService` (also used by delete + existing unpost route). DO delete uses `DeliveryService::reverseDeliveryOrder()` or `cancelDeliveryOrder()` then hard-deletes the row.

**Closed period guard:** Delete blocked when the current period is closed (reversals post today) or when any document/journal date in the cascade falls in a closed period.

**Permissions:** `ar.invoices.delete`, `ar.receipts.delete`, `ar.credit-memos.delete`, `ap.invoices.delete`, `ap.payments.delete`, `goods-receipt-pos.delete`, `delivery-orders.delete` (+ existing `sales-orders.delete`, `purchase-orders.delete`, `ar.quotations.delete`). Migration `2026_06_20_192548_add_document_delete_permissions.php`.

**Routes:** `{resource}.delete-preview?mode=single|cascade` (GET JSON) + `{resource}.destroy` with `mode` body param (DELETE) on all ten controllers. Tests: `tests/Feature/DocumentDeletionTest.php`.

**DocumentRelationshipCacheService**

-   Intelligent caching with TTL management
-   Cache invalidation on document changes
-   Performance optimization through caching

**DocumentBulkOperationService**

-   Bulk document processing capabilities
-   Workflow chain analysis
-   Document statistics and analytics

**DocumentPerformanceOptimizationService**

-   Query optimization and eager loading
-   Database performance monitoring
-   Memory usage optimization

**DocumentAnalyticsService**

-   Comprehensive usage tracking
-   Performance metrics collection
-   Analytics report generation

#### API Architecture

**DocumentNavigationController**

-   RESTful API endpoints for navigation data
-   Base/target document retrieval
-   Permission-based access control

**DocumentRelationshipController**

-   Relationship map data API endpoints
-   Mermaid.js compatible graph generation
-   Document workflow visualization
-   Comprehensive relationship data formatting
-   **Relationship map (sales documents):** `GET /api/documents/{type}/{id}/relationship-map` uses **`DocumentRelationshipService::expandSalesRelationshipMapGraph()`** when the root is a sales-chain document (SO, DO, SI, SR, CM, SQ): **BFS** over `document_relationships` (direct queries, permission-filtered) up to depth 8, then **enrichment** from FKs/pivots (DO `sales_order_id` and `salesInvoices`; SI `sales_order_id`, `deliveryOrders`, `creditMemo`, `sales_receipt_allocations`, `sales_invoice_grpo_combinations` + PO→GRPO; SQ `converted_to_sales_order_id`; CM `sales_invoice_id`). **`?legacy_map=1`** restores the former **single-hop** graph from `getNavigationData()` only. Node ids are **type-prefixed** (e.g. `doc_SI_12`) for Mermaid. Purchase documents still use the one-hop builder; see **`docs/action-plans/relationship-map-complete-sales-chain.md`** for future polish.

**JournalPreviewController**

-   Journal entry preview functionality
-   Action simulation without persistence
-   Comprehensive error handling
-   **Single source of truth (2026-06-20)**: `getJournalPreview()` dispatches to document-specific builders under `app/Services/Accounting/JournalBuilders/` and formats output via `JournalPreviewPresenter` (account code/name enrichment, totals, balance check). Posting controllers/services call the **same builders** before `PostingService::postJournal()` — preview and posted `journal_lines` stay aligned.
-   **Supported preview types**: `goods-receipt-po` (`GrpoJournalBuilder`), `purchase-invoice` (`PurchaseInvoiceJournalBuilder` — credit/direct-cash/opening-balance/PPN/withholding), `purchase-payment` (`PurchasePaymentJournalBuilder` + `CashJournalLineBuilder`), `delivery-order` (`DeliveryOrderJournalBuilder::buildRevenueRecognition` only; reservation journal at DO approval is out of scope), `sales-invoice` (`SalesInvoiceJournalBuilder`), `sales-receipt` (`SalesReceiptJournalBuilder`). Unsaved GRPO create form still uses `grpoPreview()` (form-data estimate).

**DocumentAnalyticsController**

-   Analytics data collection and retrieval
-   Performance metrics API
-   Report generation endpoints

#### Frontend Architecture

**AdvancedDocumentNavigation.js**

-   Sophisticated JavaScript component
-   Client-side caching and error handling
-   Keyboard shortcuts and tooltips
-   Real-time UI updates

**DocumentNavigationButtons.js**

-   Base/Target document navigation
-   Dropdown support for multiple documents
-   Professional AdminLTE integration

**PreviewJournalButton.js**

-   Journal preview functionality
-   Modal-based display
-   Professional formatting and validation

**Relationship Map Modal Component**

-   Mermaid.js flowchart visualization
-   Professional AdminLTE modal interface
-   **Document Workflow** diagram nodes: each box shows **document type** (e.g. Sales Order, Delivery Order), **number**, **date**, **Status:** line (not the former “N/A” reference placeholder), **amount** in IDR; **Ref:** only when a customer/vendor reference exists (`DocumentRelationshipController` + `relationship-map-modal.blade.php` `generateMermaidDefinition`)
-   Relationship summary with base/target document counts; list cards use **`type_label`** from `DocumentRelationshipService::labelForMorphClass()`
-   Interactive zoom controls and graph navigation
-   Clickable document nodes for direct navigation
-   Comprehensive error handling and loading states

**Menu Search Component (menu-search.js)**

-   jQuery-based autocomplete search functionality
-   Real-time menu item filtering with 300ms debounce
-   Keyboard navigation support (Arrow keys, Enter, Escape)
-   Click-to-select and keyboard-to-select navigation
-   Text highlighting for matching search terms
-   Permission-aware menu item display
-   AdminLTE-styled dropdown results with breadcrumb paths
-   Keyboard shortcut support (Ctrl+K / Cmd+K to focus)

### Menu Search System Architecture

The Menu Search System provides global search functionality for navigating menu items across the ERP system, enabling users to quickly find and access any menu item without manually navigating through the sidebar hierarchy.

#### Service Layer Architecture

**MenuSearchService**

-   Permission-aware menu structure builder
-   Extracts menu metadata (title, route, icon, category, breadcrumb, keywords)
-   Filters menu items based on user permissions using Spatie Permission
-   Generates searchable menu item array with hierarchical structure preservation
-   Covers all menu sections: Dashboard, MAIN (Inventory, Purchase, Sales, Fixed Assets, Business Partner, Accounting, Master Data), Reports, and Admin — including **FIFO Layer Repair** (`inventory.adjust`) and **Domain Assistant** (`access-domain-assistant`)

#### API Architecture

**MenuSearchController**

-   RESTful API endpoint: `GET /api/menu/search`
-   Returns JSON array of accessible menu items for authenticated user
-   Caches menu structure per user/permission combination (1-hour TTL)
-   Supports optional query parameter for server-side filtering
-   Permission-based access control ensuring users only see accessible menu items

#### Frontend Architecture

**Menu Search Component (menu-search.js)**

-   jQuery-based autocomplete component integrated with AdminLTE
-   Debounced search input (300ms delay) for performance
-   Client-side filtering of cached menu items
-   Keyboard navigation (ArrowDown/ArrowUp for selection, Enter for navigation, Escape to close)
-   Click-to-select functionality
-   Text highlighting in search results
-   Responsive design (hidden on mobile, visible on tablet/desktop)
-   Keyboard shortcut: Ctrl+K (or Cmd+K on Mac) to focus search input

#### Data Flow Architecture

**Menu Search Flow**

1. **Initialization**: Component loads menu items from `/api/menu/search` endpoint on page load
2. **Caching**: Menu items cached client-side for fast filtering
3. **User Input**: User types in search input, triggering debounced search
4. **Filtering**: Client-side filtering matches search query against menu item titles, breadcrumbs, and keywords
5. **Results Display**: Filtered results displayed in dropdown with icons, titles, and breadcrumb paths
6. **Navigation**: User selects item via click or Enter key, navigating to selected route
7. **Permission Filtering**: Server-side filtering ensures only accessible menu items are returned

### Data Flow Architecture

#### Document Relationship Flow

1. **Relationship persistence**: Sales documents still create links from controllers/services (e.g. DO → SI). Purchase documents now **sync** `document_relationships` on create/allocate as listed under `DocumentRelationshipService` above; legacy environments run `php artisan db:seed --class=DocumentRelationshipSeeder` once to backfill.
2. **Permission Filtering**: User-based access control for document visibility (`*.view` per morph class); empty filtered lists leave Base/Target buttons **disabled**.
3. **Caching Layer**: Per-document cache keys in `DocumentRelationshipService`; `clearDocumentCache()` invoked after purchase syncs.
4. **API Response**: `GET /api/documents/{type}/{id}/navigation` (navigation) and `GET /api/documents/{type}/{id}/relationship-map` (map) — **type string differs** between the two controllers for the same business document; see slug note above.

#### Relationship Map Flow

1. **Document Selection**: User clicks Relationship Map button on any document show page
2. **API Request**: Frontend calls `/api/documents/{documentType}/{documentId}/relationship-map`
3. **Data Retrieval**: DocumentRelationshipController uses `expandSalesRelationshipMapGraph()` for sales roots, else `getNavigationData()` one-hop bases/targets (`?legacy_map=1` forces the latter for sales)
4. **Graph Generation**: Mermaid.js compatible graph definition created with document nodes and relationships
5. **Modal Display**: Professional AdminLTE modal renders with document info, relationship summary, and interactive flowchart
6. **User Interaction**: Users can zoom, navigate, and click on document nodes for direct navigation

#### Journal Preview Flow

1. **Builder dispatch**: `JournalPreviewController` loads the document and calls the matching `JournalBuilders\*` class (same class the post action uses).
2. **JournalDraft DTO**: Builder returns `description`, normalized `lines` (`account_id`, `debit`, `credit`, `memo`, dimensions) — **no** journal insert, status change, or tax sync.
3. **Presentation**: `JournalPreviewPresenter` enriches lines with `account_code` / `account_name`, computes `total_debit`, `total_credit`, `is_balanced`.
4. **Preview Display**: Professional modal presentation via `PreviewJournalButton.js`.
5. **Posting path**: Controller/service calls the same builder, then `PostingService::postJournal()` plus document status/tax side effects.

```mermaid
flowchart LR
  builder[JournalBuilder build -> JournalDraft]
  post[Controller/Service post] --> builder
  prev[JournalPreviewController] --> builder
  builder --> postJournal[PostingService::postJournal]
  builder --> present[JournalPreviewPresenter -> modal]
```

#### Analytics Flow

1. **Usage Tracking**: Real-time user interaction monitoring
2. **Performance Metrics**: System performance data collection
3. **Data Aggregation**: Analytics processing and storage
4. **Report Generation**: Comprehensive analytics reporting

### Performance Architecture

#### Caching Strategy

-   **Document Relationships**: 1-hour TTL with automatic invalidation
-   **Query Results**: 30-minute TTL for database queries
-   **Client-side Caching**: 5-minute TTL for UI responsiveness
-   **Cache Warming**: Pre-loading of frequently accessed documents

#### Database Optimization

-   **Eager Loading**: Optimized relationship loading
-   **Query Caching**: Database query result caching
-   **Index Optimization**: Performance-optimized database indexes
-   **Batch Processing**: Efficient bulk operations

#### Memory Management

-   **Memory Usage Monitoring**: Real-time memory tracking
-   **Optimization Recommendations**: Automated performance suggestions
-   **Resource Management**: Efficient memory allocation

### Security Architecture

#### Authentication & Authorization

-   **Session-based Authentication**: Web application security
-   **Permission-based Access**: Granular document access control
-   **User Context**: User-specific data filtering

#### Data Protection

-   **Input Validation**: Comprehensive data validation
-   **SQL Injection Prevention**: Parameterized queries
-   **XSS Protection**: Output sanitization

### Integration Architecture

#### ERP System Integration

-   **Seamless Integration**: Native ERP system integration
-   **AdminLTE Compatibility**: Professional UI consistency
-   **Permission System**: Integration with existing RBAC

#### API Integration

-   **RESTful Design**: Standard API patterns
-   **JSON Responses**: Structured data delivery
-   **Error Handling**: Comprehensive error management

### Scalability Considerations

#### Horizontal Scaling

-   **Cache Distribution**: Distributed caching support
-   **Database Scaling**: Optimized for database scaling
-   **Load Balancing**: API endpoint load balancing

#### Performance Monitoring

-   **Real-time Metrics**: Live performance monitoring
-   **Analytics Dashboard**: Comprehensive performance insights
-   **Optimization Recommendations**: Automated performance suggestions

### Deployment Architecture

#### Development Environment

-   **Local Development**: Complete local setup
-   **Testing Framework**: Comprehensive testing capabilities
-   **Debug Tools**: Advanced debugging features

#### Production Deployment

-   **Cache Configuration**: Production cache setup
-   **Performance Monitoring**: Production performance tracking
-   **Analytics Collection**: Production analytics setup
