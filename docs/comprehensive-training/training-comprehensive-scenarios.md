# Comprehensive Training Scenarios for Sarange ERP

## Overview

This document provides comprehensive training scenarios that cover all major features of the Sarange ERP system, including inventory management, transfers, sales, taxation, and business intelligence.

---

## Scenario 1: Complete Inventory Lifecycle Management

### Background

PT Maju Jaya is a trading company that needs to manage their inventory from purchase to sale, including transfers between locations and proper tax compliance.

### Step 1: Inventory Item Setup

**Objective**: Set up new inventory items for trading operations

**Actions**:

1. Navigate to Inventory Management
2. Click "Add Item"
3. Create the following items:
    - **Item Code**: LAPTOP-001
    - **Name**: Laptop Dell Inspiron 15
    - **Category**: Elektronik
    - **Unit**: pcs
    - **Purchase Price**: Rp 8,000,000
    - **Selling Price**: Rp 9,500,000
    - **Min Stock**: 5
    - **Max Stock**: 50
    - **Reorder Point**: 10
    - **Valuation Method**: FIFO
    - **Initial Stock**: 0

**Expected Result**: Item created successfully with proper pricing and stock levels

### Step 2: Purchase Order Creation

**Objective**: Create purchase orders for inventory replenishment

**Actions**:

1. Navigate to Purchase Management
2. Create Purchase Order:
    - **Supplier**: OfficeMax Indonesia
    - **Items**: Laptop Dell Inspiron 15 (Qty: 20)
    - **Unit Price**: Rp 8,000,000
    - **Total**: Rp 160,000,000
    - **Tax**: PPN 11% (Rp 17,600,000)
    - **Grand Total**: Rp 177,600,000

**Expected Result**: Purchase order created with proper tax calculation

### Step 3: Goods Receipt Processing

**Objective**: Receive purchased goods and update inventory

**Actions**:

1. Navigate to Goods Receipt
2. Create receipt for Purchase Order
3. Verify quantities and quality
4. Post the receipt

**Expected Result**:

-   Inventory stock updated to 20 units
-   Purchase price recorded as Rp 8,000,000 per unit
-   PPN Masukan recorded for tax credit

### Step 4: Inventory Transfer Between Locations

**Objective**: Transfer inventory between warehouse locations

**Actions**:

1. Navigate to Inventory Management
2. Select Laptop Dell Inspiron 15
3. Click "Transfer Stock"
4. Transfer Details:
    - **From Location**: Main Warehouse
    - **To Location**: Showroom Jakarta
    - **Quantity**: 5 units
    - **Reason**: Display purposes

**Expected Result**:

-   Main Warehouse stock: 15 units
-   Showroom Jakarta stock: 5 units
-   Transfer transaction recorded

### Step 5: Sales Order Processing

**Objective**: Process sales orders with proper tax handling

**Actions**:

1. Navigate to Sales Management
2. Create Sales Order:
    - **Customer**: PT Maju Jaya
    - **Items**: Laptop Dell Inspiron 15 (Qty: 3)
    - **Unit Price**: Rp 9,500,000
    - **Subtotal**: Rp 28,500,000
    - **Tax**: PPN Keluaran 11% (Rp 3,135,000)
    - **Grand Total**: Rp 31,635,000

**Expected Result**: Sales order created with proper tax calculation

### Step 6: Sales Delivery and Inventory Update

**Objective**: Process sales delivery and update inventory

**Actions**:

1. Navigate to Sales Delivery
2. Create delivery for Sales Order
3. Verify customer details and quantities
4. Post the delivery

**Expected Result**:

-   Inventory stock reduced to 12 units (15 - 3)
-   COGS calculated using FIFO method
-   PPN Keluaran recorded for tax liability
-   Customer invoice generated

---

## Scenario 2: Tax Compliance and Reporting

### Background

PT Maju Jaya needs to ensure proper tax compliance for all transactions.

### Step 1: Tax Transaction Review

**Objective**: Review all tax transactions for the period

**Actions**:

1. Navigate to Tax Management
2. View Tax Transactions
3. Review PPN Masukan and PPN Keluaran
4. Verify tax calculations

**Expected Result**: All tax transactions properly recorded

### Step 2: Tax Period Management

**Objective**: Manage tax periods and deadlines

**Actions**:

1. Navigate to Tax Periods
2. Create new tax period for current month
3. Set reporting deadlines
4. Configure tax rates

**Expected Result**: Tax period configured with proper deadlines

### Step 3: Tax Report Generation

**Objective**: Generate required tax reports

**Actions**:

1. Navigate to Tax Reports
2. Generate SPT PPN report
3. Review tax summary
4. Export report for submission

**Expected Result**: Complete tax report generated for tax office submission

---

## Scenario 3: Business Intelligence and Analytics

### Background

Management needs insights into business performance and optimization opportunities.

### Step 1: COGS Analysis

**Objective**: Analyze cost of goods sold and margins

**Actions**:

1. Navigate to Analytics Dashboard
2. View COGS Analysis
3. Review product profitability
4. Identify optimization opportunities

**Expected Result**: Detailed COGS analysis with margin insights

### Step 2: Supplier Performance Review

**Objective**: Evaluate supplier performance and costs

**Actions**:

1. Navigate to Supplier Analytics
2. Review supplier performance metrics
3. Compare supplier costs
4. Identify best suppliers

**Expected Result**: Comprehensive supplier performance analysis

### Step 3: Business Intelligence Insights

**Objective**: Generate business insights and recommendations

**Actions**:

1. Navigate to Business Intelligence
2. Generate trading analytics report
3. Review insights and recommendations
4. Export insights for management

**Expected Result**: Actionable business insights and recommendations

---

## Scenario 4: Fixed Asset Management

### Background

PT Maju Jaya needs to manage their fixed assets including depreciation and disposal.

### Step 1: Asset Registration

**Objective**: Register new fixed assets

**Actions**:

1. Navigate to Fixed Assets
2. Add new asset:
    - **Code**: AST-001
    - **Name**: Office Server Dell PowerEdge
    - **Category**: IT Equipment
    - **Acquisition Cost**: Rp 25,000,000
    - **Useful Life**: 36 months
    - **Depreciation Method**: Straight Line

**Expected Result**: Asset registered with proper depreciation setup

### Step 2: Depreciation Processing

**Objective**: Process monthly depreciation

**Actions**:

1. Navigate to Asset Depreciation
2. Run monthly depreciation
3. Review depreciation entries
4. Post depreciation journal

**Expected Result**: Depreciation calculated and posted to accounting

### Step 3: Asset Disposal

**Objective**: Process asset disposal with gain/loss calculation

**Actions**:

1. Navigate to Asset Disposal
2. Create disposal for old asset
3. Record disposal proceeds
4. Calculate gain/loss
5. Post disposal journal

**Expected Result**: Asset disposal processed with proper gain/loss calculation

---

## Scenario 5: Multi-Dimensional Accounting

### Background

PT Maju Jaya operates multiple projects and needs project-based accounting.

### Step 1: Project Setup

**Objective**: Set up projects for cost tracking

**Actions**:

1. Navigate to Project Management
2. Create new project:
    - **Code**: PRJ-2025-001
    - **Name**: Q1 2025 Trading Operations
    - **Budget**: Rp 500,000,000
    - **Start Date**: 2025-01-01
    - **End Date**: 2025-03-31

**Expected Result**: Project created with budget allocation

### Step 2: Transaction Posting with Dimensions

**Objective**: Post transactions with project dimensions

**Actions**:

1. Navigate to Manual Journal
2. Create journal entry:
    - **Description**: Project expense allocation
    - **Account**: Office Supplies
    - **Debit**: Rp 5,000,000
    - **Project**: PRJ-2025-001
    - **Department**: Operations

**Expected Result**: Transaction posted with proper dimensional tracking

### Step 3: Project Reporting

**Objective**: Generate project-based reports

**Actions**:

1. Navigate to Project Reports
2. Generate project cost report
3. Review budget vs actual
4. Export project summary

**Expected Result**: Comprehensive project performance report

---

## Testing Checklist

### Inventory Management

-   [ ] Create inventory items
-   [ ] Set stock levels and reorder points
-   [ ] Process purchase orders
-   [ ] Receive goods and update inventory
-   [ ] Transfer inventory between locations
-   [ ] Process sales orders
-   [ ] Update inventory on sales
-   [ ] Generate inventory reports

### Tax Compliance

-   [ ] Calculate PPN on purchases
-   [ ] Calculate PPN on sales
-   [ ] Record tax transactions
-   [ ] Generate tax reports
-   [ ] Manage tax periods
-   [ ] Export tax data

### Business Intelligence

-   [ ] Generate COGS analysis
-   [ ] Review supplier performance
-   [ ] Analyze business insights
-   [ ] Export analytics reports

### Fixed Assets

-   [ ] Register new assets
-   [ ] Process depreciation
-   [ ] Handle asset disposal
-   [ ] Generate asset reports

### Multi-Dimensional Accounting

-   [ ] Set up projects and departments
-   [ ] Post transactions with dimensions
-   [ ] Generate dimensional reports
-   [ ] Track project costs

---

## Success Criteria

Each scenario should demonstrate:

1. **Functional Completeness**: All features work as designed
2. **Data Integrity**: Transactions are properly recorded
3. **Tax Compliance**: Indonesian tax requirements are met
4. **User Experience**: Intuitive and efficient workflows
5. **Reporting**: Comprehensive reports are generated
6. **Integration**: All modules work together seamlessly

---

## Troubleshooting Guide

### Common Issues and Solutions

1. **Permission Errors**: Ensure user has proper role assignments
2. **Tax Calculation Errors**: Verify tax codes and rates are correct
3. **Inventory Discrepancies**: Check valuation methods and transactions
4. **Report Generation Issues**: Verify data integrity and permissions
5. **Performance Issues**: Check database indexes and query optimization

### Error Resolution Process

1. **Identify Error**: Document the exact error message
2. **Check Logs**: Review application and database logs
3. **Verify Data**: Ensure data integrity and relationships
4. **Test Fix**: Verify solution works in test environment
5. **Document Solution**: Update troubleshooting guide
