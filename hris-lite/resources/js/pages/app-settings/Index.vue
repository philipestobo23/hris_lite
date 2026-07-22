<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SettingGroup, SettingsValues } from '@/types';

const props = defineProps<{
    groups: SettingGroup[];
    values: SettingsValues;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'App Settings', href: '/app-settings' }],
    },
});

// Build the form from the schema (image fields are uploaded separately).
// Group keys are dynamic, so the form data is loosely typed.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const initial: Record<string, any> = { logo: null as File | null };
for (const group of props.groups) {
    const groupValues: Record<string, unknown> = {};
    for (const field of group.fields) {
        if (field.type === 'image') {
            continue;
        }
        groupValues[field.key] = props.values[group.key]?.[field.key] ?? null;
    }
    initial[group.key] = groupValues;
}

const form = useForm(initial);

const existingLogo = (props.values.company?.logo as string | null) ?? null;
const logoPreview = ref<string | null>(existingLogo);

function onLogoChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.logo = file;
    logoPreview.value = file ? URL.createObjectURL(file) : existingLogo;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function fieldModel(group: string): Record<string, any> {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return form[group] as Record<string, any>;
}

function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}

function submit(): void {
    form.post('/app-settings', { preserveScroll: true });
}

const textareaClass =
    'flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none transition-[color,box-shadow] placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30';
</script>

<template>
    <Head title="App Settings" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="App Settings"
            description="Configure payroll rules, company details, and leave defaults."
        />

        <form class="flex flex-col gap-6" @submit.prevent="submit">
            <Card v-for="group in groups" :key="group.key">
                <CardHeader>
                    <CardTitle>{{ group.label }}</CardTitle>
                    <CardDescription v-if="group.description">
                        {{ group.description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6 sm:grid-cols-2">
                    <div
                        v-for="field in group.fields"
                        :key="field.key"
                        class="grid gap-2"
                        :class="{
                            'sm:col-span-2':
                                field.type === 'textarea' ||
                                field.type === 'image',
                        }"
                    >
                        <Label :for="`${group.key}.${field.key}`">
                            {{ field.label }}
                        </Label>

                        <!-- number -->
                        <Input
                            v-if="field.type === 'number'"
                            :id="`${group.key}.${field.key}`"
                            v-model.number="fieldModel(group.key)[field.key]"
                            type="number"
                            :min="field.min ?? undefined"
                            :max="field.max ?? undefined"
                            :step="field.step ?? undefined"
                        />

                        <!-- text -->
                        <Input
                            v-else-if="field.type === 'text'"
                            :id="`${group.key}.${field.key}`"
                            v-model="fieldModel(group.key)[field.key]"
                            type="text"
                        />

                        <!-- time -->
                        <Input
                            v-else-if="field.type === 'time'"
                            :id="`${group.key}.${field.key}`"
                            v-model="fieldModel(group.key)[field.key]"
                            type="time"
                            class="w-40"
                        />

                        <!-- textarea -->
                        <textarea
                            v-else-if="field.type === 'textarea'"
                            :id="`${group.key}.${field.key}`"
                            v-model="fieldModel(group.key)[field.key]"
                            :class="textareaClass"
                            rows="3"
                        />

                        <!-- select -->
                        <Select
                            v-else-if="field.type === 'select'"
                            v-model="fieldModel(group.key)[field.key]"
                        >
                            <SelectTrigger
                                :id="`${group.key}.${field.key}`"
                                class="w-full"
                            >
                                <SelectValue placeholder="Select an option" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in field.options ?? []"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- image / logo -->
                        <div
                            v-else-if="field.type === 'image'"
                            class="flex items-center gap-4"
                        >
                            <img
                                v-if="logoPreview"
                                :src="logoPreview"
                                alt="Logo preview"
                                class="size-16 rounded-md border object-contain p-1"
                            />
                            <div
                                v-else
                                class="flex size-16 items-center justify-center rounded-md border text-xs text-muted-foreground"
                            >
                                None
                            </div>
                            <Input
                                :id="`${group.key}.${field.key}`"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                class="max-w-xs"
                                @change="onLogoChange"
                            />
                        </div>

                        <p v-if="field.help" class="text-xs text-muted-foreground">
                            {{ field.help }}
                        </p>
                        <InputError
                            :message="
                                field.type === 'image'
                                    ? errorFor('logo')
                                    : errorFor(`${group.key}.${field.key}`)
                            "
                        />
                    </div>
                </CardContent>
            </Card>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    Save settings
                </Button>
            </div>
        </form>
    </div>
</template>
