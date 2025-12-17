# Manual Modul Inventory

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Memulai](#memulai)
3. [Ringkasan Fitur](#ringkasan-fitur)
4. [Manajemen Kategori Produk](#manajemen-kategori-produk)
5. [Membuat Item Inventory](#membuat-item-inventory)
6. [Melihat dan Mencari Item](#melihat-dan-mencari-item)
7. [Mengedit Item Inventory](#mengedit-item-inventory)
8. [Manajemen Stok](#manajemen-stok)
9. [Manajemen Gudang](#manajemen-gudang)
10. [Manajemen GR/GI (Goods Receipt/Goods Issue)](#manajemen-grgi-goods-receiptgoods-issue)
11. [Laporan dan Analitik](#laporan-dan-analitik)
12. [Manajemen Satuan](#manajemen-satuan)
13. [Tingkat Harga](#tingkat-harga)
14. [Pemetaan Akun](#pemetaan-akun)
15. [Tugas Umum](#tugas-umum)
16. [Pemecahan Masalah](#pemecahan-masalah)

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
- **Manual**: Menetapkan biaya secara manual untuk setiap transaksi

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

### 7. **Manajemen Kategori Produk**
- Struktur kategori hierarkis (hubungan parent-child)
- Pemetaan akun per kategori (Akun Inventory, COGS, Sales)
- Warisan akun dari kategori parent
- Opsi tampilan tree dan table

### 8. **Manajemen Gudang**
- Dukungan multi-gudang
- Pelacakan stok per gudang
- Transfer stok antar gudang
- Dukungan gudang transit untuk operasi ITO/ITI
- Titik pemesanan ulang khusus gudang

### 9. **Manajemen GR/GI**
- Dokumen Goods Receipt (GR) dan Goods Issue (GI)
- Tujuan yang dapat dikonfigurasi (Customer Return, Donation, Sample, dll.)
- Alur persetujuan (Draft → Pending Approval → Approved)
- Pembuatan jurnal otomatis
- Pemetaan akun berdasarkan kategori dan tujuan

---

## Manajemen Kategori Produk

### Memahami Kategori Produk

Kategori produk membantu mengorganisir item inventory Anda. Kategori dapat diatur dalam struktur hierarkis (hubungan parent-child) dan dikaitkan dengan akun akuntansi untuk pembuatan jurnal otomatis.

### Konsep Utama

- **Struktur Hierarkis**: Kategori dapat memiliki kategori parent dan child (misalnya, "Elektronik" > "Komputer" > "Laptop")
- **Pemetaan Akun**: Setiap kategori memetakan ke tiga akun akuntansi:
  - **Akun Inventory**: Untuk valuasi inventory
  - **Akun COGS**: Untuk cost of goods sold
  - **Akun Sales**: Untuk pengakuan pendapatan
- **Warisan Akun**: Kategori child dapat mewarisi akun dari kategori parent jika tidak ditetapkan secara eksplisit

### Membuat Kategori Produk

#### Langkah 1: Mengakses Manajemen Kategori

1. Dari menu utama, buka **"Master Data"** → **"Product Categories"**
2. Anda akan melihat daftar kategori dalam tampilan table atau tree

#### Langkah 2: Membuat Kategori Baru

1. Klik tombol **"Add Category"** atau **"Create"**
2. Isi detail kategori:

**Field Wajib:**

1. **Kode Kategori** *
   - Masukkan kode unik (misalnya, "ELEC", "FURN")
   - Kode ini mengidentifikasi kategori

2. **Nama Kategori** *
   - Masukkan nama yang deskriptif (misalnya, "Elektronik", "Perabotan Kantor")
   - Buat jelas dan mudah dipahami

3. **Deskripsi** (Opsional)
   - Tambahkan detail tambahan tentang kategori

4. **Kategori Parent** (Opsional)
   - Pilih kategori parent jika ini adalah sub-kategori
   - Hanya kategori root (kategori tanpa parent) yang ditampilkan
   - Contoh: Jika membuat "Laptop", pilih "Komputer" sebagai parent

5. **Pemetaan Akun** (Opsional tetapi Direkomendasikan)
   - **Akun Inventory**: Pilih akun untuk valuasi inventory
   - **Akun COGS**: Pilih akun untuk cost of goods sold
   - **Akun Sales**: Pilih akun untuk pendapatan penjualan
   - Jika tidak ditetapkan, kategori child akan mewarisi dari parent

6. **Status Aktif**
   - Centang untuk membuat kategori aktif
   - Hapus centang untuk menonaktifkan

#### Langkah 3: Menyimpan Kategori

1. Tinjau semua informasi
2. Klik **"Save"** atau **"Create Category"**
3. Kategori dibuat dan tersedia untuk digunakan

### Melihat Kategori

#### Tampilan Table

1. Tampilan default menunjukkan kategori dalam format table
2. Menampilkan: Kode, Nama, Kategori Parent, Akun, Status
3. Gunakan filter dan pencarian untuk menemukan kategori tertentu

#### Tampilan Tree

1. Klik tombol toggle **"Tree View"**
2. Kategori ditampilkan dalam struktur tree hierarkis
3. Perluas/tutup kategori parent untuk melihat child
4. Representasi visual dari hubungan kategori

### Mengedit Kategori

1. Temukan kategori dalam daftar
2. Klik tombol **"Edit"**
3. Perbarui field apa pun:
   - Nama dan deskripsi
   - Kategori parent (hati-hati - dapat mempengaruhi hierarki)
   - Pemetaan akun
   - Status aktif
4. Klik **"Save"**

**Catatan Penting:**
- Mengubah kategori parent dapat mempengaruhi warisan akun
- Item yang menggunakan kategori ini akan menggunakan pemetaan akun yang diperbarui
- Menonaktifkan kategori menyembunyikannya dari pemilihan tetapi mempertahankan item yang ada

### Memahami Warisan Akun

**Cara Kerjanya:**

1. Jika kategori memiliki akun yang ditetapkan, ia menggunakan akun tersebut
2. Jika kategori tidak memiliki akun yang ditetapkan, ia mewarisi dari parent-nya
3. Warisan naik ke hierarki sampai akun ditemukan
4. Jika tidak ada akun ditemukan dalam hierarki, sistem menggunakan default

**Contoh:**
- Kategori Parent "Elektronik" memiliki:
  - Akun Inventory: "Inventory - Elektronik"
  - Akun COGS: "COGS - Elektronik"
  - Akun Sales: "Sales - Elektronik"
- Kategori Child "Laptop" (tidak ada akun yang ditetapkan) mewarisi ketiga akun dari "Elektronik"
- Kategori Child "Ponsel" (memiliki Akun Sales sendiri) menggunakan:
  - Akun Inventory: Diwarisi dari "Elektronik"
  - Akun COGS: Diwarisi dari "Elektronik"
  - Akun Sales: Sendiri "Sales - Ponsel"

### Praktik Terbaik untuk Kategori

- ✅ Buat kategori root terlebih dahulu, lalu sub-kategori
- ✅ Tetapkan pemetaan akun di level parent jika memungkinkan
- ✅ Gunakan konvensi penamaan yang konsisten
- ✅ Jaga hierarki sederhana (2-3 level maksimum direkomendasikan)
- ✅ Tinjau pemetaan akun sebelum membuat banyak item

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
   - **Manual**: Menetapkan biaya secara manual untuk setiap transaksi

   **Mana yang harus dipilih?**
   - **FIFO**: Terbaik untuk sebagian besar bisnis, sesuai dengan alur fisik
   - **LIFO**: Digunakan di beberapa negara untuk keperluan pajak
   - **Rata-rata Tertimbang**: Paling sederhana, baik untuk item dengan biaya serupa
   - **Manual**: Gunakan ketika Anda memerlukan kontrol penuh atas penugasan biaya

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

Transfer stok dapat memindahkan inventory dalam dua cara:
1. **Transfer Item-ke-Item**: Memindahkan stok dari satu item ke item lain
2. **Transfer Gudang-ke-Gudang**: Memindahkan stok antar gudang

#### Transfer Item-ke-Item

Ini kurang umum tetapi berguna untuk:
- Menggabungkan item serupa
- Memisahkan item
- Mengonversi antar kode item

**Cara Mentransfer Stok Antar Item:**

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

## Manajemen Gudang

### Memahami Sistem Multi-Gudang

Sistem mendukung beberapa gudang, memungkinkan Anda untuk:
- Melacak level stok per gudang
- Mentransfer stok antar gudang
- Menetapkan titik pemesanan ulang berbeda per gudang
- Menggunakan gudang transit untuk transfer antar gudang

### Melihat Stok per Gudang

1. Buka halaman detail item
2. Cari bagian **"Warehouse Stock"** atau **"Stock by Warehouse"**
3. Anda akan melihat:
   - Nama gudang
   - Kuantitas stok saat ini
   - Titik pemesanan ulang untuk gudang tersebut
   - Status (Stok Rendah, OK, Habis)

### Transfer Stok Antar Gudang

Transfer stok dari satu gudang ke gudang lain.

#### Cara Mentransfer Stok Antar Gudang

**Langkah 1: Mengakses Transfer Gudang**

1. Buka halaman detail item
2. Cari tombol **"Transfer Between Warehouses"** atau **"Warehouse Transfer"**
3. Atau buka modul Warehouse Management

**Langkah 2: Memasukkan Detail Transfer**

1. **Dari Gudang**: Pilih gudang sumber
2. **Ke Gudang**: Pilih gudang tujuan
3. **Item**: Pilih item yang akan ditransfer
4. **Kuantitas**: Masukkan berapa banyak unit yang akan ditransfer
5. **Catatan**: Tambahkan informasi relevan apa pun

**Langkah 3: Mengirim Transfer**

1. Tinjau informasi
2. Klik **"Transfer"** atau **"Submit"**
3. Stok berkurang di gudang sumber
4. Stok meningkat di gudang tujuan
5. Transaksi dicatat

### Gudang Transit (ITO/ITI)

Untuk operasi gudang yang kompleks, sistem mendukung gudang transit:

- **ITO (Inventory Transfer Out)**: Memindahkan item dari gudang sumber ke gudang transit
- **ITI (Inventory Transfer In)**: Memindahkan item dari gudang transit ke gudang tujuan

**Kapan Menggunakan:**
- Transfer gudang multi-langkah
- Item dalam transit antar lokasi
- Melacak item selama pengiriman

**Cara Kerjanya:**

1. **Buat ITO**: Item berpindah dari gudang sumber ke gudang transit (status: "In Transit")
2. **Buat ITI**: Item berpindah dari gudang transit ke gudang tujuan (status: "Completed")

**Catatan:** Gudang transit biasanya dikonfigurasi oleh administrator sistem. Hubungi administrator Anda jika Anda perlu menggunakan fitur ini.

### Titik Pemesanan Ulang Khusus Gudang

Anda dapat menetapkan titik pemesanan ulang berbeda untuk item yang sama di gudang berbeda.

**Contoh:**
- Item "Kursi Kantor" di Gudang Utama: Pemesanan ulang pada 20 unit
- Item yang sama di Gudang Cabang: Pemesanan ulang pada 10 unit

**Cara Menetapkan:**

1. Buka halaman detail item
2. Temukan bagian stok gudang
3. Edit titik pemesanan ulang untuk gudang tertentu
4. Simpan perubahan

### Penugasan Gudang Default

Saat membuat item, Anda dapat menetapkan gudang default. Ini adalah gudang di mana item biasanya disimpan.

**Catatan:** Gudang default hanyalah saran. Stok dapat disimpan di gudang mana pun, dan Anda dapat mengubah default nanti.

---

## Manajemen GR/GI (Goods Receipt/Goods Issue)

### Memahami Dokumen GR/GI

Dokumen GR/GI (Goods Receipt/Goods Issue) menangani operasi inventory yang **bukan** bagian dari transaksi pembelian atau penjualan normal. Ini termasuk:

- **Goods Receipt (GR)**: Menerima item tanpa purchase order
  - Customer return
  - Donasi diterima
  - Item yang ditemukan
  - Sample item diterima

- **Goods Issue (GI)**: Mengeluarkan item tanpa sales order
  - Customer return (mengirim kembali)
  - Donasi diberikan
  - Sample item diberikan
  - Write-off barang rusak
  - Penggunaan internal

### Fitur Utama

- **Manajemen Tujuan**: Setiap dokumen GR/GI memiliki tujuan (Customer Return, Donation, Sample, dll.)
- **Alur Persetujuan**: Dokumen melalui Draft → Pending Approval → Approved
- **Jurnal Otomatis**: Jurnal dibuat secara otomatis saat disetujui
- **Pemetaan Akun**: Akun dipetakan secara otomatis berdasarkan kategori item dan tujuan

### Membuat Dokumen GR/GI

#### Langkah 1: Mengakses Modul GR/GI

1. Dari menu utama, buka **"Inventory"** → **"GR/GI"** atau **"Goods Receipt/Issue"**
2. Klik **"Create New"** atau **"Add Document"**
3. Pilih tipe dokumen: **Goods Receipt** atau **Goods Issue**

#### Langkah 2: Mengisi Header Dokumen

**Field Wajib:**

1. **Tipe Dokumen** *
   - **Goods Receipt**: Untuk menerima item
   - **Goods Issue**: Untuk mengeluarkan item

2. **Tujuan** *
   - Pilih tujuan dari dropdown
   - Contoh: Customer Return, Donation, Sample, Internal Use, dll.
   - Tujuan menentukan pemetaan akun

3. **Gudang** *
   - Pilih gudang untuk transaksi ini

4. **Tanggal Transaksi** *
   - Masukkan tanggal transaksi

5. **Nomor Referensi** (Opsional)
   - Nomor referensi eksternal (misalnya, nomor customer return)

6. **Catatan** (Opsional)
   - Informasi tambahan tentang transaksi

#### Langkah 3: Menambahkan Baris Dokumen

1. Klik **"Add Line"** atau **"Add Item"**
2. Untuk setiap baris, masukkan:
   - **Item**: Pilih item inventory
   - **Kuantitas**: Masukkan kuantitas
   - **Harga Unit**: Masukkan harga unit (mempengaruhi valuasi)
   - **Catatan** (Opsional): Catatan khusus baris

3. Ulangi untuk semua item
4. Sistem menghitung total jumlah secara otomatis

#### Langkah 4: Simpan sebagai Draft

1. Tinjau semua informasi
2. Klik **"Save"** atau **"Save Draft"**
3. Dokumen disimpan dengan status "Draft"
4. Anda dapat mengeditnya nanti sebelum mengirim

### Alur Persetujuan GR/GI

#### Tahapan Alur

1. **Draft**: Dokumen dibuat tetapi belum dikirim
   - Dapat diedit
   - Dapat dihapus
   - Tidak ada dampak inventory

2. **Pending Approval**: Dokumen dikirim untuk persetujuan
   - Tidak dapat diedit
   - Menunggu persetujuan
   - Belum ada dampak inventory

3. **Approved**: Dokumen disetujui
   - Tidak dapat diedit
   - Inventory diperbarui
   - Jurnal dibuat
   - Status akhir

4. **Cancelled**: Dokumen dibatalkan
   - Tidak dapat digunakan
   - Tidak ada dampak inventory

#### Mengirim untuk Persetujuan

1. Buka dokumen draft
2. Tinjau semua detail
3. Klik tombol **"Submit for Approval"**
4. Status dokumen berubah menjadi "Pending Approval"
5. Pemberi persetujuan akan diberi notifikasi (jika sistem notifikasi diaktifkan)

#### Menyetujui Dokumen

**Siapa yang Dapat Menyetujui:**
- Pengguna dengan izin persetujuan
- Biasanya manajer atau supervisor

**Cara Menyetujui:**

1. Buka daftar GR/GI
2. Temukan dokumen dengan status "Pending Approval"
3. Buka dokumen
4. Tinjau semua detail
5. Klik tombol **"Approve"**
6. Sistem akan:
   - Memperbarui stok inventory
   - Membuat jurnal
   - Mengubah status menjadi "Approved"

**Penting:**
- Setelah disetujui, dokumen tidak dapat diedit
- Inventory dan akuntansi diperbarui segera
- Tinjau dengan hati-hati sebelum menyetujui

#### Membatalkan Dokumen

1. Buka dokumen draft atau pending approval
2. Klik tombol **"Cancel"**
3. Konfirmasi pembatalan
4. Status dokumen berubah menjadi "Cancelled"
5. Tidak ada dampak inventory atau akuntansi

### Memahami Pemetaan Akun GR/GI

Sistem secara otomatis memetakan akun berdasarkan:

1. **Kategori Item**: Menggunakan akun dari kategori produk item
2. **Tujuan**: Tujuan berbeda dapat menggunakan pemetaan akun berbeda
3. **Tipe Dokumen**: GR vs GI dapat menggunakan akun berbeda

**Jenis Akun yang Digunakan:**

- **Untuk Goods Receipt:**
  - Debit: Akun Inventory (dari kategori)
  - Kredit: Akun Expense/Other (berdasarkan tujuan)

- **Untuk Goods Issue:**
  - Debit: Akun Expense/Other (berdasarkan tujuan)
  - Kredit: Akun Inventory (dari kategori)

**Contoh:**
- Dokumen GR: Customer Return, Kategori Item "Elektronik"
  - Menggunakan Akun Inventory dari kategori "Elektronik"
  - Menggunakan akun expense Customer Return (dari pemetaan tujuan)

### Melihat Dokumen GR/GI

1. Buka halaman daftar GR/GI
2. Anda akan melihat:
   - Nomor dokumen
   - Tipe dokumen (GR/GI)
   - Tujuan
   - Gudang
   - Status
   - Total jumlah
   - Tanggal

3. Filter berdasarkan:
   - Tipe dokumen
   - Status
   - Rentang tanggal
   - Gudang
   - Tujuan

4. Klik pada dokumen untuk melihat detail:
   - Semua informasi header
   - Semua baris item
   - Riwayat persetujuan
   - Jurnal yang dibuat
   - Dampak inventory

### Tujuan GR/GI Umum

**Tujuan Goods Receipt:**
- Customer Return: Item dikembalikan oleh pelanggan
- Donation Received: Item diterima sebagai donasi
- Sample Received: Sample item diterima
- Found Items: Item ditemukan selama penghitungan inventory

**Tujuan Goods Issue:**
- Customer Return: Item dikembalikan ke pelanggan
- Donation Given: Item diberikan sebagai donasi
- Sample Given: Sample item diberikan ke pelanggan
- Internal Use: Item digunakan secara internal
- Damage Write-off: Item rusak yang dihapuskan

### Praktik Terbaik untuk GR/GI

- ✅ Selalu pilih tujuan yang benar
- ✅ Tinjau pemetaan akun sebelum menyetujui
- ✅ Tambahkan catatan jelas yang menjelaskan transaksi
- ✅ Verifikasi kuantitas sebelum persetujuan
- ✅ Simpan nomor referensi untuk pelacakan
- ✅ Tinjau jurnal setelah persetujuan

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

## Pemetaan Akun

### Memahami Pemetaan Akun

Pemetaan akun secara otomatis menghubungkan item inventory ke akun akuntansi. Ini memastikan bahwa transaksi inventory membuat jurnal yang benar dalam sistem akuntansi Anda.

### Cara Kerja Pemetaan Akun

**Tiga Jenis Akun:**

1. **Akun Inventory**: Melacak nilai inventory yang tersedia
   - Contoh: "Inventory - Elektronik", "Inventory - Perlengkapan Kantor"
   - Digunakan ketika item diterima atau dikeluarkan

2. **Akun COGS (Cost of Goods Sold)**: Melacak biaya ketika item dijual
   - Contoh: "COGS - Elektronik", "COGS - Perlengkapan Kantor"
   - Digunakan ketika item dijual ke pelanggan

3. **Akun Sales**: Melacak pendapatan dari penjualan
   - Contoh: "Sales - Elektronik", "Sales - Perlengkapan Kantor"
   - Digunakan ketika item dijual ke pelanggan

### Hierarki Pemetaan Akun

Akun dipetakan dalam urutan ini:

1. **Level Item**: Item dapat memiliki akun khusus (jika dikonfigurasi)
2. **Level Kategori**: Item mewarisi dari kategori produknya
3. **Kategori Parent**: Jika kategori tidak memiliki akun, mewarisi dari parent
4. **Default Sistem**: Menggunakan default sistem jika tidak ada akun ditemukan

### Cara Item Mendapatkan Akun

Ketika Anda membuat item inventory:

1. Anda memilih **Kategori Produk**
2. Item secara otomatis mewarisi akun dari kategori tersebut
3. Jika kategori tidak memiliki akun, ia mewarisi dari kategori parent
4. Ini terjadi secara otomatis - tidak perlu setup manual

**Contoh:**
- Kategori "Elektronik" memiliki:
  - Akun Inventory: "Inventory - Elektronik"
  - Akun COGS: "COGS - Elektronik"
  - Akun Sales: "Sales - Elektronik"
- Item "Laptop Model X" dalam kategori "Elektronik" secara otomatis menggunakan akun-akun ini

### Pemetaan Akun dalam Transaksi

**Transaksi Pembelian:**
- Debit: Akun Inventory (meningkatkan nilai inventory)
- Kredit: Accounts Payable atau Cash (tergantung pembayaran)

**Transaksi Penjualan:**
- Debit: Accounts Receivable atau Cash (pendapatan diterima)
- Kredit: Akun Sales (pengakuan pendapatan)
- Debit: Akun COGS (cost of goods sold)
- Kredit: Akun Inventory (mengurangi nilai inventory)

**Transaksi GR/GI:**
- Akun tergantung pada tujuan dan tipe dokumen
- Sistem secara otomatis memilih akun yang benar

### Melihat Pemetaan Akun

1. Buka halaman detail Kategori Produk
2. Anda akan melihat:
   - Akun Inventory yang ditetapkan
   - Akun COGS yang ditetapkan
   - Akun Sales yang ditetapkan
   - Apakah akun diwarisi atau milik sendiri

3. Buka halaman detail Item Inventory
4. Anda akan melihat:
   - Akun mana yang digunakan item
   - Sumber akun (kategori, warisan, dll.)

### Catatan Penting

- ✅ Pemetaan akun ditetapkan di level kategori (direkomendasikan)
- ✅ Item secara otomatis mewarisi dari kategori
- ✅ Perubahan pada akun kategori mempengaruhi semua item dalam kategori tersebut
- ✅ Tinjau pemetaan akun sebelum membuat banyak item
- ✅ Konsultasikan dengan akuntan Anda untuk setup akun yang benar

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

### Tugas 8: Membuat Kategori Produk dengan Pemetaan Akun

**Skenario**: Anda perlu menyiapkan kategori produk baru untuk "Perlengkapan Kantor" dengan pemetaan akun yang tepat.

**Langkah-langkah**:
1. Buka Master Data → Product Categories
2. Klik "Add Category"
3. Masukkan kode: "OFF-SUP"
4. Masukkan nama: "Perlengkapan Kantor"
5. Biarkan kategori parent kosong (kategori root)
6. Pilih Akun Inventory: "Inventory - Perlengkapan Kantor"
7. Pilih Akun COGS: "COGS - Perlengkapan Kantor"
8. Pilih Akun Sales: "Sales - Perlengkapan Kantor"
9. Centang "Active"
10. Klik "Save"
11. Sekarang semua item dalam kategori ini akan menggunakan akun-akun ini

### Tugas 9: Mentransfer Stok Antar Gudang

**Skenario**: Anda perlu memindahkan 50 unit item dari Gudang Utama ke Gudang Cabang.

**Langkah-langkah**:
1. Buka Inventory → Temukan item
2. Klik pada item untuk melihat detail
3. Cari "Warehouse Transfer" atau "Transfer Between Warehouses"
4. Pilih "From Warehouse": Gudang Utama
5. Pilih "To Warehouse": Gudang Cabang
6. Masukkan kuantitas: 50
7. Tambahkan catatan: "Transfer ke cabang untuk penjualan"
8. Klik "Transfer"
9. Verifikasi level stok diperbarui di kedua gudang

### Tugas 10: Membuat Goods Receipt untuk Customer Return

**Skenario**: Pelanggan mengembalikan 5 unit item. Anda perlu mencatat ini.

**Langkah-langkah**:
1. Buka Inventory → GR/GI → Create New
2. Pilih Tipe Dokumen: "Goods Receipt"
3. Pilih Tujuan: "Customer Return"
4. Pilih Gudang: Gudang Utama
5. Masukkan tanggal transaksi
6. Tambahkan nomor referensi (nomor customer return)
7. Tambahkan baris item:
   - Pilih item yang dikembalikan
   - Masukkan kuantitas: 5
   - Masukkan harga unit (biaya asli)
8. Tambahkan catatan: "Customer return - item rusak"
9. Klik "Save" (membuat sebagai Draft)
10. Tinjau dan klik "Submit for Approval"
11. Setelah persetujuan, inventory meningkat dan jurnal dibuat

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

### Masalah: Pemetaan Akun Tidak Berfungsi

**Kemungkinan Penyebab**:
- Kategori tidak memiliki akun yang ditetapkan
- Kategori parent tidak memiliki akun
- Akun tidak dikonfigurasi dalam sistem

**Solusi**:
1. Periksa pemetaan akun kategori
2. Verifikasi kategori parent memiliki akun (jika menggunakan warisan)
3. Tetapkan akun di level kategori
4. Hubungi administrator untuk mengonfigurasi akun default

### Masalah: Dokumen GR/GI Tidak Dapat Disetujui

**Kemungkinan Penyebab**:
- Dokumen tidak dalam status "Pending Approval"
- Pemetaan akun hilang
- Stok tidak mencukupi (untuk Goods Issue)
- Pengguna tidak memiliki izin persetujuan

**Solusi**:
1. Verifikasi status dokumen adalah "Pending Approval"
2. Periksa pemetaan akun dikonfigurasi
3. Verifikasi ketersediaan stok (untuk GI)
4. Hubungi administrator untuk izin persetujuan

### Masalah: Transfer Gudang Tidak Berfungsi

**Kemungkinan Penyebab**:
- Stok tidak mencukupi di gudang sumber
- Gudang yang sama dipilih untuk sumber dan tujuan
- Item tidak tersedia di gudang sumber

**Solusi**:
1. Periksa level stok di gudang sumber
2. Verifikasi gudang berbeda dipilih
3. Pastikan item ada di gudang sumber
4. Periksa gudang aktif

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
- **Valuasi Manual**: Menetapkan biaya secara manual per transaksi
- **Titik Pemesanan Ulang**: Level stok yang memicu peringatan pemesanan ulang
- **Valuasi**: Total nilai inventory
- **Satuan Dasar**: Satuan ukuran utama untuk item
- **GR (Goods Receipt)**: Dokumen untuk menerima item tanpa purchase order
- **GI (Goods Issue)**: Dokumen untuk mengeluarkan item tanpa sales order
- **ITO (Inventory Transfer Out)**: Memindahkan item ke gudang transit
- **ITI (Inventory Transfer In)**: Memindahkan item dari gudang transit

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

