<?php

namespace App\Http\Controllers;

use App\Services\SettingsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request, SettingsManager $settings): Response
    {
        abort_unless($request->user()->can('settings.view'), 403);

        $groups = collect(config('settings.groups'))
            ->map(fn (array $group, string $key): array => [
                'key' => $key,
                'label' => $group['label'],
                'description' => $group['description'] ?? null,
                'fields' => collect($group['fields'])
                    ->map(fn (array $field, string $fieldKey): array => [
                        'key' => $fieldKey,
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'help' => $field['help'] ?? null,
                        'min' => $field['min'] ?? null,
                        'max' => $field['max'] ?? null,
                        'step' => $field['step'] ?? null,
                        'options' => isset($field['options'])
                            ? collect($field['options'])
                                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                                ->values()
                            : null,
                    ])
                    ->values(),
            ])
            ->values();

        $values = [];
        foreach (config('settings.groups') as $group => $definition) {
            foreach ($definition['fields'] as $field => $config) {
                $values[$group][$field] = $settings->get("{$group}.{$field}");
            }
        }

        return Inertia::render('app-settings/Index', [
            'groups' => $groups,
            'values' => $values,
        ]);
    }

    public function update(Request $request, SettingsManager $settings): RedirectResponse
    {
        abort_unless($request->user()->can('settings.edit'), 403);

        $request->validate([
            'payroll.grace_period' => ['required', 'integer', 'min:0'],
            'payroll.ot_multiplier' => ['required', 'numeric', 'min:1'],
            'payroll.night_diff_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'payroll.cutoff_type' => ['required', 'in:weekly,semi_monthly,monthly'],
            'company.name' => ['required', 'string', 'max:255'],
            'company.address' => ['nullable', 'string', 'max:1000'],
            'leave.vacation_days' => ['required', 'integer', 'min:0'],
            'leave.sick_days' => ['required', 'integer', 'min:0'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        foreach (config('settings.groups') as $group => $definition) {
            foreach ($definition['fields'] as $field => $config) {
                if ($config['type'] === 'image') {
                    continue; // handled via the uploaded file below
                }

                $key = "{$group}.{$field}";

                if (! $request->has($key)) {
                    continue;
                }

                $settings->set($key, $this->cast($request->input($key), $config['cast'] ?? 'string'));
            }
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $settings->set('company.logo', Storage::disk('public')->url($path));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Settings saved.')]);

        return back();
    }

    private function cast(mixed $value, string $cast): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($cast) {
            'int' => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }
}
