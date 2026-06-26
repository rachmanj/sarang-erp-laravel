# FIFO Layer Repair — HELP reference (Sarang ERP)

Use this manual when **GR/GI approval**, **purchase invoice post**, or **inventory valuation** fails with errors like *Insufficient FIFO inventory layers to consume* — often after legacy duplicate purchase rows were removed or warehouse transfers left qty-on-hand without matching FIFO layers.

---

## Where to find it

- **Sidebar**: **Inventory → FIFO Layer Repair**
- **Menu Search** (navbar): type `FIFO`, `layer repair`, or `insufficient FIFO`
- **Item detail page**: when a FIFO item has a layer mismatch, a warning banner links to the repair screen for that item
- **Permission**: `inventory.adjust`

---

## When to use

Use **FIFO Layer Repair** for **FIFO valuation items** (`valuation_method = fifo`, physical items only) when:

- On-hand stock looks correct but **strict FIFO replay** fails on a past sale, transfer, or adjustment
- **Tolerant FIFO layer quantity** is lower than **current stock** (missing layers)
- You fixed duplicate PI inventory rows and downstream sales now cannot consume enough layers

**Do not use** for weighted-average items, services, or normal day-to-day stock adjustments — use **GR/GI** or standard inventory adjustment flows instead.

---

## How it works (self-service)

1. Open **FIFO Layer Repair** — the index lists FIFO items that need attention (search by code, name, or item ID).
2. Open an item — the screen shows:
   - Current stock vs transaction net
   - Strict replay error (if any)
   - **Deficits** (warehouse, shortfall qty, suggested unit cost, failing transaction)
   - **Stock after repair** (preview)
3. Click **Apply FIFO repair** — the system inserts **adjustment** transactions (`reference_type = fifo_layer_repair`) **backdated before** the first failing outbound movement, then recalculates warehouse stock and valuation.
4. Re-try the operation that failed (e.g. approve GR/GI, post PI) after repair shows **status: ok**.

---

## Related legacy data repair (administrator)

These are **Artisan commands**, not the in-app menu:

| Issue | Command |
|--------|---------|
| Duplicate PI purchase rows | `php artisan inventory:report-purchase-invoice-duplicates` then `inventory:fix-duplicate-transaction --invoice={PI_ID\|invoice_no} --dry-run` then `--force` |
| Single item duplicate cleanup | `inventory:fix-duplicate-transaction --item={code\|id} --dry-run` |
| SR bank account wrong on old posts | `php artisan sales-receipts:repair-bank-journals --dry-run` then `--force` |

After duplicate PI cleanup, run **FIFO Layer Repair** on affected FIFO items if GR/GI or valuation still fails.

---

## GR/GI after repair

**Goods Receipt (GR)** and **Goods Issue (GI)** approval uses **tolerant valuation refresh** so a remaining layer mismatch does not block approval while you plan data repair — but **correct FIFO history** still requires repair when strict replay fails.

If you already repaired physical stock manually, **cancel or edit** pending GR documents that would **double-count** receipt quantity.

---

## HELP keywords (English)

FIFO layer repair, insufficient FIFO, FIFO replay, layer deficit, inventory adjustment, duplicate purchase invoice, GR approval failed, GI approval failed, valuation error, fifo_layer_repair, inventory adjust permission

---

## Related manuals

- **Inventory valuation & costing**: `inventory-valuation-and-costing-manual-en.md`
- **GR/GI**: `inventory-module-manual.md` (GR/GI section)
- **Duplicate PI prevention**: `docs/action-plans/inventory-transaction-deduplication-prevention.md`

After editing this file, run **`php artisan help:reindex`**.
