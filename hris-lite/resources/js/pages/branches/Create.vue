<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { BranchFormData } from '@/types';
import Form from './Form.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Branches', href: '/branches' },
            { title: 'New', href: '/branches/create' },
        ],
    },
});

const form = useForm<BranchFormData>({
    name: '',
    code: '',
    is_active: true,
});

function submit(): void {
    form.post('/branches');
}
</script>

<template>
    <Head title="New branch" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New branch"
            description="Create a company branch."
        />
        <Form :form="form" submit-label="Create branch" @submit="submit" />
    </div>
</template>
