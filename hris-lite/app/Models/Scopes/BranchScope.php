<?php

namespace App\Models\Scopes;

use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts branch-owned models to the branches the current user may see.
 *
 * The set of allowed branch ids comes from {@see BranchContext}: identity
 * models (the Branch itself) scope by accessibility only, while owned models
 * (branch_id column) additionally narrow to the active branch. Models whose
 * branch column is nullable treat NULL as "shared" and always include it.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(BranchContext::class);

        if (! $context->enabled()) {
            return; // no authenticated user (console, guests) => no scoping
        }

        $ids = $model->branchScopeUsesActive()
            ? $context->ownedBranchIds()
            : $context->accessibleBranchIds();

        if ($ids === null) {
            return; // null => unrestricted (e.g. Super Admin / HR viewing all)
        }

        $column = $model->qualifyColumn($model->getBranchColumn());
        $allowShared = $model->branchScopeAllowsShared();

        $builder->where(function (Builder $query) use ($ids, $column, $allowShared): void {
            $query->whereIn($column, $ids);

            if ($allowShared) {
                $query->orWhereNull($column);
            }
        });
    }
}
