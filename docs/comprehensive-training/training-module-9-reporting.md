# Training Module 9: Reporting & Analytics

## Custom Reports and Data Visualization

**Duration**: 2 hours  
**Target Audience**: Analysts, managers  
**Prerequisites**: Previous modules

[Full draft content with scenarios, exercises, etc., following existing format]

---

## ERP reference (current application)

**Financial statements** (sidebar **Reports**, permission `reports.view`): **Balance Sheet** and **Profit & Loss** show the chart of accounts **hierarchically** (parents display the **sum of child accounts**; section totals match journal activity). **Cash Flow Statement** uses the **indirect** method; working capital and financing buckets depend on **account code prefixes** in `config/cash_flow.php` (Trading defaults include tax payables, input VAT/prepaid tax, short-term borrowings, etc.). The balance sheet explains any **accounting equation difference** using **unclosed P&L** (cumulative income/expense not yet closed to equity). Full technical detail: **`docs/financial-statements-reports.md`**.

**Document Creation Logs** (sidebar **Reports → Document Creation Logs**, permission `reports.open-items`): Lists operational documents across PO, GRPO, PI, PP, SO, DO, SI, and SR in one table, sorted by **when the ERP record was created** (`created_at`). Use filters to narrow by document type and party. **Created by** reflects the **`created_by`** user on the document when the system stored it (older data may be blank if created before the column existed or before backfill).

**Open Items** (same permission family): Focuses on **open** documents and aging, not an all-time creation log.

**Manual journals**: Journals track the posting user via **`posted_by`** on the `journals` table, not a separate `created_by` field on that table.
