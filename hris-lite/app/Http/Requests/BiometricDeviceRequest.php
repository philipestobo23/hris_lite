<?php

namespace App\Http\Requests;

use App\Enums\DeviceMode;
use App\Enums\DeviceModel;
use App\Models\BiometricDevice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BiometricDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $device = $this->route('biometric_device');
        $deviceId = $device instanceof BiometricDevice ? $device->id : null;

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'model' => ['required', new Enum(DeviceModel::class)],
            'ip_address' => ['nullable', 'string', 'ip'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('biometric_devices', 'serial_number')->ignore($deviceId),
            ],
            'mode' => ['required', new Enum(DeviceMode::class)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'ip_address' => filled($this->input('ip_address')) ? $this->input('ip_address') : null,
            'serial_number' => filled($this->input('serial_number')) ? $this->input('serial_number') : null,
        ]);
    }
}
