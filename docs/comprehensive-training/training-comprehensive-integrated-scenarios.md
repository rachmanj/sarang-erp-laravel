# Comprehensive Integrated Training Scenarios

## End-to-End Business Process Testing

**Duration**: 6 hours  
**Target Audience**: Cross-functional teams, system administrators, business analysts  
**Prerequisites**: Modules 1-3 (Inventory, Sales, Purchase Management)

---

## Overview

This comprehensive training module provides end-to-end business scenarios that integrate Inventory Management, Purchase Management, and Sales Management features. Participants will experience complete business workflows from supplier setup to customer delivery, including inventory tracking, cost management, and financial reporting.

### Key Integration Points

-   **Inventory ↔ Purchase**: Goods receipt processing and stock updates
-   **Inventory ↔ Sales**: Stock allocation and delivery processing
-   **Purchase ↔ Sales**: Cost tracking and margin analysis
-   **Multi-Dimensional**: Project, fund, and department tracking across all modules

---

## Role-Based User Setup

### Department Structure

**Procurement Department**

-   **Procurement Manager**: Bapak Ahmad Wijaya
-   **Purchasing Officer**: Ibu Siti Nurhaliza
-   **Warehouse Supervisor**: Bapak Budi Santoso

**Sales Department**

-   **Sales Manager**: Ibu Dewi Sartika
-   **Sales Representative**: Bapak Rizki Pratama
-   **Customer Service**: Ibu Maya Sari

**Finance Department**

-   **Finance Manager**: Bapak Hendra Kurniawan
-   **Accountant**: Ibu Fitri Indah

**Operations Department**

-   **Operations Manager**: Bapak Agus Supriyanto
-   **Quality Control**: Ibu Ratna Dewi

---

## Comprehensive Business Scenarios

### Scenario 1: Complete Office Furniture Business Cycle

**Business Context**: PT Sarange Trading is expanding their office furniture business. This scenario covers the complete cycle from supplier evaluation to customer delivery, including inventory management, cost tracking, and margin analysis.

#### Phase 1: Supplier Setup and Evaluation

**Role**: Procurement Manager (Bapak Ahmad Wijaya)

**Story**: "OfficeMax Indonesia has approached PT Sarange Trading to become a preferred supplier for office furniture. They offer competitive pricing and have excellent references. You need to evaluate them and set them up in the system."

**Step-by-Step Process**:

1. **Access Supplier Management**

    - Navigate to: `Purchase > Suppliers`
    - Review existing supplier performance dashboard
    - Click "Add New Supplier"

2. **Create Supplier Profile**

    - **Supplier Code**: SUPP-OFFICE-001
    - **Supplier Name**: OfficeMax Indonesia
    - **Supplier Type**: Manufacturer
    - **Industry**: Office Furniture
    - **Contact Person**: Bapak Surya Wijaya
    - **Business Registration**: PT OfficeMax Indonesia
    - **Tax ID**: 01.234.567.8-901.000
    - **Address**: Jl. Industri No. 456, Tangerang
    - **Phone**: +62-21-87654321
    - **Email**: sales@officemax.co.id

3. **Configure Performance Tracking**

    - **Payment Terms**: 30 days
    - **Delivery Lead Time**: 7 days
    - **Quality Rating**: A (Excellent)
    - **Risk Category**: Low Risk
    - **Performance Weight**: 25% (Cost), 30% (Quality), 25% (Delivery), 20% (Service)

4. **Set Up Product Categories**
    - **Primary Categories**: Office Furniture, Office Supplies
    - **Specializations**: Premium Office Chairs, Executive Desks
    - **Certifications**: ISO 9001, FSC Certified
    - **Minimum Order Value**: Rp 10,000,000

#### Phase 2: Inventory Item Setup

**Role**: Warehouse Supervisor (Bapak Budi Santoso)

**Story**: "OfficeMax Indonesia has provided their product catalog. You need to set up the new office furniture items in the inventory system with proper categorization and valuation methods."

**Step-by-Step Process**:

1. **Access Inventory Management**

    - Navigate to: `Inventory > Items`
    - Review current inventory dashboard
    - Click "Add New Item"

2. **Create Premium Office Chair**

    - **Item Code**: CHR-PREM-001
    - **Item Name**: Premium Office Chair - Executive Model
    - **Category**: Office Furniture > Chairs
    - **Unit**: Pieces
    - **Valuation Method**: FIFO (First In, First Out)
    - **Dimensions**: 60cm x 60cm x 110cm
    - **Weight**: 15kg
    - **Material**: Premium Leather
    - **Color**: Black
    - **Supplier**: OfficeMax Indonesia

3. **Configure Stock Management**

    - **Reorder Point**: 20 units
    - **Maximum Stock**: 200 units
    - **Minimum Stock**: 10 units
    - **Lead Time**: 7 days
    - **Storage Location**: Main Warehouse - Zone A

4. **Create Executive Desk**

    - **Item Code**: DSK-EXEC-001
    - **Item Name**: Executive Desk - Mahogany Finish
    - **Category**: Office Furniture > Desks
    - **Unit**: Pieces
    - **Valuation Method**: FIFO
    - **Dimensions**: 120cm x 80cm x 75cm
    - **Weight**: 45kg
    - **Material**: Solid Mahogany
    - **Color**: Mahogany
    - **Supplier**: OfficeMax Indonesia

5. **Set Up Additional Items**
    - Create 3 more office furniture items
    - Practice with different valuation methods (LIFO, Weighted Average)
    - Set up proper categorization and specifications

#### Phase 3: Purchase Order Creation

**Role**: Purchasing Officer (Ibu Siti Nurhaliza)

**Story**: "The warehouse has identified low stock levels for office furniture. After evaluating suppliers, OfficeMax Indonesia offers the best combination of price, quality, and delivery. You need to create a purchase order for the initial stock."

**Step-by-Step Process**:

1. **Access Purchase Orders**

    - Navigate to: `Purchase > Orders`
    - Click "New Purchase Order"
    - Select supplier: OfficeMax Indonesia

2. **Create Order Header**

    - **Order Number**: PO-2024-001
    - **Order Date**: Today's date
    - **Delivery Date**: 7 days from today
    - **Payment Terms**: 30 days
    - **Buyer**: Ibu Siti Nurhaliza
    - **Reference**: Initial Stock Order
    - **Project**: Office Furniture Expansion
    - **Fund**: Operating Fund
    - **Department**: Procurement

3. **Add Order Lines**

    - **Line 1**: CHR-PREM-001 (50 units @ Rp 2,500,000 = Rp 125,000,000)
    - **Line 2**: DSK-EXEC-001 (20 units @ Rp 10,000,000 = Rp 200,000,000)
    - **Line 3**: Additional items as needed

4. **Calculate Order Totals**

    - **Subtotal**: Rp 325,000,000
    - **Freight**: Rp 5,000,000
    - **Handling**: Rp 2,000,000
    - **Tax (PPN 11%)**: Rp 36,520,000
    - **Total Amount**: Rp 368,520,000

5. **Submit for Approval**
    - Review order details
    - Check budget availability
    - Submit for manager approval
    - Note approval workflow status

#### Phase 4: Goods Receipt Processing

**Role**: Warehouse Supervisor (Bapak Budi Santoso)

**Story**: "OfficeMax Indonesia has delivered the office furniture order. You need to process the goods receipt, verify quantities and quality, and update inventory levels."

**Step-by-Step Process**:

1. **Access Goods Receipt**

    - Navigate to: `Purchase > Goods Receipt`
    - Click "New Receipt"
    - Select purchase order: PO-2024-001

2. **Process Receipt Header**

    - **Receipt Number**: GR-2024-001
    - **Receipt Date**: Today's date
    - **Supplier**: OfficeMax Indonesia
    - **Delivery Method**: Company truck
    - **Driver**: Bapak Andi
    - **Vehicle**: Truck-002

3. **Verify Received Items**

    - **CHR-PREM-001**: 50 units (ordered: 50, received: 50) ✓
    - **DSK-EXEC-001**: 20 units (ordered: 20, received: 20) ✓
    - **Total Value**: Rp 325,000,000
    - **Quality Check**: All items in excellent condition

4. **Process Receipt**

    - Confirm all items received
    - Verify quantities match purchase order
    - Check quality and condition
    - Process receipt and update inventory

5. **Verify Inventory Updates**
    - Check updated stock levels for all items
    - Verify cost calculations (FIFO method)
    - Confirm inventory valuation updates
    - Review movement transactions

#### Phase 5: Customer Setup

**Role**: Sales Representative (Bapak Rizki Pratama)

**Story**: "PT Maju Jaya, a growing construction company, has approached PT Sarange Trading for office furniture supplies. They have a good credit history and want to establish a business relationship."

**Step-by-Step Process**:

1. **Access Customer Management**

    - Navigate to: `Sales > Customers`
    - Click "Add New Customer"

2. **Create Customer Profile**

    - **Customer Code**: CUST-MAJU-001
    - **Customer Name**: PT Maju Jaya
    - **Customer Type**: Corporate
    - **Industry**: Construction
    - **Contact Person**: Bapak Ahmad Wijaya
    - **Address**: Jl. Sudirman No. 123, Jakarta
    - **Phone**: +62-21-12345678
    - **Email**: procurement@majujaya.co.id
    - **Tax ID**: 01.234.567.8-901.000

3. **Set Up Credit Management**

    - **Credit Limit**: Rp 500,000,000
    - **Payment Terms**: 30 days
    - **Credit Rating**: A (Excellent)
    - **Risk Category**: Low Risk
    - **Approval Required**: Yes (for orders above Rp 100,000,000)

4. **Configure Pricing Tier**
    - **Pricing Tier**: Tier 2 (Corporate)
    - **Discount Level**: 5% standard discount
    - **Special Pricing**: Available for bulk orders
    - **Volume Discounts**: 10% for orders above Rp 200,000,000

#### Phase 6: Sales Order Creation

**Role**: Sales Representative (Bapak Rizki Pratama)

**Story**: "PT Maju Jaya has placed their first order for office furniture. The total order value is Rp 150,000,000, which requires manager approval. You need to create the sales order and route it for approval."

**Step-by-Step Process**:

1. **Access Sales Orders**

    - Navigate to: `Sales > Orders`
    - Click "New Sales Order"
    - Select customer: PT Maju Jaya

2. **Create Order Header**

    - **Order Number**: SO-2024-001
    - **Order Date**: Today's date
    - **Delivery Date**: 7 days from today
    - **Payment Terms**: 30 days
    - **Sales Rep**: Bapak Rizki Pratama
    - **Reference**: Customer PO: MJ-2024-001
    - **Project**: Office Setup Project
    - **Fund**: Operating Fund
    - **Department**: Sales

3. **Add Order Lines**

    - **Line 1**: CHR-PREM-001 (20 units @ Rp 2,500,000 = Rp 50,000,000)
    - **Line 2**: DSK-EXEC-001 (10 units @ Rp 10,000,000 = Rp 100,000,000)

4. **Apply Pricing and Discounts**

    - **Subtotal**: Rp 150,000,000
    - **Tier 2 Discount (5%)**: Rp 7,500,000
    - **Volume Discount (10%)**: Rp 14,250,000
    - **Total Discount**: Rp 21,750,000
    - **Net Amount**: Rp 128,250,000

5. **Submit for Approval**
    - Review order details
    - Check credit limit availability
    - Submit for manager approval
    - Note approval workflow status

#### Phase 7: Order Approval

**Role**: Sales Manager (Ibu Dewi Sartika)

**Story**: "The sales order from PT Maju Jaya is pending your approval. You need to review their credit limit, current outstanding balance, and order details to make an approval decision."

**Step-by-Step Process**:

1. **Access Approval Queue**

    - Navigate to: `Sales > Approvals`
    - Review pending orders requiring approval
    - Click on SO-2024-001 for detailed review

2. **Review Customer Credit Status**

    - **Customer**: PT Maju Jaya
    - **Credit Limit**: Rp 500,000,000
    - **Current Outstanding**: Rp 0
    - **Available Credit**: Rp 500,000,000
    - **Order Value**: Rp 128,250,000
    - **Credit Utilization**: 25.65%

3. **Assess Order Details**

    - Review order lines and pricing
    - Verify discount calculations
    - Check delivery terms and conditions
    - Confirm payment terms

4. **Make Approval Decision**

    - **Decision**: Approve
    - **Reason**: Order within credit limit, good customer history
    - **Conditions**: Standard payment terms apply
    - **Approval Date**: Today's date

5. **Process Approval**
    - Click "Approve Order"
    - Add approval comments
    - Notify sales representative
    - Update order status

#### Phase 8: Delivery Processing

**Role**: Warehouse Supervisor (Bapak Budi Santoso)

**Story**: "The approved sales order is ready for delivery. You need to process the delivery, update inventory, and generate delivery documents."

**Step-by-Step Process**:

1. **Access Delivery Processing**

    - Navigate to: `Sales > Deliveries`
    - Click "New Delivery"
    - Select order: SO-2024-001

2. **Process Delivery**

    - **Delivery Number**: DEL-2024-001
    - **Delivery Date**: Today's date
    - **Delivery Method**: Company truck
    - **Driver**: Bapak Budi Santoso
    - **Vehicle**: Truck-001

3. **Confirm Items and Quantities**

    - **CHR-PREM-001**: 20 units (confirmed available)
    - **DSK-EXEC-001**: 10 units (confirmed available)
    - **Total Value**: Rp 128,250,000
    - **Delivery Address**: Jl. Sudirman No. 123, Jakarta

4. **Generate Delivery Documents**

    - Print delivery note
    - Generate packing list
    - Create delivery receipt
    - Update order status to "Delivered"

5. **Verify Inventory Updates**
    - Check updated stock levels
    - Verify cost of goods sold calculation
    - Confirm inventory valuation updates
    - Review movement transactions

#### Phase 9: Financial Impact Analysis

**Role**: Accountant (Ibu Fitri Indah)

**Story**: "The complete business cycle has been processed. You need to analyze the financial impact, including cost tracking, margin analysis, and inventory valuation."

**Step-by-Step Process**:

1. **Access Financial Reports**

    - Navigate to: `Reports > Trial Balance`
    - Generate trial balance for current period
    - Review account balances

2. **Analyze Inventory Valuation**

    - Navigate to: `Reports > GL Detail`
    - Filter by inventory accounts
    - Review cost movements and valuations

3. **Review Sales Performance**

    - Navigate to: `Sales > Reports`
    - Generate sales performance report
    - Analyze margins and profitability

4. **Check Multi-Dimensional Tracking**
    - Review project-based costs
    - Analyze fund utilization
    - Check departmental allocations

---

## Advanced Integration Scenarios

### Scenario 2: Multi-Supplier Comparison and Selection

**Business Context**: PT Sarange Trading needs to purchase 100 premium office chairs. Three suppliers have submitted quotes, and you need to compare them using the integrated system.

**Roles Involved**: Procurement Manager, Purchasing Officer, Finance Manager

**Process Flow**:

1. Set up multiple suppliers with different performance ratings
2. Create purchase orders for comparison
3. Use supplier comparison tools
4. Make selection based on integrated analysis
5. Process selected order and track performance

### Scenario 3: Inventory Optimization and Reorder Management

**Business Context**: The system has detected low stock levels for several items. You need to analyze usage patterns, optimize reorder points, and coordinate with purchasing for replenishment.

**Roles Involved**: Warehouse Supervisor, Purchasing Officer, Operations Manager

**Process Flow**:

1. Review low stock alerts and reports
2. Analyze historical usage patterns
3. Optimize reorder points and quantities
4. Coordinate with purchasing for replenishment
5. Monitor supplier performance and delivery

### Scenario 4: Customer Credit Management and Risk Assessment

**Business Context**: A customer wants to place a large order that approaches their credit limit. You need to assess credit risk and make appropriate decisions.

**Roles Involved**: Sales Representative, Sales Manager, Finance Manager

**Process Flow**:

1. Review customer credit status and history
2. Assess order impact on credit utilization
3. Evaluate payment patterns and risk factors
4. Make credit decisions and set conditions
5. Monitor ongoing credit performance

### Scenario 5: End-to-End Project Accounting

**Business Context**: PT Sarange Trading is managing a major office renovation project. You need to track all costs, revenues, and inventory movements associated with this project.

**Roles Involved**: All departments

**Process Flow**:

1. Set up project with budget and timeline
2. Allocate purchases to project
3. Track inventory movements by project
4. Process sales orders for project
5. Generate project profitability reports

---

## Cross-Module Validation Points

### Data Integrity Checks

1. **Inventory Accuracy**

    - Stock levels match between purchase receipts and sales deliveries
    - Cost calculations are consistent across modules
    - Valuation methods are properly applied

2. **Financial Integration**

    - Purchase orders create proper accounting entries
    - Sales orders generate correct revenue recognition
    - Inventory movements affect cost of goods sold

3. **Multi-Dimensional Tracking**
    - Project costs are properly allocated
    - Fund utilization is accurately tracked
    - Departmental costs are correctly assigned

### Performance Metrics

1. **Inventory Turnover**

    - Calculate inventory turnover ratios
    - Analyze slow-moving items
    - Optimize stock levels

2. **Supplier Performance**

    - Track delivery performance
    - Monitor quality metrics
    - Analyze cost trends

3. **Customer Profitability**
    - Calculate customer margins
    - Analyze order patterns
    - Assess credit risk

---

## Troubleshooting Common Integration Issues

### Issue 1: Inventory Discrepancies

**Symptoms**: Stock levels don't match between modules
**Causes**:

-   Timing differences in processing
-   Incomplete goods receipt processing
-   Delivery processing errors

**Solutions**:

1. Verify all movements are properly processed
2. Check for pending receipts or deliveries
3. Process necessary adjustments
4. Review business process workflows

### Issue 2: Cost Calculation Errors

**Symptoms**: Cost of goods sold doesn't match expectations
**Causes**:

-   Incorrect valuation method application
-   Purchase cost entry errors
-   Delivery processing issues

**Solutions**:

1. Verify valuation method settings
2. Check purchase cost accuracy
3. Review delivery processing
4. Analyze movement history

### Issue 3: Approval Workflow Delays

**Symptoms**: Orders stuck in approval queues
**Causes**:

-   Missing approver assignments
-   Budget or credit limit issues
-   Incomplete order information

**Solutions**:

1. Check approver assignments
2. Review budget and credit limits
3. Complete missing information
4. Escalate to management

---

## Best Practices for Integrated Operations

### Data Management

-   **Maintain accurate master data** across all modules
-   **Use consistent naming conventions** for codes and descriptions
-   **Regular data validation** and reconciliation
-   **Document all business processes** and exceptions

### Process Integration

-   **Follow established workflows** for all transactions
-   **Coordinate between departments** for complex orders
-   **Monitor integration points** for data consistency
-   **Use system automation** where possible

### Performance Monitoring

-   **Track key performance indicators** across modules
-   **Analyze trends and patterns** in business data
-   **Optimize processes** based on performance data
-   **Regular system maintenance** and updates

---

## Assessment and Validation

### Knowledge Check Questions

1. **How do purchase orders affect inventory levels and costs?**
2. **What happens to inventory when a sales order is delivered?**
3. **How are costs tracked from purchase to sale?**
4. **What role do projects, funds, and departments play in integrated operations?**
5. **How do you handle discrepancies between modules?**

### Practical Exercises

1. **Complete end-to-end business cycle** from supplier setup to customer delivery
2. **Process multi-supplier comparison** and selection
3. **Handle inventory optimization** and reorder management
4. **Manage customer credit** and risk assessment
5. **Generate integrated reports** and analyze performance

### Scenario-Based Questions

1. **A supplier delivers goods with quality issues. How do you handle this across all modules?**
2. **A customer wants to return goods after delivery. What's the complete process?**
3. **Inventory levels show discrepancies between modules. How do you investigate and resolve this?**
4. **A large order requires approval from multiple departments. How do you coordinate this?**
5. **You need to analyze the profitability of a specific project. What reports and data do you need?**

---

## Module Completion Checklist

-   [ ] Successfully completed end-to-end business cycle
-   [ ] Processed multi-supplier comparison and selection
-   [ ] Handled inventory optimization and reorder management
-   [ ] Managed customer credit and risk assessment
-   [ ] Generated integrated reports and analyzed performance
-   [ ] Understood cross-module data flow and integration
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions
-   [ ] Demonstrated proficiency in integrated operations

---

## Next Steps

After completing this comprehensive module, participants should:

1. **Practice integrated operations** across all modules
2. **Review integration points** and data flow
3. **Prepare for advanced modules** (Financial Management, Tax Compliance)
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear integration concepts

---

_This comprehensive module provides end-to-end training on integrated business operations in Sarange ERP. Participants should feel confident in their ability to manage complete business cycles from supplier to customer, including inventory tracking, cost management, and financial reporting._
