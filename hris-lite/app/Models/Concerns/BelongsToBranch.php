<?php

namespace App\Models\Concerns;

use App\Models\Scopes\BranchScope;

/**
 * Marks a model as branch-owned and applies the global {@see BranchScope}.
 *
 * Defaults suit a model with a nullable `branch_id` column (NULL = shared).
 * Override the methods to change the column (e.g. the Branch model scopes on
 * its own `id`), disable shared rows, or scope by accessibility only.
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    public function getBranchColumn(): string
    {
        return 'branch_id';
    }

    /** NULL in the branch column means the row is shared across branches. */
    public function branchScopeAllowsShared(): bool
    {
        return true;
    }

    /** Whether the active-branch selection narrows results (vs. accessibility only). */
    public function branchScopeUsesActive(): bool
    {
        return true;
    }
}
