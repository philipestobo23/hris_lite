<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

defineProps<{
    label: string;
    for?: string;
    error?: string;
    hint?: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div class="grid gap-2" :class="$props.class">
        <Label :for="$props.for">{{ label }}</Label>
        <slot />
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <InputError :message="error" />
    </div>
</template>
