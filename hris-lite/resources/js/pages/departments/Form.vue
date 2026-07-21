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
import type { Branch, DepartmentFormData } from '@/types';

const props = defineProps<{
    form: InertiaForm<DepartmentFormData>;
    branches: Pick<Branch, 'id' | 'name'>[];
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

// Reka's Select works with string values, so proxy the numeric branch id.
// The "shared" sentinel maps to null (department shared across branches).
const SHARED = 'shared';
const branchProxy = computed<string>({
    get: () =>
        props.form.branch_id == null ? SHARED : String(props.form.branch_id),
    set: (value) => {
        props.form.branch_id = value === SHARED ? null : Number(value);
    },
});
</script>

<template>
    <form class="flex max-w-xl flex-col gap-6" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="branch">Branch</Label>
            <Select v-model="branchProxy">
                <SelectTrigger id="branch" class="w-full">
                    <SelectValue placeholder="Select a branch" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="SHARED">
                        Shared (all branches)
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
                Leave as “Shared” for a department that spans every branch.
            </p>
            <InputError :message="form.errors.branch_id" />
        </div>

        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autofocus
                placeholder="e.g. Human Resources"
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
                <Link href="/departments">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
