# Production Deployment Guide - Sales Order Approval Fix

## Overview
This guide covers deploying the Sales Order approval fix to production. The fix addresses cases where Sales Orders have `approval_status = 'pending'` but missing approval workflow records.

## Files Changed
1. `app/Services/SalesService.php` - Auto-creates approval records if missing during approval attempt
2. `routes/web/orders.php` - Added fix route for individual Sales Orders
3. `app/Console/Commands/FixSalesOrderApproval.php` - Command to fix single or all Sales Orders

## Deployment Steps

### 1. Deploy Code Changes
```bash
# Pull latest code
git pull origin main

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 2. Ensure User Roles Are Assigned
Before fixing Sales Orders, ensure users have the required roles:

**Option A: Via Database (Recommended for initial setup)**
```sql
-- Assign 'officer' role to superadmin (user_id = 1)
INSERT INTO user_roles (user_id, role_name, is_active, created_at, updated_at)
VALUES (1, 'officer', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE is_active = 1;

-- Assign roles to other users as needed
-- Replace {user_id} with actual user IDs
INSERT INTO user_roles (user_id, role_name, is_active, created_at, updated_at)
VALUES ({user_id}, 'officer', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE is_active = 1;
```

**Option B: Via UI**
- Navigate to Users management
- Assign appropriate roles (officer, supervisor, manager) to users who should approve Sales Orders

### 3. Fix Existing Sales Orders

**Option A: Fix All Sales Orders at Once (Recommended)**
```bash
php artisan sales-order:fix-approval --all
```

This will:
- Ensure superadmin has 'officer' role
- Find all Sales Orders with `approval_status = 'pending'` but no approval records
- Create missing approval workflow records for each

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

### 4. Verify the Fix

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
-- Verify users have required roles
SELECT u.id, u.username, u.name, ur.role_name
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
WHERE ur.role_name IN ('officer', 'supervisor', 'manager')
  AND ur.is_active = 1
ORDER BY ur.role_name, u.username;
```

### 5. Post-Deployment Monitoring

After deployment, monitor:
- Sales Order approval success rate
- Any errors in logs related to approval workflows
- User feedback on approval process

**Check Logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "approval"
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

## Notes

- The fix route (`/sales-orders/fix-approval/{orderNo}`) is kept for future use
- The auto-fix logic in `SalesService::approveSalesOrder()` will handle missing approvals automatically going forward
- No database migrations are required - only code changes
- The fix is backward compatible and safe to deploy

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify user roles are correctly assigned
3. Verify approval thresholds exist: `SELECT * FROM approval_thresholds WHERE document_type = 'sales_order'`
4. Verify approval workflows exist: `SELECT * FROM approval_workflows WHERE document_type = 'sales_order' AND is_active = 1`
