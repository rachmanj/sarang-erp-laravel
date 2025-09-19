<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BusinessPartner;
use App\Models\BusinessPartnerContact;
use App\Models\BusinessPartnerAddress;
use App\Models\BusinessPartnerDetail;

class BusinessPartnerMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Business Partner migration...');

        // Migrate vendors to business partners
        $this->migrateVendors();

        // Migrate customers to business partners
        $this->migrateCustomers();

        // Check for duplicates and merge them
        $this->mergeDuplicates();

        $this->command->info('Business Partner migration completed successfully!');
    }

    /**
     * Migrate vendors to business partners
     */
    private function migrateVendors(): void
    {
        $this->command->info('Migrating vendors to business partners...');

        $vendors = DB::table('vendors')->get();
        $count = 0;

        foreach ($vendors as $vendor) {
            // Check if this vendor already exists as a business partner
            $existingPartner = BusinessPartner::where('code', $vendor->code)->first();

            if ($existingPartner) {
                // If it exists, update the partner_type to 'both' if it was a customer
                if ($existingPartner->partner_type === 'customer') {
                    $existingPartner->update(['partner_type' => 'both']);
                    $this->command->info("Updated partner type to 'both' for {$vendor->name} ({$vendor->code})");
                }
                continue;
            }

            // Create new business partner
            $businessPartner = BusinessPartner::create([
                'code' => $vendor->code,
                'name' => $vendor->name,
                'partner_type' => 'supplier',
                'status' => 'active',
                'registration_number' => $vendor->npwp,
                'tax_id' => null,
                'website' => null,
                'notes' => null,
            ]);

            // Create address
            if ($vendor->address) {
                BusinessPartnerAddress::create([
                    'business_partner_id' => $businessPartner->id,
                    'address_type' => 'billing',
                    'address_line_1' => $vendor->address,
                    'city' => 'Unknown', // Required field, default value
                    'is_primary' => true,
                ]);
            }

            // Create contact
            if ($vendor->email || $vendor->phone) {
                BusinessPartnerContact::create([
                    'business_partner_id' => $businessPartner->id,
                    'contact_type' => 'primary',
                    'name' => $vendor->name,
                    'email' => $vendor->email,
                    'phone' => $vendor->phone,
                    'is_primary' => true,
                ]);
            }

            $count++;
        }

        $this->command->info("Migrated {$count} vendors to business partners.");
    }

    /**
     * Migrate customers to business partners
     */
    private function migrateCustomers(): void
    {
        $this->command->info('Migrating customers to business partners...');

        $customers = DB::table('customers')->get();
        $count = 0;

        foreach ($customers as $customer) {
            // Check if this customer already exists as a business partner
            $existingPartner = BusinessPartner::where('code', $customer->code)->first();

            if ($existingPartner) {
                // If it exists, update the partner_type to 'both' if it was a supplier
                if ($existingPartner->partner_type === 'supplier') {
                    $existingPartner->update(['partner_type' => 'both']);
                    $this->command->info("Updated partner type to 'both' for {$customer->name} ({$customer->code})");
                }
                continue;
            }

            // Create new business partner
            $businessPartner = BusinessPartner::create([
                'code' => $customer->code,
                'name' => $customer->name,
                'partner_type' => 'customer',
                'status' => 'active',
                'registration_number' => $customer->npwp,
                'tax_id' => null,
                'website' => null,
                'notes' => null,
            ]);

            // Create address
            if ($customer->address) {
                BusinessPartnerAddress::create([
                    'business_partner_id' => $businessPartner->id,
                    'address_type' => 'billing',
                    'address_line_1' => $customer->address,
                    'city' => 'Unknown', // Required field, default value
                    'is_primary' => true,
                ]);
            }

            // Create contact
            if ($customer->email || $customer->phone) {
                BusinessPartnerContact::create([
                    'business_partner_id' => $businessPartner->id,
                    'contact_type' => 'primary',
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'is_primary' => true,
                ]);
            }

            // Migrate customer pricing tiers to business partner details
            $pricingTiers = DB::table('customer_pricing_tiers')
                ->where('customer_id', $customer->id)
                ->orderBy('discount_percentage', 'desc')
                ->first();

            if ($pricingTiers) {
                BusinessPartnerDetail::create([
                    'business_partner_id' => $businessPartner->id,
                    'section_type' => 'financial',
                    'field_name' => 'discount_percentage',
                    'field_value' => $pricingTiers->discount_percentage,
                    'field_type' => 'number',
                ]);
            }

            // Migrate customer credit limits to business partner details
            $creditLimit = DB::table('customer_credit_limits')
                ->where('customer_id', $customer->id)
                ->first();

            if ($creditLimit) {
                BusinessPartnerDetail::create([
                    'business_partner_id' => $businessPartner->id,
                    'section_type' => 'financial',
                    'field_name' => 'credit_limit',
                    'field_value' => $creditLimit->credit_limit,
                    'field_type' => 'number',
                ]);
            }

            $count++;
        }

        $this->command->info("Migrated {$count} customers to business partners.");
    }

    /**
     * Find and merge duplicate business partners
     */
    private function mergeDuplicates(): void
    {
        $this->command->info('Checking for duplicate business partners...');

        // Find potential duplicates by name (case insensitive)
        $potentialDuplicates = BusinessPartner::select('name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($potentialDuplicates->count() === 0) {
            $this->command->info('No duplicates found.');
            return;
        }

        $this->command->info("Found {$potentialDuplicates->count()} potential duplicate names.");

        foreach ($potentialDuplicates as $duplicate) {
            $partners = BusinessPartner::where('name', $duplicate->name)->get();

            $this->command->info("Processing potential duplicates for '{$duplicate->name}'...");

            // If we have both a customer and supplier with the same name, merge them
            $customer = $partners->firstWhere('partner_type', 'customer');
            $supplier = $partners->firstWhere('partner_type', 'supplier');

            if ($customer && $supplier) {
                $this->command->info("Merging customer and supplier records for '{$duplicate->name}'");

                // Keep the supplier record and update it to 'both'
                $supplier->update(['partner_type' => 'both']);

                // Move all related data from customer to supplier
                $this->migrateRelatedData($customer->id, $supplier->id);

                // Delete the customer record
                $customer->delete();

                $this->command->info("Successfully merged records for '{$duplicate->name}'");
            }
        }
    }

    /**
     * Migrate related data from one business partner to another
     */
    private function migrateRelatedData($fromId, $toId): void
    {
        // Migrate contacts
        DB::table('business_partner_contacts')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate addresses
        DB::table('business_partner_addresses')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate details
        DB::table('business_partner_details')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate sales orders
        DB::table('sales_orders')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate purchase orders
        DB::table('purchase_orders')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate delivery orders
        DB::table('delivery_orders')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate sales invoices
        DB::table('sales_invoices')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate purchase invoices
        DB::table('purchase_invoices')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate sales receipts
        DB::table('sales_receipts')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate purchase payments
        DB::table('purchase_payments')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate goods receipts
        DB::table('goods_receipts')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);

        // Migrate assets
        DB::table('assets')
            ->where('business_partner_id', $fromId)
            ->update(['business_partner_id' => $toId]);
    }
}
