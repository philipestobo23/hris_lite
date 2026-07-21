<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Branch, Department, DepartmentFormData } from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    department: Department;
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Departments', href: '/departments' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<DepartmentFormData>({
    branch_id: props.department.branch_id,
    name: props.department.name,
    is_active: props.department.is_active,
});

function submit(): void {
    form.put(`/departments/${props.department.id}`);
}
</script>

<template>
    <Head :title="`Edit ${department.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="`Edit ${department.name}`"
            description="Update this department's details."
        />
        <Form
            :form="form"
            :branches="branches"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
