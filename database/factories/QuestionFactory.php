<?php

namespace Database\Factories;

use App\Enums\ScopeType;
use App\Models\Grade;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => fn () => User::factory()->create()->id,
            'title' => 'Как се решава '.fake()->words(3, true).'?',
            'body' => fake()->paragraph(),
            'subject' => fake()->randomElement(['Математика', 'Испански език', 'Физика', null]),
            'scope_type' => null,
            'scope_id' => null,
        ];
    }

    public function by(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forClass(SchoolClass $class): static
    {
        return $this->state(fn () => ['scope_type' => ScopeType::SchoolClass, 'scope_id' => $class->id]);
    }

    public function forGrade(Grade $grade): static
    {
        return $this->state(fn () => ['scope_type' => ScopeType::Grade, 'scope_id' => $grade->id]);
    }
}
