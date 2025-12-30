# Manual Input Inventory Awal ke Setiap Gudang

## Gambaran Umum

Manual ini menjelaskan langkah-langkah untuk memasukkan stok inventory awal ke setiap gudang dalam sistem Sarang ERP. Terdapat **tiga metode** yang tersedia, masing-masing sesuai untuk skenario yang berbeda.

---

## Perbandingan Metode

| Metode | Penggunaan | Pilihan Gudang | Jurnal Akuntansi | Persetujuan Diperlukan |
|--------|-----------|---------------|------------------|----------------------|
| **Metode 1: Pembuatan Item** | Satu gudang, item baru | Hanya gudang default | Tidak | Tidak |
| **Metode 2: GR (Goods Receipt)** | Beberapa gudang, akuntansi yang benar | ✅ Ya | ✅ Ya | ✅ Ya |
| **Metode 3: Penyesuaian Stok** | Koreksi cepat | ❌ Tidak (keterbatasan) | Tidak | Tidak |

**Direkomendasikan**: Gunakan **Metode 2 (GR/GI)** untuk setup inventory awal yang benar dengan pelacakan per gudang dan integrasi akuntansi.

---

## Metode 1: Stok Awal Saat Pembuatan Item

### Kapan Menggunakan
- Membuat item inventory baru
- Stok awal hanya untuk **satu gudang** (gudang default)
- Setup sederhana tanpa entri akuntansi

### Langkah-langkah

#### Langkah 1: Buat Item Inventory
1. Navigasi ke: **Inventory** → **Add Item**
2. Isi informasi item yang diperlukan:
   - Kode Item
   - Nama Item
   - Kategori
   - Tipe Item: **Item** (bukan Service)
   - Satuan Dasar
   - Harga Beli
   - Harga Jual

#### Langkah 2: Set Gudang Default
1. Di form pembuatan item, cari field **"Default Warehouse"**
2. Pilih gudang tempat stok awal akan disimpan
3. **Penting**: Gudang ini akan menerima stok awal

#### Langkah 3: Masukkan Stok Awal
1. Cari field **"Initial Stock"**
2. Masukkan kuantitas yang Anda miliki di gudang default
3. Sistem akan menggunakan **Harga Beli** sebagai biaya per unit

#### Langkah 4: Simpan Item
1. Klik **"Save"** atau **"Create Item"**
2. Sistem secara otomatis:
   - Membuat transaksi inventory (tipe: `adjustment`, referensi: `initial_stock`)
   - Memperbarui stok gudang di tabel `inventory_warehouse_stock`
   - Membuat catatan valuasi awal

### Keterbatasan
- ❌ Hanya bekerja untuk **satu gudang** (gudang default)
- ❌ Tidak ada jurnal akuntansi yang dibuat
- ❌ Tidak dapat menambahkan stok awal ke beberapa gudang dalam satu langkah
- ❌ Tidak dapat mengubah gudang setelah pembuatan item untuk stok awal

### Contoh
```
Item: MAJUN COLOUR (MJN)
Gudang Default: Main Warehouse
Stok Awal: 50 unit
Harga Beli: Rp 8,500,000
Hasil: 50 unit ditambahkan ke Main Warehouse saja
```

---

## Metode 2: Dokumen Goods Receipt (GR) (DIREKOMENDASIKAN)

### Kapan Menggunakan
- ✅ Setup inventory awal untuk **beberapa gudang**
- ✅ Akuntansi yang benar dengan jurnal akuntansi
- ✅ Beberapa item dalam satu dokumen
- ✅ Pelacakan per gudang
- ✅ Alur kerja persetujuan untuk kontrol

### Prasyarat
1. **Item inventory** harus dibuat terlebih dahulu
2. **Gudang** harus disiapkan
3. **Kategori Produk** harus memiliki pemetaan akun yang dikonfigurasi
4. **Izin pengguna**: `gr-gi.create` dan `gr-gi.approve`

### Langkah-langkah

#### Langkah 1: Akses Modul GR/GI
1. Navigasi ke: **Inventory** → **GR/GI** (atau **Goods Receipt/Issue**)
2. Klik **"Create New"** atau **"Add Document"**
3. Pilih tipe dokumen: **Goods Receipt**

#### Langkah 2: Isi Header Dokumen

**Field Wajib:**

1. **Tipe Dokumen**: **Goods Receipt** (sudah dipilih)
2. **Tujuan**: Pilih tujuan yang sesuai
   - **"Found Inventory"** - Untuk inventory awal yang ditemukan saat setup (DIREKOMENDASIKAN untuk setup awal)
   - **"Sample Received"** - Jika item diterima sebagai sampel
   - **"Customer Return"** - Jika item dikembalikan oleh pelanggan
   - **"Donation Received"** - Jika item diterima sebagai donasi
   - **"Consignment Received"** - Jika item diterima secara konsinyasi
   - **"Transfer In"** - Jika item ditransfer dari lokasi lain
   - **Catatan**: Tujuan mempengaruhi pemetaan akun untuk jurnal akuntansi

3. **Gudang** *: Pilih gudang untuk entri stok awal ini
   - Gudang yang tersedia dalam sistem:
     - **Main Warehouse** (WH001)
     - **Branch Warehouse** (WH002)
     - **APS LOGISTIK** (WH003)
     - **BOGOR** (WH004)
   - **Penting**: Setiap dokumen GR hanya untuk **satu gudang**
   - Untuk menambahkan stok ke beberapa gudang, buat **dokumen GR terpisah** untuk setiap gudang

4. **Tanggal Transaksi** *: Masukkan tanggal entri inventory awal
   - Gunakan tanggal aktual ketika inventory dihitung/dicatat
   - Ini mempengaruhi periode akuntansi dan pelaporan

5. **Nomor Referensi** (Opsional): Referensi eksternal
   - Contoh: "INITIAL-2025-001", "OPENING-BALANCE-2025"
   - Berguna untuk melacak dokumen setup awal

6. **Catatan** (Opsional): Informasi tambahan
   - Contoh: "Setup inventory awal - Saldo pembuka per 2025-01-01"
   - Dokumentasikan mengapa stok awal ini ada

#### Langkah 3: Tambahkan Baris Dokumen

Untuk setiap item yang ingin ditambahkan ke gudang ini:

1. Klik tombol **"Add Line"** atau **"Add Item"**
2. Isi detail baris:
   - **Item**: Pilih item inventory dari dropdown
   - **Kuantitas**: Masukkan kuantitas untuk gudang ini
   - **Harga Unit**: Masukkan biaya per unit (mempengaruhi valuasi inventory)
     - Gunakan biaya pembelian aktual jika diketahui
     - Gunakan biaya rata-rata jika beberapa pembelian
     - Gunakan biaya estimasi jika biaya tepat tidak diketahui
   - **Catatan** (Opsional): Catatan khusus baris
     - Contoh: "Stok awal dari penghitungan gudang"

3. Ulangi untuk semua item di gudang ini
4. Sistem menghitung **Total Amount** secara otomatis

**Contoh Baris Item:**
```
Baris 1:
  Item: MAJUN COLOUR (MJN)
  Kuantitas: 50
  Harga Unit: [Masukkan harga beli aktual]
  Catatan: Stok awal - Main Warehouse

Baris 2:
  Item: [Pilih item lain]
  Kuantitas: 30
  Harga Unit: [Masukkan harga beli aktual]
  Catatan: Stok awal - Main Warehouse

Baris 3:
  Item: [Pilih item lain]
  Kuantitas: 10
  Harga Unit: [Masukkan harga beli aktual]
  Catatan: Stok awal - Main Warehouse
```

**Kategori Produk yang Tersedia dalam Sistem:**
- Stationery
- Electronics
- Welding
- Electrical
- Otomotif
- Lifting Tools
- Consumables
- Chemical
- Bolt Nut
- Safety
- Tools

#### Langkah 4: Simpan sebagai Draft
1. Tinjau semua informasi
2. Klik **"Save"** atau **"Save Draft"**
3. Status dokumen: **Draft**
4. Anda dapat mengedit sebelum mengirimkan

#### Langkah 5: Kirim untuk Persetujuan
1. Buka dokumen draft
2. Tinjau semua detail dengan hati-hati
3. Klik **"Submit for Approval"**
4. Status dokumen berubah menjadi **"Pending Approval"**
5. Dokumen tidak dapat diedit setelah pengiriman

#### Langkah 6: Setujui Dokumen
1. **Penyetuju** (pengguna dengan izin `gr-gi.approve`) membuka dokumen
2. Meninjau semua detail:
   - Pilihan gudang
   - Item dan kuantitas
   - Harga unit
   - Total jumlah
3. Klik tombol **"Approve"**
4. Sistem secara otomatis:
   - ✅ Memperbarui stok gudang (tabel `inventory_warehouse_stock`)
   - ✅ Membuat transaksi inventory (tabel `inventory_transactions`)
   - ✅ Membuat jurnal akuntansi (Debit: Akun Inventory, Kredit: Akun Biaya/Lainnya)
   - ✅ Memperbarui valuasi inventory
   - ✅ Mengubah status menjadi **"Approved"**

### Dampak Akuntansi

Ketika dokumen GR disetujui, jurnal akuntansi dibuat:

**Untuk Goods Receipt:**
- **Debit**: Akun Inventory (dari kategori produk item)
- **Kredit**: Akun Biaya/Lainnya (berdasarkan pemetaan tujuan)

**Contoh:**
```
Dokumen GR: Found Inventory, Gudang: Main Warehouse
Item: MAJUN COLOUR (Kategori: Consumables)
Kuantitas: 50, Harga Unit: Rp 8,500,000

Jurnal yang Dibuat:
  Debit:  Persediaan Consumables (1.1.3.01.07)  Rp 425,000,000
  Kredit: Saldo Pembuka / Biaya Found Inventory  Rp 425,000,000
```

**Akun Inventory yang Tersedia:**
- Persediaan Stationery (1.1.3.01.01)
- Persediaan Electronics (1.1.3.01.02)
- Persediaan Welding (1.1.3.01.03)
- Persediaan Electrical (1.1.3.01.04)
- Persediaan Otomotif (1.1.3.01.05)
- Persediaan Lifting Tools (1.1.3.01.06)
- Persediaan Consumables (1.1.3.01.07)
- Persediaan Chemical (1.1.3.01.08)
- Persediaan Bolt Nut (1.1.3.01.09)
- Persediaan Safety (1.1.3.01.10)
- Persediaan Tools (1.1.3.01.11)

### Setup Beberapa Gudang

Untuk menambahkan inventory awal ke **beberapa gudang**, buat **dokumen GR terpisah**:

**Contoh Alur Kerja:**
1. **GR-001**: Main Warehouse (WH001)
   - Item 1: 50 unit
   - Item 2: 30 unit
   - Item 3: 10 unit

2. **GR-002**: Branch Warehouse (WH002)
   - Item 1: 20 unit
   - Item 2: 15 unit
   - Item 3: 5 unit

3. **GR-003**: APS LOGISTIK (WH003)
   - Item 1: 30 unit
   - Item 2: 20 unit

4. **GR-004**: BOGOR (WH004)
   - Item 1: 25 unit
   - Item 2: 15 unit

Setiap dokumen GR:
- ✅ Memperbarui stok untuk gudang spesifiknya
- ✅ Membuat jurnal akuntansi terpisah
- ✅ Mempertahankan audit trail yang benar

### Praktik Terbaik

- ✅ **Buat satu dokumen GR per gudang** untuk kejelasan
- ✅ **Gunakan tujuan yang konsisten** (misalnya, "Found Inventory" untuk semua setup awal)
- ✅ **Masukkan harga unit yang akurat** untuk valuasi inventory yang benar
- ✅ **Tambahkan catatan yang jelas** menjelaskan sumber inventory awal
- ✅ **Gunakan nomor referensi** untuk melacak dokumen setup awal
- ✅ **Tinjau sebelum persetujuan** - dokumen yang disetujui tidak dapat diedit
- ✅ **Verifikasi jurnal akuntansi** setelah persetujuan untuk memastikan akun yang benar

---

## Metode 3: Penyesuaian Stok (PENGGUNAAN TERBATAS)

### Kapan Menggunakan
- Koreksi stok cepat
- Penyesuaian kecil pada item yang ada
- **Catatan**: Implementasi saat ini **TIDAK** mendukung pemilihan gudang

### Keterbatasan Saat Ini
⚠️ **Penting**: Fitur penyesuaian stok saat ini **TIDAK** memungkinkan pemilihan gudang. Ini menyesuaikan **stok item keseluruhan** tetapi tidak memperbarui stok per gudang (tabel `inventory_warehouse_stock`).

### Langkah-langkah (Implementasi Saat Ini)

#### Langkah 1: Akses Halaman Detail Item
1. Navigasi ke: **Inventory** → Cari item
2. Klik pada nama item atau tombol **"View"**

#### Langkah 2: Buka Modal Penyesuaian Stok
1. Klik tombol **"Adjust Stock"** (biasanya ikon +/-)
2. Form modal terbuka

#### Langkah 3: Masukkan Detail Penyesuaian
1. **Tipe Penyesuaian**:
   - **Tambah Stok**: Tambahkan item
   - **Kurangi Stok**: Hapus item

2. **Kuantitas**: Masukkan kuantitas untuk disesuaikan

3. **Biaya Unit**: Masukkan biaya per unit
   - Diisi sebelumnya dengan harga beli item
   - Dapat dimodifikasi

4. **Catatan**: Jelaskan penyesuaian
   - Contoh: "Entri stok awal"

#### Langkah 4: Kirim Penyesuaian
1. Klik tombol **"Adjust Stock"**
2. Sistem membuat transaksi penyesuaian
3. Memperbarui valuasi item
4. **Catatan**: **TIDAK** memperbarui stok gudang

### Rekomendasi
**JANGAN gunakan metode ini untuk setup inventory awal** karena:
- ❌ Tidak ada pemilihan gudang
- ❌ Tidak memperbarui tabel `inventory_warehouse_stock`
- ❌ Tidak ada jurnal akuntansi
- ❌ Audit trail terbatas

**Gunakan Metode 2 (GR/GI) sebagai gantinya** untuk setup inventory awal yang benar.

---

## Alur Kerja Setup Inventory Awal Lengkap

### Proses yang Direkomendasikan untuk Beberapa Gudang

#### Fase 1: Persiapan
1. ✅ Pastikan semua **gudang** telah dibuat
2. ✅ Pastikan semua **item inventory** telah dibuat
3. ✅ Verifikasi **kategori produk** memiliki pemetaan akun
4. ✅ Pastikan pengguna memiliki izin `gr-gi.create` dan `gr-gi.approve`

#### Fase 2: Penghitungan Inventory Fisik
1. Lakukan penghitungan fisik untuk setiap gudang
2. Catat kuantitas per item per gudang
3. Catat biaya unit (harga beli)

#### Fase 3: Entri Data
Untuk setiap gudang:

1. **Buat Dokumen GR**
   - Tipe Dokumen: Goods Receipt
   - Tujuan: Found Inventory (atau tujuan yang sesuai)
   - Gudang: [Pilih gudang]
   - Tanggal: [Tanggal penghitungan inventory]

2. **Tambahkan Semua Item**
   - Tambahkan baris untuk setiap item dengan kuantitas dan harga unit
   - Verifikasi kuantitas sesuai dengan penghitungan fisik

3. **Simpan dan Kirim**
   - Simpan sebagai Draft
   - Tinjau dengan hati-hati
   - Kirim untuk Persetujuan

4. **Setujui**
   - Penyetuju meninjau dan menyetujui
   - Sistem memperbarui stok gudang
   - Jurnal akuntansi dibuat

#### Fase 4: Verifikasi
1. **Periksa Stok Gudang**
   - Navigasi ke: **Inventory** → Detail Item → Bagian Warehouse Stock
   - Verifikasi kuantitas sesuai dengan penghitungan fisik

2. **Periksa Jurnal Akuntansi**
   - Navigasi ke: **Accounting** → Journals
   - Verifikasi jurnal akuntansi dibuat dengan benar
   - Verifikasi pemetaan akun benar

3. **Periksa Valuasi Inventory**
   - Navigasi ke: **Inventory** → Valuation Report
   - Verifikasi total nilai inventory sesuai dengan ekspektasi

### Contoh: Setup Lengkap

**Skenario**: Menyiapkan inventory awal untuk 4 gudang

**Gudang 1: Main Warehouse (WH001)**
- GR-2025-001: Found Inventory
  - Item A: 50 unit @ [Harga Aktual]
  - Item B: 30 unit @ [Harga Aktual]
  - Item C: 10 unit @ [Harga Aktual]

**Gudang 2: Branch Warehouse (WH002)**
- GR-2025-002: Found Inventory
  - Item A: 20 unit @ [Harga Aktual]
  - Item B: 15 unit @ [Harga Aktual]
  - Item C: 5 unit @ [Harga Aktual]

**Gudang 3: APS LOGISTIK (WH003)**
- GR-2025-003: Found Inventory
  - Item A: 30 unit @ [Harga Aktual]
  - Item B: 20 unit @ [Harga Aktual]

**Gudang 4: BOGOR (WH004)**
- GR-2025-004: Found Inventory
  - Item A: 25 unit @ [Harga Aktual]
  - Item B: 15 unit @ [Harga Aktual]

**Hasil**:
- ✅ Total Item A: 125 unit di 4 gudang
- ✅ Total Item B: 80 unit di 4 gudang
- ✅ Total Item C: 15 unit di 2 gudang
- ✅ Pelacakan per gudang yang benar
- ✅ Jurnal akuntansi yang benar untuk setiap gudang
- ✅ Audit trail lengkap

---

## Pemecahan Masalah

### Masalah: Dokumen GR Tidak Memperbarui Stok Gudang

**Kemungkinan Penyebab**:
- Dokumen tidak disetujui (hanya dokumen yang disetujui yang memperbarui stok)
- Gudang tidak dipilih
- Item tidak ditemukan

**Solusi**:
1. Verifikasi status dokumen adalah **"Approved"**
2. Periksa pilihan gudang di header dokumen
3. Verifikasi item ada dan aktif
4. Periksa tabel `inventory_warehouse_stock` secara langsung

### Masalah: Jurnal Akuntansi Tidak Dibuat

**Kemungkinan Penyebab**:
- Kategori produk kehilangan pemetaan akun
- Tujuan kehilangan pemetaan akun
- Dokumen tidak disetujui

**Solusi**:
1. Verifikasi kategori produk memiliki akun inventory yang dipetakan
2. Periksa pemetaan akun tujuan GR/GI
3. Pastikan dokumen disetujui (jurnal akuntansi dibuat saat persetujuan)
4. Periksa jurnal akuntansi di Accounting → Journals

### Masalah: Stok Gudang yang Salah Diperbarui

**Kemungkinan Penyebab**:
- Gudang yang salah dipilih dalam dokumen GR
- Beberapa dokumen GR untuk item yang sama

**Solusi**:
1. Tinjau pilihan gudang dokumen GR
2. Periksa semua dokumen GR untuk item tersebut
3. Verifikasi tabel `inventory_warehouse_stock` menunjukkan gudang yang benar
4. Buat dokumen GR koreksi jika diperlukan

### Masalah: Tidak Dapat Menyetujui Dokumen GR

**Kemungkinan Penyebab**:
- Pengguna tidak memiliki izin `gr-gi.approve`
- Dokumen tidak dalam status "Pending Approval"
- Informasi wajib hilang

**Solusi**:
1. Hubungi administrator untuk izin persetujuan
2. Verifikasi status dokumen
3. Periksa semua field wajib terisi
4. Pastikan dokumen dikirim (bukan draft)

---

## Referensi Cepat

### Alur Kerja Dokumen GR
```
Buat → Simpan Draft → Kirim → Setujui → Stok Diperbarui + Jurnal Dibuat
```

### Lokasi Penting
- **Buat GR**: Inventory → GR/GI → Create New
- **Lihat Daftar GR**: Inventory → GR/GI
- **Periksa Stok Gudang**: Inventory → Detail Item → Warehouse Stock
- **Lihat Jurnal**: Accounting → Journals

### Izin yang Diperlukan
- `gr-gi.create`: Membuat dokumen GR
- `gr-gi.approve`: Menyetujui dokumen GR
- `inventory.view`: Melihat item inventory
- `warehouse.view`: Melihat gudang

### Catatan Penting
- ✅ **Satu dokumen GR = Satu gudang**
- ✅ **Beberapa item** dapat dalam satu dokumen GR
- ✅ **Persetujuan diperlukan** untuk pembaruan stok dan jurnal akuntansi
- ✅ **Tidak dapat diedit** setelah persetujuan
- ✅ **Jurnal akuntansi** dibuat secara otomatis saat persetujuan
- ✅ **Stok gudang** diperbarui secara otomatis saat persetujuan

---

## Ringkasan

**Untuk Setup Inventory Awal:**

1. ✅ **Gunakan dokumen GR (Goods Receipt)** - Metode yang direkomendasikan
2. ✅ **Buat satu GR per gudang** - Organisasi yang jelas
3. ✅ **Gunakan tujuan "Found Inventory"** - Sesuai untuk setup awal
4. ✅ **Masukkan harga unit yang akurat** - Valuasi yang benar
5. ✅ **Setujui dokumen** - Diperlukan untuk pembaruan stok
6. ✅ **Verifikasi hasil** - Periksa stok gudang dan jurnal

**Hindari:**
- ❌ Penyesuaian Stok (tidak ada dukungan gudang)
- ❌ Stok Awal saat pembuatan item (hanya satu gudang)
- ❌ Pembaruan database manual (melewati sistem)

---

**Akhir Manual**

*Untuk bantuan tambahan, lihat:*
- *Manual Modul Inventory: `docs/manuals/inventory-module-manual-id.md`*
- *Manual Hal-Hal Pertama yang Harus Dilakukan: `docs/manuals/first-things-to-do-manual-id.md`*
