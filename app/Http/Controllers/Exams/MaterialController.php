<?php

namespace App\Http\Controllers\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\StoreMaterialRequest;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Material;
use App\Services\MaterialStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function __construct(private readonly MaterialStorage $storage) {}

    public function store(StoreMaterialRequest $request, Exam $exam): RedirectResponse
    {
        try {
            $this->storage->store($exam, $request->user(), $request->file('file'), $request->validated('title'));
        } catch (RuntimeException) {
            return back()->with('error', __('ui.materials.upload_failed'));
        }

        return back()->with('success', __('ui.materials.uploaded'));
    }

    public function download(Material $material): StreamedResponse
    {
        Gate::authorize('view', $material);

        return $this->storage->download($material);
    }

    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $material->delete();
        AuditLog::recordModeratorDeletion($material);

        return back()->with('success', __('ui.materials.deleted'));
    }
}
