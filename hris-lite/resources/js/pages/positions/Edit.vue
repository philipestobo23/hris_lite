<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Department, Position, PositionFormData } from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    position: Position;
    departments: Department[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Positions', href: '/positions' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<PositionFormData>({
    department_id: props.position.department_id,
    name: props.position.name,
    is_active: props.position.is_active,
});

function submit(): void {
    form.put(`/positions/${props.position.id}`);
}
</script>

<template>
    <Head :title="`Edit ${position.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="`Edit ${position.name}`"
            description="Update this position's details."
        />
        <Form
            :form="form"
            :departments="departments"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
