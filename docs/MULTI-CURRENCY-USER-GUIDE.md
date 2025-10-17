# Multi-Currency User Guide

## Overview

The Sarange ERP system now supports multi-currency operations, allowing you to conduct business in multiple currencies while maintaining accurate financial records in Indonesian Rupiah (IDR) as the base currency.

## Key Features

### 1. Supported Currencies

The system supports the following currencies:

-   **IDR** (Indonesian Rupiah) - Base Currency
-   **USD** (US Dollar)
-   **SGD** (Singapore Dollar)
-   **EUR** (Euro)
-   **CNY** (Chinese Yuan)
-   **JPY** (Japanese Yen)
-   **MYR** (Malaysian Ringgit)
-   **AUD** (Australian Dollar)
-   **GBP** (British Pound)
-   **HKD** (Hong Kong Dollar)

### 2. Base Currency

-   **IDR is the base currency** for all financial reporting
-   All foreign currency amounts are automatically converted to IDR using exchange rates
-   Financial reports show amounts in IDR with currency information displayed

## How to Use Multi-Currency Features

### 1. Exchange Rate Management

#### Setting Daily Exchange Rates

1. Navigate to **Accounting → Exchange Rates → Daily Rates**
2. Enter the effective date for the rates
3. Input exchange rates for all foreign currencies
4. Click **Save Daily Rates**

#### Manual Exchange Rate Entry

1. Go to **Accounting → Exchange Rates → Create**
2. Select From Currency (usually IDR)
3. Select To Currency (foreign currency)
4. Enter the exchange rate
5. Set the effective date
6. Choose rate type (Daily/Manual/Custom)
7. Save the rate

### 2. Purchase Orders with Foreign Currency

#### Creating a Foreign Currency Purchase Order

1. Navigate to **Purchase → Purchase Orders → Create**
2. Fill in the basic order information
3. **Select Currency**: Choose the foreign currency from the dropdown
4. **Exchange Rate**: The system will auto-populate the current exchange rate
5. Add line items with foreign currency prices
6. The system will automatically calculate IDR equivalents
7. Review the dual currency totals before saving

#### Foreign Currency Line Items

-   Enter prices in the selected foreign currency
-   The system calculates IDR amounts using the exchange rate
-   Both foreign and IDR amounts are displayed in the form

### 3. Sales Orders with Foreign Currency

#### Creating a Foreign Currency Sales Order

1. Navigate to **Sales → Sales Orders → Create**
2. Select the customer and basic information
3. **Select Currency**: Choose the foreign currency
4. **Exchange Rate**: Auto-populated from current rates
5. Add line items with foreign currency selling prices
6. Review dual currency calculations
7. Save the order

### 4. Multi-Currency Journal Entries

#### Creating Foreign Currency Journal Entries

1. Go to **Accounting → Journals → Manual Journal Entry**
2. Enter the journal description and date
3. For each line item:
    - Select the account
    - **Choose Currency**: Select foreign currency if needed
    - **Exchange Rate**: Auto-populated when currency is selected
    - Enter debit/credit amounts in IDR
    - Foreign currency amounts are calculated automatically
4. Ensure the journal is balanced
5. Post the journal

#### Multi-Currency Line Items

-   Each line can have a different currency
-   Exchange rates are applied automatically
-   Foreign amounts are calculated from IDR amounts
-   All amounts are displayed in both currencies

## Financial Reporting with Multi-Currency

### 1. Trial Balance Report

-   Shows account balances in IDR
-   Displays currency information for accounts with foreign currency transactions
-   Currency column shows which currencies are used for each account

### 2. GL Detail Report

-   Shows individual transactions with currency details
-   Displays both IDR and foreign currency amounts
-   Shows exchange rates used for each transaction
-   Includes separate columns for foreign currency debits/credits

### 3. Currency Information in Reports

-   **IDR Amounts**: Primary amounts shown in Indonesian Rupiah
-   **Currency Codes**: Display which currencies are used
-   **Exchange Rates**: Historical rates used for conversions
-   **Foreign Amounts**: Original foreign currency amounts

## Foreign Exchange Gain/Loss

### 1. Automatic FX Calculation

The system automatically calculates foreign exchange gains and losses when:

-   Settling foreign currency transactions
-   Performing currency revaluations
-   Converting foreign amounts to IDR

### 2. FX Accounts

Two special accounts are created for FX tracking:

-   **Realized FX Gain/Loss** (Account: 5.2.1.01)
-   **Unrealized FX Gain/Loss** (Account: 4.2.1.01)

### 3. Currency Revaluation

1. Navigate to **Accounting → Currency Revaluations → Create**
2. Select the currency to revalue
3. Set the revaluation date
4. Review the calculated revaluation amounts
5. Post the revaluation journal entries

## Best Practices

### 1. Exchange Rate Management

-   **Update rates regularly**: Set daily exchange rates for accurate reporting
-   **Use current rates**: Always use the most recent exchange rates for new transactions
-   **Historical accuracy**: Maintain historical rates for audit trails

### 2. Transaction Entry

-   **Select correct currency**: Always choose the appropriate currency for each transaction
-   **Verify exchange rates**: Check that auto-populated rates are correct
-   **Review calculations**: Ensure foreign currency amounts are properly calculated

### 3. Reporting

-   **IDR is primary**: All financial reports show amounts in IDR
-   **Currency context**: Use currency information to understand transaction origins
-   **Audit trails**: Exchange rates are preserved for historical reporting

## Troubleshooting

### Common Issues

1. **Missing Exchange Rates**

    - Ensure exchange rates are set for the transaction date
    - Check that both directions of currency pairs exist

2. **Incorrect Calculations**

    - Verify exchange rates are current and accurate
    - Check that currency selection matches the transaction

3. **Report Display Issues**
    - Currency information may show as "IDR" for base currency transactions
    - Foreign amounts will be zero for IDR-only transactions

### Getting Help

-   Check the **Exchange Rates** page for current rates
-   Review **Currency** settings in Accounting menu
-   Contact your system administrator for exchange rate setup

## Technical Notes

-   Exchange rates support 6 decimal places for precision
-   All foreign amounts are calculated using the transaction date's exchange rate
-   Currency revaluations create automatic journal entries
-   Multi-currency data is preserved for historical reporting and audit trails
