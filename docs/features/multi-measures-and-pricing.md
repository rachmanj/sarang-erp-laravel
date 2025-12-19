# Multi-Measures and Multi-Price Features Analysis

## Overview

This document explains the multi-measures and multi-price features implemented in the Sarang-ERP system.

---

## 1. Multi-Measures Feature

### What is Multi-Measures?

The system supports **multiple units of measure** for a single inventory item. This allows you to:
- Define a base unit (e.g., "Each" or "Piece")
- Add alternative units with conversion factors (e.g., "Box" = 12 pieces, "Carton" = 144 pieces)
- Use different units in sales orders, purchase orders, and inventory transactions
- Automatically convert quantities between units

### Architecture

#### Database Structure

1. **`units_of_measure` Table**
   - Master table for all available units (EA, KG, M, Box, Carton, etc.)
   - Fields: `code`, `name`, `description`, `unit_type`, `is_base_unit`, `is_active`
   - Supports unit types: count, weight, length, volume, area, time

2. **`inventory_item_units` Table** (Item-Specific Units)
   - Links inventory items to units with item-specific conversions
   - Fields:
     - `inventory_item_id` - The inventory item
     - `unit_id` - Reference to `units_of_measure`
     - `is_base_unit` - Whether this is the base unit for the item
     - `conversion_quantity` - Conversion factor to base unit (e.g., 12 for "Box")
     - `selling_price` - Price for this unit at level 1
     - `selling_price_level_2` - Price for this unit at level 2
     - `selling_price_level_3` - Price for this unit at level 3
     - `is_active` - Whether this unit is active for the item

3. **`unit_conversions` Table** (Global Unit Conversions - Optional)
   - Defines global conversion factors between units
   - Currently less used; item-specific conversions in `inventory_item_units` are preferred

### How It Works

#### Example Scenario

**Item: "Lampu Sorot 1000 watt AC"**
- Base Unit: EA (Each) - 1 EA = 1 piece
- Alternative Unit 1: BOX - 1 BOX = 12 EA
- Alternative Unit 2: CARTON - 1 CARTON = 144 EA (12 boxes)

**Database Records:**

```php
// inventory_item_units table
[
    ['inventory_item_id' => 1, 'unit_id' => 1, 'is_base_unit' => true, 'conversion_quantity' => 1],
    ['inventory_item_id' => 1, 'unit_id' => 2, 'is_base_unit' => false, 'conversion_quantity' => 12],  // BOX
    ['inventory_item_id' => 1, 'unit_id' => 3, 'is_base_unit' => false, 'conversion_quantity' => 144], // CARTON
]
```

#### Conversion Logic

When a user orders 2 BOX:
1. System looks up `inventory_item_units` for item_id=1, unit_id=2 (BOX)
2. Finds `conversion_quantity = 12`
3. Converts: 2 BOX × 12 = 24 EA (base unit)
4. Stock is tracked in base unit (EA)
5. Transaction records both: order quantity (2 BOX) and base quantity (24 EA)

### Key Models and Services

1. **`InventoryItemUnit` Model**
   - Represents one unit for one inventory item
   - Methods:
     - `convertToBaseQuantity($quantity)` - Converts quantity to base unit
     - `getSellingPriceForLevel($level)` - Gets price for specific price level
     - `getPriceLevelsAttribute()` - Returns all price levels for this unit

2. **`UnitConversionService`**
   - Handles unit conversions
   - Methods:
     - `getBaseUnitForItem($itemId)` - Gets the base unit for an item
     - `convertToBaseUnit($itemId, $quantity, $orderUnitId)` - Converts order quantity to base
     - `createItemUnit($itemId, $unitId, $data)` - Creates a new unit for an item

3. **`InventoryItem` Model**
   - Relationships:
     - `itemUnits()` - All units for this item
     - `availableUnits()` - Active units only
     - `baseUnit` - The base unit (single)
   - Methods:
     - `convertToBaseUnit($quantity, $fromUnitId)` - Converts to base unit
     - `getPriceForUnit($unitId, $priceLevel)` - Gets price for unit and level
     - `getAvailableUnitsForSelection()` - Gets units for dropdowns

### Usage in Sales/Purchase Orders

When creating a sales order:
1. User selects item
2. System shows available units (EA, BOX, CARTON)
3. User selects unit and enters quantity
4. System automatically:
   - Converts to base unit for stock calculation
   - Uses unit-specific price (if set) or converts base price
   - Records both quantities in the order

---

## 2. Multi-Price Feature

### What is Multi-Price?

The system supports **multiple selling prices** for the same item, organized by:
1. **Price Levels** (Level 1, 2, 3) - Different price tiers
2. **Unit-Specific Prices** - Different prices for different units
3. **Customer-Specific Prices** - Custom prices for specific customers

### Architecture

#### Price Levels

**Three Price Levels:**
- **Level 1** - Standard/Retail price (default)
- **Level 2** - Wholesale price (lower than Level 1)
- **Level 3** - Distributor price (lowest)

#### Database Structure

1. **`inventory_items` Table**
   - `selling_price` - Level 1 price (base unit)
   - `selling_price_level_2` - Level 2 price (base unit)
   - `selling_price_level_3` - Level 3 price (base unit)
   - `price_level_2_percentage` - Percentage markup/discount for Level 2 (if not fixed)
   - `price_level_3_percentage` - Percentage markup/discount for Level 3 (if not fixed)

2. **`inventory_item_units` Table**
   - `selling_price` - Level 1 price for this unit
   - `selling_price_level_2` - Level 2 price for this unit
   - `selling_price_level_3` - Level 3 price for this unit
   - **Note:** Each unit can have different prices at each level

3. **`customer_item_price_levels` Table** (Customer-Specific Pricing)
   - `business_partner_id` - The customer
   - `inventory_item_id` - The item
   - `price_level` - Which level to use (1, 2, or 3)
   - `custom_price` - Override price (optional)

4. **`business_partners` Table**
   - `default_sales_price_level` - Default price level for this customer ('1', '2', or '3')

### How Price Resolution Works

#### Price Resolution Priority (Highest to Lowest):

1. **Customer-Specific Custom Price** (`customer_item_price_levels.custom_price`)
   - If set, this price is used regardless of level

2. **Customer-Specific Price Level** (`customer_item_price_levels.price_level`)
   - Uses the specified level for this customer-item combination

3. **Customer Default Price Level** (`business_partners.default_sales_price_level`)
   - Uses the customer's default level

4. **Unit-Specific Price** (`inventory_item_units.selling_price_level_X`)
   - If the order uses a specific unit (e.g., BOX), use that unit's price for the level

5. **Item Base Price** (`inventory_items.selling_price_level_X`)
   - Use the item's base unit price for the level

6. **Calculated Price** (if percentage is set)
   - If `price_level_X_percentage` is set, calculate: `base_price × (1 + percentage/100)`

#### Example Scenarios

**Scenario 1: Standard Customer (Level 1)**
- Customer has no special pricing
- Item: Lampu Sorot, Base Unit: EA
- `selling_price` = 500,000
- Order: 10 EA
- **Price Used:** 500,000 per EA = 5,000,000 total

**Scenario 2: Wholesale Customer (Level 2)**
- Customer's `default_sales_price_level` = '2'
- Item: Lampu Sorot
- `selling_price` = 500,000
- `selling_price_level_2` = 450,000 (or `price_level_2_percentage` = -10%)
- Order: 10 EA
- **Price Used:** 450,000 per EA = 4,500,000 total

**Scenario 3: Unit-Specific Pricing**
- Item: Lampu Sorot
- Base Unit (EA): `selling_price` = 500,000
- Alternative Unit (BOX): `selling_price` = 5,500,000 (not 6,000,000 = 12 × 500,000)
- Order: 2 BOX
- **Price Used:** 5,500,000 per BOX = 11,000,000 total
- **Note:** BOX price is set independently, may include volume discount

**Scenario 4: Customer-Specific Override**
- Customer: "PT ABC"
- Item: Lampu Sorot
- `customer_item_price_levels` record:
  - `custom_price` = 480,000
- Order: 10 EA
- **Price Used:** 480,000 per EA (overrides all levels)

### Key Models and Services

1. **`InventoryItem` Model**
   - Method: `getPriceForLevel($level, $customerId = null)`
     - Returns price for a specific level
     - Checks customer-specific pricing first
     - Falls back to item price or calculated price

2. **`InventoryItemUnit` Model**
   - Method: `getSellingPriceForLevel($level)`
     - Returns price for this unit at the specified level
     - Falls back to base price if level price not set

3. **`PriceLevelService`**
   - Method: `getEffectivePrice($itemId, $customerId, $priceLevel)`
     - Main service for price resolution
     - Implements the priority logic above
     - Returns the final price to use

4. **`CustomerItemPriceLevel` Model**
   - Stores customer-specific pricing
   - Method: `getEffectivePrice()` - Returns custom price or level-based price

---

## 3. Combined Features: Multi-Measures + Multi-Price

### How They Work Together

When creating a sales order:

1. **Item Selection**
   - User selects inventory item

2. **Unit Selection**
   - System shows available units (EA, BOX, CARTON)
   - Each unit may have different prices

3. **Price Resolution**
   - System determines customer's price level (1, 2, or 3)
   - Checks for customer-specific pricing
   - Gets price for selected unit at that level
   - If unit-specific price not set, converts base unit price

4. **Quantity Conversion**
   - User enters quantity in selected unit (e.g., 2 BOX)
   - System converts to base unit for stock tracking (2 BOX = 24 EA)
   - Price calculation uses unit-specific price (not converted)

5. **Order Total**
   - Quantity: 2 BOX
   - Unit Price: 5,500,000 (BOX price at customer's level)
   - Total: 11,000,000
   - Stock Deduction: 24 EA (base unit)

### Example: Complete Flow

**Setup:**
- Item: "Lampu Sorot 1000 watt AC"
- Base Unit: EA, Price Level 1: 500,000
- Alternative Unit: BOX (12 EA), Price Level 1: 5,500,000
- Customer: "PT Wholesale" (default level: 2)
- BOX Price Level 2: 5,000,000

**Order Creation:**
1. User selects item: "Lampu Sorot 1000 watt AC"
2. System shows units: EA, BOX
3. User selects: BOX
4. System resolves price:
   - Customer level: 2
   - Unit: BOX
   - Price: 5,000,000 (BOX Level 2)
5. User enters quantity: 3 BOX
6. System calculates:
   - Order Total: 3 × 5,000,000 = 15,000,000
   - Stock Deduction: 3 × 12 = 36 EA
7. Order saved with:
   - `quantity` = 3
   - `unit_id` = BOX (unit_id: 2)
   - `base_quantity` = 36
   - `unit_price` = 5,000,000
   - `total` = 15,000,000

---

## 4. Summary

### Multi-Measures Feature
✅ **Fully Implemented**
- Multiple units per item
- Item-specific conversion factors
- Automatic quantity conversion
- Base unit tracking
- Unit selection in orders

### Multi-Price Feature
✅ **Fully Implemented**
- Three price levels (1, 2, 3)
- Unit-specific prices
- Customer-specific prices
- Percentage-based pricing
- Price resolution service

### Combined Usage
✅ **Fully Supported**
- Different prices for different units
- Different prices for different customer levels
- Automatic conversions and calculations
- Complete integration in sales/purchase orders

---

## 5. Files Reference

### Models
- `app/Models/InventoryItem.php` - Main item model with price/unit methods
- `app/Models/InventoryItemUnit.php` - Item-unit relationship with prices
- `app/Models/UnitOfMeasure.php` - Master units table
- `app/Models/CustomerItemPriceLevel.php` - Customer-specific pricing

### Services
- `app/Services/UnitConversionService.php` - Unit conversion logic
- `app/Services/PriceLevelService.php` - Price resolution logic

### Database Tables
- `inventory_items` - Items with base prices
- `inventory_item_units` - Item-unit relationships with conversions and prices
- `units_of_measure` - Master units catalog
- `customer_item_price_levels` - Customer-specific pricing
- `business_partners` - Customer default price levels

### Controllers
- `app/Http/Controllers/InventoryController.php` - Item management with units
- `app/Http/Controllers/UnitOfMeasureController.php` - Units management

---

## 6. UI Access Points

### Managing Units of Measure
- **Route:** `/unit-of-measures`
- **Permission:** `view_unit_of_measure`
- **Features:**
  - List all units
  - Create/edit units
  - View unit details and conversions

### Managing Item Units
- **Route:** `/inventory/{id}/units` (via Inventory Item detail page)
- **Permission:** `inventory.update`
- **Features:**
  - Add units to an item
  - Set conversion factors
  - Set prices per unit per level
  - Set base unit

### Managing Prices
- **Route:** `/inventory/{id}/edit` (Inventory Item edit page)
- **Permission:** `inventory.update`
- **Features:**
  - Set base prices for levels 1, 2, 3
  - Set percentage markups/discounts
  - Set unit-specific prices (via units management)

### Customer-Specific Pricing
- **Route:** Customer/Business Partner management
- **Permission:** `business_partners.update`
- **Features:**
  - Set default price level per customer
  - Set custom prices per item per customer

