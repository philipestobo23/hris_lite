<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    Branch,
    HolidayFormData,
    HolidayType,
    HolidayTypeOption,
} from '@/types';

const props = defineProps<{
    form: InertiaForm<HolidayFormData>;
    branches: Pick<Branch, 'id' | 'name'>[];
    types: HolidayTypeOption[];
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

// Reka's Select works with string values, so proxy the numeric branch id.
// The "company-wide" sentinel maps to null (holiday applies to every branch).
const COMPANY_WIDE = 'company';
const branchProxy = computed<string>({
    get: () =>
        props.form.branch_id == null
            ? COMPANY_WIDE
            : String(props.form.branch_id),
    set: (value) => {
        props.form.branch_id = value === COMPANY_WIDE ? null : Number(value);
    },
});

// Picking a type fills in that type's standard pay rule; the field stays
// editable so a company can override it.
function onTypeChange(value: string): void {
    props.form.type = value as HolidayType;

    const match = props.types.find((type) => type.value === value);

    if (match) {
        props.form.pay_rule = match.pay_rule;
    }
}

const typeProxy = computed<string>({
    get: () => props.form.type,
    set: onTypeChange,
});

const payRulePercent = computed(() => {
    const value = Number(props.form.pay_rule);

    return Number.isFinite(value) ? `${Math.round(value * 100)}%` : '—';
});
</script>

<template>
    <form class="flex max-w-xl flex-col gap-6" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="date">Date</Label>
            <Input id="date" v-model="form.date" type="date" required autofocus />
            <InputError :message="form.errors.date" />
        </div>

        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="e.g. Independence Day"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="type">Type</Label>
            <Select v-model="typeProxy">
                <SelectTrigger id="type" class="w-full">
                    <SelectValue placeholder="Select a holiday type" />
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

        <div class="grid gap-2">
            <Label for="branch">Branch scope</Label>
            <Select v-model="branchProxy">
                <SelectTrigger id="branch" class="w-full">
                    <SelectValue placeholder="Select a scope" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="COMPANY_WIDE">
                        Company-wide (all branches)
                    </SelectItem>
                    <SelectItem
                        v-for="branch in branches"
                        :key="branch.id"
                        :value="String(branch.id)"
                    >
                        {{ branch.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">
                Limit to one branch for a local holiday, e.g. a town fiesta.
            </p>
            <InputError :message="form.errors.branch_id" />
        </div>

        <div class="grid gap-2">
            <Label for="pay_rule">Pay rule</Label>
            <div class="flex items-center gap-3">
                <Input
                    id="pay_rule"
                    v-model.number="form.pay_rule"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-32"
                    required
                />
                <span class="text-sm text-muted-foreground">
                    ×  rate = <span class="font-medium">{{ payRulePercent }}</span>
                    of the daily rate for hours worked
                </span>
            </div>
            <p class="text-xs text-muted-foreground">
                Auto-filled from the type — adjust it if your company pays a
                different rate.
            </p>
            <InputError :message="form.errors.pay_rule" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link href="/holidays">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
