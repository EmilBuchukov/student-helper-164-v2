<?php

use App\Enums\ScopeType;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;
use App\Models\User;
use App\Rules\AudienceScopeRule;
use App\Support\Audience;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->informatics = Profile::factory()->create(['name' => 'Информатика']);
    $this->biology = Profile::factory()->create(['name' => 'Биология']);
    $this->class10b = SchoolClass::factory()->named(10, 'Б')->create(['profile_id' => $this->informatics->id]);
    $this->class10a = SchoolClass::factory()->named(10, 'А')->create(['profile_id' => $this->biology->id]);
    $this->class11a = SchoolClass::factory()->named(11, 'А')->create(['profile_id' => $this->informatics->id]);
    $this->grade10 = Grade::where('number', 10)->first();
    $this->grade11 = Grade::where('number', 11)->first();
    $this->student = User::factory()->inClass($this->class10b)->create();
});

test('scopesFor returns the class, grade and profile of the user', function () {
    expect(Audience::scopesFor($this->student))->toBe([
        'class' => $this->class10b->id,
        'grade' => $this->grade10->id,
        'profile' => $this->informatics->id,
    ])->and(Audience::scopesFor(User::factory()->teacher()->create()))->toBe([]);
});

test('userBelongsTo checks membership in each scope type', function () {
    expect(Audience::userBelongsTo($this->student, ScopeType::SchoolClass, $this->class10b->id))->toBeTrue()
        ->and(Audience::userBelongsTo($this->student, 'grade', $this->grade10->id))->toBeTrue()
        ->and(Audience::userBelongsTo($this->student, ScopeType::Profile, $this->informatics->id))->toBeTrue()
        ->and(Audience::userBelongsTo($this->student, ScopeType::SchoolClass, $this->class10a->id))->toBeFalse()
        ->and(Audience::userBelongsTo($this->student, ScopeType::Grade, $this->grade11->id))->toBeFalse()
        ->and(Audience::userBelongsTo($this->student, ScopeType::Profile, $this->biology->id))->toBeFalse()
        ->and(Audience::userBelongsTo($this->student, 'nonsense', 1))->toBeFalse();
});

test('visibleTo only returns items targeted at the user class, grade or profile', function () {
    $own = [
        Exam::factory()->forClass($this->class10b)->create(),
        Exam::factory()->forGrade($this->grade10)->create(),
        Exam::factory()->forProfile($this->informatics)->create(),
    ];
    Exam::factory()->forClass($this->class10a)->create();
    Exam::factory()->forGrade($this->grade11)->create();
    Exam::factory()->forProfile($this->biology)->create();

    $visible = Exam::query()->visibleTo($this->student)->pluck('id')->sort()->values()->all();

    expect($visible)->toBe(collect($own)->pluck('id')->sort()->values()->all());
    foreach ($own as $exam) {
        expect($exam->isVisibleTo($this->student))->toBeTrue();
    }
});

test('admins and moderators see every scope, users without a class see nothing', function () {
    Exam::factory()->forClass($this->class10a)->create();
    Exam::factory()->forGrade($this->grade11)->create();

    $moderator = User::factory()->moderator()->inClass($this->class10b)->create();

    expect(Exam::query()->visibleTo(User::factory()->admin()->create())->count())->toBe(2)
        ->and(Exam::query()->visibleTo($moderator)->count())->toBe(2)
        ->and(Exam::query()->visibleTo(User::factory()->create(['class_id' => null]))->count())->toBe(0);
});

test('audience labels are readable', function () {
    $exam = Exam::factory()->forGrade($this->grade10)->create();
    $classExam = Exam::factory()->forClass($this->class10b)->create();

    expect($exam->audienceLabel())->toBe('Випуск: 10 клас')
        ->and($classExam->audienceLabel())->toBe('Клас: 10Б');
});

test('options lists only own scopes for a user and all scopes otherwise', function () {
    expect(collect(Audience::options($this->student))->pluck('value')->all())->toBe([
        'class:'.$this->class10b->id,
        'grade:'.$this->grade10->id,
        'profile:'.$this->informatics->id,
    ])->and(Audience::options())->toHaveCount(2 + 3 + 2);
});

test('audience rule validates existence and membership', function () {
    $passes = fn (string $value, ?User $member = null) => Validator::make(
        ['scope' => $value],
        ['scope' => [new AudienceScopeRule($member)]],
    )->passes();

    expect($passes('class:'.$this->class10a->id))->toBeTrue()
        ->and($passes('class:999999'))->toBeFalse()
        ->and($passes('room:1'))->toBeFalse()
        ->and($passes('garbage'))->toBeFalse()
        ->and($passes('class:'.$this->class10b->id, $this->student))->toBeTrue()
        ->and($passes('class:'.$this->class10a->id, $this->student))->toBeFalse();
});
