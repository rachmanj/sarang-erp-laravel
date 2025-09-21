<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Count Units
            ['code' => 'PCS', 'name' => 'Pieces', 'description' => 'Individual pieces', 'unit_type' => 'count', 'is_base_unit' => true],
            ['code' => 'DOZ', 'name' => 'Dozen', 'description' => '12 pieces', 'unit_type' => 'count', 'is_base_unit' => false],
            ['code' => 'GROSS', 'name' => 'Gross', 'description' => '144 pieces', 'unit_type' => 'count', 'is_base_unit' => false],
            ['code' => 'PACK', 'name' => 'Pack', 'description' => '24 pieces', 'unit_type' => 'count', 'is_base_unit' => false],
            ['code' => 'SET', 'name' => 'Set', 'description' => '6 pieces', 'unit_type' => 'count', 'is_base_unit' => false],
            ['code' => 'BOX', 'name' => 'Box', 'description' => '50 pieces', 'unit_type' => 'count', 'is_base_unit' => false],
            ['code' => 'CARTON', 'name' => 'Carton', 'description' => '100 pieces', 'unit_type' => 'count', 'is_base_unit' => false],
            ['code' => 'BUNDLE', 'name' => 'Bundle', 'description' => '10 pieces', 'unit_type' => 'count', 'is_base_unit' => false],

            // Weight Units
            ['code' => 'GRAM', 'name' => 'Gram', 'description' => 'Base weight unit', 'unit_type' => 'weight', 'is_base_unit' => true],
            ['code' => 'KG', 'name' => 'Kilogram', 'description' => '1000 grams', 'unit_type' => 'weight', 'is_base_unit' => false],
            ['code' => 'TON', 'name' => 'Ton', 'description' => '1000000 grams', 'unit_type' => 'weight', 'is_base_unit' => false],

            // Volume Units
            ['code' => 'LITER', 'name' => 'Liter', 'description' => 'Base volume unit', 'unit_type' => 'volume', 'is_base_unit' => true],
            ['code' => 'ML', 'name' => 'Milliliter', 'description' => '0.001 liter', 'unit_type' => 'volume', 'is_base_unit' => false],
            ['code' => 'GALLON', 'name' => 'Gallon', 'description' => '3.785 liters', 'unit_type' => 'volume', 'is_base_unit' => false],

            // Length Units
            ['code' => 'METER', 'name' => 'Meter', 'description' => 'Base length unit', 'unit_type' => 'length', 'is_base_unit' => true],
            ['code' => 'CM', 'name' => 'Centimeter', 'description' => '0.01 meter', 'unit_type' => 'length', 'is_base_unit' => false],
            ['code' => 'KM', 'name' => 'Kilometer', 'description' => '1000 meters', 'unit_type' => 'length', 'is_base_unit' => false],

            // Area Units
            ['code' => 'M2', 'name' => 'Square Meter', 'description' => 'Base area unit', 'unit_type' => 'area', 'is_base_unit' => true],
            ['code' => 'CM2', 'name' => 'Square Centimeter', 'description' => '0.0001 square meter', 'unit_type' => 'area', 'is_base_unit' => false],

            // Paper Units (Custom)
            ['code' => 'RIM', 'name' => 'Rim', 'description' => '500 sheets of paper', 'unit_type' => 'custom', 'is_base_unit' => false],
            ['code' => 'REAM', 'name' => 'Ream', 'description' => '500 sheets of paper', 'unit_type' => 'custom', 'is_base_unit' => false],
        ];

        foreach ($units as $unit) {
            DB::table('units_of_measure')->insert([
                ...$unit,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create unit conversions
        $conversions = [
            // Count conversions (to pieces)
            ['from_unit' => 'DOZ', 'to_unit' => 'PCS', 'factor' => 12.00],
            ['from_unit' => 'GROSS', 'to_unit' => 'PCS', 'factor' => 144.00],
            ['from_unit' => 'PACK', 'to_unit' => 'PCS', 'factor' => 24.00],
            ['from_unit' => 'SET', 'to_unit' => 'PCS', 'factor' => 6.00],
            ['from_unit' => 'BOX', 'to_unit' => 'PCS', 'factor' => 50.00],
            ['from_unit' => 'CARTON', 'to_unit' => 'PCS', 'factor' => 100.00],
            ['from_unit' => 'BUNDLE', 'to_unit' => 'PCS', 'factor' => 10.00],

            // Weight conversions (to grams)
            ['from_unit' => 'KG', 'to_unit' => 'GRAM', 'factor' => 1000.00],
            ['from_unit' => 'TON', 'to_unit' => 'GRAM', 'factor' => 1000000.00],

            // Volume conversions (to liters)
            ['from_unit' => 'ML', 'to_unit' => 'LITER', 'factor' => 0.001],
            ['from_unit' => 'GALLON', 'to_unit' => 'LITER', 'factor' => 3.785],

            // Length conversions (to meters)
            ['from_unit' => 'CM', 'to_unit' => 'METER', 'factor' => 0.01],
            ['from_unit' => 'KM', 'to_unit' => 'METER', 'factor' => 1000.00],

            // Area conversions (to square meters)
            ['from_unit' => 'CM2', 'to_unit' => 'M2', 'factor' => 0.0001],

            // Paper conversions (to pieces)
            ['from_unit' => 'RIM', 'to_unit' => 'PCS', 'factor' => 500.00],
            ['from_unit' => 'REAM', 'to_unit' => 'PCS', 'factor' => 500.00],
        ];

        foreach ($conversions as $conversion) {
            $fromUnitId = DB::table('units_of_measure')->where('code', $conversion['from_unit'])->value('id');
            $toUnitId = DB::table('units_of_measure')->where('code', $conversion['to_unit'])->value('id');

            if ($fromUnitId && $toUnitId) {
                DB::table('unit_conversions')->insert([
                    'from_unit_id' => $fromUnitId,
                    'to_unit_id' => $toUnitId,
                    'conversion_factor' => $conversion['factor'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
