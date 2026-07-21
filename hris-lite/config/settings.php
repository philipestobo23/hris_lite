<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings schema
    |--------------------------------------------------------------------------
    |
    | Drives seeding of defaults, the settings page UI, and server-side casting.
    | Each setting's full key is "{group}.{field}", e.g. "payroll.ot_multiplier".
    |
    | field types: number | text | textarea | select | image
    | field cast:  int | float | string
    |
    */

    'groups' => [

        'payroll' => [
            'label' => 'Payroll',
            'description' => 'Rules used by attendance and payroll computations.',
            'fields' => [
                'grace_period' => [
                    'label' => 'Grace period (minutes)',
                    'type' => 'number',
                    'cast' => 'int',
                    'default' => 15,
                    'min' => 0,
                    'help' => 'Late minutes tolerated before deductions apply.',
                ],
                'ot_multiplier' => [
                    'label' => 'Overtime multiplier',
                    'type' => 'number',
                    'cast' => 'float',
                    'default' => 1.25,
                    'min' => 1,
                    'step' => 0.05,
                    'help' => 'Hourly-rate multiplier for overtime hours.',
                ],
                'night_diff_pct' => [
                    'label' => 'Night differential (%)',
                    'type' => 'number',
                    'cast' => 'float',
                    'default' => 10,
                    'min' => 0,
                    'max' => 100,
                ],
                'cutoff_type' => [
                    'label' => 'Cutoff type',
                    'type' => 'select',
                    'cast' => 'string',
                    'default' => 'semi_monthly',
                    'options' => [
                        'weekly' => 'Weekly',
                        'semi_monthly' => 'Semi-monthly',
                        'monthly' => 'Monthly',
                    ],
                ],
            ],
        ],

        'company' => [
            'label' => 'Company',
            'description' => 'Details shown on documents and payslips.',
            'fields' => [
                'name' => [
                    'label' => 'Company name',
                    'type' => 'text',
                    'cast' => 'string',
                    'default' => 'HRIS Lite',
                ],
                'logo' => [
                    'label' => 'Logo',
                    'type' => 'image',
                    'cast' => 'string',
                    'default' => null,
                    'help' => 'PNG or JPG, up to 2 MB.',
                ],
                'address' => [
                    'label' => 'Address',
                    'type' => 'textarea',
                    'cast' => 'string',
                    'default' => '',
                ],
            ],
        ],

        'leave' => [
            'label' => 'Leave',
            'description' => 'Default annual entitlements granted to new employees.',
            'fields' => [
                'vacation_days' => [
                    'label' => 'Vacation days',
                    'type' => 'number',
                    'cast' => 'int',
                    'default' => 15,
                    'min' => 0,
                ],
                'sick_days' => [
                    'label' => 'Sick days',
                    'type' => 'number',
                    'cast' => 'int',
                    'default' => 15,
                    'min' => 0,
                ],
            ],
        ],

    ],

];
