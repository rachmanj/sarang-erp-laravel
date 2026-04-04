# In-App HELP — Sarang ERP

## What is HELP?

**HELP** is the in-app assistant ( **?** icon in the top navbar). You can:

- Ask **how-to** questions about Sarang ERP (workflows, where to find menus, short steps).
- Choose **answer language**: Auto (follows app locale), **English**, or **Bahasa Indonesia**.
- Submit **bug reports** or **feature requests** under **Report / request** (stored for triage; not a formal SLA).

Answers are grounded in internal documentation (`docs/manuals/`) indexed on the server — not general web search.

---

## How to use (end users)

1. Click **?** on the right side of the navbar.
2. **How-to** tab — type a short question, e.g.:
   - "How do I transfer stock between warehouses?"
   - "Where is the Sales Invoice menu?"
   - "How do I post a purchase invoice?"
3. Set **Answer language** if you want to force the reply language.
4. Click **Ask**.
5. Read the answer and **Sources** (manual filenames used as references).

Use **Report / request** for structured feedback.

---

## Privacy and storage

- HELP **chat is not logged** to the database for auditing.
- Only **Report / request** submissions are stored (title, body, type, user, time).

---

## For administrators

### Server requirements

- **`OPENROUTER_API_KEY`** in `.env` (server-side only; never expose to the browser).
- Outbound HTTPS to **OpenRouter** (embeddings + chat).
- After deploying migrations, run **`php artisan help:reindex`** to populate **`help_embeddings`**.

### Updating HELP knowledge after docs change

1. Add or edit Markdown under **`docs/manuals/`** (use **`##` headings** so chunks index well).
2. Optionally edit **`help-navigation.json`** for menu-path hints.
3. On the server, run:

```bash
php artisan help:reindex
```

4. This calls OpenRouter (usage/costs apply).

### If answers are empty or irrelevant

- Ensure **`help:reindex`** ran after the first deploy.
- Add clearer wording in manuals (match how users ask).
- Add a dedicated manual file for new topics, then **reindex**.

### Feedback email

- Set **`HELP_FEEDBACK_NOTIFY_EMAIL`** if you want email when users submit feedback (configure `MAIL_*`).

---

## Technical pointers

- Command: `php artisan help:reindex`
- Config: `config/help.php`, `config/services.php` (`openrouter`, `help_feedback`)
- Routes: `POST /help/ask`, `POST /help/feedback` (authenticated)
- Architecture: `docs/architecture.md` (In-app HELP section)

---

## Related

- Index of manuals: **`README.md`** in this folder.
- **Domain Assistant** (robot icon, live ERP data — not HELP): see **`domain-assistant-manual-en.md`** in this folder.

