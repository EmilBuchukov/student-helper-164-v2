<?php

use App\Enums\Role;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('profile page shows name, email, class and role', function () {
    $class = SchoolClass::factory()->named(11, 'А')->create();
    $user = User::factory()->inClass($class)->create(['name' => 'Елена', 'email' => 'elena@example.com']);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')
            ->where('profile.name', 'Елена')
            ->where('profile.email', 'elena@example.com')
            ->where('profile.class_name', '11А')
            ->where('profile.role', 'student'));
});

test('class and role can not be changed through the profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', ['role' => 'admin'])->assertStatus(405);

    expect($user->fresh()->role)->toBe(Role::Student);
});

test('guests are redirected from the profile page', function () {
    $this->get('/profile')->assertRedirect(route('login'));
});
