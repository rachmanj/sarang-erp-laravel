<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GRGIPurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purposes = [
            // Goods Receipt Purposes
            [
                'code' => 'GR_RETURN',
                'name' => 'Customer Return',
                'description' => 'Goods returned by customers',
                'type' => 'goods_receipt',
                'is_active' => true,
            ],
            [
                'code' => 'GR_DONATION',
                'name' => 'Donation Received',
                'description' => 'Goods received as donation',
                'type' => 'goods_receipt',
                'is_active' => true,
            ],
            [
                'code' => 'GR_SAMPLE',
                'name' => 'Sample Received',
                'description' => 'Sample products received',
                'type' => 'goods_receipt',
                'is_active' => true,
            ],
            [
                'code' => 'GR_FOUND',
                'name' => 'Found Inventory',
                'description' => 'Inventory found during stock count',
                'type' => 'goods_receipt',
                'is_active' => true,
            ],
            [
                'code' => 'GR_CONSIGNMENT',
                'name' => 'Consignment Received',
                'description' => 'Consignment goods received',
                'type' => 'goods_receipt',
                'is_active' => true,
            ],
            [
                'code' => 'GR_TRANSFER_IN',
                'name' => 'Transfer In',
                'description' => 'Goods transferred from other location',
                'type' => 'goods_receipt',
                'is_active' => true,
            ],

            // Goods Issue Purposes
            [
                'code' => 'GI_INTERNAL',
                'name' => 'Internal Consumption',
                'description' => 'Goods used for internal purposes',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_SAMPLE',
                'name' => 'Sample Given',
                'description' => 'Sample products given to customers',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_DAMAGED',
                'name' => 'Damaged Goods',
                'description' => 'Damaged goods disposal',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_DONATION',
                'name' => 'Donation Given',
                'description' => 'Goods donated to charity',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_EMPLOYEE',
                'name' => 'Employee Benefits',
                'description' => 'Goods given as employee benefits',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_RND',
                'name' => 'R&D Materials',
                'description' => 'Materials used for research and development',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_QC',
                'name' => 'Quality Control',
                'description' => 'Goods used for quality control testing',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
            [
                'code' => 'GI_TRANSFER_OUT',
                'name' => 'Transfer Out',
                'description' => 'Goods transferred to other location',
                'type' => 'goods_issue',
                'is_active' => true,
            ],
        ];

        foreach ($purposes as $purpose) {
            DB::table('gr_gi_purposes')->insert(array_merge($purpose, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
