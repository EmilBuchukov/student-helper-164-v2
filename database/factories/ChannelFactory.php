<?php

namespace Database\Factories;

use App\Enums\ScopeType;
use App\Models\Channel;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Канал '.fake()->word(),
            'scope_type' => ScopeType::SchoolClass,
            'scope_id' => fn () => SchoolClass::factory()->create()->id,
            'is_archived' => false,
        ];
    }
}
