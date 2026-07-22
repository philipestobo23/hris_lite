<?php

namespace App\Http\Requests;

use App\Enums\HolidayType;
use App\Models\Holiday;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HolidayRequest extends FormRequest
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
        $holiday = $this->route('holiday');
        $holidayId = $holiday instanceof Holiday ? $holiday->id : null;

        return [
            // Nullable: a holiday with no branch applies company-wide.
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'date' => [
                'required',
                'date',
                // One holiday per date within the same branch scope. NULL
                // branch_id is treated as its own ("company-wide") scope.
                Rule::unique('holidays', 'date')
                    ->where(fn ($query) => $query->where('branch_id', $this->input('branch_id'))->whereNull('deleted_at'))
                    ->ignore($holidayId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(HolidayType::class)],
            'pay_rule' => ['required', 'numeric', 'min:0', 'max:99.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.unique' => 'A holiday already exists on this date for the selected branch scope.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_id' => $this->filled('branch_id') ? $this->integer('branch_id') : null,
        ]);
    }
}
