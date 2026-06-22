# Rekonsiliasi Bank — Panduan Pengguna (Bahasa Indonesia)

## Ringkasan

**Bank Reconciliation** mencocokkan baris **rekening koran bank** dengan **baris jurnal** pada akun kas/bank di buku besar. Mendukung impor PDF (dengan parsing AI opsional), matching otomatis, matching manual, dan jurnal penyesuaian.

---

## Lokasi menu

1. Menu samping **Accounting** → **Bank Accounts** — master rekening bank terhubung COA.
2. Menu samping **Accounting** → **Bank Reconciliation** — sesi rekonsiliasi dan impor.

Izin: `bank_accounts.view`, `bank_reconciliation.view`, `bank_reconciliation.import` (unggah statement).

---

## Setup Bank Accounts

Sebelum rekonsiliasi, setiap rekening bank harus ada di **Bank Accounts** dan dihubungkan ke akun **postable** di COA (biasanya `1.1.1.x`).

- **Buat** — Accounting → Bank Accounts → Add; isi nama bank, nomor rekening, dan akun GL.
- Sales Receipt dan Purchase Payment memposting sisi kas ke **COA rekening bank** yang dipilih di baris pembayaran.

---

## Impor rekening koran

1. **Bank Reconciliation** → **Import Statement**.
2. Pilih **Bank Account** dan periode statement.
3. Unggah **PDF** rekening koran.
4. Sistem mengekstrak baris (teks PDF + AI OpenRouter opsional). Periksa nominal dan tanggal.
5. Simpan untuk membuat **Bank Statement** dan baris-barisnya.

**Credit** di statement (uang masuk) = **debit** di buku pada akun bank.

---

## Proses rekonsiliasi

1. Buka sesi rekonsiliasi dari daftar (atau dari statement yang diimpor).
2. Tinjau baris statement dan baris buku yang belum match.
3. **Auto Match** atau **AI Match** (opsional) untuk usulan pasangan.
4. **Manual match** — hubungkan baris statement ke baris jurnal.
5. Buat **adjustment** untuk biaya bank atau selisih timing (posting jurnal standar).
6. **Complete** ketika saldo buku sama dengan saldo penutup statement.

---

## Catatan

- Rekonsiliasi per rekening dan periode.
- SR/PP harus sudah **posted** agar muncul di sisi buku.
- Fitur AI membutuhkan `OPENROUTER_API_KEY` di server.
