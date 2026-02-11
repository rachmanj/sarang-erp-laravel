**Purpose**: Record technical decisions and rationale for future reference
**Last Updated**: 2026-02-09 (Added Delivery Order Inventory Reduction decision)

# Technical Decision Records

## Decision Template

Decision: [Title] - [YYYY-MM-DD]

**Context**: [What situation led to this decision?]

**Options Considered**:

1. **Option A**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]
2. **Option B**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]

**Decision**: [What we chose]

**Rationale**: [Why we chose this option]

**Implementation**: [How this affects the codebase]

**Review Date**: [When to revisit this decision]

---

## Recent Decisions

### Decision: Delivery Order Inventory Reduction at Pick vs Delivery - 2026-02-09

**Context**: Delivery Order lines had Picked Qty and Delivered Qty but inventory stock (current_stock) was not reduced. Goods physically left the warehouse but remained "available" in the system, risking overselling. The system needed to decide when to reduce inventory: at picking time or at delivery completion.

**Options Considered**:

1. **Option A**: Reduce at Delivery completion only - Create inventory sale transactions when Delivered Qty is set and DO is completed.
    - ✅ Pros: Single point of reduction, aligns with revenue recognition timing
    - ❌ Cons: Between pick and delivery, goods are in transit but still show as available—can be allocated to another order (overselling)

2. **Option B**: Reduce at Pick time - Create inventory sale transactions when Picked Qty is updated.
    - ✅ Pros: Stock reflects warehouse reality immediately; picked goods are no longer on shelf; prevents double-selling during in-transit period
    - ❌ Cons: If user skips picking and goes straight to Delivered Qty, need fallback logic

3. **Option C**: Unified reduction at both pick and delivery - Use `should_reduce = max(picked_qty, delivered_qty)` and delta-based idempotent logic.
    - ✅ Pros: Handles pick-first flow (reduce at pick), skip-pick flow (reduce at delivery), partial updates; delta-based so no double-counting
    - ❌ Cons: Slightly more complex logic

**Decision**: Adopt Option C—reduce at Picked Qty update (primary) and Delivered Qty update (fallback for skip-pick), using unified logic `should_reduce = max(picked_qty, delivered_qty)`, `already_reduced` from transactions, `delta = should_reduce - already_reduced`. Apply in both `updatePickingStatus` and `updateDeliveryStatus`. Reverse via `processAdjustmentTransaction` when DO is cancelled.

**Rationale**:
- Stock must reflect warehouse reality: picked goods have physically left the shelf
- In-transit stock must not appear "available" or it can be sold again
- Skip-pick workflows (user sets Delivered Qty without Picked Qty) need fallback
- Delta-based approach is idempotent for partial updates and retries

**Implementation**:
- **DeliveryService**: Injected InventoryService; added private `ensureInventoryReduction(DeliveryOrderLine $line)`; called from `updatePickingStatus` and `updateDeliveryStatus` after line update; reversal in `cancelDeliveryOrder` for lines with picked_qty > 0
- **Backfill Command**: `php artisan delivery-orders:backfill-inventory-transactions` with `--dry-run`; registered in `app/Console/Kernel.php`
- **Reference**: `reference_type = 'delivery_order_line'`, `reference_id = line.id`

**Review Date**: 2027-02-09 (after full year of production use to assess reduction timing and backfill effectiveness).

---

### Decision: Sales Order Approval Workflow Fix & Auto-Recovery Mechanism - 2026-02-09

**Context**: Sales Orders with `approval_status = 'pending'` could not be approved because approval workflow records were missing from `sales_order_approvals` table. This occurred when Sales Orders were created before the approval workflow system was implemented, or when approval workflow creation failed silently. Additionally, users needed better inventory item information display on Sales Order show pages.

**Options Considered**:

1. **Option A**: Manual database fixes only - Require administrators to manually create approval records via SQL or database tools.
    - ✅ Pros: No code changes required, simple approach
    - ❌ Cons: Requires technical knowledge, error-prone, doesn't prevent future issues, poor user experience, no UI improvements

2. **Option B**: Auto-recovery mechanism in service layer + commands + UI enhancements - Automatically create missing approval records during approval attempt, provide commands for bulk fixes, enhance UI with inventory item information.
    - ✅ Pros: Prevents approval failures, self-healing system, user-friendly commands, better UI, production-ready solution
    - ❌ Cons: Requires code changes, additional complexity

**Decision**: Adopt Option B—implement auto-recovery mechanism in `SalesService::approveSalesOrder()`, create artisan commands for bulk fixes and role management, add fix route for ad-hoc fixes, and enhance Sales Order show page with Item Code and Item Name columns.

**Rationale**:
- Auto-recovery mechanism prevents data inconsistencies from blocking business processes
- Commands provide administrators with tools to fix existing data issues
- Dual role system (Spatie Permission + user_roles) requires synchronization commands
- UI enhancements improve user experience by displaying inventory item information directly from relationships
- Eager loading relationships improves performance and ensures data availability
- Fix route provides flexibility for ad-hoc fixes without command-line access

**Implementation**:
- **Service Layer**: Modified `SalesService::approveSalesOrder()` to check for missing approval records and automatically create them using `createApprovalWorkflow()` method
- **Commands**: Created `FixSalesOrderApproval` command (`php artisan sales-order:fix-approval {orderNo|--all}`) and `EnsureOfficerRole` command (`php artisan role:ensure-officer [--create-spatie] [--user=] [--list]`)
- **Controller**: Updated `SalesOrderController::show()` to eager load `inventoryItem` relationship
- **View**: Enhanced `resources/views/sales_orders/show.blade.php` to display Item Code (from `inventory_items.code`) and Item Name (from `inventory_items.name`) columns
- **Routes**: Added fix route `/sales-orders/fix-approval/{orderNo}` in `routes/web/orders.php`
- **Kernel**: Updated `App\Console\Kernel` to explicitly register commands in `$commands` array for Laravel 11+ compatibility
- **Documentation**: Created `PRODUCTION_DEPLOYMENT.md` with comprehensive deployment guide

**Review Date**: 2027-02-09 (after full year of production use to assess auto-recovery effectiveness and UI improvements).

### Decision: Menu Search Bar Implementation - 2026-02-04

**Context**: Users needed a faster way to navigate to menu items in the ERP system. With 50+ menu items across multiple hierarchical sections (Dashboard, MAIN, Reports, Admin), finding specific features required multiple clicks and menu expansions, reducing productivity and user experience. The sidebar navigation, while comprehensive, became cumbersome for power users who frequently switch between different modules.

**Options Considered**:

1. **Option A**: Client-side only approach - Generate menu structure server-side in Blade and pass as JavaScript variable.

    - ✅ Pros: Simpler implementation, no API endpoint needed, faster initial load for small menu sets.
    - ❌ Cons: Larger initial page load, less dynamic, menu changes require page reload, harder to maintain menu structure in JavaScript.

2. **Option B**: API-based approach with server-side permission filtering and client-side caching.

    - ✅ Pros: Dynamic menu loading, permission-aware filtering, better performance with caching, easier to maintain menu structure in PHP service, supports future enhancements (recent searches, favorites).
    - ❌ Cons: Requires API endpoint, additional service layer, slightly more complex architecture.

**Decision**: Adopt Option B—API-based menu search with server-side permission filtering, client-side caching, and jQuery-based autocomplete component.

**Rationale**:

-   API approach provides better separation of concerns—menu structure logic stays in PHP service layer.
-   Server-side permission filtering ensures security—users only receive accessible menu items.
-   Caching per user/permission combination optimizes performance (1-hour TTL).
-   Client-side filtering provides instant search results without additional API calls.
-   Easier to maintain—menu structure changes only require updating MenuSearchService.
-   Scalable architecture supports future enhancements (recent searches, favorites, analytics).
-   Follows established patterns in the codebase (similar to Document Navigation API).
-   Permission-aware filtering maintains security while improving discoverability.

**Implementation**:

-   **Service Layer**: Created `MenuSearchService` to build permission-aware menu structure programmatically, respecting Spatie Permission checks (`can()`, `hasAnyPermission()`).
-   **API Controller**: Created `MenuSearchController` with `GET /api/menu/search` endpoint, user/permission-based caching (1-hour TTL), and optional query parameter for server-side filtering.
-   **Frontend Component**: Created `menu-search.js` jQuery-based autocomplete with debounced search (300ms), keyboard navigation (Arrow keys, Enter, Escape), click-to-select, text highlighting, and AdminLTE-styled dropdown.
-   **UI Integration**: Added search bar to navbar (`navbar.blade.php`) with white background for visibility, "Search Menu here" label, and responsive design (hidden on mobile).
-   **Styling**: Created `menu-search.css` with AdminLTE-compatible styling, proper z-index for dropdown, and mobile-responsive adjustments.
-   **Routes**: Added API route in `routes/api.php` under `['web', 'auth']` middleware group.
-   **Keyboard Shortcut**: Implemented Ctrl+K (Cmd+K on Mac) to focus search input for power users.

**Review Date**: 2027-02-04 (after full year of production use to assess usage patterns and potential enhancements).

### Decision: Approval Workflow Admin UI Implementation - 2025-01-22

**Context**: Approval workflows were previously configured only through database seeders and direct database manipulation, making it difficult for administrators to manage workflows, steps, and thresholds without technical knowledge. Users needed a user-friendly interface to configure approval workflows for different document types and amount ranges.

**Options Considered**:

1. **Option A**: Continue using database seeders and direct database manipulation only.

    - ✅ Pros: No UI development required, maintains current workflow.
    - ❌ Cons: Requires technical knowledge, error-prone, no validation, difficult to maintain, poor user experience.

2. **Option B**: Create comprehensive admin UI for approval workflow management.
    - ✅ Pros: User-friendly interface, validation, error prevention, better maintainability, professional admin experience, threshold overlap validation.
    - ❌ Cons: Requires UI development, additional routes and controllers.

**Decision**: Adopt Option B—create comprehensive admin UI for approval workflow management with full CRUD operations, workflow step configuration, and threshold management.

**Rationale**:

-   User-friendly interface enables non-technical administrators to configure workflows.
-   Validation prevents configuration errors (overlapping thresholds, invalid step orders).
-   Better maintainability with centralized workflow management.
-   Professional admin experience consistent with other ERP admin features.
-   Threshold overlap validation ensures data integrity.
-   Dynamic workflow step management provides flexibility.
-   Modal-based threshold management improves UX.

**Implementation**:

-   **Controller**: Created `ApprovalWorkflowController` with full CRUD operations (index, create, store, show, edit, update, destroy) and threshold management methods (storeThreshold, updateThreshold, destroyThreshold).
-   **Views**: Created comprehensive AdminLTE views (index with DataTables, create, show, edit) with modal-based threshold management, dynamic workflow step addition/removal, and professional UI styling.
-   **Routes**: Added routes in `routes/web/admin.php` with proper middleware and permissions (`admin.approval-workflows`).
-   **Validation**: Implemented threshold overlap validation to prevent conflicting amount ranges, workflow step validation for required fields and proper ordering.
-   **Service Integration**: Leveraged existing `ApprovalWorkflowService` for workflow creation logic, ensuring consistency with automatic workflow generation in PurchaseService and SalesService.
-   **Database Schema**: Utilized existing `approval_workflows`, `approval_workflow_steps`, and `approval_thresholds` tables with proper relationships.
-   **Default Configuration**: `ApprovalWorkflowSeeder` continues to provide default workflows and thresholds for system initialization.

**Review Date**: 2026-01-22 (after full year of production use with admin UI).

### Decision: GR/GI Journal Entry Integration via PostingService - 2025-12-30

**Context**: GR/GI system initially created journal entries directly using `Journal` and `JournalLine` models, which caused schema mismatches (missing `status` field) and bypassed centralized accounting logic. The system needed to leverage existing PostingService for consistent journal creation, entity resolution, currency handling, and control account balance updates.

**Options Considered**:

1. **Option A**: Continue direct journal creation, fix schema issues.

    - ✅ Pros: Minimal code changes, direct control.
    - ❌ Cons: Duplicates logic, bypasses PostingService features, inconsistent with other modules, maintenance overhead.

2. **Option B**: Refactor GRGIService to use PostingService for journal creation.
    - ✅ Pros: Consistent with other modules, leverages existing infrastructure, automatic entity/currency handling, control account updates, single source of truth.
    - ❌ Cons: Requires refactoring existing code, need to understand PostingService API.

**Decision**: Adopt Option B—refactor GRGIService to use PostingService for all journal entry creation.

**Rationale**:

-   PostingService provides centralized accounting logic used by all other modules (Purchase Invoice, Sales Invoice, etc.).
-   Automatic entity resolution ensures proper company entity context for multi-entity environments.
-   Currency handling and exchange rate management handled automatically.
-   Control account balance updates integrated seamlessly.
-   Consistent journal schema and validation across all document types.
-   Single source of truth for accounting logic reduces maintenance burden.
-   Follows established architectural patterns in the codebase.

**Implementation**:

-   **Service Refactoring**: Updated `GRGIService::createJournalEntry()` to use `PostingService::postJournal()` instead of direct `Journal`/`JournalLine` creation.
-   **Model Namespace Fix**: Corrected `Journal` and `JournalLine` imports from `App\Models\Journal` to `App\Models\Accounting\Journal` in both `GRGIService.php` and `GRGIJournalEntry.php`.
-   **Relationship Fix**: Changed relationship name from `productCategory` to `category` in `GRGIService` to match `InventoryItem` model relationship.
-   **Payload Structure**: Created proper payload structure for PostingService with date, description, source_type, source_id, posted_by, and lines array with account_id, debit, credit, and memo.
-   **Account Mapping Logic**: Maintained existing account mapping logic (GR: debit=item category auto, credit=manual; GI: debit=manual, credit=item category auto) while leveraging PostingService for execution.
-   **Dependency Injection**: Added `PostingService` to `GRGIService` constructor for proper dependency injection.
-   **Seeder Execution**: Ran `GRGIPurposeSeeder` and `GRGIAccountMappingSeeder` to ensure required master data exists.

**Review Date**: 2026-12-30 (after full year of production use with PostingService integration).

### Decision: Business Partner Default Currency Assignment - 2025-12-24

**Context**: Business partners were being created without a default currency (`default_currency_id` was null), violating business rules that require all partners to have a default currency for financial transactions. Additionally, the BusinessPartnerService was eagerly loading relationships (purchaseOrders, salesOrders, purchaseInvoices, salesInvoices) even when the corresponding database tables didn't exist, causing QueryException errors when viewing business partner details.

**Options Considered**:

1. **Option A**: Require explicit currency selection during business partner creation.

    - ✅ Pros: Explicit user control, clear data entry requirement.
    - ❌ Cons: User burden, potential for missing currency assignment, data integrity risk.

2. **Option B**: Automatically assign base currency (IDR) as default when not provided, with conditional relationship loading.

    - ✅ Pros: Ensures data integrity, prevents null currency issues, graceful handling of missing tables, better user experience.
    - ❌ Cons: Requires service layer logic, conditional loading complexity.

3. **Option C**: Use database default constraint for currency assignment.
    - ✅ Pros: Database-level enforcement, simple implementation.
    - ❌ Cons: Less flexible, harder to change base currency, doesn't handle relationship loading issues.

**Decision**: Adopt Option B—automatically assign base currency (IDR) as default in BusinessPartnerService when `default_currency_id` is not provided, and implement conditional relationship loading using Schema::hasTable() checks.

**Rationale**:

-   Automatic base currency assignment ensures data integrity without user burden.
-   Base currency (IDR) is appropriate default for Indonesian businesses.
-   Conditional relationship loading prevents errors during schema evolution or partial migrations.
-   Service layer assignment allows for future flexibility (e.g., entity-specific default currencies).
-   Defensive programming approach ensures system remains functional even when schema is incomplete.
-   Blade views should verify both table existence and relationship loading status before accessing data.

**Implementation**:

-   **Model Update**: Added `default_currency_id` to BusinessPartner model `$fillable` array.
-   **Service Layer**: Modified `BusinessPartnerService::createBusinessPartner()` to automatically assign base currency using `Currency::getBaseCurrency()` when `default_currency_id` is not provided.
-   **Update Logic**: Enhanced `BusinessPartnerService::updateBusinessPartner()` to set base currency if `default_currency_id` is null during updates.
-   **Conditional Loading**: Updated `BusinessPartnerService::getBusinessPartnerWithDetails()` to conditionally eager load relationships only if their corresponding tables exist using `Schema::hasTable()` checks.
-   **View Protection**: Modified `resources/views/business_partners/show.blade.php` to check both table existence (`Schema::hasTable()`) and relationship loading status (`relationLoaded()`) before accessing relationship data.
-   **Data Migration**: Updated existing business partners to have `default_currency_id = 1` (IDR).

**Review Date**: 2026-12-24 (after full year of production use with automatic currency assignment).

### Decision: DataTable Pattern Consistency Standardization - 2025-01-22

**Context**: The `/unit-of-measures` page had a different DataTable implementation pattern compared to the `/inventory` page. The unit-of-measures page used nested card structures, different JavaScript patterns (`@push('scripts')` vs `@section('scripts')`), different variable declarations (`var` vs `const`), and custom language configurations. This inconsistency created maintenance overhead and potential confusion for developers working across multiple pages.

**Options Considered**:

1. **Option A**: Keep existing patterns, document differences.

    - ✅ Pros: No code changes required, preserves existing functionality.
    - ❌ Cons: Maintenance overhead, inconsistent user experience, developer confusion.

2. **Option B**: Standardize on inventory page pattern across all DataTable implementations.
    - ✅ Pros: Consistent codebase, easier maintenance, unified user experience, clear pattern for future pages.
    - ❌ Cons: Requires refactoring existing pages.

**Decision**: Adopt Option B—standardize all DataTable implementations to match the inventory page pattern.

**Rationale**:

-   Consistency improves maintainability and reduces cognitive load for developers.
-   Unified pattern makes it easier to add new DataTable pages following established conventions.
-   Consistent user experience across pages improves usability.
-   Inventory page pattern is simpler and cleaner (removed unnecessary nested structures).
-   Standardized filter form pattern using `form-inline` in card-header provides better layout consistency.
-   Using `@section('scripts')` instead of `@push('scripts')` is more explicit and matches Laravel conventions.
-   `const` instead of `var` follows modern JavaScript best practices.

**Implementation**:

-   **Layout Structure**: Simplified from nested `section`/`container-fluid`/`card-outline` to simple `row` → `col-12` → `card` structure matching inventory.
-   **Card Header**: Moved filters to card-header using `d-flex justify-content-between align-items-center` with action buttons on left, filters on right.
-   **Filter Form**: Changed to `form-inline` class with form submission pattern using `serializeArray()`.
-   **Table Structure**: Changed table ID from `units-table` to `tbl-units` (matching `tbl-inventory` pattern), removed `table-hover` and `thead-dark` classes, simplified to `table table-bordered table-striped`.
-   **JavaScript**: Changed from `@push('scripts')` to `@section('scripts')`, changed from `var table` to `const table`, removed custom language configuration, simplified column render functions.
-   **Filter Handling**: Implemented `serializeArray()` pattern matching inventory page for consistent filter data processing.

**Review Date**: 2026-01-22 (after full year of production use with standardized pattern).

### Decision: Complete Document Numbering System Migration - 2025-12-11

**Context**: The system had a dual numbering format system with entity-aware format (`EEYYDDNNNNN`) for core purchase/sales documents and legacy format (`PREFIX-YYYYMM-######`) for accounting documents (Purchase Payments, Sales Receipts, Asset Disposals, Cash Expenses, Journals, Account Statements). This created inconsistency, confusion, and prevented unified entity-specific reporting and letterhead management across all document types.

**Options Considered**:

1. **Option A**: Keep dual-format system, migrate only high-priority documents.

    - ✅ Pros: Minimal risk, preserves existing functionality for accounting documents.
    - ❌ Cons: Inconsistent numbering, duplicate sequence management logic, reporting complexity.

2. **Option B**: Migrate all documents to entity-aware format in single phase.

    - ✅ Pros: Complete consistency, unified sequence management, simpler reporting, single format standard.
    - ❌ Cons: More complex migration, requires entity resolution logic for all document types.

3. **Option C**: Phased migration over multiple releases (Recommended).
    - ✅ Pros: Managed risk, allows testing between phases, maintains backward compatibility, clear migration path.
    - ❌ Cons: Temporary inconsistency during migration period.

**Decision**: Adopt Option C—implement phased migration: Phase 1 (Purchase Payment, Sales Receipt), Phase 2 (Asset Disposal, Cash Expense), Phase 3 (Journal, Account Statement), Phase 4 (Testing & Documentation).

**Rationale**:

-   Phased approach minimizes risk while achieving complete migration goal.
-   Phase 1 focuses on high-impact documents with existing entity context.
-   Phase 2 handles documents requiring entity resolution logic.
-   Phase 3 addresses complex documents with multiple source types (Journals) or default entity assignment (Account Statements).
-   Complete migration ensures unified reporting, consistent letterhead management, and simplified sequence tracking.
-   Legacy format completely deprecated, eliminating dual-format complexity.

**Implementation**:

-   **Phase 1**: Added `company_entity_id` to Purchase Payment and Sales Receipt models, updated controllers to pass entity context, added codes `04` and `09` to `ENTITY_DOCUMENT_CODES`.
-   **Phase 2**: Created migrations for Asset Disposal and Cash Expense `company_entity_id`, implemented entity resolution (Asset→PurchaseInvoice for Disposal, default entity for Cash Expense), added codes `10` and `11`.
-   **Phase 3**: Added `company_entity_id` to Journals and Account Statements, implemented `resolveJournalEntity()` method in PostingService supporting multiple source types, Account Statements use default entity, added codes `12` and `13`.
-   **Phase 4**: Updated all documentation, created bilingual manual (`docs/manuals/document-numbering-system-manual.md`), browser testing validation.
-   All documents now use `EEYYDDNNNNN` format exclusively. Legacy format removed from active use.
-   Updated `DocumentNumberingService` with complete document code mapping, entity resolution methods, and year-based sequence management.

**Review Date**: 2026-12-11 (after full year of production use with complete entity-aware numbering).

### Decision: Product Category Hierarchical UI Improvements - 2025-01-21

**Context**: Product categories support hierarchical parent-child relationships through `parent_id` field, but the UI did not adequately display these relationships. Users needed clear visualization of category hierarchy and consistent hierarchical display across all dropdowns and selection interfaces.

**Options Considered**:

1. **Option A**: Add hierarchical display only to category index page.

    - ✅ Pros: Simple, localized change.
    - ❌ Cons: Inconsistent user experience, hierarchical relationships not visible where categories are selected.

2. **Option B**: Implement comprehensive hierarchical UI improvements across all category interfaces.
    - ✅ Pros: Consistent user experience, full hierarchy visibility, prevents circular references, improves usability.
    - ❌ Cons: Requires coordinated updates across multiple controllers and views.

**Decision**: Adopt Option B—implement comprehensive hierarchical UI improvements including model helper methods, controller filtering logic, tree/table view toggle, hierarchical dropdown display, and visual tree hierarchy.

**Rationale**:

-   Hierarchical categories provide powerful organizational capability but require clear visualization to be useful.
-   Tree view provides superior visualization for complex hierarchies while table view remains efficient for browsing.
-   Parent category filtering (showing only root categories) prevents circular references and simplifies user experience.
-   Hierarchical display names ("Parent > Child > Grandchild") provide context in dropdowns improving selection accuracy.
-   Consistent implementation pattern ensures maintainability and can be reused for other hierarchical structures.

**Implementation**:

-   Added ProductCategory model helper methods: `getHierarchicalName()`, `getHierarchicalPath()`, `isRoot()`, `getDescendants()`, `getInvalidParentIds()`.
-   Enhanced ProductCategoryController::index() to support tree/table view toggle with `$viewMode` parameter.
-   Updated ProductCategoryController::create() and edit() to filter parent categories to root categories only.
-   Created tree view partial (`resources/views/product-categories/partials/tree-item.blade.php`) with recursive rendering and color-coded levels.
-   Updated all category dropdowns across system (inventory forms, item selection modals, goods receipt, etc.) to use `getHierarchicalName()`.
-   Enhanced InventoryController::search() to load category parent relationships for hierarchical display.
-   Added tree view styling with color-coded hierarchy levels and visual indicators.

**Review Date**: 2026-04-21 (after extended production usage of hierarchical categories).

### Decision: Inventory Low Stock & Valuation Routes and Scope Fix - 2025-11-29

**Context**: Navigating to `/inventory/low-stock` and `/inventory/valuation-report` caused 500 errors due to the generic `inventory/{item}` route capturing the report URLs and the `InventoryItem::scopeLowStock()` referencing a non-existent `current_stock` database column. The valuation report view also relied on a transformed collection that broke route parameter generation for `inventory.show`.

**Options Considered**:

1. **Option A**: Change controller signatures and add custom logic in each method to special-case the report URLs.
    - ✅ Pros: Localised changes in controller.
    - ❌ Cons: Leaves route table ambiguous, brittle to future additions, duplicates logic.
2. **Option B**: Reorder and clarify routes plus fix the model scope to rely on existing warehouse stock tables.
    - ✅ Pros: Aligns with Laravel routing best practices, keeps concerns in the right layers (routing/model), reuses existing `inventory_warehouse_stock` data, fixes all consumers of the low stock scope.
    - ❌ Cons: Requires coordinated updates in routes, model, and views.

**Decision**: Adopt Option B—prioritise static inventory report routes before the catch-all `inventory/{item}` routes and refactor the `InventoryItem::scopeLowStock()` to use `inventory_warehouse_stock` instead of a non-existent `current_stock` column.

**Rationale**:

-   Ensures `/inventory/low-stock` and `/inventory/valuation-report` are always handled by their dedicated controller methods and never misrouted to `InventoryController::show()`.
-   Centralises low stock logic in the model using real schema (`inventory_warehouse_stock.quantity_on_hand` and `reorder_point`), fixing all callers (`InventoryController`, `InventoryService`, dashboard).
-   Keeps the valuation report controller returning a standard `InventoryItem` collection while the view derives latest valuation data from the eager-loaded `valuations` relation, avoiding array-mapped collections that break route helpers.

**Implementation**:

-   Updated `routes/web.php` inventory group so `/inventory/low-stock` and `/inventory/valuation-report` are declared before `Route::get('/{item}', ...)` and grouped item detail routes (`show/edit/update/destroy`) at the end.
-   Refactored `InventoryItem::scopeLowStock()` to use an `EXISTS` subquery on `inventory_warehouse_stock` (`quantity_on_hand <= reorder_point`) instead of `whereRaw('current_stock <= reorder_point')`.
-   Simplified `InventoryController::valuationReport()` to return `InventoryItem::with(['category', 'valuations'])->active()->get()` and adjusted `inventory/valuation-report.blade.php` to compute `$latestValuation` from the eager-loaded `valuations` collection.
-   Fixed JS helpers in `inventory/low-stock.blade.php` and `inventory/valuation-report.blade.php` to generate URLs for `inventory.adjust-stock` and `inventory.show` using placeholder replacement instead of calling `route()` with missing parameters.

**Review Date**: 2026-03-31 (after more extensive inventory operations and reporting usage in production).

### Decision: Multi-Entity Company Profile Foundation - 2025-11-28

**Context**: Users need to generate purchasing and sales documents under multiple legal entities (PT Cahaya Sarange Jaya and CV Cahaya Saranghae) while sharing the same master data. Each document must carry entity-specific letterheads, tax information, and numbering without duplicating inventory records.

**Options Considered**:

1. **Option A**: Store per-entity attributes inside existing `erp_parameters` and reuse current document schema.

    - ✅ Pros: Minimal schema work, quick to prototype.
    - ❌ Cons: Hard to manage multiple letterheads, no FK relation to documents, difficult to enforce referential integrity.

2. **Option B**: Create dedicated `company_entities` table and add `company_entity_id` to every document header.

    - ✅ Pros: Strong referential integrity, scalable for future entities, easy to query/filter, aligns with ERP best practices.
    - ❌ Cons: Requires new migrations/seeders and updates across many tables.

3. **Option C**: Spin up separate databases per entity.
    - ✅ Pros: Clear separation of data.
    - ❌ Cons: Duplicates master data, complicates consolidation, heavy operational overhead.

**Decision**: Adopt Option B—dedicated `company_entities` master table with FK references from all purchasing and sales headers.

**Rationale**:

-   Maintains shared inventory/master data while enabling per-entity reporting and numbering.
-   Provides a single source of truth for logos, addresses, tax numbers, and letterhead metadata.
-   Simplifies future UI changes (entity selectors, previews) and reporting filters.
-   Keeps accounting postings aligned by allowing future journal tagging via the same FK.
-   Avoids multi-database complexity and parameter sprawl.

**Implementation**:

-   Added `company_entities` table with code, legal name, contact details, logos, and letterhead metadata.
-   Seeded PT Cahaya Sarange Jaya (`code 71`, `logo_pt_csj.png`) and CV Cahaya Saranghae (`code 72`, `logo_cv_saranghae.png`).
-   Added nullable `company_entity_id` foreign keys to purchase_orders, goods_receipt_po, purchase_invoices, purchase_payments, sales_orders, delivery_orders, sales_invoices, and sales_receipts.
-   Extended `document_sequences` table with entity-aware columns (`company_entity_id`, `document_code`, `year`, `current_number`) to prepare the new numbering format.
-   Updated architecture, TODO, decision, and memory docs to reflect the multi-entity foundation.

**Review Date**: 2026-01-31 (after Phase 2/3 UI + numbering rollout).

---

### Decision: Entity-Aware Document Numbering & Services - 2025-11-28

**Context**: After introducing multiple legal entities, document numbering and service layers still produced shared numbers (PREFIX-YYYYMM-######) and did not persist `company_entity_id`, making it impossible to segregate PO/GRPO/PI/SO/DO/SI/SR data per entity.

**Options Considered**:

1. **Option A**: Keep existing numbering and add manual prefixes.

    - ✅ Pros: Minimal code changes.
    - ❌ Cons: Users must manually ensure uniqueness, no referential integrity, hard to audit.

2. **Option B**: Create per-entity numbering but keep logic in controllers.

    - ✅ Pros: Entity-aware numbering.
    - ❌ Cons: Duplicated logic, error-prone maintenance, inconsistent format.

3. **Option C**: Centralize entity resolution + numbering inside services (Recommended).
    - ✅ Pros: Single source of truth, seamless inheritance from base documents, easy to extend, minimizes controller logic.
    - ❌ Cons: Requires coordinated updates across controllers/services/models.

**Decision**: Adopt Option C. Update DocumentNumberingService + DocumentSequence to support `EEYYDDNNNNN` per entity/per doc/per year and introduce CompanyEntityService for default resolution + propagation.

**Rationale**:

-   Guarantees unique numbering sequences per legal entity and document family.
-   Keeps controllers thin; services manage default entity/fallback logic.
-   Preserves compatibility for legacy modules (PP/SR/DIS/etc.) still on prefix format.
-   Enables downstream reporting and PDF rendering to know which entity produced each document.

**Implementation**:

-   Added `CompanyEntityService` plus `company_entity_id` relationships across PO, GRPO, PI, PP, SO, DO, SI, SR models.
-   Refactored controllers/services to persist entity context, inherit it when copying documents, and pass it to DocumentNumberingService.
-   Enhanced DocumentNumberingService + DocumentSequence with entity-aware fields (`document_code`, `year`, `current_number`) while keeping legacy prefixes untouched.
-   Delivery workflow now generates DO numbers via the numbering service and copies entity context from its Sales Order.

**Review Date**: 2026-02-15 (after UI entity selector rollout and user acceptance).

---

### Decision: Corrected Accounting Flow with Intermediate Accounts - 2025-09-22

**Context**: The existing accounting system had critical mismatches where GRPO created liabilities before receiving vendor invoices and Purchase Invoices debited cash when no cash was received, violating proper accrual accounting principles. The system needed intermediate accounts to properly track goods received/delivered but not yet invoiced.

**Options Considered**:

1. **Option A**: Keep existing accounting logic with manual corrections

    - ✅ Pros: No code changes required, immediate solution
    - ❌ Cons: Ongoing manual corrections, audit issues, compliance problems, error-prone

2. **Option B**: Implement intermediate accounts (AR UnInvoice, AP UnInvoice) with corrected accounting flow

    - ✅ Pros: Proper accrual accounting, automatic journal generation, audit compliance, professional accounting standards
    - ❌ Cons: Significant code changes, database schema updates, comprehensive testing required

3. **Option C**: Use existing accounts with modified logic
    - ✅ Pros: Minimal schema changes, familiar account structure
    - ❌ Cons: Confusing account usage, poor audit trail, accounting principle violations

**Decision**: Implement intermediate accounts (AR UnInvoice, AP UnInvoice) with corrected accounting flow

**Rationale**:

-   Ensures proper accrual accounting principles compliance
-   Provides clear audit trail with intermediate account usage
-   Enables automatic journal generation with balanced entries
-   Follows professional accounting standards for trading companies
-   Eliminates manual corrections and reduces error risk
-   Provides proper timing for liability/receivable recognition

**Implementation**:

-   Created AR UnInvoice (1.1.2.04) and AP UnInvoice (2.1.1.03) accounts
-   Updated GRPOJournalService to use AP UnInvoice instead of Utang Dagang
-   Modified PurchaseInvoiceController to debit AP UnInvoice and credit Utang Dagang
-   Updated PurchasePaymentController to use correct cash and AP accounts
-   Enhanced DeliveryJournalService to use AR UnInvoice
-   Modified SalesInvoiceController to debit AR UnInvoice and credit Piutang Dagang
-   Updated SalesReceiptController to use correct cash and AR accounts
-   Fixed journal balancing issues by removing duplicate expense line creation
-   Comprehensive browser testing validation confirming proper journal entry creation

**Review Date**: 2026-03-22 (6 months)

### Decision: GR/GI System Implementation with Journal Integration - 2025-09-21

**Context**: Trading companies require comprehensive Goods Receipt (GR) and Goods Issue (GI) system for non-purchase receiving and non-sales issuing operations with automatic journal entry integration, account mapping logic, and multiple valuation methods for proper inventory management and financial integration.

**Options Considered**:

1. **Option A**: Basic GR/GI system without journal integration

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: Manual journal entries, high error risk, poor financial integration, audit issues

2. **Option B**: Comprehensive GR/GI system with automatic journal integration and sophisticated business logic

    - ✅ Pros: Automatic journal entries, account mapping, multiple valuation methods, comprehensive approval workflow, professional user interface
    - ❌ Cons: Complex implementation, extensive development effort, integration challenges

3. **Option C**: Third-party GR/GI integration
    - ✅ Pros: Proven solution, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, integration complexity

**Decision**: Comprehensive GR/GI system with automatic journal integration and sophisticated business logic (Option B)

**Rationale**:

-   Trading companies require sophisticated inventory management for non-purchase/non-sales operations
-   Automatic journal integration ensures proper financial accounting and audit trail
-   Account mapping based on item categories and purposes provides flexible configuration
-   Multiple valuation methods (FIFO, LIFO, Average, Manual) support various business scenarios
-   Comprehensive approval workflow ensures proper authorization and status tracking
-   Professional user interface with SweetAlert2 integration provides excellent user experience
-   Better integration with existing ERP architecture and business processes
-   Cost-effective long-term solution despite higher initial development effort

**Implementation**:

-   **Database Schema**: 5 new tables (gr_gi_purposes, gr_gi_headers, gr_gi_lines, gr_gi_account_mappings, gr_gi_journal_entries) with comprehensive relationships
-   **Models**: GRGIPurpose, GRGIHeader, GRGILine, GRGIAccountMapping, GRGIJournalEntry with proper relationships and business logic
-   **Service Layer**: GRGIService with automatic journal entry generation, account mapping logic, valuation methods, and approval workflow management
-   **Controller**: GRGIController with full CRUD operations, approval/cancellation workflows, API endpoints, and comprehensive error handling
-   **Views**: Complete AdminLTE views (index, create, show, edit) with SweetAlert2 confirmation dialogs and responsive design
-   **Account Mapping**: Automatic account mapping (GR: debit=item category auto, credit=manual; GI: debit=manual, credit=item category auto)
-   **Valuation Methods**: FIFO, LIFO, Average cost, and Manual entry for comprehensive inventory valuation
-   **Approval Workflow**: Complete status progression (draft → pending_approval → approved) with cancellation tracking
-   **Routes**: Complete route setup with middleware protection and permission-based access control (gr-gi.view/create/update/delete/approve)
-   **Menu Integration**: Added GR/GI Management to sidebar navigation under Inventory section
-   **Seeders**: GRGIPurposeSeeder with 6 GR types and 8 GI types, GRGIAccountMappingSeeder with default account mappings
-   **Testing**: Browser testing validation confirms complete workflow functionality

**Consequences**: System now has enterprise-level GR/GI system providing comprehensive non-purchase/non-sales inventory management with automatic journal integration. Implementation demonstrates sophisticated accounting architecture with automatic account mapping, multiple valuation methods with automatic cost calculation, comprehensive approval workflow with status tracking, and seamless integration with existing journal posting system. System provides complete audit trail, professional user interface with SweetAlert2 confirmations, and comprehensive business logic enabling proper inventory management for trading company operations with automatic financial integration.

**Review Date**: 2026-03-21 (after 6 months of production use and user feedback)

---

### Decision: Document Closure System Architecture Implementation - 2025-09-20

**Context**: ERP system required comprehensive Document Closure System for tracking document status (open/closed) throughout business workflows with automatic closure logic, manual override capabilities, and Open Items reporting for monitoring outstanding documents and ensuring business process completion.

**Options Considered**:

1. **Option A**: Manual document status tracking without automation

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: High error risk, manual burden, poor visibility, audit issues

2. **Option B**: Comprehensive Document Closure System with automatic closure logic and reporting

    - ✅ Pros: Full automation, comprehensive reporting, audit trail, business process visibility
    - ❌ Cons: Complex implementation, extensive development effort, integration challenges

3. **Option C**: Basic status tracking without closure chain management
    - ✅ Pros: Moderate complexity, basic functionality
    - ❌ Cons: Limited value, doesn't address workflow completion, poor business insight

**Decision**: Comprehensive Document Closure System with automatic closure logic and reporting (Option B)

**Rationale**:

-   Document closure tracking is fundamental to ERP business process management
-   Automatic closure logic reduces manual errors and ensures workflow completion
-   Comprehensive reporting provides visibility into outstanding documents and business process health
-   Closure chain management enables proper business workflow tracking (PO→GRPO→PI→PP, SO→DO→SI→SR)
-   ERP Parameters system enables user-configurable business rules and thresholds
-   Better integration with existing document management systems
-   Cost-effective long-term solution despite higher initial development effort
-   Full control over closure logic and reporting capabilities

**Implementation**:

-   **Database Schema**: 2 new migrations adding closure fields (closure_status, closed_by_document_type, closed_by_document_id, closed_at, closed_by_user_id) to all document tables with proper indexes
-   **Services**: DocumentClosureService for closure logic and validation, OpenItemsService for comprehensive reporting with aging analysis
-   **Controllers**: ErpParameterController for system configuration management, OpenItemsController for reporting with Excel export
-   **Models**: ErpParameter model with category-based organization and type casting, enhanced document models with closure methods
-   **ERP Parameters**: Comprehensive parameter system with document_closure, system_settings, and price_handling categories
-   **Open Items Reporting**: Complete reporting system with aging analysis, exception identification, and Excel export capabilities
-   **UI Integration**: Status indicators in DataTables with visual badges, closure information in document views
-   **Routes**: Complete route configuration with middleware and permissions (manage-erp-parameters, reports.open-items)
-   **Menu Integration**: Added ERP Parameters to Admin section, Open Items to Reports section
-   **Seeder**: ErpParameterSeeder with default system parameters including overdue thresholds and auto-closure settings
-   **Testing**: Browser testing validation confirms functionality works correctly with proper status indicators and reporting

**Consequences**: System now has enterprise-level Document Closure System providing comprehensive document lifecycle management with automatic closure logic, manual override capabilities, and professional reporting. All documents track closure status with complete audit trail, ERP Parameters enable user-configurable business rules, and Open Items reporting provides visibility into outstanding documents with aging analysis. System provides complete business process visibility, outstanding document monitoring, and professional reporting capabilities enabling effective business process management and compliance monitoring.

**Review Date**: 2026-03-20 (after 6 months of production use and user feedback)

---

### Decision: Critical Field Mapping Issues Resolution - 2025-01-19

**Context**: During comprehensive trading cycle testing, critical blocking issues were identified where multiple controllers, services, and forms were still using old field names (vendor_id, customer_id) instead of the unified business_partner_id after the business partner consolidation migration. Additionally, views were referencing undefined $funds variables after multi-dimensional accounting simplification, causing form submission failures and view loading errors.

**Options Considered**:

1. **Option A**: Fix issues individually as they arise

    - ✅ Pros: Minimal immediate impact, gradual fixes
    - ❌ Cons: Inconsistent system state, continued user confusion, potential data integrity issues

2. **Option B**: Comprehensive systematic field mapping update across entire system

    - ✅ Pros: Complete consistency, eliminates all field mapping issues, ensures data integrity
    - ❌ Cons: Large scope, requires testing across all modules

3. **Option C**: Revert business partner consolidation migration
    - ✅ Pros: Returns to known working state
    - ❌ Cons: Loses business partner consolidation benefits, requires re-implementation

**Decision**: Comprehensive systematic field mapping update across entire ERP system

**Rationale**: Option B ensures complete system consistency and eliminates all field mapping issues that were blocking form submissions and causing view errors. The business partner consolidation provides significant value and should be maintained, but requires complete field mapping consistency across all components.

**Implementation**:

-   Updated all controllers (PurchaseOrderController, SalesOrderController, SalesInvoiceController, SalesReceiptController, GoodsReceiptController, TaxController, AssetController) to use business_partner_id consistently
-   Fixed all form submissions, JavaScript prefill logic, validation rules, and database queries
-   Updated DataTables column mappings and related services (PurchaseService, SalesService, SalesInvoiceService)
-   Removed all $funds variable references from views and controllers after multi-dimensional accounting simplification
-   Updated SupplierPerformance, CustomerPricingTier, CustomerCreditLimit model queries to use correct field names
-   Verified all forms load correctly and submit with proper field validation

**Consequences**: System now has complete field mapping consistency with 95% production readiness. All forms submit correctly, all views load without errors, and all JavaScript form handling works properly. Business partner consolidation migration is fully complete and functional.

### Decision: Business Partner Journal History Implementation - 2025-01-19

**Context**: Business Partners needed comprehensive transaction history visibility with running balance calculation, but the existing system lacked integrated journal history functionality. Users required account mapping capabilities and transaction consolidation from multiple sources (journal lines, sales/purchase invoices/receipts) with professional reporting interface.

**Options Considered**:

1. **Option A**: Create separate Journal History module

    - ✅ Pros: Independent module, focused functionality
    - ❌ Cons: Disconnected from Business Partner context, duplicate navigation, user confusion

2. **Option B**: Integrate Journal History as Business Partner tab

    - ✅ Pros: Contextual integration, unified user experience, logical data flow
    - ❌ Cons: Complex tabbed interface, potential performance issues with large datasets

3. **Option C**: Add account mapping only without transaction history
    - ✅ Pros: Simple implementation, basic functionality
    - ❌ Cons: Limited value, doesn't address core requirement for transaction visibility

**Decision**: Integrate Journal History as Business Partner tab with comprehensive account mapping and transaction consolidation

**Rationale**: Option B provides the most value by integrating transaction history directly into Business Partner context where users expect to find it. The tabbed interface maintains clean organization while providing comprehensive functionality. Account mapping enables proper GL account assignment with automatic defaults based on partner type.

**Implementation**:

-   Added account_id field to business_partners table with foreign key to accounts
-   Created BusinessPartnerJournalService for transaction consolidation from multiple sources
-   Implemented journalHistory controller method with pagination and filtering
-   Added Accounting section to Taxation & Terms tab with account selection dropdown
-   Created Journal History tab with date filters, summary cards, transaction table, and AJAX data loading
-   Removed "both" partner type to simplify business logic and account mapping
-   Updated BusinessPartner model with account relationship and default account logic

**Review Date**: 2025-04-19 (3 months)

### Decision: Comprehensive Auto-Numbering System Architecture - 2025-01-17

**Context**: Sarange ERP system required consistent document numbering across all document types with PREFIX-YYYYMM-###### format, but existing implementation was scattered across multiple controllers with inconsistent logic and missing implementations for some document types.

**Options Considered**:

1. **Option A**: Fix individual implementations incrementally

    - ✅ Pros: Minimal disruption, gradual improvement
    - ❌ Cons: Inconsistent logic, duplicate code, maintenance overhead, continued inconsistencies

2. **Option B**: Create centralized auto-numbering service with unified logic

    - ✅ Pros: Consistent implementation, centralized logic, thread-safe operations, easy maintenance
    - ❌ Cons: Requires refactoring existing code, higher initial development effort

3. **Option C**: Use database auto-increment with formatting
    - ✅ Pros: Simple implementation, database-managed sequences
    - ❌ Cons: No month-based reset, potential gaps, limited control over format

**Decision**: Create centralized auto-numbering service with unified logic (Option B)

**Rationale**:

-   Ensures consistent PREFIX-YYYYMM-###### format across all document types
-   Centralized logic reduces code duplication and maintenance overhead
-   Thread-safe operations prevent duplicate numbers in concurrent environments
-   Month-based sequence tracking enables proper document organization
-   Easy to extend for new document types
-   Better error handling and validation
-   Database persistence ensures sequence integrity across system restarts

**Implementation**:

-   Created `DocumentNumberingService` with centralized numbering logic
-   Implemented `DocumentSequence` model and `document_sequences` table for sequence tracking
-   Added auto-numbering to missing document types (Asset Disposals, Cash Expenses)
-   Updated all 8 existing controllers/services to use centralized service
-   Standardized prefixes: PO, SO, PINV, SINV, PP, SR, DIS, GR, CEV, JNL
-   Implemented thread-safe operations with database transactions and locking
-   Added proper error handling and validation
-   Created database migrations for new fields and sequence table
-   Fixed database migration issues and ran fresh migration for clean implementation

**Review Date**: 2025-04-17 (after production deployment and user feedback)

---

### Decision: Trading Company Chart of Accounts Structure - 2025-01-15

**Context**: Need to modify Sarange ERP for trading company operations while ensuring PSAK compliance and Indonesian tax regulations adherence.

**Options Considered**:

1. **Option A**: Modify existing CoA incrementally

    - ✅ Pros: Minimal disruption, faster implementation
    - ❌ Cons: May not fully comply with PSAK, complex maintenance, limited trading features

2. **Option B**: Complete CoA restructuring with PSAK-compliant structure

    - ✅ Pros: Full PSAK compliance, proper trading company structure, future-proof design
    - ❌ Cons: Significant development effort, data migration complexity, longer implementation time

3. **Option C**: Create separate trading company CoA alongside existing
    - ✅ Pros: No disruption to existing system, parallel development
    - ❌ Cons: Code duplication, maintenance overhead, inconsistent user experience

**Decision**: Complete CoA restructuring with PSAK-compliant structure (Option B)

**Rationale**:

-   Ensures full compliance with Indonesian accounting standards (PSAK)
-   Provides proper foundation for trading company operations
-   Enables accurate financial reporting and tax compliance
-   Supports future scalability and regulatory changes
-   Better long-term maintainability despite higher initial effort

**Implementation**:

-   Create new `TradingCoASeeder.php` with 7 main categories
-   Implement database migration for CoA restructuring
-   Update all existing transactions to map to new account structure
-   Modify reporting templates for PSAK compliance
-   Update user interface for new account hierarchy

**Review Date**: 2025-04-15 (after Phase 1 completion)

---

### Decision: Inventory Management Architecture - 2025-01-15

**Context**: Trading companies require comprehensive inventory management with real-time tracking, multiple valuation methods, and cost allocation.

**Options Considered**:

1. **Option A**: Extend existing asset management system

    - ✅ Pros: Reuse existing code, consistent with current architecture
    - ❌ Cons: Asset management not suitable for trading inventory, limited valuation methods

2. **Option B**: Create dedicated inventory management system

    - ✅ Pros: Purpose-built for trading operations, multiple valuation methods, real-time tracking
    - ❌ Cons: Additional development effort, new learning curve

3. **Option C**: Use third-party inventory management integration
    - ✅ Pros: Proven solution, faster implementation
    - ❌ Cons: Integration complexity, ongoing licensing costs, limited customization

**Decision**: Create dedicated inventory management system (Option B)

**Rationale**:

-   Trading inventory has different requirements than fixed assets
-   Need for multiple valuation methods (FIFO, LIFO, Weighted Average)
-   Real-time stock tracking is critical for trading operations
-   Better integration with COGS calculation and tax compliance
-   Full control over features and customization

**Implementation**:

-   Create `inventory_items`, `inventory_transactions`, `inventory_valuations` tables
-   Implement `InventoryController` with CRUD operations
-   Create `InventoryService` for valuation calculations
-   Add inventory-specific permissions and security
-   Integrate with existing purchase/sales order systems

**Review Date**: 2025-03-15 (after Phase 2 completion)

---

### Decision: Inventory Stock Calculation Fix - 2026-01-22

**Context**: Critical bug discovered in inventory stock calculation causing discrepancies between Stock by Warehouse and Valuation History sections. Sales transactions store negative quantities, but the calculation was subtracting them again, causing double subtraction and incorrect stock totals.

**Options Considered**:

1. **Option A**: Fix calculation logic only

    - ✅ Pros: Simple fix, addresses root cause
    - ❌ Cons: Doesn't fix existing incorrect valuation records

2. **Option B**: Fix calculation logic + create manual SQL update script

    - ✅ Pros: Fixes both code and data
    - ❌ Cons: Manual process, error-prone, doesn't scale

3. **Option C**: Fix calculation logic + create automated correction command
    - ✅ Pros: Fixes code, provides reusable tool for data correction, scalable
    - ❌ Cons: Additional development effort

**Decision**: Fix calculation logic + create automated correction command (Option C)

**Rationale**:

-   Calculation bug affects all items with sales transactions
-   Existing valuation records may be incorrect and need correction
-   Automated command enables systematic correction of all affected items
-   Command can be reused for future data integrity checks
-   Provides audit trail of corrections made

**Implementation**:

-   Fixed `InventoryItem::getCurrentStockAttribute()` to add sales quantities (already negative) instead of subtracting them
-   Created `FixInventoryValuation` artisan command with detection and correction capabilities
-   Command compares calculated stock vs recorded valuation and updates when mismatch detected
-   Supports fixing specific items or all items in the system
-   Verified fix on item ID 2, correcting quantity from 424 to 306 PCS

**Impact**:

-   All future stock calculations are now accurate
-   Existing incorrect valuations can be systematically corrected
-   Stock by Warehouse, Current Stock, and Valuation History sections now display consistent values
-   Improved data integrity and financial reporting accuracy

**Review Date**: 2026-04-22 (after running correction command on all items and verifying accuracy)

---

### Decision: Tax Compliance Implementation Strategy - 2025-01-15

**Context**: Indonesian trading companies must comply with PPN (VAT) and PPh regulations with automated calculation and reporting.

**Options Considered**:

1. **Option A**: Manual tax calculation with basic reporting

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: High error risk, manual compliance burden, audit issues

2. **Option B**: Automated tax calculation with comprehensive reporting

    - ✅ Pros: Reduced errors, automated compliance, audit-ready reports
    - ❌ Cons: Complex implementation, extensive testing required

3. **Option C**: Third-party tax compliance integration
    - ✅ Pros: Proven compliance, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization

**Decision**: Automated tax calculation with comprehensive reporting (Option B)

**Rationale**:

-   Indonesian tax regulations are complex and error-prone when done manually
-   Automated calculation reduces compliance risk and audit issues
-   Comprehensive reporting ensures regulatory compliance
-   Better integration with existing financial system
-   Cost-effective long-term solution despite higher initial development

**Implementation**:

-   Create `tax_codes`, `tax_transactions`, `tax_reports` tables
-   Implement `PPNController` and `PPhController` for tax management
-   Create `TaxCalculationService` for automated calculations
-   Add tax-specific permissions and security
-   Implement monthly and annual tax reporting templates

**Review Date**: 2025-05-15 (after Phase 3 completion)

---

### Decision: COGS Calculation Method - 2025-01-15

**Context**: Trading companies need accurate Cost of Goods Sold calculation for profit margin analysis and financial reporting.

**Options Considered**:

1. **Option A**: Simple average cost method

    - ✅ Pros: Easy to implement and understand, consistent costs
    - ❌ Cons: May not reflect actual cost flow, less accurate for price fluctuations

2. **Option B**: FIFO (First In, First Out) method

    - ✅ Pros: Reflects actual inventory flow, better for perishable goods, PSAK compliant
    - ❌ Cons: More complex implementation, requires detailed transaction tracking

3. **Option C**: Multiple valuation methods with user selection
    - ✅ Pros: Flexibility for different business needs, compliance with various standards
    - ❌ Cons: Complex implementation, potential confusion, higher maintenance

**Decision**: Multiple valuation methods with user selection (Option C)

**Rationale**:

-   Different trading companies may have different inventory characteristics
-   PSAK allows multiple valuation methods
-   Provides flexibility for various business models
-   Better compliance with Indonesian accounting standards
-   Future-proof design for changing business needs

**Implementation**:

-   Create `InventoryService` with multiple valuation methods
-   Implement FIFO, LIFO, and Weighted Average calculations
-   Add valuation method selection in inventory item configuration
-   Create COGS calculation service with method-specific logic
-   Implement automatic COGS recognition on sales transactions

**Review Date**: 2025-06-15 (after Phase 4 completion)

---

### Decision: Database Migration Consolidation Strategy - 2025-01-15

**Context**: During development, multiple migration files were created to modify existing tables, resulting in 51 migration files with complex modification history that made schema understanding difficult.

**Options Considered**:

1. **Option A**: Keep all modification migrations as-is

    - ✅ Pros: Preserves complete development history, no risk of breaking changes
    - ❌ Cons: Complex migration history, difficult to understand final table structure, slower fresh installations

2. **Option B**: Consolidate modifications into original table creation migrations

    - ✅ Pros: Cleaner migration history, self-contained table definitions, easier maintenance
    - ❌ Cons: Loses development history, requires careful testing, potential for errors

3. **Option C**: Create new consolidated migration files
    - ✅ Pros: Clean slate approach, optimized for production
    - ❌ Cons: Complete rewrite required, high risk of data loss, complex migration path

**Decision**: Consolidate modifications into original table creation migrations (Option B)

**Rationale**:

-   Development phase allows for schema consolidation without production data concerns
-   Cleaner migration history improves maintainability and understanding
-   Self-contained table definitions make schema evolution easier to track
-   Faster fresh installations with fewer migration steps
-   Better developer experience with consolidated table structures

**Implementation**:

-   Merged 13 modification migrations into their respective table creation migrations
-   Consolidated foreign key constraints with proper dependency ordering
-   Created single permissions migration consolidating all permission additions
-   Fixed migration order to resolve foreign key dependency issues
-   Reduced total migrations from 51 to 44 files
-   Verified schema integrity with fresh migration testing

**Review Date**: 2025-04-15 (after Phase 2 completion)

---

### Decision: Indonesian Tax Compliance System Architecture - 2025-01-15

**Context**: Indonesian trading companies require comprehensive tax compliance with PPN (VAT), PPh (Income Tax) management, automated calculation, and regulatory reporting to meet Indonesian tax office requirements.

**Options Considered**:

1. **Option A**: Basic tax calculation with manual reporting

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: High compliance risk, manual burden, audit issues, error-prone

2. **Option B**: Comprehensive tax compliance system with automated calculation and reporting

    - ✅ Pros: Full Indonesian compliance, automated calculation, comprehensive reporting, audit trail
    - ❌ Cons: Complex implementation, extensive testing required, higher development effort

3. **Option C**: Third-party tax compliance integration
    - ✅ Pros: Proven compliance solution, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, integration complexity

**Decision**: Comprehensive tax compliance system with automated calculation and reporting (Option B)

**Rationale**:

-   Indonesian tax regulations are complex and require precise calculation (PPN 11%, PPh 21-26, PPh 4(2))
-   Automated calculation reduces compliance risk and audit issues
-   Comprehensive reporting ensures regulatory compliance with SPT generation
-   Better integration with existing trading operations and financial system
-   Cost-effective long-term solution despite higher initial development effort
-   Full control over tax calculation logic and reporting formats

**Implementation**:

-   Enhanced `tax_transactions` table with comprehensive Indonesian tax fields
-   Created `tax_periods`, `tax_reports`, `tax_settings`, `tax_compliance_logs` tables
-   Implemented `TaxService` with automatic calculation, period management, report generation
-   Created `TaxController` with comprehensive CRUD operations and settings management
-   Built complete AdminLTE interface with dashboard, transactions, periods, reports, settings
-   Added tax-specific permissions and security with audit trail
-   Integrated automatic tax calculation with purchase/sales systems
-   Implemented Indonesian tax types: PPN (11%), PPh 21 (5%), PPh 22 (1.5%), PPh 23 (2%), PPh 26 (20%), PPh 4(2) (0.5%)

**Review Date**: 2025-06-15 (after Phase 4 completion)

---

### Decision: Phase 4 Advanced Trading Analytics Architecture - 2025-01-15

**Context**: Trading companies require comprehensive analytics capabilities including COGS tracking, supplier performance analysis, business intelligence, and unified reporting for data-driven decision making.

**Options Considered**:

1. **Option A**: Basic reporting with simple analytics

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: Limited insights, basic functionality, insufficient for complex trading operations

2. **Option B**: Comprehensive analytics platform with multiple specialized modules

    - ✅ Pros: Advanced analytics capabilities, specialized modules, comprehensive insights, unified dashboard
    - ❌ Cons: Complex implementation, extensive development effort, higher maintenance

3. **Option C**: Third-party analytics integration
    - ✅ Pros: Proven analytics solution, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, integration complexity

**Decision**: Comprehensive analytics platform with multiple specialized modules (Option B)

**Rationale**:

-   Trading operations require sophisticated analytics for profitability analysis
-   COGS tracking is critical for accurate margin analysis and pricing decisions
-   Supplier analytics enables optimization of procurement and vendor relationships
-   Business intelligence provides strategic insights for growth and efficiency
-   Unified dashboard offers single-pane-of-glass view for comprehensive decision making
-   Better integration with existing trading operations and financial system
-   Full control over analytics logic and reporting capabilities

**Implementation**:

-   Created comprehensive COGS system with 8 database tables for cost tracking and allocation
-   Implemented COGSService with automatic cost calculation, multiple valuation methods, margin analysis
-   Built SupplierAnalyticsService with performance metrics, cost optimization, risk assessment
-   Created BusinessIntelligenceService with comprehensive analytics, insights generation, KPI tracking
-   Developed unified AnalyticsController integrating all analytics components
-   Built complete AdminLTE interfaces for all analytics modules
-   Added analytics-specific permissions and security controls
-   Implemented unified dashboard providing integrated view of all trading analytics

**Review Date**: 2025-07-15 (after Phase 4 completion and user feedback)

---

### Decision: Advanced Analytics Database Schema Design - 2025-01-15

**Context**: Advanced trading analytics require sophisticated database schema to support cost tracking, supplier performance, business intelligence, and comprehensive reporting.

**Options Considered**:

1. **Option A**: Extend existing tables with additional fields

    - ✅ Pros: Minimal schema changes, faster implementation
    - ❌ Cons: Table bloat, complex queries, limited scalability, poor performance

2. **Option B**: Create specialized analytics tables with proper normalization

    - ✅ Pros: Optimized for analytics queries, better performance, scalable design, clear data separation
    - ❌ Cons: More complex schema, additional development effort

3. **Option C**: Use data warehouse approach with denormalized tables
    - ✅ Pros: Fast analytics queries, optimized for reporting
    - ❌ Cons: Data duplication, complex ETL processes, maintenance overhead

**Decision**: Create specialized analytics tables with proper normalization (Option B)

**Rationale**:

-   Analytics queries have different performance requirements than transactional queries
-   Proper normalization ensures data integrity and consistency
-   Specialized tables allow for optimized indexing and query performance
-   Clear separation of concerns between transactional and analytical data
-   Better scalability for future analytics requirements
-   Easier maintenance and understanding of data relationships

**Implementation**:

-   Created 11 specialized analytics tables: cost_allocation_methods, cost_categories, cost_allocations, cost_histories, product_cost_summaries, customer_cost_allocations, margin_analyses, supplier_cost_analyses, supplier_performances, supplier_comparisons, business_intelligences
-   Implemented proper foreign key relationships and indexing for performance
-   Added JSON fields for flexible data storage (insights, recommendations, KPI metrics)
-   Created comprehensive migration with proper constraint naming
-   Established clear data flow between transactional and analytical tables

**Review Date**: 2025-08-15 (after performance testing and optimization)

---

### Decision: Unified Analytics Dashboard Integration Strategy - 2025-01-15

**Context**: Multiple analytics modules (COGS, Supplier Analytics, Business Intelligence) need to be integrated into a unified dashboard for comprehensive trading analytics.

**Options Considered**:

1. **Option A**: Separate dashboards for each analytics module

    - ✅ Pros: Simple implementation, focused functionality, independent development
    - ❌ Cons: User confusion, data silos, inefficient workflow, poor user experience

2. **Option B**: Single unified dashboard integrating all analytics components

    - ✅ Pros: Single-pane-of-glass view, integrated insights, better user experience, comprehensive analytics
    - ❌ Cons: Complex implementation, integration challenges, higher development effort

3. **Option C**: Hybrid approach with module-specific dashboards and unified overview
    - ✅ Pros: Balance of focus and integration, flexible user experience
    - ❌ Cons: Complex navigation, potential confusion, maintenance overhead

**Decision**: Single unified dashboard integrating all analytics components (Option B)

**Rationale**:

-   Trading operations require comprehensive view of all analytics for effective decision making
-   Integrated insights provide better understanding of business performance
-   Single dashboard reduces user confusion and improves workflow efficiency
-   Cross-module analytics enable identification of optimization opportunities
-   Better user experience with consolidated view of all trading metrics
-   Enables data-driven decision making with comprehensive analytics

**Implementation**:

-   Created AnalyticsController with unified dashboard functionality
-   Integrated data from COGSService, SupplierAnalyticsService, and BusinessIntelligenceService
-   Built comprehensive unified dashboard with integrated insights, performance metrics, optimization opportunities
-   Implemented cross-module analytics and recommendations
-   Created single AdminLTE interface providing comprehensive view of all trading analytics
-   Added unified reporting capabilities combining all analytics components

**Review Date**: 2025-09-15 (after user acceptance testing and feedback)

---

### Decision: Comprehensive Training Workshop Materials Strategy - 2025-01-15

**Context**: Sarange ERP system requires comprehensive training materials to empower employees with hands-on knowledge through realistic business scenarios and practical exercises for successful system adoption.

**Options Considered**:

1. **Option A**: Basic documentation with simple user guides

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: Insufficient for complex ERP system, poor user adoption, limited practical knowledge

2. **Option B**: Comprehensive training workshop package with story-based learning

    - ✅ Pros: Effective knowledge transfer, hands-on learning, realistic scenarios, comprehensive coverage
    - ❌ Cons: Extensive development effort, complex material creation, higher maintenance

3. **Option C**: Third-party training integration
    - ✅ Pros: Proven training methodology, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, generic content

**Decision**: Comprehensive training workshop package with story-based learning (Option B)

**Rationale**:

-   Complex ERP systems require comprehensive training for effective user adoption
-   Story-based learning provides realistic context and practical application
-   Hands-on exercises enable immediate skill development and confidence building
-   Module-based structure allows targeted training for different user roles
-   Indonesian business context ensures relevance and compliance understanding
-   Comprehensive assessment framework ensures knowledge retention and certification
-   Better long-term user adoption and system utilization

**Implementation**:

-   Created complete 3-day training workshop package with 9 comprehensive documents
-   Developed 7 module-based training guides covering all major system components
-   Implemented 35+ story-based scenarios with hands-on exercises
-   Built comprehensive assessment framework with certification levels (Basic, Intermediate, Advanced, Expert)
-   Tailored all materials for Indonesian trading company operations and PSAK compliance
-   Created detailed delivery structure, success metrics, and post-training support guidelines
-   Integrated realistic business scenarios covering tax compliance, inventory management, and analytics

**Review Date**: 2025-10-15 (after training workshop delivery and user feedback)

---

### Decision: ERP System Menu Reorganization for Trading Company Operations - 2025-01-16

**Context**: Current sidebar menu structure mixed master data and fixed assets together, and lacked dedicated inventory management section, making navigation confusing for trading company users who need quick access to inventory and purchase operations.

**Options Considered**:

1. **Option A**: Keep existing menu structure with minor adjustments

    - ✅ Pros: Minimal development effort, no disruption to existing users
    - ❌ Cons: Poor user experience, confusing navigation, doesn't reflect trading company needs

2. **Option B**: Complete menu reorganization with clear separation of concerns

    - ✅ Pros: Better user experience, logical grouping, trading company focus, scalable structure
    - ❌ Cons: Requires development effort, potential user confusion during transition

3. **Option C**: Add inventory menu without reorganizing existing structure
    - ✅ Pros: Addresses immediate inventory access need, minimal changes
    - ❌ Cons: Doesn't solve underlying navigation issues, still confusing structure

**Decision**: Complete menu reorganization with clear separation of concerns (Option B)

**Rationale**:

-   Trading companies require prominent inventory management access for daily operations
-   Clear separation between master data and fixed assets improves user understanding
-   Logical grouping of related functionality improves workflow efficiency
-   Hierarchical structure supports role-based access control effectively
-   Better reflects business processes and user mental models
-   Scalable structure enables future feature additions
-   Improved user adoption and system utilization

**Implementation**:

-   Reorganized sidebar menu under MAIN section with clear hierarchy
-   Added dedicated Inventory section with Inventory Items, Add Item, Low Stock Report, Valuation Report
-   Separated Master Data (Projects, Funds, Departments) from Fixed Assets
-   Created comprehensive Fixed Assets section with Asset Categories, Assets, Depreciation Runs, Asset Disposals, Asset Movements, Asset Import, Data Quality, Bulk Operations
-   Maintained role-based access control with permission-based menu visibility
-   Preserved existing functionality while improving navigation structure
-   Updated sidebar.blade.php with new menu organization

**Review Date**: 2025-07-16 (after user feedback and usage analytics)

---

### Decision: Dual-Type Inventory System Implementation - 2025-01-17

**Context**: Trading companies need to handle both physical inventory items and services, with different document flows and inventory impact requirements.

**Options Considered**:

1. **Option A**: Separate systems for items and services

    - ✅ Pros: Clear separation, no confusion
    - ❌ Cons: Code duplication, maintenance overhead, inconsistent user experience

2. **Option B**: Single system with type field

    - ✅ Pros: Unified interface, shared business logic, consistent data model
    - ❌ Cons: Additional complexity in validation and business rules

3. **Option C**: Service-only system without inventory integration
    - ✅ Pros: Simple implementation, no inventory complexity
    - ❌ Cons: Limited functionality, poor integration with existing systems

**Decision**: Single system with type field (Option B)

**Rationale**:

-   Unified user experience across all document types
-   Shared business logic reduces code duplication
-   Consistent data model enables better reporting and analytics
-   Flexible document flow supports both item and service workflows
-   Better integration with existing purchase/sales order systems
-   Easier maintenance and future enhancements

**Implementation**:

-   Added `item_type` enum field to `inventory_items` table (item/service)
-   Added `order_type` enum field to `purchase_orders` and `sales_orders` tables
-   Added source tracking fields to `goods_receipts` table (`source_po_id`, `source_type`)
-   Created `sales_invoice_grpo_combinations` table for multi-GRPO tracking
-   Updated models with validation methods for type consistency
-   Added GRPO document type to DocumentNumberingService
-   Implemented business logic to prevent mixing item/service types
-   Service items bypass inventory transactions but maintain accounting impact

**Document Flow**:

-   Item PO → GRPO (with selective line copying) → Sales Invoice (multi-GRPO combination)
-   Service PO → Purchase Invoice (direct, no GRPO needed)
-   Different numbering prefixes for copied documents (GRPO vs GR)

**Review Date**: 2025-04-17 (after Phase 2 implementation and user testing)

---

### Decision: Comprehensive Design Improvements Application Strategy - 2025-01-17

**Context**: ERP system create pages had inconsistent design patterns, poor user experience, and lacked professional appearance. The redesigned PO Create page demonstrated significant improvements in visual design, user experience, and functionality that needed to be applied consistently across all create pages.

**Options Considered**:

1. **Option A**: Keep existing designs with minor improvements

    - ✅ Pros: Minimal development effort, no disruption to existing functionality
    - ❌ Cons: Inconsistent user experience, poor visual design, continued usability issues

2. **Option B**: Apply consistent design improvements across all create pages

    - ✅ Pros: Unified user experience, professional appearance, enhanced functionality, consistent patterns
    - ❌ Cons: Significant development effort, requires updating multiple files

3. **Option C**: Gradual design improvements over time
    - ✅ Pros: Reduced immediate effort, incremental improvement
    - ❌ Cons: Extended inconsistency period, user confusion, maintenance overhead

**Decision**: Apply consistent design improvements across all create pages (Option B)

**Rationale**:

-   Unified design language improves user experience and reduces learning curve
-   Professional appearance enhances system credibility and user adoption
-   Consistent patterns reduce development and maintenance overhead
-   Enhanced functionality (Select2BS4, real-time calculations) improves productivity
-   Better accessibility and responsive design supports diverse user needs
-   Improved form validation and error handling reduces user frustration

**Implementation**:

-   Redesigned 6 create pages: Goods Receipt, Purchase Invoice, Purchase Payment, Sales Order, Sales Invoice, Sales Receipt
-   Applied consistent design patterns: card-outline styling, enhanced headers with icons, responsive 3-column layouts
-   Integrated Select2BS4 for enhanced dropdown functionality with search capabilities
-   Implemented real-time total calculations with Indonesian number formatting
-   Added professional table designs with card-outline sections and striped styling
-   Enhanced navigation with consistent breadcrumbs and "Back" buttons
-   Improved form validation with proper field indicators and error handling
-   Standardized button styling with FontAwesome icons and professional appearance
-   Maintained all existing functionality while significantly enhancing user experience

**Design Standards Applied**:

-   Card-outline styling with proper color schemes
-   Enhanced headers with relevant icons and navigation buttons
-   Responsive Bootstrap grid layouts with proper form groups
-   Select2BS4 integration for improved dropdown experience
-   Real-time calculations with Indonesian number formatting
-   Professional table designs with proper action buttons
-   Consistent error handling and validation messages
-   Standardized page structure with proper sections and footers

**Review Date**: 2025-04-17 (after user feedback and usage analytics)

---

### Decision: Delivery Order System Architecture - 2025-01-18

**Context**: Sales workflow required comprehensive delivery management system with inventory reservation, revenue recognition, and journal entries integration for complete trading company operations from sales order to delivery completion.

**Options Considered**:

1. **Option A**: Basic delivery tracking without inventory integration

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: No inventory management, manual processes, poor integration, limited functionality

2. **Option B**: Comprehensive delivery management system with full integration

    - ✅ Pros: Complete workflow integration, automated journal entries, inventory management, revenue recognition
    - ❌ Cons: Complex implementation, extensive development effort, integration challenges

3. **Option C**: Third-party delivery management integration
    - ✅ Pros: Proven solution, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, integration complexity

**Decision**: Comprehensive delivery management system with full integration (Option B)

**Rationale**:

-   Trading companies require complete delivery lifecycle management for customer service excellence
-   Inventory reservation is critical for accurate stock management and customer order fulfillment
-   Revenue recognition automation ensures proper accounting and financial reporting
-   Journal entries integration provides complete audit trail and financial integration
-   Better integration with existing sales order and inventory systems
-   Full control over delivery process and customization capabilities
-   Cost-effective long-term solution despite higher initial development effort

**Implementation**:

-   Created DeliveryOrder, DeliveryOrderLine, and DeliveryTracking models with comprehensive relationships
-   Implemented DeliveryService with approval workflows, status management, and business logic
-   Built DeliveryOrderController with full CRUD operations, approval/rejection workflows, and print functionality
-   Created comprehensive AdminLTE views (index, create, show, edit, print) with professional design
-   Implemented DeliveryJournalService for automatic inventory reservation and revenue recognition journal entries
-   Added seamless integration with Sales Order system for delivery order creation
-   Implemented complete status tracking from draft to completed with proper approval workflows
-   Added inventory reservation system with automatic stock allocation and release
-   Created revenue recognition system with COGS calculation and accounts receivable management
-   Enhanced data integrity: Foreign key constraint handling with graceful NULL assignment when inventory items are deleted (2026-02-09)
-   Improved UI: Customer-based filtering for Sales Order selection using Select2 `templateResult` function (2026-02-09)
-   Enhanced display: Fallback chain for item information display (item_code → inventoryItem->code, description → inventoryItem->name) (2026-02-09)

**Review Date**: 2025-04-18 (after user feedback and delivery performance analysis)

---

### Decision: Multi-Dimensional Accounting Simplification Strategy - 2025-01-18

**Context**: Multi-dimensional accounting system included projects, funds, and departments dimensions, but funds dimension was rarely used and added unnecessary complexity to the system while projects and departments provided essential cost tracking capabilities.

**Options Considered**:

1. **Option A**: Keep all three dimensions as-is

    - ✅ Pros: Complete flexibility, no disruption to existing functionality
    - ❌ Cons: Unnecessary complexity, maintenance overhead, user confusion, unused functionality

2. **Option B**: Remove funds dimension while maintaining projects and departments

    - ✅ Pros: Reduced complexity, cleaner system, maintained essential functionality, improved user experience
    - ❌ Cons: Requires comprehensive system updates, potential for missed references

3. **Option C**: Make funds dimension optional/configurable
    - ✅ Pros: Flexibility for different organizations, gradual migration
    - ❌ Cons: Continued complexity, configuration overhead, maintenance burden

**Decision**: Remove funds dimension while maintaining projects and departments (Option B)

**Rationale**:

-   Funds dimension was rarely used in practice and added unnecessary complexity
-   Projects and departments provide essential multi-dimensional accounting capabilities
-   Simplified system reduces maintenance overhead and improves user experience
-   Cleaner database schema improves performance and understanding
-   Reduced complexity enables better focus on core multi-dimensional features
-   Projects and departments continue to provide comprehensive cost tracking and allocation
-   Better alignment with actual business usage patterns

**Implementation**:

-   Created comprehensive migration to remove fund_id columns from all relevant tables
-   Updated all models to remove fund relationships while preserving project and department relationships
-   Modified PostingService to remove fund handling while maintaining project and department support
-   Updated all controllers to remove fund references and validation rules
-   Removed fund-related routes, views, and navigation elements
-   Updated sidebar navigation to remove funds section
-   Maintained complete functionality for projects and departments dimensions
-   Preserved all existing multi-dimensional accounting capabilities for essential dimensions

**Review Date**: 2025-04-18 (after user feedback and usage analytics)

---

### Decision: ERP System Menu Reordering and Navigation Optimization - 2025-09-19

**Context**: Current sidebar menu structure did not reflect the natural business process flow for trading company operations, with Business Partner duplicated in both Sales and Purchase sections, and submenus not organized according to operational workflow.

**Options Considered**:

1. **Option A**: Keep existing menu structure with minor adjustments

    - ✅ Pros: Minimal development effort, no disruption to existing users
    - ❌ Cons: Poor user experience, confusing navigation, doesn't reflect trading company workflow

2. **Option B**: Complete menu reorganization with logical business process flow

    - ✅ Pros: Better user experience, logical ordering, trading company workflow alignment, improved efficiency
    - ❌ Cons: Requires development effort, potential user confusion during transition

3. **Option C**: Add Dashboard placeholders without reorganizing structure
    - ✅ Pros: Addresses immediate analytics need, minimal changes
    - ❌ Cons: Doesn't solve underlying navigation issues, still confusing structure

**Decision**: Complete menu reorganization with logical business process flow (Option B)

**Rationale**:

-   Trading companies follow natural workflow: Inventory → Purchase → Sales → Fixed Assets → Business Partner → Accounting → Master Data
-   Logical ordering improves user efficiency and reduces navigation time
-   Standalone Business Partner menu eliminates confusion from duplicated entries
-   Dashboard placeholders prepare for future analytics integration
-   Better reflects business processes and user mental models
-   Scalable structure enables future feature additions
-   Improved user adoption and system utilization

**Implementation**:

-   Reordered main menu items according to business process flow: 1) Inventory, 2) Purchase, 3) Sales, 4) Fixed Assets, 5) Business Partner, 6) Accounting, 7) Master Data
-   Reorganized Purchase submenu: Dashboard, Purchase Orders, Goods Receipts, Purchase Invoices, Purchase Payments
-   Reorganized Sales submenu: Dashboard, Sales Orders, Delivery Orders, Sales Invoices, Sales Receipts
-   Moved Business Partner from duplicated entries in Sales/Purchase to standalone menu item with proper icon (fas fa-handshake)
-   Added Dashboard placeholders as dummy links in Purchase and Sales sections for future analytics integration
-   Updated sidebar.blade.php with new menu organization and proper active state detection
-   Maintained all existing functionality while improving navigation structure
-   **Implementation Status**: Purchase Dashboard implemented with `PurchaseDashboardDataService` and `PurchaseDashboardController`. Sales Dashboard implemented with `SalesDashboardDataService` and `SalesDashboardController` providing AR aging analysis, sales KPIs, and comprehensive sales statistics (2025-11-11).

**Review Date**: 2025-12-19 (after user feedback and usage analytics)

---

### Decision: Business Partner Consolidation Architecture - 2025-09-19

**Context**: Separate customers and vendors tables created data inconsistency, duplicate management overhead, and inability to handle entities that serve as both customers and suppliers in trading company operations.

**Options Considered**:

1. **Option A**: Keep separate customers and vendors tables

    - ✅ Pros: Simple existing structure, no migration required
    - ❌ Cons: Data inconsistency, duplicate management, limited flexibility, poor scalability

2. **Option B**: Create unified Business Partner system with flexible data structure

    - ✅ Pros: Data consistency, unified management, flexible partner types, better scalability, tabbed interface
    - ❌ Cons: Complex migration, requires updating dependent models, higher development effort

3. **Option C**: Create separate tables but with shared management interface
    - ✅ Pros: Preserves existing data structure, unified interface
    - ❌ Cons: Still maintains data inconsistency, complex relationships, maintenance overhead

**Decision**: Create unified Business Partner system with flexible data structure (Option B)

**Rationale**:

-   Trading companies often have entities that serve as both customers and suppliers
-   Unified data structure eliminates data inconsistency and duplicate management
-   Flexible partner_type classification (customer, supplier, both) supports various business relationships
-   Tabbed interface provides better organization of complex partner data
-   Multiple contacts and addresses per partner support real-world business scenarios
-   Flexible attribute storage enables customization without schema changes
-   Backward compatibility ensures smooth transition without breaking existing functionality

**Implementation**:

-   Created unified database schema: business_partners, business_partner_contacts, business_partner_addresses, business_partner_details
-   Implemented BusinessPartner model with partner_type classification and comprehensive relationships
-   Created BusinessPartnerContact, BusinessPartnerAddress, and BusinessPartnerDetail models for flexible data storage
-   Developed tabbed interface with General Information, Contact Details, Addresses, Taxation & Terms, Banking & Financial sections
-   Updated dependent models (PurchaseOrder, SalesOrder, DeliveryOrder) to use new relationships while maintaining backward compatibility
-   Created BusinessPartnerController with comprehensive CRUD operations and DataTables integration
-   Implemented BusinessPartnerService for business logic encapsulation
-   Created data migration tools for seamless transition from separate tables
-   Added comprehensive testing and validation using browser MCP

**Review Date**: 2025-12-19 (after production deployment and user feedback)

---

## Decision: Goods Receipt Testing and DataTables Fixes - 2025-09-19

**Context**: During comprehensive testing of Goods Receipt functionality, we discovered critical issues preventing proper Goods Receipt creation and DataTables errors across all order-related pages showing "Processing..." due to database field mapping issues after business partner consolidation.

**Options Considered**:

1. **Option A**: Fix only Goods Receipt model issues

    - ✅ Pros: Quick fix for immediate testing needs
    - ❌ Cons: Leaves DataTables errors unresolved, incomplete solution

2. **Option B**: Comprehensive fix of all field mapping issues across the system
    - ✅ Pros: Complete resolution, prevents future issues, maintains system consistency
    - ❌ Cons: More extensive changes required

**Decision**: Option B - Comprehensive fix of all field mapping issues

**Rationale**:

-   Goods Receipt model had critical fillable fields issue (vendor_id → business_partner_id)
-   DataTables errors were systemic across all order-related pages due to outdated database queries
-   Business partner consolidation required comprehensive field mapping updates
-   ERP accounting principles validation confirmed proper separation between inventory movements and financial transactions

**Implementation**:

-   Fixed GoodsReceipt model fillable fields and added proper relationships
-   Updated all DataTables routes in routes/web/orders.php to use business_partners table instead of vendors/customers tables
-   Updated all order-related routes (/data and /csv endpoints) to use business_partner_id field
-   Validated ERP accounting principles where Goods Receipts represent physical inventory movements without automatic journal entry creation
-   Ensured all order management functionality works correctly with proper field mapping

**Review Date**: 2025-12-19 (after comprehensive system testing and user validation)

---

## Decision: Comprehensive Inventory Enhancement Implementation - 2025-09-19

**Context**: Need to implement four major inventory enhancement initiatives for advanced trading company operations: Item Category Account Mapping System, System-Wide Audit Trail, Multi-Warehouse Feature, and Sales Price Levels (1-3) with Customer Assignment.

**Options Considered**:

1. **Option A**: Implement features incrementally over multiple phases

    - ✅ Pros: Lower risk, easier testing, gradual user adoption
    - ❌ Cons: Longer implementation timeline, potential integration issues, fragmented user experience

2. **Option B**: Implement all features comprehensively in single phase
    - ✅ Pros: Complete feature set, integrated user experience, comprehensive testing
    - ❌ Cons: Higher complexity, more extensive testing required, larger codebase changes

**Decision**: Implement all four features comprehensively in single phase with proper database design, service architecture, and testing validation.

**Rationale**:

-   All four features are interdependent and benefit from integrated implementation
-   Comprehensive database design ensures proper relationships and data integrity
-   Service-based architecture provides clean separation of concerns
-   Single-phase implementation enables comprehensive testing and validation
-   Browser testing confirms functionality works correctly with existing system

**Implementation**:

-   **Database Schema**: 8 new migrations with proper foreign key relationships and indexes
-   **Models**: 4 new models (Warehouse, InventoryWarehouseStock, AuditLog, CustomerItemPriceLevel) with comprehensive relationships
-   **Services**: 3 new services (AuditLogService, WarehouseService, PriceLevelService) for business logic
-   **Controllers**: 2 new controllers (WarehouseController, AuditLogController) with full CRUD operations
-   **Enhanced Models**: Updated existing models with new relationships and helper methods
-   **Sample Data**: Created 3 warehouses and 5 product categories with account mappings
-   **Routes**: Comprehensive route configuration with middleware and permissions
-   **Testing**: Browser testing validation confirms functionality works correctly

**Review Date**: 2026-03-19 (after 6 months of production use and user feedback)

---

## Decision: Control Account Architecture Implementation - 2025-09-19

**Context**: ERP system required comprehensive Control Account system for accounting accuracy, completeness, reconciliation, and financial reporting with automatic balance tracking, subsidiary ledger management, and reconciliation dashboard for enterprise-level financial control.

**Options Considered**:

1. **Option A**: Manual control account management without automation

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: High error risk, manual reconciliation burden, audit issues, poor scalability

2. **Option B**: Comprehensive control account system with automatic balance tracking and reconciliation

    - ✅ Pros: Full automation, real-time balance tracking, comprehensive reconciliation, audit trail
    - ❌ Cons: Complex implementation, extensive development effort, integration challenges

3. **Option C**: Third-party control account integration
    - ✅ Pros: Proven solution, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, integration complexity

**Decision**: Comprehensive control account system with automatic balance tracking and reconciliation (Option B)

**Rationale**:

-   Control accounts are fundamental to ERP accounting accuracy and financial reporting
-   Automatic balance tracking reduces reconciliation errors and audit issues
-   Real-time reconciliation ensures financial data integrity
-   Comprehensive reconciliation dashboard provides enterprise-level financial control
-   Better integration with existing journal posting and multi-dimensional accounting systems
-   Cost-effective long-term solution despite higher initial development effort
-   Full control over control account logic and reconciliation processes

**Implementation**:

-   **Database Schema**: 3 new tables (control_accounts, subsidiary_ledger_accounts, control_account_balances) with proper relationships and multi-dimensional support
-   **Models**: ControlAccount, SubsidiaryLedgerAccount, ControlAccountBalance with comprehensive relationships and helper methods
-   **Service Layer**: ControlAccountService for business logic, automatic reconciliation, and balance calculation
-   **Integration**: PostingService integration for real-time balance updates on journal posting
-   **Controller**: ControlAccountController with CRUD operations, reconciliation functionality, and data endpoints
-   **Views**: Comprehensive AdminLTE views (index, reconciliation) with professional design and DataTables integration
-   **Routes**: Complete route configuration with middleware and permissions
-   **Menu Integration**: Added to Accounting section in sidebar navigation
-   **Seeder**: ControlAccountSeeder for automatic setup of AR, AP, and Inventory control accounts with existing data
-   **Testing**: Browser testing validation confirms functionality works correctly

**Consequences**: System now has enterprise-level control account architecture with automatic balance tracking, comprehensive reconciliation capabilities, and professional reconciliation dashboard. All control accounts (AR, AP, Inventory) are automatically set up with existing data and provide real-time balance tracking with multi-dimensional accounting support. System provides complete audit trail, variance detection, and reconciliation capabilities enabling accurate financial reporting and compliance.

**Review Date**: 2026-03-19 (after 6 months of production use and user feedback)

---

## Decision: Product Category CRUD Interface Implementation - 2025-09-19

**Context**: The Item Category Account Mapping system was initially implemented with sample data only, requiring a complete CRUD interface to enable users to manage product categories and their account mappings through the web interface.

**Options Considered**:

1. **Option A**: Continue with sample-data-only approach

    - ✅ Pros: No additional development required
    - ❌ Cons: Limited functionality, no user control over categories, poor user experience

2. **Option B**: Implement comprehensive CRUD interface
    - ✅ Pros: Full user control, professional interface, complete functionality
    - ❌ Cons: Additional development time required

**Decision**: Implement comprehensive Product Category CRUD interface with full AdminLTE integration.

**Rationale**:

-   Complete CRUD interface provides full user control over product categories
-   AdminLTE integration ensures consistent user experience with rest of ERP system
-   Account mapping management enables proper financial integration
-   Hierarchical category support provides flexible organization
-   Audit trail integration ensures complete traceability

**Implementation**:

-   **Controller**: ProductCategoryController with full CRUD operations, validation, and audit logging
-   **Views**: Comprehensive AdminLTE views (index, create, show, edit) with proper form handling
-   **Routes**: Complete route configuration with middleware and permissions
-   **Menu Integration**: Added to Master Data section in sidebar navigation
-   **Layout Integration**: Fixed Breeze layout issue by switching to AdminLTE layout
-   **Account Mapping**: Dropdown interfaces for selecting inventory, COGS, and sales accounts
-   **Validation**: Comprehensive form validation with proper error handling
-   **Testing**: Browser testing validation confirms functionality works correctly

**Review Date**: 2026-03-19 (after 6 months of production use and user feedback)

---

## Decision: Control Account Architecture Implementation - 2025-09-19

**Context**: ERP system required comprehensive Control Account system for accounting accuracy, completeness, reconciliation, and financial reporting with automatic balance tracking, subsidiary ledger management, and reconciliation dashboard for enterprise-level financial control.

**Options Considered**:

1. **Option A**: Manual control account management without automation

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: High error risk, manual reconciliation burden, audit issues, poor scalability

2. **Option B**: Comprehensive control account system with automatic balance tracking and reconciliation

    - ✅ Pros: Full automation, real-time balance tracking, comprehensive reconciliation, audit trail
    - ❌ Cons: Complex implementation, extensive development effort, integration challenges

3. **Option C**: Third-party control account integration
    - ✅ Pros: Proven solution, reduced development effort
    - ❌ Cons: External dependency, ongoing costs, limited customization, integration complexity

**Decision**: Comprehensive control account system with automatic balance tracking and reconciliation (Option B)

**Rationale**:

-   Control accounts are fundamental to ERP accounting accuracy and financial reporting
-   Automatic balance tracking reduces reconciliation errors and audit issues
-   Real-time reconciliation ensures financial data integrity
-   Comprehensive reconciliation dashboard provides enterprise-level financial control
-   Better integration with existing journal posting and multi-dimensional accounting systems
-   Cost-effective long-term solution despite higher initial development effort
-   Full control over control account logic and reconciliation processes

**Implementation**:

-   **Database Schema**: 3 new tables (control_accounts, subsidiary_ledger_accounts, control_account_balances) with proper relationships and multi-dimensional support
-   **Models**: ControlAccount, SubsidiaryLedgerAccount, ControlAccountBalance with comprehensive relationships and helper methods
-   **Service Layer**: ControlAccountService for business logic, automatic reconciliation, and balance calculation
-   **Integration**: PostingService integration for real-time balance updates on journal posting
-   **Controller**: ControlAccountController with CRUD operations, reconciliation functionality, and data endpoints
-   **Views**: Comprehensive AdminLTE views (index, reconciliation) with professional design and DataTables integration
-   **Routes**: Complete route configuration with middleware and permissions
-   **Menu Integration**: Added to Accounting section in sidebar navigation
-   **Seeder**: ControlAccountSeeder for automatic setup of AR, AP, and Inventory control accounts with existing data
-   **Testing**: Browser testing validation confirms functionality works correctly

**Consequences**: System now has enterprise-level control account architecture with automatic balance tracking, comprehensive reconciliation capabilities, and professional reconciliation dashboard. All control accounts (AR, AP, Inventory) are automatically set up with existing data and provide real-time balance tracking with multi-dimensional accounting support. System provides complete audit trail, variance detection, and reconciliation capabilities enabling accurate financial reporting and compliance.

**Review Date**: 2026-03-19 (after 6 months of production use and user feedback)

---

## Decision: Goods Receipt PO System Enhancement Implementation - 2025-09-20

**Context**: The existing Goods Receipt system needed enhancement to improve user workflow efficiency and data consistency. Users were experiencing issues with vendor-PO mismatches and manual line entry was time-consuming and error-prone. The system also needed clearer naming to distinguish it from other receipt types.

**Options Considered**:

1. **Option A**: Enhance existing Goods Receipt system without renaming

    - Pros: Minimal disruption, faster implementation
    - Cons: Confusing naming, vendor-PO mismatch issues persist, no workflow improvement

2. **Option B**: Complete system renaming and enhancement with vendor-first workflow

    - Pros: Clear naming, improved workflow, data consistency, automated line copying
    - Cons: Higher implementation effort, requires comprehensive migration

3. **Option C**: Create separate GRPO module alongside existing Goods Receipt
    - Pros: No disruption to existing system
    - Cons: Code duplication, maintenance overhead, user confusion

**Decision**: Selected Option B - Complete system renaming and enhancement with vendor-first workflow

**Rationale**:

-   Complete system renaming provides clear distinction from other receipt types
-   Vendor-first workflow ensures data consistency and prevents vendor-PO mismatches
-   Automated line copying with remaining quantity calculation improves efficiency
-   AJAX-powered PO filtering provides better user experience
-   Comprehensive migration ensures clean, maintainable codebase
-   Enhanced user interface with professional AdminLTE integration
-   Better integration with existing ERP architecture and business processes

**Implementation**:

-   **Database Migration**: Renamed goods_receipts to goods_receipt_po, goods_receipt_lines to goods_receipt_po_lines with proper foreign key management
-   **Model Updates**: GoodsReceipt to GoodsReceiptPO, GoodsReceiptLine to GoodsReceiptPOLine with comprehensive relationships
-   **Controller Migration**: GoodsReceiptController to GoodsReceiptPOController with enhanced functionality
-   **Route Updates**: goods-receipts._ to goods-receipt-pos._ with new AJAX endpoints (/vendor-pos, /remaining-lines)
-   **View Migration**: Complete view directory migration with enhanced user interface
-   **JavaScript Enhancement**: Dynamic form handling with vendor selection triggering PO filtering
-   **Copy Functionality**: Automated copying of Purchase Order lines with remaining quantity calculation
-   **Menu Integration**: Updated sidebar navigation with new naming
-   **Test Data**: Comprehensive test data creation for validation
-   **Testing**: Server-side testing validation with all endpoints working correctly

**Consequences**: System now has enterprise-level Goods Receipt PO solution with sophisticated vendor-first workflow, intelligent PO filtering, automated line copying with remaining quantity calculation, and complete system renaming. Users experience improved workflow efficiency with vendor selection driving PO filtering, automatic line population from source documents, and professional user interface with consistent naming throughout the application. System provides optimal data consistency, reduced manual entry errors, and seamless integration with existing ERP architecture.

**Review Date**: 2026-03-20 (after 6 months of production use and user feedback)

---

## Decision: GRPO Enhanced User Interface Implementation - 2025-09-20

**Context**: Warehouse department users needed enhanced GRPO interface with remaining quantity visibility and guided item selection to prevent errors and improve workflow efficiency. The existing GRPO system lacked clear visibility of remaining quantities from source Purchase Orders and allowed selection of items not present in the PO.

**Options Considered**:

1. **Option A**: Add remaining quantity column and implement PO-based item filtering

    - ✅ Pros: Clear visibility of remaining quantities, guided item selection preventing errors, improved user experience, simplified interface for warehouse users
    - ❌ Cons: Additional JavaScript complexity, modal filtering logic required

2. **Option B**: Keep existing interface with manual quantity entry and all-item selection
    - ✅ Pros: Simpler implementation, no additional complexity
    - ❌ Cons: User errors from selecting wrong items, no visibility of remaining quantities, complex interface with financial columns

**Decision**: Implement Option A with remaining quantity column and PO-based item filtering

**Rationale**: Warehouse department users need clear visibility of remaining quantities and guided item selection to prevent errors. The enhanced interface provides optimal user experience with remaining quantity tracking, intelligent item filtering, and simplified interface without financial columns that warehouse users don't need to modify.

**Implementation**:

-   Added "Remaining Qty" column to GRPO lines table with proper column width adjustments
-   Updated addLineRow JavaScript function to display remaining quantities from PO data
-   Enhanced copy lines functionality to populate remaining quantities from PO pending quantities
-   Implemented PO-based item filtering in item selection modal with loadItemsFromPO and displayItemsFromPO functions
-   Updated item selection handler to populate remaining quantity display automatically
-   Created intelligent filtering system showing only items from selected PO with remaining quantities in modal
-   Simplified interface for warehouse users by removing financial columns (amount, VAT, WTax)

**Consequences**: System now provides enterprise-level GRPO interface with sophisticated remaining quantity tracking and intelligent item filtering capabilities. Warehouse department users experience clear visibility of remaining quantities, guided item selection preventing errors, simplified interface without financial columns, and intuitive workflow from PO copying to item selection. System provides optimal warehouse department experience with remaining quantity visibility, PO-based item filtering, and professional user interface enabling efficient GRPO creation workflow.

**Review Date**: 2026-03-20 (after 6 months of production use and warehouse user feedback)

---

## Decision: Comprehensive ERP System Testing and Field Mapping Resolution - 2025-09-21

**Context**: During comprehensive end-to-end ERP system testing, critical field mapping issues were discovered after the business partner consolidation migration. Controllers were still referencing old field names (vendor_id, customer_id) instead of the new unified business_partner_id field, causing form submission failures across multiple modules.

**Options Considered**:

1. **Option A**: Systematic field mapping resolution across all controllers and services

    - ✅ Pros: Complete system functionality, consistent data handling, production readiness, proper business partner integration
    - ❌ Cons: Extensive code changes required, potential for missed references

2. **Option B**: Revert business partner consolidation to separate vendors/customers tables

    - ✅ Pros: Minimal code changes, existing functionality preserved
    - ❌ Cons: Loss of unified business partner benefits, data consistency issues, duplicate maintenance overhead

3. **Option C**: Partial fix with workarounds for critical issues only
    - ✅ Pros: Quick resolution of blocking issues
    - ❌ Cons: Inconsistent system behavior, technical debt accumulation, incomplete solution

**Decision**: Option A - Systematic field mapping resolution across all controllers and services

**Rationale**: The business partner consolidation provides significant benefits including unified relationship management, data consistency, and support for entities serving as both customers and suppliers. Systematic resolution ensures complete system functionality while maintaining the architectural improvements achieved through consolidation.

**Implementation**:

-   Updated all controllers (PurchaseOrderController, SalesOrderController, SalesInvoiceController, SalesReceiptController, GoodsReceiptController, etc.) to use business_partner_id consistently
-   Fixed all form submissions, JavaScript prefill logic, validation rules, and database queries
-   Updated DataTables column mappings and AJAX endpoints
-   Resolved DocumentClosureService import issues with correct model namespaces
-   Created missing SalesReceiptAllocation model for complete functionality
-   Fixed view template references from customers table to business_partners table

**Consequences**: System now has complete functionality across all modules with consistent business partner handling. All forms submit correctly, all controllers validate properly, all views load without errors, and all JavaScript form handling works correctly. System demonstrates 95% production readiness with comprehensive end-to-end testing validation completed.

**Review Date**: 2026-03-21 (after 6 months of production use and comprehensive testing validation)

---

## Decision: Warehouse Selection System Implementation - 2025-09-21

**Context**: Trading companies require comprehensive warehouse selection functionality across all order types (Purchase Orders, Goods Receipt PO, Sales Orders, Delivery Orders) to enable proper warehouse-specific inventory tracking and management. The system needed to support single warehouse selection per order type with specific business logic: destination warehouse for POs, source warehouse for SOs, default to PO's warehouse for GRPOs (but allow changes), and single warehouse for DOs as required fields.

**Options Considered**:

1. **Option A**: Manual warehouse selection without system integration

    - ✅ Pros: Simple implementation, minimal development effort
    - ❌ Cons: No validation, poor data integrity, manual errors, no business logic enforcement

2. **Option B**: Comprehensive warehouse selection system with database integration and business logic

    - ✅ Pros: Complete validation, data integrity, business logic enforcement, professional user interface, seamless integration
    - ❌ Cons: Complex implementation, extensive development effort, integration challenges

3. **Option C**: Basic warehouse selection with limited validation
    - ✅ Pros: Moderate complexity, basic functionality
    - ❌ Cons: Limited business logic, incomplete integration, poor user experience

**Decision**: Comprehensive warehouse selection system with database integration and business logic (Option B)

**Rationale**:

-   Trading companies require proper warehouse-specific inventory tracking for accurate stock management
-   Single warehouse selection per order type ensures clear inventory flow and prevents confusion
-   GRPO defaulting to PO's warehouse but allowing changes provides flexibility while maintaining consistency
-   Required field validation ensures data completeness and business process integrity
-   Professional user interface with Select2BS4 integration provides excellent user experience
-   Database integration with foreign key constraints ensures data integrity and referential consistency
-   Service layer integration enables proper business logic enforcement and future extensibility

**Implementation**:

-   **Database Schema**: Added warehouse_id foreign key fields to all order tables (purchase_orders, goods_receipt_po, sales_orders, delivery_orders) with proper constraints
-   **Model Updates**: Updated all order models (PurchaseOrder, GoodsReceiptPO, SalesOrder, DeliveryOrder) with BelongsTo relationships to Warehouse model and proper fillable field configuration
-   **Controller Enhancement**: Enhanced all order controllers with comprehensive warehouse validation rules, dropdown population logic, and proper error handling
-   **View Integration**: Implemented professional warehouse selection dropdowns using Select2BS4 in all create/edit forms with active warehouse filtering and proper error handling
-   **Service Layer Updates**: Updated service methods (PurchaseService, SalesService, DeliveryService, GRPOCopyService) to handle warehouse_id parameter passing and business logic integration
-   **Business Logic**: Single warehouse selection per order type (destination warehouse for POs, source warehouse for SOs, single warehouse for DOs) with GRPO defaulting to PO's warehouse but allowing manual changes
-   **Validation**: Comprehensive validation rules ensuring warehouse_id is required and exists in warehouses table
-   **Testing**: Comprehensive browser testing validation across all order types with confirmed functionality

**Consequences**: System now has enterprise-level warehouse selection system providing comprehensive warehouse management across all order types with proper validation, user interface integration, and business logic support. All order types support single warehouse selection with required field validation, proper foreign key relationships, and seamless integration with existing order management workflows. System enables proper warehouse-specific inventory tracking and management for trading company operations with professional user interface and comprehensive business logic enforcement.

**Review Date**: 2026-03-21 (after 6 months of production use and user feedback)

---

## Decision: Transit Warehouse Filtering Implementation - 2025-09-21

**Context**: Transit warehouses are used exclusively for automatic ITO/ITI (Inventory Transfer Out/Inventory Transfer In) activities and should not be manually selectable by users in order creation forms. The system needed to filter out transit warehouses from manual warehouse selection dropdowns while preserving their functionality for automated inventory transfer operations.

**Options Considered**:

1. **Option A**: Allow transit warehouses in manual selection dropdowns

    - ✅ Pros: Simple implementation, no filtering required
    - ❌ Cons: User confusion, incorrect warehouse selection, business logic violations, poor user experience

2. **Option B**: Filter out transit warehouses from manual selection dropdowns

    - ✅ Pros: Clean user interface, proper business logic separation, prevents user errors, improved user experience
    - ❌ Cons: Additional filtering logic required, database query modifications

3. **Option C**: Separate transit warehouse management system
    - ✅ Pros: Complete separation of concerns
    - ❌ Cons: Complex implementation, duplicate functionality, maintenance overhead

**Decision**: Filter out transit warehouses from manual selection dropdowns (Option B)

**Rationale**:

-   Transit warehouses serve specific automated functions and should not be manually selectable
-   Filtering prevents user confusion and incorrect warehouse selection
-   Clean user interface shows only relevant warehouses for manual selection
-   Proper business logic separation between manual operations and automated ITO/ITI activities
-   Transit warehouses follow naming convention (e.g., WH001_TRANSIT for WH001) enabling reliable filtering
-   Improved user experience with clear warehouse selection options
-   Maintains transit warehouse functionality for automated operations

**Implementation**:

-   **Database Query Enhancement**: Applied where('name', 'not like', '%Transit%') condition to all warehouse dropdown queries across all order controllers
-   **Controller Updates**: Updated PurchaseOrderController, GoodsReceiptPOController, SalesOrderController, DeliveryOrderController to exclude transit warehouses from both create and edit methods
-   **Consistent Filtering**: Applied filtering consistently across all order types ensuring uniform behavior
-   **User Interface Improvement**: Clean warehouse selection interface showing only regular warehouses (Branch Warehouse, Distribution Center, Main Warehouse, Regional Distribution Center - Updated) while hiding transit warehouses
-   **Business Logic Separation**: Proper separation between manual warehouse selection for business operations and automatic transit warehouse usage for ITO/ITI activities
-   **Transit Warehouse Logic**: Transit warehouses follow naming convention and are automatically used in ITO/ITI operations based on source warehouse
-   **Testing**: Comprehensive browser testing validation across all order types confirming that transit warehouses are properly excluded from manual selection

**Consequences**: System now has enterprise-level warehouse filtering system ensuring proper separation between manual warehouse selection for business operations and automatic transit warehouse usage for ITO/ITI activities. Users experience clean warehouse selection interface with only relevant warehouses available for manual selection while transit warehouse functionality is preserved for automated inventory transfer operations. System provides improved user experience, prevents user errors, and maintains proper business logic separation.

**Review Date**: 2026-03-21 (after 6 months of production use and user feedback)

### Decision: Phase 3 Advanced Features and Optimizations Implementation - 2025-09-22

**Context**: After successfully implementing the Enhanced Document Navigation & Journal Preview Features (Phase 1) and adding navigation components to all document types (Phase 2), the system needed advanced features and optimizations to provide enterprise-level performance, comprehensive user experience enhancements, and detailed analytics capabilities for production readiness.

**Options Considered**:

1. **Option A**: Deploy current system without advanced features

    - ✅ Pros: Immediate deployment, no additional development time
    - ❌ Cons: Limited performance optimization, no analytics capabilities, basic user experience, potential scalability issues

2. **Option B**: Implement comprehensive advanced features and optimizations

    - ✅ Pros: Enterprise-level performance, comprehensive analytics, advanced UI features, production-ready scalability, data-driven optimization capabilities
    - ❌ Cons: Significant development effort, complex architecture, comprehensive testing required

3. **Option C**: Implement only basic performance optimizations
    - ✅ Pros: Moderate performance improvement, limited development effort
    - ❌ Cons: Missing advanced features, limited analytics, incomplete optimization, not production-ready

**Decision**: Implement comprehensive advanced features and optimizations (Option B)

**Rationale**:

-   Enterprise-level performance requirements demand sophisticated caching and optimization
-   Comprehensive analytics enable data-driven optimization and user behavior insights
-   Advanced UI features (tooltips, keyboard shortcuts) significantly improve user experience
-   Production readiness requires complete performance monitoring and optimization capabilities
-   Bulk operations enable efficient document processing for large datasets
-   Advanced features provide competitive advantage and professional system capabilities
-   Comprehensive testing validates all features work correctly with caching system
-   Future scalability requires sophisticated architecture foundation

**Implementation**:

-   **Caching System**: DocumentRelationshipCacheService with intelligent TTL management, automatic cache invalidation, and cache warming capabilities
-   **Bulk Operations**: DocumentBulkOperationService for efficient bulk document processing, workflow chain analysis, and document statistics
-   **Advanced UI**: AdvancedDocumentNavigation.js with tooltips, keyboard shortcuts, client-side caching, and real-time UI updates
-   **Performance Optimization**: DocumentPerformanceOptimizationService with query optimization, eager loading, and memory management
-   **Analytics System**: DocumentAnalyticsService with comprehensive usage tracking, performance metrics, and analytics report generation
-   **Database Schema**: document_analytics table with comprehensive indexing for performance analytics
-   **API Architecture**: DocumentAnalyticsController with RESTful endpoints for analytics data collection and retrieval
-   **Cache Management**: php artisan documents:cache-relationships command for cache management and statistics
-   **Integration**: Seamless integration with existing ERP architecture and AdminLTE UI framework

**Consequences**: System now provides enterprise-level advanced features with sophisticated caching reducing database queries by up to 80%, comprehensive analytics tracking with usage patterns and performance metrics, advanced JavaScript components with keyboard shortcuts and tooltips, comprehensive performance optimization with eager loading and query caching, and detailed analytics capabilities enabling data-driven optimization. System is production-ready with enterprise-level performance, comprehensive user experience enhancements, and detailed analytics capabilities for efficient document management and trading company operations.

**Review Date**: 2026-03-22 (after 6 months of production use and performance analytics review)

---

## Decision: Document Relationship Map Visualization Technology - 2025-09-22

**Context**: Need to implement visual representation of document workflows (PO→GRPO→PI→PP, SO→DO→SI→SR) to help users understand complete document chains and navigate between related documents efficiently.

**Options Considered**:

1. **Custom SVG Implementation**

    - ✅ Pros: Full control over styling, lightweight, no external dependencies
    - ❌ Cons: Complex to implement, requires custom drawing logic, difficult to maintain

2. **D3.js Library**

    - ✅ Pros: Powerful visualization capabilities, extensive customization options
    - ❌ Cons: Steep learning curve, large bundle size, complex for simple flowcharts

3. **Mermaid.js Library**

    - ✅ Pros: Simple syntax, professional appearance, built-in flowchart support, lightweight
    - ❌ Cons: Limited customization compared to D3.js, external dependency

4. **Static Image Generation**
    - ✅ Pros: No JavaScript dependencies, consistent rendering
    - ❌ Cons: Not interactive, requires server-side generation, poor user experience

**Decision**: Mermaid.js Library

**Rationale**: Mermaid.js provides the optimal balance of simplicity, professional appearance, and functionality for document workflow visualization. The library offers built-in flowchart support with simple syntax, professional styling that integrates well with AdminLTE, and interactive capabilities. The lightweight nature and ease of implementation make it ideal for the ERP system's requirements.

**Implementation**:

-   Integrated Mermaid.js 10.6.1 via CDN
-   Created reusable modal component with Mermaid.js integration
-   Implemented modern async/await API for SVG rendering
-   Added zoom controls and interactive node clicking
-   Professional AdminLTE styling integration

**Review Date**: 2025-12-22 (6 months from implementation)

---

### Decision: Direct Cash Purchase Feature & UI Simplification - 2025-12-26

**Context**: Users frequently perform direct cash purchases (buying items with immediate cash payment) and prefer simplified workflow (Purchase Invoice → Post) instead of full PO → GRPO → PI → PP flow. The system lacked support for direct cash purchases, automatic account selection for non-accounting users, and automatic inventory updates. Additionally, the "Direct Purchase" checkbox overlapped with Payment Method selection, causing user confusion and redundant data entry.

**Options Considered**:

1. **Option A**: Keep existing workflow, require full PO → GRPO → PI → PP flow for all purchases.

    - ✅ Pros: Consistent workflow, simpler implementation.
    - ❌ Cons: Doesn't match user needs, too many steps for simple cash purchases, poor user experience.

2. **Option B**: Add Direct Purchase checkbox, keep manual account selection.

    - ✅ Pros: Supports direct purchases, maintains flexibility.
    - ❌ Cons: Redundant checkbox (overlaps with Payment Method), manual account selection error-prone, non-accounting users see accounts.

3. **Option C**: Auto-set `is_direct_purchase` based on Payment Method, remove checkbox, auto-select accounts from product categories.
    - ✅ Pros: Matches user expectations (cash = direct purchase), simplified UI, automatic account selection, better UX.
    - ❌ Cons: Requires business logic changes, needs product category account mapping.

**Decision**: Adopt Option C—auto-set `is_direct_purchase` when `payment_method = 'cash'` and no PO/GRPO, remove redundant checkbox, implement automatic account selection from product categories, and add cash account selection dropdown.

**Rationale**:

-   Auto-setting `is_direct_purchase` based on payment method and context matches user mental model (cash payment = direct purchase).
-   Removing redundant checkbox simplifies UI and reduces user confusion.
-   Automatic account selection from product categories reduces errors and supports non-accounting users.
-   Cash account selection provides flexibility for multiple cash accounts while maintaining backward compatibility.
-   Direct cash purchase accounting flow (Debit Inventory, Credit Cash) is simpler and more accurate than credit flow for immediate cash transactions.
-   Multi-UOM support enables flexible unit management for inventory items.

**Implementation**:

-   **Database Schema**: Added `inventory_item_id`, `warehouse_id`, `order_unit_id`, `base_quantity`, `unit_conversion_factor` to `purchase_invoice_lines`; added `payment_method`, `is_direct_purchase`, `cash_account_id` to `purchase_invoices`.
-   **Service Layer**: Created `PurchaseInvoiceService` for business logic encapsulation (auto-account selection, warehouse validation, inventory transaction creation).
-   **Controller Logic**: Updated `PurchaseInvoiceController` to auto-set `is_direct_purchase = 1` when `payment_method = 'cash'` and no PO/GRPO, implemented direct cash accounting flow, integrated `InventoryService` for automatic inventory transactions.
-   **UI Changes**: Removed "Direct Purchase" checkbox, added Item/Warehouse/UOM selection, added cash account dropdown (shown when cash + direct purchase), updated JavaScript to handle field visibility.
-   **Accounting Flow**: Direct cash purchases use Debit Inventory, Credit Cash; credit purchases use Debit AP UnInvoice, Credit Utang Dagang.
-   **Inventory Integration**: Automatic inventory transaction creation for direct purchases with stock updates and valuation.

**Review Date**: 2026-12-26 (after full year of production use with direct cash purchase feature).

---

### Decision: Inventory-Warehouse Stock Accuracy Verification & Reconciliation - 2026-02-03

**Context**: Discovered data integrity issue where inventory items had stock calculated from transactions (`current_stock`) but warehouse stock records (`inventory_warehouse_stock`) were missing or incorrect. System-wide check revealed 78 items with discrepancies, 72 items with stock but no warehouse records. This caused inconsistencies between Current Stock (from transactions) and Stock by Warehouse sections, making warehouse-level stock tracking unreliable.

**Options Considered**:

1. **Option A**: Manual database queries and updates to fix discrepancies.

    - ✅ Pros: Direct control, immediate fixes.
    - ❌ Cons: Error-prone, time-consuming, no systematic verification, no reusable solution.

2. **Option B**: Create artisan commands for verification and reconciliation.
    - ✅ Pros: Systematic approach, reusable, safe (transaction-wrapped), provides detailed reporting, enables batch processing.
    - ❌ Cons: Requires command development.

**Decision**: Adopt Option B—create `CheckInventoryAccuracy` command for verification and `ReconcileWarehouseStock` command for systematic reconciliation.

**Rationale**:

-   Artisan commands provide systematic, repeatable approach to data integrity verification and correction.
-   Verification command enables proactive monitoring of stock accuracy across all items.
-   Reconciliation command safely allocates stock to warehouses for transactions missing `warehouse_id` and creates/updates warehouse stock records.
-   Transaction-wrapped operations ensure data consistency during reconciliation.
-   Detailed reporting helps identify root causes of discrepancies (missing warehouse_id, incorrect warehouse allocation).
-   Batch processing capability enables fixing all items at once or specific items individually.

**Implementation**:

-   **CheckInventoryAccuracy Command**: Verifies accuracy between `current_stock` (from transactions) and sum of warehouse stock records. Shows detailed breakdown including transaction analysis, warehouse allocation, and discrepancy detection. Supports checking specific items or all items.
-   **ReconcileWarehouseStock Command**: Reconciles warehouse stock from transactions by grouping transactions by warehouse, allocating stock to default warehouse for transactions without `warehouse_id`, and creating/updating warehouse stock records. Supports specific items or batch processing.
-   **Data Integrity**: Ensures `current_stock` always equals sum of warehouse stock records for accurate multi-warehouse inventory management.

**Review Date**: 2026-08-03 (6 months from implementation).

---

### Decision: Warehouse Transfer Page Enhancement - Dedicated Page with Searchable Items - 2026-02-03

**Context**: Warehouse transfer functionality was implemented as a modal form, which became ineffective with many items (84+ items) due to select dropdown limitations. Modal also lacked sufficient space to display comprehensive stock information (source warehouse stock, destination warehouse stock, after-transfer projections). Users needed better visibility into stock levels and more efficient item selection mechanism for warehouse transfers.

**Options Considered**:

1. **Option A**: Keep modal form, improve dropdown with pagination or filtering.

    - ✅ Pros: Minimal changes, maintains current UI pattern.
    - ❌ Cons: Still limited by modal space, dropdowns inefficient for large datasets, poor UX for comprehensive information display.

2. **Option B**: Create dedicated page with Select2 AJAX search and comprehensive stock information display.
    - ✅ Pros: Efficient item selection (searchable), comprehensive stock information display, real-time updates, better UX, scales to large inventories.
    - ❌ Cons: Requires new page creation, more development effort.

**Decision**: Adopt Option B—create dedicated warehouse transfer page with Select2 AJAX search and comprehensive stock information display.

**Rationale**:

-   Dedicated pages provide better UX for complex forms with many options and comprehensive information display.
-   Select2 AJAX search is essential for large datasets (84+ items) as it enables efficient searching without loading all options upfront.
-   Real-time stock information display improves decision-making by showing current stock levels, projected stock after transfer, and validation alerts.
-   Visual progress bars and color-coded alerts enhance user experience.
-   The dedicated page approach scales better than modals for feature-rich forms requiring extensive data display and validation.

**Implementation**:

-   **New Route**: Created `/warehouses/transfer` route with `WarehouseController::showTransferPage()` method.
-   **Select2 AJAX Integration**: Implemented searchable item selection using Select2 with AJAX against `/inventory/api/search` endpoint, enabling efficient item search from large inventory.
-   **Stock Information Display**: Added comprehensive stock information cards showing source warehouse stock, destination warehouse stock, and after-transfer projections with visual progress bars and status alerts.
-   **Real-time Updates**: Implemented JavaScript to update stock information dynamically when selecting warehouses or changing quantity.
-   **Transfer Types**: Maintained support for all transfer types (Direct, ITO, ITI) with dynamic form switching.
-   **UI Updates**: Updated warehouses index page to link to new transfer page instead of opening modal.

**Review Date**: 2026-08-03 (6 months from implementation).

---

### Decision: Warehouse Transfer Item Search Enhancement - Custom Autocomplete Replacement - 2026-02-03

**Context**: After implementing Select2 AJAX search for warehouse transfer page, users experienced issues: Select2 dropdown showed "No results found" even when API returned data correctly, search endpoint returned 500 errors due to problematic `category.parent` eager loading causing serialization issues, and search logic incorrectly combined multiple parameters (`q`, `code`, `name`) with AND logic instead of OR, limiting results. When users typed "CO", only one item (CON000006) appeared instead of all items matching "CO" in code or name. Users needed reliable search that works for both item codes and names with proper case-insensitive matching.

**Options Considered**:

1. **Option A**: Fix Select2 configuration and search endpoint issues, keep Select2.

    - ✅ Pros: Uses existing library, familiar pattern, less custom code.
    - ❌ Cons: Select2 has complex event handling, difficult to debug, Bootstrap theme compatibility issues, limited control over search behavior, ongoing maintenance burden.

2. **Option B**: Replace Select2 with custom autocomplete solution using Bootstrap-native components.
    - ✅ Pros: Full control over search behavior, easier debugging, Bootstrap-native styling, no external library dependencies, better error handling, simpler code, case-insensitive search with proper OR logic, search term highlighting, keyboard navigation.
    - ❌ Cons: Requires custom implementation, need to handle edge cases manually.

**Decision**: Adopt Option B—replace Select2 with custom autocomplete solution using regular input field with AJAX search and Bootstrap-styled dropdown.

**Rationale**:

-   Custom solution provides full control over search behavior and error handling, making debugging easier.
-   Bootstrap-native styling ensures consistent UI without external library dependencies or theme compatibility issues.
-   Case-insensitive search with proper OR logic (searching both code and name) improves user experience.
-   Debounced search (300ms) reduces API calls while maintaining responsive feel.
-   Visual feedback (highlighting matching text, loading states) improves usability.
-   Keyboard navigation (arrow keys, Enter, Escape) provides accessibility.
-   Simpler codebase without Select2 dependency reduces maintenance burden.
-   Fixed search endpoint ensures reliable results for both code and name searches.

**Implementation**:

-   **Frontend**: Replaced Select2 with custom autocomplete in `resources/views/warehouses/transfer.blade.php`: regular input field (`#item_search`), hidden input for form submission (`#item_id`), Bootstrap-styled dropdown (`#item_search_results`), debounced AJAX search (300ms delay), keyboard navigation support, click-to-select functionality, clear button, auto-hide dropdown on outside click, search term highlighting in results.
-   **Backend**: Fixed `/inventory/search` endpoint in `app/Http/Controllers/InventoryController.php`: removed problematic `category.parent` eager loading, made search case-insensitive using `LOWER()` SQL functions, fixed search logic to use single `q` parameter with OR logic (`LOWER(code) LIKE ... OR LOWER(name) LIKE ...`), improved ordering (code matches first, then name matches), increased result limit to 50 items, added proper error handling with try-catch.
-   **Search Features**: Case-insensitive search, searches both code and name fields, highlights matching text in results, shows up to 50 results, proper error messages, loading states.

**Review Date**: 2026-08-03 (6 months from implementation).
