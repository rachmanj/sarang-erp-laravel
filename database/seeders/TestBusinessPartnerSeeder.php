<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BusinessPartner;

class TestBusinessPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a supplier
        BusinessPartner::create([
            'code' => 'VENDOR001',
            'name' => 'PT Tech Supplier',
            'partner_type' => 'supplier',
            'status' => 'active',
            'tax_id' => '123456789012345',
            'notes' => 'Jl. Teknologi No. 123, Jakarta'
        ]);

        // Create a customer
        BusinessPartner::create([
            'code' => 'CUSTOMER001', 
            'name' => 'PT Digital Solutions',
            'partner_type' => 'customer',
            'status' => 'active',
            'tax_id' => '987654321098765',
            'notes' => 'Jl. Digital No. 456, Jakarta'
        ]);

        $this->command->info('Created test vendor and customer successfully!');
    }
}