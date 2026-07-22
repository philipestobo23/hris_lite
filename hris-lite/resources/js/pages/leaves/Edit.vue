<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Employee, Leave, LeaveFormData, SelectOption } from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    leave: Leave;
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
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<LeaveFormData>({
    employee_id: props.leave.employee_id,
    type: props.leave.type,
    // Cast dates arrive as ISO timestamps — trim to the Y-m-d a date input wants.
    start_date: props.leave.start_date.slice(0, 10),
    end_date: props.leave.end_date.slice(0, 10),
    is_paid: props.leave.is_paid,
    reason: props.leave.reason ?? '',
});

function submit(): void {
    form.put(`/leaves/${props.leave.id}`);
}
</script>

<template>
    <Head title="Edit leave" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="Edit leave"
            description="Update this pending leave filing."
        />
        <Form
            :form="form"
            :employees="employees"
            :types="types"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
