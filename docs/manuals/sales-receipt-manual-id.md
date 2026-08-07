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
2. Isi **tanggal**, **company entity** (PT/CV), **customer**, opsional **description**.
3. Pilih **invoice** yang sudah **posted** untuk customer tersebut **dan entitas company yang sama** dengan field Company; isi **alokasi**. Mengubah **Company** me-reload daftar invoice agar faktur entitas lain tidak ikut tampil.
4. Isi **baris penerimaan** (akun kas/bank dan nominal). **Total baris penerimaan** boleh sedikit berbeda dari **total alokasi** selama masih dalam **toleransi pembulatan** (lihat **Pembulatan pembayaran** di bawah). Jika ada selisih, pilih **akun pembulatan** (default **7.1.4 Selisih Pembulatan**).
5. Simpan. Dokumen tersimpan sebagai **draft**; **nomor kwitansi/receipt** dihasilkan sistem (tidak perlu diisi manual).

**Kata kunci:** buat sales receipt, alokasi invoice, baris penerimaan, toleransi pembulatan.

---

## Buat Receipt dari Sales Invoice (Create Receipt)

Dari **Sales Invoice** berstatus **posted** yang masih punya **sisa tagihan**, gunakan tombol hijau **Create Receipt** di header halaman detail faktur.

- Membuka **Sales Receipts → Create** dengan parameter `?sales_invoice_id={id}`.
- **Company**, **customer**, **deskripsi**, dan **alokasi invoice** sudah terisi otomatis.
- Bagian **Receipt Lines** / **baris penerimaan** muncul otomatis dengan dropdown **Bank/Cash Account** dan nominal sesuai alokasi (Anda tetap memilih akun kas/bank).
- Jika faktur belum posted, fully allocated, atau tidak eligible, tombol disembunyikan atau Anda di-redirect dengan pesan error.

**Menu:** **Sales** → **Sales Invoices** → buka faktur → **Create Receipt**.

**Kata kunci:** buat receipt dari faktur, tombol Create Receipt, sales_invoice_id, prefilled SR, akun kas tidak muncul, baris penerimaan kosong.

---

## Pembulatan pembayaran (kas vs alokasi)

Customer sering membayar **nominal dibulatkan** (mis. tagihan **8.245.999,99**, bayar **8.246.000,00**).

- **Alokasi** ke setiap invoice tetap dibatasi **sisa tagihan** faktur (nominal pasti).
- **Total baris penerimaan** (kas diterima) boleh berbeda dari **total alokasi** dalam batas toleransi.
- **`rounding_amount`** = total baris penerimaan − total alokasi (disimpan di dokumen).
- Toleransi default: parameter ERP **`sales_receipt_rounding_tolerance`** (default **Rp 999.999**). Melebihi toleransi → error validasi.
- Akun pembulatan default: parameter **`rounding_account_id`** → **7.1.4 Selisih Pembulatan**; bisa diubah per dokumen saat create/edit.
- Saat **posting**, jurnal mengkredit AR sebesar **alokasi** dan memposting selisih ke akun pembulatan (laba/rugi pembulatan).

Konfigurasi: **Admin** → **ERP Parameters** (kategori accounting).

**Kata kunci:** pembulatan pembayaran, rounding, selisih pembulatan, 7.1.4, rounding gain, bayar dibulatkan, toleransi sales receipt.

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

Jurnal **mendebit akun kas/bank pada baris penerimaan** (bukan akun kas tetap) dan **mengkredit piutang dagang** sebesar **total alokasi**. Jika **`rounding_amount`** tidak nol, baris tambahan memposting selisih ke **akun pembulatan** (kredit untuk gain bila customer bayar lebih, debit untuk loss bila kurang). **Data lama (sebelum Juni 2026):** beberapa SR yang sudah posted mungkin masih tercatat ke Kas di Tangan di GL — administrator memperbaiki dengan `php artisan sales-receipts:repair-bank-journals --dry-run` (lihat `docs/decisions.md`).

**Kata kunci:** posting sales receipt, finalisasi SR, jurnal dari penerimaan, akun bank baris penerimaan.

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
- Hanya invoice dengan **`company_entity_id`** sama dengan field **Company** pada receipt yang ditawarkan (`getAvailableInvoices` wajib kirim `company_entity_id`). Simpan menolak alokasi ke invoice entitas lain.
- Mengubah draft dapat mempengaruhi status pelunasan invoice dan penutupan dokumen; aturan posting tetap sama.

## Relationship Map (Sales Receipt dalam rantai)

Di halaman detail **Sales Receipt**, klik **Relationship Map** di header untuk membuka diagram **Document Workflow**.

- Menghubungkan **Sales Receipt** ke **Sales Invoice** yang dialokasikan dan memperluas ke upstream bila ada: **Delivery Order** → **Sales Order** (dan **Sales Quotation** jika dari SQ).
- Tombol **Base / Target** di halaman detail memakai tautan dokumen yang sama.
- Receipt **lama** yang map-nya kosong: edit/simpan draft SR baru agar tautan SI→SR tersimpan; receipt baru otomatis sync saat **create/update**.

**Kata kunci:** relationship map sales receipt, peta dokumen SR, rantai SO DO SI SR, map kosong receipt, invoice harus posted, sisa alokasi, filter entitas sales receipt, PT CV salah invoice, company entity penerimaan.
