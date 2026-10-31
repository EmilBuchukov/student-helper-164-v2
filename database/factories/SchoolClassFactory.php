<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    private const LETTERS = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'К'];

    public function definition(): array
    {
        return [
            'grade_id' => fn () => Grade::query()->inRandomOrder()->value('id')
                ?? Grade::factory()->create(['number' => 10])->id,
            'letter' => fake()->unique()->randomElement(self::LETTERS),
            'profile_id' => null,
        ];
    }

    /**
     * A class in the given grade/letter, creating the grade if needed.
     */
    public function named(int $grade, string $letter): static
    {
        return $this->state(fn () => [
            'grade_id' => Grade::firstOrCreate(['number' => $grade])->id,
            'letter' => $letter,
        ]);
    }
}
