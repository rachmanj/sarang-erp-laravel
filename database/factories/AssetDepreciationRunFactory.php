<?php

namespace Database\Factories;

use App\Models\AssetDepreciationRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDepreciationRun>
 */
class AssetDepreciationRunFactory extends Factory
{
    protected $model = AssetDepreciationRun::class;

    public function definition(): array
    {
        return [
            'period' => fake()->unique()->dateTimeBetween('-12 months', 'now')->format('Y-m'),
            'status' => 'draft',
            'total_depreciation' => fake()->randomFloat(2, 100000, 10000000),
            'asset_count' => fake()->numberBetween(1, 20),
            'created_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function posted(): static
    {
        return $this->state(fn () => [
            'status' => 'posted',
            'posted_by' => User::factory(),
            'posted_at' => now(),
        ]);
    }
}
