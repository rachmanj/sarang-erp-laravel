# Training Module 4: Financial Management

## Multi-Dimensional Accounting & Financial Reporting

**Duration**: 3 hours  
**Target Audience**: Accounting staff, financial managers, project managers  
**Prerequisites**: Basic accounting knowledge, understanding of Indonesian accounting standards (PSAK)

---

## Learning Objectives

By the end of this module, participants will be able to:

1. **Navigate the financial management interface** and understand the Chart of Accounts
2. **Create and process journal entries** with multi-dimensional tracking
3. **Handle project-based accounting** with fund and department allocations
4. **Process financial transactions** from other modules (sales, purchase, inventory)
5. **Generate financial reports** and statements
6. **Manage month-end closing** and reconciliation processes
7. **Understand Indonesian accounting standards** and compliance requirements

---

## Module Overview

### Key Features Covered

-   **Chart of Accounts**: PSAK-compliant account structure with 118 accounts
-   **Journal Entry Processing**: Multi-dimensional posting with project/fund/department tracking
-   **Project Accounting**: Cost allocation and tracking across multiple dimensions
-   **Fund Management**: Fund-based reporting and allocation
-   **Departmental Accounting**: Cost center management and reporting
-   **Financial Reporting**: Comprehensive financial statements and analytics
-   **Month-End Processes**: Closing procedures and reconciliation

---

## Comprehensive Financial Testing Scenarios

### Scenario 1: Chart of Accounts Exploration and Setup

**Business Context**: You're setting up the financial foundation for PT Sarange Trading's comprehensive accounting system. You need to understand the Chart of Accounts structure and verify it supports all business operations.

**Story**: "PT Sarange Trading has a PSAK-compliant Chart of Accounts with 118 accounts covering all business operations. You need to explore the account structure, understand the multi-dimensional capabilities, and verify the system is ready for comprehensive financial transactions."

#### Step-by-Step Testing

**Step 1: Access Chart of Accounts**

-   Navigate to: `Finance > Chart of Accounts`
-   Verify account count: Should show 118 accounts
-   Observe hierarchical organization and account types
-   Check account codes follow 4-digit structure

**Step 2: Explore Account Categories**

-   **Assets (1000-1999)**:
    -   Current Assets: Cash, Bank, Receivables, Inventory
    -   Fixed Assets: Equipment, Furniture, Vehicles
    -   Intangible Assets: Software, Licenses
-   **Liabilities (2000-2999)**:
    -   Current Liabilities: Payables, Accrued Expenses
    -   Long-term Liabilities: Loans, Bonds
-   **Equity (3000-3999)**:
    -   Share Capital, Retained Earnings, Reserves
-   **Revenue (4000-4999)**:
    -   Sales Revenue, Service Revenue, Other Income
-   **Expenses (5000-5999)**:
    -   Cost of Goods Sold, Operating Expenses

**Step 3: Verify Key Trading Accounts**

-   **Cash Accounts**:
    -   1110 - Bank Account (Main)
    -   1120 - Petty Cash
-   **Inventory Accounts**:
    -   1210 - Raw Materials Inventory
    -   1220 - Finished Goods Inventory
-   **Receivable/Payable Accounts**:
    -   1310 - Trade Receivables
    -   2110 - Trade Payables
-   **Revenue Accounts**:
    -   4110 - Sales Revenue
    -   4120 - Service Revenue
-   **Expense Accounts**:
    -   5110 - Cost of Goods Sold
    -   5210 - Operating Expenses

**Step 4: Test Account Management**

-   Try to create a new account (if permissions allow)
-   Verify account validation rules
-   Check parent-child relationships
-   Test account posting capabilities

#### Expected Results

-   All 118 accounts visible and properly categorized
-   Account codes follow hierarchical structure
-   Multi-dimensional tracking enabled
-   PSAK compliance maintained

---

### Scenario 2: Financial Period Management

**Business Context**: You need to set up and manage financial periods for proper month-end closing and reporting. This is critical for accurate financial reporting and compliance.

**Story**: "PT Sarange Trading operates on monthly financial periods. You need to set up periods for the current year, manage period status (open/closed), and ensure proper period controls for journal entry posting."

#### Step-by-Step Testing

**Step 1: Access Period Management**

-   Navigate to: `Finance > Periods`
-   Review current period status
-   Check available periods for current year

**Step 2: Set Up Current Year Periods**

-   **Current Year**: 2024
-   **Periods to Set Up**: January through December 2024
-   **Default Status**: All periods should be OPEN initially
-   **Current Period**: Set current month as active

**Step 3: Test Period Operations**

-   **Open Period**: Ensure current month is open for posting
-   **Close Period**: Test closing previous month (if applicable)
-   **Reopen Period**: Test reopening closed period (if needed)
-   **Period Validation**: Verify period controls work

**Step 4: Verify Period Controls**

-   **Posting Control**: Confirm journals can only post to open periods
-   **Date Validation**: Test posting to closed periods (should fail)
-   **Period Status**: Verify period status affects system behavior

#### Expected Results

-   All 12 months of current year visible
-   Current month shows as OPEN
-   Previous months can be closed
-   Period controls prevent posting to closed periods

---

### Scenario 3: Manual Journal Entry Processing

**Business Context**: You need to process various types of manual journal entries to test the complete journal entry workflow and ensure data appears correctly in reports.

**Story**: "PT Sarange Trading needs to process several types of manual journal entries: asset purchases, expense allocations, revenue recognition, and adjusting entries. Each entry must be properly balanced and include multi-dimensional tracking."

#### Step-by-Step Testing

**Step 1: Access Manual Journals**

-   Navigate to: `Finance > Journals > Manual Journal`
-   Review existing journal entries
-   Check journal numbering system (JNL-YYYYMM-######)

**Step 2: Create Asset Purchase Journal**

-   **Entry Details**:
    -   Date: Current date
    -   Description: "Office Equipment Purchase"
    -   Reference: "EQ-2024-001"
-   **Journal Lines**:
    -   Line 1: Debit Equipment Account (1420) - Rp 25,000,000
    -   Line 2: Credit Bank Account (1110) - Rp 25,000,000
-   **Dimensions**:
    -   Project: Office Setup Project
    -   Fund: Capital Fund
    -   Department: Administration

**Step 3: Create Expense Allocation Journal**

-   **Entry Details**:
    -   Date: Current date
    -   Description: "Monthly Rent Allocation"
    -   Reference: "RENT-2024-001"
-   **Journal Lines**:
    -   Line 1: Debit Rent Expense (5210) - Rp 15,000,000
    -   Line 2: Credit Accrued Rent (2120) - Rp 15,000,000
-   **Dimensions**:
    -   Project: General Operations
    -   Fund: Operating Fund
    -   Department: Administration (60%), Sales (40%)

**Step 4: Create Revenue Recognition Journal**

-   **Entry Details**:
    -   Date: Current date
    -   Description: "Service Revenue Recognition"
    -   Reference: "REV-2024-001"
-   **Journal Lines**:
    -   Line 1: Debit Accounts Receivable (1310) - Rp 50,000,000
    -   Line 2: Credit Service Revenue (4120) - Rp 50,000,000
-   **Dimensions**:
    -   Project: Service Delivery Project
    -   Fund: Operating Fund
    -   Department: Sales

**Step 5: Create Multi-Line Adjusting Entry**

-   **Entry Details**:
    -   Date: Current date
    -   Description: "Month-End Adjustments"
    -   Reference: "ADJ-2024-001"
-   **Journal Lines**:
    -   Line 1: Debit Depreciation Expense (5230) - Rp 2,000,000
    -   Line 2: Debit Office Supplies Expense (5240) - Rp 1,500,000
    -   Line 3: Credit Accumulated Depreciation (1425) - Rp 2,000,000
    -   Line 4: Credit Office Supplies Payable (2130) - Rp 1,500,000
-   **Dimensions**: Various projects, funds, and departments

**Step 6: Verify Journal Entry Balance**

-   **Total Debits**: Must equal Total Credits
-   **Entry Validation**: System should validate balance before posting
-   **Posting Process**: Confirm successful posting to general ledger

#### Expected Results

-   All journal entries properly balanced
-   Automatic journal numbering (JNL-YYYYMM-######)
-   Multi-dimensional tracking applied
-   Entries posted to general ledger successfully

---

### Scenario 4: Cash Expense Management (Cash Advance)

**Business Context**: You need to process cash expenses and advances for various business operations. This tests the cash management functionality and ensures proper expense tracking.

**Story**: "PT Sarange Trading employees frequently need cash advances for business expenses like travel, supplies, and client entertainment. You need to process these cash advances and track their settlement through expense reports."

#### Step-by-Step Testing

**Step 1: Access Cash Expenses**

-   Navigate to: `Finance > Cash Expenses`
-   Review existing cash expense entries
-   Check cash expense numbering and status

**Step 2: Create Employee Cash Advance**

-   **Advance Details**:
    -   Employee: Select from employee list
    -   Amount: Rp 5,000,000
    -   Purpose: "Business Travel Advance"
    -   Date: Current date
    -   Reference: "CA-2024-001"
-   **Account Allocation**:
    -   Debit: Employee Advance Account (1320)
    -   Credit: Petty Cash Account (1120)
-   **Dimensions**:
    -   Project: Business Travel Project
    -   Fund: Operating Fund
    -   Department: Sales

**Step 3: Create Office Supplies Cash Expense**

-   **Expense Details**:
    -   Employee: Office Manager
    -   Amount: Rp 2,500,000
    -   Purpose: "Office Supplies Purchase"
    -   Date: Current date
    -   Reference: "CE-2024-001"
-   **Account Allocation**:
    -   Debit: Office Supplies Expense (5240)
    -   Credit: Petty Cash Account (1120)
-   **Dimensions**:
    -   Project: General Operations
    -   Fund: Operating Fund
    -   Department: Administration

**Step 4: Create Client Entertainment Expense**

-   **Expense Details**:
    -   Employee: Sales Manager
    -   Amount: Rp 3,000,000
    -   Purpose: "Client Entertainment"
    -   Date: Current date
    -   Reference: "CE-2024-002"
-   **Account Allocation**:
    -   Debit: Entertainment Expense (5250)
    -   Credit: Petty Cash Account (1120)
-   **Dimensions**:
    -   Project: Client Relationship Project
    -   Fund: Operating Fund
    -   Department: Sales

**Step 5: Process Cash Advance Settlement**

-   **Settlement Details**:
    -   Original Advance: Rp 5,000,000
    -   Actual Expenses: Rp 4,200,000
    -   Refund Required: Rp 800,000
-   **Settlement Entry**:
    -   Debit: Various Expense Accounts (4,200,000)
    -   Debit: Petty Cash (800,000 refund)
    -   Credit: Employee Advance Account (5,000,000)

#### Expected Results

-   Cash advances properly recorded
-   Cash expenses tracked and allocated
-   Settlement process working correctly
-   All transactions visible in cash ledger

---

### Scenario 5: Comprehensive Financial Reports Testing

**Business Context**: After processing various financial transactions, you need to verify that all data appears correctly in the key financial reports. This validates the complete financial data flow.

**Story**: "PT Sarange Trading has processed multiple transactions including journal entries, cash expenses, and period management. You need to generate and verify key financial reports to ensure data integrity and proper reporting functionality."

#### Step-by-Step Testing

**Step 1: Generate Trial Balance Report**

-   Navigate to: `Reports > Trial Balance`
-   **Report Parameters**:
    -   As of Date: Current date
    -   Include Zero Balances: Yes
-   **Verify Report Content**:
    -   All accounts with activity appear
    -   Debits equal Credits (balanced)
    -   Account codes in proper order
    -   Balances calculated correctly
-   **Key Accounts to Verify**:
    -   Bank Account (1110): Should show debit balance
    -   Equipment (1420): Should show debit balance
    -   Service Revenue (4120): Should show credit balance
    -   Various Expense Accounts: Should show debit balances

**Step 2: Generate GL Detail Report**

-   Navigate to: `Reports > GL Detail`
-   **Report Parameters**:
    -   Account: Select specific account (e.g., Bank Account)
    -   From Date: Beginning of month
    -   To Date: Current date
    -   Project: All or specific project
    -   Fund: All or specific fund
    -   Department: All or specific department
-   **Verify Report Content**:
    -   All journal lines for selected account
    -   Proper debit/credit amounts
    -   Multi-dimensional information displayed
    -   Running balances calculated
    -   Transaction references included

**Step 3: Generate Cash Ledger Report**

-   Navigate to: `Reports > Cash Ledger`
-   **Report Parameters**:
    -   Account: Bank Account or Petty Cash
    -   From Date: Beginning of month
    -   To Date: Current date
-   **Verify Report Content**:
    -   All cash transactions listed
    -   Proper transaction descriptions
    -   Running cash balance
    -   Transaction references
    -   Multi-dimensional tracking

**Step 4: Generate AR Aging Report**

-   Navigate to: `Reports > AR Aging`
-   **Report Parameters**:
    -   As of Date: Current date
    -   Customer: All customers
-   **Verify Report Content**:
    -   Customer balances by aging buckets
    -   Total receivables
    -   Overdue amounts highlighted
    -   Customer details included

**Step 5: Generate AP Aging Report**

-   Navigate to: `Reports > AP Aging`
-   **Report Parameters**:
    -   As of Date: Current date
    -   Vendor: All vendors
-   **Verify Report Content**:
    -   Vendor balances by aging buckets
    -   Total payables
    -   Overdue amounts highlighted
    -   Vendor details included

**Step 6: Test Report Export Functionality**

-   **Export Formats**: Test PDF and Excel export
-   **Report Filters**: Verify all filter options work
-   **Report Performance**: Check report generation speed
-   **Data Accuracy**: Verify exported data matches screen data

#### Expected Results

-   Trial Balance shows balanced accounts
-   GL Detail shows all transaction details
-   Cash Ledger shows proper cash flow
-   AR/AP Aging shows customer/vendor balances
-   All reports export correctly
-   Multi-dimensional data properly displayed

---

### Scenario 6: Multi-Dimensional Accounting Validation

**Business Context**: You need to verify that the multi-dimensional accounting system properly tracks costs across projects, funds, and departments, and that this data appears correctly in reports.

**Story**: "PT Sarange Trading operates multiple projects funded by different sources and managed by various departments. You need to ensure that all transactions are properly tagged with dimensions and that reporting can filter and analyze data by these dimensions."

#### Step-by-Step Testing

**Step 1: Verify Dimension Setup**

-   **Projects**: Check available projects
    -   Office Setup Project
    -   Service Delivery Project
    -   Business Travel Project
    -   Client Relationship Project
-   **Funds**: Check available funds
    -   Operating Fund
    -   Capital Fund
    -   Expansion Fund
-   **Departments**: Check available departments
    -   Administration
    -   Sales
    -   IT

**Step 2: Test Project-Based Reporting**

-   Navigate to: `Reports > GL Detail`
-   **Filter by Project**: Select "Office Setup Project"
-   **Verify Results**: Only transactions tagged with this project appear
-   **Check Totals**: Verify project-specific totals
-   **Test Multiple Projects**: Select multiple projects

**Step 3: Test Fund-Based Reporting**

-   Navigate to: `Reports > GL Detail`
-   **Filter by Fund**: Select "Capital Fund"
-   **Verify Results**: Only capital-related transactions appear
-   **Check Fund Utilization**: Verify fund usage tracking
-   **Test Fund Performance**: Analyze fund efficiency

**Step 4: Test Department-Based Reporting**

-   Navigate to: `Reports > GL Detail`
-   **Filter by Department**: Select "Sales Department"
-   **Verify Results**: Only sales-related transactions appear
-   **Check Department Costs**: Verify cost allocation
-   **Test Department Performance**: Analyze department efficiency

**Step 5: Test Cross-Dimensional Analysis**

-   **Project + Fund**: Filter by specific project and fund combination
-   **Project + Department**: Filter by project and department
-   **Fund + Department**: Filter by fund and department
-   **All Dimensions**: Filter by all three dimensions simultaneously

**Step 6: Verify Dimension Data Integrity**

-   **Missing Dimensions**: Check for transactions without dimensions
-   **Invalid Dimensions**: Verify dimension references are valid
-   **Dimension Totals**: Ensure dimension totals match overall totals
-   **Dimension Reports**: Generate dimension-specific reports

#### Expected Results

-   All dimensions properly configured
-   Filtering works correctly for all dimensions
-   Cross-dimensional analysis functional
-   Dimension data integrity maintained
-   Reports show accurate dimensional breakdowns

---

## Advanced Features Exploration

### Financial Reporting

**Balance Sheet Generation**

-   **Scenario**: Generate monthly balance sheet
-   **Exercise**: Create balance sheet with multi-dimensional breakdown
-   **Question**: How do dimensions affect balance sheet presentation?

**Income Statement**

-   **Scenario**: Generate monthly income statement
-   **Exercise**: Create income statement by project and department
-   **Question**: How do you analyze profitability by dimension?

**Cash Flow Statement**

-   **Scenario**: Generate cash flow statement
-   **Exercise**: Analyze cash flow by fund and project
-   **Question**: How do you manage cash flow across dimensions?

### Month-End Closing

**Closing Procedures**

-   **Scenario**: Perform month-end closing
-   **Exercise**: Execute closing procedures step by step
-   **Question**: What controls ensure accurate closing?

**Reconciliation**

-   **Scenario**: Reconcile accounts and dimensions
-   **Exercise**: Perform reconciliation procedures
-   **Question**: How do you handle reconciliation discrepancies?

### Multi-Dimensional Analysis

**Cross-Dimensional Reporting**

-   **Purpose**: Analyze data across multiple dimensions
-   **Exercise**: Generate reports combining project, fund, and department
-   **Analysis**: Identify trends and patterns across dimensions

**Dimension Performance**

-   **Purpose**: Track performance by dimension
-   **Exercise**: Generate performance reports by dimension
-   **Analysis**: Identify best and worst performing dimensions

---

## Assessment Questions

### Knowledge Check

1. **What are the main account types in the Chart of Accounts?**
2. **How does multi-dimensional accounting work?**
3. **What information is required for journal entry processing?**
4. **How are project costs tracked and allocated?**
5. **What controls ensure financial data accuracy?**

### Practical Exercises

1. **Navigate the Chart of Accounts** and understand account structure
2. **Create journal entries** with multi-dimensional tracking
3. **Track project costs** and generate project reports
4. **Allocate costs** to departments and funds
5. **Generate financial reports** with dimensional breakdown

### Scenario-Based Questions

1. **A journal entry is out of balance. How do you identify and fix the error?**
2. **A project has exceeded its budget. What steps do you take?**
3. **Costs need to be reallocated between departments. How do you process this?**
4. **A fund has insufficient balance for a planned expense. How do you handle this?**
5. **Month-end closing reveals discrepancies. What's the reconciliation process?**

---

## Troubleshooting Common Issues

### Issue 1: Journal Entry Imbalance

**Symptoms**: Entry won't post due to imbalance
**Causes**:

-   Incorrect debit/credit amounts
-   Missing journal lines
-   Calculation errors

**Solutions**:

1. Verify all debit and credit amounts
2. Check for missing journal lines
3. Recalculate totals
4. Review account codes

### Issue 2: Dimension Allocation Errors

**Symptoms**: Costs not allocated to correct dimensions
**Causes**:

-   Incorrect dimension selection
-   Missing dimension data
-   System configuration issues

**Solutions**:

1. Verify dimension selections
2. Check dimension data completeness
3. Review system configuration
4. Process correction entries if needed

### Issue 3: Reporting Discrepancies

**Symptoms**: Reports don't match expected results
**Causes**:

-   Incorrect report parameters
-   Data integrity issues
-   Calculation errors

**Solutions**:

1. Verify report parameters
2. Check data integrity
3. Review calculation logic
4. Contact system administrator if needed

---

## Best Practices

### Journal Entry Processing

-   **Always verify entry balance** before posting
-   **Use proper account codes** and descriptions
-   **Include all required dimensions** for tracking
-   **Maintain supporting documentation** for all entries

### Project Accounting

-   **Set up projects properly** with clear budgets and timelines
-   **Track costs regularly** to monitor budget performance
-   **Allocate costs accurately** to ensure proper reporting
-   **Review project performance** monthly

### Fund Management

-   **Monitor fund balances** regularly
-   **Ensure proper fund allocation** for all transactions
-   **Plan fund requirements** for future periods
-   **Generate fund reports** for management review

### Departmental Accounting

-   **Use consistent allocation methods** for shared costs
-   **Review allocation rules** periodically
-   **Generate departmental reports** regularly
-   **Analyze department performance** for improvement

---

## Module Completion Checklist

-   [ ] Successfully navigated the Chart of Accounts structure
-   [ ] Created journal entries with multi-dimensional tracking
-   [ ] Tracked project costs and generated project reports
-   [ ] Allocated costs to departments and funds
-   [ ] Generated financial reports with dimensional breakdown
-   [ ] Understood multi-dimensional accounting concepts
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions

---

## Next Steps

After completing this module, participants should:

1. **Practice daily financial operations** in the system
2. **Review module materials** for reference
3. **Prepare for Module 5**: Tax Compliance
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear concepts

---

_This module provides comprehensive training on financial management in Sarange ERP. Participants should feel confident in their ability to process financial transactions, track costs across dimensions, and generate accurate financial reports._
