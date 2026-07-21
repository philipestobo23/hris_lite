<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { EmployeeFormData, SelectOption } from '@/types';
import Form from './Form.vue';

defineProps<{
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
            { title: 'New', href: '/employees/create' },
        ],
    },
});

const form = useForm<EmployeeFormData>({
    first_name: '',
    middle_name: null,
    last_name: '',
    date_of_birth: null,
    gender: null,
    civil_status: null,
    nationality: null,
    photo: null,
    branch_id: null,
    department_id: null,
    position_id: null,
    employment_status: 'probationary',
    hire_date: null,
    biometric_id: null,
    email: null,
    phone: null,
    address: null,
    emergency_contact_name: null,
    emergency_contact_phone: null,
    emergency_contact_relationship: null,
    sss_no: null,
    tin_no: null,
    philhealth_no: null,
    pagibig_no: null,
    salary_type: null,
    basic_salary: null,
    allowance: null,
    bank_name: null,
    bank_account_no: null,
});

function submit(): void {
    form.post('/employees');
}
</script>

<template>
    <Head title="New employee" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New employee"
            description="Fill in each tab, then save."
        />
        <Form
            :form="form"
            mode="create"
            :branches="branches"
            :departments="departments"
            :positions="positions"
            :employment-statuses="employmentStatuses"
            :salary-types="salaryTypes"
            submit-label="Create employee"
            @submit="submit"
        />
    </div>
</template>
