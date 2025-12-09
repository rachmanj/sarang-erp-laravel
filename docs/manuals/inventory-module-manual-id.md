# Manual Modul Inventory

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Memulai](#memulai)
3. [Ringkasan Fitur](#ringkasan-fitur)
4. [Membuat Item Inventory](#membuat-item-inventory)
5. [Melihat dan Mencari Item](#melihat-dan-mencari-item)
6. [Mengedit Item Inventory](#mengedit-item-inventory)
7. [Manajemen Stok](#manajemen-stok)
8. [Laporan dan Analitik](#laporan-dan-analitik)
9. [Manajemen Satuan](#manajemen-satuan)
10. [Tingkat Harga](#tingkat-harga)
11. [Tugas Umum](#tugas-umum)
12. [Pemecahan Masalah](#pemecahan-masalah)

---

## Pengenalan

### Apa itu Modul Inventory?

Modul Inventory adalah sistem komprehensif yang membantu Anda mengelola semua produk dan layanan perusahaan. Sistem ini melacak:

- **Item apa yang Anda miliki** (produk dan layanan)
- **Berapa banyak yang Anda miliki** (kuantitas stok)
- **Di mana lokasinya** (gudang dan lokasi)
- **Berapa harganya** (harga beli dan harga jual)
- **Berapa nilainya** (valuasi inventory)

### Siapa yang Harus Menggunakan Modul Ini?

- **Staf Gudang**: Mengelola level stok dan memproses pergerakan stok
- **Tim Pembelian**: Menyiapkan item baru dan melacak inventory
- **Tim Penjualan**: Memeriksa ketersediaan item dan harga
- **Manajer**: Memantau nilai inventory dan level stok
- **Akuntan**: Melacak biaya inventory dan valuasi

---

## Memulai

### Mengakses Modul Inventory

1. Masuk ke sistem ERP
2. Dari menu utama, klik **"Inventory"**
3. Anda akan melihat halaman Manajemen Inventory

### Memahami Layar Utama

Ketika Anda membuka modul Inventory, Anda akan melihat:

- Tombol **"Add Item"**: Membuat item inventory baru
- Tombol **"Low Stock"**: Melihat item yang perlu dipesan ulang
- Tombol **"Valuation Report"**: Melihat total nilai inventory
- Kotak pencarian: Mencari item dengan cepat
- Opsi filter: Filter berdasarkan kategori, metode valuasi, atau status stok
- Tabel daftar item: Menampilkan semua item inventory Anda

---

## Ringkasan Fitur

Modul Inventory mencakup fitur-fitur utama berikut:

### 1. **Manajemen Item**
- Membuat, mengedit, dan menghapus item inventory
- Mengorganisir item berdasarkan kategori
- Menyiapkan item sebagai produk fisik atau layanan
- Mendefinisikan satuan ukuran (buah, kotak, kilogram, dll.)

### 2. **Pelacakan Stok**
- Pemantauan level stok real-time
- Melacak pergerakan stok (pembelian, penjualan, penyesuaian, transfer)
- Menetapkan level stok minimum dan maksimum
- Peringatan stok rendah otomatis

### 3. **Metode Valuasi**
- **FIFO** (First In, First Out): Stok tertua dijual terlebih dahulu
- **LIFO** (Last In, First Out): Stok terbaru dijual terlebih dahulu
- **Rata-rata Tertimbang**: Rata-rata biaya dari semua stok

### 4. **Manajemen Harga**
- Menetapkan harga beli (yang Anda bayar)
- Menetapkan harga jual (yang Anda kenakan)
- Beberapa tingkat harga (Level 1, 2, 3) untuk pelanggan berbeda
- Harga khusus pelanggan

### 5. **Laporan dan Analitik**
- Laporan stok rendah
- Laporan valuasi inventory
- Riwayat pergerakan stok
- Ekspor data ke Excel/CSV

### 6. **Konversi Satuan**
- Dukungan untuk beberapa satuan per item (misalnya, kotak dan buah)
- Konversi otomatis antar satuan
- Harga berbeda untuk satuan berbeda

---

## Membuat Item Inventory

### Panduan Langkah demi Langkah

#### Langkah 1: Membuka Form Buat Item

1. Dari halaman Inventory, klik tombol **"Add Item"** (biasanya di pojok kiri atas)
2. Form akan terbuka untuk memasukkan detail item

#### Langkah 2: Mengisi Informasi Dasar

**Field Wajib** (ditandai dengan *):

1. **Kode Item** *
   - Masukkan kode unik untuk item ini (misalnya, "CHR-001")
   - Kode ini digunakan untuk mengidentifikasi item dengan cepat
   - Contoh: "OFF-CHAIR-001" untuk kursi kantor model 1

2. **Nama Item** *
   - Masukkan nama yang deskriptif (misalnya, "Kursi Kantor Premium - Model A")
   - Buat jelas dan mudah dipahami

3. **Deskripsi** (Opsional)
   - Tambahkan detail tambahan tentang item
   - Contoh: "Kursi kantor ergonomis dengan tinggi yang dapat disesuaikan"

4. **Kategori** *
   - Pilih kategori produk dari dropdown
   - Kategori membantu mengorganisir inventory Anda
   - Contoh: "Perabotan Kantor", "Elektronik", "Perlengkapan Kantor"

5. **Tipe Item** *
   - Pilih **"Item"** untuk produk fisik (mempengaruhi stok)
   - Pilih **"Service"** untuk layanan (tidak mempengaruhi stok)
   - Contoh: Kursi adalah "Item", konsultasi adalah "Service"

6. **Satuan Dasar** *
   - Pilih satuan ukuran (misalnya, Buah, Kotak, Kilogram)
   - Ini adalah satuan utama untuk item ini
   - Contoh: "PCS" untuk buah, "BOX" untuk kotak

#### Langkah 3: Menyiapkan Harga

1. **Harga Beli** (Opsional)
   - Masukkan biaya yang Anda bayar untuk membeli item ini
   - Contoh: 2.500.000 (dalam mata uang Anda)

2. **Harga Jual** *
   - Masukkan harga yang Anda jual untuk item ini
   - Ini adalah harga jual dasar (Price Level 1)
   - Contoh: 3.500.000

3. **Price Level 2** (Opsional)
   - Masukkan harga berbeda untuk pelanggan tertentu
   - Atau tetapkan persentase kenaikan/penurunan dari harga dasar

4. **Price Level 3** (Opsional)
   - Masukkan opsi tingkat harga lain
   - Berguna untuk tier pelanggan berbeda

#### Langkah 4: Mengonfigurasi Level Stok (Hanya untuk Item Fisik)

Jika Anda memilih tipe "Item", Anda akan melihat field level stok:

1. **Level Stok Minimum** *
   - Kuantitas terendah yang ingin Anda pertahankan
   - Sistem akan memberi peringatan ketika stok turun di bawah ini
   - Contoh: 10 unit

2. **Level Stok Maksimum** *
   - Kuantitas tertinggi yang ingin Anda simpan
   - Membantu mencegah overstocking
   - Contoh: 100 unit

3. **Titik Pemesanan Ulang** *
   - Kuantitas di mana Anda harus memesan lebih banyak
   - Biasanya ditetapkan antara minimum dan maksimum
   - Contoh: 20 unit

#### Langkah 5: Menetapkan Metode Valuasi

1. **Metode Valuasi** *
   - **FIFO**: Item yang dibeli pertama dijual terlebih dahulu (direkomendasikan untuk sebagian besar bisnis)
   - **LIFO**: Item yang dibeli terakhir dijual terlebih dahulu
   - **Rata-rata Tertimbang**: Rata-rata biaya dari semua item

   **Mana yang harus dipilih?**
   - **FIFO**: Terbaik untuk sebagian besar bisnis, sesuai dengan alur fisik
   - **LIFO**: Digunakan di beberapa negara untuk keperluan pajak
   - **Rata-rata Tertimbang**: Paling sederhana, baik untuk item dengan biaya serupa

#### Langkah 6: Pengaturan Tambahan

1. **Gudang Default** (Opsional)
   - Pilih gudang di mana item ini biasanya disimpan
   - Anda dapat mengubah ini nanti jika diperlukan

2. **Stok Awal** (Opsional)
   - Jika Anda menambahkan item yang sudah ada di gudang Anda
   - Masukkan kuantitas saat ini yang Anda miliki
   - Masukkan biaya unit untuk stok awal ini

3. **Status Aktif**
   - Centang kotak untuk membuat item aktif (tersedia untuk digunakan)
   - Hapus centang untuk menonaktifkan (sembunyikan dari penggunaan normal)

#### Langkah 7: Menyimpan Item

1. Tinjau semua informasi yang Anda masukkan
2. Klik tombol **"Save"** atau **"Create Item"**
3. Anda akan melihat pesan sukses jika item berhasil dibuat
4. Anda akan diarahkan ke halaman detail item

### Tips untuk Membuat Item

- **Gunakan penamaan yang konsisten**: "Kursi Kantor - Model A" lebih baik daripada "kursi1"
- **Buat kategori terlebih dahulu**: Siapkan kategori sebelum membuat banyak item
- **Mulai dengan info dasar**: Anda dapat menambahkan detail lebih lanjut nanti
- **Periksa kode dua kali**: Kode item harus unik

---

## Melihat dan Mencari Item

### Melihat Semua Item

1. Buka **Inventory** dari menu utama
2. Anda akan melihat tabel yang mencantumkan semua item inventory
3. Tabel menampilkan:
   - Kode Item
   - Nama Item
   - Kategori
   - Satuan Ukuran
   - Harga Beli
   - Harga Jual
   - Stok Saat Ini
   - Level Stok Minimum
   - Status (Aktif/Nonaktif)

### Mencari Item

**Pencarian Cepat:**
1. Gunakan kotak pencarian di bagian atas
2. Ketik kode item atau nama
3. Tekan Enter atau klik ikon pencarian
4. Hasil akan difilter secara otomatis

**Pencarian Lanjutan:**
1. Gunakan dropdown filter:
   - **Kategori**: Filter berdasarkan kategori produk
   - **Metode Valuasi**: Filter berdasarkan FIFO, LIFO, atau Rata-rata Tertimbang
   - **Status Stok**: Filter berdasarkan Stok Rendah, Habis, atau Tersedia
2. Klik tombol **"Filter"** untuk menerapkan filter
3. Klik **"Reset"** atau hapus filter untuk melihat semua item lagi

### Melihat Detail Item

1. Temukan item dalam daftar
2. Klik pada nama item atau tombol **"View"** (ikon mata)
3. Anda akan melihat informasi detail:
   - Semua informasi item
   - Level stok per gudang
   - Riwayat transaksi
   - Riwayat valuasi
   - Audit trail (siapa mengubah apa dan kapan)

### Memahami Indikator Status Stok

Dalam daftar item, Anda akan melihat badge berwarna:

- **Hijau "OK"**: Stok di atas level minimum
- **Kuning "Low"**: Stok berada pada atau di bawah level minimum
- **Merah "Out"**: Stok nol atau negatif

---

## Mengedit Item Inventory

### Kapan Harus Mengedit Item

Anda mungkin perlu mengedit item ketika:
- Harga berubah
- Level stok perlu disesuaikan
- Detail item perlu diperbarui
- Item menjadi tidak aktif

### Cara Mengedit Item

#### Langkah 1: Mencari Item

1. Buka daftar Inventory
2. Cari atau filter untuk menemukan item yang ingin Anda edit

#### Langkah 2: Membuka Form Edit

1. Klik tombol **"Edit"** (ikon pensil) di sebelah item
2. Atau klik pada nama item, lalu klik **"Edit"** dari halaman detail

#### Langkah 3: Membuat Perubahan

1. Perbarui field apa pun yang perlu Anda ubah
2. Sebagian besar field dapat diedit:
   - Nama dan deskripsi item
   - Harga
   - Level stok
   - Kategori
   - Metode valuasi
   - Status aktif

**Catatan:** Kode Item biasanya tidak dapat diubah setelah pembuatan untuk menjaga integritas data.

#### Langkah 4: Menyimpan Perubahan

1. Tinjau perubahan Anda
2. Klik tombol **"Save"** atau **"Update"**
3. Anda akan melihat pesan sukses
4. Perubahan disimpan dan dicatat dalam audit trail

### Catatan Penting

- **Perubahan harga**: Akan mempengaruhi penjualan masa depan, bukan transaksi masa lalu
- **Perubahan level stok**: Hanya mengubah ambang batas peringatan, bukan stok aktual
- **Perubahan metode valuasi**: Akan mempengaruhi perhitungan biaya masa depan
- **Menonaktifkan item**: Menyembunyikannya dari penggunaan normal tetapi menyimpan riwayat

---

## Manajemen Stok

### Memahami Pergerakan Stok

Pergerakan stok adalah perubahan pada kuantitas inventory Anda. Ada empat jenis utama:

1. **Pembelian**: Stok meningkat (barang diterima)
2. **Penjualan**: Stok menurun (barang dijual)
3. **Penyesuaian**: Koreksi manual level stok
4. **Transfer**: Memindahkan stok antar item atau gudang

### Penyesuaian Stok

Penyesuaian stok digunakan untuk memperbaiki perbedaan inventory yang ditemukan selama:
- Penghitungan stok fisik
- Cycle counting
- Kerusakan atau kehilangan
- Item yang ditemukan

#### Cara Menyesuaikan Stok

**Langkah 1: Mengakses Penyesuaian**

1. Buka daftar Inventory
2. Temukan item yang ingin Anda sesuaikan
3. Klik tombol **"Adjust Stock"** (biasanya ikon +/-)

**Langkah 2: Memasukkan Detail Penyesuaian**

1. **Tipe Penyesuaian**:
   - **Tambah Stok**: Menambahkan item (item yang ditemukan, koreksi)
   - **Kurangi Stok**: Menghapus item (kerusakan, kehilangan, koreksi)

2. **Kuantitas**: Masukkan berapa banyak unit yang akan disesuaikan
   - Contoh: Jika penghitungan fisik menunjukkan 28 tetapi sistem menunjukkan 30, kurangi 2

3. **Biaya Unit**: Masukkan biaya per unit
   - Ini mempengaruhi valuasi inventory
   - Gunakan biaya rata-rata saat ini jika tidak yakin

4. **Catatan**: Jelaskan mengapa Anda menyesuaikan
   - Contoh: "Perbedaan cycle count - ditemukan 2 unit hilang"

**Langkah 3: Mengirim Penyesuaian**

1. Tinjau informasi
2. Klik **"Adjust Stock"** atau **"Submit"**
3. Level stok akan diperbarui segera
4. Catatan transaksi dibuat

### Transfer Stok

Transfer stok memindahkan inventory dari satu item ke item lain. Ini kurang umum tetapi berguna untuk:
- Menggabungkan item serupa
- Memisahkan item
- Mengonversi antar kode item

#### Cara Mentransfer Stok

**Langkah 1: Mengakses Transfer**

1. Temukan item sumber (dari mana stok berasal)
2. Klik tombol **"Transfer Stock"**

**Langkah 2: Memasukkan Detail Transfer**

1. **Transfer Ke**: Pilih item tujuan dari dropdown
2. **Kuantitas**: Masukkan berapa banyak unit yang akan ditransfer
3. **Biaya Unit**: Masukkan biaya per unit
4. **Catatan**: Tambahkan informasi relevan apa pun

**Langkah 3: Mengirim Transfer**

1. Tinjau informasi
2. Klik **"Transfer Stock"**
3. Stok berkurang dari item sumber
4. Stok meningkat di item tujuan
5. Kedua transaksi dicatat

### Melihat Riwayat Stok

1. Buka halaman detail item
2. Klik pada tab **"Transactions"**
3. Anda akan melihat semua pergerakan stok:
   - Tanggal dan waktu
   - Tipe transaksi
   - Perubahan kuantitas
   - Biaya
   - Dokumen referensi (jika ada)
   - Siapa yang membuatnya

---

## Laporan dan Analitik

### Laporan Stok Rendah

Laporan ini menunjukkan item yang perlu dipesan ulang.

#### Cara Melihat Laporan Stok Rendah

1. Dari halaman Inventory, klik tombol **"Low Stock"**
2. Anda akan melihat daftar item di mana:
   - Stok saat ini ≤ Titik Pemesanan Ulang
   - Item diurutkan berdasarkan urgensi

#### Memahami Laporan

- **Kode/Nama Item**: Item mana yang perlu diperhatikan
- **Stok Saat Ini**: Berapa banyak yang Anda miliki sekarang
- **Titik Pemesanan Ulang**: Level yang memicu peringatan
- **Stok Minimum**: Level terendah yang dapat diterima
- **Kategori**: Kategori item untuk pengelompokan

#### Apa yang Harus Dilakukan

1. Tinjau setiap item
2. Periksa apakah pemesanan ulang diperlukan
3. Buat purchase order untuk item yang perlu diisi ulang
4. Sesuaikan titik pemesanan ulang jika ditetapkan secara salah

### Laporan Valuasi

Laporan ini menunjukkan total nilai inventory Anda.

#### Cara Melihat Laporan Valuasi

1. Dari halaman Inventory, klik tombol **"Valuation Report"**
2. Anda akan melihat nilai inventory berdasarkan:
   - Item individual
   - Kategori
   - Total nilai inventory

#### Memahami Laporan

- **Item**: Kode dan nama item
- **Kuantitas Tersedia**: Stok saat ini
- **Biaya Unit**: Rata-rata biaya per unit
- **Total Nilai**: Kuantitas × Biaya Unit
- **Metode Valuasi**: Bagaimana biaya dihitung

#### Menggunakan Laporan

- **Pelaporan keuangan**: Total nilai inventory untuk neraca
- **Analisis kategori**: Lihat kategori mana yang memiliki nilai terbanyak
- **Analisis biaya**: Identifikasi item bernilai tinggi
- **Perencanaan**: Memahami investasi inventory

### Mengekspor Data

Anda dapat mengekspor data inventory ke format Excel atau CSV.

#### Cara Mengekspor

1. Dari halaman daftar Inventory
2. Klik tombol **"Export"**
3. Pilih format ekspor (jika tersedia)
4. File akan diunduh ke komputer Anda

#### Apa yang Diekspor

- Semua item yang terlihat (menghormati filter saat ini)
- Kode item, nama, kategori
- Harga dan level stok
- Kuantitas stok saat ini

---

## Manajemen Satuan

### Memahami Satuan

Beberapa item dapat dijual dalam satuan berbeda. Misalnya:
- Satu kotak pulpen (1 kotak = 12 buah)
- Satu karton kertas (1 karton = 10 rim)
- Satu palet barang (1 palet = 50 kotak)

Sistem mendukung beberapa satuan per item dengan konversi otomatis.

### Mengelola Satuan untuk Item

#### Langkah 1: Mengakses Manajemen Satuan

1. Buka halaman detail item
2. Cari bagian **"Units"** atau **"Manage Units"** atau tombol
3. Klik untuk membuka manajemen satuan

#### Langkah 2: Melihat Satuan Saat Ini

Anda akan melihat:
- **Satuan Dasar**: Satuan utama (biasanya yang terkecil)
- **Satuan Lain**: Satuan tambahan dengan tingkat konversi
- **Harga**: Harga jual untuk setiap satuan

#### Langkah 3: Menambahkan Satuan Baru

1. Klik tombol **"Add Unit"**
2. Pilih satuan dari dropdown (misalnya, "BOX", "CARTON")
3. Masukkan **Kuantitas Konversi**:
   - Berapa banyak satuan dasar = 1 dari satuan ini
   - Contoh: 1 BOX = 12 PCS, jadi masukkan 12
4. Masukkan **Harga Jual** untuk satuan ini
5. Opsional, tetapkan price level 2 dan 3
6. Klik **"Save"**

#### Langkah 4: Mengedit Harga Satuan

1. Temukan satuan dalam daftar
2. Klik tombol **"Edit"**
3. Perbarui harga
4. Klik **"Save"**

#### Langkah 5: Menetapkan Satuan Dasar

1. Hanya satu satuan yang bisa menjadi satuan dasar
2. Untuk mengubah satuan dasar:
   - Temukan satuan yang ingin Anda jadikan dasar
   - Klik **"Set as Base Unit"**
   - Sistem akan secara otomatis mengonversi satuan lain

#### Langkah 6: Menghapus Satuan

1. Temukan satuan yang ingin Anda hapus
2. Klik tombol **"Remove"**
3. Konfirmasi penghapusan
4. **Catatan**: Anda tidak dapat menghapus satuan terakhir atau satuan dasar jika satuan lain ada

### Contoh Konversi Satuan

**Contoh 1: Pulpen**
- Satuan Dasar: PCS (buah)
- Satuan Tambahan: BOX
- Konversi: 1 BOX = 12 PCS
- Jika Anda memiliki 5 kotak, sistem menunjukkan: 60 PCS

**Contoh 2: Kertas**
- Satuan Dasar: REAM
- Satuan Tambahan: CARTON
- Konversi: 1 CARTON = 10 REAMS
- Jika Anda menjual 2 karton, sistem mencatat: 20 REAMS

---

## Tingkat Harga

### Memahami Tingkat Harga

Tingkat harga memungkinkan Anda mengenakan harga berbeda untuk pelanggan berbeda:
- **Level 1**: Harga standar (harga jual dasar)
- **Level 2**: Harga diskon atau premium untuk pelanggan tertentu
- **Level 3**: Tier harga lain untuk pelanggan khusus

### Menyiapkan Tingkat Harga

#### Di Level Item

Saat membuat atau mengedit item:

1. **Harga Jual**: Ini adalah Level 1 (harga dasar)
2. **Price Level 2**: 
   - Masukkan harga tetap, ATAU
   - Masukkan persentase (misalnya, +10% atau -5%)
3. **Price Level 3**: Opsi yang sama dengan Level 2

**Contoh:**
- Harga Dasar (Level 1): 100.000
- Level 2: +10% = 110.000
- Level 3: -5% = 95.000

#### Harga Khusus Pelanggan

Anda dapat menetapkan harga khusus untuk pelanggan tertentu:

1. Buka halaman detail item
2. Cari bagian **"Customer Prices"** atau **"Price Levels"**
3. Klik **"Set Customer Price"**
4. Pilih pelanggan
5. Pilih tingkat harga (1, 2, atau 3)
6. Opsional, masukkan harga khusus
7. Klik **"Save"**

### Melihat Ringkasan Tingkat Harga

1. Buka halaman detail item
2. Klik **"Price Level Summary"** (jika tersedia)
3. Anda akan melihat:
   - Harga dasar untuk setiap level
   - Pelanggan mana yang menggunakan level mana
   - Harga khusus yang ditetapkan untuk pelanggan tertentu

---

## Tugas Umum

### Tugas 1: Menambahkan Produk Baru ke Inventory

**Skenario**: Anda menerima produk baru dari supplier dan perlu menambahkannya ke sistem.

**Langkah-langkah**:
1. Buka Inventory → Klik "Add Item"
2. Masukkan kode item, nama, dan deskripsi
3. Pilih kategori
4. Pilih tipe "Item"
5. Tetapkan satuan dasar (misalnya, PCS)
6. Masukkan harga beli dan harga jual
7. Tetapkan stok minimum (10), stok maksimum (100), titik pemesanan ulang (20)
8. Pilih metode valuasi (FIFO direkomendasikan)
9. Jika Anda memiliki stok awal, masukkan kuantitas dan biaya
10. Klik "Save"

### Tugas 2: Memproses Penghitungan Stok Fisik

**Skenario**: Cycle count bulanan menunjukkan 28 unit, tetapi sistem menunjukkan 30 unit.

**Langkah-langkah**:
1. Buka Inventory → Temukan item
2. Klik "Adjust Stock"
3. Pilih "Decrease Stock"
4. Masukkan kuantitas: 2
5. Masukkan biaya unit (gunakan rata-rata saat ini)
6. Tambahkan catatan: "Cycle count bulanan - 2 unit hilang"
7. Klik "Adjust Stock"
8. Verifikasi level stok baru adalah 28

### Tugas 3: Memeriksa Item Stok Rendah

**Skenario**: Anda ingin melihat item mana yang perlu dipesan ulang.

**Langkah-langkah**:
1. Buka Inventory
2. Klik tombol "Low Stock"
3. Tinjau daftar
4. Untuk setiap item, putuskan:
   - Buat purchase order?
   - Sesuaikan titik pemesanan ulang?
   - Tidak perlu tindakan?
5. Ambil tindakan yang sesuai

### Tugas 4: Memperbarui Harga Item

**Skenario**: Supplier menaikkan biaya, jadi Anda perlu memperbarui harga jual.

**Langkah-langkah**:
1. Buka Inventory → Temukan item
2. Klik "Edit"
3. Perbarui "Purchase Price" jika berubah
4. Perbarui "Selling Price" untuk mempertahankan margin
5. Perbarui Price Level 2 dan 3 jika diperlukan
6. Klik "Save"
7. **Catatan**: Ini hanya mempengaruhi penjualan masa depan, bukan transaksi masa lalu

### Tugas 5: Melihat Nilai Inventory

**Skenario**: Akhir bulan - perlu mengetahui total nilai inventory untuk pelaporan keuangan.

**Langkah-langkah**:
1. Buka Inventory
2. Klik "Valuation Report"
3. Tinjau total nilai inventory
4. Periksa nilai berdasarkan kategori jika diperlukan
5. Ekspor ke Excel jika diperlukan untuk pelaporan
6. Gunakan total nilai untuk laporan keuangan

### Tugas 6: Menyiapkan Beberapa Satuan

**Skenario**: Anda menjual pulpen secara individual (PCS) dan dalam kotak (1 kotak = 12 buah).

**Langkah-langkah**:
1. Buat item dengan satuan dasar: PCS
2. Buka detail item → Bagian Units
3. Klik "Add Unit"
4. Pilih satuan: BOX
5. Masukkan konversi: 12 (1 kotak = 12 buah)
6. Masukkan harga jual untuk kotak (misalnya, jika 1 PCS = 1.000, 1 BOX mungkin 11.000)
7. Klik "Save"
8. Sekarang Anda dapat menjual dalam satuan PCS dan BOX

### Tugas 7: Menonaktifkan Item

**Skenario**: Anda tidak lagi menjual produk tetapi ingin menyimpan riwayatnya.

**Langkah-langkah**:
1. Buka Inventory → Temukan item
2. Klik "Edit"
3. Hapus centang kotak "Active"
4. Klik "Save"
5. Item disembunyikan dari penggunaan normal tetapi riwayat dipertahankan
6. Untuk mengaktifkan kembali nanti, edit dan centang "Active" lagi

---

## Pemecahan Masalah

### Masalah: Tidak Dapat Menemukan Item

**Kemungkinan Penyebab**:
- Item tidak aktif
- Istilah pencarian salah
- Filter diterapkan

**Solusi**:
1. Hapus semua filter
2. Periksa apakah mencari berdasarkan kode atau nama
3. Coba pencarian sebagian (misalnya, "kursi" bukan "kursi kantor")
4. Periksa apakah item ditandai sebagai tidak aktif

### Masalah: Level Stok Tampaknya Salah

**Kemungkinan Penyebab**:
- Transaksi tidak diproses
- Penyesuaian diperlukan
- Kesalahan perhitungan sistem

**Solusi**:
1. Periksa riwayat transaksi untuk item
2. Verifikasi semua pembelian dan penjualan dicatat
3. Lakukan penghitungan fisik dan sesuaikan jika diperlukan
4. Hubungi administrator sistem jika masalah berlanjut

### Masalah: Tidak Dapat Mengedit Kode Item

**Ini Normal**: Kode item tidak dapat diubah setelah pembuatan untuk menjaga integritas data.

**Solusi**:
- Jika kode salah, buat item baru dengan kode yang benar
- Nonaktifkan item lama
- Transfer stok yang tersisa jika diperlukan

### Masalah: Tingkat Harga Tidak Berfungsi

**Kemungkinan Penyebab**:
- Tingkat harga tidak ditetapkan untuk pelanggan
- Pelanggan tidak ditugaskan ke tingkat harga
- Harga khusus tidak dikonfigurasi

**Solusi**:
1. Periksa pengaturan tingkat harga item
2. Verifikasi pelanggan memiliki tingkat harga yang ditugaskan
3. Periksa harga khusus khusus pelanggan
4. Pastikan tingkat harga aktif

### Masalah: Konversi Satuan Tidak Berfungsi

**Kemungkinan Penyebab**:
- Satuan tidak ditambahkan ke item
- Tingkat konversi salah
- Satuan dasar tidak ditetapkan

**Solusi**:
1. Verifikasi satuan ditambahkan dalam manajemen satuan
2. Periksa kuantitas konversi benar
3. Pastikan satuan dasar ditetapkan dengan benar
4. Uji konversi dengan angka sederhana

### Masalah: Peringatan Stok Rendah Tidak Muncul

**Kemungkinan Penyebab**:
- Titik pemesanan ulang tidak ditetapkan
- Stok di atas titik pemesanan ulang
- Item tidak aktif

**Solusi**:
1. Periksa titik pemesanan ulang ditetapkan (bukan nol)
2. Verifikasi stok saat ini benar-benar di bawah titik pemesanan ulang
3. Pastikan item aktif
4. Periksa secara manual laporan stok rendah

### Masalah: Valuasi Tampaknya Tidak Benar

**Kemungkinan Penyebab**:
- Metode valuasi salah
- Biaya salah dimasukkan dalam transaksi
- Masalah waktu perhitungan

**Solusi**:
1. Verifikasi metode valuasi (FIFO/LIFO/Rata-rata Tertimbang)
2. Periksa biaya transaksi benar
3. Tinjau riwayat valuasi
4. Hubungi administrator jika perhitungan tampaknya salah

### Masalah: Tidak Dapat Menghapus Item

**Kemungkinan Penyebab**:
- Item memiliki riwayat transaksi
- Item direferensikan di modul lain

**Solusi**:
- Item dengan transaksi tidak dapat dihapus (dengan desain)
- Nonaktifkan item sebagai gantinya
- Hubungi administrator jika penghapusan benar-benar diperlukan

---

## Referensi Cepat

### Pintasan Keyboard

- **Ctrl + F**: Pencarian (di sebagian besar browser)
- **Enter**: Kirim form
- **Esc**: Tutup modal

### Istilah Penting

- **FIFO**: First In, First Out - stok tertua dijual terlebih dahulu
- **LIFO**: Last In, First Out - stok terbaru dijual terlebih dahulu
- **Rata-rata Tertimbang**: Rata-rata biaya dari semua stok
- **Titik Pemesanan Ulang**: Level stok yang memicu peringatan pemesanan ulang
- **Valuasi**: Total nilai inventory
- **Satuan Dasar**: Satuan ukuran utama untuk item

### Tipe Item Umum

- **Item**: Produk fisik yang mempengaruhi stok
- **Service**: Layanan non-fisik yang tidak mempengaruhi stok

### Warna Status Stok

- 🟢 **Hijau**: Stok sehat (di atas minimum)
- 🟡 **Kuning**: Stok rendah (pada atau di bawah minimum)
- 🔴 **Merah**: Stok habis (nol atau negatif)

---

## Mendapatkan Bantuan

Jika Anda memerlukan bantuan tambahan:

1. **Periksa manual ini** terlebih dahulu untuk tugas umum
2. **Hubungi administrator sistem Anda** untuk masalah teknis
3. **Tinjau materi pelatihan** jika tersedia
4. **Periksa audit trail** untuk melihat apa yang berubah dan kapan

---

## Praktik Terbaik

### Saat Membuat Item

- ✅ Gunakan konvensi penamaan yang jelas dan konsisten
- ✅ Siapkan kategori sebelum membuat banyak item
- ✅ Masukkan harga yang akurat dari awal
- ✅ Tetapkan level stok yang realistis
- ✅ Pilih metode valuasi yang sesuai

### Saat Mengelola Stok

- ✅ Lakukan cycle count secara teratur
- ✅ Sesuaikan stok segera ketika perbedaan ditemukan
- ✅ Dokumentasikan semua penyesuaian dengan catatan jelas
- ✅ Tinjau laporan stok rendah secara teratur
- ✅ Jaga riwayat transaksi tetap bersih

### Saat Menetapkan Harga

- ✅ Perbarui harga ketika biaya berubah
- ✅ Pertahankan strategi harga yang konsisten
- ✅ Tinjau tingkat harga secara berkala
- ✅ Dokumentasikan alasan perubahan harga

### Tips Umum

- ✅ Selalu verifikasi informasi sebelum menyimpan
- ✅ Gunakan catatan untuk mendokumentasikan perubahan penting
- ✅ Tinjau laporan secara teratur
- ✅ Jaga informasi item tetap up to date
- ✅ Nonaktifkan daripada menghapus jika memungkinkan

---

**Akhir Manual**

*Manual ini mencakup fitur dasar Modul Inventory. Untuk fitur lanjutan atau proses bisnis khusus, konsultasikan dengan administrator sistem Anda atau lihat dokumentasi tambahan.*

