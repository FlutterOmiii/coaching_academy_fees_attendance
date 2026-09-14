<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        return [
            'name' => 'Morning '.fake()->unique()->numberBetween(1, 9999),
            'code' => 'B'.fake()->unique()->numberBetween(1000, 9999),
            'coach_id' => null,
            'age_group' => 'under_14',
            'skill_level' => 'beginner',
            'capacity' => 20,
            'start_time' => '06:00:00',
            'end_time' => '08:00:00',
            'training_days' => [1, 3, 5],
            'ground' => 'Main Ground',
            'monthly_fee' => 1500,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ];
    }
}
