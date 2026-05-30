# Cash Expenses — HELP reference (Sarang ERP)

Use this file for in-app HELP (navbar **?**) about **Cash Expenses** (petty-cash / direct cash payments posted immediately to the GL). After edits, run **`php artisan help:reindex`**.

---

## What is a Cash Expense?

A **Cash Expense** records a payment from a **cash or bank account** to an **expense account** in one step. The document is **posted on save** (no draft workflow). Each expense gets an entity-aware number (document code **11**, format `EEYYDDNNNNN` per company entity default).

**Keywords:** cash expense, petty cash, kas keluar, direct expense, CEV, code 11.

---

## Where to open Cash Expenses

1. Sign in to Sarang ERP.
2. Sidebar **Accounting** → **Cash Expenses** (list at `/cash-expenses`).
3. **New Expense** opens the create form (`/cash-expenses/create`).

**Keywords:** where is cash expense menu, accounting cash expenses, `/cash-expenses`.

---

## List and date range filter

On the **Cash Expenses** index:

- Use the **date range** field (calendar icon) to filter by expense **date**.
- Presets include **Today**, **Yesterday**, **Last 7 Days**, **Last 30 Days**, **This Month**, **Last Month**.
- Click **Apply** in the picker to filter; **Clear** removes the range and shows all dates.
- The **Apply** button next to the range reloads the table without changing dates.
- The grid is server-side (sort/search/pagination); columns include date, description, expense account, cash account, creator, amount, and **Print**.

**Keywords:** filter cash expense by date, date range cash expenses, list petty cash, filter kas keluar.

---

## Creating a cash expense

1. **Cash Expenses** → **New Expense**.
2. Enter **date**, **expense account**, **cash account**, **amount**, optional **description**, and optional **project / department** dimensions.
3. Save. The system posts the journal: **Debit** expense, **Credit** cash/bank, and redirects to the list with a success message.

**Keywords:** new cash expense, post cash payment, debit expense credit cash.

---

## Print

From the list, use the **Print** action on a row to open the printable layout in a new tab.

**Keywords:** print cash expense, cetak pengeluaran kas.

---

## Company entity and numbering

New cash expenses use the **default company entity** for document numbering (`company_entity_id` on the record). Entity-specific sequences follow the unified numbering rules (see **Document numbering** manual).

**Keywords:** cash expense number, entity 11, default entity cash expense.
