<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Fingerprint, Search, TriangleAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { BiometricUserOption, Employee } from '@/types';

const props = defineProps<{
    open: boolean;
    employee: Employee | null;
    /** Enrolled users discovered from the biometric punches. */
    biometricUsers: BiometricUserOption[];
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const search = ref('');
const selectedId = ref<string | null>(null);
const saving = ref(false);

watch(
    () => props.open,
    (open) => {
        if (open) {
            search.value = '';
            selectedId.value = props.employee?.biometric_id ?? null;
        }
    },
);

const results = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return props.biometricUsers;
    }

    return props.biometricUsers.filter((user) =>
        `${user.device_user_id} ${user.device_user_name ?? ''}`
            .toLowerCase()
            .includes(term),
    );
});

/** The chosen enrollment number is already held by a different employee. */
const takenBy = computed(() => {
    const match = props.biometricUsers.find(
        (user) => user.device_user_id === selectedId.value,
    );

    if (!match?.employee_id || match.employee_id === props.employee?.id) {
        return null;
    }

    return match.employee_id;
});

function submit(deviceUserId: string | null): void {
    if (!props.employee || saving.value) {
        return;
    }

    saving.value = true;

    router.post(
        `/employees/${props.employee.id}/link-biometric`,
        { device_user_id: deviceUserId },
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
                emit('update:open', false);
            },
        },
    );
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <Fingerprint class="size-5" />
                    </div>
                    <div class="space-y-1">
                        <DialogTitle>Link biometric enrollment</DialogTitle>
                        <DialogDescription>
                            Choose the device user that belongs to
                            <strong>{{ employee?.full_name }}</strong
                            >.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search enrollment no. or biometric name..."
                    class="pl-8"
                />
            </div>

            <div class="max-h-64 space-y-1 overflow-y-auto">
                <button
                    v-for="user in results"
                    :key="user.device_user_id"
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors hover:bg-muted"
                    :class="{ 'bg-muted': user.device_user_id === selectedId }"
                    @click="selectedId = user.device_user_id"
                >
                    <Check
                        class="size-4 shrink-0"
                        :class="
                            user.device_user_id === selectedId
                                ? 'text-primary'
                                : 'invisible'
                        "
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            <span class="font-mono"
                                >#{{ user.device_user_id }}</span
                            >
                            <template v-if="user.device_user_name">
                                — {{ user.device_user_name }}
                            </template>
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ user.punches }} punch{{
                                user.punches === 1 ? '' : 'es'
                            }}
                            <template v-if="user.employee_id">
                                · already linked
                            </template>
                        </p>
                    </div>
                </button>

                <p
                    v-if="results.length === 0"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    <template v-if="biometricUsers.length === 0">
                        No biometric users yet — fetch punches on the Attendance
                        Logs page first.
                    </template>
                    <template v-else>
                        Nothing matches “{{ search }}”.
                    </template>
                </p>
            </div>

            <div
                v-if="takenBy"
                class="flex items-start gap-2 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-xs"
            >
                <TriangleAlert
                    class="mt-0.5 size-4 shrink-0 text-destructive"
                />
                <span>
                    Enrollment <span class="font-mono">#{{ selectedId }}</span>
                    is already linked to another employee. Clear theirs first.
                </span>
            </div>

            <p v-else-if="selectedId" class="text-xs text-muted-foreground">
                Saving attributes every past punch under
                <span class="font-mono">#{{ selectedId }}</span> to
                {{ employee?.full_name }}.
            </p>

            <div class="flex items-center justify-between gap-2">
                <Button
                    v-if="employee?.biometric_id"
                    variant="ghost"
                    class="text-destructive"
                    :disabled="saving"
                    @click="submit(null)"
                >
                    Clear link
                </Button>
                <span v-else />

                <div class="flex items-center gap-2">
                    <Button variant="ghost" @click="emit('update:open', false)">
                        Cancel
                    </Button>
                    <Button
                        :disabled="selectedId === null || saving"
                        @click="submit(selectedId)"
                    >
                        {{ saving ? 'Linking…' : 'Link' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
