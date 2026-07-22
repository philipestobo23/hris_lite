<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
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
    Holiday,
    HolidayType,
    HolidayTypeOption,
    Paginator,
} from '@/types';

const props = defineProps<{
    holidays: Paginator<Holiday>;
    types: HolidayTypeOption[];
    years: number[];
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Holidays', href: '/holidays' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'date', label: 'Date', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'branch', label: 'Branch scope' },
    { key: 'pay_rule', label: 'Pay rule', sortable: true, align: 'right' },
    { key: 'actions', label: '', align: 'right' },
];

const ALL = 'all';

const yearFilter = ref<string>(
    props.filters.year ? String(props.filters.year) : '',
);
const typeFilter = ref<string>(
    props.filters.type ? String(props.filters.type) : '',
);

const yearModel = computed({
    get: () => (yearFilter.value === '' ? ALL : yearFilter.value),
    set: (v: string) => (yearFilter.value = v === ALL ? '' : v),
});
const typeModel = computed({
    get: () => (typeFilter.value === '' ? ALL : typeFilter.value),
    set: (v: string) => (typeFilter.value = v === ALL ? '' : v),
});

const extraParams = computed(() => ({
    year: yearFilter.value || undefined,
    type: typeFilter.value || undefined,
}));

const typeLabels = computed<Record<string, string>>(() =>
    Object.fromEntries(props.types.map((t) => [t.value, t.label])),
);

const typeVariants: Record<HolidayType, 'default' | 'secondary' | 'outline'> = {
    regular: 'default',
    special_non_working: 'secondary',
    special_working: 'outline',
};

function formatDate(value: string): string {
    // Dates are plain calendar days — render the Y-m-d part as-is so no
    // timezone conversion can shift them.
    const [year, month, day] = value.slice(0, 10).split('-').map(Number);

    return new Date(year, month - 1, day).toLocaleDateString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
}

function destroy(holiday: Holiday): void {
    if (confirm(`Delete holiday “${holiday.name}”?`)) {
        router.delete(`/holidays/${holiday.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Holidays" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Holidays"
                description="Company and branch holidays, and how each one is paid."
            />
            <Button as-child>
                <Link href="/holidays/create">
                    <Plus class="size-4" />
                    New holiday
                </Link>
            </Button>
        </div>

        <DataTable
            :paginator="holidays"
            :filters="filters"
            :columns="columns"
            :only="['holidays', 'filters']"
            :extra-params="extraParams"
            search-placeholder="Search holidays..."
            empty-text="No holidays match your filters."
        >
            <template #toolbar>
                <Select v-model="yearModel">
                    <SelectTrigger class="w-32"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All years</SelectItem>
                        <SelectItem
                            v-for="year in years"
                            :key="year"
                            :value="String(year)"
                        >
                            {{ year }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="typeModel">
                    <SelectTrigger class="w-48"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All types</SelectItem>
                        <SelectItem
                            v-for="option in types"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>

            <template #cell-date="{ value }">
                {{ formatDate(String(value)) }}
            </template>

            <template #cell-type="{ value }">
                <Badge :variant="typeVariants[value as HolidayType] ?? 'outline'">
                    {{ typeLabels[String(value)] ?? value }}
                </Badge>
            </template>

            <template #cell-branch="{ row }">
                <span v-if="row.branch">{{ row.branch.name }}</span>
                <Badge v-else variant="outline">Company-wide</Badge>
            </template>

            <template #cell-pay_rule="{ value }">
                <span class="font-medium tabular-nums">
                    {{ Math.round(Number(value) * 100) }}%
                </span>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link :href="`/holidays/${row.id}/edit`" title="Edit">
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
