# Purchase Management Module User Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Features Overview](#features-overview)
4. [Purchase Orders (PO)](#purchase-orders-po)
5. [Goods Receipt PO (GRPO)](#goods-receipt-po-grpo)
6. [Purchase Invoices (PI)](#purchase-invoices-pi)
7. [Purchase Payments (PP)](#purchase-payments-pp)
8. [Purchase Analytics](#purchase-analytics)
9. [Common Tasks](#common-tasks)
10. [Troubleshooting](#troubleshooting)
11. [Quick Reference](#quick-reference)

---

## Introduction

### What is the Purchase Management Module?

The Purchase Management Module manages the full procure-to-pay workflow and supports multi-entity operations. It covers purchase requests, purchase orders, goods receipt, invoicing, and payments with automatic numbering, approvals, tax handling, and accounting integration.

### Who Should Use This Module?

-   Procurement team: create and approve purchase orders.
-   Warehouse staff: receive items against approved POs.
-   Accounting/AP: record invoices, allocate payments, manage accruals.
-   Management: monitor KPIs, approvals, and vendor performance.

### Key Benefits

-   End-to-end document chain (PO → GRPO → PI → PP).
-   Automatic numbering per entity using unified format (EEYYDDNNNNN).
-   Multi-currency handling with exchange rates.
-   Tax coverage for VAT and withholding tax.
-   Approval workflow and document closure tracking.

> **Note**: For detailed information about the document numbering system, see the [Document Numbering System Manual](document-numbering-system-manual-en.md).

---

## Getting Started

### Access Paths

-   Purchase Orders: `Purchase > Purchase Orders`
-   Goods Receipt PO: `Purchase > Goods Receipt`
-   Purchase Invoices: `Purchase > Purchase Invoices`
-   Purchase Payments: `Purchase > Purchase Payments`
-   Analytics: `Purchase > Analytics`

### Prerequisites

-   Vendors already created in Business Partner module.
-   Items/services set up in Inventory module.
-   Warehouses configured (non-transit warehouses only for manual selection).
-   Currency and tax codes configured in ERP Parameters.
-   Company entities configured with entity codes (see [Document Numbering System Manual](document-numbering-system-manual-en.md)).
-   Document numbering sequences automatically managed by the system.

### Typical Workflow

1. Create and approve Purchase Order.
2. Receive goods via GRPO (copies remaining lines).
3. Record Purchase Invoice (for received quantities).
4. Allocate Purchase Payment to invoices.
5. Monitor KPIs and aging in Analytics.

---

## Features Overview

-   **Order Management**: Full PO lifecycle with vendor-first workflow, item/service lines, tax handling, multi-currency, and single destination warehouse per PO.
-   **Receiving**: GRPO with vendor-first PO filtering, copy remaining lines, remaining quantity column, and warehouse defaulting from PO.
-   **Invoicing**: Purchase Invoices with automatic numbering, tax handling, and AP UnInvoice accounting for accruals.
-   **Payments**: Purchase Payments with allocation to invoices, multi-currency support, and closure tracking.
-   **Analytics**: AP aging, KPI cards (Purchases MTD, Outstanding AP, Pending Approvals, Open POs), supplier statistics, and recent invoices.

---

## Purchase Orders (PO)

### Key Concepts

-   **Numbering**: Uses unified format `EEYYDDNNNNN` where:
    -   `EE` = Entity Code (2 digits, e.g., 71 for PT CSJ, 72 for CV CS)
    -   `YY` = Year (2 digits, last 2 digits of year)
    -   `DD` = Document Code `01` for Purchase Order
    -   `NNNNN` = Sequence Number (5 digits, zero-padded)
    -   Example: `71250100001` = First PO of 2025 for entity 71
-   **Warehouse**: One destination warehouse per order (defaults to selected warehouse, non-transit only).
-   **Item vs Service Lines**: Items affect inventory; services do not.
-   **Taxes**: VAT and withholding tax per line or document.
-   **Approval Workflow**: Draft → Pending Approval → Approved.
-   **Closure**: Automatic closure based on GRPO/PI completion or configured auto-close days.

### Creating a PO

1. Go to `Purchase > Purchase Orders` and click **Add**.
2. Select **Vendor** (filters PO selection later).
3. Choose **Currency** and **Exchange Rate** if foreign currency.
4. Select **Warehouse** (destination).
5. Add **Lines**:
    - Choose **Item/Service**, enter quantity and price.
    - Set **VAT** and **WTax** if applicable.
6. Review **Totals** (Amount + VAT - WTax).
7. Save as Draft, then submit for approval.

### Editing & Approval

-   Draft POs can be edited; approved POs lock critical fields (vendor, warehouse, currency) unless reopened.
-   Approvers can approve/reject; once approved, PO becomes available for GRPO copying.

### Base/Target Navigation

-   From PO, use **Document Navigation** or **Relationship Map** to open related GRPO/PI/PP documents.

---

## Goods Receipt PO (GRPO)

### Key Concepts

-   **Numbering**: Uses unified format `EEYYDDNNNNN` where:
    -   `EE` = Entity Code (2 digits)
    -   `YY` = Year (2 digits)
    -   `DD` = Document Code `02` for Goods Receipt PO
    -   `NNNNN` = Sequence Number (5 digits)
    -   Example: `71250200001` = First GRPO of 2025 for entity 71
-   **Vendor-First Workflow**: Select vendor, then PO dropdown is filtered by vendor.
-   **Copy Remaining Lines**: Auto-pulls PO lines with remaining quantities.
-   **Remaining Qty Column**: Shows balance per line to avoid over-receipt.
-   **Warehouse Default**: Defaults to PO warehouse; can be overridden if allowed.
-   **Status**: Draft → Pending Approval → Approved.

### Creating a GRPO

1. Go to `Purchase > Goods Receipt`.
2. Select **Vendor** to load related approved POs.
3. Choose **Purchase Order**; click **Copy Remaining Lines**.
4. Verify **Remaining Qty** and adjust received qty.
5. Confirm **Warehouse** and taxes if needed.
6. Submit for approval; upon approval, stock updates and journal entries post.

### Common Tips

-   Use the item selection modal to filter items from the chosen PO only.
-   Keep partial receipts aligned with PO remaining quantities to avoid over-receipt blocks.
-   Use **Preview Journal** when available to validate inventory and AP entries.

---

## Purchase Invoices (PI)

### Key Concepts

-   **Numbering**: Uses unified format `EEYYDDNNNNN` where:
    -   `EE` = Entity Code (2 digits)
    -   `YY` = Year (2 digits)
    -   `DD` = Document Code `03` for Purchase Invoice
    -   `NNNNN` = Sequence Number (5 digits)
    -   Example: `71250300001` = First Purchase Invoice of 2025 for entity 71
-   **Source Data**: Typically created from GRPO quantities.
-   **Accounting**: Uses AP UnInvoice intermediate account; moves to AP on payment.
-   **Multi-Currency**: Capture exchange rate date; store base and foreign amounts.
-   **Closure**: Tracks invoiced vs received quantities and closes when complete.

### Creating a PI

1. Navigate to `Purchase > Purchase Invoices` and click **Add**.
2. Select **Vendor**; choose related GRPO/PO for line import (where supported).
3. Confirm **Currency** and **Exchange Rate**.
4. Add or import **Lines** with quantities, prices, VAT, and WTax.
5. Review **Totals**; ensure VAT/WTax align with tax rules.
6. Save and approve to post accrual entries.

### Payment Allocation Readiness

-   Approved invoices appear in Purchase Payment allocation lists.
-   Use **Document Navigation** to validate upstream GRPO/PO links.

---

## Purchase Payments (PP)

### Key Concepts

-   **Numbering**: Uses unified format `EEYYDDNNNNN` where:
    -   `EE` = Entity Code (2 digits)
    -   `YY` = Year (2 digits)
    -   `DD` = Document Code `04` for Purchase Payment
    -   `NNNNN` = Sequence Number (5 digits)
    -   Example: `71250400001` = First Purchase Payment of 2025 for entity 71
-   **Allocation**: Payments allocate to one or multiple approved invoices.
-   **Currency**: Supports payment currency with applied exchange rate.
-   **Accounting**: Credits cash/bank, debits AP; handles AP UnInvoice clearance.
-   **Closure**: Payment marks invoices closed when fully allocated.

### Creating a PP

1. Go to `Purchase > Purchase Payments` and click **Add**.
2. Select **Vendor** and **Payment Method/Account**.
3. Set **Currency** and **Exchange Rate** if applicable.
4. Select **Invoices** to allocate; system suggests outstanding balances.
5. Confirm allocations and submit. Approved payments update invoice status and document closure.

### Best Practices

-   Keep allocation exact to avoid small residuals.
-   For advance payments, record prepayment and later apply during invoicing per policy.

---

## Purchase Analytics

### Dashboards & KPIs

-   **AP Aging**: Buckets by due date to monitor outstanding AP.
-   **KPIs**: Purchases MTD, Outstanding AP, Pending Approvals, Open POs.
-   **Statistics**: PO/PI/GRPO counts and values; supplier performance indicators.
-   **Recent Invoices**: Quick view of latest AP documents.

### Usage Tips

-   Filter by entity, date range, and vendor for targeted reviews.
-   Investigate spikes in aging buckets and follow up on overdue invoices.
-   Monitor Pending Approvals to keep documents flowing.

---

## Common Tasks

-   **Create PO with multi-currency**: Set currency and exchange rate; prices stored in foreign and base amounts.
-   **Receive partial shipment**: Use GRPO copy remaining lines, adjust qty, approve; remaining balance stays on PO.
-   **Create invoice from receipt**: Import GRPO lines, verify quantities and taxes, approve PI.
-   **Allocate payment to multiple invoices**: Add allocations per invoice until totals match payment amount.
-   **View relationships**: Use Relationship Map to trace PO → GRPO → PI → PP chain.

---

## Troubleshooting

-   **PO not visible in GRPO dropdown**: Ensure PO is approved, vendor matches, and PO has remaining qty.
-   **Over-receipt blocked**: Check Remaining Qty column; adjust to available quantity.
-   **Invoice cannot allocate**: Confirm GRPO approved and invoice approved; verify outstanding amounts.
-   **Payment exchange rate mismatch**: Update exchange rate to match payment date; recalc allocations.
-   **Document closure incorrect**: Reopen document if allowed, refresh calculations, and ensure auto-close settings are correct.

---

## Quick Reference

-   **Numbering Format**: All documents use `EEYYDDNNNNN` format (11 characters):
    -   Purchase Order: Document Code `01` (e.g., `71250100001`)
    -   Goods Receipt PO: Document Code `02` (e.g., `71250200001`)
    -   Purchase Invoice: Document Code `03` (e.g., `71250300001`)
    -   Purchase Payment: Document Code `04` (e.g., `71250400001`)
    -   See [Document Numbering System Manual](document-numbering-system-manual-en.md) for details
-   **Workflow**: PO → GRPO → PI → PP with approval at each step.
-   **Taxes**: VAT + Withholding; applied per line or document per configuration.
-   **Multi-Currency**: Capture currency and exchange rate on PO/PI/PP; analytics report in base currency.
-   **Warehouses**: One destination warehouse per PO/GRPO; defaults from PO; transit warehouses excluded from manual selection.
-   **Entity Codes**: First two digits identify the legal entity (71=PT CSJ, 72=CV CS).
