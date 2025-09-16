<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Master\Customer;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPricingTier;
use App\Models\CustomerPerformance;

class TrainingCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create customers for training scenarios
        $customers = [
            [
                'code' => 'CUST-001',
                'name' => 'PT Maju Jaya',
                'email' => 'procurement@majujaya.co.id',
                'phone' => '+62-21-12345678',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'tax_id' => '01.234.567.8-901.000',
                'industry' => 'Construction',
                'contact_person' => 'Bapak Ahmad Wijaya',
                'credit_limit' => 500000000,
                'payment_terms' => 30,
                'credit_rating' => 'A',
                'risk_category' => 'Low Risk',
                'pricing_tier' => 'Tier 2',
                'discount_level' => 5,
            ],
            [
                'code' => 'CUST-002',
                'name' => 'CV Teknologi Maju',
                'email' => 'purchasing@tekmaju.co.id',
                'phone' => '+62-21-87654321',
                'address' => 'Jl. Thamrin No. 456, Jakarta',
                'tax_id' => '02.345.678.9-012.000',
                'industry' => 'Technology',
                'contact_person' => 'Ibu Sari Indah',
                'credit_limit' => 200000000,
                'payment_terms' => 15,
                'credit_rating' => 'B',
                'risk_category' => 'Medium Risk',
                'pricing_tier' => 'Tier 3',
                'discount_level' => 3,
            ],
            [
                'code' => 'CUST-003',
                'name' => 'PT Furniture Indonesia',
                'email' => 'orders@furniture.co.id',
                'phone' => '+62-21-11223344',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta',
                'tax_id' => '03.456.789.0-123.000',
                'industry' => 'Furniture',
                'contact_person' => 'Bapak Budi Santoso',
                'credit_limit' => 750000000,
                'payment_terms' => 45,
                'credit_rating' => 'A+',
                'risk_category' => 'Low Risk',
                'pricing_tier' => 'Tier 1',
                'discount_level' => 8,
            ],
            [
                'code' => 'CUST-004',
                'name' => 'UD Sumber Makmur',
                'email' => 'info@sumbermakmur.co.id',
                'phone' => '+62-21-55667788',
                'address' => 'Jl. Pasar Minggu No. 321, Jakarta',
                'tax_id' => '04.567.890.1-234.000',
                'industry' => 'Retail',
                'contact_person' => 'Ibu Dewi Lestari',
                'credit_limit' => 100000000,
                'payment_terms' => 14,
                'credit_rating' => 'C',
                'risk_category' => 'High Risk',
                'pricing_tier' => 'Tier 4',
                'discount_level' => 1,
            ],
            [
                'code' => 'CUST-005',
                'name' => 'PT Global Trading',
                'email' => 'sales@globaltrading.co.id',
                'phone' => '+62-21-99887766',
                'address' => 'Jl. Kuningan No. 654, Jakarta',
                'tax_id' => '05.678.901.2-345.000',
                'industry' => 'Trading',
                'contact_person' => 'Bapak Rudi Hartono',
                'credit_limit' => 1000000000,
                'payment_terms' => 60,
                'credit_rating' => 'A+',
                'risk_category' => 'Low Risk',
                'pricing_tier' => 'Tier 1',
                'discount_level' => 10,
            ],
        ];

        foreach ($customers as $customerData) {
            // Create customer
            $customer = Customer::updateOrCreate(
                ['code' => $customerData['code']],
                [
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'],
                ]
            );

            // Create credit limit
            CustomerCreditLimit::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'credit_limit' => $customerData['credit_limit'],
                    'payment_terms' => $customerData['payment_terms'],
                    'credit_rating' => $customerData['credit_rating'],
                    'risk_category' => $customerData['risk_category'],
                    'is_active' => true,
                ]
            );

            // Create pricing tier
            CustomerPricingTier::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'tier_name' => $customerData['pricing_tier'],
                    'discount_percentage' => $customerData['discount_level'],
                    'volume_discount_threshold' => 200000000,
                    'volume_discount_percentage' => 10,
                    'is_active' => true,
                ]
            );

            // Create performance record
            CustomerPerformance::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'total_orders' => rand(10, 100),
                    'total_value' => rand(50000000, $customerData['credit_limit']),
                    'average_order_value' => rand(5000000, 50000000),
                    'payment_performance_score' => rand(70, 100),
                    'order_frequency_score' => rand(60, 100),
                    'overall_score' => rand(70, 100),
                    'last_order_date' => now()->subDays(rand(1, 30)),
                ]
            );
        }
    }
}
