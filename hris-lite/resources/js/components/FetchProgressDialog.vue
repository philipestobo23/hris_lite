<script setup lang="ts">
import {
    CheckCircle2,
    Loader2,
    RadioTower,
    Sparkles,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BiometricDevice } from '@/types';

type Target = Pick<BiometricDevice, 'id' | 'name'>;
type RowStatus = 'pending' | 'fetching' | 'done' | 'failed';
type Mode = 'users' | 'time';

interface Row {
    id: number;
    name: string;
    status: RowStatus;
    count: number;
    error: string | null;
}

const props = defineProps<{
    open: boolean;
    devices: Target[];
    mode: Mode;
}>();

const config = computed(() =>
    props.mode === 'users'
        ? {
              icon: Users,
              title: 'Fetching biometric users',
              runningDesc: 'Reading enrolled users from your terminals…',
              metricLabel: 'Users fetched',
              endpoint: (id: number) => `/biometric-users/devices/${id}/fetch`,
              countField: 'fetched' as const,
          }
        : {
              icon: RadioTower,
              title: 'Fetching attendance',
              runningDesc: 'Pulling punches from your biometric terminals…',
              metricLabel: 'New punches fetched',
              endpoint: (id: number) =>
                  `/attendance-logs/devices/${id}/fetch-time`,
              countField: 'new' as const,
          },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    finished: [];
}>();

const rows = ref<Row[]>([]);
const running = ref(false);
const finishedNaturally = ref(false);

const total = computed(() => rows.value.length);
const completed = computed(
    () => rows.value.filter((r) => r.status === 'done' || r.status === 'failed').length,
);
const progress = computed(() =>
    total.value === 0 ? 0 : Math.round((completed.value / total.value) * 100),
);
const totalNew = computed(() =>
    rows.value.reduce((sum, r) => sum + r.count, 0),
);
const failedCount = computed(
    () => rows.value.filter((r) => r.status === 'failed').length,
);
const done = computed(() => !running.value && finishedNaturally.value);

/** Read Laravel's XSRF cookie so a manual fetch passes CSRF. */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function fetchOne(row: Row): Promise<void> {
    row.status = 'fetching';

    try {
        const response = await fetch(config.value.endpoint(row.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
        });

        const data = await response.json();

        if (response.ok && data.ok) {
            row.count = data[config.value.countField] ?? 0;
            row.status = 'done';
        } else {
            row.error = data.error ?? 'Could not reach the device.';
            row.status = 'failed';
        }
    } catch {
        row.error = 'Network error.';
        row.status = 'failed';
    }
}

async function run(): Promise<void> {
    running.value = true;
    finishedNaturally.value = false;

    // Sequential so the progress bar advances device by device.
    for (const row of rows.value) {
        await fetchOne(row);
    }

    running.value = false;
    finishedNaturally.value = true;
}

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        rows.value = props.devices.map((d) => ({
            id: d.id,
            name: d.name,
            status: 'pending',
            count: 0,
            error: null,
        }));
        finishedNaturally.value = false;

        if (rows.value.length > 0) {
            run();
        }
    },
);

function close(): void {
    if (running.value) {
        return; // don't allow closing mid-run
    }

    emit('update:open', false);

    if (finishedNaturally.value) {
        emit('finished');
    }
}

function onOpenChange(value: boolean): void {
    if (!value) {
        close();
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-lg" :class="{ '[&>button]:hidden': running }">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <component :is="config.icon" class="size-5" />
                    </div>
                    <div class="space-y-1">
                        <DialogTitle>{{ config.title }}</DialogTitle>
                        <DialogDescription>
                            {{
                                running
                                    ? config.runningDesc
                                    : done
                                      ? 'Fetch complete.'
                                      : 'Preparing…'
                            }}
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <!-- Progress bar -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">
                        {{ completed }} of {{ total }} device{{
                            total === 1 ? '' : 's'
                        }}
                    </span>
                    <span class="font-medium tabular-nums">{{ progress }}%</span>
                </div>
                <div
                    class="h-2.5 w-full overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
                        :class="{ 'animate-pulse': running }"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
            </div>

            <!-- Total new counter -->
            <div
                class="flex items-center justify-between rounded-xl border bg-gradient-to-br from-primary/5 to-transparent p-4"
            >
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Sparkles class="size-4 text-primary" />
                    {{ config.metricLabel }}
                </div>
                <span
                    class="text-3xl font-semibold tabular-nums transition-all"
                    :class="totalNew > 0 ? 'text-primary' : 'text-foreground'"
                >
                    {{ totalNew }}
                </span>
            </div>

            <!-- Per-device list -->
            <div class="max-h-56 space-y-1 overflow-y-auto">
                <div
                    v-for="row in rows"
                    :key="row.id"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 transition-colors"
                    :class="{
                        'bg-muted/50': row.status === 'fetching',
                    }"
                >
                    <Loader2
                        v-if="row.status === 'fetching'"
                        class="size-4 shrink-0 animate-spin text-primary"
                    />
                    <CheckCircle2
                        v-else-if="row.status === 'done'"
                        class="size-4 shrink-0 text-emerald-500"
                    />
                    <TriangleAlert
                        v-else-if="row.status === 'failed'"
                        class="size-4 shrink-0 text-destructive"
                    />
                    <span
                        v-else
                        class="size-4 shrink-0 rounded-full border-2 border-muted-foreground/30"
                    />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ row.name }}</p>
                        <p
                            v-if="row.status === 'failed'"
                            class="truncate text-xs text-destructive"
                        >
                            {{ row.error }}
                        </p>
                    </div>

                    <span
                        v-if="row.status === 'done'"
                        class="shrink-0 text-sm font-medium tabular-nums"
                        :class="
                            row.count > 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-muted-foreground'
                        "
                    >
                        +{{ row.count }}
                    </span>
                    <span
                        v-else-if="row.status === 'fetching'"
                        class="shrink-0 text-xs text-muted-foreground"
                    >
                        Fetching…
                    </span>
                </div>

                <p
                    v-if="rows.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    No active devices to fetch from.
                </p>
            </div>

            <div class="flex items-center justify-between gap-3">
                <p v-if="done && failedCount > 0" class="text-xs text-destructive">
                    {{ failedCount }} device{{ failedCount === 1 ? '' : 's' }}
                    unreachable.
                </p>
                <span v-else />

                <Button :disabled="running" @click="close">
                    {{ running ? 'Fetching…' : 'Done' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
