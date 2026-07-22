<?php

namespace App\Policies;

use App\Models\DailyTimeRecord;
use App\Models\User;

class DailyTimeRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, DailyTimeRecord $record): bool
    {
        return $user->can('attendance.view');
    }

    /** Generating daily time records from punches. */
    public function create(User $user): bool
    {
        return $user->can('attendance.create');
    }
}
