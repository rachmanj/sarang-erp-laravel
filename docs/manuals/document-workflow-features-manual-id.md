# Fitur alur dokumen — referensi HELP (Bahasa Indonesia)

Panduan ini mencakup **navigasi Base/Target**, **Preview Journal**, **filter Open/Closed** di daftar, tombol **Create Target Document**, dan **penghapusan dokumen** (per 2026-06).

---

## Navigasi Base / Target Document

Di halaman **detail** dokumen (PO, GRPO, PI, PP, SO, DO, SI, SR, Sales Quotation, dll.), kartu **Base / Target Document** memungkinkan lompat ke dokumen sumber dan turunan tanpa membuka Relationship Map.

- **Base** — dokumen asal (mis. PI menampilkan PO dan/atau GRPO).
- **Target** — dokumen yang dibuat dari dokumen ini (mis. PO menampilkan GRPO dan PI).
- Purchase Order menampilkan kartu navigasi tetapi **tanpa** tombol Preview Journal (PO tidak posting jurnal).

Menu: buka dokumen dari daftar **Purchase** atau **Sales**, lalu lihat kartu navigasi di halaman detail.

---

## Preview Journal (sebelum posting)

Untuk dokumen yang bisa diposting (GRPO, PI, PP, DO, SI, SR), klik **Preview Journal** di kartu Base/Target untuk melihat **baris jurnal yang sama** dengan saat posting.

Tipe yang didukung: Goods Receipt PO, Purchase Invoice, Purchase Payment, Delivery Order, Sales Invoice, Sales Receipt.

Jika preview kosong, periksa kelengkapan baris, akun, dan kode pajak pada draft.

---

## Filter Open / Closed di daftar dokumen

Halaman indeks **PO**, **GRPO**, **PI**, **PP**, **SO**, **DO**, **SI**, dan **SR** memiliki switch **All / Open / Closed** (default **Open**).

- **Open** — dokumen yang masih outstanding operasional (mis. faktur belum lunas, GRPO belum difaktur).
- **Closed** — sudah selesai / lunas.
- **All** — semua.

Filter dihitung dari alokasi dan kuantitas aktual, bukan hanya field `closure_status`.

---

## Tombol Create Target Document

Dari halaman detail dokumen Anda dapat membuat langkah berikutnya:

| Dari | Tombol | Menghasilkan |
|------|--------|--------------|
| Purchase Order | Copy to GRPO / Copy to Purchase Invoice | GRPO atau PI dengan baris disalin |
| GRPO | Create Purchase Invoice | PI dari GRPO |
| PI posted | Create Payment | Purchase Payment + alokasi faktur |
| Sales Quotation | Convert to Sales Order | SO dari quotation |
| Sales Order | Create Delivery Order | DO (jika diizinkan) |
| DO (delivered) | Create Invoice from Delivery Order | Sales Invoice |
| SI posted | Create Receipt | Sales Receipt + alokasi faktur |

PI→PP dan SI→SR membuka form pembayaran/penerimaan dengan **partner, entitas, dan centang faktur** sudah terisi.

---

## Penghapusan dokumen

Di halaman detail yang mendukung, tombol merah **Delete** (split) menawarkan:

1. **Delete this document only** — hanya dokumen ini (jurnal dibalik jika sudah posted). **Ditolak** jika masih ada dokumen target di bawahnya.
2. **Delete with related documents** — hapus berantai dari leaf ke root; jurnal posted dibalik dulu.

Izin contoh: `ap.invoices.delete`, `ar.invoices.delete`, `goods-receipt-pos.delete`, `delivery-orders.delete`.

**Periode akuntansi tertutup** memblokir penghapusan. Modal konfirmasi menampilkan **preview** daftar dokumen yang akan dihapus.

Penghapusan tunggal tidak bisa jika masih ada dokumen turunan — hapus turunan terlebih dahulu atau gunakan cascade.
