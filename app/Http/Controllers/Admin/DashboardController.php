<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AllowedStudent;
use App\Models\AuditLog;
use App\Models\ChannelMute;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'counts' => [
                'users' => User::count(),
                'students' => User::where('role', Role::Student)->count(),
                'suspended' => User::where('is_active', false)->count(),
                'exams' => Exam::count(),
                'questions' => Question::count(),
                'open_reports' => Report::open()->count(),
                'active_mutes' => ChannelMute::active()->count(),
                'pending_whitelist' => AllowedStudent::unclaimed()->count(),
            ],
            'recent' => AuditLogController::present(
                AuditLog::query()->with('actor:id,name')->latest('id')->limit(8)->get()
            ),
        ]);
    }
}
