# Koreksi alur penjualan — ringkasan untuk HELP (Bahasa Indonesia)

Dokumen ini mendukung **bantuan dalam aplikasi (HELP, ikon ?)**. Gunakan judul `##` berikut sebagai potongan pengetahuan. Setelah mengubah berkas ini, jalankan **`php artisan help:reindex`** di server.

## Sales Credit Memo — satu memo per Sales Invoice

- **Menu**: **Sales** → **Sales Credit Memos** (`/sales-credit-memos`). Buat lewat **Create** atau dari halaman **Sales Invoice** (tombol **Create Credit Memo** jika tampil).
- **Aturan sistem**: untuk satu **Sales Invoice** hanya boleh ada **satu** Sales Credit Memo (database unik pada `sales_invoice_id`). Jika sudah ada memo, pengguna akan diarahkan ke memo yang ada atau mendapat pesan error saat membuat lagi.
- **Posting**: setelah draft dicek, gunakan **Post** pada memo (izin `ar.credit-memos.post`) agar jurnal koreksi tercatat.
- **Kata kunci pengguna**: nota kredit, credit memo, koreksi faktur, pembatalan penagihan, SI sudah posting, retur penjualan administratif.

## Reverse delivery — pembatalan pengiriman yang sudah delivered/completed

- **Menu**: **Sales** → **Delivery Orders** → buka detail DO (`/delivery-orders/{id}`).
- **Tombol**: **Reverse delivery** (bukan **Cancel delivery order**). Cancel hanya untuk status awal (draft/in transit); **Reverse** untuk **partial_delivered**, **delivered**, atau **completed**.
- **Izin**: `delivery-orders.reverse`.
- **Dampak**: membalik jurnal yang bersumber dari DO tersebut, mengembalikan stok sesuai transaksi penjualan tercatat, status DO menjadi **reversed**, penutupan dokumen diatur sesuai implementasi.
- **Kata kunci**: reverse DO, batalkan pengiriman, salah kirim, surat jalan salah, kembalikan stok setelah kirim.

## Prasyarat reverse delivery bila sudah pernah difakturkan

- DO **tidak boleh** masih tertaut Sales Invoice di tabel pivot (harus **unlink** DO–SI sesuai prosedur internal).
- Jika **closure** DO ditutup oleh Sales Invoice: biasanya wajib ada **Sales Credit Memo yang sudah posted** untuk SI penutup tersebut sebelum reversal diizinkan (logika `canBeReversed` / pesan peringatan di layar).
- **Kata kunci**: unlink DO SI, lepas tautan faktur, sudah invoiced, koreksi setelah CM.

## Relationship Map dan diagram Document Workflow

- **Tombol**: pada detail dokumen (SO, DO, SI, dll.) — **Relationship Map** di header.
- **Modal**: judul **Document Relationship Map**; diagram bernama **Document Workflow**.
- **Isi kotak diagram**: baris berisi **jenis dokumen** (mis. Sales Order, Delivery Order), **nomor**, **tanggal**, **Status** (bukan singkatan N/A untuk status — referensi pelanggan hanya ditampilkan jika ada; lihat panduan teknis UI).
- **Kata kunci**: peta dokumen, alur dokumen, hubungan SO DO SI, workflow diagram, N/A di diagram (bukan status jika tidak ada referensi).

## Salah pilih Company entity pada Sales Order

- **Field**: **Company entity** pada form SO (create/edit).
- **Jika SI belum posting**: seringkali cukup **Edit** SO dan ganti entitas (sesuai aturan status dokumen).
- **Jika SI sudah posting**: umumnya perlu **Sales Credit Memo** untuk SI salah entitas, lalu perbaikan DO/tautan dan **Reverse delivery** bila dipakai, kemudian buat transaksi ulang di entitas benar.
- **Checklist panjang**: lihat berkas **`checklist-perbaikan-salah-entitas-so-id.md`** di folder `docs/manuals/`.
- **Kata kunci**: PT CV salah, entitas salah, company entity, beda PT, koreksi SO entitas.

## Izin terkait (Spatie) — referensi singkat

| Kebutuhan | Izin contoh |
|-----------|-------------|
| Lihat / buat Sales Credit Memos | `ar.credit-memos.view`, `ar.credit-memos.create` |
| Posting memo | `ar.credit-memos.post` |
| Reverse DO | `delivery-orders.reverse` |
| Sales Invoice posting | `ar.invoices.post` |
