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
13. [Pemecahan Masalah](#pemecahan-masalah)

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

### Footer Totals

- **Subtotal**: Jumlah amount setelah VAT per baris
- **Line Discounts**: Total diskon baris
- **Header Discount**: Diskon header (dihitung dari subtotal)
- **Total Discount**: Line Discounts + Header Discount
- **Amount Due**: Subtotal − Header Discount (total akhir)

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
Goods Receipt PO (GRPO)  ← Menerima barang, update inventory
    ↓ Create Invoice
Purchase Invoice (PI)    ← Menagih vendor, catat AP
```

### Membuat PI dari GRPO

1. Buka `Purchase > Goods Receipt PO`
2. Pilih GRPO yang ingin ditagih
3. Klik **Create Invoice**
4. Form PI terbuka dengan baris terisi dari GRPO
5. Sesuaikan jika perlu (harga, diskon, pajak)
6. Simpan dan Post

### Perbedaan PI dari GRPO vs Direct

| Aspek | Dari GRPO | Direct Purchase |
|-------|-----------|-----------------|
| Inventory | Sudah diterima GRPO | Dibuat saat Post PI |
| goods_receipt_id | Terisi | Kosong |
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
| Print error | Pastikan vendor (business partner) terisi |

---

## Referensi Cepat

- **Menu**: `Purchase > Purchase Invoices`
- **Format**: `EEYYDDNNNNN` (kode dokumen 03)
- **Alur**: PO → GRPO → PI → PP
- **Diskon**: Baris (per item) + Header (per invoice)
- **VAT**: Dihitung dari Net Amount (setelah diskon baris)
- **Available Qty**: Dari warehouse stock (akurat)
