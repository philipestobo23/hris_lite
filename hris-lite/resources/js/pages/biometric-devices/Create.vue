<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type {
    BiometricDeviceFormData,
    Branch,
    SelectOption,
} from '@/types';
import Form from './Form.vue';

defineProps<{
    branches: Pick<Branch, 'id' | 'name'>[];
    models: SelectOption[];
    modes: SelectOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Biometric Devices', href: '/biometric-devices' },
            { title: 'New', href: '/biometric-devices/create' },
        ],
    },
});

const form = useForm<BiometricDeviceFormData>({
    branch_id: null,
    name: '',
    model: '',
    ip_address: '',
    port: 4370,
    serial_number: '',
    mode: 'push',
    is_active: true,
});

function submit(): void {
    form.post('/biometric-devices');
}
</script>

<template>
    <Head title="New device" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="New device"
            description="Register a biometric attendance terminal."
        />
        <Form
            :form="form"
            :branches="branches"
            :models="models"
            :modes="modes"
            submit-label="Create device"
            @submit="submit"
        />
    </div>
</template>
