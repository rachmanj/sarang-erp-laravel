<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Accounting\Account;

class TradingCoASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codeToId = [];

        $create = function (string $code, string $name, string $type, bool $isPostable = true, ?string $parentCode = null) use (&$codeToId) {
            $parentId = $parentCode ? ($codeToId[$parentCode] ?? null) : null;
            $account = Account::create([
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'is_postable' => $isPostable,
                'parent_id' => $parentId,
            ]);
            $codeToId[$code] = $account->id;
        };

        // Assets (1) - Aset
        $create('1', 'Aset', 'asset', false);

        // Current Assets (1.1) - Aset Lancar
        $create('1.1', 'Aset Lancar', 'asset', false, '1');
        $create('1.1.1', 'Kas dan Setara Kas', 'asset', false, '1.1');
        $create('1.1.1.01', 'Kas di Tangan', 'asset', true, '1.1.1');
        $create('1.1.1.02', 'Kas di Bank - Operasional', 'asset', true, '1.1.1');
        $create('1.1.1.03', 'Kas di Bank - Investasi', 'asset', true, '1.1.1');

        $create('1.1.2', 'Piutang Usaha', 'asset', false, '1.1');
        $create('1.1.2.01', 'Piutang Dagang', 'asset', true, '1.1.2');
        $create('1.1.2.02', 'Piutang Lain-lain', 'asset', true, '1.1.2');
        $create('1.1.2.03', 'Cadangan Kerugian Piutang', 'asset', true, '1.1.2');

        $create('1.1.3', 'Persediaan', 'asset', false, '1.1');
        $create('1.1.3.01', 'Persediaan Barang Dagangan', 'asset', true, '1.1.3');
        $create('1.1.3.01.01', 'Persediaan Stationery', 'asset', true, '1.1.3.01');
        $create('1.1.3.01.02', 'Persediaan Electronics', 'asset', true, '1.1.3.01');
        $create('1.1.3.01.03', 'Persediaan Furniture', 'asset', true, '1.1.3.01');
        $create('1.1.3.01.04', 'Persediaan Vehicles', 'asset', true, '1.1.3.01');
        $create('1.1.3.01.05', 'Persediaan Services', 'asset', true, '1.1.3.01');
        $create('1.1.3.02', 'Persediaan dalam Perjalanan', 'asset', true, '1.1.3');
        $create('1.1.3.03', 'Persediaan Konsinyasi', 'asset', true, '1.1.3');

        $create('1.1.4', 'Pajak Dibayar Dimuka', 'asset', false, '1.1');
        $create('1.1.4.01', 'PPN Masukan', 'asset', true, '1.1.4');
        $create('1.1.4.02', 'PPh Pasal 22 Dibayar Dimuka', 'asset', true, '1.1.4');
        $create('1.1.4.03', 'PPh Pasal 23 Dibayar Dimuka', 'asset', true, '1.1.4');

        $create('1.1.5', 'Biaya Dibayar Dimuka', 'asset', false, '1.1');
        $create('1.1.5.01', 'Sewa Dibayar Dimuka', 'asset', true, '1.1.5');
        $create('1.1.5.02', 'Asuransi Dibayar Dimuka', 'asset', true, '1.1.5');
        $create('1.1.5.03', 'Biaya Lain Dibayar Dimuka', 'asset', true, '1.1.5');

        // Non-Current Assets (1.2) - Aset Tidak Lancar
        $create('1.2', 'Aset Tidak Lancar', 'asset', false, '1');
        $create('1.2.1', 'Aset Tetap', 'asset', false, '1.2');
        $create('1.2.1.01', 'Tanah', 'asset', true, '1.2.1');
        $create('1.2.1.02', 'Bangunan', 'asset', true, '1.2.1');
        $create('1.2.1.03', 'Akumulasi Penyusutan Bangunan', 'asset', true, '1.2.1');
        $create('1.2.1.04', 'Kendaraan', 'asset', true, '1.2.1');
        $create('1.2.1.05', 'Akumulasi Penyusutan Kendaraan', 'asset', true, '1.2.1');
        $create('1.2.1.06', 'Peralatan Kantor', 'asset', true, '1.2.1');
        $create('1.2.1.07', 'Akumulasi Penyusutan Peralatan Kantor', 'asset', true, '1.2.1');

        $create('1.2.2', 'Aset Tidak Berwujud', 'asset', false, '1.2');
        $create('1.2.2.01', 'Goodwill', 'asset', true, '1.2.2');
        $create('1.2.2.02', 'Merek Dagang', 'asset', true, '1.2.2');
        $create('1.2.2.03', 'Lisensi', 'asset', true, '1.2.2');

        // Liabilities (2) - Kewajiban
        $create('2', 'Kewajiban', 'liability', false);

        // Current Liabilities (2.1) - Kewajiban Lancar
        $create('2.1', 'Kewajiban Lancar', 'liability', false, '2');
        $create('2.1.1', 'Utang Usaha', 'liability', false, '2.1');
        $create('2.1.1.01', 'Utang Dagang', 'liability', true, '2.1.1');
        $create('2.1.1.02', 'Utang Lain-lain', 'liability', true, '2.1.1');

        $create('2.1.2', 'Utang Pajak', 'liability', false, '2.1');
        $create('2.1.2.01', 'PPN Keluaran', 'liability', true, '2.1.2');
        $create('2.1.2.02', 'PPh Pasal 21', 'liability', true, '2.1.2');
        $create('2.1.2.03', 'PPh Pasal 22', 'liability', true, '2.1.2');
        $create('2.1.2.04', 'PPh Pasal 23', 'liability', true, '2.1.2');
        $create('2.1.2.05', 'PPh Pasal 25', 'liability', true, '2.1.2');

        $create('2.1.3', 'Utang Jangka Pendek', 'liability', false, '2.1');
        $create('2.1.3.01', 'Utang Bank Jangka Pendek', 'liability', true, '2.1.3');
        $create('2.1.3.02', 'Utang Sewa Jangka Pendek', 'liability', true, '2.1.3');

        $create('2.1.4', 'Biaya yang Masih Harus Dibayar', 'liability', false, '2.1');
        $create('2.1.4.01', 'Gaji yang Masih Harus Dibayar', 'liability', true, '2.1.4');
        $create('2.1.4.02', 'Bunga yang Masih Harus Dibayar', 'liability', true, '2.1.4');

        // Non-Current Liabilities (2.2) - Kewajiban Tidak Lancar
        $create('2.2', 'Kewajiban Tidak Lancar', 'liability', false, '2');
        $create('2.2.1', 'Utang Jangka Panjang', 'liability', false, '2.2');
        $create('2.2.1.01', 'Utang Bank Jangka Panjang', 'liability', true, '2.2.1');
        $create('2.2.1.02', 'Obligasi', 'liability', true, '2.2.1');

        // Equity (3) - Ekuitas
        $create('3', 'Ekuitas', 'net_assets', false);
        $create('3.1', 'Modal Saham', 'net_assets', false, '3');
        $create('3.1.1', 'Modal Saham Biasa', 'net_assets', true, '3.1');
        $create('3.1.2', 'Modal Saham Preferen', 'net_assets', true, '3.1');

        $create('3.2', 'Agio/Disagio Saham', 'net_assets', false, '3');
        $create('3.2.1', 'Agio Saham', 'net_assets', true, '3.2');
        $create('3.2.2', 'Disagio Saham', 'net_assets', true, '3.2');

        $create('3.3', 'Laba Ditahan', 'net_assets', false, '3');
        $create('3.3.1', 'Saldo Awal Laba Ditahan', 'net_assets', true, '3.3');
        $create('3.3.2', 'Laba Tahun Berjalan', 'net_assets', true, '3.3');

        // Revenue (4) - Pendapatan
        $create('4', 'Pendapatan', 'income', false);
        $create('4.1', 'Pendapatan Usaha', 'income', false, '4');
        $create('4.1.1', 'Penjualan Barang Dagangan', 'income', false, '4.1');
        $create('4.1.1.01', 'Penjualan Stationery', 'income', true, '4.1.1');
        $create('4.1.1.02', 'Penjualan Electronics', 'income', true, '4.1.1');
        $create('4.1.1.03', 'Penjualan Furniture', 'income', true, '4.1.1');
        $create('4.1.1.04', 'Penjualan Vehicles', 'income', true, '4.1.1');
        $create('4.1.1.05', 'Penjualan Services', 'income', true, '4.1.1');
        $create('4.1.2', 'Retur Penjualan', 'income', true, '4.1');
        $create('4.1.3', 'Diskon Penjualan', 'income', true, '4.1');
        $create('4.1.4', 'Potongan Penjualan', 'income', true, '4.1');

        $create('4.2', 'Pendapatan Lain-lain', 'income', false, '4');
        $create('4.2.1', 'Pendapatan Sewa', 'income', true, '4.2');
        $create('4.2.2', 'Pendapatan Bunga', 'income', true, '4.2');
        $create('4.2.3', 'Pendapatan Kurs Selisih', 'income', true, '4.2');

        // Cost of Goods Sold (5) - Harga Pokok Penjualan
        $create('5', 'Harga Pokok Penjualan', 'expense', false);
        $create('5.1', 'HPP Barang Dagangan', 'expense', false, '5');
        $create('5.1.01', 'HPP Stationery', 'expense', true, '5.1');
        $create('5.1.02', 'HPP Electronics', 'expense', true, '5.1');
        $create('5.1.03', 'HPP Furniture', 'expense', true, '5.1');
        $create('5.1.04', 'HPP Vehicles', 'expense', true, '5.1');
        $create('5.1.05', 'HPP Services', 'expense', true, '5.1');
        $create('5.2', 'Retur Pembelian', 'expense', true, '5');
        $create('5.3', 'Diskon Pembelian', 'expense', true, '5');
        $create('5.4', 'Potongan Pembelian', 'expense', true, '5');
        $create('5.5', 'Biaya Pengiriman Masuk', 'expense', true, '5');
        $create('5.6', 'Biaya Asuransi Masuk', 'expense', true, '5');
        $create('5.7', 'Penyesuaian Persediaan', 'expense', true, '5');

        // Operating Expenses (6) - Beban Operasional
        $create('6', 'Beban Operasional', 'expense', false);
        $create('6.1', 'Beban Penjualan', 'expense', false, '6');
        $create('6.1.1', 'Gaji Karyawan Penjualan', 'expense', true, '6.1');
        $create('6.1.2', 'Komisi Penjualan', 'expense', true, '6.1');
        $create('6.1.3', 'Biaya Iklan dan Promosi', 'expense', true, '6.1');
        $create('6.1.4', 'Biaya Pengiriman Keluar', 'expense', true, '6.1');
        $create('6.1.5', 'Biaya Asuransi Keluar', 'expense', true, '6.1');
        $create('6.1.6', 'Biaya Pameran', 'expense', true, '6.1');

        $create('6.2', 'Beban Administrasi dan Umum', 'expense', false, '6');
        $create('6.2.1', 'Gaji Karyawan Administrasi', 'expense', true, '6.2');
        $create('6.2.2', 'Biaya Sewa Kantor', 'expense', true, '6.2');
        $create('6.2.3', 'Biaya Listrik dan Air', 'expense', true, '6.2');
        $create('6.2.4', 'Biaya Telepon dan Internet', 'expense', true, '6.2');
        $create('6.2.5', 'Biaya Perjalanan Dinas', 'expense', true, '6.2');
        $create('6.2.6', 'Biaya Konsultan', 'expense', true, '6.2');
        $create('6.2.7', 'Biaya Legal dan Notaris', 'expense', true, '6.2');
        $create('6.2.8', 'Biaya Audit', 'expense', true, '6.2');
        $create('6.2.9', 'Biaya Penyusutan', 'expense', true, '6.2');
        $create('6.2.10', 'Biaya Asuransi', 'expense', true, '6.2');
        $create('6.2.11', 'Biaya Pemeliharaan', 'expense', true, '6.2');
        $create('6.2.12', 'Biaya Lain-lain', 'expense', true, '6.2');

        // Other Income/Expenses (7) - Pendapatan dan Beban Lain-lain
        $create('7', 'Pendapatan dan Beban Lain-lain', 'income', false);
        $create('7.1', 'Pendapatan Lain-lain', 'income', false, '7');
        $create('7.1.1', 'Pendapatan Sewa', 'income', true, '7.1');
        $create('7.1.2', 'Pendapatan Bunga', 'income', true, '7.1');
        $create('7.1.3', 'Keuntungan Selisih Kurs', 'income', true, '7.1');

        $create('7.2', 'Beban Lain-lain', 'expense', false, '7');
        $create('7.2.1', 'Kerugian Selisih Kurs', 'expense', true, '7.2');
        $create('7.2.2', 'Beban Bunga', 'expense', true, '7.2');
        $create('7.2.3', 'Kerugian Penjualan Aset', 'expense', true, '7.2');
    }
}
