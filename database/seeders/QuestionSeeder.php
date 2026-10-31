<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo school-wide question with an accepted, upvoted answer.
 */
class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'student@example.com')->first();
        $responder = User::where('email', 'moderator@example.com')->first();

        if (Question::exists() || ! $author || ! $responder) {
            return;
        }

        $question = Question::create([
            'user_id' => $author->id,
            'title' => 'Какви извънкласни клубове има тази година?',
            'body' => 'Нов съм в училището и искам да се включа в нещо след часовете.',
            'subject' => null,
            'created_at' => now()->subDays(2),
        ]);

        $answer = $question->answers()->create([
            'user_id' => $responder->id,
            'body' => 'Има театрално студио „Сервантес“ – виж секцията „Клубове“.',
            'created_at' => $question->created_at->addHour(),
        ]);

        $answer->votes()->create(['user_id' => $author->id]);
        $question->votes()->create(['user_id' => $responder->id]);
        $question->update(['accepted_answer_id' => $answer->id]);
    }
}
