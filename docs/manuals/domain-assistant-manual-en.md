# Domain Assistant — Sarang ERP

## What is Domain Assistant?

**Domain Assistant** is a separate feature from **HELP** (the **?** book icon).

- **Domain Assistant** — **robot icon** in the top navbar. It answers questions using **live data** from Sarang ERP (sales orders, **sales invoices**, **purchase invoices**, purchase orders, delivery orders, goods receipts, inventory, business partners). It can run **multiple chat sessions** (threads) and keeps **conversation history** for your user account.
- **HELP** — **book icon** (`?`). It answers **how-to** questions from written manuals only; it does **not** query your company database and does **not** store chat history.

Both features use the server-side **OpenRouter** API; the API key never appears in the browser.

---

## Who can use it?

Your administrator must grant the permission **`access-domain-assistant`** to your role. If you do not see the robot icon, you do not have access.

The feature must be **enabled** in server configuration (`DOMAIN_ASSISTANT_ENABLED`) and **`OPENROUTER_API_KEY`** must be set.

---

## How to open Domain Assistant

1. Sign in to Sarang ERP.
2. In the **top navbar**, click the **robot** icon — to the left of the HELP book icon.
3. The **Domain Assistant** page opens with a dark “terminal” style: **sessions** on the left, **chat** on the right.

---

## Sessions (threads)

- **New session** — creates an empty conversation. Use it for a new topic.
- Click a session in the list to **switch** context. Older messages for that session load again.
- You can **delete** a session (hover and remove).  
- There is a **daily message limit** (configured by administrators). If you hit the limit, try again the next day.

---

## What you can ask (examples)

- **Sales Invoice (AR / faktur penjualan)** — e.g. “Show details for invoice **71260800080**” or “List invoices for customer X”.  
  The assistant searches **Sales Invoices**, not the same thing as **Sales Orders**.
- **Purchase Invoice (AP / faktur pembelian)** — e.g. “Show detail for Purchase Invoice **72260300114**”.  
  The assistant searches **Purchase Invoices**, not the same thing as **Purchase Orders** (PO).
- **Sales Order** — open orders, customer name, date range.
- **Delivery Order (DO / delivery note)** — e.g. “Check status of DO **71260700222**”. The assistant looks up by **DO document number** (not only customer name).
- **Purchase Order**, **Goods Receipt (GRPO)** — search by supplier/customer and dates where applicable.
- **Inventory items** — by code/name, category, low stock.
- **Business partners** — customers and suppliers by name or code.

Ask in **English** or **Bahasa Indonesia**; the model usually follows your language.

---

## Sales Invoice vs Sales Order (important)

- **Sales Invoice** = posted AR invoice / **faktur penjualan** (document number like invoice **71260800080**).  
- **Sales Order** = order document (**SO**), not the same as an invoice.

If you use an **invoice number**, say “invoice” or “faktur” so the assistant looks in **Sales Invoices**. Asking for “detail invoice” should return **header and line items** when the system supports it.

## Purchase Invoice vs Purchase Order (important)

- **Purchase Invoice** = AP invoice / **faktur pembelian** (posted supplier invoice document number).
- **Purchase Order** = **PO** — not the same as a purchase invoice.

Use “faktur pembelian”, “purchase invoice”, or “PI” with the document number so the assistant queries **Purchase Invoices**, not purchase orders.

Invoices may belong to different **company entities** (e.g. PT vs CV). If you have permission **“see all record switch”**, you may see an **ALL BRANCHES** style control to widen visibility; otherwise searches may follow your default entity for list views, while **lookup by invoice number** is designed to find the document across active entities.

---

## Privacy and logging (different from HELP)

- **HELP** does **not** store your Q&A in the database.  
- **Domain Assistant** **does** store **messages** (your threads) and may write **request logs** (success/error, tools used, duration, IP) for **operations and audit**. Administrators can review aggregated logs in **Admin → Assistant report** (if your role allows **view-admin**).

Do not paste secrets or personal data you would not want stored in company systems.

---

## Bug reports and product ideas

Use **HELP → Report / request** for bugs and feature requests. Domain Assistant is for **data questions**, not the official IT ticket channel unless your organization says otherwise.

---

## For administrators (short)

- **Permission**: `access-domain-assistant`; optional **`see-all-record-switch`** for “all branches” behaviour where implemented.  
- **Environment**: `DOMAIN_ASSISTANT_ENABLED`, `DOMAIN_ASSISTANT_MODEL`, `DOMAIN_ASSISTANT_DAILY_LIMIT`, same **`OPENROUTER_API_KEY`** as HELP.  
- **Reindex**: Domain Assistant does **not** use `help:reindex`; that command is only for **HELP** manuals.  
- **Documentation**: `docs/action-plans/domain-assistant.md`, `docs/architecture.md` (Domain Assistant section).

---

## Related

- **HELP** (manuals, how-to): `in-app-help-manual-en.md` in this folder.  
- **Sales Invoice** workflow (posting, screens): `sales-invoice-manual-en.md`.
