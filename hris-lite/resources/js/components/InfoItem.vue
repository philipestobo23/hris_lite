<script setup lang="ts">
defineProps<{
    label: string;
    value?: string | number | null;
}>();
</script>

<template>
    <div class="grid gap-0.5">
        <dt class="text-xs text-muted-foreground">{{ label }}</dt>
        <dd class="text-sm">
            <slot>
                {{
                    value === null || value === undefined || value === ''
                        ? '—'
                        : value
                }}
            </slot>
        </dd>
    </div>
</template>
