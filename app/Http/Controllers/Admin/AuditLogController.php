<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['action' => ['nullable', Rule::in(AuditLog::ACTIONS)]]);

        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Audit/Index', [
            'logs' => $logs->setCollection(collect(self::present($logs->getCollection()))),
            'filters' => ['action' => $filters['action'] ?? ''],
            'actions' => AuditLog::ACTIONS,
        ]);
    }

    /**
     * @param  Collection<int, AuditLog>  $logs
     * @return list<array<string, mixed>>
     */
    public static function present(Collection $logs): array
    {
        return $logs->map(fn (AuditLog $log) => [
            'id' => $log->id,
            'actor' => $log->actor?->name,
            'action' => $log->action,
            'target_type' => $log->target_type,
            'target_id' => $log->target_id,
            'label' => $log->details['label'] ?? null,
            'details' => collect($log->details ?? [])->except('label')->all(),
            'created_at' => $log->created_at->toIso8601String(),
        ])->values()->all();
    }
}
