<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetDisposal>
 */
class AssetDisposalFactory extends Factory
{
    protected $model = AssetDisposal::class;

    public function definition(): array
    {
        $bookValue = fake()->randomFloat(2, 100000, 5000000);
        $proceeds = fake()->randomFloat(2, 0, $bookValue * 1.2);
        $gainLoss = $proceeds - $bookValue;

        return [
            'disposal_no' => 'DSP-'.fake()->unique()->numerify('####'),
            'asset_id' => Asset::factory()->disposed(),
            'company_entity_id' => 1,
            'disposal_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'disposal_type' => fake()->randomElement(['sale', 'scrap', 'donation', 'trade_in', 'other']),
            'disposal_proceeds' => $proceeds,
            'book_value_at_disposal' => $bookValue,
            'gain_loss_amount' => abs($gainLoss),
            'gain_loss_type' => $gainLoss > 0 ? 'gain' : ($gainLoss < 0 ? 'loss' : 'neutral'),
            'disposal_reason' => fake()->optional()->sentence(),
            'disposal_method' => fake()->optional()->word(),
            'disposal_reference' => fake()->optional()->bothify('REF-####'),
            'created_by' => User::factory(),
            'status' => 'draft',
            'notes' => null,
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
