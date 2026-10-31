<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AllowedStudent;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    /**
     * One account per role (admin from .env, teacher, moderator, student) and one unclaimed whitelist row.
     */
    public function run(): void
    {
        $this->seedAdmin();

        $classes = SchoolClass::query()->ordered()->get();
        $class = $classes->firstWhere('name', '11А') ?? $classes->first();
        $password = Hash::make(self::DEMO_PASSWORD);

        $this->upsertUser('teacher@example.com', 'Мария Петрова', Role::Teacher, null, $password);
        $this->upsertStudent('moderator@example.com', 'Георги Иванов', Role::Moderator, $class, $password);
        $this->upsertStudent('student@example.com', 'Иван Георгиев', Role::Student, $class, $password);

        AllowedStudent::firstOrCreate(
            ['email' => 'newstudent@example.com'],
            ['class_id' => $classes->first()->id],
        );
    }

    private function seedAdmin(): void
    {
        $email = config('school.seed_admin.email');
        $password = config('school.seed_admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD are not set; skipping admin account.');

            return;
        }

        $this->upsertUser($email, 'Администратор', Role::Admin, null, Hash::make($password));
    }

    private function upsertStudent(string $email, string $name, Role $role, SchoolClass $class, string $password): void
    {
        $user = $this->upsertUser($email, $name, $role, $class, $password);

        AllowedStudent::updateOrCreate(
            ['email' => $email],
            ['class_id' => $class->id, 'claimed_at' => now(), 'claimed_by' => $user->id],
        );
    }

    private function upsertUser(string $email, string $name, Role $role, ?SchoolClass $class, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => $password,
            'role' => $role,
            'class_id' => $class?->id,
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }
}
