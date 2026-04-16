# Account Statements System Implementation

**Purpose**: Comprehensive documentation of the Account Statements functionality implementation  
**Last Updated**: 2026-04-16 · **Related note**: 2026-04-07 — partner detail GL tab: [`docs/BUSINESS-PARTNER-ACCOUNT-STATEMENT.md`](./BUSINESS-PARTNER-ACCOUNT-STATEMENT.md)  
**End-user / HELP manuals**: [`docs/manuals/account-statements-module-manual-en.md`](../manuals/account-statements-module-manual-en.md) · [`docs/manuals/account-statements-module-manual-id.md`](../manuals/account-statements-module-manual-id.md)  
**Status**: ✅ COMPLETE (maintained)

## Overview

The Account Statements system provides comprehensive financial statement generation for both General Ledger (GL) accounts and Business Partners. This system enables users to generate detailed transaction reports with running balances, supporting both internal accounting needs and external business partner communications.

## See also (partner detail — GL account statement tab)

The **Business Partner** record screen has a separate **Account statement** tab (not stored in `account_statements`). It shows **posted GL activity** scoped to trade control accounts and partner-linked journal sources. That feature is documented in **[`docs/BUSINESS-PARTNER-ACCOUNT-STATEMENT.md`](./BUSINESS-PARTNER-ACCOUNT-STATEMENT.md)**. Do not confuse it with the **Account Statements** module described in *this* file (`account_statements`, `AST-…` numbering, `AccountStatementController`).

## Key Features

### Dual-Type Support

-   **GL Account Statements**: Shows all journal entries for specific Chart of Accounts entries
-   **Business Partner Statements**: Shows all invoices, receipts, and payments for specific business partners

### Core Functionality

-   **Automatic Statement Generation**: Creates statements with opening/closing balance calculation
-   **Transaction Tracking**: Comprehensive transaction history with running balances
-   **Multi-Dimensional Support**: Project and department filtering capabilities
-   **Document Numbering**: Automatic statement numbering with AST-YYYYMM-###### format
-   **Permission-Based Access**: Role-based access control with granular permissions

## Technical Implementation

### Database Schema

#### account_statements Table

Aligned with `database/migrations/2025_09_19_114520_create_account_statements_table.php` (plus later migrations such as `company_entity_id`):

- `id`, `statement_no` (unique), `statement_type` (`gl_account` | `business_partner`)
- `account_id`, `business_partner_id` (nullable FKs per type)
- `statement_date`, `from_date`, `to_date`
- `opening_balance`, `closing_balance`, `total_debits`, `total_credits`
- `status` enum: **`draft`**, **`finalized`**, **`cancelled`** (default `draft`)
- `notes`, `created_by`, `finalized_by`, `finalized_at`, `timestamps`
- `company_entity_id` (entity-aware numbering; default entity for reporting documents)

#### account_statement_lines Table

```sql
- id: Primary key
- account_statement_id: Foreign key to account_statements
- transaction_date: Date of the transaction
- reference_type: Type of reference (Journal, SalesInvoice, etc.)
- reference_id: ID of the referenced document
- reference_no: Document number of the reference
- description: Transaction description
- debit_amount: Debit amount
- credit_amount: Credit amount
- running_balance: Calculated running balance
- project_id: Project dimension (nullable)
- dept_id: Department dimension (nullable)
- memo: Additional memo information
- sort_order: Ordering within the same date
- timestamps: created_at, updated_at
```

### Models

#### AccountStatement Model

-   **Relationships**: BelongsTo Account, BusinessPartner, User (creator/finalizer), CompanyEntity; HasMany AccountStatementLine
-   **Scopes**: e.g. `draft()`, `finalized()`, `glAccounts()`, `businessPartners()`, date/account/partner scopes
-   **Workflow**: `finalize()`, `cancel()`, `canBeFinalized()` (draft + at least one line)

#### AccountStatementLine Model

-   **Relationships**: BelongsTo AccountStatement, Project, Department
-   **Dynamic Relationships**: MorphTo reference for different document types
-   **Casting**: Proper date and decimal casting for data integrity

### Service Layer

#### AccountStatementService

-   **generateStatement()**: Main method for creating account statements
-   **calculateOpeningBalance()**: Calculates balance before statement period
-   **fetchTransactions()**: Retrieves relevant transactions for the period
-   **Business Logic**: Handles both GL account and Business Partner statement generation

### Controller

#### AccountStatementController

-   **CRUD Operations**: index, create, store, show, edit, update, destroy
-   **Statement Actions**: `finalize`, `cancel` (backend route exists; **Cancel** is not exposed as a button on standard Blade views — users primarily **Finalize** or **Delete** drafts)
-   **Export/Print**: export, print for document generation
-   **API Endpoints**: account-balance, business-partner-balance for preview functionality
-   **Validation (`store`)**: `account_id` and `business_partner_id` use **`nullable`** together with **`required_if`** so the hidden, empty select for the *other* statement type does not fail `exists:*` validation (see `docs/decisions.md`, 2026-04-16).

### Views

#### Layout Standardization

All views follow the Sales Orders layout pattern for consistency:

-   **Index View**: Clean table with inline filters in header
-   **Create View**: Simple form layout with dynamic field visibility
-   **Show View**: Comprehensive statement display with transaction details

#### Key Features

-   **Dynamic Forms**: Account vs Business Partner groups toggled by statement type; **create** view uses server-rendered visibility (`$effectiveStatementType`) so the correct fields are shown even before JavaScript runs
-   **Validation UX**: Global **`@if ($errors->any())`** alert on create form lists server-side validation failures (avoids “nothing happened” perception)
-   **Filter Integration**: Comprehensive filtering by type, account, partner, status, dates
-   **Responsive Design**: Mobile-friendly AdminLTE integration
-   **Action Buttons**: Context-sensitive actions based on statement status (Finalize on draft; Delete when not finalized)

## Integration Points

### Document Numbering Service

-   **Integration**: Added 'account_statement' type with 'AST' prefix
-   **Sequencing**: Month-based sequence tracking with thread-safe generation
-   **Format**: AST-YYYYMM-###### consistent with other document types

### Permission System

-   **Permissions**: account_statements.view, create, update, delete
-   **Role Assignment**: Automatically assigned to 'superadmin' role
-   **Middleware**: Permission-based route protection

### Navigation Integration

-   **Menu Item**: Added to Accounting submenu in sidebar
-   **Breadcrumbs**: Proper navigation breadcrumb trails
-   **Active States**: Menu highlighting for current page

## Business Logic

### GL Account Statements

1. **Transaction Source**: Journal lines from journals table
2. **Balance Calculation**: Sum of debit - credit from journal lines
3. **Period Filtering**: Transactions within specified date range
4. **Running Balance**: Cumulative balance calculation per transaction

### Business Partner Statements

1. **Transaction Sources**: Sales invoices, sales receipts, purchase invoices, purchase payments
2. **Balance Calculation**: AR balance (invoices - receipts) - AP balance (invoices - payments)
3. **Status Filtering**: Only posted/completed transactions included
4. **Reference Tracking**: Links to original documents for audit trail

## Testing and Validation

### Browser MCP Testing

-   **Index Page**: Verified proper layout, filters, and table display
-   **Create Page**: Tested form functionality and dynamic field behavior
-   **Navigation**: Confirmed proper sidebar integration and breadcrumbs
-   **Permissions**: Validated role-based access control

### Database Testing

-   **Migration**: Successfully created all tables with proper relationships
-   **Data Integrity**: Verified foreign key constraints and indexes
-   **Performance**: Optimized queries with proper indexing

## Maintenance notes (2026-04-16)

-   **Store validation**: `AccountStatementController::store` validates `account_id` / `business_partner_id` as `nullable|required_if:…|exists:…` so posting an empty hidden partner field on **GL Account Statement** does not trigger “The selected business partner id is invalid.”
-   **HELP / RAG**: User-facing manuals `account-statements-module-manual-*.md` and `help-navigation.json` entry `account-statements-formal`; run **`php artisan help:reindex`** after deploy.
-   **Status workflow**: Documented in manuals — **Draft** → **Finalize** (UI); **cancelled** via `cancel` route if integrated later; **Delete** removes non-finalized rows.

## Future Enhancements

### Potential Improvements

-   **Export Formats**: PDF, Excel export functionality
-   **Scheduled Generation**: Automated statement generation
-   **Email Integration**: Automatic statement delivery to business partners
-   **Advanced Filtering**: More granular filtering options
-   **Statement Templates**: Customizable statement formats

### Technical Debt

-   **Balance Preview**: Removed complex AJAX balance preview for layout consistency
-   **Select2 Integration**: Simplified dropdowns to match existing patterns
-   **Complex Styling**: Streamlined to maintain consistency with Sales Orders layout

## Conclusion

The Account Statements system successfully provides comprehensive financial statement generation capabilities with dual-type support for both GL accounts and Business Partners. The implementation follows established patterns, integrates seamlessly with existing systems, and provides a solid foundation for future enhancements.

The layout standardization ensures consistent user experience across the ERP system while maintaining all core functionality. The system is production-ready and fully integrated with the existing Sarange ERP infrastructure.
