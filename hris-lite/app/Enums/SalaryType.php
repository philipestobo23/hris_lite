<?php

namespace App\Enums;

enum SalaryType: string
{
    case Monthly = 'monthly';
    case Daily = 'daily';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Daily => 'Daily',
            self::Hourly => 'Hourly',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
