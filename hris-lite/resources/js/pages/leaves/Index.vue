<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Pencil, Plus, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    DataTableColumn,
    DataTableFilters,
    Leave,
    LeaveStatus,
    Paginator,
    SelectOption,
} from '@/types';

const props = defineProps<{
    leaves: Paginator<Leave>;
    types: SelectOption[];
    statuses: SelectOption[];
    can: { approve: boolean; create: boolean };
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Leave', href: '/leaves' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'employee', label: 'Employee' },
    { key: 'type', label: 'Type' },
    { key: 'start_date', label: 'From', sortable: true },
    { key: 'end_date', label: 'To', sortable: true },
    { key: 'days', label: 'Days', sortable: true, align: 'center' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: '', align: 'right' },
];

const ALL = 'all';

const statusFilter = ref<string>(
    props.filters.status ? String(props.filters.status) : '',
);
const typeFilter = ref<string>(
    props.filters.type ? String(props.filters.type) : '',
);

const statusModel = computed({
    get: () => (statusFilter.value === '' ? ALL : statusFilter.value),
    set: (v: string) => (statusFilter.value = v === ALL ? '' : v),
});
const typeModel = computed({
    get: () => (typeFilter.value === '' ? ALL : typeFilter.value),
    set: (v: string) => (typeFilter.value = v === ALL ? '' : v),
});

const extraParams = computed(() => ({
    status: statusFilter.value || undefined,
    type: typeFilter.value || undefined,
}));

const typeLabels = computed<Record<string, string>>(() =>
    Object.fromEntries(props.types.map((t) => [t.value, t.label])),
);

const statusVariants: Record<
    LeaveStatus,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    pending: 'outline',
    approved: 'default',
    rejected: 'destructive',
    cancelled: 'secondary',
};

function formatDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
}

function approve(leave: Leave): void {
    router.post(`/leaves/${leave.id}/approve`, {}, { preserveScroll: true });
}

function reject(leave: Leave): void {
    if (confirm(`Reject this ${typeLabels.value[leave.type]} filing?`)) {
        router.post(`/leaves/${leave.id}/reject`, {}, { preserveScroll: true });
    }
}

function destroy(leave: Leave): void {
    if (confirm('Delete this leave filing?')) {
        router.delete(`/leaves/${leave.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Leave" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Leave"
                description="File, approve and track employee leave."
            />
            <Button v-if="can.create" as-child>
                <Link href="/leaves/create">
                    <Plus class="size-4" />
                    File leave
                </Link>
            </Button>
        </div>

        <DataTable
            :paginator="leaves"
            :filters="filters"
            :columns="columns"
            :only="['leaves', 'filters']"
            :extra-params="extraParams"
            search-placeholder="Search employee..."
            empty-text="No leave filings match your filters."
        >
            <template #toolbar>
                <Select v-model="statusModel">
                    <SelectTrigger class="w-40"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>
                        <SelectItem
                            v-for="s in statuses"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="typeModel">
                    <SelectTrigger class="w-48"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All types</SelectItem>
                        <SelectItem
                            v-for="t in types"
                            :key="t.value"
                            :value="t.value"
                        >
                            {{ t.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>

            <template #cell-employee="{ row }">
                <span v-if="row.employee" class="font-medium">
                    {{ row.employee.full_name }}
                </span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-type="{ row }">
                <div class="flex items-center gap-2">
                    <span>{{ typeLabels[row.type] ?? row.type }}</span>
                    <Badge v-if="!row.is_paid" variant="outline">Unpaid</Badge>
                </div>
            </template>

            <template #cell-start_date="{ value }">
                {{ formatDate(String(value)) }}
            </template>

            <template #cell-end_date="{ value }">
                {{ formatDate(String(value)) }}
            </template>

            <template #cell-days="{ value }">
                <span class="tabular-nums">{{ Number(value) }}</span>
            </template>

            <template #cell-status="{ row }">
                <Badge :variant="statusVariants[row.status as LeaveStatus]">
                    {{ row.status }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <template v-if="can.approve && row.status === 'pending'">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Approve"
                            @click="approve(row)"
                        >
                            <Check class="size-4 text-emerald-600" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            title="Reject"
                            @click="reject(row)"
                        >
                            <X class="size-4 text-destructive" />
                        </Button>
                    </template>
                    <Button
                        v-if="row.status === 'pending'"
                        variant="ghost"
                        size="icon-sm"
                        as-child
                    >
                        <Link :href="`/leaves/${row.id}/edit`" title="Edit">
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
