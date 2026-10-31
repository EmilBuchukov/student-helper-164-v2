<?php

namespace Database\Factories;

use App\Enums\ExamType;
use App\Enums\ScopeType;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject' => fake()->randomElement(['Математика', 'Испански език', 'Български език и литература', 'Физика', 'История']),
            'title' => 'Контролна работа '.fake()->numberBetween(1, 5),
            'description' => fake()->optional()->sentence(),
            'exam_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'type' => fake()->randomElement(ExamType::cases()),
            'scope_type' => ScopeType::SchoolClass,
            'scope_id' => fn () => SchoolClass::factory()->create()->id,
            'created_by' => fn () => User::factory()->create()->id,
        ];
    }

    public function forClass(SchoolClass $class): static
    {
        return $this->state(fn () => ['scope_type' => ScopeType::SchoolClass, 'scope_id' => $class->id]);
    }

    public function forGrade(Grade $grade): static
    {
        return $this->state(fn () => ['scope_type' => ScopeType::Grade, 'scope_id' => $grade->id]);
    }

    public function forProfile(Profile $profile): static
    {
        return $this->state(fn () => ['scope_type' => ScopeType::Profile, 'scope_id' => $profile->id]);
    }
}
