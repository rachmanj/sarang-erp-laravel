# Manual Sistem Penomoran Dokumen

**Versi**: 1.0  
**Terakhir Diperbarui**: 11 Desember 2025  
**Berlaku Untuk**: Semua Modul ERP  
**Bahasa**: Bahasa Indonesia

---

## Daftar Isi

1. [Ikhtisar](#ikhtisar)
2. [Format Nomor](#format-nomor)
3. [Kode Dokumen](#kode-dokumen)
4. [Kode Entitas](#kode-entitas)
5. [Manajemen Urutan](#manajemen-urutan)
6. [Contoh](#contoh)
7. [Praktik Terbaik](#praktik-terbaik)
8. [Pemecahan Masalah](#pemecahan-masalah)
9. [Pertanyaan Umum](#pertanyaan-umum)

---

## 1. Ikhtisar

Sistem Sarang ERP menggunakan **sistem penomoran berbasis entitas yang terpadu** untuk semua dokumen bisnis. Setiap nomor dokumen secara unik mengidentifikasi:

- **Entitas**: Entitas legal (perusahaan) mana yang membuat dokumen
- **Tahun**: Tahun fiskal dokumen
- **Jenis Dokumen**: Jenis dokumen (PO, Invoice, dll.)
- **Nomor Urut**: Nomor urut unik dalam entitas/tahun/jenis dokumen

Sistem ini memastikan bahwa:
- Setiap dokumen memiliki nomor unik di semua entitas
- Nomor dokumen dapat diprediksi dan dapat dilacak
- Urutan berbasis tahun direset otomatis
- Pelaporan spesifik entitas menjadi lebih sederhana

---

## 2. Format Nomor

Semua nomor dokumen mengikuti format: **`EEYYDDNNNNN`**

Dimana:
- **`EE`** = Kode Entitas (2 digit)
- **`YY`** = Tahun (2 digit, 2 digit terakhir tahun)
- **`DD`** = Kode Dokumen (2 digit)
- **`NNNNN`** = Nomor Urut (5 digit, diisi dengan nol di depan)

**Panjang Total**: 11 karakter

---

## 3. Kode Dokumen

| Kode | Jenis Dokumen | Deskripsi |
|------|--------------|-----------|
| 01 | Purchase Order | Pesanan pembelian dari supplier |
| 02 | Goods Receipt PO / GRPO | Penerimaan barang dari PO |
| 03 | Purchase Invoice | Faktur pembelian dari supplier |
| 04 | Purchase Payment | Pembayaran pembelian ke supplier |
| 06 | Sales Order | Pesanan penjualan ke customer |
| 07 | Delivery Order | Surat jalan pengiriman |
| 08 | Sales Invoice | Faktur penjualan ke customer |
| 09 | Sales Receipt | Penerimaan pembayaran dari customer |
| 10 | Asset Disposal | Penghapusan/penjualan aset tetap |
| 11 | Cash Expense | Pengeluaran kas |
| 12 | Journal | Jurnal akuntansi manual |
| 13 | Account Statement | Rekening koran/rekening mutasi |

**Catatan**: Kode 05 dan 14-99 disediakan untuk penggunaan di masa depan.

---

## 4. Kode Entitas

Kode entitas adalah angka dua digit yang diberikan kepada setiap entitas legal dalam sistem. Kode-kode ini dikonfigurasi dalam data master Entitas Perusahaan.

**Kode Entitas Umum:**
- **71** = PT Cahaya Sarange Jaya (PT CSJ)
- **72** = CV Cahaya Saranghae (CV CS)

**Cara Kerja Kode Entitas:**
- Kode entitas unik per entitas legal
- Mereka tetap konstan di semua tahun
- Mereka muncul di setiap nomor dokumen untuk entitas tersebut
- Kode entitas ditetapkan selama setup awal sistem dan tidak boleh diubah

---

## 5. Manajemen Urutan

**Manajemen Urutan Otomatis:**

1. **Reset Berbasis Tahun**: Nomor urut direset ke `00001` di awal setiap tahun kalender (1 Januari)

2. **Spesifik Entitas**: Setiap entitas menjaga urutan terpisah untuk setiap jenis dokumen

3. **Aman untuk Thread**: Sistem memastikan tidak ada nomor duplikat bahkan dengan transaksi bersamaan

4. **Pembuatan Otomatis**: Nomor dokumen dibuat otomatis saat dokumen dibuat

**Penyimpanan Urutan:**
- Urutan disimpan dalam tabel `document_sequences`
- Setiap record melacak: Entitas + Jenis Dokumen + Tahun + Nomor Saat Ini
- Sistem secara otomatis menaikkan urutan saat menghasilkan nomor baru

**Intervensi Manual:**
- Nomor urut dikelola secara otomatis oleh sistem
- Penyesuaian urutan manual tidak direkomendasikan dan memerlukan akses database
- Hubungi administrator sistem untuk masalah terkait urutan

---

## 6. Contoh

#### Contoh 1: Purchase Order (PT CSJ)

**Nomor**: `71250100001`

**Penjelasan**:
- `71` = PT Cahaya Sarange Jaya
- `25` = Tahun 2025
- `01` = Purchase Order
- `00001` = PO pertama tahun ini

**Arti**: Ini adalah Purchase Order pertama yang dibuat oleh PT CSJ pada tahun 2025.

#### Contoh 2: Sales Invoice (CV CS)

**Nomor**: `72250800005`

**Penjelasan**:
- `72` = CV Cahaya Saranghae
- `25` = Tahun 2025
- `08` = Sales Invoice
- `00005` = Sales Invoice kelima tahun ini

**Arti**: Ini adalah Sales Invoice kelima yang dibuat oleh CV CS pada tahun 2025.

#### Contoh 3: Purchase Payment (PT CSJ)

**Nomor**: `71250400123`

**Penjelasan**:
- `71` = PT Cahaya Sarange Jaya
- `25` = Tahun 2025
- `04` = Purchase Payment
- `00123` = Purchase Payment ke-123 tahun ini

**Arti**: Ini adalah Purchase Payment ke-123 yang dibuat oleh PT CSJ pada tahun 2025.

#### Contoh 4: Jurnal Akuntansi (PT CSJ)

**Nomor**: `71251200001`

**Penjelasan**:
- `71` = PT Cahaya Sarange Jaya
- `25` = Tahun 2025
- `12` = Jurnal Akuntansi
- `00001` = Jurnal pertama tahun ini

**Arti**: Ini adalah Jurnal Akuntansi manual pertama yang dibuat oleh PT CSJ pada tahun 2025.

#### Contoh 5: Transisi Tahun

**Dokumen 2024**: `71240100050` (PO terakhir 2024)
**Dokumen 2025**: `71250100001` (PO pertama 2025)

Perhatikan bagaimana urutan direset dari `00050` ke `00001` ketika tahun berubah dari `24` ke `25`.

---

## 7. Praktik Terbaik

#### Untuk Pengguna

1. **Jangan Ubah Nomor**: Jangan pernah mengubah nomor dokumen secara manual. Mereka dibuat secara otomatis.

2. **Verifikasi Entitas**: Selalu verifikasi Anda membuat dokumen untuk entitas yang benar sebelum pengiriman.

3. **Perencanaan Akhir Tahun**: Sadari bahwa urutan direset pada 1 Januari. Rencanakan aktivitas akhir tahun Anda dengan tepat.

4. **Laporan per Entitas**: Saat menghasilkan laporan, gunakan filter entitas untuk mendapatkan laporan spesifik entitas yang akurat.

5. **Pengenalan Format Nomor**: Pelajari untuk mengenali jenis dokumen berdasarkan kode dokumen mereka (01=PO, 08=Sales Invoice, dll.)

#### Untuk Administrator

1. **Manajemen Kode Entitas**: Tentukan kode entitas dengan hati-hati selama setup. Mengubahnya kemudian memerlukan migrasi data.

2. **Pemantauan Urutan**: Secara berkala periksa tabel urutan untuk anomali atau celah.

3. **Backup Sebelum Akhir Tahun**: Pastikan backup database lengkap sebelum akhir tahun untuk mempertahankan status urutan.

4. **Pelatihan**: Latih pengguna pada sistem penomoran untuk menghindari kebingungan dan kesalahan.

5. **Dokumentasi**: Jaga manual ini tetap diperbarui saat menambahkan jenis dokumen atau entitas baru.

---

## 8. Pemecahan Masalah

#### Masalah: Nomor Dokumen Duplikat

**Gejala**: Sistem menampilkan error "Nomor dokumen sudah ada"

**Kemungkinan Penyebab**:
- Tabel urutan database tidak sinkron
- Manipulasi urutan manual
- Konflik transaksi bersamaan

**Solusi**:
1. Hubungi administrator sistem
2. Periksa tabel `document_sequences` untuk entitas/jenis dokumen/tahun tertentu
3. Verifikasi tidak ada penetapan nomor manual yang terjadi
4. Administrator mungkin perlu menyesuaikan urutan secara manual

#### Masalah: Entitas Salah dalam Nomor Dokumen

**Gejala**: Nomor dokumen menunjukkan kode entitas yang salah

**Kemungkinan Penyebab**:
- Entitas salah dipilih saat pembuatan dokumen
- Kode entitas diubah setelah pembuatan dokumen
- Inkonsistensi database

**Solusi**:
1. Verifikasi pemilihan entitas sebelum pembuatan dokumen
2. Periksa konfigurasi entitas dalam data master
3. Hubungi administrator jika kode entitas tampak salah

#### Masalah: Urutan Tidak Reset di Akhir Tahun

**Gejala**: Dokumen tahun baru melanjutkan nomor urut lama

**Kemungkinan Penyebab**:
- Tanggal/waktu sistem tidak benar
- Tabel urutan tidak diperbarui
- Masalah cache aplikasi

**Solusi**:
1. Verifikasi tanggal sistem benar
2. Periksa apakah record urutan tahun baru ada di tabel `document_sequences`
3. Hapus cache aplikasi
4. Hubungi administrator jika masalah berlanjut

#### Masalah: Kode Dokumen Hilang

**Gejala**: Jenis dokumen tidak menghasilkan nomor atau menggunakan format salah

**Kemungkinan Penyebab**:
- Jenis dokumen tidak terdaftar dalam sistem
- Konfigurasi kode dokumen hilang
- Masalah konfigurasi layanan

**Solusi**:
1. Verifikasi jenis dokumen ada di `DocumentNumberingService`
2. Periksa konfigurasi `ENTITY_DOCUMENT_CODES`
3. Hubungi developer/administrator untuk jenis dokumen yang hilang

---

## 9. Pertanyaan Umum

**Q: Bisakah saya mengubah nomor dokumen setelah dibuat?**  
A: Tidak, nomor dokumen tidak dapat diubah dan tidak dapat diubah setelah dibuat. Ini memastikan integritas jejak audit.

**Q: Apa yang terjadi jika saya membuat dokumen untuk entitas yang berbeda?**  
A: Setiap entitas menjaga urutan terpisah. PO untuk entitas 71 akan menjadi `71250100001`, sementara PO untuk entitas 72 akan menjadi `72250100001`, keduanya adalah PO pertama untuk entitas masing-masing.

**Q: Berapa banyak dokumen yang bisa saya buat per tahun per jenis dokumen?**  
A: Urutan memungkinkan hingga 99.999 dokumen per entitas per jenis dokumen per tahun (00001 hingga 99999).

**Q: Bagaimana jika saya memerlukan lebih dari 99.999 dokumen dalam setahun?**  
A: Ini sangat jarang terjadi. Jika terjadi, hubungi administrator sistem untuk memperpanjang panjang urutan atau mengimplementasikan solusi.

**Q: Bisakah saya menggunakan format penomoran kustom saya sendiri?**  
A: Tidak, sistem menggunakan format standar di semua entitas untuk konsistensi, pelaporan, dan integrasi sistem.

**Q: Apakah dokumen lama mempertahankan nomor mereka saat bermigrasi ke sistem ini?**  
A: Dokumen yang ada mempertahankan nomor asli mereka. Hanya dokumen baru yang dibuat setelah migrasi menggunakan format baru.

**Q: Bagaimana cara mengidentifikasi entitas mana dokumen tersebut?**  
A: Lihat dua digit pertama nomor dokumen (EE). Setiap entitas memiliki kode dua digit yang unik.

**Q: Apa yang terjadi pada urutan selama penutupan akhir tahun?**  
A: Urutan secara otomatis direset ke 00001 ketika sistem mendeteksi tahun baru. Tidak diperlukan intervensi manual.

---

## Lampiran

### Kartu Referensi Format Nomor Dokumen

```
Format: EEYYDDNNNNN (11 karakter)

EE = Kode Entitas (2 digit)
YY = Tahun (2 digit)  
DD = Kode Dokumen (2 digit)
NNNNN = Nomor Urut (5 digit)

Contoh: 71250100001
        └─┬─┘│││└─┬─┘
          71 │││  00001
            25││
              01
              
Artinya: PT CSJ, Tahun 2025, PO, Nomor 1
```

### Referensi Cepat: Kode Dokumen

| Kode | Dokumen |
|------|---------|
| 01 | PO |
| 02 | GRPO |
| 03 | Purchase Invoice |
| 04 | Purchase Payment |
| 06 | Sales Order |
| 07 | Delivery Order |
| 08 | Sales Invoice |
| 09 | Sales Receipt |
| 10 | Asset Disposal |
| 11 | Cash Expense |
| 12 | Journal |
| 13 | Account Statement |

---

## Riwayat Revisi

| Versi | Tanggal | Perubahan | Penulis |
|-------|---------|-----------|---------|
| 1.0 | 2025-12-11 | Pembuatan manual awal | Dokumentasi Sistem |

---

## Kontak

Untuk pertanyaan atau masalah terkait sistem penomoran dokumen, silakan hubungi:

**Administrator Sistem**  
**Departemen IT**  
Email: it@sarang-erp.com  
Telepon: +62-XXX-XXXX-XXXX

---

**Akhir Manual**

