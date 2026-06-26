# Perbaikan Lapisan FIFO — referensi HELP (Sarang ERP)

Gunakan panduan ini jika **persetujuan GR/GI**, **posting purchase invoice**, atau **valuasi persediaan** gagal dengan pesan seperti *Insufficient FIFO inventory layers to consume* — sering setelah duplikat baris pembelian PI dihapus atau transfer gudang meninggalkan stok tanpa lapisan FIFO yang sesuai.

---

## Lokasi menu

- **Sidebar**: **Inventory → FIFO Layer Repair**
- **Menu Search** (navbar): ketik `FIFO`, `layer repair`, atau `insufficient FIFO`
- **Halaman detail item**: jika item FIFO bermasalah, banner peringatan mengarah ke layar perbaikan
- **Izin**: `inventory.adjust`

---

## Kapan digunakan

Gunakan **FIFO Layer Repair** untuk item **valuasi FIFO** (`valuation_method = fifo`, barang fisik) bila:

- Stok on-hand terlihat benar tetapi **replay FIFO strict** gagal pada penjualan/transfer/adjustment lama
- **Qty lapisan FIFO toleran** lebih kecil dari **stok saat ini** (lapisan kurang)
- Anda sudah membersihkan duplikat transaksi PI dan penjualan berikutnya tidak cukup lapisan

**Jangan gunakan** untuk item weighted average, jasa, atau penyesuaian stok rutin — gunakan **GR/GI** atau alur adjustment standar.

---

## Langkah self-service

1. Buka **FIFO Layer Repair** — daftar item FIFO yang perlu perhatian (cari kode, nama, atau ID item).
2. Buka item — tampilan menampilkan stok vs net transaksi, error replay, **defisit** (gudang, qty kurang, unit cost, transaksi gagal), dan **stok setelah perbaikan**.
3. Klik **Apply FIFO repair** — sistem menambah transaksi **adjustment** (`reference_type = fifo_layer_repair`) **backdate** sebelum movement keluar pertama yang gagal, lalu hitung ulang stok gudang dan valuasi.
4. Ulangi operasi yang gagal (mis. approve GR/GI) setelah status **ok**.

---

## Perbaikan data legacy (administrator)

Perintah **Artisan**, bukan menu aplikasi:

| Masalah | Perintah |
|--------|---------|
| Duplikat baris pembelian PI | `php artisan inventory:report-purchase-invoice-duplicates` lalu `inventory:fix-duplicate-transaction --invoice={ID_PI\|no_invoice} --dry-run` lalu `--force` |
| Satu item | `inventory:fix-duplicate-transaction --item={kode\|id} --dry-run` |
| Akun bank SR salah (posting lama) | `php artisan sales-receipts:repair-bank-journals --dry-run` lalu `--force` |

Setelah cleanup duplikat PI, jalankan **FIFO Layer Repair** pada item FIFO terdampak jika GR/GI masih gagal.

---

## GR/GI setelah perbaikan

Approval **GR/GI** memakai **refresh valuasi toleran** agar mismatch lapisan tidak memblokir approval sementara — tetapi **riwayat FIFO benar** tetap memerlukan perbaikan jika replay strict gagal.

Jika stok fisik sudah diperbaiki manual, **batalkan atau edit** GR pending yang akan **double-count** qty penerimaan.

---

## Kata kunci HELP (Indonesia)

perbaikan FIFO, lapisan FIFO, insufficient FIFO, replay FIFO, defisit lapisan, duplikat purchase invoice, GR gagal approve, error valuasi, fifo_layer_repair, izin inventory adjust

---

## Panduan terkait

- **Valuasi persediaan**: `inventory-valuation-and-costing-manual-id.md`
- **GR/GI**: `inventory-module-manual-id.md`
- **Pencegahan duplikat PI**: `docs/action-plans/inventory-transaction-deduplication-prevention.md`

Setelah mengubah file ini, jalankan **`php artisan help:reindex`**.
