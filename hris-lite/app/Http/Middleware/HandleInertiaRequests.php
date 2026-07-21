<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use App\Support\Permissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'can' => $this->permissions($request->user()),
            ],
            'branch' => app(BranchContext::class)->toArray(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * A map of every catalog permission to whether the current user has it.
     * Empty for guests. Super Admin resolves to all-true via the Gate::before
     * bypass registered in AppServiceProvider.
     *
     * @return array<string, bool>
     */
    protected function permissions(?Authenticatable $user): array
    {
        if ($user === null) {
            return [];
        }

        $can = [];

        foreach (Permissions::all() as $permission) {
            $can[$permission] = $user->can($permission);
        }

        return $can;
    }
}
