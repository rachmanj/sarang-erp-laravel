# Inventory Module User Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Features Overview](#features-overview)
4. [Creating Inventory Items](#creating-inventory-items)
5. [Viewing and Searching Items](#viewing-and-searching-items)
6. [Editing Inventory Items](#editing-inventory-items)
7. [Stock Management](#stock-management)
8. [Reports and Analytics](#reports-and-analytics)
9. [Unit Management](#unit-management)
10. [Price Levels](#price-levels)
11. [Common Tasks](#common-tasks)
12. [Troubleshooting](#troubleshooting)

---

## Introduction

### What is the Inventory Module?

The Inventory Module is a comprehensive system that helps you manage all your company's products and services. It tracks:

- **What items you have** (products and services)
- **How many you have** (stock quantities)
- **Where they are** (warehouses and locations)
- **What they cost** (purchase and selling prices)
- **How much they're worth** (inventory valuation)

### Who Should Use This Module?

- **Warehouse Staff**: Manage stock levels and process stock movements
- **Purchasing Team**: Set up new items and track inventory
- **Sales Team**: Check item availability and prices
- **Managers**: Monitor inventory value and stock levels
- **Accountants**: Track inventory costs and valuations

---

## Getting Started

### Accessing the Inventory Module

1. Log in to the ERP system
2. From the main menu, click on **"Inventory"**
3. You will see the Inventory Management page

### Understanding the Main Screen

When you open the Inventory module, you'll see:

- **Add Item** button: Create new inventory items
- **Low Stock** button: View items that need reordering
- **Valuation Report** button: See total inventory value
- **Search box**: Find items quickly
- **Filter options**: Filter by category, valuation method, or stock status
- **Item list table**: Shows all your inventory items

---

## Features Overview

The Inventory Module includes these main features:

### 1. **Item Management**
- Create, edit, and delete inventory items
- Organize items by categories
- Set up items as physical products or services
- Define units of measure (pieces, boxes, kilograms, etc.)

### 2. **Stock Tracking**
- Real-time stock level monitoring
- Track stock movements (purchases, sales, adjustments, transfers)
- Set minimum and maximum stock levels
- Automatic low stock alerts

### 3. **Valuation Methods**
- **FIFO** (First In, First Out): Oldest stock is sold first
- **LIFO** (Last In, First Out): Newest stock is sold first
- **Weighted Average**: Average cost of all stock

### 4. **Price Management**
- Set purchase prices (what you pay)
- Set selling prices (what you charge)
- Multiple price levels (Level 1, 2, 3) for different customers
- Customer-specific pricing

### 5. **Reports and Analytics**
- Low stock reports
- Inventory valuation reports
- Stock movement history
- Export data to Excel/CSV

### 6. **Unit Conversion**
- Support for multiple units per item (e.g., boxes and pieces)
- Automatic conversion between units
- Different prices for different units

---

## Creating Inventory Items

### Step-by-Step Guide

#### Step 1: Open the Create Item Form

1. From the Inventory page, click the **"Add Item"** button (usually in the top-left corner)
2. A form will open for entering item details

#### Step 2: Fill in Basic Information

**Required Fields** (marked with *):

1. **Item Code** *
   - Enter a unique code for this item (e.g., "CHR-001")
   - This code is used to identify the item quickly
   - Example: "OFF-CHAIR-001" for office chair model 1

2. **Item Name** *
   - Enter a descriptive name (e.g., "Premium Office Chair - Model A")
   - Make it clear and easy to understand

3. **Description** (Optional)
   - Add any additional details about the item
   - Example: "Ergonomic office chair with adjustable height"

4. **Category** *
   - Select the product category from the dropdown
   - Categories help organize your inventory
   - Example: "Office Furniture", "Electronics", "Office Supplies"

5. **Item Type** *
   - Choose **"Item"** for physical products (affects stock)
   - Choose **"Service"** for services (doesn't affect stock)
   - Example: A chair is an "Item", consulting is a "Service"

6. **Base Unit** *
   - Select the unit of measure (e.g., Pieces, Boxes, Kilograms)
   - This is the primary unit for this item
   - Example: "PCS" for pieces, "BOX" for boxes

#### Step 3: Set Up Pricing

1. **Purchase Price** (Optional)
   - Enter the cost you pay to buy this item
   - Example: 2,500,000 (in your currency)

2. **Selling Price** *
   - Enter the price you sell this item for
   - This is the base selling price (Price Level 1)
   - Example: 3,500,000

3. **Price Level 2** (Optional)
   - Enter a different price for certain customers
   - Or set a percentage increase/decrease from base price

4. **Price Level 3** (Optional)
   - Enter another price level option
   - Useful for different customer tiers

#### Step 4: Configure Stock Levels (For Physical Items Only)

If you selected "Item" type, you'll see stock level fields:

1. **Minimum Stock Level** *
   - The lowest quantity you want to keep
   - System will alert you when stock goes below this
   - Example: 10 units

2. **Maximum Stock Level** *
   - The highest quantity you want to store
   - Helps prevent overstocking
   - Example: 100 units

3. **Reorder Point** *
   - The quantity at which you should order more
   - Usually set between minimum and maximum
   - Example: 20 units

#### Step 5: Set Valuation Method

1. **Valuation Method** *
   - **FIFO**: First items purchased are sold first (recommended for most businesses)
   - **LIFO**: Last items purchased are sold first
   - **Weighted Average**: Average cost of all items

   **Which one to choose?**
   - **FIFO**: Best for most businesses, matches physical flow
   - **LIFO**: Used in some countries for tax purposes
   - **Weighted Average**: Simplest, good for similar-cost items

#### Step 6: Additional Settings

1. **Default Warehouse** (Optional)
   - Select the warehouse where this item is usually stored
   - You can change this later if needed

2. **Initial Stock** (Optional)
   - If you're adding an item that already exists in your warehouse
   - Enter the current quantity you have
   - Enter the unit cost for this initial stock

3. **Active Status**
   - Check the box to make the item active (available for use)
   - Uncheck to deactivate (hide from normal use)

#### Step 7: Save the Item

1. Review all the information you entered
2. Click the **"Save"** or **"Create Item"** button
3. You'll see a success message if the item was created
4. You'll be redirected to the item detail page

### Tips for Creating Items

- **Use consistent naming**: "Office Chair - Model A" is better than "chair1"
- **Create categories first**: Set up categories before creating many items
- **Start with basic info**: You can add more details later
- **Double-check codes**: Item codes must be unique

---

## Viewing and Searching Items

### Viewing All Items

1. Go to **Inventory** from the main menu
2. You'll see a table listing all inventory items
3. The table shows:
   - Item Code
   - Item Name
   - Category
   - Unit of Measure
   - Purchase Price
   - Selling Price
   - Current Stock
   - Minimum Stock Level
   - Status (Active/Inactive)

### Searching for Items

**Quick Search:**
1. Use the search box at the top
2. Type the item code or name
3. Press Enter or click the search icon
4. Results will filter automatically

**Advanced Search:**
1. Use the filter dropdowns:
   - **Category**: Filter by product category
   - **Valuation Method**: Filter by FIFO, LIFO, or Weighted Average
   - **Stock Status**: Filter by Low Stock, Out of Stock, or In Stock
2. Click **"Filter"** button to apply filters
3. Click **"Reset"** or clear filters to see all items again

### Viewing Item Details

1. Find the item in the list
2. Click on the item name or the **"View"** button (eye icon)
3. You'll see detailed information:
   - All item information
   - Stock levels by warehouse
   - Transaction history
   - Valuation history
   - Audit trail (who changed what and when)

### Understanding Stock Status Indicators

In the item list, you'll see colored badges:

- **Green "OK"**: Stock is above minimum level
- **Yellow "Low"**: Stock is at or below minimum level
- **Red "Out"**: Stock is zero or negative

---

## Editing Inventory Items

### When to Edit Items

You may need to edit items when:
- Prices change
- Stock levels need adjustment
- Item details need updating
- Item becomes inactive

### How to Edit an Item

#### Step 1: Find the Item

1. Go to the Inventory list
2. Search or filter to find the item you want to edit

#### Step 2: Open Edit Form

1. Click the **"Edit"** button (pencil icon) next to the item
2. Or click on the item name, then click **"Edit"** from the detail page

#### Step 3: Make Changes

1. Update any fields you need to change
2. Most fields can be edited:
   - Item name and description
   - Prices
   - Stock levels
   - Category
   - Valuation method
   - Active status

**Note:** Item Code usually cannot be changed after creation to maintain data integrity.

#### Step 4: Save Changes

1. Review your changes
2. Click **"Save"** or **"Update"** button
3. You'll see a success message
4. Changes are saved and logged in the audit trail

### Important Notes

- **Price changes**: Will affect future sales, not past transactions
- **Stock level changes**: Only change the alert thresholds, not actual stock
- **Valuation method changes**: Will affect future cost calculations
- **Deactivating items**: Hides them from normal use but keeps history

---

## Stock Management

### Understanding Stock Movements

Stock movements are changes to your inventory quantity. There are four main types:

1. **Purchase**: Stock increases (goods received)
2. **Sale**: Stock decreases (goods sold)
3. **Adjustment**: Manual correction of stock levels
4. **Transfer**: Moving stock between items or warehouses

### Stock Adjustments

Stock adjustments are used to correct inventory discrepancies found during:
- Physical stock counts
- Cycle counting
- Damage or loss
- Found items

#### How to Adjust Stock

**Step 1: Access Adjustment**

1. Go to the Inventory list
2. Find the item you want to adjust
3. Click the **"Adjust Stock"** button (usually a +/- icon)

**Step 2: Enter Adjustment Details**

1. **Adjustment Type**:
   - **Increase Stock**: Add items (found items, corrections)
   - **Decrease Stock**: Remove items (damage, loss, corrections)

2. **Quantity**: Enter how many units to adjust
   - Example: If physical count shows 28 but system shows 30, decrease by 2

3. **Unit Cost**: Enter the cost per unit
   - This affects inventory valuation
   - Use the current average cost if unsure

4. **Notes**: Explain why you're adjusting
   - Example: "Cycle count discrepancy - found 2 units missing"

**Step 3: Submit Adjustment**

1. Review the information
2. Click **"Adjust Stock"** or **"Submit"**
3. Stock level will update immediately
4. A transaction record is created

### Stock Transfers

Stock transfers move inventory from one item to another. This is less common but useful for:
- Combining similar items
- Splitting items
- Converting between item codes

#### How to Transfer Stock

**Step 1: Access Transfer**

1. Find the source item (where stock is coming from)
2. Click the **"Transfer Stock"** button

**Step 2: Enter Transfer Details**

1. **Transfer To**: Select the destination item from the dropdown
2. **Quantity**: Enter how many units to transfer
3. **Unit Cost**: Enter the cost per unit
4. **Notes**: Add any relevant information

**Step 3: Submit Transfer**

1. Review the information
2. Click **"Transfer Stock"**
3. Stock decreases from source item
4. Stock increases in destination item
5. Both transactions are recorded

### Viewing Stock History

1. Go to an item's detail page
2. Click on the **"Transactions"** tab
3. You'll see all stock movements:
   - Date and time
   - Transaction type
   - Quantity change
   - Cost
   - Reference document (if any)
   - Who created it

---

## Reports and Analytics

### Low Stock Report

This report shows items that need to be reordered.

#### How to View Low Stock Report

1. From the Inventory page, click **"Low Stock"** button
2. You'll see a list of items where:
   - Current stock ≤ Reorder Point
   - Items are sorted by urgency

#### Understanding the Report

- **Item Code/Name**: Which item needs attention
- **Current Stock**: How many you have now
- **Reorder Point**: The level that triggered the alert
- **Minimum Stock**: The lowest acceptable level
- **Category**: Item category for grouping

#### What to Do

1. Review each item
2. Check if reorder is needed
3. Create purchase orders for items that need restocking
4. Adjust reorder points if they're set incorrectly

### Valuation Report

This report shows the total value of your inventory.

#### How to View Valuation Report

1. From the Inventory page, click **"Valuation Report"** button
2. You'll see inventory value by:
   - Individual items
   - Categories
   - Total inventory value

#### Understanding the Report

- **Item**: Item code and name
- **Quantity on Hand**: Current stock
- **Unit Cost**: Average cost per unit
- **Total Value**: Quantity × Unit Cost
- **Valuation Method**: How cost is calculated

#### Using the Report

- **Financial reporting**: Total inventory value for balance sheet
- **Category analysis**: See which categories hold most value
- **Cost analysis**: Identify high-value items
- **Planning**: Understand inventory investment

### Exporting Data

You can export inventory data to Excel or CSV format.

#### How to Export

1. From the Inventory list page
2. Click the **"Export"** button
3. Choose export format (if available)
4. File will download to your computer

#### What Gets Exported

- All visible items (respects current filters)
- Item codes, names, categories
- Prices and stock levels
- Current stock quantities

---

## Unit Management

### Understanding Units

Some items can be sold in different units. For example:
- A box of pens (1 box = 12 pieces)
- A carton of paper (1 carton = 10 reams)
- A pallet of goods (1 pallet = 50 boxes)

The system supports multiple units per item with automatic conversion.

### Managing Units for an Item

#### Step 1: Access Unit Management

1. Go to an item's detail page
2. Look for **"Units"** or **"Manage Units"** section or button
3. Click to open unit management

#### Step 2: View Current Units

You'll see:
- **Base Unit**: The primary unit (usually the smallest)
- **Other Units**: Additional units with conversion rates
- **Prices**: Selling price for each unit

#### Step 3: Add a New Unit

1. Click **"Add Unit"** button
2. Select the unit from the dropdown (e.g., "BOX", "CARTON")
3. Enter **Conversion Quantity**:
   - How many base units = 1 of this unit
   - Example: 1 BOX = 12 PCS, so enter 12
4. Enter **Selling Price** for this unit
5. Optionally set price levels 2 and 3
6. Click **"Save"**

#### Step 4: Edit Unit Prices

1. Find the unit in the list
2. Click **"Edit"** button
3. Update prices
4. Click **"Save"**

#### Step 5: Set Base Unit

1. Only one unit can be the base unit
2. To change base unit:
   - Find the unit you want to make base
   - Click **"Set as Base Unit"**
   - System will automatically convert other units

#### Step 6: Remove a Unit

1. Find the unit you want to remove
2. Click **"Remove"** button
3. Confirm removal
4. **Note**: You cannot remove the last unit or the base unit if other units exist

### Unit Conversion Examples

**Example 1: Pens**
- Base Unit: PCS (pieces)
- Additional Unit: BOX
- Conversion: 1 BOX = 12 PCS
- If you have 5 boxes, system shows: 60 PCS

**Example 2: Paper**
- Base Unit: REAM
- Additional Unit: CARTON
- Conversion: 1 CARTON = 10 REAMS
- If you sell 2 cartons, system records: 20 REAMS

---

## Price Levels

### Understanding Price Levels

Price levels allow you to charge different prices to different customers:
- **Level 1**: Standard price (base selling price)
- **Level 2**: Discounted or premium price for certain customers
- **Level 3**: Another price tier for special customers

### Setting Up Price Levels

#### At Item Level

When creating or editing an item:

1. **Selling Price**: This is Level 1 (base price)
2. **Price Level 2**: 
   - Enter a fixed price, OR
   - Enter a percentage (e.g., +10% or -5%)
3. **Price Level 3**: Same options as Level 2

**Example:**
- Base Price (Level 1): 100,000
- Level 2: +10% = 110,000
- Level 3: -5% = 95,000

#### Customer-Specific Pricing

You can set custom prices for specific customers:

1. Go to the item detail page
2. Look for **"Customer Prices"** or **"Price Levels"** section
3. Click **"Set Customer Price"**
4. Select the customer
5. Choose price level (1, 2, or 3)
6. Optionally enter a custom price
7. Click **"Save"**

### Viewing Price Level Summary

1. Go to an item's detail page
2. Click **"Price Level Summary"** (if available)
3. You'll see:
   - Base prices for each level
   - Which customers use which level
   - Custom prices set for specific customers

---

## Common Tasks

### Task 1: Adding a New Product to Inventory

**Scenario**: You received a new product from a supplier and need to add it to the system.

**Steps**:
1. Go to Inventory → Click "Add Item"
2. Enter item code, name, and description
3. Select category
4. Choose "Item" type
5. Set base unit (e.g., PCS)
6. Enter purchase price and selling price
7. Set minimum stock (10), maximum stock (100), reorder point (20)
8. Choose valuation method (FIFO recommended)
9. If you have initial stock, enter quantity and cost
10. Click "Save"

### Task 2: Processing a Physical Stock Count

**Scenario**: Monthly cycle count shows 28 units, but system shows 30 units.

**Steps**:
1. Go to Inventory → Find the item
2. Click "Adjust Stock"
3. Select "Decrease Stock"
4. Enter quantity: 2
5. Enter unit cost (use current average)
6. Add note: "Monthly cycle count - 2 units missing"
7. Click "Adjust Stock"
8. Verify new stock level is 28

### Task 3: Checking Low Stock Items

**Scenario**: You want to see which items need reordering.

**Steps**:
1. Go to Inventory
2. Click "Low Stock" button
3. Review the list
4. For each item, decide:
   - Create purchase order?
   - Adjust reorder point?
   - No action needed?
5. Take appropriate action

### Task 4: Updating Item Prices

**Scenario**: Supplier increased costs, so you need to update selling prices.

**Steps**:
1. Go to Inventory → Find the item
2. Click "Edit"
3. Update "Purchase Price" if changed
4. Update "Selling Price" to maintain margin
5. Update Price Level 2 and 3 if needed
6. Click "Save"
7. **Note**: This affects future sales only, not past transactions

### Task 5: Viewing Inventory Value

**Scenario**: Month-end - need to know total inventory value for financial reporting.

**Steps**:
1. Go to Inventory
2. Click "Valuation Report"
3. Review total inventory value
4. Check values by category if needed
5. Export to Excel if needed for reporting
6. Use the total value for financial statements

### Task 6: Setting Up Multiple Units

**Scenario**: You sell pens individually (PCS) and in boxes (1 box = 12 pieces).

**Steps**:
1. Create item with base unit: PCS
2. Go to item detail → Units section
3. Click "Add Unit"
4. Select unit: BOX
5. Enter conversion: 12 (1 box = 12 pieces)
6. Enter selling price for box (e.g., if 1 PCS = 1,000, 1 BOX might be 11,000)
7. Click "Save"
8. Now you can sell in both PCS and BOX units

### Task 7: Deactivating an Item

**Scenario**: You no longer sell a product but want to keep its history.

**Steps**:
1. Go to Inventory → Find the item
2. Click "Edit"
3. Uncheck "Active" checkbox
4. Click "Save"
5. Item is hidden from normal use but history is preserved
6. To reactivate later, edit and check "Active" again

---

## Troubleshooting

### Problem: Can't Find an Item

**Possible Causes**:
- Item is inactive
- Wrong search term
- Filters are applied

**Solutions**:
1. Clear all filters
2. Check if searching by code or name
3. Try partial search (e.g., "chair" instead of "office chair")
4. Check if item is marked as inactive

### Problem: Stock Level Seems Wrong

**Possible Causes**:
- Transactions not processed
- Adjustment needed
- System calculation error

**Solutions**:
1. Check transaction history for the item
2. Verify all purchases and sales are recorded
3. Perform physical count and adjust if needed
4. Contact system administrator if issue persists

### Problem: Can't Edit Item Code

**This is Normal**: Item codes cannot be changed after creation to maintain data integrity.

**Solution**:
- If code is wrong, create a new item with correct code
- Deactivate the old item
- Transfer any remaining stock if needed

### Problem: Price Level Not Working

**Possible Causes**:
- Price level not set for customer
- Customer not assigned to price level
- Custom price not configured

**Solutions**:
1. Check item's price level settings
2. Verify customer has price level assigned
3. Check for customer-specific custom prices
4. Ensure price level is active

### Problem: Unit Conversion Not Working

**Possible Causes**:
- Unit not added to item
- Wrong conversion rate
- Base unit not set

**Solutions**:
1. Verify unit is added in unit management
2. Check conversion quantity is correct
3. Ensure base unit is properly set
4. Test conversion with simple numbers

### Problem: Low Stock Alert Not Showing

**Possible Causes**:
- Reorder point not set
- Stock above reorder point
- Item is inactive

**Solutions**:
1. Check reorder point is set (not zero)
2. Verify current stock is actually below reorder point
3. Ensure item is active
4. Manually check low stock report

### Problem: Valuation Seems Incorrect

**Possible Causes**:
- Wrong valuation method
- Incorrect cost entered in transactions
- Calculation timing issue

**Solutions**:
1. Verify valuation method (FIFO/LIFO/Weighted Average)
2. Check transaction costs are correct
3. Review valuation history
4. Contact administrator if calculation seems wrong

### Problem: Can't Delete an Item

**Possible Causes**:
- Item has transaction history
- Item is referenced in other modules

**Solutions**:
- Items with transactions cannot be deleted (by design)
- Deactivate the item instead
- Contact administrator if deletion is absolutely necessary

---

## Quick Reference

### Keyboard Shortcuts

- **Ctrl + F**: Search (in most browsers)
- **Enter**: Submit forms
- **Esc**: Close modals

### Important Terms

- **FIFO**: First In, First Out - oldest stock sold first
- **LIFO**: Last In, First Out - newest stock sold first
- **Weighted Average**: Average cost of all stock
- **Reorder Point**: Stock level that triggers reorder alert
- **Valuation**: Total value of inventory
- **Base Unit**: Primary unit of measure for an item

### Common Item Types

- **Item**: Physical product that affects stock
- **Service**: Non-physical service that doesn't affect stock

### Stock Status Colors

- 🟢 **Green**: Stock is healthy (above minimum)
- 🟡 **Yellow**: Stock is low (at or below minimum)
- 🔴 **Red**: Stock is out (zero or negative)

---

## Getting Help

If you need additional assistance:

1. **Check this manual** first for common tasks
2. **Contact your system administrator** for technical issues
3. **Review training materials** if available
4. **Check the audit trail** to see what changed and when

---

## Best Practices

### When Creating Items

- ✅ Use clear, consistent naming conventions
- ✅ Set up categories before creating many items
- ✅ Enter accurate prices from the start
- ✅ Set realistic stock levels
- ✅ Choose appropriate valuation method

### When Managing Stock

- ✅ Perform regular cycle counts
- ✅ Adjust stock immediately when discrepancies found
- ✅ Document all adjustments with clear notes
- ✅ Review low stock reports regularly
- ✅ Keep transaction history clean

### When Setting Prices

- ✅ Update prices when costs change
- ✅ Maintain consistent pricing strategy
- ✅ Review price levels periodically
- ✅ Document price change reasons

### General Tips

- ✅ Always verify information before saving
- ✅ Use notes to document important changes
- ✅ Review reports regularly
- ✅ Keep item information up to date
- ✅ Deactivate instead of deleting when possible

---

**End of Manual**

*This manual covers the basic features of the Inventory Module. For advanced features or specific business processes, consult with your system administrator or refer to additional documentation.*

