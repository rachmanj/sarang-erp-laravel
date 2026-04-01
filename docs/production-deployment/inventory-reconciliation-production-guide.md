# Production Deployment Guide: Inventory Reconciliation Fixes

**Date**: 2026-02-03  
**Purpose**: Deploy inventory accuracy verification and reconciliation commands to production

## Overview

This guide covers deploying and running the inventory reconciliation fixes on the production server. The fixes address data integrity issues where warehouse stock records don't match transaction-based stock calculations.

## Prerequisites

-   Access to production server
-   Database backup capability
-   Ability to run artisan commands
-   Maintenance window (recommended for initial reconciliation)

## Step 1: Pre-Deployment Checklist

### 1.1 Backup Database

```bash
# Create a full database backup before making any changes
php artisan backup:run  # If using backup package
# OR
mysqldump -u [username] -p [database_name] > inventory_reconciliation_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 1.2 Verify Code Deployment

Ensure the following files are deployed:

-   `app/Console/Commands/CheckInventoryAccuracy.php`
-   `app/Console/Commands/ReconcileWarehouseStock.php`
-   `app/Console/Commands/CheckItemTransactions.php` (optional, for diagnostics)

### 1.3 Test Commands Are Available

```bash
php artisan list | grep inventory
# Should show:
# inventory:check-accuracy
# inventory:reconcile-warehouse-stock
# inventory:check-transactions
```

## Step 2: Initial Assessment (Read-Only)

### 2.1 Check Current Accuracy Status

```bash
# Check all items (this is read-only, safe to run)
php artisan inventory:check-accuracy > inventory_accuracy_report_$(date +%Y%m%d_%H%M%S).txt
```

**Review the report:**

-   Note the number of items with discrepancies
-   Note items with stock but no warehouse records
-   Identify any patterns (e.g., all items missing warehouse_id)

### 2.2 Check Specific Problem Items (if any)

```bash
# If you know specific item codes with issues
php artisan inventory:check-accuracy ITEM_CODE > item_ITEM_CODE_report.txt
```

## Step 3: Reconciliation Strategy

### Option A: Reconcile All Items (Recommended for Initial Run)

**When to use:**

-   First time running reconciliation
-   During maintenance window
-   When you want to fix all discrepancies at once

**Steps:**

```bash
# 1. Identify default warehouse ID (usually WH001 = ID 1)
php artisan tinker
# Then run: App\Models\Warehouse::where('code', 'WH001')->first()->id
# Exit tinker

# 2. Reconcile all items using default warehouse for transactions without warehouse_id
php artisan inventory:reconcile-warehouse-stock --warehouse_id=1

# 3. Verify reconciliation
php artisan inventory:check-accuracy > post_reconciliation_report.txt
```

### Option B: Reconcile Specific Items (Safer, Incremental)

**When to use:**

-   Testing the reconciliation process
-   Fixing specific problematic items
-   When you want more control

**Steps:**

```bash
# 1. Reconcile one item at a time
php artisan inventory:reconcile-warehouse-stock ITEM_CODE --warehouse_id=1

# 2. Verify each item after reconciliation
php artisan inventory:check-accuracy ITEM_CODE

# 3. Repeat for other items
```

### Option C: Reconcile Items Without Default Warehouse

**When to use:**

-   Items that don't have `default_warehouse_id` set
-   You need to specify a warehouse manually

**Steps:**

```bash
# Reconcile with explicit warehouse ID
php artisan inventory:reconcile-warehouse-stock ITEM_CODE --warehouse_id=1
```

## Step 4: Post-Reconciliation Verification

### 4.1 Verify All Items

```bash
php artisan inventory:check-accuracy > final_verification_$(date +%Y%m%d_%H%M%S).txt
```

**Expected Result:**

```
Summary:
Total items checked: [number]
Items with discrepancies: 0
Items with stock but no warehouse records: 0

✅ All items verified: No discrepancies found!
```

### 4.2 Spot Check Specific Items

```bash
# Check a few random items to ensure accuracy
php artisan inventory:check-accuracy ITEM_CODE_1
php artisan inventory:check-accuracy ITEM_CODE_2
php artisan inventory:check-accuracy ITEM_CODE_3
```

### 4.3 Verify in Web Interface

1. Navigate to `/inventory/{item_id}` for a few items
2. Verify that:
    - Current Stock matches Stock by Warehouse total
    - Stock by Warehouse shows correct allocations
    - Valuation History matches current stock

## Step 5: Monitoring & Maintenance

### 5.1 Schedule Regular Accuracy Checks

**Recommended:** Run weekly or monthly checks

```bash
# Add to cron (weekly check, email results)
0 2 * * 0 cd /path/to/project && php artisan inventory:check-accuracy > /var/log/inventory_accuracy_$(date +\%Y\%m\%d).log 2>&1
```

### 5.2 Monitor for New Discrepancies

If discrepancies appear after reconciliation:

1. Check recent transactions: `php artisan inventory:check-transactions ITEM_CODE`
2. Verify transaction creation includes `warehouse_id`
3. Check if new transactions are being created without warehouse allocation

### 5.3 Reconcile New Items

When new items are created with initial stock:

-   Ensure they have `default_warehouse_id` set
-   Verify transactions include `warehouse_id`
-   Run reconciliation if needed: `php artisan inventory:reconcile-warehouse-stock NEW_ITEM_CODE`

## Step 6: Troubleshooting

### Issue: Reconciliation Still Shows Discrepancies

**Possible Causes:**

1. Items without `default_warehouse_id` and no `--warehouse_id` provided
2. Transactions created after reconciliation
3. Manual database changes

**Solution:**

```bash
# Check specific item details
php artisan inventory:check-accuracy ITEM_CODE

# Check transactions
php artisan inventory:check-transactions ITEM_CODE

# Reconcile with explicit warehouse
php artisan inventory:reconcile-warehouse-stock ITEM_CODE --warehouse_id=1
```

### Issue: Negative Warehouse Stock

**Possible Causes:**

1. Transfer transactions that went wrong
2. Manual stock adjustments
3. Orphaned warehouse stock records

**Solution:**
The reconciliation command now automatically zeros out warehouse stock records that have no corresponding transactions. Run reconciliation again.

### Issue: Items Without Default Warehouse

**Solution:**

1. Set default warehouse for items: Update `inventory_items.default_warehouse_id`
2. Or use `--warehouse_id` flag when reconciling

## Step 7: Rollback Plan (If Needed)

If reconciliation causes issues:

### 7.1 Restore Database Backup

```bash
# Restore from backup created in Step 1.1
mysql -u [username] -p [database_name] < inventory_reconciliation_backup_[timestamp].sql
```

### 7.2 Manual Correction (if needed)

```sql
-- If you need to manually fix specific warehouse stock
UPDATE inventory_warehouse_stock
SET quantity_on_hand = [correct_value]
WHERE item_id = [item_id] AND warehouse_id = [warehouse_id];
```

## Important Notes

1. **Backup First**: Always backup database before running reconciliation
2. **Maintenance Window**: Consider running initial reconciliation during low-traffic period
3. **Monitor**: Watch for any errors during reconciliation
4. **Verify**: Always verify results after reconciliation
5. **Document**: Keep records of when reconciliation was run and results

## Commands Reference

```bash
# Check accuracy (read-only)
php artisan inventory:check-accuracy [item_code]

# Reconcile warehouse stock
php artisan inventory:reconcile-warehouse-stock [item_code] [--warehouse_id=1]

# Check transactions for an item (diagnostic)
php artisan inventory:check-transactions [item_code]

# Fix inventory valuation (if needed)
php artisan inventory:fix-valuation [item_id]
```

## Success Criteria

✅ All items show 0 discrepancies  
✅ All items with stock have warehouse records  
✅ Current Stock = Sum of Warehouse Stock for all items  
✅ Web interface shows consistent stock levels

## Support

If you encounter issues:

1. Check the accuracy report for patterns
2. Review transaction details for problematic items
3. Verify warehouse assignments
4. Check application logs for errors

---

**Last Updated**: 2026-02-03  
**Version**: 1.0
