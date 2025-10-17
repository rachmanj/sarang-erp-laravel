# Multi-Currency Feature Testing Scenario

## Test Overview
This document outlines comprehensive testing scenarios for the newly implemented multi-currency features across all ERP modules.

## Test Environment Setup
- **Base Currency**: Indonesian Rupiah (IDR)
- **Test Currencies**: USD, SGD, EUR
- **Exchange Rates**: USD = 15,500 IDR, SGD = 11,500 IDR, EUR = 17,000 IDR
- **Test Date**: Current date

## Test Scenarios

### Scenario 1: Currency Management
**Objective**: Test currency CRUD operations and validation

**Steps**:
1. Navigate to Currencies management
2. Verify existing currencies (IDR, USD, SGD, EUR, etc.)
3. Test currency creation with validation
4. Test currency editing
5. Test base currency protection (IDR cannot be deleted)

**Expected Results**:
- All seeded currencies visible
- Validation prevents duplicate currency codes
- Base currency (IDR) protected from deletion
- Proper currency symbols and decimal places

### Scenario 2: Exchange Rate Management
**Objective**: Test exchange rate entry and retrieval

**Steps**:
1. Navigate to Exchange Rates
2. Create manual exchange rate (USD to IDR = 15,600)
3. Create daily rates for multiple currencies
4. Test rate retrieval API
5. Verify rate history and filtering

**Expected Results**:
- Manual rates can be created with validation
- Daily rates can be bulk created
- Rate API returns correct exchange rates
- Rate history shows proper chronological order

### Scenario 3: Multi-Currency Purchase Order
**Objective**: Test foreign currency purchase order creation

**Steps**:
1. Navigate to Purchase Orders → Create
2. Select vendor (supplier)
3. Select currency: USD
4. Verify exchange rate auto-population (15,500)
5. Add line items with foreign currency pricing
6. Verify real-time IDR conversion
7. Submit purchase order
8. Verify database storage of both foreign and IDR amounts

**Expected Results**:
- Currency dropdown populated with active currencies
- Exchange rate auto-populated from daily rates
- Real-time calculation shows both USD and IDR amounts
- Database stores both foreign and IDR amounts correctly

### Scenario 4: Multi-Currency Sales Order
**Objective**: Test foreign currency sales order creation

**Steps**:
1. Navigate to Sales Orders → Create
2. Select customer
3. Select currency: SGD
4. Verify exchange rate auto-population (11,500)
5. Add line items with foreign currency pricing
6. Verify real-time IDR conversion
7. Submit sales order
8. Verify database storage

**Expected Results**:
- SGD currency selection works correctly
- Exchange rate auto-populated
- Real-time calculations accurate
- Database storage correct

### Scenario 5: Inventory Item Currency Tracking
**Objective**: Test inventory item currency support

**Steps**:
1. Navigate to Inventory Items
2. Create/edit inventory item
3. Set purchase currency to USD
4. Set selling currency to SGD
5. Verify currency relationships
6. Test price conversion

**Expected Results**:
- Currency fields visible and functional
- Currency relationships work correctly
- Price conversion accurate

### Scenario 6: Currency Revaluation (Basic)
**Objective**: Test currency revaluation functionality

**Steps**:
1. Navigate to Currency Revaluations
2. Create new revaluation
3. Select currency (USD)
4. Set revaluation date
5. Calculate revaluation
6. Preview unrealized gains/losses
7. Create revaluation document

**Expected Results**:
- Revaluation calculation works
- Unrealized gains/losses calculated correctly
- Revaluation document created successfully

### Scenario 7: Multi-Currency Journal Entry
**Objective**: Test journal entry with foreign currency

**Steps**:
1. Navigate to Journals → Create
2. Add journal lines with different currencies
3. Test journal balancing in both foreign and IDR
4. Submit journal entry
5. Verify currency fields in journal lines

**Expected Results**:
- Journal lines accept currency selection
- Journal balances in both foreign currency and IDR
- Currency fields stored correctly
- Posting works with multi-currency support

### Scenario 8: Exchange Rate API Testing
**Objective**: Test exchange rate API endpoints

**Steps**:
1. Test exchange rate retrieval API
2. Test with different currency pairs
3. Test with different dates
4. Test error handling for missing rates

**Expected Results**:
- API returns correct exchange rates
- Error handling works for missing rates
- Different currency pairs work correctly

## Test Data Requirements

### Currencies
- IDR (Base Currency) - Indonesian Rupiah
- USD - US Dollar
- SGD - Singapore Dollar
- EUR - Euro

### Exchange Rates (Sample)
- USD to IDR: 15,500
- SGD to IDR: 11,500
- EUR to IDR: 17,000

### Test Vendors/Customers
- PT Test Supplier (Supplier)
- PT Test Customer (Customer)

### Test Items
- Test Item 1 (Inventory Item)
- Test Service 1 (Service Item)

## Success Criteria

1. **Currency Management**: All CRUD operations work correctly
2. **Exchange Rate Management**: Rate entry and retrieval functional
3. **Purchase Orders**: Multi-currency PO creation and storage works
4. **Sales Orders**: Multi-currency SO creation and storage works
5. **Inventory Items**: Currency tracking functional
6. **Currency Revaluation**: Basic revaluation calculation works
7. **Journal Entries**: Multi-currency journal posting works
8. **API Endpoints**: Exchange rate API returns correct data
9. **Database Storage**: All currency fields stored correctly
10. **User Interface**: All currency-related UI elements functional

## Error Handling Tests

1. **Invalid Currency Selection**: Test with non-existent currency
2. **Missing Exchange Rate**: Test with no exchange rate available
3. **Invalid Exchange Rate**: Test with zero or negative rates
4. **Currency Validation**: Test with invalid currency codes
5. **Database Constraints**: Test foreign key constraint violations

## Performance Tests

1. **Currency Dropdown Loading**: Should load within 1 second
2. **Exchange Rate Retrieval**: Should retrieve within 500ms
3. **Real-time Calculations**: Should update within 200ms
4. **Database Operations**: Should complete within 2 seconds

## Browser Testing Checklist

- [ ] Currency management interface loads correctly
- [ ] Exchange rate management interface functional
- [ ] Purchase order currency selection works
- [ ] Sales order currency selection works
- [ ] Inventory item currency fields visible
- [ ] Currency revaluation interface loads
- [ ] Journal entry currency fields functional
- [ ] Exchange rate API endpoints accessible
- [ ] All forms submit without errors
- [ ] DataTables load currency data correctly
- [ ] Navigation between currency modules works
- [ ] Permission-based access control works
- [ ] Error messages display correctly
- [ ] Success messages display correctly
