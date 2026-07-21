<script
    setup
    lang="ts"
    generic="T extends Record<string, any> = Record<string, any>"
>
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Search,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type {
    DataTableColumn,
    DataTableFilters,
    Paginator,
    SortDirection,
} from '@/types';

const props = withDefaults(
    defineProps<{
        /** Serialized Laravel length-aware paginator. */
        paginator: Paginator<T>;
        /** Column definitions. */
        columns: DataTableColumn[];
        /** Current server-side filter state (search/sort/direction). */
        filters?: DataTableFilters;
        /**
         * Inertia props to reload on every interaction. Passing these turns
         * requests into partial reloads so only the table data + filters are
         * re-sent by the server. Leave empty for a full reload.
         */
        only?: string[];
        /**
         * Extra query params (e.g. page-level filters) merged into every
         * reload. Changing this object triggers a reload (page reset to 1).
         */
        extraParams?: Record<string, string | number | null | undefined>;
        /** Show the search box. */
        searchable?: boolean;
        searchPlaceholder?: string;
        /** Row field used as the :key (dot notation supported). */
        rowKey?: string;
        emptyText?: string;
        /** Debounce for the search input, in ms. */
        searchDebounce?: number;
    }>(),
    {
        filters: () => ({}),
        only: () => [],
        extraParams: () => ({}),
        searchable: true,
        searchPlaceholder: 'Search...',
        rowKey: 'id',
        emptyText: 'No records found.',
        searchDebounce: 300,
    },
);

const search = ref<string>(props.filters.search ?? '');
const sort = ref<string | null>(props.filters.sort ?? null);
const direction = ref<SortDirection>(
    (props.filters.direction as SortDirection) ?? 'asc',
);

// Keep local state in sync when the server echoes a different filter set
// (e.g. browser back/forward). This assignment matches the emitted values so
// it does not itself trigger another request.
watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search ?? '';
        sort.value = filters.sort ?? null;
        direction.value = (filters.direction as SortDirection) ?? 'asc';
    },
    { deep: true },
);

function reload(overrides: Record<string, unknown> = {}) {
    const query: Record<string, unknown> = {
        ...props.extraParams,
        search: search.value || undefined,
        sort: sort.value || undefined,
        direction: sort.value ? direction.value : undefined,
        ...overrides,
    };

    for (const key of Object.keys(query)) {
        const value = query[key];
        if (value === undefined || value === null || value === '') {
            delete query[key];
        }
    }

    router.get(props.paginator.path, query as Record<string, string | number>, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only.length ? props.only : undefined,
    });
}

const debouncedReload = useDebounceFn(
    () => reload({ page: 1 }),
    props.searchDebounce,
);

// Reload when page-level filters change.
watch(
    () => props.extraParams,
    () => reload({ page: 1 }),
    { deep: true },
);

function onSearchInput(value: string | number) {
    search.value = String(value);
    debouncedReload();
}

function sortBy(column: DataTableColumn) {
    if (!column.sortable) {
        return;
    }

    if (sort.value === column.key) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column.key;
        direction.value = 'asc';
    }

    reload({ page: 1 });
}

function goToPage(page: number) {
    if (
        page < 1 ||
        page > props.paginator.last_page ||
        page === props.paginator.current_page
    ) {
        return;
    }

    reload({ page });
}

function cellValue(row: T, key: string): unknown {
    return key
        .split('.')
        .reduce<unknown>(
            (acc, part) =>
                acc && typeof acc === 'object'
                    ? (acc as Record<string, unknown>)[part]
                    : undefined,
            row,
        );
}

function rowKeyValue(row: T, index: number): string | number {
    const value = cellValue(row, props.rowKey);
    return typeof value === 'string' || typeof value === 'number'
        ? value
        : index;
}

const alignClass: Record<NonNullable<DataTableColumn['align']>, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};

// Windowed page numbers with ellipses, e.g. 1 … 4 5 6 … 20
const pages = computed<(number | '...')[]>(() => {
    const current = props.paginator.current_page;
    const last = props.paginator.last_page;
    const delta = 1;
    const range: (number | '...')[] = [];

    const left = Math.max(2, current - delta);
    const right = Math.min(last - 1, current + delta);

    range.push(1);
    if (left > 2) {
        range.push('...');
    }
    for (let page = left; page <= right; page++) {
        range.push(page);
    }
    if (right < last - 1) {
        range.push('...');
    }
    if (last > 1) {
        range.push(last);
    }

    return range;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Toolbar -->
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div v-if="searchable" class="relative w-full sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    :model-value="search"
                    type="search"
                    :placeholder="searchPlaceholder"
                    class="pl-8"
                    @update:model-value="onSearchInput"
                />
            </div>

            <div class="flex items-center gap-2 sm:ml-auto">
                <slot name="toolbar" />
            </div>
        </div>

        <!-- Table -->
        <div class="w-full overflow-x-auto rounded-md border">
            <table class="w-full caption-bottom text-sm">
                <thead class="[&_tr]:border-b">
                    <tr
                        class="border-b bg-muted/40 text-muted-foreground transition-colors"
                    >
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :class="
                                cn(
                                    'h-10 px-3 align-middle font-medium whitespace-nowrap',
                                    alignClass[column.align ?? 'left'],
                                    column.headerClass,
                                )
                            "
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="inline-flex items-center gap-1 rounded transition-colors hover:text-foreground"
                                :class="{
                                    'text-foreground': sort === column.key,
                                    'ml-auto flex-row-reverse':
                                        column.align === 'right',
                                    'mx-auto': column.align === 'center',
                                }"
                                @click="sortBy(column)"
                            >
                                <span>{{ column.label }}</span>
                                <ArrowUp
                                    v-if="sort === column.key && direction === 'asc'"
                                    class="size-3.5"
                                />
                                <ArrowDown
                                    v-else-if="
                                        sort === column.key && direction === 'desc'
                                    "
                                    class="size-3.5"
                                />
                                <ArrowUpDown v-else class="size-3.5 opacity-50" />
                            </button>
                            <span v-else>{{ column.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in paginator.data"
                        :key="rowKeyValue(row, index)"
                        class="border-b transition-colors last:border-0 hover:bg-muted/50"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            :class="
                                cn(
                                    'px-3 py-2.5 align-middle',
                                    alignClass[column.align ?? 'left'],
                                    column.class,
                                )
                            "
                        >
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="cellValue(row, column.key)"
                            >
                                {{ cellValue(row, column.key) ?? '—' }}
                            </slot>
                        </td>
                    </tr>

                    <tr v-if="paginator.data.length === 0">
                        <td
                            :colspan="columns.length"
                            class="h-24 px-3 text-center text-muted-foreground"
                        >
                            <slot name="empty">{{ emptyText }}</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / pagination -->
        <div
            class="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p class="text-sm text-muted-foreground">
                <template v-if="paginator.total > 0">
                    Showing {{ paginator.from }}–{{ paginator.to }} of
                    {{ paginator.total }}
                </template>
                <template v-else>No results</template>
            </p>

            <div
                v-if="paginator.last_page > 1"
                class="flex items-center gap-1"
            >
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="paginator.current_page <= 1"
                    aria-label="Previous page"
                    @click="goToPage(paginator.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <template v-for="(page, index) in pages" :key="index">
                    <span
                        v-if="page === '...'"
                        class="px-1.5 text-sm text-muted-foreground"
                    >
                        …
                    </span>
                    <Button
                        v-else
                        :variant="
                            page === paginator.current_page
                                ? 'default'
                                : 'outline'
                        "
                        size="icon-sm"
                        @click="goToPage(page)"
                    >
                        {{ page }}
                    </Button>
                </template>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="paginator.current_page >= paginator.last_page"
                    aria-label="Next page"
                    @click="goToPage(paginator.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
