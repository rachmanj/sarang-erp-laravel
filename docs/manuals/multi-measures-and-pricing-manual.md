# Multi-Measures and Multi-Price Features User Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Understanding Units of Measure](#understanding-units-of-measure)
4. [Managing Units of Measure](#managing-units-of-measure)
5. [Setting Up Multiple Units for Items](#setting-up-multiple-units-for-items)
6. [Understanding Price Levels](#understanding-price-levels)
7. [Setting Up Price Levels](#setting-up-price-levels)
8. [Customer-Specific Pricing](#customer-specific-pricing)
9. [Using Multi-Measures in Orders](#using-multi-measures-in-orders)
10. [Common Scenarios](#common-scenarios)
11. [Troubleshooting](#troubleshooting)
12. [Quick Reference](#quick-reference)

---

## Introduction

### What are Multi-Measures and Multi-Price Features?

The Sarang-ERP system includes two powerful features that help you manage your inventory and pricing more effectively:

1. **Multi-Measures**: Allows you to define multiple units of measure for the same item (e.g., "Each", "Box", "Carton") with automatic conversion between units.

2. **Multi-Price**: Enables you to set different prices for the same item based on:
   - Price levels (Level 1, 2, 3 for different customer tiers)
   - Different units (different prices for "Each" vs "Box")
   - Specific customers (custom pricing for individual customers)

### Who Should Use These Features?

- **Inventory Managers**: Set up multiple units and conversion factors
- **Sales Managers**: Configure price levels and customer-specific pricing
- **Sales Staff**: Use different units and prices when creating orders
- **Purchasing Team**: Order items in different units (e.g., order by carton, receive by piece)
- **Accountants**: Track inventory in base units while allowing flexible pricing

### Key Benefits

- **Flexibility**: Sell items in different units (pieces, boxes, cartons) without manual calculations
- **Accurate Pricing**: Set different prices for different customer types automatically
- **Volume Discounts**: Offer better prices for larger quantities (boxes vs pieces)
- **Simplified Operations**: System automatically converts quantities and applies correct prices
- **Better Inventory Control**: Stock always tracked in base unit, regardless of how items are sold

---

## Getting Started

### Prerequisites

Before using these features, ensure:

1. You have permission to:
   - `view_unit_of_measure` - View units of measure
   - `create_unit_of_measure` - Create new units
   - `inventory.update` - Edit inventory items
   - `business_partners.update` - Set customer pricing

2. Basic units are already created (EA, KG, M, etc.)

3. Inventory items are set up in the system

### Access Points

- **Units of Measure Management**: `Master Data > Units of Measure`
- **Item Unit Setup**: `Inventory > Inventory Items > [Select Item] > Manage Units`
- **Price Level Setup**: `Inventory > Inventory Items > [Select Item] > Edit`
- **Customer Pricing**: `Business Partner > [Select Customer] > Edit`

---

## Understanding Units of Measure

### What is a Unit of Measure?

A unit of measure (UOM) is how you count or measure an item. Common examples:
- **Count units**: Each (EA), Piece (PC), Box (BOX), Carton (CTN)
- **Weight units**: Kilogram (KG), Gram (GR), Ton (TON)
- **Length units**: Meter (M), Centimeter (CM), Foot (FT)
- **Volume units**: Liter (L), Milliliter (ML), Gallon (GAL)

### Base Unit vs Alternative Units

Every inventory item has **one base unit** and can have **multiple alternative units**:

- **Base Unit**: The primary unit used for stock tracking
  - Example: "EA" (Each) - Stock is always tracked in pieces
  - This is the unit shown in inventory reports

- **Alternative Units**: Additional units you can use in orders
  - Example: "BOX" (12 EA), "CARTON" (144 EA)
  - These are converted to base unit for stock tracking

### Conversion Factors

When you define an alternative unit, you set a **conversion factor** that tells the system how many base units equal one alternative unit.

**Example:**
- Base Unit: EA (Each)
- Alternative Unit: BOX
- Conversion Factor: 12
- Meaning: 1 BOX = 12 EA

When someone orders 2 BOX, the system:
- Records the order as: 2 BOX
- Deducts stock as: 24 EA (2 × 12)

---

## Managing Units of Measure

### Viewing All Units

1. Navigate to `Master Data > Units of Measure`
2. You'll see all units grouped by type (Count, Weight, Length, etc.)
3. Each unit shows:
   - Code (e.g., EA, BOX)
   - Name (e.g., Each, Box)
   - Description
   - Whether it's a base unit
   - Status (Active/Inactive)
   - Number of conversions

### Creating a New Unit

1. Go to `Master Data > Units of Measure`
2. Click **"Add Unit"** button
3. Fill in the form:
   - **Unit Code**: Short code (e.g., BOX, CTN) - max 20 characters
   - **Unit Name**: Full name (e.g., Box, Carton) - max 100 characters
   - **Description**: Optional description
   - **Unit Type**: Select from Count, Weight, Length, Volume, Area, or Time
   - **Base Unit**: Check if this should be a base unit (usually leave unchecked)
4. Click **"Create Unit"**

**Example:**
- Code: BOX
- Name: Box
- Description: Standard shipping box
- Unit Type: Count
- Base Unit: No (unchecked)

### Editing a Unit

1. Go to `Master Data > Units of Measure`
2. Find the unit you want to edit
3. Click the **Edit** button (pencil icon)
4. Modify the fields (note: Unit Type cannot be changed after creation)
5. Click **"Update Unit"**

### Viewing Unit Details

1. Go to `Master Data > Units of Measure`
2. Click the **View** button (eye icon) for any unit
3. You'll see:
   - Unit information
   - Conversion relationships (if any)

---

## Setting Up Multiple Units for Items

### Adding Units to an Inventory Item

1. Go to `Inventory > Inventory Items`
2. Find and click on the item you want to configure
3. Click on **"Manage Units"** or navigate to the Units tab
4. Click **"Add Unit"**
5. Select the unit from the dropdown
6. Set the conversion factor:
   - If it's the base unit: Set to 1
   - If it's an alternative: Enter how many base units = 1 of this unit
7. Set prices for this unit (optional):
   - Level 1 Price
   - Level 2 Price
   - Level 3 Price
8. Click **"Save"**

### Example: Setting Up Box and Carton for an Item

**Item**: Lampu Sorot 1000 watt AC
**Base Unit**: EA (Each)

**Step 1: Add BOX unit**
- Unit: BOX
- Conversion Factor: 12 (1 BOX = 12 EA)
- Level 1 Price: 5,500,000 (optional - can be different from 12 × EA price)
- Mark as base unit: No

**Step 2: Add CARTON unit**
- Unit: CARTON
- Conversion Factor: 144 (1 CARTON = 144 EA = 12 BOX)
- Level 1 Price: 60,000,000 (optional)
- Mark as base unit: No

**Result:**
- Stock is tracked in EA (base unit)
- Orders can use EA, BOX, or CARTON
- System automatically converts quantities
- Each unit can have its own price

### Setting the Base Unit

- The first unit you add is automatically set as the base unit
- Only one unit per item can be the base unit
- The base unit cannot be deleted if the item has transactions
- To change the base unit, you must first remove all alternative units, then set a new base unit

### Removing Units

1. Go to the item's unit management page
2. Find the unit you want to remove
3. Click **"Remove"** or **"Delete"**
4. Confirm the action

**Note**: You cannot remove the base unit if:
- The item has any transactions
- It's the only unit for the item

---

## Understanding Price Levels

### What are Price Levels?

Price levels allow you to set different selling prices for the same item, typically used for different customer tiers:

- **Level 1**: Standard/Retail price (highest)
  - Used for regular customers
  - Default price level

- **Level 2**: Wholesale price (medium)
  - Used for wholesale customers
  - Usually 5-15% lower than Level 1

- **Level 3**: Distributor price (lowest)
  - Used for distributors or large volume customers
  - Usually 10-25% lower than Level 1

### How Price Levels Work

When creating a sales order:
1. System checks the customer's default price level
2. System looks up the item's price for that level
3. If the item uses a specific unit (e.g., BOX), system uses that unit's price for the level
4. Price is automatically applied to the order

### Price Resolution Priority

The system uses prices in this order (highest priority first):

1. **Customer-specific custom price** (if set for this customer-item combination)
2. **Customer-specific price level** (if set for this customer-item combination)
3. **Customer's default price level** (set in customer master data)
4. **Unit-specific price** (if order uses a specific unit like BOX)
5. **Item base price** (for the price level)
6. **Calculated price** (if percentage markup/discount is set)

---

## Setting Up Price Levels

### Setting Base Prices for an Item

1. Go to `Inventory > Inventory Items`
2. Find and click on the item
3. Click **"Edit"**
4. Scroll to the **Pricing** section
5. Set prices:
   - **Selling Price**: Level 1 price (base unit)
   - **Selling Price Level 2**: Wholesale price (base unit)
   - **Selling Price Level 3**: Distributor price (base unit)
6. Optionally set percentage calculations:
   - **Price Level 2 Percentage**: Auto-calculate Level 2 as percentage of Level 1
   - **Price Level 3 Percentage**: Auto-calculate Level 3 as percentage of Level 1
7. Click **"Update"**

### Example: Setting Price Levels

**Item**: Lampu Sorot 1000 watt AC
- Selling Price (Level 1): 500,000 per EA
- Selling Price Level 2: 450,000 per EA (10% discount)
- Selling Price Level 3: 400,000 per EA (20% discount)

**Or using percentages:**
- Selling Price (Level 1): 500,000 per EA
- Price Level 2 Percentage: -10% (automatically calculates to 450,000)
- Price Level 3 Percentage: -20% (automatically calculates to 400,000)

### Setting Unit-Specific Prices

When you add alternative units to an item, you can set different prices for each unit at each level:

1. Go to item's unit management page
2. Add or edit a unit
3. Set prices:
   - **Selling Price**: Level 1 price for this unit
   - **Selling Price Level 2**: Level 2 price for this unit
   - **Selling Price Level 3**: Level 3 price for this unit

**Example:**
- Base Unit (EA): Level 1 = 500,000
- Alternative Unit (BOX): Level 1 = 5,500,000 (not 6,000,000 - includes volume discount)

**Why different prices?**
- Volume discounts: BOX price might be less than 12 × EA price
- Packaging costs: BOX price might include packaging
- Marketing strategy: Encourage bulk purchases

---

## Customer-Specific Pricing

### Setting Customer Default Price Level

1. Go to `Business Partner > Business Partners`
2. Find and click on the customer
3. Click **"Edit"**
4. Find **"Default Sales Price Level"** field
5. Select: 1 (Standard), 2 (Wholesale), or 3 (Distributor)
6. Click **"Update"**

**Result**: All orders for this customer will use the selected price level by default.

### Setting Custom Price for Specific Item

You can override the standard pricing for a specific customer-item combination:

1. Go to `Business Partner > Business Partners`
2. Find and click on the customer
3. Navigate to **"Item Pricing"** or **"Special Pricing"** section
4. Click **"Add Item Price"** or **"Set Custom Price"**
5. Select the inventory item
6. Choose:
   - **Use Price Level**: Select level 1, 2, or 3
   - **Custom Price**: Enter a specific price (overrides all levels)
7. Click **"Save"**

**Example:**
- Customer: PT ABC (default level: 2)
- Item: Lampu Sorot
- Custom Price: 480,000 per EA
- Result: PT ABC always pays 480,000 for this item, regardless of level

---

## Using Multi-Measures in Orders

### Creating a Sales Order with Different Units

1. Go to `Sales > Sales Orders > Create New`
2. Select the customer
3. Add items:
   - Select inventory item
   - **Unit dropdown** will show available units (EA, BOX, CARTON)
   - Select the unit you want to use
   - Enter quantity in that unit
4. System automatically:
   - Shows the correct price for the selected unit and customer's price level
   - Calculates total
   - Converts quantity to base unit for stock checking
5. Complete the order

### Example: Order with Multiple Units

**Order for Customer: PT Wholesale (Level 2)**

| Item | Unit | Quantity | Unit Price | Total |
|------|------|----------|------------|-------|
| Lampu Sorot | BOX | 3 | 5,000,000 | 15,000,000 |
| Lampu Sorot | EA | 5 | 450,000 | 2,250,000 |

**What happens:**
- 3 BOX = 36 EA (stock deduction)
- 5 EA = 5 EA (stock deduction)
- Total stock deducted: 41 EA
- Prices used: BOX Level 2 price and EA Level 2 price

### Viewing Stock in Different Units

When viewing inventory:
- Stock is always shown in base unit (EA)
- But you can see equivalent quantities in other units
- Example: 144 EA = 12 BOX = 1 CARTON

---

## Common Scenarios

### Scenario 1: Setting Up a Product with Box and Carton

**Goal**: Sell item in EA, BOX (12 EA), and CARTON (144 EA)

**Steps:**
1. Create units: BOX and CARTON (if not exist)
2. Go to item management
3. Add BOX unit with conversion: 12
4. Add CARTON unit with conversion: 144
5. Set prices:
   - EA: 500,000 (Level 1)
   - BOX: 5,500,000 (Level 1) - volume discount
   - CARTON: 60,000,000 (Level 1) - larger discount

### Scenario 2: Wholesale Customer Setup

**Goal**: Give wholesale customers 15% discount

**Steps:**
1. Set item prices:
   - Level 1: 500,000
   - Level 2: 425,000 (or set -15% percentage)
2. Go to customer master data
3. Set customer's default price level to 2
4. All orders for this customer automatically use Level 2 prices

### Scenario 3: Special Price for VIP Customer

**Goal**: Give specific customer special price regardless of level

**Steps:**
1. Go to customer master data
2. Navigate to item pricing
3. Add custom price for the item: 450,000
4. This customer always gets this price, even if their level changes

### Scenario 4: Volume Discount with Units

**Goal**: Encourage bulk purchases with better box prices

**Steps:**
1. Set EA price: 500,000
2. Set BOX price: 5,400,000 (instead of 6,000,000)
3. This gives 10% discount when buying by box
4. System automatically applies correct price when customer selects BOX unit

---

## Troubleshooting

### Problem: Unit not showing in order dropdown

**Possible Causes:**
- Unit is not added to the item
- Unit is marked as inactive
- Item doesn't have the unit configured

**Solution:**
1. Go to item's unit management
2. Check if unit is added and active
3. Add the unit if missing

### Problem: Wrong price being used

**Possible Causes:**
- Customer's price level not set correctly
- Unit-specific price not configured
- Custom price override in place

**Solution:**
1. Check customer's default price level
2. Verify item prices for that level
3. Check for customer-specific custom prices
4. Verify unit-specific prices if using alternative units

### Problem: Stock calculation seems wrong

**Possible Causes:**
- Conversion factor incorrect
- Wrong base unit set
- Multiple base units (should only be one)

**Solution:**
1. Check item's base unit (should be only one)
2. Verify conversion factors for alternative units
3. Review recent transactions to see conversions

### Problem: Cannot remove base unit

**Cause**: Base unit cannot be removed if item has transactions or is the only unit

**Solution:**
1. Add another unit first
2. Set new unit as base unit
3. Then remove old base unit (if no transactions exist)

### Problem: Price level not applying

**Possible Causes:**
- Customer's default price level not set
- Item doesn't have prices for that level
- Custom price override exists

**Solution:**
1. Set customer's default price level
2. Ensure item has prices for all levels
3. Check for custom price overrides

---

## Quick Reference

### Unit Management

| Task | Location | Permission Required |
|------|----------|---------------------|
| View all units | Master Data > Units of Measure | view_unit_of_measure |
| Create unit | Master Data > Units of Measure > Add Unit | create_unit_of_measure |
| Edit unit | Master Data > Units of Measure > Edit | update_unit_of_measure |
| Add unit to item | Inventory > Items > [Item] > Manage Units | inventory.update |
| Set base unit | Inventory > Items > [Item] > Manage Units | inventory.update |

### Price Management

| Task | Location | Permission Required |
|------|----------|---------------------|
| Set item price levels | Inventory > Items > [Item] > Edit | inventory.update |
| Set unit prices | Inventory > Items > [Item] > Manage Units | inventory.update |
| Set customer price level | Business Partner > [Customer] > Edit | business_partners.update |
| Set custom price | Business Partner > [Customer] > Item Pricing | business_partners.update |

### Common Conversions

| From | To | Factor | Example |
|------|-----|--------|---------|
| BOX | EA | 12 | 1 BOX = 12 EA |
| CARTON | EA | 144 | 1 CARTON = 144 EA |
| CARTON | BOX | 12 | 1 CARTON = 12 BOX |
| DOZEN | EA | 12 | 1 DOZEN = 12 EA |
| GROSS | EA | 144 | 1 GROSS = 144 EA |

### Price Level Guidelines

| Level | Typical Use | Discount Range |
|-------|-------------|----------------|
| Level 1 | Retail customers | 0% (standard price) |
| Level 2 | Wholesale customers | 5-15% discount |
| Level 3 | Distributors | 10-25% discount |

### Best Practices

1. **Always set a base unit first** before adding alternative units
2. **Use consistent conversion factors** across similar items
3. **Set prices for all levels** even if not immediately needed
4. **Test conversions** with small quantities first
5. **Document custom prices** for audit purposes
6. **Review price levels regularly** to ensure they're current
7. **Use unit-specific prices** for volume discounts
8. **Set customer price levels** during customer setup

---

## Additional Resources

- [Inventory Module Manual](inventory-module-manual.md) - Complete inventory management guide
- [Business Partner Module Manual](business-partner-module-manual.md) - Customer management guide
- [Purchase Module Manual](purchase-module-manual.md) - Purchase order management

---

**Last Updated**: December 2025
**Version**: 1.0

