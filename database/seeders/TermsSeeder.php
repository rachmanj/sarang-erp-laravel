<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TermsSeeder extends Seeder
{
    public function run(): void
    {
        // Store default terms in a simple settings table or fallback to inserting example due dates via update
        if (Schema::hasTable('sales_invoices')) {
            DB::table('sales_invoices')->whereNull('terms_days')->update(['terms_days' => 30]);
        }
        if (Schema::hasTable('purchase_invoices')) {
            DB::table('purchase_invoices')->whereNull('terms_days')->update(['terms_days' => 30]);
        }
    }
}
