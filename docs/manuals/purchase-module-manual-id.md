# Manual Modul Manajemen Pembelian

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Memulai](#memulai)
3. [Ringkasan Fitur](#ringkasan-fitur)
4. [Purchase Order (PO)](#purchase-order-po)
5. [Goods Receipt PO (GRPO)](#goods-receipt-po-grpo)
6. [Purchase Invoice (PI)](#purchase-invoice-pi)
7. [Purchase Payment (PP)](#purchase-payment-pp)
8. [Purchase Analytics](#purchase-analytics)
9. [Tugas Umum](#tugas-umum)
10. [Pemecahan Masalah](#pemecahan-masalah)
11. [Referensi Cepat](#referensi-cepat)

---

## Pengenalan

### Apa itu Modul Manajemen Pembelian?

Modul Manajemen Pembelian mengelola alur procure-to-pay lengkap dan mendukung multi entitas. Modul ini mencakup permintaan pembelian, purchase order, penerimaan barang, penagihan, dan pembayaran dengan penomoran otomatis, persetujuan, pengelolaan pajak, serta integrasi akuntansi.

### Siapa yang Menggunakan Modul Ini?

-   Tim Pembelian: membuat dan menyetujui purchase order.
-   Staf Gudang: menerima barang terhadap PO yang disetujui.
-   Akuntansi/AP: mencatat invoice, mengalokasikan pembayaran, mengelola akrual.
-   Manajemen: memantau KPI, persetujuan, dan kinerja supplier.

### Manfaat Utama

-   Rantai dokumen ujung ke ujung (PO → GRPO → PI → PP).
-   Penomoran otomatis per entitas menggunakan format terpadu (EEYYDDNNNNN).
-   Dukungan multi-mata uang dan kurs.
-   Pajak PPN dan PPh (withholding).
-   Alur persetujuan dan pelacakan penutupan dokumen.

> **Catatan**: Untuk informasi detail tentang sistem penomoran dokumen, lihat [Manual Sistem Penomoran Dokumen](document-numbering-system-manual-id.md).

---

## Memulai

### Akses Menu

-   Purchase Order: `Purchase > Purchase Orders`
-   Goods Receipt PO: `Purchase > Goods Receipt`
-   Purchase Invoice: `Purchase > Purchase Invoices`
-   Purchase Payment: `Purchase > Purchase Payments`
-   Analytics: `Purchase > Analytics`

### Prasyarat

-   Vendor sudah dibuat di modul Business Partner.
-   Item/jasa sudah dibuat di modul Inventory.
-   Gudang sudah dikonfigurasi (hanya gudang non-transit untuk pemilihan manual).
-   Mata uang dan kode pajak terset di ERP Parameters.
-   Entitas perusahaan dikonfigurasi dengan kode entitas (lihat [Manual Sistem Penomoran Dokumen](document-numbering-system-manual-id.md)).
-   Penomoran dokumen dikelola otomatis oleh sistem.

### Alur Umum

1. Buat dan setujui Purchase Order.
2. Terima barang melalui GRPO (menyalin sisa baris).
3. Buat Purchase Invoice berdasarkan penerimaan.
4. Alokasikan Purchase Payment ke invoice.
5. Pantau KPI dan aging di Analytics.

---

## Ringkasan Fitur

-   **Manajemen PO**: Siklus lengkap PO dengan alur vendor-first, baris item/jasa, pajak, multi-mata uang, dan satu gudang tujuan per PO.
-   **Penerimaan**: GRPO dengan filter PO per vendor, salin sisa baris, kolom Remaining Qty, dan gudang default dari PO.
-   **Penagihan**: Purchase Invoice dengan penomoran otomatis, pajak, dan akuntansi AP UnInvoice untuk akrual.
-   **Pembayaran**: Purchase Payment dengan alokasi ke invoice, dukungan multi-mata uang, dan penutupan dokumen.
-   **Analitik**: AP aging, kartu KPI (Purchases MTD, Outstanding AP, Pending Approvals, Open PO), statistik supplier, dan invoice terbaru.

---

## Purchase Order (PO)

### Konsep Utama

-   **Penomoran**: Menggunakan format terpadu `EEYYDDNNNNN` dimana:
    -   `EE` = Kode Entitas (2 digit, mis. 71 untuk PT CSJ, 72 untuk CV CS)
    -   `YY` = Tahun (2 digit, 2 digit terakhir tahun)
    -   `DD` = Kode Dokumen `01` untuk Purchase Order
    -   `NNNNN` = Nomor Urut (5 digit, diisi nol di depan)
    -   Contoh: `71250100001` = PO pertama tahun 2025 untuk entitas 71
-   **Gudang**: Satu gudang tujuan per PO (default ke gudang terpilih, non-transit).
-   **Baris Item vs Jasa**: Item memengaruhi persediaan; jasa tidak.
-   **Pajak**: PPN dan WTax per baris atau per dokumen.
-   **Persetujuan**: Draft → Pending Approval → Approved.
-   **Penutupan**: Otomatis berdasarkan penyelesaian GRPO/PI atau auto-close sesuai konfigurasi.

### Membuat PO

1. Buka `Purchase > Purchase Orders` lalu klik **Add**.
2. Pilih **Vendor** (menentukan filter PO selanjutnya).
3. Pilih **Currency** dan **Exchange Rate** jika mata uang asing.
4. Pilih **Warehouse** (gudang tujuan).
5. Tambah **Lines**:
    - Pilih **Item/Jasa**, isi kuantitas dan harga.
    - Set **VAT** dan **WTax** bila diperlukan.
6. Periksa **Total** (Amount + VAT - WTax).
7. Simpan Draft, lalu ajukan untuk approval.

### Edit & Approval

-   PO Draft bisa diedit; PO Approved mengunci field penting (vendor, gudang, mata uang) kecuali dibuka ulang.
-   Approver bisa menyetujui/menolak; setelah Approved, PO dapat disalin di GRPO.

### Navigasi Dokumen

-   Dari PO, gunakan **Document Navigation** atau **Relationship Map** untuk membuka GRPO/PI/PP terkait.

---

## Goods Receipt PO (GRPO)

### Konsep Utama

-   **Penomoran**: Menggunakan format terpadu `EEYYDDNNNNN` dimana:
    -   `EE` = Kode Entitas (2 digit)
    -   `YY` = Tahun (2 digit)
    -   `DD` = Kode Dokumen `02` untuk Goods Receipt PO
    -   `NNNNN` = Nomor Urut (5 digit)
    -   Contoh: `71250200001` = GRPO pertama tahun 2025 untuk entitas 71
-   **Vendor-First**: Pilih vendor, lalu dropdown PO terfilter oleh vendor.
-   **Copy Remaining Lines**: Otomatis menarik baris PO dengan kuantitas tersisa.
-   **Kolom Remaining Qty**: Menunjukkan saldo per baris untuk mencegah over-receipt.
-   **Gudang Default**: Mengikuti gudang PO; bisa diubah jika diizinkan.
-   **Status**: Draft → Pending Approval → Approved.

### Membuat GRPO

1. Buka `Purchase > Goods Receipt`.
2. Pilih **Vendor** untuk memuat PO Approved yang terkait.
3. Pilih **Purchase Order**; klik **Copy Remaining Lines**.
4. Cek **Remaining Qty** dan sesuaikan kuantitas diterima.
5. Pastikan **Warehouse** dan pajak (jika diperlukan).
6. Ajukan approval; setelah Approved, stok dan jurnal diperbarui.

### Tips

-   Gunakan modal pemilihan item untuk menampilkan item dari PO yang dipilih saja.
-   Untuk penerimaan parsial, sesuaikan kuantitas sesuai Remaining Qty agar tidak terblokir.
-   Gunakan **Preview Journal** (jika tersedia) untuk memvalidasi entri persediaan dan AP.

---

## Purchase Invoice (PI)

### Konsep Utama

-   **Penomoran**: Menggunakan format terpadu `EEYYDDNNNNN` dimana:
    -   `EE` = Kode Entitas (2 digit)
    -   `YY` = Tahun (2 digit)
    -   `DD` = Kode Dokumen `03` untuk Purchase Invoice
    -   `NNNNN` = Nomor Urut (5 digit)
    -   Contoh: `71250300001` = Purchase Invoice pertama tahun 2025 untuk entitas 71
-   **Sumber Data**: Dapat dibuat dari kuantitas GRPO atau langsung tanpa PO/GRPO (Direct Purchase).
-   **Metode Pembayaran**:
    -   **Credit**: Pembayaran akan dilakukan kemudian (membutuhkan Purchase Payment)
    -   **Cash**: Pembayaran tunai langsung (tidak membutuhkan Purchase Payment)
-   **Akuntansi**:
    -   **Credit Purchase**: Menggunakan akun perantara AP UnInvoice; pindah ke AP saat dibayar
    -   **Direct Cash Purchase**: Langsung Debit Inventory, Credit Cash (tidak membutuhkan Purchase Payment)
-   **Multi-Mata Uang**: Simpan kurs pada tanggal invoice; menyimpan nilai dasar dan asing.
-   **Penutupan**: Melacak kuantitas yang ditagih vs diterima; menutup ketika selesai.

### Membuat PI

-   Masuk ke `Purchase > Purchase Invoices` lalu klik **Add**.
-   Pilih **Vendor**; pilih GRPO/PO terkait untuk impor baris (jika tersedia).
-   Pilih **Payment Method**:
    -   **Credit**: Untuk pembelian dengan termin pembayaran
    -   **Cash**: Untuk pembelian tunai langsung (akan otomatis menjadi Direct Purchase)
-   Pastikan **Currency** dan **Exchange Rate**.
-   Tambahkan atau impor **Lines** dengan kuantitas, harga, PPN, dan WTax.
-   Untuk **Direct Cash Purchase**:
    -   Pilih **Item** dari inventory (bukan hanya account)
    -   Pilih **Warehouse** untuk item fisik
    -   Pilih **UOM** (Unit of Measure) jika berbeda dari base unit
    -   Pilih **Cash Account** jika ingin menggunakan akun kas selain default (Kas di Tangan)
-   Periksa **Total**; pastikan pajak sesuai aturan.
-   Simpan dan setujui untuk memposting akrual.

### Direct Cash Purchase (Pembelian Tunai Langsung)----

**Kapan Menggunakan**:

-   Pembelian barang dengan pembayaran tunai langsung (struk/bon sudah dibayar)
-   Tidak melalui alur PO → GRPO → PI → PP
-   Ingin workflow yang lebih sederhana: **PI → Post** (selesai)

**Cara Kerja**:

1. Pilih **Payment Method = Cash**
2. Sistem otomatis mengatur sebagai Direct Purchase (tidak perlu checkbox)
3. Pilih **Item** dari inventory (account otomatis terpilih berdasarkan kategori item)
4. Pilih **Warehouse** dan **UOM** jika diperlukan
5. Pilih **Cash Account** (opsional, default: Kas di Tangan)
6. **Post** invoice → Transaksi selesai!

**Akuntansi Direct Cash Purchase**:

-   **Debit**: Inventory Account (dari kategori item)
-   **Credit**: Cash Account (Kas di Tangan atau yang dipilih)
-   **Tidak membutuhkan Purchase Payment** karena kas sudah dikredit saat posting

**Inventory Transaction**:

-   Otomatis membuat transaksi inventory (purchase)
-   Stock bertambah sesuai quantity
-   Unit cost tercatat untuk valuasi

**Keuntungan**:

-   ✅ Workflow lebih sederhana (2 langkah: Create → Post)
-   ✅ Tidak perlu membuat Purchase Payment terpisah
-   ✅ Cash outflow langsung tercatat
-   ✅ Inventory langsung terupdate
-   ✅ Cocok untuk pembelian kecil/harian

### Credit Purchase (Pembelian Kredit)

**Kapan Menggunakan**:

-   Pembelian dengan termin pembayaran (mis. 30 hari)
-   Perlu tracking accounts payable
-   Pembayaran akan dilakukan kemudian

**Cara Kerja**:

1. Pilih **Payment Method = Credit**
2. Buat PI dari GRPO atau langsung
3. **Post** invoice → Membuat liability (Utang Dagang)
4. Buat **Purchase Payment** untuk membayar invoice
5. Alokasikan pembayaran ke invoice

**Akuntansi Credit Purchase**:

-   **Post PI**: Debit AP UnInvoice, Credit Utang Dagang
-   **Post PP**: Debit Utang Dagang, Credit Cash

### Kesiapan Alokasi Pembayaran

-   Invoice **Credit** yang sudah Approved muncul di daftar alokasi Purchase Payment.
-   Invoice **Direct Cash Purchase** tidak membutuhkan Purchase Payment (sudah dibayar tunai).
-   Gunakan **Document Navigation** untuk memeriksa keterkaitan GRPO/PO.

---

## Purchase Payment (PP)

### Konsep Utama

-   **Penomoran**: Menggunakan format terpadu `EEYYDDNNNNN` dimana:
    -   `EE` = Kode Entitas (2 digit)
    -   `YY` = Tahun (2 digit)
    -   `DD` = Kode Dokumen `04` untuk Purchase Payment
    -   `NNNNN` = Nomor Urut (5 digit)
    -   Contoh: `71250400001` = Purchase Payment pertama tahun 2025 untuk entitas 71
-   **Alokasi**: Pembayaran dialokasikan ke satu atau beberapa invoice Approved.
-   **Mata Uang**: Mendukung mata uang pembayaran dengan kurs.
-   **Akuntansi**: Mengkredit kas/bank, mendebit AP; membersihkan AP UnInvoice.
-   **Penutupan**: Pembayaran menutup invoice ketika alokasi penuh.

### Membuat PP

1. Buka `Purchase > Purchase Payments` lalu klik **Add**.
2. Pilih **Vendor** dan **Payment Method/Account**.
3. Set **Currency** dan **Exchange Rate** jika diperlukan.
4. Pilih **Invoice** untuk dialokasikan; sistem menampilkan saldo outstanding.
5. Konfirmasi alokasi lalu submit. Setelah Approved, status invoice dan penutupan diperbarui.

### Praktik Terbaik

-   Pastikan alokasi pas untuk menghindari selisih kecil.
-   Untuk uang muka, catat prepayment lalu alokasikan saat invoice terbit sesuai kebijakan.

---

## Purchase Analytics

### Dashboard & KPI

-   **AP Aging**: Bucket berdasarkan jatuh tempo untuk memantau outstanding AP.
-   **KPI**: Purchases MTD, Outstanding AP, Pending Approvals, Open PO.
-   **Statistik**: Jumlah/nilai PO/PI/GRPO; indikator kinerja supplier.
-   **Invoice Terbaru**: Ringkasan invoice AP terbaru.

### Cara Pakai

-   Filter berdasarkan entitas, rentang tanggal, dan vendor untuk analisis terarah.
-   Tindak lanjuti bucket aging yang tinggi dan invoice jatuh tempo.
-   Pantau Pending Approvals agar dokumen tidak tertahan.

---

## Tugas Umum

-   **Buat PO multi-mata uang**: Set mata uang dan kurs; harga disimpan dalam nilai asing dan dasar.
-   **Terima parsial**: Di GRPO, salin remaining lines, sesuaikan qty, approve; saldo tetap di PO.
-   **Buat invoice dari penerimaan**: Impor baris GRPO, cek kuantitas dan pajak, approve PI.
-   **Alokasi pembayaran ke banyak invoice**: Tambah alokasi per invoice sampai total sesuai pembayaran.
-   **Lihat rantai dokumen**: Gunakan Relationship Map untuk PO → GRPO → PI → PP.

---

## Pemecahan Masalah

-   **PO tidak muncul di dropdown GRPO**: Pastikan PO Approved, vendor sama, dan masih ada qty tersisa.
-   **Over-receipt terblokir**: Cek kolom Remaining Qty; sesuaikan kuantitas ke saldo yang tersedia.
-   **Invoice tidak bisa dialokasikan**: Pastikan GRPO Approved dan invoice Approved; cek saldo outstanding.
-   **Kurs pembayaran tidak sesuai**: Perbarui kurs sesuai tanggal pembayaran; hitung ulang alokasi.
-   **Penutupan dokumen salah**: Buka kembali jika diizinkan, segarkan perhitungan, dan pastikan auto-close benar.

---

## Referensi Cepat

-   **Format Penomoran**: Semua dokumen menggunakan format `EEYYDDNNNNN` (11 karakter):
    -   Purchase Order: Kode Dokumen `01` (mis. `71250100001`)
    -   Goods Receipt PO: Kode Dokumen `02` (mis. `71250200001`)
    -   Purchase Invoice: Kode Dokumen `03` (mis. `71250300001`)
    -   Purchase Payment: Kode Dokumen `04` (mis. `71250400001`)
    -   Lihat [Manual Sistem Penomoran Dokumen](document-numbering-system-manual-id.md) untuk detail
-   **Alur**: PO → GRPO → PI → PP dengan approval tiap tahap.
-   **Pajak**: PPN + Withholding; diterapkan per baris atau per dokumen sesuai konfigurasi.
-   **Multi-mata uang**: Rekam mata uang dan kurs pada PO/PI/PP; analitik ditampilkan dalam mata uang dasar.
-   **Gudang**: Satu gudang tujuan per PO/GRPO; default dari PO; gudang transit tidak untuk pilihan manual.
-   **Kode Entitas**: Dua digit pertama mengidentifikasi entitas hukum (71=PT CSJ, 72=CV CS).
