<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<AssetCategory>
 */
class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    public function definition(): array
    {
        $suffix = fake()->unique()->numerify('###');

        $assetAccountId = DB::table('accounts')->insertGetId([
            'code' => '9.9.1.'.$suffix,
            'name' => 'Test Asset Account '.$suffix,
            'type' => 'asset',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accumAccountId = DB::table('accounts')->insertGetId([
            'code' => '9.9.2.'.$suffix,
            'name' => 'Test Accum Dep '.$suffix,
            'type' => 'asset',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expenseAccountId = DB::table('accounts')->insertGetId([
            'code' => '9.9.3.'.$suffix,
            'name' => 'Test Dep Expense '.$suffix,
            'type' => 'expense',
            'is_postable' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'code' => strtoupper(fake()->unique()->lexify('CAT???')),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'life_months_default' => 36,
            'method_default' => 'straight_line',
            'salvage_value_policy' => 10,
            'non_depreciable' => false,
            'asset_account_id' => $assetAccountId,
            'accumulated_depreciation_account_id' => $accumAccountId,
            'depreciation_expense_account_id' => $expenseAccountId,
            'is_active' => true,
        ];
    }

    public function nonDepreciable(): static
    {
        return $this->state(fn () => [
            'non_depreciable' => true,
            'life_months_default' => null,
        ]);
    }
}
