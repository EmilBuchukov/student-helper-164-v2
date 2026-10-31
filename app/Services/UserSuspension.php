<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Suspends/reactivates accounts. Suspended users cannot log in (LoginRequest) and are
 * logged out of existing sessions (EnsureUserIsActive + session/remember-token cleanup).
 */
class UserSuspension
{
    public function suspend(User $user, User $actor, ?string $reason = null): void
    {
        $user->forceFill(['is_active' => false, 'remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        AuditLog::record($actor, 'user_suspended', $user, ['reason' => $reason]);
    }

    public function reactivate(User $user, User $actor): void
    {
        $user->forceFill(['is_active' => true])->save();

        AuditLog::record($actor, 'user_reactivated', $user);
    }
}
