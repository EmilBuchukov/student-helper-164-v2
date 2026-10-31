<?php

namespace Database\Factories;

use App\Models\AllowedStudent;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AllowedStudent>
 */
class AllowedStudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'class_id' => SchoolClass::factory(),
            'claimed_at' => null,
            'claimed_by' => null,
        ];
    }

    public function claimed(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'claimed_at' => now(),
            'claimed_by' => $user?->id ?? User::factory()->state([
                'email' => $attributes['email'],
                'class_id' => $attributes['class_id'],
            ]),
        ]);
    }
}
