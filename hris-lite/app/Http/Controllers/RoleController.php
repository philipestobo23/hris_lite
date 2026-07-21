<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * The Super Admin role is the authorization escape hatch and cannot be
     * edited or deleted through the UI.
     */
    private const PROTECTED_ROLE = 'Super Admin';

    public function index(): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->values(),
                'is_protected' => $role->name === self::PROTECTED_ROLE,
            ]);

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            ...$this->catalog(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('roles/Create', $this->catalog());
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'is_protected' => $role->name === self::PROTECTED_ROLE,
            ],
            ...$this->catalog(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        // Never rename the Super Admin role or the bypass gate loses its anchor.
        if ($role->name !== self::PROTECTED_ROLE) {
            $role->update(['name' => $request->validated('name')]);
        }

        $role->syncPermissions($request->validated('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->name === self::PROTECTED_ROLE) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('The Super Admin role cannot be deleted.')]);

            return back();
        }

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return back();
    }

    /**
     * The module/ability catalog shared with the create & edit grids.
     *
     * @return array{modules: Collection<int, array<string, mixed>>, abilities: Collection<int, array<string, string>>}
     */
    private function catalog(): array
    {
        $modules = collect(config('permissions.modules'))
            ->map(fn (array $module, string $key): array => [
                'key' => $key,
                'label' => $module['label'],
                'abilities' => $module['abilities'],
            ])
            ->values();

        $abilities = collect(config('permissions.abilities'))
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
            ])
            ->values();

        return [
            'modules' => $modules,
            'abilities' => $abilities,
        ];
    }
}
