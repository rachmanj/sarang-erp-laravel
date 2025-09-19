<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessPartner;
use App\Models\BusinessPartnerContact;
use App\Models\BusinessPartnerAddress;
use App\Models\BusinessPartnerDetail;

class BusinessPartnerSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample business partners...');

        // Create sample customers
        $this->createSamplePartners();

        $this->command->info('Sample business partners created successfully!');
    }

    /**
     * Create sample business partners of all types
     */
    private function createSamplePartners(): void
    {
        // Sample customer
        $customer = BusinessPartner::create([
            'code' => 'CUST001',
            'name' => 'PT Maju Bersama',
            'partner_type' => 'customer',
            'status' => 'active',
            'registration_number' => '01.234.567.8-123.456',
            'website' => 'https://majubersama.co.id',
            'notes' => 'Large retail customer with multiple locations',
        ]);

        // Create customer contact
        BusinessPartnerContact::create([
            'business_partner_id' => $customer->id,
            'contact_type' => 'primary',
            'name' => 'Budi Santoso',
            'position' => 'Purchasing Manager',
            'email' => 'budi@majubersama.co.id',
            'phone' => '021-5551234',
            'mobile' => '0812-3456-7890',
            'is_primary' => true,
        ]);

        // Create customer address
        BusinessPartnerAddress::create([
            'business_partner_id' => $customer->id,
            'address_type' => 'billing',
            'address_line_1' => 'Jl. Sudirman No. 123',
            'address_line_2' => 'Lantai 15',
            'city' => 'Jakarta',
            'state_province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'country' => 'Indonesia',
            'is_primary' => true,
        ]);

        // Create customer details
        BusinessPartnerDetail::create([
            'business_partner_id' => $customer->id,
            'section_type' => 'financial',
            'field_name' => 'credit_limit',
            'field_value' => '100000000',
            'field_type' => 'number',
        ]);

        // Sample supplier
        $supplier = BusinessPartner::create([
            'code' => 'SUPP001',
            'name' => 'PT Makmur Jaya',
            'partner_type' => 'supplier',
            'status' => 'active',
            'registration_number' => '03.456.789.0-345.678',
            'website' => 'https://makmurjaya.co.id',
            'notes' => 'Primary raw material supplier',
        ]);

        // Create supplier contact
        BusinessPartnerContact::create([
            'business_partner_id' => $supplier->id,
            'contact_type' => 'primary',
            'name' => 'Hendra Gunawan',
            'position' => 'Sales Director',
            'email' => 'hendra@makmurjaya.co.id',
            'phone' => '021-7778888',
            'mobile' => '0818-1234-5678',
            'is_primary' => true,
        ]);

        // Create supplier address
        BusinessPartnerAddress::create([
            'business_partner_id' => $supplier->id,
            'address_type' => 'registered',
            'address_line_1' => 'Jl. Industri Raya No. 45',
            'city' => 'Bekasi',
            'state_province' => 'Jawa Barat',
            'postal_code' => '17530',
            'country' => 'Indonesia',
            'is_primary' => true,
        ]);

        // Create supplier details
        BusinessPartnerDetail::create([
            'business_partner_id' => $supplier->id,
            'section_type' => 'banking',
            'field_name' => 'bank_name',
            'field_value' => 'Bank Mandiri',
            'field_type' => 'text',
        ]);

        // Sample both type
        $both = BusinessPartner::create([
            'code' => 'BOTH001',
            'name' => 'PT Mitra Sejati',
            'partner_type' => 'both',
            'status' => 'active',
            'registration_number' => '05.678.901.2-567.890',
            'website' => 'https://mitrasejati.co.id',
            'notes' => 'Strategic partner for both supply and distribution',
        ]);

        // Create both type contact
        BusinessPartnerContact::create([
            'business_partner_id' => $both->id,
            'contact_type' => 'primary',
            'name' => 'Joko Susanto',
            'position' => 'CEO',
            'email' => 'joko@mitrasejati.co.id',
            'phone' => '021-9998888',
            'mobile' => '0816-8765-4321',
            'is_primary' => true,
        ]);

        // Create both type address
        BusinessPartnerAddress::create([
            'business_partner_id' => $both->id,
            'address_type' => 'registered',
            'address_line_1' => 'Jl. MT Haryono Kav. 15',
            'address_line_2' => 'Gedung Graha Mitra, Lantai 10',
            'city' => 'Jakarta',
            'state_province' => 'DKI Jakarta',
            'postal_code' => '12810',
            'country' => 'Indonesia',
            'is_primary' => true,
        ]);

        $this->command->info('Created 3 sample business partners (1 customer, 1 supplier, 1 both).');
    }
}
