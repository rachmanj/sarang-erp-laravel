# Manual Sales Invoice (Faktur Penjualan / SI)

## Daftar Isi Ringkas

- [Pengenalan](#pengenalan)
- [Menu, izin, dan URL](#menu-izin-dan-url)
- [Alur bisnis umum](#alur-bisnis-umum)
- [Membuat Sales Invoice dari Delivery Order](#membuat-sales-invoice-dari-delivery-order)
- [Membuat Sales Invoice dari Sales Quotation](#membuat-sales-invoice-dari-sales-quotation)
- [Membuat Sales Invoice manual (tanpa DO)](#membuat-sales-invoice-manual-tanpa-do)
- [Daftar dan filter Sales Invoice](#daftar-dan-filter-sales-invoice)
- [Detail, edit, hapus (draft)](#detail-edit-hapus-draft)
- [Jumlah baris, PPN, dan total tampilan](#jumlah-baris-ppn-dan-total-tampilan)
- [Posting, unpost, dan akuntansi](#posting-unpost-dan-akuntansi)
- [Cetak dan PDF (layout)](#cetak-dan-pdf-layout)
- [Import Sales Invoice](#import-sales-invoice)
- [Opening balance invoice](#opening-balance-invoice)
- [Penomoran dokumen](#penomoran-dokumen)
- [Pemecahan masalah](#pemecahan-masalah)

---

## Pengenalan

### Apa itu Sales Invoice?

**Sales Invoice (SI)** atau **Faktur Penjualan** adalah dokumen penagihan ke pelanggan yang mencatat piutang usaha (AR) dan mengintegrasikan dengan **Sales Order**, **Delivery Order**, **Sales Quotation**, serta jurnal akuntansi (Piutang, Pendapatan, PPN keluaran, dll.).

### Kapan SI dibuat?

| Sumber | Keterangan |
|--------|------------|
| **Dari Delivery Order** | Alur utama: barang sudah dikirim (DO **delivered** / **completed**), lalu dibuat faktur. |
| **Dari Sales Quotation** | Mengisi form create dengan parameter quotation (baris diisi dari penawaran). |
| **Manual** | Create dari menu tanpa DO — isi pelanggan dan baris faktur sendiri (misalnya jasa atau kasus khusus). |

### Istilah yang dipakai pengguna

- **Faktur penjualan**, **SI**, **Sales Invoice**, **invoice AR** — merujuk pada modul yang sama.
- **Posting** — mengunci faktur dan membuat jurnal akuntansi resmi.

---

## Menu, izin, dan URL

### Lokasi menu

1. Login ke Sarang ERP.
2. Sidebar **Sales** → **Sales Invoices**.

### Izin (Spatie)

| Aktivitas | Izin umum |
|-----------|-----------|
| Melihat daftar & detail | `ar.invoices.view` |
| Membuat & mengedit | `ar.invoices.create` |
| Posting / unpost | `ar.invoices.post` |

Tanpa izin yang sesuai, menu atau tombol tidak tampil atau mengarah ke akses ditolak.

### URL aplikasi (referensi)

- Daftar: `/sales-invoices`
- Buat baru: `/sales-invoices/create`
- Dari DO: tombol di halaman DO mengarah ke create dengan parameter `delivery_order_id` / `delivery_order_ids` (lihat bagian DO).

---

## Alur bisnis umum

Urutan tipikal untuk penjualan barang:

1. **Sales Order** (SO) disetujui.
2. **Delivery Order** (DO) dibuat dari SO → disetujui → **Mark as Delivered** → status DO menjadi **delivered** atau **completed**.
3. **Sales Invoice** dibuat dari DO (tombol *Create Invoice from Delivery Order*) atau dari daftar SI dengan memilih DO yang boleh difakturkan.
4. SI **di-posting** untuk mengakui piutang dan pendapatan secara akuntansi (sesuai pengaturan modul).

Detail pengiriman: lihat manual **Delivery Order** (`delivery-order-manual-id.md`).

---

## Membuat Sales Invoice dari Delivery Order

### Prasyarat DO

- Status DO: **delivered** atau **completed** (setelah pengiriman selesai).
- DO **belum** difakturkan (satu DO tidak boleh dibuat dua SI untuk baris yang sama secara sistem).
- Semua DO yang dipilih multi-DO harus **customer dan entitas perusahaan (company)** yang sama.

### Langkah dari halaman Delivery Order

1. Buka **Sales** → **Delivery Orders** → pilih DO yang sudah selesai.
2. Klik **Create Invoice from Delivery Order** (atau label serupa).
3. Form **Sales Invoice** terbuka dengan baris terisi dari **Delivery Qty** / baris DO.
4. Periksa tanggal, syarat pembayaran (terms), PPN/WTax per baris, proyek (jika dipakai).
5. Simpan (**Save**) sebagai **Draft**.
6. Setelah dicek, lakukan **Post** dari halaman detail SI (butuh izin posting).

### Prasyarat tombol "Create Invoice" di DO

- DO sudah **delivered** / **completed** dan disetujui.
- DO belum tertutup oleh faktur lain (lihat closure di sistem).

Jika tombol tidak muncul, lihat juga manual Delivery Order — bagian membuat faktur.

---

## Membuat Sales Invoice dari Sales Quotation

1. Buka pembuatan SI dengan parameter **`quotation_id`** (biasanya dari alur penawaran yang disetujui / tautan internal).
2. Header dan baris diisi dari **Sales Quotation** (harga, qty, akun pendapatan, PPN).
3. Sesuaikan tanggal faktur dan data wajib lainnya, lalu simpan dan posting sesuai kebijakan.

---

## Membuat Sales Invoice manual (tanpa DO)

1. **Sales** → **Sales Invoices** → **Create** (atau `/sales-invoices/create` tanpa parameter DO).
2. Pilih **Customer** (business partner tipe customer), **Company entity**, **tanggal**.
3. Tambah **baris faktur**: akun pendapatan, qty, harga, kode pajak jika ada.
4. Simpan draft → review → **Post**.

Gunakan untuk jasa, penagihan tidak melalui DO, atau skenario khusus sesuai kebijakan perusahaan.

---

## Daftar dan filter Sales Invoice

**Lokasi**: **Sales** → **Sales Invoices**.

- Gunakan filter tanggal, pelanggan, entitas, status (draft/posted), dan pencarian nomor faktur bila tersedia.
- Kolom umum: nomor invoice, tanggal, customer, total, status.

---

## Detail, edit, hapus (draft)

- **Detail**: klik nomor atau aksi View — melihat baris, total, status, tombol cetak/PDF.
- **Edit**: hanya untuk SI berstatus **Draft** (sesuai implementasi terkini).
- **Hapus**: biasanya hanya **Draft**; SI yang sudah **Posted** tidak boleh dihapus sembarangan (gunakan alur unpost jika diizinkan).

---

## Jumlah baris, PPN, dan total tampilan

- **Nilai tersimpan per baris (`amount`)**: **Termasuk PPN** (konsisten dengan logika total baris **Sales Order**: dasar = qty × harga satuan, lalu PPN atas dasar tersebut, serta pemotongan WTax jika ada).
- **Kolom Jumlah / Amount di layar & cetak**: Menampilkan **qty × harga satuan** (DPP per baris tanpa PPN di kolom tersebut) agar tidak terlihat seperti PPN dihitung dua kali terhadap angka yang sudah termasuk pajak.
- **Ringkasan bawah faktur**: **Subtotal (ex. PPN)** → **PPN** → **WTax** (jika ada) → **Total (incl. PPN)**. Baris terakhir adalah nilai tagihan bruto dan sejalan dengan **`total_amount`** dokumen—bukan penjumlahan “subtotal + PPN” yang mengulang pajak atas baris yang sudah inclusive.

---

## Posting, unpost, dan akuntansi

- **Post** — mengunci dokumen dan membuat jurnal melalui **`PostingService`**, dengan perhitungan PPN/AR dari baris inclusive lewat **`SalesInvoicePostingMath`**. Alur dari DO: **Kredit AR UnInvoice** dan **Debit Piutang Dagang** sebesar bruto termasuk PPN; **Debit pendapatan** (per baris) untuk komponen PPN yang “dibawa” di nilai baris; **Kredit PPN keluaran**. SI **opening balance** memakai pola berbeda (AR bruto, saldo awal laba ditahan, PPN sesuai kasus).
- **Unpost** — membuka kembali untuk koreksi (jika fitur dan izin tersedia).

### Pemeriksaan jurnal (CLI)

`php artisan sales-invoices:validate-posted-journals`

Opsi: `--id=` (satu SI), `--limit=` (batch). Membandingkan jurnal yang sudah posting dengan bruto/PPN yang diharapkan dari baris faktur. Perintah terdaftar di **`App\Console\Kernel`**.

Untuk detail COA spesifik, koordinasikan dengan tim akuntansi atau lihat **`docs/architecture.md`** (bagian Sales Invoice accounting).

---

## Sales Credit Memo (nota kredit / koreksi faktur)

### Apa itu?

**Sales Credit Memo** adalah dokumen untuk **mengoreksi** piutang dan pendapatan yang sudah diakui lewat **Sales Invoice yang sudah posting** — misalnya retur administratif, koreksi harga, atau penyesuaian setelah kesalahan entitas/dokumen.

### Menu dan URL

- **Sales** → **Sales Credit Memos** — daftar `/sales-credit-memos`, buat `/sales-credit-memos/create`, detail `/sales-credit-memos/{id}`.
- Dari halaman detail **Sales Invoice** yang sudah **posted**: tombol **Credit Memo** (jika sudah ada) atau **Create Credit Memo** (jika belum ada dan syarat terpenuhi).

### Aturan satu memo per faktur

Sistem membatasi **hanya satu** Sales Credit Memo per **Sales Invoice** (satu SI tidak boleh memiliki dua CM). Jika pengguna mencoba membuat CM kedua, aplikasi menolak atau mengarahkan ke memo yang sudah ada.

### Alur singkat

1. Pilih SI yang akan dikoreksi (biasanya status **posted**).
2. Buat **Sales Credit Memo** (isi baris koreksi sesuai kebijakan).
3. **Post** memo (izin `ar.credit-memos.post`) agar jurnal koreksi tercatat.

### Terkait Delivery Order dan entitas salah

Sales Credit Memo sering menjadi langkah **sebelum** **Reverse delivery** pada DO yang pernah tertaut SI dan ditutup oleh faktur — ikuti pesan di layar dan checklist **`checklist-perbaikan-salah-entitas-so-id.md`**.

### Untuk HELP

Kata kunci: *nota kredit*, *credit memo*, *koreksi faktur*, *dua CM*, *satu memo per invoice*. Setelah memperbarui dokumentasi, jalankan **`php artisan help:reindex`**.

---

## Cetak dan PDF (layout)

Dari halaman detail SI:

- **Print** — pilih layout:
  - **Standard** — cetak A4/formal.
  - **Dot matrix** — lebar kertas sempit (mis. 9.5", font monospace) untuk printer struk/dot matrix.
- Beberapa entitas perusahaan dapat memiliki **template cetak berbeda** (mis. nama perusahaan pada kop).

Parameter `?layout=dotmatrix` dapat dipakai sesuai implementasi halaman print.

---

## Import Sales Invoice

Jika menu **Import** tersedia untuk SI:

1. Unduh **template** dari sistem.
2. Isi sesuai format, validasi lewat langkah **Validate**.
3. Jalankan **Import** setelah data bersih.

Izin dan nama route mengikuti pengaturan admin (`sales-invoices/import`).

---

## Opening balance invoice

Centang / gunakan opsi **Opening Balance** pada header SI jika mencatat **saldo awal piutang** di awal migrasi data (sesuai form). Koordinasikan dengan tim implementasi agar konsisten dengan saldo GL dan AR.

---

## Penomoran dokumen

Format mengikuti **Document Numbering** per entitas (kode dokumen untuk Sales Invoice dalam sistem penomoran). Lihat **Document Numbering System** (`document-numbering-system-manual-id.md`) — kode dokumen untuk faktur penjualan biasanya tercantum di tabel jenis dokumen (mis. kode `08` dalam dokumentasi nomor).

---

## Pemecahan masalah

### "Tidak bisa buat SI dari DO"

- Pastikan status DO **delivered** atau **completed**.
- Pastikan DO **belum** pernah difakturkan.
- Pastikan customer dan company entity konsisten jika memilih banyak DO.

### "Sudah ada Sales Invoice untuk DO ini"

- Satu DO (atau set DO tertentu) sudah terhubung ke SI. Buka SI yang ada atau batalkan alur sesuai kebijakan.

### Tombol Post tidak ada

- Cek izin `ar.invoices.post` dan status SI (harus draft untuk posting pertama).

### PPN atau harga salah

- Periksa **tax code** per baris dan master **tax codes**.
- Periksa akun pendapatan dari kategori item / baris.

### Bantuan dalam aplikasi (HELP)

Gunakan ikon **?** di navbar untuk bertanya cara penggunaan modul. Isi pengetahuan HELP berasal dari folder `docs/manuals/` — setelah pembaruan manual, administrator harus menjalankan **`php artisan help:reindex`** di server.

---

## Sumber terkait

- Manual **Delivery Order**: `delivery-order-manual-id.md`
- Manual **Customer / Project** (proyek di SI): `customer-project-manual-id.md`
- Manual **Penomoran dokumen**: `document-numbering-system-manual-id.md`
- Manual **Bantuan dalam aplikasi**: `in-app-help-manual-id.md`
- Ringkasan koreksi alur (HELP): `sales-workflow-corrections-help-id.md`
- Checklist salah entitas SO: `checklist-perbaikan-salah-entitas-so-id.md`
