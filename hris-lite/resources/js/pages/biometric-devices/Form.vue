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
import type {
    BiometricDeviceFormData,
    Branch,
    SelectOption,
} from '@/types';

const props = defineProps<{
    form: InertiaForm<BiometricDeviceFormData>;
    branches: Pick<Branch, 'id' | 'name'>[];
    models: SelectOption[];
    modes: SelectOption[];
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

// Reka's Select works with string values, so proxy the numeric branch id.
const branchProxy = computed<string>({
    get: () => (props.form.branch_id == null ? '' : String(props.form.branch_id)),
    set: (value) => {
        props.form.branch_id = value === '' ? null : Number(value);
    },
});
</script>

<template>
    <form class="flex max-w-xl flex-col gap-6" @submit.prevent="emit('submit')">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autofocus
                placeholder="e.g. Main Entrance Terminal"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="branch">Branch</Label>
            <Select v-model="branchProxy">
                <SelectTrigger id="branch" class="w-full">
                    <SelectValue placeholder="Select a branch" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="branch in branches"
                        :key="branch.id"
                        :value="String(branch.id)"
                    >
                        {{ branch.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.branch_id" />
        </div>

        <div class="grid gap-2">
            <Label for="model">Model</Label>
            <Select v-model="form.model">
                <SelectTrigger id="model" class="w-full">
                    <SelectValue placeholder="Select a model" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in models"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.model" />
        </div>

        <div class="grid gap-4 sm:grid-cols-[1fr_auto]">
            <div class="grid gap-2">
                <Label for="ip_address">
                    IP address
                    <span class="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="ip_address"
                    v-model="form.ip_address"
                    placeholder="e.g. 192.168.1.201"
                />
                <InputError :message="form.errors.ip_address" />
            </div>

            <div class="grid gap-2">
                <Label for="port">Port</Label>
                <Input
                    id="port"
                    v-model.number="form.port"
                    type="number"
                    min="1"
                    max="65535"
                    class="sm:w-28"
                />
                <InputError :message="form.errors.port" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="serial_number">
                Serial number
                <span class="text-muted-foreground">(optional)</span>
            </Label>
            <Input
                id="serial_number"
                v-model="form.serial_number"
                placeholder="e.g. AGL7223900045"
            />
            <InputError :message="form.errors.serial_number" />
        </div>

        <div class="grid gap-2">
            <Label for="mode">Mode</Label>
            <Select v-model="form.mode">
                <SelectTrigger id="mode" class="w-full">
                    <SelectValue placeholder="Select a mode" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in modes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">
                Push: the device posts logs to the server. Pull: the server
                polls the device to collect them.
            </p>
            <InputError :message="form.errors.mode" />
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
                <Link href="/biometric-devices">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
