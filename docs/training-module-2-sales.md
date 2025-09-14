# Training Module 2: Sales Management

## Customer Management & Order Processing

**Duration**: 3 hours  
**Target Audience**: Sales team, customer service, sales managers  
**Prerequisites**: Module 1 (Inventory Management), basic sales process knowledge

---

## Learning Objectives

By the end of this module, participants will be able to:

1. **Manage customer master data** and credit limits effectively
2. **Create and process sales orders** with proper approval workflows
3. **Handle pricing tiers** and discount management
4. **Track sales commissions** and performance metrics
5. **Process deliveries** and manage order fulfillment
6. **Generate sales reports** and analytics
7. **Handle customer returns** and credit notes

---

## Module Overview

### Key Features Covered

-   **Customer Management**: Master data, credit limits, pricing tiers
-   **Sales Order Processing**: Order creation, approval workflows, line management
-   **Pricing Management**: Tier-based pricing, discounts, promotions
-   **Commission Tracking**: Sales performance, commission calculations
-   **Delivery Management**: Order fulfillment, shipping, invoicing
-   **Customer Analytics**: Performance tracking, credit monitoring
-   **Returns Processing**: Credit notes, return authorization

---

## Story-Based Training Scenarios

### Scenario 1: New Customer Setup

**Business Context**: You're a sales representative at PT Sarange Trading. A new corporate client wants to establish a business relationship and needs to be set up in the system.

**Story**: "PT Maju Jaya, a growing construction company, has approached your company for office furniture supplies. They have a good credit history and want to establish a credit limit of Rp 500,000,000. You need to set them up as a new customer with appropriate pricing tier and credit terms."

#### Step-by-Step Exploration

**Step 1: Access Customer Management**

-   Navigate to: `Sales > Customers`
-   Observe the customer dashboard showing current customers and their status
-   Notice the different customer categories and credit limits displayed

**Step 2: Create New Customer**

-   Click "Add New Customer"
-   Fill in basic information:
    -   **Customer Code**: CUST-001
    -   **Customer Name**: PT Maju Jaya
    -   **Customer Type**: Corporate
    -   **Industry**: Construction
    -   **Contact Person**: Bapak Ahmad Wijaya

**Step 3: Set Up Credit Management**

-   **Credit Limit**: Rp 500,000,000
-   **Payment Terms**: 30 days
-   **Credit Rating**: A (Excellent)
-   **Risk Category**: Low Risk
-   **Approval Required**: Yes (for orders above Rp 100,000,000)

**Step 4: Configure Pricing Tier**

-   **Pricing Tier**: Tier 2 (Corporate)
-   **Discount Level**: 5% standard discount
-   **Special Pricing**: Available for bulk orders
-   **Volume Discounts**: 10% for orders above Rp 200,000,000

**Step 5: Set Up Contact Information**

-   **Address**: Jl. Sudirman No. 123, Jakarta
-   **Phone**: +62-21-12345678
-   **Email**: procurement@majujaya.co.id
-   **Tax ID**: 01.234.567.8-901.000

#### Discussion Points

-   Why is proper customer categorization important?
-   How do credit limits affect order processing?
-   What factors determine pricing tier assignment?

#### Hands-On Exercise

Create 3 more customers with different credit limits, pricing tiers, and risk categories. Practice setting up various customer types and terms.

---

### Scenario 2: Sales Order Creation and Processing

**Business Context**: PT Maju Jaya has placed their first order for office furniture. You need to create a sales order and process it through the approval workflow.

**Story**: "PT Maju Jaya has requested 20 premium office chairs (CHR-001) and 10 executive desks (DSK-001) for their new office. The total order value is Rp 150,000,000, which requires manager approval. You need to create the sales order and route it for approval."

#### Step-by-Step Exploration

**Step 1: Access Sales Orders**

-   Navigate to: `Sales > Orders`
-   Click "New Sales Order"
-   Select customer: PT Maju Jaya

**Step 2: Create Order Header**

-   **Order Number**: SO-2024-001
-   **Order Date**: Today's date
-   **Delivery Date**: 7 days from today
-   **Payment Terms**: 30 days
-   **Sales Rep**: Your name
-   **Reference**: Customer PO: MJ-2024-001

**Step 3: Add Order Lines**

-   **Line 1**:
    -   Item: CHR-001 (Premium Office Chair - Model A)
    -   Quantity: 20 units
    -   Unit Price: Rp 2,500,000
    -   Line Total: Rp 50,000,000
-   **Line 2**:
    -   Item: DSK-001 (Executive Desk)
    -   Quantity: 10 units
    -   Unit Price: Rp 10,000,000
    -   Line Total: Rp 100,000,000

**Step 4: Apply Pricing and Discounts**

-   **Subtotal**: Rp 150,000,000
-   **Tier 2 Discount (5%)**: Rp 7,500,000
-   **Volume Discount (10%)**: Rp 14,250,000
-   **Total Discount**: Rp 21,750,000
-   **Net Amount**: Rp 128,250,000

**Step 5: Submit for Approval**

-   Review order details
-   Check credit limit availability
-   Submit for manager approval
-   Note approval workflow status

#### Discussion Points

-   How does the pricing tier system work?
-   What triggers approval requirements?
-   How are discounts calculated and applied?

#### Hands-On Exercise

Create orders for different customers with various pricing tiers, discount levels, and approval requirements. Practice with different product combinations.

---

### Scenario 3: Order Approval and Credit Management

**Business Context**: You're a sales manager reviewing orders that require approval. You need to assess credit limits and approve or reject orders.

**Story**: "The sales order from PT Maju Jaya is pending your approval. You need to review their credit limit, current outstanding balance, and order details to make an approval decision."

#### Step-by-Step Exploration

**Step 1: Access Approval Queue**

-   Navigate to: `Sales > Approvals`
-   Review pending orders requiring approval
-   Click on SO-2024-001 for detailed review

**Step 2: Review Customer Credit Status**

-   **Customer**: PT Maju Jaya
-   **Credit Limit**: Rp 500,000,000
-   **Current Outstanding**: Rp 0
-   **Available Credit**: Rp 500,000,000
-   **Order Value**: Rp 128,250,000
-   **Credit Utilization**: 25.65%

**Step 3: Assess Order Details**

-   Review order lines and pricing
-   Verify discount calculations
-   Check delivery terms and conditions
-   Confirm payment terms

**Step 4: Make Approval Decision**

-   **Decision**: Approve
-   **Reason**: Order within credit limit, good customer history
-   **Conditions**: Standard payment terms apply
-   **Approval Date**: Today's date

**Step 5: Process Approval**

-   Click "Approve Order"
-   Add approval comments
-   Notify sales representative
-   Update order status

#### Discussion Points

-   What factors influence approval decisions?
-   How do you handle orders exceeding credit limits?
-   What documentation is needed for approvals?

#### Hands-On Exercise

Practice approving and rejecting orders based on different credit scenarios. Learn to handle edge cases and special circumstances.

---

### Scenario 4: Delivery Processing and Fulfillment

**Business Context**: The approved sales order is ready for delivery. You need to process the delivery, update inventory, and generate delivery documents.

**Story**: "The warehouse has confirmed that all items for SO-2024-001 are available and ready for delivery. You need to process the delivery, update stock levels, and generate delivery documents for the customer."

#### Step-by-Step Exploration

**Step 1: Access Delivery Processing**

-   Navigate to: `Sales > Deliveries`
-   Click "New Delivery"
-   Select order: SO-2024-001

**Step 2: Process Delivery**

-   **Delivery Number**: DEL-2024-001
-   **Delivery Date**: Today's date
-   **Delivery Method**: Company truck
-   **Driver**: Bapak Budi
-   **Vehicle**: Truck-001

**Step 3: Confirm Items and Quantities**

-   **CHR-001**: 20 units (confirmed available)
-   **DSK-001**: 10 units (confirmed available)
-   **Total Value**: Rp 128,250,000
-   **Delivery Address**: Jl. Sudirman No. 123, Jakarta

**Step 4: Generate Delivery Documents**

-   Print delivery note
-   Generate packing list
-   Create delivery receipt
-   Update order status to "Delivered"

**Step 5: Update Inventory**

-   System automatically updates stock levels
-   Records cost of goods sold
-   Updates inventory valuation
-   Generates movement transactions

#### Discussion Points

-   How does delivery processing affect inventory?
-   What documents are required for delivery?
-   How is cost of goods sold calculated?

#### Hands-On Exercise

Process deliveries for multiple orders, practice with different delivery methods, and learn to handle partial deliveries.

---

### Scenario 5: Sales Commission Tracking

**Business Context**: You need to track sales performance and commission calculations for the sales team.

**Story**: "The month has ended, and you need to calculate sales commissions for your team. You've achieved sales of Rp 500,000,000 this month, which qualifies for a 3% commission rate. You need to review your performance and commission calculation."

#### Step-by-Step Exploration

**Step 1: Access Commission Tracking**

-   Navigate to: `Sales > Commissions`
-   Review your sales performance dashboard
-   Check current month's sales summary

**Step 2: Review Sales Performance**

-   **Total Sales**: Rp 500,000,000
-   **Number of Orders**: 15 orders
-   **Average Order Value**: Rp 33,333,333
-   **Commission Rate**: 3%
-   **Commission Amount**: Rp 15,000,000

**Step 3: Analyze Performance Metrics**

-   **Sales Target**: Rp 400,000,000
-   **Target Achievement**: 125%
-   **Bonus Eligibility**: Yes (above 120%)
-   **Bonus Amount**: Rp 2,500,000

**Step 4: Review Commission Details**

-   **Base Commission**: Rp 15,000,000
-   **Performance Bonus**: Rp 2,500,000
-   **Total Commission**: Rp 17,500,000
-   **Payment Date**: Next payroll cycle

**Step 5: Generate Commission Report**

-   Print commission statement
-   Review detailed breakdown
-   Submit for payroll processing

#### Discussion Points

-   How are commission rates determined?
-   What factors affect commission calculations?
-   How do performance bonuses work?

#### Hands-On Exercise

Practice calculating commissions for different sales scenarios, including various commission rates and bonus structures.

---

## Advanced Features Exploration

### Customer Credit Management

**Credit Limit Monitoring**

-   **Scenario**: Monitor customer credit utilization
-   **Exercise**: Review credit status for multiple customers
-   **Question**: How do you handle customers approaching credit limits?

**Credit Risk Assessment**

-   **Scenario**: Evaluate customer payment history
-   **Exercise**: Review payment patterns and risk indicators
-   **Question**: What factors indicate credit risk?

### Pricing Strategy Management

**Dynamic Pricing**

-   **Scenario**: Adjust prices based on market conditions
-   **Exercise**: Update pricing for specific customer segments
-   **Question**: When should pricing be adjusted?

**Volume Discounts**

-   **Scenario**: Set up tiered volume discounts
-   **Exercise**: Configure discount structures
-   **Question**: How do volume discounts affect profitability?

### Sales Analytics and Reporting

**Sales Performance Dashboard**

-   **Purpose**: Track sales team performance
-   **Exercise**: Generate monthly sales report
-   **Analysis**: Identify top performers and improvement areas

**Customer Analytics**

-   **Purpose**: Analyze customer behavior and profitability
-   **Exercise**: Generate customer profitability report
-   **Analysis**: Identify most valuable customers

**Product Performance**

-   **Purpose**: Track product sales and margins
-   **Exercise**: Generate product performance report
-   **Analysis**: Identify best-selling and most profitable products

---

## Assessment Questions

### Knowledge Check

1. **What information is required to set up a new customer?**
2. **How does the pricing tier system work?**
3. **What triggers order approval requirements?**
4. **How are sales commissions calculated?**
5. **What happens to inventory when a delivery is processed?**

### Practical Exercises

1. **Set up a new customer** with proper credit limits and pricing tier
2. **Create a sales order** with multiple line items and discounts
3. **Process order approval** based on credit assessment
4. **Handle delivery processing** and inventory updates
5. **Calculate sales commissions** and generate reports

### Scenario-Based Questions

1. **A customer wants to place an order exceeding their credit limit. How do you handle this?**
2. **A delivery is damaged during transport. What steps do you take?**
3. **A customer requests a price reduction after order approval. How do you process this?**
4. **You need to process a partial delivery due to stock shortages. What's the procedure?**
5. **A customer wants to return goods after delivery. How do you handle the return?**

---

## Troubleshooting Common Issues

### Issue 1: Order Approval Delays

**Symptoms**: Orders stuck in approval queue
**Causes**:

-   Missing approver assignments
-   Credit limit exceeded
-   Incomplete order information

**Solutions**:

1. Check approver assignments
2. Review credit limits and outstanding balances
3. Complete missing order information
4. Escalate to management if needed

### Issue 2: Pricing Calculation Errors

**Symptoms**: Incorrect pricing or discounts applied
**Causes**:

-   Wrong pricing tier assigned
-   Incorrect discount configuration
-   System calculation errors

**Solutions**:

1. Verify customer pricing tier
2. Check discount configuration
3. Review pricing rules
4. Contact system administrator if needed

### Issue 3: Delivery Processing Issues

**Symptoms**: Delivery not updating inventory
**Causes**:

-   Incomplete delivery information
-   System integration issues
-   Inventory availability problems

**Solutions**:

1. Complete all delivery fields
2. Check inventory availability
3. Verify system integration
4. Process manual inventory update if needed

---

## Best Practices

### Customer Management

-   **Maintain accurate customer data** and update regularly
-   **Monitor credit limits** and payment patterns
-   **Set appropriate pricing tiers** based on customer value
-   **Document all customer interactions** and agreements

### Order Processing

-   **Verify inventory availability** before order confirmation
-   **Apply correct pricing** and discount structures
-   **Follow approval workflows** for credit and pricing
-   **Maintain accurate order documentation**

### Delivery Management

-   **Confirm delivery details** before processing
-   **Generate proper documentation** for all deliveries
-   **Update inventory immediately** after delivery
-   **Handle exceptions** promptly and professionally

### Performance Tracking

-   **Monitor sales performance** regularly
-   **Track commission calculations** accurately
-   **Analyze customer profitability** and trends
-   **Use analytics** for decision making

---

## Module Completion Checklist

-   [ ] Successfully set up new customers with proper credit limits
-   [ ] Created sales orders with multiple line items and pricing
-   [ ] Processed order approvals based on credit assessment
-   [ ] Handled delivery processing and inventory updates
-   [ ] Calculated sales commissions and performance metrics
-   [ ] Generated sales reports and analytics
-   [ ] Understood pricing tier and discount systems
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions

---

## Next Steps

After completing this module, participants should:

1. **Practice daily sales operations** in the system
2. **Review module materials** for reference
3. **Prepare for Module 3**: Purchase Management
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear concepts

---

_This module provides comprehensive training on sales management in Sarange ERP. Participants should feel confident in their ability to manage customers, process orders, and track sales performance effectively._
