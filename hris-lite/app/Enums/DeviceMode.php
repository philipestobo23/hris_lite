<?php

namespace App\Enums;

/**
 * How a biometric terminal exchanges attendance data with the server.
 *
 * Push: the device posts logs to the server (ZKTeco ADMS/push protocol).
 * Pull: the server polls the device over its SDK/TCP port to fetch logs.
 */
enum DeviceMode: string
{
    case Push = 'push';
    case Pull = 'pull';

    public function label(): string
    {
        return match ($this) {
            self::Push => 'Push (device → server)',
            self::Pull => 'Pull (server polls device)',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode): array => ['value' => $mode->value, 'label' => $mode->label()],
            self::cases(),
        );
    }
}
