<?php

namespace App\Policies;

use App\Models\BiometricDevice;
use App\Models\User;

class BiometricDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('devices.view');
    }

    public function view(User $user, BiometricDevice $device): bool
    {
        return $user->can('devices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('devices.create');
    }

    public function update(User $user, BiometricDevice $device): bool
    {
        return $user->can('devices.edit');
    }

    public function delete(User $user, BiometricDevice $device): bool
    {
        return $user->can('devices.delete');
    }
}
