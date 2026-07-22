# Rekonsiliasi Bank — Panduan Pengguna (Bahasa Indonesia)

## Ringkasan

**Bank Reconciliation** mencocokkan baris **rekening koran bank** dengan **baris jurnal** pada akun kas/bank di buku besar. Mendukung:

- Impor PDF (parsing AI) atau entri manual
- Matching N:M otomatis / manual
- **Outstanding** untuk selisih waktu (deposito dalam perjalanan / cek belum cair) — ikut **carry-forward** ke bulan berikutnya
- **Exclude** hanya untuk error/duplikat
- **Jurnal penyesuaian** (biaya admin / bunga) dari baris bank yang belum match
- Finalize bila identitas rekonsiliasi seimbang:

  `saldo tutup statement + deposito dalam perjalanan − cek outstanding ≈ saldo tutup buku`

---

## Lokasi menu

1. Menu samping **Accounting** → **Bank Accounts** — master rekening bank terhubung COA.
2. Menu samping **Accounting** → **Rekening Koran** — grid bulan (entri utama); **All Sessions** untuk daftar lengkap.

Izin: `bank_reconciliation.view` / `import` / `reconcile` / `finalize`.

---

## Grid Rekening Koran

Halaman **Rekening Koran** menampilkan matriks **rekening bank × bulan** untuk tahun yang dipilih.

- **Sel kosong** — klik untuk unggah PDF atau buat sesi manual.
- **Badge berwarna** — sesi ada; klik untuk buka workbench atau laporan.
- Outstanding bulan sebelumnya otomatis diimpor ke sesi baru.

---

## Workbench

1. Isi **saldo opening/closing statement** (penting untuk cross-foot).
2. **Fetch Book Lines** lalu **Auto Match**.
3. Tandai selisih waktu dengan **Outstanding (O)**; error dengan **Exclude (X)**.
4. Biaya bank / bunga: tombol **J** → pilih akun lawan → jurnal diposting dan di-match otomatis.
5. **Finalize** bila tidak ada baris unmatched dan identitas rekonsiliasi ≈ 0.
6. **Export CSV** / **Print PDF** untuk laporan + jadwal outstanding.

---

## Catatan

- Jalankan `php artisan queue:work` agar parse PDF tidak memblokir browser.
- SR/PP harus sudah **posted** agar muncul di sisi buku.
- Fitur AI membutuhkan `OPENROUTER_API_KEY`.
- Jika muncul peringatan **STALE**, fetch ulang book lines sebelum finalize.
