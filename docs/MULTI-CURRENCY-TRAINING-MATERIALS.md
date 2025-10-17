# Multi-Currency Training Materials

## Training Overview

This document provides comprehensive training materials for the multi-currency functionality in Sarange ERP. It includes hands-on exercises, scenarios, and assessment questions.

## Training Objectives

By the end of this training, participants will be able to:

1. **Understand Multi-Currency Concepts**

    - Explain the role of base currency (IDR)
    - Identify supported foreign currencies
    - Understand exchange rate management

2. **Manage Exchange Rates**

    - Set daily exchange rates
    - Create manual exchange rate entries
    - Verify exchange rate accuracy

3. **Process Multi-Currency Transactions**

    - Create foreign currency Purchase Orders
    - Process foreign currency Sales Orders
    - Enter multi-currency Journal Entries

4. **Generate Multi-Currency Reports**
    - Interpret Trial Balance with currency information
    - Analyze GL Detail reports with foreign amounts
    - Understand FX gain/loss calculations

## Training Modules

### Module 1: Multi-Currency Fundamentals (30 minutes)

#### Learning Objectives

-   Understand base currency concept
-   Identify supported currencies
-   Learn exchange rate basics

#### Key Concepts

-   **Base Currency**: IDR (Indonesian Rupiah) is the reporting currency
-   **Foreign Currencies**: All other supported currencies
-   **Exchange Rate**: Rate used to convert foreign currency to IDR
-   **Dual Currency**: Transactions recorded in both currencies

#### Hands-On Exercise 1: Currency Overview

1. Navigate to **Accounting → Currencies**
2. Review the list of supported currencies
3. Identify which currency is marked as "Base Currency"
4. Note the decimal places for each currency

**Expected Result**: IDR should be the only base currency, other currencies show their decimal places.

### Module 2: Exchange Rate Management (45 minutes)

#### Learning Objectives

-   Set daily exchange rates
-   Create manual exchange rate entries
-   Understand rate types and sources

#### Key Concepts

-   **Daily Rates**: Standard exchange rates for a specific date
-   **Manual Rates**: Custom exchange rates for special transactions
-   **Inverse Rates**: Automatic calculation of reverse currency pairs
-   **Effective Date**: Date when the rate becomes valid

#### Hands-On Exercise 2: Setting Daily Exchange Rates

**Scenario**: Set exchange rates for October 17, 2025

1. Navigate to **Accounting → Exchange Rates → Daily Rates**
2. Set effective date to today's date
3. Enter the following rates:
    - USD: 16,500.00
    - SGD: 12,200.00
    - EUR: 17,800.00
4. Click **Save Daily Rates**
5. Verify that inverse rates are created automatically

**Expected Result**: Both IDR→USD and USD→IDR rates should be created.

#### Hands-On Exercise 3: Manual Exchange Rate Entry

**Scenario**: Create a custom rate for a special transaction

1. Go to **Accounting → Exchange Rates → Create**
2. Select From Currency: IDR
3. Select To Currency: USD
4. Enter Rate: 16,600.00
5. Set Rate Type: Manual
6. Set Source: Special Transaction
7. Save the rate

**Assessment Question**: What happens when you create an IDR→USD rate?

### Module 3: Multi-Currency Purchase Orders (60 minutes)

#### Learning Objectives

-   Create foreign currency Purchase Orders
-   Understand dual currency calculations
-   Process foreign currency line items

#### Key Concepts

-   **Currency Selection**: Choose foreign currency for the entire order
-   **Exchange Rate**: Auto-populated from current rates
-   **Foreign Prices**: Enter prices in the selected currency
-   **IDR Conversion**: Automatic calculation of IDR equivalents

#### Hands-On Exercise 4: Foreign Currency Purchase Order

**Scenario**: Create a USD Purchase Order for imported goods

**Setup**:

-   Supplier: ABC Import Co.
-   Currency: USD
-   Exchange Rate: 16,500.00
-   Items: 2 units @ $100 each

**Steps**:

1. Navigate to **Purchase → Purchase Orders → Create**
2. Fill in basic order information
3. Select supplier: ABC Import Co.
4. **Select Currency**: USD
5. Verify **Exchange Rate**: 16,500.00
6. Add line item:
    - Quantity: 2
    - Unit Price (Foreign): 100.00
    - Description: Imported Product
7. Review the calculated amounts:
    - Foreign Total: $200.00
    - IDR Total: Rp 3,300,000.00
8. Save the Purchase Order

**Assessment Questions**:

-   What is the IDR equivalent of $200 at rate 16,500?
-   Why does the system show both foreign and IDR amounts?

#### Hands-On Exercise 5: Mixed Currency Understanding

**Scenario**: Compare IDR vs USD Purchase Orders

1. Create another Purchase Order with IDR currency
2. Use the same items and quantities
3. Compare the forms and calculations
4. Note the differences in display

**Expected Result**: IDR orders show 1.000000 exchange rate, USD orders show foreign currency calculations.

### Module 4: Multi-Currency Sales Orders (45 minutes)

#### Learning Objectives

-   Create foreign currency Sales Orders
-   Process customer orders in foreign currency
-   Understand sales pricing in foreign currency

#### Hands-On Exercise 6: Foreign Currency Sales Order

**Scenario**: Create a USD Sales Order for export customer

**Setup**:

-   Customer: XYZ Export Ltd.
-   Currency: USD
-   Exchange Rate: 16,500.00
-   Items: 5 units @ $150 each

**Steps**:

1. Navigate to **Sales → Sales Orders → Create**
2. Select customer: XYZ Export Ltd.
3. **Select Currency**: USD
4. Verify **Exchange Rate**: 16,500.00
5. Add line item:
    - Quantity: 5
    - Unit Price (Foreign): 150.00
    - Description: Export Product
6. Review calculations:
    - Foreign Total: $750.00
    - IDR Total: Rp 12,375,000.00
7. Save the Sales Order

**Assessment Question**: How does the system ensure accurate foreign currency pricing?

### Module 5: Multi-Currency Journal Entries (60 minutes)

#### Learning Objectives

-   Create multi-currency journal entries
-   Understand FX gain/loss calculations
-   Process currency conversion entries

#### Key Concepts

-   **Line-by-Line Currency**: Each journal line can have different currency
-   **Exchange Rate Application**: Rates applied per line item
-   **Foreign Amount Calculation**: Automatic calculation from IDR amounts
-   **Balance Validation**: Journal must balance in IDR

#### Hands-On Exercise 7: Multi-Currency Journal Entry

**Scenario**: Record a foreign currency bank deposit

**Setup**:

-   Date: Today
-   Description: USD Bank Deposit
-   Debit: Bank Account (USD) - $1,000
-   Credit: Cash Account (IDR) - Rp 16,500,000

**Steps**:

1. Navigate to **Accounting → Journals → Manual Journal Entry**
2. Enter description: "USD Bank Deposit"
3. **Line 1**:
    - Account: Bank Account
    - Currency: USD
    - Exchange Rate: 16,500.00
    - Debit: 16,500,000 (IDR)
    - Foreign Debit: 1,000.00 (USD)
4. **Line 2**:
    - Account: Cash Account
    - Currency: IDR
    - Exchange Rate: 1.000000
    - Credit: 16,500,000
5. Verify journal is balanced
6. Post the journal

**Assessment Questions**:

-   Why do we need to specify currency for each line?
-   How does the system ensure the journal balances?

#### Hands-On Exercise 8: FX Gain/Loss Journal Entry

**Scenario**: Record FX gain on foreign currency revaluation

**Setup**:

-   USD account revaluation
-   Original balance: $1,000 at 16,000 rate = Rp 16,000,000
-   New rate: 16,500
-   New value: $1,000 at 16,500 rate = Rp 16,500,000
-   FX Gain: Rp 500,000

**Steps**:

1. Create journal entry for FX gain
2. Debit: USD Account - Rp 500,000
3. Credit: Realized FX Gain/Loss - Rp 500,000
4. Post the journal

### Module 6: Multi-Currency Reporting (45 minutes)

#### Learning Objectives

-   Interpret Trial Balance with currency information
-   Analyze GL Detail reports with foreign amounts
-   Understand currency reporting concepts

#### Hands-On Exercise 9: Trial Balance Review

1. Navigate to **Reports → Trial Balance**
2. Set date to today
3. Review the report columns:
    - Code, Name, Currencies, Debit (IDR), Credit (IDR), Balance (IDR)
4. Identify accounts with multiple currencies
5. Note how currency information is displayed

**Assessment Question**: What does the "Currencies" column tell you?

#### Hands-On Exercise 10: GL Detail Analysis

1. Go to **Reports → GL Detail**
2. Set date range to include your test transactions
3. Review the expanded columns:
    - Date, Journal, Account, Currency, Debit (IDR), Credit (IDR), Debit (FC), Credit (FC), Rate, Memo
4. Find your multi-currency transactions
5. Verify exchange rates and foreign amounts

**Assessment Questions**:

-   How can you identify foreign currency transactions?
-   What information does the "Rate" column provide?

## Assessment Questions

### Knowledge Check

1. **What is the base currency in Sarange ERP?**

    - A) USD
    - B) IDR
    - C) EUR
    - D) SGD

2. **How many decimal places does JPY typically use?**

    - A) 2
    - B) 4
    - C) 0
    - D) 6

3. **When creating a foreign currency Purchase Order, where is the exchange rate entered?**

    - A) Manually for each line item
    - B) Automatically populated from current rates
    - C) Only in the header
    - D) Calculated at posting time

4. **In multi-currency journal entries, what currency is used for balance validation?**

    - A) The currency with the highest amount
    - B) IDR (base currency)
    - C) The first line's currency
    - D) The most common currency

5. **What does the "Currencies" column in Trial Balance show?**
    - A) Only the base currency
    - B) All currencies used for that account
    - C) The primary currency only
    - D) Foreign currencies only

### Practical Assessment

**Scenario**: You need to process the following transaction:

-   Import purchase from US supplier: $5,000
-   Current USD rate: 16,500
-   Payment to be made in USD
-   Record the purchase order and journal entry

**Tasks**:

1. Set up the exchange rate
2. Create the USD Purchase Order
3. Create the journal entry for payment
4. Generate reports to verify the transaction

**Evaluation Criteria**:

-   Correct exchange rate setup
-   Accurate foreign currency calculations
-   Proper journal entry structure
-   Balanced journal entries
-   Correct report interpretation

## Training Resources

### Reference Materials

-   Multi-Currency User Guide
-   Exchange Rate Management Procedures
-   Financial Reporting with Multi-Currency

### Practice Scenarios

-   Daily exchange rate updates
-   Foreign currency purchase processing
-   Multi-currency sales order management
-   FX gain/loss journal entries
-   Currency revaluation procedures

### Support Contacts

-   System Administrator: For exchange rate setup
-   Finance Manager: For FX policy questions
-   IT Support: For technical issues

## Training Completion Checklist

-   [ ] Completed all hands-on exercises
-   [ ] Passed knowledge check (80% or higher)
-   [ ] Successfully completed practical assessment
-   [ ] Reviewed all reference materials
-   [ ] Understood FX gain/loss concepts
-   [ ] Can generate and interpret multi-currency reports
-   [ ] Can troubleshoot common issues

## Follow-up Training

### Advanced Topics (Optional)

-   Currency revaluation procedures
-   FX hedging strategies
-   Multi-currency consolidation
-   Advanced reporting techniques

### Refresher Training

-   Quarterly review sessions
-   Annual system updates
-   New feature introductions
-   Best practice updates
