<?php

namespace Database\Seeders;

use App\Enums\RsvpStatus;
use App\Models\Club;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo club with a leader, a member and one upcoming event.
 */
class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $leader = User::where('email', 'student@example.com')->first();
        $member = User::where('email', 'moderator@example.com')->first();

        if (Club::exists() || ! $leader) {
            return;
        }

        $name = 'Театрално студио „Сервантес“';
        $club = Club::create([
            'name' => $name,
            'slug' => Club::uniqueSlug($name),
            'description' => 'Поставяме пиеси на испански и български език. Тази година репетираме „Дон Кихот“.',
            'leader_id' => $leader->id,
            'is_active' => true,
        ]);

        $members = collect([$leader, $member])->filter();
        $club->members()->syncWithoutDetaching($members->mapWithKeys(fn (User $u) => [$u->id => ['joined_at' => now()->subDays(10)]])->all());

        $start = now()->startOfDay()->addDays(3)->setTime(16, 0);

        $event = Event::create([
            'club_id' => $club->id,
            'title' => 'Репетиция на „Дон Кихот“',
            'description' => 'Заповядайте! Повече информация ще получите от организаторите.',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'location' => 'Актова зала',
            'created_by' => $leader->id,
        ]);

        $event->attendees()->attach($leader->id, ['status' => RsvpStatus::Going]);
    }
}
