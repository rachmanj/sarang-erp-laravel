# Manual Transfer Stok Antar Gudang

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Prasyarat](#prasyarat)
3. [Memahami Jenis Transfer](#memahami-jenis-transfer)
4. [Metode 1: Transfer Langsung](#metode-1-transfer-langsung)
5. [Metode 2: Transfer Dua Langkah (ITO/ITI)](#metode-2-transfer-dua-langkah-itoiti)
6. [Melihat Riwayat Transfer](#melihat-riwayat-transfer)
7. [Mengelola Transfer Tertunda](#mengelola-transfer-tertunda)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)
10. [FAQ](#faq)

---

## Pengenalan

### Apa itu Transfer Stok Antar Gudang?

Transfer Stok Antar Gudang memungkinkan Anda memindahkan item inventori dari satu gudang ke gudang lainnya. Fitur ini penting untuk:

-   **Mendistribusikan stok** ke berbagai lokasi
-   **Mengisi ulang** gudang dengan stok rendah
-   **Mengkonsolidasikan inventori** dari beberapa gudang
-   **Mengelola perpindahan antar gudang** untuk logistik

### Siapa yang Dapat Mentransfer Stok?

Pengguna dengan izin **`warehouse.transfer`** dapat mentransfer stok antar gudang. Hubungi administrator sistem jika Anda memerlukan izin ini.

---

## Prasyarat

Sebelum mentransfer stok, pastikan:

1. ✅ **Kedua gudang sudah ada** dan aktif
2. ✅ **Gudang sumber memiliki stok yang cukup** untuk item tersebut
3. ✅ **Item sudah ada** dalam sistem inventori
4. ✅ **Anda memiliki izin transfer** (`warehouse.transfer`)
5. ✅ **Gudang transit sudah dikonfigurasi** (jika menggunakan metode ITO/ITI)

---

## Memahami Jenis Transfer

Sistem mendukung tiga metode transfer:

### 1. Transfer Langsung (Immediate)

-   **Kapan digunakan**: Kedua gudang dapat diakses, transfer segera diperlukan
-   **Proses**: Proses satu langkah, stok langsung pindah dari sumber ke tujuan
-   **Status**: Selesai segera
-   **Terbaik untuk**: Transfer di lokasi yang sama, pengisian ulang mendesak

### 2. Inventory Transfer Out (ITO)

-   **Kapan digunakan**: Item perlu melalui transit (pengiriman, logistik)
-   **Proses**: Proses dua langkah
    -   Langkah 1: Pindahkan item dari gudang sumber ke gudang transit
    -   Langkah 2: Selesaikan transfer dengan memindahkan dari transit ke tujuan
-   **Status**: Dalam Transit → Selesai
-   **Terbaik untuk**: Transfer antar lokasi, skenario pengiriman

### 3. Inventory Transfer In (ITI)

-   **Kapan digunakan**: Menyelesaikan transfer ITO yang tertunda
-   **Proses**: Langkah akhir dari proses ITO
-   **Status**: Menyelesaikan transfer tertunda
-   **Terbaik untuk**: Menerima item yang dikirim via ITO

---

## Metode 1: Transfer Langsung

### Gambaran Umum

Transfer Langsung langsung memindahkan stok dari satu gudang ke gudang lain dalam satu operasi.

### Instruksi Langkah demi Langkah

#### Langkah 1: Akses Fungsi Transfer

**Opsi A: Dari Daftar Gudang**

1. Navigasi ke **Inventory** → **Warehouses**
2. Klik tombol **"Transfer Stock"** di bagian atas halaman

**Opsi B: Dari Halaman Detail Gudang**

1. Navigasi ke **Inventory** → **Warehouses**
2. Klik nama gudang untuk melihat detail
3. Klik tombol **"Transfer Stock"** di header

#### Langkah 2: Isi Form Transfer

Modal transfer akan terbuka. Isi field berikut:

1. **Item** (Wajib)

    - Klik dropdown dan pilih item yang akan ditransfer
    - Item ditampilkan sebagai: `KODE - Nama`
    - Contoh: `SUMATOSM05 - Sumato SM-05`

2. **Dari Gudang** (Wajib)

    - Pilih gudang sumber
    - Ini adalah tempat stok akan dikurangi
    - Format: `KODE - Nama` (contoh: `WH001 - Main Warehouse`)

3. **Ke Gudang** (Wajib)

    - Pilih gudang tujuan
    - Ini adalah tempat stok akan ditambahkan
    - Harus berbeda dari gudang sumber
    - Format: `KODE - Nama` (contoh: `WH002 - Branch Warehouse`)

4. **Jumlah** (Wajib)

    - Masukkan jumlah unit yang akan ditransfer
    - Harus angka positif
    - Tidak boleh melebihi stok tersedia di gudang sumber
    - Sistem menampilkan stok tersedia: "Available: X units"

5. **Catatan** (Opsional)
    - Tambahkan catatan relevan tentang transfer
    - Contoh: "Mengisi ulang stok gudang cabang"
    - Contoh: "Transfer untuk sales order #12345"

#### Langkah 3: Tinjau Informasi Stok

Sistem menampilkan informasi stok real-time:

-   **Stok Sumber**: Stok saat ini di gudang sumber
-   **Stok Tujuan**: Stok saat ini di gudang tujuan
-   **Setelah Transfer**: Stok proyeksi di tujuan setelah transfer

**Contoh Tampilan**:

```
Informasi Stok
Stok Sumber:        150 unit
Stok Tujuan:        50 unit
Setelah Transfer:  200 unit
```

#### Langkah 4: Validasi dan Kirim

1. **Verifikasi** semua informasi sudah benar
2. **Periksa** bahwa jumlah tidak melebihi stok tersedia
3. **Pastikan** gudang sumber dan tujuan berbeda
4. Klik tombol **"Transfer Stock"**

#### Langkah 5: Konfirmasi

-   Pesan sukses: "Stock transfer completed successfully"
-   Level stok diperbarui segera
-   Transfer muncul di riwayat transfer
-   Transaksi inventori dibuat

### Contoh: Transfer Langsung

**Skenario**: Transfer 50 unit "Sumato SM-05" dari Main Warehouse ke Branch Warehouse

1. **Akses**: Pergi ke `/warehouses` → Klik "Transfer Stock"
2. **Pilih Item**: `SUMATOSM05 - Sumato SM-05`
3. **Dari Gudang**: `WH001 - Main Warehouse`
4. **Ke Gudang**: `WH002 - Branch Warehouse`
5. **Jumlah**: `50`
6. **Catatan**: `Mengisi ulang stok cabang`
7. **Kirim**: Klik "Transfer Stock"

**Hasil**:

-   Main Warehouse: 150 → 100 unit (dikurangi 50)
-   Branch Warehouse: 50 → 100 unit (ditambah 50)
-   Transfer selesai segera

---

## Metode 2: Transfer Dua Langkah (ITO/ITI)

### Gambaran Umum

Transfer dua langkah menggunakan gudang transit untuk melacak item selama pengiriman/logistik. Ini berguna ketika item secara fisik berpindah antar lokasi.

### Bagian A: Buat Inventory Transfer Out (ITO)

#### Langkah 1: Akses Fungsi Transfer

1. Navigasi ke **Inventory** → **Warehouses**
2. Klik tombol **"Transfer Stock"**
3. Pilih **Transfer Type**: **"Inventory Transfer Out (ITO)"**

#### Langkah 2: Isi Form ITO

1. **Item** (Wajib)

    - Pilih item yang akan ditransfer
    - Hanya item dengan stok di gudang sumber yang tersedia

2. **Dari Gudang** (Wajib)

    - Pilih gudang sumber
    - Sistem secara otomatis mengidentifikasi gudang transit

3. **Ke Gudang** (Wajib)

    - Pilih gudang tujuan akhir
    - Ini adalah tempat item akan tiba

4. **Jumlah** (Wajib)

    - Masukkan jumlah yang akan ditransfer
    - Tidak boleh melebihi stok tersedia

5. **Catatan** (Opsional)
    - Tambahkan catatan pengiriman, nomor tracking, dll.
    - Contoh: "Pengiriman via kurir, tracking #ABC123"

#### Langkah 3: Kirim ITO

1. Klik **"Create Transfer Out"**
2. Sistem membuat transfer dengan status: **"In Transit"**
3. Stok pindah dari gudang sumber ke gudang transit
4. Transfer muncul di daftar **Pending Transfers**

**Yang Terjadi**:

-   ✅ Stok dikurangi dari gudang sumber
-   ✅ Stok ditambahkan ke gudang transit
-   ✅ Status transfer: "In Transit"
-   ✅ Transfer ID dibuat untuk pelacakan

### Bagian B: Selesaikan Inventory Transfer In (ITI)

#### Langkah 1: Akses Transfer Tertunda

1. Navigasi ke **Inventory** → **Warehouses**
2. Klik tombol **"Pending Transfers"**
3. Atau langsung ke `/warehouses/pending-transfers-page`

#### Langkah 2: Temukan Transfer Tertunda

Daftar transfer tertunda menampilkan:

-   **Item**: Nama dan kode item
-   **Dari Gudang**: Gudang sumber
-   **Ke Gudang**: Gudang tujuan
-   **Jumlah**: Jumlah dalam transit
-   **Tanggal**: Tanggal pembuatan transfer
-   **Status**: "In Transit"

#### Langkah 3: Selesaikan Transfer

**Opsi A: Selesaikan via Halaman Pending Transfers**

1. Temukan transfer dalam daftar
2. Klik tombol **"Receive"** atau **"Complete Transfer"**
3. Verifikasi jumlah yang diterima (bisa berbeda dari jumlah dikirim)
4. Tambahkan catatan jika jumlah berbeda
5. Klik **"Complete Transfer"**

**Opsi B: Selesaikan via Modal Transfer**

1. Pergi ke **Warehouses** → Klik **"Transfer Stock"**
2. Pilih **Transfer Type**: **"Inventory Transfer In (ITI)"**
3. Pilih **Pending Transfer** dari dropdown
4. Masukkan **Received Quantity** (jika berbeda)
5. Tambahkan catatan
6. Klik **"Complete Transfer"**

#### Langkah 4: Konfirmasi

-   Pesan sukses: "Transfer completed successfully"
-   Stok pindah dari gudang transit ke gudang tujuan
-   Status transfer berubah menjadi "Completed"
-   Transfer dihapus dari daftar tertunda

**Yang Terjadi**:

-   ✅ Stok dikurangi dari gudang transit
-   ✅ Stok ditambahkan ke gudang tujuan
-   ✅ Status transfer: "Completed"
-   ✅ Transfer dihapus dari transfer tertunda

### Contoh: Transfer Dua Langkah

**Skenario**: Kirim 100 unit "Sumato SM-05" dari Main Warehouse ke Branch Warehouse via kurir

**Langkah 1: Buat ITO**

1. Pergi ke `/warehouses` → Klik "Transfer Stock"
2. Pilih Type: **"Inventory Transfer Out (ITO)"**
3. Item: `SUMATOSM05 - Sumato SM-05`
4. Dari: `WH001 - Main Warehouse`
5. Ke: `WH002 - Branch Warehouse`
6. Jumlah: `100`
7. Catatan: `Pengiriman via kurir, tracking #XYZ789`
8. Klik **"Create Transfer Out"**

**Hasil**:

-   Main Warehouse: 200 → 100 unit
-   Transit Warehouse: 0 → 100 unit
-   Status: In Transit

**Langkah 2: Selesaikan ITI (Setelah Menerima Pengiriman)**

1. Pergi ke `/warehouses/pending-transfers-page`
2. Temukan transfer untuk Sumato SM-05
3. Klik **"Receive"**
4. Verifikasi jumlah: `100` (atau masukkan jumlah aktual yang diterima)
5. Klik **"Complete Transfer"**

**Hasil**:

-   Transit Warehouse: 100 → 0 unit
-   Branch Warehouse: 50 → 150 unit
-   Status: Completed

---

## Melihat Riwayat Transfer

### Akses Riwayat Transfer

1. Navigasi ke **Inventory** → **Warehouses**
2. Klik tombol **"Transfer History"**
3. Atau langsung ke `/warehouses/transfer-history`

### Memahami Riwayat Transfer

Riwayat transfer menampilkan semua transfer yang selesai dengan:

-   **Tanggal**: Tanggal transfer
-   **Item**: Kode dan nama item
-   **Dari Gudang**: Kode dan nama gudang sumber
-   **Ke Gudang**: Kode dan nama gudang tujuan
-   **Jumlah**: Jumlah yang ditransfer
-   **Tipe**: Tipe transfer (Direct, ITO, ITI)
-   **Status**: Status transfer
-   **Catatan**: Catatan transfer

### Memfilter Riwayat Transfer

Anda dapat memfilter transfer berdasarkan:

-   **Rentang Tanggal**: Pilih tanggal dari dan sampai
-   **Gudang**: Filter berdasarkan gudang tertentu
-   **Item**: Filter berdasarkan item tertentu
-   **Status**: Filter berdasarkan status transfer

### Mengekspor Riwayat Transfer

1. Terapkan filter jika diperlukan
2. Klik tombol **"Export"**
3. Riwayat transfer akan diekspor (format tergantung konfigurasi sistem)

---

## Mengelola Transfer Tertunda

### Melihat Transfer Tertunda

1. Navigasi ke **Inventory** → **Warehouses**
2. Klik tombol **"Pending Transfers"**
3. Atau ke `/warehouses/pending-transfers-page`

### Daftar Transfer Tertunda

Menampilkan semua transfer dengan status "In Transit":

-   **Item**: Item yang sedang ditransfer
-   **Dari Gudang**: Gudang sumber
-   **Ke Gudang**: Gudang tujuan
-   **Jumlah**: Jumlah dalam transit
-   **Tanggal**: Tanggal pembuatan transfer
-   **Aksi**: Tombol selesaikan transfer

### Menyelesaikan Transfer Tertunda

1. Temukan transfer dalam daftar
2. Klik **"Receive"** atau **"Complete Transfer"**
3. Verifikasi jumlah yang diterima
4. Masukkan jumlah aktual yang diterima jika berbeda
5. Tambahkan catatan jika diperlukan
6. Klik **"Complete Transfer"**

### Menangani Penerimaan Sebagian

Jika Anda menerima kurang dari jumlah yang dikirim:

1. Buka transfer tertunda
2. Masukkan **jumlah aktual yang diterima** (kurang dari dikirim)
3. Tambahkan catatan menjelaskan perbedaan
    - Contoh: "Diterima 95 unit, 5 unit rusak dalam transit"
4. Selesaikan transfer

**Catatan**: Sistem akan menyesuaikan jumlah sesuai. Perbedaan akan tetap di gudang transit sampai diselesaikan.

### Membatalkan Transfer Tertunda

Jika transfer perlu dibatalkan:

1. Hubungi administrator sistem
2. Atau balikkan transfer secara manual:
    - Selesaikan ITI untuk mengembalikan item ke gudang sumber
    - Atau buat adjustment untuk memperbaiki level stok

---

## Best Practices

### 1. Verifikasi Stok Sebelum Transfer

-   Selalu periksa stok tersedia sebelum memulai transfer
-   Pastikan gudang sumber memiliki jumlah yang cukup
-   Pertimbangkan jumlah yang dipesan jika berlaku

### 2. Gunakan Tipe Transfer yang Tepat

-   **Transfer Langsung**: Lokasi yang sama, kebutuhan segera
-   **ITO/ITI**: Pengiriman antar lokasi, perlu pelacakan

### 3. Dokumentasikan Transfer dengan Benar

-   Selalu tambahkan catatan menjelaskan alasan transfer
-   Sertakan nomor referensi (sales orders, purchase orders)
-   Catat persyaratan penanganan khusus

### 4. Selesaikan Transfer ITO Tepat Waktu

-   Selesaikan transfer ITI segera setelah item diterima
-   Verifikasi jumlah cocok sebelum menyelesaikan
-   Laporkan ketidaksesuaian segera

### 5. Rekonsiliasi Berkala

-   Tinjau transfer tertunda secara berkala
-   Selesaikan atau selesaikan semua transfer tertunda
-   Rekonsiliasi stok gudang transit secara teratur

### 6. Pemantauan Level Stok

-   Pantau level stok setelah transfer
-   Pastikan gudang tujuan memiliki stok yang memadai
-   Periksa alert stok rendah setelah transfer

### 7. Audit Trail

-   Semua transfer dicatat dalam audit trail
-   Tinjau riwayat transfer secara teratur
-   Gunakan riwayat transfer untuk rekonsiliasi

---

## Troubleshooting

### Masalah: "Insufficient stock in source warehouse"

**Penyebab**: Mencoba mentransfer lebih dari stok tersedia

**Solusi**:

1. Periksa stok saat ini di gudang sumber
2. Kurangi jumlah transfer
3. Pertimbangkan jumlah yang dipesan jika berlaku
4. Verifikasi stok belum ditransfer ke tempat lain

### Masalah: "Source and destination warehouses must be different"

**Penyebab**: Memilih gudang yang sama untuk sumber dan tujuan

**Solusi**:

1. Pilih gudang yang berbeda
2. Verifikasi pilihan gudang dalam form

### Masalah: "Transit warehouse not found"

**Penyebab**: Gudang sumber tidak memiliki gudang transit yang dikonfigurasi

**Solusi**:

1. Hubungi administrator sistem
2. Konfigurasi gudang transit untuk gudang sumber
3. Atau gunakan Transfer Langsung sebagai gantinya

### Masalah: Tidak dapat menemukan transfer tertunda

**Penyebab**: Transfer mungkin sudah selesai atau tidak ada

**Solusi**:

1. Periksa riwayat transfer sebagai gantinya
2. Verifikasi ID transfer jika diketahui
3. Periksa apakah transfer diselesaikan oleh pengguna lain
4. Hubungi administrator jika diperlukan

### Masalah: Ketidaksesuaian jumlah setelah ITI

**Penyebab**: Jumlah yang diterima berbeda dari jumlah yang dikirim

**Solusi**:

1. Masukkan jumlah aktual yang diterima saat menyelesaikan ITI
2. Tambahkan catatan menjelaskan perbedaan
3. Buat adjustment jika diperlukan untuk rekonsiliasi
4. Laporkan ke manajemen jika ketidaksesuaian signifikan

### Masalah: Tombol transfer tidak terlihat

**Penyebab**: Tidak memiliki izin `warehouse.transfer`

**Solusi**:

1. Hubungi administrator sistem
2. Minta izin `warehouse.transfer`
3. Verifikasi peran pengguna memiliki izin yang benar

### Masalah: Stok tidak diperbarui setelah transfer

**Penyebab**: Kemungkinan masalah sistem atau cache

**Solusi**:

1. Refresh halaman
2. Periksa riwayat transfer untuk memastikan transfer selesai
3. Verifikasi level stok di halaman detail gudang
4. Hubungi dukungan IT jika masalah berlanjut

---

## FAQ

### Q1: Bisakah saya mentransfer beberapa item dalam satu transfer?

**A**: Saat ini, setiap transfer menangani satu item sekaligus. Buat transfer terpisah untuk setiap item.

### Q2: Apa yang terjadi jika saya membuat kesalahan dalam transfer?

**A**: Anda dapat membuat transfer balik (transfer kembali) atau menggunakan stock adjustment untuk memperbaiki. Hubungi administrator untuk bantuan.

### Q3: Bisakah saya membatalkan transfer yang sudah selesai?

**A**: Transfer yang sudah selesai tidak dapat dibatalkan, tetapi Anda dapat membuat transfer balik untuk memindahkan stok kembali.

### Q4: Bagaimana saya tahu gudang transit mana yang digunakan?

**A**: Sistem secara otomatis menggunakan gudang transit yang dikonfigurasi untuk gudang sumber. Periksa pengaturan gudang atau hubungi administrator.

### Q5: Apa perbedaan antara Transfer Langsung dan ITO/ITI?

**A**:

-   **Transfer Langsung**: Langsung, satu langkah, tanpa pelacakan transit
-   **ITO/ITI**: Dua langkah, menggunakan gudang transit, melacak item selama pengiriman

### Q6: Bisakah saya mentransfer stok antar item?

**A**: Fungsi transfer gudang memindahkan stok antar gudang untuk item yang sama. Untuk mentransfer antar item berbeda, gunakan fungsi "Transfer Stock" di halaman detail item inventori (fitur berbeda).

### Q7: Berapa lama transfer tertunda tetap dalam sistem?

**A**: Transfer tertunda tetap sampai diselesaikan. Tidak ada kedaluwarsa otomatis. Selesaikan segera setelah item diterima.

### Q8: Bagaimana jika saya menerima item yang rusak?

**A**: Saat menyelesaikan ITI, masukkan jumlah baik aktual yang diterima. Buat adjustment terpisah atau catat untuk item yang rusak. Dokumentasikan dalam catatan transfer.

### Q9: Bisakah saya melihat siapa yang membuat transfer?

**A**: Ya, riwayat transfer menyertakan informasi pengguna. Periksa audit log untuk pelacakan pengguna detail.

### Q10: Apakah transfer mempengaruhi valuasi inventori?

**A**: Transfer memindahkan stok antar gudang tetapi tidak mengubah total nilai inventori. Valuasi dihitung per item di semua gudang.

---

## Referensi Cepat

### Langkah Transfer Langsung

1. Warehouses → Transfer Stock
2. Pilih Item, Dari, Ke, Jumlah
3. Tambahkan Catatan
4. Kirim

### Langkah ITO

1. Warehouses → Transfer Stock → ITO
2. Isi form
3. Create Transfer Out
4. Item pindah ke transit

### Langkah ITI

1. Warehouses → Pending Transfers
2. Temukan transfer
3. Complete Transfer
4. Item pindah ke tujuan

### Rute Penting

-   Transfer Stock: `/warehouses` → tombol "Transfer Stock"
-   Pending Transfers: `/warehouses/pending-transfers-page`
-   Transfer History: `/warehouses/transfer-history`
-   Warehouse Detail: `/warehouses/{id}`

---

## Dukungan

Untuk bantuan tambahan:

-   Periksa dokumentasi sistem
-   Hubungi administrator sistem Anda
-   Tinjau audit log untuk detail transfer
-   Periksa pengaturan dan konfigurasi gudang

---

**Terakhir Diperbarui**: 2026-01-22  
**Versi**: 1.0
