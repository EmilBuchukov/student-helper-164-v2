<?php

namespace Database\Seeders;

use App\Enums\ExamType;
use App\Enums\ScopeType;
use App\Models\Exam;
use App\Models\User;
use App\Services\MaterialStorage;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/**
 * One demo exam for the demo student's class, with one sample material.
 */
class ExamSeeder extends Seeder
{
    public function run(MaterialStorage $storage): void
    {
        $author = User::where('email', 'student@example.com')->first();

        if (Exam::exists() || ! $author?->class_id) {
            return;
        }

        $exam = Exam::create([
            'subject' => 'Математика',
            'title' => 'Контролна работа по тригонометрия',
            'description' => 'Синусова и косинусова теорема, формули за събиране.',
            'exam_date' => now()->addDays(7)->toDateString(),
            'type' => ExamType::Test,
            'scope_type' => ScopeType::SchoolClass,
            'scope_id' => $author->class_id,
            'created_by' => $author->id,
        ]);

        $upload = new UploadedFile(__DIR__.'/files/sample.pdf', 'primerni-zadachi.pdf', null, null, true);

        $storage->store($exam, $author, $upload, 'Примерни задачи');
    }
}
