<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, ShieldCheck, Trash2, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useCan } from '@/composables/useCan';
import type { PermissionModule, Role } from '@/types';

defineProps<{
    roles: Role[];
    modules: PermissionModule[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Roles & Permissions', href: '/roles' }],
    },
});

const { can } = useCan();

function grantedCount(role: Role, module: PermissionModule): number {
    return role.permissions.filter((permission) =>
        permission.startsWith(`${module.key}.`),
    ).length;
}

function badgeVariant(
    granted: number,
    total: number,
): 'default' | 'secondary' | 'outline' {
    if (granted === 0) {
        return 'outline';
    }
    return granted === total ? 'default' : 'secondary';
}

function destroy(role: Role): void {
    if (
        confirm(
            `Delete role “${role.name}”? Users assigned to it will lose these permissions.`,
        )
    ) {
        router.delete(`/roles/${role.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Roles & Permissions" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Roles & Permissions"
                description="Define what each role can do across every module."
            />
            <Button v-if="can('roles.create')" as-child>
                <Link href="/roles/create">
                    <Plus class="size-4" />
                    New role
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="role in roles" :key="role.id" class="flex flex-col">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <ShieldCheck
                            v-if="role.is_protected"
                            class="size-4 text-primary"
                        />
                        {{ role.name }}
                    </CardTitle>
                    <div
                        class="flex items-center gap-4 text-sm text-muted-foreground"
                    >
                        <span class="flex items-center gap-1">
                            <Users class="size-3.5" />
                            {{ role.users_count ?? 0 }}
                            {{
                                (role.users_count ?? 0) === 1
                                    ? 'member'
                                    : 'members'
                            }}
                        </span>
                        <span>{{ role.permissions.length }} permissions</span>
                    </div>
                </CardHeader>

                <CardContent class="flex-1">
                    <div class="flex flex-wrap gap-1.5">
                        <Badge
                            v-for="module in modules"
                            :key="module.key"
                            :variant="
                                badgeVariant(
                                    grantedCount(role, module),
                                    module.abilities.length,
                                )
                            "
                            class="font-normal"
                        >
                            {{ module.label }}
                            {{ grantedCount(role, module) }}/{{
                                module.abilities.length
                            }}
                        </Badge>
                    </div>
                </CardContent>

                <CardFooter class="gap-2">
                    <Button
                        v-if="can('roles.edit')"
                        variant="outline"
                        size="sm"
                        as-child
                    >
                        <Link :href="`/roles/${role.id}/edit`">
                            <Pencil class="size-4" />
                            {{ role.is_protected ? 'View' : 'Edit' }}
                        </Link>
                    </Button>
                    <Button
                        v-if="can('roles.delete') && !role.is_protected"
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        @click="destroy(role)"
                    >
                        <Trash2 class="size-4" />
                        Delete
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>
