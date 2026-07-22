<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetMovement>
 */
class AssetMovementFactory extends Factory
{
    protected $model = AssetMovement::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'movement_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'movement_type' => fake()->randomElement(['transfer', 'relocation', 'custodian_change', 'maintenance', 'other']),
            'from_location' => fake()->city(),
            'to_location' => fake()->city(),
            'from_custodian' => fake()->name(),
            'to_custodian' => fake()->name(),
            'movement_reason' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'reference_number' => 'MOV-'.fake()->unique()->numerify('####'),
            'created_by' => User::factory(),
            'status' => 'draft',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }
}
