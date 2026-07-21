<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Branch, DataTableColumn, DataTableFilters, Paginator } from '@/types';

defineProps<{
    branches: Paginator<Branch>;
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Branches', href: '/branches' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'code', label: 'Code', sortable: true },
    { key: 'departments_count', label: 'Departments', align: 'center' },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'actions', label: '', align: 'right' },
];

function destroy(branch: Branch): void {
    if (confirm(`Delete branch “${branch.name}”?`)) {
        router.delete(`/branches/${branch.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Branches" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Branches"
                description="Manage your company's branches."
            />
            <Button as-child>
                <Link href="/branches/create">
                    <Plus class="size-4" />
                    New branch
                </Link>
            </Button>
        </div>

        <DataTable
            :paginator="branches"
            :filters="filters"
            :columns="columns"
            :only="['branches', 'filters']"
            search-placeholder="Search branches..."
            empty-text="No branches yet."
        >
            <template #cell-code="{ value }">
                <span v-if="value">{{ value }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-is_active="{ value }">
                <Badge :variant="value ? 'default' : 'secondary'">
                    {{ value ? 'Active' : 'Inactive' }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link :href="`/branches/${row.id}/edit`" title="Edit">
                            <Pencil class="size-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        title="Delete"
                        @click="destroy(row)"
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>
            </template>
        </DataTable>
    </div>
</template>
