# Panduan Pengguna Fitur Multi-Measures dan Multi-Price

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Memulai](#memulai)
3. [Memahami Satuan Ukuran](#memahami-satuan-ukuran)
4. [Mengelola Satuan Ukuran](#mengelola-satuan-ukuran)
5. [Mengatur Beberapa Satuan untuk Item](#mengatur-beberapa-satuan-untuk-item)
6. [Memahami Level Harga](#memahami-level-harga)
7. [Mengatur Level Harga](#mengatur-level-harga)
8. [Harga Khusus Pelanggan](#harga-khusus-pelanggan)
9. [Menggunakan Multi-Measures dalam Pesanan](#menggunakan-multi-measures-dalam-pesanan)
10. [Skenario Umum](#skenario-umum)
11. [Pemecahan Masalah](#pemecahan-masalah)
12. [Referensi Cepat](#referensi-cepat)

---

## Pengenalan

### Apa itu Fitur Multi-Measures dan Multi-Price?

Sistem Sarang-ERP mencakup dua fitur yang membantu Anda mengelola inventori dan harga dengan lebih efektif:

1. **Multi-Measures**: Memungkinkan Anda mendefinisikan beberapa satuan ukuran untuk item yang sama (misalnya, "Buah", "Kotak", "Karton") dengan konversi otomatis antar satuan.

2. **Multi-Price**: Memungkinkan Anda menetapkan harga berbeda untuk item yang sama berdasarkan:
   - Level harga (Level 1, 2, 3 untuk tier pelanggan berbeda)
   - Satuan berbeda (harga berbeda untuk "Buah" vs "Kotak")
   - Pelanggan spesifik (harga khusus untuk pelanggan individual)

### Siapa yang Harus Menggunakan Fitur Ini?

- **Manajer Inventori**: Mengatur beberapa satuan dan faktor konversi
- **Manajer Penjualan**: Mengonfigurasi level harga dan harga khusus pelanggan
- **Staf Penjualan**: Menggunakan satuan dan harga berbeda saat membuat pesanan
- **Tim Pembelian**: Memesan item dalam satuan berbeda (misalnya, pesan per karton, terima per buah)
- **Akuntan**: Melacak inventori dalam satuan dasar sambil memungkinkan harga fleksibel

### Manfaat Utama

- **Fleksibilitas**: Menjual item dalam satuan berbeda (buah, kotak, karton) tanpa perhitungan manual
- **Harga Akurat**: Menetapkan harga berbeda untuk tipe pelanggan berbeda secara otomatis
- **Diskon Volume**: Menawarkan harga lebih baik untuk kuantitas lebih besar (kotak vs buah)
- **Operasi Sederhana**: Sistem secara otomatis mengonversi kuantitas dan menerapkan harga yang benar
- **Kontrol Inventori Lebih Baik**: Stok selalu dilacak dalam satuan dasar, terlepas dari bagaimana item dijual

---

## Memulai

### Prasyarat

Sebelum menggunakan fitur ini, pastikan:

1. Anda memiliki izin untuk:
   - `view_unit_of_measure` - Melihat satuan ukuran
   - `create_unit_of_measure` - Membuat satuan baru
   - `inventory.update` - Mengedit item inventori
   - `business_partners.update` - Menetapkan harga pelanggan

2. Satuan dasar sudah dibuat (EA, KG, M, dll.)

3. Item inventori sudah disetup di sistem

### Titik Akses

- **Manajemen Satuan Ukuran**: `Master Data > Units of Measure`
- **Setup Satuan Item**: `Inventory > Inventory Items > [Pilih Item] > Manage Units`
- **Setup Level Harga**: `Inventory > Inventory Items > [Pilih Item] > Edit`
- **Harga Pelanggan**: `Business Partner > [Pilih Pelanggan] > Edit`

---

## Memahami Satuan Ukuran

### Apa itu Satuan Ukuran?

Satuan ukuran (UOM) adalah cara Anda menghitung atau mengukur suatu item. Contoh umum:
- **Satuan hitung**: Buah (EA), Potong (PC), Kotak (BOX), Karton (CTN)
- **Satuan berat**: Kilogram (KG), Gram (GR), Ton (TON)
- **Satuan panjang**: Meter (M), Sentimeter (CM), Kaki (FT)
- **Satuan volume**: Liter (L), Mililiter (ML), Galon (GAL)

### Satuan Dasar vs Satuan Alternatif

Setiap item inventori memiliki **satu satuan dasar** dan dapat memiliki **beberapa satuan alternatif**:

- **Satuan Dasar**: Satuan utama yang digunakan untuk pelacakan stok
  - Contoh: "EA" (Buah) - Stok selalu dilacak dalam buah
  - Ini adalah satuan yang ditampilkan dalam laporan inventori

- **Satuan Alternatif**: Satuan tambahan yang dapat Anda gunakan dalam pesanan
  - Contoh: "BOX" (12 EA), "CARTON" (144 EA)
  - Ini dikonversi ke satuan dasar untuk pelacakan stok

### Faktor Konversi

Saat Anda mendefinisikan satuan alternatif, Anda menetapkan **faktor konversi** yang memberi tahu sistem berapa banyak satuan dasar sama dengan satu satuan alternatif.

**Contoh:**
- Satuan Dasar: EA (Buah)
- Satuan Alternatif: BOX
- Faktor Konversi: 12
- Artinya: 1 BOX = 12 EA

Ketika seseorang memesan 2 BOX, sistem:
- Mencatat pesanan sebagai: 2 BOX
- Mengurangi stok sebagai: 24 EA (2 × 12)

---

## Mengelola Satuan Ukuran

### Melihat Semua Satuan

1. Navigasi ke `Master Data > Units of Measure`
2. Anda akan melihat semua satuan dikelompokkan berdasarkan tipe (Count, Weight, Length, dll.)
3. Setiap satuan menampilkan:
   - Kode (misalnya, EA, BOX)
   - Nama (misalnya, Each, Box)
   - Deskripsi
   - Apakah ini satuan dasar
   - Status (Aktif/Nonaktif)
   - Jumlah konversi

### Membuat Satuan Baru

1. Pergi ke `Master Data > Units of Measure`
2. Klik tombol **"Add Unit"**
3. Isi formulir:
   - **Unit Code**: Kode singkat (misalnya, BOX, CTN) - maks 20 karakter
   - **Unit Name**: Nama lengkap (misalnya, Box, Carton) - maks 100 karakter
   - **Description**: Deskripsi opsional
   - **Unit Type**: Pilih dari Count, Weight, Length, Volume, Area, atau Time
   - **Base Unit**: Centang jika ini harus menjadi satuan dasar (biasanya biarkan tidak dicentang)
4. Klik **"Create Unit"**

**Contoh:**
- Code: BOX
- Name: Box
- Description: Standard shipping box
- Unit Type: Count
- Base Unit: Tidak (tidak dicentang)

### Mengedit Satuan

1. Pergi ke `Master Data > Units of Measure`
2. Temukan satuan yang ingin Anda edit
3. Klik tombol **Edit** (ikon pensil)
4. Ubah field (catatan: Unit Type tidak dapat diubah setelah dibuat)
5. Klik **"Update Unit"**

### Melihat Detail Satuan

1. Pergi ke `Master Data > Units of Measure`
2. Klik tombol **View** (ikon mata) untuk satuan apa pun
3. Anda akan melihat:
   - Informasi satuan
   - Hubungan konversi (jika ada)

---

## Mengatur Beberapa Satuan untuk Item

### Menambahkan Satuan ke Item Inventori

1. Pergi ke `Inventory > Inventory Items`
2. Temukan dan klik pada item yang ingin Anda konfigurasi
3. Klik pada **"Manage Units"** atau navigasi ke tab Units
4. Klik **"Add Unit"**
5. Pilih satuan dari dropdown
6. Tetapkan faktor konversi:
   - Jika ini satuan dasar: Tetapkan ke 1
   - Jika ini alternatif: Masukkan berapa banyak satuan dasar = 1 dari satuan ini
7. Tetapkan harga untuk satuan ini (opsional):
   - Harga Level 1
   - Harga Level 2
   - Harga Level 3
8. Klik **"Save"**

### Contoh: Mengatur Kotak dan Karton untuk Item

**Item**: Lampu Sorot 1000 watt AC
**Satuan Dasar**: EA (Buah)

**Langkah 1: Tambahkan satuan BOX**
- Unit: BOX
- Faktor Konversi: 12 (1 BOX = 12 EA)
- Harga Level 1: 5,500,000 (opsional - bisa berbeda dari 12 × harga EA)
- Tandai sebagai satuan dasar: Tidak

**Langkah 2: Tambahkan satuan CARTON**
- Unit: CARTON
- Faktor Konversi: 144 (1 CARTON = 144 EA = 12 BOX)
- Harga Level 1: 60,000,000 (opsional)
- Tandai sebagai satuan dasar: Tidak

**Hasil:**
- Stok dilacak dalam EA (satuan dasar)
- Pesanan dapat menggunakan EA, BOX, atau CARTON
- Sistem secara otomatis mengonversi kuantitas
- Setiap satuan dapat memiliki harganya sendiri

### Menetapkan Satuan Dasar

- Satuan pertama yang Anda tambahkan secara otomatis ditetapkan sebagai satuan dasar
- Hanya satu satuan per item yang dapat menjadi satuan dasar
- Satuan dasar tidak dapat dihapus jika item memiliki transaksi
- Untuk mengubah satuan dasar, Anda harus terlebih dahulu menghapus semua satuan alternatif, lalu menetapkan satuan dasar baru

### Menghapus Satuan

1. Pergi ke halaman manajemen satuan item
2. Temukan satuan yang ingin Anda hapus
3. Klik **"Remove"** atau **"Delete"**
4. Konfirmasi tindakan

**Catatan**: Anda tidak dapat menghapus satuan dasar jika:
- Item memiliki transaksi apa pun
- Ini adalah satu-satunya satuan untuk item

---

## Memahami Level Harga

### Apa itu Level Harga?

Level harga memungkinkan Anda menetapkan harga jual berbeda untuk item yang sama, biasanya digunakan untuk tier pelanggan berbeda:

- **Level 1**: Harga standar/eceran (tertinggi)
  - Digunakan untuk pelanggan reguler
  - Level harga default

- **Level 2**: Harga grosir (sedang)
  - Digunakan untuk pelanggan grosir
  - Biasanya 5-15% lebih rendah dari Level 1

- **Level 3**: Harga distributor (terendah)
  - Digunakan untuk distributor atau pelanggan volume besar
  - Biasanya 10-25% lebih rendah dari Level 1

### Cara Kerja Level Harga

Saat membuat pesanan penjualan:
1. Sistem memeriksa level harga default pelanggan
2. Sistem mencari harga item untuk level tersebut
3. Jika item menggunakan satuan spesifik (misalnya, BOX), sistem menggunakan harga satuan tersebut untuk level tersebut
4. Harga secara otomatis diterapkan ke pesanan

### Prioritas Resolusi Harga

Sistem menggunakan harga dalam urutan ini (prioritas tertinggi terlebih dahulu):

1. **Harga khusus pelanggan** (jika ditetapkan untuk kombinasi pelanggan-item ini)
2. **Level harga khusus pelanggan** (jika ditetapkan untuk kombinasi pelanggan-item ini)
3. **Level harga default pelanggan** (ditetapkan dalam master data pelanggan)
4. **Harga spesifik satuan** (jika pesanan menggunakan satuan spesifik seperti BOX)
5. **Harga dasar item** (untuk level harga)
6. **Harga terhitung** (jika persentase markup/diskon ditetapkan)

---

## Mengatur Level Harga

### Menetapkan Harga Dasar untuk Item

1. Pergi ke `Inventory > Inventory Items`
2. Temukan dan klik pada item
3. Klik **"Edit"**
4. Scroll ke bagian **Pricing**
5. Tetapkan harga:
   - **Selling Price**: Harga Level 1 (satuan dasar)
   - **Selling Price Level 2**: Harga grosir (satuan dasar)
   - **Selling Price Level 3**: Harga distributor (satuan dasar)
6. Opsional menetapkan perhitungan persentase:
   - **Price Level 2 Percentage**: Otomatis menghitung Level 2 sebagai persentase dari Level 1
   - **Price Level 3 Percentage**: Otomatis menghitung Level 3 sebagai persentase dari Level 1
7. Klik **"Update"**

### Contoh: Menetapkan Level Harga

**Item**: Lampu Sorot 1000 watt AC
- Selling Price (Level 1): 500,000 per EA
- Selling Price Level 2: 450,000 per EA (diskon 10%)
- Selling Price Level 3: 400,000 per EA (diskon 20%)

**Atau menggunakan persentase:**
- Selling Price (Level 1): 500,000 per EA
- Price Level 2 Percentage: -10% (secara otomatis menghitung ke 450,000)
- Price Level 3 Percentage: -20% (secara otomatis menghitung ke 400,000)

### Menetapkan Harga Spesifik Satuan

Ketika Anda menambahkan satuan alternatif ke item, Anda dapat menetapkan harga berbeda untuk setiap satuan di setiap level:

1. Pergi ke halaman manajemen satuan item
2. Tambahkan atau edit satuan
3. Tetapkan harga:
   - **Selling Price**: Harga Level 1 untuk satuan ini
   - **Selling Price Level 2**: Harga Level 2 untuk satuan ini
   - **Selling Price Level 3**: Harga Level 3 untuk satuan ini

**Contoh:**
- Satuan Dasar (EA): Level 1 = 500,000
- Satuan Alternatif (BOX): Level 1 = 5,500,000 (bukan 6,000,000 - termasuk diskon volume)

**Mengapa harga berbeda?**
- Diskon volume: Harga BOX mungkin kurang dari 12 × harga EA
- Biaya kemasan: Harga BOX mungkin termasuk kemasan
- Strategi pemasaran: Mendorong pembelian grosir

---

## Harga Khusus Pelanggan

### Menetapkan Level Harga Default Pelanggan

1. Pergi ke `Business Partner > Business Partners`
2. Temukan dan klik pada pelanggan
3. Klik **"Edit"**
4. Temukan field **"Default Sales Price Level"**
5. Pilih: 1 (Standard), 2 (Wholesale), atau 3 (Distributor)
6. Klik **"Update"**

**Hasil**: Semua pesanan untuk pelanggan ini akan menggunakan level harga yang dipilih secara default.

### Menetapkan Harga Kustom untuk Item Spesifik

Anda dapat menimpa harga standar untuk kombinasi pelanggan-item spesifik:

1. Pergi ke `Business Partner > Business Partners`
2. Temukan dan klik pada pelanggan
3. Navigasi ke bagian **"Item Pricing"** atau **"Special Pricing"**
4. Klik **"Add Item Price"** atau **"Set Custom Price"**
5. Pilih item inventori
6. Pilih:
   - **Use Price Level**: Pilih level 1, 2, atau 3
   - **Custom Price**: Masukkan harga spesifik (menimpa semua level)
7. Klik **"Save"**

**Contoh:**
- Pelanggan: PT ABC (level default: 2)
- Item: Lampu Sorot
- Harga Kustom: 480,000 per EA
- Hasil: PT ABC selalu membayar 480,000 untuk item ini, terlepas dari level

---

## Menggunakan Multi-Measures dalam Pesanan

### Membuat Pesanan Penjualan dengan Satuan Berbeda

1. Pergi ke `Sales > Sales Orders > Create New`
2. Pilih pelanggan
3. Tambahkan item:
   - Pilih item inventori
   - **Dropdown satuan** akan menampilkan satuan yang tersedia (EA, BOX, CARTON)
   - Pilih satuan yang ingin Anda gunakan
   - Masukkan kuantitas dalam satuan tersebut
4. Sistem secara otomatis:
   - Menampilkan harga yang benar untuk satuan yang dipilih dan level harga pelanggan
   - Menghitung total
   - Mengonversi kuantitas ke satuan dasar untuk pengecekan stok
5. Selesaikan pesanan

### Contoh: Pesanan dengan Beberapa Satuan

**Pesanan untuk Pelanggan: PT Wholesale (Level 2)**

| Item | Satuan | Kuantitas | Harga Satuan | Total |
|------|--------|-----------|--------------|-------|
| Lampu Sorot | BOX | 3 | 5,000,000 | 15,000,000 |
| Lampu Sorot | EA | 5 | 450,000 | 2,250,000 |

**Yang terjadi:**
- 3 BOX = 36 EA (pengurangan stok)
- 5 EA = 5 EA (pengurangan stok)
- Total stok dikurangi: 41 EA
- Harga yang digunakan: Harga BOX Level 2 dan harga EA Level 2

### Melihat Stok dalam Satuan Berbeda

Saat melihat inventori:
- Stok selalu ditampilkan dalam satuan dasar (EA)
- Tetapi Anda dapat melihat kuantitas setara dalam satuan lain
- Contoh: 144 EA = 12 BOX = 1 CARTON

---

## Skenario Umum

### Skenario 1: Mengatur Produk dengan Kotak dan Karton

**Tujuan**: Menjual item dalam EA, BOX (12 EA), dan CARTON (144 EA)

**Langkah:**
1. Buat satuan: BOX dan CARTON (jika belum ada)
2. Pergi ke manajemen item
3. Tambahkan satuan BOX dengan konversi: 12
4. Tambahkan satuan CARTON dengan konversi: 144
5. Tetapkan harga:
   - EA: 500,000 (Level 1)
   - BOX: 5,500,000 (Level 1) - diskon volume
   - CARTON: 60,000,000 (Level 1) - diskon lebih besar

### Skenario 2: Setup Pelanggan Grosir

**Tujuan**: Memberikan diskon 15% untuk pelanggan grosir

**Langkah:**
1. Tetapkan harga item:
   - Level 1: 500,000
   - Level 2: 425,000 (atau tetapkan persentase -15%)
2. Pergi ke master data pelanggan
3. Tetapkan level harga default pelanggan ke 2
4. Semua pesanan untuk pelanggan ini secara otomatis menggunakan harga Level 2

### Skenario 3: Harga Khusus untuk Pelanggan VIP

**Tujuan**: Memberikan harga khusus untuk pelanggan spesifik terlepas dari level

**Langkah:**
1. Pergi ke master data pelanggan
2. Navigasi ke harga item
3. Tambahkan harga kustom untuk item: 450,000
4. Pelanggan ini selalu mendapatkan harga ini, bahkan jika level mereka berubah

### Skenario 4: Diskon Volume dengan Satuan

**Tujuan**: Mendorong pembelian grosir dengan harga kotak yang lebih baik

**Langkah:**
1. Tetapkan harga EA: 500,000
2. Tetapkan harga BOX: 5,400,000 (bukan 6,000,000)
3. Ini memberikan diskon 10% saat membeli per kotak
4. Sistem secara otomatis menerapkan harga yang benar ketika pelanggan memilih satuan BOX

---

## Pemecahan Masalah

### Masalah: Satuan tidak muncul di dropdown pesanan

**Kemungkinan Penyebab:**
- Satuan tidak ditambahkan ke item
- Satuan ditandai sebagai tidak aktif
- Item tidak memiliki satuan yang dikonfigurasi

**Solusi:**
1. Pergi ke manajemen satuan item
2. Periksa apakah satuan ditambahkan dan aktif
3. Tambahkan satuan jika hilang

### Masalah: Harga yang salah digunakan

**Kemungkinan Penyebab:**
- Level harga pelanggan tidak ditetapkan dengan benar
- Harga spesifik satuan tidak dikonfigurasi
- Override harga kustom ada

**Solusi:**
1. Periksa level harga default pelanggan
2. Verifikasi harga item untuk level tersebut
3. Periksa harga kustom khusus pelanggan
4. Verifikasi harga spesifik satuan jika menggunakan satuan alternatif

### Masalah: Perhitungan stok tampaknya salah

**Kemungkinan Penyebab:**
- Faktor konversi salah
- Satuan dasar salah ditetapkan
- Beberapa satuan dasar (seharusnya hanya satu)

**Solusi:**
1. Periksa satuan dasar item (seharusnya hanya satu)
2. Verifikasi faktor konversi untuk satuan alternatif
3. Tinjau transaksi terkini untuk melihat konversi

### Masalah: Tidak dapat menghapus satuan dasar

**Penyebab**: Satuan dasar tidak dapat dihapus jika item memiliki transaksi atau adalah satu-satunya satuan

**Solusi:**
1. Tambahkan satuan lain terlebih dahulu
2. Tetapkan satuan baru sebagai satuan dasar
3. Lalu hapus satuan dasar lama (jika tidak ada transaksi)

### Masalah: Level harga tidak diterapkan

**Kemungkinan Penyebab:**
- Level harga default pelanggan tidak ditetapkan
- Item tidak memiliki harga untuk level tersebut
- Override harga kustom ada

**Solusi:**
1. Tetapkan level harga default pelanggan
2. Pastikan item memiliki harga untuk semua level
3. Periksa override harga kustom

---

## Referensi Cepat

### Manajemen Satuan

| Tugas | Lokasi | Izin Diperlukan |
|-------|--------|-----------------|
| Melihat semua satuan | Master Data > Units of Measure | view_unit_of_measure |
| Membuat satuan | Master Data > Units of Measure > Add Unit | create_unit_of_measure |
| Mengedit satuan | Master Data > Units of Measure > Edit | update_unit_of_measure |
| Menambahkan satuan ke item | Inventory > Items > [Item] > Manage Units | inventory.update |
| Menetapkan satuan dasar | Inventory > Items > [Item] > Manage Units | inventory.update |

### Manajemen Harga

| Tugas | Lokasi | Izin Diperlukan |
|-------|--------|-----------------|
| Menetapkan level harga item | Inventory > Items > [Item] > Edit | inventory.update |
| Menetapkan harga satuan | Inventory > Items > [Item] > Manage Units | inventory.update |
| Menetapkan level harga pelanggan | Business Partner > [Pelanggan] > Edit | business_partners.update |
| Menetapkan harga kustom | Business Partner > [Pelanggan] > Item Pricing | business_partners.update |

### Konversi Umum

| Dari | Ke | Faktor | Contoh |
|------|-----|--------|--------|
| BOX | EA | 12 | 1 BOX = 12 EA |
| CARTON | EA | 144 | 1 CARTON = 144 EA |
| CARTON | BOX | 12 | 1 CARTON = 12 BOX |
| DOZEN | EA | 12 | 1 DOZEN = 12 EA |
| GROSS | EA | 144 | 1 GROSS = 144 EA |

### Panduan Level Harga

| Level | Penggunaan Khas | Rentang Diskon |
|-------|-----------------|----------------|
| Level 1 | Pelanggan eceran | 0% (harga standar) |
| Level 2 | Pelanggan grosir | Diskon 5-15% |
| Level 3 | Distributor | Diskon 10-25% |

### Praktik Terbaik

1. **Selalu tetapkan satuan dasar terlebih dahulu** sebelum menambahkan satuan alternatif
2. **Gunakan faktor konversi konsisten** di seluruh item serupa
3. **Tetapkan harga untuk semua level** bahkan jika tidak segera diperlukan
4. **Uji konversi** dengan kuantitas kecil terlebih dahulu
5. **Dokumentasikan harga kustom** untuk tujuan audit
6. **Tinjau level harga secara teratur** untuk memastikan mereka saat ini
7. **Gunakan harga spesifik satuan** untuk diskon volume
8. **Tetapkan level harga pelanggan** selama setup pelanggan

---

## Sumber Daya Tambahan

- [Panduan Modul Inventori](inventory-module-manual-id.md) - Panduan lengkap manajemen inventori
- [Panduan Modul Business Partner](business-partner-module-manual-id.md) - Panduan manajemen pelanggan
- [Panduan Modul Pembelian](purchase-module-manual-id.md) - Panduan manajemen pesanan pembelian

---

**Terakhir Diperbarui**: Desember 2025
**Versi**: 1.0

