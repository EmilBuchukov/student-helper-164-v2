<?php

namespace App\Http\Controllers\Exams;

use App\Enums\ExamType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\ExamRequest;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Material;
use App\Support\Audience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Exam::class);

        $user = $request->user();
        $filters = $request->validate([
            'subject' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'scope' => ['nullable', 'string', 'max:30'],
        ]);

        $today = now()->toDateString();
        $scope = Audience::decode($filters['scope'] ?? null);

        $exams = Exam::query()
            ->visibleTo($user)
            ->with(['scope', 'creator:id,name'])
            ->withCount('materials')
            ->when($filters['subject'] ?? null, fn ($q, $subject) => $q->where('subject', $subject))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('exam_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('exam_date', '<=', $to))
            ->when($scope, fn ($q) => $q->forAudience($scope[0], $scope[1]))
            // Upcoming exams first (soonest first), then past exams (most recent first).
            ->orderByRaw('CASE WHEN exam_date >= ? THEN 0 ELSE 1 END', [$today])
            ->orderByRaw('CASE WHEN exam_date >= ? THEN exam_date END ASC', [$today])
            ->orderByDesc('exam_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Exam $exam) => $this->summary($exam));

        $seesAll = $user->isAdmin() || $user->isModerator() || $user->isTeacher();

        return Inertia::render('Exams/Index', [
            'exams' => $exams,
            'filters' => [
                'subject' => $filters['subject'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
                'scope' => $filters['scope'] ?? '',
            ],
            'subjects' => Exam::query()->visibleTo($user)->distinct()->orderBy('subject')->pluck('subject'),
            'scopeOptions' => Audience::options($seesAll ? null : $user),
            'can' => ['create' => $user->can('create', Exam::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Exam::class);

        return Inertia::render('Exams/Form', $this->formProps($request));
    }

    public function store(ExamRequest $request): RedirectResponse
    {
        $exam = Exam::create([...$request->examData(), 'created_by' => $request->user()->id]);

        return redirect()->route('exams.show', $exam)->with('success', __('ui.exams.created'));
    }

    public function show(Request $request, Exam $exam): Response
    {
        Gate::authorize('view', $exam);

        $user = $request->user();
        $exam->load(['scope', 'creator:id,name', 'materials' => fn ($q) => $q->with('user:id,name')->latest()]);

        return Inertia::render('Exams/Show', [
            'exam' => [
                ...$this->summary($exam),
                'description' => $exam->description,
                'can' => [
                    'update' => $user->can('update', $exam),
                    'delete' => $user->can('delete', $exam),
                    'upload' => $user->can('create', [Material::class, $exam]),
                ],
            ],
            'materials' => $exam->materials->map(fn (Material $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'original_name' => $m->original_name,
                'mime_type' => $m->mime_type,
                'size' => $m->size,
                'author' => $m->user?->name,
                'created_at' => $m->created_at->toIso8601String(),
                'download_url' => route('materials.download', $m),
                'can_delete' => $user->can('delete', $m),
            ]),
            'upload' => [
                'extensions' => config('school.materials_extensions'),
                'max_mb' => (int) (config('school.materials_max_kb') / 1024),
            ],
        ]);
    }

    public function edit(Request $request, Exam $exam): Response
    {
        Gate::authorize('update', $exam);

        return Inertia::render('Exams/Form', [
            ...$this->formProps($request, $exam),
            'exam' => [
                'id' => $exam->id,
                'subject' => $exam->subject,
                'title' => $exam->title,
                'description' => $exam->description,
                'exam_date' => $exam->exam_date->toDateString(),
                'type' => $exam->type->value,
                'scope' => $exam->scope_type->value.':'.$exam->scope_id,
            ],
        ]);
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $exam->update($request->examData());

        return redirect()->route('exams.show', $exam)->with('success', __('ui.exams.updated'));
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        Gate::authorize('delete', $exam);

        $exam->delete();
        AuditLog::recordModeratorDeletion($exam, 'created_by');

        return redirect()->route('exams.index')->with('success', __('ui.exams.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Exam $exam): array
    {
        return [
            'id' => $exam->id,
            'subject' => $exam->subject,
            'title' => $exam->title,
            'exam_date' => $exam->exam_date->toDateString(),
            'type' => $exam->type->value,
            'audience' => $exam->audienceLabel(),
            'is_upcoming' => $exam->isUpcoming(),
            'creator' => $exam->creator?->name,
            'materials_count' => $exam->materials_count ?? $exam->materials->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Request $request, ?Exam $exam = null): array
    {
        $user = $request->user();
        $options = Audience::options($user->can('chooseAnyScope', Exam::class) ? null : $user);

        // Keep the current audience selectable when a moderator edits an exam outside their class.
        if ($exam && ! collect($options)->contains('value', $exam->scope_type->value.':'.$exam->scope_id)) {
            $exam->loadMissing('scope');
            $options[] = [
                'value' => $exam->scope_type->value.':'.$exam->scope_id,
                'type' => $exam->scope_type->value,
                'id' => $exam->scope_id,
                'label' => $exam->audienceLabel(),
            ];
        }

        return [
            'exam' => null,
            'scopeOptions' => $options,
            'types' => array_map(fn (ExamType $t) => ['value' => $t->value, 'label' => $t->label()], ExamType::cases()),
            'subjects' => __('ui.exams.subject_suggestions'),
        ];
    }
}
