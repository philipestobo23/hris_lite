<?php

namespace App\Enums;

enum DeviceModel: string
{
    case K30 = 'K30';
    case K40 = 'K40';

    public function label(): string
    {
        return match ($this) {
            self::K30 => 'ZKTeco K30',
            self::K40 => 'ZKTeco K40',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $model): array => ['value' => $model->value, 'label' => $model->label()],
            self::cases(),
        );
    }
}
