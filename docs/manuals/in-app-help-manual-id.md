# Bantuan Dalam Aplikasi (HELP) — Sarang ERP

## Apa itu HELP?

**HELP** adalah fitur bantuan di dalam aplikasi (ikon **?** di bilah atas). Anda dapat:

- Bertanya **cara melakukan sesuatu** di Sarang ERP (alur, menu, langkah ringkas).
- Memilih **bahasa jawaban**: Otomatis (mengikuti bahasa aplikasi), **English**, atau **Bahasa Indonesia**.
- Mengirim **laporan bug** atau **permintaan fitur** di tab **Report / request** (disimpan untuk ditindaklanjuti tim IT; bukan janji SLA resmi).

Jawaban bersumber dari **dokumentasi internal** (`docs/manuals/`) yang telah diindeks di server. Bukan chat umum di internet.

---

## Cara menggunakan (pengguna akhir)

1. Klik ikon **?** di pojok kanan navbar.
2. Tab **How-to** — ketik pertanyaan singkat, misalnya:
   - "Bagaimana transfer stok antar gudang?"
   - "Di mana menu Sales Invoice?"
   - "Cara posting Purchase Invoice?"
3. Pilih **Answer language** jika ingin memaksa bahasa jawaban.
4. Klik **Ask**.
5. Baca jawaban dan daftar **Sources** (nama file panduan yang dipakai sebagai referensi).

Tab **Report / request** untuk mengisi formulir bug atau ide perbaikan.

---

## Privasi dan penyimpanan

- **Percakapan HELP tidak disimpan** di database untuk audit pengguna.
- Hanya pengiriman **Report / request** yang disimpan (judul, isi, jenis, pengguna, waktu).

---

## Untuk administrator IT / deployment

### Persyaratan server

- Variabel lingkungan **`OPENROUTER_API_KEY`** (kunci hanya di server, jangan dibagikan ke browser).
- Akses keluar HTTPS ke **OpenRouter** (embedding + model bahasa).
- Setelah migrasi database, jalankan **`php artisan help:reindex`** untuk mengisi tabel **`help_embeddings`**.

### Memperbarui pengetahuan HELP setelah dokumentasi berubah

1. Edit atau tambah file Markdown di **`docs/manuals/`** (gunakan heading **`##`** agar bagian mudah diindeks).
2. Opsional: edit **`docs/manuals/help-navigation.json`** untuk petunjuk jalur menu (Reports, Sales, dll.).
3. Di server produksi, jalankan:

```bash
php artisan help:reindex
```

4. Perintah ini memanggil API OpenRouter (biaya penggunaan sesuai akun Anda).

### Jika jawaban HELP tidak relevan atau kosong

- Pastikan **`help:reindex`** sudah dijalankan setelah deploy pertama.
- Tambahkan isi di manual yang lebih spesifik (kata kunci yang sama dengan yang ditanyakan pengguna).
- Untuk topik baru, buat berkas manual baru, misalnya `nama-modul-manual-id.md`, lalu **reindex**.

### Email notifikasi feedback

- Set **`HELP_FEEDBACK_NOTIFY_EMAIL`** di `.env` jika ingin menerima email saat pengguna mengirim bug/ide (konfigurasi mail SMTP harus valid).

---

## File terkait teknis (referensi developer)

- Perintah Artisan: `help:reindex`
- Konfigurasi: `config/help.php`, `config/services.php` (kunci `openrouter`, `help_feedback`)
- Rute: `POST /help/ask`, `POST /help/feedback` (memerlukan login)
- Arsitektur ringkas: `docs/architecture.md` (bagian In-app HELP)

---

## Daftar manual umum di folder ini

Lihat **`README.md`** di folder yang sama untuk indeks berkas panduan dan petunjuk pemeliharaan HELP.

**Account Statements** (menu Accounting — rekening koran tersimpan AST, draft/finalize; **bukan** tab Account statement di Business Partner): **`account-statements-module-manual-id.md`** / **`account-statements-module-manual-en.md`**.

**Koreksi alur penjualan** (Sales Credit Memo, reverse delivery, Relationship Map, salah **Company entity**): ringkasan untuk indeks HELP — **`sales-workflow-corrections-help-id.md`** (ID) dan **`sales-workflow-corrections-help-en.md`** (EN). Checklist operasional: **`checklist-perbaikan-salah-entitas-so-id.md`**.

**Domain Assistant** (ikon robot, data ERP langsung — bukan HELP): lihat **`domain-assistant-manual-id.md`** di folder ini.

