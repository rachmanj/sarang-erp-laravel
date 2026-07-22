<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDepreciationEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDepreciationEntry>
 */
class AssetDepreciationEntryFactory extends Factory
{
    protected $model = AssetDepreciationEntry::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'period' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m'),
            'amount' => fake()->randomFloat(2, 50000, 2000000),
            'book' => 'financial',
            'journal_id' => null,
            'project_id' => null,
            'department_id' => null,
        ];
    }
}
