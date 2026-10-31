<?php

namespace App\Http\Controllers\Questions;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Toggle upvotes. Users cannot vote on their own content.
 */
class VoteController extends Controller
{
    public function question(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('vote', $question);

        $question->toggleVote($request->user());

        return back();
    }

    public function answer(Request $request, Answer $answer): RedirectResponse
    {
        Gate::authorize('vote', $answer);

        $answer->toggleVote($request->user());

        return back();
    }
}
