<?php

namespace App\Http\Controllers\Questions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Questions\AnswerRequest;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnswerController extends Controller
{
    public function store(AnswerRequest $request, Question $question): RedirectResponse
    {
        $question->answers()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return back()->with('success', __('ui.answers.created'));
    }

    public function update(AnswerRequest $request, Answer $answer): RedirectResponse
    {
        $answer->update($request->validated());

        return back()->with('success', __('ui.answers.updated'));
    }

    public function destroy(Answer $answer): RedirectResponse
    {
        Gate::authorize('delete', $answer);

        $answer->delete();
        AuditLog::recordModeratorDeletion($answer);

        return back()->with('success', __('ui.answers.deleted'));
    }

    /**
     * Mark (or unmark) the accepted answer. Only one answer per question can be accepted.
     */
    public function accept(Answer $answer): RedirectResponse
    {
        $question = $answer->question;

        abort_unless($question, 404);
        Gate::authorize('accept', $question);

        $accepted = $question->accepted_answer_id === $answer->id;
        $question->forceFill(['accepted_answer_id' => $accepted ? null : $answer->id])->saveQuietly();

        return back()->with('success', __($accepted ? 'ui.answers.unaccepted' : 'ui.answers.accepted'));
    }
}
