# Manual Modul Aset & Penyusutan

## Daftar Isi

1. [Pendahuluan](#pendahuluan)
2. [Memulai](#memulai)
3. [Kategori Aset](#kategori-aset)
4. [Manajemen Aset (CRUD)](#manajemen-aset-crud)
5. [Proses Penyusutan](#proses-penyusutan)
6. [Pelepasan Aset](#pelepasan-aset)
7. [Mutasi Aset](#mutasi-aset)
8. [Impor & Ekspor](#impor--ekspor)
9. [Kualitas Data](#kualitas-data)
10. [Operasi Massal](#operasi-massal)
11. [Laporan](#laporan)
12. [Referensi Izin](#referensi-izin)
13. [Tugas Umum](#tugas-umum)
14. [Pemecahan Masalah](#pemecahan-masalah)
15. [Referensi Cepat](#referensi-cepat)

---

## Pendahuluan

### Apa itu Modul Aset & Penyusutan?

Modul Aset & Penyusutan mengelola siklus hidup lengkap aset tetap — mulai dari perolehan, penyusutan, mutasi, hingga pelepasan. Modul ini terintegrasi dengan Buku Besar untuk membuat jurnal otomatis untuk beban penyusutan, akumulasi penyusutan, serta keuntungan atau kerugian pelepasan aset.

### Siapa yang Menggunakan Modul Ini?

- **Manajer aset**: Membuat dan memelihara catatan aset, melacak lokasi dan penanggung jawab.
- **Staf akuntansi**: Menjalankan penyusutan bulanan, posting ke GL, memproses pelepasan dengan perhitungan laba/rugi.
- **IT / fasilitas**: Mencatat mutasi aset antar departemen dan lokasi.
- **Manajemen**: Meninjau daftar aset, jadwal penyusutan, dan ringkasan pelepasan.
- **Auditor**: Memverifikasi kualitas data aset, riwayat mutasi, dan dokumentasi pelepasan.

### Manfaat Utama

- Siklus hidup aset lengkap: perolehan → penyusutan → mutasi → pelepasan.
- Posting jurnal otomatis untuk penyusutan dan transaksi pelepasan.
- Dukungan metode penyusutan straight-line (declining balance direncanakan).
- Impor/ekspor aset melalui Excel/CSV dengan validasi.
- Pemeriksaan kualitas data bawaan: deteksi duplikat, data tidak lengkap, validasi konsistensi.
- Izin granular (20 izin terpisah) untuk kontrol akses berbasis peran.
- Pelacakan mutasi aset dengan alur persetujuan dan riwayat lengkap.
- Kalkulasi otomatis laba/rugi pelepasan dengan integrasi jurnal.

### Navigasi

Semua fitur aset dapat diakses dari grup menu sidebar: **Fixed Assets**.

| Menu | Jalur | Deskripsi |
|------|------|-----------|
| Asset Categories | `Assets > Asset Categories` | Mengelola data master kategori aset |
| Assets | `Assets > Assets` | Daftar aset utama dengan CRUD lengkap |
| Depreciation Runs | `Assets > Depreciation Runs` | Menjalankan penyusutan bulanan |
| Asset Disposals | `Assets > Asset Disposals` | Memproses pelepasan (jual, buang, hilang) |
| Asset Movements | `Assets > Asset Movements` | Mencatat mutasi antar lokasi/penanggung jawab |
| Asset Import | `Assets > Asset Import` | Impor aset dari Excel/CSV |
| Data Quality | `Assets > Data Quality` | Periksa duplikat, inkonsistensi |
| Bulk Operations | `Assets > Bulk Operations` | Update massal, pelepasan massal |

---

## Memulai

### Prasyarat

Sebelum menggunakan modul Aset, pastikan hal berikut sudah dikonfigurasi:

1. **Chart of Accounts** (Bagan Akun) sudah disiapkan dengan akun aset, akun akumulasi penyusutan, akun beban penyusutan, dan akun laba/rugi pelepasan.
2. **Kategori Aset** sudah dibuat dengan pemetaan akun COA yang benar (lihat [Kategori Aset](#kategori-aset)).
3. **Business Partners** (vendor/pemasok) sudah dikonfigurasi jika aset dibeli dari pemasok eksternal.
4. **Departemen** dan **Proyek** sudah dikonfigurasi jika Anda ingin menetapkan aset ke dimensi.
5. **Izin pengguna** sudah diberikan oleh administrator (lihat [Referensi Izin](#referensi-izin)).

### Alur Kerja Umum

1. **Atur Kategori Aset** dengan pemetaan akun COA dan parameter penyusutan default.
2. **Buat catatan aset** untuk setiap aset tetap dengan biaya perolehan, masa manfaat, dan metode penyusutan.
3. **Jalankan penyusutan bulanan** untuk menghitung dan memposting beban penyusutan ke GL.
4. **Catat mutasi aset** ketika aset berpindah lokasi atau penanggung jawab.
5. **Proses pelepasan** ketika aset dijual, dibuang, atau hilang — otomatis menghitung laba/rugi.
6. **Buat laporan** untuk daftar aset, jadwal penyusutan, dan ringkasan pelepasan.

---

## Kategori Aset

### Apa itu Kategori Aset?

Kategori Aset adalah fondasi modul aset tetap. Setiap kategori menentukan:
- Metode penyusutan default dan masa manfaat
- Apakah aset dalam kategori ini dapat disusutkan
- Pemetaan Chart of Account (COA) untuk semua jurnal terkait
- Kebijakan nilai residu default

### Kategori Bawaan

Sistem dilengkapi dengan 6 kategori yang sudah diisi:

| Kode | Nama | Masa Manfaat | Dapat Disusutkan? |
|------|------|-------------|:---:|
| LAND | Tanah | N/A | Tidak |
| BUILDINGS | Bangunan | 240 bulan (20 tahun) | Ya |
| VEHICLES | Kendaraan | 60 bulan (5 tahun) | Ya |
| EQUIPMENT | Peralatan | 48 bulan (4 tahun) | Ya |
| FURNITURE | Furniture & Perlengkapan | 36 bulan (3 tahun) | Ya |
| IT_EQUIPMENT | Peralatan TI | 36 bulan (3 tahun) | Ya |

### Mengelola Kategori

**Membuat kategori baru:**
1. Buka **Assets > Asset Categories**.
2. Klik **Add New Category** (modal terbuka melalui DataTables).
3. Isi:
   - **Kode**: Pengenal pendek unik (mis. `MESIN`).
   - **Nama**: Nama tampilan (mis. "Mesin").
   - **Deskripsi**: Deskripsi opsional.
   - **Masa Manfaat Default (bulan)**: Kosongkan/null untuk kategori non-depreciable.
   - **Metode Default**: `straight_line` (declining_balance direncanakan).
   - **Non-Depreciable**: Centang jika aset dalam kategori ini tidak pernah disusutkan (mis. Tanah).
   - **Kebijakan Nilai Residu**: Persentase atau 0.
   - **Pemetaan COA**: Pilih akun yang benar untuk:
     - Akun Aset (neraca)
     - Akun Akumulasi Penyusutan (kontra-aset)
     - Akun Beban Penyusutan (laba rugi)
     - Akun Keuntungan Pelepasan (laba rugi)
     - Akun Kerugian Pelepasan (laba rugi)
4. Klik **Save**.

**Mengedit kategori:**
- Klik aksi edit pada baris kategori, ubah field, dan simpan.

**Menghapus kategori:**
- Anda hanya dapat menghapus kategori jika tidak ada aset yang ditetapkan ke kategori tersebut. Sistem akan memblokir penghapusan jika masih ada aset yang terkait.

### Pemetaan Akun COA

| Jenis Akun | Neraca / Laba Rugi | Contoh Akun |
|-----------|---------------------|-------------|
| Akun Aset | Neraca (Aset) | 1.2.1.01 — Tanah |
| Akumulasi Penyusutan | Neraca (Kontra-Aset) | 1.2.1.03 — Akum. Peny. Bangunan |
| Beban Penyusutan | Laba Rugi (Beban) | 6.2.9 — Biaya Penyusutan |
| Keuntungan Pelepasan | Laba Rugi (Pendapatan) | 7.1.1 — Pendapatan Sewa |
| Kerugian Pelepasan | Laba Rugi (Beban) | 7.2.3 — Kerugian Penjualan Aset |

---

## Manajemen Aset (CRUD)

### Daftar Aset

Buka **Assets > Assets** untuk melihat daftar aset lengkap.

DataTable menampilkan:
- **Kode**: Kode unik aset.
- **Nama**: Nama aset.
- **Kategori**: Kategori aset yang ditetapkan.
- **Biaya Perolehan**: Harga beli awal.
- **Nilai Buku Saat Ini**: Biaya perolehan dikurangi akumulasi penyusutan.
- **Akumulasi Penyusutan**: Total penyusutan sampai saat ini.
- **Info Penyusutan**: Sisa bulan dan tarif penyusutan.
- **Status**: active, disposed, dll.
- **Dimensi**: Proyek dan Departemen yang ditetapkan.

**Filter tersedia:**
- Berdasarkan Kategori
- Berdasarkan Status
- Berdasarkan Proyek
- Berdasarkan Departemen

**Aksi per baris:**
- **View**: Lihat detail aset lengkap, entri penyusutan, riwayat mutasi.
- **Edit**: Perbarui informasi aset.
- **Delete**: Hapus aset (hanya jika tidak ada entri penyusutan).

### Membuat Aset

1. Buka **Assets > Assets** dan klik **Create Asset**.
2. Isi field yang diperlukan:

| Field | Deskripsi | Wajib |
|-------|-----------|:---:|
| Kode Aset | Pengenal unik untuk aset (mis. `PC-001`) | Ya |
| Nama Aset | Nama deskriptif (mis. "Dell OptiPlex 7090") | Ya |
| Deskripsi | Catatan tambahan tentang aset | Tidak |
| Nomor Seri | Nomor seri pabrikan | Tidak |
| Kategori | Pilih dari kategori aset yang tersedia | Ya |
| Biaya Perolehan | Harga beli awal (mata uang dasar) | Ya |
| Nilai Residu | Estimasi nilai sisa di akhir masa manfaat | Tidak (default 0) |
| Metode Penyusutan | `straight_line` (metode lain direncanakan) | Ya |
| Masa Manfaat (bulan) | Total periode penyusutan | Ya |
| Tanggal Mulai Pakai | Tanggal aset mulai digunakan (penyusutan dimulai dari sini) | Ya |
| Status | Biasanya `active` untuk aset baru | Ya |
| Proyek | Dimensi proyek opsional | Tidak |
| Departemen | Dimensi departemen opsional | Tidak |
| Business Partner (Vendor) | Pemasok yang menjual aset | Tidak |
| Purchase Invoice | Tautan ke faktur pembelian | Tidak |

3. Klik **Save**.

### Melihat Aset

Klik **View** pada baris aset untuk melihat:
- **Detail Aset**: Semua field termasuk nilai terhitung (biaya yang dapat disusutkan, penyusutan bulanan, sisa masa manfaat).
- **Tabel Entri Penyusutan**: Semua entri penyusutan untuk aset ini, dikelompokkan berdasarkan proses penyusutan.
- **Riwayat Mutasi**: Semua mutasi sebelumnya (dari/ke lokasi, penanggung jawab, tanggal).
- **Riwayat Pelepasan**: Semua pelepasan (termasuk yang dibatalkan).

### Mengedit Aset

1. Klik **Edit** pada baris aset.
2. Ubah field apa pun. Catatan:
   - Mengubah `acquisition_cost`, `salvage_value`, atau `life_months` akan mempengaruhi perhitungan penyusutan di masa mendatang.
   - Mengubah `category_id` akan mengubah pemetaan COA untuk proses penyusutan berikutnya.
   - `current_book_value` dihitung otomatis dan tidak dapat diedit langsung.
3. Klik **Update Asset** untuk menyimpan perubahan.

### Menghapus Aset

Anda dapat menghapus aset **hanya jika tidak memiliki entri penyusutan**. Setelah penyusutan diposting, aset tidak dapat dihapus — harus melalui proses pelepasan.

- Jika dapat dihapus: Klik **Delete**, konfirmasi prompt.
- Jika tidak dapat dihapus: Gunakan alur **Asset Disposal** sebagai gantinya.

---

## Proses Penyusutan

### Ikhtisar

Proses penyusutan adalah operasi akuntansi inti dari modul ini. Setiap proses:
- Mencakup **periode** tertentu (bulan, mis. `2024-01`).
- Menghitung penyusutan untuk semua aset aktif yang dapat disusutkan.
- Mengelompokkan entri berdasarkan kategori + dimensi untuk posting jurnal.
- Bergerak melalui alur kerja: **Draft** → **Posted** → (opsional) **Reversed**.

### Melihat Proses Penyusutan

Buka **Assets > Depreciation Runs**.

DataTable menampilkan:
- **Periode**: Bulan dan tahun proses penyusutan.
- **Total Penyusutan**: Jumlah semua entri dalam proses ini.
- **Status**: Draft, Posted, atau Reversed.
- **Dibuat Oleh**: Pengguna yang membuat proses.
- **Diposting Pada**: Timestamp saat diposting.
- **Poster**: Pengguna yang memposting/menjalankan proses.

### Membuat Proses Penyusutan

1. Buka **Assets > Depreciation Runs**.
2. Klik **New Depreciation Run**.
3. Pilih **Periode** (bulan). Hanya satu proses yang diizinkan per periode.
4. Sistem menampilkan semua aset yang memenuhi syarat:
   - Status harus `active`.
   - Kategori harus dapat disusutkan (`non_depreciable = false`).
   - Aset belum sepenuhnya disusutkan.
   - Aset belum dilepaskan.
5. Tinjau jumlah yang dihitung per aset.
6. Klik **Calculate** untuk membuat entri penyusutan draft.

### Logika Perhitungan Penyusutan (Straight-Line)

Untuk setiap aset yang memenuhi syarat:
1. **Biaya yang Dapat Disusutkan** = `biaya_perolehan - nilai_residu`
2. **Penyusutan Bulanan** = `biaya_yang_dapat_disusutkan / masa_manfaat_bulan`
3. **Prorata bulan pertama**: Jika `tanggal_mulai_pakai.hari > 1`, bulan pertama diprorata.

### Memposting Proses Penyusutan

1. Di halaman detail proses (atau dari daftar), klik **Post**.
2. Sistem akan:
   - Mengelompokkan semua entri penyusutan berdasarkan **kategori** dan **dimensi** (proyek + departemen).
   - Membuat satu jurnal per kelompok:
     - **Debit**: Akun Beban Penyusutan (dari pemetaan COA kategori)
     - **Kredit**: Akun Akumulasi Penyusutan (dari pemetaan COA kategori)
   - Memperbarui `accumulated_depreciation` dan `current_book_value` setiap aset.
   - Menandai status proses sebagai `posted`.
3. Jurnal ditautkan kembali ke proses penyusutan untuk jejak audit.

### Membatalkan Proses Penyusutan

Jika proses penyusutan diposting secara keliru:
1. Klik **Reverse** pada proses yang sudah diposting.
2. Sistem akan:
   - Membuat jurnal pembalik (kredit beban, debit akum. peny.).
   - Mengembalikan `accumulated_depreciation` dan `current_book_value` aset ke kondisi sebelum posting.
   - Menandai status proses sebagai `reversed`.
3. Proses yang dibatalkan **tidak dapat** diposting ulang. Anda harus membuat proses baru untuk periode tersebut.

### Jadwal Penyusutan

Dari halaman detail aset atau tampilan proses penyusutan, Anda dapat melihat **Jadwal Penyusutan** — proyeksi penyusutan bulanan ke depan hingga aset sepenuhnya disusutkan.

---

## Pelepasan Aset

### Ikhtisar

Pelepasan Aset menangani akhir masa pakai atau penghapusan aset. Sistem mendukung 5 jenis pelepasan:
- **Sale (Penjualan)**: Aset dijual dengan hasil penjualan.
- **Scrapping (Pembuangan)**: Aset dibuang (hasil nol).
- **Loss/Theft (Kehilangan)**: Aset hilang atau dicuri.
- **Insurance Claim (Klaim Asuransi)**: Dipulihkan melalui asuransi.
- **Transfer Out**: Aset ditransfer ke entitas lain.

Setiap pelepasan menghitung **laba atau rugi** secara otomatis dengan membandingkan:
- **Nilai Buku** = `biaya_perolehan - akumulasi_penyusutan`
- **Hasil** = harga jual atau pemulihan asuransi
- **Laba** = `hasil - nilai_buku` (jika positif)
- **Rugi** = `nilai_buku - hasil` (jika positif)

### Membuat Pelepasan

1. Buka **Assets > Asset Disposals** dan klik **New Disposal**.
2. Pilih **Aset** yang akan dilepaskan. Hanya aset aktif yang belum dilepaskan yang tersedia.
3. Isi:

| Field | Deskripsi | Wajib |
|-------|-----------|:---:|
| Aset | Aset yang dilepaskan | Ya |
| Tanggal Pelepasan | Tanggal pelepasan | Ya |
| Jenis Pelepasan | Sale, Scrapping, Loss/Theft, Insurance Claim, Transfer Out | Ya |
| Hasil | Jumlah yang diterima (untuk jenis Sale/Insurance) | Untuk Sale/Insurance |
| Alasan Pelepasan | Deskripsi mengapa aset dilepaskan | Tidak |
| Pembeli/Penerima | Entitas yang menerima aset | Tidak |

4. Sistem menampilkan:
   - Biaya Awal
   - Akumulasi Penyusutan (sampai saat ini)
   - Nilai Buku (dihitung otomatis)
   - Hasil
   - **Laba / (Rugi)** — dihitung otomatis

### Memposting Pelepasan

1. Klik **Post** pada pelepasan.
2. Sistem membuat jurnal:
   - **Debit**: Akumulasi Penyusutan (hapus dari pembukuan)
   - **Debit**: Kas/Bank (jika ada hasil)
   - **Kredit**: Akun Aset (hapus dari pembukuan)
   - **Kredit/Debit**: Laba/Rugi Pelepasan (entri penyeimbang)
3. Status aset berubah menjadi `disposed`.
4. `disposal_date` aset ditetapkan.

### Membatalkan Pelepasan

Jika pelepasan diposting secara keliru:
1. Klik **Reverse** pada pelepasan yang sudah diposting.
2. Sistem akan:
   - Membuat jurnal pembalik.
   - Mengembalikan aset ke status `active`.
   - Mengembalikan akumulasi penyusutan dan nilai buku.
3. Status pelepasan diatur ke `reversed`.

### Status Alur Kerja Pelepasan

| Status | Deskripsi | Aksi yang Diizinkan |
|--------|-----------|---------------------|
| Draft | Pelepasan baru dibuat | Edit, Delete, Post |
| Posted | Jurnal pelepasan diposting ke GL | View, Reverse |
| Reversed | Pelepasan dibatalkan (aset dipulihkan) | View saja |

---

## Mutasi Aset

### Ikhtisar

Mutasi Aset mencatat pemindahan fisik atau penanggung jawab aset. Sistem melacak 5 jenis mutasi:
- **Department Transfer**: Aset pindah antar departemen.
- **Location Change**: Aset pindah ke lokasi fisik baru.
- **Custodian Change**: Tanggung jawab berpindah ke orang lain.
- **Temporary Loan**: Aset dipinjamkan sementara.
- **Return from Loan**: Aset dikembalikan dari peminjaman.

### Membuat Mutasi

1. Buka **Assets > Asset Movements** dan klik **New Movement**.
2. Pilih **Aset** yang akan dipindahkan.
3. Isi:

| Field | Deskripsi | Wajib |
|-------|-----------|:---:|
| Aset | Aset yang dipindahkan | Ya |
| Tanggal Mutasi | Tanggal mutasi | Ya |
| Jenis Mutasi | Salah satu dari 5 jenis di atas | Ya |
| Dari Lokasi | Lokasi awal | Ya |
| Ke Lokasi | Lokasi tujuan | Ya (kecuali loan return) |
| Dari Penanggung Jawab | Penanggung jawab awal | Tidak |
| Ke Penanggung Jawab | Penanggung jawab baru | Tidak |
| Alasan | Alasan mutasi | Tidak |
| Nomor Referensi | Dibuat otomatis (mis. `MOV-2024-001`) | Otomatis |

4. Klik **Save**.

### Alur Persetujuan

Mutasi dapat melalui proses persetujuan:
- **Draft**: Baru dibuat, belum ditindaklanjuti.
- **Approved**: Disetujui oleh seseorang dengan izin `assets.movement.approve`.
- **Completed**: Mutasi diselesaikan (lokasi aset diperbarui).
- **Cancelled**: Mutasi dibatalkan tanpa efek.

**Untuk menyetujui mutasi**: Klik **Approve** pada baris mutasi (memerlukan izin `assets.movement.approve`).

### Riwayat Mutasi

Untuk aset apa pun, lihat riwayat mutasi lengkapnya:
1. Buka **Assets > Assets**, klik **View** pada aset.
2. Gulir ke bagian **Movement History**.
3. Atau buka **Assets > Asset Movements** dan gunakan filter aset.

---

## Impor & Ekspor

### Mengimpor Aset

Fitur impor aset memungkinkan pembuatan dan pembaruan aset secara massal melalui Excel/CSV.

**Alur kerja impor:**

1. Buka **Assets > Asset Import**.

2. **Unduh Template**: Klik **Download Template** untuk mendapatkan template Excel dengan header kolom yang benar.

3. **Siapkan data Anda**: Isi template mengikuti aturan berikut:

| Kolom | Format | Catatan |
|-------|--------|---------|
| Code | String | Kode aset unik (wajib) |
| Name | String | Nama aset (wajib) |
| Description | String | Opsional |
| Serial Number | String | Opsional |
| Category Code | String | Harus cocok dengan kode kategori yang ada |
| Acquisition Cost | Angka | Biaya awal (wajib) |
| Salvage Value | Angka | Default 0 |
| Depreciation Method | `straight_line` | Wajib |
| Life Months | Integer | Periode penyusutan dalam bulan |
| Placed in Service Date | Tanggal `Y-m-d` | Kapan aset mulai digunakan |
| Status | `active` | Biasanya active |
| Project Code | String | Opsional, harus cocok dengan proyek yang ada |
| Department Code | String | Opsional, harus cocok dengan departemen yang ada |
| Business Partner Code | String | Opsional, harus cocok dengan vendor yang ada |

4. **Validasi**: Unggah file dan klik **Validate**. Sistem memeriksa:
   - Semua field wajib ada.
   - Kode kategori ada.
   - Kode proyek/departemen ada (jika disediakan).
   - Kode business partner ada (jika disediakan).
   - Tanggal valid.
   - Kode aset unik (belum ada di sistem).

5. **Perbaiki kesalahan validasi**: Jika ditemukan kesalahan, sistem menunjukkan baris dan kolom mana yang bermasalah. Perbaiki file dan validasi lagi.

6. **Impor**: Setelah validasi lolos, klik **Import** untuk membuat semua aset.

### Pembaruan Massal via Impor

Anda juga dapat menggunakan template impor untuk **memperbarui** aset yang sudah ada:
1. Sertakan `code` aset yang sudah ada.
2. Isi kolom yang ingin diperbarui.
3. Validasi dan impor seperti di atas.

### Ekspor

Daftar aset dapat diekspor melalui bagian **Reports**:
- **Ekspor Daftar Aset** (Excel): Daftar aset lengkap dengan status penyusutan.
- **Ekspor Jadwal Penyusutan** (Excel): Proyeksi penyusutan masa depan.
- **Ekspor Ringkasan Pelepasan** (Excel): Semua pelepasan dengan laba/rugi.

---

## Kualitas Data

### Ikhtisar

Alat Kualitas Data membantu menjaga kebersihan data aset dengan mendeteksi:

1. **Aset Duplikat**: Aset dengan nama, nomor seri, atau kode yang identik atau sangat mirip.
2. **Data Tidak Lengkap**: Aset yang kekurangan field wajib (kategori, biaya, tanggal, metode, masa manfaat).
3. **Masalah Konsistensi**:
   - Aset dengan nilai buku negatif.
   - Aset dengan akumulasi penyusutan melebihi biaya yang dapat disusutkan.
   - Aset dengan tanggal mulai pakai di masa depan yang belum disusutkan.
   - Entri penyusutan yang tidak cocok dengan jumlah bulanan yang diharapkan.
4. **Data Yatim (Orphaned)**: Entri penyusutan atau mutasi yang terkait dengan aset yang sudah dihapus.

### Menggunakan Kualitas Data

1. Buka **Assets > Data Quality**.

2. Dashboard menampilkan jumlah untuk setiap kategori:
   - **Duplikat**: Jumlah grup duplikat potensial.
   - **Tidak Lengkap**: Aset dengan data wajib yang hilang.
   - **Konsistensi**: Anomali dalam perhitungan penyusutan.
   - **Yatim**: Catatan dengan referensi rusak.

3. Klik jumlah mana pun untuk melihat daftar detail.

### Deteksi Duplikat

- Sistem mengelompokkan aset berdasarkan nama yang mirip (case-insensitive, menghilangkan spasi).
- Untuk setiap grup, Anda dapat melihat semua aset yang cocok dan memutuskan mana yang akan disimpan.
- **Tindakan**: Dari halaman detail duplikat, tinjau dan bersihkan catatan duplikat.

### Data Tidak Lengkap

Menampilkan aset yang kekurangan:
- Penetapan kategori
- Biaya perolehan (nol atau null)
- Tanggal mulai pakai
- Metode penyusutan
- Masa manfaat (untuk kategori yang dapat disusutkan)

**Tindakan**: Klik kode aset untuk masuk ke halaman edit dan isi data yang hilang.

### Masalah Konsistensi

Mendeteksi anomali seperti:
- **Terlalu banyak disusutkan**: `akumulasi_penyusutan > biaya_yang_dapat_disusutkan`
- **Nilai buku negatif**: `nilai_buku_saat_ini < 0`
- **Penyusutan hilang**: Aset aktif yang dapat disusutkan tanpa entri penyusutan setelah tanggal mulai pakai.

**Tindakan**: Tinjau setiap masalah. Untuk aset yang terlalu disusutkan, batalkan proses penyusutan berlebih. Untuk penyusutan yang hilang, pastikan aset disertakan dalam proses mendatang.

### Data Yatim

- Entri penyusutan yang menunjuk ke aset yang sudah dihapus.
- Mutasi atau pelepasan dengan referensi aset yang hilang.

**Tindakan**: Ini biasanya tidak berbahaya tetapi dapat dibersihkan oleh administrator database.

---

## Operasi Massal

### Ikhtisar

Operasi Massal memungkinkan Anda melakukan tindakan pada beberapa aset sekaligus.

Buka **Assets > Bulk Operations**.

Operasi yang tersedia:
- **Bulk Update**: Mengubah field (mis. departemen, proyek, status) pada aset yang dipilih.
- **Bulk Disposal**: Melepaskan beberapa aset dalam satu batch.
- **Bulk Recalculate**: Menghitung ulang akumulasi penyusutan untuk aset yang dipilih.

### Cara Menggunakan

1. Pilih aset dengan mencentang kotak centang di daftar aset.
2. Pilih operasi dari halaman Bulk Operations.
3. Ikuti formulir di layar untuk operasi spesifik.
4. Konfirmasi untuk menerapkan perubahan.

---

## Laporan

### Laporan yang Tersedia

Buka **Reports > Assets** (atau gunakan tautan di modul Aset).

| Laporan | Deskripsi |
|---------|-----------|
| **Daftar Aset** | Daftar lengkap semua aset dengan biaya, penyusutan, nilai buku. Dapat difilter berdasarkan kategori, status, proyek, departemen, dan rentang tanggal. Dapat diekspor ke Excel. |
| **Jadwal Penyusutan** | Proyeksi penyusutan bulanan ke depan. Menunjukkan kapan setiap aset akan sepenuhnya disusutkan. |
| **Ringkasan Pelepasan** | Semua pelepasan dalam rentang tanggal, dengan jumlah laba/rugi dan referensi jurnal. |
| **Log Mutasi** | Riwayat mutasi lengkap dengan lokasi dari/ke, penanggung jawab, dan tanggal. |
| **Laporan Beban Penyusutan** | Beban penyusutan per periode, per kategori — berguna untuk penganggaran. |

### Membuat Laporan

1. Buka **Reports > Assets** dan pilih jenis laporan.
2. Atur filter:
   - Rentang tanggal (untuk jadwal, pelepasan, mutasi).
   - Filter kategori.
   - Filter status.
   - Filter proyek/departemen.
3. Klik **Generate**.
4. Tinjau laporan di browser, atau klik **Export** untuk mengunduh sebagai Excel.

---

## Referensi Izin

### Semua 20 Izin Aset

Modul Aset menggunakan 20 izin granular untuk kontrol akses berbasis peran:

| # | String Izin | Mengontrol |
|---|-------------|------------|
| 1 | `assets.view` | Melihat daftar dan detail aset |
| 2 | `assets.create` | Membuat aset baru |
| 3 | `assets.update` | Mengedit aset yang ada |
| 4 | `assets.delete` | Menghapus aset (hanya jika tidak ada penyusutan) |
| 5 | `asset_categories.view` | Melihat daftar kategori aset |
| 6 | `asset_categories.manage` | Membuat, mengedit, menghapus kategori aset |
| 7 | `assets.depreciation.run` | Membuat dan memposting proses penyusutan |
| 8 | `assets.depreciation.reverse` | Membatalkan proses penyusutan yang sudah diposting |
| 9 | `assets.disposal.view` | Melihat catatan pelepasan |
| 10 | `assets.disposal.create` | Membuat catatan pelepasan baru |
| 11 | `assets.disposal.update` | Mengedit catatan pelepasan draft |
| 12 | `assets.disposal.delete` | Menghapus catatan pelepasan draft |
| 13 | `assets.disposal.post` | Memposting pelepasan (membuat jurnal) |
| 14 | `assets.disposal.reverse` | Membatalkan pelepasan yang sudah diposting |
| 15 | `assets.movement.view` | Melihat catatan mutasi |
| 16 | `assets.movement.create` | Membuat catatan mutasi baru |
| 17 | `assets.movement.update` | Mengedit catatan mutasi |
| 18 | `assets.movement.delete` | Menghapus catatan mutasi |
| 19 | `assets.movement.approve` | Menyetujui mutasi yang tertunda |
| 20 | `assets.reports.view` | Mengakses laporan aset |

### Penetapan Peran Default

| Peran | Izin Aset |
|-------|-----------|
| **Superadmin** | Semua 20 izin |
| **Akuntan** | `assets.view`, `asset_categories.view`, `assets.reports.view` |
| **Approver** | `assets.depreciation.run`, `assets.depreciation.reverse`, semua izin pelepasan, semua izin mutasi |
| **Kasir** | Tidak ada |
| **Auditor** | Tidak ada (menggunakan `reports.view` untuk laporan aset) |
| **Staf** | (lihat konfigurasi peran spesifik Anda) |

### Matriks Izin per Fitur

| Fitur | Lihat | Buat | Edit | Hapus | Posting | Batalkan | Setujui |
|-------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Aset** | `assets.view` | `assets.create` | `assets.update` | `assets.delete` | — | — | — |
| **Kategori** | `asset_categories.view` | `asset_categories.manage` | `asset_categories.manage` | `asset_categories.manage` | — | — | — |
| **Penyusutan** | `assets.view` | `assets.depreciation.run` | — | — | `assets.depreciation.run` | `assets.depreciation.reverse` | — |
| **Pelepasan** | `assets.disposal.view` | `assets.disposal.create` | `assets.disposal.update` | `assets.disposal.delete` | `assets.disposal.post` | `assets.disposal.reverse` | — |
| **Mutasi** | `assets.movement.view` | `assets.movement.create` | `assets.movement.update` | `assets.movement.delete` | — | — | `assets.movement.approve` |
| **Laporan** | `assets.reports.view` | — | — | — | — | — | — |

---

## Tugas Umum

### Tugas: Mendaftarkan Aset yang Baru Dibeli

1. Pastikan **Kategori Aset** sudah ada untuk jenis aset (mis. IT Equipment untuk komputer).
2. Verifikasi kategori memiliki **akun COA** yang benar.
3. Buka **Assets > Assets** → **Create Asset**.
4. Isi detail aset:
   - Kode, Nama, Kategori, Biaya Perolehan.
   - Nilai Residu (jika ada), Metode Penyusutan, Masa Manfaat (bulan).
   - Tanggal Mulai Pakai (biasanya tanggal pembelian/faktur).
   - Opsional tautkan ke Faktur Pembelian dan Business Partner (vendor).
   - Tetapkan ke Proyek dan/atau Departemen jika berlaku.
5. Klik **Save**.
6. Aset sekarang muncul dalam daftar aset dengan status `active`.
7. Penyusutan akan dihitung mulai dari proses penyusutan berikutnya.

### Tugas: Menjalankan Penyusutan Bulanan

1. Buka **Assets > Depreciation Runs** → **New Depreciation Run**.
2. Pilih periode (mis. `2024-01`).
3. Tinjau daftar aset yang memenuhi syarat dan jumlah yang dihitung.
4. Klik **Calculate** untuk membuat entri draft.
5. Tinjau detail proses — periksa total penyusutan.
6. Klik **Post** untuk membuat jurnal:
   - Debit: Beban Penyusutan
   - Kredit: Akumulasi Penyusutan
7. Proses sekarang `posted`. Nilai buku aset diperbarui.

### Tugas: Mentransfer Aset ke Departemen Lain

1. Buka **Assets > Asset Movements** → **New Movement**.
2. Pilih aset dan atur Jenis Mutasi ke **Department Transfer**.
3. Atur **Dari Departemen** dan **Ke Departemen**.
4. Atur **Dari Lokasi** dan **Ke Lokasi** (jika juga mengubah lokasi fisik).
5. Tambahkan **Alasan** untuk transfer.
6. Klik **Save** (status: Draft).
7. Approver (dengan izin `assets.movement.approve`) klik **Approve**.
8. Setelah disetujui, klik **Complete** untuk menyelesaikan. Departemen/lokasi aset diperbarui.

### Tugas: Melepaskan Aset yang Sudah Disusutkan Penuh

1. Buka **Assets > Asset Disposals** → **New Disposal**.
2. Pilih aset.
3. Atur Jenis Pelepasan:
   - **Sale** jika menjual (masukkan jumlah hasil).
   - **Scrapping** jika membuang (hasil = 0).
4. Atur tanggal pelepasan dan alasan.
5. Sistem menghitung otomatis:
   - Nilai Buku (seharusnya mendekati nilai residu jika sudah disusutkan penuh).
   - Laba/Rugi berdasarkan hasil.
6. Klik **Save**.
7. Klik **Post** untuk membuat jurnal dan menandai aset sebagai dilepaskan.

### Tugas: Memperbaiki Proses Penyusutan yang Salah Posting

1. Buka **Assets > Depreciation Runs**.
2. Temukan proses yang sudah diposting yang perlu dibatalkan.
3. Klik **Reverse** (memerlukan izin `assets.depreciation.reverse`).
4. Konfirmasi pembatalan.
5. Sistem akan:
   - Membuat jurnal pembalik.
   - Mengembalikan akumulasi penyusutan aset.
   - Mengatur status proses ke `reversed`.
6. Buat proses penyusutan baru untuk periode yang sama dengan pengaturan yang benar.

### Tugas: Memeriksa Kualitas Data

1. Buka **Assets > Data Quality**.
2. Tinjau dashboard untuk jumlah masalah.
3. Klik **Duplikat** untuk melihat aset duplikat potensial → gabung atau bersihkan.
4. Klik **Tidak Lengkap** untuk melihat aset dengan data kurang → klik untuk edit dan isi.
5. Klik **Konsistensi** untuk melihat anomali perhitungan → selidiki dan perbaiki.
6. Jalankan pemeriksaan kualitas data secara berkala (bulanan, setelah impor).

### Tugas: Mengimpor Beberapa Aset dari Excel

1. Buka **Assets > Asset Import**.
2. Klik **Download Template** untuk mendapatkan template Excel.
3. Isi semua aset dalam template:
   - Satu baris per aset.
   - Gunakan kode kategori yang ada (periksa daftar Asset Categories).
   - Referensikan kode proyek/departemen yang ada jika diperlukan.
4. Unggah file dan klik **Validate**.
5. Tinjau kesalahan validasi dan perbaiki di file Excel.
6. Unggah ulang dan validasi hingga bersih.
7. Klik **Import** untuk membuat semua aset.

---

## Pemecahan Masalah

### Masalah: Tidak Dapat Memposting Proses Penyusutan

**Gejala**: Tombol **Post** dinonaktifkan atau tidak ada pada proses penyusutan.

**Penyebab**:
- Anda tidak memiliki izin `assets.depreciation.run`.
- Proses sudah diposting atau dibatalkan.
- Periode ditutup (diperiksa melalui `PeriodCloseService`).

**Solusi**:
1. Verifikasi izin Anda dengan administrator.
2. Periksa status proses — hanya proses **Draft** yang dapat diposting.
3. Jika periode ditutup, minta administrator untuk membuka kembali periode.

### Masalah: Aset Tidak Dapat Dihapus

**Gejala**: Tombol Delete tidak ada atau mengembalikan error.

**Penyebab**:
- Aset memiliki entri penyusutan yang diposting.
- Aset telah dilepaskan.
- Anda tidak memiliki izin `assets.delete`.

**Solusi**:
1. Jika entri penyusutan ada, Anda harus **melepaskan** aset sebagai gantinya.
2. Jika sudah dilepaskan, tidak perlu tindakan lebih lanjut — aset yang dilepaskan tetap ada untuk audit.
3. Periksa izin Anda.

### Masalah: Jumlah Penyusutan Tampaknya Salah

**Gejala**: Penyusutan bulanan tidak cocok dengan jumlah yang diharapkan.

**Penyebab**:
- `acquisition_cost`, `salvage_value`, atau `life_months` pada aset salah.
- Kategori aset berubah di tengah masa manfaat.
- Prorata bulan pertama (bulan parsial).
- Aset dalam kategori non-depreciable.

**Solusi**:
1. Periksa field aset: biaya perolehan, nilai residu, masa manfaat.
2. Verifikasi kategori diatur ke depreciable.
3. Periksa `placed_in_service_date` — bulan pertama diprorata jika hari > 1.
4. Rumus: `penyusutan_bulanan = (biaya_perolehan - nilai_residu) / masa_manfaat_bulan`.

### Masalah: Laba/Rugi Pelepasan Tampaknya Salah

**Gejala**: Laba/rugi yang dihitung pada pelepasan tidak cocok dengan harapan.

**Penyebab**:
- Akumulasi penyusutan tidak terkini. Jalankan penyusutan untuk periode saat ini terlebih dahulu.
- Jumlah hasil dimasukkan salah.
- Nilai buku berubah karena edit aset setelah penyusutan diposting.

**Solusi**:
1. Pastikan semua proses penyusutan diposting hingga tanggal pelepasan.
2. Verifikasi akumulasi penyusutan di halaman detail aset.
3. Nilai Buku = `biaya_perolehan - akumulasi_penyusutan`.
4. Laba = `hasil - nilai_buku` (positif = laba, negatif = rugi).

### Masalah: Validasi Impor Gagal

**Gejala**: Error merah muncul saat memvalidasi file impor.

**Penyebab**:
- Kolom wajib hilang.
- Kode kategori/proyek/departemen tidak cocok dengan catatan yang ada.
- Format tanggal salah (harus `Y-m-d`).
- Kode aset duplikat.

**Solusi**:
1. Unduh template lagi dan periksa silang header kolom.
2. Buka daftar Asset Categories dan verifikasi kode kategori.
3. Periksa daftar Project dan Department untuk kode yang benar.
4. Pastikan tanggal dalam format `YYYY-MM-DD`.
5. Periksa bahwa kode aset belum digunakan.

### Masalah: Penutupan Periode Mencegah Tindakan

**Gejala**: Tidak dapat membuat proses penyusutan atau memposting pelepasan — sistem mengatakan periode ditutup.

**Penyebab**:
- Periode akuntansi telah ditutup oleh tim keuangan.

**Solusi**:
1. Minta periode dibuka kembali oleh administrator.
2. Atau posting transaksi di periode terbuka berikutnya (tidak disarankan untuk akurasi).
3. Catatan: **Pembatalan** proses penyusutan dan pelepasan saat ini tidak memeriksa penutupan periode.

---

## Referensi Cepat

### Rumus Utama

| Rumus | Deskripsi |
|-------|-----------|
| `Biaya Dapat Disusutkan = Biaya Perolehan - Nilai Residu` | Jumlah yang disusutkan selama masa manfaat |
| `Penyusutan Bulanan = Biaya Dapat Disusutkan / Masa Manfaat (bulan)` | Jumlah bulanan straight-line |
| `Nilai Buku = Biaya Perolehan - Akumulasi Penyusutan` | Nilai buku bersih saat ini |
| `Tarif Penyusutan = 1 / Masa Manfaat (bulan)` | Tarif penyusutan bulanan |
| `Sisa Masa Manfaat = Masa Manfaat - Bulan Dalam Pemakaian` | Sisa masa manfaat |
| `Laba Pelepasan = Hasil - Nilai Buku` | Ketika hasil > nilai buku |
| `Rugi Pelepasan = Nilai Buku - Hasil` | Ketika nilai buku > hasil |

### Nilai Status

| Entitas | Status yang Mungkin |
|---------|---------------------|
| Aset | `active`, `disposed` |
| Proses Penyusutan | `draft`, `posted`, `reversed` |
| Pelepasan | `draft`, `posted`, `reversed` |
| Mutasi | `draft`, `approved`, `completed`, `cancelled` |

### Referensi Cepat URL

| Halaman | URL |
|---------|-----|
| Daftar Aset | `/assets` |
| Buat Aset | `/assets/create` |
| Lihat Aset | `/assets/{id}` |
| Edit Aset | `/assets/{id}/edit` |
| Kategori Aset | `/asset-categories` |
| Proses Penyusutan | `/assets/depreciation` |
| Proses Penyusutan Baru | `/assets/depreciation/create` |
| Lihat Proses Penyusutan | `/assets/depreciation/{id}` |
| Pelepasan | `/assets/disposals` |
| Pelepasan Baru | `/assets/disposals/create` |
| Mutasi | `/assets/movements` |
| Mutasi Baru | `/assets/movements/create` |
| Impor | `/assets/import` |
| Kualitas Data | `/assets/data-quality` |
| Operasi Massal | `/assets/bulk-operations` |
| Laporan Daftar Aset | `/reports/assets/asset-register` |

### Glosarium

| Istilah | Definisi |
|---------|----------|
| **Biaya Perolehan** | Harga beli awal aset |
| **Akumulasi Penyusutan** | Total penyusutan yang dibebankan sejak perolehan |
| **Nilai Buku** | Nilai bersih setelah akumulasi penyusutan (`biaya - akum penyusutan`) |
| **Nilai Residu** | Estimasi nilai sisa di akhir masa manfaat |
| **Masa Manfaat** | Total periode (dalam bulan) di mana aset disusutkan |
| **Tanggal Mulai Pakai** | Tanggal aset mulai digunakan; penyusutan dimulai dari tanggal ini |
| **Straight-Line** | Jumlah penyusutan sama setiap bulan |
| **Proses Penyusutan** | Proses batch yang menghitung dan memposting penyusutan untuk suatu periode |
| **Laba Pelepasan** | Keuntungan saat dijual lebih dari nilai buku |
| **Rugi Pelepasan** | Kerugian saat dijual kurang dari nilai buku |
| **COA** | Chart of Accounts — struktur akun buku besar |

---

_Manual ini mencakup modul Aset & Penyusutan di Sarang ERP. Untuk bantuan tambahan, gunakan **HELP Assistant** (ikon **?** di navbar) untuk mengajukan pertanyaan cara kerja spesifik tentang manajemen aset._