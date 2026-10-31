<?php

namespace App\Models;

use App\Models\Concerns\HasAudienceScope;
use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Student chat channel for one grade, class or profile. Created automatically by ChannelService.
 */
#[Fillable(['name', 'scope_type', 'scope_id', 'is_archived'])]
class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use HasAudienceScope, HasFactory;

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<ChannelMute, $this>
     */
    public function mutes(): HasMany
    {
        return $this->hasMany(ChannelMute::class);
    }

    /**
     * Channels are a student space: teachers never see them, not even read-only.
     */
    protected static function audienceHiddenFrom(User $user): bool
    {
        return $user->isTeacher();
    }

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }
}
