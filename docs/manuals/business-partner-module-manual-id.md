# Manual Modul Business Partner

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Memulai](#memulai)
3. [Ringkasan Fitur](#ringkasan-fitur)
4. [Membuat Business Partner](#membuat-business-partner)
5. [Melihat dan Mencari Partner](#melihat-dan-mencari-partner)
6. [Mengedit Business Partner](#mengedit-business-partner)
7. [Mengelola Kontak](#mengelola-kontak)
8. [Mengelola Alamat](#mengelola-alamat)
9. [Pajak & Syarat](#pajak--syarat)
10. [Informasi Perbankan](#informasi-perbankan)
11. [Laporan Rekening (Account Statement)](#laporan-rekening-account-statement)
12. [Tugas Umum](#tugas-umum)
13. [Pemecahan Masalah](#pemecahan-masalah)
14. [Referensi Cepat](#referensi-cepat)
15. [Praktik Terbaik](#praktik-terbaik)

---

## Pengenalan

### Apa itu Modul Business Partner?

Modul Business Partner adalah sistem terpadu yang membantu Anda mengelola semua hubungan bisnis perusahaan di satu tempat. Modul ini menggabungkan pelanggan dan supplier ke dalam satu sistem manajemen komprehensif yang melacak:

- **Dengan siapa Anda berbisnis** (pelanggan dan supplier)
- **Cara menghubungi mereka** (beberapa kontak dan alamat)
- **Syarat bisnis** (syarat pembayaran, batas kredit, informasi pajak)
- **Hubungan keuangan** (pemetaan akun, riwayat transaksi)
- **Detail perbankan** (metode pembayaran dan rekening bank)

### Siapa yang Harus Menggunakan Modul Ini?

- **Tim Penjualan**: Mengelola informasi dan hubungan pelanggan
- **Tim Pembelian**: Mengelola informasi dan hubungan supplier
- **Tim Akuntansi**: Melacak piutang/hutang dagang dan transaksi keuangan
- **Layanan Pelanggan**: Mengakses informasi kontak dan riwayat transaksi
- **Manajer**: Memantau hubungan business partner dan status keuangan

### Manfaat Utama

- **Manajemen Terpadu**: Satu antarmuka untuk pelanggan dan supplier
- **Informasi Lengkap**: Semua data partner di satu tempat dengan organisasi berbasis tab
- **Integrasi Keuangan**: Pemetaan akun otomatis dan laporan rekening GL (Account Statement)
- **Penyimpanan Data Fleksibel**: Field khusus untuk kebutuhan bisnis tertentu
- **Visibilitas Transaksi**: Tab Transactions dan Account statement (masing-masing dengan tujuan berbeda)

---

## Memulai

### Mengakses Modul Business Partner

1. Masuk ke sistem ERP
2. Dari menu utama, klik **"Business Partner"**
3. Anda akan melihat halaman Manajemen Business Partner

### Memahami Layar Utama

Ketika Anda membuka modul Business Partner, Anda akan melihat:

- **Tombol Add Business Partner**: Membuat business partner baru
- **Opsi filter**: Filter berdasarkan tipe (Semua, Customer, Supplier) atau status
- **Kartu statistik**: Total partner, pelanggan, supplier, jumlah aktif/nonaktif
- **Kotak pencarian**: Mencari partner dengan cepat berdasarkan kode, nama, atau nomor registrasi
- **Tabel daftar partner**: Menampilkan semua business partner Anda dengan informasi utama

### Memahami Tipe Partner

Business partner dapat diklasifikasikan sebagai:

- **Customer**: Entitas yang membeli dari perusahaan Anda
- **Supplier**: Entitas yang memasok barang/layanan ke perusahaan Anda

Catatan: Sistem mendukung partner yang dapat menjadi pelanggan dan supplier, tetapi mereka dikelola sebagai catatan terpisah dengan tipe partner yang berbeda.

---

## Ringkasan Fitur

Modul Business Partner mencakup fitur-fitur utama berikut:

### 1. **Manajemen Partner**
- Membuat, mengedit, dan menghapus business partner
- Mengklasifikasikan partner sebagai pelanggan atau supplier
- Menetapkan status partner (aktif, nonaktif, ditangguhkan)
- Melacak nomor registrasi dan ID pajak

### 2. **Manajemen Kontak**
- Beberapa kontak per partner
- Tipe kontak berbeda (primary, billing, shipping, technical, sales, support)
- Informasi kontak lengkap (nama, posisi, email, telepon, mobile)
- Penunjukan kontak utama

### 3. **Manajemen Alamat**
- Beberapa alamat per partner
- Tipe alamat berbeda (billing, shipping, registered, warehouse, office)
- Field alamat lengkap dengan format yang tepat
- Penunjukan alamat utama

### 4. **Pajak & Syarat**
- Informasi registrasi pajak (NPWP/ID Pajak)
- Syarat pembayaran dan batas kredit
- Pemetaan akun (otomatis atau manual)
- Penugasan tingkat harga jual default
- Struktur diskon

### 5. **Informasi Perbankan**
- Detail rekening bank
- Metode pembayaran
- Penyimpanan fleksibel untuk informasi terkait perbankan

### 6. **Integrasi Keuangan**
- Pemetaan akun otomatis (Customer→AR, Supplier→AP)
- Laporan rekening (Account Statement) dengan saldo berjalan pada aktivitas GL yang diposting
- Konsolidasi transaksi dari beberapa sumber
- Dukungan akuntansi multi-dimensi (proyek/departemen)

### 7. **Pelacakan Transaksi**
- Purchase order terkini
- Sales order terkini
- Invoice dan pembayaran terkini
- Riwayat transaksi lengkap

---

## Membuat Business Partner

### Panduan Langkah demi Langkah

1. **Navigasi ke Business Partners**
   - Klik **"Business Partner"** dari menu utama
   - Klik tombol **"Add Business Partner"**

2. **Mengisi Informasi Umum**
   - **Code**: Kode partner unik (wajib, maks 50 karakter)
   - **Name**: Nama partner (wajib, maks 150 karakter)
   - **Partner Type**: Pilih Customer atau Supplier (wajib)
   - **Status**: Pilih Active, Inactive, atau Suspended (default: Active)
   - **Registration Number**: Nomor registrasi bisnis (opsional, maks 30 karakter)
   - **Tax ID**: Nomor identifikasi pajak/NPWP (opsional, maks 50 karakter)
   - **Website**: URL website partner (opsional)
   - **Notes**: Catatan tambahan tentang partner (opsional)

3. **Menambahkan Informasi Kontak** (Opsional)
   - Klik tab **"Contact Details"**
   - Klik tombol **"Add Contact"**
   - Isi informasi kontak:
     - **Contact Type**: Primary, Billing, Shipping, Technical, Sales, atau Support
     - **Name**: Nama kontak (wajib)
     - **Position**: Jabatan/posisi (opsional)
     - **Email**: Alamat email (opsional)
     - **Phone**: Nomor telepon (opsional)
     - **Mobile**: Nomor mobile (opsional)
     - **Is Primary**: Centang jika ini adalah kontak utama
     - **Notes**: Catatan tambahan (opsional)
   - Klik **"Add Contact"** untuk menambahkan kontak lebih banyak

4. **Menambahkan Alamat** (Opsional)
   - Klik tab **"Addresses"**
   - Klik tombol **"Add Address"**
   - Isi informasi alamat:
     - **Address Type**: Billing, Shipping, Registered, Warehouse, atau Office
     - **Address Line 1**: Alamat jalan (wajib)
     - **Address Line 2**: Detail alamat tambahan (opsional)
     - **City**: Nama kota (wajib)
     - **State/Province**: Negara bagian atau provinsi (opsional)
     - **Postal Code**: Kode pos (opsional)
     - **Country**: Nama negara (default: Indonesia)
     - **Is Primary**: Centang jika ini adalah alamat utama
     - **Notes**: Catatan tambahan (opsional)
   - Klik **"Add Address"** untuk menambahkan alamat lebih banyak

5. **Mengonfigurasi Pajak & Syarat** (Opsional)
   - Klik tab **"Taxation & Terms"**
   - **Account**: Pilih akun GL untuk partner ini (opsional)
     - Pelanggan default ke Accounts Receivable
     - Supplier default ke Accounts Payable
   - **Default Sales Price Level**: Pilih tingkat harga (1, 2, atau 3) untuk pelanggan
   - Tambahkan field khusus sesuai kebutuhan untuk syarat pembayaran, batas kredit, dll.

6. **Menambahkan Informasi Perbankan** (Opsional)
   - Klik tab **"Banking"**
   - Tambahkan detail rekening bank dan metode pembayaran menggunakan field khusus

7. **Menyimpan Partner**
   - Klik tombol **"Create Business Partner"**
   - Sistem akan memvalidasi semua field wajib
   - Anda akan diarahkan ke halaman detail partner setelah berhasil

### Catatan Penting

- **Kode harus unik**: Setiap partner harus memiliki kode unik
- **Tipe partner tidak dapat diubah**: Setelah ditetapkan, tipe partner tidak dapat dimodifikasi
- **Pemetaan akun**: Jika tidak ditentukan, sistem menetapkan akun default secara otomatis
- **Kontak/alamat utama**: Hanya satu kontak utama dan satu alamat utama per partner

---

## Melihat dan Mencari Partner

### Melihat Daftar Partner

Halaman indeks Business Partner menampilkan:

- **Partner Code**: Pengidentifikasi unik
- **Partner Name**: Nama lengkap
- **Type Badge**: Customer (biru) atau Supplier (kuning)
- **Status Badge**: Active (hijau), Inactive (abu-abu), atau Suspended (merah)
- **Primary Contact**: Informasi kontak utama
- **Primary Address**: Informasi alamat utama
- **Actions**: Tombol View, Edit, Delete

### Memfilter Partner

Anda dapat memfilter partner berdasarkan:

- **Type**: Semua, Customer, atau Supplier
- **Status**: Active, Inactive, atau Suspended (melalui pencarian)

### Mencari Partner

Gunakan kotak pencarian untuk menemukan partner berdasarkan:

- Kode partner
- Nama partner
- Nomor registrasi

Pencarian tidak peka huruf besar/kecil dan mencari di ketiga field tersebut.

### Melihat Detail Partner

1. Klik tombol **"View"** partner atau klik nama partner
2. Halaman detail menampilkan semua informasi partner yang diorganisir dalam tab:
   - **General Information**: Data partner dasar
   - **Contact Details**: Semua kontak
   - **Addresses**: Semua alamat
   - **Taxation & Terms**: Info pajak, pemetaan akun, syarat pembayaran
   - **Banking**: Detail rekening bank
   - **Transactions**: Order, invoice, dan pembayaran terkini
   - **Account statement**: Aktivitas GL yang diposting pada akun dagang (bisa berbeda dari tab Transactions)

---

## Mengedit Business Partner

### Cara Mengedit Partner

1. **Navigasi ke Detail Partner**
   - Buka daftar Business Partners
   - Klik **"View"** atau nama partner
   - Klik tombol **"Edit"** di header

2. **Memodifikasi Informasi**
   - Perbarui field apa pun di tab General Information
   - Modifikasi kontak di tab Contact Details
   - Perbarui alamat di tab Addresses
   - Ubah pajak & syarat di tab Taxation & Terms
   - Perbarui informasi perbankan di tab Banking

3. **Menyimpan Perubahan**
   - Klik tombol **"Update Business Partner"**
   - Perubahan disimpan segera
   - Anda akan diarahkan ke halaman detail partner

### Pembatasan Pengeditan

- **Partner Code**: Dapat diubah tetapi harus tetap unik
- **Partner Type**: Tidak dapat diubah setelah pembuatan
- **Contacts/Addresses**: Dapat ditambahkan, dimodifikasi, atau dihapus
- **Details**: Field khusus dapat ditambahkan atau dimodifikasi

### Pembaruan Massal

Saat ini, pembaruan massal tidak tersedia. Setiap partner harus diedit secara individual.

---

## Mengelola Kontak

### Menambahkan Kontak

1. Buka halaman detail atau edit partner
2. Buka tab **"Contact Details"**
3. Klik tombol **"Add Contact"**
4. Isi informasi kontak
5. Centang **"Is Primary"** jika ini adalah kontak utama
6. Klik **"Save"** atau **"Add Contact"** untuk menambahkan lebih banyak

### Tipe Kontak

- **Primary**: Kontak utama untuk partner
- **Billing**: Kontak untuk masalah penagihan dan faktur
- **Shipping**: Kontak untuk masalah pengiriman dan pengiriman
- **Technical**: Kontak dukungan teknis
- **Sales**: Kontak terkait penjualan
- **Support**: Kontak dukungan pelanggan/supplier

### Mengedit Kontak

1. Buka tab **"Contact Details"**
2. Klik **"Edit"** pada kontak yang ingin dimodifikasi
3. Perbarui informasi
4. Klik **"Update"** untuk menyimpan perubahan

### Menghapus Kontak

1. Buka tab **"Contact Details"**
2. Klik **"Delete"** pada kontak yang ingin dihapus
3. Konfirmasi penghapusan di kotak dialog

### Kontak Utama

- Hanya satu kontak yang dapat ditandai sebagai utama
- Kontak utama ditampilkan di daftar partner
- Mengubah kontak utama secara otomatis menghapus penandaan utama sebelumnya

---

## Mengelola Alamat

### Menambahkan Alamat

1. Buka halaman detail atau edit partner
2. Buka tab **"Addresses"**
3. Klik tombol **"Add Address"**
4. Isi informasi alamat
5. Centang **"Is Primary"** jika ini adalah alamat utama
6. Klik **"Save"** atau **"Add Address"** untuk menambahkan lebih banyak

### Tipe Alamat

- **Billing**: Alamat untuk faktur dan penagihan
- **Shipping**: Alamat untuk pengiriman dan pengiriman
- **Registered**: Alamat kantor terdaftar resmi
- **Warehouse**: Lokasi gudang atau penyimpanan
- **Office**: Alamat kantor umum

### Mengedit Alamat

1. Buka tab **"Addresses"**
2. Klik **"Edit"** pada alamat yang ingin dimodifikasi
3. Perbarui informasi
4. Klik **"Update"** untuk menyimpan perubahan

### Menghapus Alamat

1. Buka tab **"Addresses"**
2. Klik **"Delete"** pada alamat yang ingin dihapus
3. Konfirmasi penghapusan di kotak dialog

### Alamat Utama

- Hanya satu alamat yang dapat ditandai sebagai utama
- Alamat utama ditampilkan di daftar partner
- Mengubah alamat utama secara otomatis menghapus penandaan utama sebelumnya

---

## Pajak & Syarat

### Pemetaan Akun

Business partner dapat ditugaskan ke akun GL tertentu:

- **Customers**: Default ke akun Accounts Receivable (AR)
- **Suppliers**: Default ke akun Accounts Payable (AP)

**Untuk menugaskan akun:**

1. Buka halaman detail atau edit partner
2. Klik tab **"Taxation & Terms"**
3. Temukan bagian **"Accounting"**
4. Pilih akun dari dropdown **"Account"**
5. Simpan perubahan

**Penugasan Akun Otomatis:**

Jika tidak ada akun yang ditentukan, sistem secara otomatis menetapkan:
- Customers → Akun AR pertama yang tersedia (kode dimulai dengan 1100%)
- Suppliers → Akun AP pertama yang tersedia (kode dimulai dengan 2100%)

### Default Sales Price Level

Untuk pelanggan, Anda dapat menetapkan tingkat harga jual default:

- **Level 1**: Harga standar
- **Level 2**: Harga pelanggan preferensi
- **Level 3**: Harga pelanggan VIP

Default ini digunakan saat membuat sales order jika tidak ada tingkat harga spesifik yang dipilih.

### Syarat Pembayaran

Syarat pembayaran dapat disimpan sebagai field khusus di bagian Taxation & Terms:

- Hari jatuh tempo pembayaran (misalnya, Net 30, Net 60)
- Diskon pembayaran awal
- Penalti pembayaran terlambat

### Batas Kredit

Batas kredit dapat dikelola melalui field khusus:

- Jumlah kredit maksimum
- Periode kredit
- Persyaratan persetujuan kredit

### Informasi Pajak

- **Tax ID (NPWP)**: Nomor identifikasi pajak Indonesia
- **Registration Number**: Nomor registrasi bisnis
- Informasi terkait pajak tambahan dapat disimpan sebagai field khusus

---

## Informasi Perbankan

### Menambahkan Detail Perbankan

1. Buka halaman detail atau edit partner
2. Buka tab **"Banking"**
3. Tambahkan informasi perbankan menggunakan field khusus:
   - Nama bank
   - Nomor rekening
   - Nama pemegang rekening
   - Cabang bank
   - Kode SWIFT
   - Metode pembayaran

### Metode Pembayaran

Metode pembayaran umum yang dapat dikonfigurasi:

- Transfer bank
- Tunai
- Cek
- Kartu kredit
- Letter of Credit (L/C)
- Metode lain

### Beberapa Rekening Bank

Partner dapat memiliki beberapa rekening bank. Tambahkan setiap rekening sebagai entri terpisah di bagian Banking.

---

## Laporan Rekening (Account Statement)

### Apa itu tab Account statement?

Tab **Account statement** menampilkan aktivitas **jurnal yang sudah diposting** ke **buku besar (GL)** untuk partner tersebut, dengan fokus pada akun **piutang/hutang dagang** (akun kontrol sesuai pengaturan sistem) dan baris jurnal yang terhubung ke dokumen partner (faktur, pembayaran, penerimaan, dan sejenisnya). Tab ini **bukan** salinan tab **Transactions**.

Di bagian atas tab terdapat petunjuk:

> Menampilkan aktivitas GL yang diposting pada akun dagang; dapat berbeda dari tab Transactions.

Tanggal pada tabel (posting dan dokumen) ditampilkan dalam format **dd/mm/yyyy**. Ekspor **CSV** dan **PDF** memakai format tanggal yang konsisten.

### Mengakses Account statement

1. Buka halaman detail partner
2. Klik tab **Account statement**
3. Atur **Start Date** dan **End Date** (default umumnya tahun berjalan)
4. Klik **Filter** atau **Refresh** untuk memuat ulang data
5. Gunakan **CSV** atau **PDF** untuk mengunduh laporan periode yang dipilih

### Memahami ringkasan dan tabel

**Kartu ringkasan:**
- **Opening Balance**: Saldo di awal rentang tanggal (berdasarkan baris jurnal yang masuk ruang lingkup statement)
- **Total Debits** / **Total Credits**: Jumlah debit/kredit dalam periode
- **Closing Balance**: Saldo akhir periode

**Kolom tabel (utama):**
- **Posting Date** / **Document Date**: Tanggal posting jurnal dan tanggal dokumen (tampilan **dd/mm/yyyy**)
- **Document Type**, **Document No.**, **Journal No.**, **Description**
- **Debit** / **Credit** / **Balance** (saldo berjalan)
- **Posted By**: Pengguna terkait posting (sesuai data jurnal)

### Perbedaan dengan tab Transactions

Tab **Transactions** menampilkan aktivitas operasional (order, faktur, pembayaran, dll.) sesuai tampilan modul. Tab **Account statement** hanya mencakup baris GL yang **sudah diposting** dan memenuhi aturan akun/sumber jurnal untuk partner ini. Karena itu, **bisa ada** transaksi di tab Transactions **tanpa** baris di Account statement (misalnya belum diposting ke GL sesuai ruang lingkup), atau sebaliknya tergantung alur akuntansi.

### Filter dan pagination

- **Rentang tanggal**: Membatasi periode saldo pembuka, total, dan baris tabel
- **Pagination**: Baris ditampilkan per halaman (misalnya 25 entri per halaman)

### Mengekspor (CSV dan PDF)

Gunakan tombol **CSV** atau **PDF** pada tab untuk mengunduh laporan **periode yang sama** dengan tanggal mulai/akhir yang dipilih di filter.

---

## Tugas Umum

### Tugas: Membuat Pelanggan Baru

1. Buka Business Partners → Add Business Partner
2. Masukkan kode (misalnya, "CUST001")
3. Masukkan nama (misalnya, "PT Maju Bersama")
4. Pilih "Customer" sebagai tipe partner
5. Tetapkan status ke "Active"
6. Tambahkan setidaknya satu kontak (kontak utama direkomendasikan)
7. Tambahkan setidaknya satu alamat (alamat utama direkomendasikan)
8. Tambahkan Tax ID jika tersedia
9. Klik "Create Business Partner"

### Tugas: Membuat Supplier Baru

1. Buka Business Partners → Add Business Partner
2. Masukkan kode (misalnya, "SUPP001")
3. Masukkan nama (misalnya, "PT Makmur Jaya")
4. Pilih "Supplier" sebagai tipe partner
5. Tetapkan status ke "Active"
6. Tambahkan informasi kontak dan alamat
7. Tambahkan detail perbankan untuk pemrosesan pembayaran
8. Klik "Create Business Partner"

### Tugas: Memperbarui Informasi Kontak Partner

1. Buka Business Partners → Temukan dan buka partner
2. Klik tombol "Edit"
3. Buka tab "Contact Details"
4. Klik "Edit" pada kontak yang ingin dimodifikasi
5. Perbarui telepon, email, atau informasi lain
6. Klik "Update" untuk menyimpan
7. Klik "Update Business Partner" untuk menyelesaikan

### Tugas: Menambahkan Alamat Baru

1. Buka Business Partners → Temukan dan buka partner
2. Klik tombol "Edit"
3. Buka tab "Addresses"
4. Klik "Add Address"
5. Pilih tipe alamat (misalnya, "Shipping")
6. Masukkan detail alamat
7. Klik "Save"
8. Klik "Update Business Partner" untuk menyelesaikan

### Tugas: Menugaskan Akun ke Partner

1. Buka Business Partners → Temukan dan buka partner
2. Klik tombol "Edit"
3. Buka tab "Taxation & Terms"
4. Temukan bagian "Accounting"
5. Pilih akun dari dropdown
6. Klik "Update Business Partner"

### Tugas: Melihat Riwayat Transaksi Partner

1. Buka Business Partners → Temukan dan buka partner
2. Klik tab "Transactions"
3. Lihat purchase order, sales order, invoice, dan pembayaran terkini
4. Klik tab **Account statement** untuk aktivitas GL yang diposting (bukan daftar yang sama persis dengan Transactions)

### Tugas: Menonaktifkan Partner

1. Buka Business Partners → Temukan dan buka partner
2. Klik tombol "Edit"
3. Ubah status dari "Active" ke "Inactive"
4. Klik "Update Business Partner"
5. Partner tidak akan lagi muncul di daftar partner aktif

### Tugas: Mencari Partner

1. Buka Business Partners
2. Gunakan kotak pencarian di bagian atas
3. Ketik kode partner, nama, atau nomor registrasi
4. Hasil akan difilter secara otomatis saat Anda mengetik

### Tugas: Memfilter Partner berdasarkan Tipe

1. Buka Business Partners
2. Gunakan dropdown filter di bagian atas
3. Pilih "Customer" atau "Supplier"
4. Daftar diperbarui untuk menampilkan hanya tipe yang dipilih

---

## Pemecahan Masalah

### Masalah: Tidak Dapat Membuat Partner - Kode Sudah Ada

**Kemungkinan Penyebab:**
- Kode partner tidak unik
- Kode digunakan oleh partner yang dihapus

**Solusi:**
1. Gunakan kode yang berbeda
2. Periksa apakah partner sebelumnya dihapus
3. Hubungi administrator untuk memeriksa ketersediaan kode

### Masalah: Tidak Dapat Menemukan Partner

**Kemungkinan Penyebab:**
- Partner tidak aktif atau ditangguhkan
- Istilah pencarian salah
- Partner dihapus

**Solusi:**
1. Periksa pengaturan filter (tampilkan partner tidak aktif)
2. Coba istilah pencarian yang berbeda
3. Cari berdasarkan nomor registrasi
4. Hubungi administrator jika partner seharusnya ada

### Masalah: Akun Tidak Muncul di Dropdown

**Kemungkinan Penyebab:**
- Akun tidak ada
- Akun tidak aktif
- Tipe akun salah

**Solusi:**
1. Verifikasi akun ada di Chart of Accounts
2. Periksa akun aktif
3. Pastikan tipe akun sesuai dengan tipe partner (AR untuk pelanggan, AP untuk supplier)
4. Hubungi administrator untuk membuat akun

### Masalah: Account Statement Kosong Padahal Tab Transactions Ada Data

**Kemungkinan penyebab:**
- Baris jurnal untuk partner diposting ke **akun kontrol** (hutang/piutang dagang) dan/atau memerlukan **jurnal terhubung dokumen**; aktivitas di Transactions belum tentu membentuk baris GL dalam ruang lingkup statement
- Rentang tanggal filter tidak mencakup tanggal posting jurnal
- Dokumen belum diposting ke GL

**Solusi:**
1. Periksa rentang tanggal filter
2. Konfirmasi dokumen relevan **sudah diposting** ke jurnal umum
3. Ingat: tab **Transactions** dan **Account statement** sumber datanya **tidak identik** — baca petunjuk di atas tab Account statement

### Masalah: Tidak Dapat Menghapus Partner

**Kemungkinan Penyebab:**
- Partner memiliki riwayat transaksi
- Partner direferensikan di modul lain

**Solusi:**
- Partner dengan transaksi tidak dapat dihapus (dengan desain)
- Nonaktifkan partner sebagai gantinya (ubah status ke Inactive)
- Hubungi administrator jika penghapusan benar-benar diperlukan

### Masalah: Kontak/Alamat Utama Tidak Tersimpan

**Kemungkinan Penyebab:**
- Beberapa kontak/alamat utama dipilih
- Kesalahan validasi form

**Solusi:**
1. Pastikan hanya satu kontak utama dan satu alamat utama
2. Periksa semua field wajib diisi
3. Coba simpan lagi
4. Hubungi administrator jika masalah berlanjut

---

## Referensi Cepat

### Pintasan Keyboard

- **Ctrl + F**: Pencarian (di sebagian besar browser)
- **Enter**: Kirim form
- **Esc**: Tutup modal
- **Tab**: Navigasi antar field form

### Istilah Penting

- **Business Partner**: Istilah terpadu untuk pelanggan dan supplier
- **Partner Type**: Klasifikasi sebagai Customer atau Supplier
- **Primary Contact**: Kontak utama untuk partner
- **Primary Address**: Alamat utama untuk partner
- **Account Mapping**: Penugasan akun GL untuk pelacakan keuangan
- **Account statement**: Aktivitas GL terposting pada akun dagang (dapat berbeda dari Transactions)
- **Running Balance**: Saldo kumulatif setelah setiap transaksi

### Warna Status Partner

- 🟢 **Hijau (Active)**: Partner aktif dan dapat digunakan dalam transaksi
- ⚪ **Abu-abu (Inactive)**: Partner tidak aktif dan disembunyikan dari daftar aktif
- 🔴 **Merah (Suspended)**: Partner ditangguhkan dan tidak dapat digunakan

### Badge Tipe Partner

- 🔵 **Biru (Customer)**: Partner adalah pelanggan
- 🟡 **Kuning (Supplier)**: Partner adalah supplier

### Tipe Kontak

- **Primary**: Kontak utama
- **Billing**: Kontak penagihan dan faktur
- **Shipping**: Kontak pengiriman dan pengiriman
- **Technical**: Kontak dukungan teknis
- **Sales**: Kontak terkait penjualan
- **Support**: Kontak dukungan pelanggan/supplier

### Tipe Alamat

- **Billing**: Alamat faktur dan penagihan
- **Shipping**: Alamat pengiriman dan pengiriman
- **Registered**: Kantor terdaftar resmi
- **Warehouse**: Lokasi gudang atau penyimpanan
- **Office**: Alamat kantor umum

---

## Mendapatkan Bantuan

Jika Anda memerlukan bantuan tambahan:

1. **Periksa manual ini** terlebih dahulu untuk tugas umum
2. **Hubungi administrator sistem Anda** untuk masalah teknis
3. **Tinjau materi pelatihan** jika tersedia
4. **Periksa audit trail** untuk melihat apa yang berubah dan kapan
5. **Konsultasikan dengan tim akuntansi** untuk pertanyaan pemetaan akun

---

## Praktik Terbaik

### Saat Membuat Partner

- ✅ Gunakan konvensi pengkodean yang jelas dan konsisten (misalnya, CUST001, SUPP001)
- ✅ Masukkan informasi kontak lengkap dari awal
- ✅ Tambahkan setidaknya satu kontak utama dan alamat utama
- ✅ Sertakan Tax ID (NPWP) untuk partner Indonesia
- ✅ Tugaskan akun yang sesuai untuk pelacakan keuangan
- ✅ Tetapkan tipe partner yang benar (Customer vs Supplier)

### Saat Mengelola Kontak

- ✅ Selalu tentukan kontak utama
- ✅ Jaga informasi kontak tetap up to date
- ✅ Tambahkan beberapa kontak untuk tujuan berbeda (billing, shipping, dll.)
- ✅ Sertakan email dan telepon untuk semua kontak penting
- ✅ Dokumentasikan perubahan kontak dalam catatan

### Saat Mengelola Alamat

- ✅ Selalu tentukan alamat utama
- ✅ Gunakan tipe alamat yang benar (billing, shipping, dll.)
- ✅ Sertakan informasi alamat lengkap
- ✅ Jaga alamat tetap terkini
- ✅ Tambahkan beberapa alamat ketika partner memiliki beberapa lokasi

### Saat Menyiapkan Informasi Keuangan

- ✅ Tugaskan akun GL yang benar (AR untuk pelanggan, AP untuk supplier)
- ✅ Tetapkan batas kredit yang sesuai untuk pelanggan
- ✅ Konfigurasi syarat pembayaran secara akurat
- ✅ Tinjau pemetaan akun secara berkala
- ✅ Pastikan tab Account statement dapat diakses bila diperlukan rekonsiliasi GL

### Saat Mengelola Status Partner

- ✅ Jaga partner aktif tetap terkini
- ✅ Nonaktifkan daripada menghapus jika memungkinkan
- ✅ Gunakan status ditangguhkan untuk pembatasan sementara
- ✅ Tinjau partner tidak aktif secara berkala
- ✅ Dokumentasikan alasan perubahan status

### Tips Umum

- ✅ Selalu verifikasi informasi sebelum menyimpan
- ✅ Gunakan catatan untuk mendokumentasikan perubahan penting
- ✅ Tinjau informasi partner secara teratur
- ✅ Jaga informasi kontak dan alamat tetap terkini
- ✅ Bandingkan Transactions dengan Account statement bila perlu memahami perbedaan operasional vs GL
- ✅ Gunakan konvensi penamaan dan pengkodean yang konsisten
- ✅ Dokumentasikan syarat atau perjanjian khusus dalam catatan

---

**Akhir Manual**

*Manual ini mencakup fitur dasar Modul Business Partner. Untuk fitur lanjutan atau proses bisnis khusus, konsultasikan dengan administrator sistem Anda atau lihat dokumentasi tambahan.*

