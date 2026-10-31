<?php

namespace App\Http\Controllers\Clubs;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClubMembershipController extends Controller
{
    public function store(Request $request, Club $club): RedirectResponse
    {
        Gate::authorize('join', $club);

        $club->members()->syncWithoutDetaching([$request->user()->id => ['joined_at' => now()]]);

        return back()->with('success', __('ui.clubs.joined'));
    }

    public function destroy(Request $request, Club $club): RedirectResponse
    {
        Gate::authorize('leave', $club);

        $club->members()->detach($request->user()->id);

        return back()->with('success', __('ui.clubs.left'));
    }
}
