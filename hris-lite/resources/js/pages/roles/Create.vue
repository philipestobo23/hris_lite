<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type { PermissionAbility, PermissionModule, RoleFormData } from '@/types';
import Form from './Form.vue';

defineProps<{
    modules: PermissionModule[];
    abilities: PermissionAbility[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Roles & Permissions', href: '/roles' },
            { title: 'New', href: '/roles/create' },
        ],
    },
});

const form = useForm<RoleFormData>({
    name: '',
    permissions: [],
});

function submit(): void {
    form.post('/roles');
}
</script>

<template>
    <Head title="New role" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New role"
            description="Create a role and assign its permissions."
        />
        <Form
            :form="form"
            :modules="modules"
            :abilities="abilities"
            submit-label="Create role"
            @submit="submit"
        />
    </div>
</template>
