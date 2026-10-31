<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Services\ChannelService;
use Illuminate\Database\Seeder;

/**
 * Channels for the school structure, one demo chat message and one open report.
 */
class ChannelSeeder extends Seeder
{
    public function run(ChannelService $channels): void
    {
        $channels->syncAll();

        $student = User::where('email', 'student@example.com')->first();

        if (Message::exists() || ! $student?->class_id) {
            return;
        }

        $channel = Channel::query()->where('scope_type', 'class')->where('scope_id', $student->class_id)->first();

        $channel?->messages()->create([
            'user_id' => $student->id,
            'body' => 'Здравейте! Някой разбра ли домашното по математика?',
        ]);

        if ($answer = Answer::query()->where('user_id', '!=', $student->id)->first()) {
            Report::create([
                'reporter_id' => $student->id,
                'reportable_type' => 'answer',
                'reportable_id' => $answer->id,
                'reason' => 'Отговорът е непълен.',
            ]);
        }
    }
}
