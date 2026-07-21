<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    DataTableColumn,
    DataTableFilters,
    Paginator,
    Position,
} from '@/types';

defineProps<{
    positions: Paginator<Position>;
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Positions', href: '/positions' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'department', label: 'Department' },
    { key: 'branch', label: 'Branch' },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'actions', label: '', align: 'right' },
];

function destroy(position: Position): void {
    if (confirm(`Delete position “${position.name}”?`)) {
        router.delete(`/positions/${position.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Positions" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Positions"
                description="Job titles that belong to a department."
            />
            <Button as-child>
                <Link href="/positions/create">
                    <Plus class="size-4" />
                    New position
                </Link>
            </Button>
        </div>

        <DataTable
            :paginator="positions"
            :filters="filters"
            :columns="columns"
            :only="['positions', 'filters']"
            search-placeholder="Search positions..."
            empty-text="No positions yet."
        >
            <template #cell-department="{ row }">
                <span v-if="row.department">{{ row.department.name }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-branch="{ row }">
                <span v-if="row.department?.branch">
                    {{ row.department.branch.name }}
                </span>
                <Badge v-else variant="outline">Shared</Badge>
            </template>

            <template #cell-is_active="{ value }">
                <Badge :variant="value ? 'default' : 'secondary'">
                    {{ value ? 'Active' : 'Inactive' }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link :href="`/positions/${row.id}/edit`" title="Edit">
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
