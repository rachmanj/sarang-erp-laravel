#!/bin/bash

# Production Deployment Script - Inventory Category Migration
# Usage: bash deploy-category-migration.sh

set -e  # Exit on error

echo "========================================="
echo "Inventory Category Migration Deployment"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Backup reminder
echo -e "${YELLOW}⚠️  IMPORTANT: Ensure you have backed up your database before proceeding!${NC}"
read -p "Have you created a database backup? (yes/no): " backup_confirm

if [ "$backup_confirm" != "yes" ]; then
    echo -e "${RED}Please create a database backup first!${NC}"
    exit 1
fi

# Step 2: Pull latest code
echo ""
echo -e "${GREEN}Step 1: Pulling latest code...${NC}"
git pull origin main || git pull origin master
echo -e "${GREEN}✓ Code updated${NC}"

# Step 3: Install dependencies
echo ""
echo -e "${GREEN}Step 2: Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✓ Dependencies installed${NC}"

# Step 4: Run database seeders
echo ""
echo -e "${GREEN}Step 3: Running Chart of Accounts seeder...${NC}"
php artisan db:seed --class=TradingCoASeeder
echo -e "${GREEN}✓ Chart of Accounts updated${NC}"

echo ""
echo -e "${GREEN}Step 4: Running Product Category seeder...${NC}"
php artisan db:seed --class=ProductCategoryAccountSeeder
echo -e "${GREEN}✓ Product Categories updated${NC}"

# Step 5: Clear cache
echo ""
echo -e "${GREEN}Step 5: Clearing application cache...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✓ Cache cleared${NC}"

# Step 6: Optimize
echo ""
echo -e "${GREEN}Step 6: Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Application optimized${NC}"

# Step 7: Verification
echo ""
echo -e "${GREEN}Step 7: Verifying deployment...${NC}"
CATEGORY_COUNT=$(php artisan tinker --execute="echo \App\Models\ProductCategory::where('is_active', 1)->count();" 2>/dev/null || echo "0")

if [ "$CATEGORY_COUNT" -eq 11 ]; then
    echo -e "${GREEN}✓ Verification passed: 11 categories found${NC}"
else
    echo -e "${YELLOW}⚠ Warning: Expected 11 categories, found $CATEGORY_COUNT${NC}"
    echo "Please verify manually in the database"
fi

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Test the UI by logging in and checking Product Categories"
echo "2. Verify inventory item creation works with new categories"
echo "3. Check reports and analytics"
echo ""
echo "For detailed verification steps, see: docs/deployment-category-migration.md"

