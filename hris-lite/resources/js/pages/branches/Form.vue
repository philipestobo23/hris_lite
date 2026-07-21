<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BranchFormData } from '@/types';

defineProps<{
    form: InertiaForm<BranchFormData>;
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();
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
                placeholder="e.g. Head Office"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="code">
                Code
                <span class="text-muted-foreground">(optional)</span>
            </Label>
            <Input id="code" v-model="form.code" placeholder="e.g. HO-001" />
            <InputError :message="form.errors.code" />
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
                <Link href="/branches">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
