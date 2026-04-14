# `docs/manuals` — User & HELP Knowledge Base

This folder holds **Markdown manuals** for Sarang ERP. The **in-app HELP** assistant (navbar **?**) retrieves chunks from these files after an administrator runs:

```bash
php artisan help:reindex
```

## Authoring rules (for better HELP answers)

1. Use **`##` headings** — the indexer splits content by `##` sections; each section becomes a retrievable chunk.
2. Prefer **Indonesian** (`*-manual-id.md`) and **English** (`*-manual.md` / `*-manual-en.md`) pairs for the same topic when both audiences matter.
3. Include **keywords users type** (e.g. “faktur penjualan”, “Sales Invoice”, “transfer stok”).
4. After any meaningful edit here, run **`php artisan help:reindex`** on each deployed environment.

## Optional navigation hints

File **`help-navigation.json`** holds short **menu path** and **keyword** entries for questions like “where is Reports?”. Edit it when the sidebar changes, then **reindex**.

## Manuals index (inventory)

| Topic | Indonesian | English |
|-------|------------|---------|
| First steps / onboarding | `first-things-to-do-manual-id.md` | `first-things-to-do-manual.md` |
| In-app HELP & reindex | `in-app-help-manual-id.md` | `in-app-help-manual-en.md` |
| **Domain Assistant** (live data chat, robot icon) | `domain-assistant-manual-id.md` | `domain-assistant-manual-en.md` |
| Business partners (incl. **Account statement** tab vs Transactions) | `business-partner-module-manual-id.md` | `business-partner-module-manual.md` |
| Inventory | `inventory-module-manual-id.md` | `inventory-module-manual.md` |
| **Inventory valuation & costing** (FIFO/WAC, Unit Cost vs document price, go-live change) | `inventory-valuation-and-costing-manual-id.md` | `inventory-valuation-and-costing-manual-en.md` |
| Part numbers | `inventory-part-numbers-manual-id.md` | `inventory-part-numbers-manual-en.md` |
| Multi-UOM & pricing | `multi-measures-and-pricing-manual-id.md` | `multi-measures-and-pricing-manual.md` |
| Delete inventory items | `delete-inventory-items-guide.md` | — |
| Document numbering | `document-numbering-system-manual-id.md` | `document-numbering-system-manual-en.md` |
| Purchase (module) | `purchase-module-manual-id.md` | `purchase-module-manual.md` |
| Purchase Invoice | `purchase-invoice-manual-id.md` | `purchase-invoice-manual-en.md` |
| Purchase Payment | `purchase-payment-manual-id.md` | — |
| Delivery Order | `delivery-order-manual-id.md` | — |
| **Sales Invoice** | `sales-invoice-manual-id.md` | `sales-invoice-manual-en.md` |
| Customer / project | `customer-project-manual-id.md` | — |
| Warehouse stock transfer | `warehouse-stock-transfer-manual-id.md` | `warehouse-stock-transfer-manual.md` |
| Bank/Kas saldo awal | `setup-saldo-awal-bank-kas-manual-id.md` | — |
| Initial inventory | `initial-inventory-entry-manual-id.md` | `initial-inventory-entry-manual.md` |
| Approval roles | `approval-roles.md` | — |

Internal / analysis (not indexed as regular manual chunks): `inventory-manual-coverage-analysis.md` (excluded from `help:reindex` chunker).

## Deployment reminder

On production: migrate → set **`OPENROUTER_API_KEY`** → **`php artisan help:reindex`**. See `docs/architecture.md` (In-app HELP) and `docs/action-plans/in-app-help-assistant.md`.
