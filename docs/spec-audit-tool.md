# Spec: Automated Data Audit Tool (Sarang ERP)

## 1. Goal
Deteksi anomali data akuntansi secara otomatis & periodik (1×/hari), hasil dilihat di app
atau diakses Hermes. Mencegah terulangnya kasus korupsi data seperti yang baru ditemukan
(jurnal duplikat, reversal salah akun, DCP tanpa PPN, gap rekonsiliasi).

## 2. Scope
**In:** framework audit extensible + 9 check awal + artisan command `audit:run` +
scheduler 1×/hari + halaman "Audit" di app (Blade/AntD).
**Out:** auto-fix (deteksi saja), integrasi lintas-app.

## 3. Tech Decisions
- Laravel 12 + Blade + AdminLTE 3 + Bootstrap 4 + jQuery + DataTables (Yajra).
- Framework: base class `AuditCheck` + tiap check = 1 class di `app/Services/Audit/Checks/`.
- `audit:run` menulis hasil ke 2 tabel; scheduler via `routes/console.php`.
- Lookup account by `code` (jangan hardcode id).
- Spatie string permission (`audit.view`).

## 4. DB Changes
- `audit_runs`: id, status, started_at, finished_at, triggered_by, total_checks,
  passed_checks, failed_checks, total_issues, timestamps.
- `audit_results`: id, audit_run_id FK, check_key, check_name, status (pass/fail/warning),
  issue_count, details (longtext JSON), timestamps.

## 5. UI/UX
Halaman `Audit` (dalam grup Accounting): daftar run + detail result per check (pass/fail +
daftar issue). Menu item + permission `audit.view`.

## 6. Check list (9)
1. tb_imbalance — selisih debit/credit semua jurnal posted ≠ 0 (tol 0.02)
2. journal_imbalance — satu jurnal posted Dr ≠ Cr
3. duplicate_posting — source_type+source_id punya >1 jurnal non-reversal
4. reversal_wrong_account — reversal debit akun kas ≠ akun kas asli (1.1.1.%)
5. non_postable_parent — journal_lines ke akun is_postable=0
6. backdated_journal — jurnal posted date < 2026-01-01
7. negative_cash — akun 1.1.1.% saldo berjalan negatif
8. reconciliation_gap — closing_balance_bank ≠ closing_balance_book
9. orphaned_match_group — match group tanpa bank/book line

## 7. Risks
- Command belum terdaftar di Kernel `$commands` (custom loader) → wajib append.
- Scheduler harus diaktifkan cron di production (`php artisan schedule:run`).
- Deteksi duplikat/reversal bisa false-positive → status `warning` untuk kasus ambigu.
