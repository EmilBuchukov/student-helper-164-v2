<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\StoreReportRequest;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): RedirectResponse
    {
        $request->target()->reports()->create([
            'reporter_id' => $request->user()->id,
            'reason' => $request->validated('reason'),
        ]);

        return back()->with('success', __('ui.reports.created'));
    }
}
