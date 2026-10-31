<?php

namespace App\Http\Controllers\Questions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Questions\QuestionRequest;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\Question;
use App\Support\Audience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public const SORTS = ['newest', 'votes', 'unresolved'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Question::class);

        $user = $request->user();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:100'],
            'scope' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ]);
        $sort = $filters['sort'] ?? 'newest';
        $scopeFilter = $filters['scope'] ?? null;
        $scope = Audience::decode($scopeFilter);

        $questions = Question::query()
            ->visibleTo($user)
            ->with(['user:id,name', 'scope'])
            ->withCount(['answers', 'votes'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->search($term))
            ->when($filters['subject'] ?? null, fn (Builder $q, string $subject) => $q->where('subject', $subject))
            ->when($scopeFilter === 'school', fn (Builder $q) => $q->whereNull('scope_type'))
            ->when($scope, fn (Builder $q) => $q->forAudience($scope[0], $scope[1]))
            ->when($sort === 'unresolved', fn (Builder $q) => $q->whereNull('accepted_answer_id'))
            ->when($sort === 'votes', fn (Builder $q) => $q->orderByDesc('votes_count'))
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Question $question) => $this->summary($question));

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'subject' => $filters['subject'] ?? '',
                'scope' => $scopeFilter ?? '',
                'sort' => $sort,
            ],
            'subjects' => Question::query()->visibleTo($user)->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject'),
            'scopeOptions' => Audience::options($user->isAdmin() || $user->isModerator() ? null : $user),
            'can' => ['create' => $user->can('create', Question::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Question::class);

        return Inertia::render('Questions/Form', $this->formProps($request));
    }

    public function store(QuestionRequest $request): RedirectResponse
    {
        $question = Question::create([...$request->questionData(), 'user_id' => $request->user()->id]);

        return redirect()->route('questions.show', $question)->with('success', __('ui.questions.created'));
    }

    public function show(Request $request, Question $question): Response
    {
        Gate::authorize('view', $question);

        $user = $request->user();
        $voted = fn ($q) => $q->where('user_id', $user->id);

        $question->load(['user:id,name', 'scope'])
            ->loadCount('votes')
            ->loadExists(['votes as voted' => $voted]);

        $answers = $question->answers()
            ->with('user:id,name')
            ->withCount('votes')
            ->withExists(['votes as voted' => $voted])
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$question->accepted_answer_id ?? 0])
            ->orderByDesc('votes_count')
            ->oldest()
            ->get()
            ->each->setRelation('question', $question);

        return Inertia::render('Questions/Show', [
            'question' => [
                ...$this->summary($question),
                'body' => $question->body,
                'voted' => (bool) $question->voted,
                'can' => [
                    'update' => $user->can('update', $question),
                    'delete' => $user->can('delete', $question),
                    'vote' => $user->can('vote', $question),
                    'report' => $user->can('report', $question),
                    'accept' => $user->can('accept', $question),
                    'answer' => $user->can('answer', $question),
                ],
            ],
            'answers' => $answers->map(fn (Answer $answer) => [
                'id' => $answer->id,
                'body' => $answer->body,
                'author' => $answer->user?->name,
                'created_at' => $answer->created_at->toIso8601String(),
                'edited' => $answer->updated_at->gt($answer->created_at),
                'votes_count' => $answer->votes_count,
                'voted' => (bool) $answer->voted,
                'accepted' => $answer->id === $question->accepted_answer_id,
                'can' => [
                    'update' => $user->can('update', $answer),
                    'delete' => $user->can('delete', $answer),
                    'vote' => $user->can('vote', $answer),
                    'report' => $user->can('report', $answer),
                ],
            ]),
        ]);
    }

    public function edit(Request $request, Question $question): Response
    {
        Gate::authorize('update', $question);

        return Inertia::render('Questions/Form', [
            ...$this->formProps($request, $question),
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'body' => $question->body,
                'subject' => $question->subject ?? '',
                'scope' => $question->scope_type ? $question->scope_type->value.':'.$question->scope_id : '',
            ],
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update($request->questionData());

        return redirect()->route('questions.show', $question)->with('success', __('ui.questions.updated'));
    }

    public function destroy(Question $question): RedirectResponse
    {
        Gate::authorize('delete', $question);

        $question->delete();
        AuditLog::recordModeratorDeletion($question);

        return redirect()->route('questions.index')->with('success', __('ui.questions.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Question $question): array
    {
        return [
            'id' => $question->id,
            'title' => $question->title,
            'excerpt' => str($question->body)->squish()->limit(160)->toString(),
            'subject' => $question->subject,
            'audience' => $question->audienceLabel(),
            'author' => $question->user?->name,
            'created_at' => $question->created_at->toIso8601String(),
            'edited' => $question->updated_at->gt($question->created_at),
            'resolved' => $question->isResolved(),
            'answers_count' => $question->answers_count ?? 0,
            'votes_count' => $question->votes_count ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Question $question = null): array
    {
        $user = $request->user();
        $options = Audience::options($user->can('chooseAnyScope', Question::class) ? null : $user);

        if ($question?->scope_type && ! collect($options)->contains('value', $question->scope_type->value.':'.$question->scope_id)) {
            $question->loadMissing('scope');
            $options[] = [
                'value' => $question->scope_type->value.':'.$question->scope_id,
                'type' => $question->scope_type->value,
                'id' => $question->scope_id,
                'label' => $question->audienceLabel(),
            ];
        }

        return [
            'question' => null,
            'scopeOptions' => $options,
            'subjects' => __('ui.exams.subject_suggestions'),
        ];
    }
}
