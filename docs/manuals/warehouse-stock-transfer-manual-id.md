# Panduan Transfer Stok Antar Gudang (Warehouse Stock Transfer)

## Daftar Isi

1. [Pengantar](#pengantar)
2. [Prasyarat](#prasyarat)
3. [Jenis Transfer](#jenis-transfer)
4. [Metode 1: Transfer Langsung (Direct Transfer)](#metode-1-transfer-langsung-direct-transfer)
5. [Metode 2: Transfer Dua Tahap (ITO / ITI)](#metode-2-transfer-dua-tahap-ito--iti)
6. [Melihat Riwayat Transfer](#melihat-riwayat-transfer)
7. [Mengelola Transfer Tertunda](#mengelola-transfer-tertunda)
8. [Praktik Terbaik](#praktik-terbaik)
9. [Penanganan Masalah](#penanganan-masalah)
10. [Pertanyaan Umum](#pertanyaan-umum)

---

## Pengantar

### Apa itu transfer stok antar gudang?

Fitur ini memungkinkan Anda memindahkan stok barang dari satu gudang ke gudang lain. Berguna untuk:

-   **Distribusi stok** ke beberapa lokasi
-   **Penambahan stok** di gudang yang menipis
-   **Konsolidasi** persediaan dari beberapa gudang
-   **Pengelolaan pergerakan antar gudang** untuk keperluan logistik

### Siapa yang boleh melakukan transfer?

Pengguna yang memiliki izin **`warehouse.transfer`** dapat melakukan transfer antar gudang. Hubungi administrator sistem jika Anda memerlukan izin tersebut.

---

## Prasyarat

Sebelum transfer, pastikan:

1. ✅ **Kedua gudang sudah ada** dan berstatus aktif
2. ✅ **Gudang asal memiliki stok** yang cukup untuk barang tersebut
3. ✅ **Barang sudah terdaftar** di master persediaan
4. ✅ **Akun Anda memiliki izin **`warehouse.transfer`**
5. ✅ **Gudang transit sudah dikonfigurasi** (jika menggunakan metode ITO / ITI)

---

## Jenis Transfer

Sistem mendukung tiga mode transfer:

### 1. Direct Transfer (Immediate) — Transfer langsung

-   **Kapan dipakai**: Kedua gudang dapat diakses dan transfer harus segera selesai
-   **Alur**: Satu langkah; stok langsung dari gudang asal ke gudang tujuan
-   **Status**: Langsung selesai (completed)
-   **Cocok untuk**: Transfer cepat di lokasi yang sama atau percepatan pengisian stok

### 2. Inventory Transfer Out (ITO) — Pengeluaran transfer

-   **Kapan dipakai**: Barang melalui pengiriman / logistik (ada tahap dalam perjalanan)
-   **Alur**: Dua tahap
    -   Tahap 1: Stok keluar dari gudang asal ke **gudang transit**
    -   Tahap 2: Penyelesaian dari transit ke gudang tujuan (melalui ITI)
-   **Status**: In Transit → Completed
-   **Cocok untuk**: Antar lokasi, skenario pengiriman

### 3. Inventory Transfer In (ITI) — Penerimaan transfer

-   **Kapan dipakai**: Menyelesaikan transfer ITO yang masih tertunda
-   **Alur**: Langkah akhir dari proses ITO
-   **Status**: Menutup transfer yang tertunda
-   **Cocok untuk**: Menerima barang yang dikirim melalui ITO

---

## Metode 1: Transfer Langsung (Direct Transfer)

### Ringkasan

Stok dipindahkan dari gudang asal ke gudang tujuan dalam **satu kali proses**.

### Langkah demi langkah

#### Langkah 1: Buka menu transfer

**Opsi A: Dari daftar gudang**

1. Buka **Inventory** → **Warehouses**
2. Klik tombol **Transfer Stock** di bagian atas halaman

**Opsi B: Dari detail gudang**

1. Buka **Inventory** → **Warehouses**
2. Klik nama gudang untuk membuka detail
3. Klik **Transfer Stock** di header

#### Langkah 2: Isi formulir transfer

1. **Item** (wajib)

    - Ketik kode atau nama barang, lalu pilih dari daftar yang muncul
    - Format tampilan: `KODE - Nama`
    - Contoh: `SUMATOSM05 - Sumato SM-05`

2. **From Warehouse** (wajib)

    - Pilih gudang asal (stok akan dikurangi di sini)
    - Format: `KODE - Nama` (mis. `WH001 - Main Warehouse`)

3. **To Warehouse** (wajib)

    - Pilih gudang tujuan (stok akan ditambah di sini)
    - Harus **berbeda** dari gudang asal
    - Format: `KODE - Nama` (mis. `WH002 - Branch Warehouse`)

4. **Quantity** (wajib)

    - Masukkan jumlah yang dipindahkan
    - Tidak boleh melebihi stok tersedia di gudang asal
    - Sistem menampilkan informasi stok tersedia (Available)

5. **Notes** (opsional)

    - Catatan alasan transfer, nomor referensi, dll.
    - Contoh: `Replenishing branch warehouse stock` atau `Transfer untuk sales order #12345`

#### Langkah 3: Tinjau informasi stok

Sistem menampilkan ringkasan stok, misalnya:

-   Stok di gudang asal
-   Stok di gudang tujuan saat ini
-   Proyeksi stok di gudang tujuan setelah transfer

#### Langkah 4: Validasi dan kirim

1. Pastikan semua data benar
2. Pastikan jumlah tidak melebihi stok tersedia
3. Pastikan gudang asal dan tujuan berbeda
4. Klik **Process Transfer** (atau tombol setara sesuai tampilan layar)

#### Langkah 5: Konfirmasi

-   Pesan sukses (mis. transfer berhasil)
-   Level stok terbarui segera
-   Transaksi tercatat di riwayat transfer

### Contoh: transfer langsung

**Skenario**: Memindahkan 50 unit "Sumato SM-05" dari Main Warehouse ke Branch Warehouse.

1. Buka `/warehouses` → **Transfer Stock**
2. Item: `SUMATOSM05 - Sumato SM-05`
3. From: `WH001 - Main Warehouse`
4. To: `WH002 - Branch Warehouse`
5. Quantity: `50`
6. Notes: `Replenishing branch stock`
7. Klik **Process Transfer**

**Hasil**:

-   Main Warehouse: 150 → 100 (berkurang 50)
-   Branch Warehouse: 50 → 100 (bertambah 50)
-   Transfer selesai langsung

---

## Metode 2: Transfer Dua Tahap (ITO / ITI)

### Ringkasan

Transfer menggunakan **gudang transit** agar stok dapat dilacak selama pengiriman.

### Bagian A: Membuat ITO (Inventory Transfer Out)

#### Langkah 1: Buka transfer

1. **Inventory** → **Warehouses**
2. Klik **Transfer Stock**
3. Pada **Transfer Type**, pilih **Inventory Transfer Out (ITO)**

#### Langkah 2: Isi formulir ITO

1. **Item** — pilih barang yang akan dikirim
2. **From Warehouse** — gudang asal (sistem akan mengaitkan gudang transit)
3. **To Warehouse** — gudang tujuan akhir
4. **Quantity** — jumlah (tidak boleh melebihi stok tersedia)
5. **Notes** — mis. nomor resi pengiriman

#### Langkah 3: Simpan ITO

1. Klik **Create Transfer Out** (atau tombol setara)
2. Status transfer: **In Transit**
3. Stok berkurang di gudang asal dan masuk ke gudang transit
4. Transfer muncul di daftar **Pending Transfers**

**Yang terjadi**:

-   Stok dikurangi di gudang asal
-   Stok bertambah di gudang transit
-   Status: In Transit

### Bagian B: Menyelesaikan ITI (Inventory Transfer In)

#### Langkah 1: Buka transfer tertunda

1. **Inventory** → **Warehouses**
2. Klik **Pending Transfers**
3. Atau langsung ke `/warehouses/pending-transfers-page`

#### Langkah 2: Cari transaksi

Daftar menampilkan antara lain: barang, gudang asal, gudang tujuan, jumlah, tanggal, status **In Transit**.

#### Langkah 3: Selesaikan transfer

**Opsi A: Dari halaman Pending Transfers**

1. Temukan baris transfer
2. Klik **Receive** atau **Complete Transfer**
3. Sesuaikan jumlah diterima jika perlu
4. Tambahkan catatan jika ada selisih
5. Konfirmasi penyelesaian

**Opsi B: Dari modal Transfer Stock**

1. **Warehouses** → **Transfer Stock**
2. **Transfer Type**: **Inventory Transfer In (ITI)**
3. Pilih transfer tertunda dari dropdown
4. Isi **Received Quantity** jika berbeda
5. Klik **Complete Transfer**

#### Langkah 4: Konfirmasi

-   Stok keluar dari gudang transit dan masuk ke gudang tujuan
-   Status menjadi **Completed**
-   Entri hilang dari daftar tertunda (setelah selesai)

### Contoh: transfer dua tahap

**Skenario**: Memindahkan 100 unit "Sumato SM-05" dari Main Warehouse ke Branch Warehouse via kurir.

**Tahap 1 — ITO**

1. `/warehouses` → **Transfer Stock** → tipe **ITO**
2. Item, From `WH001`, To `WH002`, Qty `100`, catatan resi
3. **Create Transfer Out**

**Tahap 2 — ITI (setelah barang diterima)**

1. Buka `/warehouses/pending-transfers-page`
2. Temukan transfer tersebut
3. **Receive** → isi jumlah diterima → **Complete Transfer**

---

## Melihat Riwayat Transfer

### Akses

1. **Inventory** → **Warehouses**
2. Klik **Transfer History**
3. Atau buka `/warehouses/transfer-history`

### Isi riwayat

Biasanya mencakup: tanggal, kode/nama barang, gudang asal, gudang tujuan, kuantitas, jenis/status transfer, catatan, dan pembuat transaksi.

### Filter

Gunakan filter yang tersedia di layar, misalnya rentang tanggal dan gudang.

### Ekspor

Jika tombol **Export** tersedia, Anda dapat mengekspor data sesuai konfigurasi sistem.

---

## Mengelola Transfer Tertunda

### Melihat daftar

**Inventory** → **Warehouses** → **Pending Transfers**, atau `/warehouses/pending-transfers-page`.

### Menyelesaikan transfer tertunda

1. Cari baris yang **In Transit**
2. Gunakan **Receive** / **Complete Transfer**
3. Verifikasi jumlah diterima
4. Selesaikan

### Penerimaan sebagian

Jika jumlah diterima lebih kecil dari yang dikirim:

1. Buka transfer tertunda
2. Masukkan **jumlah diterima riil**
3. Catat alasan selisih di catatan
4. Selesaikan transfer

**Catatan**: Selisih dapat masih tercermin di gudang transit sampai ditindaklanjuti (penyesuaian atau proses lain sesuai kebijakan perusahaan).

### Membatalkan transfer tertunda

-   Hubungi administrator, atau
-   Ikuti prosedur pembalikan / penyesuaian stok sesuai arahan IT.

---

## Praktik Terbaik

1. **Selalu cek stok tersedia** di gudang asal sebelum transfer.
2. **Pilih jenis transfer** yang sesuai: langsung untuk kebutuhan cepat; ITO/ITI jika ada pengiriman antar lokasi.
3. **Isi catatan** dengan jelas (alasan, nomor SO/PO, resi).
4. **Segera selesaikan ITI** setelah barang diterima.
5. **Rekonsiliasi berkala** terhadap transfer tertunda dan stok gudang transit.
6. **Pantau level stok** setelah transfer.
7. **Manfaatkan riwayat transfer** untuk audit dan rekonsiliasi.

---

## Penanganan Masalah

| Gejala / pesan | Penyebab umum | Tindakan |
|----------------|---------------|----------|
| Stok tidak cukup di gudang asal | Jumlah transfer melebihi stok | Kurangi qty; cek stok dan reservasi |
| Gudang asal dan tujuan harus berbeda | Gudang asal = tujuan | Pilih gudang tujuan lain |
| Transit warehouse not found | Gudang transit belum diatur untuk gudang asal | Hubungi admin; atau gunakan Direct Transfer |
| Transfer tertunda tidak ditemukan | Sudah selesai atau sudah dihapus/ditutup | Cek **Transfer History** |
| Tombol transfer tidak muncul | Tidak punya izin `warehouse.transfer` | Minta admin menambahkan izin |
| Stok tidak berubah di layar | Cache / tampilan belum segar | Refresh halaman; cek riwayat dan detail gudang |

---

## Pertanyaan Umum

**Apakah bisa banyak barang dalam satu transfer?**  
Saat ini umumnya **satu barang per transfer**. Untuk beberapa barang, buat beberapa transaksi.

**Bagaimana jika salah input?**  
Lakukan **transfer balik** ke gudang semula atau **penyesuaian stok** sesuai prosedur; konsultasikan admin jika ada keraguan.

**Apakah transfer yang sudah selesai bisa dibatalkan?**  
Biasanya tidak dibatalkan; gunakan transfer balik ke gudang asal.

**Perbedaan Direct vs ITO/ITI?**  
Direct = satu langkah tanpa pelacakan transit. ITO/ITI = melalui gudang transit untuk skenario pengiriman.

**Apakah sama dengan transfer stok antar barang (item berbeda)?**  
Tidak. Transfer antar **gudang** untuk **barang yang sama** ada di menu **Warehouses** → **Transfer Stock**. Transfer antar **item** berbeda ada di fitur lain pada detail item persediaan.

**Apakah nilai persediaan total berubah?**  
Transfer antar gudang memindahkan kuantitas; **nilai total per item** (secara agregat) tidak berubah karena hanya lokasi gudang yang berubah.

---

## Referensi Cepat

| Tindakan | Alur singkat |
|----------|----------------|
| Transfer langsung | Warehouses → **Transfer Stock** → Direct → isi form → **Process Transfer** |
| ITO | **Transfer Stock** → pilih **ITO** → **Create Transfer Out** |
| ITI | **Pending Transfers** → pilih transfer → **Receive** / **Complete Transfer** |

**URL penting (contoh):**

-   Transfer: `/warehouses` → tombol **Transfer Stock**
-   Tertunda: `/warehouses/pending-transfers-page`
-   Riwayat: `/warehouses/transfer-history`
-   Detail gudang: `/warehouses/{id}`

---

## Dukungan

Untuk bantuan lebih lanjut:

-   Dokumentasi sistem lain di folder `docs/manuals/`
-   Administrator IT / sistem
-   Audit log untuk jejak transaksi

---

**Terakhir diperbarui**: 2026-04-01  
**Versi**: 1.0 (Bahasa Indonesia)
