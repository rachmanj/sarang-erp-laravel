# Direct Sales (mode Sales Invoice)

## Pengenalan

### Apa itu Direct Sales?

**Direct Sales** (Penjualan Langsung) adalah **mode** pada **Sales Invoice** yang **melewati Sales Order dan Delivery Order**. Stok keluar dan jurnal pendapatan/HPP dibuat saat Anda **Post** faktur.

Cocok untuk penjualan di counter, pelanggan walk-in, atau penjualan tanpa alur SO → DO → SI.

Dokumen tetap **Sales Invoice** biasa (penomoran kode **08**, izin sama dengan SI).

### Kapan pakai Direct Sales vs Sales Invoice biasa

| Situasi | Jalur yang disarankan |
|---------|------------------------|
| Barang sudah dikirim lewat DO | **Sales Invoice dari Delivery Order** (SI normal) |
| Penjualan tunai/counter, stok langsung keluar | **Direct Sales** |
| Penjualan kredit tanpa DO, stok keluar saat post faktur | **Direct Sales** (pembayaran = Credit) |
| Jasa saja (tanpa stok) | **Sales Invoice** manual biasa (tanpa centang Direct Sale) |

---

## Menu, izin, dan navigasi

### Lokasi menu

1. Sidebar **Sales** → **New Direct Sale**, atau
2. **Sales** → **Sales Invoices** → **Create** → centang **Direct Sale**, atau
3. **Pencarian menu** navbar (Ctrl+K) → ketik **direct sales** / **penjualan langsung** → **Direct Sales**.

### URL

- Create: `/sales-invoices/create?direct=1`

### Izin

| Aksi | Permission |
|------|------------|
| Buat Direct Sale | `ar.invoices.create` |
| Post (stok + jurnal) | `ar.invoices.post` |
| Lihat daftar & detail | `ar.invoices.view` |

---

## Membuat Direct Sale

### Header

1. Pilih **Customer** dan **Company entity**.
2. Centang **Direct Sale** (aktif otomatis jika dibuka dari **New Direct Sale**).
3. Pilih **Payment**:
   - **Credit (AR — pay later)** — piutang tetap terbuka sampai dibuat **Sales Receipt**.
   - **Cash (paid now)** — pilih **Cash / Bank account** (mis. Kas di Tangan). Saat post, sistem otomatis membuat dan **post** **Sales Receipt**.

### Baris faktur

Setiap baris **wajib** punya **inventory item** (tombol cari di baris).

1. Cari dan pilih item (kode, nama, harga jual terisi).
2. Isi **Qty** dan **Unit price** (DPP, belum termasuk PPN).
3. Atur **VAT** / **WTax** dan diskon jika perlu.

Direct Sale **tidak boleh** dikaitkan ke **Sales Order** atau **Delivery Order**.

### Simpan dan post

1. **Save Invoice** — status **Draft**.
2. **Post** — stok berkurang, jurnal pendapatan/HPP/PPN dibuat.
3. Jika **Cash**, **Sales Receipt** otomatis di-post dan faktur lunas.

Di halaman detail muncul badge **Direct Sale** dan **Cash (Paid)** atau **Credit**.

---

## Akuntansi saat post (ringkas)

Satu jurnal pada faktur:

- Kredit **Pendapatan** (per baris, DPP neto setelah diskon)
- Kredit **PPN Keluaran** bila ada PPN
- Debit **HPP**, Kredit **Inventory Available** untuk barang stok
- Debit **Piutang Dagang (AR)** sebesar tagihan
- Debit **PPh 23 dibayar dimuka** bila ada WTax

**Cash** menambah jurnal kedua: Debit Kas/Bank, Kredit AR (Sales Receipt otomatis).

SI normal dari DO masih memakai konversi **AR UnInvoice**; Direct Sale **tidak**.

---

## Setelah posting

### Direct Sale kredit

- Terima pembayaran nanti: **Sales** → **Sales Receipts** → **Create** → alokasi ke faktur ini.

### Direct Sale tunai

- Faktur sudah dialokasi; tombol **Create Receipt** disembunyikan.

---

## Pemecahan masalah

### Post gagal: stok tidak cukup

- Qty melebihi stok/FIFO tersedia. Kurangi qty, ganti item, atau terima stok dulu.

### Post gagal: baris tanpa item persediaan

- Setiap baris Direct Sale harus memilih item lewat pencarian item.

### Salah metode pembayaran

- Hanya faktur **draft** yang bisa diedit. Hapus draft dan buat ulang, atau ikuti kebijakan koreksi (credit memo / receipt) untuk dokumen posted.

---

## Manual terkait

- **Sales Invoice** — `sales-invoice-manual-id.md`
- **Sales Receipt** — `sales-receipt-manual-id.md`
- **Delivery Order** — bila pengiriman terpisah sebelum faktur

Setelah mengubah berkas ini, jalankan **`php artisan help:reindex`**.
