<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Central read/write access to persisted key/value settings, backed by a
 * forever cache so reads don't hit the database on every request.
 */
class SettingsManager
{
    private const CACHE_KEY = 'settings.all';

    /** @var array<string, mixed>|null */
    private ?array $items = null;

    /**
     * All settings as a flat "group.key" => value map (cached).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->items === null) {
            $this->items = Cache::rememberForever(
                self::CACHE_KEY,
                fn (): array => Setting::query()
                    ->get(['key', 'value'])
                    ->pluck('value', 'key')
                    ->all(),
            );
        }

        return $this->items;
    }

    /**
     * Get a setting by full key ("group.field"). Falls back to the provided
     * default, then to the schema default from config/settings.php.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $items = $this->all();

        if (array_key_exists($key, $items)) {
            return $items[$key];
        }

        return $default ?? $this->schemaDefault($key);
    }

    /**
     * All settings within a group as a "field" => value map.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $prefix = $group.'.';
        $result = [];

        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $result[substr($key, strlen($prefix))] = $value;
            }
        }

        return $result;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => explode('.', $key)[0], 'value' => $value],
        );

        $this->flush();
    }

    /**
     * Persist many settings at once.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => explode('.', $key)[0], 'value' => $value],
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->items = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function schemaDefault(string $key): mixed
    {
        [$group, $field] = array_pad(explode('.', $key, 2), 2, null);

        return config("settings.groups.{$group}.fields.{$field}.default");
    }
}
