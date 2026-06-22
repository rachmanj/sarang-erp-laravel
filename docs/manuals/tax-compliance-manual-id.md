# Tax Compliance (Indonesia) — Panduan Pengguna (Bahasa Indonesia)

## Ringkasan

Modul **Tax Compliance** melacak PPN, PPh potong, periode pajak, log kepatuhan, dan ekspor untuk pelaporan Indonesia (Coretax, e-Bupot, SPT). Transaksi pajak tersinkron saat **Purchase Invoice** dan **Sales Invoice** di-**post**.

Menu: **Accounting** → **Tax Compliance**. Izin: `tax.view` (plus `tax.update`, `tax.approve` untuk alur kerja).

---

## Dashboard

Dashboard menampilkan ringkasan periode pajak aktif: jumlah transaksi, DPP, saldo net PPN, dan tautan ke transaksi, laporan, kalender, log kepatuhan.

---

## Transaksi pajak

Faktur posted membuat/memperbarui baris **tax_transactions** dengan DPP, PPN, dan potongan yang benar. Daftar: Tax Compliance → **Transactions**.

Baris faktur pembelian mendukung **wtax_rate**. Faktur penjualan mendukung field **Faktur Pajak** untuk ekspor e-Faktur.

---

## Laporan dan ekspor

- **PPN Reconciliation** (menu Reports) — rekonsiliasi GL vs ledger pajak; ekspor JSON SPT-1111.
- **Reports** di modul Tax — buat/kirim/setujui laporan periodik.
- **Compliance logs** — jejak audit aksi pajak.
- **Calendar** — tanggal jatuh tempo kewajiban.

Ekspor **Coretax** dan **e-Bupot** CSV jika dikonfigurasi.

---

## Tutup buku dan periode

**Periods** (Accounting → Periods): tutup/buka bulanan dan **Close Fiscal Year** (jurnal akhir tahun). Posting dan penghapusan dokumen diblokir di periode tertutup.

Periode pajak di Tax Compliance → Periods terpisah tetapi sebaiknya selaras dengan tutup buku GL.

---

## Ringkasan izin

| Aksi | Izin |
|------|------|
| Lihat dashboard & transaksi | `tax.view` |
| Buat/ubah transaksi | `tax.create`, `tax.update` |
| Submit laporan | `tax.update` |
| Setujui laporan | `tax.approve` |

Setelah perubahan dokumentasi, admin menjalankan `php artisan help:reindex`.
