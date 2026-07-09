# Rekonsiliasi Bank — Panduan Pengguna (Bahasa Indonesia)

## Ringkasan

**Bank Reconciliation** mencocokkan baris **rekening koran bank** dengan **baris jurnal** pada akun kas/bank di buku besar. Mendukung impor PDF (dengan parsing AI opsional), matching otomatis, matching manual, dan jurnal penyesuaian.

---

## Lokasi menu

1. Menu samping **Accounting** → **Bank Accounts** — master rekening bank terhubung COA.
2. Menu samping **Accounting** → **Rekening Koran** — grid bulan (entri utama); **All Sessions** untuk daftar lengkap.

---

## Grid Rekening Koran

Halaman **Rekening Koran** menampilkan matriks **rekening bank × bulan** untuk tahun yang dipilih.

- **Sel kosong** — klik untuk unggah PDF atau buat sesi manual.
- **Badge berwarna** — sesi ada; klik untuk buka workbench atau laporan.
- Gunakan **All Sessions** untuk daftar pencarian; panah tahun untuk ganti tahun.

---

## Buat sesi rekonsiliasi

Dari grid (klik sel kosong) atau **New Session**:

1. Pilih **Bank Account** dan **Periode** (bulan).
2. Pilih **AI** (unggah PDF) atau **Manual**.
3. Submit — parsing AI berjalan di background (perlu queue worker).

Satu sesi per rekening bank per bulan.

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
