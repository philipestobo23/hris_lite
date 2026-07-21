<?php

namespace App\Http\Requests;

use App\Enums\EmploymentStatus;
use App\Enums\SalaryType;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules for every tab of the employee form. The front-end maps each field
     * back to its tab so validation errors surface on the right one.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : null;

        return [
            // Tab 1 — Personal
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'civil_status' => ['nullable', 'in:single,married,widowed,separated'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],

            // Tab 2 — Employment
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'hire_date' => ['nullable', 'date'],
            'biometric_id' => ['nullable', 'string', 'max:50'],

            // Tab 3 — Contact & Emergency
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],

            // Tab 4 — Government IDs
            'sss_no' => ['nullable', 'string', 'max:50'],
            'tin_no' => ['nullable', 'string', 'max:50'],
            'philhealth_no' => ['nullable', 'string', 'max:50'],
            'pagibig_no' => ['nullable', 'string', 'max:50'],

            // Tab 5 — Salary
            'salary_type' => ['nullable', Rule::enum(SalaryType::class)],
            'basic_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'allowance' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
