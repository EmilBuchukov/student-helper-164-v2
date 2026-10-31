<?php

use App\Enums\ReportStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\SchoolClass;
use App\Models\User;

beforeEach(function () {
    $this->class = SchoolClass::factory()->named(10, 'Б')->create();
    $this->student = User::factory()->inClass($this->class)->create();
    $this->author = User::factory()->inClass($this->class)->create();
    $this->question = Question::factory()->by($this->author)->create();
});

function submitReport($test, User $user, string $type, int $id, string $reason = 'Обидно съдържание')
{
    return $test->actingAs($user)->post(route('reports.store'), compact('type', 'id', 'reason'));
}

test('students can report questions and answers with a reason', function () {
    $answer = Answer::factory()->by($this->author)->create(['question_id' => $this->question->id]);

    submitReport($this, $this->student, 'question', $this->question->id)->assertSessionHasNoErrors()->assertRedirect();
    submitReport($this, $this->student, 'answer', $answer->id)->assertSessionHasNoErrors();

    expect(Report::count())->toBe(2)
        ->and(Report::first()->reporter_id)->toBe($this->student->id)
        ->and(Report::first()->status)->toBe(ReportStatus::Open)
        ->and($answer->reports()->count())->toBe(1);
});

test('a reason is required', function () {
    submitReport($this, $this->student, 'question', $this->question->id, '')->assertSessionHasErrors('reason');

    expect(Report::count())->toBe(0);
});

test('only one open report per user and item', function () {
    submitReport($this, $this->student, 'question', $this->question->id);
    submitReport($this, $this->student, 'question', $this->question->id, 'Отново')->assertSessionHasErrors('reason');

    expect(Report::count())->toBe(1);

    // After it is handled, a new report is allowed; others can always report.
    Report::first()->update(['status' => ReportStatus::Dismissed]);
    submitReport($this, $this->student, 'question', $this->question->id)->assertSessionHasNoErrors();
    submitReport($this, User::factory()->inClass($this->class)->create(), 'question', $this->question->id)->assertSessionHasNoErrors();

    expect(Report::count())->toBe(3);
});

test('content outside the user scope, own content and unknown types cannot be reported', function () {
    $foreign = Question::factory()->forClass(SchoolClass::factory()->named(12, 'В')->create())->create();

    submitReport($this, $this->student, 'question', $foreign->id)->assertForbidden();
    submitReport($this, $this->author, 'question', $this->question->id)->assertForbidden();
    submitReport($this, $this->student, 'user', $this->author->id)->assertSessionHasErrors('type');
    submitReport($this, $this->student, 'question', 999999)->assertNotFound();

    expect(Report::count())->toBe(0);
});
