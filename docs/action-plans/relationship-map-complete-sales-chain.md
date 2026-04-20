# Action plan: Complete sales document chain on Relationship Map

**Status:** In progress — **T1/T2/T3 core behaviour shipped** in API (2026-04-22); see `docs/relationship-map-implementation-summary.md`  
**Last updated:** 2026-04-22  
**Related:** `docs/relationship-map-implementation-summary.md`, `docs/architecture.md` (Document Navigation & Journal Preview), `app/Services/DocumentRelationshipService.php`, `app/Http/Controllers/DocumentRelationshipController.php`, `resources/views/components/relationship-map-modal.blade.php`

---

## 1. Problem

The **Document workflow** modal renders a Mermaid graph from **`getNavigationData()`**, which only includes **one hop** of **base** (parents) and **target** (children) documents resolved via **`document_relationships`** (`relationship_type` `base` / `target`).

Effects observed in production-like data:

- From a **Delivery Order**, users often see **DO → Sales Invoice** only.
- **Sales Order** is omitted when the SO→DO `document_relationships` row is missing, stale, or the SO is filtered by permission—even though **`delivery_orders.sales_order_id`** may still point to the correct SO.
- **Sales Receipt** is omitted when opening the map from **DO** because SR is linked to **SI** (via allocations / `document_relationships`), not directly to DO—one hop is insufficient.

The product goal is: **from any sales document in the chain, the chart should show the full path**  
**SO → DO → SI → SR** (and optionally **Sales Credit Memo** when present), subject to user permissions.

**Extended scope (same programme, phased):**

- **Sales Quotation** as an upstream node when the chain started from a quotation: e.g. **`sales_quotations.converted_to_sales_order_id`** → SO, SI created with **`sales_quotation_id`** / quotation reference in description, and/or explicit **`document_relationships`** once sync is implemented.
- **Trading overlay:** **Goods Receipt PO** (and related purchase legs) linked to **Sales Invoice** via `sales_invoice_grpo_combinations` / existing `initializeSIRelationships()` edges, so trading-heavy invoices show **GRPO → SI** (and optionally **PO → GRPO** when navigable from those GRPOs) in the same diagram without replacing the core SO→DO→SI→SR spine.

---

## 2. Objectives

| ID | Objective |
|----|------------|
| O1 | **Sales Order** appears upstream of **Delivery Order** when a real link exists (`document_relationships` and/or `sales_order_id`). |
| O2 | **Sales Receipt** appears downstream of **Sales Invoice** when allocations exist (`document_relationships` and/or `sales_receipt_allocations`). |
| O3 | No duplicate nodes/edges; cycles prevented (defensive). |
| O4 | **Permission parity** with today: hide documents the user cannot `*.view`; do not leak existence via labels/amounts where policy requires (match existing `filterByUserPermissions` behavior). |
| O5 | Acceptable performance: one API call; bounded work (depth cap + dedupe); cache strategy documented. |
| O6 | **Sales Quotation** appears when applicable (FK / persisted edges), with labels and permissions consistent with sales-quotation routes. |
| O7 | **Trading overlay:** GRPO (and optional upstream PO) appears for SI rows tied to GRPO via combination table / `document_relationships`; does not collapse the sales chain. |

### 2.1 Delivery tiers (for scheduling)

| Tier | Scope |
|------|--------|
| **T1 — Core sales chain** | SO, DO, SI, SR, optional CM (O1–O5). |
| **T2 — Quotation** | SQ node + edges to SO (and to SI if direct quotation→SI is modeled) (O6). |
| **T3 — Trading** | GRPO → SI, and expansion to PO → GRPO where those rows exist (O7). |

---

## 3. Current technical baseline (for implementers)

- **Navigation:** `DocumentRelationshipService::getNavigationData()` → `getBaseDocuments` / `getTargetDocuments` (cached ~60 minutes per `DocumentRelationshipService::CACHE_DURATION`).
- **Diagram:** `DocumentRelationshipController::generateMermaidDiagram()` builds nodes/edges from that single-level payload + `addCrossRelationships()` (purchase PO↔PI only today).
- **Known persistence:** SO→DO rows created in `DeliveryService::createDeliveryOrderFromSalesOrder` via `createBaseRelationship` / `createTargetRelationship`; backfill via `initializeDORelationships()`. DO→SI from pivot / SI store paths; SR from `initializeSRRelationships()` / payment flows.

---

## 4. Recommended approach (hybrid)

Implement a **sales-chain expansion** step used **only for relationship-map API** (keep Base/Target toolbar buttons behavior unchanged unless product asks otherwise).

1. **Start** from the same root model as today (the document whose map was opened).
2. **Collect nodes and edges** using:
   - **A. Graph walk (BFS):** From the root, repeatedly load bases and targets for documents whose morph class is in an **allowlist**. **T1:** `SalesOrder`, `DeliveryOrder`, `Accounting\SalesInvoice`, `Accounting\SalesReceipt`, optionally `Accounting\SalesCreditMemo`. **T2:** add `SalesQuotation` (or the app’s quotation model morph class). **T3:** add `GoodsReceiptPO`, `PurchaseOrder` when following trading edges from SI (see below). Use a **fixed max depth** (e.g. 6–8 when T3 is on) and **visited** `(morphClass, id)` deduplication.
   - **B. FK / pivot enrichment (deterministic glue):**
     - **`DeliveryOrder`:** if `sales_order_id` is set and SO node not yet in the set, load SO and add edge **SO → DO** with the same semantic label as today’s `getRelationshipLabel('Sales Order','Delivery Order')`.
     - **`SalesOrder` (T2):** resolve **`SalesQuotation`** where `converted_to_sales_order_id` = SO id (or future FK on SO if added); add **SQ → SO**. For **SI**, use stored quotation linkage / request-time `sales_quotation_id` / description parsing only as a fallback—prefer **`document_relationships`** once written.
     - **`SalesInvoice`:** load SRs via existing relationships or `sales_receipt_allocations` / `SalesReceipt` relations; add **SI → SR** edges if rows exist and user may view SR.
     - **`SalesInvoice` (T3):** for each `sales_invoice_grpo_combinations` (or equivalent) row, add **GRPO → SI** (reuse labels from purchase side); optionally walk **PO → GRPO** using existing `document_relationships` or `goods_receipt_po.purchase_order_id`.
3. **Merge** enriched nodes/edges into the structure passed to Mermaid (or replace `generateMermaidDiagram` input with this expanded graph).
4. **Permissions:** Run each discovered model through the same **`filterByUserPermissions`** rules before emitting nodes.

**Why hybrid:** BFS alone still misses edges if `document_relationships` is incomplete; FK enrichment matches user mental model (“this DO was created from SO #…”) and mirrors the purchase-side lesson in `relationship-map-implementation-summary.md` (persist + sync + backfill).

---

## 5. Work breakdown

### Phase 1 — Design & API contract

- [ ] Confirm whether **toolbar** “Base / Target” stays **single-hop** or switches to “full chain” (recommend: **keep single-hop** for buttons; **full chain only in modal** to avoid navigation surprises).
- [ ] Define **max depth**, **sales morph allowlist**, and **edge labels** (reuse `DocumentRelationshipController::getRelationshipLabel` where possible).
- [ ] Decide **Credit Memo** scope: if `sales_credit_memos.sales_invoice_id` is set, show **SI → CM** in the same graph (optional but low effort if SI is already on the graph).

### Phase 2 — Service layer

- [ ] Add something like **`DocumentRelationshipService::getExpandedSalesChainGraph(Model $root, ?User $user): array`** returning `['nodes' => ..., 'edges' => ...]` in the same shape `generateMermaidDiagram` expects (or a DTO that the controller maps).
- [ ] Implement BFS over `getBaseDocuments` / `getTargetDocuments` with **visited set** and **depth limit**.
- [ ] Implement **FK enrichment** for DO→SO and SI→SR (and optional SI→CM).
- [ ] **Clear cache** policy: document when `clearDocumentCache` must run from `SalesReceiptController` / allocation changes if SR edges are relationship-table-only (align with existing cache busts).

### Phase 3 — Controller & response

- [ ] In `DocumentRelationshipController::getRelationshipMap`, either:
  - call the expanded builder **instead of** single-hop `generateMermaidDiagram`, or
  - add query flag `?full_chain=1` defaulting to **true** for the modal only (if you need a rollback switch).
- [ ] Ensure **`isCurrent`** remains true only for the opened document.
- [ ] Reuse **`getDocumentModelById`**, **`getDocumentReference`**, **`formatDate`** for consistency.

### Phase 4 — Frontend

- [ ] `relationship-map-modal.blade.php`: verify layout when **4+ nodes** (zoom, min-height, long labels); no change strictly required if node payload is backward compatible.
- [ ] Optional: legend text “Shows full sales chain when available.”

### Phase 5 — Data hygiene (operations)

- [ ] Run or schedule **`php artisan db:seed --class=DocumentRelationshipSeeder`** (or documented initializer) on environments with legacy DO/SI/SR so `document_relationships` matches FKs—reduces reliance on enrichment alone.
- [ ] Optional Artisan: **`documents:repair-sales-relationships`** that upserts SO→DO from `delivery_orders.sales_order_id` and SI→SR from allocations (idempotent)—only if seeding is too heavy for some deployments.

### Phase 6 — Tests

- [ ] Feature test: open map from **DO** with `sales_order_id` set but **without** SO base row → graph still contains **SO** after implementation.
- [ ] Feature test: DO with **SI** and **SR** allocated → graph contains **SR**.
- [ ] Test: user **without** `sales-orders.view` does not see SO node (matches O4).
- [ ] Test: **cycle** protection (malformed duplicate rows) does not infinite-loop.

### Phase 7 — Documentation

- [ ] Update **`docs/relationship-map-implementation-summary.md`** with “Sales chain expansion” subsection and link to this file.
- [ ] Short note in **`docs/architecture.md`** under Document Relationship Map if a dedicated section exists.
- [ ] Keep **`.cursorrules`** cross-reference and **`docs/decisions.md`** in sync when behaviour ships.

### Phase 8 — Sales Quotation (T2)

- [ ] Use **`SalesQuotation::converted_to_sales_order_id`** for **SQ → SO** enrichment; confirm whether **`sales_invoices`** gains a nullable **`sales_quotation_id`** (or rely on `document_relationships` / description) for **SQ → SI** when SI is created from quotation.
- [ ] Persist **SQ → SO** / **SQ → SI** in `document_relationships` on convert/create where missing, or document FK-only enrichment; align with `QuotationConversionService` / `SalesInvoiceController` store paths.
- [ ] Extend **`DocumentRelationshipController`** / modal labels for **Sales Quotation** type; ensure permission key exists in `DocumentRelationship::getDocumentPermissionMap()` (add if missing).
- [ ] Tests: map from **SO** with `quotation_id` shows **SQ**; permission filter for quotation view.

### Phase 9 — Trading overlay (T3)

- [ ] Merge **SI ↔ GRPO** edges from `initializeSIRelationships()` / live sync into the **expanded** graph builder (not only one-hop).
- [ ] Optional: from each GRPO on the graph, pull **PO** via `document_relationships` or `purchase_order_id` to show **PO → GRPO → SI** branch beside the sales chain.
- [ ] UX: visually distinguish **sales** vs **purchase/trading** subtrees (e.g. edge style or subgraph) if graph gets busy—defer to design.
- [ ] Tests: SI with GRPO combination shows **GRPO** node; user without `purchase-orders.view` does not see PO/GRPO if policy requires.

---

## 6. Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Performance on wide graphs (many SRs) | Cap SR nodes to receipts with allocations to SIs already on graph; max depth. |
| Stale cache hides new SR | Bust `target_documents_*` / `base_documents_*` keys when posting SR or changing allocations (same pattern as other sync methods). |
| Wrong morph class in old rows | Reuse seeder normalization; FK enrichment uses models, not raw strings. |
| Multi-SI / multi-DO | BFS naturally includes multiple SIs; edge dedupe by `(from,to,label)` or stable key. |
| T3 + T1 on one canvas | Use max depth and optional subgraph/styling so PO/GRPO does not drown out SO→DO→SI; cap orphan purchase nodes. |

---

## 7. Definition of done

- **T1:** Opening the map from **DO**, **SI**, or **SO** shows **all** of SO / DO / SI / SR that exist for that business chain (within permission), not only adjacent documents.
- **T2:** When a quotation applies, **SQ** appears and links coherently to **SO** (and **SI** if in scope).
- **T3:** When SI is tied to **GRPO**, the trading branch appears without breaking T1 layout.
- No regression for **purchase** chain behavior (`addCrossRelationships`, PO→GRPO→PI→PP).
- Phase 6 / Phase 9 tests pass; staging UAT signed off on 3–5 real document numbers per tier.

---

## 8. Deferred / optional (not required for T1–T3 sign-off)

- Automatic **re-layout** algorithm beyond Mermaid’s default TB/LR—only if UX testing requires it after T3 graphs become dense.
