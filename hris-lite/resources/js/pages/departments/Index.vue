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
    Department,
    Paginator,
} from '@/types';

defineProps<{
    departments: Paginator<Department>;
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Departments', href: '/departments' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'branch', label: 'Branch' },
    { key: 'positions_count', label: 'Positions', align: 'center' },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'actions', label: '', align: 'right' },
];

function destroy(department: Department): void {
    if (confirm(`Delete department “${department.name}”?`)) {
        router.delete(`/departments/${department.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Departments" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Departments"
                description="Group roles within (or across) branches."
            />
            <Button as-child>
                <Link href="/departments/create">
                    <Plus class="size-4" />
                    New department
                </Link>
            </Button>
        </div>

        <DataTable
            :paginator="departments"
            :filters="filters"
            :columns="columns"
            :only="['departments', 'filters']"
            search-placeholder="Search departments..."
            empty-text="No departments yet."
        >
            <template #cell-branch="{ row }">
                <span v-if="row.branch">{{ row.branch.name }}</span>
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
                        <Link :href="`/departments/${row.id}/edit`" title="Edit">
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
