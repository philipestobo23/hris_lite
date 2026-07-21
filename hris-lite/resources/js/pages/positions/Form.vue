<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Department, PositionFormData } from '@/types';

const props = defineProps<{
    form: InertiaForm<PositionFormData>;
    departments: Department[];
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

const departmentProxy = computed<string>({
    get: () =>
        props.form.department_id == null
            ? ''
            : String(props.form.department_id),
    set: (value) => {
        props.form.department_id = value === '' ? null : Number(value);
    },
});

function departmentLabel(department: Department): string {
    const scope = department.branch ? department.branch.name : 'Shared';
    return `${department.name} · ${scope}`;
}
</script>

<template>
    <form class="flex max-w-xl flex-col gap-6" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="department">Department</Label>
            <Select v-model="departmentProxy">
                <SelectTrigger id="department" class="w-full">
                    <SelectValue placeholder="Select a department" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="department in departments"
                        :key="department.id"
                        :value="String(department.id)"
                    >
                        {{ departmentLabel(department) }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.department_id" />
        </div>

        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autofocus
                placeholder="e.g. HR Officer"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox id="is_active" v-model="form.is_active" />
            <Label for="is_active" class="font-normal">Active</Label>
        </div>
        <InputError :message="form.errors.is_active" />

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link href="/positions">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
