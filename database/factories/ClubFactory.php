<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Клуб '.fake()->unique()->word(),
            'description' => fake()->paragraph(),
            'leader_id' => null,
            'is_active' => true,
        ];
    }

    public function ledBy(User $user): static
    {
        return $this->state(fn () => ['leader_id' => $user->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
