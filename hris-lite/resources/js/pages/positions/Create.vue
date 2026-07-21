<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Department, PositionFormData } from '@/types';
import Form from './Form.vue';

defineProps<{
    departments: Department[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Positions', href: '/positions' },
            { title: 'New', href: '/positions/create' },
        ],
    },
});

const form = useForm<PositionFormData>({
    department_id: null,
    name: '',
    is_active: true,
});

function submit(): void {
    form.post('/positions');
}
</script>

<template>
    <Head title="New position" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New position"
            description="Create a job position."
        />
        <Form
            :form="form"
            :departments="departments"
            submit-label="Create position"
            @submit="submit"
        />
    </div>
</template>
