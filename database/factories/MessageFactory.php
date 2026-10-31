<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'channel_id' => fn () => Channel::factory()->create()->id,
            'user_id' => fn () => User::factory()->create()->id,
            'body' => fake()->sentence(),
        ];
    }
}
