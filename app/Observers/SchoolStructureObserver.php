<?php

namespace App\Observers;

use App\Models\Grade;
use App\Models\Profile;
use App\Models\SchoolClass;
use App\Services\ChannelService;

/**
 * Creates/renames/archives the channel of a grade, class or profile when it changes.
 */
class SchoolStructureObserver
{
    public function __construct(private readonly ChannelService $channels) {}

    public function saved(Grade|SchoolClass|Profile $model): void
    {
        $this->channels->ensureFor($model);
    }

    public function deleted(Grade|SchoolClass|Profile $model): void
    {
        $this->channels->archiveFor($model);
    }
}
