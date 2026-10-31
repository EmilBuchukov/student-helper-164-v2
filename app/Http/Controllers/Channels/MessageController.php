<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Channels\StoreMessageRequest;
use App\Models\AuditLog;
use App\Models\Channel;
use App\Models\ChannelMute;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * JSON endpoints for the channel view. The frontend polls `index?after=<last id>&since=<server_time>`;
 * a broadcaster (e.g. Laravel Reverb) could later push the same payloads instead.
 */
class MessageController extends Controller
{
    public const PAGE_SIZE = 30;

    public function index(Request $request, Channel $channel): JsonResponse
    {
        Gate::authorize('view', $channel);

        $data = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'before' => ['nullable', 'integer', 'min:1'],
            'since' => ['nullable', 'date'],
        ]);
        $user = $request->user();

        if (isset($data['before'])) {
            return response()->json(self::page($channel, $user, (int) $data['before']));
        }

        $messages = $channel->messages()
            ->with('user:id,name')
            ->where('id', '>', (int) ($data['after'] ?? 0))
            ->orderBy('id')
            ->limit(100)
            ->get();

        $deleted = isset($data['since'])
            ? $channel->messages()->onlyTrashed()->where('deleted_at', '>=', Carbon::parse($data['since']))->pluck('id')
            : collect();

        return response()->json([
            'messages' => self::presentMany($messages, $channel, $user),
            'deleted_ids' => $deleted,
            'mute' => self::mute($user, $channel),
            'can_post' => $user->can('post', $channel),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function store(StoreMessageRequest $request, Channel $channel): JsonResponse
    {
        $message = $channel->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return response()->json(['message' => self::present($message->setRelation('user', $request->user()), $request->user(), false, true)], 201);
    }

    public function destroy(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $message);

        $message->delete();
        AuditLog::recordModeratorDeletion($message);

        return $request->expectsJson()
            ? response()->json(['deleted' => $message->id])
            : back()->with('success', __('ui.channels.message_deleted'));
    }

    /**
     * Latest messages (or those older than $before), oldest first.
     *
     * @return array<string, mixed>
     */
    public static function page(Channel $channel, User $user, ?int $before = null): array
    {
        $messages = $channel->messages()
            ->with('user:id,name')
            ->when($before, fn ($q) => $q->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $messages->count() > self::PAGE_SIZE;

        return [
            'messages' => self::presentMany($messages->take(self::PAGE_SIZE)->reverse()->values(), $channel, $user),
            'hasMore' => $hasMore,
            'mute' => self::mute($user, $channel),
            'serverTime' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<Message>  $messages
     * @return list<array<string, mixed>>
     */
    private static function presentMany(iterable $messages, Channel $channel, User $user): array
    {
        $canModerate = $user->can('moderate', $channel);
        $canView = $user->can('view', $channel);
        $result = [];

        foreach ($messages as $message) {
            $result[] = self::present($message, $user, $canModerate, $canView);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function present(Message $message, User $user, bool $canModerate, bool $canView): array
    {
        $own = $message->user_id === $user->id;

        return [
            'id' => $message->id,
            'body' => $message->body,
            'author' => $message->user?->name,
            'author_id' => $message->user_id,
            'own' => $own,
            'created_at' => $message->created_at->toIso8601String(),
            'can' => [
                'delete' => $canView && ($own || $canModerate),
                'report' => $canView && ! $own,
                'mute' => $canModerate && ! $own && $message->user_id !== null,
            ],
        ];
    }

    /**
     * @return array{until: string, reason: ?string, global: bool}|null
     */
    private static function mute(User $user, Channel $channel): ?array
    {
        $mute = ChannelMute::activeFor($user, $channel);

        return $mute ? [
            'until' => $mute->muted_until->toIso8601String(),
            'reason' => $mute->reason,
            'global' => $mute->isGlobal(),
        ] : null;
    }
}
