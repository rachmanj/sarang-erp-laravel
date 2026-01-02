# Sales Quotation Implementation Plan

**Document Version**: 1.0  
**Date**: 2025-01-22  
**Status**: 📋 Pending Approval

---

## Executive Summary

This document provides a comprehensive recommendation and action plan for implementing Sales Quotation functionality in the Sarang ERP system. Sales Quotation will serve as a preliminary document in the sales workflow, allowing sales teams to send price quotes to customers before converting them to formal Sales Orders.

---

## 1. Business Justification & Recommendation

### 1.1 Why Sales Quotation is Needed

**Current Gap**: The system currently starts with Sales Orders, which are binding commitments. Sales teams need a non-binding preliminary document to:
- Send price quotes to potential customers
- Negotiate terms and conditions before commitment
- Track quotation conversion rates
- Manage quotation expiration dates
- Provide professional quotation documents to customers

**Business Benefits**:
- ✅ Improved sales process with formal quotation stage
- ✅ Better customer communication with professional quotation documents
- ✅ Sales analytics on quotation-to-order conversion rates
- ✅ Quotation expiration management
- ✅ Non-binding price exploration before commitment

### 1.2 Recommended Workflow Integration

**Proposed Workflow**: `SQ → SO → DO → SI → SR`

```
Sales Quotation (SQ) [Non-binding, No inventory impact]
    ↓ [Customer Approval]
Sales Order (SO) [Binding commitment, Inventory impact]
    ↓
Delivery Order (DO)
    ↓
Sales Invoice (SI)
    ↓
Sales Receipt (SR)
```

**Key Characteristics**:
- **Sales Quotation**: Non-binding, no inventory impact, no journal entries, expiration date tracking
- **Sales Order**: Binding commitment, inventory impact, journal entries, approval workflow

---

## 2. Architecture Design

### 2.1 Database Schema

#### 2.1.1 `sales_quotations` Table

```sql
CREATE TABLE sales_quotations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    quotation_no VARCHAR(50) UNIQUE NOT NULL,
    reference_no VARCHAR(100) NULL,
    date DATE NOT NULL,
    valid_until_date DATE NOT NULL,  -- Quotation expiration date
    business_partner_id BIGINT UNSIGNED NOT NULL,
    company_entity_id BIGINT UNSIGNED NOT NULL,
    currency_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
    exchange_rate DECIMAL(10,6) DEFAULT 1.000000,
    warehouse_id BIGINT UNSIGNED NULL,  -- Optional, for reference
    description VARCHAR(255) NULL,
    notes TEXT NULL,
    terms_conditions TEXT NULL,
    payment_terms VARCHAR(100) NULL,
    delivery_method VARCHAR(100) NULL,
    total_amount DECIMAL(15,2) DEFAULT 0,
    total_amount_foreign DECIMAL(15,2) DEFAULT 0,
    freight_cost DECIMAL(15,2) DEFAULT 0,
    handling_cost DECIMAL(15,2) DEFAULT 0,
    insurance_cost DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    net_amount DECIMAL(15,2) DEFAULT 0,
    order_type ENUM('item', 'service') DEFAULT 'item',
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired', 'converted') DEFAULT 'draft',
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    converted_to_sales_order_id BIGINT UNSIGNED NULL,  -- Link to converted SO
    converted_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (business_partner_id) REFERENCES business_partners(id),
    FOREIGN KEY (company_entity_id) REFERENCES company_entities(id),
    FOREIGN KEY (currency_id) REFERENCES currencies(id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (converted_to_sales_order_id) REFERENCES sales_orders(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_quotation_no (quotation_no),
    INDEX idx_business_partner_id (business_partner_id),
    INDEX idx_status (status),
    INDEX idx_valid_until_date (valid_until_date),
    INDEX idx_company_entity_id (company_entity_id)
);
```

#### 2.1.2 `sales_quotation_lines` Table

```sql
CREATE TABLE sales_quotation_lines (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    quotation_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NULL,
    inventory_item_id BIGINT UNSIGNED NULL,
    item_code VARCHAR(100) NULL,
    item_name VARCHAR(255) NULL,
    unit_of_measure VARCHAR(50) NULL,
    order_unit_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    qty DECIMAL(15,2) NOT NULL DEFAULT 0,
    base_quantity DECIMAL(15,2) DEFAULT 0,
    unit_conversion_factor DECIMAL(10,4) DEFAULT 1.0000,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    unit_price_foreign DECIMAL(15,2) DEFAULT 0,
    amount DECIMAL(15,2) DEFAULT 0,
    amount_foreign DECIMAL(15,2) DEFAULT 0,
    freight_cost DECIMAL(15,2) DEFAULT 0,
    handling_cost DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    net_amount DECIMAL(15,2) DEFAULT 0,
    tax_code_id BIGINT UNSIGNED NULL,
    vat_rate DECIMAL(5,2) DEFAULT 0,
    wtax_rate DECIMAL(5,2) DEFAULT 0,
    notes TEXT NULL,
    line_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (quotation_id) REFERENCES sales_quotations(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (order_unit_id) REFERENCES units_of_measure(id),
    FOREIGN KEY (tax_code_id) REFERENCES tax_codes(id),
    INDEX idx_quotation_id (quotation_id),
    INDEX idx_inventory_item_id (inventory_item_id)
);
```

#### 2.1.3 `sales_quotation_approvals` Table

```sql
CREATE TABLE sales_quotation_approvals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sales_quotation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    approval_level VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (sales_quotation_id) REFERENCES sales_quotations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_sales_quotation_id (sales_quotation_id),
    INDEX idx_user_id (user_id)
);
```

### 2.2 Document Numbering

**Document Code**: `05` (Available, not currently used)  
**Format**: `EEYYDDNNNNN` (Entity-aware format)  
**Example**: `71052500001` (Entity 71, Year 2025, Code 05, Sequence 00001)

**Integration**: Add to `DocumentNumberingService::ENTITY_DOCUMENT_CODES`:
```php
'sales_quotation' => '05',
```

### 2.3 Model Structure

#### 2.3.1 SalesQuotation Model
- Similar to `SalesOrder` model but simpler
- No inventory impact methods
- No journal entry methods
- Status management: `draft` → `sent` → `accepted`/`rejected`/`expired` → `converted`
- Conversion tracking to Sales Order
- Expiration date checking

#### 2.3.2 SalesQuotationLine Model
- Similar to `SalesOrderLine` model
- No delivery quantity tracking (not needed for quotations)
- Unit conversion support
- Discount calculation support

### 2.4 Service Layer

#### 2.4.1 QuotationService
- `createQuotation($data)` - Create new quotation
- `updateQuotation($id, $data)` - Update quotation
- `sendQuotation($id)` - Mark quotation as sent to customer
- `acceptQuotation($id)` - Mark quotation as accepted by customer
- `rejectQuotation($id, $reason)` - Mark quotation as rejected
- `expireQuotation($id)` - Mark quotation as expired
- `convertToSalesOrder($id, $data)` - Convert quotation to Sales Order
- `applyCustomerPricingTier($quotation)` - Apply customer pricing discounts
- `checkExpiration()` - Batch check for expired quotations

#### 2.4.2 QuotationConversionService
- `convertQuotationToSalesOrder($quotationId, $options)` - Convert SQ to SO
- Copy all quotation data to Sales Order
- Link Sales Order back to quotation
- Update quotation status to `converted`

### 2.5 Controller Structure

#### 2.5.1 SalesQuotationController
- `index()` - List all quotations
- `create()` - Create new quotation form
- `store()` - Save new quotation
- `show($id)` - View quotation details
- `edit($id)` - Edit quotation form
- `update($id)` - Update quotation
- `destroy($id)` - Delete quotation
- `send($id)` - Send quotation to customer
- `accept($id)` - Accept quotation
- `reject($id)` - Reject quotation
- `convertToSalesOrder($id)` - Convert to Sales Order
- `print($id)` - Print quotation PDF
- `approve($id)` - Approve quotation
- `rejectApproval($id)` - Reject quotation approval

### 2.6 View Structure

#### 2.6.1 Views Directory: `resources/views/sales_quotations/`
- `index.blade.php` - Quotation list with DataTables
- `create.blade.php` - Create quotation form
- `show.blade.php` - View quotation details
- `edit.blade.php` - Edit quotation form
- `print.blade.php` - Print quotation template

**UI Features**:
- Modal-based item selection (reuse from Sales Order)
- Real-time discount calculation
- Expiration date warnings
- Conversion status indicators
- Print/PDF generation
- Document navigation (if converted to SO)

---

## 3. Implementation Action Plan

### Phase 1: Foundation Setup (3-4 days)

#### Task 1.1: Database Migration
- [ ] Create migration for `sales_quotations` table
- [ ] Create migration for `sales_quotation_lines` table
- [ ] Create migration for `sales_quotation_approvals` table
- [ ] Add foreign key constraints
- [ ] Add indexes for performance
- [ ] Test migration rollback

**Estimated Time**: 4 hours

#### Task 1.2: Document Numbering Integration
- [ ] Add `sales_quotation => '05'` to `DocumentNumberingService`
- [ ] Test document number generation
- [ ] Verify entity-aware format (`EEYYDDNNNNN`)

**Estimated Time**: 1 hour

#### Task 1.3: Model Creation
- [ ] Create `SalesQuotation` model with relationships
- [ ] Create `SalesQuotationLine` model with relationships
- [ ] Create `SalesQuotationApproval` model
- [ ] Add fillable fields and casts
- [ ] Add scopes (draft, sent, accepted, expired, converted)
- [ ] Add helper methods (canBeSent, canBeConverted, isExpired, etc.)
- [ ] Add relationship to SalesOrder (converted_to_sales_order_id)

**Estimated Time**: 6 hours

#### Task 1.4: Service Layer - QuotationService
- [ ] Create `QuotationService` class
- [ ] Implement `createQuotation()` method
- [ ] Implement `updateQuotation()` method
- [ ] Implement `sendQuotation()` method
- [ ] Implement `acceptQuotation()` method
- [ ] Implement `rejectQuotation()` method
- [ ] Implement `expireQuotation()` method
- [ ] Implement `applyCustomerPricingTier()` method
- [ ] Implement `checkExpiration()` batch method

**Estimated Time**: 8 hours

#### Task 1.5: Service Layer - QuotationConversionService
- [ ] Create `QuotationConversionService` class
- [ ] Implement `convertQuotationToSalesOrder()` method
- [ ] Copy quotation data to Sales Order
- [ ] Copy quotation lines to Sales Order lines
- [ ] Link Sales Order back to quotation
- [ ] Update quotation status to `converted`
- [ ] Handle unit conversions
- [ ] Handle discount calculations

**Estimated Time**: 6 hours

**Phase 1 Total**: ~25 hours (3-4 days)

---

### Phase 2: Controller & Routes (2-3 days)

#### Task 2.1: Controller Implementation
- [ ] Create `SalesQuotationController` class
- [ ] Implement `index()` method with DataTables
- [ ] Implement `create()` method
- [ ] Implement `store()` method with validation
- [ ] Implement `show($id)` method
- [ ] Implement `edit($id)` method
- [ ] Implement `update($id)` method
- [ ] Implement `destroy($id)` method
- [ ] Implement `send($id)` method
- [ ] Implement `accept($id)` method
- [ ] Implement `reject($id)` method
- [ ] Implement `convertToSalesOrder($id)` method
- [ ] Implement `print($id)` method
- [ ] Implement `approve($id)` method
- [ ] Implement `rejectApproval($id)` method
- [ ] Add AJAX endpoints for item selection
- [ ] Add AJAX endpoints for document number generation

**Estimated Time**: 12 hours

#### Task 2.2: Route Configuration
- [ ] Add routes in `routes/web/orders.php` or new `routes/web/sales-quotations.php`
- [ ] Add DataTables AJAX routes (`/data`, `/csv`)
- [ ] Add API routes for AJAX operations
- [ ] Add middleware (auth, permissions)
- [ ] Add permission checks (`ar.quotations.view`, `ar.quotations.create`, etc.)

**Estimated Time**: 2 hours

#### Task 2.3: Permission Setup
- [ ] Create permissions seeder for Sales Quotations
- [ ] Add permissions: `ar.quotations.view`, `ar.quotations.create`, `ar.quotations.update`, `ar.quotations.delete`, `ar.quotations.approve`, `ar.quotations.convert`
- [ ] Assign permissions to roles

**Estimated Time**: 1 hour

**Phase 2 Total**: ~15 hours (2-3 days)

---

### Phase 3: User Interface (3-4 days)

#### Task 3.1: Index Page
- [ ] Create `resources/views/sales_quotations/index.blade.php`
- [ ] Implement DataTables with filters (status, date range, customer, expiration)
- [ ] Add action buttons (View, Edit, Send, Convert, Print)
- [ ] Add status badges (draft, sent, accepted, expired, converted)
- [ ] Add expiration date warnings
- [ ] Add conversion status indicators
- [ ] Follow AdminLTE layout pattern

**Estimated Time**: 6 hours

#### Task 3.2: Create/Edit Pages
- [ ] Create `resources/views/sales_quotations/create.blade.php`
- [ ] Create `resources/views/sales_quotations/edit.blade.php`
- [ ] Implement form with customer selection
- [ ] Implement modal-based item selection (reuse from Sales Order)
- [ ] Add line items table with add/remove functionality
- [ ] Add discount fields (amount, percentage)
- [ ] Add expiration date picker
- [ ] Add real-time calculation JavaScript
- [ ] Add unit conversion support
- [ ] Add validation and error handling
- [ ] Follow Sales Order create page design pattern

**Estimated Time**: 12 hours

#### Task 3.3: Show Page
- [ ] Create `resources/views/sales_quotations/show.blade.php`
- [ ] Display quotation header information
- [ ] Display line items table
- [ ] Add action buttons (Edit, Send, Accept, Reject, Convert, Print)
- [ ] Add status indicators
- [ ] Add expiration date display with warnings
- [ ] Add conversion link (if converted to SO)
- [ ] Add document navigation (if converted)
- [ ] Add approval workflow display
- [ ] Follow Sales Order show page design pattern

**Estimated Time**: 6 hours

#### Task 3.4: Print Template
- [ ] Create `resources/views/sales_quotations/print.blade.php`
- [ ] Design professional quotation document
- [ ] Include company letterhead (entity-aware)
- [ ] Include customer information
- [ ] Include line items with pricing
- [ ] Include terms and conditions
- [ ] Include expiration date prominently
- [ ] Add PDF generation support

**Estimated Time**: 4 hours

**Phase 3 Total**: ~28 hours (3-4 days)

---

### Phase 4: Integration & Workflow (2-3 days)

#### Task 4.1: Approval Workflow Integration
- [ ] Integrate with existing `ApprovalWorkflowService`
- [ ] Add quotation approval workflow configuration
- [ ] Add approval dashboard integration
- [ ] Add approval notifications
- [ ] Test multi-level approval process

**Estimated Time**: 6 hours

#### Task 4.2: Sales Order Conversion Integration
- [ ] Add "Convert to Sales Order" button in quotation show page
- [ ] Create conversion confirmation dialog (SweetAlert2)
- [ ] Implement conversion flow with data copying
- [ ] Add link from Sales Order back to quotation
- [ ] Update Sales Order show page to display source quotation
- [ ] Test conversion with various scenarios (items, services, discounts)

**Estimated Time**: 8 hours

#### Task 4.3: Document Closure Integration
- [ ] Integrate with `DocumentClosureService`
- [ ] Add quotation closure tracking
- [ ] Update quotation status when converted to SO
- [ ] Add closure relationship tracking

**Estimated Time**: 2 hours

#### Task 4.4: Document Navigation Integration
- [ ] Add quotation to document relationship map
- [ ] Add navigation from Sales Order to source quotation
- [ ] Update DocumentRelationshipService to handle quotations
- [ ] Test document navigation flow

**Estimated Time**: 4 hours

**Phase 4 Total**: ~20 hours (2-3 days)

---

### Phase 5: Advanced Features (2-3 days)

#### Task 5.1: Expiration Management
- [ ] Create scheduled command to check expired quotations
- [ ] Add expiration notification system
- [ ] Add expiration dashboard widget
- [ ] Add automatic expiration status update
- [ ] Add expiration reports

**Estimated Time**: 6 hours

#### Task 5.2: Quotation Analytics
- [ ] Add quotation conversion rate tracking
- [ ] Add quotation analytics dashboard
- [ ] Add quotation performance reports
- [ ] Add customer quotation history
- [ ] Add quotation-to-order conversion reports

**Estimated Time**: 8 hours

#### Task 5.3: Email Integration (Optional)
- [ ] Add email sending capability for quotations
- [ ] Create email template for quotation
- [ ] Add email tracking (sent, opened, clicked)
- [ ] Add email reminder system for expiring quotations

**Estimated Time**: 6 hours (Optional)

**Phase 5 Total**: ~20 hours (2-3 days, optional features)

---

### Phase 6: Testing & Documentation (2-3 days)

#### Task 6.1: Unit Testing
- [ ] Write tests for `SalesQuotation` model
- [ ] Write tests for `SalesQuotationLine` model
- [ ] Write tests for `QuotationService`
- [ ] Write tests for `QuotationConversionService`
- [ ] Write tests for `SalesQuotationController`

**Estimated Time**: 8 hours

#### Task 6.2: Integration Testing
- [ ] Test quotation creation workflow
- [ ] Test quotation approval workflow
- [ ] Test quotation conversion to Sales Order
- [ ] Test expiration management
- [ ] Test document navigation
- [ ] Test print/PDF generation

**Estimated Time**: 6 hours

#### Task 6.3: Browser Testing
- [ ] Test all UI functionality using browser MCP
- [ ] Test form validations
- [ ] Test JavaScript calculations
- [ ] Test modal item selection
- [ ] Test conversion flow
- [ ] Test print functionality

**Estimated Time**: 4 hours

#### Task 6.4: Documentation
- [ ] Update `docs/MODULES-AND-FEATURES.md`
- [ ] Create user manual for Sales Quotations
- [ ] Update architecture documentation
- [ ] Create training materials
- [ ] Update API documentation (if applicable)

**Estimated Time**: 4 hours

**Phase 6 Total**: ~22 hours (2-3 days)

---

## 4. Total Implementation Estimate

| Phase | Tasks | Estimated Time | Days |
|-------|-------|----------------|------|
| Phase 1: Foundation Setup | 5 tasks | ~25 hours | 3-4 days |
| Phase 2: Controller & Routes | 3 tasks | ~15 hours | 2-3 days |
| Phase 3: User Interface | 4 tasks | ~28 hours | 3-4 days |
| Phase 4: Integration & Workflow | 4 tasks | ~20 hours | 2-3 days |
| Phase 5: Advanced Features | 3 tasks | ~20 hours | 2-3 days (optional) |
| Phase 6: Testing & Documentation | 4 tasks | ~22 hours | 2-3 days |
| **Total (Core)** | **20 tasks** | **~110 hours** | **12-17 days** |
| **Total (With Advanced)** | **23 tasks** | **~130 hours** | **14-20 days** |

---

## 5. Risk Assessment & Mitigation

### 5.1 Technical Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Document numbering conflicts | High | Low | Use reserved code `05`, test thoroughly |
| Conversion data loss | High | Medium | Comprehensive testing, data validation |
| Performance issues with large quotations | Medium | Low | Proper indexing, pagination |
| Approval workflow integration issues | Medium | Medium | Reuse existing approval service patterns |

### 5.2 Business Risks

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| User adoption | Medium | Medium | Training, clear documentation |
| Workflow confusion | Medium | Low | Clear status indicators, user guidance |
| Expiration management overhead | Low | Medium | Automated expiration checking |

---

## 6. Success Criteria

### 6.1 Functional Requirements
- ✅ Sales Quotation CRUD operations working
- ✅ Quotation conversion to Sales Order working
- ✅ Approval workflow integrated
- ✅ Expiration management functional
- ✅ Print/PDF generation working
- ✅ Document navigation integrated

### 6.2 Performance Requirements
- ✅ Quotation list loads in < 2 seconds
- ✅ Quotation creation saves in < 1 second
- ✅ Conversion to Sales Order completes in < 3 seconds

### 6.3 User Experience Requirements
- ✅ Intuitive UI matching Sales Order patterns
- ✅ Clear status indicators
- ✅ Helpful error messages
- ✅ Professional print templates

---

## 7. Dependencies

### 7.1 External Dependencies
- ✅ Existing Sales Order module (for conversion)
- ✅ Approval Workflow Service (for approvals)
- ✅ Document Numbering Service (for numbering)
- ✅ Document Closure Service (for tracking)
- ✅ Company Entity Service (for multi-entity support)

### 7.2 Internal Dependencies
- ✅ AdminLTE UI framework
- ✅ DataTables for listing
- ✅ SweetAlert2 for confirmations
- ✅ PDF generation library (dompdf or similar)

---

## 8. Post-Implementation Considerations

### 8.1 Future Enhancements
- Email integration for sending quotations
- Quotation templates/customization
- Quotation versioning
- Quotation comparison tools
- Mobile-responsive quotation viewing

### 8.2 Maintenance
- Regular expiration batch job monitoring
- Performance monitoring for large datasets
- User feedback collection
- Continuous improvement based on usage patterns

---

## 9. Approval Checklist

Before starting implementation, please review and approve:

- [ ] **Architecture Design**: Database schema, models, services
- [ ] **Document Numbering**: Use code `05` for Sales Quotation
- [ ] **Workflow Integration**: SQ → SO conversion flow
- [ ] **UI/UX Design**: Follow Sales Order patterns
- [ ] **Implementation Timeline**: 12-17 days for core features
- [ ] **Resource Allocation**: Development time and priorities
- [ ] **Success Criteria**: Functional and performance requirements

---

## 10. Next Steps After Approval

1. **Kickoff Meeting**: Review plan with stakeholders
2. **Phase 1 Start**: Begin database migration and model creation
3. **Daily Standups**: Track progress and address blockers
4. **Phase Reviews**: Review each phase before proceeding
5. **User Acceptance Testing**: Involve end users in testing
6. **Deployment**: Deploy to staging, then production

---

**Document Prepared By**: AI Assistant  
**Review Required By**: Project Owner  
**Approval Status**: ⏳ Pending Approval

---

## Appendix A: Database Schema Diagrams

### Entity Relationship Diagram

```
sales_quotations (1) ──┐
                       ├── (M) sales_quotation_lines
                       ├── (M) sales_quotation_approvals
                       └── (1) sales_orders (converted_to_sales_order_id)

sales_quotations
    ├── business_partner_id → business_partners
    ├── company_entity_id → company_entities
    ├── currency_id → currencies
    └── warehouse_id → warehouses (optional)
```

---

## Appendix B: Sample Quotation Status Flow

```
[draft] → [pending_approval] → [approved] → [sent] → [accepted] → [converted]
                                                      ↓
                                                   [rejected]
                                                      ↓
                                                   [expired]
```

---

## Appendix C: Code Examples

### Sample QuotationService::convertToSalesOrder()

```php
public function convertToSalesOrder($quotationId, $options = [])
{
    return DB::transaction(function () use ($quotationId, $options) {
        $quotation = SalesQuotation::with('lines')->findOrFail($quotationId);
        
        if (!$quotation->canBeConverted()) {
            throw new \Exception('Quotation cannot be converted in current status');
        }
        
        // Create Sales Order from Quotation
        $salesOrder = SalesOrder::create([
            'order_no' => $this->documentNumberingService->generateNumber('sales_order', now()->toDateString(), [
                'company_entity_id' => $quotation->company_entity_id,
            ]),
            'reference_no' => $quotation->quotation_no,
            'date' => $options['date'] ?? now()->toDateString(),
            'business_partner_id' => $quotation->business_partner_id,
            'company_entity_id' => $quotation->company_entity_id,
            // ... copy all relevant fields
        ]);
        
        // Copy lines
        foreach ($quotation->lines as $line) {
            SalesOrderLine::create([
                'order_id' => $salesOrder->id,
                // ... copy line data
            ]);
        }
        
        // Update quotation
        $quotation->update([
            'status' => 'converted',
            'converted_to_sales_order_id' => $salesOrder->id,
            'converted_at' => now(),
        ]);
        
        return $salesOrder;
    });
}
```

---

**End of Document**
