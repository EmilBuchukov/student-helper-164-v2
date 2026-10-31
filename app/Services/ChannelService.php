<?php

namespace App\Services;

use App\Enums\ScopeType;
use App\Models\Channel;
use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;

/**
 * Keeps one channel per grade, class and profile.
 */
class ChannelService
{
    public function ensureFor(Grade|SchoolClass|Profile $target): Channel
    {
        $type = $this->typeOf($target);

        $channel = Channel::firstOrNew(['scope_type' => $type->value, 'scope_id' => $target->getKey()]);
        $channel->fill(['name' => $this->nameFor($target), 'is_archived' => false])->save();

        return $channel;
    }

    public function archiveFor(Grade|SchoolClass|Profile $target): void
    {
        Channel::query()
            ->where('scope_type', $this->typeOf($target)->value)
            ->where('scope_id', $target->getKey())
            ->update(['is_archived' => true]);
    }

    /**
     * Create (or refresh) channels for the whole school structure. Returns the number of channels.
     */
    public function syncAll(): int
    {
        $targets = Grade::query()->orderBy('number')->get()
            ->concat(SchoolClass::query()->ordered()->get())
            ->concat(Profile::query()->orderBy('name')->get());

        $targets->each(fn ($target) => $this->ensureFor($target));

        return $targets->count();
    }

    public function nameFor(Grade|SchoolClass|Profile $target): string
    {
        return match (true) {
            $target instanceof Grade => __('ui.channels.names.grade', ['number' => $target->number]),
            $target instanceof SchoolClass => __('ui.channels.names.class', ['name' => $target->name]),
            $target instanceof Profile => __('ui.channels.names.profile', ['name' => $target->name]),
        };
    }

    private function typeOf(Grade|SchoolClass|Profile $target): ScopeType
    {
        return match (true) {
            $target instanceof Grade => ScopeType::Grade,
            $target instanceof SchoolClass => ScopeType::SchoolClass,
            $target instanceof Profile => ScopeType::Profile,
        };
    }
}
