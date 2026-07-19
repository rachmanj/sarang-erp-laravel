# Asset & Depreciation Module User Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Asset Categories](#asset-categories)
4. [Asset Management (CRUD)](#asset-management-crud)
5. [Depreciation Runs](#depreciation-runs)
6. [Asset Disposals](#asset-disposals)
7. [Asset Movements](#asset-movements)
8. [Import & Export](#import--export)
9. [Data Quality](#data-quality)
10. [Bulk Operations](#bulk-operations)
11. [Reports](#reports)
12. [Permissions Reference](#permissions-reference)
13. [Common Tasks](#common-tasks)
14. [Troubleshooting](#troubleshooting)
15. [Quick Reference](#quick-reference)

---

## Introduction

### What is the Asset & Depreciation Module?

The Asset & Depreciation Module manages the complete fixed asset lifecycle — from acquisition through depreciation, movement, and eventual disposal. It integrates with the General Ledger to automatically generate journal entries for depreciation expenses, accumulated depreciation, and disposal gains or losses.

### Who Should Use This Module?

- **Asset managers**: Create and maintain asset records, track locations and custodians.
- **Accounting staff**: Run monthly depreciation, post to GL, process disposals with gain/loss calculation.
- **IT / facilities**: Record asset movements between departments and locations.
- **Management**: Review asset registers, depreciation schedules, and disposal summaries.
- **Auditors**: Verify asset data quality, movement history, and disposal documentation.

### Key Benefits

- Complete asset lifecycle: acquisition → depreciation → movement → disposal.
- Automatic journal postings for depreciation and disposal transactions.
- Multiple depreciation method support (straight-line with declining balance planned).
- Asset import/export via Excel/CSV with validation.
- Built-in data quality checks: duplicate detection, incomplete records, consistency validation.
- Granular permissions (17 distinct permissions) for role-based access.
- Asset movement tracking with approval workflow and full history.
- Disposal gain/loss auto-calculation with journal integration.

### Navigation

All asset features are accessible from the sidebar menu group: **Fixed Assets**.

| Menu Item | Path | Description |
|-----------|------|-------------|
| Asset Categories | `Assets > Asset Categories` | Manage asset category master data |
| Assets | `Assets > Assets` | Main asset listing with full CRUD |
| Depreciation Runs | `Assets > Depreciation Runs` | Run monthly depreciation |
| Asset Disposals | `Assets > Asset Disposals` | Process disposals (sale, scrap, loss) |
| Asset Movements | `Assets > Asset Movements` | Record transfers between locations/custodians |
| Asset Import | `Assets > Asset Import` | Import assets from Excel/CSV |
| Data Quality | `Assets > Data Quality` | Check for duplicates, inconsistencies |
| Bulk Operations | `Assets > Bulk Operations` | Mass update, mass disposal |

---

## Getting Started

### Prerequisites

Before using the Asset module, ensure the following are configured:

1. **Chart of Accounts** set up with asset accounts, accumulated depreciation accounts, depreciation expense accounts, and gain/loss on disposal accounts.
2. **Asset Categories** created with proper COA account mappings (see [Asset Categories](#asset-categories)).
3. **Business Partners** (vendors) configured if assets are purchased from external suppliers.
4. **Departments** and **Projects** configured if you want to assign assets to dimensions.
5. **User permissions** assigned by your administrator (see [Permissions Reference](#permissions-reference)).

### Typical Workflow

1. **Set up Asset Categories** with COA account mappings and default depreciation parameters.
2. **Create asset records** for each fixed asset with acquisition cost, useful life, and depreciation method.
3. **Run monthly depreciation** to calculate and post depreciation expense to the GL.
4. **Record asset movements** when assets change location or custodian.
5. **Process disposals** when assets are sold, scrapped, or lost — auto-calculates gain/loss.
6. **Generate reports** for asset registers, depreciation schedules, and disposal summaries.

---

## Asset Categories

### What are Asset Categories?

Asset Categories are the foundation of the fixed asset module. Each category defines:
- Default depreciation method and useful life
- Whether assets in this category are depreciable
- Chart of Account (COA) mappings for all related journal entries
- Default salvage value policy

### Built-in Categories

The system comes with 6 pre-seeded categories:

| Code | Name | Useful Life | Depreciable? |
|------|------|-------------|:---:|
| LAND | Land | N/A | No |
| BUILDINGS | Buildings | 240 months (20 years) | Yes |
| VEHICLES | Vehicles | 60 months (5 years) | Yes |
| EQUIPMENT | Equipment | 48 months (4 years) | Yes |
| FURNITURE | Furniture & Fixtures | 36 months (3 years) | Yes |
| IT_EQUIPMENT | IT Equipment | 36 months (3 years) | Yes |

### Managing Categories

**Create a new category:**
1. Navigate to **Assets > Asset Categories**.
2. Click **Add New Category** (modal opens via DataTables).
3. Fill in:
   - **Code**: Unique short identifier (e.g., `MACHINERY`).
   - **Name**: Display name (e.g., "Machinery").
   - **Description**: Optional description.
   - **Default Useful Life (months)**: Set to empty/null for non-depreciable categories.
   - **Default Method**: `straight_line` (declining_balance planned).
   - **Non-Depreciable**: Check if assets in this category never depreciate (e.g., Land).
   - **Salvage Value Policy**: Percentage or 0.
   - **COA Mappings**: Select the correct accounts for:
     - Asset Account (balance sheet)
     - Accumulated Depreciation Account (contra-asset)
     - Depreciation Expense Account (P&L)
     - Gain on Disposal Account (P&L)
     - Loss on Disposal Account (P&L)
4. Click **Save**.

**Edit a category:**
- Click the edit action on the category row, modify fields, and save.

**Delete a category:**
- You can only delete a category if no assets are assigned to it. The system will block deletion otherwise.

### COA Account Mapping

| Account Type | Balance Sheet / P&L | Example Account |
|-------------|---------------------|-----------------|
| Asset Account | Balance Sheet (Asset) | 1.2.1.01 — Tanah |
| Accumulated Depreciation | Balance Sheet (Contra-Asset) | 1.2.1.03 — Akum. Peny. Bangunan |
| Depreciation Expense | Profit & Loss (Expense) | 6.2.9 — Biaya Penyusutan |
| Gain on Disposal | Profit & Loss (Income) | 7.1.1 — Pendapatan Sewa |
| Loss on Disposal | Profit & Loss (Expense) | 7.2.3 — Kerugian Penjualan Aset |

---

## Asset Management (CRUD)

### Asset Index

Navigate to **Assets > Assets** to see the full asset listing.

The DataTable displays:
- **Code**: Asset unique code.
- **Name**: Asset name.
- **Category**: Assigned asset category.
- **Acquisition Cost**: Original purchase cost.
- **Current Book Value**: Cost minus accumulated depreciation.
- **Accumulated Depreciation**: Total depreciation to date.
- **Depreciation Info**: Remaining months and depreciation rate.
- **Status**: active, disposed, etc.
- **Dimensions**: Assigned Project and Department.

**Filters available:**
- By Category
- By Status (active / disposed / etc.)
- By Project
- By Department

**Actions per row:**
- **View**: See full asset details, depreciation entries, movement history.
- **Edit**: Update asset information.
- **Delete**: Remove asset (only if no depreciation entries exist).

### Creating an Asset

1. Navigate to **Assets > Assets** and click **Create Asset**.
2. Fill in the required fields:

| Field | Description | Required |
|-------|-------------|:---:|
| Asset Code | Unique identifier for the asset (e.g., `PC-001`) | Yes |
| Asset Name | Descriptive name (e.g., "Dell OptiPlex 7090") | Yes |
| Description | Additional notes about the asset | No |
| Serial Number | Manufacturer serial number | No |
| Category | Select from available asset categories | Yes |
| Acquisition Cost | Original purchase price (in base currency) | Yes |
| Salvage Value | Estimated residual value at end of life | No (default 0) |
| Depreciation Method | `straight_line` (others planned) | Yes |
| Useful Life (months) | Total depreciation period | Yes |
| Placed in Service Date | Date asset began use (depreciation starts from this) | Yes |
| Status | Usually `active` for new assets | Yes |
| Project | Optional project dimension | No |
| Department | Optional department dimension | No |
| Business Partner (Vendor) | Supplier who sold the asset | No |
| Purchase Invoice | Link to the purchase invoice | No |

3. Click **Save**.

### Viewing an Asset

Click **View** on any asset row to see:
- **Asset Details**: All fields including computed values (depreciable cost, monthly depreciation, remaining life).
- **Depreciation Entries Table**: All depreciation entries for this asset, grouped by run.
- **Movement History**: All past movements (from/to locations, custodians, dates).
- **Disposal History**: Any disposals (including reversed ones).

### Editing an Asset

1. Click **Edit** on the asset row.
2. Modify any field. Note:
   - Changing `acquisition_cost`, `salvage_value`, or `life_months` will affect future depreciation calculations.
   - Changing `category_id` changes the COA mappings for future depreciation runs.
   - `current_book_value` is auto-computed and cannot be directly edited.
3. Click **Update Asset** to save changes.

### Deleting an Asset

You can delete an asset **only if it has no depreciation entries**. Once depreciation has been posted, the asset cannot be deleted — it must be disposed of instead.

- If deletable: Click **Delete**, confirm the prompt.
- If not deletable: Use the **Asset Disposal** flow instead.

---

## Depreciation Runs

### Overview

Depreciation runs are the core accounting operation of the module. Each run:
- Covers a specific **period** (month, e.g., `2024-01`).
- Calculates depreciation for all active, depreciable assets.
- Groups entries by category + dimensions for journal posting.
- Moves through a workflow: **Draft** → **Posted** → (optionally) **Reversed**.

### Viewing Depreciation Runs

Navigate to **Assets > Depreciation Runs**.

The DataTable shows:
- **Period**: Month and year of the depreciation run.
- **Total Depreciation**: Sum of all entries in this run.
- **Status**: Draft, Posted, or Reversed.
- **Created By**: User who created the run.
- **Posted At**: Timestamp when posted.
- **Poster**: User who posted/ran the run.

### Creating a Depreciation Run

1. Navigate to **Assets > Depreciation Runs**.
2. Click **New Depreciation Run**.
3. Select the **Period** (month). Only one run is allowed per period.
4. The system lists all eligible assets:
   - Status must be `active`.
   - Category must be depreciable (`non_depreciable = false`).
   - Asset not yet fully depreciated.
   - Asset not already disposed.
5. Review the calculated amounts per asset.
6. Click **Calculate** to generate draft depreciation entries.

### Depreciation Calculation Logic (Straight-Line)

For each eligible asset:
1. **Depreciable Cost** = `acquisition_cost - salvage_value`
2. **Monthly Depreciation** = `depreciable_cost / life_months`
3. **First-month proration**: If `placed_in_service_date.day > 1`, the first month is prorated.

### Posting a Depreciation Run

1. On the run detail page (or from the index), click **Post**.
2. The system:
   - Groups all depreciation entries by **category** and **dimensions** (project + department).
   - Creates one journal entry per group:
     - **Debit**: Depreciation Expense Account (from category COA mapping)
     - **Credit**: Accumulated Depreciation Account (from category COA mapping)
   - Updates each asset's `accumulated_depreciation` and `current_book_value`.
   - Marks the run status as `posted`.
3. Journal entries are linked back to the depreciation run for audit trail.

### Reversing a Depreciation Run

If a depreciation run was posted in error:
1. Click **Reverse** on the posted run.
2. The system:
   - Creates reversing journal entries (credit expense, debit accum. depr.).
   - Restores asset `accumulated_depreciation` and `current_book_value` to pre-post state.
   - Marks the run status as `reversed`.
   - Marks the disposal's journal as reversed.

**Important**: A reversed run **cannot** be re-posted. You must create a new run for that period.

### Depreciation Schedule

From any asset's detail page or the depreciation run view, you can view the **Depreciation Schedule** — a month-by-month projection of future depreciation until the asset is fully depreciated.

---

## Asset Disposals

### Overview

Asset Disposals handle the end-of-life or removal of an asset. The system supports 5 disposal types:
- **Sale**: Asset sold for proceeds.
- **Scrapping**: Asset scrapped (zero proceeds).
- **Loss/Theft**: Asset lost or stolen.
- **Insurance Claim**: Recovered through insurance.
- **Transfer Out**: Asset transferred to another entity.

Each disposal auto-calculates **gain or loss** by comparing:
- **Book Value** = `acquisition_cost - accumulated_depreciation`
- **Proceeds** = sale price or insurance recovery
- **Gain** = `proceeds - book_value` (if positive)
- **Loss** = `book_value - proceeds` (if positive)

### Creating a Disposal

1. Navigate to **Assets > Asset Disposals** and click **New Disposal**.
2. Select the **Asset** to dispose. Only active, non-disposed assets are available.
3. Fill in:

| Field | Description | Required |
|-------|-------------|:---:|
| Asset | The asset being disposed | Yes |
| Disposal Date | Date of disposal | Yes |
| Disposal Type | Sale, Scrapping, Loss/Theft, Insurance Claim, Transfer Out | Yes |
| Proceeds | Amount received (for Sale/Insurance types) | For Sale/Insurance |
| Disposal Reason | Description of why the asset is being disposed | No |
| Buyer/Recipient | Entity receiving the asset | No |

4. The system displays:
   - Original Cost
   - Accumulated Depreciation (to date)
   - Book Value (auto-calculated)
   - Proceeds
   - **Gain / (Loss)** — auto-calculated

### Posting a Disposal

1. Click **Post** on the disposal.
2. The system creates a journal entry:
   - **Debit**: Accumulated Depreciation (remove from books)
   - **Debit**: Cash/Bank (if proceeds received)
   - **Credit**: Asset Account (remove from books)
   - **Credit/Debit**: Gain/Loss on Disposal (balancing entry)
3. The asset status changes to `disposed`.
4. The asset's `disposal_date` is set.

### Reversing a Disposal

If a disposal was posted in error:
1. Click **Reverse** on the posted disposal.
2. The system:
   - Creates reversing journal entries.
   - Restores the asset to `active` status.
   - Restores accumulated depreciation and book value.
3. The disposal status is set to `reversed`.

### Disposal Workflow States

| Status | Description | Allowed Actions |
|--------|-------------|-----------------|
| Draft | Newly created disposal | Edit, Delete, Post |
| Posted | Disposal journal posted to GL | View, Reverse |
| Reversed | Disposal reversed (asset restored) | View only |

---

## Asset Movements

### Overview

Asset Movements record physical or custodial transfers of assets. The system tracks 5 movement types:
- **Department Transfer**: Asset moves between departments.
- **Location Change**: Asset moves to a new physical location.
- **Custodian Change**: Responsibility transfers to a different person.
- **Temporary Loan**: Asset temporarily loaned out.
- **Return from Loan**: Asset returned from temporary loan.

### Creating a Movement

1. Navigate to **Assets > Asset Movements** and click **New Movement**.
2. Select the **Asset** to move.
3. Fill in:

| Field | Description | Required |
|-------|-------------|:---:|
| Asset | The asset being moved | Yes |
| Movement Date | Date of movement | Yes |
| Movement Type | One of the 5 types above | Yes |
| From Location | Original location | Yes |
| To Location | Destination location | Yes (except loan return) |
| From Custodian | Original responsible person | No |
| To Custodian | New responsible person | No |
| Reason | Reason for movement | No |
| Reference Number | Auto-generated (e.g., `MOV-2024-001`) | Auto |

4. Click **Save**.

### Approval Workflow

Movements can go through an approval process:
- **Draft**: Newly created, not yet actioned.
- **Approved**: Approved by someone with `assets.movement.approve` permission.
- **Completed**: Movement finalized (asset location updated).
- **Cancelled**: Movement cancelled without effect.

**To approve a movement**: Click **Approve** on the movement row (requires `assets.movement.approve` permission).

### Movement History

For any asset, view its full movement history:
1. Go to **Assets > Assets**, click **View** on an asset.
2. Scroll to the **Movement History** section.
3. Or navigate to **Assets > Asset Movements** and use the asset filter.

---

## Import & Export

### Importing Assets

The asset import feature allows bulk creation and updating of assets via Excel/CSV.

**Import workflow:**

1. Navigate to **Assets > Asset Import**.

2. **Download Template**: Click **Download Template** to get the Excel template with the correct column headers.

3. **Prepare your data**: Fill in the template following these rules:

| Column | Format | Notes |
|--------|--------|-------|
| Code | String | Unique asset code (required) |
| Name | String | Asset name (required) |
| Description | String | Optional |
| Serial Number | String | Optional |
| Category Code | String | Must match an existing category code |
| Acquisition Cost | Number | Original cost (required) |
| Salvage Value | Number | Default 0 |
| Depreciation Method | `straight_line` | Required |
| Life Months | Integer | Depreciation period in months |
| Placed in Service Date | Date `Y-m-d` | When asset entered service |
| Status | `active` | Usually active |
| Project Code | String | Optional, must match existing project |
| Department Code | String | Optional, must match existing department |
| Business Partner Code | String | Optional, must match existing vendor |

4. **Validate**: Upload the file and click **Validate**. The system checks:
   - All required fields are present.
   - Category codes exist.
   - Project/department codes exist (if provided).
   - Business partner codes exist (if provided).
   - Dates are valid.
   - Asset codes are unique (not already in the system).

5. **Fix validation errors**: If errors are found, the system shows exactly which rows and columns have problems. Fix the file and validate again.

6. **Import**: Once validation passes, click **Import** to create all assets.

### Bulk Update via Import

You can also use the import template to **update** existing assets:
1. Include the `code` of existing assets.
2. Fill in the columns you want to update.
3. Validate and import as above.

### Export

The asset register can be exported via the **Reports** section:
- **Asset Register Export** (Excel): Full asset listing with depreciation status.
- **Depreciation Schedule Export** (Excel): Projected future depreciation.
- **Disposal Summary Export** (Excel): All disposals with gain/loss.

---

## Data Quality

### Overview

The Data Quality tool helps maintain clean asset data by detecting:

1. **Duplicate Assets**: Assets with identical or very similar names, serial numbers, or codes.
2. **Incomplete Records**: Assets missing required fields (category, cost, dates, method, life).
3. **Consistency Issues**:
   - Assets with negative book values.
   - Assets with accumulated depreciation exceeding depreciable cost.
   - Assets with future placed-in-service dates missing depreciation.
   - Depreciation entries that don't match the expected monthly amount.
4. **Orphaned Records**: Depreciation entries or movements linked to deleted assets.

### Using Data Quality

1. Navigate to **Assets > Data Quality**.

2. The dashboard shows counts for each category:
   - **Duplicates**: Number of potential duplicate groups.
   - **Incomplete**: Assets with missing required data.
   - **Consistency**: Anomalies in depreciation calculations.
   - **Orphaned**: Records with broken references.

3. Click any count to see the detailed list.

### Duplicate Detection

- The system groups assets by similar names (case-insensitive, trimming whitespace).
- For each group, you can see all matching assets and decide which to keep.
- **Action**: From the duplicates detail page, review and clean up duplicate records.

### Incomplete Records

Shows assets missing:
- Category assignment
- Acquisition cost (zero or null)
- Placed in service date
- Depreciation method
- Useful life (for depreciable categories)

**Action**: Click the asset code to go to the edit page and fill in missing data.

### Consistency Issues

Detects anomalies such as:
- **Over-depreciated**: `accumulated_depreciation > depreciable_cost`
- **Negative book value**: `current_book_value < 0`
- **Missing depreciation**: Active, depreciable asset with no depreciation entries past its placed-in-service date.

**Action**: Review each issue. For over-depreciated assets, reverse the excess depreciation run. For missing depreciation, ensure the asset is included in future runs.

### Orphaned Records

- Depreciation entries pointing to deleted assets.
- Movements or disposals with missing asset references.

**Action**: These are typically harmless but can be cleaned up by a database administrator.

---

## Bulk Operations

### Overview

Bulk Operations let you perform actions on multiple assets at once.

Navigate to **Assets > Bulk Operations**.

Available operations:
- **Bulk Update**: Change a field (e.g., department, project, status) on selected assets.
- **Bulk Disposal**: Dispose multiple assets in a single batch.
- **Bulk Recalculate**: Recalculate accumulated depreciation for selected assets.

### How to Use

1. Select the assets by checking their checkboxes on the asset index.
2. Choose the operation from the Bulk Operations page.
3. Follow the on-screen form for the specific operation.
4. Confirm to apply changes.

---

## Reports

### Available Reports

Navigate to **Reports > Assets** (or use the links in the Asset module).

| Report | Description |
|--------|-------------|
| **Asset Register** | Full listing of all assets with cost, depreciation, book value. Filterable by category, status, project, department, and date range. Exportable to Excel. |
| **Depreciation Schedule** | Month-by-month projection of future depreciation. Shows when each asset will be fully depreciated. |
| **Disposal Summary** | All disposals within a date range, with gain/loss amounts and journal references. |
| **Movement Log** | Full movement history with from/to locations, custodians, and dates. |
| **Depreciation Expense Report** | Depreciation expense by period, by category — useful for budgeting. |

### Generating a Report

1. Navigate to **Reports > Assets** and select the report type.
2. Set filters:
   - Date range (for schedule, disposals, movements).
   - Category filter.
   - Status filter.
   - Project/department filter.
3. Click **Generate**.
4. Review the report in the browser, or click **Export** to download as Excel.

---

## Permissions Reference

### All 17 Asset Permissions

The Asset module uses 17 granular permissions for role-based access control:

| # | Permission String | Controls |
|---|-------------------|----------|
| 1 | `assets.view` | View asset listing and details |
| 2 | `assets.create` | Create new assets |
| 3 | `assets.update` | Edit existing assets |
| 4 | `assets.delete` | Delete assets (only if no depreciation) |
| 5 | `asset_categories.view` | View asset categories list |
| 6 | `asset_categories.manage` | Create, edit, delete asset categories |
| 7 | `assets.depreciation.run` | Create and post depreciation runs |
| 8 | `assets.depreciation.reverse` | Reverse posted depreciation runs |
| 9 | `assets.disposal.view` | View disposal records |
| 10 | `assets.disposal.create` | Create new disposal records |
| 11 | `assets.disposal.update` | Edit draft disposal records |
| 12 | `assets.disposal.delete` | Delete draft disposal records |
| 13 | `assets.disposal.post` | Post disposals (creates journal entries) |
| 14 | `assets.disposal.reverse` | Reverse posted disposals |
| 15 | `assets.movement.view` | View movement records |
| 16 | `assets.movement.create` | Create new movement records |
| 17 | `assets.movement.update` | Edit movement records |
| 18 | `assets.movement.delete` | Delete movement records |
| 19 | `assets.movement.approve` | Approve pending movements |
| 20 | `assets.reports.view` | Access asset reports |

### Default Role Assignments

| Role | Asset Permissions |
|------|-------------------|
| **Superadmin** | All 20 permissions |
| **Accountant** | `assets.view`, `asset_categories.view`, `assets.reports.view` |
| **Approver** | `assets.depreciation.run`, `assets.depreciation.reverse`, all disposal permissions, all movement permissions |
| **Cashier** | None |
| **Auditor** | None (uses `reports.view` for asset reports) |
| **Staff** | (see your specific role configuration) |

### Permission Matrix by Feature

| Feature | View | Create | Edit | Delete | Post | Reverse | Approve |
|---------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Assets** | `assets.view` | `assets.create` | `assets.update` | `assets.delete` | — | — | — |
| **Categories** | `asset_categories.view` | `asset_categories.manage` | `asset_categories.manage` | `asset_categories.manage` | — | — | — |
| **Depreciation** | `assets.view` | `assets.depreciation.run` | — | — | `assets.depreciation.run` | `assets.depreciation.reverse` | — |
| **Disposals** | `assets.disposal.view` | `assets.disposal.create` | `assets.disposal.update` | `assets.disposal.delete` | `assets.disposal.post` | `assets.disposal.reverse` | — |
| **Movements** | `assets.movement.view` | `assets.movement.create` | `assets.movement.update` | `assets.movement.delete` | — | — | `assets.movement.approve` |
| **Reports** | `assets.reports.view` | — | — | — | — | — | — |

---

## Common Tasks

### Task: Register a Newly Purchased Asset

1. Ensure an **Asset Category** exists for the asset type (e.g., IT Equipment for computers).
2. Verify the category has the correct **COA accounts** mapped.
3. Go to **Assets > Assets** → **Create Asset**.
4. Fill in the asset details:
   - Code, Name, Category, Acquisition Cost.
   - Salvage Value (if any), Depreciation Method, Useful Life (months).
   - Placed in Service Date (typically the purchase/invoice date).
   - Optionally link to the Purchase Invoice and Business Partner (vendor).
   - Assign to a Project and/or Department if applicable.
5. Click **Save**.
6. The asset now appears in the asset list with status `active`.
7. Depreciation will be calculated starting from the next depreciation run.

### Task: Run Monthly Depreciation

1. Go to **Assets > Depreciation Runs** → **New Depreciation Run**.
2. Select the period (e.g., `2024-01`).
3. Review the list of eligible assets and calculated amounts.
4. Click **Calculate** to create draft entries.
5. Review the run details — check total depreciation.
6. Click **Post** to create journal entries:
   - Debit: Depreciation Expense
   - Credit: Accumulated Depreciation
7. The run is now `posted`. Asset book values are updated.

### Task: Transfer an Asset to Another Department

1. Go to **Assets > Asset Movements** → **New Movement**.
2. Select the asset and set Movement Type to **Department Transfer**.
3. Set **From Department** and **To Department**.
4. Set **From Location** and **To Location** (if also changing physical location).
5. Add a **Reason** for the transfer.
6. Click **Save** (status: Draft).
7. An approver (with `assets.movement.approve` permission) clicks **Approve**.
8. Once approved, click **Complete** to finalize. The asset's department/location is updated.

### Task: Dispose of a Fully Depreciated Asset

1. Go to **Assets > Asset Disposals** → **New Disposal**.
2. Select the asset.
3. Set Disposal Type:
   - **Sale** if selling (enter proceeds amount).
   - **Scrapping** if throwing away (proceeds = 0).
4. Set the disposal date and reason.
5. The system auto-calculates:
   - Book Value (should be close to salvage value if fully depreciated).
   - Gain/Loss based on proceeds.
6. Click **Save**.
7. Click **Post** to create the journal entry and mark the asset as disposed.

### Task: Fix a Wrongly Posted Depreciation Run

1. Go to **Assets > Depreciation Runs**.
2. Find the posted run you need to reverse.
3. Click **Reverse** (requires `assets.depreciation.reverse` permission).
4. Confirm the reversal.
5. The system:
   - Creates reversing journal entries.
   - Restores asset accumulated depreciation.
   - Sets the run status to `reversed`.
6. Create a new depreciation run for the same period with corrected settings.

### Task: Check Data Quality

1. Go to **Assets > Data Quality**.
2. Review the dashboard for counts of issues.
3. Click **Duplicates** to see potential duplicate assets → merge or clean up.
4. Click **Incomplete** to see assets missing data → click to edit and fill in.
5. Click **Consistency** to see calculation anomalies → investigate and fix.
6. Run data quality checks periodically (monthly, after import).

### Task: Import Multiple Assets from Excel

1. Go to **Assets > Asset Import**.
2. Click **Download Template** to get the Excel template.
3. Fill in all assets in the template:
   - One row per asset.
   - Use existing category codes (check Asset Categories list).
   - Reference existing project/department codes if needed.
4. Upload the file and click **Validate**.
5. Review any validation errors and fix them in the Excel file.
6. Re-upload and validate until clean.
7. Click **Import** to create all assets.

---

## Troubleshooting

### Issue: Cannot Post Depreciation Run

**Symptoms**: The **Post** button is disabled or missing on a depreciation run.

**Causes**:
- You don't have the `assets.depreciation.run` permission.
- The run is already posted or reversed.
- The period is closed (checked via `PeriodCloseService`).

**Solutions**:
1. Verify your permissions with your administrator.
2. Check the run status — only **Draft** runs can be posted.
3. If the period is closed, request your administrator to reopen the period.

### Issue: Asset Cannot Be Deleted

**Symptoms**: The Delete button is missing or returns an error.

**Causes**:
- The asset has depreciation entries posted.
- The asset has been disposed.
- You lack `assets.delete` permission.

**Solutions**:
1. If depreciation entries exist, you must **dispose** of the asset instead.
2. If already disposed, no further action is needed — disposed assets remain for audit.
3. Check your permissions.

### Issue: Depreciation Amount Seems Wrong

**Symptoms**: Monthly depreciation doesn't match expected amount.

**Causes**:
- Incorrect `acquisition_cost`, `salvage_value`, or `life_months` on the asset.
- Asset category changed mid-life.
- First-month proration (partial month).
- Asset is in a non-depreciable category.

**Solutions**:
1. Check the asset's fields: acquisition cost, salvage value, useful life.
2. Verify the category is set to depreciable.
3. Check the `placed_in_service_date` — first month is prorated if day > 1.
4. Formula: `monthly_depreciation = (acquisition_cost - salvage_value) / life_months`.

### Issue: Disposal Gain/Loss Seems Incorrect

**Symptoms**: The calculated gain/loss on disposal doesn't match expectations.

**Causes**:
- Accumulated depreciation is not up to date. Run depreciation for the current period first.
- Proceeds amount entered incorrectly.
- Book value changed due to asset edits after depreciation was posted.

**Solutions**:
1. Ensure all depreciation runs are posted up to the disposal date.
2. Verify the accumulated depreciation on the asset detail page.
3. Book Value = `acquisition_cost - accumulated_depreciation`.
4. Gain = `proceeds - book_value` (positive = gain, negative = loss).

### Issue: Import Validation Fails

**Symptoms**: Red errors appear when validating the import file.

**Causes**:
- Missing required columns.
- Category/Project/Department codes don't match existing records.
- Date format is incorrect (must be `Y-m-d`).
- Duplicate asset codes.

**Solutions**:
1. Download the template again and cross-check column headers.
2. Go to Asset Categories list and verify category codes.
3. Check Project and Department lists for correct codes.
4. Ensure dates are in `YYYY-MM-DD` format.
5. Check that asset codes are not already in use.

### Issue: Period Close Prevents Actions

**Symptoms**: Cannot create depreciation run or post disposal — system says period is closed.

**Causes**:
- The accounting period has been closed by the finance team.

**Solutions**:
1. Request the period be reopened by an administrator.
2. Or post the transactions in the next open period (not recommended for accuracy).
3. Note: **Reversals** of depreciation runs and disposals do not currently check period closure.

---

## Quick Reference

### Key Formulas

| Formula | Description |
|---------|-------------|
| `Depreciable Cost = Acquisition Cost - Salvage Value` | Amount to depreciate over life |
| `Monthly Depreciation = Depreciable Cost / Life Months` | Straight-line monthly amount |
| `Book Value = Acquisition Cost - Accumulated Depreciation` | Current net book value |
| `Depreciation Rate = 1 / Life Months` | Monthly depreciation rate |
| `Remaining Life = Life Months - Months in Service` | Remaining useful life |
| `Gain on Disposal = Proceeds - Book Value` | When proceeds > book value |
| `Loss on Disposal = Book Value - Proceeds` | When book value > proceeds |

### Status Values

| Entity | Possible Statuses |
|--------|-------------------|
| Asset | `active`, `disposed` |
| Depreciation Run | `draft`, `posted`, `reversed` |
| Disposal | `draft`, `posted`, `reversed` |
| Movement | `draft`, `approved`, `completed`, `cancelled` |

### URL Quick Reference

| Page | URL |
|------|-----|
| Asset List | `/assets` |
| Create Asset | `/assets/create` |
| View Asset | `/assets/{id}` |
| Edit Asset | `/assets/{id}/edit` |
| Asset Categories | `/asset-categories` |
| Depreciation Runs | `/assets/depreciation` |
| New Depreciation Run | `/assets/depreciation/create` |
| View Depreciation Run | `/assets/depreciation/{id}` |
| Disposals | `/assets/disposals` |
| New Disposal | `/assets/disposals/create` |
| Movements | `/assets/movements` |
| New Movement | `/assets/movements/create` |
| Import | `/assets/import` |
| Data Quality | `/assets/data-quality` |
| Bulk Operations | `/assets/bulk-operations` |
| Asset Register Report | `/reports/assets/asset-register` |

### Glossary

| Term | Definition |
|------|------------|
| **Acquisition Cost** | Original purchase price of the asset |
| **Accumulated Depreciation** | Total depreciation charged since acquisition |
| **Book Value** | Net value after accumulated depreciation (`cost - accum depr`) |
| **Salvage Value** | Estimated residual value at end of useful life |
| **Useful Life** | Total period (in months) over which the asset is depreciated |
| **Placed in Service Date** | Date the asset started being used; depreciation begins from this date |
| **Straight-Line** | Equal depreciation amount each month |
| **Depreciation Run** | Batch process that calculates and posts depreciation for a period |
| **Gain on Disposal** | Profit when sold for more than book value |
| **Loss on Disposal** | Loss when sold for less than book value |
| **COA** | Chart of Accounts — the general ledger account structure |

---

_This manual covers the Asset & Depreciation module in Sarang ERP. For additional help, use the **HELP Assistant** ( **?** icon in the navbar) to ask specific how-to questions about asset management._