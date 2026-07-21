<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type {
    PermissionAbility,
    PermissionModule,
    RoleFormData,
} from '@/types';

const props = defineProps<{
    form: InertiaForm<RoleFormData>;
    modules: PermissionModule[];
    abilities: PermissionAbility[];
    submitLabel: string;
    readonly?: boolean;
}>();

const emit = defineEmits<{ submit: [] }>();

const selected = computed(() => new Set(props.form.permissions));

function permissionName(moduleKey: string, ability: string): string {
    return `${moduleKey}.${ability}`;
}

function supports(module: PermissionModule, ability: string): boolean {
    return module.abilities.includes(ability);
}

function has(moduleKey: string, ability: string): boolean {
    return selected.value.has(permissionName(moduleKey, ability));
}

function setPermissions(next: Set<string>): void {
    props.form.permissions = [...next];
}

function toggle(moduleKey: string, ability: string, value: boolean): void {
    if (props.readonly) {
        return;
    }
    const next = new Set(props.form.permissions);
    const name = permissionName(moduleKey, ability);
    if (value) {
        next.add(name);
    } else {
        next.delete(name);
    }
    setPermissions(next);
}

function modulePermissions(module: PermissionModule): string[] {
    return module.abilities.map((ability) =>
        permissionName(module.key, ability),
    );
}

function moduleChecked(module: PermissionModule): boolean {
    return modulePermissions(module).every((name) => selected.value.has(name));
}

function toggleModule(module: PermissionModule, value: boolean): void {
    if (props.readonly) {
        return;
    }
    const next = new Set(props.form.permissions);
    for (const name of modulePermissions(module)) {
        value ? next.add(name) : next.delete(name);
    }
    setPermissions(next);
}

const allPermissions = computed(() =>
    props.modules.flatMap((module) => modulePermissions(module)),
);

const allChecked = computed(() =>
    allPermissions.value.every((name) => selected.value.has(name)),
);

function toggleAll(value: boolean): void {
    if (props.readonly) {
        return;
    }
    setPermissions(value ? new Set(allPermissions.value) : new Set());
}
</script>

<template>
    <form class="flex flex-col gap-6" @submit.prevent="emit('submit')">
        <div
            v-if="readonly"
            class="flex items-center gap-2 rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-700 dark:text-amber-400"
        >
            <Lock class="size-4 shrink-0" />
            The Super Admin role always has every permission and cannot be
            changed.
        </div>

        <div class="grid max-w-md gap-2">
            <Label for="name">Role name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autofocus
                :disabled="readonly"
                placeholder="e.g. Payroll Clerk"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <Label class="text-sm font-medium">Permissions</Label>
                <label
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Checkbox
                        :model-value="allChecked"
                        :disabled="readonly"
                        @update:model-value="
                            (value) => toggleAll(value === true)
                        "
                    />
                    Select all
                </label>
            </div>

            <div class="w-full overflow-x-auto rounded-md border">
                <table class="w-full caption-bottom text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-muted-foreground">
                            <th class="px-3 py-2 text-left font-medium">
                                Module
                            </th>
                            <th
                                v-for="ability in abilities"
                                :key="ability.key"
                                class="px-3 py-2 text-center font-medium whitespace-nowrap"
                            >
                                {{ ability.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="module in modules"
                            :key="module.key"
                            class="border-b last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-3 py-2">
                                <label
                                    :class="
                                        cn(
                                            'flex items-center gap-2 font-medium',
                                            readonly
                                                ? 'cursor-default'
                                                : 'cursor-pointer',
                                        )
                                    "
                                >
                                    <Checkbox
                                        :model-value="moduleChecked(module)"
                                        :disabled="readonly"
                                        @update:model-value="
                                            (value) =>
                                                toggleModule(
                                                    module,
                                                    value === true,
                                                )
                                        "
                                    />
                                    {{ module.label }}
                                </label>
                            </td>
                            <td
                                v-for="ability in abilities"
                                :key="ability.key"
                                class="px-3 py-2 text-center"
                            >
                                <Checkbox
                                    v-if="supports(module, ability.key)"
                                    :model-value="has(module.key, ability.key)"
                                    :disabled="readonly"
                                    class="mx-auto"
                                    @update:model-value="
                                        (value) =>
                                            toggle(
                                                module.key,
                                                ability.key,
                                                value === true,
                                            )
                                    "
                                />
                                <span v-else class="text-muted-foreground/40">
                                    —
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <InputError :message="form.errors.permissions" />
        </div>

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing || readonly">
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link href="/roles">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
