<?php

namespace App\Support;

/**
 * Reads the permission catalog (config/permissions.php) and turns it into the
 * flat "{module}.{ability}" permission names used across seeding, policies and
 * the shared Inertia `auth.can` prop.
 */
class Permissions
{
    /**
     * Every permission name defined by the catalog.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (config('permissions.modules') as $module => $definition) {
            foreach ($definition['abilities'] as $ability) {
                $permissions[] = "{$module}.{$ability}";
            }
        }

        return $permissions;
    }

    /**
     * Permission names granted to a given role from the catalog.
     *
     * @return list<string>
     */
    public static function forRole(string $role): array
    {
        $grants = config("permissions.roles.{$role}", []);

        if ($grants === '*') {
            return self::all();
        }

        $permissions = [];

        foreach ($grants as $module => $abilities) {
            foreach ($abilities as $ability) {
                $permissions[] = "{$module}.{$ability}";
            }
        }

        return $permissions;
    }
}
