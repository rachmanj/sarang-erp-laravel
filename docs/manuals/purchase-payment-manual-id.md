# Manual Modul Purchase Payment

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Memulai](#memulai)
3. [Konsep Utama](#konsep-utama)
4. [Membuat Purchase Payment](#membuat-purchase-payment)
5. [Melihat Purchase Payment](#melihat-purchase-payment)
6. [Posting Purchase Payment](#posting-purchase-payment)
7. [Alokasi Pembayaran](#alokasi-pembayaran)
8. [Praktik Terbaik](#praktik-terbaik)
9. [Pemecahan Masalah](#pemecahan-masalah)
10. [Referensi Cepat](#referensi-cepat)

---

## Pengenalan

### Apa itu Purchase Payment?

Purchase Payment (PP) adalah dokumen yang digunakan untuk mencatat pembayaran kepada vendor untuk invoice pembelian yang dilakukan secara kredit. Modul ini memungkinkan Anda untuk:

-   Mencatat pembayaran kepada vendor
-   Mengalokasikan pembayaran ke satu atau beberapa Purchase Invoice
-   Melacak status pembayaran dan penutupan invoice
-   Mengintegrasikan dengan sistem akuntansi untuk pencatatan otomatis

### Siapa yang Menggunakan Modul Ini?

-   **Tim Akuntansi/AP**: Mencatat pembayaran vendor dan mengalokasikan ke invoice
-   **Manajemen Keuangan**: Memantau arus kas keluar dan outstanding Accounts Payable
-   **Auditor**: Melacak transaksi pembayaran untuk keperluan audit

### Manfaat Utama

-   **Alokasi Fleksibel**: Satu pembayaran dapat dialokasikan ke beberapa invoice
-   **Pembayaran Parsial**: Mendukung pembayaran sebagian dari invoice
-   **Pelacakan Otomatis**: Sistem otomatis menutup invoice ketika sudah dibayar penuh
-   **Integrasi Akuntansi**: Pencatatan jurnal otomatis saat posting
-   **Multi-Mata Uang**: Mendukung pembayaran dalam mata uang asing dengan kurs

---

## Memulai

### Akses Menu

Untuk mengakses modul Purchase Payment:

1. Login ke sistem ERP
2. Klik menu **Purchase** di sidebar
3. Pilih **Purchase Payments**

Atau akses langsung melalui URL: `/purchase-payments`

### Prasyarat

Sebelum membuat Purchase Payment, pastikan:

-   ✅ **Vendor** sudah dibuat di modul Business Partner
-   ✅ **Purchase Invoice** sudah dibuat dan di-approve (status: Posted)
-   ✅ **Purchase Invoice** menggunakan metode pembayaran **Credit** (bukan Cash)
-   ✅ **Account Kas/Bank** sudah dikonfigurasi di Chart of Accounts
-   ✅ **Company Entity** sudah dikonfigurasi dengan kode entitas

### Alur Umum

1. **Buat Purchase Invoice** dengan metode pembayaran Credit
2. **Post Purchase Invoice** → Membuat liability (Utang Dagang)
3. **Buat Purchase Payment** → Pilih invoice yang akan dibayar
4. **Alokasikan pembayaran** ke invoice yang dipilih
5. **Post Purchase Payment** → Mencatat jurnal pembayaran

---

## Konsep Utama

### Penomoran Dokumen

Purchase Payment menggunakan format penomoran terpadu `EEYYDDNNNNN`:

-   **EE** = Kode Entitas (2 digit)
    -   Contoh: `71` untuk PT Cahaya Sarange Jaya
    -   Contoh: `72` untuk CV Cahaya Sarange
-   **YY** = Tahun (2 digit terakhir)
    -   Contoh: `26` untuk tahun 2026
-   **DD** = Kode Dokumen `04` untuk Purchase Payment
-   **NNNNN** = Nomor Urut (5 digit, diisi nol di depan)

**Contoh Nomor:**

-   `71260400001` = Purchase Payment pertama tahun 2026 untuk entitas 71
-   `72260400001` = Purchase Payment pertama tahun 2026 untuk entitas 72

### Status Dokumen

Purchase Payment memiliki dua status utama:

1. **DRAFT**: Dokumen baru dibuat, belum di-posting
    - Dapat diedit atau dihapus
    - Belum ada pencatatan akuntansi
2. **POSTED**: Dokumen sudah di-posting
    - Tidak dapat diedit
    - Sudah ada pencatatan jurnal akuntansi
    - Invoice yang dialokasikan akan ditutup jika sudah dibayar penuh

### Alokasi Pembayaran

**Alokasi** adalah proses menghubungkan pembayaran dengan invoice yang dibayar. Satu Purchase Payment dapat dialokasikan ke:

-   **Satu Invoice**: Pembayaran untuk satu invoice tertentu
-   **Beberapa Invoice**: Satu pembayaran untuk beberapa invoice sekaligus
-   **Pembayaran Parsial**: Membayar sebagian dari invoice (misalnya 50% dari total)

### Akuntansi Purchase Payment

Saat Purchase Payment di-posting, sistem akan membuat jurnal:

```
Debit:  Utang Dagang (Accounts Payable) - 2.1.1.01
Credit: Kas di Tangan (Cash) - 1.1.1.01
```

**Persamaan Akuntansi:**

```
Assets (Kas) ↓ = Liabilities (Utang Dagang) ↓
```

### Penutupan Invoice

Sistem akan otomatis menutup Purchase Invoice ketika:

-   Total alokasi pembayaran ≥ Total invoice
-   Invoice sudah di-posting
-   Status invoice berubah menjadi **Closed**

---

## Membuat Purchase Payment

### Langkah-langkah Membuat Purchase Payment

#### Langkah 1: Buka Form Create

1. Klik menu **Purchase > Purchase Payments**
2. Klik tombol **Create** di pojok kanan atas
3. Form Create Purchase Payment akan terbuka

#### Langkah 2: Isi Informasi Dasar

Isi informasi berikut:

-   **Date** (Tanggal): Tanggal pembayaran (wajib)
-   **Company** (Perusahaan): Pilih entitas perusahaan (wajib)
-   **Vendor** (Vendor): Pilih vendor yang akan dibayar (wajib)
-   **Description** (Deskripsi): Keterangan pembayaran (opsional)

**Catatan**: Setelah memilih Vendor, sistem akan otomatis memuat daftar invoice yang tersedia.

#### Langkah 3: Pilih Invoice yang Akan Dibayar

Setelah memilih Vendor, sistem akan menampilkan **tabel "Select Invoices to Pay"** dengan kolom:

-   **Checkbox**: Untuk memilih invoice
-   **Invoice #**: Nomor invoice
-   **Date**: Tanggal invoice
-   **Due Date**: Tanggal jatuh tempo
-   **Total Amount**: Total invoice
-   **Allocated**: Jumlah yang sudah dialokasikan
-   **Remaining**: Sisa yang belum dibayar
-   **Allocation Amount**: Jumlah yang akan dialokasikan (dapat diubah)

**Cara Memilih Invoice:**

1. **Pilih Manual**: Centang checkbox pada invoice yang ingin dibayar

    - Sistem akan otomatis mengisi **Allocation Amount** dengan nilai **Remaining**
    - Anda dapat mengubah jumlah alokasi sesuai kebutuhan

2. **Select All**: Klik tombol **Select All** untuk memilih semua invoice

    - Berguna jika ingin membayar semua invoice outstanding sekaligus

3. **Deselect All**: Klik tombol **Deselect All** untuk membatalkan semua pilihan

**Mengatur Jumlah Alokasi:**

-   Klik pada kolom **Allocation Amount** untuk mengubah jumlah
-   Sistem akan memvalidasi bahwa jumlah tidak melebihi **Remaining**
-   Jika melebihi, sistem akan otomatis menyesuaikan ke maksimum yang tersedia

**Fitur Tambahan:**

-   Invoice yang **Overdue** (jatuh tempo) akan ditandai dengan warna kuning
-   Badge **Overdue X days** menunjukkan berapa hari invoice sudah jatuh tempo
-   Invoice yang sudah **Fully Paid** tidak akan muncul dalam daftar

#### Langkah 4: Konfirmasi Payment Lines

Setelah memilih invoice, sistem akan otomatis membuat **Payment Line** dengan:

-   **Bank/Cash Account**: Pilih akun kas/bank yang digunakan untuk pembayaran
-   **Amount**: Jumlah pembayaran (otomatis terisi sesuai total alokasi)
-   **Notes**: Catatan tambahan (opsional)

**Catatan**:

-   Total Payment harus sama dengan Total Allocation
-   Sistem akan memvalidasi kesesuaian ini secara real-time
-   Jika tidak sesuai, tombol **Save Payment** akan dinonaktifkan

#### Langkah 5: Simpan Purchase Payment

1. Pastikan semua informasi sudah benar
2. Pastikan **Total Payment = Total Allocation**
3. Klik tombol **Save Payment**
4. Sistem akan membuat Purchase Payment dengan status **DRAFT**

**Validasi yang Dilakukan:**

-   ✅ Minimal satu invoice harus dipilih
-   ✅ Jumlah alokasi tidak boleh melebihi remaining balance
-   ✅ Total payment harus sama dengan total allocation
-   ✅ Invoice harus dalam status Posted
-   ✅ Invoice harus milik vendor yang dipilih

---

## Melihat Purchase Payment

### Mengakses Detail Purchase Payment

1. Klik menu **Purchase > Purchase Payments**
2. Klik tombol **View** pada baris Purchase Payment yang ingin dilihat
3. Halaman detail akan menampilkan informasi lengkap

### Informasi yang Ditampilkan

#### 1. Header Information

-   **Payment Number**: Nomor Purchase Payment (contoh: `71260400001`)
-   **Status**: Status dokumen (DRAFT atau POSTED)
-   **Action Buttons**:
    -   **Relationship Map**: Melihat hubungan dokumen terkait
    -   **Post**: Untuk posting dokumen (jika masih DRAFT)
    -   **Print**: Mencetak dokumen
    -   **PDF**: Mengunduh PDF
    -   **Queue PDF**: Generate PDF di background
    -   **Preview Journal**: Melihat jurnal yang akan dibuat

#### 2. Payment Information

Menampilkan informasi pembayaran:

-   **Payment Number**: Nomor dokumen pembayaran
-   **Payment Date**: Tanggal pembayaran
-   **Vendor**: Nama vendor (dapat diklik untuk melihat detail)
-   **Company Entity**: Entitas perusahaan
-   **Description**: Deskripsi pembayaran
-   **Total Amount**: Total jumlah pembayaran

#### 3. System Information

Menampilkan informasi sistem:

-   **Created At**: Tanggal dan waktu pembuatan dokumen
-   **Created By**: User yang membuat dokumen (diambil dari audit log)
-   **Last Updated**: Tanggal dan waktu terakhir update

#### 4. Purchase Invoices Being Paid

Tabel yang menampilkan invoice yang dialokasikan:

| Kolom             | Deskripsi                                                                       |
| ----------------- | ------------------------------------------------------------------------------- |
| Invoice #         | Nomor invoice (dapat diklik untuk melihat detail)                               |
| Invoice Date      | Tanggal invoice                                                                 |
| Due Date          | Tanggal jatuh tempo                                                             |
| Invoice Total     | Total invoice                                                                   |
| Allocation Amount | Jumlah yang dialokasikan (highlighted)                                          |
| Status            | Status invoice (Posted/Draft) dan status pembayaran (Fully Paid/Partially Paid) |
| Actions           | Tombol untuk melihat detail invoice                                             |

**Total Allocation**: Total semua alokasi ditampilkan di bagian bawah tabel.

#### 5. Payment Lines

Tabel yang menampilkan detail pembayaran:

| Kolom        | Deskripsi          |
| ------------ | ------------------ |
| Account Code | Kode akun kas/bank |
| Account Name | Nama akun kas/bank |
| Description  | Catatan pembayaran |
| Amount       | Jumlah pembayaran  |

**Total Payment**: Total semua payment lines ditampilkan di bagian bawah tabel.

---

## Posting Purchase Payment

### Kapan Harus Posting?

Purchase Payment harus di-posting ketika:

-   ✅ Pembayaran sudah dilakukan secara fisik
-   ✅ Semua informasi sudah benar dan lengkap
-   ✅ Alokasi sudah sesuai dengan invoice yang dibayar
-   ✅ Tidak ada perubahan yang diperlukan lagi

### Langkah-langkah Posting

1. Buka halaman detail Purchase Payment
2. Pastikan status masih **DRAFT**
3. Klik tombol **Post** di header
4. Sistem akan memvalidasi dan membuat jurnal akuntansi
5. Status akan berubah menjadi **POSTED**

### Apa yang Terjadi Saat Posting?

1. **Jurnal Akuntansi Dibuat**:

    ```
    Debit:  Utang Dagang (2.1.1.01) - Total Payment
    Credit: Kas di Tangan (1.1.1.01) - Total Payment
    ```

2. **Status Berubah**: DRAFT → POSTED

3. **Invoice Closure**:

    - Sistem memeriksa setiap invoice yang dialokasikan
    - Jika total alokasi ≥ total invoice, invoice akan ditutup
    - Status invoice berubah menjadi **Closed**

4. **Audit Trail**:
    - Dicatat siapa yang melakukan posting
    - Dicatat waktu posting

### Validasi Sebelum Posting

Sistem akan memvalidasi:

-   ✅ Purchase Payment masih dalam status DRAFT
-   ✅ Periode akuntansi belum ditutup
-   ✅ Total payment sama dengan total allocation
-   ✅ Semua invoice yang dialokasikan valid

---

## Alokasi Pembayaran

### Konsep Alokasi

**Alokasi** adalah proses menghubungkan pembayaran dengan invoice yang dibayar. Setiap Purchase Payment harus memiliki minimal satu alokasi.

### Jenis-jenis Alokasi

#### 1. Alokasi Penuh (Full Allocation)

Membayar seluruh invoice:

-   **Allocation Amount** = **Remaining Balance**
-   Invoice akan ditutup setelah posting
-   Status invoice: **Fully Paid**

**Contoh:**

-   Invoice Total: Rp 1.000.000
-   Remaining: Rp 1.000.000
-   Allocation: Rp 1.000.000 ✅

#### 2. Alokasi Parsial (Partial Allocation)

Membayar sebagian invoice:

-   **Allocation Amount** < **Remaining Balance**
-   Invoice tetap terbuka setelah posting
-   Status invoice: **Partially Paid**

**Contoh:**

-   Invoice Total: Rp 1.000.000
-   Remaining: Rp 1.000.000
-   Allocation: Rp 500.000 (50%)
-   Sisa: Rp 500.000 (akan muncul di pembayaran berikutnya)

#### 3. Alokasi Multi-Invoice

Satu pembayaran untuk beberapa invoice:

-   Pilih beberapa invoice dengan checkbox
-   Set jumlah alokasi untuk masing-masing invoice
-   Total allocation = Total payment

**Contoh:**

-   Invoice A: Rp 500.000 (Allocation: Rp 500.000)
-   Invoice B: Rp 300.000 (Allocation: Rp 300.000)
-   Invoice C: Rp 200.000 (Allocation: Rp 200.000)
-   **Total Payment**: Rp 1.000.000 ✅

### Strategi Alokasi

#### FIFO (First In First Out)

Membayar invoice tertua terlebih dahulu:

1. Sistem menampilkan invoice diurutkan berdasarkan **Due Date** (tertua dulu)
2. Pilih invoice yang paling lama jatuh tempo
3. Berguna untuk menghindari denda keterlambatan

#### Prioritas Vendor

Membayar vendor tertentu terlebih dahulu:

1. Filter berdasarkan vendor
2. Pilih invoice dari vendor prioritas
3. Berguna untuk menjaga hubungan baik dengan vendor penting

#### Pembayaran Parsial Strategis

Membayar sebagian dari beberapa invoice:

1. Pilih beberapa invoice
2. Alokasikan jumlah yang sama atau proporsional
3. Berguna untuk menjaga cash flow

---

## Praktik Terbaik

### 1. Verifikasi Invoice Sebelum Membayar

-   ✅ Pastikan invoice sudah di-approve dan di-posting
-   ✅ Verifikasi jumlah invoice sesuai dengan dokumen fisik
-   ✅ Periksa tanggal jatuh tempo untuk menghindari denda

### 2. Gunakan Deskripsi yang Jelas

-   Tuliskan deskripsi yang menjelaskan tujuan pembayaran
-   Contoh: "Pembayaran Invoice Bulan Januari 2026"
-   Memudahkan audit dan pelacakan

### 3. Validasi Real-time

-   Sistem akan memvalidasi secara real-time saat Anda mengetik
-   Perhatikan pesan validasi yang muncul
-   Pastikan Total Payment = Total Allocation sebelum menyimpan

### 4. Review Sebelum Posting

-   Setelah membuat Purchase Payment, review kembali detailnya
-   Pastikan invoice yang dipilih sudah benar
-   Pastikan jumlah alokasi sesuai dengan dokumen fisik

### 5. Dokumentasi

-   Simpan bukti pembayaran fisik
-   Catat nomor Purchase Payment pada bukti pembayaran
-   Memudahkan rekonsiliasi bank

### 6. Penanganan Uang Muka (Prepayment)

Jika melakukan pembayaran sebelum invoice dibuat:

1. Buat Purchase Payment tanpa alokasi (atau dengan alokasi sementara)
2. Simpan sebagai DRAFT
3. Setelah invoice dibuat, edit Purchase Payment dan alokasikan ke invoice
4. Post Purchase Payment

### 7. Penanganan Selisih Kecil

Jika ada selisih kecil antara payment dan allocation:

-   Sistem memiliki toleransi ±0.01 untuk pembulatan
-   Jika selisih lebih besar, periksa kembali perhitungan
-   Hubungi administrator jika diperlukan

---

## Pemecahan Masalah

### Masalah: Invoice Tidak Muncul di Daftar

**Penyebab:**

-   Invoice belum di-posting
-   Invoice menggunakan metode pembayaran Cash (Direct Purchase)
-   Invoice sudah dibayar penuh
-   Invoice milik vendor yang berbeda

**Solusi:**

1. Pastikan invoice sudah di-posting (status: Posted)
2. Periksa metode pembayaran invoice (harus Credit)
3. Periksa remaining balance invoice
4. Pastikan vendor yang dipilih sesuai dengan invoice

### Masalah: Tidak Bisa Mengubah Jumlah Alokasi

**Penyebab:**

-   Jumlah melebihi remaining balance
-   Input tidak valid (bukan angka)

**Solusi:**

1. Periksa remaining balance invoice
2. Pastikan input adalah angka yang valid
3. Sistem akan otomatis membatasi ke maksimum yang tersedia

### Masalah: Tombol Save Payment Disabled

**Penyebab:**

-   Belum memilih invoice
-   Total Payment ≠ Total Allocation
-   Validasi belum terpenuhi

**Solusi:**

1. Pilih minimal satu invoice
2. Pastikan Total Payment = Total Allocation
3. Periksa pesan validasi yang muncul
4. Isi semua field yang wajib

### Masalah: Tidak Bisa Post Purchase Payment

**Penyebab:**

-   Purchase Payment sudah di-posting
-   Periode akuntansi sudah ditutup
-   Ada validasi yang gagal

**Solusi:**

1. Periksa status Purchase Payment (harus DRAFT)
2. Periksa periode akuntansi
3. Hubungi administrator untuk membuka periode jika diperlukan
4. Periksa log error untuk detail lebih lanjut

### Masalah: Invoice Tidak Tertutup Setelah Posting

**Penyebab:**

-   Total alokasi < Total invoice
-   Ada pembulatan yang menyebabkan selisih

**Solusi:**

1. Periksa total alokasi vs total invoice
2. Buat Purchase Payment tambahan untuk sisa yang belum dibayar
3. Sistem akan menutup invoice ketika total alokasi ≥ total invoice

### Masalah: Jurnal Tidak Seimbang

**Penyebab:**

-   Konfigurasi akun tidak benar
-   Data tidak konsisten

**Solusi:**

1. Hubungi administrator untuk memeriksa konfigurasi akun
2. Periksa log error untuk detail lebih lanjut
3. Jangan posting jika ada warning tentang jurnal tidak seimbang

---

## Referensi Cepat

### Format Penomoran

**Format**: `EEYYDDNNNNN` (11 karakter)

-   **EE**: Kode Entitas (2 digit)
-   **YY**: Tahun (2 digit)
-   **DD**: `04` untuk Purchase Payment
-   **NNNNN**: Nomor urut (5 digit)

**Contoh**: `71260400001`

### Status Dokumen

| Status | Deskripsi                | Dapat Diedit? |
| ------ | ------------------------ | ------------- |
| DRAFT  | Dokumen baru dibuat      | ✅ Ya         |
| POSTED | Dokumen sudah di-posting | ❌ Tidak      |

### Alur Dokumen

```
Purchase Invoice (Credit) → Purchase Payment → Post → Jurnal Akuntansi
                                      ↓
                              Invoice Closure (jika fully paid)
```

### Akun Akuntansi Default

| Keterangan       | Akun                     | Debit | Credit |
| ---------------- | ------------------------ | ----- | ------ |
| Pembayaran Utang | Utang Dagang (2.1.1.01)  | ✅    |        |
| Pengeluaran Kas  | Kas di Tangan (1.1.1.01) |       | ✅     |

### Shortcut Keyboard

-   **Tab**: Pindah ke field berikutnya
-   **Enter**: Submit form (jika validasi terpenuhi)
-   **Esc**: Batal/tutup dialog

### Field Wajib vs Opsional

| Field             | Wajib? | Keterangan               |
| ----------------- | ------ | ------------------------ |
| Date              | ✅     | Tanggal pembayaran       |
| Company           | ✅     | Entitas perusahaan       |
| Vendor            | ✅     | Vendor yang dibayar      |
| Description       | ❌     | Keterangan tambahan      |
| Invoice Selection | ✅     | Minimal 1 invoice        |
| Allocation Amount | ✅     | Per invoice yang dipilih |
| Payment Account   | ✅     | Akun kas/bank            |
| Payment Amount    | ✅     | Jumlah pembayaran        |

### Tips Cepat

1. **Gunakan Select All** untuk memilih semua invoice sekaligus
2. **Perhatikan warna kuning** pada invoice overdue
3. **Validasi real-time** membantu mencegah kesalahan
4. **Review sebelum posting** untuk memastikan akurasi
5. **Simpan bukti pembayaran** untuk audit trail

---

## FAQ (Frequently Asked Questions)

### Q: Apakah Purchase Payment bisa diedit setelah di-posting?

**A**: Tidak. Purchase Payment yang sudah di-posting tidak dapat diedit. Jika ada kesalahan, hubungi administrator untuk melakukan reversal atau membuat Purchase Payment baru.

### Q: Bagaimana jika saya salah memilih invoice?

**A**: Jika masih DRAFT, Anda dapat menghapus Purchase Payment dan membuat ulang. Jika sudah POSTED, hubungi administrator.

### Q: Apakah bisa membayar invoice sebelum jatuh tempo?

**A**: Ya, bisa. Sistem tidak membatasi pembayaran sebelum jatuh tempo. Anda dapat membayar kapan saja setelah invoice di-posting.

### Q: Bagaimana cara melihat invoice yang sudah dibayar?

**A**: Invoice yang sudah dibayar penuh akan memiliki status **Fully Paid** dan tidak akan muncul lagi di daftar invoice yang tersedia untuk pembayaran.

### Q: Apakah Purchase Payment mendukung multi-mata uang?

**A**: Ya, sistem mendukung multi-mata uang. Pastikan untuk mengatur currency dan exchange rate dengan benar.

### Q: Bagaimana jika ada selisih kecil karena pembulatan?

**A**: Sistem memiliki toleransi ±0.01 untuk pembulatan. Jika selisih lebih besar, periksa kembali perhitungan atau hubungi administrator.

### Q: Apakah bisa membuat Purchase Payment tanpa invoice?

**A**: Tidak. Purchase Payment harus dialokasikan ke minimal satu Purchase Invoice yang sudah di-posting.

### Q: Bagaimana cara melihat riwayat pembayaran untuk suatu invoice?

**A**: Buka detail Purchase Invoice, dan lihat bagian **Payment Allocations** untuk melihat semua pembayaran yang sudah dialokasikan ke invoice tersebut.

---

## Kesimpulan

Modul Purchase Payment adalah alat penting untuk mengelola pembayaran kepada vendor. Dengan fitur alokasi yang fleksibel, validasi real-time, dan integrasi akuntansi otomatis, modul ini membantu memastikan akurasi dan efisiensi dalam proses pembayaran.

**Poin Penting:**

-   ✅ Selalu verifikasi invoice sebelum membayar
-   ✅ Gunakan alokasi yang tepat untuk menghindari kesalahan
-   ✅ Review sebelum posting untuk memastikan akurasi
-   ✅ Simpan dokumentasi untuk audit trail
-   ✅ Hubungi administrator jika menemui masalah

---

**Versi Manual**: 1.0  
**Tanggal Update**: Februari 2026  
**Aplikasi**: Sarang ERP
