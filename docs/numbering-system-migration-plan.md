# Legacy Format to Entity-Aware Numbering Migration Plan

**Date**: 2025-01-21  
**Status**: Planning Phase  
**Estimated Duration**: 5-7 days

---

## Executive Summary

This document outlines the plan to migrate the remaining 6 legacy format documents (`PREFIX-YYYYMM-######`) to the new entity-aware numbering system (`EEYYDDNNNNN`). The migration will complete the standardization of document numbering across the entire ERP system.

---

## Documents to Migrate

| Document Type           | Current Format      | Proposed Format | Priority | Complexity |
| ----------------------- | ------------------- | --------------- | -------- | ---------- |
| Purchase Payment (PP)   | `PP-202509-000001`  | `71250400001`   | **HIGH** | Low        |
| Sales Receipt (SR)      | `SR-202509-000001`  | `72250900001`   | **HIGH** | Low        |
| Asset Disposal (DIS)    | `DIS-202509-000001` | `71251000001`   | Medium   | Medium     |
| Cash Expense (CEV)      | `CEV-202509-000001` | `71251100001`   | Medium   | Medium     |
| Journal (JNL)           | `JNL-202509-000001` | `71251200001`   | Medium   | High       |
| Account Statement (AST) | `AST-202509-000001` | `71251300001`   | Low      | High       |

---

## Recommended Document Codes

Based on existing entity document code pattern (`01-08`), we recommend the following codes:

| Document Type     | Document Code | Rationale                                |
| ----------------- | ------------- | ---------------------------------------- |
| Purchase Payment  | `04`          | Sequential after Purchase Invoice (`03`) |
| Sales Receipt     | `09`          | Sequential after Sales Invoice (`08`)    |
| Asset Disposal    | `10`          | Fixed Assets module                      |
| Cash Expense      | `11`          | Expense transactions                     |
| Journal           | `12`          | Manual/adjusting entries                 |
| Account Statement | `13`          | Reporting documents                      |

**Alternative Consideration**: If we want to keep codes within single digits (0-9), we could use:

-   Purchase Payment: `04`
-   Sales Receipt: `09`
-   Asset Disposal: `05` (if Sales Quotation `05` is not used)
-   Cash Expense: Use `99` for miscellaneous
-   Journal: Use `00` for manual entries
-   Account Statement: Use `98` for reporting

**Recommendation**: Use two-digit codes (`04`, `09`, `10`, `11`, `12`, `13`) for clarity and future expansion.

---

## Current State Analysis

### ✅ Already Entity-Ready

#### 1. Purchase Payment (PP)

-   ✅ Has `company_entity_id` field in database
-   ✅ Has `companyEntity()` relationship in model
-   ✅ Controller already passes `company_entity_id` to numbering service
-   ✅ Migration Status: **READY FOR NUMBERING MIGRATION**

**Action Required**:

-   Add to `ENTITY_DOCUMENT_CODES` with code `04`
-   Update controller to ensure entity context is always provided
-   Test entity inheritance from Purchase Invoice

#### 2. Sales Receipt (SR)

-   ✅ Has `company_entity_id` field in database
-   ✅ Has `companyEntity()` relationship in model
-   ✅ Controller already uses `company_entity_id`
-   ✅ Migration Status: **READY FOR NUMBERING MIGRATION**

**Action Required**:

-   Add to `ENTITY_DOCUMENT_CODES` with code `09`
-   Update controller to ensure entity context is always provided
-   Test entity inheritance from Sales Invoice

### ⚠️ Requires Entity Field Addition

#### 3. Asset Disposal (DIS)

-   ❌ No `company_entity_id` field currently
-   ✅ Links to `Asset` model
-   ✅ Asset can link to `PurchaseInvoice` which has `company_entity_id`
-   ⚠️ Migration Status: **NEEDS SCHEMA UPDATE**

**Recommendation**:

-   Add `company_entity_id` to `asset_disposals` table
-   Inherit entity from Asset → PurchaseInvoice → company_entity_id
-   Fallback to default entity if asset has no purchase invoice
-   Add to `ENTITY_DOCUMENT_CODES` with code `10`

**Business Logic**:

```php
// Asset Disposal entity resolution
if ($asset->purchaseInvoice && $asset->purchaseInvoice->company_entity_id) {
    $entityId = $asset->purchaseInvoice->company_entity_id;
} else {
    $entityId = $companyEntityService->getDefaultEntity()->id;
}
```

#### 4. Cash Expense (CEV)

-   ❌ No `company_entity_id` field currently
-   ⚠️ Migration Status: **NEEDS SCHEMA UPDATE**

**Recommendation**:

-   Add `company_entity_id` to `cash_expenses` table
-   Use default entity for manual cash expenses
-   Add to `ENTITY_DOCUMENT_CODES` with code `11`

**Business Logic**:

-   Default to system default entity
-   Can be overridden in UI if needed in future

### 🔄 Complex Cases Requiring Special Handling

#### 5. Journal (JNL)

-   ❌ No `company_entity_id` field currently
-   ✅ Has polymorphic `source_type` and `source_id` relationships
-   ⚠️ Migration Status: **NEEDS SCHEMA UPDATE + LOGIC ENHANCEMENT**

**Recommendation**:

-   Add `company_entity_id` to `journals` table
-   Inherit entity from source document when available:
    -   `App\Models\Accounting\PurchaseInvoice` → use its `company_entity_id`
    -   `App\Models\Accounting\SalesInvoice` → use its `company_entity_id`
    -   `App\Models\Accounting\PurchasePayment` → use its `company_entity_id`
    -   `App\Models\Accounting\SalesReceipt` → use its `company_entity_id`
    -   Other source types → use default entity
-   Add to `ENTITY_DOCUMENT_CODES` with code `12`

**Business Logic**:

```php
// Journal entity resolution
if ($payload['source_type'] && $payload['source_id']) {
    $sourceModel = $payload['source_type']::find($payload['source_id']);
    if ($sourceModel && isset($sourceModel->company_entity_id)) {
        $entityId = $sourceModel->company_entity_id;
    } else {
        $entityId = $companyEntityService->getDefaultEntity()->id;
    }
} else {
    // Manual journal - use default entity
    $entityId = $companyEntityService->getDefaultEntity()->id;
}
```

#### 6. Account Statement (AST)

-   ❌ No `company_entity_id` field currently
-   ⚠️ Can be for GL Accounts (entity-agnostic) or Business Partners (entity-agnostic)
-   ⚠️ Migration Status: **NEEDS DECISION ON SCOPE**

**Recommendation Options**:

**Option A**: Add entity support, use default entity

-   Add `company_entity_id` to `account_statements` table
-   Always use default entity (account statements are reporting documents, not transactional)
-   Add to `ENTITY_DOCUMENT_CODES` with code `13`
-   **Pros**: Consistent with other documents
-   **Cons**: May not provide value since statements are already filtered by account/partner

**Option B**: Keep legacy format for Account Statements

-   Account statements are reporting/analytical documents, not transactional
-   They aggregate data from multiple sources which may span entities
-   **Pros**: Avoids unnecessary complexity
-   **Cons**: Inconsistent numbering format

**Recommendation**: **Option A** - Add entity support but default to primary entity. This maintains consistency and allows future entity-specific statement filtering if needed.

---

## Migration Action Plan

### Phase 1: High Priority Documents (2-3 days)

#### Step 1.1: Purchase Payment Migration

1. Add `'purchase_payment' => '04'` to `ENTITY_DOCUMENT_CODES`
2. Verify controller always passes `company_entity_id`
3. Test entity inheritance from Purchase Invoice
4. Update documentation

#### Step 1.2: Sales Receipt Migration

1. Add `'sales_receipt' => '09'` to `ENTITY_DOCUMENT_CODES`
2. Verify controller always passes `company_entity_id`
3. Test entity inheritance from Sales Invoice
4. Update documentation

**Deliverables**:

-   ✅ PP and SR using entity format
-   ✅ Test cases passing
-   ✅ Updated numbering documentation

---

### Phase 2: Schema Updates (1-2 days)

#### Step 2.1: Asset Disposal Schema Update

1. Create migration to add `company_entity_id` to `asset_disposals` table
2. Update `AssetDisposal` model with:
    - Add `company_entity_id` to fillable
    - Add `companyEntity()` relationship
3. Update `AssetDisposalController`:
    - Resolve entity from Asset → PurchaseInvoice
    - Pass entity to numbering service
4. Update views to show entity (optional)
5. Test entity inheritance

#### Step 2.2: Cash Expense Schema Update

1. Create migration to add `company_entity_id` to `cash_expenses` table
2. Update `CashExpense` model with:
    - Add `company_entity_id` to fillable
    - Add `companyEntity()` relationship
3. Update `CashExpenseController`:
    - Use default entity
    - Pass entity to numbering service
4. Update views to show entity (optional)
5. Test default entity assignment

**Deliverables**:

-   ✅ Database migrations applied
-   ✅ Models updated
-   ✅ Controllers updated
-   ✅ Test cases passing

---

### Phase 3: Complex Documents (2 days)

#### Step 3.1: Journal Migration

1. Create migration to add `company_entity_id` to `journals` table
2. Update `Journal` model with:
    - Add `company_entity_id` to fillable
    - Add `companyEntity()` relationship
3. Update `PostingService::postJournal()`:
    - Add entity resolution logic from source document
    - Pass entity to numbering service
    - Store entity in journal record
4. Test entity inheritance from various source types
5. Test manual journals (default entity)

#### Step 3.2: Account Statement Migration

1. **Decision Point**: Confirm Option A or Option B
2. If Option A:
    - Create migration to add `company_entity_id` to `account_statements` table
    - Update `AccountStatement` model
    - Update `AccountStatementService` to use default entity
    - Update controller to pass entity
    - Add to `ENTITY_DOCUMENT_CODES` with code `13`
3. If Option B:
    - Document decision to keep legacy format
    - No code changes needed

**Deliverables**:

-   ✅ Journal entity resolution logic
-   ✅ Account Statement decision and implementation
-   ✅ Test cases passing

---

### Phase 4: Testing & Documentation (1 day)

#### Step 4.1: Comprehensive Testing

1. Test all 6 document types with entity format
2. Test entity inheritance chains:
    - PO → PI → PP
    - SO → SI → SR
    - Asset → Disposal
    - Source documents → Journal
3. Test default entity fallback
4. Test concurrent number generation
5. Test sequence reset on year boundary

#### Step 4.2: Documentation Updates

1. Update `docs/numbering-system-analysis.md`
2. Update `docs/architecture.md`
3. Update `docs/MODULES-AND-FEATURES.md`
4. Update `MEMORY.md` with migration completion
5. Update `docs/decisions.md` if needed

**Deliverables**:

-   ✅ All tests passing
-   ✅ Documentation updated
-   ✅ Migration complete

---

## Risk Assessment & Mitigation

### Risks

| Risk                                  | Impact | Probability | Mitigation                                                                 |
| ------------------------------------- | ------ | ----------- | -------------------------------------------------------------------------- |
| Breaking existing document references | High   | Low         | Keep legacy format validation for backward compatibility during transition |
| Sequence number conflicts             | High   | Low         | Test sequence generation thoroughly; use database transactions             |
| Missing entity context                | Medium | Medium      | Implement robust default entity fallback logic                             |
| Performance impact                    | Low    | Low         | Entity resolution is simple lookup; minimal impact                         |
| Data migration complexity             | Medium | Low         | New documents only; existing documents remain unchanged                    |

### Backward Compatibility

**Important**: Existing documents with legacy format numbers will remain unchanged. Only **new documents** created after migration will use entity format.

**Recommendation**:

-   Keep legacy format validation for reading existing documents
-   Add migration script (optional) to regenerate numbers for historical documents if needed (future phase)

---

## Implementation Checklist

### Pre-Migration

-   [ ] Review and approve document codes
-   [ ] Review and approve entity resolution logic for complex documents
-   [ ] Create backup of production database (if applicable)
-   [ ] Set up test environment with sample data

### Phase 1: High Priority

-   [x] Purchase Payment: Add to ENTITY_DOCUMENT_CODES
-   [x] Purchase Payment: Verify entity context in controller
-   [x] Purchase Payment: Test and validate
-   [x] Sales Receipt: Add to ENTITY_DOCUMENT_CODES
-   [x] Sales Receipt: Verify entity context in controller
-   [x] Sales Receipt: Test and validate

### Phase 2: Schema Updates

-   [x] Asset Disposal: Create migration
-   [x] Asset Disposal: Update model
-   [x] Asset Disposal: Update controller
-   [x] Asset Disposal: Test entity inheritance
-   [x] Cash Expense: Create migration
-   [x] Cash Expense: Update model
-   [x] Cash Expense: Update controller
-   [x] Cash Expense: Test default entity

### Phase 3: Complex Documents

-   [x] Journal: Create migration
-   [x] Journal: Update model
-   [x] Journal: Update PostingService entity resolution
-   [x] Journal: Test various source types
-   [x] Account Statement: Make decision (Option A/B)
-   [x] Account Statement: Implement chosen option (Option A)
-   [x] Account Statement: Test and validate

### Phase 4: Testing & Documentation

-   [ ] Comprehensive integration testing
-   [ ] Entity inheritance chain testing
-   [ ] Default entity fallback testing
-   [ ] Update numbering-system-analysis.md
-   [ ] Update architecture.md
-   [ ] Update MODULES-AND-FEATURES.md
-   [ ] Update MEMORY.md
-   [ ] Create migration completion report

---

## Success Criteria

✅ All 6 document types successfully migrated to entity format  
✅ Entity inheritance working correctly for all document chains  
✅ Default entity fallback working for documents without source  
✅ No breaking changes to existing functionality  
✅ All tests passing  
✅ Documentation updated and accurate  
✅ Number generation thread-safe and performant

---

## Decisions Made

1. **Document Code Assignment**: ✅ **APPROVED** - Use two-digit codes (10-13) for clarity

    - Purchase Payment: `04`
    - Sales Receipt: `09`
    - Asset Disposal: `10`
    - Cash Expense: `11`
    - Journal: `12`
    - Account Statement: `13`

2. **Account Statement Entity Support**: ✅ **APPROVED** - Option A - Add entity support with default entity

    - Will add `company_entity_id` to `account_statements` table
    - Use default entity for all account statements
    - Maintains consistency with other documents

3. **Historical Document Migration**: ✅ **APPROVED** - Focus on new documents only

    - Existing documents remain with legacy format
    - Only new documents created after migration use entity format
    - No data migration required

4. **Journal Manual Entry UI**: ✅ **APPROVED** - Start with default entity; add selector in future if needed
    - Manual journals will use default entity
    - Entity selector can be added to UI in future enhancement

---

## Timeline Estimate

| Phase                            | Duration     | Dependencies     |
| -------------------------------- | ------------ | ---------------- |
| Phase 1: High Priority           | 1-2 days     | None             |
| Phase 2: Schema Updates          | 1-2 days     | Phase 1 complete |
| Phase 3: Complex Documents       | 2 days       | Phase 2 complete |
| Phase 4: Testing & Documentation | 1 day        | Phase 3 complete |
| **Total**                        | **5-7 days** | Sequential       |

---

## Next Steps

1. **Review & Approval**: Review this plan and approve document codes and approach
2. **Start Phase 1**: Begin with Purchase Payment and Sales Receipt (highest priority, lowest risk)
3. **Iterative Development**: Complete each phase before moving to next
4. **Continuous Testing**: Test after each document type migration
5. **Documentation**: Update docs as implementation progresses

---

**Document Owner**: Development Team  
**Last Updated**: 2025-01-21  
**Status**: ✅ Approved - Implementation Started
