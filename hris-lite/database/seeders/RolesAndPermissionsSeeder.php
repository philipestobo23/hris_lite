<?php

namespace Database\Seeders;

use App\Support\Permissions as PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        $registrar->forgetCachedPermissions();

        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // When invoked through DatabaseSeeder (which uses WithoutModelEvents),
        // Spatie's automatic cache flush on model save never fires, so the
        // permission cache can still hold the pre-seed (empty) collection.
        // Flush it manually so the permissions we just created are resolvable
        // before we assign them to roles.
        $registrar->forgetCachedPermissions();

        foreach (array_keys(config('permissions.roles')) as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(PermissionCatalog::forRole($roleName));
        }

        $registrar->forgetCachedPermissions();
    }
}
