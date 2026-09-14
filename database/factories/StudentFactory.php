<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'student_code' => 'STU'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'gender' => 'male',
            'guardian_name' => fake()->name(),
            'guardian_phone' => '98'.fake()->numerify('########'),
            'playing_role' => 'batter',
            'admission_date' => now()->toDateString(),
            'admission_status' => 'approved',
            'status' => 'active',
        ];
    }
}
