<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { LogIn, LogOut, RefreshCw } from '@lucide/vue';
import { computed, ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import FetchProgressDialog from '@/components/FetchProgressDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    AttendanceLog,
    BiometricDevice,
    DataTableColumn,
    DataTableFilters,
    Paginator,
} from '@/types';

const props = defineProps<{
    logs: Paginator<AttendanceLog>;
    devices: Pick<BiometricDevice, 'id' | 'name' | 'is_active'>[];
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Attendance Logs', href: '/attendance-logs' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'employee', label: 'Employee' },
    { key: 'device_user_id', label: 'Device user', sortable: true },
    { key: 'device', label: 'Terminal' },
    { key: 'punched_at', label: 'Date', sortable: true },
    { key: 'time', label: 'Time' },
    { key: 'status', label: 'In / Out', align: 'center' },
    { key: 'verify_mode', label: 'Verified by' },
];

const ALL = 'all';

const deviceFilter = ref<string>(
    props.filters.device_id ? String(props.filters.device_id) : '',
);
const dateFilter = ref<string>(
    props.filters.date ? String(props.filters.date) : '',
);

const deviceModel = computed({
    get: () => (deviceFilter.value === '' ? ALL : deviceFilter.value),
    set: (v: string) => (deviceFilter.value = v === ALL ? '' : v),
});

const extraParams = computed(() => ({
    device_id: deviceFilter.value || undefined,
    date: dateFilter.value || undefined,
}));

// Devices the fetch modal will pull from: the selected one, or every active
// device when no device filter is applied.
const targetDevices = computed(() => {
    if (deviceFilter.value !== '') {
        return props.devices.filter(
            (d) => d.id === Number(deviceFilter.value),
        );
    }

    return props.devices.filter((d) => d.is_active);
});

const fetchOpen = ref(false);

function openFetch(): void {
    fetchOpen.value = true;
}

function onFetchFinished(): void {
    // Pull the newly stored punches into the table.
    router.reload({ only: ['logs', 'filters'] });
}

// Company timezone: punches always display in Philippine time (Asia/Manila),
// regardless of the viewer's device timezone.
const TIME_ZONE = 'Asia/Manila';

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        timeZone: TIME_ZONE,
    });
}

function formatTime(value: string): string {
    return new Date(value).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        timeZone: TIME_ZONE,
    });
}

const verifyLabels: Record<string, string> = {
    fingerprint: 'Fingerprint',
    password: 'Password',
    card: 'Card',
};
</script>

<template>
    <Head title="Attendance Logs" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Attendance Logs"
                description="Punches fetched from your biometric terminals."
            />
            <Button @click="openFetch">
                <RefreshCw class="size-4" />
                Fetch time
            </Button>
        </div>

        <FetchProgressDialog
            v-model:open="fetchOpen"
            mode="time"
            :devices="targetDevices"
            @finished="onFetchFinished"
        />

        <DataTable
            :paginator="logs"
            :filters="filters"
            :columns="columns"
            :only="['logs', 'filters']"
            :extra-params="extraParams"
            search-placeholder="Search employee or device user..."
            empty-text="No punches yet. Use “Fetch time” to pull from a device."
        >
            <template #toolbar>
                <Select v-model="deviceModel">
                    <SelectTrigger class="w-48"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All devices</SelectItem>
                        <SelectItem
                            v-for="d in devices"
                            :key="d.id"
                            :value="String(d.id)"
                        >
                            {{ d.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Input
                    v-model="dateFilter"
                    type="date"
                    class="w-40"
                    aria-label="Filter by date"
                />
            </template>

            <template #cell-employee="{ row }">
                <span v-if="row.employee" class="font-medium">
                    {{ row.employee.full_name }}
                </span>
                <div v-else class="flex items-center gap-2">
                    <span v-if="row.device_user_name" class="text-muted-foreground">
                        {{ row.device_user_name }}
                    </span>
                    <Badge
                        variant="outline"
                        title="This device user is not linked to an employee record yet"
                    >
                        Unmapped
                    </Badge>
                </div>
            </template>

            <template #cell-device_user_id="{ row }">
                <div class="flex flex-col leading-tight">
                    <span class="font-mono text-sm">{{ row.device_user_id }}</span>
                    <span v-if="row.device_user_name" class="text-xs text-muted-foreground">
                        {{ row.device_user_name }}
                    </span>
                </div>
            </template>

            <template #cell-device="{ row }">
                <span v-if="row.device">{{ row.device.name }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-punched_at="{ value }">
                {{ formatDate(String(value)) }}
            </template>

            <template #cell-time="{ row }">
                <span class="font-mono text-sm">{{ formatTime(row.punched_at) }}</span>
            </template>

            <template #cell-status="{ value }">
                <Badge
                    v-if="value === 'in'"
                    variant="default"
                    class="gap-1"
                >
                    <LogIn class="size-3" /> In
                </Badge>
                <Badge
                    v-else-if="value === 'out'"
                    variant="secondary"
                    class="gap-1"
                >
                    <LogOut class="size-3" /> Out
                </Badge>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-verify_mode="{ value }">
                <span v-if="value">{{ verifyLabels[String(value)] ?? value }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>
        </DataTable>
    </div>
</template>
