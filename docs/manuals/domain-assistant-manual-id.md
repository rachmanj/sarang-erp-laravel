# Domain Assistant — Sarang ERP

## Apa itu Domain Assistant?

**Domain Assistant** adalah fitur **terpisah** dari **HELP** (ikon **?** / buku).

- **Domain Assistant** — ikon **robot** di navbar atas. Fitur ini menjawab pertanyaan memakai **data langsung** dari Sarang ERP (sales order, **sales invoice**, purchase order, delivery order, penerimaan barang, stok inventori, mitra bisnis). Mendukung **beberapa percakapan** (thread) dan **riwayat chat** disimpan per pengguna.
- **HELP** — ikon **buku** (`?`). Menjawab pertanyaan **cara pakai** dari **manual** tertulis; **tidak** meng-query database perusahaan dan **tidak** menyimpan riwayat chat.

Keduanya memakai API **OpenRouter** di server; kunci API tidak tampil di browser.

---

## Siapa yang boleh memakai?

Administrator harus memberi izin **`access-domain-assistant`** pada peran Anda. Jika ikon robot tidak tampil, Anda belum punya akses.

Fitur harus **diaktifkan** di konfigurasi server (`DOMAIN_ASSISTANT_ENABLED`) dan **`OPENROUTER_API_KEY`** harus diisi.

---

## Cara membuka Domain Assistant

1. Login ke Sarang ERP.
2. Di **navbar atas**, klik ikon **robot** — di sebelah kiri ikon HELP.
3. Halaman **Domain Assistant** terbuka dengan tampilan terminal gelap: **daftar sesi** di kiri, **obrolan** di kanan.

---

## Sesi (thread)

- **Sesi baru** — membuat percakapan kosong untuk topik baru.
- Klik sesi di daftar untuk **berpindah** konteks; pesan lama untuk sesi itu dimuat lagi.
- Anda bisa **menghapus** sesi (hover lalu hapus).
- Ada **batas pesan per hari** (diatur administrator). Jika limit tercapai, coba lagi keesokan harinya.

---

## Contoh pertanyaan

- **Sales Invoice (AR / faktur penjualan)** — mis. “Tampilkan detail invoice **71260800080**” atau “Daftar faktur untuk pelanggan X”.  
  Asisten mencari di **Sales Invoice**, bukan dokumen **Sales Order**.
- **Sales Order** — pesanan terbuka, nama pelanggan, rentang tanggal.
- **Purchase Order**, **Delivery Order**, **Goods Receipt (GRPO)** — sesuai filter pemasok/pelanggan dan tanggal.
- **Item inventori** — kode/nama, kategori, stok rendah.
- **Mitra bisnis** — pelanggan dan pemasok.

Boleh bertanya dalam **Bahasa Indonesia** atau **English**; model biasanya mengikuti bahasa Anda.

---

## Bedanya Sales Invoice dan Sales Order (penting)

- **Sales Invoice** = faktur penjualan / AR yang sudah diposting (nomor seperti **71260800080**).  
- **Sales Order** = dokumen pesanan (**SO**), **bukan** faktur.

Jika Anda memberi **nomor faktur**, sebut “invoice” atau “faktur” agar pencarian ke **Sales Invoice**. Pertanyaan “detail faktur” seharusnya mengembalikan **header dan baris** jika sistem mendukung.

Faktur bisa berbeda **entitas perusahaan** (mis. PT vs CV). Jika Anda punya izin **see all record switch**, mungkin ada opsi semacam **ALL BRANCHES**; tanpa itu, tampilan daftar bisa mengikuti entitas default, sedangkan **pencarian nomor faktur** dirancang agar dokumen tetap ditemukan di entitas aktif yang relevan.

---

## Privasi dan pencatatan (beda dengan HELP)

- **HELP** **tidak** menyimpan Q&A Anda di database untuk audit percakapan.  
- **Domain Assistant** **menyimpan pesan** (thread Anda) dan dapat menulis **log permintaan** (sukses/gagal, tool yang dipakai, durasi, IP) untuk **operasional dan audit**. Administrator dapat melihat ringkasan log di **Admin → Assistant report** (jika peran Anda punya **view-admin**).

Jangan menempel rahasia atau data pribadi yang tidak boleh disimpan di sistem perusahaan.

---

## Bug dan ide perbaikan

Gunakan **HELP → Report / request** untuk laporan bug dan permintaan fitur. Domain Assistant untuk **pertanyaan data**, bukan saluran tiket resmi kecuali kebijakan organisasi Anda menyatakan lain.

---

## Untuk administrator (ringkas)

- **Izin**: `access-domain-assistant`; opsional **`see-all-record-switch`** untuk perilaku “semua cabang” jika diimplementasikan.  
- **Environment**: `DOMAIN_ASSISTANT_ENABLED`, `DOMAIN_ASSISTANT_MODEL`, `DOMAIN_ASSISTANT_DAILY_LIMIT`, **`OPENROUTER_API_KEY`** sama seperti HELP.  
- **Reindex**: Domain Assistant **tidak** memakai `help:reindex`; perintah itu hanya untuk manual **HELP**.  
- **Dokumentasi**: `docs/action-plans/domain-assistant.md`, `docs/architecture.md` (bagian Domain Assistant).

---

## Terkait

- **HELP** (panduan cara pakai): `in-app-help-manual-id.md` di folder ini.  
- **Sales Invoice** (alur, layar): `sales-invoice-manual-id.md`.
