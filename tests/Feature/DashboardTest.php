<?php

use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shows the user name, class and role', function () {
    $class = SchoolClass::factory()->named(10, 'Б')->create();
    $user = User::factory()->inClass($class)->create(['name' => 'Иван']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user.name', 'Иван')
            ->where('auth.user.class_name', '10Б')
            ->where('auth.user.role', 'student')
            ->where('auth.user.is_admin', false));
});

test('unverified users are sent to email verification', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
});

test('guests see the Bulgarian landing page and logged in users are redirected to the dashboard', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('locale', 'bg')
            ->where('translations', fn ($t) => $t['nav.home'] === 'Начало' && $t['nav.exams'] === 'Изпити'));

    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
});
