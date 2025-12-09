# Quick Deployment Steps - Production Server

## After `git pull`, run these commands in order:

```bash
# 1. Install dependencies (if composer.json changed)
composer install --no-dev --optimize-autoloader

# 2. Update Chart of Accounts
php artisan db:seed --class=TradingCoASeeder

# 3. Update Product Categories (creates new, deletes old)
php artisan db:seed --class=ProductCategoryAccountSeeder

# 4. Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 5. Rebuild caches (recommended for production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Quick Verification

```bash
# Check category count (should be 11)
php artisan tinker --execute="echo \App\Models\ProductCategory::where('is_active', 1)->count();"
```

Or check in database:
```sql
SELECT COUNT(*) FROM product_categories WHERE is_active = 1;
-- Should return: 11
```

## ⚠️ IMPORTANT REMINDERS

1. **BACKUP FIRST**: Always backup your database before running seeders
2. **Check for items**: Verify no inventory items use old categories (FURNITURE, VEHICLES, SERVICES)
3. **Test after deployment**: Login and verify categories appear correctly in UI

## Full Documentation

See `docs/deployment-category-migration.md` for detailed instructions, troubleshooting, and rollback procedures.

