<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type {
    PermissionAbility,
    PermissionModule,
    Role,
    RoleFormData,
} from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    role: Role;
    modules: PermissionModule[];
    abilities: PermissionAbility[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Roles & Permissions', href: '/roles' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<RoleFormData>({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

function submit(): void {
    form.put(`/roles/${props.role.id}`);
}
</script>

<template>
    <Head :title="`Edit ${role.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="role.is_protected ? role.name : `Edit ${role.name}`"
            description="Update this role's name and permissions."
        />
        <Form
            :form="form"
            :modules="modules"
            :abilities="abilities"
            :readonly="role.is_protected"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
