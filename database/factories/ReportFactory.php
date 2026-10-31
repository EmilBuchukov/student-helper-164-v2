<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reporter_id' => fn () => User::factory()->create()->id,
            'reportable_type' => 'question',
            'reportable_id' => fn () => Question::factory()->create()->id,
            'reason' => 'Обидно съдържание',
            'status' => ReportStatus::Open,
        ];
    }
}
