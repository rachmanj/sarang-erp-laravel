# Manual Setup Saldo Awal Bank dan Kas

**Tujuan**: Panduan lengkap untuk memasukkan saldo awal rekening bank dan kas ke dalam sistem ERP Sarang  
**Target Pembaca**: Administrator Sistem, Staf Akuntansi, dan Tim Implementasi  
**Perkiraan Waktu**: 15-30 menit per rekening bank/kas  
**Tingkat Kesulitan**: Menengah (memerlukan pemahaman dasar akuntansi)

---

## Daftar Isi

1. [Gambaran Umum](#gambaran-umum)
2. [Prasyarat](#prasyarat)
3. [Konsep Dasar Akuntansi](#konsep-dasar-akuntansi)
4. [Langkah-Langkah Setup](#langkah-langkah-setup)
5. [Contoh Praktis](#contoh-praktis)
6. [Setup Multiple Rekening](#setup-multiple-rekening)
7. [Verifikasi Saldo](#verifikasi-saldo)
8. [Troubleshooting](#troubleshooting)
9. [Pertanyaan Umum (FAQ)](#pertanyaan-umum-faq)

---

## Gambaran Umum

Sistem ERP Sarang menggunakan **Manual Journal Entry** (Jurnal Manual) untuk memasukkan saldo awal rekening bank dan kas. Sistem ini menghitung saldo rekening secara otomatis berdasarkan semua transaksi jurnal yang telah diposting, sehingga saldo awal harus dimasukkan melalui jurnal akuntansi yang seimbang.

### Mengapa Perlu Setup Saldo Awal?

- ✅ **Akurasi Laporan Keuangan**: Memastikan laporan keuangan menampilkan saldo yang benar sejak awal periode
- ✅ **Realisasi Saldo**: Mencerminkan kondisi keuangan perusahaan yang sebenarnya
- ✅ **Audit Trail**: Menyediakan jejak audit yang jelas untuk saldo awal
- ✅ **Konsistensi Data**: Memastikan semua rekening memiliki saldo awal yang konsisten

### Kapan Harus Setup Saldo Awal?

- 🗓️ **Awal Tahun Fiskal**: Saat memulai tahun akuntansi baru
- 🏢 **Implementasi Sistem Baru**: Saat pertama kali menggunakan sistem ERP
- 📊 **Migrasi Data**: Saat memindahkan data dari sistem lama
- 🔄 **Penyesuaian Saldo**: Saat perlu mengoreksi saldo yang salah

---

## Prasyarat

Sebelum memulai setup saldo awal, pastikan:

### 1. Akses dan Izin

- ✅ Memiliki akses login ke sistem ERP
- ✅ Memiliki izin `journals.create` untuk membuat jurnal manual
- ✅ Memahami dasar-dasar akuntansi (debit/kredit)

### 2. Informasi yang Diperlukan

- 📋 **Daftar Rekening Bank/Kas**: Semua rekening yang perlu disetup saldo awalnya
- 💰 **Saldo Awal Setiap Rekening**: Jumlah saldo per tanggal tertentu
- 📅 **Tanggal Saldo Awal**: Biasanya tanggal pertama tahun fiskal atau tanggal implementasi
- 📄 **Dokumen Pendukung**: Bank statement, buku kas, atau dokumen keuangan lainnya

### 3. Verifikasi Chart of Accounts

Pastikan rekening-rekening berikut sudah ada di sistem:

**Rekening Bank:**
- `1.1.2.01` - Bank – Operating – Main (Bank Operasional - Utama)
- `1.1.2.02` - Bank – Operating – Payroll (Bank Operasional - Payroll)
- Atau rekening bank lainnya sesuai kebutuhan

**Rekening Kas:**
- `1.1.1` - Cash on Hand (Kas di Tangan)
- Atau rekening kas lainnya sesuai kebutuhan

**Rekening Ekuitas (untuk kredit saldo awal):**
- `3.3.1` - Saldo Awal Laba Ditahan (Opening Retained Earnings) - **DIREKOMENDASIKAN**
- `3.1.1` - Modal Saham Biasa (Common Stock) - jika saldo awal adalah modal awal
- `3.1.2` - Modal Saham Preferen (Preferred Stock) - jika saldo awal adalah modal preferen

**Cara Verifikasi:**
1. Navigasi ke: `Accounting > Chart of Accounts`
2. Cari rekening yang diperlukan menggunakan kode atau nama
3. Pastikan rekening memiliki status `is_postable = true` (dapat diposting)

---

## Konsep Dasar Akuntansi

### Prinsip Dasar: Jurnal Harus Seimbang

Setiap jurnal akuntansi harus **seimbang**, artinya:
- **Total Debit = Total Credit**
- Sistem tidak akan mengizinkan posting jurnal yang tidak seimbang

### Persamaan Akuntansi Dasar

```
Assets (Aktiva) = Liabilities (Kewajiban) + Equity (Ekuitas)
```

Untuk saldo awal bank/kas:
- **Debit**: Rekening Bank/Kas (Asset - Aktiva)
- **Credit**: Rekening Ekuitas (Equity - Ekuitas)

### Mengapa Menggunakan Rekening Ekuitas?

Saldo awal bank/kas biasanya berasal dari:
- **Modal awal perusahaan** → Credit ke "Modal Saham"
- **Laba ditahan periode sebelumnya** → Credit ke "Saldo Awal Laba Ditahan"
- **Investasi tambahan** → Credit ke "Modal Saham" atau "Agio Saham"

**Rekomendasi**: Gunakan **"Saldo Awal Laba Ditahan" (3.3.1)** untuk saldo awal yang berasal dari operasi bisnis sebelumnya.

---

## Langkah-Langkah Setup

### Langkah 1: Akses Menu Jurnal Manual

1. Login ke sistem ERP
2. Navigasi ke: **Accounting** → **Journals** → **Create Manual Journal**
3. Atau akses langsung melalui URL: `/journals/manual/create`
4. Pastikan Anda memiliki izin `journals.create`

### Langkah 2: Isi Informasi Header Jurnal

Di bagian atas form, isi informasi berikut:

**Tanggal Transaksi (Transaction Date):**
- Pilih tanggal saldo awal (biasanya tanggal pertama tahun fiskal)
- Contoh: `2025-01-01` untuk saldo awal tahun 2025
- **Penting**: Tanggal ini menentukan kapan saldo awal berlaku

**Deskripsi (Description):**
- Masukkan deskripsi yang jelas dan informatif
- Format yang direkomendasikan: `Saldo Awal - [Nama Rekening] - [Tanggal]`
- Contoh: `Saldo Awal - Bank Operasional Utama - 1 Januari 2025`
- Contoh: `Saldo Awal - Kas di Tangan - 1 Januari 2025`

### Langkah 3: Tambahkan Baris Jurnal - Debit Rekening Bank/Kas

1. Klik tombol **"Add Line"** (Tambah Baris) di bagian bawah tabel
2. Isi informasi baris pertama:

   **Account (Rekening):**
   - Pilih rekening bank atau kas yang akan disetup saldo awalnya
   - Gunakan dropdown Select2 untuk mencari rekening
   - Format: `[Kode] - [Nama Rekening]`
   - Contoh: `1.1.2.01 - Bank – Operating – Main`

   **Currency (Mata Uang):**
   - Pilih mata uang rekening (default: IDR - Rupiah Indonesia)
   - Jika rekening dalam mata uang asing, pilih mata uang yang sesuai
   - Sistem akan otomatis mengambil kurs tukar untuk tanggal transaksi

   **Exchange Rate (Kurs Tukar):**
   - Untuk IDR, kurs tukar otomatis: `1.000000`
   - Untuk mata uang asing, sistem akan mengambil kurs tukar otomatis
   - Anda dapat mengubah kurs jika diperlukan

   **Debit:**
   - Masukkan **jumlah saldo awal** rekening
   - Contoh: `100000000` untuk Rp 100.000.000
   - **Penting**: Hanya isi kolom Debit, biarkan Credit = 0

   **Credit:**
   - Biarkan kosong atau isi dengan `0`
   - Tidak perlu mengisi kolom ini untuk baris debit

   **Project (Proyek):**
   - Opsional: Pilih proyek jika saldo awal terkait dengan proyek tertentu
   - Biarkan kosong jika tidak terkait proyek

   **Dept (Departemen):**
   - Opsional: Pilih departemen jika saldo awal terkait dengan departemen tertentu
   - Biarkan kosong jika tidak terkait departemen

   **Memo:**
   - Opsional: Tambahkan catatan atau memo untuk baris ini
   - Contoh: `Saldo awal berdasarkan bank statement tanggal 31 Desember 2024`

### Langkah 4: Tambahkan Baris Jurnal - Credit Rekening Ekuitas

1. Klik tombol **"Add Line"** lagi untuk menambahkan baris kedua
2. Isi informasi baris kedua:

   **Account (Rekening):**
   - Pilih rekening ekuitas yang sesuai
   - **Rekomendasi**: `3.3.1 - Saldo Awal Laba Ditahan`
   - Alternatif: `3.1.1 - Modal Saham Biasa` (jika saldo awal adalah modal)

   **Currency (Mata Uang):**
   - Pilih mata uang yang sama dengan baris pertama (biasanya IDR)

   **Exchange Rate (Kurs Tukar):**
   - Sistem akan otomatis mengambil kurs tukar yang sama

   **Debit:**
   - Biarkan kosong atau isi dengan `0`
   - Tidak perlu mengisi kolom ini untuk baris credit

   **Credit:**
   - Masukkan **jumlah yang sama** dengan debit di baris pertama
   - Contoh: `100000000` untuk Rp 100.000.000
   - **Penting**: Pastikan jumlah Credit sama persis dengan Debit

   **Project (Proyek):**
   - Opsional: Pilih proyek jika diperlukan
   - Biasanya dikosongkan untuk saldo awal

   **Dept (Departemen):**
   - Opsional: Pilih departemen jika diperlukan
   - Biasanya dikosongkan untuk saldo awal

   **Memo:**
   - Opsional: Tambahkan catatan
   - Contoh: `Saldo awal laba ditahan periode sebelumnya`

### Langkah 5: Verifikasi Keseimbangan Jurnal

Sebelum posting, pastikan jurnal sudah seimbang:

1. **Periksa Total Debit dan Credit:**
   - Di bagian bawah tabel, lihat baris "Totals"
   - **Total Debit** harus sama dengan **Total Credit**
   - Contoh: Debit = Rp 100.000.000, Credit = Rp 100.000.000 ✅

2. **Periksa Balance Indicator:**
   - Di bagian bawah form, ada indikator keseimbangan
   - Jika seimbang: **"Journal is balanced"** (hijau) ✅
   - Jika tidak seimbang: **"Journal is not balanced. Difference: [jumlah]"** (kuning) ⚠️

3. **Perbaiki Jika Tidak Seimbang:**
   - Periksa kembali jumlah Debit dan Credit
   - Pastikan tidak ada kesalahan input angka
   - Pastikan tidak ada baris yang terlewat

### Langkah 6: Post Jurnal

Setelah jurnal seimbang:

1. Tombol **"Post Journal"** akan aktif (tidak lagi disabled)
2. Klik tombol **"Post Journal"** untuk memposting jurnal
3. Sistem akan:
   - ✅ Memvalidasi jurnal (keseimbangan, periode tidak ditutup, dll)
   - ✅ Membuat record jurnal di database
   - ✅ Membuat baris-baris jurnal (journal lines)
   - ✅ Mengupdate saldo control account
   - ✅ Menghasilkan nomor jurnal otomatis (format: `EEYYDDNNNNN`)
   - ✅ Mencatat audit trail

4. Setelah berhasil, sistem akan menampilkan pesan sukses:
   - `Journal #[nomor] posted`
   - Contoh: `Journal #71010100001 posted`

5. Jurnal akan muncul di daftar jurnal: **Accounting** → **Journals**

---

## Contoh Praktis

### Contoh 1: Setup Saldo Awal Bank Operasional Utama

**Skenario:**
- Rekening: Bank BCA - Operasional Utama
- Saldo Awal: Rp 250.000.000
- Tanggal: 1 Januari 2025

**Langkah-Langkah:**

1. **Akses Menu**: Accounting → Journals → Create Manual Journal

2. **Isi Header:**
   - Date: `2025-01-01`
   - Description: `Saldo Awal - Bank BCA Operasional Utama - 1 Januari 2025`

3. **Baris 1 - Debit:**
   - Account: `1.1.2.01 - Bank – Operating – Main`
   - Currency: `IDR - Rupiah Indonesia`
   - Exchange Rate: `1.000000`
   - Debit: `250000000`
   - Credit: `0`
   - Memo: `Saldo awal berdasarkan bank statement 31 Des 2024`

4. **Baris 2 - Credit:**
   - Account: `3.3.1 - Saldo Awal Laba Ditahan`
   - Currency: `IDR - Rupiah Indonesia`
   - Exchange Rate: `1.000000`
   - Debit: `0`
   - Credit: `250000000`
   - Memo: `Saldo awal laba ditahan`

5. **Verifikasi:**
   - Total Debit: Rp 250.000.000
   - Total Credit: Rp 250.000.000
   - Status: ✅ Balanced

6. **Post Journal:**
   - Klik "Post Journal"
   - Sistem menghasilkan: `Journal #71010100001 posted`

### Contoh 2: Setup Saldo Awal Kas di Tangan

**Skenario:**
- Rekening: Kas di Tangan
- Saldo Awal: Rp 15.000.000
- Tanggal: 1 Januari 2025

**Langkah-Langkah:**

1. **Isi Header:**
   - Date: `2025-01-01`
   - Description: `Saldo Awal - Kas di Tangan - 1 Januari 2025`

2. **Baris 1 - Debit:**
   - Account: `1.1.1 - Cash on Hand`
   - Debit: `15000000`
   - Credit: `0`

3. **Baris 2 - Credit:**
   - Account: `3.3.1 - Saldo Awal Laba Ditahan`
   - Debit: `0`
   - Credit: `15000000`

4. **Post Journal**

### Contoh 3: Setup Multiple Rekening Bank dalam Satu Jurnal

**Skenario:**
- Bank BCA Operasional: Rp 250.000.000
- Bank Mandiri Payroll: Rp 50.000.000
- Kas di Tangan: Rp 15.000.000
- Total: Rp 315.000.000

**Langkah-Langkah:**

1. **Isi Header:**
   - Date: `2025-01-01`
   - Description: `Saldo Awal - Bank dan Kas - 1 Januari 2025`

2. **Baris 1 - Debit Bank BCA:**
   - Account: `1.1.2.01 - Bank – Operating – Main`
   - Debit: `250000000`
   - Credit: `0`

3. **Baris 2 - Debit Bank Mandiri:**
   - Account: `1.1.2.02 - Bank – Operating – Payroll` (atau rekening yang sesuai)
   - Debit: `50000000`
   - Credit: `0`

4. **Baris 3 - Debit Kas:**
   - Account: `1.1.1 - Cash on Hand`
   - Debit: `15000000`
   - Credit: `0`

5. **Baris 4 - Credit Ekuitas:**
   - Account: `3.3.1 - Saldo Awal Laba Ditahan`
   - Debit: `0`
   - Credit: `315000000` (total semua debit)

6. **Verifikasi:**
   - Total Debit: Rp 315.000.000
   - Total Credit: Rp 315.000.000
   - Status: ✅ Balanced

7. **Post Journal**

---

## Setup Multiple Rekening

### Strategi 1: Satu Jurnal untuk Semua Rekening

**Keuntungan:**
- ✅ Satu dokumen untuk semua saldo awal
- ✅ Lebih mudah untuk audit dan pelacakan
- ✅ Satu nomor jurnal untuk semua saldo awal

**Kekurangan:**
- ⚠️ Jurnal menjadi lebih panjang jika banyak rekening
- ⚠️ Lebih sulit untuk mengidentifikasi saldo per rekening

**Kapan Menggunakan:**
- Semua rekening memiliki tanggal saldo awal yang sama
- Ingin konsolidasi dalam satu dokumen

### Strategi 2: Satu Jurnal per Rekening

**Keuntungan:**
- ✅ Lebih mudah untuk melacak saldo per rekening
- ✅ Deskripsi jurnal lebih spesifik
- ✅ Lebih mudah untuk revisi jika ada kesalahan

**Kekurangan:**
- ⚠️ Banyak dokumen jurnal yang harus dibuat
- ⚠️ Lebih banyak waktu yang diperlukan

**Kapan Menggunakan:**
- Setiap rekening memiliki tanggal saldo awal berbeda
- Ingin detail yang lebih spesifik per rekening
- Memudahkan revisi di masa depan

### Rekomendasi

**Untuk Setup Awal Sistem:**
- Gunakan **Strategi 1** (satu jurnal untuk semua rekening)
- Semua saldo awal biasanya memiliki tanggal yang sama
- Lebih efisien dan mudah untuk audit

**Untuk Penyesuaian Saldo:**
- Gunakan **Strategi 2** (satu jurnal per rekening)
- Setiap penyesuaian memiliki konteks yang berbeda
- Lebih mudah untuk dokumentasi dan approval

---

## Verifikasi Saldo

Setelah posting jurnal, verifikasi bahwa saldo sudah benar:

### Metode 1: Melalui Account Statement

1. Navigasi ke: **Accounting** → **Account Statements**
2. Klik **"Create Statement"**
3. Pilih:
   - **Account Type**: `GL Account`
   - **Account**: Pilih rekening bank/kas yang baru disetup
   - **From Date**: Tanggal saldo awal (contoh: `2025-01-01`)
   - **To Date**: Tanggal saat ini atau tanggal akhir periode
4. Klik **"Generate Statement"**
5. Periksa:
   - **Opening Balance**: Harus sesuai dengan saldo awal yang dimasukkan
   - **Transactions**: Harus menampilkan jurnal saldo awal
   - **Closing Balance**: Harus sesuai dengan saldo awal (jika belum ada transaksi lain)

### Metode 2: Melalui Journal List

1. Navigasi ke: **Accounting** → **Journals**
2. Cari jurnal yang baru dibuat menggunakan:
   - Nomor jurnal
   - Deskripsi
   - Tanggal
3. Klik pada jurnal untuk melihat detail
4. Verifikasi:
   - Baris debit menampilkan rekening bank/kas dengan jumlah yang benar
   - Baris credit menampilkan rekening ekuitas dengan jumlah yang benar
   - Total debit = total credit

### Metode 3: Melalui Chart of Accounts Report

1. Navigasi ke: **Accounting** → **Reports** → **General Ledger** (jika tersedia)
2. Pilih rekening bank/kas
3. Pilih periode yang mencakup tanggal saldo awal
4. Verifikasi saldo awal muncul dengan benar

### Metode 4: Melalui Database Query (Advanced)

Jika Anda memiliki akses database:

```sql
-- Cek saldo rekening berdasarkan journal lines
SELECT 
    a.code,
    a.name,
    SUM(jl.debit - jl.credit) as balance
FROM accounts a
LEFT JOIN journal_lines jl ON jl.account_id = a.id
WHERE a.code = '1.1.2.01'  -- Ganti dengan kode rekening Anda
GROUP BY a.id, a.code, a.name;
```

---

## Troubleshooting

### Masalah 1: Jurnal Tidak Bisa Dipost - "Journal is not balanced"

**Gejala:**
- Tombol "Post Journal" tetap disabled
- Indikator menunjukkan "Journal is not balanced"
- Ada selisih antara total debit dan credit

**Penyebab:**
- Jumlah debit tidak sama dengan credit
- Ada kesalahan input angka
- Ada baris yang terlewat

**Solusi:**
1. Periksa kembali semua baris jurnal
2. Pastikan total debit = total credit
3. Periksa apakah ada angka yang salah ketik
4. Pastikan tidak ada baris yang memiliki debit dan credit sekaligus (harus salah satu saja)
5. Gunakan kalkulator untuk memverifikasi total

**Contoh Perbaikan:**
```
Salah:
- Debit: 100000000
- Credit: 99999999
- Difference: 1

Benar:
- Debit: 100000000
- Credit: 100000000
- Difference: 0 ✅
```

### Masalah 2: Rekening Tidak Muncul di Dropdown

**Gejala:**
- Rekening bank/kas tidak muncul di dropdown Account
- Rekening ekuitas tidak ditemukan

**Penyebab:**
- Rekening belum dibuat di Chart of Accounts
- Rekening memiliki `is_postable = false`
- Rekening dihapus atau dinonaktifkan

**Solusi:**
1. Verifikasi rekening ada di Chart of Accounts:
   - Navigasi ke: **Accounting** → **Chart of Accounts**
   - Cari rekening menggunakan kode atau nama
2. Jika rekening tidak ada, buat rekening baru:
   - Pastikan kode rekening sesuai dengan struktur Chart of Accounts
   - Set `is_postable = true`
3. Jika rekening ada tapi tidak muncul:
   - Periksa status `is_postable` harus `true`
   - Periksa apakah rekening aktif

### Masalah 3: Error "Cannot post to a closed period"

**Gejala:**
- Pesan error muncul saat mencoba post jurnal
- Jurnal tidak bisa diposting meskipun sudah seimbang

**Penyebab:**
- Periode akuntansi untuk tanggal jurnal sudah ditutup (closed)
- Tanggal jurnal berada di periode yang sudah ditutup

**Solusi:**
1. Periksa status periode akuntansi:
   - Navigasi ke: **Accounting** → **Periods** (jika tersedia)
   - Cari periode yang mencakup tanggal jurnal
   - Periksa status periode (Open/Closed)
2. Jika periode sudah ditutup:
   - Gunakan tanggal yang berada di periode yang masih terbuka
   - Atau buka kembali periode jika diperlukan (memerlukan izin admin)
3. Alternatif:
   - Buat jurnal dengan tanggal yang berada di periode terbuka
   - Tambahkan memo yang menjelaskan bahwa ini adalah saldo awal untuk periode sebelumnya

### Masalah 4: Saldo Tidak Sesuai Setelah Posting

**Gejala:**
- Jurnal sudah diposting
- Tapi saldo rekening tidak sesuai dengan yang diharapkan

**Penyebab:**
- Ada transaksi lain yang mempengaruhi saldo
- Jurnal saldo awal diposting dengan tanggal yang salah
- Ada jurnal lain yang sudah mempengaruhi rekening

**Solusi:**
1. Periksa Account Statement:
   - Buat account statement untuk rekening tersebut
   - Periksa semua transaksi yang mempengaruhi saldo
   - Identifikasi transaksi yang tidak seharusnya ada
2. Periksa tanggal jurnal:
   - Pastikan tanggal saldo awal benar
   - Pastikan tidak ada transaksi lain di tanggal yang sama atau sebelumnya
3. Periksa jurnal lain:
   - Cari jurnal lain yang mempengaruhi rekening yang sama
   - Pastikan tidak ada duplikasi jurnal saldo awal
4. Jika perlu, buat jurnal penyesuaian:
   - Buat jurnal baru untuk menyesuaikan selisih
   - Dokumentasikan alasan penyesuaian

### Masalah 5: Kurs Tukar Mata Uang Asing Tidak Benar

**Gejala:**
- Rekening dalam mata uang asing (USD, EUR, dll)
- Kurs tukar yang digunakan tidak sesuai

**Penyebab:**
- Sistem mengambil kurs tukar otomatis yang mungkin tidak sesuai
- Kurs tukar berubah setelah jurnal dibuat

**Solusi:**
1. Periksa kurs tukar di sistem:
   - Navigasi ke: **Accounting** → **Exchange Rates** (jika tersedia)
   - Periksa kurs tukar untuk tanggal jurnal
2. Update kurs tukar jika perlu:
   - Masukkan kurs tukar yang benar secara manual
   - Pastikan kurs tukar sesuai dengan tanggal jurnal
3. Verifikasi jumlah foreign currency:
   - Periksa kolom "Debit FC" dan "Credit FC"
   - Pastikan jumlah foreign currency benar

---

## Pertanyaan Umum (FAQ)

### Q1: Apakah saya harus setup saldo awal untuk semua rekening bank/kas sekaligus?

**Jawaban:**
Tidak harus sekaligus, tapi **sangat direkomendasikan** untuk setup semua saldo awal pada tanggal yang sama untuk konsistensi. Anda bisa:
- Setup semua sekaligus dalam satu jurnal (lebih efisien)
- Setup satu per satu (lebih detail, lebih lama)

### Q2: Bagaimana jika saya lupa memasukkan saldo awal dan sudah ada transaksi lain?

**Jawaban:**
Anda masih bisa membuat jurnal saldo awal, tapi:
- Gunakan tanggal yang sesuai (biasanya tanggal pertama periode)
- Sistem akan menghitung ulang saldo dengan benar
- Pastikan tidak ada transaksi lain di tanggal yang sama atau sebelumnya
- Jika perlu, buat jurnal penyesuaian untuk koreksi

### Q3: Apakah saldo awal harus menggunakan rekening "Saldo Awal Laba Ditahan"?

**Jawaban:**
Tidak harus, tapi **sangat direkomendasikan** untuk operasi bisnis yang berkelanjutan. Alternatif:
- **Saldo Awal Laba Ditahan (3.3.1)**: Untuk saldo dari operasi bisnis sebelumnya ✅ **DIREKOMENDASIKAN**
- **Modal Saham Biasa (3.1.1)**: Untuk saldo dari modal awal perusahaan
- **Modal Saham Preferen (3.1.2)**: Untuk saldo dari modal preferen

### Q4: Bagaimana jika saya perlu mengubah saldo awal setelah diposting?

**Jawaban:**
Anda bisa:
1. **Membuat Jurnal Penyesuaian**:
   - Buat jurnal baru untuk menyesuaikan selisih
   - Debit/Credit rekening bank/kas dengan selisihnya
   - Credit/Debit rekening ekuitas dengan selisihnya
2. **Reverse Jurnal Lama** (jika fitur tersedia):
   - Reverse jurnal saldo awal yang salah
   - Buat jurnal baru dengan saldo yang benar

### Q5: Apakah saldo awal mempengaruhi laporan keuangan?

**Jawaban:**
Ya, saldo awal sangat penting untuk:
- **Balance Sheet (Neraca)**: Menampilkan saldo bank/kas yang benar
- **Cash Flow Statement**: Menghitung arus kas dengan benar
- **Account Statements**: Menampilkan saldo awal dan transaksi dengan benar

### Q6: Bagaimana setup saldo awal untuk rekening multi-mata uang?

**Jawaban:**
1. Pilih mata uang rekening di dropdown Currency
2. Sistem akan otomatis mengambil kurs tukar untuk tanggal jurnal
3. Isi jumlah dalam mata uang dasar (IDR) di kolom Debit/Credit
4. Sistem akan otomatis menghitung jumlah foreign currency
5. Verifikasi kurs tukar dan jumlah foreign currency sebelum posting

### Q7: Apakah saya perlu setup saldo awal untuk setiap proyek/departemen?

**Jawaban:**
Tidak harus, tapi jika Anda menggunakan multi-dimensional accounting:
- Setup saldo awal dengan Project/Dept jika saldo terkait dengan proyek/departemen tertentu
- Biarkan Project/Dept kosong jika saldo umum (tidak terkait proyek/departemen)
- Sistem akan menghitung saldo per dimensi secara terpisah

### Q8: Bagaimana jika saya tidak tahu saldo awal yang tepat?

**Jawaban:**
1. **Konsultasi dengan Akuntan**: Minta bantuan akuntan untuk menentukan saldo awal
2. **Periksa Dokumen**: Bank statement, buku kas, laporan keuangan periode sebelumnya
3. **Rekonsiliasi**: Lakukan rekonsiliasi bank untuk memastikan saldo benar
4. **Estimasi Sementara**: Jika perlu, gunakan estimasi dan buat jurnal penyesuaian nanti

### Q9: Apakah saldo awal bisa negatif?

**Jawaban:**
Secara teknis bisa (overdraft), tapi:
- **Tidak direkomendasikan** untuk setup awal sistem
- Jika rekening memiliki saldo negatif, gunakan Credit untuk bank/kas dan Debit untuk ekuitas
- Konsultasi dengan akuntan sebelum memasukkan saldo negatif

### Q10: Bagaimana cara menghapus jurnal saldo awal yang salah?

**Jawaban:**
1. **Reverse Jurnal** (jika fitur tersedia):
   - Gunakan fitur "Reverse Journal" di menu Journals
   - Sistem akan membuat jurnal pembalik otomatis
2. **Buat Jurnal Pembalik Manual**:
   - Buat jurnal baru dengan debit/credit yang dibalik
   - Gunakan deskripsi yang jelas: "Pembalik Jurnal Saldo Awal [Nomor Jurnal]"
3. **Hapus Jurnal** (jika memungkinkan):
   - Hanya jika jurnal belum mempengaruhi transaksi lain
   - Memerlukan izin khusus dan harus hati-hati

---

## Kesimpulan

Setup saldo awal bank dan kas adalah langkah penting dalam implementasi sistem ERP. Dengan mengikuti panduan ini:

✅ Anda dapat memasukkan saldo awal dengan benar dan akurat  
✅ Sistem akan menghitung saldo rekening dengan benar sejak awal  
✅ Laporan keuangan akan menampilkan informasi yang akurat  
✅ Audit trail akan tersedia untuk semua saldo awal  

**Tips Penting:**
- 📋 Siapkan semua informasi sebelum mulai
- ✅ Verifikasi keseimbangan jurnal sebelum posting
- 📊 Verifikasi saldo setelah posting
- 📝 Dokumentasikan semua jurnal saldo awal
- 🔍 Lakukan rekonsiliasi berkala untuk memastikan akurasi

**Bantuan Lebih Lanjut:**
- Konsultasi dengan tim akuntansi untuk pertanyaan teknis
- Hubungi administrator sistem untuk masalah akses atau izin
- Periksa dokumentasi sistem untuk fitur-fitur lanjutan

---

**Dokumen ini dibuat untuk**: Sistem ERP Sarang  
**Versi**: 1.0  
**Terakhir Diupdate**: 2025-01-22  
**Penulis**: Tim Implementasi ERP Sarang
