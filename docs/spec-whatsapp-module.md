# Sarang ERP — WhatsApp Module (WA Bridge) — Specification

**Document Version**: 1.0
**Date**: 2026-08-16
**Status**: 📋 Pending Approval
**Author**: Dea (from grill-me interview with Iwan)

---

## 1. Goal

Beri **owner / decision-maker** kemampuan menjalankan proses bisnis inti Sarang ERP — **approval dokumen** dan **pantauan kondisi harian** — langsung dari WhatsApp, tanpa buka aplikasi ERP.

**Wedge (fokus awal):**
1. **PO approval via WA** — owner terima notif PO baru → balas `approve` / `reject` → status PO di ERP terupdate otomatis.
2. **Laporan harian** — tiap pagi owner terima rekap: penjualan, kas/bank, PO pending approval, invoice jatuh tempo.

**Target user awal:** owner PT CSJ + CV Cahaya Saranghae (client existing, jadi beta user).

---

## 2. Scope

### In Scope (MVP)
- Konfigurasi gateway WA (provider, API key, nomor pengirim, nomor owner).
- Notifikasi WA otomatis saat PO masuk status `pending` approval.
- Owner balas `approve` / `reject` + nomor PO → status PO update via logika approval existing.
- Laporan harian terjadwal (penjualan, kas/bank, PO pending, invoice jatuh tempo).
- Audit log pesan masuk/keluar (tabel `whatsapp_messages`).
- Lisensi: flag per client (`wa_module_enabled` + `wa_expiry`).

### Out of Scope (v1)
- Approval dokumen selain PO (SO, Purchase Payment, dll) — fase berikutnya.
- Chat dua arah bebas / chatbot NLP — v1 cuma parse command terstruktur.
- Multi-gateway routing kompleks / multi-company per nomor.
- Broadcast promosi ke customer / CRM.

---

## 3. Tech Decisions

### 3.1 Gateway Adapter Pattern
Satu interface, banyak provider. MVP implementasi **Fonnte** (gateway lokal), siap ganti ke Wablas / Ruangwa / Qontak / Meta Cloud API tanpa ubah core logic.

```php
interface WhatsAppGatewayInterface
{
    public function sendMessage(string $to, string $message): array;   // -> ['message_id' => ..., 'status' => ...]
    public function fetchNewMessages(): array;                         // polling inbound
    public function sendInteractiveApproval(string $to, array $payload): array; // optional: tombol approve/reject
}
```

- Provider dipilih via config `whatsapp.provider` (`.env` `WA_PROVIDER`).
- Binding via Laravel service container (`app/Providers/WhatsAppServiceProvider`).

### 3.2 Polling-based Inbound (BUKAN webhook-only)
Client on-prem mungkin LAN-only / NAT. Maka inbound pakai **polling**: scheduler Laravel jalan `whatsapp:poll-messages` tiap N detik, tarik pesan baru dari gateway, proses. Tidak butuh port inbound.

- Tetap sediakan endpoint `POST /api/wa/webhook` sebagai **opsional** (untuk gateway yang bisa push / masa depan Meta Cloud API) — tetapi jalur utama MVP adalah polling.
- Scheduler dipicu via **queue `database`** (sudah ada) — catatan: VPS prod saat ini **tidak ada cron**, jadi perlu aktifkan cron `* * * * * php artisan schedule:run`.

### 3.3 Reuse Approval Logic Existing
WA inbound **tidak** mengimplementasi ulang approval. `WhatsAppInboundHandler` memanggil method/model yang sama dengan UI approval:
- `PurchaseOrder::canBeApproved()`, `approvals()`, `PurchaseOrderApproval::approve()/reject()`.
- Sumber kebenaran status tetap `purchase_orders.approval_status`.

### 3.4 Stack & Library
- Laravel 12 `Illuminate\Support\Facades\Http` untuk call API gateway (tanpa package tambahan).
- Queue `database` + `DispatchAfterResponse` / job `SendWhatsAppMessage`.
- Config terpusat di `config/whatsapp.php`, override per-install via `.env`.
- Spatie Permission: permission baru `whatsapp.settings`, `whatsapp.logs`.

---

## 4. DB Changes

### 4.1 Migration: `whatsapp_messages`
Audit log + inbound dedup.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| direction | enum('in','out') | |
| gateway_message_id | varchar(100) nullable | id dari gateway, untuk dedup inbound |
| to_number | varchar(30) | |
| from_number | varchar(30) | |
| body | text | |
| message_type | varchar(30) | 'po_approval_notify', 'approve_reply', 'daily_report', ... |
| status | varchar(30) | 'queued','sent','delivered','failed','received','processed','ignored' |
| related_entity_type | varchar(50) nullable | 'purchase_order' |
| related_entity_id | bigint nullable | |
| error | text nullable | |
| created_at / updated_at | timestamps | |

Index: `(direction, status)`, `(related_entity_type, related_entity_id)`, unique `(gateway_message_id)` (nullable OK).

### 4.2 Migration: `users` — add `whatsapp_number`
Nomor WA approver (owner). nullable varchar(30). Alternatif: ErpParameter `wa_owner_phone`.

### 4.3 `ErpParameter` entries (settings runtime)
| Key | Default | Notes |
|---|---|---|
| wa_module_enabled | false | flag lisensi |
| wa_expiry | null | tanggal expired lisensi |
| wa_provider | fonnte | |
| wa_api_key | null | di-encrypt saat store |
| wa_sender_number | null | nomor pengirim (device) |
| wa_owner_phone | null | nomor WA owner/approver |
| wa_daily_report_time | 07:00 | jam kirim laporan |
| wa_poll_interval_seconds | 10 | |

> Catatan: secret (`wa_api_key`) sebaiknya tetap di `.env`, bukan DB. ErpParameter untuk nilai non-secret; fallback ke `.env` bila kosong.

### 4.4 `purchase_orders` — TIDAK ada kolom baru
Reuse `approval_status`, `approved_by`, `approved_at`. Jejak notifikasi WA cukup dari `whatsapp_messages` (related_entity_type = purchase_order).

---

## 5. UI/UX (Blade + AdminLTE 3)

### 5.1 Settings Page — "WhatsApp Integration"
Lokasi: Settings → WhatsApp Integration (permission `whatsapp.settings`).
- Toggle enable module + expiry display (lisensi).
- Dropdown provider, field API key (masked), sender number, owner phone.
- Jam laporan harian.
- Tombol **"Send Test Message"** → kirim WA test ke owner.

### 5.2 Purchase Order Detail
- Badge status notifikasi WA (terkirim / belum / gagal) di header PO.
- (Opsional) Section "WA Activity" menampilkan pesan terkait PO dari `whatsapp_messages`.

### 5.3 Log Viewer — "WA Message Log"
Lokasi: Settings → WA Message Log (permission `whatsapp.logs`). Tabel filterable: arah, status, entity, tanggal.

---

## 6. API Endpoints & Commands

### 6.1 Routes
| Method | Path | Purpose |
|---|---|---|
| POST | `/api/wa/webhook` | (Opsional) webhook receive untuk gateway push / Meta Cloud API. Tanpa auth app, tapi verifikasi signature provider. |
| GET/POST | `/whatsapp/settings` | Form settings |
| POST | `/whatsapp/test` | Kirim test message |
| GET | `/whatsapp/logs` | Log viewer |

### 6.2 Artisan Commands
```bash
php artisan whatsapp:poll-messages       # tarik & proses pesan masuk (scheduler tiap N detik)
php artisan whatsapp:send-daily-report   # kirim laporan harian (scheduler tiap wa_daily_report_time)
php artisan whatsapp:send-test {--to=}   # debug
```

### 6.3 Scheduler (routes/console.php)
```php
Schedule::command('whatsapp:poll-messages')->everyTenSeconds();
Schedule::command('whatsapp:send-daily-report')->dailyAt(config('whatsapp.daily_report_time', '07:00'));
```
> ⚠️ Wajib aktifkan cron `* * * * * php artisan schedule:run` di VPS (saat ini belum ada).

---

## 7. Message Format (MVP)

### 7.1 Notif PO ke owner
```
📋 *PO Baru Butuh Approval*
No: PO-20260816001
Supplier: CV Maju Jaya
Total: Rp 15.250.000
*Balas:* `approve PO-20260816001` atau `reject PO-20260816001`
```

### 7.2 Command parser (inbound)
- Pattern: `^(approve|reject|setuju|tolak)\s+(PO-[A-Za-z0-9\-]+)$` (case-insensitive, normalisasi angka).
- Ambil PO via `order_no`, cek `canBeApproved()`, lalu panggil `approve()`/`reject()` dengan `approved_by` = user owner (match via `users.whatsapp_number`).
- Balasan otomatis ke owner: konfirmasi "PO X telah di-approve ✅" / error bila PO tidak ditemukan / tidak dalam status pending.

### 7.3 Laporan harian
```
☀️ *Laporan Harian [date]*
Penjualan: Rp xxx
Kas/Bank: Rp xxx
PO pending: N
Invoice jatuh tempo: N
```

---

## 8. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| Gateway lokal semi-legal, nomor bisa banned | Adapter pattern → ganti provider cepat; gunakan nomor bisnis terpisah |
| Inbound spoofing (orang lain balas WA) | Verifikasi `from_number` cocok `users.whatsapp_number` / `wa_owner_phone`; abaikan selain itu |
| Parse ambigu / salah approval | Pattern strict + konfirmasi balik; hanya proses PO berstatus `pending` |
| Duplikat pesan (polling overlap) | Dedup via `gateway_message_id` unique |
| Credential bocor | Secret di `.env` (bukan DB), mask di UI |
| Queue/cron tidak jalan di VPS | Aktifkan cron + supervisor untuk queue worker; dokumentasi deploy |
| Lisensi expire saat jalan | Gate di `WhatsAppService::isEnabled()` cek `wa_module_enabled` + `wa_expiry` |

---

## 9. MVP Task Breakdown

### Phase 1 — Fondasi & Outbound (PO notif)
1. Migration `whatsapp_messages` + `users.whatsapp_number`.
2. `config/whatsapp.php` + `WhatsAppServiceProvider` + `WhatsAppGatewayInterface` + `FonnteGateway`.
3. `WhatsAppService` (send via queue job `SendWhatsAppMessage`, `isEnabled()` lisensi gate).
4. Hook notifikasi saat PO dibuat/update ke `pending` (event/listener atau di controller) → kirim notif ke owner.
5. Settings page + `whatsapp.settings` permission + test message.

### Phase 2 — Inbound (approve/reject)
6. `whatsapp:poll-messages` command + `FonnteGateway::fetchNewMessages()`.
7. `WhatsAppInboundHandler` (parser + call existing approval logic + balasan).
8. Scheduler + cron activation di VPS.
9. PO detail: badge status notifikasi WA.

### Phase 3 — Laporan harian
10. `whatsapp:send-daily-report` + service `DailyReportService` (penjualan, kas/bank, PO pending, invoice jatuh tempo).
11. Scheduler daily + setting jam.

### Phase 4 — Polish & Observability
12. Log viewer + `whatsapp.logs` permission.
13. Dedup inbound + error handling + retry.
14. Dokumentasi deploy (cron + queue) di `references/deploy-flow.md`.

---

## 10. Open Questions (bisa dijawab saat implementasi)

- Provider gateway final (Fonnte sebagai default awal, tapi konfirmasi API key & harga volume WA).
- Format nomor Indonesia (normalisasi `08xx` → `628xx`).
- Apakah perlu tombol interaktif (interactive button approve/reject) selain balas teks — tergantung dukungan provider.
