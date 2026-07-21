<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { Branch, BranchFormData } from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    branch: Branch;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Branches', href: '/branches' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<BranchFormData>({
    name: props.branch.name,
    code: props.branch.code ?? '',
    is_active: props.branch.is_active,
});

function submit(): void {
    form.put(`/branches/${props.branch.id}`);
}
</script>

<template>
    <Head :title="`Edit ${branch.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="`Edit ${branch.name}`"
            description="Update this branch's details."
        />
        <Form :form="form" submit-label="Save changes" @submit="submit" />
    </div>
</template>
