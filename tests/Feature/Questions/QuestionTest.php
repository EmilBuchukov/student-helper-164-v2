<?php

use App\Models\Answer;
use App\Models\Question;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->class10b = SchoolClass::factory()->named(10, 'Б')->create();
    $this->class11a = SchoolClass::factory()->named(11, 'А')->create();
    $this->student = User::factory()->inClass($this->class10b)->create();
    $this->other = User::factory()->inClass($this->class11a)->create();
    $this->teacher = User::factory()->teacher()->create();
});

test('teachers get 403 on every Q&A endpoint', function () {
    $question = Question::factory()->create();
    $answer = Answer::factory()->create(['question_id' => $question->id]);

    $requests = [
        ['get', route('questions.index')],
        ['get', route('questions.create')],
        ['post', route('questions.store')],
        ['get', route('questions.show', $question)],
        ['get', route('questions.edit', $question)],
        ['patch', route('questions.update', $question)],
        ['delete', route('questions.destroy', $question)],
        ['post', route('questions.answers.store', $question)],
        ['post', route('questions.vote', $question)],
        ['patch', route('answers.update', $answer)],
        ['delete', route('answers.destroy', $answer)],
        ['post', route('answers.accept', $answer)],
        ['post', route('answers.vote', $answer)],
        ['post', route('reports.store'), ['type' => 'question', 'id' => $question->id, 'reason' => 'Спам и реклама']],
    ];

    foreach ($requests as $request) {
        [$method, $url] = $request;
        $this->actingAs($this->teacher)->{$method}($url, $request[2] ?? [])->assertForbidden();
    }

    expect(Question::query()->visibleTo($this->teacher)->count())->toBe(0)
        ->and($question->isVisibleTo($this->teacher))->toBeFalse();
});

test('students see school-wide questions and those for their own scopes only', function () {
    $schoolWide = Question::factory()->create();
    $ownClass = Question::factory()->forClass($this->class10b)->create();
    $ownGrade = Question::factory()->forGrade($this->class10b->grade)->create();
    $foreign = Question::factory()->forClass($this->class11a)->create();

    $this->actingAs($this->student)->get('/questions')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Questions/Index')->has('questions.data', 3));

    $this->actingAs($this->student)->get(route('questions.show', $foreign))->assertForbidden();
    $this->actingAs($this->student)->get(route('questions.show', $ownGrade))->assertOk();
    $this->actingAs($this->student)->post(route('questions.answers.store', $foreign), ['body' => 'Отговор'])->assertForbidden();

    expect(Question::query()->visibleTo($this->student)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$schoolWide->id, $ownClass->id, $ownGrade->id])->sort()->values()->all());
});

test('students can ask school-wide or own-scope questions but not for other classes', function () {
    $this->actingAs($this->student)->post('/questions', [
        'title' => 'Как се намира производна?', 'body' => 'Не разбирам правилото.', 'subject' => 'Математика', 'scope' => '',
    ])->assertRedirect();

    $this->actingAs($this->student)->post('/questions', [
        'title' => 'Въпрос за 10Б', 'body' => 'Текст', 'scope' => 'class:'.$this->class10b->id,
    ])->assertRedirect();

    $this->actingAs($this->student)->post('/questions', [
        'title' => 'Чужд клас', 'body' => 'Текст', 'scope' => 'class:'.$this->class11a->id,
    ])->assertSessionHasErrors('scope');

    expect(Question::count())->toBe(2)
        ->and(Question::whereNull('scope_type')->first()->user_id)->toBe($this->student->id);
});

test('search, subject filter and sorting work', function () {
    $popular = Question::factory()->create(['title' => 'Тригонометрия на 10 клас', 'subject' => 'Математика', 'created_at' => now()->subDay()]);
    $resolved = Question::factory()->create(['title' => 'Испански глаголи', 'subject' => 'Испански език']);
    $answer = Answer::factory()->create(['question_id' => $resolved->id]);
    $resolved->update(['accepted_answer_id' => $answer->id]);
    $popular->votes()->create(['user_id' => $this->other->id]);

    $this->actingAs($this->student)->get('/questions?q=тригонометрия')
        ->assertInertia(fn (Assert $page) => $page->has('questions.data', 1)->where('questions.data.0.id', $popular->id));

    $this->actingAs($this->student)->get('/questions?q=100%25')
        ->assertInertia(fn (Assert $page) => $page->has('questions.data', 0));

    $this->actingAs($this->student)->get('/questions?subject=Испански език')
        ->assertInertia(fn (Assert $page) => $page->has('questions.data', 1)->where('questions.data.0.id', $resolved->id));

    $this->actingAs($this->student)->get('/questions?sort=votes')
        ->assertInertia(fn (Assert $page) => $page->where('questions.data.0.id', $popular->id));

    $this->actingAs($this->student)->get('/questions?sort=unresolved')
        ->assertInertia(fn (Assert $page) => $page->has('questions.data', 1)->where('questions.data.0.id', $popular->id));
});

test('votes toggle and own content cannot be voted', function () {
    $question = Question::factory()->by($this->other)->create();
    $own = Question::factory()->by($this->student)->create();

    $this->actingAs($this->student)->post(route('questions.vote', $question))->assertRedirect();
    expect($question->votes()->count())->toBe(1);

    $this->actingAs($this->student)->post(route('questions.vote', $question));
    expect($question->votes()->count())->toBe(0);

    $this->actingAs($this->student)->post(route('questions.vote', $own))->assertForbidden();

    $answer = Answer::factory()->by($this->other)->create(['question_id' => $own->id]);
    $this->actingAs($this->student)->post(route('answers.vote', $answer));
    expect($answer->votes()->count())->toBe(1);
});

test('only the question author can accept, and only one answer at a time', function () {
    $question = Question::factory()->by($this->student)->create();
    [$first, $second] = Answer::factory()->count(2)->by($this->other)->create(['question_id' => $question->id]);

    $this->actingAs($this->other)->post(route('answers.accept', $first))->assertForbidden();

    $this->actingAs($this->student)->post(route('answers.accept', $first))->assertRedirect();
    expect($question->fresh()->accepted_answer_id)->toBe($first->id);

    $this->actingAs($this->student)->post(route('answers.accept', $second));
    expect($question->fresh()->accepted_answer_id)->toBe($second->id);

    $this->actingAs($this->student)->post(route('answers.accept', $second));
    expect($question->fresh()->accepted_answer_id)->toBeNull();

    $this->actingAs($this->student)->post(route('answers.accept', $first));
    $first->delete();
    expect($question->fresh()->accepted_answer_id)->toBeNull();
});

test('accepted answer is listed first on the question page', function () {
    $question = Question::factory()->by($this->student)->create();
    $older = Answer::factory()->create(['question_id' => $question->id, 'created_at' => now()->subHour()]);
    $accepted = Answer::factory()->create(['question_id' => $question->id]);
    $question->update(['accepted_answer_id' => $accepted->id]);

    $this->actingAs($this->student)->get(route('questions.show', $question))
        ->assertInertia(fn (Assert $page) => $page
            ->where('answers.0.id', $accepted->id)
            ->where('answers.0.accepted', true)
            ->where('answers.1.id', $older->id)
            ->where('question.resolved', true));
});

test('authors edit and delete their own content; moderators and admins delete anything', function () {
    $question = Question::factory()->by($this->student)->create();
    $answer = Answer::factory()->by($this->student)->create(['question_id' => $question->id]);
    $moderator = User::factory()->moderator()->inClass($this->class11a)->create();

    $this->actingAs($this->other)->patch(route('questions.update', $question), ['title' => 'Промяна', 'body' => 'x'])->assertForbidden();
    $this->actingAs($this->other)->patch(route('answers.update', $answer), ['body' => 'x'])->assertForbidden();
    $this->actingAs($this->other)->delete(route('answers.destroy', $answer))->assertForbidden();
    $this->actingAs($moderator)->patch(route('questions.update', $question), ['title' => 'Промяна', 'body' => 'x'])->assertForbidden();

    $this->actingAs($this->student)->patch(route('questions.update', $question), ['title' => 'Ново заглавие', 'body' => 'Нов текст'])->assertRedirect();
    $this->actingAs($this->student)->patch(route('answers.update', $answer), ['body' => 'Обновен отговор'])->assertRedirect();
    expect($question->fresh()->title)->toBe('Ново заглавие')->and($answer->fresh()->body)->toBe('Обновен отговор');

    $this->actingAs($moderator)->delete(route('answers.destroy', $answer))->assertRedirect();
    $this->actingAs(User::factory()->admin()->create())->delete(route('questions.destroy', $question))->assertRedirect();

    expect(Answer::count())->toBe(0)->and(Answer::withTrashed()->count())->toBe(1)
        ->and(Question::count())->toBe(0)->and(Question::withTrashed()->count())->toBe(1);
});

test('question and answer creation is rate limited', function () {
    foreach (range(1, 5) as $i) {
        $this->actingAs($this->student)->post('/questions', ['title' => "Въпрос номер {$i}", 'body' => 'Текст'])->assertRedirect();
    }

    $this->actingAs($this->student)->post('/questions', ['title' => 'Въпрос номер 6', 'body' => 'Текст'])->assertStatus(429);

    $question = Question::first();
    foreach (range(1, 10) as $i) {
        $this->actingAs($this->other)->post(route('questions.answers.store', $question), ['body' => "Отговор {$i}"]);
    }
    $this->actingAs($this->other)->post(route('questions.answers.store', $question), ['body' => 'Още'])->assertStatus(429);
});
