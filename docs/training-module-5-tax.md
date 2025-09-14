# Training Module 5: Tax Compliance

## Indonesian Tax System & Compliance Management

**Duration**: 3 hours  
**Target Audience**: Accounting staff, tax specialists, financial managers  
**Prerequisites**: Module 4 (Financial Management), understanding of Indonesian tax system

---

## Learning Objectives

By the end of this module, participants will be able to:

1. **Understand Indonesian tax system** and compliance requirements
2. **Process PPN (VAT) transactions** with automatic calculation
3. **Handle PPh (Income Tax) management** and withholding
4. **Generate tax reports** and SPT submissions
5. **Manage tax periods** and compliance monitoring
6. **Process tax transactions** from other modules (sales, purchase)
7. **Handle tax audits** and compliance documentation

---

## Module Overview

### Key Features Covered

-   **Tax Transaction Management**: PPN, PPh, and other Indonesian taxes
-   **Automatic Tax Calculation**: System-calculated tax amounts with proper rates
-   **Tax Period Management**: Monthly and annual tax period handling
-   **Tax Reporting**: SPT generation and submission
-   **Compliance Monitoring**: Tax compliance tracking and alerts
-   **Audit Trail**: Complete tax transaction history
-   **Integration**: Seamless integration with sales, purchase, and financial modules

---

## Story-Based Training Scenarios

### Scenario 1: Understanding Indonesian Tax System

**Business Context**: You're a tax specialist at PT Sarange Trading. You need to understand the Indonesian tax system and how it's implemented in the ERP system.

**Story**: "PT Sarange Trading operates in Indonesia and must comply with Indonesian tax regulations. The company deals with PPN (VAT), PPh (Income Tax), and other taxes. You need to understand the tax structure and how the system handles tax calculations and compliance."

#### Step-by-Step Exploration

**Step 1: Access Tax Management**

-   Navigate to: `Tax > Tax Management`
-   Observe the tax dashboard showing current tax status
-   Notice the different tax types and periods

**Step 2: Review Tax Types**

-   **PPN (Pajak Pertambahan Nilai)**: Value Added Tax
    -   Rate: 11% (current rate)
    -   Applies to: Sales and purchases
    -   Collection: Output tax (sales) and Input tax (purchases)
-   **PPh (Pajak Penghasilan)**: Income Tax
    -   Rate: 21-26% (corporate), 4(2) (withholding)
    -   Applies to: Income and payments
    -   Collection: Withholding tax on payments
-   **Other Taxes**: Regional taxes, customs duties

**Step 3: Understand Tax Codes**

-   **PPN Codes**:
    -   01: PPN 11% (standard rate)
    -   02: PPN 0% (exports)
    -   03: PPN Exempt (certain goods)
-   **PPh Codes**:
    -   21: Corporate income tax
    -   22: Withholding tax on purchases
    -   23: Withholding tax on services
    -   4(2): Final withholding tax

**Step 4: Review Tax Settings**

-   **Tax Period**: Monthly (PPN), Annual (PPh)
-   **Tax Office**: Jakarta Tax Office
-   **Tax ID**: 01.234.567.8-901.000
-   **Registration Date**: 1 January 2020

**Step 5: Understand Compliance Requirements**

-   **Monthly PPN**: File SPT Masa PPN by 20th of following month
-   **Annual PPh**: File SPT Tahunan PPh by 31 March
-   **Tax Payments**: Due dates for each tax type
-   **Documentation**: Required supporting documents

#### Discussion Points

-   Why is tax compliance important for businesses?
-   How does the Indonesian tax system work?
-   What are the key compliance requirements?

#### Hands-On Exercise

Explore different tax types, review tax settings, and understand the tax code structure. Practice identifying which taxes apply to different transactions.

---

### Scenario 2: PPN (VAT) Transaction Processing

**Business Context**: You need to process sales transactions with PPN calculation and understand how VAT works in the system.

**Story**: "PT Sarange Trading has made a sale of office furniture worth Rp 100,000,000 to PT Maju Jaya. The sale is subject to PPN at 11%. You need to process this transaction and understand how PPN is calculated and recorded."

#### Step-by-Step Exploration

**Step 1: Access Tax Transactions**

-   Navigate to: `Tax > Tax Transactions`
-   Click "New Tax Transaction"
-   Select transaction type: "Sales with PPN"

**Step 2: Process Sales Transaction**

-   **Transaction Date**: Today's date
-   **Customer**: PT Maju Jaya
-   **Transaction Type**: Sales
-   **Base Amount**: Rp 100,000,000
-   **Tax Code**: 01 (PPN 11%)
-   **Tax Rate**: 11%

**Step 3: Calculate PPN**

-   **Base Amount**: Rp 100,000,000
-   **PPN Amount**: Rp 11,000,000 (11% of base)
-   **Total Amount**: Rp 111,000,000
-   **Tax Type**: Output Tax (PPN Keluaran)

**Step 4: Record Tax Transaction**

-   **Transaction Number**: TXN-2024-001
-   **Tax Period**: Current month
-   **Tax Office**: Jakarta Tax Office
-   **Reference**: Sales Invoice SI-2024-001
-   **Status**: Posted

**Step 5: Generate Tax Documents**

-   **Tax Invoice**: Faktur Pajak with PPN details
-   **Tax Receipt**: Bukti Pungut PPN
-   **Tax Report**: PPN transaction report
-   **SPT Masa**: Monthly PPN report

#### Discussion Points

-   How is PPN calculated on sales transactions?
-   What is the difference between output and input tax?
-   What documents are required for PPN compliance?

#### Hands-On Exercise

Process various sales transactions with different PPN rates, practice with exports (0% PPN), and understand exempt transactions.

---

### Scenario 3: PPh (Income Tax) Management

**Business Context**: You need to process payments with PPh withholding and understand how income tax works in the system.

**Story**: "PT Sarange Trading has made a payment of Rp 50,000,000 to a consultant for professional services. This payment is subject to PPh 23 withholding tax at 2%. You need to process this payment and handle the withholding tax."

#### Step-by-Step Exploration

**Step 1: Access PPh Transactions**

-   Navigate to: `Tax > PPh Management`
-   Click "New PPh Transaction"
-   Select transaction type: "Payment with PPh 23"

**Step 2: Process Payment Transaction**

-   **Payment Date**: Today's date
-   **Vendor**: Consultant Services
-   **Payment Type**: Professional Services
-   **Gross Amount**: Rp 50,000,000
-   **PPh Code**: 23 (Withholding on services)
-   **PPh Rate**: 2%

**Step 3: Calculate PPh Withholding**

-   **Gross Amount**: Rp 50,000,000
-   **PPh Withholding**: Rp 1,000,000 (2% of gross)
-   **Net Amount**: Rp 49,000,000
-   **Tax Type**: PPh 23 (Withholding Tax)

**Step 4: Record PPh Transaction**

-   **Transaction Number**: PPH-2024-001
-   **Tax Period**: Current month
-   **Tax Office**: Jakarta Tax Office
-   **Reference**: Payment Voucher PV-2024-001
-   **Status**: Posted

**Step 5: Generate PPh Documents**

-   **Withholding Certificate**: Bukti Potong PPh 23
-   **PPh Receipt**: Bukti Setor PPh
-   **PPh Report**: Monthly PPh report
-   **SPT Masa**: Monthly PPh report

#### Discussion Points

-   How is PPh withholding calculated?
-   What are the different PPh rates and when do they apply?
-   How do you handle PPh remittance to tax office?

#### Hands-On Exercise

Process various payment transactions with different PPh rates, practice with PPh 4(2) final withholding, and understand corporate income tax.

---

### Scenario 4: Tax Period Management

**Business Context**: You need to manage tax periods and understand the monthly and annual tax cycle.

**Story**: "The month has ended, and you need to close the tax period for PPN and PPh. You need to review all tax transactions, calculate totals, and prepare for tax reporting and payment."

#### Step-by-Step Exploration

**Step 1: Access Tax Period Management**

-   Navigate to: `Tax > Tax Periods`
-   Select current period: January 2024
-   Review period status and transactions

**Step 2: Review PPN Period**

-   **Period**: January 2024
-   **Status**: Open
-   **Output Tax**: Rp 55,000,000
-   **Input Tax**: Rp 33,000,000
-   **Net PPN**: Rp 22,000,000 (payable)
-   **Due Date**: 20 February 2024

**Step 3: Review PPh Period**

-   **Period**: January 2024
-   **Status**: Open
-   **PPh 21**: Rp 15,000,000
-   **PPh 22**: Rp 8,000,000
-   **PPh 23**: Rp 5,000,000
-   **Total PPh**: Rp 28,000,000

**Step 4: Close Tax Period**

-   Review all transactions for accuracy
-   Calculate final tax amounts
-   Close period for reporting
-   Update period status to "Closed"

**Step 5: Prepare Tax Reports**

-   **SPT Masa PPN**: Monthly PPN report
-   **SPT Masa PPh**: Monthly PPh report
-   **Tax Payment**: Prepare payment to tax office
-   **Documentation**: Organize supporting documents

#### Discussion Points

-   What is the tax period cycle?
-   How do you ensure accurate tax period closing?
-   What reports are required for each period?

#### Hands-On Exercise

Practice tax period management, review period transactions, and prepare tax reports. Learn to handle period adjustments and corrections.

---

### Scenario 5: Tax Reporting and SPT Generation

**Business Context**: You need to generate tax reports and SPT submissions for tax office compliance.

**Story**: "The tax period has closed, and you need to generate the SPT Masa PPN and SPT Masa PPh reports for submission to the tax office. These reports must be accurate and complete for compliance."

#### Step-by-Step Exploration

**Step 1: Access Tax Reporting**

-   Navigate to: `Tax > Tax Reports`
-   Select report type: "SPT Masa PPN"
-   Set period: January 2024

**Step 2: Generate SPT Masa PPN**

-   **Form**: SPT Masa PPN 1111
-   **Period**: January 2024
-   **Taxpayer**: PT Sarange Trading
-   **Tax Office**: Jakarta Tax Office
-   **Output Tax**: Rp 55,000,000
-   **Input Tax**: Rp 33,000,000
-   **Net PPN**: Rp 22,000,000

**Step 3: Generate SPT Masa PPh**

-   **Form**: SPT Masa PPh 21
-   **Period**: January 2024
-   **Taxpayer**: PT Sarange Trading
-   **Tax Office**: Jakarta Tax Office
-   **PPh 21**: Rp 15,000,000
-   **PPh 22**: Rp 8,000,000
-   **PPh 23**: Rp 5,000,000
-   **Total PPh**: Rp 28,000,000

**Step 4: Review and Validate Reports**

-   Check all amounts and calculations
-   Verify tax codes and rates
-   Ensure all transactions are included
-   Validate report format and structure

**Step 5: Submit Tax Reports**

-   **Submission Method**: Online (e-Filing)
-   **Submission Date**: Before due date
-   **Confirmation**: Receive submission confirmation
-   **Payment**: Process tax payment if applicable

#### Discussion Points

-   What information is required for SPT reports?
-   How do you ensure report accuracy?
-   What are the submission requirements?

#### Hands-On Exercise

Generate various tax reports, practice with different tax periods, and understand the submission process. Learn to handle report corrections and amendments.

---

## Advanced Features Exploration

### Tax Compliance Monitoring

**Compliance Dashboard**

-   **Scenario**: Monitor tax compliance status
-   **Exercise**: Review compliance dashboard and alerts
-   **Question**: How do you ensure ongoing compliance?

**Compliance Alerts**

-   **Scenario**: Set up compliance alerts
-   **Exercise**: Configure alerts for due dates and thresholds
-   **Question**: What alerts are most important?

### Tax Audit Support

**Audit Trail**

-   **Scenario**: Prepare for tax audit
-   **Exercise**: Generate audit trail reports
-   **Question**: What documentation is needed for audits?

**Tax Documentation**

-   **Scenario**: Organize tax documents
-   **Exercise**: Create tax document library
-   **Question**: How do you maintain tax records?

### Tax Integration

**Sales Integration**

-   **Purpose**: Automatic PPN calculation on sales
-   **Exercise**: Process sales with automatic tax calculation
-   **Analysis**: Verify tax calculation accuracy

**Purchase Integration**

-   **Purpose**: Automatic PPh withholding on purchases
-   **Exercise**: Process purchases with automatic tax calculation
-   **Analysis**: Verify withholding tax accuracy

---

## Assessment Questions

### Knowledge Check

1. **What are the main types of Indonesian taxes?**
2. **How is PPN calculated on sales transactions?**
3. **What is PPh withholding and when does it apply?**
4. **What are the tax period requirements?**
5. **What reports are required for tax compliance?**

### Practical Exercises

1. **Process sales transactions** with PPN calculation
2. **Handle payment transactions** with PPh withholding
3. **Manage tax periods** and closing procedures
4. **Generate tax reports** and SPT submissions
5. **Monitor tax compliance** and alerts

### Scenario-Based Questions

1. **A sales transaction has incorrect PPN calculation. How do you correct this?**
2. **A payment exceeds PPh withholding threshold. What steps do you take?**
3. **Tax period closing reveals discrepancies. How do you handle this?**
4. **SPT submission is rejected by tax office. What's the correction process?**
5. **Tax audit requires additional documentation. How do you prepare?**

---

## Troubleshooting Common Issues

### Issue 1: Incorrect Tax Calculation

**Symptoms**: Tax amounts don't match expected calculations
**Causes**:

-   Wrong tax rate selected
-   Incorrect tax code
-   System configuration issues

**Solutions**:

1. Verify tax rate and code selection
2. Check system tax configuration
3. Review calculation logic
4. Process correction entry if needed

### Issue 2: Tax Period Closing Errors

**Symptoms**: Period won't close due to errors
**Causes**:

-   Unbalanced transactions
-   Missing tax data
-   System validation errors

**Solutions**:

1. Review all transactions for accuracy
2. Check for missing tax data
3. Verify system validation rules
4. Contact system administrator if needed

### Issue 3: SPT Submission Failures

**Symptoms**: SPT reports fail to submit
**Causes**:

-   Incorrect report format
-   Missing required data
-   System integration issues

**Solutions**:

1. Verify report format and structure
2. Check for missing required data
3. Review system integration settings
4. Contact tax office support if needed

---

## Best Practices

### Tax Transaction Processing

-   **Always verify tax calculations** before posting
-   **Use correct tax codes** for different transaction types
-   **Maintain accurate tax documentation** for all transactions
-   **Process tax transactions promptly** to avoid delays

### Tax Period Management

-   **Close tax periods on time** to meet compliance requirements
-   **Review all transactions** before period closing
-   **Maintain accurate tax records** for audit purposes
-   **Monitor compliance status** regularly

### Tax Reporting

-   **Generate reports accurately** with all required information
-   **Submit reports on time** to avoid penalties
-   **Maintain supporting documentation** for all reports
-   **Review reports** before submission

### Compliance Monitoring

-   **Monitor compliance status** continuously
-   **Set up alerts** for due dates and thresholds
-   **Maintain audit trail** for all tax activities
-   **Stay updated** on tax regulation changes

---

## Module Completion Checklist

-   [ ] Successfully understood Indonesian tax system structure
-   [ ] Processed PPN transactions with automatic calculation
-   [ ] Handled PPh withholding transactions
-   [ ] Managed tax periods and closing procedures
-   [ ] Generated tax reports and SPT submissions
-   [ ] Monitored tax compliance and alerts
-   [ ] Understood tax integration with other modules
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions

---

## Next Steps

After completing this module, participants should:

1. **Practice daily tax operations** in the system
2. **Review module materials** for reference
3. **Prepare for Module 6**: Fixed Asset Management
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear concepts

---

_This module provides comprehensive training on Indonesian tax compliance in Sarange ERP. Participants should feel confident in their ability to process tax transactions, manage compliance, and generate accurate tax reports._
