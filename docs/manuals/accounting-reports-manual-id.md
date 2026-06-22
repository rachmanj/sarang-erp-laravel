# Laporan akuntansi — referensi HELP (Bahasa Indonesia)

Laporan keuangan dan subledger yang diperbarui (2026-06). Menu: **Reports** di sidebar. Izin: `reports.view`.

---

## Laporan Perubahan Ekuitas

**Reports** → **Changes in Equity**

Menampilkan pergerakan akun ekuitas per periode — saldo awal, laba rugi, perubahan lain, saldo akhir. Filter entitas dan periode. Ekspor CSV/PDF jika tersedia.

---

## Subledger Reconciliation

**Reports** → **Subledger Reconciliation**

Membandingkan total **AR/AP aging** dengan saldo **akun kontrol GL** (`control_accounts` tipe `ar` / `ap`). Berguna menemukan selisih subledger vs buku besar sebelum tutup buku.

Pilih tanggal as-of dan entitas. Selidiki via GL Detail atau daftar faktur/penerimaan terbuka.

---

## Rekonsiliasi PPN

**Reports** → **PPN Reconciliation**

Merekonsiliasi **PPN Keluaran** vs **PPN Masukan** dari transaksi pajak dan jurnal. Mendukung persiapan SPT; ekspor **SPT-1111** JSON jika dikonfigurasi.

Cross-check dengan dashboard Tax Compliance dan faktur posted.

---

## AR/AP balances dan aging

**AR Party Balances**, **AP Party Balances**, **AR Aging**, **AP Aging** memakai basis **outstanding net alokasi** yang sama.

Gunakan tanggal as-of yang sama pada aging dan balances agar angka konsisten.

---

## GL Detail dan Cash Ledger

- **GL Detail** — baris jurnal per akun dengan **saldo berjalan**; filter periode dan entitas.
- **Cash Ledger** — saldo berjalan akun **kas/bank** (default `1.1.1.x`, bukan piutang).

Default hanya jurnal posted; opsi include unposted jika UI menyediakan.

---

## Neraca, Laba Rugi, Arus Kas

Tampilan hierarki dari COA dengan `report_group`. Neraca menampilkan tie-out laba rugi belum ditutup. Arus kas metode **tidak langsung** dari `config/cash_flow.php`.

Detail teknis: `docs/financial-statements-reports.md`.
