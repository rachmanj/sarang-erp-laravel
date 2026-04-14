# Inventory valuation and costing — HELP reference (Sarang ERP)

This document supplements the **Inventory Module** manual. It explains how Sarang ERP handles **valuation methods**, **inventory cost / COGS**, and **document commercial prices**. Use it for HELP questions such as: *FIFO*, *weighted average*, *unit cost*, *purchase vs selling price*, *change valuation method*.

---

## Valuation method choices on inventory items

On the item create/edit form, the system offers **three** valuation methods:

- **FIFO** — screen label; cost logic is driven from purchase history (see below).
- **LIFO** — cost is computed using a last-in style layer walk (per application code).
- **Weighted average** — label for an average-cost style method based on purchase lines.

**Note:** There is **no** separate “Manual” valuation mode on the item; stock adjustments still capture a unit cost on the adjustment transaction itself.

---

## How unit cost is calculated (inventory value and COGS)

The calculation uses **purchase** transactions only: it sums purchase amounts and purchase quantities across the **full purchase history** recorded for the item.

- **FIFO and Weighted average** — in the **current implementation the formula is the same**:  
  **average cost = total cost of all purchases ÷ total quantity of all purchases.**  
  This is a **purchase weighted average**, **not** strict classical FIFO layer consumption (oldest layers first) for every sale line.

- **LIFO** — the application computes cost by walking purchase layers starting from the **newest** until the model’s remaining quantity is covered (see inventory service code).

- If there are **no** purchase transactions yet, cost may fall back to the item’s **default purchase price** on the master record.

**On-screen meaning:** In **Recent Transactions**, **Unit Cost** is the **inventory valuation cost** used for that movement—not the customer selling price, and not always identical to a specific invoice line price.

---

## Unit Cost vs Purchase / Selling price (document) columns

On the item detail page **Recent Transactions** table:

- **Unit Cost** — **inventory cost per unit** under the app’s valuation and posting rules (including outbound sales shipments).
- **Purchase / Selling price (document)** — the **commercial unit price from the source document** when the system can resolve it (purchase invoice line; Delivery Order or Sales Order line for sales).

Use **document price** for **what was bought/sold at**; use **Unit Cost** for **inventory costing**.

---

## Trading businesses: buy-to-order and low stock

Many distributors purchase only after a customer order, so **on-hand stock is often small**. In that situation:

- Differences between “pure” **FIFO layers** and **weighted averages** are often **smaller** than for deep, long-held stock.
- The app’s **FIFO** option currently behaves like a **purchase weighted average** (same numeric path as weighted average in code), not full batch traceability.
- If you need **true batch/PO traceability**, discuss with your accountant whether your reporting requires **lot tracking** or processes beyond default costing.

---

## Changing valuation method after go-live

**Possible in principle**, but it is an **accounting policy** decision—not only a UI change:

- Future costing follows the new method’s rules; **historical posted figures are not automatically restated** as if the old method never existed.
- You may need a **cut-over date**, **manual adjustments**, or **prospective** treatment—per your reporting standard and auditor.
- Check **tax and statutory** rules before changing methods.

Document the chosen method and any change dates in your internal policy.

---

## HELP keywords (English)

inventory valuation, FIFO, LIFO, weighted average, unit cost, COGS, inventory cost, purchase price, selling price, purchase invoice, delivery order, sales order, change valuation method, trading, low stock, Recent Transactions, inventory item
