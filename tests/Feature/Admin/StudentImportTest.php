<?php

use App\Models\AllowedStudent;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\SchoolStructureSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->class10b = SchoolClass::factory()->named(10, 'Б')->create();
    $this->class8a = SchoolClass::factory()->named(8, 'А')->create();
});

function csvUpload(string $content, string $name = 'students.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

test('admin can import a CSV and gets a report with created, skipped and invalid rows', function () {
    AllowedStudent::factory()->create(['email' => 'existing@example.com', 'class_id' => $this->class8a->id]);
    User::factory()->teacher()->create(['email' => 'teacher@example.com']);

    $csv = implode("\n", [
        'email,grade,letter',
        'new1@example.com,10,Б',            // 2 created
        'NEW2@example.com , 8 , а',          // 3 created (normalized)
        'existing@example.com,8,А',          // 4 skipped: already whitelisted
        'new1@example.com,10,Б',             // 5 skipped: duplicate in file
        'teacher@example.com,10,Б',          // 6 skipped: already registered
        'not-an-email,10,Б',                 // 7 invalid email
        'x@example.com,13,А',                // 8 invalid grade
        'y@example.com,10,Ж',                // 9 unknown class
        'z@example.com,10',                  // 10 wrong column count
        'w@example.com,10,B',                // 11 latin letter
        '',
    ]);

    $response = $this->actingAs($this->admin)
        ->post('/admin/students/import', ['file' => csvUpload($csv)]);

    $response->assertRedirect(route('admin.students.import'))
        ->assertSessionHasNoErrors();

    $report = session('import_report');

    expect(collect($report['created'])->pluck('email')->all())->toBe(['new1@example.com', 'new2@example.com'])
        ->and(collect($report['skipped'])->pluck('row')->all())->toBe([4, 5, 6])
        ->and(collect($report['invalid'])->pluck('row')->all())->toBe([7, 8, 9, 10, 11])
        ->and($report['invalid'][2]['reason'])->toBe(__('ui.import.unknown_class', ['class' => '10Ж']));

    expect(AllowedStudent::where('email', 'new2@example.com')->first()->class_id)->toBe($this->class8a->id)
        ->and(AllowedStudent::count())->toBe(3);

    $this->get(route('admin.students.import'))
        ->assertInertia(fn ($page) => $page->has('report.created', 2)->has('report.invalid', 5));
});

test('re-importing the same file creates nothing new', function () {
    $csv = "email,grade,letter\na@example.com,10,Б\nb@example.com,8,А\n";

    $this->actingAs($this->admin)->post('/admin/students/import', ['file' => csvUpload($csv)]);
    $this->actingAs($this->admin)->post('/admin/students/import', ['file' => csvUpload($csv)]);

    $report = session('import_report');
    expect($report['created'])->toBeEmpty()
        ->and($report['skipped'])->toHaveCount(2)
        ->and(AllowedStudent::count())->toBe(2);
});

test('semicolon separated files with a BOM are accepted', function () {
    $csv = "\xEF\xBB\xBFemail;grade;letter\r\na@example.com;10;Б\r\n";

    $this->actingAs($this->admin)->post('/admin/students/import', ['file' => csvUpload($csv)]);

    expect(AllowedStudent::where('email', 'a@example.com')->exists())->toBeTrue();
});

test('a file with a wrong header is rejected', function () {
    $this->actingAs($this->admin)
        ->from('/admin/students/import')
        ->post('/admin/students/import', ['file' => csvUpload("mail,class\na@example.com,10Б\n")])
        ->assertSessionHasErrors(['file' => __('ui.import.bad_header')]);

    expect(AllowedStudent::count())->toBe(0);
});

test('the file is required and must be a csv', function () {
    $this->actingAs($this->admin)
        ->post('/admin/students/import', [])
        ->assertSessionHasErrors('file');

    $this->actingAs($this->admin)
        ->post('/admin/students/import', ['file' => UploadedFile::fake()->image('photo.png')])
        ->assertSessionHasErrors('file');
});

test('the sample CSV in docs imports cleanly against the seeded classes', function () {
    $this->seed(SchoolStructureSeeder::class);

    $this->actingAs($this->admin)->post('/admin/students/import', [
        'file' => csvUpload(file_get_contents(base_path('docs/sample-students.csv'))),
    ]);

    $report = session('import_report');
    expect($report['created'])->toHaveCount(6)
        ->and($report['skipped'])->toHaveCount(1)
        ->and($report['invalid'])->toHaveCount(3);
});
