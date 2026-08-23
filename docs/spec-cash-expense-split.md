# Spec: Split Cash Expense (1 Cash/Bank → N Expense Accounts)

## Latar
Fitur Cash Expense (`/cash-expenses/create`) sekarang cuma 1 akun biaya per transaksi.
`cash_expenses` cuma punya `account_id` (1 biaya) + `amount`. Akun cash/bank hanya di jurnal.
Kebutuhan: 1 transaksi = 1 Cash/Bank (kredit) → beberapa akun biaya (debit).

## Keputusan (grill-me)
1. Tabel baru `cash_expense_lines` (1 header + N baris).
2. Dimensi (project/dept) per baris biaya. (Fund di-skip — dropdown-nya kosong, gak wired.)
3. Deskripsi per baris.
4. Tanpa PPN.
5. Tanpa edit/reverse (tetap create + list + print).

## Data model
- `cash_expenses` (header): + `cash_account_id` (FK accounts), + `total_amount` decimal(18,2).
  Hapus `account_id` + `amount` (pindah ke lines).
- `cash_expense_lines` (baru): id, cash_expense_id (FK cascade), account_id (biaya), amount,
  description nullable, project_id nullable, dept_id nullable, timestamps.

## Migrasi data lama (186 baris)
- Tiap cash_expense lama → 1 baris lines (account_id, amount, description).
- `cash_account_id` di-backfill dari jurnal (credit line, source_type='cash_expense').
- `total_amount` = amount.
- Lalu drop kolom `account_id` + `amount`.

## Journal posting
1 header + N lines → 1 jurnal: 1 kredit (cash_account_id, total) + N debit (per line).
source_type='cash_expense', source_id=header id.

## UI
- create: tabel baris dinamis (akun biaya + nominal + deskripsi + project + dept, tambah/hapus,
  total otomatis). Tetap ada date + cash/bank + deskripsi header.
- print: tampil rincian baris.
- list (data): total + akun cash dari header.

## Scope
- Label UI Bahasa Indonesia.
- Jangan push (commit aja).
