<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'user_id' => User::factory(),
            'title' => 'Конспект',
            'file_path' => 'materials/test/'.Str::random(40).'.pdf',
            'original_name' => 'konspekt.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
