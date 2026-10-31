<?php

use App\Enums\Role;
use App\Models\AllowedStudent;
use App\Models\SchoolClass;
use App\Models\User;

function registrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Иван Иванов',
        'email' => 'ivan@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('registration screen can be rendered', function () {
    $this->get('/register')->assertOk();
});

test('whitelisted students can register and get the class from the whitelist', function () {
    $class = SchoolClass::factory()->named(10, 'Б')->create();
    $allowed = AllowedStudent::factory()->create(['email' => 'ivan@example.com', 'class_id' => $class->id]);

    $response = $this->post('/register', registrationPayload(['email' => 'Ivan@Example.com']));

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::where('email', 'ivan@example.com')->firstOrFail();
    expect($user->role)->toBe(Role::Student)
        ->and($user->class_id)->toBe($class->id)
        ->and($user->schoolClass->name)->toBe('10Б')
        ->and($user->is_active)->toBeTrue();

    $allowed->refresh();
    expect($allowed->claimed_at)->not->toBeNull()
        ->and($allowed->claimed_by)->toBe($user->id);
});

test('registration fails for an email that is not whitelisted', function () {
    $response = $this->from('/register')->post('/register', registrationPayload());

    $response->assertRedirect('/register')
        ->assertSessionHasErrors(['email' => __('auth.not_whitelisted')]);
    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('registration fails for an already claimed whitelist email with the same message', function () {
    $existing = User::factory()->create(['email' => 'ivan@example.com']);
    $allowed = AllowedStudent::factory()->claimed($existing)->create(['email' => 'ivan@example.com']);

    $response = $this->from('/register')->post('/register', registrationPayload());

    $response->assertSessionHasErrors(['email' => __('auth.not_whitelisted')]);
    $this->assertGuest();
    expect(User::where('email', 'ivan@example.com')->count())->toBe(1)
        ->and($allowed->fresh()->claimed_by)->not->toBeNull();
});

test('registration fails when a non-student account already uses the whitelisted email', function () {
    User::factory()->teacher()->create(['email' => 'ivan@example.com']);
    AllowedStudent::factory()->create(['email' => 'ivan@example.com']);

    $this->post('/register', registrationPayload())
        ->assertSessionHasErrors(['email' => __('auth.not_whitelisted')]);

    $this->assertGuest();
});

test('registration error message is in Bulgarian', function () {
    expect(__('auth.not_whitelisted'))
        ->toBe('Този имейл не е в списъка на училището. Свържи се с администратор.');
});

test('registration is rate limited', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/register', registrationPayload(['email' => "nobody{$i}@example.com"]));
    }

    $this->post('/register', registrationPayload())->assertStatus(429);
});
