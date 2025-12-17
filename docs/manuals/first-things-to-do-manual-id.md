# Hal-Hal Pertama yang Harus Dilakukan - Panduan Setup Sistem ERP

**Tujuan**: Panduan langkah demi langkah untuk konfigurasi awal sistem ERP  
**Target Pembaca**: Administrator Sistem dan Tim Implementasi  
**Perkiraan Waktu**: 4-6 jam untuk setup lengkap

---

## Daftar Isi

1. [Prasyarat](#prasyarat)
2. [Fase 1: Konfigurasi Sistem](#fase-1-konfigurasi-sistem)
3. [Fase 2: Fondasi Keuangan](#fase-2-fondasi-keuangan)
4. [Fase 3: Setup Master Data](#fase-3-setup-master-data)
5. [Fase 4: Setup Operasi Bisnis](#fase-4-setup-operasi-bisnis)
6. [Fase 5: Manajemen Pengguna](#fase-5-manajemen-pengguna)
7. [Fase 6: Verifikasi dan Pengujian](#fase-6-verifikasi-dan-pengujian)
8. [Daftar Periksa Cepat](#daftar-periksa-cepat)

---

## Fase 1: Konfigurasi Sistem

**Perkiraan Waktu**: 30-45 menit  
**Prioritas**: 🔴 KRITIS - Harus diselesaikan terlebih dahulu

### 1.1 Setup Informasi Perusahaan

**Lokasi**: `Admin > ERP Parameters > Company Info`

Konfigurasi informasi dasar perusahaan yang akan muncul di semua dokumen:

1. **Nama Perusahaan**

    - Navigasi ke: `Admin > ERP Parameters`
    - Kategori: `company_info`
    - Update: parameter `company_name`
    - Contoh: "PT Sarang Trading Indonesia"

2. **Alamat Perusahaan**

    - Update: parameter `company_address`
    - Contoh: "Jl. Sudirman No. 123, Jakarta Pusat 10110"

3. **Informasi Kontak**

    - Update: `company_phone` (contoh: "+62 21 1234 5678")
    - Update: `company_email` (contoh: "info@company.com")
    - Update: `company_website` (contoh: "www.company.com")

4. **Informasi Pajak**

    - Update: `company_tax_number` (NPWP)
    - Contoh: "01.234.567.8-901.000"

5. **Logo Perusahaan**
    - Upload logo perusahaan ke direktori `public/`
    - Update: parameter `company_logo_path` dengan nama file
    - Format yang didukung: PNG, JPG, SVG

**Mengapa Ini Penting**: Informasi ini muncul di semua purchase order, sales invoice, dan dokumen resmi.

---

### 1.2 Entitas Perusahaan (Setup Multi-Entity)

**Lokasi**: `Admin > Company Entities` (jika multi-entity diaktifkan)

Jika organisasi Anda mengoperasikan beberapa entitas legal:

1. **Verifikasi Setup Entitas**

    - Pastikan semua entitas legal telah dibuat
    - Entitas default: PT Cahaya Sarange Jaya (Kode: 71), CV Cahaya Saranghae (Kode: 72)

2. **Konfigurasi Detail Entitas**

    - Update nama entitas, alamat, nomor pajak
    - Upload logo khusus entitas
    - Konfigurasi metadata letterhead (warna, branding)

3. **Set Entitas Default**
    - Tentukan entitas mana yang menjadi default untuk dokumen baru
    - Konfigurasi di ERP Parameters jika diperlukan

**Mengapa Ini Penting**: Setiap entitas memerlukan penomoran dokumen, letterhead, dan pelaporan terpisah.

---

### 1.3 Konfigurasi Pengaturan Sistem

**Lokasi**: `Admin > ERP Parameters > System Settings`

Konfigurasi perilaku inti sistem:

1. **Mata Uang Default**

    - Parameter: `default_currency` (default: "IDR")
    - Parameter: `default_currency_id` (set ke ID mata uang IDR)
    - Verifikasi mata uang ada di sistem

2. **Timezone**

    - Parameter: `default_timezone` (default: "Asia/Jakarta")
    - Pastikan sesuai dengan lokasi bisnis Anda

3. **Pengaturan Penutupan Dokumen**

    - Konfigurasi threshold keterlambatan:
        - `po_overdue_days`: 30 hari
        - `grpo_overdue_days`: 15 hari
        - `pi_overdue_days`: 7 hari
        - `so_overdue_days`: 30 hari
        - `do_overdue_days`: 15 hari
        - `si_overdue_days`: 7 hari
    - `auto_close_days`: 90 hari
    - `enable_auto_closure`: true/false

4. **Penanganan Harga**
    - `allow_price_differences`: true/false
    - `max_price_difference_percent`: 10%

**Mengapa Ini Penting**: Pengaturan ini mengontrol pemrosesan dokumen otomatis, penanganan mata uang, dan penegakan aturan bisnis.

---

## Fase 2: Fondasi Keuangan

**Perkiraan Waktu**: 1-2 jam  
**Prioritas**: 🔴 KRITIS - Diperlukan sebelum transaksi

### 2.1 Verifikasi Chart of Accounts

**Lokasi**: `Accounting > Chart of Accounts`

1. **Verifikasi Chart of Accounts Dimuat**

    - Sistem harus memiliki 118+ akun yang sesuai PSAK
    - Periksa struktur akun:
        - **Aset (1.x.x.x)**: Kas, Bank, Persediaan, Piutang
        - **Kewajiban (2.x.x.x)**: Utang, Beban Akrual
        - **Ekuitas (3.x.x.x)**: Modal Saham, Laba Ditahan
        - **Pendapatan (4.x.x.x)**: Pendapatan Penjualan, Pendapatan Jasa
        - **Beban (5.x.x.x)**: HPP, Beban Operasional

2. **Verifikasi Akun Kunci Ada**

    - **Akun Kas**:
        - `1.1.1.01` - Kas di Bank - Operasional
        - `1.1.1.02` - Kas di Bank - Payroll
    - **Akun Persediaan**:
        - `1.1.3.01` - Persediaan Barang Dagangan
    - **Akun Piutang/Utang**:
        - `1.1.2.01` - Piutang Dagang
        - `1.1.2.04` - AR UnInvoice (Intermediate)
        - `2.1.1.01` - Utang Dagang
        - `2.1.1.03` - AP UnInvoice (Intermediate)
    - **Akun Pendapatan**:
        - `4.1.1.01` - Penjualan Stationery
        - `4.1.1.02` - Penjualan Electronics
    - **Akun HPP**:
        - `5.1.01` - HPP Stationery
        - `5.1.02` - HPP Electronics

3. **Tambah Akun yang Hilang** (jika diperlukan)
    - Buat akun khusus bisnis
    - Pastikan hierarki akun benar
    - Set flag `is_postable` dengan benar

**Mengapa Ini Penting**: Semua transaksi memerlukan akun yang valid. Akun yang hilang akan menyebabkan kegagalan posting.

---

### 2.2 Kategori Produk dengan Pemetaan Akun

**Lokasi**: `Master Data > Product Categories`

**KRITIS**: Kategori produk HARUS dibuat SEBELUM item inventory, karena mereka menentukan perilaku akuntansi.

1. **Buat Kategori Produk**

    - Navigasi ke: `Master Data > Product Categories`
    - Klik "Create New Category"

2. **Untuk Setiap Kategori, Konfigurasi**:

    - **Kode Kategori**: Pengenal unik (contoh: "ELECTRONICS")
    - **Nama Kategori**: Nama tampilan (contoh: "Electronics")
    - **Deskripsi**: Deskripsi singkat
    - **Pemetaan Akun** (WAJIB):
        - **Akun Persediaan**: Akun GL untuk nilai persediaan
            - Contoh: `1.1.3.01.02` - Persediaan Electronics
        - **Akun HPP**: Akun GL untuk harga pokok penjualan
            - Contoh: `5.1.02` - HPP Electronics
        - **Akun Penjualan**: Akun GL untuk pendapatan penjualan
            - Contoh: `4.1.1.02` - Penjualan Electronics

3. **Kategori Standar yang Harus Dibuat**:
    - **Stationery** (STATIONERY)
        - Persediaan: `1.1.3.01.01`
        - HPP: `5.1.01`
        - Penjualan: `4.1.1.01`
    - **Electronics** (ELECTRONICS)
        - Persediaan: `1.1.3.01.02`
        - HPP: `5.1.02`
        - Penjualan: `4.1.1.02`
    - **Furniture** (FURNITURE)
        - Persediaan: `1.1.3.01.03`
        - HPP: `5.1.03`
        - Penjualan: `4.1.1.03`
    - **Vehicles** (VEHICLES)
        - Persediaan: `1.1.3.01.04`
        - HPP: `5.1.04`
        - Penjualan: `4.1.1.04`
    - **Services** (SERVICES)
        - Persediaan: `null` (jasa tidak memiliki persediaan)
        - HPP: `5.1.05`
        - Penjualan: `4.1.1.05`

**Mengapa Ini Penting**: Pemetaan akun menentukan akun GL mana yang digunakan ketika item inventory dibeli, dijual, atau disesuaikan. Pemetaan yang salah menyebabkan kesalahan akuntansi.

---

### 2.3 Setup Mata Uang dan Kurs

**Lokasi**: `Admin > Currencies` dan `Admin > Exchange Rates`

1. **Verifikasi Mata Uang**

    - Mata uang default (IDR) harus ada
    - Tambah mata uang tambahan jika diperlukan:
        - USD, EUR, SGD, dll.

2. **Konfigurasi Kurs**

    - Navigasi ke: `Admin > Exchange Rates`
    - Set kurs saat ini untuk semua mata uang aktif
    - Update kurs secara teratur (harian/mingguan)

3. **Pengaturan Mata Uang**
    - Verifikasi `default_currency_id` di ERP Parameters
    - Konfigurasi `auto_exchange_rate_enabled` jika menggunakan pengambilan kurs otomatis
    - Set `exchange_rate_tolerance` (default: 10%)

**Mengapa Ini Penting**: Transaksi multi-mata uang memerlukan kurs yang akurat untuk akuntansi yang benar.

---

### 2.4 Setup Control Accounts

**Lokasi**: `Accounting > Control Accounts`

Control accounts memungkinkan rekonsiliasi antara GL dan buku besar tambahan:

1. **Verifikasi Control Accounts**

    - Sistem harus membuat otomatis setelah data seeding
    - Periksa untuk:
        - **AR Control Account**: `1.1.2.01` - Piutang Dagang
        - **AP Control Account**: `2.1.1.01` - Utang Dagang
        - **Inventory Control Account**: `1.1.3.01` - Persediaan Barang Dagangan

2. **Inisialisasi Subsidiary Ledgers**

    - AR Control: Tautan ke Business Partners (Pelanggan)
    - AP Control: Tautan ke Business Partners (Pemasok)
    - Inventory Control: Tautan ke Product Categories

3. **Verifikasi Rekonsiliasi**
    - Navigasi ke: `Accounting > Control Accounts > Reconciliation`
    - Periksa bahwa saldo direkonsiliasi (harus nol awalnya)

**Mengapa Ini Penting**: Control accounts memastikan saldo GL sesuai dengan buku besar tambahan yang detail untuk pelaporan keuangan yang akurat.

---

## Fase 3: Setup Master Data

**Perkiraan Waktu**: 1-2 jam  
**Prioritas**: 🟡 TINGGI - Diperlukan untuk operasi harian

### 3.1 Setup Gudang

**Lokasi**: `Inventory > Warehouses`

1. **Buat Gudang**

    - Navigasi ke: `Inventory > Warehouses`
    - Klik "Create New Warehouse"

2. **Untuk Setiap Gudang, Konfigurasi**:

    - **Kode Gudang**: Pengenal unik
    - **Nama Gudang**: Nama tampilan
    - **Alamat**: Lokasi fisik
    - **Tipe**: Gudang reguler (gudang transit dibuat otomatis)
    - **Is Active**: Aktifkan/nonaktifkan

3. **Gudang Standar**:
    - Main Warehouse (penyimpanan utama)
    - Branch Warehouse (lokasi cabang)
    - Distribution Center (pusat distribusi)

**Catatan**: Gudang transit (untuk ITO/ITI) secara otomatis difilter dari pemilihan manual.

**Mengapa Ini Penting**: Semua purchase order dan sales order memerlukan pemilihan gudang untuk pelacakan inventory.

---

### 3.2 Setup Business Partners

**Lokasi**: `Business Partner > Business Partners`

Business Partners dapat menjadi pelanggan dan pemasok:

1. **Buat Pemasok**

    - Navigasi ke: `Business Partner > Create`
    - Pilih **Tipe Partner**: "Supplier"
    - **Informasi yang Diperlukan**:
        - Kode Partner (unik)
        - Nama Legal
        - Nomor Pajak (NPWP)
        - Alamat
        - Informasi Kontak
    - **Tab Accounting**:
        - Verifikasi pemetaan akun AP (otomatis ditetapkan: `2.1.1.01`)
    - **Tab Terms & Conditions**:
        - Syarat Pembayaran (contoh: "Net 30")
        - Batas Kredit
        - Pengaturan Pajak

2. **Buat Pelanggan**

    - Pilih **Tipe Partner**: "Customer"
    - **Informasi yang Diperlukan**: Sama dengan pemasok
    - **Tab Accounting**:
        - Verifikasi pemetaan akun AR (otomatis ditetapkan: `1.1.2.01`)
    - **Tab Terms & Conditions**:
        - Syarat Pembayaran
        - Batas Kredit
        - Tingkat Harga (1-3)

3. **Buat Partner Ganda** (jika entitas adalah pelanggan dan pemasok)
    - Pilih **Tipe Partner**: "Both" (jika tersedia)
    - Konfigurasi kedua akun AR dan AP

**Mengapa Ini Penting**: Semua transaksi pembelian dan penjualan memerlukan business partners yang valid. Batas kredit dan syarat pembayaran mempengaruhi workflow persetujuan.

---

### 3.3 Setup Proyek dan Departemen

**Lokasi**: `Master Data > Projects` dan `Master Data > Departments`

Untuk akuntansi multi-dimensi:

1. **Buat Proyek**

    - Navigasi ke: `Master Data > Projects`
    - Buat proyek untuk pelacakan biaya
    - Contoh: "Project Alpha", "Project Beta"

2. **Buat Departemen**
    - Navigasi ke: `Master Data > Departments`
    - Buat departemen untuk alokasi biaya
    - Contoh: "Sales", "Operations", "Finance"

**Mengapa Ini Penting**: Proyek dan departemen memungkinkan pelacakan biaya dan pelaporan multi-dimensi untuk analisis keuangan yang lebih baik.

---

### 3.4 Setup Syarat Pembayaran

**Lokasi**: `Master Data > Terms` (jika tersedia)

1. **Buat Syarat Pembayaran**

    - Syarat umum:
        - "Net 15" - Pembayaran jatuh tempo dalam 15 hari
        - "Net 30" - Pembayaran jatuh tempo dalam 30 hari
        - "Net 60" - Pembayaran jatuh tempo dalam 60 hari
        - "Due on Receipt" - Pembayaran langsung

2. **Tetapkan ke Business Partners**
    - Set syarat default saat membuat business partners
    - Syarat muncul di dokumen pembelian dan penjualan

**Mengapa Ini Penting**: Syarat pembayaran menentukan tanggal jatuh tempo untuk invoice dan mempengaruhi laporan aging.

---

## Fase 4: Setup Operasi Bisnis

**Perkiraan Waktu**: 1-2 jam  
**Prioritas**: 🟡 TINGGI - Diperlukan untuk transaksi

### 4.1 Pembuatan Item Inventory

**Lokasi**: `Inventory > Inventory Items`

**PENTING**: Buat kategori produk TERLEBIH DAHULU (lihat Fase 2.2)

1. **Buat Item Inventory**

    - Navigasi ke: `Inventory > Add Item`
    - **Informasi yang Diperlukan**:
        - Kode Item (unik)
        - Nama Item
        - **Kategori Produk** (WAJIB - harus ada)
        - Tipe Item: "Item" atau "Service"
        - Satuan Ukur (unit dasar)
        - **Gudang** (untuk stok awal)
    - **Harga**:
        - Harga Beli
        - Harga Jual
        - Tingkat Harga (1-3) jika menggunakan harga bertingkat
    - **Pengaturan Inventory**:
        - Titik Pemesanan Ulang
        - Level Stok Minimum
        - Level Stok Maksimum

2. **Setup Satuan Ukur** (jika menggunakan konversi)

    - Navigasi ke: `Inventory > Units of Measure`
    - Buat unit dasar: Piece, Box, Dozen, dll.
    - Konfigurasi faktor konversi jika diperlukan

3. **Entri Stok Awal** (jika berlaku)
    - Gunakan Inventory Adjustment atau sistem GR/GI
    - Masukkan saldo awal untuk setiap gudang

**Mengapa Ini Penting**: Item inventory diperlukan untuk semua transaksi pembelian dan penjualan. Kategori yang hilang atau pemetaan akun yang salah menyebabkan kesalahan posting.

---

### 4.2 Setup Kode Pajak

**Lokasi**: `Admin > Tax Codes` (jika tersedia)

1. **Verifikasi Kode Pajak**

    - Sistem harus memiliki kode pajak Indonesia yang di-seed:
        - **PPN 11%**: Pajak Pertambahan Nilai (VAT)
        - **PPh 21**: Pajak Penghasilan Karyawan
        - **PPh 23**: Pajak Penghasilan Pasal 23 (Jasa)
        - **PPh 4(2)**: Pajak Penghasilan Final

2. **Konfigurasi Pengaturan Pajak**
    - Verifikasi tarif pajak benar
    - Update jika undang-undang pajak berubah

**Mengapa Ini Penting**: Kode pajak digunakan dalam purchase invoice dan sales invoice untuk kepatuhan pajak Indonesia.

---

### 4.3 Konfigurasi Approval Workflows

**Lokasi**: `Admin > Approval Workflows` (jika tersedia)

1. **Verifikasi Workflow Default**

    - Sistem harus memiliki workflow default untuk:
        - Purchase Orders
        - Sales Orders

2. **Konfigurasi Threshold Persetujuan**

    - **Purchase Orders**:
        - 0 - 5.000.000: Persetujuan Officer
        - 5.000.000 - 15.000.000: Officer + Supervisor
        - 15.000.000+: Officer + Supervisor + Manager
    - **Sales Orders**: Threshold yang sama

3. **Tetapkan Role Pengguna**
    - Pastikan pengguna memiliki role yang benar:
        - `officer`
        - `supervisor`
        - `manager`

**Mengapa Ini Penting**: Approval workflows mengontrol otorisasi dokumen. Workflow yang hilang mencegah persetujuan dokumen.

---

## Fase 5: Manajemen Pengguna

**Perkiraan Waktu**: 30-45 menit  
**Prioritas**: 🟡 TINGGI - Diperlukan untuk akses sistem

### 5.1 Pembuatan Akun Pengguna

**Lokasi**: `Admin > Users`

1. **Buat Akun Pengguna**

    - Navigasi ke: `Admin > Users > Create`
    - **Informasi yang Diperlukan**:
        - Nama
        - Email (unik)
        - Username (unik)
        - Password (disarankan password kuat)
    - **Tetapkan Role**:
        - Admin (akses penuh)
        - Manager (akses manajemen)
        - User (akses operasional)
        - Role kustom sesuai kebutuhan

2. **Tetapkan Izin**

    - Izin granular tersedia:
        - Akses modul (inventory, sales, purchase, dll.)
        - Izin operasi (view, create, update, delete, post, reverse)
    - Gunakan penetapan berbasis role untuk efisiensi

3. **Setup Role Pengguna**
    - Navigasi ke: `Admin > Roles`
    - Verifikasi role default ada:
        - `admin`
        - `manager`
        - `user`
    - Buat role kustom jika diperlukan

**Mengapa Ini Penting**: Manajemen pengguna yang tepat memastikan keamanan dan kontrol akses yang sesuai.

---

### 5.2 Penetapan Role Persetujuan

**Lokasi**: `Admin > User Roles` (jika tersedia)

1. **Tetapkan Role Persetujuan**

    - Pengguna memerlukan role persetujuan untuk persetujuan dokumen:
        - `officer` - Persetujuan level pertama
        - `supervisor` - Persetujuan level kedua
        - `manager` - Persetujuan akhir

2. **Verifikasi Approval Workflow**
    - Uji bahwa approval workflows diarahkan dengan benar
    - Verifikasi pengguna menerima notifikasi persetujuan

**Mengapa Ini Penting**: Approval workflows memerlukan pengguna dengan role yang tepat untuk berfungsi.

---

## Fase 6: Verifikasi dan Pengujian

**Perkiraan Waktu**: 30-45 menit  
**Prioritas**: 🟢 SEDANG - Memastikan kesiapan sistem

### 6.1 Daftar Periksa Verifikasi Sistem

Verifikasi semua komponen kritis:

-   [ ] Informasi perusahaan ditampilkan dengan benar di dokumen
-   [ ] Chart of Accounts memiliki semua akun yang diperlukan
-   [ ] Kategori produk memiliki pemetaan akun
-   [ ] Gudang dibuat dan aktif
-   [ ] Setidaknya satu pemasok (business partner) ada
-   [ ] Setidaknya satu pelanggan (business partner) ada
-   [ ] Setidaknya satu item inventory ada
-   [ ] Pengguna dapat login dengan role yang ditetapkan
-   [ ] Approval workflows dikonfigurasi
-   [ ] Mata uang dan kurs ditetapkan

---

### 6.2 Uji Workflow Transaksi

Lakukan pengujian end-to-end:

1. **Uji Siklus Pembelian**:

    - Buat Purchase Order
    - Verifikasi generasi nomor PO
    - Buat Goods Receipt PO (GRPO)
    - Verifikasi update inventory
    - Buat Purchase Invoice
    - Verifikasi journal entries
    - Buat Purchase Payment
    - Verifikasi update akun kas

2. **Uji Siklus Penjualan**:

    - Buat Sales Order
    - Verifikasi generasi nomor SO
    - Buat Delivery Order
    - Verifikasi reservasi inventory
    - Buat Sales Invoice
    - Verifikasi journal entries
    - Buat Sales Receipt
    - Verifikasi update akun kas

3. **Verifikasi Journal**:
    - Navigasi ke: `Accounting > Journals`
    - Verifikasi journal entries seimbang
    - Periksa saldo akun
    - Verifikasi rekonsiliasi control account

---

### 6.3 Masalah Umum dan Solusi

**Masalah**: Error "Journal is not balanced"

-   **Solusi**: Verifikasi pemetaan akun kategori produk benar

**Masalah**: Error "Account not found"

-   **Solusi**: Verifikasi chart of accounts lengkap, tambah akun yang hilang

**Masalah**: Approval workflow tidak berfungsi

-   **Solusi**: Verifikasi pengguna memiliki role persetujuan yang benar

**Masalah**: Nomor dokumen tidak ter-generate

-   **Solusi**: Verifikasi sequence dokumen diinisialisasi di database

**Masalah**: Item inventory tidak muncul di dropdown

-   **Solusi**: Verifikasi item ditetapkan ke kategori produk dengan pemetaan akun

---

## Daftar Periksa Cepat

Gunakan daftar periksa ini untuk verifikasi setup cepat:

### Kritis (Harus Diselesaikan Terlebih Dahulu)

-   [ ] Informasi perusahaan dikonfigurasi
-   [ ] Chart of Accounts diverifikasi (118+ akun)
-   [ ] Kategori Produk dibuat dengan pemetaan akun
-   [ ] Setidaknya satu Gudang dibuat
-   [ ] Setidaknya satu Pemasok (Business Partner) dibuat
-   [ ] Setidaknya satu Pelanggan (Business Partner) dibuat
-   [ ] Setidaknya satu Item Inventory dibuat
-   [ ] Pengguna dibuat dengan role yang tepat

### Prioritas Tinggi (Selesaikan Sebelum Operasi)

-   [ ] Mata uang dan kurs dikonfigurasi
-   [ ] Control accounts diverifikasi
-   [ ] Approval workflows dikonfigurasi
-   [ ] Syarat pembayaran dibuat
-   [ ] Proyek dan Departemen dibuat (jika menggunakan akuntansi multi-dimensi)
-   [ ] Kode pajak diverifikasi

### Prioritas Sedang (Dapat Diselesaikan Selama Operasi)

-   [ ] Gudang tambahan dibuat
-   [ ] Business partners tambahan ditambahkan
-   [ ] Item inventory tambahan ditambahkan
-   [ ] Role dan izin kustom dikonfigurasi
-   [ ] ERP Parameters disesuaikan

---

## Langkah Selanjutnya Setelah Setup

Setelah setup awal selesai:

1. **Pelatihan**: Tinjau materi pelatihan di `docs/comprehensive-training/`
2. **Migrasi Data**: Impor data yang ada jika bermigrasi dari sistem lain
3. **Persiapan Go-Live**:
    - Set saldo awal
    - Konfigurasi tanggal periode
    - Siapkan pelatihan pengguna
4. **Pemeliharaan Berkelanjutan**:
    - Update kurs secara teratur
    - Manajemen akun pengguna
    - Penyesuaian approval workflow
    - Penyetelan parameter ERP

---

## Dukungan dan Dokumentasi

-   **Dokumentasi Arsitektur**: `docs/architecture.md`
-   **Materi Pelatihan**: `docs/comprehensive-training/`
-   **Skenario Pengujian**: `docs/comprehensive-erp-testing-scenario.md`
-   **Memory/Decisions**: `MEMORY.md` dan `docs/decisions.md`

---

## Catatan Penting

1. **Urutan Penting**: Kategori produk HARUS dibuat sebelum item inventory
2. **Pemetaan Akun**: Setiap kategori produk memerlukan pemetaan akun persediaan, HPP, dan penjualan
3. **Business Partners**: Dapat menjadi pelanggan dan pemasok (sistem terpadu)
4. **Multi-Entity**: Jika menggunakan beberapa entitas, konfigurasi pengaturan khusus entitas
5. **Pengujian**: Selalu uji dengan data sampel sebelum penggunaan produksi

---

**Terakhir Diupdate**: 2025-01-20  
**Versi**: 1.0  
**Dikelola Oleh**: Tim Implementasi ERP
