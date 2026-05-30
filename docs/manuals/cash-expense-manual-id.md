# Cash Expenses — referensi HELP (Sarang ERP)

Dokumen ini dipakai **bantuan dalam aplikasi** (ikon **?**) untuk topik **Cash Expenses** / **pengeluaran kas** (pembayaran tunai langsung yang langsung posting ke jurnal). Setelah mengubah berkas ini, jalankan **`php artisan help:reindex`**.

---

## Apa itu Cash Expense?

**Cash Expense** mencatat pembayaran dari akun **kas/bank** ke akun **beban** dalam satu langkah. Dokumen **langsung posted** saat disimpan (bukan draft). Setiap transaksi mendapat nomor sesuai entitas (kode dokumen **11**, format `EEYYDDNNNNN` untuk entitas default).

**Kata kunci:** cash expense, pengeluaran kas, kas keluar, beban tunai, CEV, kode 11.

---

## Di mana menu Cash Expenses

1. Login ke Sarang ERP.
2. Menu samping **Accounting** → **Cash Expenses** (daftar `/cash-expenses`).
3. **New Expense** untuk form buat baru (`/cash-expenses/create`).

**Kata kunci:** di mana menu cash expense, akuntansi pengeluaran kas, `/cash-expenses`.

---

## Daftar dan filter rentang tanggal

Di halaman **Cash Expenses**:

- Gunakan field **rentang tanggal** (ikon kalender) untuk memfilter menurut **tanggal** pengeluaran.
- Preset: **Today**, **Yesterday**, **Last 7 Days**, **Last 30 Days**, **This Month**, **Last Month**.
- Klik **Apply** di picker untuk memfilter; **Clear** menghapus filter dan menampilkan semua tanggal.
- Tombol **Apply** di samping rentang tanggal me-reload tabel tanpa mengubah tanggal.
- Tabel server-side (urut/cari/halaman); kolom: tanggal, deskripsi, akun beban, akun kas, pembuat, jumlah, **Print**.

**Kata kunci:** filter pengeluaran kas tanggal, rentang tanggal cash expense, daftar kas keluar, filter cash expense.

---

## Membuat cash expense

1. **Cash Expenses** → **New Expense**.
2. Isi **tanggal**, **akun beban**, **akun kas**, **jumlah**, opsional **deskripsi**, dan opsional **project / department**.
3. Simpan. Sistem posting jurnal: **Debit** beban, **Kredit** kas/bank, lalu kembali ke daftar.

**Kata kunci:** buat cash expense, posting kas keluar, debit beban kredit kas.

---

## Cetak

Dari daftar, gunakan aksi **Print** pada baris untuk membuka layout cetak di tab baru.

**Kata kunci:** cetak cash expense, print pengeluaran kas.

---

## Company entity dan penomoran

Cash expense baru memakai **company entity default** untuk penomoran (`company_entity_id` pada record). Urutan nomor mengikuti aturan penomoran terpadu (lihat manual **Document numbering**).

**Kata kunci:** nomor cash expense, entitas 11, default entity pengeluaran kas.
