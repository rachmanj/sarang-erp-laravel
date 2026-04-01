# Manual Part Number (Nomor Part) Inventory

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Konsep Part Number](#konsep-part-number)
3. [Mengatur Part Number pada Item](#mengatur-part-number-pada-item)
4. [Memilih Part Number di Dokumen](#memilih-part-number-di-dokumen)
5. [Tampilan di Cetak Dokumen](#tampilan-di-cetak-dokumen)
6. [Penyalinan ke Dokumen Turunan](#penyalinan-ke-dokumen-turunan)
7. [Tugas Umum](#tugas-umum)
8. [Pemecahan Masalah](#pemecahan-masalah)

---

## Pengenalan

### Apa itu Part Number?

**Part Number** adalah nomor identifikasi alternatif untuk item inventory selain kode internal (mis. `BAT000001`). Contoh part number:

- **Nomor part pelanggan**: Pelanggan A menggunakan nomor `BOSCH-70A` untuk item yang sama
- **Nomor part pabrikan**: Kode pabrikan dari vendor
- **Nomor part supplier**: Kode yang digunakan supplier

### Mengapa Memakai Part Number?

- **Dokumen ke pelanggan**: Menampilkan nomor part pelanggan agar dokumen mudah dikenali
- **Dokumen ke vendor**: Menampilkan nomor part supplier agar pemesanan lebih jelas
- **Satu item, banyak nomor**: Satu item bisa punya beberapa part number untuk berbagai pihak

### Siapa yang Menggunakan?

- **Tim Pembelian**: Memilih part number supplier saat buat PO
- **Tim Penjualan**: Memilih part number pelanggan saat buat SO, DO, atau Invoice
- **Staf Gudang**: Part number ikut tercetak di dokumen delivery
- **Admin Inventory**: Mengatur part number di master data item

---

## Konsep Part Number

### Per Item

- Setiap item inventory bisa punya **lebih dari satu** part number
- Satu part number bisa ditandai sebagai **default**
- Default digunakan otomatis saat item dipilih di dokumen (bisa diubah per baris)

### Per Baris Dokumen

- **Per baris**: User memilih part number mana yang dipakai
- **Opsi "Internal code"**: Jika tidak dipilih part number, tampil kode internal saja
- **Tampilan**: Dokumen selalu menampilkan **Item Code** (kode internal) dan **Part No.** (jika dipilih)

### Tabel Part Number

| Kolom | Keterangan |
|-------|------------|
| Part Number | Nomor part (wajib, unik per item) |
| Description | Deskripsi opsional (mis. "Customer PN", "Supplier code") |
| Default | Centang satu untuk default per item |

---

## Mengatur Part Number pada Item

### Langkah

1. Buka **Inventory > Inventory Items**
2. Klik **Edit** pada item yang ingin diatur
3. Scroll ke bagian **Part Numbers**
4. Isi:
   - **Part Number**: Masukkan nomor part (mis. `BOSCH-70A`)
   - **Description**: Deskripsi opsional (mis. `Customer PN`)
   - **Default**: Centang untuk menjadikan default
5. Klik **Add Part Number** untuk menambah ke daftar
6. Ulangi untuk part number lain
7. Klik **Update Item** untuk menyimpan

### Contoh

Item: `BAT000001` (Battery Bosch 70A Dry MF)

| Part Number | Description | Default |
|-------------|-------------|---------|
| BOSCH-70A | Customer PN | ✓ |
| 0 092 S0 105 | Manufacturer PN | |
| SUP-BAT-001 | Supplier code | |

### Catatan

- Part number harus unik per item (tidak boleh duplikat)
- Hanya satu default per item
- Hapus part number dengan tombol **Remove** (ikon tempat sampah)

---

## Memilih Part Number di Dokumen

### Dokumen yang Mendukung

| Dokumen | Lokasi Pilihan |
|---------|----------------|
| Purchase Order | Baris item, dropdown setelah pilih item |
| Sales Order | Baris item, dropdown setelah pilih item |
| Delivery Order | Disalin dari SO |
| Sales Invoice | Disalin dari DO |
| Sales Quotation | Baris item |
| Purchase Invoice | Disalin dari PO (service) |
| GR/GI | Baris item |

### Cara Memilih (Purchase Order / Sales Order)

1. Buat atau edit dokumen PO/SO
2. Pilih baris, klik **Search** (ikon kaca pembesar) untuk pilih item
3. Di modal, pilih item yang diinginkan
4. Setelah item terpilih, muncul **dropdown Part Number** di bawah item
5. Pilih:
   - **Internal code** (default jika kosong): Hanya tampilkan kode internal
   - **Part number** (mis. BOSCH-70A - Customer PN): Tampilkan part number di dokumen
6. Jika item punya default part number, dropdown otomatis terisi default

### Dropdown Part Number

- Muncul **hanya** jika item punya part number
- Jika item tidak punya part number, dropdown disembunyikan
- Default part number otomatis terpilih saat item dipilih

---

## Tampilan di Cetak Dokumen

### Kolom di Dokumen Cetak

| Kolom | Keterangan |
|-------|------------|
| Item Code | Kode internal item (selalu tampil) |
| Part No. | Part number yang dipilih (atau "-" jika tidak dipilih) |
| Description | Nama/deskripsi item |

### Contoh Tampilan Cetak

```
No | Item Code  | Part No.   | Description              | Qty  | Unit Price | Amount
---|------------|------------|---------------------------|------|------------|--------
1  | BAT000001  | BOSCH-70A  | Battery Bosch 70A Dry MF  | 1.00 | 1,051,843  | 1,051,843
2  | WOR000037  | -          | Accu Dimineralisasi       | 2.00 | 77,500     | 155,000
```

- Baris 1: Part number dipilih → tampil `BOSCH-70A`
- Baris 2: Part number tidak dipilih → tampil `-`

### Template Cetak yang Mendukung

- Purchase Order: Semua layout (Standard, PT CSJ, CV Saranghae, Dot Matrix)
- Delivery Order: Semua layout
- Sales Invoice: Template cetak
- Sales Order: Halaman show
- Sales Quotation: Template cetak

---

## Penyalinan ke Dokumen Turunan

Part number **otomatis disalin** saat dokumen dibuat dari dokumen lain:

| Dari | Ke | Part Number |
|------|-----|-------------|
| Sales Order | Delivery Order | ✓ Disalin |
| Delivery Order | Sales Invoice | ✓ Disalin |
| Sales Quotation | Sales Order | ✓ Disalin |
| Purchase Order (service) | Purchase Invoice | ✓ Disalin |

Tidak perlu memilih ulang part number di dokumen turunan.

---

## Tugas Umum

### Menambah Part Number untuk Item Baru

1. Inventory > Inventory Items > Edit item
2. Part Numbers > Isi Part Number, Description, Default
3. Add Part Number > Update Item

### Mengganti Part Number di Baris PO/SO

1. Buka dokumen PO/SO (create atau edit)
2. Di baris yang bersangkutan, klik dropdown Part Number
3. Pilih part number lain atau "Internal code"

### Mengatur Default Part Number

1. Edit item di Inventory
2. Di Part Numbers, centang **Default** pada part number yang diinginkan
3. Update Item (hanya satu default per item)

---

## Pemecahan Masalah

### Dropdown Part Number tidak muncul

- **Penyebab**: Item belum punya part number
- **Solusi**: Tambah part number di Inventory > Edit item > Part Numbers

### Part number default tidak terpilih otomatis

- **Penyebab**: Belum ada part number yang ditandai default
- **Solusi**: Edit item, centang Default pada part number yang diinginkan

### Part number tidak tampil di cetak

- **Penyebab**: Part number tidak dipilih di baris dokumen
- **Solusi**: Edit dokumen, pilih part number di dropdown per baris

### Error "Part number sudah ada"

- **Penyebab**: Part number duplikat untuk item yang sama
- **Solusi**: Gunakan part number lain atau hapus yang duplikat

---

## Referensi Cepat

| Aksi | Lokasi |
|------|--------|
| Tambah part number | Inventory > Edit Item > Part Numbers |
| Pilih part number di PO | PO Create/Edit > Baris > Dropdown Part Number |
| Pilih part number di SO | SO Create/Edit > Baris > Dropdown Part Number |
| Lihat part number di cetak | Print dokumen > Kolom "Part No." |
