<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $this->sharedUser($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'locale' => fn () => app()->getLocale(),
            'translations' => fn () => $this->translations(),
        ];
    }

    /**
     * The subset of the authenticated user exposed to the frontend.
     *
     * @return array<string, mixed>|null
     */
    protected function sharedUser(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'class_name' => $user->schoolClass?->name,
            'is_admin' => $user->isAdmin(),
        ];
    }

    /**
     * Flattened UI translations (lang/{locale}/ui.php) for the frontend.
     *
     * @return array<string, string>
     */
    protected function translations(): array
    {
        $translations = trans('ui');

        return is_array($translations) ? Arr::dot($translations) : [];
    }
}
