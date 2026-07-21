<?php

namespace App\Policies;

use App\Models\AttendanceLog;
use App\Models\User;

class AttendanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, AttendanceLog $log): bool
    {
        return $user->can('attendance.view');
    }

    /**
     * Fetching punches from a device creates attendance records.
     */
    public function create(User $user): bool
    {
        return $user->can('attendance.create');
    }
}
