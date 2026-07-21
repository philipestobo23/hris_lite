<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type {
    BiometricDevice,
    BiometricDeviceFormData,
    Branch,
    SelectOption,
} from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    device: BiometricDevice;
    branches: Pick<Branch, 'id' | 'name'>[];
    models: SelectOption[];
    modes: SelectOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Biometric Devices', href: '/biometric-devices' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<BiometricDeviceFormData>({
    branch_id: props.device.branch_id,
    name: props.device.name,
    model: props.device.model,
    ip_address: props.device.ip_address ?? '',
    port: props.device.port,
    serial_number: props.device.serial_number ?? '',
    mode: props.device.mode,
    is_active: props.device.is_active,
});

function submit(): void {
    form.put(`/biometric-devices/${props.device.id}`);
}
</script>

<template>
    <Head :title="`Edit ${device.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="`Edit ${device.name}`"
            description="Update this device's details."
        />
        <Form
            :form="form"
            :branches="branches"
            :models="models"
            :modes="modes"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
