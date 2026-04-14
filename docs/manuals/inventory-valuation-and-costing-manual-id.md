# Valuasi persediaan dan biaya — referensi HELP (Sarang ERP)

Dokumen ini melengkapi **Manual Modul Inventory** dengan penjelasan teknis dan praktik yang dipakai di Sarang ERP terkait **metode valuasi**, **biaya persediaan (HPP)**, dan **harga dari dokumen sumber**. Cocok untuk pertanyaan HELP seperti: *FIFO*, *rata-rata*, *unit cost*, *harga beli jual*, *ganti metode valuasi*.

---

## Pilihan metode valuasi pada item inventory

Pada form item (buat/edit), sistem menawarkan **tiga** metode valuasi stok:

- **FIFO** — label di layar; dipakai untuk perhitungan biaya mengacu pada data pembelian.
- **LIFO** — biaya dihitung dengan logika lapisan dari arah pembelian terbaru (sesuai implementasi kode).
- **Rata-rata tertimbang (weighted average)** — istilah di layar untuk metode rata-rata berbasis transaksi pembelian.

**Catatan:** Tidak ada mode valuasi bernama “Manual” di pilihan metode pada item; penyesuaian stok tetap memasukkan biaya per unit secara manual pada transaksi penyesuaian.

---

## Cara aplikasi menghitung biaya per unit (untuk nilai persediaan dan HPP)

Perhitungan memakai **hanya transaksi bertipe pembelian** (`purchase`) pada item: dijumlah total biaya pembelian dan total kuantitas pembelian (seluruh riwayat pembelian yang tercatat untuk item tersebut).

- **FIFO dan Rata-rata tertimbang** — pada implementasi saat ini **rumusnya sama**:  
  **biaya rata-rata = jumlah total biaya semua pembelian ÷ jumlah kuantitas semua pembelian.**  
  Ini setara dengan **rata-rata tertimbang seluruh pembelian**, bukan konsumsi lapisan “yang masuk pertama keluar pertama” per unit secara ketat seperti teks buku akuntansi FIFO klasik.

- **LIFO** — sistem menghitung biaya dengan menjumlahkan kontribusi dari batch pembelian mulai dari **yang terbaru** sampai kuantitas yang dianggap masih mewakili stok (sesuai fungsi perhitungan di layanan inventory).

- Jika **belum ada** transaksi pembelian untuk item, biaya dapat mengacu ke **harga beli default** pada master item.

**Dampak pada layar:** Di halaman detail item, kolom **Unit Cost** pada **Recent Transactions** menunjukkan **biaya persediaan / valuasi** yang dipakai untuk transaksi tersebut (bukan harga jual ke pelanggan dan tidak selalu sama dengan harga baris faktur pembelian terakhir).

---

## Perbedaan kolom Unit Cost dan Harga beli atau jual (dokumen)

Pada tabel **Recent Transactions** di halaman detail item:

- **Unit Cost** — **biaya persediaan per unit** menurut logika valuasi dan posting transaksi (termasuk saat pengurangan stok karena pengiriman penjualan).
- **Harga beli / jual** — **harga dari dokumen sumber** jika dapat dihubungkan ke sistem:
  - pembelian: baris faktur pembelian (harga net per unit bila ada diskon, atau harga satuan);
  - penjualan: harga pada baris **Delivery Order** atau **Sales Order** sesuai referensi transaksi.

Gunakan kolom **Harga beli / jual** untuk melihat **harga transaksi komersial**; gunakan **Unit Cost** untuk **biaya persediaan** yang dipakai secara internal.

---

## Bisnis perdagangan: beli karena sudah ada pesanan dan stok tipis

Banyak perusahaan perdagangan membeli barang setelah ada order pelanggan sehingga **stok sering tipis**. Dalam kondisi itu:

- **FIFO (makna akuntansi klasik)** dan **rata-rata tertimbang** sering memberi hasil **mirip** jika tidak ada banyak lapisan stok yang tertahan lama.
- **FIFO** dalam arti “jejak per batch pesanan” lebih dekat ke **identifikasi khusus** atau pencatatan per PO; di aplikasi ini, pilihan **FIFO** mengikuti **rumus rata-rata pembelian** seperti di atas.
- Jika yang diutamakan adalah **satu angka biaya yang sederhana** dan barang homogen, **rata-rata tertimbang** (dan perilaku FIFO saat ini yang sejenis) biasanya **cukup**; jika yang diutamakan adalah **alokasi biaya mengikuti urutan fisik atau batch**, diskusikan dengan akuntan apakah kebutuhan pelaporan memerlukan prosedur atau sistem yang melacak lapisan secara eksplisit.

---

## Mengganti metode valuasi setelah sistem dipakai (go-live)

**Secara prinsip bisa**, tetapi ini **kebijakan akuntansi**, bukan sekadar mengubah dropdown:

- Perubahan mempengaruhi **perhitungan biaya ke depan** sesuai logika aplikasi; data historis yang sudah terposting **tidak otomatis** dibuat ulang seperti jika dari awal memakai metode lain.
- Mungkin diperlukan **tanggal efektif**, **penyesuaian manual**, atau kebijakan **prospektif** — tergantui standar pelaporan dan keputusan akuntan/auditor.
- Untuk **perpajakan dan pelaporan resmi**, tanyakan aturan yang berlaku di yurisdiksi Anda sebelum mengganti metode.

Disarankan **mendokumentasikan** metode yang dipilih dan tanggal perubahan di kebijakan internal perusahaan.

---

## Kata kunci untuk HELP (Bahasa Indonesia)

valuasi persediaan, metode FIFO, LIFO, rata-rata tertimbang, weighted average, unit cost, biaya persediaan, HPP, harga beli, harga jual, faktur pembelian, delivery order, sales order, ganti metode valuasi, perdagangan, stok tipis, Recent Transactions, item inventory
