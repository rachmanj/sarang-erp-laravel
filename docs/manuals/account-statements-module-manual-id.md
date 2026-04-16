# Modul Account Statements (rekening koran formal GL / Business Partner)

## Apa fitur ini

Menu **Accounting → Account Statements** membuat **dokumen rekening koran tersimpan** bernomor (format **`AST-…`**), dengan **baris transaksi**, **saldo awal/akhir**, dan **status** alur kerja.

Ini **bukan** tab **Account statement** di layar **Business Partner** (yang menampilkan **jurnal terposting** langsung dari GL). Tab partner dijelaskan di **`business-partner-module-manual-id.md`**. Gunakan modul ini jika Anda perlu **laporan tersimpan** untuk **akun** atau **partner** dalam suatu periode.

## Membuat rekening koran

1. Buka **Accounting → Account Statements →** tautan **Generate** (atau **`/account-statements/create`**).
2. Pilih **Statement type**:
   - **GL Account Statement** — pilih **Account** (COA).
   - **Business Partner Statement** — pilih **Business Partner**.
3. Isi **From date** dan **To date** (wajib). Opsional: **Project** / **Department** jika tersedia.
4. Klik **Generate Statement**.

Jika muncul kotak merah **Please fix the following** / daftar error, baca pesannya (misalnya akun kosong, tanggal tidak valid, atau hak akses).

## Tautan langsung (GL)

Anda bisa mengisi tipe dan akun lewat URL, contoh:

`/account-statements/create?statement_type=gl_account&account_id=<id>`

Ganti `<id>` dengan ID akun di data Anda (nilai berbeda tiap basis data).

## Status (Draft, Finalized, Cancelled)

| Status | Arti |
|--------|------|
| **Draft** | Baru dibuat; bisa **edit** catatan (sesuai aturan), **finalize**, atau **hapus**. |
| **Finalized** | Terkunci; menurut aturan UI saat ini **tidak** bisa dihapus/dibatalkan lewat tombol standar. |
| **Cancelled** | Dibatalkan (diset lewat rute backend; **belum** ada tombol **Cancel** di layar standar). |

Statement baru selalu **Draft**.

## Mengubah status

- **Draft → Finalized**: Buka detail statement (ikon **mata**). Klik **Finalize** (konfirmasi). Perlu izin **`account_statements.update`**. Harus ada **minimal satu baris** transaksi di periode; jika tidak, finalisasi ditolak.
- **Draft → dihapus**: Gunakan **Delete** di daftar atau detail (izin **`account_statements.delete`**). Ini **menghapus** data, bukan mengubah status menjadi Cancelled.
- **Finalized**: Tidak bisa dihapus/dibatalkan lewat aksi UI yang ada sekarang.

Di **daftar**, baris **Draft** juga punya tombol **centang hijau** untuk finalisasi cepat.

## Izin

- **`account_statements.view`** — lihat daftar dan detail.
- **`account_statements.create`** — buat statement baru.
- **`account_statements.update`** — edit catatan draft, **finalize**.
- **`account_statements.delete`** — **hapus** statement yang belum finalized.

## Troubleshooting

### Klik Generate Statement tidak pindah halaman

- Periksa **alert merah** validasi di atas form.
- Pastikan **Statement type** cocok dengan isian (**GL** wajib **Account**; **Business Partner** wajib pilih partner).
- **To date** harus **sama atau setelah** **From date**.

### Tombol Finalize tidak ada atau gagal

- Hanya **Draft** yang punya **Finalize**. Jika sudah **Finalized**, tombol disembunyikan.
- Jika error mengenai **transaksi**, mungkin periode **tanpa baris**; periksa jurnal dan rentang tanggal.

## Dokumentasi terkait

Referensi teknis: **`docs/ACCOUNT-STATEMENTS-IMPLEMENTATION.md`**. Tab GL di partner (fitur lain): **`docs/BUSINESS-PARTNER-ACCOUNT-STATEMENT.md`**.
