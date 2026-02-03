# Warehouse Stock Transfer Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Understanding Transfer Types](#understanding-transfer-types)
4. [Method 1: Direct Transfer](#method-1-direct-transfer)
5. [Method 2: Two-Step Transfer (ITO/ITI)](#method-2-two-step-transfer-itoiti)
6. [Viewing Transfer History](#viewing-transfer-history)
7. [Managing Pending Transfers](#managing-pending-transfers)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)
10. [FAQs](#faqs)

---

## Introduction

### What is Warehouse Stock Transfer?

Warehouse Stock Transfer allows you to move inventory items from one warehouse to another warehouse. This is essential for:

-   **Distributing stock** across multiple locations
-   **Replenishing** warehouses with low stock
-   **Consolidating inventory** from multiple warehouses
-   **Managing inter-warehouse movements** for logistics

### Who Can Transfer Stock?

Users with the **`warehouse.transfer`** permission can transfer stock between warehouses. Contact your system administrator if you need this permission.

---

## Prerequisites

Before transferring stock, ensure:

1. ✅ **Both warehouses exist** and are active
2. ✅ **Source warehouse has sufficient stock** for the item
3. ✅ **Item exists** in the inventory system
4. ✅ **You have transfer permissions** (`warehouse.transfer`)
5. ✅ **Transit warehouse is configured** (if using ITO/ITI method)

---

## Understanding Transfer Types

The system supports three transfer methods:

### 1. Direct Transfer (Immediate)

-   **When to use**: Both warehouses are accessible, immediate transfer needed
-   **Process**: One-step process, stock moves directly from source to destination
-   **Status**: Completed immediately
-   **Best for**: Same location transfers, urgent replenishments

### 2. Inventory Transfer Out (ITO)

-   **When to use**: Items need to go through transit (shipping, logistics)
-   **Process**: Two-step process
    -   Step 1: Move items from source warehouse to transit warehouse
    -   Step 2: Complete transfer by moving from transit to destination
-   **Status**: In Transit → Completed
-   **Best for**: Inter-location transfers, shipping scenarios

### 3. Inventory Transfer In (ITI)

-   **When to use**: Completing a pending ITO transfer
-   **Process**: Final step of ITO process
-   **Status**: Completes pending transfer
-   **Best for**: Receiving items that were sent via ITO

---

## Method 1: Direct Transfer

### Overview

Direct Transfer immediately moves stock from one warehouse to another in a single operation.

### Step-by-Step Instructions

#### Step 1: Access Transfer Function

**Option A: From Warehouses List**

1. Navigate to **Inventory** → **Warehouses**
2. Click the **"Transfer Stock"** button at the top of the page

**Option B: From Warehouse Detail Page**

1. Navigate to **Inventory** → **Warehouses**
2. Click on a warehouse name to view details
3. Click the **"Transfer Stock"** button in the header

#### Step 2: Fill Transfer Form

The transfer modal will open. Fill in the following fields:

1. **Item** (Required)

    - Click the dropdown and select the item to transfer
    - Items are listed as: `CODE - Name`
    - Example: `SUMATOSM05 - Sumato SM-05`

2. **From Warehouse** (Required)

    - Select the source warehouse
    - This is where stock will be deducted from
    - Format: `CODE - Name` (e.g., `WH001 - Main Warehouse`)

3. **To Warehouse** (Required)

    - Select the destination warehouse
    - This is where stock will be added to
    - Must be different from source warehouse
    - Format: `CODE - Name` (e.g., `WH002 - Branch Warehouse`)

4. **Quantity** (Required)

    - Enter the number of units to transfer
    - Must be a positive number
    - Cannot exceed available stock in source warehouse
    - The system shows available stock: "Available: X units"

5. **Notes** (Optional)
    - Add any relevant notes about the transfer
    - Example: "Replenishing branch warehouse stock"
    - Example: "Transfer for sales order #12345"

#### Step 3: Review Stock Information

The system displays real-time stock information:

-   **Source Stock**: Current stock in source warehouse
-   **Destination Stock**: Current stock in destination warehouse
-   **After Transfer**: Projected stock in destination after transfer

**Example Display:**

```
Stock Information
Source Stock:        150 units
Destination Stock:    50 units
After Transfer:      200 units
```

#### Step 4: Validate and Submit

1. **Verify** all information is correct
2. **Check** that quantity doesn't exceed available stock
3. **Ensure** source and destination warehouses are different
4. Click **"Transfer Stock"** button

#### Step 5: Confirmation

-   Success message: "Stock transfer completed successfully"
-   Stock levels are updated immediately
-   Transfer appears in transfer history
-   Inventory transactions are created

### Example: Direct Transfer

**Scenario**: Transfer 50 units of "Sumato SM-05" from Main Warehouse to Branch Warehouse

1. **Access**: Go to `/warehouses` → Click "Transfer Stock"
2. **Select Item**: `SUMATOSM05 - Sumato SM-05`
3. **From Warehouse**: `WH001 - Main Warehouse`
4. **To Warehouse**: `WH002 - Branch Warehouse`
5. **Quantity**: `50`
6. **Notes**: `Replenishing branch stock`
7. **Submit**: Click "Transfer Stock"

**Result**:

-   Main Warehouse: 150 → 100 units (deducted 50)
-   Branch Warehouse: 50 → 100 units (added 50)
-   Transfer completed immediately

---

## Method 2: Two-Step Transfer (ITO/ITI)

### Overview

Two-step transfer uses a transit warehouse to track items during shipping/logistics. This is useful when items are physically moving between locations.

### Part A: Create Inventory Transfer Out (ITO)

#### Step 1: Access Transfer Function

1. Navigate to **Inventory** → **Warehouses**
2. Click **"Transfer Stock"** button
3. Select **Transfer Type**: **"Inventory Transfer Out (ITO)"**

#### Step 2: Fill ITO Form

1. **Item** (Required)

    - Select the item to transfer
    - Only items with stock in source warehouse are available

2. **From Warehouse** (Required)

    - Select source warehouse
    - System automatically identifies transit warehouse

3. **To Warehouse** (Required)

    - Select final destination warehouse
    - This is where items will eventually arrive

4. **Quantity** (Required)

    - Enter quantity to transfer
    - Cannot exceed available stock

5. **Notes** (Optional)
    - Add shipping notes, tracking numbers, etc.
    - Example: "Shipment via courier, tracking #ABC123"

#### Step 3: Submit ITO

1. Click **"Create Transfer Out"**
2. System creates transfer with status: **"In Transit"**
3. Stock moves from source warehouse to transit warehouse
4. Transfer appears in **Pending Transfers** list

**What Happens**:

-   ✅ Stock deducted from source warehouse
-   ✅ Stock added to transit warehouse
-   ✅ Transfer status: "In Transit"
-   ✅ Transfer ID created for tracking

### Part B: Complete Inventory Transfer In (ITI)

#### Step 1: Access Pending Transfers

1. Navigate to **Inventory** → **Warehouses**
2. Click **"Pending Transfers"** button
3. Or go directly to `/warehouses/pending-transfers-page`

#### Step 2: Find Pending Transfer

The pending transfers list shows:

-   **Item**: Item name and code
-   **From Warehouse**: Source warehouse
-   **To Warehouse**: Destination warehouse
-   **Quantity**: Amount in transit
-   **Date**: Transfer creation date
-   **Status**: "In Transit"

#### Step 3: Complete Transfer

**Option A: Complete via Pending Transfers Page**

1. Find the transfer in the list
2. Click **"Receive"** or **"Complete Transfer"** button
3. Verify quantity received (can differ from sent quantity)
4. Add notes if quantity differs
5. Click **"Complete Transfer"**

**Option B: Complete via Transfer Modal**

1. Go to **Warehouses** → Click **"Transfer Stock"**
2. Select **Transfer Type**: **"Inventory Transfer In (ITI)"**
3. Select **Pending Transfer** from dropdown
4. Enter **Received Quantity** (if different)
5. Add notes
6. Click **"Complete Transfer"**

#### Step 4: Confirmation

-   Success message: "Transfer completed successfully"
-   Stock moves from transit warehouse to destination warehouse
-   Transfer status changes to "Completed"
-   Transfer removed from pending list

**What Happens**:

-   ✅ Stock deducted from transit warehouse
-   ✅ Stock added to destination warehouse
-   ✅ Transfer status: "Completed"
-   ✅ Transfer removed from pending transfers

### Example: Two-Step Transfer

**Scenario**: Ship 100 units of "Sumato SM-05" from Main Warehouse to Branch Warehouse via courier

**Step 1: Create ITO**

1. Go to `/warehouses` → Click "Transfer Stock"
2. Select Type: **"Inventory Transfer Out (ITO)"**
3. Item: `SUMATOSM05 - Sumato SM-05`
4. From: `WH001 - Main Warehouse`
5. To: `WH002 - Branch Warehouse`
6. Quantity: `100`
7. Notes: `Shipment via courier, tracking #XYZ789`
8. Click **"Create Transfer Out"**

**Result**:

-   Main Warehouse: 200 → 100 units
-   Transit Warehouse: 0 → 100 units
-   Status: In Transit

**Step 2: Complete ITI (After Receiving Shipment)**

1. Go to `/warehouses/pending-transfers-page`
2. Find the transfer for Sumato SM-05
3. Click **"Receive"**
4. Verify quantity: `100` (or enter actual received quantity)
5. Click **"Complete Transfer"**

**Result**:

-   Transit Warehouse: 100 → 0 units
-   Branch Warehouse: 50 → 150 units
-   Status: Completed

---

## Viewing Transfer History

### Access Transfer History

1. Navigate to **Inventory** → **Warehouses**
2. Click **"Transfer History"** button
3. Or go directly to `/warehouses/transfer-history`

### Understanding Transfer History

The transfer history shows all completed transfers with:

-   **Date**: Transfer date
-   **Item**: Item code and name
-   **From Warehouse**: Source warehouse code and name
-   **To Warehouse**: Destination warehouse code and name
-   **Quantity**: Amount transferred
-   **Type**: Transfer type (Direct, ITO, ITI)
-   **Status**: Transfer status
-   **Notes**: Transfer notes

### Filtering Transfer History

You can filter transfers by:

-   **Date Range**: Select from and to dates
-   **Warehouse**: Filter by specific warehouse
-   **Item**: Filter by specific item
-   **Status**: Filter by transfer status

### Exporting Transfer History

1. Apply filters if needed
2. Click **"Export"** button
3. Transfer history will be exported (format depends on system configuration)

---

## Managing Pending Transfers

### Viewing Pending Transfers

1. Navigate to **Inventory** → **Warehouses**
2. Click **"Pending Transfers"** button
3. Or go to `/warehouses/pending-transfers-page`

### Pending Transfers List

Shows all transfers with status "In Transit":

-   **Item**: Item being transferred
-   **From Warehouse**: Source warehouse
-   **To Warehouse**: Destination warehouse
-   **Quantity**: Amount in transit
-   **Date**: Transfer creation date
-   **Actions**: Complete transfer button

### Completing Pending Transfers

1. Find the transfer in the list
2. Click **"Receive"** or **"Complete Transfer"**
3. Verify quantity received
4. Enter actual received quantity if different
5. Add notes if needed
6. Click **"Complete Transfer"**

### Handling Partial Receipts

If you receive less than sent quantity:

1. Open the pending transfer
2. Enter **actual received quantity** (less than sent)
3. Add notes explaining the difference
    - Example: "Received 95 units, 5 units damaged in transit"
4. Complete transfer

**Note**: The system will adjust quantities accordingly. The difference will remain in transit warehouse until resolved.

### Canceling Pending Transfers

If a transfer needs to be canceled:

1. Contact system administrator
2. Or reverse the transfer manually:
    - Complete ITI to return items to source warehouse
    - Or create adjustment to correct stock levels

---

## Best Practices

### 1. Verify Stock Before Transfer

-   Always check available stock before initiating transfer
-   Ensure source warehouse has sufficient quantity
-   Account for reserved quantities if applicable

### 2. Use Appropriate Transfer Type

-   **Direct Transfer**: Same location, immediate need
-   **ITO/ITI**: Shipping between locations, need tracking

### 3. Document Transfers Properly

-   Always add notes explaining the reason for transfer
-   Include reference numbers (sales orders, purchase orders)
-   Note any special handling requirements

### 4. Complete ITO Transfers Promptly

-   Complete ITI transfers as soon as items are received
-   Verify quantities match before completing
-   Report discrepancies immediately

### 5. Regular Reconciliation

-   Periodically review pending transfers
-   Complete or resolve all pending transfers
-   Reconcile transit warehouse stock regularly

### 6. Stock Level Monitoring

-   Monitor stock levels after transfers
-   Ensure destination warehouse has adequate stock
-   Check for low stock alerts after transfers

### 7. Audit Trail

-   All transfers are logged in audit trail
-   Review transfer history regularly
-   Use transfer history for reconciliation

---

## Troubleshooting

### Problem: "Insufficient stock in source warehouse"

**Cause**: Trying to transfer more than available stock

**Solution**:

1. Check current stock in source warehouse
2. Reduce transfer quantity
3. Account for reserved quantities if applicable
4. Verify stock hasn't been transferred elsewhere

### Problem: "Source and destination warehouses must be different"

**Cause**: Selected same warehouse for source and destination

**Solution**:

1. Select different warehouses
2. Verify warehouse selection in form

### Problem: "Transit warehouse not found"

**Cause**: Source warehouse doesn't have transit warehouse configured

**Solution**:

1. Contact system administrator
2. Configure transit warehouse for source warehouse
3. Or use Direct Transfer instead

### Problem: Cannot find pending transfer

**Cause**: Transfer may have been completed or doesn't exist

**Solution**:

1. Check transfer history instead
2. Verify transfer ID if known
3. Check if transfer was completed by another user
4. Contact administrator if needed

### Problem: Quantity mismatch after ITI

**Cause**: Received quantity differs from sent quantity

**Solution**:

1. Enter actual received quantity when completing ITI
2. Add notes explaining the difference
3. Create adjustment if needed to reconcile
4. Report to management if significant discrepancy

### Problem: Transfer button not visible

**Cause**: Missing `warehouse.transfer` permission

**Solution**:

1. Contact system administrator
2. Request `warehouse.transfer` permission
3. Verify user role has correct permissions

### Problem: Stock not updating after transfer

**Cause**: Possible system issue or cache

**Solution**:

1. Refresh the page
2. Check transfer history to confirm transfer completed
3. Verify stock levels in warehouse detail page
4. Contact IT support if issue persists

---

## FAQs

### Q1: Can I transfer multiple items in one transfer?

**A**: Currently, each transfer handles one item at a time. Create separate transfers for each item.

### Q2: What happens if I make a mistake in transfer?

**A**: You can create a reverse transfer (transfer back) or use stock adjustment to correct. Contact administrator for assistance.

### Q3: Can I cancel a completed transfer?

**A**: Completed transfers cannot be canceled, but you can create a reverse transfer to move stock back.

### Q4: How do I know which transit warehouse is used?

**A**: The system automatically uses the transit warehouse configured for the source warehouse. Check warehouse settings or contact administrator.

### Q5: What's the difference between Direct Transfer and ITO/ITI?

**A**:

-   **Direct Transfer**: Immediate, one-step, no transit tracking
-   **ITO/ITI**: Two-step, uses transit warehouse, tracks items during shipping

### Q6: Can I transfer stock between items?

**A**: The warehouse transfer function moves stock between warehouses for the same item. To transfer between different items, use the "Transfer Stock" function on the inventory item detail page (different feature).

### Q7: How long do pending transfers stay in the system?

**A**: Pending transfers remain until completed. There's no automatic expiration. Complete them as soon as items are received.

### Q8: What if I receive damaged items?

**A**: When completing ITI, enter the actual good quantity received. Create a separate adjustment or note for damaged items. Document in transfer notes.

### Q9: Can I see who created a transfer?

**A**: Yes, transfer history includes user information. Check audit logs for detailed user tracking.

### Q10: Do transfers affect inventory valuation?

**A**: Transfers move stock between warehouses but don't change the total inventory value. Valuation is calculated per item across all warehouses.

---

## Quick Reference

### Direct Transfer Steps

1. Warehouses → Transfer Stock
2. Select Item, From, To, Quantity
3. Add Notes
4. Submit

### ITO Steps

1. Warehouses → Transfer Stock → ITO
2. Fill form
3. Create Transfer Out
4. Items move to transit

### ITI Steps

1. Warehouses → Pending Transfers
2. Find transfer
3. Complete Transfer
4. Items move to destination

### Key Routes

-   Transfer Stock: `/warehouses` → "Transfer Stock" button
-   Pending Transfers: `/warehouses/pending-transfers-page`
-   Transfer History: `/warehouses/transfer-history`
-   Warehouse Detail: `/warehouses/{id}`

---

## Support

For additional assistance:

-   Check system documentation
-   Contact your system administrator
-   Review audit logs for transfer details
-   Check warehouse settings and configurations

---

**Last Updated**: 2026-01-22  
**Version**: 1.0
