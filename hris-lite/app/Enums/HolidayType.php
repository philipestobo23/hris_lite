<?php

namespace App\Enums;

enum HolidayType: string
{
    case Regular = 'regular';
    case SpecialNonWorking = 'special_non_working';
    case SpecialWorking = 'special_working';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular Holiday',
            self::SpecialNonWorking => 'Special (Non-Working)',
            self::SpecialWorking => 'Special (Working)',
        };
    }

    /**
     * Default multiplier applied to hours worked on this holiday, following
     * the usual Philippine rates. Stored per-holiday so it stays overridable.
     */
    public function defaultPayRule(): float
    {
        return match ($this) {
            self::Regular => 2.00,           // 200%
            self::SpecialNonWorking => 1.30, // 130%
            self::SpecialWorking => 1.00,    // ordinary day rate
        };
    }

    /**
     * @return list<array{value: string, label: string, pay_rule: float}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'pay_rule' => $type->defaultPayRule(),
            ],
            self::cases(),
        );
    }
}
