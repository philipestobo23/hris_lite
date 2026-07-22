<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Branch, HolidayFormData, HolidayTypeOption } from '@/types';
import Form from './Form.vue';

defineProps<{
    branches: Pick<Branch, 'id' | 'name'>[];
    types: HolidayTypeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Holidays', href: '/holidays' },
            { title: 'New', href: '/holidays/create' },
        ],
    },
});

const form = useForm<HolidayFormData>({
    branch_id: null,
    date: '',
    name: '',
    type: 'regular',
    pay_rule: 2,
});

function submit(): void {
    form.post('/holidays');
}
</script>

<template>
    <Head title="New holiday" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New holiday"
            description="Add a holiday and how it should be paid."
        />
        <Form
            :form="form"
            :branches="branches"
            :types="types"
            submit-label="Create holiday"
            @submit="submit"
        />
    </div>
</template>
