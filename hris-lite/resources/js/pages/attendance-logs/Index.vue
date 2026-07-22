<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { LogIn, LogOut, RefreshCw, Users } from '@lucide/vue';
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
    { key: 'device_user_name', label: 'Biometric name' },
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
        return props.devices.filter((d) => d.id === Number(deviceFilter.value));
    }

    return props.devices.filter((d) => d.is_active);
});

const fetchOpen = ref(false);
const fetchMode = ref<'users' | 'time'>('time');

function openFetch(mode: 'users' | 'time'): void {
    fetchMode.value = mode;
    fetchOpen.value = true;
}

function onFetchFinished(): void {
    // Pull the newly stored punches (and refreshed names) into the table.
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
            <div class="flex items-center gap-2">
                <Button variant="outline" @click="openFetch('users')">
                    <Users class="size-4" />
                    Fetch users
                </Button>
                <Button @click="openFetch('time')">
                    <RefreshCw class="size-4" />
                    Fetch time
                </Button>
            </div>
        </div>

        <FetchProgressDialog
            v-model:open="fetchOpen"
            :mode="fetchMode"
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
                <Badge
                    v-else
                    variant="outline"
                    title="This device user is not linked to an employee record yet"
                >
                    Unmapped
                </Badge>
            </template>

            <template #cell-device_user_name="{ value }">
                <span v-if="value">{{ value }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-device_user_id="{ row }">
                <div class="flex flex-col leading-tight">
                    <span class="font-mono text-sm">{{
                        row.device_user_id
                    }}</span>
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
                <span class="font-mono text-sm">{{
                    formatTime(row.punched_at)
                }}</span>
            </template>

            <template #cell-status="{ value }">
                <Badge v-if="value === 'in'" variant="default" class="gap-1">
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
                <span v-if="value">{{
                    verifyLabels[String(value)] ?? value
                }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>
        </DataTable>
    </div>
</template>
