<?php

namespace App\Enums;

enum LeaveType: string
{
    case Vacation = 'vacation';
    case Sick = 'sick';
    case Emergency = 'emergency';
    case Maternity = 'maternity';
    case Paternity = 'paternity';
    case Unpaid = 'unpaid';

    public function label(): string
    {
        return match ($this) {
            self::Vacation => 'Vacation Leave',
            self::Sick => 'Sick Leave',
            self::Emergency => 'Emergency Leave',
            self::Maternity => 'Maternity Leave',
            self::Paternity => 'Paternity Leave',
            self::Unpaid => 'Leave Without Pay',
        };
    }

    /** Whether this leave type is paid by default. */
    public function isPaid(): bool
    {
        return $this !== self::Unpaid;
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
