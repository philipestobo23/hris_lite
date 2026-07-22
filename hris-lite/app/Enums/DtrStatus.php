<?php

namespace App\Enums;

/**
 * How a single day resolved for an employee once their punches were processed.
 */
enum DtrStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case OnLeave = 'on_leave';
    case Holiday = 'holiday';
    case RestDay = 'rest_day';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::OnLeave => 'On Leave',
            self::Holiday => 'Holiday',
            self::RestDay => 'Rest Day',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
