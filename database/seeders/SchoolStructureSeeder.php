<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolStructureSeeder extends Seeder
{
    /**
     * Grades 8-12, the school's profiles and demo classes.
     */
    public function run(): void
    {
        foreach (range(Grade::MIN, Grade::MAX) as $number) {
            Grade::firstOrCreate(['number' => $number]);
        }

        $profiles = collect([
            'Химия',
            'Биология',
            'Математика',
            'Предприемачество',
            'Физика',
            'География',
            'История',
        ])->mapWithKeys(fn (string $name) => [$name => Profile::firstOrCreate(['name' => $name])]);

        $classes = [
            [8, 'А', 'Химия'],
            [8, 'Б', 'Биология'],
            [9, 'А', 'Математика'],
            [9, 'Б', 'Предприемачество'],
            [10, 'А', 'Физика'],
            [10, 'Б', 'География'],
            [11, 'А', 'История'],
            [11, 'Б', 'Химия'],
            [12, 'А', 'Биология'],
            [12, 'Б', 'Математика'],
        ];

        foreach ($classes as [$grade, $letter, $profile]) {
            SchoolClass::updateOrCreate(
                ['grade_id' => Grade::where('number', $grade)->value('id'), 'letter' => $letter],
                ['profile_id' => $profiles[$profile]->id],
            );
        }
    }
}
