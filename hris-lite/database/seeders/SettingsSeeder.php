<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsManager;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('settings.groups') as $group => $definition) {
            foreach ($definition['fields'] as $field => $config) {
                // firstOrCreate: never clobber an already-customized value.
                Setting::firstOrCreate(
                    ['key' => "{$group}.{$field}"],
                    ['group' => $group, 'value' => $config['default'] ?? null],
                );
            }
        }

        app(SettingsManager::class)->flush();
    }
}
