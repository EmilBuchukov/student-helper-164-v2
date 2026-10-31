<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Models\AllowedStudent;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class StudentImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Students/Import', [
            'report' => session('import_report'),
            'stats' => [
                'total' => AllowedStudent::count(),
                'claimed' => AllowedStudent::whereNotNull('claimed_at')->count(),
            ],
        ]);
    }

    public function store(ImportStudentsRequest $request, StudentImportService $importer): RedirectResponse
    {
        try {
            $report = $importer->import($request->file('file')->getRealPath());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('admin.students.import')
            ->with('import_report', $report)
            ->with('success', __('ui.import.done', [
                'created' => count($report['created']),
                'skipped' => count($report['skipped']),
                'invalid' => count($report['invalid']),
            ]));
    }
}
