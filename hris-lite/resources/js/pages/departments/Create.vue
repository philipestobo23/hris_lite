<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Branch, DepartmentFormData } from '@/types';
import Form from './Form.vue';

defineProps<{
    branches: Pick<Branch, 'id' | 'name'>[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Departments', href: '/departments' },
            { title: 'New', href: '/departments/create' },
        ],
    },
});

const form = useForm<DepartmentFormData>({
    branch_id: null,
    name: '',
    is_active: true,
});

function submit(): void {
    form.post('/departments');
}
</script>

<template>
    <Head title="New department" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New department"
            description="Create a department."
        />
        <Form
            :form="form"
            :branches="branches"
            submit-label="Create department"
            @submit="submit"
        />
    </div>
</template>
