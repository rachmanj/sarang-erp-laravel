# Checklist: Perbaikan Salah Pilih Company Entity pada Sales Order

Dokumen ini adalah panduan operasional **Sarang ERP** untuk menyelesaikan kasus SO yang terlanjur dibuat di entitas salah, lalu harus dipindahkan ke entitas yang benar (atau dikoreksi lewat dokumen turunan). Judul `##` di bawah dipakai sebagai potongan pengetahuan untuk **HELP** (ikon **?**); setelah perubahan, jalankan **`php artisan help:reindex`** di server.

**Contoh kasus:** SO `722260201103` dan `722260201105` terinput **CV Cahaya Saranghae**, seharusnya **PT Cahaya Sarang Jaya**. Sesuaikan nama entitas dengan master **Company entity** di sistem.

---

## Menu & URL (referensi)

| Menu sidebar (grup **Sales**) | Pola URL |
|-------------------------------|----------|
| **Dashboard** | `/sales/dashboard` |
| **Sales Orders** | `/sales-orders` → detail `/sales-orders/{id}` |
| **Delivery Orders** | `/delivery-orders` → detail `/delivery-orders/{id}` |
| **Sales Invoices** | `/sales-invoices` → detail `/sales-invoices/{id}` |
| **Sales Credit Memos** | `/sales-credit-memos` → detail `/sales-credit-memos/{id}` |
| **Sales Receipts** | `/sales-receipts` (jika ada alokasi pembayaran ke SI) |

**Field entitas:** pada form SO (create/edit), pilih **Company entity** (dropdown berisi nama & kode entitas).

**Peta dokumen:** di halaman detail SO/DO/SI, gunakan tombol **Relationship Map** untuk melihat rantai dokumen (bukan menu sidebar terpisah).

---

## A. Identifikasi (ulangi untuk setiap SO yang bermasalah)

- [ ] Buka **Sales** → **Sales Orders** → cari nomor SO → buka halaman detail.
- [ ] Catat **Company entity** saat ini (mis. CV …).
- [ ] Catat **status** SO dan apakah sudah ada **Delivery Orders** / **Sales Invoices**.
- [ ] Buka **Relationship Map** bila perlu untuk melihat hubungan SO → DO → SI.

---

## B. Jalur cepat — SO masih dapat diubah tanpa koreksi jurnal

Berlaku umumnya jika SO masih **draft** atau sistem mengizinkan edit entitas.

- [ ] **Sales** → **Sales Orders** → buka SO → **Edit** (`/sales-orders/{id}/edit`).
- [ ] Ubah **Company entity** ke entitas yang benar (mis. **PT Cahaya Sarang Jaya**) → simpan.
- [ ] Ulangi untuk SO berikutnya pada daftar kasus.

---

## C. Sales Invoice sudah **posted** — koreksi akuntansi

- [ ] **Sales** → **Sales Invoices** → buka SI terkait → pastikan status **posted**.
- [ ] **Sales** → **Sales Credit Memos** → buat memo untuk SI tersebut (atau dari SI gunakan **Create Credit Memo** jika tombol tersedia).
- [ ] Pastikan aturan sistem: **hanya satu Sales Credit Memo per Sales Invoice** (duplikasi ditolak).
- [ ] Di halaman **Sales Credit Memos** → detail memo → **Post** (memerlukan hak `ar.credit-memos.post`).

---

## D. Ada Delivery Order — siapkan reversal (jika kebijakan mengizinkan)

- [ ] **Sales** → **Delivery Orders** → buka DO yang terkait SO salah entitas.
- [ ] Jika DO masih tertaut SI: **lepas tautan DO–SI** sesuai prosedur internal (admin/IT), karena reversal DO diblokir selain pivot kosong.
- [ ] Setelah SI ditangani dengan credit memo (sesuai aturan penutupan DO) dan tautan sudah sesuai: pada detail DO, gunakan **Reverse delivery** (opsional: isi alasan). Memerlukan izin `delivery-orders.reverse`.
- [ ] Tinjau catatan/audit di sistem bila tersedia.

---

## E. Transaksi ulang di entitas yang benar

- [ ] **Sales** → **Sales Orders** → **Create** — pilih **Company entity** yang benar sejak awal.
- [ ] Lanjutkan **Delivery Orders** / **Sales Invoices** sesuai proses normal.

---

## F. Verifikasi akhir

- [ ] **Relationship Map** pada dokumen baru: rantai dan entitas sudah benar.
- [ ] Tidak ada SI **posted** mengambang di entitas lama untuk transaksi yang sudah dikoreksi (SI lama telah memiliki **Sales Credit Memos** yang **posted** sesuai kebijakan).
- [ ] Selaraskan dengan gudang/stok jika ada DO yang di-reverse.

---

## Ringkasan satu kalimat

**Ubah entitas di Edit SO jika masih bisa; jika SI sudah posted, posting Sales Credit Memos dulu, sesuaikan tautan DO–SI dan reversal DO bila dipakai, lalu buat transaksi benar di entitas PT.**

---

*Versi dokumen: diselaraskan dengan nama menu sidebar **Sales** dan route web aplikasi Sarang ERP.*
