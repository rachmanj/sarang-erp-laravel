<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $acquisitionCost = fake()->randomFloat(2, 500000, 50000000);
        $salvageValue = round($acquisitionCost * 0.1, 2);
        $accumulated = round(($acquisitionCost - $salvageValue) * fake()->randomFloat(2, 0, 0.4), 2);

        return [
            'code' => 'AST-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'serial_number' => fake()->optional()->bothify('SN-####??'),
            'category_id' => AssetCategory::factory(),
            'acquisition_cost' => $acquisitionCost,
            'salvage_value' => $salvageValue,
            'accumulated_depreciation' => $accumulated,
            'current_book_value' => $acquisitionCost - $accumulated,
            'method' => 'straight_line',
            'life_months' => fake()->randomElement([36, 48, 60, 120]),
            'placed_in_service_date' => fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'status' => 'active',
            'disposal_date' => null,
            'project_id' => null,
            'department_id' => null,
            'business_partner_id' => null,
            'purchase_invoice_id' => null,
        ];
    }

    public function retired(): static
    {
        return $this->state(fn () => ['status' => 'retired']);
    }

    public function disposed(): static
    {
        return $this->state(fn () => [
            'status' => 'disposed',
            'disposal_date' => now()->subDays(10)->format('Y-m-d'),
        ]);
    }
}
