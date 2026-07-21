<?php

use App\Services\SettingsManager;

if (! function_exists('setting')) {
    /**
     * Retrieve a setting value, or the SettingsManager when called with no key.
     *
     * setting('payroll.ot_multiplier')      // => 1.25
     * setting('payroll.ot_multiplier', 1.0) // => value or 1.0 fallback
     * setting()->set('company.name', 'Acme')
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $manager = app(SettingsManager::class);

        if ($key === null) {
            return $manager;
        }

        return $manager->get($key, $default);
    }
}
