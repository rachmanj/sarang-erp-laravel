# Production Deployment Guide - Sales Order Approval Fix & Delivery Order Inventory

## Overview
This guide covers deploying the Sales Order approval fix and Delivery Order inventory reduction to production.

**Sales Order Fix**: Addresses cases where Sales Orders have `approval_status = 'pending'` but missing approval workflow records, and ensures the "officer" role exists in both systems.

**Delivery Order Inventory**: Ensures inventory stock is reduced when Picked Qty or Delivered Qty is updated. A backfill command creates missing inventory transactions for existing DOs.

## Files Changed
1. `app/Services/SalesService.php` - Auto-creates approval records if missing during approval attempt
2. `app/Services/DeliveryService.php` - Inventory reduction when Picked Qty or Delivered Qty updated; reversal on DO cancel
3. `routes/web/orders.php` - Added fix route for individual Sales Orders
4. `app/Console/Commands/FixSalesOrderApproval.php` - Command to fix single or all Sales Orders
5. `app/Console/Commands/EnsureOfficerRole.php` - Command to ensure officer role exists
6. `app/Console/Commands/BackfillDeliveryOrderInventoryTransactions.php` - Backfill inventory transactions for existing DOs
7. `app/Console/Kernel.php` - Registered new commands

## Deployment Steps

### 1. Deploy Code Changes
```bash
# Pull latest code
git pull origin main

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
```

### 2. Ensure Officer Role Exists (CRITICAL - Do this first!)

**Step 2.1: Create Officer Role in Spatie Permission System**
```bash
php artisan role:ensure-officer --create-spatie
```

This will:
- Create "officer" role in Spatie Permission system (visible on `/admin/roles` page)
- Ensure at least superadmin has the officer role for approval workflows

**Step 2.2: Assign Officer Role to Users (if needed)**
```bash
# List current users with officer role
php artisan role:ensure-officer --list

# Assign to specific user
php artisan role:ensure-officer --user=superadmin

# Assign to another user
php artisan role:ensure-officer --user={username}
```

**Step 2.3: Verify Officer Role**
```sql
-- Check Spatie Permission role exists
SELECT id, name, guard_name FROM roles WHERE name = 'officer';

-- Check users with officer role in approval workflow
SELECT ur.user_id, u.username, u.name, ur.role_name, ur.is_active 
FROM user_roles ur 
JOIN users u ON ur.user_id = u.id 
WHERE ur.role_name = 'officer' AND ur.is_active = 1;
```

### 3. Fix Existing Sales Orders with Missing Approval Records

**Option A: Fix All Sales Orders at Once (Recommended)**
```bash
php artisan sales-order:fix-approval --all
```

This will:
- Find all Sales Orders with `approval_status = 'pending'` but no approval records
- Create missing approval workflow records for each
- Show summary of fixed/failed orders

**Option B: Fix Individual Sales Orders**
```bash
# Fix a specific Sales Order
php artisan sales-order:fix-approval {orderNo}

# Example:
php artisan sales-order:fix-approval 71260600002
```

**Option C: Use the Web Route (For ad-hoc fixes)**
```
GET /sales-orders/fix-approval/{orderNo}
```

### 4. Backfill Delivery Order Inventory Transactions (If deploying DO inventory fix)

For existing Delivery Orders with Picked Qty or Delivered Qty but no inventory sale transactions:

```bash
# Dry run first (no changes)
php artisan delivery-orders:backfill-inventory-transactions --dry-run

# Execute backfill
php artisan delivery-orders:backfill-inventory-transactions
```

This creates inventory transactions for DO lines that should have reduced stock but don't.

### 5. Verify the Fix

**Check Sales Orders with Missing Approvals:**
```sql
-- Find Sales Orders that still have issues
SELECT so.id, so.order_no, so.approval_status, COUNT(soa.id) as approval_count
FROM sales_orders so
LEFT JOIN sales_order_approvals soa ON so.id = soa.sales_order_id
WHERE so.approval_status = 'pending'
GROUP BY so.id, so.order_no, so.approval_status
HAVING approval_count = 0;
```

**Check User Roles:**
```sql
-- Verify users have required roles for approval workflows
SELECT u.id, u.username, u.name, ur.role_name, ur.is_active
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
WHERE ur.role_name IN ('officer', 'supervisor', 'manager')
  AND ur.is_active = 1
ORDER BY ur.role_name, u.username;
```

**Check Approval Thresholds:**
```sql
-- Verify approval thresholds exist
SELECT document_type, min_amount, max_amount, required_approvals
FROM approval_thresholds
WHERE document_type = 'sales_order'
ORDER BY min_amount;
```

### 6. Post-Deployment Testing

1. **Test Sales Order Approval:**
   - Navigate to a Sales Order with status "DRAFT"
   - Click "Approve Sales Order"
   - Verify it approves successfully

2. **Verify Officer Role on Admin Page:**
   - Navigate to `/admin/roles`
   - Verify "officer" role appears in the list

3. **Check Logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "approval"
   ```

### 7. Post-Deployment Monitoring

After deployment, monitor:
- Sales Order approval success rate
- Any errors in logs related to approval workflows
- User feedback on approval process
- Check if new Sales Orders create approval records automatically

**Check Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "approval"
```

## Quick Reference Commands

```bash
# 1. Ensure officer role exists (CRITICAL - Run first!)
php artisan role:ensure-officer --create-spatie

# 2. Fix all Sales Orders with missing approvals
php artisan sales-order:fix-approval --all

# 3. List users with officer role
php artisan role:ensure-officer --list

# 4. Assign officer role to a user
php artisan role:ensure-officer --user={username}

# 5. Backfill DO inventory (dry-run first)
php artisan delivery-orders:backfill-inventory-transactions --dry-run
php artisan delivery-orders:backfill-inventory-transactions
```

## Rollback Plan

If issues occur, you can rollback by:

1. **Revert code changes:**
```bash
git revert {commit-hash}
php artisan config:clear
php artisan cache:clear
```

2. **The auto-fix logic in SalesService is safe** - it only creates missing records, doesn't modify existing ones

3. **Roles can be removed if needed:**
```sql
-- Remove officer role from Spatie Permission (if needed)
DELETE FROM roles WHERE name = 'officer';

-- Deactivate officer role in user_roles (if needed)
UPDATE user_roles SET is_active = 0 WHERE role_name = 'officer';
```

## Important Notes

- **No database migrations required** - only code changes
- **Safe to deploy** - auto-fix logic only creates missing records
- **Two role systems:**
  - **Spatie Permission roles** (`roles` table) - shown on `/admin/roles` page, used for permissions
  - **User Roles** (`user_roles` table) - used for approval workflows (officer, supervisor, manager)
- The `role:ensure-officer` command ensures both systems are synchronized
- The fix route (`/sales-orders/fix-approval/{orderNo}`) is kept for future use
- Future Sales Orders will automatically create approval records when created

## Troubleshooting

### Issue: Command not found
**Solution:** Commands are registered in `app/Console/Kernel.php`. If command doesn't work, check:
```bash
php artisan list | grep -i "sales-order\|role:ensure\|delivery-orders"
```

### Issue: No users with officer role
**Solution:** Run:
```bash
php artisan role:ensure-officer --user=superadmin
```

### Issue: Sales Orders still can't be approved
**Solution:** 
1. Check if approval records exist:
   ```sql
   SELECT * FROM sales_order_approvals WHERE sales_order_id = {id};
   ```
2. If missing, run:
   ```bash
   php artisan sales-order:fix-approval {orderNo}
   ```

### Issue: Officer role not showing on /admin/roles page
**Solution:** Run:
```bash
php artisan role:ensure-officer --create-spatie
```

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify user roles are correctly assigned
3. Verify approval thresholds exist: `SELECT * FROM approval_thresholds WHERE document_type = 'sales_order'`
4. Verify approval workflows exist: `SELECT * FROM approval_workflows WHERE document_type = 'sales_order' AND is_active = 1`
