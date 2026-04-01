# Manual Proyek Pelanggan (Customer's Project)

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Prasyarat](#prasyarat)
3. [Mengelola Proyek Pelanggan](#mengelola-proyek-pelanggan)
4. [Menggunakan Proyek di Dokumen Penjualan](#menggunakan-proyek-di-dokumen-penjualan)
5. [Alur Konversi Dokumen](#alur-konversi-dokumen)
6. [Tampilan di Dokumen Cetak](#tampilan-di-dokumen-cetak)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)
10. [Referensi Cepat](#referensi-cepat)

---

## Pengenalan

### Apa itu Proyek Pelanggan?

Proyek Pelanggan (Customer's Project) adalah fitur yang memungkinkan Anda mengelompokkan transaksi penjualan berdasarkan proyek atau job tertentu milik pelanggan. Fitur ini berguna untuk:

- **Pelacakan pendapatan per proyek**: Melihat revenue yang dihasilkan dari setiap proyek pelanggan
- **Manajemen job**: Mengaitkan penawaran, pesanan, pengiriman, dan faktur ke proyek tertentu
- **Pelaporan**: Membuat laporan berdasarkan proyek pelanggan
- **Koordinasi tim**: Tim penjualan dan operasional dapat mengidentifikasi dokumen terkait proyek dengan mudah

### Siapa yang Dapat Menggunakan Fitur Ini?

- **Tim Penjualan**: Menambahkan proyek ke pelanggan dan memilih proyek saat membuat dokumen penjualan
- **Tim Operasional**: Melihat proyek pada Surat Jalan (DO) untuk koordinasi pengiriman
- **Tim Keuangan**: Melacak faktur per proyek untuk penagihan dan pelaporan
- **Manajer**: Memantau kinerja per proyek pelanggan

### Cakupan Fitur

- Proyek dikelola **per pelanggan** (Business Partner tipe Customer)
- Proyek muncul di **level header** dokumen (bukan per baris)
- Proyek **otomatis terbawa** saat dokumen dikonversi (Quotation → SO → DO → Invoice)
- Proyek ditampilkan di **halaman detail** dan **dokumen cetak**

---

## Prasyarat

Sebelum menggunakan fitur Proyek Pelanggan, pastikan:

1. ✅ **Business Partner sudah ada** dan bertipe **Customer**
2. ✅ **Anda memiliki izin** untuk mengelola Business Partner dan dokumen penjualan
3. ✅ **Pelanggan sudah terdaftar** di modul Business Partner

**Catatan**: Tab "Customer's Projects" hanya muncul untuk Business Partner dengan tipe **Customer**. Supplier tidak memiliki fitur ini.

---

## Mengelola Proyek Pelanggan

### Mengakses Tab Proyek Pelanggan

1. Navigasi ke **Business Partner** → **Business Partners**
2. Klik nama pelanggan yang ingin dikelola proyeknya
3. Klik tab **"Customer's Projects"**

### Menambahkan Proyek Baru

1. Di tab Customer's Projects, klik tombol **"Add Project"**
2. Isi form:
   - **Code** (Wajib): Kode unik proyek, misalnya PRJ001, PROJ-2026-01
   - **Name** (Wajib): Nama proyek, misalnya "Ekspansi Retail 2026", "Proyek Renovasi Gedung A"
   - **Description** (Opsional): Deskripsi detail proyek
   - **Status**: Active, Completed, On Hold, atau Cancelled
   - **Start Date** (Opsional): Tanggal mulai proyek
   - **End Date** (Opsional): Tanggal selesai proyek
3. Klik **"Add Project"**

**Contoh**:
- Code: `PRJ001`
- Name: `Ekspansi Retail 2026`
- Status: `Active`

### Mengedit Proyek

1. Di daftar proyek, klik tombol **Edit** (ikon pensil) pada baris proyek
2. Ubah field yang diperlukan
3. Klik **"Update Project"**

### Menghapus Proyek

1. Di daftar proyek, klik tombol **Delete** (ikon tempat sampah) pada baris proyek
2. Konfirmasi penghapusan

**Peringatan**: Proyek yang sudah terhubung ke dokumen penjualan sebaiknya tidak dihapus. Pertimbangkan mengubah status menjadi "Cancelled" atau "Completed" sebagai gantinya.

---

## Menggunakan Proyek di Dokumen Penjualan

### Di Sales Quotation (Penawaran)

1. Saat membuat atau mengedit **Sales Quotation**, pilih **Customer** terlebih dahulu
2. Setelah customer dipilih, dropdown **"Customer's Project"** akan terisi otomatis dengan proyek-proyek milik customer tersebut
3. Pilih proyek (opsional) dari dropdown
4. Simpan quotation

**Catatan**: Pilih customer dulu sebelum memilih proyek. Dropdown proyek akan kosong sampai customer dipilih.

### Di Sales Order (Pesanan Penjualan)

1. Saat membuat atau mengedit **Sales Order**, pilih **Customer**
2. Pilih **"Customer's Project"** dari dropdown (opsional)
3. Simpan sales order

**Saat konversi dari Quotation**: Jika quotation memiliki proyek, proyek akan otomatis terbawa ke Sales Order.

### Di Delivery Order (Surat Jalan)

Proyek **tidak perlu dipilih manual** saat membuat Delivery Order dari Sales Order. Proyek akan **otomatis terbawa** dari Sales Order yang dipilih.

### Di Sales Invoice (Faktur Penjualan)

1. Saat membuat Sales Invoice dari Delivery Order, proyek akan **otomatis terisi** dari DO
2. Anda dapat mengubah proyek di form jika diperlukan (untuk invoice draft)
3. Saat edit invoice draft, proyek dapat diubah

---

## Alur Konversi Dokumen

Proyek pelanggan **otomatis terbawa** saat dokumen dikonversi:

```
Sales Quotation (pilih proyek)
    ↓ Convert to Sales Order
Sales Order (proyek terbawa otomatis)
    ↓ Create Delivery Order
Delivery Order (proyek terbawa otomatis)
    ↓ Create Sales Invoice
Sales Invoice (proyek terbawa otomatis)
```

**Ringkasan**:
- **Quotation → Sales Order**: Proyek disalin dari quotation
- **Sales Order → Delivery Order**: Proyek disalin dari sales order
- **Delivery Order → Sales Invoice**: Proyek disalin dari delivery order (atau dari DO pertama jika multi-DO)

---

## Tampilan di Dokumen Cetak

Proyek pelanggan ditampilkan di dokumen cetak berikut:

### Delivery Order (Surat Jalan)

- **Print Standar**: Baris "Customer's Project" muncul setelah baris Customer (jika proyek ada)
- **Print Dot Matrix**: Baris "Project" muncul setelah baris Customer (jika proyek ada)

### Sales Invoice (Faktur Penjualan)

- **Print Standar**: "Customer's Project" muncul di bagian info invoice (jika proyek ada)
- **Print Dot Matrix**: Baris "Project" muncul setelah "Bill To" (jika proyek ada)

**Catatan**: Jika dokumen tidak memiliki proyek, baris proyek tidak ditampilkan di cetakan.

---

## Best Practices

### 1. Buat Proyek Sebelum Transaksi

Tambahkan proyek ke pelanggan sebelum membuat quotation atau sales order, agar proyek tersedia di dropdown.

### 2. Gunakan Kode yang Konsisten

Gunakan format kode proyek yang konsisten, misalnya:
- `PRJ-YYYY-NN` (PRJ-2026-01, PRJ-2026-02)
- `{CUSTOMER_CODE}-{PROJECT}` (CUST001-RETAIL)

### 3. Update Status Proyek

Ubah status proyek menjadi "Completed" atau "Cancelled" ketika proyek selesai, daripada menghapus proyek.

### 4. Pilih Proyek di Tahap Awal

Pilih proyek di Sales Quotation atau Sales Order. Proyek akan terbawa ke dokumen berikutnya, mengurangi risiko lupa memilih di tahap lanjutan.

### 5. Verifikasi di Dokumen Cetak

Sebelum mencetak DO atau Invoice, pastikan proyek sudah benar di halaman detail. Proyek akan muncul di dokumen cetak.

---

## Troubleshooting

### Masalah: Dropdown "Customer's Project" kosong

**Penyebab**: Customer belum memiliki proyek, atau customer belum dipilih

**Solusi**:
1. Pastikan Anda sudah memilih customer terlebih dahulu
2. Buka halaman Business Partner → pilih customer → tab Customer's Projects
3. Tambahkan proyek jika belum ada

### Masalah: Tab "Customer's Projects" tidak terlihat

**Penyebab**: Business Partner bukan tipe Customer

**Solusi**: Tab Customer's Projects hanya tersedia untuk Business Partner dengan tipe **Customer**. Supplier tidak memiliki fitur ini.

### Masalah: Proyek tidak terbawa saat konversi Quotation ke SO

**Penyebab**: Kemungkinan bug atau quotation tidak memiliki proyek

**Solusi**:
1. Verifikasi quotation memiliki proyek yang dipilih
2. Saat convert, proyek seharusnya terbawa otomatis
3. Jika tidak terbawa, pilih proyek manual di form Sales Order
4. Hubungi administrator jika masalah berlanjut

### Masalah: Proyek tidak muncul di dokumen cetak

**Penyebab**: Dokumen tidak memiliki proyek yang terpilih

**Solusi**:
1. Buka halaman detail DO atau Invoice
2. Periksa apakah "Customer's Project" ditampilkan
3. Jika tidak ada, edit dokumen (jika masih draft) dan pilih proyek
4. Untuk DO dari SO: pastikan SO memiliki proyek; buat ulang DO jika perlu

---

## FAQ

### Q1: Apakah proyek wajib diisi?

**A**: Tidak. Proyek bersifat opsional. Anda dapat membuat dokumen penjualan tanpa memilih proyek.

### Q2: Bisakah satu pelanggan memiliki banyak proyek?

**A**: Ya. Satu pelanggan dapat memiliki banyak proyek. Pilih proyek yang sesuai untuk setiap dokumen.

### Q3: Apakah proyek di Sales Invoice bisa berbeda dari DO?

**A**: Untuk invoice yang dibuat dari DO, proyek awalnya diisi dari DO. Jika invoice masih draft, Anda dapat mengubah proyek saat edit.

### Q4: Bagaimana cara melaporkan revenue per proyek?

**A**: Gunakan field `business_partner_project_id` di tabel `sales_invoices` untuk membuat laporan. Laporan "Revenue by Customer Project" dapat dikembangkan berdasarkan data ini.

### Q5: Apakah proyek pelanggan sama dengan proyek internal (Projects)?

**A**: Tidak. Proyek pelanggan (Customer's Project) adalah entitas terpisah dari proyek internal. Proyek pelanggan dikelola per Business Partner Customer, sedangkan proyek internal ada di modul Master Data.

### Q6: Bisakah saya menambah proyek saat dokumen sudah approved?

**A**: Untuk dokumen yang sudah approved/confirmed, perubahan terbatas. Untuk Sales Order draft, Anda dapat mengedit dan menambah proyek. Untuk DO dan Invoice, tergantung status dokumen.

---

## Referensi Cepat

### Menambah Proyek ke Pelanggan

1. Business Partner → Pilih Customer → Tab "Customer's Projects"
2. Klik "Add Project"
3. Isi Code, Name, Status
4. Simpan

### Memilih Proyek di Dokumen Penjualan

1. **Quotation/SO/Invoice**: Pilih Customer → Pilih "Customer's Project" (opsional)
2. **DO**: Otomatis dari Sales Order

### Rute Penting

- Business Partners: `/business-partners`
- Customer Detail: `/business-partners/{id}` (tab Customer's Projects)
- Sales Quotations: `/sales-quotations`
- Sales Orders: `/sales-orders`
- Delivery Orders: `/delivery-orders`
- Sales Invoices: `/sales-invoices`

### Dokumen yang Menampilkan Proyek

| Dokumen        | Halaman Detail | Print Standar | Print Dot Matrix |
|----------------|----------------|---------------|------------------|
| Sales Quotation| ✅             | ✅            | -                |
| Sales Order    | ✅             | -             | -                |
| Delivery Order | ✅             | ✅            | ✅               |
| Sales Invoice  | ✅             | ✅            | ✅               |

---

**Terakhir Diperbarui**: 2026-02-27  
**Versi**: 1.0
