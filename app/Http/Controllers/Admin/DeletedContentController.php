<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Изтрито съдържание": soft-deleted questions, answers and messages that admins can restore.
 */
class DeletedContentController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const TYPES = [
        'question' => Question::class,
        'answer' => Answer::class,
        'message' => Message::class,
    ];

    public function index(Request $request): Response
    {
        $type = $request->validate(['type' => ['nullable', Rule::in(array_keys(self::TYPES))]])['type'] ?? 'question';
        $class = self::TYPES[$type];
        $context = match ($type) {
            'question' => [],
            'answer' => ['question' => fn ($q) => $q->withTrashed()->select('id', 'title')],
            'message' => ['channel:id,name'],
        };

        $items = $class::onlyTrashed()
            ->with(['user:id,name', ...$context])
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Model $item) => [
                'id' => $item->id,
                'text' => $item instanceof Question ? $item->title : str($item->body)->squish()->limit(160)->toString(),
                'author' => $item->user?->name,
                'context' => match (true) {
                    $item instanceof Answer => $item->question?->title,
                    $item instanceof Message => $item->channel?->name,
                    default => null,
                },
                'deleted_at' => $item->deleted_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Deleted/Index', [
            'items' => $items,
            'type' => $type,
            'types' => array_keys(self::TYPES),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $item = self::TYPES[$type]::onlyTrashed()->findOrFail($id);
        $item->restore();

        AuditLog::record($request->user(), 'content_restored', $item);

        return back()->with('success', __('ui.admin.deleted.restored'));
    }
}
