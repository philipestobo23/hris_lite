<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Regular = 'regular';
    case Probationary = 'probationary';
    case Contractual = 'contractual';
    case PartTime = 'part_time';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Probationary => 'Probationary',
            self::Contractual => 'Contractual',
            self::PartTime => 'Part-time',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
        };
    }

    /** Whether the status represents an actively employed person. */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Resigned, self::Terminated], true);
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
