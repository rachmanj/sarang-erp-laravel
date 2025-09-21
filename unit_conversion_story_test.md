# Unit Conversion System - Story-Based Test Scenario

## Test Story: "Office Supplies Trading Company - Multi-Unit Inventory Management"

### Background

You are the inventory manager at "PT Office Supplies Trading", a company that buys office supplies in bulk and sells them in various unit sizes. Today you need to:

1. Set up a new inventory item (pencils) with multiple unit options
2. Create a purchase order buying pencils in dozens
3. Verify the system correctly converts to base units for inventory tracking

### Test Scenario: "Setting Up Pencil Inventory with Multiple Units"

#### Step 1: Login and Navigate to Inventory Management

-   Login as admin@example.com
-   Navigate to Inventory Management
-   Access the new "Manage Units" feature

#### Step 2: Create Sample Inventory Item

-   Create a new inventory item: "Wooden Pencil" (Code: PENCIL001)
-   Set up the item with basic information

#### Step 3: Configure Multiple Units for Pencils

-   Add base unit: Pieces (PCS) - Rp 500 purchase, Rp 1,000 selling
-   Add dozen unit: Dozen (DOZ) - Rp 6,000 purchase, Rp 12,000 selling
-   Add gross unit: Gross (GROSS) - Rp 72,000 purchase, Rp 144,000 selling
-   Verify unit conversion factors are working correctly

#### Step 4: Test Purchase Order with Unit Conversion

-   Create a new Purchase Order
-   Add pencil item and select "Dozen" as the order unit
-   Enter quantity: 5 dozen at Rp 12,000 per dozen
-   Verify conversion preview shows: "5 Dozen = 60 Pieces"
-   Verify base unit price calculation: Rp 1,000 per piece

#### Step 5: Validate System Calculations

-   Confirm the system correctly calculates:
    -   Order quantity: 5 dozen
    -   Base quantity: 60 pieces
    -   Conversion factor: 12
    -   Base unit price: Rp 1,000 per piece

### Expected Results

-   Unit conversion system working correctly
-   Real-time conversion previews displaying
-   Automatic base unit cost calculations
-   Professional UI with unit selection dropdowns
-   Complete integration with Purchase Order workflow

### Test Data

-   Item: Wooden Pencil (PENCIL001)
-   Units: PCS (base), DOZ (12x), GROSS (144x)
-   Purchase Order: 5 dozen @ Rp 12,000/dozen
-   Expected Conversion: 5 dozen = 60 pieces @ Rp 1,000/piece
