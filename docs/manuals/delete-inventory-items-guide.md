# Guide: Deleting Inventory Items

**Date**: 2026-02-03  
**Purpose**: Safely delete inventory items from the database

## Overview

This guide explains how to delete inventory items using the artisan command. The system includes safety checks to prevent accidental deletion of items with transaction history.

## Prerequisites

-   Access to run artisan commands
-   Database backup (recommended before bulk deletions)

## Command Usage

### Basic Syntax

```bash
php artisan inventory:delete-items --codes=ITEM_CODE1,ITEM_CODE2,ITEM_CODE3
```

### Options

-   `--codes=`: Comma-separated list of item codes to delete (required)
-   `--force`: Force deletion even if items have transactions (use with caution!)

## Step-by-Step Process

### Step 1: Check Items Before Deletion

The command will show you a summary table before deletion:

```
+-------------+--------------------------+------+--------------+-----------------+------------+
| Code        | Name                     | Type | Transactions | Warehouse Stock | Valuations |
+-------------+--------------------------+------+--------------+-----------------+------------+
| ITEM001     | Item Name                | item | 5            | 2               | 3          |
+-------------+--------------------------+------+--------------+--------------+------------+
```

### Step 2: Handle Items with Transactions

**If items have NO transactions:**

-   Items can be deleted directly without `--force` flag

**If items HAVE transactions:**

-   The command will stop and warn you
-   You must use `--force` flag to proceed
-   **WARNING**: Using `--force` will delete ALL related records:
    -   Inventory transactions
    -   Inventory valuations
    -   Warehouse stock records
    -   Customer price levels
    -   Item units
    -   GR/GI lines

### Step 3: Confirm Deletion

The command will ask for confirmation:

```
Are you sure you want to delete these items? This action cannot be undone! (yes/no)
```

If using `--force` with items that have transactions:

```
WARNING: Items have transactions. This will delete ALL related records. Continue? (yes/no)
```

## Examples

### Example 1: Delete Items Without Transactions

```bash
php artisan inventory:delete-items --codes=PBS-000001,PBS-000003
```

**Output:**

-   Shows summary table
-   Asks for confirmation
-   Deletes items if confirmed

### Example 2: Delete Items With Transactions (Force)

```bash
php artisan inventory:delete-items --codes=SUMATOSM05,SUMATOSM10,TESTITEM001 --force
```

**Output:**

-   Shows summary table with transaction counts
-   Warns about transactions
-   Asks for confirmation twice (general + force warning)
-   Deletes items and all related records if confirmed

### Example 3: Delete Single Item

```bash
php artisan inventory:delete-items --codes=TESTITEM001
```

## What Gets Deleted

When an item is deleted (with or without `--force`):

### Automatically Deleted (Cascade):

-   ✅ Inventory transactions (`inventory_transactions`)
-   ✅ Inventory valuations (`inventory_valuations`)
-   ✅ Warehouse stock records (`inventory_warehouse_stock`)
-   ✅ Customer price levels (`customer_item_price_levels`)
-   ✅ Item units (`inventory_item_units`)

### Set to NULL (Preserved):

-   Purchase invoice lines (`purchase_invoice_lines.inventory_item_id`)
-   Sales quotation lines (`sales_quotation_lines.inventory_item_id`)
-   Delivery order lines (`delivery_order_lines.inventory_item_id`)
-   Goods receipt PO lines (`goods_receipt_po_lines.item_id`)
-   Cost histories (`cost_histories.inventory_item_id`)

### Explicitly Deleted (Force Mode):

-   GR/GI lines (`gr_gi_lines`) - deleted explicitly in force mode

## Safety Features

1. **Transaction Check**: Prevents deletion of items with transactions unless `--force` is used
2. **Confirmation Prompts**: Requires explicit confirmation before deletion
3. **Summary Display**: Shows what will be deleted before proceeding
4. **Error Handling**: Catches and reports errors for individual items
5. **Transaction Wrapping**: Each deletion is wrapped in a database transaction

## Important Notes

⚠️ **WARNING**:

-   Deletion is **permanent** and **cannot be undone**
-   Always backup your database before bulk deletions
-   Items with transactions should only be deleted if you're certain you want to remove all history
-   Related records in purchase/sales orders will have `inventory_item_id` set to NULL (preserves order history)

## Troubleshooting

### Error: "No items found with the provided codes"

-   Check that item codes are spelled correctly
-   Verify items exist in the database
-   Check for typos in the comma-separated list

### Error: "Cannot delete inventory item with existing transactions"

-   Items have transaction history
-   Use `--force` flag if you want to delete anyway
-   Or manually delete/archive transactions first

### Error: Foreign key constraint violation

-   Some related records may not have cascade delete
-   Check database constraints
-   May need to manually delete related records first

## Production Recommendations

1. **Backup First**: Always backup database before deletion

    ```bash
    mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d).sql
    ```

2. **Test First**: Test deletion on a single item first

    ```bash
    php artisan inventory:delete-items --codes=TEST_ITEM
    ```

3. **Verify**: Check that items are actually deleted

    ```bash
    php artisan tinker
    # Then: App\Models\InventoryItem::whereIn('code', ['ITEM1', 'ITEM2'])->count()
    # Should return 0
    ```

4. **Monitor**: Check for any errors in application logs after deletion

## Related Commands

-   `php artisan inventory:check-accuracy` - Check inventory accuracy
-   `php artisan inventory:reconcile-warehouse-stock` - Reconcile warehouse stock
-   `php artisan inventory:check-transactions` - Check item transactions

---

**Last Updated**: 2026-02-03
