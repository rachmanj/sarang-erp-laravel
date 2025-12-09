# Production Deployment Guide - Inventory Category Migration

**Purpose**: Step-by-step guide for deploying inventory category changes to production  
**Date**: 2025-01-XX  
**Changes**: Update inventory categories from 5 to 11 categories with new Chart of Accounts structure

---

## Pre-Deployment Checklist

### 1. Backup Database ⚠️ CRITICAL
```bash
# Create a full database backup before proceeding
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# Or using Laravel backup package (if installed)
php artisan backup:run
```

### 2. Verify Current State
Check if any inventory items are using categories that will be deleted:
```sql
SELECT pc.code, pc.name, COUNT(ii.id) as item_count 
FROM product_categories pc 
LEFT JOIN inventory_items ii ON pc.id = ii.category_id 
WHERE pc.code IN ('FURNITURE', 'VEHICLES', 'SERVICES') 
GROUP BY pc.id, pc.code, pc.name;
```

**⚠️ IMPORTANT**: If any items exist with FURNITURE, VEHICLES, or SERVICES categories, you MUST reassign them to new categories BEFORE running the migration.

### 3. Maintenance Window Recommendation
- **Recommended**: Schedule during low-traffic hours
- **Estimated Downtime**: 5-10 minutes
- **User Notification**: Inform users about brief maintenance window

---

## Deployment Steps

### Step 1: Pull Latest Code
```bash
cd /path/to/sarang-erp
git pull origin main  # or your production branch name
```

### Step 2: Install Dependencies (if needed)
```bash
composer install --no-dev --optimize-autoloader
npm install --production  # if frontend assets changed
npm run build  # if frontend assets changed
```

### Step 3: Run Database Seeders

#### 3.1 Update Chart of Accounts
```bash
php artisan db:seed --class=TradingCoASeeder
```

**Expected Output**:
- Updates existing accounts (Stationery, Electronics)
- Creates new inventory accounts (01.03-01.11)
- Creates new COGS accounts (5.1.03-5.1.11)
- Creates new Sales accounts (4.1.1.03-4.1.1.11)

#### 3.2 Update Product Categories
```bash
php artisan db:seed --class=ProductCategoryAccountSeeder
```

**Expected Output**:
- Deletes obsolete categories: FURNITURE, VEHICLES, SERVICES
- Updates ELECTRONICS → Electronics
- Creates 8 new categories
- Maps all categories to their accounts

### Step 4: Clear Application Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 5: Optimize Application (Optional but Recommended)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Post-Deployment Verification

### 1. Verify Categories
```sql
SELECT code, name, is_active 
FROM product_categories 
WHERE is_active = 1 
ORDER BY code;
```

**Expected**: 11 active categories
- STATIONERY
- ELECTRONICS
- WELDING
- ELECTRICAL
- OTOMOTIF
- LIFTING_TOOLS
- CONSUMABLES
- CHEMICAL
- BOLT_NUT
- SAFETY
- TOOLS

### 2. Verify Account Mappings
```sql
SELECT 
    pc.code as category_code,
    pc.name as category_name,
    inv.code as inventory_account,
    cogs.code as cogs_account,
    sales.code as sales_account
FROM product_categories pc
LEFT JOIN accounts inv ON pc.inventory_account_id = inv.id
LEFT JOIN accounts cogs ON pc.cogs_account_id = cogs.id
LEFT JOIN accounts sales ON pc.sales_account_id = sales.id
WHERE pc.is_active = 1
ORDER BY pc.code;
```

**Expected**: All 11 categories should have all 3 accounts mapped.

### 3. Verify Account Structure
```sql
-- Verify Inventory Accounts
SELECT code, name FROM accounts 
WHERE code LIKE '1.1.3.01.%' 
ORDER BY code;

-- Verify COGS Accounts
SELECT code, name FROM accounts 
WHERE code LIKE '5.1.%' 
AND code NOT LIKE '5.1.%_%'  -- Exclude sub-accounts
ORDER BY code;

-- Verify Sales Accounts
SELECT code, name FROM accounts 
WHERE code LIKE '4.1.1.%' 
ORDER BY code;
```

### 4. Test User Interface

1. **Login to Admin Panel**
2. **Navigate to**: `Inventory > Product Categories`
   - Verify all 11 categories are listed
   - Verify account mappings are correct
3. **Navigate to**: `Inventory > Create Item`
   - Verify category dropdown shows all 11 categories
   - Try selecting each category
4. **Test Purchase Order Creation**
   - Verify category selection works in PO items
5. **Test Sales Order Creation**
   - Verify category selection works in SO items

### 5. Verify Reports
- Check inventory valuation reports
- Check category-based analytics
- Verify financial reports include new accounts

---

## Rollback Procedure

If issues occur, follow these steps:

### 1. Restore Database Backup
```bash
mysql -u [username] -p [database_name] < backup_[timestamp].sql
```

### 2. Revert Code Changes
```bash
git checkout [previous_commit_hash]
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
```

### 3. Verify Rollback
- Check categories are back to original state
- Test critical workflows
- Verify account mappings

---

## Potential Issues and Solutions

### Issue 1: Foreign Key Constraint Errors
**Symptom**: Error when deleting FURNITURE, VEHICLES, or SERVICES categories

**Solution**: 
1. Reassign any inventory items from these categories:
```sql
-- Find items using old categories
SELECT ii.id, ii.code, ii.name, pc.name as category
FROM inventory_items ii
JOIN product_categories pc ON ii.category_id = pc.id
WHERE pc.code IN ('FURNITURE', 'VEHICLES', 'SERVICES');

-- Reassign to appropriate new category (example: Tools)
UPDATE inventory_items 
SET category_id = (SELECT id FROM product_categories WHERE code = 'TOOLS')
WHERE category_id IN (SELECT id FROM product_categories WHERE code IN ('FURNITURE', 'VEHICLES', 'SERVICES'));
```

### Issue 2: Account Already Exists Errors
**Symptom**: Seeder fails with "Account already exists"

**Solution**: 
- The updated seeder handles this automatically (checks for existing accounts)
- If issues persist, manually verify account codes don't conflict

### Issue 3: Missing Account Mappings
**Symptom**: Categories created but accounts not mapped

**Solution**:
```bash
# Re-run only the category seeder
php artisan db:seed --class=ProductCategoryAccountSeeder
```

### Issue 4: UI Shows Old Categories
**Symptom**: Category dropdowns still show deleted categories

**Solution**:
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## Communication Template

### Pre-Deployment Notification
```
Subject: Scheduled Maintenance - Inventory Category Update

Dear Users,

We will be performing a scheduled update to our inventory category system 
on [DATE] at [TIME].

Duration: 5-10 minutes
Impact: Temporary unavailability of inventory-related features

After the update, you will have access to 11 product categories:
- Stationery, Electronics, Welding, Electrical, Otomotif, 
  Lifting Tools, Consumables, Chemical, Bolt Nut, Safety, Tools

Thank you for your understanding.
```

### Post-Deployment Notification
```
Subject: Maintenance Complete - New Categories Available

The inventory category update has been completed successfully.

New features:
✅ 11 product categories now available
✅ Updated account mappings for accurate financial reporting

All systems are operational. If you experience any issues, 
please contact IT support.
```

---

## Support Contacts

- **Technical Issues**: [Your contact]
- **Data Issues**: [Your contact]
- **Emergency Rollback**: [Your contact]

---

## Change Log

| Date | Version | Changes |
|------|---------|---------|
| 2025-01-XX | 1.0 | Initial deployment guide created |

---

**Last Updated**: [Current Date]  
**Prepared By**: AI Assistant  
**Reviewed By**: [Your Name]

