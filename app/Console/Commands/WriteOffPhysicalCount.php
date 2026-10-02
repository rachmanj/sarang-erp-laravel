<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryValuation;
use App\Models\InventoryWarehouseStock;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WriteOffPhysicalCount extends Command
{
    /**
     * @var string
     */
    protected $signature = 'inventory:write-off-physical-count
        {--file= : Berkas CSV dua kolom (code,physical_qty). Default storage/app/writeoff.csv}
        {--date= : Tanggal transaksi penyesuaian (YYYY-MM-DD). Default hari ini}
        {--execute : Tulis perubahan (wajib bersama --force)}
        {--force : Menyatakan bahwa kamu sadar perintah ini mengubah data}';

    /**
     * @var string
     */
    protected $description = 'Write-off persediaan berdasarkan hasil hitung fisik: menurunkan buku besar, stok gudang, dan valuasi. TIDAK membuat jurnal.';

    /** @var array<int, array<int, string>> */
    private array $reportRows = [];

    /** @var array<int, string> */
    private array $errors = [];

    private int $itemsProcessed = 0;

    private int $itemsSkippedEqual = 0;

    private int $itemsSkippedIncrease = 0;

    private int $rowsUpdated = 0;

    private int $unitsRemoved = 0;

    private float $valueRemoved = 0.0;

    public function handle(InventoryService $inventoryService): int
    {
        $execute = (bool) $this->option('execute');
        $force = (bool) $this->option('force');

        if ($execute && ! $force) {
            $this->error('Menolak menulis: jalankan dengan --execute dan --force sekaligus.');

            return self::FAILURE;
        }

        $dateOption = $this->option('date');
        $date = $dateOption !== null && $dateOption !== '' ? (string) $dateOption : now()->toDateString();

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error("Tanggal '{$date}' tidak valid. Pakai format YYYY-MM-DD.");

            return self::FAILURE;
        }

        $file = $this->option('file');
        $path = $file !== null && $file !== '' ? (string) $file : storage_path('app/writeoff.csv');

        if (! is_readable($path)) {
            $this->error("Berkas tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if ($rows === []) {
            $this->error("Berkas {$path} tidak berisi baris data yang bisa dibaca (format: code,physical_qty).");

            return self::FAILURE;
        }

        $backups = [];

        if ($execute) {
            $backups = $this->createBackupTables();
            foreach ($backups as $label => $table) {
                $this->info("Backup {$label}: {$table}");
            }
        } else {
            $this->comment('DRY-RUN: tidak ada perubahan yang ditulis.');
        }

        foreach ($rows as $row) {
            $code = $row['code'];
            $physical = $row['physical'];

            $item = InventoryItem::query()->where('code', $code)->first();

            if ($item === null) {
                $this->errors[] = "Kode '{$code}' tidak ditemukan di database.";
                $this->warn("Item '{$code}' tidak ditemukan - dilewati.");

                continue;
            }

            $ledger = $this->ledgerTotal($item);
            $delta = $physical - $ledger;

            if ($delta === 0) {
                $this->itemsSkippedEqual++;

                continue;
            }

            if ($delta > 0) {
                $this->itemsSkippedIncrease++;
                $this->errors[] = "Item {$code}: fisik {$physical} LEBIH BESAR dari buku besar {$ledger}. Perintah ini hanya untuk mengurangi stok.";

                continue;
            }

            $unitCost = $this->unitCostFor($item);
            $notes = "Write-off hasil hitung fisik {$date}: sistem {$ledger} -> fisik {$physical}";
            $units = abs($delta);
            $value = round($units * $unitCost, 2);

            $actions = [];

            if ($execute) {
                try {
                    DB::transaction(function () use ($item, $delta, $unitCost, $notes, $date, $physical, &$actions): void {
                        $warehouseId = app(InventoryService::class)->resolveWarehouseId($item, null);

                        app(InventoryService::class)->processAdjustmentTransaction(
                            (int) $item->id,
                            (int) $delta,
                            (float) $unitCost,
                            $notes,
                            $warehouseId
                        );

                        $actions = array_merge($actions, $this->forceWarehouseTotalTo($item, $physical));

                        app(InventoryService::class)->updateItemValuationAfterDataRepair($item);

                        $item->refresh();
                    });
                } catch (\Throwable $exception) {
                    $this->error("Gagal memproses {$code}: {$exception->getMessage()}");
                    $this->errors[] = "Item {$code} gagal: {$exception->getMessage()}";

                    continue;
                }
            } else {
                $actions[] = "akan disesuaikan ke fisik {$physical}";
            }

            if ($actions !== []) {
                $this->rowsUpdated++;
            }

            $this->itemsProcessed++;
            $this->unitsRemoved += $units;
            $this->valueRemoved += $value;

            $this->reportRows[] = [
                $code,
                mb_substr((string) $item->name, 0, 34),
                (string) $ledger,
                (string) $physical,
                (string) $units,
                number_format($unitCost, 2, ',', '.'),
                number_format($value, 2, ',', '.'),
                implode('; ', $actions),
            ];
        }

        if ($this->reportRows !== []) {
            $this->table(
                ['Kode', 'Nama', 'Buku Besar', 'Fisik', 'Unit Dihapus', 'Harga Satuan', 'Nilai Dihapus', 'Aksi'],
                $this->reportRows
            );
        }

        $mode = $execute ? 'EXECUTE' : 'DRY-RUN';

        $this->newLine();
        $this->info("Ringkasan ({$mode}, tanggal {$date}):");
        $this->line('Item diproses: '.$this->itemsProcessed);
        $this->line('Item dilewati (buku besar sudah sama dengan fisik): '.$this->itemsSkippedEqual);
        $this->line('Item ditolak (fisik lebih besar dari buku besar): '.$this->itemsSkippedIncrease);
        $this->line('Baris disesuaikan: '.$this->rowsUpdated);
        $this->line('Total unit dihapus: '.$this->unitsRemoved);
        $this->line('TOTAL NILAI WRITE-OFF: Rp '.number_format($this->valueRemoved, 2, ',', '.'));

        if ($execute) {
            foreach ($backups as $label => $table) {
                $this->line("Backup {$label}: {$table}");
            }
        }

        if ($this->errors !== []) {
            $this->newLine();
            $this->warn('Catatan/kesalahan:');
            foreach ($this->errors as $error) {
                $this->line(' - '.$error);
            }
        }

        $this->newLine();
        $this->warn('PENTING: perintah ini TIDAK membuat jurnal. Sisi GL (Dr kerugian persediaan / Cr persediaan) harus disusun Finance.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{code: string, physical: int}>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return [];
        }

        $first = true;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = str_getcsv($line);

            if (count($parts) < 2) {
                continue;
            }

            $code = trim($parts[0], " \t\"'");
            $rawQty = trim($parts[1], " \t\"'");

            if ($first) {
                $first = false;

                if (! is_numeric($rawQty)) {
                    continue; // baris header
                }
            }

            if ($code === '' || ! is_numeric($rawQty)) {
                $this->errors[] = "Baris dilewati (tidak bisa dibaca): {$line}";

                continue;
            }

            $rows[] = ['code' => $code, 'physical' => (int) $rawQty];
        }

        fclose($handle);

        return $rows;
    }

    private function ledgerTotal(InventoryItem $item): int
    {
        return (int) InventoryTransaction::query()
            ->where('item_id', $item->id)
            ->sum('quantity');
    }

    private function unitCostFor(InventoryItem $item): float
    {
        $valuation = InventoryValuation::query()
            ->where('item_id', $item->id)
            ->orderByDesc('valuation_date')
            ->orderByDesc('id')
            ->first();

        if ($valuation !== null && (float) $valuation->unit_cost > 0) {
            return (float) $valuation->unit_cost;
        }

        return (float) $item->purchase_price;
    }

    /**
     * Paksa total stok gudang item sama dengan angka fisik.
     * Hanya MENGURANGI, dari baris terbesar dulu, dan tidak pernah di bawah nol.
     *
     * @return array<int, string>
     */
    private function forceWarehouseTotalTo(InventoryItem $item, int $physical): array
    {
        $rows = InventoryWarehouseStock::query()
            ->where('item_id', $item->id)
            ->orderByDesc('quantity_on_hand')
            ->get();

        $total = (int) $rows->sum('quantity_on_hand');

        if ($total <= $physical) {
            return [];
        }

        $excess = $total - $physical;
        $actions = [];

        foreach ($rows as $row) {
            if ($excess <= 0) {
                break;
            }

            $available = (int) $row->quantity_on_hand;

            if ($available <= 0) {
                continue;
            }

            $take = min($available, $excess);
            $old = (int) $row->quantity_on_hand;

            $row->quantity_on_hand = $old - $take;
            $row->updateAvailableQuantity();
            $row->save();

            $actions[] = "wh {$row->warehouse_id}: {$old} => {$row->quantity_on_hand}";
            $excess -= $take;
        }

        return $actions;
    }

    /**
     * @return array<string, string>
     */
    private function createBackupTables(): array
    {
        $suffix = now()->format('Ymd_His');

        return [
            'inventory_transactions' => $this->copyTable('inventory_transactions', $suffix),
            'inventory_warehouse_stock' => $this->copyTable('inventory_warehouse_stock', $suffix),
            'inventory_valuations' => $this->copyTable('inventory_valuations', $suffix),
        ];
    }

    private function copyTable(string $source, string $suffix): string
    {
        $table = $source.'_bak_'.$suffix;
        $attempt = 0;

        while (Schema::hasTable($table)) {
            $attempt++;
            $table = $source.'_bak_'.$suffix.'_'.$attempt;

            if ($attempt > 99) {
                throw new \RuntimeException("Tidak bisa mendapat nama tabel backup unik untuk {$source} (suffix {$suffix})");
            }
        }

        DB::statement("CREATE TABLE `{$table}` AS SELECT * FROM `{$source}`");

        return $table;
    }
}
