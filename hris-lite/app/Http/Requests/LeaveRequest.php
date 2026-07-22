<?php

namespace App\Http\Requests;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LeaveRequest extends FormRequest
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
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_paid' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->overlapsExistingLeave()) {
                $validator->errors()->add(
                    'start_date',
                    'This employee already has a leave filed that overlaps these dates.',
                );
            }
        });
    }

    /**
     * The employee's branch and the inclusive day count, derived rather than
     * trusted from the client.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $employee = Employee::query()->findOrFail($this->integer('employee_id'));

        $start = Carbon::parse($this->date('start_date'))->startOfDay();
        $end = Carbon::parse($this->date('end_date'))->startOfDay();

        return [
            ...$this->safe()->all(),
            'branch_id' => $employee->branch_id,
            'days' => $start->diffInDays($end) + 1,
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = LeaveType::tryFrom((string) $this->input('type'));

        $this->merge([
            // Paid-ness follows the type unless the form explicitly says otherwise.
            'is_paid' => $this->has('is_paid')
                ? $this->boolean('is_paid')
                : ($type?->isPaid() ?? true),
        ]);
    }

    /**
     * Pending or approved filings that cover any of the requested days.
     */
    private function overlapsExistingLeave(): bool
    {
        $current = $this->route('leave');
        $currentId = $current instanceof Leave ? $current->id : null;

        return Leave::query()
            ->where('employee_id', $this->integer('employee_id'))
            ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
            ->when($currentId, fn ($query) => $query->whereKeyNot($currentId))
            ->where('start_date', '<=', $this->date('end_date'))
            ->where('end_date', '>=', $this->date('start_date'))
            ->exists();
    }
}
