# Document Numbering System Analysis

**Last Updated**: 2025-01-21  
**Status**: Production-Ready with Dual Format Support

## Executive Summary

Sarang ERP implements a sophisticated dual-format document numbering system designed to support both legacy single-entity operations and modern multi-entity trading company requirements. The system provides centralized, thread-safe number generation with automatic sequence management and comprehensive document type support.

---

## Numbering Formats Overview

### 1. Entity-Aware Format: `EEYYDDNNNNN`

**Purpose**: Support multiple legal entities (PT Cahaya Sarange Jaya `71`, CV Cahaya Saranghae `72`) with entity-specific numbering sequences.

**Format Breakdown**:

-   `EE` (2 digits): Entity code extracted from company entity (e.g., `71`, `72`)
-   `YY` (2 digits): Last 2 digits of the year (e.g., `25` for 2025)
-   `DD` (2 digits): Document type code (e.g., `01` for Purchase Orders)
-   `NNNNN` (5 digits): Zero-padded sequence number (e.g., `00001`, `00002`)

**Example Numbers**:

-   `71250100001` - PT CSJ Purchase Order #1 in 2025
-   `72250700001` - CV CS Delivery Order #1 in 2025
-   `71250300001` - PT CSJ Purchase Invoice #1 in 2025

**Supported Document Types**:
| Document Type | Code | Entity Format | Example |
|--------------|------|---------------|---------|
| Purchase Order | `01` | ✅ | `71250100001` |
| Goods Receipt PO (GRPO) | `02` | ✅ | `71250200001` |
| Purchase Invoice | `03` | ✅ | `71250300001` |
| Purchase Payment | `04` | ✅ | `71250400001` |
| Sales Order | `06` | ✅ | `72250600001` |
| Delivery Order | `07` | ✅ | `72250700001` |
| Sales Invoice | `08` | ✅ | `72250800001` |
| Sales Receipt | `09` | ✅ | `72250900001` |
| Asset Disposal | `10` | ✅ | `71251000001` |
| Cash Expense | `11` | ✅ | `71251100001` |
| Journal | `12` | ✅ | `71251200001` |
| Account Statement | `13` | ✅ | `71251300001` |

**Sequence Management**:

-   Per-entity, per-document-type, per-year tracking
-   Sequence resets annually (January 1st)
-   Unique composite key: `(company_entity_id, document_code, year)`

---

### 2. Legacy Format: `PREFIX-YYYYMM-######`

**Purpose**: Support existing modules and documents outside the multi-entity scope.

**Format Breakdown**:

-   `PREFIX` (2-4 characters): Document type prefix (e.g., `PO`, `PINV`, `JNL`)
-   `YYYYMM` (6 digits): Year and month (e.g., `202509` for September 2025)
-   `######` (6 digits): Zero-padded sequence number (e.g., `000001`, `000002`)

**Example Numbers**:

-   `PO-202509-000001` - Purchase Order #1 in September 2025
-   `PINV-202509-000001` - Purchase Invoice #1 in September 2025
-   `JNL-202509-000001` - Journal Entry #1 in September 2025

**Supported Document Types**:
| Document Type | Prefix | Legacy Format | Current Status | Example |
|--------------|--------|---------------|----------------|---------|
| Purchase Order | `PO` | ❌ **Deprecated** | ✅ Uses Entity Format Only | `71250100001` |
| Sales Order | `SO` | ❌ **Deprecated** | ✅ Uses Entity Format Only | `72250600001` |
| Purchase Invoice | `PINV` | ❌ **Deprecated** | ✅ Uses Entity Format Only | `71250300001` |
| Sales Invoice | `SINV` | ❌ **Deprecated** | ✅ Uses Entity Format Only | `72250800001` |
| Goods Receipt PO | `GRPO` | ❌ **Deprecated** | ✅ Uses Entity Format Only | `71250200001` |
| Delivery Order | N/A | ❌ Not Supported | ✅ Uses Entity Format Only | `72250700001` |
| Goods Receipt | `GR` | ❌ **Deprecated** | ✅ Uses Entity Format Only | `71250200001` |
| Purchase Payment | `PP` | ✅ **Active** | ✅ Uses Legacy Format Only | `PP-202509-000001` |
| Sales Receipt | `SR` | ✅ **Active** | ✅ Uses Legacy Format Only | `SR-202509-000001` |
| Asset Disposal | `DIS` | ✅ **Active** | ✅ Uses Legacy Format Only | `DIS-202509-000001` |
| Cash Expense | `CEV` | ✅ **Active** | ✅ Uses Legacy Format Only | `CEV-202509-000001` |
| Journal | `JNL` | ✅ **Active** | ✅ Uses Legacy Format Only | `JNL-202509-000001` |
| Account Statement | `AST` | ✅ **Active** | ✅ Uses Legacy Format Only | `AST-202509-000001` |

**Important Notes**:

-   **Entity Format Documents**: PO, GRPO, PI, PP, SO, DO, SI, SR, GR, DIS, CEV, JNL, and AST **ALWAYS** use entity format (`EEYYDDNNNNN`). Legacy format is **no longer used** for these document types.
-   **All Documents Migrated**: All document types now use entity-aware format (`EEYYDDNNNNN`). Legacy format is **completely deprecated**.
-   The system automatically selects the format based on whether the document type exists in `ENTITY_DOCUMENT_CODES`. There is **no fallback** - entity-aware documents always use entity format.

**Sequence Management**:

-   Per-document-type, per-month tracking
-   Sequence resets monthly (1st of each month)
-   Unique composite key: `(document_type, year_month)`

---

## System Architecture

### Core Components

#### 1. DocumentNumberingService

**Location**: `app/Services/DocumentNumberingService.php`

**Responsibilities**:

-   Unified number generation for all document types
-   Format selection based on document type and entity context
-   Thread-safe sequence management with database locking
-   Number validation and format verification

**Key Methods**:

```php
generateNumber(string $documentType, string $date, array $options = []): string
validateNumber(string $number, string $documentType): bool
getSupportedTypes(): array
repairSequences(string $documentType, string $yearMonth): int
```

**Format Selection Logic**:

```php
if ($this->usesEntityFormat($documentType)) {
    // Generate EEYYDDNNNNN format
    $entity = $this->companyEntityService->getEntity($options['company_entity_id'] ?? null);
    $year = Carbon::parse($date)->year;
    $docCode = self::ENTITY_DOCUMENT_CODES[$documentType];
    $sequence = $this->getNextEntitySequence($entity->id, $documentType, $docCode, $year);
    return $this->formatEntityNumber($entity->code, $year, $docCode, $sequence);
} else {
    // Generate PREFIX-YYYYMM-###### format
    $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
    $yearMonth = Carbon::parse($date)->format('Ym');
    $sequence = $this->getNextLegacySequence($documentType, $yearMonth);
    return sprintf('%s-%s-%06d', $prefix, $yearMonth, $sequence);
}
```

#### 2. DocumentSequence Model

**Location**: `app/Models/DocumentSequence.php`

**Database Table**: `document_sequences`

**Schema**:

```sql
- id (bigint, primary key)
- company_entity_id (bigint, nullable, FK to company_entities)
- document_type (string, 50)
- year_month (string, 6) -- Format: YYYYMM
- document_code (string, 5, nullable) -- For entity format (01-08)
- year (smallint, nullable) -- For entity format
- last_sequence (integer, default 0) -- For legacy format
- current_number (integer, default 0) -- For entity format
- timestamps
```

**Unique Constraints**:

-   Legacy format: `(document_type, year_month)`
-   Entity format: `(company_entity_id, document_code, year)`

**Key Methods**:

```php
incrementSequence(): int // For legacy format
incrementEntitySequence(): int // For entity format
getNextSequence(): int
```

#### 3. CompanyEntityService

**Location**: `app/Services/CompanyEntityService.php`

**Responsibilities**:

-   Entity resolution and default entity selection
-   Entity context propagation from base documents
-   Entity validation and active status checking

**Key Methods**:

```php
getEntity(?int $entityId = null): CompanyEntity
resolveFromModel(?int $entityId = null, ?Model $source = null): CompanyEntity
getDefaultEntity(): CompanyEntity
getActiveEntities(): Collection
```

---

## Database Schema

### document_sequences Table

**Migration**: `database/migrations/2025_11_28_165537_create_document_sequences_table.php`

**Complete Schema**:

```php
Schema::create('document_sequences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_entity_id')->nullable()->constrained('company_entities')->nullOnDelete();
    $table->string('document_type', 50)->index();
    $table->string('document_code', 5)->nullable(); // Entity format: 01-08
    $table->string('year_month', 6)->index(); // Legacy: YYYYMM, Entity: YYYY00
    $table->unsignedSmallInteger('year')->nullable(); // Entity format: 2025
    $table->integer('last_sequence')->default(0); // Legacy format counter
    $table->unsignedInteger('current_number')->default(0); // Entity format counter
    $table->timestamps();

    // Unique constraints
    $table->unique(['document_type', 'year_month'], 'unique_type_month'); // Legacy
    $table->unique(['company_entity_id', 'document_code', 'year'], 'doc_seq_entity_code_year_unique'); // Entity
});
```

### company_entities Table

**Migration**: `database/migrations/2025_11_28_165531_create_company_entities_table.php`

**Schema**:

```php
Schema::create('company_entities', function (Blueprint $table) {
    $table->id();
    $table->string('code', 10)->unique(); // Entity code: 71, 72
    $table->string('name', 150);
    $table->string('legal_name', 200)->nullable();
    $table->string('tax_number', 50)->nullable();
    $table->string('address', 500)->nullable();
    $table->string('phone', 50)->nullable();
    $table->string('email', 100)->nullable();
    $table->string('website', 150)->nullable();
    $table->string('logo_path')->nullable();
    $table->json('letterhead_meta')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Seeded Entities**:

-   **PT Cahaya Sarange Jaya**: Code `71`, Logo: `logo_pt_csj.png`
-   **CV Cahaya Saranghae**: Code `72`, Logo: `logo_cv_saranghae.png`

---

## Thread Safety and Concurrency

### Database Locking Mechanism

The numbering system uses **pessimistic locking** with `lockForUpdate()` to prevent race conditions and duplicate numbers:

```php
return DB::transaction(function () use ($documentType, $yearMonth) {
    $sequence = DocumentSequence::lockForUpdate()
        ->where('document_type', $documentType)
        ->where('year_month', $yearMonth)
        ->first();

    if (!$sequence) {
        $sequence = DocumentSequence::create([...]);
    }

    $sequence->increment('last_sequence');
    return $sequence->last_sequence;
});
```

**Benefits**:

-   Prevents duplicate numbers in concurrent scenarios
-   Ensures sequence integrity across multiple requests
-   Automatic rollback on transaction failure
-   No gap in sequence numbers (unless transaction explicitly rolled back)

---

## Integration Patterns

### Controller Integration

Controllers typically generate numbers during document creation:

```php
// Purchase Order Controller Example
$documentNumber = $this->documentNumberingService->generateNumber(
    'purchase_order',
    $request->input('date'),
    ['company_entity_id' => $request->input('company_entity_id')]
);

$purchaseOrder = PurchaseOrder::create([
    'order_no' => $documentNumber,
    'company_entity_id' => $companyEntityId,
    // ... other fields
]);
```

### Service Layer Integration

Services inherit entity context from base documents:

```php
// GRPO Copy Service Example
$grpo = GoodsReceiptPO::create([
    'order_no' => $this->documentNumberingService->generateNumber(
        'grpo',
        now()->toDateString(),
        ['company_entity_id' => $purchaseOrder->company_entity_id] // Inherit from PO
    ),
    'company_entity_id' => $purchaseOrder->company_entity_id, // Copy entity
    'purchase_order_id' => $purchaseOrder->id,
    // ... other fields
]);
```

### Entity Context Propagation

The system automatically propagates entity context through document chains:

1. **Purchase Flow**: PO → GRPO → PI → PP

    - Each document inherits `company_entity_id` from previous document
    - All documents use entity-aware numbering format

2. **Sales Flow**: SO → DO → SI → SR

    - Each document inherits `company_entity_id` from previous document
    - All documents use entity-aware numbering format

3. **Document Copying**:
    - GRPO copies entity from Purchase Order
    - Delivery Order copies entity from Sales Order
    - Invoice copies entity from source document

---

## Sequence Reset Behavior

### Legacy Format (Monthly Reset)

-   **Reset Trigger**: First document of each month
-   **Reset Logic**: New `year_month` value triggers new sequence record
-   **Example**:
    -   September 2025: `PO-202509-000001` to `PO-202509-999999`
    -   October 2025: `PO-202510-000001` (resets to 1)

### Entity Format (Annual Reset)

-   **Reset Trigger**: First document of each year
-   **Reset Logic**: New `year` value triggers new sequence record
-   **Example**:
    -   2025: `71250100001` to `71250199999`
    -   2026: `71260100001` (resets to 1, year changes to 26)

---

## Number Validation

### Validation Patterns

**Entity Format Validation**:

```php
preg_match('/^\d{2}\d{2}\d{2}\d{5}$/', $number) === 1
// Matches: 71250100001 (11 digits total)
```

**Legacy Format Validation**:

```php
preg_match('/^' . preg_quote($prefix) . '-\d{6}-\d{6}$/', $number) === 1
// Matches: PO-202509-000001
```

### Validation Service Method

```php
public function validateNumber(string $number, string $documentType): bool
{
    if ($this->usesEntityFormat($documentType)) {
        return preg_match('/^\d{2}\d{2}\d{2}\d{5}$/', $number) === 1;
    }

    $prefix = self::LEGACY_DOCUMENT_TYPES[$documentType];
    $pattern = '/^' . preg_quote($prefix) . '-\d{6}-\d{6}$/';
    return preg_match($pattern, $number) === 1;
}
```

---

## Supported Document Types Summary

| Document Type     | Entity Format | Legacy Format     | Document Code | Legacy Prefix       | **Current Usage**      |
| ----------------- | ------------- | ----------------- | ------------- | ------------------- | ---------------------- |
| Purchase Order    | ✅            | ❌ **Deprecated** | `01`          | `PO` (deprecated)   | **Entity Format Only** |
| Goods Receipt PO  | ✅            | ❌ **Deprecated** | `02`          | `GRPO` (deprecated) | **Entity Format Only** |
| Purchase Invoice  | ✅            | ❌ **Deprecated** | `03`          | `PINV` (deprecated) | **Entity Format Only** |
| Sales Order       | ✅            | ❌ **Deprecated** | `06`          | `SO` (deprecated)   | **Entity Format Only** |
| Delivery Order    | ✅            | ❌ Not Supported  | `07`          | N/A                 | **Entity Format Only** |
| Sales Invoice     | ✅            | ❌ **Deprecated** | `08`          | `SINV` (deprecated) | **Entity Format Only** |
| Goods Receipt     | ✅            | ❌ **Deprecated** | `02`          | `GR` (deprecated)   | **Entity Format Only** |
| Purchase Payment  | ✅            | ❌ **Deprecated** | `04`          | `PP` (deprecated)   | **Entity Format Only** |
| Sales Receipt     | ✅            | ❌ **Deprecated** | `09`          | `SR` (deprecated)   | **Entity Format Only** |
| Asset Disposal    | ✅            | ❌ **Deprecated** | `10`          | `DIS` (deprecated)  | **Entity Format Only** |
| Cash Expense      | ✅            | ❌ **Deprecated** | `11`          | `CEV` (deprecated)  | **Entity Format Only** |
| Journal           | ✅            | ❌ **Deprecated** | `12`          | `JNL` (deprecated)  | **Entity Format Only** |
| Account Statement | ✅            | ❌ **Deprecated** | `13`          | `AST` (deprecated)  | **Entity Format Only** |

**Format Selection Logic**:

-   All documents in `ENTITY_DOCUMENT_CODES` (PO, GRPO, PI, PP, SO, DO, SI, SR, GR, DIS, CEV, JNL, AST) **ALWAYS** use entity format (`EEYYDDNNNNN`)
-   Legacy format (`PREFIX-YYYYMM-######`) is **completely deprecated** and no longer used
-   There is **no conditional fallback** - the format is determined solely by document type registration

---

## Implementation History

### Phase 1: Initial Centralized System (2025-01-17)

-   Implemented centralized `DocumentNumberingService`
-   Established `PREFIX-YYYYMM-######` format for all document types
-   Created `DocumentSequence` model and table
-   Thread-safe sequence management with database locking
-   **Memory Entry**: [018] Comprehensive Auto-Numbering System Implementation

### Phase 2: Multi-Entity Foundation (2025-11-28)

-   Added `company_entities` table and seeder
-   Extended `document_sequences` table with entity-aware fields
-   Added `company_entity_id` foreign keys to purchase/sales documents
-   **Memory Entry**: [026] Multi-Entity Company Profile Foundation

### Phase 3: Entity-Aware Numbering (2025-11-28)

-   Created `CompanyEntityService` for entity resolution
-   Implemented dual-format system with automatic format selection
-   Added `EEYYDDNNNNN` format for entity-aware documents
-   Updated controllers/services to use entity context
-   **Memory Entry**: [027] Entity-Aware Numbering & Service Update

---

## Design Decisions

### Decision: Dual Format Support

**Context**: System needed to support both legacy single-entity operations and new multi-entity requirements without breaking existing functionality.

**Decision**: Maintain both numbering formats with automatic selection based on document type registration in `ENTITY_DOCUMENT_CODES`.

**Rationale**:

-   Preserves backward compatibility for legacy modules (PP, SR, DIS, CEV, JNL, AST)
-   Core trading documents (PO, GRPO, PI, SO, DO, SI, GR) migrated to entity-aware format for multi-entity support
-   Format selection is **deterministic** - based on document type registration, not runtime conditions
-   Minimizes code duplication through centralized service

**Current State** (2025-01-21):

-   **Entity Format**: PO, GRPO, PI, SO, DO, SI, GR - **ALWAYS** use `EEYYDDNNNNN` format. Legacy format is **deprecated** for these types.
-   **Legacy Format**: PP, SR, DIS, CEV, JNL, AST - Continue using `PREFIX-YYYYMM-######` format.

**Reference**: `docs/decisions.md` lines 140-170

---

## Future Considerations

### Potential Enhancements

1. **Format Migration Tool**: Automated migration of existing documents from legacy to entity format
2. **Custom Format Configuration**: Allow per-entity custom numbering formats
3. **Number Gap Handling**: Tool to detect and optionally fill sequence gaps
4. **Format Validation Enhancement**: More sophisticated validation with entity code verification
5. **Reporting Integration**: Numbering format analytics and usage reports

### Migration Path

For documents currently using legacy format that should migrate to entity format:

1. Add `company_entity_id` to existing document records
2. Run migration script to generate new entity-aware numbers
3. Update sequence tracking to reflect new format
4. Archive old numbers for audit trail

---

## Testing Recommendations

### Unit Tests

-   Format generation for both entity and legacy formats
-   Sequence increment logic
-   Thread safety with concurrent requests
-   Validation patterns
-   Entity resolution logic

### Integration Tests

-   End-to-end document creation workflows
-   Entity context propagation through document chains
-   Sequence reset on month/year boundaries
-   Concurrent document creation scenarios

### Manual Testing Checklist

-   [ ] Purchase Order creation with entity format
-   [ ] Sales Order creation with entity format
-   [ ] Legacy format documents (PP, SR, JNL, etc.)
-   [ ] Entity context inheritance (PO → GRPO, SO → DO)
-   [ ] Sequence reset at month/year boundaries
-   [ ] Concurrent creation (multiple users creating documents simultaneously)
-   [ ] Validation of generated numbers

---

## Related Documentation

-   **Architecture Documentation**: `docs/architecture.md` (lines 398-412)
-   **Design Decisions**: `docs/decisions.md` (lines 140-170)
-   **Multi-Entity Plan**: `docs/company-profile-multi-entity-plan.md`
-   **Memory Entries**: `MEMORY.md` ([018], [026], [027])
-   **Module Features**: `docs/MODULES-AND-FEATURES.md` (lines 482-492)

---

## Code References

### Key Files

-   **Service**: `app/Services/DocumentNumberingService.php`
-   **Model**: `app/Models/DocumentSequence.php`
-   **Entity Service**: `app/Services/CompanyEntityService.php`
-   **Entity Model**: `app/Models/CompanyEntity.php`
-   **Migration**: `database/migrations/2025_11_28_165537_create_document_sequences_table.php`
-   **Seeder**: `database/seeders/CompanyEntitySeeder.php`

### Usage Examples

-   Purchase Order: `app/Http/Controllers/PurchaseOrderController.php`
-   Sales Order: `app/Http/Controllers/SalesOrderController.php`
-   GRPO Copy: `app/Services/GRPOCopyService.php`
-   Delivery Service: `app/Services/DeliveryService.php`

---

**Document Status**: ✅ Complete and Current  
**Last Review**: 2025-01-21  
**Next Review**: As needed for system enhancements
