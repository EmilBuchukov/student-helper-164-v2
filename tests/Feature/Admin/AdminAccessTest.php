<?php

use App\Enums\Role;
use App\Models\User;
use App\Policies\ChannelPolicy;
use Illuminate\Http\UploadedFile;

dataset('non-admin roles', [Role::Student, Role::Moderator, Role::Teacher]);

test('non-admins get 403 on admin pages', function (Role $role) {
    $user = User::factory()->role($role)->create();

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get('/admin/students/import')->assertForbidden();
    $this->actingAs($user)->post('/admin/students/import', [
        'file' => UploadedFile::fake()->createWithContent('s.csv', "email,grade,letter\na@example.com,10,Б\n"),
    ])->assertForbidden();
})->with('non-admin roles');

test('guests are redirected to login from admin pages', function () {
    $this->get('/admin/users')->assertRedirect(route('login'));
    $this->get('/admin/students/import')->assertRedirect(route('login'));
});

test('admins can open the user list and the import page', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 4)
            ->has('users.data.0', fn ($user) => $user
                ->hasAll(['id', 'name', 'email', 'role', 'class_name', 'is_active'])));

    $this->actingAs($admin)
        ->get('/admin/students/import')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Students/Import'));
});

test('user helper methods reflect the role', function () {
    $admin = User::factory()->admin()->make();
    $teacher = User::factory()->teacher()->make();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->hasRole('admin', 'moderator'))->toBeTrue()
        ->and($teacher->isAdmin())->toBeFalse()
        ->and($teacher->isTeacher())->toBeTrue();
});

test('channel policy denies teachers', function () {
    $policy = new ChannelPolicy;

    expect($policy->viewAny(User::factory()->teacher()->make()))->toBeFalse()
        ->and($policy->viewAny(User::factory()->make()))->toBeTrue();
});

test('error pages are shown in Bulgarian', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/users')
        ->assertForbidden()
        ->assertSee('Нямаш достъп до тази страница')
        ->assertDontSee('Forbidden');

    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Страницата не е намерена');
});
