<?php

use App\Models\User;

test('styleguide is available to admins', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/styleguide')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Styleguide'));
});

test('styleguide is hidden from other users outside local', function () {
    $this->actingAs(User::factory()->create())
        ->get('/styleguide')
        ->assertForbidden();
});

test('styleguide requires login', function () {
    $this->get('/styleguide')->assertRedirect('/login');
});
