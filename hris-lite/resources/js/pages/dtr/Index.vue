<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarRange,
    CheckCheck,
    Printer,
    RefreshCw,
    Search,
    Unlink,
    Users,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Employee } from '@/types';

type EmployeeRow = Pick<
    Employee,
    'id' | 'first_name' | 'last_name' | 'employee_no' | 'biometric_id'
> & { department?: { id: number; name: string } | null };

type Period = 'whole' | 'first_half' | 'second_half' | 'custom';

const props = defineProps<{
    employees: EmployeeRow[];
    /** employee id => days already built inside the range. */
    generated: Record<number, number>;
    filters: {
        year: number;
        month: number;
        period: Period;
        from: string;
        to: string;
    };
    rangeDays: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'DTR', href: '/dtr' }],
    },
});

// --- period controls -------------------------------------------------------

const year = ref<number>(props.filters.year);
const month = ref<number>(props.filters.month);
const period = ref<Period>(props.filters.period);
const customFrom = ref<string>(props.filters.from);
const customTo = ref<string>(props.filters.to);

const years = computed(() => {
    const current = new Date().getFullYear();

    return Array.from({ length: 6 }, (_, i) => current + 1 - i);
});

const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const periodOptions: { value: Period; label: string; hint: string }[] = [
    { value: 'whole', label: 'Whole month', hint: '1 – end' },
    { value: 'first_half', label: '1st half', hint: '1 – 15' },
    { value: 'second_half', label: '2nd half', hint: '16 – end' },
    { value: 'custom', label: 'Custom range', hint: 'pick dates' },
];

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

function iso(y: number, m: number, d: number): string {
    return `${y}-${pad(m)}-${pad(d)}`;
}

const lastDay = computed(() => new Date(year.value, month.value, 0).getDate());

/** Inclusive [from, to] for the selected period. */
const range = computed<{ from: string; to: string }>(() => {
    if (period.value === 'custom') {
        return { from: customFrom.value, to: customTo.value };
    }

    const spans: Record<Exclude<Period, 'custom'>, [number, number]> = {
        whole: [1, lastDay.value],
        first_half: [1, 15],
        second_half: [16, lastDay.value],
    };

    const [start, end] = spans[period.value];

    return {
        from: iso(year.value, month.value, start),
        to: iso(year.value, month.value, Math.min(end, lastDay.value)),
    };
});

const monthValue = computed(() => `${year.value}-${pad(month.value)}`);

const rangeLabel = computed(() => {
    const fmt = (value: string) => {
        const [y, m, d] = value.split('-').map(Number);

        return new Date(y, m - 1, d).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    };

    if (!range.value.from || !range.value.to) {
        return 'Pick a range';
    }

    return `${fmt(range.value.from)} – ${fmt(range.value.to)}`;
});

// Re-query so the "already generated" counts follow the selected range.
watch(
    range,
    (value) => {
        if (!value.from || !value.to) {
            return;
        }

        router.get(
            '/dtr',
            { period: period.value, from: value.from, to: value.to },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['generated', 'filters', 'rangeDays'],
            },
        );
    },
    { deep: true },
);

// --- employee selection ----------------------------------------------------

const search = ref('');
const selected = ref<Set<number>>(new Set());

const visible = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return props.employees;
    }

    return props.employees.filter((employee) =>
        `${employee.first_name} ${employee.last_name} ${employee.employee_no}`
            .toLowerCase()
            .includes(term),
    );
});

const allVisibleSelected = computed(
    () =>
        visible.value.length > 0 &&
        visible.value.every((employee) => selected.value.has(employee.id)),
);

function toggle(id: number): void {
    const next = new Set(selected.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    selected.value = next;
}

function toggleAll(): void {
    const next = new Set(selected.value);

    if (allVisibleSelected.value) {
        visible.value.forEach((employee) => next.delete(employee.id));
    } else {
        visible.value.forEach((employee) => next.add(employee.id));
    }

    selected.value = next;
}

// --- actions ---------------------------------------------------------------

const building = ref(false);

const canRun = computed(
    () => selected.value.size > 0 && !!range.value.from && !!range.value.to,
);

function generate(): void {
    if (!canRun.value || building.value) {
        return;
    }

    building.value = true;

    router.post(
        '/dtr/build',
        {
            employee_ids: [...selected.value],
            from: range.value.from,
            to: range.value.to,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                building.value = false;
            },
        },
    );
}

const printUrl = computed(() => {
    const params = new URLSearchParams({ month: monthValue.value });

    selected.value.forEach((id) => params.append('employee_ids[]', String(id)));

    return `/dtr/print?${params.toString()}`;
});
</script>

<template>
    <Head title="DTR" />

    <div class="flex flex-col gap-6">
        <Heading
            variant="small"
            title="Daily Time Record"
            description="Pick a period, choose employees, then generate or print the CSC Form 48."
        />

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Left: generate options -->
            <div class="lg:col-span-1">
                <div
                    class="sticky top-20 flex flex-col gap-5 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <CalendarRange class="size-4.5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold">Period</p>
                            <p class="text-xs text-muted-foreground">
                                What range to build
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <Label class="text-xs">Month</Label>
                            <Select
                                :model-value="String(month)"
                                @update:model-value="month = Number($event)"
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(name, index) in months"
                                        :key="name"
                                        :value="String(index + 1)"
                                    >
                                        {{ name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-xs">Year</Label>
                            <Select
                                :model-value="String(year)"
                                @update:model-value="year = Number($event)"
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="y in years"
                                        :key="y"
                                        :value="String(y)"
                                    >
                                        {{ y }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Period preset -->
                    <div class="grid gap-1.5">
                        <Label class="text-xs" for="range">Range</Label>
                        <Select
                            :model-value="period"
                            @update:model-value="period = $event as Period"
                        >
                            <SelectTrigger id="range" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in periodOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                    <span class="text-muted-foreground">
                                        ({{ option.hint }})
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Custom dates -->
                    <div
                        v-if="period === 'custom'"
                        class="grid grid-cols-2 gap-3"
                    >
                        <div class="grid gap-1.5">
                            <Label class="text-xs" for="from">From</Label>
                            <Input id="from" v-model="customFrom" type="date" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-xs" for="to">To</Label>
                            <Input id="to" v-model="customTo" type="date" />
                        </div>
                    </div>

                    <!-- Resolved range -->
                    <div class="rounded-lg bg-muted/50 px-3 py-2.5">
                        <p class="text-xs text-muted-foreground">
                            Selected range
                        </p>
                        <p class="text-sm font-medium">{{ rangeLabel }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ rangeDays }} day{{
                                rangeDays === 1 ? '' : 's'
                            }}
                            · {{ selected.size }} employee{{
                                selected.size === 1 ? '' : 's'
                            }}
                            selected
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Button
                            :disabled="!canRun || building"
                            @click="generate"
                        >
                            <RefreshCw
                                :class="[
                                    'size-4',
                                    { 'animate-spin': building },
                                ]"
                            />
                            {{ building ? 'Generating…' : 'Generate DTR' }}
                        </Button>
                        <Button
                            variant="outline"
                            :disabled="selected.size === 0"
                            as-child
                        >
                            <a :href="printUrl" target="_blank" rel="noopener">
                                <Printer class="size-4" />
                                Print CSC Form 48
                            </a>
                        </Button>
                        <p
                            class="text-center text-[11px] text-muted-foreground"
                        >
                            Printing uses the whole month — 2 copies per sheet.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right: employee list, two columns -->
            <div class="lg:col-span-2">
                <div
                    class="flex h-full flex-col gap-4 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-2">
                            <div
                                class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Users class="size-4.5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Employees</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ selected.size }} of
                                    {{ employees.length }} selected
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <Search
                                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    v-model="search"
                                    placeholder="Search employee..."
                                    class="w-56 pl-8"
                                />
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="toggleAll"
                            >
                                <CheckCheck class="size-4" />
                                {{
                                    allVisibleSelected ? 'Clear' : 'Select all'
                                }}
                            </Button>
                        </div>
                    </div>

                    <!-- Two-column employee grid -->
                    <div
                        class="grid max-h-[34rem] auto-rows-min gap-2 overflow-y-auto pr-1 sm:grid-cols-2"
                    >
                        <button
                            v-for="employee in visible"
                            :key="employee.id"
                            type="button"
                            class="flex items-start gap-3 rounded-lg border p-3 text-left transition-colors"
                            :class="
                                selected.has(employee.id)
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:bg-muted/60'
                            "
                            @click="toggle(employee.id)"
                        >
                            <span
                                class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border"
                                :class="
                                    selected.has(employee.id)
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-muted-foreground/40'
                                "
                            >
                                <CheckCheck
                                    v-if="selected.has(employee.id)"
                                    class="size-3"
                                />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium"
                                >
                                    {{ employee.first_name }}
                                    {{ employee.last_name }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{ employee.employee_no }}
                                    <template v-if="employee.department">
                                        · {{ employee.department.name }}
                                    </template>
                                </span>

                                <span
                                    class="mt-1.5 flex flex-wrap items-center gap-1"
                                >
                                    <Badge
                                        v-if="generated[employee.id]"
                                        variant="secondary"
                                        class="gap-1 text-[10px]"
                                    >
                                        {{ generated[employee.id] }}/{{
                                            rangeDays
                                        }}
                                        built
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="outline"
                                        class="text-[10px]"
                                    >
                                        Not generated
                                    </Badge>

                                    <span
                                        v-if="employee.biometric_id"
                                        class="inline-flex items-center gap-0.5 text-[10px] text-emerald-600 dark:text-emerald-400"
                                    >
                                        <BadgeCheck class="size-3" />
                                        #{{ employee.biometric_id }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-0.5 text-[10px] text-amber-600 dark:text-amber-400"
                                        title="No biometric enrollment linked — punches cannot be matched"
                                    >
                                        <Unlink class="size-3" />
                                        Not linked
                                    </span>
                                </span>
                            </span>
                        </button>

                        <p
                            v-if="visible.length === 0"
                            class="col-span-full py-12 text-center text-sm text-muted-foreground"
                        >
                            No employees match “{{ search }}”.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
