# Initial Inventory Entry Manual

## Overview

This manual explains the steps to input initial inventory stock to each warehouse in the Sarang ERP system. There are **three methods** available, each suitable for different scenarios.

---

## Method Comparison

| Method                           | Use Case                               | Warehouse Selection    | Journal Entries | Approval Required |
| -------------------------------- | -------------------------------------- | ---------------------- | --------------- | ----------------- |
| **Method 1: Item Creation**      | Single warehouse, new items            | Default warehouse only | No              | No                |
| **Method 2: GR (Goods Receipt)** | Multiple warehouses, proper accounting | ✅ Yes                 | ✅ Yes          | ✅ Yes            |
| **Method 3: Stock Adjustment**   | Quick corrections                      | ❌ No (limitation)     | No              | No                |

**Recommended**: Use **Method 2 (GR/GI)** for proper initial inventory setup with warehouse-specific tracking and accounting integration.

---

## Method 1: Initial Stock During Item Creation

### When to Use

-   Creating new inventory items
-   Initial stock goes to **one warehouse only** (default warehouse)
-   Simple setup without accounting entries

### Steps

#### Step 1: Create Inventory Item

1. Navigate to: **Inventory** → **Add Item**
2. Fill in required item information:
    - Item Code
    - Item Name
    - Category
    - Item Type: **Item** (not Service)
    - Base Unit
    - Purchase Price
    - Selling Price

#### Step 2: Set Default Warehouse

1. In the item creation form, find **"Default Warehouse"** field
2. Select the warehouse where initial stock will be stored
3. **Important**: This warehouse will receive the initial stock

#### Step 3: Enter Initial Stock

1. Find **"Initial Stock"** field
2. Enter the quantity you have in the default warehouse
3. The system will use the **Purchase Price** as unit cost

#### Step 4: Save Item

1. Click **"Save"** or **"Create Item"**
2. System automatically:
    - Creates inventory transaction (type: `adjustment`, reference: `initial_stock`)
    - Updates warehouse stock in `inventory_warehouse_stock` table
    - Creates initial valuation record

### Limitations

-   ❌ Only works for **one warehouse** (default warehouse)
-   ❌ No journal entries created
-   ❌ Cannot add initial stock to multiple warehouses in one step
-   ❌ Cannot change warehouse after item creation for initial stock

### Example

```
Item: Laptop Dell Inspiron 15
Default Warehouse: Main Warehouse
Initial Stock: 50 units
Purchase Price: Rp 8,500,000
Result: 50 units added to Main Warehouse only
```

---

## Method 2: Goods Receipt (GR) Document (RECOMMENDED)

### When to Use

-   ✅ Initial inventory setup for **multiple warehouses**
-   ✅ Proper accounting with journal entries
-   ✅ Multiple items in one document
-   ✅ Warehouse-specific tracking
-   ✅ Approval workflow for control

### Prerequisites

1. **Inventory items** must be created first
2. **Warehouses** must be set up
3. **Product Categories** must have account mappings configured
4. **User permissions**: `gr-gi.create` and `gr-gi.approve`

### Steps

#### Step 1: Access GR/GI Module

1. Navigate to: **Inventory** → **GR/GI** (or **Goods Receipt/Issue**)
2. Click **"Create New"** or **"Add Document"**
3. Select document type: **Goods Receipt**

#### Step 2: Fill Document Header

**Required Fields:**

1. **Document Type**: **Goods Receipt** (already selected)
2. **Purpose**: Select appropriate purpose

    - **"Found Inventory"** - For initial inventory discovered during setup (RECOMMENDED for initial setup)
    - **"Sample Received"** - If items were received as samples
    - **"Customer Return"** - If items were returned by customers
    - **"Donation Received"** - If items were donated
    - **"Consignment Received"** - If items received on consignment
    - **"Transfer In"** - If items transferred from other location
    - **Note**: Purpose affects account mapping for journal entries

3. **Warehouse** \*: Select the warehouse for this initial stock entry

    - Available warehouses in system:
        - **Main Warehouse** (WH001)
        - **Branch Warehouse** (WH002)
        - **APS LOGISTIK** (WH003)
        - **BOGOR** (WH004)
    - **Important**: Each GR document is for **one warehouse only**
    - To add stock to multiple warehouses, create **separate GR documents** for each warehouse

4. **Transaction Date** \*: Enter the date of initial inventory entry

    - Use the actual date when inventory was counted/recorded
    - This affects accounting period and reporting

5. **Reference Number** (Optional): External reference

    - Example: "INITIAL-2025-001", "OPENING-BALANCE-2025"
    - Useful for tracking initial setup documents

6. **Notes** (Optional): Additional information
    - Example: "Initial inventory setup - Opening balance as of 2025-01-01"
    - Document why this initial stock exists

#### Step 3: Add Document Lines

For each item you want to add to this warehouse:

1. Click **"Add Line"** or **"Add Item"** button
2. Fill in line details:

    - **Item**: Select inventory item from dropdown
    - **Quantity**: Enter quantity for this warehouse
    - **Unit Price**: Enter cost per unit (affects inventory valuation)
        - Use actual purchase cost if known
        - Use average cost if multiple purchases
        - Use estimated cost if exact cost unknown
    - **Notes** (Optional): Line-specific notes
        - Example: "Initial stock from warehouse count"

3. Repeat for all items in this warehouse
4. System calculates **Total Amount** automatically

**Example Line Items:**

```
Line 1:
  Item: MAJUN COLOUR (MJN)
  Quantity: 50
  Unit Price: [Enter actual purchase price]
  Notes: Initial stock - Main Warehouse

Line 2:
  Item: [Select another item]
  Quantity: 30
  Unit Price: [Enter actual purchase price]
  Notes: Initial stock - Main Warehouse

Line 3:
  Item: [Select another item]
  Quantity: 10
  Unit Price: [Enter actual purchase price]
  Notes: Initial stock - Main Warehouse
```

**Available Product Categories in System:**

-   Stationery
-   Electronics
-   Welding
-   Electrical
-   Otomotif
-   Lifting Tools
-   Consumables
-   Chemical
-   Bolt Nut
-   Safety
-   Tools

#### Step 4: Save as Draft

1. Review all information
2. Click **"Save"** or **"Save Draft"**
3. Document status: **Draft**
4. You can edit before submitting

#### Step 5: Submit for Approval

1. Open the draft document
2. Review all details carefully
3. Click **"Submit for Approval"**
4. Document status changes to **"Pending Approval"**
5. Document cannot be edited after submission

#### Step 6: Approve Document

1. **Approver** (user with `gr-gi.approve` permission) opens the document
2. Reviews all details:
    - Warehouse selection
    - Items and quantities
    - Unit prices
    - Total amounts
3. Clicks **"Approve"** button
4. System automatically:
    - ✅ Updates warehouse stock (`inventory_warehouse_stock` table)
    - ✅ Creates inventory transactions (`inventory_transactions` table)
    - ✅ Creates journal entries (Debit: Inventory Account, Credit: Expense/Other Account)
    - ✅ Updates inventory valuations
    - ✅ Changes status to **"Approved"**

### Accounting Impact

When GR document is approved, journal entries are created:

**For Goods Receipt:**

-   **Debit**: Inventory Account (from item's product category)
-   **Credit**: Expense/Other Account (based on purpose mapping)

**Example:**

```
GR Document: Found Inventory, Warehouse: Main Warehouse
Item: MAJUN COLOUR (Category: Consumables)
Quantity: 50, Unit Price: Rp 8,500,000

Journal Entry Created:
  Debit:  Persediaan Consumables (1.1.3.01.07)  Rp 425,000,000
  Credit: Opening Balance / Found Inventory Expense  Rp 425,000,000
```

**Available Inventory Accounts:**

-   Persediaan Stationery (1.1.3.01.01)
-   Persediaan Electronics (1.1.3.01.02)
-   Persediaan Welding (1.1.3.01.03)
-   Persediaan Electrical (1.1.3.01.04)
-   Persediaan Otomotif (1.1.3.01.05)
-   Persediaan Lifting Tools (1.1.3.01.06)
-   Persediaan Consumables (1.1.3.01.07)
-   Persediaan Chemical (1.1.3.01.08)
-   Persediaan Bolt Nut (1.1.3.01.09)
-   Persediaan Safety (1.1.3.01.10)
-   Persediaan Tools (1.1.3.01.11)

### Multiple Warehouses Setup

To add initial inventory to **multiple warehouses**, create **separate GR documents**:

**Example Workflow:**

1. **GR-001**: Main Warehouse (WH001)

    - Item 1: 50 units
    - Item 2: 30 units
    - Item 3: 10 units

2. **GR-002**: Branch Warehouse (WH002)

    - Item 1: 20 units
    - Item 2: 15 units
    - Item 3: 5 units

3. **GR-003**: APS LOGISTIK (WH003)

    - Item 1: 30 units
    - Item 2: 20 units

4. **GR-004**: BOGOR (WH004)
    - Item 1: 25 units
    - Item 2: 15 units

Each GR document:

-   ✅ Updates stock for its specific warehouse
-   ✅ Creates separate journal entries
-   ✅ Maintains proper audit trail

### Best Practices

-   ✅ **Create one GR document per warehouse** for clarity
-   ✅ **Use consistent purpose** (e.g., "Found Inventory" for all initial setup)
-   ✅ **Enter accurate unit prices** for proper inventory valuation
-   ✅ **Add clear notes** explaining initial inventory source
-   ✅ **Use reference numbers** to track initial setup documents
-   ✅ **Review before approval** - approved documents cannot be edited
-   ✅ **Verify journal entries** after approval to ensure correct accounts

---

## Method 3: Stock Adjustment (LIMITED USE)

### When to Use

-   Quick stock corrections
-   Small adjustments to existing items
-   **Note**: Current implementation does **NOT** support warehouse selection

### Current Limitation

⚠️ **Important**: The current stock adjustment feature does **NOT** allow warehouse selection. It adjusts the **overall item stock** but does not update warehouse-specific stock (`inventory_warehouse_stock` table).

### Steps (Current Implementation)

#### Step 1: Access Item Detail Page

1. Navigate to: **Inventory** → Find the item
2. Click on item name or **"View"** button

#### Step 2: Open Stock Adjustment Modal

1. Click **"Adjust Stock"** button (usually +/- icon)
2. Modal form opens

#### Step 3: Enter Adjustment Details

1. **Adjustment Type**:

    - **Increase Stock**: Add items
    - **Decrease Stock**: Remove items

2. **Quantity**: Enter quantity to adjust

3. **Unit Cost**: Enter cost per unit

    - Pre-filled with item's purchase price
    - Can be modified

4. **Notes**: Explain the adjustment
    - Example: "Initial stock entry"

#### Step 4: Submit Adjustment

1. Click **"Adjust Stock"** button
2. System creates adjustment transaction
3. Updates item valuation
4. **Note**: Does **NOT** update warehouse stock

### Recommendation

**Do NOT use this method for initial inventory setup** because:

-   ❌ No warehouse selection
-   ❌ Does not update `inventory_warehouse_stock` table
-   ❌ No journal entries
-   ❌ Limited audit trail

**Use Method 2 (GR/GI) instead** for proper initial inventory setup.

---

## Complete Initial Inventory Setup Workflow

### Recommended Process for Multiple Warehouses

#### Phase 1: Preparation

1. ✅ Ensure all **warehouses** are created
2. ✅ Ensure all **inventory items** are created
3. ✅ Verify **product categories** have account mappings
4. ✅ Ensure user has `gr-gi.create` and `gr-gi.approve` permissions

#### Phase 2: Physical Inventory Count

1. Perform physical count for each warehouse
2. Record quantities per item per warehouse
3. Record unit costs (purchase prices)

#### Phase 3: Data Entry

For each warehouse:

1. **Create GR Document**

    - Document Type: Goods Receipt
    - Purpose: Found Inventory (or appropriate purpose)
    - Warehouse: [Select warehouse]
    - Date: [Inventory count date]

2. **Add All Items**

    - Add line for each item with quantity and unit price
    - Verify quantities match physical count

3. **Save and Submit**

    - Save as Draft
    - Review carefully
    - Submit for Approval

4. **Approve**
    - Approver reviews and approves
    - System updates warehouse stock
    - Journal entries created

#### Phase 4: Verification

1. **Check Warehouse Stock**

    - Navigate to: **Inventory** → Item Detail → Warehouse Stock section
    - Verify quantities match physical count

2. **Check Journal Entries**

    - Navigate to: **Accounting** → Journals
    - Verify journal entries created correctly
    - Verify account mappings are correct

3. **Check Inventory Valuation**
    - Navigate to: **Inventory** → Valuation Report
    - Verify total inventory value matches expectations

### Example: Complete Setup

**Scenario**: Setting up initial inventory for 3 warehouses

**Warehouse 1: Main Warehouse (WH001)**

-   GR-2025-001: Found Inventory
    -   Item A: 50 units @ [Actual Price]
    -   Item B: 30 units @ [Actual Price]
    -   Item C: 10 units @ [Actual Price]

**Warehouse 2: Branch Warehouse (WH002)**

-   GR-2025-002: Found Inventory
    -   Item A: 20 units @ [Actual Price]
    -   Item B: 15 units @ [Actual Price]
    -   Item C: 5 units @ [Actual Price]

**Warehouse 3: APS LOGISTIK (WH003)**

-   GR-2025-003: Found Inventory
    -   Item A: 30 units @ [Actual Price]
    -   Item B: 20 units @ [Actual Price]

**Warehouse 4: BOGOR (WH004)**

-   GR-2025-004: Found Inventory
    -   Item A: 25 units @ [Actual Price]
    -   Item B: 15 units @ [Actual Price]

**Result**:

-   ✅ Total Item A: 125 units across 4 warehouses
-   ✅ Total Item B: 80 units across 4 warehouses
-   ✅ Total Item C: 15 units across 2 warehouses
-   ✅ Proper warehouse-specific tracking
-   ✅ Proper journal entries for each warehouse
-   ✅ Complete audit trail

---

## Troubleshooting

### Problem: GR Document Not Updating Warehouse Stock

**Possible Causes**:

-   Document not approved (only approved documents update stock)
-   Warehouse not selected
-   Item not found

**Solutions**:

1. Verify document status is **"Approved"**
2. Check warehouse selection in document header
3. Verify item exists and is active
4. Check `inventory_warehouse_stock` table directly

### Problem: Journal Entries Not Created

**Possible Causes**:

-   Product category missing account mappings
-   Purpose missing account mappings
-   Document not approved

**Solutions**:

1. Verify product category has inventory account mapped
2. Check GR/GI purpose account mappings
3. Ensure document is approved (journal entries created on approval)
4. Check journal entries in Accounting → Journals

### Problem: Wrong Warehouse Stock Updated

**Possible Causes**:

-   Wrong warehouse selected in GR document
-   Multiple GR documents for same item

**Solutions**:

1. Review GR document warehouse selection
2. Check all GR documents for the item
3. Verify `inventory_warehouse_stock` table shows correct warehouse
4. Create correction GR document if needed

### Problem: Cannot Approve GR Document

**Possible Causes**:

-   User lacks `gr-gi.approve` permission
-   Document not in "Pending Approval" status
-   Missing required information

**Solutions**:

1. Contact administrator for approval permission
2. Verify document status
3. Check all required fields are filled
4. Ensure document is submitted (not draft)

---

## Quick Reference

### GR Document Workflow

```
Create → Save Draft → Submit → Approve → Stock Updated + Journals Created
```

### Key Locations

-   **Create GR**: Inventory → GR/GI → Create New
-   **View GR List**: Inventory → GR/GI
-   **Check Warehouse Stock**: Inventory → Item Detail → Warehouse Stock
-   **View Journals**: Accounting → Journals

### Required Permissions

-   `gr-gi.create`: Create GR documents
-   `gr-gi.approve`: Approve GR documents
-   `inventory.view`: View inventory items
-   `warehouse.view`: View warehouses

### Important Notes

-   ✅ **One GR document = One warehouse**
-   ✅ **Multiple items** can be in one GR document
-   ✅ **Approval required** for stock updates and journal entries
-   ✅ **Cannot edit** after approval
-   ✅ **Journal entries** created automatically on approval
-   ✅ **Warehouse stock** updated automatically on approval

---

## Summary

**For Initial Inventory Setup:**

1. ✅ **Use GR (Goods Receipt) documents** - Recommended method
2. ✅ **Create one GR per warehouse** - Clear organization
3. ✅ **Use "Found Inventory" purpose** - Appropriate for initial setup
4. ✅ **Enter accurate unit prices** - Proper valuation
5. ✅ **Approve documents** - Required for stock updates
6. ✅ **Verify results** - Check warehouse stock and journals

**Avoid:**

-   ❌ Stock Adjustment (no warehouse support)
-   ❌ Initial Stock during item creation (single warehouse only)
-   ❌ Manual database updates (bypasses system)

---

**End of Manual**

_For additional assistance, refer to:_

-   _Inventory Module Manual: `docs/manuals/inventory-module-manual.md`_
-   _First Things to Do Manual: `docs/manuals/first-things-to-do-manual.md`_
