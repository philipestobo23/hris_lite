<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abilities
    |--------------------------------------------------------------------------
    |
    | The set of actions a permission can grant, keyed by slug with a display
    | label. Permission names are composed as "{module}.{ability}".
    |
    */

    'abilities' => [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'approve' => 'Approve',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    |
    | Each module declares which abilities are meaningful for it. Only the
    | listed abilities are turned into permissions and rendered on the grid.
    |
    */

    'modules' => [
        'employees' => ['label' => 'Employees', 'abilities' => ['view', 'create', 'edit', 'delete']],
        'attendance' => ['label' => 'Attendance', 'abilities' => ['view', 'create', 'edit', 'delete', 'approve']],
        'devices' => ['label' => 'Biometric Devices', 'abilities' => ['view', 'create', 'edit', 'delete']],
        'leave' => ['label' => 'Leave', 'abilities' => ['view', 'create', 'edit', 'delete', 'approve']],
        'payroll' => ['label' => 'Payroll', 'abilities' => ['view', 'create', 'edit', 'delete', 'approve']],
        'branches' => ['label' => 'Branches', 'abilities' => ['view', 'create', 'edit', 'delete']],
        'departments' => ['label' => 'Departments', 'abilities' => ['view', 'create', 'edit', 'delete']],
        'positions' => ['label' => 'Positions', 'abilities' => ['view', 'create', 'edit', 'delete']],
        'reports' => ['label' => 'Reports', 'abilities' => ['view']],
        'roles' => ['label' => 'Roles & Permissions', 'abilities' => ['view', 'create', 'edit', 'delete']],
        'settings' => ['label' => 'Settings', 'abilities' => ['view', 'edit']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    |
    | Seeded roles and the permissions granted to each. Use '*' to grant every
    | permission, or a map of module => [abilities].
    |
    */

    'roles' => [

        'Super Admin' => '*',

        'HR Officer' => [
            'employees' => ['view', 'create', 'edit', 'delete'],
            'attendance' => ['view', 'create', 'edit', 'approve'],
            'devices' => ['view', 'create', 'edit', 'delete'],
            'leave' => ['view', 'create', 'edit', 'approve'],
            'payroll' => ['view', 'create', 'edit'],
            'branches' => ['view'],
            'departments' => ['view', 'create', 'edit', 'delete'],
            'positions' => ['view', 'create', 'edit', 'delete'],
            'reports' => ['view'],
            'settings' => ['view', 'edit'],
        ],

        'Branch Manager' => [
            'employees' => ['view'],
            'attendance' => ['view', 'approve'],
            'devices' => ['view'],
            'leave' => ['view', 'approve'],
            'payroll' => ['view'],
            'branches' => ['view'],
            'departments' => ['view'],
            'positions' => ['view'],
            'reports' => ['view'],
            'settings' => ['view'],
        ],

        'Employee' => [
            'attendance' => ['view'],
            'leave' => ['view', 'create'],
            'payroll' => ['view'],
        ],

    ],

];
