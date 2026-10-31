<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAllowedStudentRequest;
use App\Models\AllowedStudent;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WhitelistController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['claimed', 'pending'])],
        ]);

        $entries = AllowedStudent::query()
            ->with(['schoolClass', 'claimedBy:id,name'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('email', 'ilike', '%'.addcslashes($term, '\\%_').'%'))
            ->when(($filters['status'] ?? null) === 'claimed', fn ($q) => $q->whereNotNull('claimed_at'))
            ->when(($filters['status'] ?? null) === 'pending', fn ($q) => $q->unclaimed())
            ->orderBy('email')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AllowedStudent $entry) => [
                'id' => $entry->id,
                'email' => $entry->email,
                'class_name' => $entry->schoolClass?->name,
                'claimed' => $entry->isClaimed(),
                'claimed_by' => $entry->claimedBy?->name,
                'claimed_by_id' => $entry->claimed_by,
                'claimed_at' => $entry->claimed_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Whitelist/Index', [
            'entries' => $entries,
            'filters' => ['q' => $filters['q'] ?? '', 'status' => $filters['status'] ?? ''],
            'stats' => [
                'total' => AllowedStudent::count(),
                'claimed' => AllowedStudent::whereNotNull('claimed_at')->count(),
            ],
            'classes' => SchoolClass::query()->ordered()->get()->map(fn (SchoolClass $c) => ['id' => $c->id, 'name' => $c->name]),
        ]);
    }

    public function store(StoreAllowedStudentRequest $request): RedirectResponse
    {
        $entry = AllowedStudent::create($request->validated());

        AuditLog::record($request->user(), 'whitelist_added', $entry);

        return back()->with('success', __('ui.admin.whitelist.added'));
    }

    public function destroy(Request $request, AllowedStudent $allowedStudent): RedirectResponse
    {
        if ($allowedStudent->isClaimed()) {
            return back()->with('error', __('ui.admin.whitelist.cannot_remove_claimed'));
        }

        AuditLog::record($request->user(), 'whitelist_removed', $allowedStudent);
        $allowedStudent->delete();

        return back()->with('success', __('ui.admin.whitelist.removed'));
    }
}
