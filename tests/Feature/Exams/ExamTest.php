<?php

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->class10b = SchoolClass::factory()->named(10, 'Б')->create();
    $this->class10a = SchoolClass::factory()->named(10, 'А')->create();
    $this->student = User::factory()->inClass($this->class10b)->create();
});

function examPayload(array $overrides = []): array
{
    return [
        'subject' => 'Математика',
        'title' => 'Контролна по тригонометрия',
        'description' => 'Синус и косинус',
        'exam_date' => now()->addWeek()->toDateString(),
        'type' => 'test',
        ...$overrides,
    ];
}

test('guests are redirected to login', function () {
    $this->get('/exams')->assertRedirect(route('login'));
});

test('students only see exams for their own audience, upcoming first', function () {
    $past = Exam::factory()->forClass($this->class10b)->create(['exam_date' => now()->subDays(3)->toDateString()]);
    $later = Exam::factory()->forClass($this->class10b)->create(['exam_date' => now()->addDays(10)->toDateString()]);
    $soon = Exam::factory()->forGrade($this->class10b->grade)->create(['exam_date' => now()->addDay()->toDateString()]);
    Exam::factory()->forClass($this->class10a)->create();

    $this->actingAs($this->student)
        ->get('/exams')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Exams/Index')
            ->has('exams.data', 3)
            ->where('exams.data.0.id', $soon->id)
            ->where('exams.data.1.id', $later->id)
            ->where('exams.data.2.id', $past->id)
            ->where('can.create', true));
});

test('exam list filters by subject, date range and scope', function () {
    $admin = User::factory()->admin()->create();
    $math = Exam::factory()->forClass($this->class10b)->create(['subject' => 'Математика', 'exam_date' => '2030-03-10']);
    Exam::factory()->forClass($this->class10b)->create(['subject' => 'Физика', 'exam_date' => '2030-03-10']);
    Exam::factory()->forClass($this->class10a)->create(['subject' => 'Математика', 'exam_date' => '2030-05-10']);

    $this->actingAs($admin)->get('/exams?subject=Математика')
        ->assertInertia(fn (Assert $page) => $page->has('exams.data', 2));

    $this->actingAs($admin)->get('/exams?from=2030-03-01&to=2030-03-31')
        ->assertInertia(fn (Assert $page) => $page->has('exams.data', 2));

    $this->actingAs($admin)->get('/exams?subject=Математика&scope=class:'.$this->class10b->id)
        ->assertInertia(fn (Assert $page) => $page->has('exams.data', 1)->where('exams.data.0.id', $math->id));
});

test('teachers can browse every exam and create one for any scope', function () {
    $teacher = User::factory()->teacher()->create();
    Exam::factory()->forClass($this->class10a)->create();
    Exam::factory()->forClass($this->class10b)->create();

    $this->actingAs($teacher)->get('/exams')
        ->assertInertia(fn (Assert $page) => $page->has('exams.data', 2));

    $this->actingAs($teacher)
        ->post('/exams', examPayload(['scope' => 'class:'.$this->class10a->id]))
        ->assertRedirect();

    expect(Exam::where('created_by', $teacher->id)->exists())->toBeTrue();
});

test('a student from another class cannot open an exam', function () {
    $exam = Exam::factory()->forClass($this->class10a)->create();

    $this->actingAs($this->student)->get(route('exams.show', $exam))->assertForbidden();
});

test('students can create exams only for their own scope', function () {
    $this->actingAs($this->student)
        ->post('/exams', examPayload(['scope' => 'class:'.$this->class10b->id]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($this->student)
        ->post('/exams', examPayload(['scope' => 'class:'.$this->class10a->id]))
        ->assertSessionHasErrors('scope');

    expect(Exam::count())->toBe(1)
        ->and(Exam::first()->created_by)->toBe($this->student->id);
});

test('exam input is validated', function () {
    $this->actingAs($this->student)
        ->post('/exams', ['subject' => '', 'title' => '', 'exam_date' => 'tomorrow', 'type' => 'party', 'scope' => 'x'])
        ->assertSessionHasErrors(['subject', 'title', 'exam_date', 'type', 'scope']);
});

test('students without a class cannot create exams', function () {
    $this->actingAs(User::factory()->create())->get('/exams/create')->assertForbidden();
});

test('only the creator, moderators and admins can edit or delete an exam', function () {
    $exam = Exam::factory()->forClass($this->class10b)->create(['created_by' => $this->student->id]);
    $classmate = User::factory()->inClass($this->class10b)->create();
    $moderator = User::factory()->moderator()->inClass($this->class10a)->create();

    $this->actingAs($classmate)->get(route('exams.edit', $exam))->assertForbidden();
    $this->actingAs($classmate)->put(route('exams.update', $exam), examPayload(['scope' => 'class:'.$this->class10b->id]))->assertForbidden();
    $this->actingAs($classmate)->delete(route('exams.destroy', $exam))->assertForbidden();

    $this->actingAs($this->student)
        ->put(route('exams.update', $exam), examPayload(['title' => 'Ново', 'scope' => 'class:'.$this->class10b->id]))
        ->assertRedirect(route('exams.show', $exam));
    expect($exam->fresh()->title)->toBe('Ново');

    // A moderator from another class may keep the existing audience.
    $this->actingAs($moderator)
        ->put(route('exams.update', $exam), examPayload(['title' => 'Модерирано', 'scope' => 'class:'.$this->class10b->id]))
        ->assertSessionHasNoErrors();
    expect($exam->fresh()->title)->toBe('Модерирано');

    $this->actingAs(User::factory()->admin()->create())->delete(route('exams.destroy', $exam))->assertRedirect(route('exams.index'));
    expect(Exam::count())->toBe(0);
});
