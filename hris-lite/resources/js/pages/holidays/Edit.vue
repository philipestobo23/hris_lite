<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import type {
    Branch,
    Holiday,
    HolidayFormData,
    HolidayTypeOption,
} from '@/types';
import Form from './Form.vue';

const props = defineProps<{
    holiday: Holiday;
    branches: Pick<Branch, 'id' | 'name'>[];
    types: HolidayTypeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Holidays', href: '/holidays' },
            { title: 'Edit', href: '#' },
        ],
    },
});

const form = useForm<HolidayFormData>({
    branch_id: props.holiday.branch_id,
    // The model casts `date`, so it arrives as an ISO timestamp — trim it to
    // the Y-m-d a date input expects.
    date: props.holiday.date.slice(0, 10),
    name: props.holiday.name,
    type: props.holiday.type,
    pay_rule: Number(props.holiday.pay_rule),
});

function submit(): void {
    form.put(`/holidays/${props.holiday.id}`);
}
</script>

<template>
    <Head :title="`Edit ${holiday.name}`" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            :title="`Edit ${holiday.name}`"
            description="Update this holiday's details."
        />
        <Form
            :form="form"
            :branches="branches"
            :types="types"
            submit-label="Save changes"
            @submit="submit"
        />
    </div>
</template>
