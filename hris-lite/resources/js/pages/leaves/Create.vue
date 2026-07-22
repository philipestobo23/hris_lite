<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Employee, LeaveFormData, SelectOption } from '@/types';
import Form from './Form.vue';

defineProps<{
    employees: Pick<
        Employee,
        'id' | 'first_name' | 'last_name' | 'employee_no'
    >[];
    types: SelectOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Leave', href: '/leaves' },
            { title: 'File leave', href: '/leaves/create' },
        ],
    },
});

const form = useForm<LeaveFormData>({
    employee_id: null,
    type: 'vacation',
    start_date: '',
    end_date: '',
    is_paid: true,
    reason: '',
});

function submit(): void {
    form.post('/leaves');
}
</script>

<template>
    <Head title="File leave" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="File leave"
            description="Record a leave request for approval."
        />
        <Form
            :form="form"
            :employees="employees"
            :types="types"
            submit-label="File leave"
            @submit="submit"
        />
    </div>
</template>
