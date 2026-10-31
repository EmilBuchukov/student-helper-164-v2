<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+1 month');

        return [
            'club_id' => fn () => Club::factory()->create()->id,
            'title' => 'Среща '.fake()->words(2, true),
            'description' => fake()->sentence(),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+2 hours'),
            'location' => 'Зала '.fake()->numberBetween(100, 400),
            'created_by' => null,
        ];
    }

    public function schoolWide(): static
    {
        return $this->state(fn () => ['club_id' => null]);
    }

    public function past(): static
    {
        return $this->state(fn () => ['starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(2)]);
    }
}
