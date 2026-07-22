<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave.view');
    }

    public function view(User $user, Leave $leave): bool
    {
        return $user->can('leave.view');
    }

    public function create(User $user): bool
    {
        return $user->can('leave.create');
    }

    public function update(User $user, Leave $leave): bool
    {
        return $user->can('leave.edit');
    }

    public function delete(User $user, Leave $leave): bool
    {
        return $user->can('leave.delete');
    }

    /**
     * Permission to act on a filing. Whether the filing is still *in a state*
     * that can be acted on is enforced in the controller — a Super Admin
     * bypasses policies via Gate::before, but must not bypass the state machine.
     */
    public function approve(User $user, Leave $leave): bool
    {
        return $user->can('leave.approve');
    }
}
