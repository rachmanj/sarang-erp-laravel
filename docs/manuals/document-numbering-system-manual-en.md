# Document Numbering System Manual

**Version**: 1.0  
**Last Updated**: December 11, 2025  
**Applicable To**: All ERP Modules  
**Language**: English

---

## Table of Contents

1. [Overview](#overview)
2. [Number Format](#number-format)
3. [Document Codes](#document-codes)
4. [Entity Codes](#entity-codes)
5. [Sequence Management](#sequence-management)
6. [Examples](#examples)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)

---

## 1. Overview

The Sarang ERP system uses a **unified entity-aware numbering system** for all business documents. Every document number uniquely identifies:

- **Entity**: Which legal entity (company) created the document
- **Year**: The fiscal year of the document
- **Document Type**: The type of document (PO, Invoice, etc.)
- **Sequence Number**: Unique sequential number within the entity/year/document type

This system ensures that:
- Each document has a unique number across all entities
- Document numbers are predictable and traceable
- Year-based sequences reset automatically
- Entity-specific reporting is simplified

---

## 2. Number Format

All document numbers follow the format: **`EEYYDDNNNNN`**

Where:
- **`EE`** = Entity Code (2 digits)
- **`YY`** = Year (2 digits, last 2 digits of the year)
- **`DD`** = Document Code (2 digits)
- **`NNNNN`** = Sequence Number (5 digits, zero-padded)

**Total Length**: 11 characters

---

## 3. Document Codes

| Code | Document Type | Description |
|------|--------------|-------------|
| 01 | Purchase Order | Purchase order from supplier |
| 02 | Goods Receipt PO / GRPO | Goods receipt from Purchase Order |
| 03 | Purchase Invoice | Purchase invoice from supplier |
| 04 | Purchase Payment | Payment to supplier |
| 06 | Sales Order | Sales order to customer |
| 07 | Delivery Order | Delivery order / shipping document |
| 08 | Sales Invoice | Sales invoice to customer |
| 09 | Sales Receipt | Payment received from customer |
| 10 | Asset Disposal | Fixed asset disposal/sale |
| 11 | Cash Expense | Cash expense/payment |
| 12 | Journal | Manual accounting journal entry |
| 13 | Account Statement | Account statement / account movement report |

**Note**: Codes 05 and 14-99 are reserved for future use.

---

## 4. Entity Codes

Entity codes are two-digit numbers assigned to each legal entity in the system. These codes are configured in the Company Entities master data.

**Common Entity Codes:**
- **71** = PT Cahaya Sarange Jaya (PT CSJ)
- **72** = CV Cahaya Saranghae (CV CS)

**How Entity Codes Work:**
- Entity codes are unique per legal entity
- They remain constant across all years
- They appear in every document number for that entity
- Entity codes are set during initial system setup and should not be changed

---

## 5. Sequence Management

**Automatic Sequence Management:**

1. **Year-Based Reset**: Sequence numbers reset to `00001` at the start of each calendar year (January 1st)

2. **Entity-Specific**: Each entity maintains separate sequences for each document type

3. **Thread-Safe**: The system ensures no duplicate numbers even with concurrent transactions

4. **Automatic Generation**: Document numbers are automatically generated when documents are created

**Sequence Storage:**
- Sequences are stored in the `document_sequences` table
- Each record tracks: Entity + Document Type + Year + Current Number
- The system automatically increments the sequence when generating new numbers

**Manual Intervention:**
- Sequence numbers are managed automatically by the system
- Manual sequence adjustment is not recommended and requires database access
- Contact system administrator for sequence-related issues

---

## 6. Examples

#### Example 1: Purchase Order (PT CSJ)

**Number**: `71250100001`

**Breakdown**:
- `71` = PT Cahaya Sarange Jaya
- `25` = Year 2025
- `01` = Purchase Order
- `00001` = First PO of the year

**Meaning**: This is the first Purchase Order created by PT CSJ in 2025.

#### Example 2: Sales Invoice (CV CS)

**Number**: `72250800005`

**Breakdown**:
- `72` = CV Cahaya Saranghae
- `25` = Year 2025
- `08` = Sales Invoice
- `00005` = Fifth Sales Invoice of the year

**Meaning**: This is the fifth Sales Invoice created by CV CS in 2025.

#### Example 3: Purchase Payment (PT CSJ)

**Number**: `71250400123`

**Breakdown**:
- `71` = PT Cahaya Sarange Jaya
- `25` = Year 2025
- `04` = Purchase Payment
- `00123` = 123rd Purchase Payment of the year

**Meaning**: This is the 123rd Purchase Payment made by PT CSJ in 2025.

#### Example 4: Journal Entry (PT CSJ)

**Number**: `71251200001`

**Breakdown**:
- `71` = PT Cahaya Sarange Jaya
- `25` = Year 2025
- `12` = Journal Entry
- `00001` = First Journal Entry of the year

**Meaning**: This is the first manual Journal Entry created by PT CSJ in 2025.

#### Example 5: Year Transition

**2024 Document**: `71240100050` (Last PO of 2024)
**2025 Document**: `71250100001` (First PO of 2025)

Notice how the sequence resets from `00050` to `00001` when the year changes from `24` to `25`.

---

## 7. Best Practices

#### For Users

1. **Don't Modify Numbers**: Never manually change document numbers. They are generated automatically.

2. **Verify Entity**: Always verify you're creating documents for the correct entity before submission.

3. **Year-End Planning**: Be aware that sequences reset on January 1st. Plan your year-end activities accordingly.

4. **Report by Entity**: When generating reports, use entity filters to get accurate entity-specific reports.

5. **Number Format Recognition**: Learn to recognize document types by their document codes (01=PO, 08=Sales Invoice, etc.)

#### For Administrators

1. **Entity Code Management**: Carefully assign entity codes during setup. Changing them later requires data migration.

2. **Sequence Monitoring**: Periodically check sequence tables for any anomalies or gaps.

3. **Backup Before Year End**: Ensure database backups are complete before year-end to preserve sequence states.

4. **Training**: Train users on the numbering system to avoid confusion and errors.

5. **Documentation**: Keep this manual updated when adding new document types or entities.

---

## 8. Troubleshooting

#### Problem: Duplicate Document Numbers

**Symptoms**: System shows error "Document number already exists"

**Possible Causes**:
- Database sequence table out of sync
- Manual sequence manipulation
- Concurrent transaction conflicts

**Solution**:
1. Contact system administrator
2. Check `document_sequences` table for the specific entity/document type/year
3. Verify no manual number assignments occurred
4. Administrator may need to adjust sequence manually

#### Problem: Incorrect Entity in Document Number

**Symptoms**: Document number shows wrong entity code

**Possible Causes**:
- Wrong entity selected during document creation
- Entity code changed after document creation
- Database inconsistency

**Solution**:
1. Verify entity selection before document creation
2. Check entity configuration in master data
3. Contact administrator if entity code appears wrong

#### Problem: Sequence Not Resetting at Year End

**Symptoms**: New year documents continue old sequence numbers

**Possible Causes**:
- System date/time incorrect
- Sequence table not updated
- Application cache issue

**Solution**:
1. Verify system date is correct
2. Check if new year sequence records exist in `document_sequences` table
3. Clear application cache
4. Contact administrator if problem persists

#### Problem: Missing Document Code

**Symptoms**: Document type not generating numbers or using wrong format

**Possible Causes**:
- Document type not registered in system
- Missing document code configuration
- Service configuration issue

**Solution**:
1. Verify document type exists in `DocumentNumberingService`
2. Check `ENTITY_DOCUMENT_CODES` configuration
3. Contact developer/administrator for missing document types

---

## 9. FAQ

**Q: Can I change a document number after it's created?**  
A: No, document numbers are immutable and cannot be changed after creation. This ensures audit trail integrity.

**Q: What happens if I create documents for different entities?**  
A: Each entity maintains separate sequences. A PO for entity 71 will be `71250100001`, while a PO for entity 72 will be `72250100001`, both being the first PO for their respective entities.

**Q: How many documents can I create per year per document type?**  
A: The sequence allows up to 99,999 documents per entity per document type per year (00001 to 99999).

**Q: What if I need more than 99,999 documents in a year?**  
A: This is extremely rare. If it occurs, contact the system administrator to extend the sequence length or implement a solution.

**Q: Can I use my own custom numbering format?**  
A: No, the system uses a standardized format across all entities for consistency, reporting, and system integration.

**Q: Do old documents keep their numbers when migrating to this system?**  
A: Existing documents retain their original numbers. Only new documents created after migration use the new format.

**Q: How do I identify which entity a document belongs to?**  
A: Look at the first two digits of the document number (EE). Each entity has a unique two-digit code.

**Q: What happens to sequences during year-end closing?**  
A: Sequences automatically reset to 00001 when the system detects a new year. No manual intervention is required.

---

## Appendix

### Document Number Format Reference Card

```
Format: EEYYDDNNNNN (11 characters)

EE = Entity Code (2 digits)
YY = Year (2 digits)  
DD = Document Code (2 digits)
NNNNN = Sequence Number (5 digits)

Example: 71250100001
        └─┬─┘│││└─┬─┘
          71 │││  00001
            25││
              01
              
Meaning: PT CSJ, Year 2025, PO, Number 1
```

### Quick Reference: Document Codes

| Code | Document |
|------|----------|
| 01 | PO |
| 02 | GRPO |
| 03 | Purchase Invoice |
| 04 | Purchase Payment |
| 06 | Sales Order |
| 07 | Delivery Order |
| 08 | Sales Invoice |
| 09 | Sales Receipt |
| 10 | Asset Disposal |
| 11 | Cash Expense |
| 12 | Journal |
| 13 | Account Statement |

---

## Revision History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2025-12-11 | Initial manual creation | System Documentation |

---

## Contact

For questions or issues regarding the document numbering system, please contact:

**System Administrator**  
**IT Department**  
Email: it@sarang-erp.com  
Phone: +62-XXX-XXXX-XXXX

---

**End of Manual**

