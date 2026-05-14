<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\QuranPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Minimal QuranPackage factory created for Phase B test suite.
 * The v2 SubscriptionPricing service exercises this factory via
 * PricingResolver, which reads `monthly_price` / `quarterly_price` /
 * `yearly_price` (and their `sale_*` siblings).
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranPackage>
 */
class QuranPackageFactory extends Factory
{
    protected $model = QuranPackage::class;

    public function definition(): array
    {
        return [
            'academy_id' => Academy::factory(),
            'name' => fake()->randomElement(['Basic Memorization', 'Standard', 'Premium']),
            'description' => fake()->sentence(),
            'sessions_per_month' => 8,
            'session_duration_minutes' => 30,
            'monthly_price' => 200,
            'sale_monthly_price' => null,
            'quarterly_price' => 540,
            'sale_quarterly_price' => null,
            'yearly_price' => 2000,
            'sale_yearly_price' => null,
            'currency' => 'SAR',
            'features' => ['quran', 'memorization'],
            'is_active' => true,
            'sort_order' => 1,
        ];
    }

    /**
     * Package with the given session duration (used by INV-E1 tests).
     */
    public function duration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'session_duration_minutes' => $minutes,
        ]);
    }
}
