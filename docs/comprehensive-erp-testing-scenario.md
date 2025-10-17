# Comprehensive ERP System Testing Scenario

**Purpose**: Complete end-to-end testing of Sarange ERP system to validate all modules and ensure proper journal entries are generated throughout the business cycle.

**Date**: 2025-01-22
**Tester**: AI Assistant
**Goal**: Validate complete business cycle with proper accounting integration

## Testing Overview

This comprehensive testing scenario covers all major ERP modules in a realistic Indonesian trading company business cycle:

1. **Inventory Management** - Product setup and warehouse management
2. **Business Partner Management** - Customer and supplier setup
3. **Purchase Cycle** - PO → GRPO → PI → PP
4. **Sales Cycle** - SO → DO → SI → SR
5. **Fixed Assets Management** - Asset acquisition and depreciation
6. **Accounting Integration** - Journal entries and financial reporting

## Test Data Setup

### Company Profile

-   **Company**: PT Sarang Trading Indonesia
-   **Business Type**: Trading Company (Electronics & Office Supplies)
-   **Location**: Jakarta, Indonesia
-   **Currency**: Indonesian Rupiah (IDR)

### Test Scenarios

#### Scenario 1: Electronics Trading Cycle

-   **Product**: Laptop Dell Inspiron 15 (ITEM001)
-   **Supplier**: PT Makmur Jaya Electronics
-   **Customer**: PT Maju Bersama Solutions
-   **Warehouse**: Main Warehouse
-   **Transaction Value**: Rp 15,000,000

#### Scenario 2: Office Supplies Trading Cycle

-   **Product**: Printer Canon PIXMA (ITEM002)
-   **Supplier**: PT Office Solutions
-   **Customer**: PT Digital Office
-   **Warehouse**: Branch Warehouse
-   **Transaction Value**: Rp 8,500,000

#### Scenario 3: Fixed Asset Acquisition

-   **Asset**: Office Furniture Set
-   **Supplier**: PT Furniture Indonesia
-   **Asset Category**: Office Equipment
-   **Value**: Rp 25,000,000

## Testing Steps

### Phase 1: System Preparation

1. Access ERP system at http://localhost:8000
2. Login with admin credentials
3. Verify system modules are accessible
4. Check database connectivity

### Phase 2: Inventory Management Testing

1. **Product Categories Setup**

    - Create Electronics category
    - Create Office Supplies category
    - Verify account mappings

2. **Inventory Items Creation**

    - Create Laptop Dell Inspiron 15
    - Create Printer Canon PIXMA
    - Set pricing and warehouse locations

3. **Warehouse Management**
    - Verify warehouse setup
    - Check stock levels
    - Test warehouse transfers

### Phase 3: Business Partner Management Testing

1. **Supplier Setup**

    - Create PT Makmur Jaya Electronics
    - Create PT Office Solutions
    - Create PT Furniture Indonesia
    - Set payment terms and credit limits

2. **Customer Setup**
    - Create PT Maju Bersama Solutions
    - Create PT Digital Office
    - Set credit limits and pricing tiers

### Phase 4: Purchase Cycle Testing

1. **Purchase Order Creation**

    - Create PO for Laptop Dell Inspiron 15
    - Create PO for Printer Canon PIXMA
    - Verify calculations and approvals

2. **Goods Receipt PO (GRPO)**

    - Process GRPO for both POs
    - Verify inventory updates
    - Check journal entries

3. **Purchase Invoice Processing**

    - Create Purchase Invoices
    - Verify tax calculations (PPN 11%)
    - Check journal entries

4. **Purchase Payment Processing**
    - Process Purchase Payments
    - Verify cash account updates
    - Check journal entries

### Phase 5: Sales Cycle Testing

1. **Sales Order Creation**

    - Create SO for Laptop Dell Inspiron 15
    - Create SO for Printer Canon PIXMA
    - Verify calculations and approvals

2. **Delivery Order Processing**

    - Process Delivery Orders
    - Verify inventory updates
    - Check journal entries

3. **Sales Invoice Processing**

    - Create Sales Invoices
    - Verify tax calculations (PPN 11%)
    - Check journal entries

4. **Sales Receipt Processing**
    - Process Sales Receipts
    - Verify cash account updates
    - Check journal entries

### Phase 6: Fixed Assets Management Testing

1. **Asset Acquisition**

    - Create Fixed Asset for Office Furniture
    - Process acquisition journal entries
    - Verify asset registration

2. **Depreciation Processing**
    - Run depreciation calculation
    - Verify depreciation journal entries
    - Check asset valuation

### Phase 7: Accounting Integration Validation

1. **Journal Entry Review**

    - Review all generated journal entries
    - Verify debit/credit balancing
    - Check account mappings

2. **Financial Reports**

    - Generate Trial Balance
    - Generate Profit & Loss Statement
    - Generate Balance Sheet

3. **Account Statements**
    - Generate Business Partner statements
    - Generate GL Account statements
    - Verify running balances

## Expected Journal Entries

### Purchase Cycle Journal Entries

1. **GRPO Processing**

    - Debit: Inventory (1.1.3.01) - Rp 15,000,000
    - Credit: AP UnInvoice (2.1.1.03) - Rp 15,000,000

2. **Purchase Invoice**

    - Debit: AP UnInvoice (2.1.1.03) - Rp 15,000,000
    - Credit: Utang Dagang (2.1.1.01) - Rp 15,000,000
    - Debit: PPN Masukan (1.1.2.01) - Rp 1,650,000
    - Credit: Utang Dagang (2.1.1.01) - Rp 1,650,000

3. **Purchase Payment**
    - Debit: Utang Dagang (2.1.1.01) - Rp 16,650,000
    - Credit: Kas di Bank (1.1.1.02) - Rp 16,650,000

### Sales Cycle Journal Entries

1. **Delivery Order**

    - Debit: AR UnInvoice (1.1.2.04) - Rp 15,000,000
    - Credit: Penjualan Kredit (4.1.1.02) - Rp 15,000,000
    - Debit: HPP (5.1.1.01) - Rp 12,000,000
    - Credit: Inventory (1.1.3.01) - Rp 12,000,000

2. **Sales Invoice**

    - Debit: AR UnInvoice (1.1.2.04) - Rp 15,000,000
    - Credit: Piutang Dagang (1.1.2.01) - Rp 15,000,000
    - Debit: Piutang Dagang (1.1.2.01) - Rp 1,650,000
    - Credit: PPN Keluaran (2.1.2.01) - Rp 1,650,000

3. **Sales Receipt**
    - Debit: Kas di Bank (1.1.1.02) - Rp 16,650,000
    - Credit: Piutang Dagang (1.1.2.01) - Rp 16,650,000

## Success Criteria

1. **Functional Requirements**

    - All modules accessible and functional
    - Complete business cycle executable
    - Proper data validation and error handling

2. **Accounting Requirements**

    - All journal entries properly generated
    - Debit/Credit balancing maintained
    - Account mappings correct
    - Tax calculations accurate (PPN 11%)

3. **Integration Requirements**
    - Inventory updates correctly
    - Business partner balances accurate
    - Financial reports generated properly
    - Document numbering sequential

## Testing Results Documentation

Each test step will be documented with:

-   Test execution status (Pass/Fail)
-   Issues encountered
-   Journal entries generated
-   Account balances affected
-   Recommendations for improvements

## Conclusion

This comprehensive testing scenario validates the complete ERP system functionality and ensures proper accounting integration throughout the business cycle. The testing will confirm that all modules work together seamlessly and generate accurate journal entries for proper financial reporting.
