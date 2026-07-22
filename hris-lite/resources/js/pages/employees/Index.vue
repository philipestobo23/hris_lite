<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Download,
    Eye,
    Pencil,
    Plus,
    Unlink,
    UserRoundCheck,
    UserRoundX,
} from '@lucide/vue';
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
    BiometricUserOption,
    DataTableColumn,
    DataTableFilters,
    Employee,
    Paginator,
    SelectOption,
} from '@/types';
import LinkBiometricDialog from './LinkBiometricDialog.vue';

const props = defineProps<{
    employees: Paginator<Employee>;
    filters: DataTableFilters;
    branches: { id: number; name: string }[];
    departments: { id: number; name: string; branch_id: number | null }[];
    statuses: SelectOption[];
    biometricUsers: BiometricUserOption[];
    canLink: boolean;
}>();

// Linking an employee to their biometric enrollment.
const linkOpen = ref(false);
const linkTarget = ref<Employee | null>(null);

function openLink(employee: Employee): void {
    linkTarget.value = employee;
    linkOpen.value = true;
}

/** Biometric name for an employee's enrollment number, when known. */
const biometricNames = computed<Record<string, string>>(() =>
    Object.fromEntries(
        props.biometricUsers
            .filter((user) => user.device_user_name)
            .map((user) => [
                user.device_user_id,
                user.device_user_name as string,
            ]),
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Employees', href: '/employees' }],
    },
});

const columns: DataTableColumn[] = [
    { key: 'employee_no', label: 'Employee no.', sortable: true },
    { key: 'name', label: 'Name' },
    { key: 'department', label: 'Department' },
    { key: 'position', label: 'Position' },
    { key: 'branch', label: 'Branch' },
    { key: 'biometric', label: 'Linked' },
    { key: 'employment_status', label: 'Status', sortable: true },
    { key: 'actions', label: '', align: 'right' },
];

const ALL = 'all';

const branchFilter = ref<string>(
    props.filters.branch_id ? String(props.filters.branch_id) : '',
);
const departmentFilter = ref<string>(
    props.filters.department_id ? String(props.filters.department_id) : '',
);
const statusFilter = ref<string>(
    props.filters.status ? String(props.filters.status) : '',
);

// Department options follow the selected branch (plus shared departments).
const availableDepartments = computed(() =>
    branchFilter.value === ''
        ? props.departments
        : props.departments.filter(
              (d) =>
                  d.branch_id === Number(branchFilter.value) ||
                  d.branch_id === null,
          ),
);

function onBranchChange(value: string): void {
    branchFilter.value = value === ALL ? '' : value;

    if (
        departmentFilter.value !== '' &&
        !availableDepartments.value.some(
            (d) => d.id === Number(departmentFilter.value),
        )
    ) {
        departmentFilter.value = '';
    }
}

const branchModel = computed({
    get: () => (branchFilter.value === '' ? ALL : branchFilter.value),
    set: onBranchChange,
});
const departmentModel = computed({
    get: () => (departmentFilter.value === '' ? ALL : departmentFilter.value),
    set: (v: string) => (departmentFilter.value = v === ALL ? '' : v),
});
const statusModel = computed({
    get: () => (statusFilter.value === '' ? ALL : statusFilter.value),
    set: (v: string) => (statusFilter.value = v === ALL ? '' : v),
});

const extraParams = computed(() => ({
    branch_id: branchFilter.value || undefined,
    department_id: departmentFilter.value || undefined,
    status: statusFilter.value || undefined,
}));

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (props.filters.search) {
        params.set('search', String(props.filters.search));
    }

    if (branchFilter.value) {
        params.set('branch_id', branchFilter.value);
    }

    if (departmentFilter.value) {
        params.set('department_id', departmentFilter.value);
    }

    if (statusFilter.value) {
        params.set('status', statusFilter.value);
    }

    const qs = params.toString();

    return `/employees/export${qs ? `?${qs}` : ''}`;
});

const inactiveStatuses = ['resigned', 'terminated'];
function isActive(employee: Employee): boolean {
    return !inactiveStatuses.includes(employee.employment_status);
}

function statusLabel(value: string): string {
    return value.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function toggleStatus(employee: Employee): void {
    const verb = isActive(employee) ? 'Deactivate' : 'Activate';

    if (confirm(`${verb} ${employee.full_name}?`)) {
        router.patch(
            `/employees/${employee.id}/toggle-status`,
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head title="Employees" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Employee Master List"
                description="Search, filter, and manage employee records."
            />
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <a :href="exportUrl">
                        <Download class="size-4" />
                        Export to Excel
                    </a>
                </Button>
                <Button as-child>
                    <Link href="/employees/create">
                        <Plus class="size-4" />
                        New employee
                    </Link>
                </Button>
            </div>
        </div>

        <DataTable
            :paginator="employees"
            :filters="filters"
            :columns="columns"
            :only="['employees', 'filters']"
            :extra-params="extraParams"
            search-placeholder="Search name or employee no..."
            empty-text="No employees match your filters."
        >
            <template #toolbar>
                <Select v-model="branchModel">
                    <SelectTrigger class="w-40"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All branches</SelectItem>
                        <SelectItem
                            v-for="b in branches"
                            :key="b.id"
                            :value="String(b.id)"
                            >{{ b.name }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <Select v-model="departmentModel">
                    <SelectTrigger class="w-44"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All departments</SelectItem>
                        <SelectItem
                            v-for="d in availableDepartments"
                            :key="d.id"
                            :value="String(d.id)"
                            >{{ d.name }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <Select v-model="statusModel">
                    <SelectTrigger class="w-40"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="ALL">All statuses</SelectItem>
                        <SelectItem
                            v-for="s in statuses"
                            :key="s.value"
                            :value="s.value"
                            >{{ s.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
            </template>

            <template #cell-name="{ row }">
                <Link
                    :href="`/employees/${row.id}`"
                    class="flex items-center gap-2 hover:underline"
                >
                    <img
                        v-if="row.photo_url"
                        :src="row.photo_url"
                        alt=""
                        class="size-7 rounded-full object-cover"
                    />
                    <span>{{ row.full_name }}</span>
                </Link>
            </template>

            <template #cell-biometric="{ row }">
                <button
                    v-if="canLink"
                    type="button"
                    class="flex items-center gap-1.5 rounded text-left transition-colors hover:opacity-80"
                    :title="
                        row.biometric_id
                            ? `Linked to biometric #${row.biometric_id} — click to change`
                            : 'Link this employee to a biometric enrollment'
                    "
                    @click="openLink(row)"
                >
                    <template v-if="row.biometric_id">
                        <BadgeCheck
                            class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                        />
                        <span class="leading-tight">
                            <span class="font-mono text-xs">
                                #{{ row.biometric_id }}
                            </span>
                            <span
                                v-if="biometricNames[row.biometric_id]"
                                class="block text-[11px] text-muted-foreground"
                            >
                                {{ biometricNames[row.biometric_id] }}
                            </span>
                        </span>
                    </template>
                    <template v-else>
                        <Unlink
                            class="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <span class="text-xs text-muted-foreground">
                            Not linked
                        </span>
                    </template>
                </button>

                <!-- Read-only view for users without employees.edit. -->
                <span v-else-if="row.biometric_id" class="font-mono text-xs">
                    #{{ row.biometric_id }}
                </span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-department="{ row }">
                <span v-if="row.department">{{ row.department.name }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-position="{ row }">
                <span v-if="row.position">{{ row.position.name }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-branch="{ row }">
                <span v-if="row.branch">{{ row.branch.name }}</span>
                <span v-else class="text-muted-foreground">—</span>
            </template>

            <template #cell-employment_status="{ row }">
                <Badge :variant="isActive(row) ? 'default' : 'secondary'">
                    {{ statusLabel(row.employment_status) }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-1">
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link
                            :href="`/employees/${row.id}`"
                            title="View 201 file"
                        >
                            <Eye class="size-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon-sm" as-child>
                        <Link :href="`/employees/${row.id}/edit`" title="Edit">
                            <Pencil class="size-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :title="isActive(row) ? 'Deactivate' : 'Activate'"
                        @click="toggleStatus(row)"
                    >
                        <UserRoundX
                            v-if="isActive(row)"
                            class="size-4 text-destructive"
                        />
                        <UserRoundCheck
                            v-else
                            class="size-4 text-emerald-600"
                        />
                    </Button>
                </div>
            </template>
        </DataTable>

        <LinkBiometricDialog
            v-model:open="linkOpen"
            :employee="linkTarget"
            :biometric-users="biometricUsers"
        />
    </div>
</template>
