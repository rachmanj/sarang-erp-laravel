<?php

namespace App\Console\Commands;

use Database\Seeders\DashboardDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedDashboardDemoData extends Command
{
    protected $signature = 'dashboard:seed-demo {--fresh : Remove previously generated demo records before seeding}';

    protected $description = 'Populate realistic multi-month demo data to power dashboard widgets';

    public function handle(): int
    {
        $this->info('Preparing dashboard demo dataset...');

        if ($this->option('fresh')) {
            $this->purgeDemoRecords();
        }

        $exitCode = Artisan::call('db:seed', [
            '--class' => DashboardDemoSeeder::class,
        ]);

        if ($exitCode !== Command::SUCCESS) {
            $this->error('Dashboard demo seeding failed.');
            return $exitCode;
        }

        $this->line(Artisan::output());
        $this->info('Dashboard demo data ready. Open /dashboard to review updated widgets.');

        return Command::SUCCESS;
    }

    private function purgeDemoRecords(): void
    {
        $this->warn('Removing existing dashboard demo records...');

        DB::transaction(function () {
            DB::table('document_relationships')->where('notes', 'like', 'Demo%')->delete();

            DB::table('tax_calendar')->where('description', 'like', 'Dashboard demo%')->delete();
            DB::table('tax_compliance_logs')->where('description', 'like', 'Demo tax%')->delete();
            DB::table('tax_reports')->where('report_name', 'like', 'PPN Monthly Filing%')->delete();
            DB::table('tax_transactions')->where('reference_type', 'sales_invoice')->where('reference_id', 0)->delete();
            DB::table('tax_settings')->where('setting_key', 'dashboard_demo_tax_flag')->delete();

            DB::table('supplier_performance')->where('notes', 'like', 'Dashboard demo%')->delete();
            DB::table('gr_gi_headers')->where('document_number', 'like', 'GRGI-DEMO-%')->delete();

            DB::table('delivery_orders')->where('do_number', 'like', 'DO-DEMO-%')->delete();
            DB::table('sales_orders')->where('order_no', 'like', 'SO-DEMO-%')->delete();
            DB::table('purchase_orders')->where('order_no', 'like', 'PO-DEMO-%')->delete();

            DB::table('sales_invoices')->where('invoice_no', 'like', 'SINV-DEMO-%')->delete();
            DB::table('purchase_invoices')->where('invoice_no', 'like', 'PINV-DEMO-%')->delete();

            DB::table('journals')->where('journal_no', 'like', 'JNL-DEMO-%')->delete();

            $itemIds = DB::table('inventory_items')->where('code', 'like', 'DEMO-%')->pluck('id');
            if ($itemIds->isNotEmpty()) {
                DB::table('inventory_warehouse_stock')->whereIn('item_id', $itemIds)->delete();
                DB::table('inventory_valuations')->whereIn('item_id', $itemIds)->delete();
            }
            DB::table('inventory_items')->where('code', 'like', 'DEMO-%')->delete();

            DB::table('business_partners')->where('code', 'like', 'DEMO-%')->delete();
            DB::table('assets')->where('code', 'like', 'DEMO-%')->delete();
            DB::table('asset_depreciation_runs')->where('notes', 'like', 'Demo depreciation run%')->delete();
        });
    }
}