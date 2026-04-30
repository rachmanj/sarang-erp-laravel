# Sales Receipt — referensi HELP (Sarang ERP)

Dokumen ini dipakai **bantuan dalam aplikasi** (ikon **?** di navbar) untuk topik **Sales Receipt** / **penerimaan penjualan** (uang masuk dari customer yang dialokasikan ke **Sales Invoice** yang sudah **posted**). Setelah mengubah berkas ini, administrator menjalankan **`php artisan help:reindex`**.

---

## Apa itu Sales Receipt?

**Sales Receipt** mencatat uang yang diterima dari **customer** dan mengalokasikannya ke satu atau lebih **Sales Invoice** (piutang usaha) dengan status **posted**. Status awal biasanya **draft**; setelah **posting**, sistem membentuk jurnal akuntansi (debit kas/bank, kredit piutang dagang). Singkatan umum: **SR**.

**Kata kunci:** sales receipt, SR, penerimaan penjualan, pelunasan faktur, pembayaran customer, AR receipt.

---

## Di mana menu Sales Receipts

1. Login ke Sarang ERP.
2. Di menu samping **Sales**, buka **Sales Receipts** (daftar).
3. Buka detail lewat **View**, atau buat baru lewat **Create**.

**Kata kunci:** di mana sales receipt, menu SR, `/sales-receipts`.

---

## Membuat Sales Receipt (draft)

Perlu izin **`ar.receipts.create`**.

1. **Sales Receipts** — buat baru (**Create**).
2. Isi **tanggal**, **company entity**, **customer**, opsional **description**.
3. Pilih **invoice** yang sudah **posted** untuk customer tersebut; isi **alokasi** (berapa dari penerimaan ini untuk tiap invoice).
4. Isi **baris penerimaan** (akun kas/bank dan nominal). **Jumlah total baris** harus sama dengan **total alokasi**.
5. Simpan. Dokumen tersimpan sebagai **draft**; **nomor kwitansi/receipt** dihasilkan sistem (tidak perlu diisi manual).

**Kata kunci:** buat sales receipt, alokasi invoice, total baris harus sama alokasi.

---

## Mengubah Sales Receipt berstatus draft

Anda hanya boleh **mengedit** Sales Receipt selama masih **draft**. Yang sudah **posted** **tidak bisa** diubah lewat layar (tombol **Edit** tidak tampil setelah posting).

**Siapa boleh mengedit:** sama seperti membuat — izin **`ar.receipts.create`**.

**Langkah:**

1. Buka receipt **draft** (dari daftar, **View**).
2. Klik **Edit** di header (tombol warna peringatan, di samping **Post**).
3. Ubah **tanggal**, **company**, **customer**, **deskripsi**, **alokasi invoice**, dan **baris penerimaan**. Aturan sama seperti saat buat baru: total harus cocok; invoice harus milik customer yang dipilih dan status **posted**; alokasi tidak boleh melebihi **sisa tagihan** invoice (sistem **mengabaikan** alokasi dari receipt **ini** saat menghitung sisa agar nominal bisa diubah dengan aman).
4. **Simpan** (**Update Receipt**). **Nomor receipt tidak berubah**.
5. Jika ada **lebih dari satu baris penerimaan**, nominal baris **tidak** diisi otomatis dari alokasi — pastikan jumlah baris sama dengan total alokasi.

**Kata kunci:** edit sales receipt, ubah draft SR, koreksi alokasi, salah nominal sebelum posting, perbarui penerimaan draft.

---

## Posting Sales Receipt

Posting mencatat ke akuntansi. Perlu izin **`ar.receipts.post`**.

1. Buka receipt.
2. Klik **Post**.

**Kata kunci:** posting sales receipt, finalisasi SR, jurnal dari penerimaan.

---

## Izin (permission) Sales Receipt

| Izin                    | Fungsi umum                                      |
|-------------------------|--------------------------------------------------|
| **`ar.receipts.view`**  | Lihat daftar dan detail, PDF/cetak.               |
| **`ar.receipts.create`**| Buat receipt baru dan **edit receipt draft**.   |
| **`ar.receipts.post`**  | Posting draft ke jurnal.                          |

**Kata kunci:** siapa bisa edit sales receipt, hak akses SR, ar.receipts.

---

## Dokumen terkait

- **Sales Invoice** harus sudah **posted** agar bisa dipilih untuk alokasi.
- Mengubah draft dapat mempengaruhi status pelunasan invoice dan penutupan dokumen; aturan posting tetap sama.

**Kata kunci:** invoice harus posted, sisa alokasi.
