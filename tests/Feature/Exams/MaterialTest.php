<?php

use App\Models\Exam;
use App\Models\Material;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->class10b = SchoolClass::factory()->named(10, 'Б')->create();
    $this->class10a = SchoolClass::factory()->named(10, 'А')->create();
    $this->student = User::factory()->inClass($this->class10b)->create();
    $this->exam = Exam::factory()->forClass($this->class10b)->create();
});

function uploadMaterial($test, User $user, Exam $exam, UploadedFile $file, string $title = 'Конспект')
{
    return $test->actingAs($user)->post(route('exams.materials.store', $exam), ['title' => $title, 'file' => $file]);
}

/**
 * A real (non-fake) upload, so the MIME type is detected from the content like in production.
 */
function realUpload(string $name, string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upl');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

function pdfFile(string $name = 'konspekt.pdf'): UploadedFile
{
    return realUpload($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
}

test('a student can upload a material, stored under a random name on the configured disk', function () {
    uploadMaterial($this, $this->student, $this->exam, pdfFile('Моят конспект.pdf'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $material = Material::sole();

    expect($material->original_name)->toBe('Моят конспект.pdf')
        ->and($material->mime_type)->toBe('application/pdf')
        ->and($material->file_path)->not->toContain('конспект')
        ->and($material->file_path)->toStartWith('materials/'.$this->exam->id.'/');

    Storage::disk('local')->assertExists($material->file_path);
});

test('plain text and images are accepted', function () {
    uploadMaterial($this, $this->student, $this->exam, realUpload('notes.txt', "Тригонометрия: sin² + cos² = 1\n"))->assertSessionHasNoErrors();
    uploadMaterial($this, $this->student, $this->exam, realUpload('board.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')))->assertSessionHasNoErrors();

    expect(Material::count())->toBe(2);
});

test('the materials disk is configurable', function () {
    Storage::fake('s3');
    config(['filesystems.default' => 's3']);

    uploadMaterial($this, $this->student, $this->exam, pdfFile())->assertSessionHasNoErrors();

    Storage::disk('s3')->assertExists(Material::sole()->file_path);
});

test('uploads with a disallowed type are rejected', function (UploadedFile $file) {
    uploadMaterial($this, $this->student, $this->exam, $file)->assertSessionHasErrors('file');

    expect(Material::count())->toBe(0);
})->with([
    'executable' => fn () => realUpload('virus.exe', "MZ\x90\x00binary"),
    'php script' => fn () => realUpload('shell.php', '<?php echo 1;'),
    'html renamed to pdf' => fn () => realUpload('fake.pdf', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>'),
    'pdf renamed to exe' => fn () => realUpload('doc.exe', "%PDF-1.4\n%%EOF\n"),
    'png renamed to txt' => fn () => realUpload('image.txt', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')),
]);

test('uploads larger than 10 MB are rejected', function () {
    uploadMaterial($this, $this->student, $this->exam, UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'))
        ->assertSessionHasErrors('file');

    expect(Material::count())->toBe(0);
});

test('teachers cannot upload materials but can download them', function () {
    $teacher = User::factory()->teacher()->create();
    $material = Material::factory()->for($this->exam)->create();
    Storage::disk('local')->put($material->file_path, 'content');

    uploadMaterial($this, $teacher, $this->exam, pdfFile())->assertForbidden();
    $this->actingAs($teacher)->get(route('materials.download', $material))->assertOk();
});

test('a student from another class cannot upload, see or download materials', function () {
    $outsider = User::factory()->inClass($this->class10a)->create();
    $material = Material::factory()->for($this->exam)->create();
    Storage::disk('local')->put($material->file_path, 'secret notes');

    uploadMaterial($this, $outsider, $this->exam, pdfFile())->assertForbidden();
    $this->actingAs($outsider)->get(route('exams.show', $this->exam))->assertForbidden();
    $this->actingAs($outsider)->get(route('materials.download', $material))->assertForbidden();
});

test('download sends the original file name', function () {
    $material = Material::factory()->for($this->exam)->create(['original_name' => 'konspekt-10b.pdf']);
    Storage::disk('local')->put($material->file_path, 'notes');

    $response = $this->actingAs($this->student)->get(route('materials.download', $material));

    $response->assertOk()->assertDownload('konspekt-10b.pdf');
    expect($response->streamedContent())->toBe('notes');
});

test('users can delete their own materials, others cannot; moderators can', function () {
    $classmate = User::factory()->inClass($this->class10b)->create();
    $own = Material::factory()->for($this->exam)->for($this->student)->create();
    $other = Material::factory()->for($this->exam)->for($classmate)->create();
    Storage::disk('local')->put($own->file_path, 'x');

    $this->actingAs($this->student)->delete(route('materials.destroy', $other))->assertForbidden();
    $this->actingAs($this->student)->delete(route('materials.destroy', $own))->assertRedirect();

    expect(Material::find($own->id))->toBeNull();
    Storage::disk('local')->assertMissing($own->file_path);

    $this->actingAs(User::factory()->moderator()->create())->delete(route('materials.destroy', $other))->assertRedirect();
    expect(Material::count())->toBe(0);
});

test('deleting an exam removes its material files', function () {
    $material = Material::factory()->for($this->exam)->create();
    Storage::disk('local')->put($material->file_path, 'x');

    $this->actingAs(User::factory()->admin()->create())->delete(route('exams.destroy', $this->exam));

    Storage::disk('local')->assertMissing($material->file_path);
    expect(Material::count())->toBe(0);
});

test('exam page lists materials with permissions', function () {
    Material::factory()->for($this->exam)->for($this->student)->create();

    $this->actingAs($this->student)
        ->get(route('exams.show', $this->exam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Exams/Show')
            ->has('materials', 1)
            ->where('materials.0.can_delete', true)
            ->missing('materials.0.file_path')
            ->where('exam.can.upload', true));
});
