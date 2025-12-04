## Multi-Entity Company Profile Enhancement

### Goal

Enable purchasing and sales documents to reference different legal entities (PT Cahaya Sarange Jaya `71`, CV Cahaya Saranghae `72`) while sharing the same inventory/master data. Each document must render the correct letterhead and follow the new numbering format `EEYYDD99999`.

---

### Implementation Phases

#### Phase 1 – Foundation & Schema

1. Create `company_entities` table and seed PT CSJ (code `71`) + CV CS (code `72`) with provided logos.
2. Introduce `document_sequences` table and `DocumentNumberService`.
3. Add `company_entity_id` to purchasing and sales header tables (PO, GRPO, PI, SQ, SO, DO, SI, etc.), update migrations/factories, and ensure FK constraints.
4. Document architecture change outline in `docs/architecture.md` draft section.

#### Phase 2 – Services & Domain Logic

1. Build `CompanyEntityService` for entity CRUD, letterhead assets, and convenience getters.
2. Integrate numbering service into document creation flows (controllers/jobs).
3. Update reporting queries and repositories to accept `company_entity_id` filters.
4. Record design decisions in `docs/decisions.md` and key learnings in `MEMORY.md`.

#### Phase 3 – UI/UX & Letterhead

1. Extend Company Profile admin UI to manage entities (tabs or selector) with logo upload + contact details.
2. Add entity selector to document creation/edit forms, display active entity badges, and persist selections.
3. Update PDFs/emails/print views to pull logos + contact info via `CompanyEntityService`.
4. Ensure SweetAlert confirmations and previews reflect chosen entity letterhead.

#### Phase 4 – Reporting, QA & Documentation Sweep

1. Update dashboards, listings, and exports to filter/summarize by entity.
2. Add tests (feature + unit) covering numbering, entity assignment, and sequencing edge cases.
3. Refresh `docs/todo.md` (track progress + move completed work), add Mermaid diagram to `docs/architecture.md`, and note any backlog items.
4. Browser-test key flows using entity switching; verify numbering matches `EEYYDDNNNNN`.

---

### Implementation Workstreams

#### 1. Schema & Seeds

-   Create `company_entities` table
    -   Columns: `id`, `code`, `name`, `legal_name`, `tax_number`, `address`, `phone`, `email`, `website`, `logo_path`, `letterhead_meta` (JSON), `is_active`, timestamps.
    -   Seed with two entities referencing `public/logo_pt_csj.png` and `public/logo_cv_saranghae.png`.
-   Add `company_entity_id` (FK) to purchasing and sales header tables: `purchase_orders`, `goods_receipt_pos`, `purchase_invoices`, `sales_quotations`, `sales_orders`, `delivery_orders`, `sales_invoices` (and any other printable document).
    -   Since project is pre-launch, modify existing migrations directly and keep FK constraints with cascade restrict.
    -   Ensure factories and seeders pick a valid entity.

#### 2. Numbering Service

-   Create `document_sequences` table with `company_entity_id`, `document_code`, `year`, `current_number`, timestamps, unique composite index.
-   Implement `DocumentNumberService` that:
    -   Accepts `company_entity_id`, `document_code`, `issue_date`.
    -   Wraps logic in DB transaction, `lockForUpdate()` the sequence row (create on demand).
    -   Generates formatted number `EEYYDDNNNNN`:
        -   `EE` from entity code (`71`, `72`).
        -   `YY` from `issue_date`.
        -   `DD` from document mapping (`01` PO, `02` GRPO, `03` PI, `05` SQ, `06` SO, `07` DO, `08` SI).
        -   `NNNNN` zero-padded sequence.
    -   Expose helper for retrieving next number + preview.
-   Update document creation controllers to call the service and persist the generated number.

#### 3. Services & Domain Layer

-   Create `CompanyEntityService` to:
    -   Fetch active entities for dropdowns.
    -   Provide letterhead assets (logo path, legal info).
    -   Return defaults for PDF rendering and email templates.
-   Extend existing `CompanyInfoService` or replace references with the new service where entity-specific data is required.
-   Update repositories/report queries to include `company_entity_id` filters.

#### 4. UI & UX

-   Update Company Profile admin screen:
    -   Add tabbed or list-based UI to edit each entity (name, address, contact, NPWP, website, logo upload).
    -   Keep global parameters for system-level defaults.
-   Document Forms (PO/GRPO/PI/SQ/SO/DO/SI):
    -   Add entity selector (required).
    -   Show active entity badge and preview letterhead.
    -   Persist selection in Vue/Blade components and API requests.
-   Printed/PDF documents:
    -   Load logos from `public/logo_pt_csj.png` and `public/logo_cv_saranghae.png` via `CompanyEntityService`.
    -   Display legal name, address, tax number per entity.

#### 5. Reporting & Accounting

-   Update list pages, exports, dashboards to filter/group by entity.
-   Ensure journal postings (if any) record `company_entity_id` to enable future multi-entity GL separation.

#### 6. Documentation & Decision Tracking

-   `docs/architecture.md`: add multi-entity section + Mermaid diagram for document generation flow and numbering service.
-   `docs/decisions.md`: log decision about centralized multi-entity sequencing.
-   `docs/todo.md`: add tasks per workstream, move completed work to “Recently Completed”.
-   `MEMORY.md`: capture discovery of entity codes, logos, numbering rules.

---

### Open Items / Questions

1. Need confirmation on additional per-entity fields (bank accounts, VAT IDs).
2. Confirm whether returns/credit memos must follow the same numbering now or in future phase.
3. UX approval for entity selector placement on each document form.

Once these are confirmed, proceed with development in the order above: schema → services → UI → reporting → documentation sweep.

---

### Phase 1 Progress Log

- **2025-11-28** – Schema foundation delivered  
    - Added `company_entities` table with logo/contact metadata and seeded PT Cahaya Sarange Jaya (`code 71`) plus CV Cahaya Saranghae (`code 72`).  
    - Extended `document_sequences` table with entity-aware columns (`company_entity_id`, `document_code`, `year`, `current_number`) to prepare the numbering service refactor.  
    - Added nullable `company_entity_id` FKs to all purchasing/sales headers (PO, GRPO, PI, PP, SO, DO, SI, SR) for per-entity tracking ahead of UI integration.  
    - Updated documentation set (`docs/architecture.md`, `docs/decisions.md`, `MEMORY.md`, `docs/todo.md`) to reflect the new multi-entity foundation.

### Phase 2 Progress Log

- **2025-11-28** – Service & numbering layer updated  
    - Introduced `CompanyEntityService` (default entity resolution + helper APIs) and added `company_entity_id` relationships to PO/GRPO/PI/PP/SO/DO/SI/SR models.  
    - Refactored `DocumentNumberingService` to generate the new `EEYYDD99999` format per entity/document/year while preserving legacy prefixes for other docs.  
    - Updated DocumentSequence model logic plus all purchase/sales controllers and services to:  
        - Persist `company_entity_id` (defaulting to seeded entity or inheriting from base document).  
        - Pass entity context when requesting numbers for PO, GRPO, PI, SO, DO, SI, SR.  
        - Propagate entity context through copying flows (e.g., GRPO from PO, Delivery Order from Sales Order).  
    - DeliveryService now generates DO numbers via the numbering service instead of hardcoded strings and copies the entity from its Sales Order.  
    - Documentation refreshed (architecture/decisions/todo/memory) to capture the new service layer and numbering behavior.
