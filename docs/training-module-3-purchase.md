# Training Module 3: Purchase Management

## Supplier Management & Procurement Processes

**Duration**: 3 hours  
**Target Audience**: Purchasing team, procurement managers, warehouse staff  
**Prerequisites**: Module 1 (Inventory Management), basic procurement knowledge

---

## Learning Objectives

By the end of this module, participants will be able to:

1. **Manage supplier master data** and performance tracking
2. **Create and process purchase orders** with proper approval workflows
3. **Handle supplier comparison** and selection processes
4. **Process goods receipts** and manage quality control
5. **Track supplier performance** and analytics
6. **Generate procurement reports** and cost analysis
7. **Handle purchase returns** and supplier disputes

---

## Module Overview

### Key Features Covered

-   **Supplier Management**: Master data, performance tracking, risk assessment
-   **Purchase Order Processing**: Order creation, approval workflows, line management
-   **Supplier Comparison**: Cost analysis, performance evaluation, selection criteria
-   **Goods Receipt Management**: Receipt processing, quality control, discrepancy handling
-   **Supplier Analytics**: Performance monitoring, cost optimization, risk management
-   **Procurement Reporting**: Cost analysis, supplier performance, purchase trends
-   **Returns Processing**: Return authorization, supplier credit notes

---

## Story-Based Training Scenarios

### Scenario 1: New Supplier Setup

**Business Context**: You're a procurement manager at PT Sarange Trading. A new supplier has approached your company and needs to be evaluated and set up in the system.

**Story**: "OfficeMax Indonesia, a new office furniture supplier, has submitted their credentials and wants to become a preferred supplier. They offer competitive pricing and have good references. You need to evaluate them and set them up in the system with proper performance tracking."

#### Step-by-Step Exploration

**Step 1: Access Supplier Management**

-   Navigate to: `Purchase > Suppliers`
-   Observe the supplier dashboard showing current suppliers and their performance
-   Notice the different supplier categories and performance ratings

**Step 2: Create New Supplier**

-   Click "Add New Supplier"
-   Fill in basic information:
    -   **Supplier Code**: SUPP-001
    -   **Supplier Name**: OfficeMax Indonesia
    -   **Supplier Type**: Manufacturer
    -   **Industry**: Office Furniture
    -   **Contact Person**: Bapak Surya Wijaya

**Step 3: Set Up Supplier Details**

-   **Business Registration**: PT OfficeMax Indonesia
-   **Tax ID**: 01.234.567.8-901.000
-   **Address**: Jl. Industri No. 456, Tangerang
    -   **Phone**: +62-21-87654321
    -   **Email**: sales@officemax.co.id
    -   **Website**: www.officemax.co.id

**Step 4: Configure Performance Tracking**

-   **Payment Terms**: 30 days
-   **Delivery Lead Time**: 7 days
-   **Quality Rating**: A (Excellent)
-   **Risk Category**: Low Risk
-   **Performance Weight**: 25% (Cost), 30% (Quality), 25% (Delivery), 20% (Service)

**Step 5: Set Up Product Categories**

-   **Primary Categories**: Office Furniture, Office Supplies
-   **Specializations**: Premium Office Chairs, Executive Desks
-   **Certifications**: ISO 9001, FSC Certified
-   **Minimum Order Value**: Rp 10,000,000

#### Discussion Points

-   Why is supplier evaluation important?
-   What factors determine supplier performance ratings?
-   How do you assess supplier risk?

#### Hands-On Exercise

Create 3 more suppliers with different specialties, performance ratings, and risk categories. Practice setting up various supplier types and terms.

---

### Scenario 2: Purchase Order Creation and Processing

**Business Context**: You need to create a purchase order for office furniture based on inventory requirements and supplier evaluation.

**Story**: "The warehouse has identified low stock for premium office chairs (CHR-001) and executive desks (DSK-001). After evaluating suppliers, OfficeMax Indonesia offers the best combination of price, quality, and delivery. You need to create a purchase order for 50 chairs and 20 desks."

#### Step-by-Step Exploration

**Step 1: Access Purchase Orders**

-   Navigate to: `Purchase > Orders`
-   Click "New Purchase Order"
-   Select supplier: OfficeMax Indonesia

**Step 2: Create Order Header**

-   **Order Number**: PO-2024-001
-   **Order Date**: Today's date
-   **Delivery Date**: 7 days from today
-   **Payment Terms**: 30 days
-   **Buyer**: Your name
-   **Reference**: Internal Req: REQ-2024-001

**Step 3: Add Order Lines**

-   **Line 1**:
    -   Item: CHR-001 (Premium Office Chair - Model A)
    -   Quantity: 50 units
    -   Unit Price: Rp 2,500,000
    -   Line Total: Rp 125,000,000
-   **Line 2**:
    -   Item: DSK-001 (Executive Desk)
    -   Quantity: 20 units
    -   Unit Price: Rp 10,000,000
    -   Line Total: Rp 200,000,000

**Step 4: Calculate Order Totals**

-   **Subtotal**: Rp 325,000,000
-   **Freight**: Rp 5,000,000
-   **Handling**: Rp 2,000,000
-   **Tax (PPN 11%)**: Rp 36,520,000
-   **Total Amount**: Rp 368,520,000

**Step 5: Submit for Approval**

-   Review order details
-   Check budget availability
-   Submit for manager approval
-   Note approval workflow status

#### Discussion Points

-   How do you determine optimal order quantities?
-   What factors influence supplier selection?
-   How are freight and handling costs calculated?

#### Hands-On Exercise

Create orders for different suppliers with various product combinations, freight terms, and approval requirements. Practice with different pricing scenarios.

---

### Scenario 3: Supplier Comparison and Selection

**Business Context**: You need to compare multiple suppliers for a large office furniture purchase to ensure the best value and quality.

**Story**: "You're planning a major office renovation and need to purchase 100 premium office chairs. Three suppliers have submitted quotes: OfficeMax Indonesia, Furniture Plus, and Office Solutions. You need to compare them using the system's supplier comparison tools."

#### Step-by-Step Exploration

**Step 1: Access Supplier Comparison**

-   Navigate to: `Purchase > Supplier Comparison`
-   Click "New Comparison"
-   Select comparison type: "Product Comparison"

**Step 2: Set Up Comparison Criteria**

-   **Product**: CHR-001 (Premium Office Chair - Model A)
-   **Quantity**: 100 units
-   **Delivery Date**: 14 days from today
-   **Quality Requirements**: Premium grade, 5-year warranty

**Step 3: Add Supplier Quotes**

-   **Supplier 1**: OfficeMax Indonesia
    -   Unit Price: Rp 2,500,000
    -   Total Price: Rp 250,000,000
    -   Delivery: 7 days
    -   Quality Rating: A
-   **Supplier 2**: Furniture Plus
    -   Unit Price: Rp 2,400,000
    -   Total Price: Rp 240,000,000
    -   Delivery: 10 days
    -   Quality Rating: B+
-   **Supplier 3**: Office Solutions
    -   Unit Price: Rp 2,600,000
    -   Total Price: Rp 260,000,000
    -   Delivery: 5 days
    -   Quality Rating: A

**Step 4: Analyze Comparison Results**

-   **Cost Analysis**: Furniture Plus offers lowest price
-   **Quality Analysis**: OfficeMax and Office Solutions have best quality
-   **Delivery Analysis**: Office Solutions offers fastest delivery
-   **Overall Score**: OfficeMax Indonesia (85%), Office Solutions (82%), Furniture Plus (78%)

**Step 5: Make Selection Decision**

-   **Selected Supplier**: OfficeMax Indonesia
-   **Reason**: Best balance of price, quality, and delivery
-   **Alternative**: Office Solutions (if faster delivery needed)

#### Discussion Points

-   What factors are most important in supplier selection?
-   How do you balance cost vs. quality vs. delivery?
-   When should you consider alternative suppliers?

#### Hands-On Exercise

Practice supplier comparisons for different products and scenarios. Learn to use various comparison criteria and weighting systems.

---

### Scenario 4: Goods Receipt Processing

**Business Context**: The purchase order has been delivered, and you need to process the goods receipt, verify quantities and quality, and update inventory.

**Story**: "OfficeMax Indonesia has delivered the office furniture order. The delivery includes 50 chairs and 20 desks. You need to process the goods receipt, verify the items against the purchase order, and update inventory levels."

#### Step-by-Step Exploration

**Step 1: Access Goods Receipt**

-   Navigate to: `Purchase > Goods Receipt`
-   Click "New Receipt"
-   Select purchase order: PO-2024-001

**Step 2: Process Receipt Header**

-   **Receipt Number**: GR-2024-001
-   **Receipt Date**: Today's date
-   **Supplier**: OfficeMax Indonesia
-   **Delivery Method**: Company truck
-   **Driver**: Bapak Andi
-   **Vehicle**: Truck-002

**Step 3: Verify Received Items**

-   **CHR-001**: 50 units (ordered: 50, received: 50) ✓
-   **DSK-001**: 20 units (ordered: 20, received: 20) ✓
-   **Total Value**: Rp 325,000,000
-   **Quality Check**: All items in good condition

**Step 4: Process Receipt**

-   Confirm all items received
-   Verify quantities match purchase order
-   Check quality and condition
-   Process receipt and update inventory

**Step 5: Generate Receipt Documents**

-   Print goods receipt note
-   Generate receiving report
-   Create quality inspection report
-   Update purchase order status

#### Discussion Points

-   What checks are required during goods receipt?
-   How do you handle quantity discrepancies?
-   What documentation is needed for receipt processing?

#### Hands-On Exercise

Process goods receipts for multiple purchase orders, practice with different scenarios including partial deliveries and quality issues.

---

### Scenario 5: Supplier Performance Tracking

**Business Context**: You need to review supplier performance for the quarter and identify areas for improvement or recognition.

**Story**: "The quarter has ended, and you need to evaluate the performance of your key suppliers. OfficeMax Indonesia has been your primary furniture supplier, and you want to assess their performance across cost, quality, delivery, and service metrics."

#### Step-by-Step Exploration

**Step 1: Access Supplier Performance**

-   Navigate to: `Purchase > Supplier Performance`
-   Select supplier: OfficeMax Indonesia
-   Set period: Last quarter

**Step 2: Review Performance Metrics**

-   **Cost Performance**: 92% (within budget)
-   **Quality Performance**: 96% (excellent quality)
-   **Delivery Performance**: 88% (mostly on time)
-   **Service Performance**: 94% (responsive service)
-   **Overall Score**: 92.5%

**Step 3: Analyze Performance Trends**

-   **Cost Trend**: Stable pricing, 2% increase
-   **Quality Trend**: Consistent high quality
-   **Delivery Trend**: Improved from 85% to 88%
-   **Service Trend**: Excellent response times

**Step 4: Identify Improvement Areas**

-   **Strengths**: Quality and service excellence
-   **Areas for Improvement**: Delivery timeliness
-   **Recommendations**: Work on delivery scheduling
-   **Action Plan**: Monthly delivery review meetings

**Step 5: Generate Performance Report**

-   Print supplier performance report
-   Document recommendations
-   Schedule performance review meeting
-   Update supplier rating if needed

#### Discussion Points

-   How do you measure supplier performance objectively?
-   What actions result from performance reviews?
-   How do you improve supplier relationships?

#### Hands-On Exercise

Practice evaluating supplier performance for different suppliers and time periods. Learn to identify trends and improvement opportunities.

---

## Advanced Features Exploration

### Supplier Risk Management

**Risk Assessment**

-   **Scenario**: Evaluate supplier financial stability
-   **Exercise**: Review supplier credit ratings and payment history
-   **Question**: How do you assess supplier risk?

**Risk Mitigation**

-   **Scenario**: Develop risk mitigation strategies
-   **Exercise**: Create backup supplier plans
-   **Question**: What strategies reduce supplier risk?

### Cost Optimization

**Price Analysis**

-   **Scenario**: Analyze price trends and market conditions
-   **Exercise**: Compare prices across suppliers and time periods
-   **Question**: How do you optimize procurement costs?

**Volume Discounts**

-   **Scenario**: Negotiate better terms with suppliers
-   **Exercise**: Calculate volume discount benefits
-   **Question**: When should you consolidate purchases?

### Procurement Analytics

**Spend Analysis**

-   **Purpose**: Analyze procurement spending patterns
-   **Exercise**: Generate spend analysis report
-   **Analysis**: Identify cost optimization opportunities

**Supplier Analytics**

-   **Purpose**: Track supplier performance and trends
-   **Exercise**: Generate supplier analytics dashboard
-   **Analysis**: Identify best performing suppliers

**Purchase Trends**

-   **Purpose**: Monitor purchase patterns and seasonality
-   **Exercise**: Generate purchase trends report
-   **Analysis**: Plan for seasonal variations

---

## Assessment Questions

### Knowledge Check

1. **What information is required to set up a new supplier?**
2. **How does the supplier comparison system work?**
3. **What factors influence supplier selection decisions?**
4. **How are goods receipts processed and verified?**
5. **What metrics are used to track supplier performance?**

### Practical Exercises

1. **Set up a new supplier** with proper performance tracking
2. **Create a purchase order** with multiple line items and approvals
3. **Compare suppliers** using the comparison tools
4. **Process goods receipt** and verify quantities
5. **Evaluate supplier performance** and generate reports

### Scenario-Based Questions

1. **A supplier delivers goods with quality issues. How do you handle this?**
2. **A purchase order exceeds the budget. What steps do you take?**
3. **A supplier requests payment terms changes. How do you evaluate this?**
4. **You need to process a partial delivery due to supplier constraints. What's the procedure?**
5. **A supplier's performance has declined. How do you address this?**

---

## Troubleshooting Common Issues

### Issue 1: Purchase Order Approval Delays

**Symptoms**: Orders stuck in approval queue
**Causes**:

-   Missing approver assignments
-   Budget exceeded
-   Incomplete order information

**Solutions**:

1. Check approver assignments
2. Review budget availability
3. Complete missing order information
4. Escalate to management if needed

### Issue 2: Goods Receipt Discrepancies

**Symptoms**: Quantities don't match purchase order
**Causes**:

-   Supplier shipping errors
-   Damage during transport
-   Incorrect order processing

**Solutions**:

1. Verify actual received quantities
2. Check for damaged goods
3. Process adjustments as needed
4. Contact supplier for resolution

### Issue 3: Supplier Performance Issues

**Symptoms**: Poor supplier performance metrics
**Causes**:

-   Quality problems
-   Delivery delays
-   Service issues

**Solutions**:

1. Review performance data
2. Identify root causes
3. Schedule performance review
4. Implement improvement plan

---

## Best Practices

### Supplier Management

-   **Maintain accurate supplier data** and update regularly
-   **Monitor supplier performance** continuously
-   **Develop strong supplier relationships** for long-term success
-   **Document all supplier interactions** and agreements

### Purchase Processing

-   **Verify requirements** before creating orders
-   **Compare suppliers** for best value
-   **Follow approval workflows** for budget control
-   **Maintain accurate order documentation**

### Goods Receipt

-   **Verify deliveries** against purchase orders
-   **Check quality** and condition of goods
-   **Process receipts promptly** to maintain accuracy
-   **Handle discrepancies** professionally and quickly

### Performance Management

-   **Track supplier performance** regularly
-   **Analyze trends** and identify improvements
-   **Provide feedback** to suppliers
-   **Use analytics** for decision making

---

## Module Completion Checklist

-   [ ] Successfully set up new suppliers with proper performance tracking
-   [ ] Created purchase orders with multiple line items and approvals
-   [ ] Compared suppliers using the comparison tools
-   [ ] Processed goods receipts and verified quantities
-   [ ] Evaluated supplier performance and generated reports
-   [ ] Understood supplier selection and evaluation processes
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions

---

## Next Steps

After completing this module, participants should:

1. **Practice daily procurement operations** in the system
2. **Review module materials** for reference
3. **Prepare for Module 4**: Financial Management
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear concepts

---

_This module provides comprehensive training on purchase management in Sarange ERP. Participants should feel confident in their ability to manage suppliers, process purchases, and track performance effectively._
