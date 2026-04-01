# Inventory Part Numbers Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Part Number Concepts](#part-number-concepts)
3. [Setting Up Part Numbers on Items](#setting-up-part-numbers-on-items)
4. [Selecting Part Numbers on Documents](#selecting-part-numbers-on-documents)
5. [Display on Print Templates](#display-on-print-templates)
6. [Copying to Downstream Documents](#copying-to-downstream-documents)
7. [Common Tasks](#common-tasks)
8. [Troubleshooting](#troubleshooting)

---

## Introduction

### What is a Part Number?

A **Part Number** is an alternate identifier for an inventory item, separate from the internal code (e.g. `BAT000001`). Examples include:

- **Customer part number**: Customer A uses `BOSCH-70A` for the same item
- **Manufacturer part number**: Vendor's manufacturer code
- **Supplier part number**: Code used by the supplier

### Why Use Part Numbers?

- **Customer-facing documents**: Show the customer's part number so documents are easy to recognize
- **Vendor-facing documents**: Show the supplier part number for clearer ordering
- **One item, many numbers**: One item can have multiple part numbers for different parties

### Who Uses This?

- **Purchasing**: Select supplier part numbers when creating POs
- **Sales**: Select customer part numbers when creating SOs, DOs, or Invoices
- **Warehouse**: Part numbers appear on delivery documents
- **Inventory admin**: Configure part numbers in item master data

---

## Part Number Concepts

### Per Item

- Each inventory item can have **multiple** part numbers
- One part number can be marked as **default**
- The default is used automatically when the item is selected on a document (can be changed per line)

### Per Document Line

- **Per line**: The user chooses which part number to use
- **"Internal code" option**: If no part number is selected, only the internal code is shown
- **Display**: Documents always show **Item Code** (internal) and **Part No.** (when selected)

### Part Number Table

| Column | Description |
|--------|-------------|
| Part Number | The part number (required, unique per item) |
| Description | Optional description (e.g. "Customer PN", "Supplier code") |
| Default | Check one to set as default per item |

---

## Setting Up Part Numbers on Items

### Steps

1. Go to **Inventory > Inventory Items**
2. Click **Edit** on the item to configure
3. Scroll to the **Part Numbers** section
4. Fill in:
   - **Part Number**: Enter the part number (e.g. `BOSCH-70A`)
   - **Description**: Optional (e.g. `Customer PN`)
   - **Default**: Check to set as default
5. Click **Add Part Number** to add to the list
6. Repeat for additional part numbers
7. Click **Update Item** to save

### Example

Item: `BAT000001` (Battery Bosch 70A Dry MF)

| Part Number | Description | Default |
|-------------|-------------|---------|
| BOSCH-70A | Customer PN | ✓ |
| 0 092 S0 105 | Manufacturer PN | |
| SUP-BAT-001 | Supplier code | |

### Notes

- Part numbers must be unique per item (no duplicates)
- Only one default per item
- Remove a part number with the **Remove** (trash) button

---

## Selecting Part Numbers on Documents

### Supported Documents

| Document | Selection Location |
|----------|--------------------|
| Purchase Order | Item line, dropdown after selecting item |
| Sales Order | Item line, dropdown after selecting item |
| Delivery Order | Copied from SO |
| Sales Invoice | Copied from DO |
| Sales Quotation | Item line |
| Purchase Invoice | Copied from PO (service) |
| GR/GI | Item line |

### How to Select (Purchase Order / Sales Order)

1. Create or edit a PO/SO
2. On a line, click **Search** (magnifying glass) to select an item
3. In the modal, choose the item
4. After selection, a **Part Number dropdown** appears below the item
5. Select:
   - **Internal code** (default when empty): Show only internal code
   - **Part number** (e.g. BOSCH-70A - Customer PN): Show part number on the document
6. If the item has a default part number, the dropdown is pre-filled with it

### Part Number Dropdown

- Appears **only** when the item has part numbers
- If the item has no part numbers, the dropdown is hidden
- Default part number is auto-selected when the item is chosen

---

## Display on Print Templates

### Columns on Printed Documents

| Column | Description |
|--------|-------------|
| Item Code | Internal item code (always shown) |
| Part No. | Selected part number (or "-" if none) |
| Description | Item name/description |

### Example Print Output

```
No | Item Code  | Part No.   | Description              | Qty  | Unit Price | Amount
---|------------|------------|---------------------------|------|------------|--------
1  | BAT000001  | BOSCH-70A  | Battery Bosch 70A Dry MF  | 1.00 | 1,051,843  | 1,051,843
2  | WOR000037  | -          | Accu Dimineralisasi       | 2.00 | 77,500     | 155,000
```

- Line 1: Part number selected → shows `BOSCH-70A`
- Line 2: No part number → shows `-`

### Supported Print Templates

- Purchase Order: All layouts (Standard, PT CSJ, CV Saranghae, Dot Matrix)
- Delivery Order: All layouts
- Sales Invoice: Print templates
- Sales Order: Show page
- Sales Quotation: Print templates

---

## Copying to Downstream Documents

Part numbers are **automatically copied** when documents are created from others:

| From | To | Part Number |
|------|-----|-------------|
| Sales Order | Delivery Order | ✓ Copied |
| Delivery Order | Sales Invoice | ✓ Copied |
| Sales Quotation | Sales Order | ✓ Copied |
| Purchase Order (service) | Purchase Invoice | ✓ Copied |

No need to re-select part numbers on downstream documents.

---

## Common Tasks

### Adding Part Numbers for a New Item

1. Inventory > Inventory Items > Edit item
2. Part Numbers > Enter Part Number, Description, Default
3. Add Part Number > Update Item

### Changing Part Number on a PO/SO Line

1. Open the PO/SO (create or edit)
2. On the line, use the Part Number dropdown
3. Select another part number or "Internal code"

### Setting the Default Part Number

1. Edit the item in Inventory
2. In Part Numbers, check **Default** for the desired part number
3. Update Item (only one default per item)

---

## Troubleshooting

### Part Number dropdown does not appear

- **Cause**: Item has no part numbers
- **Solution**: Add part numbers in Inventory > Edit item > Part Numbers

### Default part number not auto-selected

- **Cause**: No part number is marked as default
- **Solution**: Edit item, check Default for the desired part number

### Part number not showing on print

- **Cause**: Part number not selected on the document line
- **Solution**: Edit document, select part number in the dropdown per line

### Error "Part number already exists"

- **Cause**: Duplicate part number for the same item
- **Solution**: Use a different part number or remove the duplicate

---

## Quick Reference

| Action | Location |
|--------|----------|
| Add part number | Inventory > Edit Item > Part Numbers |
| Select part number on PO | PO Create/Edit > Line > Part Number dropdown |
| Select part number on SO | SO Create/Edit > Line > Part Number dropdown |
| View part number on print | Print document > "Part No." column |
