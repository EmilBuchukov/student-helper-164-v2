<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Channels\MuteRequest;
use App\Http\Requests\Moderation\ResolveReportRequest;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\ChannelMute;
use App\Models\Message;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\UserSuspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reports queue for moderators and admins.
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([...ReportStatus::values(), 'all'])],
            'type' => ['nullable', Rule::in(Report::REPORTABLE_TYPES)],
        ]);
        $status = $filters['status'] ?? ReportStatus::Open->value;

        $reports = Report::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('reportable_type', $type))
            ->with([
                'reporter:id,name',
                'handler:id,name',
                'reportable' => fn (MorphTo $morph) => $morph->morphWith([
                    Question::class => ['user:id,name,role'],
                    Answer::class => ['user:id,name,role'],
                    Message::class => ['user:id,name,role', 'channel:id,name'],
                ]),
            ])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Report $report) => $this->present($report));

        return Inertia::render('Moderation/Reports/Index', [
            'reports' => $reports,
            'filters' => ['status' => $status, 'type' => $filters['type'] ?? ''],
            'statuses' => ReportStatus::values(),
            'types' => Report::REPORTABLE_TYPES,
            'muteDurations' => MuteRequest::DURATIONS,
            'canSuspend' => $request->user()->isAdmin(),
        ]);
    }

    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        abort_unless($report->status === ReportStatus::Open, 409);

        $note = $request->validate(['note' => ['nullable', 'string', 'max:1000']])['note'] ?? null;
        $this->close($report, ReportStatus::Dismissed, $request->user(), $note);

        AuditLog::record($request->user(), 'report_dismissed', $report, ['note' => $note]);

        return back()->with('success', __('ui.moderation.dismissed'));
    }

    public function resolve(ResolveReportRequest $request, Report $report, UserSuspension $suspension): RedirectResponse
    {
        abort_unless($report->status === ReportStatus::Open, 409);

        $actor = $request->user();
        $content = $report->reportable;
        $author = $content?->user;
        $protected = $author && ($author->isAdmin() || ($author->isModerator() && ! $actor->isAdmin()));

        if ($protected && ($request->filled('mute_minutes') || $request->boolean('suspend'))) {
            return back()->withErrors(['mute_minutes' => __('ui.channels.cannot_mute')]);
        }

        DB::transaction(function () use ($request, $report, $actor, $content, $author, $suspension) {
            $removed = false;

            if ($request->boolean('remove_content') && $content && ! $content->trashed()) {
                $content->delete();
                AuditLog::record($actor, 'content_deleted', $content, ['report' => $report->id]);
                $removed = true;
            }

            if ($author && $request->filled('mute_minutes')) {
                $mute = ChannelMute::create([
                    'channel_id' => null,
                    'user_id' => $author->id,
                    'muted_until' => now()->addMinutes($request->integer('mute_minutes')),
                    'reason' => $request->validated('note') ?: $report->reason,
                    'created_by' => $actor->id,
                ]);
                AuditLog::record($actor, 'user_muted', $author, ['until' => $mute->muted_until->toIso8601String(), 'global' => true, 'report' => $report->id]);
            }

            if ($author && $request->boolean('suspend') && $author->is_active && ! $author->is($actor)) {
                $suspension->suspend($author, $actor, $request->validated('note') ?: $report->reason);
            }

            $this->close($report, ReportStatus::Resolved, $actor, $request->validated('note'));

            // Removing the content settles every other open report about it.
            if ($removed) {
                Report::open()
                    ->where('reportable_type', $report->reportable_type)
                    ->where('reportable_id', $report->reportable_id)
                    ->get()
                    ->each(fn (Report $other) => $this->close($other, ReportStatus::Resolved, $actor, $request->validated('note')));
            }

            AuditLog::record($actor, 'report_resolved', $report, [
                'removed' => $removed,
                'muted_minutes' => $request->integer('mute_minutes') ?: null,
                'suspended' => $request->boolean('suspend'),
            ]);
        });

        return back()->with('success', __('ui.moderation.resolved'));
    }

    private function close(Report $report, ReportStatus $status, User $actor, ?string $note): void
    {
        $report->update([
            'status' => $status,
            'handled_by' => $actor->id,
            'handled_at' => now(),
            'resolution_note' => $note,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Report $report): array
    {
        /** @var Model|null $content */
        $content = $report->reportable;

        return [
            'id' => $report->id,
            'type' => $report->reportable_type,
            'reason' => $report->reason,
            'status' => $report->status->value,
            'reporter' => $report->reporter?->name,
            'handler' => $report->handler?->name,
            'handled_at' => $report->handled_at?->toIso8601String(),
            'note' => $report->resolution_note,
            'created_at' => $report->created_at->toIso8601String(),
            'content' => $content ? [
                'title' => $content instanceof Question ? $content->title : null,
                'text' => str($content->body)->squish()->limit(300)->toString(),
                'author' => $content->user?->name,
                'author_id' => $content->user_id,
                'author_protected' => (bool) $content->user?->isAdmin(),
                'context' => $content instanceof Message ? $content->channel?->name : null,
                'deleted' => $content->trashed(),
                'url' => match (true) {
                    $content instanceof Question && ! $content->trashed() => route('questions.show', $content->id),
                    $content instanceof Answer => route('questions.show', $content->question_id),
                    $content instanceof Message => route('channels.show', $content->channel_id),
                    default => null,
                },
            ] : null,
        ];
    }
}
