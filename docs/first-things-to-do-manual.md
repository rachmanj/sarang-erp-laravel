# First Things to Do - ERP System Setup Manual

**Purpose**: Step-by-step guide for initial ERP system configuration  
**Target Audience**: System Administrators and Implementation Team  
**Estimated Time**: 4-6 hours for complete setup

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Phase 1: System Configuration](#phase-1-system-configuration)
3. [Phase 2: Financial Foundation](#phase-2-financial-foundation)
4. [Phase 3: Master Data Setup](#phase-3-master-data-setup)
5. [Phase 4: Business Operations Setup](#phase-4-business-operations-setup)
6. [Phase 5: User Management](#phase-5-user-management)
7. [Phase 6: Verification and Testing](#phase-6-verification-and-testing)
8. [Quick Reference Checklist](#quick-reference-checklist)

---

## Prerequisites

Before starting the setup process, ensure:

- ✅ Database migrations have been run (`php artisan migrate`)
- ✅ Database seeders have been executed (`php artisan db:seed`)
- ✅ Admin user account exists (default: `admin@example.com` / `password`)
- ✅ System is accessible via web browser
- ✅ You have administrative access to the system

---

## Phase 1: System Configuration

**Estimated Time**: 30-45 minutes  
**Priority**: 🔴 CRITICAL - Must be completed first

### 1.1 Company Information Setup

**Location**: `Admin > ERP Parameters > Company Info`

Configure your company's basic information that will appear on all documents:

1. **Company Name**
   - Navigate to: `Admin > ERP Parameters`
   - Category: `company_info`
   - Update: `company_name` parameter
   - Example: "PT Sarang Trading Indonesia"

2. **Company Address**
   - Update: `company_address` parameter
   - Example: "Jl. Sudirman No. 123, Jakarta Pusat 10110"

3. **Contact Information**
   - Update: `company_phone` (e.g., "+62 21 1234 5678")
   - Update: `company_email` (e.g., "info@company.com")
   - Update: `company_website` (e.g., "www.company.com")

4. **Tax Information**
   - Update: `company_tax_number` (NPWP)
   - Example: "01.234.567.8-901.000"

5. **Company Logo**
   - Upload company logo to `public/` directory
   - Update: `company_logo_path` parameter with filename
   - Supported formats: PNG, JPG, SVG

**Why This Matters**: This information appears on all purchase orders, sales invoices, and official documents.

---

### 1.2 Company Entities (Multi-Entity Setup)

**Location**: `Admin > Company Entities` (if multi-entity enabled)

If your organization operates multiple legal entities:

1. **Verify Entity Setup**
   - Check that all legal entities are created
   - Default entities: PT Cahaya Sarange Jaya (Code: 71), CV Cahaya Saranghae (Code: 72)

2. **Configure Entity Details**
   - Update entity names, addresses, tax numbers
   - Upload entity-specific logos
   - Configure letterhead metadata (colors, branding)

3. **Set Default Entity**
   - Determine which entity should be default for new documents
   - Configure in ERP Parameters if needed

**Why This Matters**: Each entity requires separate document numbering, letterheads, and reporting.

---

### 1.3 System Settings Configuration

**Location**: `Admin > ERP Parameters > System Settings`

Configure core system behavior:

1. **Default Currency**
   - Parameter: `default_currency` (default: "IDR")
   - Parameter: `default_currency_id` (set to IDR currency ID)
   - Verify currency exists in system

2. **Timezone**
   - Parameter: `default_timezone` (default: "Asia/Jakarta")
   - Ensure matches your business location

3. **Document Closure Settings**
   - Configure overdue thresholds:
     - `po_overdue_days`: 30 days
     - `grpo_overdue_days`: 15 days
     - `pi_overdue_days`: 7 days
     - `so_overdue_days`: 30 days
     - `do_overdue_days`: 15 days
     - `si_overdue_days`: 7 days
   - `auto_close_days`: 90 days
   - `enable_auto_closure`: true/false

4. **Price Handling**
   - `allow_price_differences`: true/false
   - `max_price_difference_percent`: 10%

**Why This Matters**: These settings control automatic document processing, currency handling, and business rule enforcement.

---

## Phase 2: Financial Foundation

**Estimated Time**: 1-2 hours  
**Priority**: 🔴 CRITICAL - Required before transactions

### 2.1 Chart of Accounts Verification

**Location**: `Accounting > Chart of Accounts`

1. **Verify Chart of Accounts Loaded**
   - System should have 118+ PSAK-compliant accounts
   - Check account structure:
     - **Assets (1.x.x.x)**: Cash, Bank, Inventory, Receivables
     - **Liabilities (2.x.x.x)**: Payables, Accrued Expenses
     - **Equity (3.x.x.x)**: Share Capital, Retained Earnings
     - **Revenue (4.x.x.x)**: Sales Revenue, Service Revenue
     - **Expenses (5.x.x.x)**: COGS, Operating Expenses

2. **Verify Key Accounts Exist**
   - **Cash Accounts**:
     - `1.1.1.01` - Kas di Bank - Operasional
     - `1.1.1.02` - Kas di Bank - Payroll
   - **Inventory Accounts**:
     - `1.1.3.01` - Persediaan Barang Dagangan
   - **Receivable/Payable Accounts**:
     - `1.1.2.01` - Piutang Dagang
     - `1.1.2.04` - AR UnInvoice (Intermediate)
     - `2.1.1.01` - Utang Dagang
     - `2.1.1.03` - AP UnInvoice (Intermediate)
   - **Revenue Accounts**:
     - `4.1.1.01` - Penjualan Stationery
     - `4.1.1.02` - Penjualan Electronics
   - **COGS Accounts**:
     - `5.1.01` - HPP Stationery
     - `5.1.02` - HPP Electronics

3. **Add Missing Accounts** (if needed)
   - Create any business-specific accounts
   - Ensure proper account hierarchy
   - Set `is_postable` flag correctly

**Why This Matters**: All transactions require valid accounts. Missing accounts will cause posting failures.

---

### 2.2 Product Categories with Account Mappings

**Location**: `Master Data > Product Categories`

**CRITICAL**: Product categories must be created BEFORE inventory items, as they determine accounting behavior.

1. **Create Product Categories**
   - Navigate to: `Master Data > Product Categories`
   - Click "Create New Category"

2. **For Each Category, Configure**:
   - **Category Code**: Unique identifier (e.g., "ELECTRONICS")
   - **Category Name**: Display name (e.g., "Electronics")
   - **Description**: Brief description
   - **Account Mappings** (REQUIRED):
     - **Inventory Account**: GL account for inventory value
       - Example: `1.1.3.01.02` - Persediaan Electronics
     - **COGS Account**: GL account for cost of goods sold
       - Example: `5.1.02` - HPP Electronics
     - **Sales Account**: GL account for sales revenue
       - Example: `4.1.1.02` - Penjualan Electronics

3. **Standard Categories to Create**:
   - **Stationery** (STATIONERY)
     - Inventory: `1.1.3.01.01`
     - COGS: `5.1.01`
     - Sales: `4.1.1.01`
   - **Electronics** (ELECTRONICS)
     - Inventory: `1.1.3.01.02`
     - COGS: `5.1.02`
     - Sales: `4.1.1.02`
   - **Furniture** (FURNITURE)
     - Inventory: `1.1.3.01.03`
     - COGS: `5.1.03`
     - Sales: `4.1.1.03`
   - **Vehicles** (VEHICLES)
     - Inventory: `1.1.3.01.04`
     - COGS: `5.1.04`
     - Sales: `4.1.1.04`
   - **Services** (SERVICES)
     - Inventory: `null` (services don't have inventory)
     - COGS: `5.1.05`
     - Sales: `4.1.1.05`

**Why This Matters**: Account mappings determine which GL accounts are used when inventory items are purchased, sold, or adjusted. Incorrect mappings cause accounting errors.

---

### 2.3 Currency and Exchange Rate Setup

**Location**: `Admin > Currencies` and `Admin > Exchange Rates`

1. **Verify Currencies**
   - Default currency (IDR) should exist
   - Add additional currencies if needed:
     - USD, EUR, SGD, etc.

2. **Configure Exchange Rates**
   - Navigate to: `Admin > Exchange Rates`
   - Set current exchange rates for all active currencies
   - Update rates regularly (daily/weekly)

3. **Currency Settings**
   - Verify `default_currency_id` in ERP Parameters
   - Configure `auto_exchange_rate_enabled` if using automatic rate fetching
   - Set `exchange_rate_tolerance` (default: 10%)

**Why This Matters**: Multi-currency transactions require accurate exchange rates for proper accounting.

---

### 2.4 Control Accounts Setup

**Location**: `Accounting > Control Accounts`

Control accounts enable reconciliation between GL and subsidiary ledgers:

1. **Verify Control Accounts**
   - System should auto-create after data seeding
   - Check for:
     - **AR Control Account**: `1.1.2.01` - Piutang Dagang
     - **AP Control Account**: `2.1.1.01` - Utang Dagang
     - **Inventory Control Account**: `1.1.3.01` - Persediaan Barang Dagangan

2. **Initialize Subsidiary Ledgers**
   - AR Control: Links to Business Partners (Customers)
   - AP Control: Links to Business Partners (Suppliers)
   - Inventory Control: Links to Product Categories

3. **Verify Reconciliation**
   - Navigate to: `Accounting > Control Accounts > Reconciliation`
   - Check that balances reconcile (should be zero initially)

**Why This Matters**: Control accounts ensure GL balances match detailed subsidiary ledgers for accurate financial reporting.

---

## Phase 3: Master Data Setup

**Estimated Time**: 1-2 hours  
**Priority**: 🟡 HIGH - Required for daily operations

### 3.1 Warehouse Setup

**Location**: `Inventory > Warehouses`

1. **Create Warehouses**
   - Navigate to: `Inventory > Warehouses`
   - Click "Create New Warehouse"

2. **For Each Warehouse, Configure**:
   - **Warehouse Code**: Unique identifier
   - **Warehouse Name**: Display name
   - **Address**: Physical location
   - **Type**: Regular warehouse (transit warehouses are auto-created)
   - **Is Active**: Enable/disable

3. **Standard Warehouses**:
   - Main Warehouse (primary storage)
   - Branch Warehouse (branch locations)
   - Distribution Center (distribution hub)

**Note**: Transit warehouses (for ITO/ITI) are automatically filtered from manual selection.

**Why This Matters**: All purchase and sales orders require warehouse selection for inventory tracking.

---

### 3.2 Business Partners Setup

**Location**: `Business Partner > Business Partners`

Business Partners can be both customers and suppliers:

1. **Create Suppliers**
   - Navigate to: `Business Partner > Create`
   - Select **Partner Type**: "Supplier"
   - **Required Information**:
     - Partner Code (unique)
     - Legal Name
     - Tax Number (NPWP)
     - Address
     - Contact Information
   - **Accounting Tab**:
     - Verify AP account mapping (auto-assigned: `2.1.1.01`)
   - **Terms & Conditions Tab**:
     - Payment Terms (e.g., "Net 30")
     - Credit Limit
     - Tax Settings

2. **Create Customers**
   - Select **Partner Type**: "Customer"
   - **Required Information**: Same as suppliers
   - **Accounting Tab**:
     - Verify AR account mapping (auto-assigned: `1.1.2.01`)
   - **Terms & Conditions Tab**:
     - Payment Terms
     - Credit Limit
     - Pricing Tier (1-3)

3. **Create Dual Partners** (if entity is both customer and supplier)
   - Select **Partner Type**: "Both" (if available)
   - Configure both AR and AP accounts

**Why This Matters**: All purchase and sales transactions require valid business partners. Credit limits and payment terms affect approval workflows.

---

### 3.3 Projects and Departments Setup

**Location**: `Master Data > Projects` and `Master Data > Departments`

For multi-dimensional accounting:

1. **Create Projects**
   - Navigate to: `Master Data > Projects`
   - Create projects for cost tracking
   - Example: "Project Alpha", "Project Beta"

2. **Create Departments**
   - Navigate to: `Master Data > Departments`
   - Create departments for cost allocation
   - Example: "Sales", "Operations", "Finance"

**Why This Matters**: Projects and departments enable multi-dimensional cost tracking and reporting for better financial analysis.

---

### 3.4 Payment Terms Setup

**Location**: `Master Data > Terms` (if available)

1. **Create Payment Terms**
   - Common terms:
     - "Net 15" - Payment due in 15 days
     - "Net 30" - Payment due in 30 days
     - "Net 60" - Payment due in 60 days
     - "Due on Receipt" - Immediate payment

2. **Assign to Business Partners**
   - Set default terms when creating business partners
   - Terms appear on purchase and sales documents

**Why This Matters**: Payment terms determine due dates for invoices and affect aging reports.

---

## Phase 4: Business Operations Setup

**Estimated Time**: 1-2 hours  
**Priority**: 🟡 HIGH - Required for transactions

### 4.1 Inventory Items Creation

**Location**: `Inventory > Inventory Items`

**IMPORTANT**: Create product categories FIRST (see Phase 2.2)

1. **Create Inventory Items**
   - Navigate to: `Inventory > Add Item`
   - **Required Information**:
     - Item Code (unique)
     - Item Name
     - **Product Category** (REQUIRED - must exist)
     - Item Type: "Item" or "Service"
     - Unit of Measure (base unit)
     - **Warehouse** (for initial stock)
   - **Pricing**:
     - Purchase Price
     - Selling Price
     - Price Levels (1-3) if using tiered pricing
   - **Inventory Settings**:
     - Reorder Point
     - Minimum Stock Level
     - Maximum Stock Level

2. **Unit of Measure Setup** (if using conversions)
   - Navigate to: `Inventory > Units of Measure`
   - Create base units: Piece, Box, Dozen, etc.
   - Configure conversion factors if needed

3. **Initial Stock Entry** (if applicable)
   - Use Inventory Adjustment or GR/GI system
   - Enter opening balances for each warehouse

**Why This Matters**: Inventory items are required for all purchase and sales transactions. Missing categories or incorrect account mappings cause posting errors.

---

### 4.2 Tax Codes Setup

**Location**: `Admin > Tax Codes` (if available)

1. **Verify Tax Codes**
   - System should have Indonesian tax codes seeded:
     - **PPN 11%**: Value Added Tax (VAT)
     - **PPh 21**: Employee Income Tax
     - **PPh 23**: Withholding Tax on Services
     - **PPh 4(2)**: Final Income Tax

2. **Configure Tax Settings**
   - Verify tax rates are correct
   - Update if tax laws change

**Why This Matters**: Tax codes are used in purchase and sales invoices for Indonesian tax compliance.

---

### 4.3 Approval Workflows Configuration

**Location**: `Admin > Approval Workflows` (if available)

1. **Verify Default Workflows**
   - System should have default workflows for:
     - Purchase Orders
     - Sales Orders

2. **Configure Approval Thresholds**
   - **Purchase Orders**:
     - 0 - 5,000,000: Officer approval
     - 5,000,000 - 15,000,000: Officer + Supervisor
     - 15,000,000+: Officer + Supervisor + Manager
   - **Sales Orders**: Same thresholds

3. **Assign User Roles**
   - Ensure users have correct roles:
     - `officer`
     - `supervisor`
     - `manager`

**Why This Matters**: Approval workflows control document authorization. Missing workflows prevent document approval.

---

## Phase 5: User Management

**Estimated Time**: 30-45 minutes  
**Priority**: 🟡 HIGH - Required for system access

### 5.1 User Account Creation

**Location**: `Admin > Users`

1. **Create User Accounts**
   - Navigate to: `Admin > Users > Create`
   - **Required Information**:
     - Name
     - Email (unique)
     - Username (unique)
     - Password (strong password recommended)
   - **Assign Roles**:
     - Admin (full access)
     - Manager (management access)
     - User (operational access)
     - Custom roles as needed

2. **Assign Permissions**
   - Granular permissions available:
     - Module access (inventory, sales, purchase, etc.)
     - Operation permissions (view, create, update, delete, post, reverse)
   - Use role-based assignment for efficiency

3. **User Roles Setup**
   - Navigate to: `Admin > Roles`
   - Verify default roles exist:
     - `admin`
     - `manager`
     - `user`
   - Create custom roles if needed

**Why This Matters**: Proper user management ensures security and appropriate access control.

---

### 5.2 Approval Role Assignment

**Location**: `Admin > User Roles` (if available)

1. **Assign Approval Roles**
   - Users need approval roles for document approval:
     - `officer` - First level approval
     - `supervisor` - Second level approval
     - `manager` - Final approval

2. **Verify Approval Workflow**
   - Test that approval workflows route correctly
   - Verify users receive approval notifications

**Why This Matters**: Approval workflows require users with proper roles to function.

---

## Phase 6: Verification and Testing

**Estimated Time**: 30-45 minutes  
**Priority**: 🟢 MEDIUM - Ensures system readiness

### 6.1 System Verification Checklist

Verify all critical components:

- [ ] Company information displays correctly on documents
- [ ] Chart of Accounts has all required accounts
- [ ] Product categories have account mappings
- [ ] Warehouses are created and active
- [ ] At least one supplier (business partner) exists
- [ ] At least one customer (business partner) exists
- [ ] At least one inventory item exists
- [ ] Users can log in with assigned roles
- [ ] Approval workflows are configured
- [ ] Currency and exchange rates are set

---

### 6.2 Test Transaction Workflow

Perform end-to-end test:

1. **Purchase Cycle Test**:
   - Create Purchase Order
   - Verify PO number generation
   - Create Goods Receipt PO (GRPO)
   - Verify inventory update
   - Create Purchase Invoice
   - Verify journal entries
   - Create Purchase Payment
   - Verify cash account update

2. **Sales Cycle Test**:
   - Create Sales Order
   - Verify SO number generation
   - Create Delivery Order
   - Verify inventory reservation
   - Create Sales Invoice
   - Verify journal entries
   - Create Sales Receipt
   - Verify cash account update

3. **Journal Verification**:
   - Navigate to: `Accounting > Journals`
   - Verify journal entries are balanced
   - Check account balances
   - Verify control account reconciliation

---

### 6.3 Common Issues and Solutions

**Issue**: "Journal is not balanced" error
- **Solution**: Verify product category account mappings are correct

**Issue**: "Account not found" error
- **Solution**: Verify chart of accounts is complete, add missing accounts

**Issue**: Approval workflow not working
- **Solution**: Verify users have correct approval roles assigned

**Issue**: Document numbers not generating
- **Solution**: Verify document sequences are initialized in database

**Issue**: Inventory items not appearing in dropdowns
- **Solution**: Verify items are assigned to product categories with account mappings

---

## Quick Reference Checklist

Use this checklist for quick setup verification:

### Critical (Must Complete First)
- [ ] Company information configured
- [ ] Chart of Accounts verified (118+ accounts)
- [ ] Product Categories created with account mappings
- [ ] At least one Warehouse created
- [ ] At least one Supplier (Business Partner) created
- [ ] At least one Customer (Business Partner) created
- [ ] At least one Inventory Item created
- [ ] Users created with proper roles

### High Priority (Complete Before Operations)
- [ ] Currency and exchange rates configured
- [ ] Control accounts verified
- [ ] Approval workflows configured
- [ ] Payment terms created
- [ ] Projects and Departments created (if using multi-dimensional accounting)
- [ ] Tax codes verified

### Medium Priority (Can Complete During Operations)
- [ ] Additional warehouses created
- [ ] Additional business partners added
- [ ] Additional inventory items added
- [ ] Custom roles and permissions configured
- [ ] ERP Parameters fine-tuned

---

## Next Steps After Setup

Once initial setup is complete:

1. **Training**: Review training materials in `docs/comprehensive-training/`
2. **Data Migration**: Import existing data if migrating from another system
3. **Go-Live Preparation**: 
   - Set opening balances
   - Configure period dates
   - Prepare user training
4. **Ongoing Maintenance**:
   - Regular exchange rate updates
   - User account management
   - Approval workflow adjustments
   - ERP parameter tuning

---

## Support and Documentation

- **Architecture Documentation**: `docs/architecture.md`
- **Training Materials**: `docs/comprehensive-training/`
- **Testing Scenarios**: `docs/comprehensive-erp-testing-scenario.md`
- **Memory/Decisions**: `MEMORY.md` and `docs/decisions.md`

---

## Important Notes

1. **Order Matters**: Product categories MUST be created before inventory items
2. **Account Mappings**: Every product category needs inventory, COGS, and sales account mappings
3. **Business Partners**: Can be both customers and suppliers (unified system)
4. **Multi-Entity**: If using multiple entities, configure entity-specific settings
5. **Testing**: Always test with sample data before production use

---

**Last Updated**: 2025-01-20  
**Version**: 1.0  
**Maintained By**: ERP Implementation Team

