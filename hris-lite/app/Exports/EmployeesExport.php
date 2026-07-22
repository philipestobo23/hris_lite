<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private array $filters) {}

    /**
     * @return Builder<Employee>
     */
    public function query(): Builder
    {
        // Employee::query() applies the BranchScope, so exports respect the
        // current user's accessible branches.
        return Employee::query()
            ->with(['branch:id,name', 'department:id,name', 'position:id,name'])
            ->filter($this->filters)
            ->orderBy('last_name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Employee No',
            'Last Name',
            'First Name',
            'Middle Name',
            'Branch',
            'Department',
            'Position',
            'Employment Status',
            'Email',
            'Phone',
            'Hire Date',
            'Basic Salary',
        ];
    }

    /**
     * @param  Employee  $employee
     * @return list<mixed>
     */
    public function map($employee): array
    {
        return [
            $employee->employee_no,
            $employee->last_name,
            $employee->first_name,
            $employee->middle_name,
            $employee->branch?->name,
            $employee->department?->name,
            $employee->position?->name,
            $employee->employment_status->label(),
            $employee->email,
            $employee->phone,
            $employee->hire_date?->format('Y-m-d'),
            $employee->basic_salary,
        ];
    }
}
