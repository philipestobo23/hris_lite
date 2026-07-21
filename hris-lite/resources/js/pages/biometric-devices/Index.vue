<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Loader2, Pencil, Plus, Trash2, Wifi } from '@lucide/vue';
import { ref } from 'vue';
import DataTable from '@/components/DataTable.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    BiometricDevice,
    DataTableColumn,
    DataTableFilters,
    Paginator,
} from '@/types';

defineProps<{
    devices: Paginator<BiometricDevice>;
    filters: DataTableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Biometric Devices', href: '/biometric-devices' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'branch', label: 'Branch' },
    { key: 'model', label: 'Model', sortable: true },
    { key: 'connection', label: 'Connection' },
    { key: 'mode', label: 'Mode', sortable: true },
    { key: 'is_active', label: 'Status', sortable: true },
    { key: 'actions', label: '', align: 'right' },
];

const modeLabels: Record<string, string> = {
    push: 'Push',
    pull: 'Pull',
};

// Id of the device currently being pinged, so only its button spins.
const testingId = ref<number | null>(null);

function testConnection(device: BiometricDevice): void {
    if (testingId.value !== null) {
        return;
    }

    testingId.value = device.id;

    router.post(
        `/biometric-devices/${device.id}/test-connection`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                testingId.value = null;
            },
        },
    );
}

function destroy(device: BiometricDevice): void {
    if (confirm(`Delete device “${device.name}”?`)) {
        router.delete(`/biometric-devices/${device.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Biometric Devices" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Biometric Devices"
                description="Manage the attendance terminals installed across your branches."
            />
            <Button as-child>
                <Link href="/biometric-devices/create">
                    <Plus class="size-4" />
                    New device
                </Link>
            </Button>
        </div>

        <DataTable
            :paginator="devices"
            :filters="filters"
            :columns="columns"
            :only="['devices', 'filters']"
            search-placeholder="Search by name, serial or IP..."
            empty-text="No devices yet."
        >
            <template #cell-branch="{ row }">
                <span v-if="row.branch">{{ row.branch.name }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-connection="{ row }">
                <span v-if="row.ip_address" class="font-mono text-sm">
                    {{ row.ip_address }}:{{ row.port }}
                </span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-mode="{ value }">
                <Badge variant="outline">
                    {{ modeLabels[String(value)] ?? value }}
                </Badge>
            </template>

            <template #cell-is_active="{ value }">
                <Badge :variant="value ? 'default' : 'secondary'">
                    {{ value ? 'Active' : 'Inactive' }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        title="Test connection"
                        :disabled="testingId !== null"
                        @click="testConnection(row)"
                    >
                        <Loader2
                            v-if="testingId === row.id"
                            class="size-4 animate-spin"
                        />
                        <Wifi v-else class="size-4" />
                    </Button>
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link :href="`/biometric-devices/${row.id}/edit`" title="Edit">
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
