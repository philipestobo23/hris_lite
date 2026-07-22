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
import type { Employee, LeaveFormData, LeaveType, SelectOption } from '@/types';

type EmployeeOption = Pick<
    Employee,
    'id' | 'first_name' | 'last_name' | 'employee_no'
>;

const props = defineProps<{
    form: InertiaForm<LeaveFormData>;
    employees: EmployeeOption[];
    types: SelectOption[];
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

// Reka's Select works with string values, so proxy the numeric employee id.
const employeeProxy = computed<string>({
    get: () =>
        props.form.employee_id == null ? '' : String(props.form.employee_id),
    set: (value) => {
        props.form.employee_id = value === '' ? null : Number(value);
    },
});

// Unpaid leave is never paid; everything else defaults to paid but stays
// overridable for cases like exhausted credits.
const typeProxy = computed<string>({
    get: () => props.form.type,
    set: (value) => {
        props.form.type = value as LeaveType;
        props.form.is_paid = value !== 'unpaid';
    },
});

/** Inclusive day count, mirroring what the server derives. */
const days = computed<number | null>(() => {
    if (!props.form.start_date || !props.form.end_date) {
        return null;
    }

    const start = new Date(props.form.start_date);
    const end = new Date(props.form.end_date);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return null;
    }

    const diff = Math.round(
        (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24),
    );

    return diff < 0 ? null : diff + 1;
});
</script>

<template>
    <form class="flex max-w-xl flex-col gap-6" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="employee">Employee</Label>
            <Select v-model="employeeProxy">
                <SelectTrigger id="employee" class="w-full">
                    <SelectValue placeholder="Select an employee" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="employee in employees"
                        :key="employee.id"
                        :value="String(employee.id)"
                    >
                        {{ employee.first_name }} {{ employee.last_name }}
                        <span class="text-muted-foreground">
                            ({{ employee.employee_no }})
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.employee_id" />
        </div>

        <div class="grid gap-2">
            <Label for="type">Leave type</Label>
            <Select v-model="typeProxy">
                <SelectTrigger id="type" class="w-full">
                    <SelectValue placeholder="Select a leave type" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in types"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.type" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="start_date">From</Label>
                <Input
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    required
                />
                <InputError :message="form.errors.start_date" />
            </div>
            <div class="grid gap-2">
                <Label for="end_date">To</Label>
                <Input
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    required
                />
                <InputError :message="form.errors.end_date" />
            </div>
        </div>

        <p v-if="days !== null" class="-mt-2 text-sm text-muted-foreground">
            {{ days }} day{{ days === 1 ? '' : 's' }} inclusive.
        </p>

        <div class="flex items-center gap-2">
            <Checkbox id="is_paid" v-model="form.is_paid" />
            <Label for="is_paid" class="font-normal">Paid leave</Label>
        </div>
        <InputError :message="form.errors.is_paid" />

        <div class="grid gap-2">
            <Label for="reason">
                Reason
                <span class="text-muted-foreground">(optional)</span>
            </Label>
            <textarea
                id="reason"
                v-model="form.reason"
                rows="3"
                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />
            <InputError :message="form.errors.reason" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link href="/leaves">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
