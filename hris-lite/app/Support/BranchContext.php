<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves, per request, which branches the current user can see and which one
 * is active. Registered as a singleton so the resolution (and its queries) run
 * once. Super Admin / HR Officer are "privileged" and may see every branch;
 * everyone else is limited to the branches they are assigned to.
 */
class BranchContext
{
    private const PRIVILEGED_ROLES = ['Super Admin', 'HR Officer'];

    private const SESSION_KEY = 'active_branch_id';

    private bool $resolved = false;

    private ?User $user = null;

    private bool $privileged = false;

    /** @var array<int>|null null = unrestricted (all branches) */
    private ?array $accessibleIds = null;

    private ?int $activeId = null;

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }
        $this->resolved = true;

        $this->user = Auth::user();

        if ($this->user === null) {
            $this->privileged = false;
            $this->accessibleIds = [];
            $this->activeId = null;

            return;
        }

        $this->privileged = $this->user->hasAnyRole(self::PRIVILEGED_ROLES);

        $this->accessibleIds = $this->privileged
            ? null
            : $this->user->branches()
                ->withoutGlobalScope(BranchScope::class)
                ->pluck('branches.id')
                ->map(fn ($id): int => (int) $id)
                ->all();

        $this->activeId = $this->normalizeActive(session(self::SESSION_KEY));

        // A user with exactly one accessible branch is always scoped to it.
        if (! $this->privileged
            && $this->activeId === null
            && is_array($this->accessibleIds)
            && count($this->accessibleIds) === 1) {
            $this->activeId = $this->accessibleIds[0];
        }
    }

    private function normalizeActive(mixed $id): ?int
    {
        if ($id === null) {
            return null;
        }

        $id = (int) $id;

        if ($this->accessibleIds === null) {
            return Branch::withoutGlobalScope(BranchScope::class)->whereKey($id)->exists() ? $id : null;
        }

        return in_array($id, $this->accessibleIds, true) ? $id : null;
    }

    public function enabled(): bool
    {
        $this->resolve();

        return $this->user !== null;
    }

    public function isPrivileged(): bool
    {
        $this->resolve();

        return $this->privileged;
    }

    public function activeId(): ?int
    {
        $this->resolve();

        return $this->activeId;
    }

    /**
     * Branch ids for identity models (the Branch itself): accessibility only.
     *
     * @return array<int>|null
     */
    public function accessibleBranchIds(): ?array
    {
        $this->resolve();

        return $this->accessibleIds;
    }

    /**
     * Branch ids for owned models: the active branch narrows the result,
     * otherwise fall back to everything accessible.
     *
     * @return array<int>|null
     */
    public function ownedBranchIds(): ?array
    {
        $this->resolve();

        if ($this->activeId !== null) {
            return [$this->activeId];
        }

        return $this->accessibleIds;
    }

    /** Whether an "All branches" choice should be offered in the switcher. */
    public function canSelectAll(): bool
    {
        $this->resolve();

        return $this->privileged || count($this->accessibleIds ?? []) > 1;
    }

    /**
     * The branches the user may pick from, for the switcher UI.
     *
     * @return Collection<int, Branch>
     */
    public function accessibleBranches(): Collection
    {
        $this->resolve();

        $query = Branch::withoutGlobalScope(BranchScope::class)
            ->where('is_active', true)
            ->orderBy('name');

        if ($this->accessibleIds !== null) {
            $query->whereIn('id', $this->accessibleIds);
        }

        return $query->get(['id', 'name']);
    }

    public function setActive(?int $id): void
    {
        if ($id === null) {
            session()->forget(self::SESSION_KEY);
        } else {
            session()->put(self::SESSION_KEY, $id);
        }

        $this->resolved = false; // force re-resolution against the new session
    }

    /**
     * Whether the given branch id is a valid target for this user to activate.
     */
    public function canActivate(?int $id): bool
    {
        $this->resolve();

        if ($id === null) {
            return $this->canSelectAll();
        }

        if ($this->accessibleIds === null) {
            return Branch::withoutGlobalScope(BranchScope::class)->whereKey($id)->exists();
        }

        return in_array($id, $this->accessibleIds, true);
    }

    /**
     * Shape shared with the front-end.
     *
     * @return array{active: int|null, canSwitchAll: bool, accessible: array<int, array{id: int, name: string}>}
     */
    public function toArray(): array
    {
        $this->resolve();

        return [
            'active' => $this->activeId,
            'canSwitchAll' => $this->canSelectAll(),
            'accessible' => $this->accessibleBranches()
                ->map(fn (Branch $branch): array => ['id' => $branch->id, 'name' => $branch->name])
                ->values()
                ->all(),
        ];
    }
}
