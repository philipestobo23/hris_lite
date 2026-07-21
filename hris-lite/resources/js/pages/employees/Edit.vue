<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Employee, EmployeeFormData, SelectOption } from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    employee: Employee;
    branches: { id: number; name: string }[];
    departments: { id: number; name: string; branch_id: number | null }[];
    positions: { id: number; name: string; department_id: number }[];
    employmentStatuses: SelectOption[];
    salaryTypes: SelectOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Employees', href: '/employees' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const toDateInput = (value: string | null): string | null =>
    value ? value.substring(0, 10) : null;

const form = useForm<EmployeeFormData>({
    first_name: props.employee.first_name,
    middle_name: props.employee.middle_name,
    last_name: props.employee.last_name,
    date_of_birth: toDateInput(props.employee.date_of_birth),
    gender: props.employee.gender,
    civil_status: props.employee.civil_status,
    nationality: props.employee.nationality,
    photo: null,
    branch_id: props.employee.branch_id,
    department_id: props.employee.department_id,
    position_id: props.employee.position_id,
    employment_status: props.employee.employment_status,
    hire_date: toDateInput(props.employee.hire_date),
    biometric_id: props.employee.biometric_id,
    email: props.employee.email,
    phone: props.employee.phone,
    address: props.employee.address,
    emergency_contact_name: props.employee.emergency_contact_name,
    emergency_contact_phone: props.employee.emergency_contact_phone,
    emergency_contact_relationship: props.employee.emergency_contact_relationship,
    sss_no: props.employee.sss_no,
    tin_no: props.employee.tin_no,
    philhealth_no: props.employee.philhealth_no,
    pagibig_no: props.employee.pagibig_no,
    salary_type: props.employee.salary_type,
    basic_salary: props.employee.basic_salary,
    allowance: props.employee.allowance,
    bank_name: props.employee.bank_name,
    bank_account_no: props.employee.bank_account_no,
});

function submit(): void {
    form.put(`/employees/${props.employee.id}`);
}
</script>

<template>
    <Head :title="`Edit ${employee.full_name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="employee.full_name"
            :description="`Employee ${employee.employee_no}`"
        />
        <Form
            :form="form"
            mode="edit"
            :employee="employee"
            :branches="branches"
            :departments="departments"
            :positions="positions"
            :employment-statuses="employmentStatuses"
            :salary-types="salaryTypes"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
