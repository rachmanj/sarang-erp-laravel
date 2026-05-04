# Manual Purchase Invoice (PI)

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Daftar Purchase Invoice](#daftar-purchase-invoice)
3. [Membuat Purchase Invoice](#membuat-purchase-invoice)
4. [Diskon (Header & Baris)](#diskon-header--baris)
5. [Pajak VAT dan Amount After VAT](#pajak-vat-dan-amount-after-vat)
6. [Modal Pilih Item](#modal-pilih-item)
7. [Halaman Detail Invoice](#halaman-detail-invoice)
8. [Cetak dan PDF](#cetak-dan-pdf)
9. [Posting dan Unposting](#posting-dan-unposting)
10. [Integrasi dengan GRPO](#integrasi-dengan-grpo)
11. [Direct Cash Purchase](#direct-cash-purchase)
12. [Opening Balance Invoice](#opening-balance-invoice)
13. [Validasi tanggal invoice](#validasi-tanggal-invoice)
14. [Pemeliharaan: sinkron harga GRPO dari PO](#pemeliharaan-sinkron-harga-grpo-dari-po)
15. [Pemecahan Masalah](#pemecahan-masalah)

---

## Pengenalan

### Apa itu Purchase Invoice?

Purchase Invoice (PI) adalah dokumen penagihan dari vendor yang mencatat hutang pembelian barang atau jasa. PI mengintegrasikan dengan modul pembelian (PO, GRPO) dan akuntansi (AP, jurnal).

### Sumber Pembuatan PI

| Sumber | Deskripsi |
|--------|-----------|
| **Dari GRPO** | Baris diimpor dari Goods Receipt PO; inventory sudah diterima saat GRPO |
| **Dari PO** | Referensi ke Purchase Order (untuk service PO) |
| **Direct Purchase** | Tanpa PO/GRPO; entry manual untuk pembelian langsung |

### Harga pembelian bila ada PO dan GRPO

- **KUANTITAS diterima** dicatat di **Goods Receipt PO (GRPO)**.
- **HARGA NEGOSIASI (per barang)** mengacu ke **Purchase Order (PO)**:
  - Saat menyimpan GRPO dengan **Purchase Order** terpilih, sistem mengisi **`unit_price`**, **`amount`**, akun (`account_id`), dan **`tax_code_id`** pada baris GRPO sesuai **baris PO** yang cocok (**item pertama** untuk pasangan `(PO, sku)` yang sama).
  - Saat membuat **Purchase Invoice** lewat tombol dari GRPO, **Pull lines**, atau kombinasi GRPO dari halaman PI, **harga satuan PI** juga diambil dari **PO yang tertaut pada GRPO** (bukan dari harga kartu inventori secara otomatis). Kuantitas baris tetap mengikuti **GRPO**.
- GRPO tanpa tautan PO: harga dapat mengacu ke **purchase price** di master **Inventory Item**, seperti perilaku penyimpanan GRPO mandiri.

### Metode Pembayaran

- **Credit**: Pembayaran nanti; membutuhkan Purchase Payment untuk alokasi
- **Cash**: Pembayaran tunai; untuk Direct Purchase tidak membutuhkan Purchase Payment

### Penomoran

Format: `EEYYDDNNNNN`
- `EE` = Kode Entitas (2 digit)
- `YY` = Tahun (2 digit)
- `DD` = Kode Dokumen `03` untuk Purchase Invoice
- `NNNNN` = Nomor Urut (5 digit)

Contoh: `72260300007` = PI ke-7 tahun 2026 untuk entitas 72.

---

## Daftar Purchase Invoice

**Lokasi**: `Purchase > Purchase Invoices`

### Kolom Tabel

| Kolom | Keterangan |
|-------|------------|
| Date | Tanggal invoice |
| Invoice No | Nomor invoice (bukan ID) |
| Vendor | Nama vendor/supplier |
| Total | Total amount invoice |
| **VAT** | Total PPN invoice |
| **Amount After VAT** | Total setelah PPN (sama dengan Total jika VAT = 0) |
| Status | Draft / Posted |
| Actions | View, Edit, Post, dll. |

### Filter

- **Date From / To**: Rentang tanggal
- **Search**: Pencarian (invoice no, vendor)
- **Status**: All / Draft / Posted

---

## Membuat Purchase Invoice

**Lokasi**: `Purchase > Purchase Invoices` → **Create**

### Langkah Umum

1. **Date** – Tanggal invoice (wajib)
2. **Company** – Entitas perusahaan (wajib)
3. **Vendor** – Pilih vendor (wajib)
4. **Payment Method** – Credit atau Cash
5. **Terms (days)** – Jangka waktu pembayaran (default 30)
6. **Description** – Keterangan opsional
7. **Invoice Lines** – Tambah baris item/jasa

### Field Header

| Field | Wajib | Keterangan |
|-------|-------|------------|
| Date | ✓ | Tanggal invoice |
| Company | ✓ | Entitas hukum |
| Vendor | ✓ | Business partner tipe supplier |
| Payment Method | ✓ | Credit atau Cash |
| Terms (days) | | Hari jatuh tempo (untuk Credit) |
| Opening Balance Invoice | | Centang untuk invoice saldo awal |
| Description | | Keterangan invoice |
| Discount (%) | | Diskon header (persentase) |
| Discount Amount | | Diskon header (nominal) |
| Due Date | | Otomatis dari Date + Terms |
| Cash Account | | Hanya untuk Direct Cash Purchase |

### Invoice Lines

| Kolom | Wajib | Keterangan |
|-------|-------|-------------|
| Account | * | Akun COA (auto dari item jika pilih item) |
| Item | * | Pilih via tombol **Select Item** |
| Warehouse | | Untuk item fisik |
| Description | | Deskripsi baris |
| Qty | ✓ | Kuantitas |
| UOM | | Unit of measure (default dari item) |
| Unit Price | ✓ | Harga satuan |
| VAT | | Kode pajak (No VAT, PPN11_IN, dll.) |
| WTax | | Withholding tax |
| **Disc %** | | Diskon baris (persentase) |
| **Disc Amt** | | Diskon baris (nominal) |
| Amount | | Auto: Qty × Unit Price |
| Project / Dept | | Dimensi opsional |

### Footer Totals (Invoice Lines)

| Baris footer | Makna |
|----------------|-------|
| **Net subtotal (excl. VAT / WTax)** | Jumlah `Qty × Unit Price` per baris setelah **diskon baris**, sebelum pajak VAT / WTax |
| **VAT** | Total PPN (dari kombinasi kode pajak per baris) |
| **WTax** | Total pemotongan (withholding tax) sesuai setelan baris |
| **Total discount** | Diskon baris **+** diskon header (kedua-duanya dari field header/disc baris form) |
| **Amount due** | Grand total hingga pembayaran (setelah pajak dikurangkan WTax, lalu diskon header) |

**Catatan**: Diskon **baris** (kolom Disc % / Disc Amt) tetap bisa diisi; ringkasan diskon gabungan ada di satu baris **Total discount**.

---

## Diskon (Header & Baris)

### Diskon Baris (Line Discount)

- **Disc %**: Persentase diskon per baris
- **Disc Amt**: Nominal diskon per baris
- Jika isi salah satu, yang lain ter-update otomatis
- **Net Amount** = Amount − Line Discount
- VAT dihitung dari **Net Amount**, bukan Amount

### Diskon Header

- **Discount (%)**: Persentase dari subtotal (setelah diskon baris)
- **Discount Amount**: Nominal diskon header
- Sinkronisasi otomatis antara % dan nominal
- Diterapkan setelah subtotal baris

### Alur Perhitungan

```
Amount (per baris)
  → Line Discount (Disc % atau Disc Amt)
  → Net Amount
  → VAT (dari Net Amount)
  → Amount After VAT

Subtotal = Σ(Amount After VAT per baris)
  → Header Discount
  → Total Amount (Amount Due)
```

---

## Pajak VAT dan Amount After VAT

### Kode Pajak

- **No VAT**: Tanpa PPN
- **PPN11_IN**: PPN 11% (pembelian)
- **PPN11_OUT**, **PPN12_OUT**: PPN penjualan (jika dipakai)

### Perhitungan

- **VAT Amount** = Net Amount × (rate pajak / 100)
- **Amount After VAT** = Net Amount + VAT Amount
- Jika VAT = 0, Amount After VAT = Net Amount (bukan 0)

### Tampilan

- Di **list**: Kolom VAT dan Amount After VAT
- Di **detail**: Tabel baris menampilkan VAT dan Amount After VAT per baris
- Di **create/edit**: Footer menampilkan total

---

## Modal Pilih Item

Tombol **Select Item** pada baris membuka modal pencarian item.

### Filter Pencarian

- **Item Code**: Cari berdasarkan kode
- **Item Name**: Cari berdasarkan nama
- **Category**: Filter kategori produk
- **Item Type**: Physical Item / Service

### Kolom Tabel

| Kolom | Keterangan |
|-------|------------|
| # | Nomor urut |
| Code | Kode item |
| Name | Nama item |
| Category | Kategori produk |
| Type | item / service |
| UOM | Unit of measure |
| Purchase Price | Harga beli |
| Selling Price | Harga jual |
| **Available Qty** | Stok tersedia (akurat dari warehouse) |
| Action | Tombol Select |

### Available Qty

- **Sumber**: `inventory_warehouse_stock` (quantity_on_hand − reserved_quantity)
- **Tanpa warehouse**: Total stok di semua gudang
- **Dengan warehouse**: Stok di gudang yang dipilih baris
- **Service**: Menampilkan "—" (tidak ada stok)

### Cara Pakai

1. Klik **Select Item** pada baris
2. Isi filter (opsional) lalu klik **Search**
3. Klik **Select** pada item yang dipilih
4. Item, account, harga, UOM terisi otomatis

---

## Halaman Detail Invoice

**Lokasi**: Klik invoice dari list atau `Purchase Invoices > {invoice_no}`

### Bagian

1. **Header**
   - Judul: Purchase Invoice {invoice_no}
   - Badge: Status (DRAFT/POSTED), Opening Balance, Direct Purchase
   - Tombol: Relationship Map, Edit, Post/Unpost, Print, PDF, Queue PDF

2. **Document Navigation**
   - Link cepat ke PO, GRPO, Payment terkait

3. **Vendor Information**
   - Nama, kode, NPWP, alamat

4. **Invoice Details**
   - Invoice Number, Date, Due Date, Terms, Company, Payment Method
   - Posted At (jika sudah posted)

5. **Related Documents**
   - Link ke Purchase Order dan Goods Receipt (jika ada)

6. **Financial Summary**
   - Subtotal, Discount, Total VAT, Total Amount
   - Payment Status, Allocated, Remaining Balance

7. **Line Items**
   - Tabel: #, Account, Item Code, Item Name, Description, Qty, Unit Price, Amount
   - Discount, Net Amount (jika ada diskon)
   - VAT, Amount After VAT
   - Subtotal per section, Discount header, Total

8. **Payment Allocations** (jika ada)
   - Daftar pembayaran yang dialokasikan

9. **Journal Entry** (jika posted)
   - Informasi jurnal terkait

10. **Inventory Transactions** (untuk Direct Purchase)
    - Transaksi inventory yang dibuat saat posting

---

## Cetak dan PDF

### Standard Print

- **Tombol**: Print → Standard Print
- **URL**: `/purchase-invoices/{id}/print`
- Menampilkan: Header perusahaan, vendor, invoice details, tabel baris dengan diskon, total

### PDF

- **Tombol**: PDF
- **URL**: `/purchase-invoices/{id}/pdf`
- Mengunduh PDF untuk cetak atau arsip

### Queue PDF

- Untuk generate PDF di background (jika fitur diaktifkan)

---

## Posting dan Unposting

### Post

- **Syarat**: Status Draft
- **Efek**:
  - **Direct Purchase**: Membuat inventory transaction + jurnal (Debit Inventory, Credit Cash)
  - **Credit (dari GRPO/PO)**: Membuat jurnal (Debit AP UnInvoice, Credit Utang Dagang)
  - Tidak membuat inventory transaction jika dari GRPO (stok sudah ada)

### Unpost

- **Syarat**: Posted, belum ada payment allocation, belum closed
- **Efek**: Reverse jurnal, hapus inventory transaction (jika Direct Purchase)

---

## Integrasi dengan GRPO

### Alur

```
Purchase Order (approved)
    ↓ Copy to GRPO
Goods Receipt PO (GRPO)  ← Penerimaan kuantitas, update inventori (harga acuan mengikuti PO jika tautan PO ada)
    ↓ Create Invoice / Pull dari daftar GRPO
Purchase Invoice (PI)    ← Menagih vendor, catat AP (harga diisi dari PO bila GRPO punya tautan PO)
```

### Gabung beberapa GRPO dari halaman Create PI

- Pada form **Create Purchase Invoice**, jika Anda **tidak** memilih satu PO sebagai sumber utama, panel **Invoice from supplier GRPO** menampilkan daftar **Open GRPOs** untuk **vendor yang dipilih** (semua entitas hukum dapat muncul; label menunjukkan kode/nama **Company** untuk membedakan PT/CV dll.).
- Anda **tidak** perlu mencocokkan **Company** hanya untuk memuat daftar GRPO terbuka — pilih **Vendor**, **Refresh list**, pilah GRPO sesuai entitas dari labelnya.
- Klik **Pull lines from selected GRPOs**: baris akan terisi (**qty dari GRPO**, **harga satuan dari baris PO** yang cocok untuk GRPO yang tertaut PO), dan field **Company** pada invoice akan diselaraskan ke entitas dokumen tersebut.
- Hanya GRPO berstatus **belum** tertaut Purchase Invoice tertentu yang muncul.

### Membuat PI dari satu GRPO (tombol dari detail GRPO)

1. Buka `Purchase > Goods Receipt PO`
2. Pilih GRPO yang ingin ditagih
3. Klik **Create Invoice**
4. Form PI terbuka dengan baris (**qty**, **deskripsi** dari GRPN, **Unit price** sesuai aturan PO seperti di atas)
5. Sesuaikan jika perlu (diskon, pajak, dimensi—harga bisa diedit tetapi disarankan selaras dokumen pembelian)
6. Simpan dan Post

### Perbedaan PI dari GRPO vs Direct

| Aspek | Dari GRPO | Direct Purchase |
|-------|-----------|-----------------|
| Inventory | Sudah diterima GRPO | Dibuat saat Post PI |
| goods_receipt_id | Terisi (satu atau banyak via pemilihan beberapa GRPO) | Kosong |
| is_direct_purchase | false | true |
| Document closure | GRPO bisa ditutup | — |

---

## Direct Cash Purchase

### Kapan Menggunakan

- Pembelian tunai langsung (sudah dibayar)
- Tidak melalui PO → GRPO
- Workflow singkat: **PI → Post** (selesai)

### Langkah

1. Payment Method = **Cash**
2. Jangan pilih PO/GRPO (sistem otomatis Direct Purchase)
3. Pilih **Item** dari inventory
4. Pilih **Warehouse** (untuk item fisik)
5. Pilih **Cash Account** (opsional, default: Kas di Tangan)
6. **Post** → Selesai

### Akuntansi

- Debit: Inventory Account
- Credit: Cash Account
- Tidak perlu Purchase Payment

---

## Opening Balance Invoice

### Kapan Menggunakan

- Mencatat invoice yang sudah ada (sebelum pakai sistem)
- **Tidak mempengaruhi** inventory

### Centang

- **Opening Balance Invoice** di form create/edit
- Invoice akan dicatat di AP tanpa mempengaruhi stok

---

## Validasi tanggal invoice

### Aturan

Saat **membuat** PI atau **mengubah draft** (belum posted), field **Date** harus **tidak lebih dari hari ini** (sesuai **zona waktu aplikasi** / `APP_TIMEZONE`).

### Pengecualian

1. **Opening Balance Invoice** — centang **Opening Balance Invoice** pada form create/edit. Tanggal boleh di masa depan jika memang dibutuhkan untuk pencatatan saldo awal (misalnya selaras dokumen sumber).
2. **Izin khusus** — pengguna dengan permission **`ap.invoices.future_date`** boleh mengisi tanggal invoice **setelah hari ini** tanpa centang opening balance. Permission ini diatur lewat **Admin → Roles** (superadmin biasanya sudah punya semua permission).

### Jika validasi menolak

Pesan error menjelaskan bahwa tanggal tidak boleh lebih dari hari ini kecuali salah satu pengecualian di atas. Sesuaikan tanggal, centang opening balance jika memang invoice saldo awal, atau minta admin memberi **`ap.invoices.future_date`**.

### Catatan teknis

Validasi di `PurchaseInvoiceController` (`store` dan `update`); posting dari draft **tidak** mengubah tanggal lewat request terpisah.

---

## Pemeliharaan: sinkron harga GRPO dari PO

Untuk dokumentasi dengan **`purchase_order_id`** yang sebelumnya tersimpan harga salah di baris GRPO (misalnya dari versi aplikasi yang memakai harga kartu item alih-alih PO), Anda dapat menyelaraskan ulang **`goods_receipt_po_lines`** dari baris Purchase Order secara massal atau per dokumen:

```bash
# Pratinjau perubahan (tidak menyimpan)
php artisan grpo:repair-lines-from-po-pricing --dry-run

# Terapkan satu GRPO dengan id tertentu
php artisan grpo:repair-lines-from-po-pricing --grpo=123

# Terapkan semua GRPO yang punya tautan PO
php artisan grpo:repair-lines-from-po-pricing
```

Perintah ini akan:

- Mengisi **`unit_price`**, **`amount`** (= harga × `qty` pada baris GRPO), **`account_id`**, dan **`tax_code_id`** untuk setiap baris inventori sesuai **baris pertama** pada PO untuk pasangan **`(purchase_order_id, item_id)`** (sama seperti logika baru saat simpan GRPO + prefill PI).
- Menjadikan **`goods_receipt_po.total_amount`** sama dengan penjumlahan **`amount`** baris.
- **Tidak** mengubah jurnal yang sudah dipost atau transaksi inventori yang sudah tercipta — jika Anda sudah mempost GRPO secara akuntansi, tinjau ulang pembukuan Anda setelah pembenahan.

---

## Pemecahan Masalah

| Masalah | Solusi |
|---------|--------|
| Invoice No tidak muncul | Pastikan Company Entity dipilih; klik Preview untuk cek nomor |
| Item tidak bisa dipilih | Pastikan item aktif; cek warehouse untuk item fisik |
| Available Qty 0 atau — | Cek warehouse stock; service item tidak punya stok |
| Diskon tidak terhitung | Periksa Disc % atau Disc Amt; pastikan trigger update (blur/change) |
| Amount After VAT 0 | Pastikan VAT dihitung; untuk No VAT harus sama dengan Net Amount |
| Tidak bisa Post | Cek account item; pastikan warehouse untuk item fisik |
| Tidak bisa Unpost | Pastikan belum ada payment allocation; belum closed |
| Tanggal ditolak (tidak boleh masa depan) | Gunakan tanggal ≤ hari ini, centang **Opening Balance** jika memang saldo awal, atau minta permission **`ap.invoices.future_date`** |
| Print error | Pastikan vendor (business partner) terisi |
| **Open GRPOs** kosong padahal harus ada penerimaan | Pastikan vendor benar dan GRPO tersebut belum tertaut Purchase Invoice lain; pemilihan PT/CV di header tidak membatasi daftar lagi — pilih GRPO berdasarkan label entitas |
| Simpan GRPO dengan PO: item ditolak | Item harus ada **pada PO yang dipilih**; gunakan baris dari salinan PO ke GRPO atau hapus item yang tidak termasuk PO |

---

## Referensi Cepat

- **Menu**: `Purchase > Purchase Invoices`
- **Format**: `EEYYDDNNNNN` (kode dokumen 03)
- **Alur**: PO → GRPO → PI → PP
- **Harga pembelian (PO tertaut GRPO/PI)**: qty di GRPO, harga acuan dari **baris PO**; jalankan **`php artisan grpo:repair-lines-from-po-pricing --dry-run`** bila ada data GRPO historis salah
- **Diskon**: Baris (per item) + Header (per invoice)
- **VAT**: Dihitung dari Net Amount (setelah diskon baris)
- **Available Qty**: Dari warehouse stock (akurat)
- **HELP in-app**: Setelah mengubah manual ini, jalankan **`php artisan help:reindex`** di tiap lingkungan yang memakai bantuan internal.
