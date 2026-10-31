<?php

namespace App\Models\Concerns;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Content that users can report to moderators (questions, answers, messages).
 */
trait Reportable
{
    /**
     * @return MorphMany<Report, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function hasOpenReportFrom(User $user): bool
    {
        return $this->reports()
            ->where('reporter_id', $user->id)
            ->where('status', ReportStatus::Open)
            ->exists();
    }
}
