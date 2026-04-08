# Financial statement reports (Balance Sheet, P&L, Cash Flow)

**Last updated**: 2026-04-08  
**Audience**: Developers, finance power users, implementers configuring COA prefixes for cash flow.

This document describes the **implemented** behaviour of the trading-style financial reports delivered via `ReportService` and `ReportsController`. It complements `docs/architecture.md` (routing and stack) and `docs/MODULES-AND-FEATURES.md` (feature list).

---

## Scope

| Report | Route name | Notes |
|--------|----------------|-------|
| Trial Balance | `reports.trial-balance` | All account types; debit/credit/balance. |
| GL Detail | `reports.gl-detail` | Lines by account + date range. |
| Balance Sheet | `reports.balance-sheet` | Types: `asset`, `liability`, `net_assets` only. |
| Profit & Loss | `reports.profit-loss` | Types: `income`, `expense`; bucketed by COA root (4/5/6/7). |
| Cash Flow (indirect) | `reports.cash-flow-statement` | See **Cash flow configuration** below. |

**Permission**: `reports.view` (see `routes/web/reports.php` middleware).

**Exports**: Query `export=csv` or `export=pdf`. CSV includes hierarchy columns (`depth`, `is_parent`) where applicable. PDF uses Dompdf (`App\Services\PdfService`).

---

## Hierarchical chart display (Balance Sheet & P&L)

- Rows are built from the **accounts** table (`parent_id`, `is_postable`, `code`, `name`, `type`).
- **Leaf/postable** balances come from **aggregated journal lines** (same rules as before).
- **Parent** rows show the **rollup** = own balance on that account (if any) plus all descendant balances in the subtree.
- Each row in JSON includes: `depth`, `is_parent`, `is_postable`, `amount` (rollup for parents).
- **Section totals** (assets, liabilities, equity; P&L section totals) remain the **sum of journal activity** in that section—not the sum of parent display lines—so figures stay reconciled to the trial balance.

**Balance sheet tie-out**: Response includes `totals.unclosed_pnl_cumulative` and `totals.difference_vs_unclosed_pnl`. The difference (Assets − Liabilities − Equity) matches cumulative **net** P&L in income/expense accounts until closing entries move results into equity.

---

## Cash flow (indirect method)

- **Not** the same as **Cash Ledger** (transaction list for one bank/cash account).
- Operating section starts from **net income** (same period as the P&amp;L) plus non-cash add-backs (e.g. depreciation by account pattern), then **working capital** deltas from **balance sheet display balances** at period start vs end, using **prefix lists** in `config/cash_flow.php`.
- Default **Trading COA** mappings include e.g. `2.1.2` tax payables, `1.1.4` input VAT / prepaid tax assets, `2.1.3` short-term borrowings, `3.1`/`3.2` equity financing (not `3.3` retained earnings, to avoid double-counting NI).
- **Investing / financing**: Non-current assets (`1.2`…), long-term liabilities (`2.2`…), plus configured short-term borrowings and equity financing prefixes.
- **Reconciliation**: `summary.reconciliation_difference` compares computed net cash change to the change in **cash and bank** accounts (`1.1.1` by default). Non-zero remainder usually means unmapped WC, equity moves, FX, or cash outside configured prefixes.

**Public helper**: `ReportService::balanceSheetDisplayTotalForPrefixes(?string $asOf, array $prefixes, bool $onlyPosted)` for audits and tests.

---

## Key files

| Area | Path |
|------|------|
| Service | `app/Services/Reports/ReportService.php` |
| Controller | `app/Http/Controllers/Reports/ReportsController.php` |
| Routes | `routes/web/reports.php` |
| Cash flow prefixes | `config/cash_flow.php` |
| Web UI | `resources/views/reports/balance-sheet.blade.php`, `profit-loss.blade.php`, … |
| PDF | `resources/views/reports/pdf/balance-sheet.blade.php`, `profit-loss.blade.php`, … |
| Menu / search | `resources/views/layouts/partials/menu/reports.blade.php`, `app/Services/MenuSearchService.php` |

---

## Automated tests

- `tests/Feature/ReportsTest.php` — routes, JSON shape, CSV/PDF responses.
- `tests/Feature/ReportAccuracyTest.php` — BS sections vs TB; BS difference vs unclosed P&L; cash flow cash delta and financing lines vs prefix math; P&L net income vs DB aggregation.

Run: `php artisan test tests/Feature/ReportsTest.php tests/Feature/ReportAccuracyTest.php`

---

## Operational notes

- **Posted only** is the default; use **Include unposted journals** where supported to match drafts.
- **Hide zero lines** suppresses immaterial lines; parents may still appear if any child is non-zero or structure requires it (visibility rules in `ReportService`).
- For **non-Trading** or custom COA, edit **`config/cash_flow.php`** (and retest reconciliation). If `1.1.4` is not input VAT in your COA, set `input_vat_prepaid_assets` to `[]` or the correct codes.

---

## Related documentation

- `docs/architecture.md` — routes and `ReportService` summary.
- `docs/decisions.md` — decision record for financial statements & cash flow configuration (2026-04-08).
- `docs/comprehensive-training/training-module-9-reporting.md` — training cross-reference.
