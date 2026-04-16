# Spesifikasi fungsional: AR Credit Memo & Reversal Delivery Order

**Versi:** 1.0  
**Status:** Draft untuk development & UAT  
**Konteks:** Koreksi entitas (CV vs PT), SI posted tanpa Sales Receipt, rantai SO → DO → SI yang perlu dinetralkan lalu diposting ulang dengan benar.

---

## 1. Tujuan & ruang lingkup

| Item | Deskripsi |
|------|-----------|
| **Tujuan** | Menyediakan alur **didukung aplikasi** untuk (a) **mencatat pembalikan pengakuan penjualan** lewat **AR Credit Memo**, dan (b) **mereversal Delivery Order** yang salah (termasuk status yang saat ini tidak bisa di-cancel lewat UI biasa), agar rantai dokumen bisa dikoreksi tanpa edit DB langsung. |
| **Di dalam scope** | Desain fungsional, aturan status, prasyarat posting, integrasi dengan jurnal & inventory (level requirement), skenario UAT. |
| **Di luar scope dokumen ini** | Implementasi kode, migrasi data historis massal, kebijakan pajak per transaksi konkret (diserahkan ke finance). |

---

## 2. Istilah

- **SI** — Sales Invoice (`App\Models\Accounting\SalesInvoice`), status `posted`.
- **CM** — **Credit Memo** AR: dokumen penjualan dengan efek **mengurangi** pendapatan/piutang sesuai desain posting (bukan sekadar jurnal bebas).
- **SR** — Sales Receipt (penerimaan piutang); pada kasus referensi, **belum ada**.
- **DO** — Delivery Order; **reversal** = proses aplikasi yang menetralisir/membuka DO yang sudah jalan (termasuk `completed`), dengan aturan dampak stok & jurnal.

---

## 3. Prasyarat bisnis (gate)

Sebelum operasi CM atau reversal DO:

1. **Finance** menyetujui **nominal** dan referensi bisnis (mis. selisih SO vs DO/SI untuk ref tertentu).
2. **Entitas tujuan** akhir (mis. PT vs CV) jelas untuk dokumen pengganti.
3. Untuk SI yang akan di-CM: **tidak ada Sales Receipt** yang mengalokasikan piutang ke SI tersebut.  
   - *Pengecualian di masa depan:* jika ada SR, definisikan aturan terpisah (void SR / alokasi ulang) — **bukan bagian dari asumsi awal ini.**

---

## 4. Modul AR Credit Memo (1b)

### 4.1 Konsep

- CM adalah dokumen **terpisah** dari SI, dengan **referensi wajib** ke satu atau lebih SI sumber (minimal: `sales_invoice_id` atau pivot jika multi-SI — keputusan teknis).
- CM memiliki **status** minimal: `draft` → `posted` (opsional: `cancelled` untuk draft yang tidak dipakai).
- **Posting CM** menghasilkan jurnal yang **menebus/mengurangi** dampak SI yang sudah posted (polak: mirror/kebalikan logika SI sesuai kebijakan akuntansi PT; detail mapping akun ditetapkan bersama finance).

### 4.2 Field fungsional (minimum)

| Area | Field / perilaku |
|------|------------------|
| Header | Nomor dokumen (penomoran per entitas), tanggal, customer, **company entity**, referensi SI, mata uang, total, status, keterangan. |
| Baris | Item/akun pendapatan, qty (jika relevan), harga, diskon, kode pajak, jumlah baris — selaras dengan pola SI agar konsisten laporan. |
| Link | CM tidak boleh `posted` jika SI sumber tidak `posted` (atau aturan eksplisit jika ada partial CM — opsi fase 2). |
| Audit | `created_by`, `posted_at`, jejak approval jika dipakai modul approval. |

### 4.3 Aturan bisnis

- **Posting CM** hanya jika prasyarat §3 terpenuhi (khususnya **tanpa SR** pada SI sumber untuk fase ini).
- Setelah CM `posted`, SI sumber tetap ada sebagai dokumen historis; **koreksi** tercermin lepas kombinasi **SI + CM** (atau laporan “net” — didefinisikan di laporan AR).

### 4.4 Hak akses (contoh)

- `ar.credit-memos.view` / `create` / `post` (nama disesuaikan konvensi project).
- Posting CM memerlukan permission terpisah dari posting SI jika kebijakan segregation of duties menghendaki.

---

## 5. Modul Reversal Delivery Order

### 5.1 Konsep

- Aksi **“Reverse DO”** (nama UI bisa “Void completed DO” / “Reverse delivery”) untuk DO pada status **`delivered`** / **`completed`** (dan varian lain yang disepakati), yang saat ini **bukan** target `cancel` biasa.
- Reversal menghasilkan **status akhir** DO yang jelas (mis. `reversed` atau `cancelled` dengan flag `reversal_of_id`) — satu pilihan konsisten di seluruh sistem.

### 5.2 Dampak (harus didefinisikan di implementasi)

| Area | Requirement |
|------|-------------|
| **Inventory** | Mengembalikan/menetralisir qty sesuai DO yang di-reverse (sama dengan arah bisnis reversal; selaras dengan transaksi inventory existing). |
| **Jurnal** | Jika DO completion memicu jurnal (mis. COGS, pengakuan lain), reversal harus **membalik** atau men-generate jurnal pembalik terkait DO — mengacu pada `DeliveryJournalService` / pola existing. |
| **SO line** | Qty terkirim / open SO line harus konsisten setelah reversal (sinkron seperti setelah cancel DO untuk status yang didukung hari ini). |

### 5.3 Prasyarat

- DO **tidak** terkunci oleh aturan closure yang melarang reversal (mis. jika masih ada SI `posted` yang mengikat DO, urutan operasi: **CM / netralisasi SI terlebih dahulu** atau aturan eksplisit — lihat §6).
- Tidak ada **Goods Issue** / dokumen gudang lain yang melarang reversal (sesuaikan dengan model data aktual).

### 5.4 Hak akses

- Permission khusus, mis. `sales.delivery-orders.reverse` (nama final mengikuti `bootstrap/app.php` / roles).

---

## 6. Urutan operasi yang disarankan (SOP)

Urutan ini menghindari konflik “SI posted mengunci DO” vs “DO reversal mengunci SI”.

1. **Gate finance** (§3).
2. **Buat & posting Credit Memo** terhadap SI yang salah (menetralkan dampak AR/pendapatan sesuai desain posting CM).
3. **Reverse DO** yang salah (§5), setelah CM memenuhi syarat integrasi (jika sistem mewajibkan SI/CM tidak outstanding — divalidasi di development).
4. **Koreksi SO** (entity / referensi / baris sesuai keputusan) — lewat edit yang diizinkan atau dokumen pengganti.
5. **Buat DO baru** (entity & qty benar) jika diperlukan.
6. **Buat SI baru** dari DO → **post** di entitas benar.
7. **Verifikasi** TB/AR aging per entitas.

*Catatan:* Jika validasi teknis mengharuskan **reversal DO sebelum CM**, dokumentasikan keputusan dan sesuaikan urutan §6 — yang penting **tidak ada keadaan tidak konsisten** di DB.

---

## 7. Integrasi dokumen & hubungan

- Setelah ada model CM: daftarkan di **`document_relationships`** (atau pola existing) antara CM ↔ SI, dan DO reversal ↔ DO asli jika perlu untuk **Relationship Map**.
- **Clear cache** navigasi dokumen jika pola existing memakai `DocumentRelationshipService`.

---

## 8. Kriteria penerimaan (high level)

- [ ] CM dapat dibuat draft, diposting, dan jurnal tercatat dengan benar untuk skenario UAT.
- [ ] SI sumber tetap auditable; laporan AR menunjukkan efek CM (atau net sesuai definisi).
- [ ] Reversal DO mengubah status & inventory/jurnal sesuai §5.2.
- [ ] Tanpa SR pada SI sumber, tidak ada error posting CM; dengan SR (uji negatif opsional), sistem menolak atau mengikuti aturan §3.
- [ ] Permission dan audit log mencatat siapa mem-post CM dan siapa mereversal DO.

---

## 9. Skenario UAT (dari kasus referensi)

| ID | Skenario | Data uji | Hasil yang diharapkan |
|----|----------|----------|------------------------|
| UAT-1 | CM penuh untuk 1 SI posted, tanpa SR | SI aktual dari lingkungan staging / salinan | CM posted; AR terkoreksi; SI tidak double-count pendapatan. |
| UAT-2 | Reversal 1 DO `completed` setelah UAT-1 | DO terkait | Status DO reversal; stok/jurnal sesuai §5.2. |
| UAT-3 | Rantai SO → DO baru → SI baru | Entity benar | Posting SI sukses; nomor & entity konsisten. |
| UAT-4 | Negatif: posting CM saat ada SR pada SI | Data sintetis | Ditolak dengan pesan jelas (jika aturan §3 dipakai). |

*Sesuaikan ID dokumen (72260600141, 72260600142, DO/SI terkait) saat menjalankan UAT di lingkungan non-produksi.*

---

## 10. Risiko & keputusan tertunda

| Risiko | Mitigasi |
|--------|----------|
| PPN / faktur pajak untuk SI yang sudah dibuat | Libatkan tax/compliance; mungkin perlu dokumen pajak di luar ERP atau flag khusus. |
| Partial CM vs full CM | Fase 1: full CM per SI; partial sebagai enhancement. |
| Urutan CM vs reversal DO | Kunci di §6 + uji integrasi; sesuaikan jika constraint DB memaksa. |

---

## 11. Referensi teknis (codebase)

- Posting SI: `App\Http\Controllers\Accounting\SalesInvoiceController::post`
- Reverse jurnal generik: `App\Services\Accounting\PostingService::reverseJournal`
- Unpost PI (pola referensi reversal dokumen): `App\Http\Controllers\Accounting\PurchaseInvoiceController::unpost`
- DO cancel status saat ini: `App\Models\DeliveryOrder::canBeCancelled`
- Relasi dokumen: `App\Services\DocumentRelationshipService`

---

## 12. Dokumentasi pengguna, HELP, dan navigasi bantuan

Untuk **bantuan dalam aplikasi (HELP, ikon ?)** dan indeks menu opsional:

- Ringkasan chunk (`##`) untuk retrieval: `docs/manuals/sales-workflow-corrections-help-id.md`, `docs/manuals/sales-workflow-corrections-help-en.md`
- Checklist operasional (salah entitas SO): `docs/manuals/checklist-perbaikan-salah-entitas-so-id.md`
- Petunjuk jalur menu / kata kunci: `docs/manuals/help-navigation.json` (entri `sales-credit-memos`, `document-relationship-map`, `company-entity-correction`, pembaruan `delivery-orders`)
- Manual modul: `docs/manuals/delivery-order-manual-id.md` (bagian **Reverse delivery**), `docs/manuals/sales-invoice-manual-id.md` / `sales-invoice-manual-en.md` (bagian **Sales Credit Memo**)

Setelah mengubah berkas di `docs/manuals/`, jalankan **`php artisan help:reindex`** di lingkungan server.

---

*Dokumen ini dapat direvisi setelah discovery singkat development (1–2 sesi) untuk menyesuaikan nama route, permission, dan constraint database.*
