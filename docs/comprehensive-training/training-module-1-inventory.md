# Training Module 1: Inventory Management

## Real-Time Stock Tracking & Valuation

**Duration**: 3 hours  
**Target Audience**: Warehouse staff, inventory managers, purchasing team  
**Prerequisites**: Basic computer skills, understanding of inventory concepts

---

## Learning Objectives

By the end of this module, participants will be able to:

1. **Navigate the inventory management interface** efficiently
2. **Create and manage inventory items** with proper categorization
3. **Process stock movements** (receipts, issues, transfers, adjustments)
4. **Understand valuation methods** (FIFO, LIFO, Weighted Average)
5. **Monitor stock levels** and set up reorder points
6. **Generate inventory reports** for management and analysis
7. **Handle inventory discrepancies** and cycle counting

---

## Module Overview

### Key Features Covered

-   **Inventory Item Management**: Product setup, categorization, specifications
-   **Stock Movement Tracking**: Real-time inventory updates
-   **Valuation Methods**: Automatic cost calculation and tracking
-   **Stock Monitoring**: Low stock alerts and reorder management
-   **Reporting**: Comprehensive inventory analytics and reports
-   **Data Quality**: Duplicate detection and consistency validation

---

## Story-Based Training Scenarios

### Scenario 1: New Product Setup

**Business Context**: You're a warehouse manager at PT Sarange Trading. A new supplier has provided a new product line of office supplies that needs to be added to your inventory system.

**Story**: "The purchasing team has just finalized a contract with OfficeMax Indonesia for a new line of premium office chairs. You need to set up 5 different chair models in the system before the first shipment arrives next week."

#### Step-by-Step Exploration

**Step 1: Access Inventory Management**

-   Navigate to: `Inventory > Items`
-   Observe the inventory dashboard showing current stock levels
-   Notice the different categories and valuation methods displayed

**Step 2: Create New Inventory Item**

-   Click "Add New Item"
-   Fill in basic information:
    -   **Item Code**: CHR-001 (Office Chair Model A)
    -   **Item Name**: Premium Office Chair - Model A
    -   **Category**: Office Furniture
    -   **Unit**: Pieces
    -   **Valuation Method**: FIFO (First In, First Out)

**Step 3: Set Up Item Specifications**

-   **Dimensions**: 60cm x 60cm x 110cm
-   **Weight**: 15kg
-   **Material**: Leather
-   **Color**: Black
-   **Supplier**: OfficeMax Indonesia

**Step 4: Configure Stock Management**

-   **Reorder Point**: 10 units
-   **Maximum Stock**: 100 units
-   **Minimum Stock**: 5 units
-   **Lead Time**: 7 days

#### Discussion Points

-   Why is proper categorization important for inventory management?
-   How do different valuation methods affect cost tracking?
-   What factors should be considered when setting reorder points?

#### Hands-On Exercise

Create 4 more chair models (CHR-002 to CHR-005) with different specifications and pricing. Practice setting up different categories and valuation methods.

---

### Scenario 2: Stock Receipt Processing

**Business Context**: The first shipment of office chairs has arrived. You need to process the goods receipt and update inventory levels.

**Story**: "The delivery truck from OfficeMax Indonesia has arrived with 50 units of CHR-001 chairs. The invoice shows a unit cost of Rp 2,500,000. You need to process this receipt and verify the stock levels."

#### Step-by-Step Exploration

**Step 1: Access Stock Movement**

-   Navigate to: `Inventory > Movements`
-   Click "New Movement"
-   Select movement type: "Purchase Receipt"

**Step 2: Process Goods Receipt**

-   **Item**: CHR-001 (Premium Office Chair - Model A)
-   **Quantity**: 50 units
-   **Unit Cost**: Rp 2,500,000
-   **Total Cost**: Rp 125,000,000
-   **Supplier**: OfficeMax Indonesia
-   **Reference**: PO-2024-001
-   **Date**: Today's date

**Step 3: Verify Stock Update**

-   Check current stock level for CHR-001
-   Verify the cost has been updated in the system
-   Confirm the valuation method calculation (FIFO)

**Step 4: Generate Receipt Report**

-   Print goods receipt document
-   Verify all details are correct
-   File for accounting purposes

#### Discussion Points

-   How does the FIFO valuation method work in practice?
-   What happens to stock levels when goods are received?
-   Why is it important to verify costs during receipt?

#### Hands-On Exercise

Process receipts for the other 4 chair models with different quantities and costs. Practice with different valuation methods to see how they affect cost calculations.

---

### Scenario 3: Stock Issue and Transfer

**Business Context**: The sales team has received orders for office chairs. You need to issue stock for sales and transfer some items between locations.

**Story**: "The sales team has confirmed orders for 15 CHR-001 chairs to be delivered to customers. Additionally, you need to transfer 5 chairs from the main warehouse to the showroom for display purposes."

#### Step-by-Step Exploration

**Step 1: Process Sales Issue**

-   Navigate to: `Inventory > Movements`
-   Click "New Movement"
-   Select movement type: "Sales Issue"
-   **Item**: CHR-001
-   **Quantity**: 15 units
-   **Reference**: SO-2024-001
-   **Customer**: PT Maju Jaya

**Step 2: Process Stock Transfer**

-   Create new movement: "Transfer"
-   **From Location**: Main Warehouse
-   **To Location**: Showroom
-   **Item**: CHR-001
-   **Quantity**: 5 units
-   **Reference**: TR-2024-001

**Step 3: Verify Stock Levels**

-   Check updated stock levels
-   Verify cost of goods sold calculation
-   Confirm transfer documentation

#### Discussion Points

-   How do stock issues affect inventory valuation?
-   What documentation is needed for stock transfers?
-   How does the system track cost of goods sold?

#### Hands-On Exercise

Practice different types of stock movements: sales issues, transfers, adjustments, and returns. Observe how each affects stock levels and costs.

---

### Scenario 4: Inventory Adjustment and Cycle Counting

**Business Context**: Monthly cycle counting reveals discrepancies between physical stock and system records. You need to process adjustments.

**Story**: "During the monthly cycle count, you discovered that the physical count shows 28 units of CHR-001, but the system shows 30 units. You need to process an adjustment to reconcile the difference."

#### Step-by-Step Exploration

**Step 1: Access Adjustment Function**

-   Navigate to: `Inventory > Adjustments`
-   Click "New Adjustment"
-   Select adjustment type: "Cycle Count Adjustment"

**Step 2: Process Stock Adjustment**

-   **Item**: CHR-001
-   **System Quantity**: 30 units
-   **Physical Quantity**: 28 units
-   **Adjustment**: -2 units
-   **Reason**: Cycle count discrepancy
-   **Reference**: CC-2024-001

**Step 3: Review Adjustment Impact**

-   Check updated stock levels
-   Review adjustment report
-   Verify cost impact

**Step 4: Generate Cycle Count Report**

-   Print cycle count summary
-   Document discrepancies and reasons
-   File for management review

#### Discussion Points

-   What causes inventory discrepancies?
-   How should adjustments be documented?
-   What controls prevent future discrepancies?

#### Hands-On Exercise

Practice processing various types of adjustments: cycle count discrepancies, damage write-offs, and quality control rejections.

---

### Scenario 5: Low Stock Monitoring and Reorder Management

**Business Context**: The system has detected low stock levels for several items. You need to review and manage reorder points.

**Story**: "The system has sent alerts that CHR-001 is below the reorder point of 10 units. You need to review the situation and either adjust reorder points or initiate a purchase order."

#### Step-by-Step Exploration

**Step 1: Access Low Stock Report**

-   Navigate to: `Inventory > Reports > Low Stock`
-   Review items below reorder point
-   Check current stock levels and usage patterns

**Step 2: Analyze Stock Situation**

-   **Item**: CHR-001
-   **Current Stock**: 8 units
-   **Reorder Point**: 10 units
-   **Usage Rate**: 5 units per week
-   **Lead Time**: 7 days

**Step 3: Make Reorder Decision**

-   Calculate required quantity: 5 units/week × 2 weeks = 10 units
-   Add safety stock: 10 + 5 = 15 units
-   **Recommended Order**: 15 units

**Step 4: Update Reorder Points**

-   Review and adjust reorder points if needed
-   Consider seasonal variations
-   Update lead times if supplier performance changes

#### Discussion Points

-   How do you calculate optimal reorder points?
-   What factors affect reorder quantities?
-   How can historical data improve reorder decisions?

#### Hands-On Exercise

Practice setting up reorder points for different items, considering various factors like seasonality, supplier performance, and usage patterns.

---

## Advanced Features Exploration

### Valuation Methods Deep Dive

**FIFO (First In, First Out)**

-   **Scenario**: Process multiple receipts at different costs
-   **Exercise**: Receive 10 units at Rp 2,500,000, then 20 units at Rp 2,600,000
-   **Question**: What is the cost when you issue 15 units?

**LIFO (Last In, First Out)**

-   **Scenario**: Same receipts as above
-   **Exercise**: Issue 15 units using LIFO method
-   **Question**: How does the cost differ from FIFO?

**Weighted Average**

-   **Scenario**: Multiple receipts with different costs
-   **Exercise**: Calculate weighted average cost
-   **Question**: When is weighted average most appropriate?

### Reporting and Analytics

**Stock Movement Report**

-   **Purpose**: Track all inventory movements
-   **Exercise**: Generate report for last 30 days
-   **Analysis**: Identify patterns and anomalies

**Inventory Valuation Report**

-   **Purpose**: Current stock value by category
-   **Exercise**: Generate valuation report
-   **Analysis**: Identify high-value items

**Stock Aging Report**

-   **Purpose**: Identify slow-moving inventory
-   **Exercise**: Generate aging report
-   **Analysis**: Plan for slow-moving items

---

## Assessment Questions

### Knowledge Check

1. **What are the three main valuation methods available in the system?**
2. **How does the system calculate reorder points?**
3. **What documentation is required for stock adjustments?**
4. **How do you process a stock transfer between locations?**
5. **What happens to inventory valuation when goods are received?**

### Practical Exercises

1. **Set up a new inventory item** with proper categorization and specifications
2. **Process a goods receipt** with multiple items and different costs
3. **Issue stock for a sales order** and verify cost calculation
4. **Process a stock adjustment** for cycle count discrepancy
5. **Generate inventory reports** for management review

### Scenario-Based Questions

1. **A supplier delivers goods with a different quantity than ordered. How do you handle this?**
2. **During cycle counting, you find damaged goods. What steps do you take?**
3. **A customer returns goods that were previously sold. How do you process this?**
4. **The system shows negative stock for an item. What could cause this and how do you fix it?**
5. **You need to transfer inventory between two warehouses. What documentation is required?**

---

## Troubleshooting Common Issues

### Issue 1: Negative Stock Levels

**Symptoms**: System shows negative quantities for items
**Causes**:

-   Sales processed before goods receipt
-   Incorrect movement processing
-   System timing issues

**Solutions**:

1. Check movement history for the item
2. Verify goods receipt processing
3. Process stock adjustment if needed
4. Review business processes

### Issue 2: Incorrect Valuation

**Symptoms**: Cost calculations don't match expectations
**Causes**:

-   Wrong valuation method selected
-   Incorrect cost entry during receipt
-   System calculation errors

**Solutions**:

1. Verify valuation method setting
2. Check cost entry accuracy
3. Review movement history
4. Contact system administrator if needed

### Issue 3: Reorder Point Alerts Not Working

**Symptoms**: No alerts for low stock items
**Causes**:

-   Reorder points not set
-   Alert system disabled
-   Incorrect usage rate calculation

**Solutions**:

1. Check reorder point settings
2. Verify alert configuration
3. Review usage rate calculations
4. Test alert system

---

## Best Practices

### Data Entry

-   **Always verify quantities** before processing movements
-   **Double-check costs** during goods receipt
-   **Use consistent naming** for item codes and descriptions
-   **Maintain accurate specifications** for all items

### Stock Management

-   **Regular cycle counting** to maintain accuracy
-   **Monitor reorder points** and adjust as needed
-   **Track supplier performance** for lead time accuracy
-   **Document all adjustments** with proper reasons

### Reporting

-   **Generate regular reports** for management review
-   **Analyze trends** in stock movements and costs
-   **Monitor slow-moving items** for disposal planning
-   **Track inventory turnover** for efficiency analysis

---

## Module Completion Checklist

-   [ ] Successfully created new inventory items
-   [ ] Processed goods receipts with proper cost tracking
-   [ ] Issued stock for sales orders
-   [ ] Processed stock transfers between locations
-   [ ] Handled inventory adjustments and cycle counting
-   [ ] Set up and monitored reorder points
-   [ ] Generated inventory reports and analytics
-   [ ] Understood different valuation methods
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions

---

## Next Steps

After completing this module, participants should:

1. **Practice daily operations** in the inventory system
2. **Review module materials** for reference
3. **Prepare for Module 2**: Sales Management
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear concepts

---

_This module provides the foundation for effective inventory management in Sarange ERP. Participants should feel confident in their ability to manage daily inventory operations and understand the system's capabilities._
